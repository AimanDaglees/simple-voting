<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\simple_voting\Exception\DuplicateVoteException;
use Drupal\simple_voting\Exception\InvalidVoteException;
use Drupal\simple_voting\Exception\VotingDisabledException;
use Drupal\simple_voting\VotingOptionInterface;
use Drupal\simple_voting\VotingQuestionInterface;
use Psr\Log\LoggerInterface;

/**
 * Central vote validation and persistence service.
 */
final class VoteManager {

  public function __construct(
    private readonly Connection $database,
    private readonly AccountProxyInterface $currentUser,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * Returns whether the global voting flow is enabled.
   */
  public function isEnabled(): bool {
    return (bool) $this->configFactory
      ->get('simple_voting.settings')
      ->get('voting_enabled');
  }

  /**
   * Records an atomic vote for the current authenticated user.
   *
   * The database unique key on question_id + uid is the final
   * concurrency guard.
   */
  public function castVote(VotingQuestionInterface $question, int $option_id, string $source = 'cms'): int {
    if (!$this->isEnabled()) {
      throw new VotingDisabledException('Voting is globally disabled.');
    }

    if ($this->currentUser->isAnonymous()) {
      throw new InvalidVoteException('Authentication is required.');
    }

    if (!$question->isPublished()) {
      throw new InvalidVoteException('The question is inactive.');
    }

    $option = $this->entityTypeManager
      ->getStorage('voting_option')
      ->load($option_id);

    if (!$option instanceof VotingOptionInterface || !$option->isPublished()) {
      throw new InvalidVoteException('The selected answer option is invalid or inactive.');
    }

    if ($option->getQuestionId() !== (int) $question->id()) {
      throw new InvalidVoteException('The selected option does not belong to this question.');
    }

    $uid = (int) $this->currentUser->id();
    $transaction = $this->database->startTransaction();

    try {
      $vote_id = $this->database
        ->insert('simple_voting_vote')
        ->fields([
          'question_id' => (int) $question->id(),
          'option_id' => $option_id,
          'uid' => $uid,
          'created' => $this->time->getRequestTime(),
          'source' => substr($source, 0, 16),
        ])
        ->execute();

      $transaction->commitOrRelease();
    }
    catch (IntegrityConstraintViolationException $exception) {
      $transaction->rollBack();
      $this->logger->notice(
        'Duplicate vote rejected for question {question_id} and user {uid}.',
        ['question_id' => $question->id(), 'uid' => $uid]
      );
      throw new DuplicateVoteException('The user has already voted on this question.', 0, $exception);
    }
    catch (\Throwable $exception) {
      $transaction->rollBack();
      $this->logger->error(
        'Vote persistence failed for question {question_id}, option {option_id}, and user {uid}: {message}',
        [
          'question_id' => $question->id(),
          'option_id' => $option_id,
          'uid' => $uid,
          'message' => $exception->getMessage(),
        ]
      );
      throw $exception;
    }

    $this->cacheTagsInvalidator->invalidateTags([
      'simple_voting:results:' . $question->id(),
      'voting_question:' . $question->id(),
    ]);

    $this->logger->info(
      'Vote {vote_id} recorded for question {question_id}, option {option_id}, user {uid}, source {source}.',
      [
        'vote_id' => $vote_id,
        'question_id' => $question->id(),
        'option_id' => $option_id,
        'uid' => $uid,
        'source' => $source,
      ]
    );

    return (int) $vote_id;
  }

  /**
   * Checks whether a user has voted on a question.
   */
  public function hasVoted(int $question_id, ?int $uid = NULL): bool {
    $uid ??= (int) $this->currentUser->id();
    if ($uid <= 0) {
      return FALSE;
    }

    return (bool) $this->database
      ->select('simple_voting_vote', 'v')
      ->condition('question_id', $question_id)
      ->condition('uid', $uid)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Gets the selected option ID for a user, when available.
   */
  public function getSelectedOptionId(int $question_id, ?int $uid = NULL): ?int {
    $uid ??= (int) $this->currentUser->id();
    if ($uid <= 0) {
      return NULL;
    }

    $value = $this->database
      ->select('simple_voting_vote', 'v')
      ->fields('v', ['option_id'])
      ->condition('question_id', $question_id)
      ->condition('uid', $uid)
      ->execute()
      ->fetchField();

    return $value === FALSE ? NULL : (int) $value;
  }

}
