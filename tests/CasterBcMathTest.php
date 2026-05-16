<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use BcMath\Number;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;

final class CasterBcMathTest extends TestCase {
    public function testToStringDecimal(): void {
        $this->assertSame('3.14', Caster::toString(new Number('3.14')));
    }

    public function testToStringInteger(): void {
        $this->assertSame('42', Caster::toString(new Number('42')));
    }

    public function testToStringNegative(): void {
        $this->assertSame('-1.5', Caster::toString(new Number('-1.5')));
    }

    public function testToStringZero(): void {
        $this->assertSame('0', Caster::toString(new Number('0')));
    }

    public function testToStringArithmeticResult(): void {
        $result = new Number('3.14') + new Number('1');
        $this->assertSame('4.14', Caster::toString($result));
    }

    public function testToJsonValue(): void {
        $decoded = json_decode(Caster::toJson(new Number('3.14')), true);
        $this->assertSame('3.14', $decoded['value']);
    }

    public function testToJsonScale(): void {
        $decoded = json_decode(Caster::toJson(new Number('3.14')), true);
        $this->assertSame(2, $decoded['scale']);
    }

    public function testToJsonIntegerScale(): void {
        $decoded = json_decode(Caster::toJson(new Number('42')), true);
        $this->assertSame('42', $decoded['value']);
        $this->assertSame(0, $decoded['scale']);
    }

    public function testToJsonPrettyPrintByDefault(): void {
        $result = Caster::toJson(new Number('1'));
        $this->assertStringContainsString("\n", $result);
    }
}
