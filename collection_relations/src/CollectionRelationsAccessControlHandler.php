<?php

namespace Drupal\collection_relations;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Collection relations entity.
 *
 * @see \Drupal\collection_relations\Entity\CollectionRelations.
 */
class CollectionRelationsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\collection_relations\Entity\CollectionRelationsInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished collection relations entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published collection relations entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit collection relations entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete collection relations entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add collection relations entities');
  }

}
