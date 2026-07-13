<?php

declare(strict_types=1);

namespace Drupal\simple_voting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lists voting questions.
 */
final class VotingQuestionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The table header.
   */
  public function buildHeader(): array {
    return [
      'id' => $this->t('ID'),
      'title' => $this->t('Question'),
      'status' => $this->t('Active'),
      'show_results' => $this->t('Show results'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The table row.
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof VotingQuestionInterface);

    return [
      'id' => $entity->id(),
      'title' => $entity->toLink($entity->label(), 'edit-form'),
      'status' => $entity->isPublished() ? $this->t('Yes') : $this->t('No'),
      'show_results' => $entity->showsResults() ? $this->t('Yes') : $this->t('No'),
    ] + parent::buildRow($entity);
  }

}
