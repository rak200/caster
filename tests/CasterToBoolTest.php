<?php

declare(strict_types=1);

namespace Rak200\Caster\Tests;

use ArrayIterator;
use BcMath\Number;
use InvalidArgumentException;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Caster;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Caster\Contracts\ToBool;
use Rak200\Caster\Contracts\ToCollection;
use Rak200\Caster\Contracts\ToFloat;
use Rak200\Caster\Contracts\ToInt;
use Rak200\Caster\Contracts\ToNumber;
use RuntimeException;
use Stringable;
use Traversable;

/**
 * Tests for Caster::toBool().
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 *
 * @internal
 */
#[CoversClass(Caster::class)]
final class CasterToBoolTest extends TestCase
{
    public function testBoolTrue(): void
    {
        $this->assertTrue(Caster::toBool(true));
    }

    public function testIntZero(): void
    {
        $this->assertFalse(Caster::toBool(0));
    }

    public function testIntNonZero(): void
    {
        $this->assertTrue(Caster::toBool(42));
    }

    public function testFloatZero(): void
    {
        $this->assertFalse(Caster::toBool(0.0));
    }

    public function testStringEmpty(): void
    {
        $this->assertFalse(Caster::toBool(''));
    }

    public function testStringZero(): void
    {
        $this->assertFalse(Caster::toBool('0'));
    }

    public function testStringOther(): void
    {
        $this->assertTrue(Caster::toBool('hello'));
    }

    public function testStringable(): void
    {
        $obj = new class implements Stringable {
            public function __toString(): string
            {
                return 'x';
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    /** A Stringable is judged by its string content: one rendering "0" is false, not truthy by mere objecthood. */
    public function testStringableFalsy(): void
    {
        $obj = new class implements Stringable {
            public function __toString(): string
            {
                return '0';
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testToBool(): void
    {
        $obj = new class implements ToBool {
            public function toBool(): bool
            {
                return true;
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testToInt(): void
    {
        $obj = new class implements ToInt {
            public function toInt(): int
            {
                return 1;
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testToFloat(): void
    {
        $obj = new class implements ToFloat {
            public function toFloat(): float
            {
                return 0.0;
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testToNumber(): void
    {
        $obj = new class implements ToNumber {
            public function toNumber(): Number
            {
                return new Number('0');
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testEmptyArray(): void
    {
        $this->assertFalse(Caster::toBool([]));
    }

    public function testNonEmptyArray(): void
    {
        $this->assertTrue(Caster::toBool([1]));
    }

    public function testToArrayEmpty(): void
    {
        $obj = new class implements ToArray {
            public function toArray(): array
            {
                return [];
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testToCollectionEmpty(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                return [];
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testToCollectionGenerator(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                yield 1;
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Cannot convert null to bool');
        Caster::toBool(null);
    }

    /** Zero is false at any scale — (bool) '0.00' string truthiness would say true. */
    public function testNumberZeroWithScale(): void
    {
        $this->assertFalse(Caster::toBool(new Number('0.00')));
    }

    public function testNumberNonZero(): void
    {
        $this->assertTrue(Caster::toBool(new Number('0.01')));
    }

    public function testToNumberZeroWithScale(): void
    {
        $obj = new class implements ToNumber {
            public function toNumber(): Number
            {
                return new Number('0.00');
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    /** Emptiness is decided from the first element alone; the iterable is never materialised. */
    public function testToCollectionEmptinessIsLazy(): void
    {
        $obj = new class implements ToCollection {
            public function toCollection(): iterable
            {
                yield 1;

                throw new RuntimeException('must not be reached');
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testEmptyTraversable(): void
    {
        $this->assertFalse(Caster::toBool(new ArrayIterator([])));
    }

    public function testNonEmptyTraversable(): void
    {
        $this->assertTrue(Caster::toBool(new ArrayIterator([1])));
    }

    public function testEmptyGenerator(): void
    {
        $empty = (static function (): Traversable {
            yield from [];
        })();
        $this->assertFalse(Caster::toBool($empty));
    }

    public function testNonEmptyGenerator(): void
    {
        $gen = (static function (): Traversable {
            yield 1;
        })();
        $this->assertTrue(Caster::toBool($gen));
    }

    public function testIteratorAggregate(): void
    {
        $obj = new class implements IteratorAggregate {
            public function getIterator(): Traversable
            {
                return new ArrayIterator(['a']);
            }
        };
        $this->assertTrue(Caster::toBool($obj));
    }

    public function testGeneratorIsNotConsumed(): void
    {
        $gen = (static function (): Traversable {
            yield 1;

            yield 2;

            yield 3;
        })();

        Caster::toBool($gen);

        // Emptiness starts the generator without advancing it, so nothing is lost.
        $this->assertSame([1, 2, 3], iterator_to_array($gen, false));
    }

    public function testTraversableDoesNotOutrankAContract(): void
    {
        // Implements both. ToBool must decide it, not iterability — the object is
        // non-empty as an iterable and false by its own contract.
        $obj = new class implements IteratorAggregate, ToBool {
            public function toBool(): bool
            {
                return false;
            }

            public function getIterator(): Traversable
            {
                return new ArrayIterator([1, 2]);
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testToCollectionOutranksBeingTraversable(): void
    {
        // Implements both, and they disagree: iterating it directly yields two
        // elements, while its contract reports an empty collection.
        $obj = new class implements IteratorAggregate, ToCollection {
            public function toCollection(): iterable
            {
                return [];
            }

            public function getIterator(): Traversable
            {
                return new ArrayIterator([1, 2]);
            }
        };
        $this->assertFalse(Caster::toBool($obj));
    }

    public function testTryToBool(): void
    {
        $this->assertTrue(Caster::tryToBool(1));
        $this->assertFalse(Caster::tryToBool('0'));
    }

    public function testTryToBoolNullOnNull(): void
    {
        $this->assertNull(Caster::tryToBool(null));
    }
}
