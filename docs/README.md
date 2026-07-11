# Reference

Per-class API reference with runnable examples. For installation and a package overview, see the [top-level README](../README.md).

| Class                  | Doc                          | What it covers |
| ---------------------- | ---------------------------- | -------------- |
| `Caster`               | [caster.md](caster.md)       | Universal converters (`to*` + null-returning `try*` twins), contract dispatch (`cast`), JSON encoding (`toJson`) |
| `CasterInterface` + `DefaultCaster` | [caster-interface.md](caster-interface.md) | Instance-level mirror of the `Caster` API for dependency injection and mocking |
| `Castable` + contracts | [contracts.md](contracts.md) | The marker interface and the 10 typed casting contracts (`ToArray` … `ToString`) |

The contracts are single-method interfaces that form one cohesive concept, so they share a single page instead of one page each; `CasterInterface` and its canonical implementation pair up the same way.

## Conventions used in these docs

- Output is shown in trailing `// …` comments next to each call.
- All snippets assume the relevant `use Rak200\Caster\...;` imports shown at the top of each file.
- Every converter throws `InvalidArgumentException` for values it cannot convert; only the non-obvious throw conditions are called out per method.
