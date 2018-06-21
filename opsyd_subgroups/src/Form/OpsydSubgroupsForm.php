<?php

namespace Drupal\opsyd_subgroups\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Opsyd subgroups edit forms.
 *
 * @ingroup opsyd_subgroups
 */
class OpsydSubgroupsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\opsyd_subgroups\Entity\OpsydSubgroups */
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
        drupal_set_message($this->t('Created the %label Opsyd subgroups.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Opsyd subgroups.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.opsyd_subgroups.canonical', ['opsyd_subgroups' => $entity->id()]);
  }

}
