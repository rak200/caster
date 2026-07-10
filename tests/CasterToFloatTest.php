<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use BackedEnum;
use BcMath\Number;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToDateTime;
use Rak200\Caster\Contracts\ToEnum;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToNumber;
use RuntimeException;
use Stringable;
use UnitEnum;

/**
 * Tests for Caster::toFloat().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterToFloatTest extends TestCase
{
    public function testFloat(): void
    {
        $this->assertSame(3.14, Caster::toFloat(3.14));
    }

    public function testInt(): void
    {
        $this->assertSame(42.0, Caster::toFloat(42));
    }

    public function testBoolTrue(): void
    {
        $this->assertSame(1.0, Caster::toFloat(true));
    }

    public function testStringNumeric(): void
    {
        $this->assertSame(2.5, Caster::toFloat('2.5'));
    }

    public function testStringable(): void
    {
        $obj = new class implements Stringable {
            public function __toString(): string
            {
                return '7.5';
            }
        };
        $this->assertSame(7.5, Caster::toFloat($obj));
    }

    public function testToFloat(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return 1.5;
            }
        };
        $this->assertSame(1.5, Caster::toFloat($obj));
    }

    public function testToInt(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 5;
            }
        };
        $this->assertSame(5.0, Caster::toFloat($obj));
    }

    public function testToNumber(): void
    {
        $obj = new class implements ToNumber {
            public function toNumber(): Number
            {
                return new Number('3.14');
            }
        };
        $this->assertSame(3.14, Caster::toFloat($obj));
    }

    public function testToBool(): void
    {
        $obj = new class implements ToBool {
            public function toBool(): bool
            {
                return false;
            }
        };
        $this->assertSame(0.0, Caster::toFloat($obj));
    }

    public function testToDateTimeIncludesMicroseconds(): void
    {
        $obj = new class implements ToDateTime {
            public function toDateTime(): DateTimeImmutable
            {
                return DateTimeImmutable::createFromFormat('U.u', '1748366400.123456')
                    ?: throw new RuntimeException('failed to build fixture');
            }
        };
        $this->assertEqualsWithDelta(1748366400.123456, Caster::toFloat($obj), 0.0001);
    }

    public function testToEnumIntBacked(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterToFloatTestLevel::High;
            }
        };
        $this->assertSame(2.0, Caster::toFloat($obj));
    }

    public function testToEnumStringBackedNumeric(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterToFloatTestCode::Half;
            }
        };
        $this->assertSame(0.5, Caster::toFloat($obj));
    }

    public function testToEnumStringBackedNonNumericThrows(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterToFloatTestCode::Text;
            }
        };
        $this->expectException(InvalidArgumentException::class);
        Caster::toFloat($obj);
    }

    public function testToEnumPureThrows(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): UnitEnum
            {
                return CasterToFloatTestColor::Red;
            }
        };
        $this->expectException(InvalidArgumentException::class);
        Caster::toFloat($obj);
    }

    public function testNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toFloat(null);
    }
}

enum CasterToFloatTestLevel: int
{
    case Low = 1;
    case High = 2;
}

enum CasterToFloatTestCode: string
{
    case Half = '0.5';
    case Text = 'nope';
}

enum CasterToFloatTestColor
{
    case Red;
}
