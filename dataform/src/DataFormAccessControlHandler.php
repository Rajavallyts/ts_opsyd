<?php

namespace Drupal\dataform;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Data Form Setting entity.
 *
 * @see \Drupal\dataform\Entity\DataForm.
 */
class DataFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dataform\Entity\DataFormInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished data form setting entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published data form setting entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit data form setting entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete data form setting entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add data form setting entities');
  }

}
