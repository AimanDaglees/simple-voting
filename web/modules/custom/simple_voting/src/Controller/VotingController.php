<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\simple_voting\Service\ResultManager;
use Drupal\simple_voting\Service\VoteManager;
use Drupal\simple_voting\VotingQuestionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * CMS-facing question and result pages.
 */
final class VotingController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ResultManager $resultManager,
    private readonly VoteManager $voteManager,
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get(ResultManager::class),
      $container->get(VoteManager::class),
      $container->get('current_user'),
    );
  }

  /**
   * Lists active questions.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function questionList(): array {
    if (!$this->configFactory->get('simple_voting.settings')->get('voting_enabled')) {
      return ['#markup' => $this->t('Voting is currently disabled.')];
    }

    $storage = $this->entityTypeManager->getStorage('voting_question');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->execute();

    $items = [];
    foreach ($storage->loadMultiple($ids) as $question) {
      $items[] = Link::fromTextAndUrl(
        $question->label(),
        Url::fromRoute('simple_voting.vote', ['voting_question' => $question->id()])
      )->toRenderable();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $this->t('There are no active voting questions.'),
      '#cache' => [
        'tags' => ['voting_question_list'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * Returns a dynamic question-page title.
   */
  public function questionTitle(VotingQuestionInterface $voting_question): string {
    return $voting_question->getTitle();
  }

  /**
   * Returns a dynamic results-page title.
   */
  public function resultsTitle(VotingQuestionInterface $voting_question): string {
    return (string) $this->t('Results: @question', ['@question' => $voting_question->getTitle()]);
  }

  /**
   * Displays results when permitted.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function results(VotingQuestionInterface $voting_question): array {
    if (!$this->voteManager->isEnabled() || !$voting_question->isPublished()) {
      throw new AccessDeniedHttpException();
    }

    $is_admin = $this->currentUser->hasPermission('administer simple voting');
    if (!$voting_question->showsResults() && !$is_admin) {
      throw new AccessDeniedHttpException('Results are hidden for this question.');
    }

    if (!$is_admin && !$this->voteManager->hasVoted((int) $voting_question->id())) {
      throw new AccessDeniedHttpException('Results are available only after voting.');
    }

    $results = $this->resultManager->getResults($voting_question);
    $rows = [];
    foreach ($results['options'] as $option) {
      $rows[] = [
        $option['title'],
        $option['votes'],
        $option['percentage'] . '%',
      ];
    }

    return [
      'summary' => [
        '#markup' => '<p>' . $this->t('Total votes: @total', ['@total' => $results['total_votes']]) . '</p>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Option'),
          $this->t('Votes'),
          $this->t('Percentage'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No votes have been submitted.'),
      ],
      '#cache' => [
        'tags' => ['simple_voting:results:' . $voting_question->id()],
        'contexts' => ['user', 'user.permissions'],
      ],
    ];
  }

}
