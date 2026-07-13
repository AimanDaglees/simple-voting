<?php

declare(strict_types=1);

namespace Drupal\simple_voting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lists voting answer options.
 */
final class VotingOptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The table header.
   */
  public function buildHeader(): array {
    return [
      'id' => $this->t('ID'),
      'title' => $this->t('Option'),
      'question' => $this->t('Question'),
      'weight' => $this->t('Weight'),
      'status' => $this->t('Active'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The table row.
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof VotingOptionInterface);
    $question = $entity->get('question')->entity;

    return [
      'id' => $entity->id(),
      'title' => $entity->toLink($entity->label(), 'edit-form'),
      'question' => $question ? $question->label() : $this->t('Missing question'),
      'weight' => $entity->getWeight(),
      'status' => $entity->isPublished() ? $this->t('Yes') : $this->t('No'),
    ] + parent::buildRow($entity);
  }

}
