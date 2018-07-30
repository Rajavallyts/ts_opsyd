<?php

namespace Drupal\mdbschema;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Mdbschema entities.
 *
 * @ingroup mdbschema
 */
class MDBSchemaListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Mdbschema ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\mdbschema\Entity\MDBSchema */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.mdb_schema.edit_form',
      ['mdb_schema' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
