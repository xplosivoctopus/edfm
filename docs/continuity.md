# EDFM continuity model

This repository preserves the EDFM-specific source layer: project-owned assets, custom helper code, example configuration, scripts, and tests.

It is one part of the broader continuity plan.

## Companion content archive

Current public wiki content is preserved separately in:

- `https://github.com/xplosivoctopus/edfm-content`

The split is intentional:

- `edfm` keeps source code, assets, examples, and developer-facing material.
- `edfm-content` keeps current public wikitext exports.

A future maintainer should use both repositories when rebuilding a readable EDFM-derived site.

## What this repository is for

- Preserve EDFM-specific code and assets.
- Provide sanitized example configuration.
- Support testing and future development.
- Explain the relationship between source/config and archived content.

## What this repository is not

This repository is not a full server backup. It does not contain:

- the MediaWiki database;
- live `LocalSettings.php`;
- user accounts, emails, password hashes, sessions, or tokens;
- logs;
- backups;
- DNS/Cloudflare/OAuth/Turnstile secrets;
- deleted or suppressed wiki content;
- uploaded binary media storage.

## Recovery path overview

If the live EDFM site becomes unavailable:

1. Clone this repository for source/config examples and custom helper code.
2. Clone `xplosivoctopus/edfm-content` for current public page text.
3. Decide whether to build a static archive or a fresh MediaWiki install.
4. Import templates/modules before main article content.
5. Preserve licensing and attribution information.
6. If an encrypted disaster-recovery package is available to trusted successors, use that for full restoration instead.

## Disaster recovery boundary

A true full restore needs separate encrypted offsite backups containing the database, uploads, live configuration, and restore instructions. Those materials should remain private and encrypted because they may contain sensitive user and operational data.
