#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

lando start

if [[ -f composer.lock ]]; then
  lando composer install --no-interaction --prefer-dist
else
  lando composer update --no-interaction --prefer-dist
fi

lando drush site:install standard \
  --db-url=mysql://drupal11:drupal11@database/drupal11 \
  --site-name="Simple Voting Demo" \
  --account-name=admin \
  --account-pass=admin \
  -y

lando drush en simple_voting -y
lando drush php:script /app/web/modules/custom/simple_voting/scripts/seed.php
lando drush php:script /app/web/modules/custom/simple_voting/scripts/seed-additional-voting.php
lando drush cr

mkdir -p database
rm -f database/simple-voting.sql.gz
lando db-export database/simple-voting.sql.gz

echo
echo "Drupal 11 is ready."
echo "URL: https://simple-voting.lndo.site"
echo "Administrator: admin / admin"
echo "API user: api_voter / api_voter"
echo "Database: database/simple-voting.sql.gz"
