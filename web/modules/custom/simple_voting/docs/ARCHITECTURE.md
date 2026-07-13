# Architecture

## Data model

### Voting question

`voting_question` is a custom content entity containing:

- unique numeric ID and UUID
- question title
- active state
- result-visibility setting
- created and changed timestamps

### Voting option

`voting_option` is a custom content entity containing:

- parent question reference
- title
- brief description
- optional image
- display weight
- active state

### Vote table

`simple_voting_vote` is a dedicated table containing:

- `question_id`
- `option_id`
- `uid`
- `created`
- `source`

A unique database key on `question_id + uid` is the final concurrency guard.

## Services

- `VoteManager`: validation, atomic persistence, duplicate handling, cache invalidation, and logging
- `ResultManager`: aggregate counts and percentages
- `QuestionSerializer`: API-safe question and option payloads

Both the Drupal form and the API use the same central voting service.

## API

The API is implemented with custom routes and controllers. JSON:API is not used for the central endpoint logic.

Authentication uses Drupal core Basic Authentication. The authenticated account is always used as the vote owner; clients cannot submit an arbitrary user ID.

## Result visibility

Regular users can view results only when:

1. global voting is enabled;
2. the question is active;
3. the question permits result display;
4. the authenticated account has voted;
5. the account has the required permission.

Administrators can inspect hidden results.

## Global shutdown

The global setting blocks the CMS voting flow and all external API endpoints. CMS voting routes are marked `no_cache: TRUE` so a previously rendered question list is not reused after shutdown.

## Observability

The `simple_voting` logger channel records:

- successful votes;
- duplicate attempts;
- persistence failures;
- unexpected API failures.

Credentials and authorization headers are not logged.
