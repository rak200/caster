<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

/**
 * Contract for objects that can be represented as a float.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToFloat extends Castable
{
    /**
     * Return a float representation of the object.
     *
     * @return float the object's numeric value as a float
     */
    public function toFloat(): float;
}
