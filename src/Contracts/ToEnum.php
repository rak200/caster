<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

use BackedEnum;

/**
 * Contract for objects that can be represented as a backed enum case.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToEnum extends Castable {

    /**
     * Return a BackedEnum representation of the object.
     *
     * @return BackedEnum The object's value as a backed enum case.
     */
    public function toEnum(): BackedEnum;
}
