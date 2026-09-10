<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\CasterInterface;
use Rak200\Utils\Arr;
use Rak200\Utils\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

use function preg_match_all;
use function var_export;

/**
 * Tests that CasterInterface mirrors the static Caster API.
 *
 * The docs state the mirror as a promise — "same signatures, same defaults, same
 * exceptions" — and nothing enforces it: Caster does not implement the interface,
 * so PHPStan compares the two for nothing at all. It only checks DefaultCaster
 * against the interface, which keeps the pair consistent with each other while
 * both drift away from the static class. These tests are that missing comparison.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 */
// Genuinely covers nothing: it executes no src code, it compares the metadata of two
// declarations by reflection. An interface is not a valid coverage target either — PHPUnit
// warns on #[CoversClass] here, but only while collecting coverage, so `composer test`
// stays silent about it and the mutation run is what fails.
#[CoversNothing]
final class CasterInterfaceTest extends TestCase
{
    public function testEveryPublicStaticMethodIsMirrored(): void
    {
        $interface = new ReflectionClass(CasterInterface::class);
        $missing = [];
        foreach (self::staticApi() as $name => $method) {
            if (!$interface->hasMethod($name)) {
                $missing[] = $name;
            }
        }
        $this->assertSame([], $missing, 'Caster methods absent from CasterInterface');
    }

    public function testTheInterfaceDeclaresNothingTheStaticClassLacks(): void
    {
        $api = self::staticApi();
        $extra = [];
        foreach (new ReflectionClass(CasterInterface::class)->getMethods() as $method) {
            if (!isset($api[$method->getName()])) {
                $extra[] = $method->getName();
            }
        }
        $this->assertSame([], $extra, 'CasterInterface methods absent from Caster');
    }

    public function testParametersMatchInNameTypeAndDefault(): void
    {
        foreach (self::staticApi() as $name => $method) {
            $this->assertSame(
                self::parameters($method),
                self::parameters(new ReflectionMethod(CasterInterface::class, $name)),
                "parameters of {$name}() differ between Caster and CasterInterface",
            );
        }
    }

    public function testReturnTypesMatch(): void
    {
        foreach (self::staticApi() as $name => $method) {
            $this->assertSame(
                self::type($method->getReturnType()),
                self::type(new ReflectionMethod(CasterInterface::class, $name)->getReturnType()),
                "return type of {$name}() differs between Caster and CasterInterface",
            );
        }
    }

    public function testDeclaredThrowsMatch(): void
    {
        foreach (self::staticApi() as $name => $method) {
            $this->assertSame(
                self::throws($method),
                self::throws(new ReflectionMethod(CasterInterface::class, $name)),
                "@throws of {$name}() differ between Caster and CasterInterface",
            );
        }
    }

    /**
     * The public static surface of Caster, keyed by method name.
     *
     * @return array<string, ReflectionMethod>
     */
    private static function staticApi(): array
    {
        $api = [];
        foreach (new ReflectionClass(Caster::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $api[$method->getName()] = $method;
        }

        return $api;
    }

    /**
     * Each parameter as "type $name" plus its default, when it has one.
     *
     * @return list<string>
     */
    private static function parameters(ReflectionMethod $method): array
    {
        return Arr::map(
            $method->getParameters(),
            static fn (ReflectionParameter $p): string => self::type($p->getType())
                . ' $' . $p->getName()
                . ($p->isDefaultValueAvailable() ? ' = ' . var_export($p->getDefaultValue(), true) : ''),
        );
    }

    /** A type as written, with a null union normalised so ?T and T|null compare equal. */
    private static function type(?ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return ($type->allowsNull() && $type->getName() !== 'null' && $type->getName() !== 'mixed' ? 'null|' : '')
                . $type->getName();
        }

        $parts = [];
        foreach (($type instanceof ReflectionUnionType ? $type->getTypes() : []) as $part) {
            $parts[] = self::type($part);
        }

        return Str::join(Arr::sort($parts), '|');
    }

    /**
     * The exception class of every @throws tag, in declaration order.
     *
     * @return list<string>
     */
    private static function throws(ReflectionMethod $method): array
    {
        $doc = $method->getDocComment();
        preg_match_all('/@throws\s+(\S+)/', $doc === false ? '' : $doc, $matches);

        return $matches[1];
    }
}
