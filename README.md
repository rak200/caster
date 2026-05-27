# Caster

Type casting contracts and utilities for PHP 8.4+.

## Installation

```bash
composer require rak200/caster
```

## Contracts

All contracts live under `Rak200\Caster\Contracts` and extend `Castable`.

| Interface  | Method              | Return   |
|------------|---------------------|----------|
| `ToArray`  | `toArray()`         | `array`  |
| `ToBool`   | `toBool()`          | `bool`   |
| `ToFloat`  | `toFloat()`         | `float`  |
| `ToInt`    | `toInt()`           | `int`    |
| `ToJson`   | `toJson()`          | `string` |
| `ToString` | `__toString()`      | `string` |

## Usage

### Implementing a contract

```php
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToJson;
use Rak200\Caster\Contracts\ToString;

class Money implements ToInt, ToFloat, ToJson {
    public function __construct(
        private int $cents,
        private string $currency,
    ) {}

    public function toInt(): int   { return $this->cents; }
    public function toFloat(): float { return $this->cents / 100; }
    public function toJson(): string {
        return json_encode(['cents' => $this->cents, 'currency' => $this->currency]);
    }
}

class Status implements ToBool, ToString {
    public function __construct(private bool $active) {}

    public function toBool(): bool      { return $this->active; }
    public function __toString(): string { return $this->active ? 'active' : 'inactive'; }
}

class Tag implements ToArray {
    public function __construct(private string $name, private string $color) {}

    public function toArray(): array { return ['name' => $this->name, 'color' => $this->color]; }
}
```

### Converting values with `Caster`

```php
use Rak200\Caster\Caster;

// Primitives
Caster::toString(42);           // "42"
Caster::toString(true);         // "true"
Caster::toString([1, 2, 3]);    // JSON string

// Objects implementing a contract
$money = new Money(1999, 'BRL');
Caster::cast($money);           // '{"cents":1999,"currency":"BRL"}' — ToJson wins
Caster::toString($money);       // same, via cast()

$status = new Status(true);
Caster::cast($status);          // 'active' — ToString wins over ToBool
Caster::toString($status);      // 'active'

// JSON encoding
Caster::toJson(['key' => 'value']);   // pretty-printed JSON
Caster::toJson($money);              // delegates to toJson()
Caster::toJson($money, 0);           // compact JSON (flags ignored for ToJson objects)
```

### `Caster::cast()` dispatch order

When an object implements multiple contracts, `cast()` resolves the **first** match:

1. `ToJson`   → `toJson()`
2. `ToString` → `__toString()`
3. `ToInt`    → `toInt()`
4. `ToFloat`  → `toFloat()`
5. `ToBool`   → `toBool()`
6. `ToArray`  → `toArray()`

### Error handling

`Caster::toString()` throws `InvalidArgumentException` for types it cannot convert
(e.g. `null`, `resource`). `Caster::toJson()` throws `\JsonException` on encoding failure.

```php
// Safe pattern
$result = $value !== null ? Caster::toString($value) : 'default';

// Or catch explicitly
try {
    $str = Caster::toString($value);
} catch (\InvalidArgumentException $e) {
    $str = '(unknown)';
}
```

## Versioning

This library follows [Semantic Versioning](https://semver.org).  
Current version: **1.2.0** — see [CHANGELOG.md](CHANGELOG.md) for release history.

## License

MIT
