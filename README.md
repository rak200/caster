# Caster

Type casting contracts and utilities for PHP 8.4+.

## Installation

```bash
composer require ricardo/caster
```

## Contracts

All contracts live under `Ricardo\Caster\Contracts` and extend `Castable`.

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
use Ricardo\Caster\Contracts\ToJson;

class User implements ToJson {
    public function __construct(
        private string $name,
        private string $email,
    ) {}

    public function toJson(): string {
        return json_encode(['name' => $this->name, 'email' => $this->email]);
    }
}
```

### Converting values with `Caster`

```php
use Ricardo\Caster\Caster;

// Primitives
Caster::toString(42);           // "42"
Caster::toString(true);         // "true"
Caster::toString([1, 2, 3]);    // JSON string

// Objects implementing a contract
Caster::toString(new User('Ana', 'ana@example.com'));   // calls toJson() internally
Caster::cast(new User('Ana', 'ana@example.com'));       // calls toJson(), returns string

// JSON encoding
Caster::toJson(['key' => 'value']);   // pretty-printed JSON
Caster::toJson(new User(...));        // delegates to toJson()
```

### `Caster::cast()` dispatch order

`cast()` resolves the first matching contract in this priority:

1. `ToJson` → `toJson()`
2. `ToString` → `__toString()`
3. `ToInt` → `toInt()`
4. `ToFloat` → `toFloat()`
5. `ToBool` → `toBool()`
6. `ToArray` → `toArray()`

## Versioning

This library follows [Semantic Versioning](https://semver.org).  
Current version: **0.0.1** — API is unstable until unit tests are added and `1.0.0` is released.

## License

MIT
