# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.0.1]: https://github.com/rak200/caster/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/rak200/caster/compare/0.1.0...1.0.0
[0.1.0]: https://github.com/rak200/caster/compare/0.0.1...0.1.0
[0.0.1]: https://github.com/rak200/caster/releases/tag/0.0.1
