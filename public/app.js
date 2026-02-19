/**
 * Fuel Transactions App
 *
 * Simple vanilla JS frontend for the Fuel API.
 */

const API_BASE = '/rpc';
const INTERNAL_API_KEY = 'test-api-key-12345';
vehicleFilterInitialized = false;

// DOM Elements
const elements = {
    importForm: document.getElementById('import-form'),
    importBtn: document.getElementById('import-btn'),
    importResult: document.getElementById('import-result'),
    csvFileInput: document.getElementById('csv-file'),
    refreshBtn: document.getElementById('refresh-btn'),
    clearDbBtn: document.getElementById('clear-db-btn'),
    enrichAllBtn: document.getElementById('enrich-all-btn'),
    transactionsTable: document.getElementById('transactions-table'),
    transactionsBody: document.getElementById('transactions-body'),
    transactionsLoading: document.getElementById('transactions-loading'),
    transactionsEmpty: document.getElementById('transactions-empty'),
    pagination: document.getElementById('pagination'),
    paginationInfo: document.getElementById('pagination-info'),
    vehicleFilter: document.getElementById('vehicle-filter'),
    orderBy: document.getElementById('order-by'),
};

/**
 * Make an RPC call to the API.
 *
 * @param {string} method - Method name in format "Section__Action" (e.g., "Transaction__GetList")
 * @param {object} params - Request parameters
 */
