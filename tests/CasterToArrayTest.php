<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use ArrayIterator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToCollection;

/**
 * Tests for Caster::toArray().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterToArrayTest extends TestCase
{
    public function testArray(): void
    {
        $this->assertSame([1, 2, 3], Caster::toArray([1, 2, 3]));
    }

    public function testEmptyArray(): void
    {
        $this->assertSame([], Caster::toArray([]));
    }

    public function testToArray(): void
    {
        $obj = new class implements ToArray {
            public function toArray(): array
            {
                return ['a' => 1];
            }
        };
        $this->assertSame(['a' => 1], Caster::toArray($obj));
    }

    public function testToCollectionArray(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                return ['x' => 10];
            }
        };
        $this->assertSame(['x' => 10], Caster::toArray($obj));
    }

    public function testToCollectionGenerator(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                yield 1;

                yield 2;
            }
        };
        $this->assertSame([1, 2], Caster::toArray($obj));
    }

    public function testTraversable(): void
    {
        $iterator = new ArrayIterator(['a', 'b']);
        $this->assertSame(['a', 'b'], Caster::toArray($iterator));
    }

    public function testStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toArray('hello');
    }

    public function testNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toArray(null);
    }
}
