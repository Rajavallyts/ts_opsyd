<?php

namespace Drupal\collection_relations;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Collection relations entities.
 *
 * @ingroup collection_relations
 */
class CollectionRelationsListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Collection relations ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\collection_relations\Entity\CollectionRelations */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.collection_relations.edit_form',
      ['collection_relations' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
