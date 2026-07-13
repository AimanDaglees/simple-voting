#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

DUMP="database/simple-voting.sql.gz"

if [[ ! -f "$DUMP" ]]; then
  echo "Missing database dump: $DUMP" >&2
  exit 1
fi

gzip -t "$DUMP"
lando start

if [[ -f composer.lock ]]; then
  lando composer install --no-interaction --prefer-dist
else
  lando composer update --no-interaction --prefer-dist
fi

mkdir -p web/sites/default/files
cp scripts/lando/settings.php web/sites/default/settings.php
chmod 664 web/sites/default/settings.php
chmod -R ug+rwX web/sites/default/files

lando db-import "$DUMP"
lando drush updb -y
lando drush cr

echo
echo "Drupal 11 project database is ready."
echo "URL: https://simple-voting.lndo.site"
echo "Administrator: admin / admin"
echo "API user: api_voter / api_voter"
