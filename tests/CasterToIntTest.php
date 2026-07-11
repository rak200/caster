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
use Stringable;
use UnitEnum;

/**
 * Tests for Caster::toInt().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterToIntTest extends TestCase
{
    public function testInt(): void
    {
        $this->assertSame(42, Caster::toInt(42));
    }

    public function testFloatTruncates(): void
    {
        $this->assertSame(3, Caster::toInt(3.9));
    }

    public function testBoolTrue(): void
    {
        $this->assertSame(1, Caster::toInt(true));
    }

    public function testBoolFalse(): void
    {
        $this->assertSame(0, Caster::toInt(false));
    }

    public function testStringNumeric(): void
    {
        $this->assertSame(42, Caster::toInt('42'));
    }

    public function testStringable(): void
    {
        $obj = new class implements Stringable {
            public function __toString(): string
            {
                return '99';
            }
        };
        $this->assertSame(99, Caster::toInt($obj));
    }

    public function testToInt(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 7;
            }
        };
        $this->assertSame(7, Caster::toInt($obj));
    }

    public function testToFloat(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return 2.7;
            }
        };
        $this->assertSame(2, Caster::toInt($obj));
    }

    public function testToNumber(): void
    {
        $obj = new class implements ToNumber {
            public function toNumber(): Number
            {
                return new Number('5');
            }
        };
        $this->assertSame(5, Caster::toInt($obj));
    }

    public function testToBool(): void
    {
        $obj = new class implements ToBool {
            public function toBool(): bool
            {
                return true;
            }
        };
        $this->assertSame(1, Caster::toInt($obj));
    }

    public function testToDateTime(): void
    {
        $obj = new class implements ToDateTime {
            public function toDateTime(): DateTimeImmutable
            {
                return new DateTimeImmutable('@1748366400');
            }
        };
        $this->assertSame(1748366400, Caster::toInt($obj));
    }

    public function testToEnumIntBacked(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterToIntTestLevel::High;
            }
        };
        $this->assertSame(2, Caster::toInt($obj));
    }

    /** Only int-backed enums convert to int; a string-backed case throws, even when numeric. */
    public function testToEnumStringBackedThrows(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterToIntTestCode::Ten;
            }
        };
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt($obj);
    }

    public function testToEnumPureThrows(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): UnitEnum
            {
                return CasterToIntTestColor::Red;
            }
        };
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt($obj);
    }

    public function testNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(null);
    }

    public function testToIntTakesPriorityOverStringable(): void
    {
        $obj = new class implements Stringable, ToInt {
            public function __toString(): string
            {
                return '999';
            }

            public function toInt(): int
            {
                return 7;
            }
        };
        $this->assertSame(7, Caster::toInt($obj));
    }

    public function testStringDecimalTruncates(): void
    {
        $this->assertSame(3, Caster::toInt('3.9'));
    }

    public function testStringNonNumericThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt('abc');
    }

    public function testStringWhitespacePaddedThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(' 5 ');
    }

    public function testStringableNonNumericThrows(): void
    {
        $obj = new class implements Stringable {
            public function __toString(): string
            {
                return 'abc';
            }
        };
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt($obj);
    }
}

enum CasterToIntTestLevel: int
{
    case Low = 1;
    case High = 2;
}

enum CasterToIntTestCode: string
{
    case Ten = '10';
}

enum CasterToIntTestColor
{
    case Red;
}
