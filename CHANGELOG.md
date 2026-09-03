# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.2](https://github.com/rak200/caster/compare/3.1.1...3.1.2) (2026-09-03)


### Bug Fixes

* reject floats that no int can represent, and document the limit that remains ([#30](https://github.com/rak200/caster/issues/30)) ([c0c8935](https://github.com/rak200/caster/commit/c0c89358826caf92e5b8e297c53006a665828ab2))

## [3.1.1](https://github.com/rak200/caster/compare/3.1.0...3.1.1) (2026-08-30)


### Bug Fixes

* make every PHPStan suppression say what it covers and why ([#17](https://github.com/rak200/caster/issues/17)) ([53298e9](https://github.com/rak200/caster/commit/53298e9c5af4d1f69dd02fed975beb764e2a67ff))

## [3.1.0] - 2026-07-25

Materialising an iterable now keeps every key, and the enum and date-time converters compose the primitives added by `rak200/utils` 4.4.0 instead of reimplementing them.

### Fixed
- **`Caster::toArray()` and `Caster::toJson()` no longer renumber int keys.** Materialisation used the spread operator, which preserves string keys but *renumbers int ones* — so `toArray(new ArrayIterator([5 => 'a']))` returned `[0 => 'a']` while the documentation promised keys were preserved. Both now materialise with `iterator_to_array($value, true)`. **Observable change** for an iterable with non-sequential int keys: `toArray` keeps them, and `toJson` (including the `ToCollection` branch of `toString`) encodes such a value as a JSON **object** rather than an array. Lists and string keys are byte-for-byte unchanged, which is why no existing test caught it

### Changed
- **`rak200/utils` requirement raised `^4.3` → `^4.4`** — `Caster` now calls `Enum::tryFromValue`, `Dt::fromInterface`, `Dt::toEpoch` and `Dt::toEpochFloat`, all new in 4.4.0
- `Caster::toEnum()` delegates the backed-value lookup to `Enum::tryFromValue()`, replacing the local backing-type branch (derive the backing type from `cases()[0]`, coerce the scalar, `tryFrom`) with a two-step `?? Enum::tryFromName()` chain. Behaviour is identical — the same coercion now lives in utils, where `rak200/http-input` duplicates it too
- `Caster::toDateTime()` collapses its `DateTimeImmutable` and `DateTime` arms into a single `DateTimeInterface` arm through `Dt::fromInterface()`; an already-immutable instance is still returned as the same instance. Safe because PHP forbids userland implementations of `DateTimeInterface`, so the arm covers exactly the two former ones
- `Caster::toInt()` and `Caster::toFloat()` read `ToDateTime` values through `Dt::toEpoch()` / `Dt::toEpochFloat()`. The pre-epoch microsecond correction — and the `@infection-ignore-all` documenting it — moves to utils, where it is stated once instead of at every consumer
- In-code `@infection-ignore-all` suppressions in `Caster` drop from four to three
- **Deliberate exception to the prefer-utils rule**, recorded in `CLAUDE.md`: materialisation keeps the native `iterator_to_array` (imported via `use function`) rather than `Iter::toArray($value, true)`. The helper binds `TKey of array-key`, which cannot resolve against the unconstrained `Traversable` that `Caster` accepts; adopting it would require a PHPStan suppression or a weaker generic in utils

## [3.0.3] - 2026-07-25

Maintenance release: the converters are rebuilt on the helpers added by `rak200/utils` 4.2/4.3. No behaviour change — every conversion, exception and edge case is identical.

### Changed
- **`rak200/utils` requirement raised `^4.0` → `^4.3`** — the converters now call `Enum::intOrNull` (4.3.0) and `Enum::isInt` (4.2.0), so `^4.0` would resolve to a version without them. Consumers must accept the newer utils; caster's own public API is untouched
- `Caster::toInt()` reads an int-backed `ToEnum` through `Enum::intOrNull` instead of pairing `Enum::isBackedInt` with `Enum::scalar` — a single value-extracting read replaces predicate-then-extract, and the `(int)` cast (with its `@infection-ignore-all`) disappears
- `Caster::toFloat()` resolves its `ToEnum`, `string` and `Stringable` arms with `Num::parseFloatOrNull` instead of gating on `Num::is` and re-parsing the same value — one pass instead of two (the shared "no redundant second pass" rule), and the `(float) (string)` double cast (with its `@infection-ignore-all`) disappears
- `Caster::toBool()` compares `Number` values — direct instances and `ToNumber` results — to zero via `Num::sign(…) !== 0` rather than a loose `!= new Number('0')`
- `Caster::toEnum()` detects the target's backing type with `Enum::isInt($cases[0])` (utils 4.2.0), whose `BackedEnum` parameter narrows `$case->value` exactly in both branches, replacing `Type::isInt($cases[0]->value)`
- `Caster::toString()` and `Caster::toFloat()` format date-times through utils' `Dt::iso` / `Dt::format` instead of calling `DateTimeInterface::format()` directly (prefer-lib-over-native); `Dt::iso` emits `DateTimeInterface::ATOM`, byte-for-byte the previous `format('c')`
- In-code `@infection-ignore-all` suppressions in `Caster` drop from six to four; the 100% MSI gate stays closed and the changed lines mutate clean (83 mutants, all killed)

### Removed
- Roadmap emptied in `CLAUDE.md`: the **Fluent API** idea moved to the devr proposal process as RFC 0015 (`rak200/fluent-utils`, which would consume caster's public API as a regular dependency); **`Caster::all()`** was dropped as already covered by `array_map` plus first-class callables; the **custom converter registry** was dropped for its stringly-typed dispatch, global mutable state and mixed return types. caster's surface is unaffected by all three

## [3.0.2] - 2026-07-17

Test-quality release: the Infection mutation-testing gate is enforced at **100% MSI**. No behaviour change.

### Added
- **Infection MSI gate** — `minMsi: 100` / `minCoveredMsi: 100` in `infection.json5.dist`, enforced in CI by a floor-only `composer infection -- --logger-github` step (PHP 8.4 job), and advertised by a new MSI badge in the README

### Changed
- Reached 100% MSI (from 98%): six provably-equivalent survivors — the PHPStan-only casts in `toString`/`toInt`/`toFloat` and the re-cast `Stringable` in `toEnum` — are now suppressed in-code with narrowly-anchored `@infection-ignore-all` comments, placed on the smallest node so the same-line condition mutators (`&&`, `instanceof`, arithmetic) stay live
- `Caster::toNumber()` drops the redundant `$value instanceof Number => $value` fast-path arm: a `BcMath\Number` already falls through to `Num::is($value) => Num::parseNumber($value)`, which returns the **same instance** (identity preserved — `assertSame` stays green), so removing it is behaviour-neutral and kills the seventh survivor, which `@infection-ignore-all` cannot suppress because Infection's `MatchArmRemoval` targets the parent `Match_` node rather than the annotated arm
- CI: `codecov/codecov-action` bumped v5 → v7, dropping the deprecated Node 20 `github-script` transitive dependency

## [3.0.1] - 2026-07-15

Tooling and test-quality release: no library code changed.

### Added
- **Mutation testing** — Infection (`infection/infection`) as a dev dependency, configured via `infection.json5.dist` and run with the new `composer infection` script. The suite sits at **98% MSI**; the seven remaining survivors are provably-equivalent mutants (PHPStan-only casts, a `final BcMath\Number` passthrough, and a re-cast `Stringable` — documented in `CLAUDE.md`), so the `minMsi=100` gate and its CI step are deferred to a follow-up, never met by lowering the threshold

### Changed
- Test suite strengthened while raising the mutation score from 82% to 98% (no behaviour change): exact exception-message assertions (`expectExceptionMessageIs`) on every converter's throw path, plus new coverage for `toString` typed-contract dispatch order (`ToInt`/`ToFloat`/`ToBool`/`ToCollection` arms), `toInt`/`toFloat` on a `ToBool` value, `toBool` on a falsy `Stringable`, `toEnum` backing-value-before-name priority and int-before-string extraction, and the `tryToString`/`tryToJson` non-`InvalidArgumentException` catch paths (`JsonException`, marker-only `Castable`)

## [3.0.0] - 2026-07-11

Roadmap release: the `try*` family and the DI-ready instance API land, and the last non-uniform exception (`toDateTime`) is aligned.

### Added
- **`try*` family** — a null-returning twin for every conversion method: `tryToString`, `tryToInt`, `tryToFloat`, `tryToBool`, `tryToArray`, `tryToNumber`, `tryToDateTime`, `tryToEnum` (null for any failure, a non-enum `$enumClass` included), `tryToCollection`, `tryCast` (null for a marker-only `Castable`) and `tryToJson` (null on encoding failure)
- **`CasterInterface`** — instance-level contract mirroring the full static API (converters, `try*` twins, `cast`, `toJson`) to enable dependency injection and mocking in consumer tests
- **`DefaultCaster`** — canonical stateless `CasterInterface` implementation, delegating every method to the static `Caster`

### Changed
- **Breaking:** `Caster::toDateTime()` parses strings via utils' `Dt::parse` — a malformed string now throws `InvalidArgumentException` (previously PHP's `DateMalformedStringException` leaked through), so "every converter throws `InvalidArgumentException`" now holds without exceptions; int timestamps go through `Dt::fromEpoch`
- `Caster::toString()` / `Caster::toJson()` docblocks now declare their full exception surface (`JsonException` on the JSON-encoding branch of `toString`; `InvalidArgumentException` for a marker-only `Castable` in `toJson`)

## [2.0.0] - 2026-07-11

Correctness release: every defect found in a full-project review, fixed at the right layer (several fixes landed in `rak200/utils` 3.1.0/4.0.0 and are consumed here).

### Changed
- **Breaking:** `Caster::toInt()` and `Caster::toFloat()` now require strictly numeric strings/Stringables (no surrounding whitespace) and throw `InvalidArgumentException` instead of silently coercing non-numeric input to `0` / `0.0`
- `Caster::toBool()` compares `BcMath\Number` values (direct instances and `ToNumber` results) numerically to zero — a zero `Number` is false at any scale (`'0.00'` included), where string truthiness said true
- `Caster::toBool()` decides `ToCollection` emptiness lazily via utils' `Iter::isNotEmpty` instead of materialising the whole iterable (unbounded generators no longer hang)
- Bumped the `rak200/utils` requirement from `^3.0` to `^4.0`: `Iter::isNotEmpty` (lazy `ToCollection` emptiness), the widened `Num::parseNumber` (float-accepting, non-finite-safe — `toNumber()` now delegates to it directly with no local exception-mapping helpers), and utils' new SPL exception contract, whose invalid-input failures are already `InvalidArgumentException` and therefore match `Caster`'s documented contract natively
- `Caster::toEnum()` coerces the extracted scalar to the enum's backing type before `tryFrom()`, so `'2'` matches an int-backed case and `2` a string-backed `'2'`; case-name matching now also reachable for backed enums given a non-numeric string
- PHP-CS-Fixer: `concat_space` overridden to one space around the concatenation operator (`'x ' . $y`, never the preset's glued form); the bulk reformat commit is recorded in the new `.git-blame-ignore-revs`, which GitHub honours automatically

### Fixed
- `Caster::toEnum()` crashed with `TypeError` when the scalar's type didn't match the enum's backing type (e.g. numeric string against an int-backed enum)
- `Caster::toJson()` silently encoded `Traversable`s (plain ones and those produced by `cast()`, e.g. a `ToCollection` generator) as `'{}'` — they are now materialised before encoding
- `Caster::toFloat()` returned wrong values for pre-epoch `ToDateTime` instants with a fractional-second component (`format('U.u')` glued negative seconds to positive microseconds)
- `Caster::toNumber()` leaked a `ValueError` for `ToFloat` values whose string form is scientific notation (e.g. `1.0E-7`); they are now expanded exactly via `Num::parseNumber`
- `Caster::toNumber()` leaked a `RuntimeException` (plus a PHP 8.5 coercion warning) for `NAN` / `INF`; non-finite floats now throw `InvalidArgumentException`

## [1.4.0] - 2026-07-10

### Added
- Per-class reference under `docs/`: `docs/caster.md` (resolution order and runnable examples for every converter), `docs/contracts.md` (the `Castable` marker and the 10 typed contracts, one section each) and the `docs/README.md` index
- README badges: CI, Codecov coverage, latest tag, PHP version, PHPStan level, code style, license, SemVer, Keep a Changelog
- `ext-bcmath` declared explicitly in `require` (used directly by `Caster::toNumber()`)
- PHP-CS-Fixer `^3.75` as a dev dependency, configured with the shared `@PhpCsFixer`-preset ruleset in `.php-cs-fixer.dist.php`; new `cs-check` / `cs-fix` composer scripts

### Changed
- Bumped `rak200/utils` from `^1.8` to `^3.0`; call sites migrated to the 2.0.0 canonical names (`Type::isInstance`, `Type::isSubclass`)
- PHPStan raised from level 8 to level **max** and now analyses `tests/` too; config renamed `phpstan.neon` → `phpstan.neon.dist`, dev dependency bumped to `^2.1`
- Whole codebase reformatted under the `@PhpCsFixer` preset (no behaviour change)
- CI workflow rewritten as `ci.yml` (replacing `tests.yml`): `permissions: contents: read`, `composer validate`, `fail-fast: false` matrix over PHP 8.4/8.5, CS check on the floor job, PHPStan on every job, PHPUnit with `pcov` coverage and Codecov upload
- Composer scripts aligned with the shared conventions: `test` is plain `phpunit` (the Windows `-c php.ini` pin is no longer needed), `analyse` renamed to `phpstan` (with `--memory-limit=512M`)
- `phpunit.xml` now sets `failOnWarning` and `failOnRisky`
- README rewritten: lean overview + installation (the VCS instructions now list **both** required repositories — caster and utils, since Composer reads `repositories` only from the root project), full reference moved to `docs/`, hardcoded version line replaced by the tag badge
- `CLAUDE.md` now imports the shared rak200 PHP conventions file and keeps only caster-specific content

### Fixed
- `Castable` docblock now lists all 10 typed sub-interfaces (was naming only the original 6)

## [1.3.0] - 2026-05-30

### Added
- Pure (non-backed) enum support: `Caster::toEnum()` matches a pure enum by case name, and `Caster::toString()` renders a pure enum case as its name

### Changed
- `ToEnum::toEnum()` return type widened from `\BackedEnum` to `\UnitEnum` (covariant — implementers may still return a `\BackedEnum`)
- `Caster::toEnum()` signature now takes `class-string<\UnitEnum>`, and `Caster::cast()`'s return type widened to include `\UnitEnum`
- Enum/numeric converters now route through `rak200/utils` 1.8.0 helpers — `Enum::scalar()`, `Enum::isBackedInt()`, and the `Num::is()` gate with `Num::parseFloat()`/`Num::parseNumber()` — removing the private `unitEnumScalar()`, `backedEnumValue()` and `numberFromString()` helpers
- `Caster::toInt()` accepts only **int-backed** enum cases; string-backed cases (numeric or not) and pure enums now throw `InvalidArgumentException`
- `Caster::toFloat()` and `Caster::toNumber()` require a **numeric** scalar: int-backed and numeric string-backed enums convert, while non-numeric string-backed cases and pure enums now throw `InvalidArgumentException`
- Bumped the `rak200/utils` requirement from `^1.0` to `^1.8`

## [1.2.0] - 2026-05-27

### Added
- Universal converter methods on `Caster`, mirroring `toString()`'s pattern: `toInt()`, `toFloat()`, `toBool()`, `toArray()`, `toNumber()`, `toDateTime()`, `toEnum()`, `toCollection()`
- All new methods accept `mixed`, dispatch to matching contracts (with specific contracts winning over generic `Stringable` fallback), and throw `InvalidArgumentException` for unconvertible types
- `Caster::toEnum()` requires the target `class-string<\BackedEnum>` to disambiguate; `Caster::toDateTime()` interprets integers as Unix timestamps
- 96 new tests covering every branch of the new converters

## [1.1.0] - 2026-05-27

### Added
- New contracts: `ToNumber` (→ `\BcMath\Number`), `ToDateTime` (→ `\DateTimeImmutable`), `ToEnum` (→ `\BackedEnum`), `ToCollection` (→ `iterable`)
- `Caster::cast()` dispatches to the four new contracts; return type widened to `string|int|float|bool|array|\BcMath\Number|\DateTimeImmutable|\BackedEnum|\Traversable`
- `Caster::toString()` handles the new contracts with natural string outputs: ISO 8601 for `ToDateTime`, backed value for `ToEnum`, materialised iterable as JSON for `ToCollection`
- `rak200/utils` (`^1.0`) added as a runtime dependency, sourced from GitHub via `repositories`

### Changed
- `Caster::cast()` dispatch priority: `ToJson` → `ToString` → `ToNumber` → `ToInt` → `ToFloat` → `ToBool` → `ToDateTime` → `ToEnum` → `ToCollection` → `ToArray`
- Improvement roadmap in `CLAUDE.md` translated to English; "New contracts" item marked as done

## [1.0.1] - 2026-05-26

### Changed
- Built-in PHP functions imported via grouped `use function` declarations in `Caster.php` and test files; removed `\` prefix from call sites

## [1.0.0] - 2026-05-16

### Added
- GitHub Actions CI workflow (PHP 8.4 and 8.5)
- `keywords`, `homepage` and `support` fields in `composer.json`
- README: examples for all contracts (`ToArray`, `ToBool`, `ToFloat`, `ToInt`, `ToJson`, `ToString`)
- README: error handling section with safe usage patterns
- README: dispatch priority documentation
- `.gitattributes`: exclude `.github/` from Composer package

### Changed
- Exception messages capitalised: `"Cannot stringify"`, `"Cannot cast"`
- Built-in PHP functions prefixed with `\` in `Caster.php` for compiler optimisation

## [0.1.0] - 2026-05-16

### Added
- PHPUnit 13 test suite: 45 tests covering `Caster::toString()`, `Caster::cast()`,
  `Caster::toJson()` and `BcMath\Number` integration
- PHPDoc on all classes and interface members, including `@author` tags
- `phpunit.xml` configuration
- `.gitignore`
- `composer test` script

### Changed
- **Breaking:** namespace renamed from `Ricardo\Caster` to `Rak200\Caster`
- `Caster::toJson()` now documents that `JSON_THROW_ON_ERROR` is always added to `$flags`
- `ToString` interface now explicitly declares `__toString(): string`

## [0.0.1] - 2026-05-14

### Added
- `Caster` static utility class with `toString()`, `cast()` and `toJson()` methods
- Type contracts: `Castable`, `ToArray`, `ToBool`, `ToFloat`, `ToInt`, `ToJson`, `ToString`

[3.1.0]: https://github.com/rak200/caster/compare/3.0.3...3.1.0
[3.0.3]: https://github.com/rak200/caster/compare/3.0.2...3.0.3
[3.0.2]: https://github.com/rak200/caster/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/rak200/caster/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/rak200/caster/compare/2.0.0...3.0.0
[2.0.0]: https://github.com/rak200/caster/compare/1.4.0...2.0.0
[1.4.0]: https://github.com/rak200/caster/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/rak200/caster/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/rak200/caster/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/rak200/caster/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/rak200/caster/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/rak200/caster/compare/0.1.0...1.0.0
[0.1.0]: https://github.com/rak200/caster/compare/0.0.1...0.1.0
[0.0.1]: https://github.com/rak200/caster/releases/tag/0.0.1
