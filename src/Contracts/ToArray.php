<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

/**
 * Contract for objects that can be represented as an array.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToArray extends Castable
{
    /**
     * Return an array representation of the object.
     *
     * @return array<mixed> the object's data as a plain PHP array
     */
    public function toArray(): array;
}
