# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

The **cross-library rak200 PHP conventions** (baseline & tooling, dev dependencies, CI, code style, naming, `use function` inventory, first-class callables, correctness-over-efficiency, safe defaults, testing, versioning, README badges) are shared and imported below. This file keeps only what is specific to **caster**.

@~/.claude/rak200-php-conventions.md

## Project Overview

**rak200/caster** is a PHP 8.4+ library providing type casting contracts (interfaces) and a `Caster` utility class that converts arbitrary values to those types.

**Deliberate deviation from the shared "no runtime Composer dependencies" rule:** caster requires **`rak200/utils` (`^4.4`)** at runtime — the converters are built on its `Type`, `Enum`, `Num`, `Iter`, `Dt` and `Json` helpers (the prefer-lib-over-native rule applied across libraries). The one native kept against that rule is `iterator_to_array` (imported via `use function`, with the reason stated at the import): materialisation must preserve keys for an **arbitrary** `Traversable`, and `Iter::toArray()` binds `TKey of array-key`, which cannot resolve against the unconstrained iterables `Caster` accepts — adopting it would need a PHPStan suppression or a weaker generic in utils. utils is consumed through a `"type": "vcs"` repository entry until both libraries land on Packagist (see Roadmap); consumers must therefore list **both** VCS repositories (Composer reads `repositories` only from the root project — the README's Installation section shows this).

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
- **Mutation testing** — Infection (`infection/infection`, config `infection.json5.dist`) runs via `composer infection` (locally through Xdebug via the script's `XDEBUG_MODE=coverage`; CI uses pcov). The **MSI gate is closed at 100** (`minMsi=100` / `minCoveredMsi=100`), enforced by a floor-only CI step. Surviving mutants are killed by strengthening tests, or — when provably equivalent — suppressed in-code with `@infection-ignore-all` anchored on the smallest node that isolates just the equivalent construct (the three remaining PHPStan-only casts / re-cast `Stringable`), or removed as dead code when even that can't reach the mutation (the redundant `toNumber` `Number => $value` fast-path arm — `MatchArmRemoval` targets the parent `Match_` node, not the arm, and the fall-through `Num::parseNumber` returns the same instance); the threshold is never lowered.

## Versioning & releases

SemVer policy and the release checklist live in the shared conventions. caster delta: not on Packagist yet — consumers add this repo (and `rak200/utils`) as `"type": "vcs"` and resolve versions from git tags.

## Roadmap

Pending work only — items are **pruned** on delivery (shared release checklist); `CHANGELOG.md` is the historical record.

