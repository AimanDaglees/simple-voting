<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\simple_voting\Exception\DuplicateVoteException;
use Drupal\simple_voting\Exception\InvalidVoteException;
use Drupal\simple_voting\Exception\VotingDisabledException;
use Drupal\simple_voting\Service\VoteManager;
use Drupal\simple_voting\VotingOptionInterface;
use Drupal\simple_voting\VotingQuestionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * CMS voting form.
 */
final class VoteForm extends FormBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected VoteManager $voteManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get(VoteManager::class),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'simple_voting_vote_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\simple_voting\VotingQuestionInterface|null $voting_question
   *   The voting question being displayed.
   *
   * @return array<string, mixed>
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?VotingQuestionInterface $voting_question = NULL): array {
    if (!$voting_question || !$voting_question->isPublished()) {
      throw new AccessDeniedHttpException();
    }

    if (!$this->voteManager->isEnabled()) {
      return ['disabled' => ['#markup' => $this->t('Voting is currently disabled.')]];
    }

    $form_state->set('question_id', (int) $voting_question->id());

    if ($this->voteManager->hasVoted((int) $voting_question->id())) {
      $form['message'] = ['#markup' => '<p>' . $this->t('You have already voted on this question.') . '</p>'];
      if ($voting_question->showsResults() && $this->currentUser->hasPermission('view simple voting results')) {
        $form['results'] = [
          '#type' => 'link',
          '#title' => $this->t('View results'),
          '#url' => Url::fromRoute('simple_voting.results', [
            'voting_question' => $voting_question->id(),
          ]),
        ];
      }
      return $form;
    }

    $storage = $this->entityTypeManager->getStorage('voting_option');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('question', (int) $voting_question->id())
      ->condition('status', 1)
      ->sort('weight')
      ->sort('title')
      ->execute();

    $options = [];
    $option_entities = [];
    foreach ($storage->loadMultiple($ids) as $option) {
      if (!$option instanceof VotingOptionInterface) {
        continue;
      }
      $options[(int) $option->id()] = $option->getTitle();
      $option_entities[(int) $option->id()] = $option;
    }

    if (!$options) {
      return ['empty' => ['#markup' => $this->t('This question has no active answer options.')]];
    }

    $form['option_id'] = [
      '#type' => 'radios',
      '#title' => $voting_question->getTitle(),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['option_details'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['simple-voting-option-details']],
    ];

    foreach ($option_entities as $option_id => $option) {
      $form['option_details'][$option_id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['simple-voting-option-detail'],
          'data-option-id' => (string) $option_id,
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => Html::escape($option->getTitle()),
        ],
      ];

      if ($option->getDescription() !== '') {
        $form['option_details'][$option_id]['description'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => Html::escape($option->getDescription()),
        ];
      }

      $image_item = $option->get('image')->first();
      if ($image_item !== NULL) {
        $file_id = (int) $image_item->get('target_id')->getValue();
        $file = $this->entityTypeManager
          ->getStorage('file')
          ->load($file_id);

        if ($file instanceof FileInterface) {
          $form['option_details'][$option_id]['image'] = [
            '#theme' => 'image',
            '#uri' => $file->getFileUri(),
            '#alt' => (string) $image_item->get('alt')->getValue(),
          ];
        }
      }
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit vote'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<mixed> $form
   *   The submitted form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $question = $this->entityTypeManager
      ->getStorage('voting_question')
      ->load((int) $form_state->get('question_id'));

    if (!$question instanceof VotingQuestionInterface) {
      throw new AccessDeniedHttpException();
    }

    try {
      $this->voteManager->castVote(
        $question,
        (int) $form_state->getValue('option_id'),
        'cms'
      );
      $this->messenger()->addStatus($this->t('Your vote has been recorded.'));
    }
    catch (DuplicateVoteException | InvalidVoteException | VotingDisabledException $exception) {
      $this->messenger()->addError($this->t('@message', ['@message' => $exception->getMessage()]));
    }

    if ($question->showsResults() && $this->currentUser->hasPermission('view simple voting results')) {
      $form_state->setRedirect('simple_voting.results', [
        'voting_question' => $question->id(),
      ]);
    }
    else {
      $form_state->setRedirect('simple_voting.vote', [
        'voting_question' => $question->id(),
      ]);
    }
  }

}
