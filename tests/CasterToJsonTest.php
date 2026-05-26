<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToJson;

use function json_decode;

/**
 * Tests for Caster::toJson().
 *
 * Covers delegation to ToJson objects, encoding of Castable objects
 * through cast(), plain arrays and objects, JSON_PRETTY_PRINT default
 * behaviour, and custom flag handling.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class CasterToJsonTest extends TestCase {
    /** ToJson objects delegate directly to their toJson() method. */
    public function testToJsonObject(): void {
        $obj = new class implements ToJson {
            public function toJson(): string { return '{"a":1}'; }
        };
        $this->assertSame('{"a":1}', Caster::toJson($obj));
    }

    /** ToArray Castable objects are cast first, then JSON-encoded. */
    public function testToArrayCastable(): void {
        $obj = new class implements ToArray {
            public function toArray(): array { return ['x' => 2]; }
        };
        $result = Caster::toJson($obj);
        $this->assertJson($result);
        $this->assertSame(['x' => 2], json_decode($result, true));
    }

    /** ToInt Castable objects are cast to int, then JSON-encoded. */
    public function testToIntCastable(): void {
        $obj = new class implements ToInt {
            public function toInt(): int { return 99; }
        };
        $this->assertSame(99, json_decode(Caster::toJson($obj)));
    }

    /** ToBool Castable objects are cast to bool, then JSON-encoded. */
    public function testToBoolCastable(): void {
        $obj = new class implements ToBool {
            public function toBool(): bool { return true; }
        };
        $this->assertTrue(json_decode(Caster::toJson($obj)));
    }

    /** Plain arrays are JSON-encoded directly. */
    public function testPlainArray(): void {
        $result = Caster::toJson([1, 2, 3]);
        $this->assertSame([1, 2, 3], json_decode($result, true));
    }

    /** Plain stdClass objects are JSON-encoded with their public properties. */
    public function testPlainObject(): void {
        $obj = new \stdClass();
        $obj->key = 'value';
        $result = Caster::toJson($obj);
        $this->assertJson($result);
        $this->assertSame('value', json_decode($result)->key);
    }

    /** Output is pretty-printed by default (contains newlines). */
    public function testPrettyPrintByDefault(): void {
        $result = Caster::toJson(['a' => 1]);
        $this->assertStringContainsString("\n", $result);
    }

    /** Passing flags=0 disables pretty-printing and produces compact JSON. */
    public function testCustomFlagsDisablePrettyPrint(): void {
        $result = Caster::toJson(['a' => 1], 0);
        $this->assertStringNotContainsString("\n", $result);
        $this->assertJson($result);
    }

    /** ToJson objects return their own JSON regardless of the $flags argument. */
    public function testToJsonObjectIgnoresFlags(): void {
        $obj = new class implements ToJson {
            public function toJson(): string { return '{"raw":true}'; }
        };
        $this->assertSame('{"raw":true}', Caster::toJson($obj, 0));
    }
}
