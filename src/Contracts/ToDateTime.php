<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

use DateTimeImmutable;

/**
 * Contract for objects that can be represented as a date and time.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToDateTime extends Castable {

    /**
     * Return a DateTimeImmutable representation of the object.
     *
     * @return DateTimeImmutable The object's value as an immutable date and time.
     */
    public function toDateTime(): DateTimeImmutable;
}
