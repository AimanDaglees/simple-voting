# Lando installation

This project runs on Drupal 11 with:

- PHP 8.3
- MariaDB 10.11
- Drush 13
- Lando's `drupal11` recipe

## Install using the included database

From the project root, run:

```bash
bash scripts/lando/install.sh
```

This command:

1. Starts Lando.
2. Installs Composer dependencies.
3. Configures Drupal database access.
4. Imports `database/simple-voting.sql.gz`.
5. Runs database updates.
6. Rebuilds Drupal caches.

## Fresh installation

To create a new Drupal 11 site, enable the module, add sample data, and
export a new database dump:

```bash
bash scripts/lando/setup.sh
```

## Useful commands

```bash
lando start
lando stop
lando info
lando drush status
lando drush cr
lando composer validate --strict
lando composer check:phpcs
lando composer check:phpstan
lando db-import database/simple-voting.sql.gz
lando db-export database/simple-voting.sql.gz
```

## Main routes

```text
https://simple-voting.lndo.site
/admin/structure/simple-voting
/admin/config/system/simple-voting
/voting
```
