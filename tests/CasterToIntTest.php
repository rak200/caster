<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use BackedEnum;
use BcMath\Number;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
 */
#[CoversClass(Caster::class)]
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

    public function testFirstFloatBelowIntMinThrows(): void
    {
        // The mirror of 2**63 on the other side: the first double below PHP_INT_MIN,
        // 2048 further down, does not fit either.
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt(-(2.0 ** 63) - 2048.0);
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

    #[DataProvider('numericStringBeyondIntRangeProvider')]
    public function testNumericStringBeyondIntRangeThrows(string $value): void
    {
        // These saturated at PHP_INT_MAX or PHP_INT_MIN until the string path was decided
        // through Number; the float path above has always refused its own equivalents.
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function numericStringBeyondIntRangeProvider(): iterable
    {
        yield 'one past PHP_INT_MAX' => ['9223372036854775808'];

        yield 'one past PHP_INT_MIN' => ['-9223372036854775809'];

        yield 'twenty digits' => ['99999999999999999999'];

        yield 'an exponent' => ['1e20'];

        yield 'a negative exponent form' => ['-1e20'];

        yield 'an exponent too large to expand' => ['1e999999999'];
    }

    public function testNonNumericStringIsNotConvertibleRatherThanOutOfRange(): void
    {
        // A string that is not a number never reaches the range check: it is refused as a
        // string, and the message says so rather than calling it a number too large.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert string to int');
        Caster::toInt('abc');
    }

    public function testNonRepresentableStringNamesItselfInTheMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'9223372036854775808' is not representable as an int");
        Caster::toInt('9223372036854775808');
    }

    #[DataProvider('numericStringTruncatedExactlyProvider')]
    public function testNumericStringIsTruncatedTowardZeroExactly(string $value, int $expected): void
    {
        // PHP's own (int) goes through float for anything with a fraction or an exponent, and
        // past 2**53 a float is not the number the string spells.
        $this->assertSame($expected, Caster::toInt($value));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function numericStringTruncatedExactlyProvider(): iterable
    {
        yield 'PHP_INT_MAX' => ['9223372036854775807', PHP_INT_MAX];

        yield 'PHP_INT_MIN' => ['-9223372036854775808', PHP_INT_MIN];

        yield 'a fraction above PHP_INT_MAX that truncates into range' => ['9223372036854775807.9', PHP_INT_MAX];

        yield 'a fraction below PHP_INT_MIN that truncates into range' => ['-9223372036854775808.9', PHP_INT_MIN];

        yield '2**53 + 1 with a fraction, which float rounds to an even neighbour' => ['9007199254740993.0', 9007199254740993];

        yield 'one below PHP_INT_MAX, with a fraction' => ['9223372036854775806.9', 9223372036854775806];

        yield 'a negative fraction, toward zero' => ['-9223372036854775807.5', -9223372036854775807];

        yield 'a small negative fraction, to zero' => ['-0.5', 0];

        yield 'an exponent within range' => ['1e3', 1000];
    }

    public function testStringableAndToNumberBeyondIntRangeThrow(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return '9223372036854775808';
            }
        };
        $number = new class implements ToNumber {
            public function toNumber(): Number
            {
                return new Number('-9223372036854775809');
            }
        };

        $this->assertNull(Caster::tryToInt($stringable));
        $this->assertNull(Caster::tryToInt($number));
        $this->expectException(InvalidArgumentException::class);
        Caster::toInt($stringable);
    }

    public function testTryToIntNullOnStringBeyondIntRange(): void
    {
        $this->assertNull(Caster::tryToInt('9223372036854775808'));
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
