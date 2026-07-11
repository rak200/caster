# Caster

[← Reference](README.md)

Static utility class for converting values between PHP types. Dispatches to the appropriate contract method when the value implements one of the [Castable contracts](contracts.md), and falls back to native PHP coercions for primitives. Every converter throws `InvalidArgumentException` for values it cannot convert.

```php
use Rak200\Caster\Caster;
```

## Contents

- [`toString`](#tostring)
- [`toInt`](#toint)
- [`toFloat`](#tofloat)
- [`toBool`](#tobool)
- [`toArray`](#toarray)
- [`toNumber`](#tonumber)
- [`toDateTime`](#todatetime)
- [`toEnum`](#toenum)
- [`toCollection`](#tocollection)
- [`cast`](#cast)
- [`toJson`](#tojson)

---

## `toString`

```php
Caster::toString(mixed $value): string
```

Resolution order: `string` as-is → `int` / `float` / `Stringable` via `(string)` → `ToInt` / `ToFloat` / `ToNumber` contract value as string → `bool` / `ToBool` as `'true'` / `'false'` → `ToDateTime` formatted as ISO 8601 (`format('c')`) → `ToEnum` as its backing value (backed) or case name (pure) → `ToCollection` materialised and JSON-encoded → any other `array` / `object` via [`toJson`](#tojson).

```php
Caster::toString('abc');          // 'abc'
Caster::toString(42);             // '42'
Caster::toString(1.5);            // '1.5'
Caster::toString(true);           // 'true'
Caster::toString([1, 2, 3]);      // "[\n    1,\n    2,\n    3\n]" — pretty-printed JSON
Caster::toString(null);           // throws InvalidArgumentException

enum Status: string { case Active = 'active'; }
final class Current implements ToEnum
{
    public function toEnum(): Status
    {
        return Status::Active;
    }
}
Caster::toString(new Current());  // 'active'
```

[↑ Back to top](#caster)

---

## `toInt`

```php
Caster::toInt(mixed $value): int
```

Resolution order: `int` as-is → `ToInt` → `ToFloat` truncated by `(int)` → `ToNumber` via its string form → `ToBool` as `1` / `0` → `ToDateTime` as its **Unix timestamp** → int-backed `ToEnum` as its backing value → `float` / `bool` via `(int)` → strictly numeric `string` / `Stringable` via `(int)`. Non-numeric or whitespace-padded strings **throw** — they are never coerced to `0`.

```php
Caster::toInt(42);        // 42
Caster::toInt(3.9);       // 3   (truncation, not rounding)
Caster::toInt(true);      // 1
Caster::toInt('17');      // 17
Caster::toInt('3.9');     // 3   (numeric string, truncated)
Caster::toInt('abc');     // throws InvalidArgumentException (not numeric)
Caster::toInt(' 5 ');     // throws InvalidArgumentException (surrounding whitespace)
Caster::toInt(null);      // throws InvalidArgumentException
Caster::toInt([1]);       // throws InvalidArgumentException
```

A string-backed or pure `ToEnum` does **not** match the enum branch — it falls through and throws unless another branch applies.

[↑ Back to top](#caster)

---

## `toFloat`

```php
Caster::toFloat(mixed $value): float
```

Resolution order: `float` as-is → `ToFloat` → `ToInt` via `(float)` → `ToNumber` via its string form → `ToBool` as `1.0` / `0.0` → `ToDateTime` as epoch seconds **with microseconds** (`getTimestamp()` plus the microsecond fraction — correct for pre-epoch instants too) → `ToEnum` whose scalar is numeric, parsed by `Num::parseFloat` → `int` / `bool` via `(float)` → strictly numeric `string` / `Stringable` via `(float)`. Non-numeric or whitespace-padded strings **throw** — they are never coerced to `0.0`.

```php
Caster::toFloat(1.5);      // 1.5
Caster::toFloat(42);       // 42.0
Caster::toFloat('3.14');   // 3.14
Caster::toFloat(false);    // 0.0
Caster::toFloat('abc');    // throws InvalidArgumentException (not numeric)
Caster::toFloat(null);     // throws InvalidArgumentException
```

[↑ Back to top](#caster)

---

## `toBool`

```php
Caster::toBool(mixed $value): bool
```

Resolution order: `bool` as-is → `BcMath\Number` compared numerically to zero → `ToBool` → `ToInt` / `ToFloat` via `(bool)` → `ToNumber` compared numerically to zero → `int` / `float` / `string` / `Stringable` via `(bool)` (PHP semantics: `''`, `'0'`, `0`, `0.0` are false) → `array` / `ToArray` **emptiness** (`!== []`) → `ToCollection` **emptiness**, decided lazily from the first element (the iterable is never materialised).

A zero `Number` is false at **any scale** — string truthiness would call `'0.00'` true.

```php
use BcMath\Number;

Caster::toBool(1);                  // true
Caster::toBool(0.0);                // false
Caster::toBool('0');                // false
Caster::toBool('false');            // true  (non-empty, non-'0' string — PHP semantics)
Caster::toBool(new Number('0.00')); // false (zero at any scale)
Caster::toBool([]);                 // false (empty array)
Caster::toBool([0]);                // true  (non-empty array)
Caster::toBool(null);               // throws InvalidArgumentException
```

[↑ Back to top](#caster)

---

## `toArray`

```php
Caster::toArray(mixed $value): array
```

Resolution order: `array` as-is → `ToArray` → `ToCollection` materialised → any other `Traversable` materialised. Materialisation spreads the iterable, so **keys are preserved**.

```php
Caster::toArray([1, 2]);                        // [1, 2]
Caster::toArray(new ArrayIterator(['a' => 1])); // ['a' => 1]
Caster::toArray('abc');                         // throws InvalidArgumentException
```

[↑ Back to top](#caster)

---

## `toNumber`

```php
Caster::toNumber(mixed $value): BcMath\Number
```

Arbitrary-precision conversion. Resolution order: `BcMath\Number` as-is → `ToNumber` → `ToInt` / `ToFloat` / `ToBool` wrapped into a `Number` → `ToEnum` whose scalar is numeric → `bool` as `1` / `0` → any strict numeric value (`int`, `float`, numeric string — no surrounding whitespace) via `Num::parseNumber` → `Stringable` whose string form is numeric.

Floats are expanded to their exact decimal form, so values whose string form is scientific notation (`1.0E-7`) convert cleanly. Non-finite floats (`NAN`, `INF`) throw `InvalidArgumentException` — they have no arbitrary-precision representation.

```php
use BcMath\Number;

Caster::toNumber(new Number('1.23'));   // the same Number instance
Caster::toNumber(42);                   // Number('42')
Caster::toNumber('0.1');                // Number('0.1') — exact, no float rounding
Caster::toNumber(0.0000001);            // Number('0.00000010') — scientific form expanded
Caster::toNumber(true);                 // Number('1')
Caster::toNumber('12 ');                // throws InvalidArgumentException (not strictly numeric)
Caster::toNumber(NAN);                  // throws InvalidArgumentException (non-finite)
```

[↑ Back to top](#caster)

---

## `toDateTime`

```php
Caster::toDateTime(mixed $value): DateTimeImmutable
```

Resolution order: `DateTimeImmutable` as-is → mutable `DateTime` converted via `createFromMutable` → `ToDateTime` → `ToInt` / `int` interpreted as a **Unix timestamp** → `string` / `Stringable` parsed by the `DateTimeImmutable` constructor (anything `strtotime`-parseable; a malformed string throws PHP's `DateMalformedStringException`, not `InvalidArgumentException`).

```php
Caster::toDateTime(0);                       // 1970-01-01T00:00:00+00:00
Caster::toDateTime('2026-07-10 12:00:00');   // DateTimeImmutable('2026-07-10 12:00:00')
Caster::toDateTime('tomorrow noon');         // shape: DateTimeImmutable (relative formats work)
Caster::toDateTime(null);                    // throws InvalidArgumentException
```

[↑ Back to top](#caster)

---

## `toEnum`

```php
Caster::toEnum(mixed $value, class-string<T> $enumClass = UnitEnum::class): T
```

Converts to a case of `$enumClass`. Resolution order:

1. `$enumClass` must be an enum (or `UnitEnum` itself) — otherwise throws.
2. A value that already **is** a case of `$enumClass` passes through.
3. A `ToEnum` object whose case is an instance of `$enumClass` passes through.
4. Otherwise a scalar is extracted (`ToInt` / `int` / `Stringable` / `string`) and matched: **backed enums** by backing value — the scalar is coerced to the backing type first, so `'2'` matches an int-backed case and `2` a string-backed `'2'` — then any enum by **case name**.

With the bare `UnitEnum::class` default, only steps 2–3 can succeed — pass a concrete enum class to convert scalars.

```php
enum Priority: int { case Low = 1; case High = 2; }
enum Suit { case Hearts; case Spades; }

Caster::toEnum(Priority::Low, Priority::class);   // Priority::Low (pass-through)
Caster::toEnum(2, Priority::class);               // Priority::High (backing value)
Caster::toEnum('2', Priority::class);             // Priority::High (numeric string coerced)
Caster::toEnum('Low', Priority::class);           // Priority::Low (case name)
Caster::toEnum('Hearts', Suit::class);            // Suit::Hearts (case name)
Caster::toEnum(Suit::Hearts);                     // Suit::Hearts (already an enum case)
Caster::toEnum('Clubs', Suit::class);             // throws InvalidArgumentException
Caster::toEnum('Hearts', DateTime::class);        // throws InvalidArgumentException (not an enum)
```

[↑ Back to top](#caster)

---

## `toCollection`

```php
Caster::toCollection(mixed $value): iterable
```

Resolution order: `array` / `Traversable` as-is (**not** materialised — a generator stays lazy) → `ToCollection` → `ToArray`.

```php
Caster::toCollection([1, 2]);            // [1, 2]
Caster::toCollection($someGenerator);    // the same generator, untouched
Caster::toCollection('abc');             // throws InvalidArgumentException
```

[↑ Back to top](#caster)

---

## `cast`

```php
Caster::cast(Castable $value): string|int|float|bool|array|BcMath\Number|DateTimeImmutable|UnitEnum|Traversable
```

Dispatches a [`Castable`](contracts.md#castable) object to its typed value. When the object implements several contracts, the **first match wins**:

1. `ToJson` → `toJson()` : `string`
2. `ToString` → `__toString()` : `string`
3. `ToNumber` → `toNumber()` : `BcMath\Number`
4. `ToInt` → `toInt()` : `int`
5. `ToFloat` → `toFloat()` : `float`
6. `ToBool` → `toBool()` : `bool`
7. `ToDateTime` → `toDateTime()` : `DateTimeImmutable`
8. `ToEnum` → `toEnum()` : `UnitEnum`
9. `ToCollection` → `toCollection()` : `iterable`
10. `ToArray` → `toArray()` : `array`

Throws `InvalidArgumentException` when the object implements only the marker `Castable` interface.

```php
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

Caster::cast(new Money(1999));   // '{"cents":1999}' — ToJson outranks ToInt and ToFloat
```

[↑ Back to top](#caster)

---

## `toJson`

```php
Caster::toJson(mixed $value, int $flags = JSON_PRETTY_PRINT): string
```

Encodes any value as JSON:

- **`ToJson` objects** delegate directly to their `toJson()` — `$flags` is **ignored**, the object controls its own encoding.
- **Other `Castable` objects** go through [`cast()`](#cast) first, then the result is encoded.
- **`Traversable`s** — plain ones and those produced by `cast()` (e.g. a `ToCollection` generator) — are **materialised** before encoding; `json_encode()` does not iterate them and would silently emit `'{}'`.
- **Everything else** is encoded directly via utils' `Json::encode`, which always adds `JSON_THROW_ON_ERROR` — encoding failures throw `JsonException`, never return `false`.

```php
Caster::toJson(['a' => 1]);      // "{\n    \"a\": 1\n}" — pretty-printed by default
Caster::toJson(['a' => 1], 0);   // '{"a":1}' — compact
Caster::toJson(new Money(1999)); // '{"cents":1999}' — ToJson object, flags ignored
Caster::toJson(new ArrayIterator([1, 2]), 0);  // '[1,2]' — Traversable materialised
Caster::toJson(fopen('php://memory', 'r'));   // throws JsonException (resource)
```

[↑ Back to top](#caster)
