# Elite Dangerous Field Manual

Project-specific source files, interface assets, and configuration examples for
[Elite Dangerous Field Manual](https://edfieldmanual.com/).

This repository intentionally does **not** include the MediaWiki application
release, the live database, uploaded-file storage, operational backups, logs,
private deployment notes, live server configuration, or secrets.

Included here:

- `branding/` — EDFM logo/icon working files and wiki-interface/template source exports. Some branding imagery incorporates or derives from *Elite Dangerous* game assets; those underlying Frontier-owned materials remain the property of Frontier Developments plc and are not owned by EDFM or licensed for reuse by this repository.
- `includes/` — EDFM-specific MediaWiki helper code.
- `scripts/` — maintenance helper scripts that are safe to share.
- `config/` — redacted examples only.
- `tests/` — smoke tests for EDFM-specific code.

Live deployments must provide their own MediaWiki installation, database,
secrets, web-server configuration, and private runtime settings.

See `NOTICE.md` and `branding/README.md` for the Frontier-owned material and
EDFM branding notices.

## Continuity

This repository preserves the EDFM source/config-example layer. Current public wiki content is archived separately in `xplosivoctopus/edfm-content`. See:

- `docs/continuity.md`
- `docs/exclusions.md`

