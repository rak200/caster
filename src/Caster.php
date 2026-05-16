<?php

declare(strict_types=1);

namespace Ricardo\Caster;

use Ricardo\Caster\Contracts\Castable;
use Ricardo\Caster\Contracts\ToArray;
use Ricardo\Caster\Contracts\ToBool;
use Ricardo\Caster\Contracts\ToFloat;
use Ricardo\Caster\Contracts\ToInt;
use Ricardo\Caster\Contracts\ToJson;
use Ricardo\Caster\Contracts\ToString;
use InvalidArgumentException;
use Stringable;

final class Caster {

    /**
     * Convert a value to a string.
     * @param mixed $value
     * @return string
     */
    public static function toString(mixed $value): string {
        return match (true) {
            is_string($value) => $value,
            is_int($value) || is_float($value) || $value instanceof Stringable => (string) $value,
            $value instanceof ToInt || $value instanceof ToFloat => (string) static::cast($value),
            is_bool($value) => $value ? 'true' : 'false',
            $value instanceof ToBool => $value->toBool() ? 'true' : 'false',
            is_array($value) || is_object($value) => static::toJson($value),
            default => throw new InvalidArgumentException('cant stringify ' . get_debug_type($value)),
        };
    }

    /**
     * Convert a Castable object to its typed value.
     * @param Castable $value
     * @return string|int|float|bool|array
     * @throws InvalidArgumentException
     */
    public static function cast(Castable $value): string|int|float|bool|array {
        return match (true) {
            $value instanceof ToJson => $value->toJson(),
            $value instanceof ToString => $value->__toString(),
            $value instanceof ToInt => $value->toInt(),
            $value instanceof ToFloat => $value->toFloat(),
            $value instanceof ToBool => $value->toBool(),
            $value instanceof ToArray => $value->toArray(),
            default => throw new InvalidArgumentException('cant cast ' . get_debug_type($value)),
        };
    }

    /**
     * Convert a value to a JSON string.
     * @param mixed $value
     * @param int $flags flags for json_encode, default is JSON_PRETTY_PRINT
     * @return string
     */
    public static function toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string {
        if ($value instanceof ToJson) {
            return $value->toJson();
        } else if ($value instanceof Castable) {
            $value = static::cast($value);
        }
        return json_encode($value, $flags | JSON_THROW_ON_ERROR);
    }
}
