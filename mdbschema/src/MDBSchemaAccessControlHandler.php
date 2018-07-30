<?php

namespace Drupal\mdbschema;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Mdbschema entity.
 *
 * @see \Drupal\mdbschema\Entity\MDBSchema.
 */
class MDBSchemaAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\mdbschema\Entity\MDBSchemaInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished mdbschema entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published mdbschema entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit mdbschema entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete mdbschema entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add mdbschema entities');
  }

}
