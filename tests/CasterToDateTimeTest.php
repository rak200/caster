<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToDateTime;
use Rak200\Caster\Contracts\ToInt;
use Stringable;

/**
 * Tests for Caster::toDateTime().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class CasterToDateTimeTest extends TestCase {
    public function testDateTimeImmutablePassthrough(): void {
        $dt = new DateTimeImmutable('2026-05-27T12:00:00+00:00');
        $this->assertSame($dt, Caster::toDateTime($dt));
    }

    public function testDateTimeMutableConverted(): void {
        $mutable = new DateTime('2026-05-27T12:00:00+00:00');
        $result = Caster::toDateTime($mutable);
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2026-05-27T12:00:00+00:00', $result->format('c'));
    }

    public function testIntAsUnixTimestamp(): void {
        $result = Caster::toDateTime(1748366400);
        $this->assertSame(1748366400, $result->getTimestamp());
    }

    public function testString(): void {
        $result = Caster::toDateTime('2026-05-27T12:00:00+00:00');
        $this->assertSame('2026-05-27T12:00:00+00:00', $result->format('c'));
    }

    public function testStringable(): void {
        $obj = new class implements Stringable {
            public function __toString(): string { return '2026-05-27T12:00:00+00:00'; }
        };
        $result = Caster::toDateTime($obj);
        $this->assertSame('2026-05-27T12:00:00+00:00', $result->format('c'));
    }

    public function testToDateTime(): void {
        $expected = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $obj = new class($expected) implements ToDateTime {
            public function __construct(private DateTimeImmutable $dt) {}
            public function toDateTime(): DateTimeImmutable { return $this->dt; }
        };
        $this->assertSame($expected, Caster::toDateTime($obj));
    }

    public function testToInt(): void {
        $obj = new class implements ToInt {
            public function toInt(): int { return 1748366400; }
        };
        $this->assertSame(1748366400, Caster::toDateTime($obj)->getTimestamp());
    }

    public function testNullThrows(): void {
        $this->expectException(InvalidArgumentException::class);
        Caster::toDateTime(null);
    }
}
