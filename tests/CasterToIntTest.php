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

    public function testToBoolFalse(): void
    {
        $obj = new class implements ToBool {
            public function toBool(): bool
            {
                return false;
            }
        };
        $this->assertSame(0, Caster::toInt($obj));
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
        $this->expectExceptionMessageIs('Cannot convert null to int');
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

    public function testTryToInt(): void
    {
        $this->assertSame(17, Caster::tryToInt('17'));
    }

    public function testTryToIntNullOnNonNumericString(): void
    {
        $this->assertNull(Caster::tryToInt('abc'));
    }

    public function testTryToIntNullOnNull(): void
    {
        $this->assertNull(Caster::tryToInt(null));
    }

    public function testNonFiniteFloatThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(NAN);
    }

    public function testInfinityThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(INF);
    }

    public function testNegativeInfinityThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(-INF);
    }

    public function testFloatBeyondIntRangeThrowsInsteadOfWrapping(): void
    {
        // Unguarded, (int) 1e20 wraps to 7766279631452241920.
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(1e20);
    }

    public function testFloatBeyondIntRangeThrowsInsteadOfFlippingSign(): void
    {
        // Unguarded, (int) 9.3e18 wraps to -9146744073709551616 — positive in,
        // negative out, which is the worst shape this guard exists to prevent.
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(9.3e18);
    }

    public function testNonRepresentableFloatNamesItselfInTheMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('1.0E+20 is not representable as an int');
        Caster::toInt(1e20);
    }

    public function testFloatAtTheLowerBoundConverts(): void
    {
        // PHP_INT_MIN is exactly representable as a double, so it must survive
        // the guard rather than being refused with the values beyond it.
        $this->assertSame(PHP_INT_MIN, Caster::toInt((float) PHP_INT_MIN));
    }

    public function testLargestFloatBelowIntMaxConverts(): void
    {
        // The last double that still fits: 2**63 - 1024.
        $this->assertSame(9223372036854774784, Caster::toInt(9.2233720368547748E18));
    }

    public function testFloatAtTwoToThe63Throws(): void
    {
        // The first double above PHP_INT_MAX. It is what (float) PHP_INT_MAX
        // rounds up to, which is why the guard cannot be written against it.
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(9.2233720368547758E18);
    }

    public function testToFloatContractBeyondIntRangeThrows(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return 1e20;
            }
        };
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt($obj);
    }

    public function testTryToIntNullOnNonFiniteFloat(): void
    {
        $this->assertNull(Caster::tryToInt(NAN));
    }

    public function testTryToIntNullOnFloatBeyondIntRange(): void
    {
        $this->assertNull(Caster::tryToInt(1e20));
    }

    // A DOCUMENTED LIMITATION, not desired behaviour: the string path saturates
    // where the float path above throws. Deciding it exactly needs arbitrary-
    // precision arithmetic — see the toInt() docblock, docs/caster.md and #23.
    // The test exists so the limitation cannot change without the documentation
    // changing with it.
    public function testDocumentedLimitNumericStringBeyondIntRangeSaturates(): void
    {
        $this->assertSame(PHP_INT_MAX, Caster::toInt('1e20'));
        $this->assertSame(PHP_INT_MAX, Caster::toInt('9223372036854775808'));
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
