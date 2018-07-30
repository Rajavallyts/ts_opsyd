<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

class addJSONForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_JSON';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {	  
    global $base_url;
	checkConnectionStatus();
	if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {
		$form['#prefix'] = "<div id='addjson_wrapper'>";
		$form['#suffix'] = "</div>";
	//	$form_state->setRebuild();
		$form['json_text'] = [
			'#type' => 'textarea',
			'#rows' => 20,
			'#cols' => 20,
			'#default_value' => $_SESSION['json_text'],
		];
		
		$form['submit'] = [
		  '#type' => 'submit',
		  '#value' => t('Save'),
		];
	}else {
		$form['description'] = [
			'#type' => 'markup',
			'#markup' => "MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>",
		];
	}
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	  global $base_url;
	  $ob = json_decode($form_state->getValue("json_text"));
	  if($ob === null) {
		  drupal_set_message("Invalid JSON", "error");
		  $_SESSION['json_text'] = $form_state->getvalue("json_text");			  
	  } else {
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $_GET['mongodb_collection'] ."/insert";
		$_SESSION['json_text'] = "";
		$api_param = array ( 		    
			"token" => $_SESSION['mongodb_token'], 
			"document" => $form_state->getValue("json_text")
		);
								 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);		
		curl_close ($ch);
		//$form_state->setRebuild();
		$showHideJson = \Drupal::config('mongodb_api.settings')->get('json_setting');
		if($showHideJson == "Yes")
			drupal_set_message($server_output);
		$json_result = json_decode($server_output, true);
		if (isset($json_result['success'])) {
			if ($json_result['success'] == 1) {
			  drupal_set_message ("Added document successfully");		
			}
		}	
	}
	$redirect_url = $base_url . '/mongodb_api/listdocument?mongodb_collection=' . $_GET['mongodb_collection'];
	$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
	$response->send();
	return;
  }	
}