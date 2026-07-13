# Lando install

## Prerequisites

Install Docker and Lando before continuing.

## Install with the included database

Open a terminal in the project root and run:

```bash
bash scripts/lando/install.sh
```

The script starts Lando, installs Composer dependencies, imports the
database, applies database updates, and clears Drupal caches.

After installation, open:

```text
https://simple-voting.lndo.site
```

Administrative pages:

```text
https://simple-voting.lndo.site/admin/structure/simple-voting
https://simple-voting.lndo.site/admin/config/system/simple-voting
```

Voting page:

```text
https://simple-voting.lndo.site/voting
```

## Fresh installation

To install a new Drupal 11 site instead of importing the included
database:

```bash
bash scripts/lando/setup.sh
```

## Local accounts

```text
Administrator: admin / admin
API user: api_voter / api_voter
Voter: voter / voter
```

Use these accounts only in the local development environment.

## Validation commands

```bash
lando composer validate --strict
lando drush cr
lando composer check:phpcs
lando composer check:phpstan
```

## Stop the environment

```bash
lando stop
```
