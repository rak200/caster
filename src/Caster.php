<?php

declare(strict_types=1);

namespace Rak200\Caster;

use BackedEnum;
use BcMath\Number;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
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
use Rak200\Utils\Enum;
use Rak200\Utils\Json;
use Rak200\Utils\Num;
use Rak200\Utils\Type;
use Stringable;
use Traversable;
use UnitEnum;

/**
 * Static utility class for converting values between PHP types.
 *
 * Dispatches to the appropriate contract method when the value implements
 * one of the Castable contracts, and falls back to native PHP coercions
 * for primitives.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Caster
{
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
     *  - ToEnum                   → BackedEnum backing value, or pure enum case name, as string
     *  - ToCollection             → materialised iterable encoded as JSON
     *  - array|object             → toJson()
     *
     * @param mixed $value the value to convert
     *
     * @return string the string representation of $value
     *
     * @throws InvalidArgumentException When $value cannot be stringified (e.g. null, resource).
     */
    public static function toString(mixed $value): string
    {
        return match (true) {
            Type::isStr($value) => $value,
            Type::isInt($value) || Type::isFloat($value) || $value instanceof Stringable => (string) $value,
            $value instanceof ToInt => (string) $value->toInt(),
            $value instanceof ToFloat => (string) $value->toFloat(),
            $value instanceof ToNumber => (string) $value->toNumber(),
            Type::isBool($value) => $value ? 'true' : 'false',
            $value instanceof ToBool => $value->toBool() ? 'true' : 'false',
            $value instanceof ToDateTime => $value->toDateTime()->format('c'),
            $value instanceof ToEnum => (string) Enum::scalar($value->toEnum()),
            $value instanceof ToCollection => self::toJson([...$value->toCollection()]),
            Type::isArray($value) || Type::isObject($value) => self::toJson($value),
            default => throw new InvalidArgumentException('Cannot stringify ' . Type::of($value)),
        };
    }

    /**
     * Convert any value to an integer.
     *
     * @param mixed $value the value to convert
     *
     * @return int the integer representation of $value
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public static function toInt(mixed $value): int
    {
        return match (true) {
            Type::isInt($value) => $value,
            $value instanceof ToInt => $value->toInt(),
            $value instanceof ToFloat => (int) $value->toFloat(),
            $value instanceof ToNumber => (int) (string) $value->toNumber(),
            $value instanceof ToBool => $value->toBool() ? 1 : 0,
            $value instanceof ToDateTime => $value->toDateTime()->getTimestamp(),
            $value instanceof ToEnum && Enum::isBackedInt($e = $value->toEnum()) => (int) Enum::scalar($e),
            Type::isFloat($value) || Type::isBool($value) => (int) $value,
            Type::isStr($value) => (int) $value,
            $value instanceof Stringable => (int) (string) $value,
            default => throw new InvalidArgumentException('Cannot convert ' . Type::of($value) . ' to int'),
        };
    }

    /**
     * Convert any value to a float.
     *
     * @param mixed $value the value to convert
     *
     * @return float the float representation of $value
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public static function toFloat(mixed $value): float
    {
        return match (true) {
            Type::isFloat($value) => $value,
            $value instanceof ToFloat => $value->toFloat(),
            $value instanceof ToInt => (float) $value->toInt(),
            $value instanceof ToNumber => (float) (string) $value->toNumber(),
            $value instanceof ToBool => $value->toBool() ? 1.0 : 0.0,
            $value instanceof ToDateTime => (float) $value->toDateTime()->format('U.u'),
            $value instanceof ToEnum && Num::is($s = Enum::scalar($value->toEnum())) => Num::parseFloat((string) $s),
            Type::isInt($value) || Type::isBool($value) => (float) $value,
            Type::isStr($value) => (float) $value,
            $value instanceof Stringable => (float) (string) $value,
            default => throw new InvalidArgumentException('Cannot convert ' . Type::of($value) . ' to float'),
        };
    }

    /**
     * Convert any value to a boolean.
     *
     * @param mixed $value the value to convert
     *
     * @return bool the boolean representation of $value
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public static function toBool(mixed $value): bool
    {
        return match (true) {
            Type::isBool($value) => $value,
            $value instanceof ToBool => $value->toBool(),
            $value instanceof ToInt => (bool) $value->toInt(),
            $value instanceof ToFloat => (bool) $value->toFloat(),
            $value instanceof ToNumber => (bool) (string) $value->toNumber(),
            Type::isInt($value) || Type::isFloat($value) => (bool) $value,
            Type::isStr($value) => (bool) $value,
            $value instanceof Stringable => (bool) (string) $value,
            Type::isArray($value) => $value !== [],
            $value instanceof ToArray => $value->toArray() !== [],
            $value instanceof ToCollection => [...$value->toCollection()] !== [],
            default => throw new InvalidArgumentException('Cannot convert ' . Type::of($value) . ' to bool'),
        };
    }

    /**
     * Convert any value to an array.
     *
     * @param mixed $value the value to convert
     *
     * @return array<mixed> the array representation of $value
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public static function toArray(mixed $value): array
    {
        return match (true) {
            Type::isArray($value) => $value,
            $value instanceof ToArray => $value->toArray(),
            $value instanceof ToCollection => [...$value->toCollection()],
            $value instanceof Traversable => [...$value],
            default => throw new InvalidArgumentException('Cannot convert ' . Type::of($value) . ' to array'),
        };
    }

    /**
     * Convert any value to a BcMath\Number.
     *
     * @param mixed $value the value to convert
     *
     * @return Number the arbitrary-precision number representation of $value
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public static function toNumber(mixed $value): Number
    {
        return match (true) {
            $value instanceof Number => $value,
            $value instanceof ToNumber => $value->toNumber(),
            $value instanceof ToInt => new Number((string) $value->toInt()),
            $value instanceof ToFloat => new Number((string) $value->toFloat()),
            $value instanceof ToBool => new Number($value->toBool() ? '1' : '0'),
            $value instanceof ToEnum && Num::is($s = Enum::scalar($value->toEnum())) => Num::parseNumber((string) $s),
            Type::isBool($value) => new Number($value ? '1' : '0'),
            Num::is($value) => Num::parseNumber((string) $value),
            $value instanceof Stringable && Num::is($v = (string) $value) => Num::parseNumber($v),
            default => throw new InvalidArgumentException('Cannot convert ' . Type::of($value) . ' to Number'),
        };
    }

    /**
     * Convert any value to a DateTimeImmutable.
     *
     * Integer values are interpreted as Unix timestamps.
     *
     * @param mixed $value the value to convert
     *
     * @return DateTimeImmutable the immutable date-time representation of $value
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public static function toDateTime(mixed $value): DateTimeImmutable
    {
        return match (true) {
            $value instanceof DateTimeImmutable => $value,
            $value instanceof DateTime => DateTimeImmutable::createFromMutable($value),
            $value instanceof ToDateTime => $value->toDateTime(),
            $value instanceof ToInt => new DateTimeImmutable('@' . $value->toInt()),
            Type::isInt($value) => new DateTimeImmutable('@' . $value),
            Type::isStr($value) => new DateTimeImmutable($value),
            $value instanceof Stringable => new DateTimeImmutable((string) $value),
            default => throw new InvalidArgumentException('Cannot convert ' . Type::of($value) . ' to DateTimeImmutable'),
        };
    }

    /**
     * Convert any value to a case of the given UnitEnum.
     *
     * For backed enums, ints/strings are mapped via $enumClass::from() (the
     * backing value). For pure enums, strings are matched against case names.
     *
     * @template T of UnitEnum
     *
     * @param mixed           $value     the value to convert
     * @param class-string<T> $enumClass the target enum class
     *
     * @return T the matching enum case
     *
     * @throws InvalidArgumentException when $enumClass is not a UnitEnum, or $value cannot be converted
     */
    public static function toEnum(mixed $value, string $enumClass = UnitEnum::class): UnitEnum
    {
        // spread operator to overpass staticMethod.alreadyNarrowedType error from PHPStan
        if (!Type::isA(...[$enumClass, UnitEnum::class])) {
            throw new InvalidArgumentException("{$enumClass} is not a UnitEnum");
        }
        if (Type::isInstance($value, $enumClass)) {
            return $value;
        }
        if ($value instanceof ToEnum) {
            $case = $value->toEnum();
            if (Type::isInstance($case, $enumClass)) {
                return $case;
            }
        }
        $intValue = match (true) {
            $value instanceof ToInt => $value->toInt(),
            Type::isInt($value) => $value,
            default => null,
        };
        $stringValue = match (true) {
            $value instanceof Stringable => (string) $value,
            Type::isStr($value) => $value,
            default => null,
        };
        $scalar = $intValue ?? $stringValue;
        if (Type::isNull($scalar)) {
            throw new InvalidArgumentException('Cannot convert ' . Type::of($value) . ' to ' . $enumClass);
        }

        return (Type::isSubclass($enumClass, BackedEnum::class)
            ? $enumClass::tryFrom($scalar) : null)
            ?? Enum::tryFromName($enumClass, (string) $stringValue)
            ?? throw new InvalidArgumentException("'{$scalar}' is not a case of {$enumClass}");
    }

    /**
     * Convert any value to an iterable.
     *
     * @param mixed $value the value to convert
     *
     * @return iterable<mixed> the iterable representation of $value
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public static function toCollection(mixed $value): iterable
    {
        return match (true) {
            Type::isArray($value) => $value,
            $value instanceof Traversable => $value,
            $value instanceof ToCollection => $value->toCollection(),
            $value instanceof ToArray => $value->toArray(),
            default => throw new InvalidArgumentException('Cannot convert ' . Type::of($value) . ' to iterable'),
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
     *  8. ToEnum       → toEnum()      : \UnitEnum
     *  9. ToCollection → toCollection(): iterable
     * 10. ToArray      → toArray()     : array
     *
     * @param Castable $value an object implementing at least one typed contract
     *
     * @return array<mixed>|bool|DateTimeImmutable|float|int|Number|string|Traversable<mixed>|UnitEnum the value returned by the matching contract method
     *
     * @throws InvalidArgumentException when $value implements only the marker Castable interface
     */
    public static function cast(Castable $value): array|bool|DateTimeImmutable|float|int|Number|string|Traversable|UnitEnum
    {
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
            default => throw new InvalidArgumentException('Cannot cast ' . Type::of($value)),
        };
    }

    /**
     * Encode any value as a JSON string.
     *
     * - ToJson objects: delegates directly to toJson(), ignoring $flags.
     * - Other Castable objects: cast() first, then Json::encode().
     * - Everything else: Json::encode() directly.
     *
     * JSON_THROW_ON_ERROR is always added to $flags by Json::encode(), so
     * encoding failures throw a JsonException rather than returning false.
     *
     * @param mixed $value the value to encode
     * @param int   $flags json_encode() flags. Defaults to JSON_PRETTY_PRINT.
     *
     * @return string a valid JSON string
     *
     * @throws JsonException when $value cannot be encoded to JSON
     */
    public static function toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string
    {
        if ($value instanceof ToJson) {
            return $value->toJson();
        }
        if ($value instanceof Castable) {
            $value = self::cast($value);
        }

        return Json::encode($value, $flags);
    }
}
