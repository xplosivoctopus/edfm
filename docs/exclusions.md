# What is intentionally excluded

This repository is designed to be publishable as source material. It intentionally excludes live operational data and private recovery material.

Do not add:

- raw database dumps;
- full-history XML exports containing private/deleted/suppressed revisions;
- live `LocalSettings.php`;
- `.env` files;
- OAuth, Turnstile, Cloudflare, Discord, or other credentials;
- private keys or certificates;
- logs;
- backups;
- cache/session/temp directories;
- user/account data;
- internal-only security reports or incident notes;
- unreviewed uploaded media bundles.

Use placeholders in examples. Store full disaster-recovery material only in an encrypted private backup location.

## Related repository

Current public wiki content is archived in:

- `https://github.com/xplosivoctopus/edfm-content`
