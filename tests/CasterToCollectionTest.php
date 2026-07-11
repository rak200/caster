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
 * Tests for Caster::toCollection().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 *
 * @coversNothing
 */
final class CasterToCollectionTest extends TestCase
{
    public function testArrayPassthrough(): void
    {
        $this->assertSame([1, 2, 3], Caster::toCollection([1, 2, 3]));
    }

    public function testTraversablePassthrough(): void
    {
        $iterator = new ArrayIterator(['a', 'b']);
        $this->assertSame($iterator, Caster::toCollection($iterator));
    }

    public function testToCollection(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                return ['k' => 'v'];
            }
        };
        $this->assertSame(['k' => 'v'], Caster::toCollection($obj));
    }

    public function testToArray(): void
    {
        $obj = new class implements ToArray {
            public function toArray(): array
            {
                return [10, 20];
            }
        };
        $this->assertSame([10, 20], Caster::toCollection($obj));
    }

    public function testStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toCollection('hello');
    }

    public function testNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Caster::toCollection(null);
    }

    public function testTryToCollection(): void
    {
        $this->assertSame([1, 2], Caster::tryToCollection([1, 2]));
    }

    public function testTryToCollectionNullOnString(): void
    {
        $this->assertNull(Caster::tryToCollection('hello'));
    }
}
