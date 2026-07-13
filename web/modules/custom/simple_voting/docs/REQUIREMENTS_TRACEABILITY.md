# Requirements traceability

| Requirement | Implementation |
|---|---|
| Unique questions | `voting_question` custom content entity with numeric ID and UUID |
| Multiple options | `voting_option` entities referencing a question |
| Option image, title, description | Base fields on `voting_option` |
| Per-question result visibility | `show_results` field |
| Global shutdown | `simple_voting.settings:voting_enabled` |
| CMS voting | `/voting/{voting_question}` and `VoteForm` |
| One vote per user/question | Unique database key on `question_id + uid` |
| Question listing API | `GET /api/v1/voting/questions` |
| Question detail API | `GET /api/v1/voting/questions/{id}` |
| Vote API | `POST /api/v1/voting/questions/{id}/votes` |
| Results API | `GET /api/v1/voting/questions/{id}/results` |
| No JSON:API for central logic | Custom routes, controller, serializer, and services |
| No nodes | Custom content entities and dedicated vote table |
| Security | Basic Auth, permissions, entity access, server-side user identity |
| Concurrency | Atomic insert and database uniqueness constraint |
| Observability | Dedicated `simple_voting` logger channel |
| Documentation | README, environment guides, API docs, testing guides |
| Postman | `docs/api/Simple-Voting.postman_collection.json` |
| Drupal version | Drupal 11 with `core_version_requirement: ^11` |
