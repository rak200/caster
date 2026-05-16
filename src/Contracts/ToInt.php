<?php

declare(strict_types=1);

namespace Ricardo\Caster\Contracts;

interface ToInt extends Castable {

    /**
     * Convert the object to an integer representation.
     * @return int
     */
    public function toInt(): int;
}
