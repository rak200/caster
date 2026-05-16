<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

use Stringable;

/**
 * Contract for objects that can be represented as a string via __toString().
 *
 * Combines Castable with PHP's built-in Stringable interface, so implementors
 * are automatically compatible with (string) casts and string contexts.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToString extends Castable, Stringable {

    /**
     * Return a string representation of the object.
     *
     * @return string The object's string value.
     */
    public function __toString(): string;
}
