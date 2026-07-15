<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use BackedEnum;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToEnum;
use Rak200\Caster\Contracts\ToInt;
use stdClass;
use Stringable;

/**
 * Tests for Caster::toEnum().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterToEnumTest extends TestCase
{
    public function testEnumCasePassthrough(): void
    {
        $this->assertSame(
            CasterToEnumTestStatus::Active,
            Caster::toEnum(CasterToEnumTestStatus::Active, CasterToEnumTestStatus::class),
        );
    }

    public function testStringValue(): void
    {
        $this->assertSame(
            CasterToEnumTestStatus::Active,
            Caster::toEnum('active', CasterToEnumTestStatus::class),
        );
    }

    public function testIntValue(): void
    {
        $this->assertSame(
            CasterToEnumTestLevel::High,
            Caster::toEnum(2, CasterToEnumTestLevel::class),
        );
    }

    public function testStringable(): void
    {
        $obj = new class implements Stringable {
            public function __toString(): string
            {
                return 'inactive';
            }
        };
        $this->assertSame(
            CasterToEnumTestStatus::Inactive,
            Caster::toEnum($obj, CasterToEnumTestStatus::class),
        );
    }

    public function testToInt(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 1;
            }
        };
        $this->assertSame(
            CasterToEnumTestLevel::Low,
            Caster::toEnum($obj, CasterToEnumTestLevel::class),
        );
    }

    public function testToEnum(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterToEnumTestStatus::Active;
            }
        };
        $this->assertSame(
            CasterToEnumTestStatus::Active,
            Caster::toEnum($obj, CasterToEnumTestStatus::class),
        );
    }

    public function testNonEnumClassThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // @phpstan-ignore-next-line argument.type
        Caster::toEnum('hello', stdClass::class);
    }

    public function testNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Cannot convert null to ' . CasterToEnumTestStatus::class);
        Caster::toEnum(null, CasterToEnumTestStatus::class);
    }

    /** tryFrom() is strictly typed: '2' must still match the int-backed case, not TypeError. */
    public function testNumericStringMatchesIntBackedEnum(): void
    {
        $this->assertSame(
            CasterToEnumTestLevel::High,
            Caster::toEnum('2', CasterToEnumTestLevel::class),
        );
    }

    public function testCaseNameMatchesIntBackedEnum(): void
    {
        $this->assertSame(
            CasterToEnumTestLevel::Low,
            Caster::toEnum('Low', CasterToEnumTestLevel::class),
        );
    }

    public function testIntMatchesStringBackedEnumWithNumericBacking(): void
    {
        $this->assertSame(
            CasterToEnumTestCode::Ten,
            Caster::toEnum(10, CasterToEnumTestCode::class),
        );
    }

    public function testNonMatchingNumericStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toEnum('9', CasterToEnumTestLevel::class);
    }

    public function testNonMatchingIntAgainstStringBackedEnumThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toEnum(7, CasterToEnumTestStatus::class);
    }

    /** Backing-value match wins over case-name match: 'Bar' is Foo's backing value before it is Bar's name. */
    public function testBackingValueMatchTakesPriorityOverCaseName(): void
    {
        $this->assertSame(
            CasterToEnumTestClash::Foo,
            Caster::toEnum('Bar', CasterToEnumTestClash::class),
        );
    }

    /** For a value that is both ToInt and Stringable, the int is extracted first: toInt() 1 wins over "2". */
    public function testIntExtractionTakesPriorityOverStringForBackedMatch(): void
    {
        $obj = new class implements Stringable, ToInt {
            public function __toString(): string
            {
                return '2';
            }

            public function toInt(): int
            {
                return 1;
            }
        };
        $this->assertSame(
            CasterToEnumTestLevel::Low,
            Caster::toEnum($obj, CasterToEnumTestLevel::class),
        );
    }

    /** For a value that is both ToInt and Stringable, the extracted int (not the string) names it in the not-a-case error. */
    public function testDualIntStringableReportsIntInError(): void
    {
        $obj = new class implements Stringable, ToInt {
            public function __toString(): string
            {
                return 'xyz';
            }

            public function toInt(): int
            {
                return 7;
            }
        };
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs("'7' is not a case of " . CasterToEnumTestLevel::class);
        Caster::toEnum($obj, CasterToEnumTestLevel::class);
    }

    public function testTryToEnum(): void
    {
        $this->assertSame(
            CasterToEnumTestStatus::Active,
            Caster::tryToEnum('active', CasterToEnumTestStatus::class),
        );
    }

    public function testTryToEnumNullOnMiss(): void
    {
        $this->assertNull(Caster::tryToEnum('Clubs', CasterToEnumTestStatus::class));
    }

    /** Any failure returns null — including a class-string that is not an enum. */
    public function testTryToEnumNullOnNonEnumClass(): void
    {
        // @phpstan-ignore-next-line argument.type
        $this->assertNull(Caster::tryToEnum('hello', stdClass::class));
    }
}

enum CasterToEnumTestStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum CasterToEnumTestLevel: int
{
    case Low = 1;
    case High = 2;
}

enum CasterToEnumTestCode: string
{
    case Ten = '10';
}

/**
 * One case's backing value ('Bar') collides with another case's name (Bar),
 * exercising the backing-value-before-name resolution order.
 */
enum CasterToEnumTestClash: string
{
    case Foo = 'Bar';
    case Bar = 'Baz';
}
