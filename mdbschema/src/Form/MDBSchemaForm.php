<?php

namespace Drupal\mdbschema\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Mdbschema edit forms.
 *
 * @ingroup mdbschema
 */
class MDBSchemaForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\mdbschema\Entity\MDBSchema */
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
        drupal_set_message($this->t('Created the %label Mdbschema.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Mdbschema.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.mdb_schema.canonical', ['mdb_schema' => $entity->id()]);
  }

}
