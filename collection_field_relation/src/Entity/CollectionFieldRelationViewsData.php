<?php

namespace Drupal\collection_field_relation\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Collection field relation entities.
 */
class CollectionFieldRelationViewsData extends EntityViewsData {

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
