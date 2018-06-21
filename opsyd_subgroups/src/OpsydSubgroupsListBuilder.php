<?php

namespace Drupal\opsyd_subgroups;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Opsyd subgroups entities.
 *
 * @ingroup opsyd_subgroups
 */
class OpsydSubgroupsListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Opsyd subgroups ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\opsyd_subgroups\Entity\OpsydSubgroups */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.opsyd_subgroups.edit_form',
      ['opsyd_subgroups' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
