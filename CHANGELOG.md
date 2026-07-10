# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2026-07-10

### Added
- Per-class reference under `docs/`: `docs/caster.md` (resolution order and runnable examples for every converter), `docs/contracts.md` (the `Castable` marker and the 10 typed contracts, one section each) and the `docs/README.md` index
- README badges: CI, Codecov coverage, latest tag, PHP version, PHPStan level, code style, license, SemVer, Keep a Changelog
- `ext-bcmath` declared explicitly in `require` (used directly by `Caster::toNumber()`)
- PHP-CS-Fixer `^3.75` as a dev dependency, configured with the shared `@PhpCsFixer`-preset ruleset in `.php-cs-fixer.dist.php`; new `cs-check` / `cs-fix` composer scripts

### Changed
- Bumped `rak200/utils` from `^1.8` to `^3.0`; call sites migrated to the 2.0.0 canonical names (`Type::isInstance`, `Type::isSubclass`)
- PHPStan raised from level 8 to level **max** and now analyses `tests/` too; config renamed `phpstan.neon` → `phpstan.neon.dist`, dev dependency bumped to `^2.1`
- Whole codebase reformatted under the `@PhpCsFixer` preset (no behaviour change)
- CI workflow rewritten as `ci.yml` (replacing `tests.yml`): `permissions: contents: read`, `composer validate`, `fail-fast: false` matrix over PHP 8.4/8.5, CS check on the floor job, PHPStan on every job, PHPUnit with `pcov` coverage and Codecov upload
- Composer scripts aligned with the shared conventions: `test` is plain `phpunit` (the Windows `-c php.ini` pin is no longer needed), `analyse` renamed to `phpstan` (with `--memory-limit=512M`)
- `phpunit.xml` now sets `failOnWarning` and `failOnRisky`
- README rewritten: lean overview + installation (the VCS instructions now list **both** required repositories — caster and utils, since Composer reads `repositories` only from the root project), full reference moved to `docs/`, hardcoded version line replaced by the tag badge
- `CLAUDE.md` now imports the shared rak200 PHP conventions file and keeps only caster-specific content

### Fixed
- `Castable` docblock now lists all 10 typed sub-interfaces (was naming only the original 6)

## [1.3.0] - 2026-05-30

### Added
- Pure (non-backed) enum support: `Caster::toEnum()` matches a pure enum by case name, and `Caster::toString()` renders a pure enum case as its name

### Changed
- `ToEnum::toEnum()` return type widened from `\BackedEnum` to `\UnitEnum` (covariant — implementers may still return a `\BackedEnum`)
- `Caster::toEnum()` signature now takes `class-string<\UnitEnum>`, and `Caster::cast()`'s return type widened to include `\UnitEnum`
- Enum/numeric converters now route through `rak200/utils` 1.8.0 helpers — `Enum::scalar()`, `Enum::isBackedInt()`, and the `Num::is()` gate with `Num::parseFloat()`/`Num::parseNumber()` — removing the private `unitEnumScalar()`, `backedEnumValue()` and `numberFromString()` helpers
- `Caster::toInt()` accepts only **int-backed** enum cases; string-backed cases (numeric or not) and pure enums now throw `InvalidArgumentException`
- `Caster::toFloat()` and `Caster::toNumber()` require a **numeric** scalar: int-backed and numeric string-backed enums convert, while non-numeric string-backed cases and pure enums now throw `InvalidArgumentException`
- Bumped the `rak200/utils` requirement from `^1.0` to `^1.8`

## [1.2.0] - 2026-05-27

### Added
- Universal converter methods on `Caster`, mirroring `toString()`'s pattern: `toInt()`, `toFloat()`, `toBool()`, `toArray()`, `toNumber()`, `toDateTime()`, `toEnum()`, `toCollection()`
- All new methods accept `mixed`, dispatch to matching contracts (with specific contracts winning over generic `Stringable` fallback), and throw `InvalidArgumentException` for unconvertible types
- `Caster::toEnum()` requires the target `class-string<\BackedEnum>` to disambiguate; `Caster::toDateTime()` interprets integers as Unix timestamps
- 96 new tests covering every branch of the new converters

## [1.1.0] - 2026-05-27

### Added
- New contracts: `ToNumber` (→ `\BcMath\Number`), `ToDateTime` (→ `\DateTimeImmutable`), `ToEnum` (→ `\BackedEnum`), `ToCollection` (→ `iterable`)
- `Caster::cast()` dispatches to the four new contracts; return type widened to `string|int|float|bool|array|\BcMath\Number|\DateTimeImmutable|\BackedEnum|\Traversable`
- `Caster::toString()` handles the new contracts with natural string outputs: ISO 8601 for `ToDateTime`, backed value for `ToEnum`, materialised iterable as JSON for `ToCollection`
- `rak200/utils` (`^1.0`) added as a runtime dependency, sourced from GitHub via `repositories`

### Changed
- `Caster::cast()` dispatch priority: `ToJson` → `ToString` → `ToNumber` → `ToInt` → `ToFloat` → `ToBool` → `ToDateTime` → `ToEnum` → `ToCollection` → `ToArray`
- Improvement roadmap in `CLAUDE.md` translated to English; "New contracts" item marked as done

## [1.0.1] - 2026-05-26

### Changed
- Built-in PHP functions imported via grouped `use function` declarations in `Caster.php` and test files; removed `\` prefix from call sites

## [1.0.0] - 2026-05-16

### Added
- GitHub Actions CI workflow (PHP 8.4 and 8.5)
- `keywords`, `homepage` and `support` fields in `composer.json`
- README: examples for all contracts (`ToArray`, `ToBool`, `ToFloat`, `ToInt`, `ToJson`, `ToString`)
- README: error handling section with safe usage patterns
- README: dispatch priority documentation
- `.gitattributes`: exclude `.github/` from Composer package

### Changed
- Exception messages capitalised: `"Cannot stringify"`, `"Cannot cast"`
- Built-in PHP functions prefixed with `\` in `Caster.php` for compiler optimisation

## [0.1.0] - 2026-05-16

### Added
- PHPUnit 13 test suite: 45 tests covering `Caster::toString()`, `Caster::cast()`,
  `Caster::toJson()` and `BcMath\Number` integration
- PHPDoc on all classes and interface members, including `@author` tags
- `phpunit.xml` configuration
- `.gitignore`
- `composer test` script

### Changed
- **Breaking:** namespace renamed from `Ricardo\Caster` to `Rak200\Caster`
- `Caster::toJson()` now documents that `JSON_THROW_ON_ERROR` is always added to `$flags`
- `ToString` interface now explicitly declares `__toString(): string`

## [0.0.1] - 2026-05-14

### Added
- `Caster` static utility class with `toString()`, `cast()` and `toJson()` methods
- Type contracts: `Castable`, `ToArray`, `ToBool`, `ToFloat`, `ToInt`, `ToJson`, `ToString`

[1.4.0]: https://github.com/rak200/caster/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/rak200/caster/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/rak200/caster/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/rak200/caster/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/rak200/caster/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/rak200/caster/compare/0.1.0...1.0.0
[0.1.0]: https://github.com/rak200/caster/compare/0.0.1...0.1.0
[0.0.1]: https://github.com/rak200/caster/releases/tag/0.0.1
