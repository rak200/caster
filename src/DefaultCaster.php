<?php

declare(strict_types=1);

namespace Rak200\Caster;

use BcMath\Number;
use DateTimeImmutable;
use Rak200\Caster\Contracts\Castable;
use Traversable;
use UnitEnum;

/**
 * Canonical {@see CasterInterface} implementation: a stateless instance
 * facade over the static {@see Caster}, for dependency injection and
 * mocking in consumer tests.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class DefaultCaster implements CasterInterface
{
    /** {@see Caster::toString()}. */
    public function toString(mixed $value): string
    {
        return Caster::toString($value);
    }

    /** {@see Caster::tryToString()}. */
    public function tryToString(mixed $value): ?string
    {
        return Caster::tryToString($value);
    }

    /** {@see Caster::toInt()}. */
    public function toInt(mixed $value): int
    {
        return Caster::toInt($value);
    }

    /** {@see Caster::tryToInt()}. */
    public function tryToInt(mixed $value): ?int
    {
        return Caster::tryToInt($value);
    }

    /** {@see Caster::toFloat()}. */
    public function toFloat(mixed $value): float
    {
        return Caster::toFloat($value);
    }

    /** {@see Caster::tryToFloat()}. */
    public function tryToFloat(mixed $value): ?float
    {
        return Caster::tryToFloat($value);
    }

    /** {@see Caster::toBool()}. */
    public function toBool(mixed $value): bool
    {
        return Caster::toBool($value);
    }

    /** {@see Caster::tryToBool()}. */
    public function tryToBool(mixed $value): ?bool
    {
        return Caster::tryToBool($value);
    }

    /**
     * {@see Caster::toArray()}.
     *
     * @return array<mixed>
     */
    public function toArray(mixed $value): array
    {
        return Caster::toArray($value);
    }

    /**
     * {@see Caster::tryToArray()}.
     *
     * @return null|array<mixed>
     */
    public function tryToArray(mixed $value): ?array
    {
        return Caster::tryToArray($value);
    }

    /** {@see Caster::toNumber()}. */
    public function toNumber(mixed $value): Number
    {
        return Caster::toNumber($value);
    }

    /** {@see Caster::tryToNumber()}. */
    public function tryToNumber(mixed $value): ?Number
    {
        return Caster::tryToNumber($value);
    }

    /** {@see Caster::toDateTime()}. */
    public function toDateTime(mixed $value): DateTimeImmutable
    {
        return Caster::toDateTime($value);
    }

    /** {@see Caster::tryToDateTime()}. */
    public function tryToDateTime(mixed $value): ?DateTimeImmutable
    {
        return Caster::tryToDateTime($value);
    }

    /**
     * {@see Caster::toEnum()}.
     *
     * @template T of UnitEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T
     */
    public function toEnum(mixed $value, string $enumClass = UnitEnum::class): UnitEnum
    {
        return Caster::toEnum($value, $enumClass);
    }

    /**
     * {@see Caster::tryToEnum()}.
     *
     * @template T of UnitEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return null|T
     */
    public function tryToEnum(mixed $value, string $enumClass = UnitEnum::class): ?UnitEnum
    {
        return Caster::tryToEnum($value, $enumClass);
    }

    /**
     * {@see Caster::toCollection()}.
     *
     * @return iterable<mixed>
     */
    public function toCollection(mixed $value): iterable
    {
        return Caster::toCollection($value);
    }

    /**
     * {@see Caster::tryToCollection()}.
     *
     * @return null|iterable<mixed>
     */
    public function tryToCollection(mixed $value): ?iterable
    {
        return Caster::tryToCollection($value);
    }

    /**
     * {@see Caster::cast()}.
     *
     * @return array<mixed>|bool|DateTimeImmutable|float|int|Number|string|Traversable<mixed>|UnitEnum
     */
    public function cast(Castable $value): array|bool|DateTimeImmutable|float|int|Number|string|Traversable|UnitEnum
    {
        return Caster::cast($value);
    }

    /**
     * {@see Caster::tryCast()}.
     *
     * @return null|array<mixed>|bool|DateTimeImmutable|float|int|Number|string|Traversable<mixed>|UnitEnum
     */
    public function tryCast(Castable $value): array|bool|DateTimeImmutable|float|int|Number|string|Traversable|UnitEnum|null
    {
        return Caster::tryCast($value);
    }

    /** {@see Caster::toJson()}. */
    public function toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string
    {
        return Caster::toJson($value, $flags);
    }

    /** {@see Caster::tryToJson()}. */
    public function tryToJson(mixed $value, int $flags = JSON_PRETTY_PRINT): ?string
    {
        return Caster::tryToJson($value, $flags);
    }
}
