<?php

declare(strict_types=1);

namespace Rak200\Caster;

use Rak200\Caster\Contracts\Castable;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToCollection;
use Rak200\Caster\Contracts\ToDateTime;
use Rak200\Caster\Contracts\ToEnum;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToJson;
use Rak200\Caster\Contracts\ToNumber;
use Rak200\Caster\Contracts\ToString;
use BackedEnum;
use BcMath\Number;
use DateTimeImmutable;
use InvalidArgumentException;
use Stringable;
use Traversable;

use function is_string, is_int, is_float, is_bool, is_array, is_object, get_debug_type, json_encode;

/**
 * Static utility class for converting values between PHP types.
 *
 * Dispatches to the appropriate contract method when the value implements
 * one of the Castable contracts, and falls back to native PHP coercions
 * for primitives.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Caster {

    /**
     * Convert any value to a string.
     *
     * Resolution order:
     *  - string                   → returned as-is
     *  - int|float                → (string) cast
     *  - Stringable               → (string) cast via __toString()
     *  - ToInt|ToFloat|ToNumber   → contract value as string
     *  - bool                     → 'true' or 'false'
     *  - ToBool                   → toBool() then 'true' or 'false'
     *  - ToDateTime               → DateTimeImmutable formatted as ISO 8601
     *  - ToEnum                   → BackedEnum backing value as string
     *  - ToCollection             → materialised iterable encoded as JSON
     *  - array|object             → toJson()
     *
     * @param mixed $value The value to convert.
     * @return string The string representation of $value.
     * @throws InvalidArgumentException When $value cannot be stringified (e.g. null, resource).
     */
    public static function toString(mixed $value): string {
        return match (true) {
            is_string($value) => $value,
            is_int($value) || is_float($value) || $value instanceof Stringable => (string) $value,
            $value instanceof ToInt => (string) $value->toInt(),
            $value instanceof ToFloat => (string) $value->toFloat(),
            $value instanceof ToNumber => (string) $value->toNumber(),
            is_bool($value) => $value ? 'true' : 'false',
            $value instanceof ToBool => $value->toBool() ? 'true' : 'false',
            $value instanceof ToDateTime => $value->toDateTime()->format('c'),
            $value instanceof ToEnum => (string) $value->toEnum()->value,
            $value instanceof ToCollection => static::toJson([...$value->toCollection()]),
            is_array($value) || is_object($value) => static::toJson($value),
            default => throw new InvalidArgumentException('Cannot stringify ' . get_debug_type($value)),
        };
    }

    /**
     * Dispatch a Castable object to its typed value.
     *
     * Contract priority (first match wins):
     *  1. ToJson       → toJson()      : string
     *  2. ToString     → __toString()  : string
     *  3. ToNumber     → toNumber()    : \BcMath\Number
     *  4. ToInt        → toInt()       : int
     *  5. ToFloat      → toFloat()     : float
     *  6. ToBool       → toBool()      : bool
     *  7. ToDateTime   → toDateTime()  : \DateTimeImmutable
     *  8. ToEnum       → toEnum()      : \BackedEnum
     *  9. ToCollection → toCollection(): iterable
     * 10. ToArray      → toArray()     : array
     *
     * @param Castable $value An object implementing at least one typed contract.
     * @return string|int|float|bool|array<mixed>|Number|DateTimeImmutable|BackedEnum|Traversable<mixed> The value returned by the matching contract method.
     * @throws InvalidArgumentException When $value implements only the marker Castable interface.
     */
    public static function cast(Castable $value): string|int|float|bool|array|Number|DateTimeImmutable|BackedEnum|Traversable {
        return match (true) {
            $value instanceof ToJson => $value->toJson(),
            $value instanceof ToString => $value->__toString(),
            $value instanceof ToNumber => $value->toNumber(),
            $value instanceof ToInt => $value->toInt(),
            $value instanceof ToFloat => $value->toFloat(),
            $value instanceof ToBool => $value->toBool(),
            $value instanceof ToDateTime => $value->toDateTime(),
            $value instanceof ToEnum => $value->toEnum(),
            $value instanceof ToCollection => $value->toCollection(),
            $value instanceof ToArray => $value->toArray(),
            default => throw new InvalidArgumentException('Cannot cast ' . get_debug_type($value)),
        };
    }

    /**
     * Encode any value as a JSON string.
     *
     * - ToJson objects: delegates directly to toJson(), ignoring $flags.
     * - Other Castable objects: cast() first, then json_encode().
     * - Everything else: json_encode() directly.
     *
     * JSON_THROW_ON_ERROR is always added to $flags, so encoding failures
     * throw a JsonException rather than returning false.
     *
     * @param mixed $value The value to encode.
     * @param int   $flags json_encode() flags. Defaults to JSON_PRETTY_PRINT.
     * @return string A valid JSON string.
     * @throws \JsonException When $value cannot be encoded to JSON.
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
