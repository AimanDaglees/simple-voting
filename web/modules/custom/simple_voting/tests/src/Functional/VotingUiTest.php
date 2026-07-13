<?php

declare(strict_types=1);

namespace Drupal\Tests\simple_voting\Functional;

use Drupal\simple_voting\Entity\VotingOption;
use Drupal\simple_voting\Entity\VotingQuestion;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the authenticated CMS voting flow.
 *
 * @group simple_voting
 */
final class VotingUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['simple_voting'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests a user can vote once and then see results.
   */
  public function testVotingFlow(): void {
    $user = $this->drupalCreateUser([
      'access simple voting',
      'cast simple voting votes',
      'view simple voting results',
    ]);
    $this->drupalLogin($user);

    $question = VotingQuestion::create([
      'title' => 'Best option?',
      'status' => 1,
      'show_results' => 1,
    ]);
    $question->save();

    $option = VotingOption::create([
      'question' => $question->id(),
      'title' => 'First option',
      'status' => 1,
    ]);
    $option->save();

    $this->drupalGet('/voting/' . $question->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm(['option_id' => $option->id()], 'Submit vote');

    $this->assertSession()->pageTextContains('Your vote has been recorded.');
    $this->assertSession()->pageTextContains('Total votes: 1');
  }

}
