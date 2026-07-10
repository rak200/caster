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
        Caster::toEnum(null, CasterToEnumTestStatus::class);
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
