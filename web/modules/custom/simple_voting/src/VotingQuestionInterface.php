<?php

declare(strict_types=1);

namespace Drupal\simple_voting;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Defines the voting question entity contract.
 */
interface VotingQuestionInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Gets the question title.
   */
  public function getTitle(): string;

  /**
   * Sets the question title.
   */
  public function setTitle(string $title): static;

  /**
   * Determines whether results are visible after voting.
   */
  public function showsResults(): bool;

  /**
   * Sets whether results are visible after voting.
   */
  public function setShowResults(bool $show_results): static;

}
