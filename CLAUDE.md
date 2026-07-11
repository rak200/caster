# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

The **cross-library rak200 PHP conventions** (baseline & tooling, dev dependencies, CI, code style, naming, `use function` inventory, first-class callables, correctness-over-efficiency, safe defaults, testing, versioning, README badges) are shared and imported below. This file keeps only what is specific to **caster**.

@~/.claude/rak200-php-conventions.md

## Project Overview

**rak200/caster** is a PHP 8.4+ library providing type casting contracts (interfaces) and a `Caster` utility class that converts arbitrary values to those types.

**Deliberate deviation from the shared "no runtime Composer dependencies" rule:** caster requires **`rak200/utils` (`^4.0`)** at runtime — the converters are built on its `Type`, `Enum`, `Num`, `Iter` and `Json` helpers (the prefer-lib-over-native rule applied across libraries). utils is consumed through a `"type": "vcs"` repository entry until both libraries land on Packagist (see Roadmap); consumers must therefore list **both** VCS repositories (Composer reads `repositories` only from the root project — the README's Installation section shows this).

## Structure

```
caster/
├── docs/                    # per-class reference pages (caster.md, caster-interface.md, contracts.md + index)
├── src/
│   ├── Caster.php           # static utility class (final)
│   ├── CasterInterface.php  # instance-level mirror of the Caster API (DI/mocking)
│   ├── DefaultCaster.php    # canonical stateless CasterInterface implementation
│   └── Contracts/           # Castable marker + the 10 To* contracts (table below)
└── tests/                   # split per converter — see Testing
```

Production classes live under `Rak200\Caster\` (PSR-4 from `src/`); test classes live under `Rak200\Caster\Tests\` (PSR-4 from `tests/`, dev-only).

## Contracts

All contracts live under `Rak200\Caster\Contracts`. Every contract extends `Castable` (a marker interface); `ToString` additionally extends PHP's built-in `Stringable`.

| Interface      | Method           | Return               |
|----------------|------------------|----------------------|
| `ToArray`      | `toArray()`      | `array`              |
| `ToBool`       | `toBool()`       | `bool`               |
| `ToCollection` | `toCollection()` | `iterable`           |
| `ToDateTime`   | `toDateTime()`   | `\DateTimeImmutable` |
| `ToEnum`       | `toEnum()`       | `\UnitEnum`          |
| `ToFloat`      | `toFloat()`      | `float`              |
| `ToInt`        | `toInt()`        | `int`                |
| `ToJson`       | `toJson()`       | `string`             |
| `ToNumber`     | `toNumber()`     | `\BcMath\Number`     |
| `ToString`     | `__toString()`   | `string`             |

## Caster class

`Rak200\Caster\Caster` is `final` with the following static methods. Every conversion method has a **`try*` twin** returning `null` instead of throwing (`tryToString`, …, `tryToCollection`, `tryCast`, `tryToJson`; `tryToEnum` returns null for any failure, a non-enum `$enumClass` included).

Universal converters (throw `InvalidArgumentException` for unconvertible types):
- `toString(mixed $value): string`
- `toInt(mixed $value): int` (strings/Stringables must be strictly numeric — no surrounding whitespace; non-numeric throws instead of coercing to 0)
- `toFloat(mixed $value): float` (same strict numeric-string rule as `toInt`)
- `toBool(mixed $value): bool`
- `toArray(mixed $value): array`
- `toNumber(mixed $value): \BcMath\Number`
- `toDateTime(mixed $value): \DateTimeImmutable` (int values interpreted as Unix timestamps via `Dt::fromEpoch`; strings parsed by `Dt::parse` — malformed strings throw `InvalidArgumentException`)
- `toEnum(mixed $value, class-string<\UnitEnum> $enumClass = \UnitEnum::class): \UnitEnum` (backed enums match by backing value — the scalar is coerced to the backing type first, so `'2'` matches an int-backed case — then any enum by case name; enum instances pass through — the bare `\UnitEnum::class` default only accepts values that already are enum cases)
- `toCollection(mixed $value): iterable`

Other:
- `cast(Castable $value): string|int|float|bool|array|\BcMath\Number|\DateTimeImmutable|\UnitEnum|\Traversable` — dispatches to the first matching contract (priority: `ToJson` → `ToString` → `ToNumber` → `ToInt` → `ToFloat` → `ToBool` → `ToDateTime` → `ToEnum` → `ToCollection` → `ToArray`)
- `toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string` — JSON-encodes any value via utils' `Json::encode` (always `JSON_THROW_ON_ERROR`); `ToJson` objects delegate to `toJson()` ignoring `$flags`; other `Castable`s go through `cast()` first; `Traversable`s (including `cast()` results) are materialised before encoding

## CasterInterface & DefaultCaster

`Rak200\Caster\CasterInterface` mirrors the full `Caster` API as instance methods (same signatures, defaults and exceptions — converters, `try*` twins, `cast`, `toJson`) so consumers can inject and mock the conversion surface. `Rak200\Caster\DefaultCaster` is the canonical implementation: `final`, stateless, each method a one-line delegation to the corresponding static.

## Testing

General testing conventions are in the shared file. caster specifics:

- PHPUnit is configured via `phpunit.xml` with a single `Unit` suite.
- The suite is split per converter: one `CasterTo<Type>Test.php` per universal converter (covering its `try*` twin too), plus `CasterCastTest.php` (`cast()`/`tryCast()` dispatch), `CasterBcMathTest.php` (BcMath edge cases) and `DefaultCasterTest.php` (interface delegation + mockability).

## Versioning & releases

SemVer policy and the release checklist live in the shared conventions. caster delta: not on Packagist yet — consumers add this repo (and `rak200/utils`) as `"type": "vcs"` and resolve versions from git tags.

## Roadmap

Pending work only — items are **pruned** on delivery (shared release checklist); `CHANGELOG.md` is the historical record.

### 🟢 Low priority

- [ ] **`Caster::all(array $values, string $method): array`** — applies a conversion method in batch
- [ ] **Custom converter registry** — `Caster::register('uuid', fn($v) => ...)` + `Caster::convert($value, 'uuid')`
- [ ] **Fluent API** — `Caster::of($value)->toString()->trim()->upper()` (see section below)
- [ ] **Mutation testing** — integrate Infection to validate test quality

### Fluent API — Details

The idea is to create a `CasterBuilder` (or `CastValue`) class that wraps a value and chains operations:

```php
// Today:
$result = strtoupper(trim(Caster::toString($value)));

// With fluent API:
$result = Caster::of($value)
    ->toString()
    ->trim()
    ->upper()
    ->get(); // returns the final value
```

**Required components:**

```
src/
└── Builder/
    ├── CastBuilder.php       # main fluent class
    └── StringCastBuilder.php # specialized builder for strings
```

**Inner workings:**
- `Caster::of($value)` returns a `CastBuilder` instance carrying the value
- Methods like `toString()`, `toInt()` perform the conversion and return a specialized builder
- Transformation methods (`trim()`, `upper()`, `lower()`, `pad()`) operate on the already-converted value
- `get()` extracts the final value
- The builder is immutable — each operation returns a new instance

**Minimal implementation example:**

```php
final class CastBuilder
{
    public function __construct(private readonly mixed $value) {}

    public function toString(): StringCastBuilder
    {
        return new StringCastBuilder(Caster::toString($this->value));
    }

    public function toInt(): IntCastBuilder { ... }
    // etc.
}

final class StringCastBuilder
{
    public function __construct(private readonly string $value) {}

    public function trim(string $chars = " \t\n\r"): self
    {
        return new self(trim($this->value, $chars));
    }

    public function upper(): self  { return new self(strtoupper($this->value)); }
    public function lower(): self  { return new self(strtolower($this->value)); }
    public function get(): string  { return $this->value; }
}
```

**Pros:**
- More readable, declarative code
- Eliminates hard-to-read nested function calls
- Endless chaining without intermediate variables
- Easy to extend with new transformation methods

**Cons / caveats:**
- Significantly increases the library size
- Each transformation creates a new object (memory impact in high-volume scenarios)
- May be perceived as "scope creep" for a simple contracts library
- Alternative: keep it as a separate package `rak200/caster-fluent`
