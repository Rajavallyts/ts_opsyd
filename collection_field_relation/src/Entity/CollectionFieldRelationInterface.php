<?php

namespace Drupal\collection_field_relation\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Collection field relation entities.
 *
 * @ingroup collection_field_relation
 */
interface CollectionFieldRelationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Collection field relation name.
   *
   * @return string
   *   Name of the Collection field relation.
   */
  public function getName();

  /**
   * Sets the Collection field relation name.
   *
   * @param string $name
   *   The Collection field relation name.
   *
   * @return \Drupal\collection_field_relation\Entity\CollectionFieldRelationInterface
   *   The called Collection field relation entity.
   */
  public function setName($name);

  /**
   * Gets the Collection field relation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Collection field relation.
   */
  public function getCreatedTime();

  /**
   * Sets the Collection field relation creation timestamp.
   *
   * @param int $timestamp
   *   The Collection field relation creation timestamp.
   *
   * @return \Drupal\collection_field_relation\Entity\CollectionFieldRelationInterface
   *   The called Collection field relation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Collection field relation published status indicator.
   *
   * Unpublished Collection field relation are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Collection field relation is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Collection field relation.
   *
   * @param bool $published
   *   TRUE to set this Collection field relation to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\collection_field_relation\Entity\CollectionFieldRelationInterface
   *   The called Collection field relation entity.
   */
  public function setPublished($published);

}
