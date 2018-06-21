<?php

namespace Drupal\dataform\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Data Form Setting entities.
 */
class DataFormViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
