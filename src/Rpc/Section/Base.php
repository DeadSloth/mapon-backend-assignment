<?php

declare(strict_types=1);

namespace App\Rpc\Section;

use stdClass;

/**
 * Base class for all RPC method handlers.
 *
 * Subclasses must implement:
 *   - process(): Execute the method and return result
 *
 * Subclasses may override:
 *   - AUTH: Set to false for public endpoints
 *   - validate(): Add custom validation after construction
 *
 * Parameter validation:
 *   Use requireParam() and optionalParam() in constructor to validate input.
 *
 * Example (src/Rpc/Section/Transaction/GetList.php):
 *
 *   namespace App\Rpc\Section\Transaction;
 *
 *   class GetList extends Base {
 *       public const AUTH = true;
 *       private int $limit;
 *
 *       public function __construct(stdClass $params) {
 *           parent::__construct($params);
 *           $this->limit = $this->optionalParam('limit', 'int', 100);
 *       }
 *
 *       public function process(): array {
 *           return ['items' => [...]];
 *       }
 *   }
 *
 * RPC method name: "Transaction__GetList" -> App\Rpc\Section\Transaction\GetList
 */
abstract class Base
{
    /**
     * Whether this method requires authentication.
     */
    public const AUTH = true;

    protected stdClass $params;

    public function __construct(stdClass $params)
    {
        $this->params = $params;
    }

    /**
     * Execute the method. Must return a JSON-serializable result.
     */
    abstract public function process(): mixed;

    /**
     * Override for custom validation.
     * Throw InvalidArgumentException on validation failure.
     * Called by RPC dispatcher after construction.
     */
    public function validate(): void
    {
        // Override in subclass if needed
    }

    /**
     * Get a required parameter with type coercion.
     *
     * @throws \InvalidArgumentException if parameter is missing or wrong type
     */
    protected function requireParam(string $name, string $type): mixed
    {
        if (!isset($this->params->{$name})) {
            throw new \InvalidArgumentException("Missing required parameter: {$name}");
        }

        return $this->castParam($name, $this->params->{$name}, $type);
    }

    /**
     * Get an optional parameter with type coercion and default value.
     */
    protected function optionalParam(string $name, string $type, mixed $default = null): mixed
    {
        if (!isset($this->params->{$name})) {
            return $default;
        }

        return $this->castParam($name, $this->params->{$name}, $type);
    }

    /**
     * Cast parameter to expected type.
     */
    private function castParam(string $name, mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => $this->castInt($name, $value),
            'float' => $this->castFloat($name, $value),
            'string' => $this->castString($name, $value),
            'bool' => $this->castBool($name, $value),
            'array' => $this->castArray($name, $value),
            default => $value,
        };
    }

    private function castInt(string $name, mixed $value): int
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Parameter {$name} must be an integer");
        }
        return (int) $value;
    }

    private function castFloat(string $name, mixed $value): float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Parameter {$name} must be a number");
        }
        return (float) $value;
    }

    private function castString(string $name, mixed $value): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            throw new \InvalidArgumentException("Parameter {$name} must be a string");
        }
        return (string) $value;
    }

    private function castBool(string $name, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }
        if ($value === 0 || $value === '0' || $value === 'false') {
            return false;
        }
        throw new \InvalidArgumentException("Parameter {$name} must be a boolean");
    }

    private function castArray(string $name, mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value instanceof \stdClass) {
            return (array) $value;
        }
        throw new \InvalidArgumentException("Parameter {$name} must be an array");
    }

    /**
     * Throw a user-friendly error.
     */
    protected function throwError(string $message): never
    {
        throw new \RuntimeException($message);
    }
}
