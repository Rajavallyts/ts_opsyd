<?php

namespace Drupal\mdbschema\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Mdbschema entities.
 *
 * @ingroup mdbschema
 */
interface MDBSchemaInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Mdbschema name.
   *
   * @return string
   *   Name of the Mdbschema.
   */
  public function getName();

  /**
   * Sets the Mdbschema name.
   *
   * @param string $name
   *   The Mdbschema name.
   *
   * @return \Drupal\mdbschema\Entity\MDBSchemaInterface
   *   The called Mdbschema entity.
   */
  public function setName($name);

  /**
   * Gets the Mdbschema creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Mdbschema.
   */
  public function getCreatedTime();

  /**
   * Sets the Mdbschema creation timestamp.
   *
   * @param int $timestamp
   *   The Mdbschema creation timestamp.
   *
   * @return \Drupal\mdbschema\Entity\MDBSchemaInterface
   *   The called Mdbschema entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Mdbschema published status indicator.
   *
   * Unpublished Mdbschema are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Mdbschema is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Mdbschema.
   *
   * @param bool $published
   *   TRUE to set this Mdbschema to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\mdbschema\Entity\MDBSchemaInterface
   *   The called Mdbschema entity.
   */
  public function setPublished($published);

}
