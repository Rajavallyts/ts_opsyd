<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\collection_relations\Entity\CollectionRelations;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AlertCommand;

class collectionfieldrelationdeleteForm extends FormBase {

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'collection_field_relation_delete_form';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		global $base_url;
		
		$form['confirm text'] = [
			'#markup' => t('Are you sure you want to delete the collection field relation?')."<br/><br/>"
		];
		
		if(isset($_GET["field_rel"])){
			$field_rel = $_GET["field_rel"];
			$form['field_rel'] = [
				'#type' => 'hidden',
				'#value' => $field_rel
			];
		}
		
		$form['confirm_submit'] = [
			'#type' => 'submit',
			'#name' => 'confirm_submit',
			'#value' => t("Confirm")
		];
		
		$form['cancel_submit'] = [
			'#type' => 'submit',
			'#name' => 'cancel_submit',
			'#value' => t("Cancel")
		];
		
		return $form;
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		global $base_url;
		
		$field_rel = $form_state->getValue("field_rel");
	
		$triggering_element = $form_state->getTriggeringElement()["#name"];	
		if($triggering_element == "confirm_submit"){
			if(!empty($field_rel))
				$redirect_url = $base_url.'/mongodb_api/collectionfieldrelation/delete?field_rel='.$field_rel;
			$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			$response->send();
			return;
		}else{
			$redirect_url = $base_url.'/mongodb_api/collectionrelation';
			$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			$response->send();
			return;
		}
	}
}