<?php

declare(strict_types=1);

/**
 * RFC 0017, rollout step 5 — the scanner canary. NOT FOR MERGE.
 *
 * Reproduced shape for shape from the fixture `rak200/utils` measured on 2026-08-15,
 * which recorded `Findings: 3 (3 blocking)`, exit 1. The first attempt on this
 * repository did not reproduce it — it put the sink inside a class method as
 * `return eval($_POST['code'])` — and the gate stayed green, which was read as the rule
 * pack having lost `eval-use`. That is the same wrong answer this document has now
 * reached twice, for the same reason: silence is evidence about nothing until you know
 * what the noise should have sounded like.
 *
 * Three shapes:
 *   1. a superglobal concatenated into SQL
 *   2. `eval` on request input, undivided
 *   3. a shell call on request input
 *
 * It lives outside `src/` and `tests/` on purpose. phpstan.neon.dist,
 * .php-cs-fixer.dist.php, phpunit.xml and infection.json5.dist all look only at those
 * two directories, while semgrep scans `.` — so a fixture inside `src/` reddens an
 * earlier verb and the scanner step never runs.
 *
 * @param \PDO $connection
 */
function canary(\PDO $connection): void
{
    // 1. Request input concatenated straight into a query.
    $connection->query('SELECT * FROM users WHERE id = ' . $_GET['id']);

    // 2. Request input evaluated as code, undivided — no intermediate variable.
    eval($_POST['cmd']);

    // 3. Request input handed to a shell.
    system('ping -c 1 ' . $_GET['host']);
}
