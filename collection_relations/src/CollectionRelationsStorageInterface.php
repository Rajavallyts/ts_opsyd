<?php

namespace Drupal\collection_relations;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface CollectionRelationsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Collection relations revision IDs for a specific Collection relations.
   *
   * @param \Drupal\collection_relations\Entity\CollectionRelationsInterface $entity
   *   The Collection relations entity.
   *
   * @return int[]
   *   Collection relations revision IDs (in ascending order).
   */
  public function revisionIds(CollectionRelationsInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Collection relations author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Collection relations revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\collection_relations\Entity\CollectionRelationsInterface $entity
   *   The Collection relations entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(CollectionRelationsInterface $entity);

  /**
   * Unsets the language for all Collection relations with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
