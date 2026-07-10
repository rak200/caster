<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

/**
 * Contract for objects that can be represented as an iterable collection.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToCollection extends Castable
{
    /**
     * Return an iterable representation of the object.
     *
     * @return iterable<mixed> the object's elements as an array or Traversable
     */
    public function toCollection(): iterable;
}
