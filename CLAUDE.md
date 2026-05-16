# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**rak200/caster** is a standalone PHP 8.4+ library providing type casting contracts (interfaces) and a `Caster` utility class. It has no dependencies beyond PHP itself.

## Structure

```
caster/
├── .gitignore
├── composer.json
├── phpunit.xml
├── src/
│   ├── Caster.php           # Static utility class
│   └── Contracts/
│       ├── Castable.php     # Base marker interface
│       ├── ToArray.php
│       ├── ToBool.php
│       ├── ToFloat.php
│       ├── ToInt.php
│       ├── ToJson.php
│       └── ToString.php     # extends Castable + Stringable
└── tests/
    ├── CasterBcMathTest.php
    ├── CasterCastTest.php
    ├── CasterToJsonTest.php
    └── CasterToStringTest.php
```

All classes live under the `Rak200\Caster` namespace (PSR-4, mapped from `src/`).

## Contracts

Every contract extends `Castable` (a marker interface). `ToString` additionally extends PHP's built-in `Stringable`.

| Interface  | Namespace                    | Method         | Return   |
|------------|------------------------------|----------------|----------|
| `ToArray`  | `Rak200\Caster\Contracts`   | `toArray()`    | `array`  |
| `ToBool`   | `Rak200\Caster\Contracts`   | `toBool()`     | `bool`   |
| `ToFloat`  | `Rak200\Caster\Contracts`   | `toFloat()`    | `float`  |
| `ToInt`    | `Rak200\Caster\Contracts`   | `toInt()`      | `int`    |
| `ToJson`   | `Rak200\Caster\Contracts`   | `toJson()`     | `string` |
| `ToString` | `Rak200\Caster\Contracts`   | `__toString()` | `string` |

## Caster class

`Rak200\Caster\Caster` is `final` with three static methods:

- `toString(mixed $value): string` — converts any value to string; throws `InvalidArgumentException` for unconvertible types
- `cast(Castable $value): string|int|float|bool|array` — dispatches to the first matching contract (priority: `ToJson` → `ToString` → `ToInt` → `ToFloat` → `ToBool` → `ToArray`)
- `toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string` — JSON-encodes any value; delegates to `toJson()` for `ToJson` objects; uses `JSON_THROW_ON_ERROR`

## Running tests

```bash
composer test
# or directly:
php -c "A:\Program Files\php\php.ini" vendor/bin/phpunit
```

The explicit `-c` flag is required on Windows because PHPUnit's xdebug-handler restarts
PHP without the ini path when xdebug is listed but not found, dropping extensions like mbstring.

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.1.0**

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Commit and push
3. Create and push a git tag matching the version: `git tag 0.x.y && git push origin 0.x.y`

Consumers using `"type": "vcs"` in their `composer.json` resolve versions from git tags.
