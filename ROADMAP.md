# Roadmap

Pending work, ordered. Released history lives in [CHANGELOG.md](CHANGELOG.md); a delivered entry
is **removed** by the pull request that delivers it, not annotated as done.

## Planned additions

**None outstanding.**

## Distribution

**Not on Packagist.** Consumers add this repository and `rak200/utils` as `"type": "vcs"` entries
and resolve versions from git tags — Composer reads `repositories` only from the root project, so
the requirement travels to every consumer rather than being declared once here. Publishing would
remove that, and it is not scheduled: the decision belongs with the ecosystem rather than with this
library alone.

## Test coverage

The suite covers **100.00%** of statements (166/166), and `.coverage-floor` carries that figure.
The floor is measured under **pcov** in CI; a local run under **xdebug** may read one statement
differently, which is expected and is not evidence of anything.

## Canary

- A delivered entry left in place on purpose (#99).
