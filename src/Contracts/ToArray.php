<?php

declare(strict_types=1);

namespace Ricardo\Caster\Contracts;

interface ToArray extends Castable {

    /**
     * Return an array representation of the object.
     * @return array
     */
    public function toArray(): array;
}
