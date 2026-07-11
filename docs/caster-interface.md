# CasterInterface & DefaultCaster

[← Reference](README.md)

Instance-level contract for the [`Caster`](caster.md) conversion API, enabling dependency injection and mocking in consumer tests — the static `Caster` class is `final`, so it cannot be mocked. `DefaultCaster` is the canonical implementation: a stateless facade delegating every call to the corresponding static method.

```php
use Rak200\Caster\CasterInterface;
use Rak200\Caster\DefaultCaster;
```

## Contents

- [Surface](#surface)
- [Dependency injection](#dependency-injection)
- [Mocking in tests](#mocking-in-tests)

---

## Surface

`CasterInterface` mirrors the full static API one-to-one — same signatures, same defaults, same exceptions. See [caster.md](caster.md) for the semantics of each method.

| Throwing                        | Null-returning twin                |
|---------------------------------|------------------------------------|
| `toString` … `toCollection` (the 9 universal converters) | `tryToString` … `tryToCollection` |
| `cast(Castable $value)`         | `tryCast(Castable $value)`         |
| `toJson(mixed $value, int $flags = JSON_PRETTY_PRINT)` | `tryToJson(...)` |

```php
$caster = new DefaultCaster();

$caster->toInt('17');        // 17
$caster->tryToInt('abc');    // null
$caster->toJson(['a' => 1]); // "{\n    \"a\": 1\n}"
```

[↑ Back to top](#casterinterface--defaultcaster)

---

## Dependency injection

Type-hint the interface; bind `DefaultCaster` as the implementation (most containers autowire this with a single alias):

```php
final class PriceNormalizer
{
    public function __construct(private readonly CasterInterface $caster) {}

    public function normalize(mixed $raw): BcMath\Number
    {
        return $this->caster->toNumber($raw);
    }
}

new PriceNormalizer(new DefaultCaster());   // production wiring
```

[↑ Back to top](#casterinterface--defaultcaster)

---

## Mocking in tests

Consumer tests can stub any conversion without touching real coercion logic:

```php
$caster = $this->createStub(CasterInterface::class);
$caster->method('toNumber')->willReturn(new BcMath\Number('9.99'));

$normalizer = new PriceNormalizer($caster);
(string) $normalizer->normalize('anything');   // '9.99'
```

[↑ Back to top](#casterinterface--defaultcaster)
