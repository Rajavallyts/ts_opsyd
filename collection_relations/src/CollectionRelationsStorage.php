<?php

namespace Drupal\collection_relations;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\collection_relations\Entity\CollectionRelationsInterface;

/**
 * Defines the storage handler class for Collection relations entities.
 *
 * This extends the base storage class, adding required special handling for
 * Collection relations entities.
 *
 * @ingroup collection_relations
 */
class CollectionRelationsStorage extends SqlContentEntityStorage implements CollectionRelationsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(CollectionRelationsInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {collection_relations_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {collection_relations_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(CollectionRelationsInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {collection_relations_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('collection_relations_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
