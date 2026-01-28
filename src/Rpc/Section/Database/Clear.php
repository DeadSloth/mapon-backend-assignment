<?php

declare(strict_types=1);

namespace App\Rpc\Section\Database;

use App\Lib\DB;
use App\Rpc\Section\Base;

/**
 * Clear all transactions from the database.
 *
 * For development/testing purposes only.
 */
class Clear extends Base
{
    public const AUTH = true;

    public function process(): array
    {
        $pdo = DB::connection();

        $countStmt = $pdo->query('SELECT COUNT(*) FROM transactions');
        $count = (int) $countStmt->fetchColumn();

        /** @noinspection SqlWithoutWhere */
        $pdo->exec('DELETE FROM transactions');

        return [
            'deleted' => $count,
        ];
    }
}
