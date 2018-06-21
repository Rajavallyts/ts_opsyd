<?php

namespace Drupal\collection_relations\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Collection relations entities.
 */
class CollectionRelationsViewsData extends EntityViewsData {

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
