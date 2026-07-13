<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_voting\VotingOptionInterface;
use Drupal\simple_voting\VotingQuestionInterface;

/**
 * Builds aggregated results without storing mutable counters.
 */
final class ResultManager {

  public function __construct(
    private readonly Connection $database,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns counts and percentages for all active options.
   *
   * @return array{question_id:int,total_votes:int,options:array<int,array<string,mixed>>}
   *   Aggregated result payload.
   */
  public function getResults(VotingQuestionInterface $question): array {
    $option_storage = $this->entityTypeManager->getStorage('voting_option');
    $option_ids = $option_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('question', (int) $question->id())
      ->sort('weight')
      ->sort('title')
      ->execute();

    $options = $option_storage->loadMultiple($option_ids);

    $query = $this->database
      ->select('simple_voting_vote', 'v');
    $query->addField('v', 'option_id');
    $query->addExpression('COUNT(v.id)', 'vote_count');
    $query->condition('v.question_id', (int) $question->id());
    $query->groupBy('v.option_id');

    $counts = [];
    foreach ($query->execute() as $row) {
      $counts[(int) $row->option_id] = (int) $row->vote_count;
    }

    $total = array_sum($counts);
    $rows = [];

    foreach ($option_ids as $option_id) {
      $option = $options[$option_id] ?? NULL;
      if (!$option instanceof VotingOptionInterface) {
        continue;
      }

      $count = $counts[(int) $option_id] ?? 0;
      $rows[] = [
        'id' => (int) $option_id,
        'title' => $option->getTitle(),
        'votes' => $count,
        'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
      ];
    }

    return [
      'question_id' => (int) $question->id(),
      'total_votes' => $total,
      'options' => $rows,
    ];
  }

}
