<?php

namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class configsettingForm extends ConfigFormBase {

  public function getFormId() {
    return 'mongodbapi_configsetting';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mongodb_api.settings');
	
	$form['endpointurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mongo DB - API Endpoint url'),
      '#default_value' => $config->get('endpointurl'),	 
	  '#description' => "This url is to access Mongo DB API functions.<BR>It should have port number if it is necessary to access the API functions."
    ];
	
	$form['json_setting'] = [
      '#type' => 'select',
      '#title' => $this->t('JSON Hide/Show'),
	  '#options' => ['Yes'=>"Yes", "No" => "No"],
      '#default_value' => $config->get('json_setting'),	 
	  '#description' => "Hide/Show the mongogdb json response in frontend."
    ];
	
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
	$config = $this->config('mongodb_api.settings');
	$config->set('endpointurl', $form_state->getValue('endpointurl'));
	$config->set('json_setting', $form_state->getValue('json_setting'));
	$config->save();
    parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    return ['mongodb_api.settings'];
  }

}
