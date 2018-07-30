<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class closeconnectionForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mongodb_closeConnection';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  checkConnectionStatus();
	  global $base_url;
	  if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {
		$form['description'] = [
		  '#type' => 'markup',
		  '#markup' => 'Do you want to close the Mongo DB Connection?<BR><BR>'
		];
		
		$form['actions']['submit'] = [
		  '#type' => 'submit',
		  '#name' => 'submit_btn',
		  '#value' => t('Close'),
		];
		$form['actions']['cancel'] = [
		  '#type' => 'submit',
		  '#name' => 'cancel_btn',
		  '#value' => t('Cancel'),
		];
	  } else  {
		$form['notice'] = [
			'#markup' => "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>"
		];
	  }
	
    return $form;
  }
  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	global $base_url;
	if ($form_state->getTriggeringElement()['#name'] == 'submit_btn') {
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/close";
		$api_param = array ("token" => $_SESSION['mongodb_token']);
									 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $server_output = curl_exec ($ch);		
		curl_close ($ch);
		$json_result = json_decode($server_output, true);		
		if ($json_result['success'] == 1) {
			$_SESSION['mongodb_token'] = "";
			$_SESSION['mongodb_nid'] = "";			
			$_SESSION["data_mongodb_collection"] = "";
			$_SESSION["data_webform_id"] = "";
			$_SESSION["data_document_id"] = "";
			$_SESSION["doc_mongodb_collection"] = "";
			$_SESSION["doc_document_id"] = "";
			if(isset($_SESSION['group_id']))
				$_SESSION['group_id'] = "";
			if(isset($_SESSION['schema_check']))
				$_SESSION["schema_check"] = "";
			drupal_set_message ('Connection closed');			
		}
		else
			drupal_set_message ($json_result['result']);
		
		$redirect_url = $base_url . '/mongodb-list';
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
	} else {
		$redirect_url = $base_url . '/mongodb_api/listcollection';
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
    }	
  }

}