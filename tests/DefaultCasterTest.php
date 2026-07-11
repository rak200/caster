<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use ArrayIterator;
use BcMath\Number;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\CasterInterface;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\DefaultCaster;

use function fopen;

/**
 * Tests for DefaultCaster: the stateless instance facade delegates every
 * method to the static Caster, and CasterInterface is mockable in consumer
 * tests.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class DefaultCasterTest extends TestCase
{
    public function testImplementsCasterInterface(): void
    {
        $this->assertInstanceOf(CasterInterface::class, new DefaultCaster());
    }

    public function testConvertersDelegate(): void
    {
        $caster = new DefaultCaster();
        $this->assertSame('42', $caster->toString(42));
        $this->assertSame(17, $caster->toInt('17'));
        $this->assertSame(2.5, $caster->toFloat('2.5'));
        $this->assertTrue($caster->toBool(1));
        $this->assertSame([1, 2], $caster->toArray(new ArrayIterator([1, 2])));
        $this->assertTrue($caster->toNumber('1.5') == new Number('1.5'));
        $this->assertSame(0, $caster->toDateTime(0)->getTimestamp());
        $this->assertSame(DefaultCasterTestStatus::Active, $caster->toEnum('active', DefaultCasterTestStatus::class));
        $this->assertSame([1, 2], $caster->toCollection([1, 2]));
        $this->assertSame('{"a":1}', $caster->toJson(['a' => 1], 0));
    }

    public function testCastDelegates(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 99;
            }
        };
        $caster = new DefaultCaster();
        $this->assertSame(99, $caster->cast($obj));
        $this->assertSame(99, $caster->tryCast($obj));
    }

    public function testTryVariantsDelegate(): void
    {
        $caster = new DefaultCaster();
        $this->assertSame('42', $caster->tryToString(42));
        $this->assertNull($caster->tryToString(null));
        $this->assertSame(17, $caster->tryToInt('17'));
        $this->assertNull($caster->tryToInt('abc'));
        $this->assertSame(2.5, $caster->tryToFloat('2.5'));
        $this->assertNull($caster->tryToFloat(null));
        $this->assertTrue($caster->tryToBool(1));
        $this->assertNull($caster->tryToBool(null));
        $this->assertSame([1], $caster->tryToArray([1]));
        $this->assertNull($caster->tryToArray('x'));
        $this->assertNotNull($caster->tryToNumber('1.5'));
        $this->assertNull($caster->tryToNumber(NAN));
        $this->assertNotNull($caster->tryToDateTime('2026-01-01'));
        $this->assertNull($caster->tryToDateTime('not a date'));
        $this->assertSame(DefaultCasterTestStatus::Active, $caster->tryToEnum('active', DefaultCasterTestStatus::class));
        $this->assertNull($caster->tryToEnum('nope', DefaultCasterTestStatus::class));
        $this->assertSame([1], $caster->tryToCollection([1]));
        $this->assertNull($caster->tryToCollection(42));
        $this->assertSame('1', $caster->tryToJson(1));
        $this->assertNull($caster->tryToJson(fopen('php://memory', 'r')));
    }

    /** The point of the interface: consumer tests can stub/mock the whole conversion API. */
    public function testInterfaceIsStubbable(): void
    {
        $stub = $this->createStub(CasterInterface::class);
        $stub->method('toInt')->willReturn(123);
        $this->assertSame(123, $stub->toInt('anything'));
    }
}

/**
 * String-backed enum used exclusively by DefaultCasterTest.
 */
enum DefaultCasterTestStatus: string
{
    case Active = 'active';
}
