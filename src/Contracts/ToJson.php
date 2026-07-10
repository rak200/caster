<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

/**
 * Contract for objects that provide their own JSON serialization.
 *
 * Takes priority over all other contracts in Caster::cast() and
 * Caster::toJson(). The returned string must be valid JSON.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface ToJson extends Castable
{
    /**
     * Return a JSON string representation of the object.
     *
     * @return string a valid JSON-encoded string
     */
    public function toJson(): string;
}
