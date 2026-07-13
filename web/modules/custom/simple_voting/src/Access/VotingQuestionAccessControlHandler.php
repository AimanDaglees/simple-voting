<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_voting\VotingQuestionInterface;

/**
 * Access control for voting questions.
 */
final class VotingQuestionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer simple voting')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation === 'view' && $entity instanceof VotingQuestionInterface) {
      return AccessResult::allowedIf(
        $entity->isPublished() && (
          $account->hasPermission('access simple voting') ||
          $account->hasPermission('access simple voting api')
        )
      )
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account requesting access.
   * @param array<string, mixed> $context
   *   Additional access-check context.
   * @param string|null $entity_bundle
   *   The entity bundle, when applicable.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer simple voting');
  }

}
