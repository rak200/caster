<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\Castable;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToJson;
use Rak200\Caster\Contracts\ToString;

final class CasterCastTest extends TestCase {
    public function testToJson(): void {
        $obj = new class implements ToJson {
            public function toJson(): string { return '{"key":"value"}'; }
        };
        $this->assertSame('{"key":"value"}', Caster::cast($obj));
    }

    public function testToString(): void {
        $obj = new class implements ToString {
            public function __toString(): string { return 'hello'; }
        };
        $this->assertSame('hello', Caster::cast($obj));
    }

    public function testToInt(): void {
        $obj = new class implements ToInt {
            public function toInt(): int { return 42; }
        };
        $this->assertSame(42, Caster::cast($obj));
    }

    public function testToFloat(): void {
        $obj = new class implements ToFloat {
            public function toFloat(): float { return 3.14; }
        };
        $this->assertSame(3.14, Caster::cast($obj));
    }

    public function testToBoolTrue(): void {
        $obj = new class implements ToBool {
            public function toBool(): bool { return true; }
        };
        $this->assertTrue(Caster::cast($obj));
    }

    public function testToBoolFalse(): void {
        $obj = new class implements ToBool {
            public function toBool(): bool { return false; }
        };
        $this->assertFalse(Caster::cast($obj));
    }

    public function testToArray(): void {
        $obj = new class implements ToArray {
            public function toArray(): array { return [1, 2, 3]; }
        };
        $this->assertSame([1, 2, 3], Caster::cast($obj));
    }

    public function testPlainCastableThrows(): void {
        $obj = new class implements Castable {};
        $this->expectException(InvalidArgumentException::class);
        Caster::cast($obj);
    }

    public function testToJsonTakesPriorityOverToString(): void {
        $obj = new class implements ToJson, ToString {
            public function toJson(): string { return '"json"'; }
            public function __toString(): string { return 'string'; }
        };
        $this->assertSame('"json"', Caster::cast($obj));
    }

    public function testToJsonTakesPriorityOverToInt(): void {
        $obj = new class implements ToJson, ToInt {
            public function toJson(): string { return '"json"'; }
            public function toInt(): int { return 0; }
        };
        $this->assertSame('"json"', Caster::cast($obj));
    }

    public function testToIntTakesPriorityOverToFloat(): void {
        $obj = new class implements ToInt, ToFloat {
            public function toInt(): int { return 5; }
            public function toFloat(): float { return 5.0; }
        };
        $this->assertSame(5, Caster::cast($obj));
    }
}
