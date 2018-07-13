<?php
namespace Drupal\mongodb_api\importdb;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class importdbForm extends FormBase {
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
	  if ($_SESSION['mongodb_token'] != "") {	
			
		//$json_result = json_decode($server_output, true);				
		
		$form['csv_file'] = [
		'#type' => 'managed_file',
		'#title' => $this->t('Upload .csv file to import'),
		'#upload_location' => 'public://csvimport',
		'#upload_validators' => [
			'file_validate_extensions' => ['csv'],
		],
		'#description' => 'Note: First line in the csv will be considered as key for mongodb import.<BR>Data should start from second line.',
		'#required' => TRUE,
		//'#default_value' => $this->get('userimport_csvfile'),
	];
		
		$form['collection_name'] = array(
			'#type' => 'textfield',
			'#title' => t('Collection Name'),
			'#required' => TRUE,			
		);

		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Import'),    
			'#button_type' => 'primary',
		);
	
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
		$fid = $form_state->getValue(['csv_file', 0]);
		$file = "";
		$collection_name = $form_state->getValue("collection_name");
		global $base_url;
		
		if (!empty($fid)) {
			$file = File::load($fid);
			$file->setPermanent();
			$file->save();
	
			$uri = $file->getFileUri();
			$file_url = drupal_realpath($uri);
	
			//$curl_file = new CurlFile(realpath($file_url));
	
			if (function_exists('curl_file_create')) { // php 5.5+
			  $curl_file = curl_file_create($file_url);
			}
		
			$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl') . "/import";
			$api_param = [
				'token' => $_SESSION['mongodb_token'],
				'collection' => $collection_name,
				'importfile' => $curl_file,
				'type' => 'csv',
				'headerline' => true
			];
			$headers = array("Content-Type:multipart/form-data");				
			$ch = curl_init();
				
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $api_param);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);	
			curl_close ($ch);
			
			$showHideJson = \Drupal::config('mongodb_api.settings')->get('json_setting');
			if($showHideJson == "Yes")
			drupal_set_message($server_output);
		}
	}
}