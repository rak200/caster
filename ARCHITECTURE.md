# Architecture

Why `rak200/caster` is shaped the way it is, and what was rejected on the way. The reference for
*what* each method does is [`docs/`](docs/README.md); this is the half a consumer needs in order to
judge whether the library fits, and to understand the choices that are visible from outside it.

## Conversions refuse rather than coerce

The premise of the library is that a conversion which cannot be made is an error, not a zero.
`Caster::toInt('abc')` throws where PHP's `(int) 'abc'` returns `0`, and the same holds for
whitespace-padded numerics, `null`, and resources.

That is what the `try*` twins are for. Every converter has one — `tryToInt`, `tryToString`, and so
on — returning `null` instead of throwing, so a caller who genuinely wants "convert or nothing"
says so at the call site rather than wrapping every call in `try`/`catch`. The throwing form is the
default because the silent form is what the library exists to avoid; making the null form opt-in
keeps the mistake explicit.

`tryToEnum` swallows one failure the others do not: an `$enumClass` that is not an enum at all.
It is a programming error rather than a data error, but the twin's contract is "null for any
failure", and a twin with an exception carved out of it is worse to reason about than one without.

### The limit that is documented rather than guarded

A numeric **string** beyond the int64 range saturates at `PHP_INT_MAX` instead of throwing.
Deciding it exactly needs arbitrary-precision arithmetic: `(float) '9223372036854775807'` rounds
*up* to 2⁶³, so a float comparison would refuse a string that fits perfectly. That is more
machinery than the string path warrants, so the behaviour is stated in `docs/caster.md` and pinned
by a test rather than fixed.

Floats are guarded, and the asymmetry is deliberate: `toInt` refuses `NAN`, the infinities, and any
magnitude beyond the int64 range, because PHP's cast wraps those silently — `9.3e18` came back as
`-9146744073709551616`, sign and all. PHP 8.5 raises a diagnostic on that same cast, which is what
turned a documented quirk into a defect worth fixing: on 8.5 the unguarded version emitted warnings
in consumer code, and a framework converting warnings to exceptions would have made `tryToInt`
throw.

`toNumber` has neither limitation and is the conversion to reach for when magnitude is unbounded.

## One runtime dependency, against the estate's own rule

The shared PHP standard says **no runtime Composer dependencies**. caster deviates: it requires
`rak200/utils` at runtime, and the converters are built on its `Type`, `Enum`, `Num`, `Iter`, `Dt`
and `Json` helpers.

The deviation is the prefer-library-over-native rule applied across libraries rather than within
one. The alternative was to reimplement strict numeric parsing, enum coercion, ISO-8601 formatting
and throwing JSON encode inside this package — the same code that already exists next door, and
that `rak200/http-input` would then hold a third copy of. A rule against dependencies exists to
stop a library dragging a tree behind it; utils is one package with no dependencies of its own, so
the cost it imposes is the cost of the rule, not of the tree the rule was written against.

### `iterator_to_array` is the one native kept

Materialisation must preserve keys for an **arbitrary** `Traversable`. utils' `Iter::toArray()`
binds `TKey of array-key`, which cannot resolve against the unconstrained iterables `Caster`
accepts — adopting it would need a PHPStan suppression here or a weaker generic there. Neither is
worth it for one call, so the native stays, imported via `use function` with the reason at the
import.

## Contract dispatch has a fixed priority

An object declares what it can be reduced to by implementing small single-method contracts, and
`cast()` dispatches on the first match in a fixed order:

```
ToJson → ToString → ToNumber → ToInt → ToFloat → ToBool → ToDateTime → ToEnum → ToCollection → ToArray
```

The order runs from **most specific representation to least**. `ToJson` leads because an object
that knows how to serialise itself has said the most about its own shape — and the encoding is
its own, so `toJson()`'s `$flags` are ignored for such objects rather than second-guessed.
`ToArray` trails because nearly anything can be expressed as an array; a value that reaches the
last arm has told us the least.

The alternative — dispatching on what the *caller* asked for — is what the universal converters
already do. `cast()` exists for the other question: "reduce this object to whatever it says it is".
Two mechanisms for one question would be the mistake; one for each of two questions is the point.

**Contracts outrank being a native type.** A value that both implements a contract and is natively
iterable is decided by its contract: `toBool` on an object implementing `ToCollection` reads the
collection the contract reports, even when iterating the object directly would yield something
else. The iterable arms are ordered so that the native one is always last.

## A static class with an interface it does not implement

`Caster` is `final` and static. `CasterInterface` mirrors its whole surface as instance methods,
and `DefaultCaster` implements the interface as one-line delegations to the statics.

The mirror exists because a `final` static class cannot be mocked, and consumers testing their own
code should not have to exercise real coercion to do it. They type-hint `CasterInterface`, bind
`DefaultCaster` in production, and stub the interface in tests.

**`Caster` deliberately does not implement `CasterInterface`** — it cannot, the methods are static.
The consequence is worth stating because it caught us: PHPStan checks `DefaultCaster` against the
interface, which keeps *that pair* consistent while both drift away from the static class. Nothing
in the toolchain compares `Caster` to `CasterInterface` at all. `CasterInterfaceTest` is that
comparison, by reflection — method sets both ways, parameter names, types and defaults, return
types, and declared `@throws`.

## Not on Packagist

Consumers add this repository and `rak200/utils` as `"type": "vcs"` entries and resolve versions
from git tags.

Composer reads `repositories` only from the root project, so the requirement travels to every
consumer rather than being declared once here — a consuming project must list **both** repositories,
not just this one. Publishing would remove that, and it is not scheduled: the decision belongs with
the ecosystem rather than with this library alone.
