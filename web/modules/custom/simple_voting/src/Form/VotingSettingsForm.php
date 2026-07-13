<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Global Simple Voting settings.
 */
final class VotingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'simple_voting_settings_form';
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   The editable configuration names.
   */
  protected function getEditableConfigNames(): array {
    return ['simple_voting.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array<string, mixed>
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('simple_voting.settings');

    $form['voting_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable voting'),
      '#description' => $this->t('When disabled, the CMS voting flow and every external API endpoint are blocked.'),
      '#default_value' => $config->get('voting_enabled'),
    ];

    $form['api_page_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Default API page limit'),
      '#default_value' => $config->get('api_page_limit') ?? 25,
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
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
    $this->config('simple_voting.settings')
      ->set('voting_enabled', (bool) $form_state->getValue('voting_enabled'))
      ->set('api_page_limit', (int) $form_state->getValue('api_page_limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
