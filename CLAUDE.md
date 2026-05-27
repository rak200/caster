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

## Roadmap de Melhorias

Lista de sugestões levantadas em 2026-05-27. Atualizar conforme forem implementadas.

### 🔴 Alta prioridade

- [x] **Arquivo `LICENSE`** — necessário para uso legal por terceiros (sugestão: MIT)
- [x] **PHPStan no CI** — adicionar análise estática com nível 8+ ao workflow do GitHub Actions

### 🟡 Média prioridade

- [ ] **Métodos universais no `Caster`** — espelhar `toString()` para os demais tipos:
  - `toInt(mixed): int`
  - `toFloat(mixed): float`
  - `toBool(mixed): bool`
  - `toArray(mixed): array`
- [ ] **Métodos `tryTo*`** — variantes que retornam `null` em vez de lançar exceção:
  - `tryToString(mixed): ?string`
  - `tryToInt(mixed): ?int`
  - etc.
- [ ] **`CasterInterface`** — interface para a classe `Caster` permitir injeção de dependência e mocks em testes de consumidores
- [ ] **`Caster::can(mixed $value, string $contract): bool`** — verifica se um valor suporta determinado contrato

### 🟢 Baixa prioridade

- [ ] **Novos contratos**:
  - `ToDateTime extends Castable` → `toDateTime(): \DateTimeImmutable`
  - `ToEnum extends Castable` → `toEnum(): \BackedEnum`
  - `ToCollection extends Castable` → `toCollection(): iterable`
- [ ] **`Caster::all(array $values, string $method): array`** — aplica um método de conversão em lote
- [ ] **Registro de conversores customizados** — `Caster::register('uuid', fn($v) => ...)` + `Caster::convert($value, 'uuid')`
- [ ] **API fluente** — `Caster::of($value)->toString()->trim()->upper()` (ver seção abaixo)
- [ ] **Testes de mutação** — integrar Infection para validar a qualidade dos testes
- [ ] **PHP CS Fixer** — garantir estilo consistente (`@PER-CS2.0`)
- [ ] **Cobertura de código** — integrar Codecov/Coveralls com badge no README
- [ ] **Publicar no Packagist** — para instalação via `composer require rak200/caster`
- [ ] **`CONTRIBUTING.md`** — guia para contribuidores
- [ ] **Badges no README** — versão, CI status, PHP version

### API Fluente — Detalhamento

A ideia é criar uma classe `CasterBuilder` (ou `CastValue`) que envolve um valor e encadeia operações:

```php
// Hoje:
$result = strtoupper(trim(Caster::toString($value)));

// Com API fluente:
$result = Caster::of($value)
    ->toString()
    ->trim()
    ->upper()
    ->get(); // retorna o valor final
```

**Componentes necessários:**

```
src/
└── Builder/
    ├── CastBuilder.php       # classe fluente principal
    └── StringCastBuilder.php # builder especializado para strings
```

**Funcionamento interno:**
- `Caster::of($value)` retorna uma instância de `CastBuilder` que carrega o valor
- Métodos como `toString()`, `toInt()` fazem a conversão e retornam um builder especializado
- Métodos de transformação (`trim()`, `upper()`, `lower()`, `pad()`) operam sobre o valor já convertido
- `get()` extrai o valor final
- O builder é imutável — cada operação retorna uma nova instância

**Exemplo de implementação mínima:**

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

**Prós:**
- Código mais legível e declarativo
- Elimina funções aninhadas difíceis de ler
- Encadeamento infinito sem variáveis intermediárias
- Fácil de estender com novos métodos de transformação

**Contras / Cuidados:**
- Aumenta o tamanho da biblioteca significativamente
- Cada transformação cria um novo objeto (impacto de memória em volumes altos)
- Pode ser percebido como "escopo demais" para uma lib de contratos simples
- Alternativa: manter como pacote separado `rak200/caster-fluent`
