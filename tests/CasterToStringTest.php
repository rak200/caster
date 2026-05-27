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
use Rak200\Caster\Contracts\ToCollection;
use Rak200\Caster\Contracts\ToDateTime;
use Rak200\Caster\Contracts\ToEnum;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToNumber;
use Stringable;

use function json_decode;

/**
 * Tests for Caster::toString().
 *
 * Covers primitive types, Castable contract objects, priority resolution
 * between Stringable and typed contracts, and the exception path.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class CasterToStringTest extends TestCase {
    /** Strings are returned as-is without modification. */
    public function testString(): void {
        $this->assertSame('hello', Caster::toString('hello'));
    }

    /** Empty string is preserved. */
    public function testEmptyString(): void {
        $this->assertSame('', Caster::toString(''));
    }

    /** Integers are cast to their decimal string representation. */
    public function testInt(): void {
        $this->assertSame('42', Caster::toString(42));
    }

    /** Negative integers include the minus sign. */
    public function testNegativeInt(): void {
        $this->assertSame('-1', Caster::toString(-1));
    }

    /** Floats are cast to their decimal string representation. */
    public function testFloat(): void {
        $this->assertSame('3.14', Caster::toString(3.14));
    }

    /** Stringable objects are converted via __toString(). */
    public function testStringable(): void {
        $obj = new class implements Stringable {
            public function __toString(): string { return 'stringable'; }
        };
        $this->assertSame('stringable', Caster::toString($obj));
    }

    /** ToInt objects are cast via cast() then converted to string. */
    public function testToIntObject(): void {
        $obj = new class implements ToInt {
            public function toInt(): int { return 7; }
        };
        $this->assertSame('7', Caster::toString($obj));
    }

    /** ToFloat objects are cast via cast() then converted to string. */
    public function testToFloatObject(): void {
        $obj = new class implements ToFloat {
            public function toFloat(): float { return 1.5; }
        };
        $this->assertSame('1.5', Caster::toString($obj));
    }

    /** Boolean true maps to the literal string "true". */
    public function testBoolTrue(): void {
        $this->assertSame('true', Caster::toString(true));
    }

    /** Boolean false maps to the literal string "false". */
    public function testBoolFalse(): void {
        $this->assertSame('false', Caster::toString(false));
    }

    /** ToBool objects returning true produce "true". */
    public function testToBoolObjectTrue(): void {
        $obj = new class implements ToBool {
            public function toBool(): bool { return true; }
        };
        $this->assertSame('true', Caster::toString($obj));
    }

    /** ToBool objects returning false produce "false". */
    public function testToBoolObjectFalse(): void {
        $obj = new class implements ToBool {
            public function toBool(): bool { return false; }
        };
        $this->assertSame('false', Caster::toString($obj));
    }

    /** Arrays are JSON-encoded and the result is a valid JSON string. */
    public function testArray(): void {
        $result = Caster::toString(['a' => 1]);
        $this->assertJson($result);
        $this->assertSame(['a' => 1], json_decode($result, true));
    }

    /** Plain objects are JSON-encoded via toJson(). */
    public function testObject(): void {
        $obj = new \stdClass();
        $obj->x = 1;
        $result = Caster::toString($obj);
        $this->assertJson($result);
        $this->assertSame(1, json_decode($result)->x);
    }

    /** Null throws InvalidArgumentException as it cannot be stringified. */
    public function testNullThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        Caster::toString(null);
    }

    /** Stringable takes priority over ToInt when both are implemented. */
    public function testStringableTakesPriorityOverToInt(): void {
        $obj = new class implements Stringable, ToInt {
            public function __toString(): string { return 'from-string'; }
            public function toInt(): int { return 999; }
        };
        $this->assertSame('from-string', Caster::toString($obj));
    }

    /** ToNumber objects are stringified via BcMath\Number's Stringable. */
    public function testToNumberObject(): void {
        $obj = new class implements ToNumber {
            public function toNumber(): Number { return new Number('3.14'); }
        };
        $this->assertSame('3.14', Caster::toString($obj));
    }

    /** ToDateTime objects are stringified as ISO 8601. */
    public function testToDateTimeObject(): void {
        $obj = new class implements ToDateTime {
            public function toDateTime(): DateTimeImmutable {
                return new DateTimeImmutable('2026-05-27T12:00:00+00:00');
            }
        };
        $this->assertSame('2026-05-27T12:00:00+00:00', Caster::toString($obj));
    }

    /** ToEnum objects with a string-backed enum yield the backing value. */
    public function testToEnumObjectStringBacked(): void {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum { return CasterToStringTestStatus::Active; }
        };
        $this->assertSame('active', Caster::toString($obj));
    }

    /** ToEnum objects with an int-backed enum yield the value cast to string. */
    public function testToEnumObjectIntBacked(): void {
        $obj = new class implements ToEnum {
            public function toEnum(): BackedEnum { return CasterToStringTestLevel::High; }
        };
        $this->assertSame('2', Caster::toString($obj));
    }

    /** ToCollection objects are stringified as JSON of the materialised iterable. */
    public function testToCollectionObjectArray(): void {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable { return ['a' => 1, 'b' => 2]; }
        };
        $result = Caster::toString($obj);
        $this->assertJson($result);
        $this->assertSame(['a' => 1, 'b' => 2], json_decode($result, true));
    }

    /** ToCollection objects backed by a Generator are materialised before encoding. */
    public function testToCollectionObjectGenerator(): void {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable {
                yield 1;
                yield 2;
                yield 3;
            }
        };
        $result = Caster::toString($obj);
        $this->assertJson($result);
        $this->assertSame([1, 2, 3], json_decode($result, true));
    }
}

/**
 * String-backed enum used exclusively by CasterToStringTest::testToEnumObjectStringBacked().
 */
enum CasterToStringTestStatus: string {
    case Active = 'active';
    case Inactive = 'inactive';
}

/**
 * Int-backed enum used exclusively by CasterToStringTest::testToEnumObjectIntBacked().
 */
enum CasterToStringTestLevel: int {
    case Low = 1;
    case High = 2;
}
