# CLAUDE.md

Guidance for Claude Code when working in this repository.

The cross-repository conventions are imported below, and this file keeps only what is specific to
**caster**. Layer 1 is language-agnostic: versioning and releases, commits, the pipeline, the
shared task vocabulary, testing policy, documentation, proposals, support files, repository
hygiene, README badges, security, and the non-negotiables. Layer 2 is PHP: the baseline, what each
verb runs, static analysis, code style, naming, member order, prefer-`utils`-over-native,
correctness over efficiency, safe defaults, and the form of tests and docblocks.

@.rak200/CONVENTIONS.md
@vendor/rak200/coding-standard-php/CONVENTIONS.md

> If `.rak200/` is empty, the clone skipped its submodule:
> `git submodule update --init --recursive`. If the second import is missing, run
> `composer install` — PHP development needs it anyway.

## Project Overview

**rak200/caster** is a PHP 8.4+ library providing type casting contracts (interfaces) and a `Caster` utility class that converts arbitrary values to those types.

It requires **`rak200/utils` (`^4.4`)** at runtime — a deviation from the shared "no runtime Composer dependencies" rule — and keeps `iterator_to_array` as its one native against the prefer-`utils` rule. Both deviations, the contract dispatch order, the reason `CasterInterface` exists, and why the package is not on Packagist are argued in [ARCHITECTURE.md](ARCHITECTURE.md); this file does not repeat them.

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
- `cast(Castable $value): string|int|float|bool|array|\BcMath\Number|\DateTimeImmutable|\UnitEnum|\Traversable` — dispatches to the first matching contract (priority: `ToJson` → `ToString` → `ToNumber` → `ToInt` → `ToFloat` → `ToBool` → `ToDateTime` → `ToEnum` → `ToCollection` → `ToArray` — [why that order](ARCHITECTURE.md#contract-dispatch-has-a-fixed-priority))
- `toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string` — JSON-encodes any value via utils' `Json::encode` (always `JSON_THROW_ON_ERROR`); `ToJson` objects delegate to `toJson()` ignoring `$flags`; other `Castable`s go through `cast()` first; `Traversable`s (including `cast()` results) are materialised before encoding

## CasterInterface & DefaultCaster

`Rak200\Caster\CasterInterface` mirrors the full `Caster` API as instance methods (same signatures, defaults and exceptions — converters, `try*` twins, `cast`, `toJson`). `Rak200\Caster\DefaultCaster` is the canonical implementation: `final`, stateless, each method a one-line delegation to the corresponding static.

`Caster` does not implement the interface — the methods are static — so **no analyser compares the two**, and `CasterInterfaceTest` is what does, by reflection. [ARCHITECTURE.md](ARCHITECTURE.md#a-static-class-with-an-interface-it-does-not-implement) has the reasoning.

## Testing

Testing **policy** is Layer 1 and testing **form** is Layer 2. caster specifics:

- PHPUnit is configured via `phpunit.xml` with a single `Unit` suite. Every test class declares
  its coverage target with `#[CoversClass]`, and `requireCoverageMetadata` makes omitting it a
  red suite — the reason is at the setting.
- The suite is split per converter: one `CasterTo<Type>Test.php` per universal converter (covering its `try*` twin too), plus `CasterCastTest.php` (`cast()`/`tryCast()` dispatch), `CasterBcMathTest.php` (BcMath edge cases) and `DefaultCasterTest.php` (interface delegation + mockability).
- **Mutation testing** — Infection (`infection/infection`, config `infection.json5.dist`) runs via `composer mutation` (locally through Xdebug via the script's `XDEBUG_MODE=coverage`; CI uses pcov, and narrows the run to the changed lines on a pull request). The **MSI gate is closed at 100** (`minMsi=100` / `minCoveredMsi=100`), enforced by a floor-only CI step. Surviving mutants are killed by strengthening tests, or — when provably equivalent — suppressed in-code with `@infection-ignore-all` anchored on the smallest node that isolates just the equivalent construct; each suppression states its own reason where it sits, and `grep -n infection-ignore-all src/` is the inventory. Where even that cannot reach the mutation the code is removed instead — `MatchArmRemoval` targets the parent `Match_` node rather than the arm, so a redundant arm has to go rather than be annotated. The threshold is never lowered.

## Versioning & releases

SemVer policy and the release checklist are Layer 1. caster delta: not on Packagist yet — consumers add this repo (and `rak200/utils`) as `"type": "vcs"` and resolve versions from git tags, for the reason in [ARCHITECTURE.md](ARCHITECTURE.md#not-on-packagist).
