<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToJson;

final class CasterToJsonTest extends TestCase {
    public function testToJsonObject(): void {
        $obj = new class implements ToJson {
            public function toJson(): string { return '{"a":1}'; }
        };
        $this->assertSame('{"a":1}', Caster::toJson($obj));
    }

    public function testToArrayCastable(): void {
        $obj = new class implements ToArray {
            public function toArray(): array { return ['x' => 2]; }
        };
        $result = Caster::toJson($obj);
        $this->assertJson($result);
        $this->assertSame(['x' => 2], json_decode($result, true));
    }

    public function testToIntCastable(): void {
        $obj = new class implements ToInt {
            public function toInt(): int { return 99; }
        };
        $this->assertSame(99, json_decode(Caster::toJson($obj)));
    }

    public function testToBoolCastable(): void {
        $obj = new class implements ToBool {
            public function toBool(): bool { return true; }
        };
        $this->assertTrue(json_decode(Caster::toJson($obj)));
    }

    public function testPlainArray(): void {
        $result = Caster::toJson([1, 2, 3]);
        $this->assertSame([1, 2, 3], json_decode($result, true));
    }

    public function testPlainObject(): void {
        $obj = new \stdClass();
        $obj->key = 'value';
        $result = Caster::toJson($obj);
        $this->assertJson($result);
        $this->assertSame('value', json_decode($result)->key);
    }

    public function testPrettyPrintByDefault(): void {
        $result = Caster::toJson(['a' => 1]);
        $this->assertStringContainsString("\n", $result);
    }

    public function testCustomFlagsDisablePrettyPrint(): void {
        $result = Caster::toJson(['a' => 1], 0);
        $this->assertStringNotContainsString("\n", $result);
        $this->assertJson($result);
    }

    public function testToJsonObjectIgnoresFlags(): void {
        $obj = new class implements ToJson {
            public function toJson(): string { return '{"raw":true}'; }
        };
        $this->assertSame('{"raw":true}', Caster::toJson($obj, 0));
    }
}
