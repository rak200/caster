# rak200/caster

[![CI](https://github.com/rak200/caster/actions/workflows/ci.yml/badge.svg)](https://github.com/rak200/caster/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/rak200/caster/graph/badge.svg)](https://codecov.io/gh/rak200/caster)
[![Latest tag](https://img.shields.io/github/v/tag/rak200/caster?sort=semver)](https://github.com/rak200/caster/tags)
[![PHP](https://img.shields.io/badge/php-8.4%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen?logo=php&logoColor=white)](https://phpstan.org/)
[![Code style](https://img.shields.io/badge/code%20style-PHP--CS--Fixer-blue?logo=php&logoColor=white)](.php-cs-fixer.dist.php)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![SemVer](https://img.shields.io/badge/semver-2.0.0-blue)](https://semver.org/spec/v2.0.0.html)
[![Keep a Changelog](https://img.shields.io/badge/changelog-Keep%20a%20Changelog-orange)](CHANGELOG.md)

Type casting contracts and utilities for PHP 8.4+.

Objects declare which types they can be reduced to by implementing small single-method contracts (`ToInt`, `ToString`, `ToJson`, …); the static `Caster` class converts **any** value — primitives and contract implementors alike — to the requested type, throwing `InvalidArgumentException` instead of silently coercing garbage. Every converter has a `try*` twin that returns `null` instead of throwing, and the whole API is mirrored instance-level by `CasterInterface` / `DefaultCaster` for dependency injection and mocking.

## Requirements

- PHP 8.4+
- Extension: `bcmath` (for `BcMath\Number` support in `toNumber`). Bundled with PHP and enabled by default on most distributions.
- Runtime dependency: [`rak200/utils`](https://github.com/rak200/utils) (installed automatically).

## Installation

Not published on Packagist — install straight from the GitHub repository as a Composer VCS package. Because `rak200/utils` is also VCS-only and Composer only reads `repositories` from the root project, the consuming project must list **both** repositories:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/rak200/caster" },
        { "type": "vcs", "url": "https://github.com/rak200/utils" }
    ]
}
```

then require it as usual:

```bash
composer require rak200/caster
```

## Contracts

All contracts live under `Rak200\Caster\Contracts` and extend the `Castable` marker interface.

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

`ToString` additionally extends PHP's built-in `Stringable`.

## Usage

```php
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToJson;

final class Money implements ToInt, ToFloat, ToJson
{
    public function __construct(private int $cents) {}

    public function toInt(): int
    {
        return $this->cents;
    }

    public function toFloat(): float
    {
        return $this->cents / 100;
    }

    public function toJson(): string
    {
        return json_encode(['cents' => $this->cents], JSON_THROW_ON_ERROR);
    }
}

$money = new Money(1999);

// Universal converters accept anything — primitives or contract implementors:
Caster::toInt($money);        // 1999
Caster::toFloat($money);      // 19.99
Caster::toInt('17');          // 17
Caster::toString(true);       // 'true'
Caster::toDateTime(0);        // DateTimeImmutable @1970-01-01T00:00:00+00:00
Caster::toString(null);       // throws InvalidArgumentException

// cast() dispatches a Castable to its highest-priority contract:
Caster::cast($money);         // '{"cents":1999}' — ToJson outranks ToInt/ToFloat

// toJson() encodes any value (JSON_PRETTY_PRINT by default, always JSON_THROW_ON_ERROR):
Caster::toJson(['a' => 1], 0);   // '{"a":1}'
```

Universal converters: `toString`, `toInt`, `toFloat`, `toBool`, `toArray`, `toNumber`, `toDateTime`, `toEnum`, `toCollection` — plus the `cast()` dispatcher and the `toJson()` encoder.

## Documentation

Per-method reference with runnable examples lives in [`docs/`](docs/README.md).

## Versioning

Follows [Semantic Versioning](https://semver.org). The public API is stable from `1.0.0` onwards: breaking changes require a major version bump. See [CHANGELOG.md](CHANGELOG.md) for release history.

## Licence

MIT
