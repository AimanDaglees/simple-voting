<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\simple_voting\VotingOptionInterface;
use Drupal\simple_voting\VotingQuestionInterface;

/**
 * Converts questions and answer options into API-safe arrays.
 */
final class QuestionSerializer {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Serializes one question.
   *
   * @return array<string, mixed>
   *   The serialized question.
   */
  public function serialize(VotingQuestionInterface $question, bool $include_options = TRUE): array {
    $data = [
      'id' => (int) $question->id(),
      'uuid' => $question->uuid(),
      'title' => $question->getTitle(),
      'show_results' => $question->showsResults(),
      'active' => $question->isPublished(),
    ];

    if ($include_options) {
      $data['options'] = $this->serializeOptions((int) $question->id());
    }

    return $data;
  }

  /**
   * Serializes active options belonging to a question.
   *
   * @return list<array<string, mixed>>
   *   The serialized options.
   */
  private function serializeOptions(int $question_id): array {
    $storage = $this->entityTypeManager->getStorage('voting_option');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('question', $question_id)
      ->condition('status', 1)
      ->sort('weight')
      ->sort('title')
      ->execute();

    $rows = [];
    foreach ($storage->loadMultiple($ids) as $option) {
      if (!$option instanceof VotingOptionInterface) {
        continue;
      }

      $row = [
        'id' => (int) $option->id(),
        'uuid' => $option->uuid(),
        'title' => $option->getTitle(),
        'description' => $option->getDescription(),
        'weight' => $option->getWeight(),
        'image' => NULL,
      ];

      $image_item = $option->get('image')->first();
      if ($image_item !== NULL) {
        $file_id = (int) $image_item->get('target_id')->getValue();
        $file = $this->entityTypeManager
          ->getStorage('file')
          ->load($file_id);

        if ($file instanceof FileInterface) {
          $row['image'] = [
            'url' => $this->fileUrlGenerator
              ->generateAbsoluteString($file->getFileUri()),
            'alt' => (string) $image_item->get('alt')->getValue(),
          ];
        }
      }

      $rows[] = $row;
    }

    return $rows;
  }

}
