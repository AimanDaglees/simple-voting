<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Administrative dashboard.
 */
final class AdminController extends ControllerBase {

  /**
   * Builds the module dashboard.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function dashboard(): array {
    $items = [
      Link::fromTextAndUrl($this->t('Manage questions'), Url::fromRoute('entity.voting_question.collection'))->toRenderable(),
      Link::fromTextAndUrl($this->t('Manage answer options'), Url::fromRoute('entity.voting_option.collection'))->toRenderable(),
      Link::fromTextAndUrl($this->t('Global settings'), Url::fromRoute('simple_voting.settings'))->toRenderable(),
      Link::fromTextAndUrl($this->t('Open voting interface'), Url::fromRoute('simple_voting.question_list'))->toRenderable(),
    ];

    return [
      'description' => [
        '#markup' => '<p>' . $this->t('Create questions first, then create answer options and associate each option with its question.') . '</p>',
      ],
      'links' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

}
