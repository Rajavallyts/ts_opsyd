<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Ajax\CloseModalDialogCommand;

class managesubdocumentForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_subdocument';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	global $base_url;
	checkConnectionStatus();
	if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {
	  $server_output = "";
	  
	  $form['#tree'] = TRUE;
	  
	  if (isset($_GET['mongodb_collection'])) {
		  $document_id = $_GET['document_id'];
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $_GET['mongodb_collection'] ."/findByID";		  
		  $api_param = array ( "token" => $_SESSION['mongodb_token'], "id" => $document_id);
									 
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $server_output = curl_exec ($ch);		
		  curl_close ($ch);		 
	 }
	  
	 $form['api_result'] = array (
		'#type' => 'markup',
		'#markup' => "<b><a href='" . $base_url . "/mongodb_api/listdocument?mongodb_collection=" . $_GET['mongodb_collection'] . "' target='_self'>" . $_GET['mongodb_collection']. "</a> > <a href='" . $base_url . "/mongodb_api/managedocument?mongodb_collection=" . $_GET['mongodb_collection'] . "&document_id=" . $_GET['document_id'] . "' target='_self'>" . $_GET['document_id'] . "</a> > " . $_GET['editkey'] . "</b><br><br><a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='" . $base_url . "/mongodb_api/addsubdocument?mongodb_collection=".$_GET['mongodb_collection']."&document_id=" . $_GET['document_id'] . "&editkey=".$_GET['editkey']."'>Add Sub Document</a>",
	);
	$json_result = json_decode($server_output, true); 
	$queryKeys = explode(".", $_GET['editkey']);
	  
	foreach($queryKeys as $queryKey)
		$json_result = $json_result[$queryKey];
	
	$form['#prefix'] = '<div id="subdocument_wrapper">';
	$form['#suffix'] = '</div>';
		
	$form['subdocument'] = [
		'#type' => 'fieldset',
		'#title' => $this->t(' Existing [Key - Value]  '),
		'#prefix' => "<div>",
		'#suffix' => '</div>',
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
		'#tree' => TRUE,
	];
	$i=0;
		
	foreach($json_result as $resultkey => $resultValue):			
		
		if(!is_int($resultkey))
			$cond = "_id";
		else
			$cond = $resultkey+1;
				
		if ($resultkey != $cond) {
			$form['subdocument'][$i]['remove_key'] = array(
				'#type' => 'checkbox',				
				'#prefix' => '<div class="clearboth">',    
				'#theme_wrappers' => array(),
			);
			$form['subdocument'][$i]['key'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,
				'#default_value' => $resultkey,	 
				'#class' => 'value-field',
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;','disabled' => 'disabled'),								
				'#theme_wrappers' => array(),
				'#size' => 2000,
			);
			if (!is_array($resultValue)) {
				$form['subdocument'][$i]['valuee'] = array(
					'#type' => 'textfield',      
					'#required' => FALSE,
					'#default_value' => $resultValue,	  
					'#class' => 'value-field',	  
					'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
					'#suffix' => '</div><br>',
					'#theme_wrappers' => array(),
					'#size' => 2000,
				);	
			} else {
				if (count($resultValue) > 1)
					$fields_text = "fields";
				else
					$fields_text = "field";
					
				$form['subdocument'][$i]['valuee'] = array(
					'#type' => 'link',
					'#title' => "{" . count($resultValue) . " " . $fields_text. "}",
					'#url' => \Drupal\Core\Url::fromRoute('mongodb_api.subdocument', ['mongodb_collection' => $_GET['mongodb_collection'], 'document_id' => $_GET['document_id'], 'editkey' => $_GET['editkey'] ."." . $resultkey]),
					'#attributes' => [ 
						'class' => ['use-ajax'],
						'data-dialog-type' => 'modal',
						'data-dialog-options' => \Drupal\Component\Serialization\Json::encode([
							'width' => 900,
						]),
					],
					'#prefix' => "<div class='mongodb_subform_list'>",
					'#suffix' => '</div></div><br>',
				);
			}
			$i++;
		}
	endforeach;	
		
	if (count ($json_result) > 0 ) {
		$form['subdocument']['actions']['delete_kv'] = [
			'#type' => 'submit',
			'#value' => 'Delete selected',		 
			'#prefix' => '<div class="clearboth">',
			'#suffix' => '</div>',
										 
			'#name' => 'delete_kv',
		];
	}

	$form['subdocument_new'] = [
       '#type' => 'fieldset',
       '#title' => $this->t(' New [Key - Value]  '),
       '#prefix' => "<div id='subnames-fieldset-wrapper-sub'>",
       '#suffix' => '</div>',
	   '#collapsible' => TRUE,
       '#collapsed' => FALSE,
	   '#tree' => TRUE,
     ];					 
		
	// Gather the number of names in the form already.
	$num_name_field = $form_state->get('num_subdocument');
	// We have to ensure that there is at least one name field.
	if ($num_name_field === NULL) {
	  $name_field = $form_state->set('num_subdocument', 1);
	  $num_name_field = 1;
	}
		
	for($newi = 0; $newi < $num_name_field; $newi++){
		$form['subdocument_new'][$newi]['key'] = array(
			'#type' => 'textfield',      
			'#required' => FALSE,	  	  
			'#class' => 'value-field',
			'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
			'#prefix' => '<div class="clearboth">',    				
			'#theme_wrappers' => array(),
			'#size' => 2000,
		);
		$form['subdocument_new'][$newi]['valuee'] = array(
			'#type' => 'textfield',      
			'#required' => FALSE,	  	  
			'#class' => 'value-field',	  
			'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),				
			'#suffix' => '</div><br>',
			'#theme_wrappers' => array(),
			'#size' => 2000,
		);
	}

	$form['subdocument_new']['actions']['add_name'] = [
		'#type' => 'submit',
        '#value' => t('Add one more'),
        '#submit' => array('::addOne'),
        '#ajax' => [
			'callback' => '::addmoreCallback',
			'wrapper' => "subnames-fieldset-wrapper-sub",		
		],		
		'#prefix' => '<div class="clearboth">', 
    ];
	
    if ($num_name_field > 1) {
        $form['subdocument_new']['actions']['remove_name'] = [
          '#type' => 'submit',		 
          '#value' => t('Remove one'),
          '#submit' => array('::removeCallback'),
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => "subnames-fieldset-wrapper-sub",
          ],
		  '#suffix' => '</div><br>',
        ];
	}
	$form_state->setCached(FALSE);

	$form['action']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
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
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
	public function addmoreCallback(array &$form, FormStateInterface $form_state) {
		return $form['subdocument_new'];
	}

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
	public function addOne(array &$form, FormStateInterface $form_state) {
		$name_field = $form_state->get('num_subdocument');
		$add_button = $name_field + 1;
		$form_state->set('num_subdocument', $add_button);
		$form_state->setRebuild();
	}

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
	public function removeCallback(array &$form, FormStateInterface $form_state) {
		$name_field = $form_state->get('num_subdocument');
		if ($name_field > 1) {
			$remove_button = $name_field - 1;
			$form_state->set('num_subdocument', $remove_button);
		}
		$form_state->setRebuild();
	}
  

  /**
   * {@inheritdoc}
   */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		global $base_url;
	    $document_id = $_GET['document_id'];
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $_GET['mongodb_collection'] ."/findByID";
		$api_param = array ( "token" => $_SESSION['mongodb_token'], "id" => $document_id);
									 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);		
		curl_close ($ch);
	  
		$json_result = json_decode($server_output, true);  
		$json_result = $json_result[$_GET['editkey']];
		
		$action_btn = $form_state->getTriggeringElement()['#name'];
	  
		if ($action_btn == "delete_kv" ) {
			$document_values = $form_state->getValue("subdocument");
			$query = '{"_id" : "' . $_GET['document_id'] . '",' ;
			$fields = "{";
			foreach($document_values as $document_value)
			{
			 if (isset($document_value['remove_key']) && $document_value['remove_key'] == 1) {
				 $query .= '"' . $_GET['editkey'] . '.' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
				 $fields .= '"' . $_GET['editkey'] . '.'  . $document_value['key'] . '":"",';
			 }
			}
			$query = substr($query,0, strlen($query)-1) . "}";
			$fields = substr($fields,0, strlen($fields)-1) . "}";		 
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $_GET['mongodb_collection'] ."/unset";
			$api_param = array ( 		    
				"token" => $_SESSION['mongodb_token'], 
				"query" => $query, 
				"fields" => $fields
			);
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);		
			drupal_set_message("Deleted fields successfully");
			$showHideJson = \Drupal::config('mongodb_api.settings')->get('json_setting');
			if($showHideJson == "Yes")
				drupal_set_message($server_output);
			curl_close ($ch);   
		}else{ 		
	  
			$updateWith = '{';
			$document_values = $form_state->getValue("subdocument");	
	
			foreach($document_values as $document_key => $document_value){
			// if ($document_key != 'actionss') {
				 if (isset($document_value['valuee'])) {
					 if ($document_value['valuee'] != "") {
						$updateWith .= '"' . $_GET['editkey'] . '.' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
					 } 				
				 }
			 //}
			}
			$document_values = $form_state->getValue("subdocument_new");	
	
			foreach($document_values as $document_key => $document_value){
			// if ($document_key != 'actionss') {
				 if (isset($document_value['valuee'])) {
					 if ($document_value['valuee'] != "") {
						$updateWith .= '"' . $_GET['editkey'] . '.' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
					 } 				
				 }
			 //}
			}
		 
			$updateWith = substr($updateWith,0, strlen($updateWith)-1);		 
			if($updateWith != "")
				$updateWith .= "}";
	   
			if ($updateWith != "") {
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $_GET['mongodb_collection'] ."/update";
				$api_param = array ( 
					"query" => '{"_id":"'.$_GET['document_id'].'"}', 
					"token" => $_SESSION['mongodb_token'], 
					"updateWith" => $updateWith
				);
							 
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$server_output = curl_exec ($ch);		
				curl_close ($ch);
		 
				drupal_set_message("Updated Successfully");
				$showHideJson = \Drupal::config('mongodb_api.settings')->get('json_setting');
				if($showHideJson == "Yes")
					drupal_set_message($server_output);
			}else{
				drupal_set_message("Updated Successfully");
			}
		}
		$redirect_url = $base_url . '/mongodb_api/managedocument?mongodb_collection=' . $_GET['mongodb_collection'] . '&document_id=' . $_GET['document_id'];
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
	}  

}