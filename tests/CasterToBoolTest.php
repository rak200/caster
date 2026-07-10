<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use BcMath\Number;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToCollection;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToNumber;
use Stringable;

/**
 * Tests for Caster::toBool().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterToBoolTest extends TestCase
{
    public function testBoolTrue(): void
    {
        $this->assertTrue(Caster::toBool(true));
    }

    public function testIntZero(): void
    {
        $this->assertFalse(Caster::toBool(0));
    }

    public function testIntNonZero(): void
    {
        $this->assertTrue(Caster::toBool(42));
    }

    public function testFloatZero(): void
    {
        $this->assertFalse(Caster::toBool(0.0));
    }

    public function testStringEmpty(): void
    {
        $this->assertFalse(Caster::toBool(''));
    }

    public function testStringZero(): void
    {
        $this->assertFalse(Caster::toBool('0'));
    }

    public function testStringOther(): void
    {
        $this->assertTrue(Caster::toBool('hello'));
    }

    public function testStringable(): void
    {
        $obj = new class implements Stringable {
            public function __toString(): string
            {
                return 'x';
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testToBool(): void
    {
        $obj = new class implements ToBool {
            public function toBool(): bool
            {
                return true;
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testToInt(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 1;
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testToFloat(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return 0.0;
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testToNumber(): void
    {
        $obj = new class implements ToNumber {
            public function toNumber(): Number
            {
                return new Number('0');
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testEmptyArray(): void
    {
        $this->assertFalse(Caster::toBool([]));
    }

    public function testNonEmptyArray(): void
    {
        $this->assertTrue(Caster::toBool([1]));
    }

    public function testToArrayEmpty(): void
    {
        $obj = new class implements ToArray {
            public function toArray(): array
            {
                return [];
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testToCollectionEmpty(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                return [];
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testToCollectionGenerator(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                yield 1;
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toBool(null);
    }
}
