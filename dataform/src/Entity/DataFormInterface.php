<?php

namespace Drupal\dataform\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Data Form Setting entities.
 *
 * @ingroup dataform
 */
interface DataFormInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Data Form Setting name.
   *
   * @return string
   *   Name of the Data Form Setting.
   */
  public function getName();

  /**
   * Sets the Data Form Setting name.
   *
   * @param string $name
   *   The Data Form Setting name.
   *
   * @return \Drupal\dataform\Entity\DataFormInterface
   *   The called Data Form Setting entity.
   */
  public function setName($name);

  /**
   * Gets the Data Form Setting creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Data Form Setting.
   */
  public function getCreatedTime();

  /**
   * Sets the Data Form Setting creation timestamp.
   *
   * @param int $timestamp
   *   The Data Form Setting creation timestamp.
   *
   * @return \Drupal\dataform\Entity\DataFormInterface
   *   The called Data Form Setting entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Data Form Setting published status indicator.
   *
   * Unpublished Data Form Setting are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Data Form Setting is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Data Form Setting.
   *
   * @param bool $published
   *   TRUE to set this Data Form Setting to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\dataform\Entity\DataFormInterface
   *   The called Data Form Setting entity.
   */
  public function setPublished($published);

}
