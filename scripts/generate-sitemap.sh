#!/usr/bin/env bash
set -euo pipefail
WIKI_ROOT=${WIKI_ROOT:-/path/to/mediawiki}
OUT_DIR="$WIKI_ROOT/sitemap"
SERVER=https://edfieldmanual.com
install -d -m 0755 "$OUT_DIR"
# --namespaces below is the sole source of truth for sitemap namespace scope --
# generateSitemap.php prefers --namespaces over $wgSitemapNamespaces whenever
# both are set, so that config var was removed from edfm-seo.php as dead
# weight (2026-08-18 duplicate-info audit).
php "$WIKI_ROOT/maintenance/run.php" generateSitemap \
  --server "$SERVER" \
  --fspath "$OUT_DIR" \
  --urlpath /sitemap/ \
  --identifier edfm \
  --namespaces 0 \
  --skip-redirects \
  --compress no \
  --quiet
# generateSitemap has no noindex concept (it's a generic core script with
# no hook point) -- strip EDFM-noindexed pages (true placeholders marked
# via {{SEO|noindex=yes}} / {{Article status|...|noindex=yes}}) out as a
# separate post-processing step (2026-08-19 SEO pass).
php "$WIKI_ROOT/maintenance/run.php" "${EDFM_SOURCE_ROOT:-/path/to/edfm}/scripts/filterSitemapNoindex.php" --dir "$OUT_DIR"
find "$OUT_DIR" -type f -name 'sitemap*.xml' -exec chmod 0644 {} +
