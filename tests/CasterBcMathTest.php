<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use BcMath\Number;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;

use function json_decode;

/**
 * Tests for Caster integration with BcMath\Number (PHP 8.4+).
 *
 * BcMath\Number implements Stringable, so toString() uses __toString().
 * toJson() falls through to json_encode(), which serialises the object
 * as {"value": "...", "scale": N} using its public readonly properties.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterBcMathTest extends TestCase
{
    /** Decimal BcMath\Number is stringified via Stringable. */
    public function testToStringDecimal(): void
    {
        $this->assertSame('3.14', Caster::toString(new Number('3.14')));
    }

    /** Integer BcMath\Number (no fractional part) is stringified correctly. */
    public function testToStringInteger(): void
    {
        $this->assertSame('42', Caster::toString(new Number('42')));
    }

    /** Negative BcMath\Number includes the minus sign. */
    public function testToStringNegative(): void
    {
        $this->assertSame('-1.5', Caster::toString(new Number('-1.5')));
    }

    /** Zero is stringified as "0". */
    public function testToStringZero(): void
    {
        $this->assertSame('0', Caster::toString(new Number('0')));
    }

    /** Arithmetic results are BcMath\Number objects and stringify correctly. */
    public function testToStringArithmeticResult(): void
    {
        $result = new Number('3.14') + new Number('1');
        $this->assertSame('4.14', Caster::toString($result));
    }

    /** toJson() encodes the decimal value in the "value" property. */
    public function testToJsonValue(): void
    {
        $decoded = json_decode(Caster::toJson(new Number('3.14')), true);
        $this->assertIsArray($decoded);
        $this->assertSame('3.14', $decoded['value']);
    }

    /** toJson() encodes the number of decimal places in the "scale" property. */
    public function testToJsonScale(): void
    {
        $decoded = json_decode(Caster::toJson(new Number('3.14')), true);
        $this->assertIsArray($decoded);
        $this->assertSame(2, $decoded['scale']);
    }

    /** Integer BcMath\Number has scale 0 in the JSON output. */
    public function testToJsonIntegerScale(): void
    {
        $decoded = json_decode(Caster::toJson(new Number('42')), true);
        $this->assertIsArray($decoded);
        $this->assertSame('42', $decoded['value']);
        $this->assertSame(0, $decoded['scale']);
    }

    /** toJson() uses JSON_PRETTY_PRINT by default, producing newlines. */
    public function testToJsonPrettyPrintByDefault(): void
    {
        $result = Caster::toJson(new Number('1'));
        $this->assertStringContainsString("\n", $result);
    }
}
