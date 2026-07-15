<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use BackedEnum;
use BcMath\Number;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\Castable;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToCollection;
use Rak200\Caster\Contracts\ToDateTime;
use Rak200\Caster\Contracts\ToEnum;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToJson;
use Rak200\Caster\Contracts\ToNumber;
use Rak200\Caster\Contracts\ToString;

/**
 * Tests for Caster::cast().
 *
 * Verifies that each typed contract is dispatched correctly and that
 * the priority order (ToJson > ToString > ToInt > ToFloat > ToBool > ToArray)
 * is respected when an object implements multiple contracts.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterCastTest extends TestCase
{
    /** ToJson objects return their toJson() string. */
    public function testToJson(): void
    {
        $obj = new class implements ToJson {
            public function toJson(): string
            {
                return '{"key":"value"}';
            }
        };
        $this->assertSame('{"key":"value"}', Caster::cast($obj));
    }

    /** ToString objects return their __toString() string. */
    public function testToString(): void
    {
        $obj = new class implements ToString {
            public function __toString(): string
            {
                return 'hello';
            }
        };
        $this->assertSame('hello', Caster::cast($obj));
    }

    /** ToInt objects return their toInt() integer. */
    public function testToInt(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 42;
            }
        };
        $this->assertSame(42, Caster::cast($obj));
    }

    /** ToFloat objects return their toFloat() float. */
    public function testToFloat(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return 3.14;
            }
        };
        $this->assertSame(3.14, Caster::cast($obj));
    }

    /** ToBool objects returning true resolve to boolean true. */
    public function testToBoolTrue(): void
    {
        $obj = new class implements ToBool {
            public function toBool(): bool
            {
                return true;
            }
        };
        $this->assertTrue(Caster::cast($obj));
    }

    /** ToBool objects returning false resolve to boolean false. */
    public function testToBoolFalse(): void
    {
        $obj = new class implements ToBool {
            public function toBool(): bool
            {
                return false;
            }
        };
        $this->assertFalse(Caster::cast($obj));
    }

    /** ToArray objects return their toArray() array. */
    public function testToArray(): void
    {
        $obj = new class implements ToArray {
            public function toArray(): array
            {
                return [1, 2, 3];
            }
        };
        $this->assertSame([1, 2, 3], Caster::cast($obj));
    }

    /** ToNumber objects return their toNumber() BcMath\Number. */
    public function testToNumber(): void
    {
        $obj = new class implements ToNumber {
            public function toNumber(): Number
            {
                return new Number('3.14');
            }
        };
        $result = Caster::cast($obj);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame('3.14', (string) $result);
    }

    /** ToDateTime objects return their toDateTime() DateTimeImmutable. */
    public function testToDateTime(): void
    {
        $obj = new class implements ToDateTime {
            public function toDateTime(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-05-27T12:00:00+00:00');
            }
        };
        $result = Caster::cast($obj);
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2026-05-27T12:00:00+00:00', $result->format('c'));
    }

    /** ToEnum objects return their toEnum() BackedEnum case. */
    public function testToEnum(): void
    {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum
            {
                return CasterCastTestStatus::Active;
            }
        };
        $result = Caster::cast($obj);
        $this->assertSame(CasterCastTestStatus::Active, $result);
    }

    /** ToCollection objects return their toCollection() iterable. */
    public function testToCollection(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                return [1, 2, 3];
            }
        };
        $this->assertSame([1, 2, 3], Caster::cast($obj));
    }

    /** Objects implementing only the marker Castable interface throw InvalidArgumentException naming the type. */
    public function testPlainCastableThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Cannot cast ' . CasterCastTestMarker::class);
        Caster::cast(new CasterCastTestMarker());
    }

    /** tryCast() returns the same value as cast() for a real contract. */
    public function testTryCast(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 99;
            }
        };
        $this->assertSame(99, Caster::tryCast($obj));
    }

    /** tryCast() returns null for a marker-only Castable instead of throwing. */
    public function testTryCastNullOnPlainCastable(): void
    {
        $this->assertNull(Caster::tryCast(new class implements Castable {}));
    }

    /** ToJson takes priority over ToString when both are implemented. */
    public function testToJsonTakesPriorityOverToString(): void
    {
        $obj = new class implements ToJson, ToString {
            public function toJson(): string
            {
                return '"json"';
            }

            public function __toString(): string
            {
                return 'string';
            }
        };
        $this->assertSame('"json"', Caster::cast($obj));
    }

    /** ToJson takes priority over ToInt when both are implemented. */
    public function testToJsonTakesPriorityOverToInt(): void
    {
        $obj = new class implements ToJson, ToInt {
            public function toJson(): string
            {
                return '"json"';
            }

            public function toInt(): int
            {
                return 0;
            }
        };
        $this->assertSame('"json"', Caster::cast($obj));
    }

    /** ToInt takes priority over ToFloat when both are implemented. */
    public function testToIntTakesPriorityOverToFloat(): void
    {
        $obj = new class implements ToInt, ToFloat {
            public function toInt(): int
            {
                return 5;
            }

            public function toFloat(): float
            {
                return 5.0;
            }
        };
        $this->assertSame(5, Caster::cast($obj));
    }
}

/**
 * Backed enum used exclusively by CasterCastTest::testToEnum().
 */
enum CasterCastTestStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

/**
 * Marker-only Castable with a stable class name, so the "Cannot cast" message
 * is assertable (an anonymous class renders an unstable Type::of string).
 */
final class CasterCastTestMarker implements Castable {}
