<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add/edit form for voting questions.
 */
final class VotingQuestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);
    $this->messenger()->addStatus(
      $status === SAVED_NEW
        ? $this->t('The voting question has been created.')
        : $this->t('The voting question has been updated.')
    );
    $form_state->setRedirect('entity.voting_question.collection');
    return $status;
  }

}
