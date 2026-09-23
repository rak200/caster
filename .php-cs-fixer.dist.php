<?php

declare(strict_types=1);

// Canary round 2: no require of the pinned standard, and no setRules either — so the
// require half of the gate must fire on its own, with the setRules half silent.

return (new PhpCsFixer\Config())
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    );
