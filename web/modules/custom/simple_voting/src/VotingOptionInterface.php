<?php

declare(strict_types=1);

namespace Drupal\simple_voting;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Defines the voting answer-option entity contract.
 */
interface VotingOptionInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Gets the option title.
   */
  public function getTitle(): string;

  /**
   * Gets the parent question entity ID.
   */
  public function getQuestionId(): int;

  /**
   * Gets the option description.
   */
  public function getDescription(): string;

  /**
   * Gets the option display weight.
   */
  public function getWeight(): int;

}
