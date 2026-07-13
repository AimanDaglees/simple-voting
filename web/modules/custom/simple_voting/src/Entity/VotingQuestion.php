<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_voting\Access\VotingQuestionAccessControlHandler;
use Drupal\simple_voting\Form\VotingQuestionForm;
use Drupal\simple_voting\VotingQuestionInterface;
use Drupal\simple_voting\VotingQuestionListBuilder;

/**
 * Defines a voting question.
 */
#[ContentEntityType(
  id: 'voting_question',
  label: new TranslatableMarkup('Voting question'),
  label_collection: new TranslatableMarkup('Voting questions'),
  label_singular: new TranslatableMarkup('voting question'),
  label_plural: new TranslatableMarkup('voting questions'),
  handlers: [
    'list_builder' => VotingQuestionListBuilder::class,
    'access' => VotingQuestionAccessControlHandler::class,
    'form' => [
      'add' => VotingQuestionForm::class,
      'edit' => VotingQuestionForm::class,
      'delete' => ContentEntityDeleteForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  base_table: 'voting_question',
  admin_permission: 'administer simple voting',
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'title',
    'published' => 'status',
  ],
  links: [
    'add-form' => '/admin/structure/simple-voting/questions/add',
    'edit-form' => '/admin/structure/simple-voting/questions/{voting_question}/edit',
    'delete-form' => '/admin/structure/simple-voting/questions/{voting_question}/delete',
    'collection' => '/admin/structure/simple-voting/questions',
  ],
)]
final class VotingQuestion extends ContentEntityBase implements VotingQuestionInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return (string) $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title): static {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function showsResults(): bool {
    return (bool) $this->get('show_results')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setShowResults(bool $show_results): static {
    $this->set('show_results', $show_results);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Question'))
      ->setDescription(new TranslatableMarkup('The question presented to voters.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_results'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Show results after voting'))
      ->setDescription(new TranslatableMarkup('When disabled, counts and percentages remain hidden from regular voters.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields += static::publishedBaseFieldDefinitions($entity_type);

    /** @var \Drupal\Core\Field\BaseFieldDefinition $status */
    $status = $fields['status'];
    $status
      ->setLabel(new TranslatableMarkup('Active'))
      ->setDescription(new TranslatableMarkup('Inactive questions cannot receive votes.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'));

    return $fields;
  }

}
