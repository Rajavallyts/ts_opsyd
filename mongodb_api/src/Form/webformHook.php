<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\collection_relations\Entity\CollectionRelations;
use Drupal\Core\Database\Database;

class webformHook extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_dataform';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  global $base_url;	 


	if (isset($_GET['mongodb_collection']) && isset($_GET['webform_id']) && isset($_GET['document_id'])) {
		$mongodb_collection = $_SESSION["data_mongodb_collection"] = $_GET['mongodb_collection'];
		$webform_id = $_SESSION["data_webform_id"] = $_GET['webform_id'];
		$document_id = $_SESSION["data_document_id"] = $_GET['document_id'];
	}else if (isset($_GET['mongodb_collection']) && isset($_GET['webform_id']) && !isset($_GET['document_id'])) {
		$mongodb_collection = $_GET['mongodb_collection'];
		$webform_id = $_GET['webform_id'];
		$_SESSION["data_document_id"] = '';
	}else{
		if(isset($_SESSION["data_mongodb_collection"]) && isset($_SESSION["data_webform_id"]) && isset($_SESSION['data_document_id'])){
			$mongodb_collection = $_SESSION["data_mongodb_collection"];
			$webform_id = $_SESSION["data_webform_id"];
			$document_id = $_SESSION["data_document_id"];
		}
	}
		
	if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != ""){
	  if (!empty($mongodb_collection) && !empty($webform_id)) {
		  $webform = \Drupal\webform\Entity\Webform::load($webform_id);
		  $webform_elements = $webform->getElementsDecoded();
		  'propertyDefinitions' => 'protected',
)


		  $webform_elements_keys = array_keys($webform_elements); 

		if(!empty($document_id)){

$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $mongodb_collection ."/findByID";		  
			$api_param = array ( "token" => $_SESSION['mongodb_token'], "id" => $document_id);
									 
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);		
			curl_close ($ch);	
			
			$roles = \Drupal::currentUser()->getRoles();
			if(in_array("datauser",$roles))
				$breadcrumb = '/dataformsdocument?webform_id='.$webform_id;
			else
				$breadcrumb = '/mongodb_api/listdataformdocument?mongodb_collection='.$mongodb_collection.'&webform_id='.$webform_id;
			
			$form['api_result'] = array (
				'#type' => 'markup',
				'#markup' => "<b><a href='".$base_url.$breadcrumb."' target='_self'>".$mongodb_collection."</a> > ". $document_id. "</b>",
			);	 
	 
			$json_result = json_decode($server_output, true);	
		}

		$form['#tree'] = TRUE;

		//if (count ($json_result) > 0 ) {	
		$i=0;			
		
		$form['document'] = [
			'#type' => 'fieldset',
			'#title' => $this->t(' Data Form  '),
			'#prefix' => "<div>",
			'#suffix' => '</div>',
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
		    '#states' => [
				'visible' => [
					':input[name="options[date_format]"]' => array('value' => array('custom', 'raw time ago', 'time ago', 'raw time span', 'time span')),
				],
			],
		];
  }