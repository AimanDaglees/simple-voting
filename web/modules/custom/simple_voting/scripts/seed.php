<?php

/**
 * @file
 * Creates local demonstration data for Simple Voting.
 */

declare(strict_types=1);

use Drupal\simple_voting\Entity\VotingOption;
use Drupal\simple_voting\Entity\VotingQuestion;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Creates local demonstration users, a voter role, and an initial question.
 *
 * This script is intended only for a local project environment.
 */

$role = Role::load('voter') ?? Role::create([
  'id' => 'voter',
  'label' => 'Voter',
]);

foreach ([
  'access simple voting',
  'cast simple voting votes',
  'view simple voting results',
  'access simple voting api',
] as $permission) {
  $role->grantPermission($permission);
}
$role->save();

$user_storage = \Drupal::entityTypeManager()->getStorage('user');

$accounts = [
  'voter',
  'api_voter',
  'hidden_test',
  'invalid_test',
  'inactive_test',
  'concurrent_voter',
];

foreach ($accounts as $username) {
  $existing = $user_storage->loadByProperties(['name' => $username]);
  if ($existing) {
    continue;
  }

  $user = User::create([
    'name' => $username,
    'mail' => $username . '@example.test',
    'status' => 1,
    'roles' => ['voter'],
  ]);
  $user->setPassword($username);
  $user->save();

  print sprintf("Created user %s.\n", $username);
}

$question_storage = \Drupal::entityTypeManager()->getStorage('voting_question');
$question_ids = $question_storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('title', 'Which option do you prefer?')
  ->execute();

if (!$question_ids) {
  $question = VotingQuestion::create([
    'title' => 'Which option do you prefer?',
    'status' => 1,
    'show_results' => 1,
  ]);
  $question->save();

  foreach ([
    ['Option A', 'The first demonstration answer.', 0],
    ['Option B', 'The second demonstration answer.', 1],
    ['Option C', 'The third demonstration answer.', 2],
  ] as [$title, $description, $weight]) {
    VotingOption::create([
      'question' => $question->id(),
      'title' => $title,
      'description' => $description,
      'weight' => $weight,
      'status' => 1,
    ])->save();
  }

  print sprintf("Created question %d: %s.\n", $question->id(), $question->label());
}
else {
  print "Initial demonstration question already exists.\n";
}
