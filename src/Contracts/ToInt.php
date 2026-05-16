<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

/**
 * Contract for objects that can be represented as an integer.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToInt extends Castable {

    /**
     * Return an integer representation of the object.
     *
     * @return int The object's numeric value as an integer.
     */
    public function toInt(): int;
}
