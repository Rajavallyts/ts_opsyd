<?php

namespace Drupal\opsyd_subgroups\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Opsyd subgroups entities.
 *
 * @ingroup opsyd_subgroups
 */
interface OpsydSubgroupsInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Opsyd subgroups name.
   *
   * @return string
   *   Name of the Opsyd subgroups.
   */
  public function getName();

  /**
   * Sets the Opsyd subgroups name.
   *
   * @param string $name
   *   The Opsyd subgroups name.
   *
   * @return \Drupal\opsyd_subgroups\Entity\OpsydSubgroupsInterface
   *   The called Opsyd subgroups entity.
   */
  public function setName($name);

  /**
   * Gets the Opsyd subgroups creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Opsyd subgroups.
   */
  public function getCreatedTime();

  /**
   * Sets the Opsyd subgroups creation timestamp.
   *
   * @param int $timestamp
   *   The Opsyd subgroups creation timestamp.
   *
   * @return \Drupal\opsyd_subgroups\Entity\OpsydSubgroupsInterface
   *   The called Opsyd subgroups entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Opsyd subgroups published status indicator.
   *
   * Unpublished Opsyd subgroups are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Opsyd subgroups is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Opsyd subgroups.
   *
   * @param bool $published
   *   TRUE to set this Opsyd subgroups to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\opsyd_subgroups\Entity\OpsydSubgroupsInterface
   *   The called Opsyd subgroups entity.
   */
  public function setPublished($published);

}
