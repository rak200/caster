<?php

declare(strict_types=1);

namespace Rak200\Caster\Contracts;

/**
 * Marker interface for objects that can be dispatched by Caster::cast().
 *
 * Implement one of the typed sub-interfaces (ToArray, ToBool, ToCollection,
 * ToDateTime, ToEnum, ToFloat, ToInt, ToJson, ToNumber, ToString) to declare
 * which type the object can be reduced to.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface Castable {}
