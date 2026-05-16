# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**ricardo/caster** is a standalone PHP 8.4+ library providing type casting contracts (interfaces) and a `Caster` utility class. It has no dependencies beyond PHP itself.

## Structure

```
caster/
├── composer.json
└── src/
    ├── Caster.php           # Static utility class
    └── Contracts/
        ├── Castable.php     # Base marker interface
        ├── ToArray.php
        ├── ToBool.php
        ├── ToFloat.php
        ├── ToInt.php
        ├── ToJson.php
        └── ToString.php     # extends Castable + Stringable
```

All classes live under the `Ricardo\Caster` namespace (PSR-4, mapped from `src/`).

## Contracts

Every contract extends `Castable` (a marker interface). `ToString` additionally extends PHP's built-in `Stringable`.

| Interface  | Namespace                    | Method         | Return   |
|------------|------------------------------|----------------|----------|
| `ToArray`  | `Ricardo\Caster\Contracts`   | `toArray()`    | `array`  |
| `ToBool`   | `Ricardo\Caster\Contracts`   | `toBool()`     | `bool`   |
| `ToFloat`  | `Ricardo\Caster\Contracts`   | `toFloat()`    | `float`  |
| `ToInt`    | `Ricardo\Caster\Contracts`   | `toInt()`      | `int`    |
| `ToJson`   | `Ricardo\Caster\Contracts`   | `toJson()`     | `string` |
| `ToString` | `Ricardo\Caster\Contracts`   | `__toString()` | `string` |

## Caster class

`Ricardo\Caster\Caster` is `final` with three static methods:

- `toString(mixed $value): string` — converts any value to string; throws `InvalidArgumentException` for unconvertible types
- `cast(Castable $value): string|int|float|bool|array` — dispatches to the first matching contract (priority: `ToJson` → `ToString` → `ToInt` → `ToFloat` → `ToBool` → `ToArray`)
- `toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string` — JSON-encodes any value; delegates to `toJson()` for `ToJson` objects; uses `JSON_THROW_ON_ERROR`

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.0.1** — unstable until unit tests are added.

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Commit and push
3. Create and push a git tag matching the version: `git tag 0.x.y && git push origin 0.x.y`

Consumers using `"type": "vcs"` in their `composer.json` resolve versions from git tags.
