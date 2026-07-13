<?php

/**
 * @file
 * Creates additional Simple Voting test data.
 */

declare(strict_types=1);

use Drupal\simple_voting\Entity\VotingOption;
use Drupal\simple_voting\Entity\VotingQuestion;

/**
 * Creates additional questions for end-to-end Simple Voting tests.
 *
 * Run this script through Drush from the project root.
 */

$definitions = [
  [
    'title' => 'Which Drupal area should receive the next improvement?',
    'show_results' => FALSE,
    'status' => TRUE,
    'options' => [
      ['Security', 'Security hardening and access control improvements.', 0],
      ['Performance', 'Caching, query optimization, and response-time improvements.', 1],
      ['Editorial experience', 'Better forms, workflows, and administrative usability.', 2],
    ],
  ],
  [
    'title' => 'Which API enhancement should be prioritized?',
    'show_results' => TRUE,
    'status' => TRUE,
    'options' => [
      ['Pagination metadata', 'Improve pagination and navigation information.', 0],
      ['Rate limiting', 'Protect the API from excessive request volume.', 1],
      ['Token authentication', 'Add a token-based authentication mechanism.', 2],
    ],
  ],
  [
    'title' => 'Inactive voting test question',
    'show_results' => TRUE,
    'status' => FALSE,
    'options' => [
      ['Inactive option A', 'Used to confirm inactive questions cannot receive votes.', 0],
      ['Inactive option B', 'Used to confirm inactive questions are hidden.', 1],
    ],
  ],
];

$question_storage = \Drupal::entityTypeManager()->getStorage('voting_question');

foreach ($definitions as $definition) {
  $existing_ids = $question_storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('title', $definition['title'])
    ->execute();

  if ($existing_ids) {
    $question = $question_storage->load(reset($existing_ids));
    print sprintf(
      "Skipped existing question %d: %s\n",
      $question->id(),
      $question->label()
    );
    continue;
  }

  $question = VotingQuestion::create([
    'title' => $definition['title'],
    'show_results' => $definition['show_results'] ? 1 : 0,
    'status' => $definition['status'] ? 1 : 0,
  ]);
  $question->save();

  foreach ($definition['options'] as [$title, $description, $weight]) {
    VotingOption::create([
      'question' => $question->id(),
      'title' => $title,
      'description' => $description,
      'weight' => $weight,
      'status' => 1,
    ])->save();
  }

  print sprintf(
    "Created question %d: %s (%s results, %s)\n",
    $question->id(),
    $question->label(),
    $definition['show_results'] ? 'visible' : 'hidden',
    $definition['status'] ? 'active' : 'inactive'
  );
}

\Drupal::service('cache_tags.invalidator')->invalidateTags([
  'voting_question_list',
]);

print "Additional voting test data is ready.\n";
