<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class exportdbForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mongodb_exportdb';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {	 
	checkConnectionStatus();
	global $base_url;
	  if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {	
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/export";	
		$api_param = array ( "token" => $_SESSION['mongodb_token']);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);		
		curl_close ($ch);
			
		$json_result = json_decode($server_output, true);				
	  
		$form['description'] = [
			'#type' => 'markup',
			'#markup' => '<a href="' . $json_result['downloadUrl'].'" title="Export DB">Export MongoDB</a>',
		];		
	  } else {
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
	if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") { 
		
	}
}
}