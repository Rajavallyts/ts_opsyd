<?php

namespace Drupal\collection_relations\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Collection relations entities.
 *
 * @ingroup collection_relations
 */
interface CollectionRelationsInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Collection relations name.
   *
   * @return string
   *   Name of the Collection relations.
   */
  public function getName();

  /**
   * Sets the Collection relations name.
   *
   * @param string $name
   *   The Collection relations name.
   *
   * @return \Drupal\collection_relations\Entity\CollectionRelationsInterface
   *   The called Collection relations entity.
   */
  public function setName($name);

  /**
   * Gets the Collection relations creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Collection relations.
   */
  public function getCreatedTime();

  /**
   * Sets the Collection relations creation timestamp.
   *
   * @param int $timestamp
   *   The Collection relations creation timestamp.
   *
   * @return \Drupal\collection_relations\Entity\CollectionRelationsInterface
   *   The called Collection relations entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Collection relations published status indicator.
   *
   * Unpublished Collection relations are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Collection relations is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Collection relations.
   *
   * @param bool $published
   *   TRUE to set this Collection relations to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\collection_relations\Entity\CollectionRelationsInterface
   *   The called Collection relations entity.
   */
  public function setPublished($published);

  /**
   * Gets the Collection relations revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Collection relations revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\collection_relations\Entity\CollectionRelationsInterface
   *   The called Collection relations entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Collection relations revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Collection relations revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\collection_relations\Entity\CollectionRelationsInterface
   *   The called Collection relations entity.
   */
  public function setRevisionUserId($uid);

}
