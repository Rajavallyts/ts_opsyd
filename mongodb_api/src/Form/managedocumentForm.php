<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class managedocumentForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_document';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  global $base_url;	  
	  $server_output = "";
	checkConnectionStatus();
	  
	$mongodb_collection = $document_id = '';
	if (isset($_GET['mongodb_collection']) && isset($_GET['document_id'])) {
		$mongodb_collection = $_SESSION["doc_mongodb_collection"] = $_GET['mongodb_collection'];
		$document_id = $_SESSION["doc_document_id"] = $_GET['document_id'];
	}else{
		if(isset($_SESSION["doc_mongodb_collection"]) && isset($_SESSION["doc_document_id"])){
			$mongodb_collection = $_SESSION["doc_mongodb_collection"];
			$document_id = $_SESSION["doc_document_id"];
		}
	}
	if ($_SESSION['mongodb_token'] != ""){
	  if (!empty($mongodb_collection) && !empty($document_id)) {
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/".$mongodb_collection."/findByID";		  
		  $api_param = array ( "token" => $_SESSION['mongodb_token'], "id" => $document_id);
									 
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $server_output = curl_exec ($ch);		
		  curl_close ($ch);	
	
	  $name_field = $form_state->get('num_document');
	  
	  if (empty($name_field)) 
			$form_state->set('num_document', 1);
	  
	  $form['api_result'] = array (
		'#type' => 'markup',
		'#markup' => "<b><a href='".$base_url."/mongodb_api/listdocument?mongodb_collection=".$mongodb_collection. "' target='_self'>".$mongodb_collection."</a> >".$document_id."</b><br><br>".mongodb_parseJSON($server_output)."<BR><BR>ObjectId - <b>".$document_id."</b><BR><a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='".$base_url. "/mongodb_api/addsubdocument?mongodb_collection=".$mongodb_collection."&document_id=".$document_id."'>Add Sub Document</a>&nbsp;&nbsp;&nbsp;<a class='' data-dialog-type='modal' data-dialog-options='{\"width\":900}'  href='".$base_url."/mongodb_api/keyupdate?mongodb_collection=".$mongodb_collection."&document_id=".$document_id."'>Update Keys</a>",
	 );
	 
      $form['#tree'] = TRUE;
	  $form['#attached']['library'][] = 'mongodb_api.customcss';
     

	  $json_result = json_decode($server_output, true);	
	  $initial_state = 0;
	  if (count ($json_result) > 0 ) {	
		$i=0;	
		if (empty($name_field)) { 			
			$form_state->set('num_document', 1);
			$initial_state = $form_state->get('num_document');
		}
		
		 $form['document'] = [
       '#type' => 'fieldset',
       '#title' => $this->t(' Existing [Key - Value]  '),
       '#prefix' => "<div>",
       '#suffix' => '</div>',
	   '#collapsible' => TRUE,
       '#collapsed' => FALSE,
      ];
		
		foreach($json_result as $resultkey => $resultValue):			
			if (($resultkey != "_id")) {								
				$form['document'][$i]['remove_key'] = array(
					'#type' => 'checkbox',				
					'#prefix' => '<div class="clearboth">',    
					'#theme_wrappers' => array(),
				);			
				$form['document'][$i]['key'] = array(
					'#type' => 'textfield',      
					'#required' => FALSE,
					'#default_value' => $resultkey,	 
					'#class' => 'value-field',
					'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;','disabled' => 'disabled'),					   					
					'#theme_wrappers' => array(),
					'#size' => 2000,					
				);
				if (!is_array($resultValue)) {
					$form['document'][$i]['valuee'] = array(
						'#type' => 'textfield',  
						'#title' => $resultkey,
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
					
					$form['document'][$i]['valuee'] = array(
     					'#type' => 'link',
     					'#title' => "{" . count($resultValue) . " " . $fields_text. "}",
						 '#url' => \Drupal\Core\Url::fromRoute('mongodb_api.subdocument', ['mongodb_collection' => $mongodb_collection, 'document_id' => $document_id, 'editkey' => $resultkey]),
						 '#attributes' => ['class' => ['use-ajax'],'data-dialog-type' => 'modal','data-dialog-options' => \Drupal\Component\Serialization\Json::encode(['width' => 900])],	
						 '#prefix' => "<div class='mongodb_subform_list'>",
						 '#suffix' => '</div></div><br>',
					);
 
						/* if (is_array($resultValue) && !empty($resultValue)){
	 
							if(is_asso($resultValue)){
								if(count($resultValue) > 1)
									$fields_text = "fields";
								else
									$fields_text = "field";
 
								$form['document'][$i]['valuee'] = array(
								 '#type' => 'link',
								 '#title' => "{" . count($resultValue) . " " . $fields_text. "}",
								 '#url' => \Drupal\Core\Url::fromRoute('mongodb_api.subdocument', ['mongodb_collection' => $_GET['mongodb_collection'], 'document_id' => $_GET['document_id'], 'editkey' => $resultkey]),
								 '#attributes' => ['class' => ['use-ajax'],'data-dialog-type' => 'modal','data-dialog-options' => \Drupal\Component\Serialization\Json::encode(['width' => 900])],	
	 '#prefix' => "<div class='mongodb_subform_list'>",
	 '#suffix' => '</div></div><br>',
   );
							}else{
								$resultFieldVal = '';
								foreach($resultValue as $resultVal){
									$resultFieldVal .=  $resultVal.',';
								}
								$form['document'][$i]['valuee'] = array(
									'#type' => 'textfield',  
									'#title' => $resultkey,
									'#required' => FALSE,
									'#default_value' => $resultFieldVal,	  
									'#class' => 'value-field',	  
									'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
									'#suffix' => '</div><br>',
									'#theme_wrappers' => array(),
									'#size' => 2000,
								);
							}
						}else{
							$form['document'][$i]['valuee'] = array(
								'#type' => 'textfield',
								'#title' => $resultkey,
								'#required' => FALSE,
								//'#default_value' => '',	  
								'#class' => 'value-field',	  
								'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
								'#suffix' => '</div><br>',
								'#theme_wrappers' => array(),
								'#size' => 2000,
							);
						} */
				}
				$i++;
			}
		endforeach;	

		$form['document_new'] = [
       '#type' => 'fieldset',
       '#title' => $this->t(' New [Key - Value]  '),
       '#prefix' => "<div id='names-fieldset-wrapper'>",
       '#suffix' => '</div>',
	   '#collapsible' => TRUE,
       '#collapsed' => FALSE,
      ];
		$new_field = $form_state->get('num_document');
		for($newi = 0; $newi < $new_field; $newi++){
			$form['document_new'][$i]['key'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
				'#prefix' => '<div class="clearboth">',       				
				'#theme_wrappers' => array(),
				'#size' => 2000,
			);
			$form['document_new'][$i]['valuee'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',	  
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),				
				'#suffix' => '</div><br>',
				'#theme_wrappers' => array(),
				'#size' => 2000,
			);	
	
			$i++;
		}				
	
	$form['document']['actions']['delete_kv'] = [
		'#type' => 'submit',
		'#value' => 'Delete selected',		 
		'#prefix' => '<div class="clearboth">',
		'#suffix' => '</div>',
		'#name' => 'delete_kv',
	];

	$form['document_new']['actions']['add_name'] = [
		'#type' => 'submit',
        '#value' => t('Add one more'),
        '#submit' => array('::addOne'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => "names-fieldset-wrapper",		
		],		
		'#prefix' => '<div class="clearboth">',       
    ];
    if ($form_state->get('num_document') > 1) {
        $form['document_new']['actions']['remove_name'] = [
          '#type' => 'submit',		 
          '#value' => t('Remove one'),
          '#submit' => array('::removeCallback'),
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => "names-fieldset-wrapper",
          ],		  
		  '#suffix' => '</div><br>',
        ];
	}
	$form_state->setCached(FALSE);

	$form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Changes'),
	  '#name' => 'save_changes',
    ];
	
		}
		
	} else {
		$form['noelement'] = array(
			'#type' => 'markup',
			'#markup' => "<BR><BR>No document selected. <a href='" . $base_url . "/mongodb_api/listcollection'>Select Document</a>",	
		);					
	}
	}else{
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

	$mongodb_collection = $_SESSION["doc_mongodb_collection"];
	$document_id = $_SESSION["doc_document_id"];
	   $action_btn = $form_state->getTriggeringElement()['#name'];
	  
	   if ($action_btn == "delete_kv" ) {
		 $document_values = $form_state->getValue("document");
		 $query = '{"_id" : "' . $document_id . '"}' ;
		 $fields = "{";
		 foreach($document_values as $document_value)
		 {
			 if ($document_value['remove_key'] == 1) {
				 $fields .= '"' . $document_value['key'] . '":"",';
			 }
		 }
		 $fields = substr($fields,0, strlen($fields)-1) . "}";		 
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $mongodb_collection."/unset";
		 $api_param = array ( 		    
			"token" => $_SESSION['mongodb_token'], 
			"query" => $query, 
			"fields" => $fields);
		 
		 $ch = curl_init();
		 curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		 curl_setopt($ch, CURLOPT_POST, 1);
		 curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		 $server_output = curl_exec ($ch);		
		 drupal_set_message("Deleted fields successfully");
		 curl_close ($ch);   
		   
	   } else {
	 
	     $updateWith = "{";
		 $document_values = $form_state->getValue("document");
		 
		 foreach($document_values as $document_value)
		 {
			 if (isset($document_value['valuee'])) {
				 if ($document_value['valuee'] != "") {
					$updateWith .= '"' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
				 }
			 }
		 }
		 //$updateWith = substr($updateWith,0, strlen($updateWith)-1); // . "}";		 
		 $document_values = $form_state->getValue("document_new");
		 foreach($document_values as $document_value)
		 {
			 if (isset($document_value['valuee'])) {
				 if ($document_value['valuee'] != "") {
					$updateWith .= '"' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
				 }
			 }
		 }
		 $updateWith = substr($updateWith,0, strlen($updateWith)-1) . "}";		 
		 
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $mongodb_collection."/update";
		  $api_param = array ( 
		    "query" => '{"_id":"'.$document_id.'"}', 
			"token" => $_SESSION['mongodb_token'], 
			"updateWith" => $updateWith);
									 
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $server_output = curl_exec ($ch);
		  drupal_set_message("Updated changes successfully");
		  curl_close ($ch);
	  }	 
	  $showHideJson = \Drupal::config('mongodb_api.settings')->get('json_setting');
	  if($showHideJson == "Yes")
		drupal_set_message($server_output);
  }


/**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_document');
    return $form['document_new'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
	  
    $name_field = $form_state->get('num_document');
    $add_button = $name_field + 1;
    $form_state->set('num_document', $add_button);	
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_document');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_document', $remove_button);
    }
    $form_state->setRebuild();
  }
  
}