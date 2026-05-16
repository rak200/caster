<?php

declare(strict_types=1);

namespace Ricardo\Caster\Contracts;

interface ToBool extends Castable {

    /**
     * Convert the object to a boolean representation.
     * @return bool
     */
    public function toBool(): bool;
}
