<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

use UnitEnum;

/**
 * Contract for objects that can be represented as an enum case.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToEnum extends Castable {

    /**
     * Return an enum representation of the object.
     *
     * @return UnitEnum The object's value as an enum case. Implementers may
     *                  return a {@see \BackedEnum} (covariant) when a backing
     *                  value is meaningful.
     */
    public function toEnum(): UnitEnum;
}
