<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

/**
 * Contract for objects that can be represented as a boolean.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToBool extends Castable {

    /**
     * Return a boolean representation of the object.
     *
     * @return bool The object's boolean value.
     */
    public function toBool(): bool;
}
