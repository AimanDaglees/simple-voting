# Validation report

The following behavior was exercised successfully in a Drupal 11 environment used to prepare the included database:

- question collection: `200`;
- active question detail: `200`;
- vote creation: `201`;
- duplicate vote: `409`;
- visible results: `200`;
- invalid JSON: `400`;
- missing option ID: `400`;
- invalid option: `422`;
- unauthenticated API access: `401`;
- hidden result vote: `201`;
- hidden result retrieval: `403`;
- inactive question retrieval: `404`;
- inactive question vote: `404`;
- global API shutdown: `503`;
- concurrent submissions: one `201`, nine `409`, and one database row.

Corrective changes applied during validation:

- resolved the controller-property conflict with Drupal `ControllerBase`;
- marked CMS voting routes as non-cacheable so global shutdown appears immediately;
- retained the database unique key as the final concurrency guard.

This package targets Drupal 11 and declares `core_version_requirement: ^11`. The recorded functional validation was performed in a Drupal 11 environment.

## Static-analysis corrections

Corrections are included for the reported PHPCS strict-types spacing
findings and PHPStan iterable, field-definition, and field-item typing
findings. Run PHPCS and PHPStan inside the target container to confirm
the final environment-specific result.

## Final annotation corrections

The inherited form and access-handler parameters now have complete Drupal
docblock descriptions. By-reference form parameters use `array<mixed>` so
their PHPDoc contract does not narrow the parent method's mutable array
type.
