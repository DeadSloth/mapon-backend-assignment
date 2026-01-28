<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Base model class with simple active record pattern.
 *
 * Subclasses must define:
 *   - const TABLE = 'table_name';
 *   - const PRIMARY_KEY = 'id';
 *   - const FIELDS = ['field1', 'field2', ...];
 *
 * Features:
 *   - Automatic change tracking via $_changed
 *   - Simple get/getArray/save/delete operations
 *   - Supports both insert (new) and update (existing) via save()
 *
 * Usage:
 *   $transaction = Transaction::get(123);
 *   $transaction->amount = 50.00;
 *   $transaction->save();
 *
 *   $new = new Transaction();
 *   $new->car_id = 1;
 *   $new->amount = 100.00;
 *   $new->save(); // inserts, sets $new->id
 */
abstract class Model
{
    public const TABLE = '';
    public const PRIMARY_KEY = 'id';
    public const FIELDS = [];

    protected ?int $id = null;
    protected array $_data = [];
    protected array $_changed = [];

    public function __construct(?array $data = null)
    {
        if ($data !== null) {
            $this->hydrate($data);
        }
    }

    /**
     * Get a single record by primary key.
     */
    public static function get(int $id): ?static
    {
        $table = static::TABLE;
        $pk = static::PRIMARY_KEY;

        $row = DB::queryOne("SELECT * FROM `{$table}` WHERE `{$pk}` = :id", ['id' => $id]);

        if ($row === null) {
            return null;
        }

        return new static($row);
    }

    /**
     * Get multiple records by primary keys.
     */
    public static function getArray(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $table = static::TABLE;
        $pk = static::PRIMARY_KEY;

        // Build placeholder list for IN clause
        $placeholders = [];
        $params = [];
        foreach (array_values($ids) as $i => $id) {
            $placeholders[] = ":id{$i}";
            $params["id{$i}"] = $id;
        }

        $sql = "SELECT * FROM `{$table}` WHERE `{$pk}` IN (" . implode(', ', $placeholders) . ")";
        $rows = DB::query($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[$row[$pk]] = new static($row);
        }

        return $result;
    }

    /**
     * Get all records, optionally with a WHERE clause.
     */
    public static function getAll(?string $where = null, array $params = [], ?string $orderBy = null, ?int $limit = null): array
    {
        $table = static::TABLE;

        $sql = "SELECT * FROM `{$table}`";

        if ($where !== null) {
            $sql .= " WHERE {$where}";
        }

        if ($orderBy !== null) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        $rows = DB::query($sql, $params);

        return array_map(fn($row) => new static($row), $rows);
    }

    /**
     * Populate model from database row.
     */
    protected function hydrate(array $data): void
    {
        $pk = static::PRIMARY_KEY;

        if (isset($data[$pk])) {
            $this->id = (int) $data[$pk];
        }

        foreach (static::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $this->_data[$field] = $data[$field];
            }
        }

        $this->_changed = [];
    }

    /**
     * Save the model (insert or update).
     */
    public function save(): bool
    {
        if ($this->id === null) {
            return $this->insert();
        }

        return $this->update();
    }

    protected function insert(): bool
    {
        $table = static::TABLE;
        $fields = [];
        $placeholders = [];
        $params = [];

        foreach ($this->_data as $field => $value) {
            $fields[] = "`{$field}`";
            $placeholders[] = ":{$field}";
            $params[$field] = $value;
        }

        $sql = "INSERT INTO `{$table}` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $this->id = DB::insert($sql, $params);
        $this->_changed = [];

        return $this->id > 0;
    }

    protected function update(): bool
    {
        if (empty($this->_changed)) {
            return true; // Nothing to update
        }

        $table = static::TABLE;
        $pk = static::PRIMARY_KEY;
        $sets = [];
        $params = ['id' => $this->id];

        foreach ($this->_changed as $field) {
            $sets[] = "`{$field}` = :{$field}";
            $params[$field] = $this->_data[$field];
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE `{$pk}` = :id";

        DB::execute($sql, $params);
        $this->_changed = [];

        return true;
    }

    /**
     * Delete this record.
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $table = static::TABLE;
        $pk = static::PRIMARY_KEY;

        DB::execute("DELETE FROM `{$table}` WHERE `{$pk}` = :id", ['id' => $this->id]);

        return true;
    }

    /**
     * Check if this is a new (unsaved) record.
     */
    public function isNew(): bool
    {
        return $this->id === null;
    }

    /**
     * Get changed field names.
     */
    public function getChangedFields(): array
    {
        return $this->_changed;
    }

    /**
     * Magic getter for id and fields.
     */
    public function __get(string $name): mixed
    {
        if ($name === 'id') {
            return $this->id;
        }

        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        return null;
    }

    /**
     * Magic setter with change tracking.
     */
    public function __set(string $name, mixed $value): void
    {
        if ($name === 'id') {
            $this->id = $value;
            return;
        }

        if (!in_array($name, static::FIELDS, true)) {
            throw new \InvalidArgumentException("Unknown field: {$name}");
        }

        $oldValue = $this->_data[$name] ?? null;
        $this->_data[$name] = $value;

        if ($oldValue !== $value && !in_array($name, $this->_changed, true)) {
            $this->_changed[] = $name;
        }
    }

    /**
     * Magic isset for fields.
     */
    public function __isset(string $name): bool
    {
        return $name === 'id' || array_key_exists($name, $this->_data);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        $pk = static::PRIMARY_KEY;
        return [$pk => $this->id] + $this->_data;
    }
}
