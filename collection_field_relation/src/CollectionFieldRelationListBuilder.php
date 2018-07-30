<?php

namespace Drupal\collection_field_relation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Collection field relation entities.
 *
 * @ingroup collection_field_relation
 */
class CollectionFieldRelationListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Collection field relation ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\collection_field_relation\Entity\CollectionFieldRelation */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.collection_field_relation.edit_form',
      ['collection_field_relation' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
