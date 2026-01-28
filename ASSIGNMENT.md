# PHP Developer - Take-Home Assignment

## Overview

You'll extend an existing fuel transaction API to add GPS enrichment from the Mapon telematics platform.

**Estimated time:** 1-2 hours, possibly quicker

**What we're assessing:**
- Code quality and consistency with existing patterns
- Edge case handling - what happens when things go wrong?
- Architectural thinking - how does this fit into the bigger picture?
- Future vision - what would you improve or do differently?

## Using AI/LLM Tools

**We encourage the use of AI coding assistants** (Claude, ChatGPT, Copilot, Cursor, etc.) for this assignment.

What matters to us:
- **Understanding** - Can you explain your code and the decisions made?
- **Architecture** - Did you think about how this fits into the larger system?
- **Quality** - Is the final result clean, working, and well-structured?

In the follow-up interview, we'll discuss your implementation. Be prepared to explain your approach, trade-offs considered, and how you'd extend or improve it.

## Background

Fleet managers import fuel card transactions into our system. Each transaction shows when and where a vehicle purchased fuel. We want to enrich these transactions with GPS coordinates from Mapon to verify the vehicle's actual location at purchase time.

## Setup

```bash
composer install
cp .env.example .env
php bin/setup.php
php -S localhost:8000 -t public public/router.php
```

Open http://localhost:8000 - you should see an empty transaction list.
**Test the import:** Try importing the sample CSV from `sample-data/fuel_transactions.csv`

## Your Task

### 1. Implement `/rpc/transaction/enrich` endpoint

Create an RPC endpoint that enriches a single transaction with GPS data from the Mapon API.

**Input:**
- `id` (int, required) - Transaction ID to enrich

**Behavior:**
1. Fetch the transaction from database
2. Call Mapon API to get GPS position at the transaction time
3. Update the transaction with coordinates and odometer
4. Update enrichment status appropriately

**Output:** Return the enrichment result with the updated transaction data.

### 2. Implement `/rpc/transaction/enrichAll` endpoint

Create an RPC endpoint that enriches multiple pending transactions in batch.

**Input:**
- `limit` (int, optional) - Maximum number of transactions to process

**Behavior:**
- Process transactions with `pending` enrichment status
- Handle partial failures gracefully
- Consider what happens with rate limits or API errors mid-batch

**Output:** Return a summary of the batch operation results.

### What's Already Provided

- `mapon_unit_id` field is populated from the `vehicles` table during import
- `MaponClient` stub in `src/Domain/Mapon/` (you need to implement the API call)
- `MaponUnitData` DTO for API responses
- `MaponApiException` for error handling
- Transaction model with enrichment helper methods
- `Vehicle` model with lookup helpers

### Mapon API Details

**Documentation:** https://mapon.com/api/

**API Key:** Provided in the assignment PDF

**Endpoint:** `GET /unit_data/history_point.json`

Gets vehicle position and odometer at a specific point in time.

**Parameters:**
- `key` - API key
- `unit_id` - Vehicle unit ID (from `transaction.mapon_unit_id`)
- `datetime` - ISO 8601 timestamp with Z suffix (e.g., `2025-01-15T08:30:00Z`)
- `include[]` - Data to include: `position`, `mileage`

**Example Request:**
```
GET https://mapon.com/api/v1/unit_data/history_point.json?key=YOUR_API_KEY&unit_id=199332&datetime=2025-01-15T08:30:00Z&include[]=position&include[]=mileage
```

**Notes:**
- Datetime must use `Z` suffix for UTC
- Returns `404` if no data found for the requested time
- Returns `401` for invalid API key
- Response contains position coordinates and mileage data - refer to Mapon documentation for structure

### Requirements

1. **Implement `MaponClient`** - Complete the stub to call the Mapon API
2. **Create `Enrich.php` endpoint** - Single transaction enrichment
3. **Create `EnrichAll.php` endpoint** - Batch enrichment with proper error handling
4. **Handle edge cases gracefully** - Think about what can go wrong

### 3. Review the existing codebase

As you work through the implementation, take note of:
- Issues or bugs you encounter in the existing code
- Patterns or conventions you'd change
- Architectural decisions you agree or disagree with
- Anything that surprised you (good or bad)

Write these observations in your `NOTES.md` - we'll discuss them in the follow-up interview.

### Bonus (Optional)

**Duplicate prevention:** The current import allows the same CSV to be imported multiple times, creating duplicate transactions. Implement logic to detect and skip duplicates during import.

Consider: What fields define a "duplicate" transaction?

### Hints

- Look at existing code in `src/Rpc/Section/Transaction/` for patterns
- The transaction model has helper methods you might find useful
- The frontend has buttons to test your endpoints

## Things to Think About

Beyond the implementation, consider these questions (we'll discuss in the interview):

- **Batch processing**: How do you handle failures mid-batch? Stop everything or continue?
- **Reliability**: What happens if the Mapon API is slow, returns errors, or times out?
- **Idempotency**: What if someone calls enrich twice on the same transaction?
- **Testing**: How would you test the Mapon integration without hitting the real API?
- **Code review**: Take a look at the existing import flow (`ImportService`). What would you improve? Are there any issues or patterns you'd change?
- **Codebase structure**: What do you think about the current directory structure and naming conventions? What would you change?

You don't need to implement all of these - just be prepared to discuss your thoughts.

## Submission

1. Create a git repository with your changes
2. Include a brief NOTES.md with:
   - How to test your implementation
   - Any assumptions you made
   - Architectural considerations and trade-offs
   - What you'd improve with more time

## What Happens Next

In the follow-up interview, we'll:
- Walk through your implementation together
- Discuss your design decisions and trade-offs
- Explore the architectural questions above
- Talk about what you liked and didn't like about the codebase

---

Questions? Make reasonable assumptions and note them in NOTES.md.
