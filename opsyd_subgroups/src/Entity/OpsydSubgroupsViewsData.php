<?php

namespace Drupal\opsyd_subgroups\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Opsyd subgroups entities.
 */
class OpsydSubgroupsViewsData extends EntityViewsData {

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
