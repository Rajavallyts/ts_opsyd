<?php

namespace Drupal\collection_field_relation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Collection field relation edit forms.
 *
 * @ingroup collection_field_relation
 */
class CollectionFieldRelationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\collection_field_relation\Entity\CollectionFieldRelation */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Collection field relation.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Collection field relation.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.collection_field_relation.canonical', ['collection_field_relation' => $entity->id()]);
  }

}
