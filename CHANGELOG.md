# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.2.0]: https://github.com/rak200/caster/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/rak200/caster/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/rak200/caster/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/rak200/caster/compare/0.1.0...1.0.0
[0.1.0]: https://github.com/rak200/caster/compare/0.0.1...0.1.0
[0.0.1]: https://github.com/rak200/caster/releases/tag/0.0.1
