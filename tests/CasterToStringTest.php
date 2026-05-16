<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Stringable;

final class CasterToStringTest extends TestCase {
    public function testString(): void {
        $this->assertSame('hello', Caster::toString('hello'));
    }

    public function testEmptyString(): void {
        $this->assertSame('', Caster::toString(''));
    }

    public function testInt(): void {
        $this->assertSame('42', Caster::toString(42));
    }

    public function testNegativeInt(): void {
        $this->assertSame('-1', Caster::toString(-1));
    }

    public function testFloat(): void {
        $this->assertSame('3.14', Caster::toString(3.14));
    }

    public function testStringable(): void {
        $obj = new class implements Stringable {
            public function __toString(): string { return 'stringable'; }
        };
        $this->assertSame('stringable', Caster::toString($obj));
    }

    public function testToIntObject(): void {
        $obj = new class implements ToInt {
            public function toInt(): int { return 7; }
        };
        $this->assertSame('7', Caster::toString($obj));
    }

    public function testToFloatObject(): void {
        $obj = new class implements ToFloat {
            public function toFloat(): float { return 1.5; }
        };
        $this->assertSame('1.5', Caster::toString($obj));
    }

    public function testBoolTrue(): void {
        $this->assertSame('true', Caster::toString(true));
    }

    public function testBoolFalse(): void {
        $this->assertSame('false', Caster::toString(false));
    }

    public function testToBoolObjectTrue(): void {
        $obj = new class implements ToBool {
            public function toBool(): bool { return true; }
        };
        $this->assertSame('true', Caster::toString($obj));
    }

    public function testToBoolObjectFalse(): void {
        $obj = new class implements ToBool {
            public function toBool(): bool { return false; }
        };
        $this->assertSame('false', Caster::toString($obj));
    }

    public function testArray(): void {
        $result = Caster::toString(['a' => 1]);
        $this->assertJson($result);
        $this->assertSame(['a' => 1], json_decode($result, true));
    }

    public function testObject(): void {
        $obj = new \stdClass();
        $obj->x = 1;
        $result = Caster::toString($obj);
        $this->assertJson($result);
        $this->assertSame(1, json_decode($result)->x);
    }

    public function testNullThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        Caster::toString(null);
    }

    public function testStringableTakesPriorityOverToInt(): void {
        $obj = new class implements Stringable, ToInt {
            public function __toString(): string { return 'from-string'; }
            public function toInt(): int { return 999; }
        };
        $this->assertSame('from-string', Caster::toString($obj));
    }
}
