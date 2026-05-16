<?php

declare(strict_types=1);

namespace Ricardo\Caster\Contracts;

interface ToJson extends Castable {

    /**
     * Return a JSON representation of the object.
     * @return string
     */
    public function toJson(): string;
}
