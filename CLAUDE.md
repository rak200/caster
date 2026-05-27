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

Follows [Semantic Versioning](https://semver.org). Current version: **1.0.0**

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Update `CHANGELOG.md`: add a new `## [x.y.z] - YYYY-MM-DD` section with `### Added / Changed / Fixed / Removed` entries and a comparison link at the bottom
3. Update the version reference in `README.md`
4. Commit and push
5. Create and push a git tag matching the version: `git tag x.y.z && git push origin x.y.z`

Consumers using `"type": "vcs"` in their `composer.json` resolve versions from git tags.

---

## Improvement Roadmap

List of suggestions raised on 2026-05-27. Update as items are implemented.

### 🔴 High priority

- [x] **`LICENSE` file** — required for legal third-party use (suggested: MIT)
- [x] **PHPStan in CI** — add static analysis at level 8+ to the GitHub Actions workflow

### 🟡 Medium priority

- [ ] **Universal methods on `Caster`** — mirror `toString()` for the remaining types:
  - `toInt(mixed): int`
  - `toFloat(mixed): float`
  - `toBool(mixed): bool`
  - `toArray(mixed): array`
- [ ] **`tryTo*` methods** — variants that return `null` instead of throwing:
  - `tryToString(mixed): ?string`
  - `tryToInt(mixed): ?int`
  - etc.
- [ ] **`CasterInterface`** — interface for the `Caster` class to enable dependency injection and mocking in consumer tests
- [ ] **`Caster::can(mixed $value, string $contract): bool`** — checks whether a value supports a given contract

### 🟢 Low priority

- [ ] **New contracts**:
  - `ToDateTime extends Castable` → `toDateTime(): \DateTimeImmutable`
  - `ToEnum extends Castable` → `toEnum(): \BackedEnum`
  - `ToCollection extends Castable` → `toCollection(): iterable`
- [ ] **`Caster::all(array $values, string $method): array`** — applies a conversion method in batch
- [ ] **Custom converter registry** — `Caster::register('uuid', fn($v) => ...)` + `Caster::convert($value, 'uuid')`
- [ ] **Fluent API** — `Caster::of($value)->toString()->trim()->upper()` (see section below)
- [ ] **Mutation testing** — integrate Infection to validate test quality
- [ ] **PHP CS Fixer** — enforce consistent style (`@PER-CS2.0`)
- [ ] **Code coverage** — integrate Codecov/Coveralls with a README badge
- [ ] **Publish on Packagist** — for installation via `composer require rak200/caster`
- [ ] **`CONTRIBUTING.md`** — contributor guide
- [ ] **README badges** — version, CI status, PHP version

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
