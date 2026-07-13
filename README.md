# Simple Voting — Drupal 11 with Lando

Simple Voting is a Drupal 11 project that provides a custom voting
system built with custom content entities, a manual REST API, and
authenticated per-user voting.

## Requirements

- Lando
- Docker
- Composer 2
- PHP 8.3
- MariaDB 10.11

## Installation

The project includes a database dump with the module enabled and sample
voting data.

```bash
bash scripts/lando/install.sh
```

For a fresh Drupal 11 installation without importing the included
database:

```bash
bash scripts/lando/setup.sh
```

A copy of the installation instructions is available inside the docroot:

```text
web/lando install.md
```

## Local URLs

```text
https://simple-voting.lndo.site
/admin/structure/simple-voting
/admin/config/system/simple-voting
/voting
```

## Default local accounts

```text
Administrator: admin / admin
API user: api_voter / api_voter
Voter: voter / voter
```

These credentials are intended only for the local development
environment.

## Module documentation

```text
web/modules/custom/simple_voting/README.md
web/modules/custom/simple_voting/docs/ARCHITECTURE.md
web/modules/custom/simple_voting/docs/LANDO.md
web/modules/custom/simple_voting/docs/TESTING.md
web/modules/custom/simple_voting/docs/CODE_STANDARDS.md
web/modules/custom/simple_voting/docs/REQUIREMENTS_TRACEABILITY.md
web/modules/custom/simple_voting/docs/api/
```
