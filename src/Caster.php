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
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Stringable;
use Traversable;

use function is_string, is_int, is_float, is_bool, is_array, is_object, is_a, is_subclass_of, is_numeric, get_debug_type, json_encode;

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
     * Convert any value to an integer.
     *
     * @param mixed $value The value to convert.
     * @return int The integer representation of $value.
     * @throws InvalidArgumentException When $value cannot be converted.
     */
    public static function toInt(mixed $value): int {
        return match (true) {
            is_int($value) => $value,
            $value instanceof ToInt => $value->toInt(),
            $value instanceof ToFloat => (int) $value->toFloat(),
            $value instanceof ToNumber => (int) (string) $value->toNumber(),
            $value instanceof ToBool => $value->toBool() ? 1 : 0,
            $value instanceof ToDateTime => $value->toDateTime()->getTimestamp(),
            $value instanceof ToEnum => (int) $value->toEnum()->value,
            is_float($value) || is_bool($value) => (int) $value,
            is_string($value) => (int) $value,
            $value instanceof Stringable => (int) (string) $value,
            default => throw new InvalidArgumentException('Cannot convert ' . get_debug_type($value) . ' to int'),
        };
    }

    /**
     * Convert any value to a float.
     *
     * @param mixed $value The value to convert.
     * @return float The float representation of $value.
     * @throws InvalidArgumentException When $value cannot be converted.
     */
    public static function toFloat(mixed $value): float {
        return match (true) {
            is_float($value) => $value,
            $value instanceof ToFloat => $value->toFloat(),
            $value instanceof ToInt => (float) $value->toInt(),
            $value instanceof ToNumber => (float) (string) $value->toNumber(),
            $value instanceof ToBool => $value->toBool() ? 1.0 : 0.0,
            $value instanceof ToDateTime => (float) $value->toDateTime()->format('U.u'),
            $value instanceof ToEnum => (float) $value->toEnum()->value,
            is_int($value) || is_bool($value) => (float) $value,
            is_string($value) => (float) $value,
            $value instanceof Stringable => (float) (string) $value,
            default => throw new InvalidArgumentException('Cannot convert ' . get_debug_type($value) . ' to float'),
        };
    }

    /**
     * Convert any value to a boolean.
     *
     * @param mixed $value The value to convert.
     * @return bool The boolean representation of $value.
     * @throws InvalidArgumentException When $value cannot be converted.
     */
    public static function toBool(mixed $value): bool {
        return match (true) {
            is_bool($value) => $value,
            $value instanceof ToBool => $value->toBool(),
            $value instanceof ToInt => (bool) $value->toInt(),
            $value instanceof ToFloat => (bool) $value->toFloat(),
            $value instanceof ToNumber => (bool) (string) $value->toNumber(),
            is_int($value) || is_float($value) => (bool) $value,
            is_string($value) => (bool) $value,
            $value instanceof Stringable => (bool) (string) $value,
            is_array($value) => $value !== [],
            $value instanceof ToArray => $value->toArray() !== [],
            $value instanceof ToCollection => [...$value->toCollection()] !== [],
            default => throw new InvalidArgumentException('Cannot convert ' . get_debug_type($value) . ' to bool'),
        };
    }

    /**
     * Convert any value to an array.
     *
     * @param mixed $value The value to convert.
     * @return array<mixed> The array representation of $value.
     * @throws InvalidArgumentException When $value cannot be converted.
     */
    public static function toArray(mixed $value): array {
        return match (true) {
            is_array($value) => $value,
            $value instanceof ToArray => $value->toArray(),
            $value instanceof ToCollection => [...$value->toCollection()],
            $value instanceof Traversable => [...$value],
            default => throw new InvalidArgumentException('Cannot convert ' . get_debug_type($value) . ' to array'),
        };
    }

    /**
     * Convert any value to a BcMath\Number.
     *
     * @param mixed $value The value to convert.
     * @return Number The arbitrary-precision number representation of $value.
     * @throws InvalidArgumentException When $value cannot be converted.
     */
    public static function toNumber(mixed $value): Number {
        return match (true) {
            $value instanceof Number => $value,
            $value instanceof ToNumber => $value->toNumber(),
            $value instanceof ToInt => new Number((string) $value->toInt()),
            $value instanceof ToFloat => new Number((string) $value->toFloat()),
            $value instanceof ToBool => new Number($value->toBool() ? '1' : '0'),
            $value instanceof ToEnum => static::numberFromString((string) $value->toEnum()->value),
            is_int($value) || is_float($value) => new Number((string) $value),
            is_bool($value) => new Number($value ? '1' : '0'),
            is_string($value) => static::numberFromString($value),
            $value instanceof Stringable => static::numberFromString((string) $value),
            default => throw new InvalidArgumentException('Cannot convert ' . get_debug_type($value) . ' to Number'),
        };
    }

    /**
     * Build a BcMath\Number from a string after verifying it is numeric.
     *
     * @throws InvalidArgumentException When $value is not a numeric string.
     */
    private static function numberFromString(string $value): Number {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Cannot convert non-numeric string '$value' to Number");
        }
        return new Number($value);
    }

    /**
     * Convert any value to a DateTimeImmutable.
     *
     * Integer values are interpreted as Unix timestamps.
     *
     * @param mixed $value The value to convert.
     * @return DateTimeImmutable The immutable date-time representation of $value.
     * @throws InvalidArgumentException When $value cannot be converted.
     */
    public static function toDateTime(mixed $value): DateTimeImmutable {
        return match (true) {
            $value instanceof DateTimeImmutable => $value,
            $value instanceof DateTime => DateTimeImmutable::createFromMutable($value),
            $value instanceof ToDateTime => $value->toDateTime(),
            $value instanceof ToInt => new DateTimeImmutable('@' . $value->toInt()),
            is_int($value) => new DateTimeImmutable('@' . $value),
            is_string($value) => new DateTimeImmutable($value),
            $value instanceof Stringable => new DateTimeImmutable((string) $value),
            default => throw new InvalidArgumentException('Cannot convert ' . get_debug_type($value) . ' to DateTimeImmutable'),
        };
    }

    /**
     * Convert any value to a case of the given BackedEnum.
     *
     * @template T of BackedEnum
     * @param mixed $value The value to convert.
     * @param class-string<T> $enumClass The target BackedEnum class.
     * @return T The matching enum case.
     * @throws InvalidArgumentException When $enumClass is not a BackedEnum, or $value cannot be converted.
     * @throws \ValueError When the underlying value does not match any case of $enumClass.
     */
    public static function toEnum(mixed $value, string $enumClass): BackedEnum {
        if (!is_subclass_of($enumClass, BackedEnum::class)) {
            throw new InvalidArgumentException($enumClass . ' is not a BackedEnum');
        }
        return match (true) {
            is_a($value, $enumClass) => $value,
            $value instanceof ToEnum && is_a($value->toEnum(), $enumClass) => $value->toEnum(),
            $value instanceof ToInt => $enumClass::from($value->toInt()),
            is_int($value) || is_string($value) => $enumClass::from($value),
            $value instanceof Stringable => $enumClass::from((string) $value),
            default => throw new InvalidArgumentException('Cannot convert ' . get_debug_type($value) . ' to ' . $enumClass),
        };
    }

    /**
     * Convert any value to an iterable.
     *
     * @param mixed $value The value to convert.
     * @return iterable<mixed> The iterable representation of $value.
     * @throws InvalidArgumentException When $value cannot be converted.
     */
    public static function toCollection(mixed $value): iterable {
        return match (true) {
            is_array($value) => $value,
            $value instanceof Traversable => $value,
            $value instanceof ToCollection => $value->toCollection(),
            $value instanceof ToArray => $value->toArray(),
            default => throw new InvalidArgumentException('Cannot convert ' . get_debug_type($value) . ' to iterable'),
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
