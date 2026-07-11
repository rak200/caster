<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use BackedEnum;
use BcMath\Number;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToEnum;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToNumber;
use Stringable;
use UnitEnum;

/**
 * Tests for Caster::toNumber().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterToNumberTest extends TestCase
{
    public function testNumberPassthrough(): void
    {
        $num = new Number('3.14');
        $this->assertSame($num, Caster::toNumber($num));
    }

    public function testInt(): void
    {
        $this->assertSame('42', (string) Caster::toNumber(42));
    }

    public function testFloat(): void
    {
        $this->assertSame('3.14', (string) Caster::toNumber(3.14));
    }

    public function testStringDecimal(): void
    {
        $this->assertSame('1.5', (string) Caster::toNumber('1.5'));
    }

    public function testBoolTrue(): void
    {
        $this->assertSame('1', (string) Caster::toNumber(true));
    }

    public function testBoolFalse(): void
    {
        $this->assertSame('0', (string) Caster::toNumber(false));
    }

    public function testStringable(): void
    {
        $obj = new class implements Stringable {
            public function __toString(): string
            {
                return '7.7';
            }
        };
        $this->assertSame('7.7', (string) Caster::toNumber($obj));
    }

    public function testToNumber(): void
    {
        $obj = new class implements ToNumber {
            public function toNumber(): Number
            {
                return new Number('2.71');
            }
        };
        $this->assertSame('2.71', (string) Caster::toNumber($obj));
    }

    public function testToInt(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 9;
            }
        };
        $this->assertSame('9', (string) Caster::toNumber($obj));
    }

    public function testToFloat(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return 0.5;
            }
        };
        $this->assertSame('0.5', (string) Caster::toNumber($obj));
    }

    public function testToBool(): void
    {
        $obj = new class implements ToBool {
            public function toBool(): bool
            {
                return true;
            }
        };
        $this->assertSame('1', (string) Caster::toNumber($obj));
    }

    public function testToEnum(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterToNumberTestLevel::High;
            }
        };
        $this->assertSame('2', (string) Caster::toNumber($obj));
    }

    public function testToEnumStringBackedNumeric(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterToNumberTestCode::Pi;
            }
        };
        $this->assertSame('3.14', (string) Caster::toNumber($obj));
    }

    public function testToEnumPureThrows(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): UnitEnum
            {
                return CasterToNumberTestColor::Red;
            }
        };
        $this->expectException(InvalidArgumentException::class);
        Caster::toNumber($obj);
    }

    public function testNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toNumber(null);
    }

    public function testNanThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toNumber(NAN);
    }

    public function testInfinityThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toNumber(INF);
    }

    /** (string) 0.0000001 is '1.0E-7' — must be expanded, not fed raw to the Number constructor. */
    public function testFloatInScientificNotationRange(): void
    {
        $this->assertTrue(Caster::toNumber(0.0000001) == new Number('0.0000001'));
    }

    public function testToFloatInScientificNotationRange(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return 0.0000001;
            }
        };
        $this->assertTrue(Caster::toNumber($obj) == new Number('0.0000001'));
    }

    public function testToFloatNanThrows(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return NAN;
            }
        };
        $this->expectException(InvalidArgumentException::class);
        Caster::toNumber($obj);
    }
}

enum CasterToNumberTestLevel: int
{
    case Low = 1;
    case High = 2;
}

enum CasterToNumberTestCode: string
{
    case Pi = '3.14';
}

enum CasterToNumberTestColor
{
    case Red;
}
