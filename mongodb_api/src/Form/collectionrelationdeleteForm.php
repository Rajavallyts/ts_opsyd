<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\collection_relations\Entity\CollectionRelations;

use Drupal\Core\Ajax\AjaxResponse;
/* use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand; */
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AlertCommand;

class collectionrelationdeleteForm extends FormBase {

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'collection_relation_deleteform';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		global $base_url;
		
		$form['confirm text'] = [
			'#markup' => t('Are you sure you want to delete the collection relations ?')."<br/><br/>"
		];
		
		$coll_ref = $_GET["coll_ref"];
		$form['coll_ref'] = [
			'#type' => 'hidden',
			'#value' => $coll_ref
		];
		
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
		
		$coll_ref = $form_state->getValue("coll_ref");
	
		$triggering_element = $form_state->getTriggeringElement()["#name"];	
		if($triggering_element == "confirm_submit"){
			$redirect_url = $base_url.'/mongodb_api/collectionrelation/delete?coll_ref='.$coll_ref;
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