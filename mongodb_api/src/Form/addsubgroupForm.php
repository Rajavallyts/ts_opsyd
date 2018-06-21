<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\opsyd_subgroups\Entity\OpsydSubgroups;

class addsubgroupForm extends FormBase {

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'add_subgroup';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		
		\Drupal::service('page_cache_kill_switch')->trigger();
		global $base_url;
		
		$form["group_name"] = [
			'#type' => 'textfield',
			'#title' => t("Subgroup Name"),
			'#required' => TRUE,
		];
		
		$form['submit'] = [
			'#type' => 'submit',
			'#name' => 'add_subgroup',
			'#value' => t('Add Subgroup'),
			'#button_type' => 'primary',
		];
		
		return $form;
	}
	
	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		
		$form_values = $form_state->getValues();
		$group_id = $_SESSION['group_id'];
		
		$query = \Drupal::entityQuery('opsyd_subgroups')
			->condition('status', 1)
			->condition('field_sub_group_name', trim($form_values["group_name"]), '=')
			->condition('field_parent_group_id', $group_id, '=');
		$subgroups = $query->execute();
		
		if(!empty($subgroups)){
			if($form_values["group_name"] != array_keys($subgroups)[0])
				$form_state->setErrorByName('group_name', $this->t('This Subgroup name is already exist.'));
		}
	
	}
	
	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {	 
		global $base_url;
		
		$form_values = $form_state->getValues();
		$group_id = $_SESSION['group_id'];
		
		$subgroups = OpsydSubgroups::create([
		   'field_sub_group_name' => trim($form_values["group_name"]),
		   'field_parent_group_id' => $group_id
		]);  
		$subgroups->save();
		
		drupal_set_message("Subgroup created successfully.\n");
		$redirect_url = $base_url.'/mongodb_api/assigndataform';
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
	}
}