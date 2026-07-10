<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

use BcMath\Number;

/**
 * Contract for objects that can be represented as an arbitrary-precision number.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToNumber extends Castable
{
    /**
     * Return a BcMath\Number representation of the object.
     *
     * @return Number the object's value as an arbitrary-precision number
     */
    public function toNumber(): Number;
}
