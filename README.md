# Elite Dangerous Field Manual

Project-specific source files, interface assets, and configuration examples for
[Elite Dangerous Field Manual](https://edfieldmanual.com/).

This repository intentionally does **not** include the MediaWiki application
release, the live database, uploaded-file storage, operational backups, logs,
private deployment notes, live server configuration, or secrets.

Included here:

- `branding/` — EDFM logos, icons, and wiki-interface/template source exports.
- `includes/` — EDFM-specific MediaWiki helper code.
- `scripts/` — maintenance helper scripts that are safe to share.
- `config/` — redacted examples only.
- `tests/` — smoke tests for EDFM-specific code.

Live deployments must provide their own MediaWiki installation, database,
secrets, web-server configuration, and private runtime settings.