async function rpc(method, params = {}) {
    // Convert "Transaction__GetList" to "/rpc/transaction/getList"
    const [section, action] = method.split('__');
    const url = `${API_BASE}/${section.toLowerCase()}/${action.charAt(0).toLowerCase() + action.slice(1)}`;

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${INTERNAL_API_KEY}`,
        },
        body: JSON.stringify(params),
    });

    const data = await response.json();

    if (data.error) {
        throw new Error(data.error);
    }

    return data.result;
}

/**
 * Load and display transactions.
 */
async function loadTransactions() {
    showLoading(true);
    hideElement(elements.transactionsTable);
    hideElement(elements.transactionsEmpty);
    hideElement(elements.pagination);

    try {
        const result = await rpc('Transaction__GetList', {
            limit: 100,
            vehicle_number: elements.vehicleFilter.value || undefined,
            order_by: elements.orderBy.value || undefined,
        });

        if (result.items.length === 0) {
            showElement(elements.transactionsEmpty);
        } else {
            renderTransactions(result.items);
            showElement(elements.transactionsTable);

            // Show pagination info
            elements.paginationInfo.textContent = `Showing ${result.items.length} of ${result.total} transactions`;
            showElement(elements.pagination);
        }
    } catch (error) {
        console.error('Failed to load transactions:', error);
        showError('Failed to load transactions: ' + error.message);
    } finally {
        showLoading(false);
    }
}

/**
 * Render transactions into the table.
 */
function renderTransactions(transactions) {
    elements.transactionsBody.innerHTML = transactions.map(t => `
        <tr>
            <td>${formatDate(t.transaction_date)}</td>
            <td>${escapeHtml(t.vehicle_number || '-')}</td>
            <td><span class="card-number">${maskCardNumber(t.card_number)}</span></td>
            <td>${escapeHtml(t.station_name || '-')}</td>
            <td><span class="product-badge product-${t.product_type}">${t.product_type}</span></td>
            <td class="text-right">${formatQuantity(t.quantity, t.unit)}</td>
            <td class="text-right">${formatAmount(t.total_amount, t.currency)}</td>
            <td class="text-center">${renderEnrichmentStatus(t)}</td>
            <td class="text-center">
                <button class="btn btn-small btn-enrich" onclick="enrichTransaction(${t.id})" ${t.enrichment_status === 'completed' ? 'disabled' : ''}>
                    Enrich
                </button>
            </td>
        </tr>
    `).join('');

    // Update vehicle filter options if not initialized
    if (!vehicleFilterInitialized) {
        updateVehicleFilter(transactions);
        vehicleFilterInitialized = true;
    }
}

/**
 * Render vehicle filter options.
 */
function updateVehicleFilter(transactions) {
    const vehicles = [...new Set(transactions.map(t => t.vehicle_number))];

    const current = elements.vehicleFilter.value;

    elements.vehicleFilter.innerHTML =
        '<option value="">All Vehicles</option>' +
        vehicles.map(v =>
            `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`
        ).join('');

    elements.vehicleFilter.value = current;
}


/**
 * Render enrichment status with GPS data if available.
 */
function renderEnrichmentStatus(transaction) {
    const status = transaction.enrichment_status;

    if (status === 'completed' && transaction.gps_latitude && transaction.gps_longitude) {
        const lat = transaction.gps_latitude.toFixed(4);
        const lng = transaction.gps_longitude.toFixed(4);
        const odometer = transaction.odometer_gps ? `${Math.round(transaction.odometer_gps)} km` : '';
        return `<span class="gps-data" title="Odometer: ${odometer}">
            <span class="status-icon status-completed">&#10003;</span>
            <span class="gps-coords">${lat}, ${lng}</span>
        </span>`;
    }

    const icons = {
        completed: '<span class="status-icon status-completed" title="Enriched">&#10003;</span>',
        pending: '<span class="status-icon status-pending" title="Pending">&#8230;</span>',
        failed: '<span class="status-icon status-failed" title="Failed">&#10007;</span>',
        not_found: '<span class="status-icon status-not-found" title="No GPS data found">?</span>',
    };
    return icons[status] || icons.pending;
}

/**
 * Handle CSV import.
 */
async function handleImport(event) {
    event.preventDefault();

    const file = elements.csvFileInput.files[0];
    if (!file) {
        showImportResult('Please select a CSV file', 'error');
        return;
    }

    setImportLoading(true);
    hideElement(elements.importResult);

    try {
        const csvData = await readFile(file);
        const result = await rpc('Transaction__Import', {
            csv_data: csvData,
        });

        if (result.failed === 0 && result.imported > 0) {
            showImportResult(
                `Successfully imported ${result.imported} transaction(s).`,
                'success'
            );
        } else if (result.imported > 0 && result.failed > 0) {
            showImportResult(
                `Imported ${result.imported} transaction(s). ${result.failed} row(s) failed.`,
                'partial',
                result.errors
            );
        } else if (result.imported === 0) {
            showImportResult(
                `Import failed. ${result.failed} row(s) had errors.`,
                'error',
                result.errors
            );
        }

        // Refresh the transaction list
        await loadTransactions();

        // Reset the file input
        elements.csvFileInput.value = '';
        updateFileInputLabel();

    } catch (error) {
        console.error('Import failed:', error);
        showImportResult('Import failed: ' + error.message, 'error');
    } finally {
        setImportLoading(false);
    }
}

/**
 * Show import result message.
 */
function showImportResult(message, type, errors = []) {
    elements.importResult.className = `import-result ${type}`;
    let html = '<button class="dismiss-btn" onclick="dismissImportResult()">&times;</button>';
    html += escapeHtml(message);

    if (errors.length > 0) {
        html += '<div class="import-errors"><strong>Errors:</strong><ul>';
        errors.forEach(err => {
            html += `<li>${escapeHtml(err)}</li>`;
        });
        html += '</ul></div>';
    }

    elements.importResult.innerHTML = html;
    showElement(elements.importResult);
}

/**
 * Dismiss the import result message.
 */
function dismissImportResult() {
    hideElement(elements.importResult);
}

/**
 * Read file as text.
 */
function readFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(new Error('Failed to read file'));
        reader.readAsText(file);
    });
}

// Utility functions

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatQuantity(quantity, unit) {
    return `${quantity.toFixed(2)} ${unit || 'L'}`;
}

function formatAmount(amount, currency) {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: currency || 'EUR',
    }).format(amount);
}

function maskCardNumber(cardNumber) {
    if (!cardNumber || cardNumber.length < 4) return cardNumber || '-';
    return '**** ' + cardNumber.slice(-4);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showElement(el) {
    if (el) el.hidden = false;
}

function hideElement(el) {
    if (el) el.hidden = true;
}

function showLoading(show) {
    if (show) {
        showElement(elements.transactionsLoading);
    } else {
        hideElement(elements.transactionsLoading);
    }
}

function setImportLoading(loading) {
    elements.importBtn.disabled = loading;
    elements.importBtn.querySelector('.btn-text').hidden = loading;
    elements.importBtn.querySelector('.btn-loading').hidden = !loading;
}

function showError(message) {
    // Simple error display - could be improved
    alert(message);
}

/**
 * Update file input label with selected filename.
 */
function updateFileInputLabel() {
    const fileName = elements.csvFileInput.files[0]?.name || 'No file chosen';
    const fileNameEl = document.querySelector('.file-input-name');
    if (fileNameEl) {
        fileNameEl.textContent = fileName;
    }
}

/**
 * Enrich a single transaction with GPS data.
 */
async function enrichTransaction(id) {
    try {
        const result = await rpc('Transaction__Enrich', { id });

        if (result.success) {
            // Refresh the list to show updated status
            await loadTransactions();
        } else {
            showError('Enrichment failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Enrichment failed:', error);
        showError('Enrichment failed: ' + error.message);
    }
}

/**
 * Clear all transactions from the database.
 */
async function handleClearDb() {
    if (!confirm('Are you sure you want to delete all transactions? This cannot be undone.')) {
        return;
    }

    try {
        const result = await rpc('Database__Clear');
        alert(`Deleted ${result.deleted} transaction(s).`);
        await loadTransactions();
    } catch (error) {
        console.error('Clear database failed:', error);
        showError('Failed to clear database: ' + error.message);
    }
}

/**
 * Enrich all pending transactions.
 */
async function handleEnrichAll() {
    elements.enrichAllBtn.disabled = true;
    elements.enrichAllBtn.textContent = 'Enriching...';

    try {
        const result = await rpc('Transaction__EnrichAll', { limit: 20 });

        const message = `Enrichment complete:\n` +
            `- Completed: ${result.completed}\n` +
            `- Not found: ${result.not_found}\n` +
            `- Failed: ${result.failed}\n` +
            `- Skipped: ${result.skipped}`;

        alert(message);
        console.log('Enrich all results:', result);

        await loadTransactions();
    } catch (error) {
        console.error('Enrich all failed:', error);
        showError('Enrich all failed: ' + error.message);
    } finally {
        elements.enrichAllBtn.disabled = false;
        elements.enrichAllBtn.textContent = 'Enrich All';
    }
}

// Event listeners
elements.importForm.addEventListener('submit', handleImport);
elements.refreshBtn.addEventListener('click', loadTransactions);
elements.clearDbBtn.addEventListener('click', handleClearDb);
elements.enrichAllBtn.addEventListener('click', handleEnrichAll);
elements.csvFileInput.addEventListener('change', updateFileInputLabel);
elements.vehicleFilter.addEventListener('change', loadTransactions);
elements.orderBy.addEventListener('change', loadTransactions);

// Initial load
document.addEventListener('DOMContentLoaded', loadTransactions);
