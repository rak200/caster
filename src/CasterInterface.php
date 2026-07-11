<?php

declare(strict_types=1);

namespace Rak200\Caster;

use BcMath\Number;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use Rak200\Caster\Contracts\Castable;
use Traversable;
use UnitEnum;

/**
 * Instance-level contract for the {@see Caster} conversion API, enabling
 * dependency injection and mocking in consumer tests. {@see DefaultCaster}
 * is the canonical implementation, delegating to the static Caster.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface CasterInterface
{
    /**
     * Convert any value to a string. See {@see Caster::toString()}.
     *
     * @throws InvalidArgumentException when $value cannot be stringified
     */
    public function toString(mixed $value): string;

    /**
     * Like {@see toString()}, but returns null when $value cannot be stringified.
     */
    public function tryToString(mixed $value): ?string;

    /**
     * Convert any value to an integer. See {@see Caster::toInt()}.
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public function toInt(mixed $value): int;

    /**
     * Like {@see toInt()}, but returns null when $value cannot be converted.
     */
    public function tryToInt(mixed $value): ?int;

    /**
     * Convert any value to a float. See {@see Caster::toFloat()}.
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public function toFloat(mixed $value): float;

    /**
     * Like {@see toFloat()}, but returns null when $value cannot be converted.
     */
    public function tryToFloat(mixed $value): ?float;

    /**
     * Convert any value to a boolean. See {@see Caster::toBool()}.
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public function toBool(mixed $value): bool;

    /**
     * Like {@see toBool()}, but returns null when $value cannot be converted.
     */
    public function tryToBool(mixed $value): ?bool;

    /**
     * Convert any value to an array. See {@see Caster::toArray()}.
     *
     * @return array<mixed>
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public function toArray(mixed $value): array;

    /**
     * Like {@see toArray()}, but returns null when $value cannot be converted.
     *
     * @return null|array<mixed>
     */
    public function tryToArray(mixed $value): ?array;

    /**
     * Convert any value to a BcMath\Number. See {@see Caster::toNumber()}.
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public function toNumber(mixed $value): Number;

    /**
     * Like {@see toNumber()}, but returns null when $value cannot be converted.
     */
    public function tryToNumber(mixed $value): ?Number;

    /**
     * Convert any value to a DateTimeImmutable. See {@see Caster::toDateTime()}.
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public function toDateTime(mixed $value): DateTimeImmutable;

    /**
     * Like {@see toDateTime()}, but returns null when $value cannot be converted.
     */
    public function tryToDateTime(mixed $value): ?DateTimeImmutable;

    /**
     * Convert any value to a case of the given UnitEnum. See {@see Caster::toEnum()}.
     *
     * @template T of UnitEnum
     *
     * @param mixed           $value     the value to convert
     * @param class-string<T> $enumClass the target enum class
     *
     * @return T
     *
     * @throws InvalidArgumentException when $enumClass is not a UnitEnum, or $value cannot be converted
     */
    public function toEnum(mixed $value, string $enumClass = UnitEnum::class): UnitEnum;

    /**
     * Like {@see toEnum()}, but returns null when $value cannot be converted —
     * including when $enumClass is not an enum.
     *
     * @template T of UnitEnum
     *
     * @param mixed           $value     the value to convert
     * @param class-string<T> $enumClass the target enum class
     *
     * @return null|T
     */
    public function tryToEnum(mixed $value, string $enumClass = UnitEnum::class): ?UnitEnum;

    /**
     * Convert any value to an iterable. See {@see Caster::toCollection()}.
     *
     * @return iterable<mixed>
     *
     * @throws InvalidArgumentException when $value cannot be converted
     */
    public function toCollection(mixed $value): iterable;

    /**
     * Like {@see toCollection()}, but returns null when $value cannot be converted.
     *
     * @return null|iterable<mixed>
     */
    public function tryToCollection(mixed $value): ?iterable;

    /**
     * Dispatch a Castable object to its typed value. See {@see Caster::cast()}.
     *
     * @return array<mixed>|bool|DateTimeImmutable|float|int|Number|string|Traversable<mixed>|UnitEnum
     *
     * @throws InvalidArgumentException when $value implements only the marker Castable interface
     */
    public function cast(Castable $value): array|bool|DateTimeImmutable|float|int|Number|string|Traversable|UnitEnum;

    /**
     * Like {@see cast()}, but returns null when $value implements only the
     * marker Castable interface.
     *
     * @return null|array<mixed>|bool|DateTimeImmutable|float|int|Number|string|Traversable<mixed>|UnitEnum
     */
    public function tryCast(Castable $value): array|bool|DateTimeImmutable|float|int|Number|string|Traversable|UnitEnum|null;

    /**
     * Encode any value as a JSON string. See {@see Caster::toJson()}.
     *
     * @throws JsonException when $value cannot be encoded to JSON
     */
    public function toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string;

    /**
     * Like {@see toJson()}, but returns null when $value cannot be encoded.
     */
    public function tryToJson(mixed $value, int $flags = JSON_PRETTY_PRINT): ?string;
}
