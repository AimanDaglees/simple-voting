# Simple Voting

## Compatibility

- Drupal 11
- PHP 8.3

The module uses custom content entities for questions and answer
options. Votes are stored in a dedicated table with a database-level
uniqueness constraint.

## Features

- Administrative CRUD for questions and answer options
- Optional image, title, description, weight, and active state
- Authenticated voting in Drupal
- Manually implemented JSON API
- One vote per authenticated user and question
- Per-question result visibility
- Global voting shutdown
- Vote counts and percentages
- Structured logging
- Kernel and functional tests
- Postman collection and OpenAPI specification
- Lando environment documentation

## Installation in an existing Drupal project

Copy this directory to:

```text
web/modules/custom/simple_voting
```

Enable the module:

```bash
drush en simple_voting -y
drush cr
```

## Main routes

```text
/admin/structure/simple-voting
/admin/config/system/simple-voting
/voting
```

## API endpoints

```text
GET  /api/v1/voting/questions?page=0&limit=25
GET  /api/v1/voting/questions/{question_id}
POST /api/v1/voting/questions/{question_id}/votes
GET  /api/v1/voting/questions/{question_id}/results
```

## Documentation

```text
docs/ARCHITECTURE.md
docs/LANDO.md
docs/TESTING.md
docs/CODE_STANDARDS.md
docs/REQUIREMENTS_TRACEABILITY.md
docs/VALIDATION_REPORT.md
docs/api/
```
