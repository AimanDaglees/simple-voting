<?php

declare(strict_types=1);

namespace Drupal\Tests\simple_voting\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simple_voting\Entity\VotingOption;
use Drupal\simple_voting\Entity\VotingQuestion;
use Drupal\simple_voting\Exception\DuplicateVoteException;
use Drupal\simple_voting\Exception\InvalidVoteException;
use Drupal\simple_voting\Service\VoteManager;
use Drupal\user\Entity\User;

/**
 * Tests atomic vote persistence and validation.
 *
 * @group simple_voting
 */
final class VoteManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'file',
    'image',
    'basic_auth',
    'simple_voting',
  ];

  /**
   * The vote manager under test.
   */
  private VoteManager $voteManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('voting_question');
    $this->installEntitySchema('voting_option');
    $this->installSchema('simple_voting', ['simple_voting_vote']);
    $this->installConfig(['simple_voting']);

    $user = User::create([
      'name' => 'voter',
      'status' => 1,
    ]);
    $user->save();
    $this->container->get('account_switcher')->switchTo($user);

    $this->voteManager = $this->container->get(VoteManager::class);
  }

  /**
   * Tests successful voting and duplicate protection.
   */
  public function testVoteAndDuplicateProtection(): void {
    $question = VotingQuestion::create([
      'title' => 'Choose one',
      'status' => 1,
      'show_results' => 1,
    ]);
    $question->save();

    $option = VotingOption::create([
      'question' => $question->id(),
      'title' => 'Option A',
      'status' => 1,
    ]);
    $option->save();

    $vote_id = $this->voteManager->castVote($question, (int) $option->id(), 'cms');
    $this->assertGreaterThan(0, $vote_id);
    $this->assertTrue($this->voteManager->hasVoted((int) $question->id()));
    $this->assertSame((int) $option->id(), $this->voteManager->getSelectedOptionId((int) $question->id()));

    $this->expectException(DuplicateVoteException::class);
    $this->voteManager->castVote($question, (int) $option->id(), 'api');
  }

  /**
   * Tests that an option from another question is rejected.
   */
  public function testCrossQuestionOptionIsRejected(): void {
    $question_a = VotingQuestion::create(['title' => 'Question A', 'status' => 1]);
    $question_a->save();

    $question_b = VotingQuestion::create(['title' => 'Question B', 'status' => 1]);
    $question_b->save();

    $option_b = VotingOption::create([
      'question' => $question_b->id(),
      'title' => 'Option B',
      'status' => 1,
    ]);
    $option_b->save();

    $this->expectException(InvalidVoteException::class);
    $this->voteManager->castVote($question_a, (int) $option_b->id());
  }

}
