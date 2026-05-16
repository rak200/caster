<?php

declare(strict_types=1);

namespace Ricardo\Caster\Contracts;

interface ToFloat extends Castable {

    /**
     * Convert the object to a float representation.
     * @return float
     */
    public function toFloat(): float;
}
