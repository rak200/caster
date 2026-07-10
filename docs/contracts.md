# Contracts

[← Reference](README.md)

Marker interface for objects that can be dispatched by `Caster::cast()`, plus the 10 typed sub-interfaces that declare which type an object can be reduced to. Every contract lives under `Rak200\Caster\Contracts` and extends `Castable`; each declares exactly one conversion method.

```php
use Rak200\Caster\Contracts\Castable;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToCollection;
use Rak200\Caster\Contracts\ToDateTime;
use Rak200\Caster\Contracts\ToEnum;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToJson;
use Rak200\Caster\Contracts\ToNumber;
use Rak200\Caster\Contracts\ToString;
```

## Contents

- [`Castable`](#castable)
- [`ToArray`](#toarray)
- [`ToBool`](#tobool)
- [`ToCollection`](#tocollection)
- [`ToDateTime`](#todatetime)
- [`ToEnum`](#toenum)
- [`ToFloat`](#tofloat)
- [`ToInt`](#toint)
- [`ToJson`](#tojson)
- [`ToNumber`](#tonumber)
- [`ToString`](#tostring)

---

## `Castable`

The base **marker interface** — it declares no methods. Don't implement it directly: implement one of the typed sub-interfaces below. Its two roles:

- It is the parameter type of [`Caster::cast()`](caster.md#cast), so any contract implementor can be dispatched.
- `instanceof Castable` answers "can this object be cast at all?".

An object may implement **several** contracts; `cast()` resolves them in a fixed priority order (see [`Caster::cast()`](caster.md#cast)), and each universal `Caster::to*()` converter prefers the contract matching its target type.

[↑ Back to top](#contracts)

---

## `ToArray`

`toArray(): array` — the object's array representation.

```php
final class Tag implements ToArray
{
    public function __construct(private string $name, private string $color) {}

    public function toArray(): array
    {
        return ['name' => $this->name, 'color' => $this->color];
    }
}

Caster::toArray(new Tag('urgent', 'red'));   // ['name' => 'urgent', 'color' => 'red']
Caster::cast(new Tag('urgent', 'red'));      // same — ToArray is its only contract
```

[↑ Back to top](#contracts)

---

## `ToBool`

`toBool(): bool` — the object's truth value.

```php
final class FeatureFlag implements ToBool
{
    public function __construct(private bool $enabled) {}

    public function toBool(): bool
    {
        return $this->enabled;
    }
}

Caster::toBool(new FeatureFlag(true));    // true
Caster::toString(new FeatureFlag(true));  // 'true' — toString() understands ToBool too
```

[↑ Back to top](#contracts)

---

## `ToCollection`

`toCollection(): iterable` — the object's elements as an iterable (an `array` or any `Traversable`, including generators).

```php
final class Basket implements ToCollection
{
    /** @param list<string> $items */
    public function __construct(private array $items) {}

    public function toCollection(): iterable
    {
        yield from $this->items;
    }
}

$basket = new Basket(['apple', 'pear']);
Caster::toCollection($basket);   // Generator yielding 'apple', 'pear'
Caster::toArray($basket);        // ['apple', 'pear'] — materialised
```

[↑ Back to top](#contracts)

---

## `ToDateTime`

`toDateTime(): \DateTimeImmutable` — the object's point in time. Immutable by contract: no mutable `DateTime` in the public API.

```php
final class Subscription implements ToDateTime
{
    public function __construct(private string $expiresAt) {}

    public function toDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->expiresAt);
    }
}

$sub = new Subscription('2026-12-31 23:59:59');
Caster::toDateTime($sub);   // DateTimeImmutable('2026-12-31 23:59:59')
Caster::toInt($sub);        // the expiry instant as a Unix timestamp (int)
```

[↑ Back to top](#contracts)

---

## `ToEnum`

`toEnum(): \UnitEnum` — the object's value as an enum case (pure or backed).

```php
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

final class Account implements ToEnum
{
    public function __construct(private bool $active) {}

    public function toEnum(): Status
    {
        return $this->active ? Status::Active : Status::Inactive;
    }
}

Caster::toEnum(new Account(true), Status::class);   // Status::Active
Caster::toString(new Account(true));                // 'active' — the backing value
```

[↑ Back to top](#contracts)

---

## `ToFloat`

`toFloat(): float` — the object's floating-point representation.

```php
final class Percentage implements ToFloat
{
    public function __construct(private int $basisPoints) {}

    public function toFloat(): float
    {
        return $this->basisPoints / 10_000;
    }
}

Caster::toFloat(new Percentage(1250));   // 0.125
```

[↑ Back to top](#contracts)

---

## `ToInt`

`toInt(): int` — the object's integer representation.

```php
final class Money implements ToInt, ToFloat
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
}

Caster::toInt(new Money(1999));     // 1999
Caster::toFloat(new Money(1999));   // 19.99
Caster::cast(new Money(1999));      // 1999 — ToInt outranks ToFloat in cast()
```

[↑ Back to top](#contracts)

---

## `ToJson`

`toJson(): string` — the object's representation as a **ready-made JSON string**. The object controls its own encoding (flags passed to [`Caster::toJson()`](caster.md#tojson) are ignored for `ToJson` objects).

```php
final class ApiError implements ToJson
{
    public function __construct(private int $code, private string $message) {}

    public function toJson(): string
    {
        return json_encode(['code' => $this->code, 'error' => $this->message], JSON_THROW_ON_ERROR);
    }
}

Caster::toJson(new ApiError(404, 'not found'));   // '{"code":404,"error":"not found"}'
Caster::cast(new ApiError(404, 'not found'));     // same — ToJson has top priority in cast()
```

[↑ Back to top](#contracts)

---

## `ToNumber`

`toNumber(): \BcMath\Number` — the object's value as an arbitrary-precision number (PHP 8.4's `BcMath\Number`). Use it where `float` rounding is unacceptable.

```php
use BcMath\Number;

final class ExchangeRate implements ToNumber
{
    public function __construct(private string $rate) {}

    public function toNumber(): Number
    {
        return new Number($this->rate);
    }
}

Caster::toNumber(new ExchangeRate('5.4321'));   // BcMath\Number('5.4321')
Caster::toString(new ExchangeRate('5.4321'));   // '5.4321'
```

[↑ Back to top](#contracts)

---

## `ToString`

`__toString(): string` — the only contract whose method is a **magic method**: it extends both `Castable` and PHP's built-in `Stringable`, so implementors work everywhere a `Stringable` is accepted (string interpolation, `(string)` casts, `Caster::toString()`).

```php
final class Slug implements ToString
{
    public function __construct(private string $value) {}

    public function __toString(): string
    {
        return strtolower(str_replace(' ', '-', $this->value));
    }
}

Caster::toString(new Slug('Hello World'));   // 'hello-world'
(string) new Slug('Hello World');            // 'hello-world' — plain Stringable usage
```

[↑ Back to top](#contracts)
