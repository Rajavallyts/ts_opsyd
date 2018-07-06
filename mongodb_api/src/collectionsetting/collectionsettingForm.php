<?php
namespace Drupal\mongodb_api\collectionsetting;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\dataform\Entity\DataForm;
use Drupal\url_redirect\Entity\UrlRedirect;
use Drupal\collection_relations\Entity\CollectionRelations;

class collectionsettingForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collectionsetting';
  }
  
 /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  
	  global $base_url;	  
	  $server_output = "";
	  \Drupal::service('page_cache_kill_switch')->trigger();
	checkConnectionStatus();
	
	$mongodb_collection = $webform_id = '';
	if (isset($_GET['mongodb_collection']) && isset($_GET['webform_id'])) {
		$mongodb_collection = $_SESSION["data_mongodb_collection"] = $_GET['mongodb_collection'];
		$webform_id = $_SESSION["data_webform_id"] = $_GET['webform_id'];
	}else if (isset($_GET['mongodb_collection']) && !isset($_GET['webform_id'])) {
		$mongodb_collection = $_SESSION["data_mongodb_collection"] = $_GET['mongodb_collection'];
		$_SESSION["data_webform_id"] = '';
	}else{
		if(isset($_SESSION["data_mongodb_collection"]) && isset($_SESSION["data_webform_id"])){
			$mongodb_collection = $_SESSION["data_mongodb_collection"];
			$webform_id = $_SESSION["data_webform_id"];
		}
	}

if ($_SESSION['mongodb_token'] != ""){
	if (!empty($mongodb_collection)) {
		  $webform = \Drupal\webform\Entity\Webform::load($webform_id);
		  $collection_url = $collection_title = "";
		  $webform_elements = [];
		 
		  $webform_elements_key = [];
		  if (isset($webform)) {
			$webform_settings = $webform->getSettings();			
			$webform_elements = $webform->getElementsDecoded();
			//$webform_elements_key = array_keys($webform_elements);
			$webform_elements_key = array_keys_multi($webform_elements);
			$collection_title = $webform->label();
		  }
		  
		  $urlredirect = UrlRedirect::load('ur_' . $webform_id);
		  if (isset($urlredirect)) {			 
			  $collection_url = $urlredirect->get('path');			
		  }
		  
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $mongodb_collection ."/find";
          $api_param = array ( "token" => $_SESSION['mongodb_token']);
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $server_output = curl_exec ($ch);		
		  curl_close ($ch);
		  
		  $json_results = json_decode($server_output, true);
		  
		  $new_json = array();
		  foreach($json_results as $json_result){
			$new_json = array_merge_recursive($new_json,$json_result);
		  }
		
      $form['#tree'] = TRUE;
	  $form['#attached']['library'][] = 'mongodb_api.customcss'; 	  
      $form['markup_breadcrumb'] = [
		'#markup' => "<b>" . $mongodb_collection . "</b>",
	  ];
	  
	$form['collection_title'] = [
		'#type' => 'textfield',
		'#title' => t('Collection Title'),
		'#required' => TRUE,
		'#default_value' => $collection_title,
	];

      $form['collection_url'] = [
		'#type' => 'textfield',
		'#title' => t('Link for this collection'),
		'#required' => TRUE,		
		'#default_value' => $collection_url,
	];

	$form['webform_id'] = [
		'#type' => 'hidden',
		'#default_value' => $webform_id,
	];

	$form['connection_ref'] = [
		'#type' => 'hidden',
		'#default_value' => isset($_SESSION['mongodb_nid']) ? $_SESSION['mongodb_nid'] : '',
	];

		 $form['document'] = [
       '#type' => 'details',
       '#title' => $this->t(' Please select the key which you want to display in dataform '),
       '#prefix' => '<div class="clearboth">',
       '#suffix' => '</div>',
	   '#open' => TRUE,
      ];
		
	$form['document']['select_all'] = [
		'#type' => 'checkbox',
		'#title' => 'Select All',
		'#attributes' => array('data-attr' => 'check-firstlevel'),
	];
		
		$json_result = $new_json;
		$i=0;
		foreach($json_result as $resultkey => $resultValue):

			if (($resultkey != "_id")) {						
					
				if (is_array($resultValue) && is_asso($resultValue)){
							$form['document'][$i] = [
							'#type' => 'details',
								'#title' => $resultkey ,
							'#prefix' => '<div class="clearboth">',
								'#suffix' => '</div>',
							'#open' => TRUE,
							];							
						
						if(isset($webform_elements[$resultkey])){
							$sub_webform_elements_key = array_keys_multi($webform_elements[$resultkey]);
							$sub_webform_elements = $webform_elements[$resultkey];
						}else{
							$sub_webform_elements_key = $sub_webform_elements = array();
						}
						
						$form['document'][$i]['document'] = addsublevel($resultkey, $resultValue, $resultkey, $sub_webform_elements, $sub_webform_elements_key);
							
						} else {
							$form['document'][$i]['webform_select'] = array(
					'#type' => 'checkbox',				
					'#prefix' => '<div class="clearboth">',    
					'#theme_wrappers' => array(),	
					'#default_value' => in_array($resultkey, $webform_elements_key) ? true : false,
						'#attributes' => array('data-attr' => 'firstlevel'),
				);
				
				$form['document'][$i]['key'] = array(
						'#type' => 'textfield',      
						'#required' => FALSE,
						'#default_value' => $resultkey,	 
						'#class' => 'value-field',
						'#attributes' => array('style' => 'float: left; max-width: 200px; margin: 10px;','disabled' => 'disabled'),
						'#theme_wrappers' => array(),
					);
					
					$label_value = isset($webform_elements[$resultkey]["#title"]) ? $webform_elements[$resultkey]["#title"] : '';
					$form['document'][$i]['label_name'] = array(
						'#type' => 'textfield',      
						'#required' => FALSE,
						'#default_value' => $label_value,
						'#attributes' => array('style' => 'float: left; max-width: 200px; margin: 10px;','placeholder' => 'Rename '.$resultkey),
					);
					
					$query = \Drupal::entityQuery('collection_relations')
							->condition('status', 1)
							->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'], '=')
							->condition('field_source_collection', trim($mongodb_collection), '=')
							->condition('field_source_key', trim($resultkey), '=');
					$coll_ids = $query->execute();
					
					$disabledAttr = FALSE;
					if(!empty($coll_ids)){
						$coll_rel = CollectionRelations::load(array_keys($coll_ids)[0]);
						$rel_collection = $coll_rel->field_relative_collection->value;
						$rel_key 		= $coll_rel->field_relative_key->value;
						$rel_value 		= $coll_rel->field_relative_value->value;
						
						$disabledAttr = TRUE;
					}
					
					$form['document'][$i]['field_format'] = array(
					'#type' => 'select',      
					'#required' => FALSE,
					'#options' => [
						'textfield' => 'Textfield',
						'textarea' => 'Textarea',
						'select' => 'Dropdown',
							'radios' => 'Radio',
						'boolean' => 'Boolean',
							'webform_image_file' => 'File',
							//'generic_element' => t('Collection Relation'),
					],
					'#class' => 'value-field',					
					'#theme_wrappers' => array(),		
						'#attributes' => array('style' => 'float: left;', 'class' => array('formfieldformat'), 'disabled' => $disabledAttr),
				);
					
				$options = array();					
				$multiple_check = $required_check = $unique_check = false;
				if (isset($webform_elements[$resultkey])) {		
					if ($webform_elements[$resultkey]['#type']	== 'select') {
							$form['document'][$i]['field_format']['#default_value'] = 'select';
							foreach ($webform_elements[$resultkey]['#options'] as $okey => $ovalue):
								$options[] = $ovalue;
							endforeach;
							
							if(isset($webform_elements[$resultkey]["#multiple"]) && $webform_elements[$resultkey]["#multiple"] == 1)
								$multiple_check = true;
					}else if ($webform_elements[$resultkey]['#type'] == 'radios') {
						$form['document'][$i]['field_format']['#default_value'] = 'radios';
						foreach ($webform_elements[$resultkey]['#options'] as $okey => $ovalue):
							$options[] = $ovalue;
						endforeach;
						}else if ($webform_elements[$resultkey]['#type']	== 'checkbox') {
							$form['document'][$i]['field_format']['#default_value'] = 'boolean';
						}else {					
						if(isset($webform_elements[$resultkey]["#multiple"]) && $webform_elements[$resultkey]["#multiple"] == 1)
							$multiple_check = true;
						$form['document'][$i]['field_format']['#default_value'] = $webform_elements[$resultkey]['#type'];
					}
					if(isset($webform_elements[$resultkey]["#required"]) && $webform_elements[$resultkey]["#required"] == 1)
						$required_check = true;
					if(isset($webform_elements[$resultkey]["#unique"]) && $webform_elements[$resultkey]["#unique"] == 1)
						$unique_check = true;
				}
				
				if(!empty($coll_ids)){
					
					$relative_field_format = '';
					if(isset($webform_elements[$resultkey]["#field_type"]))
						$relative_field_format = $webform_elements[$resultkey]["#field_type"];
					
					$form['document'][$i]['relative_field_format'] = array(
						'#type' => 'select',      
						'#required' => FALSE,
						'#options' => [
							'radio' => 'Radio',
							'select' => 'Dropdown',
						],
						'#default_value' => $relative_field_format,
						'#theme_wrappers' => array(),		
						'#attributes' => array('style' => 'float: left;', 'class' => array('relative_field_format')),
					);
				}
				
				$text_field_type = isset($webform_elements[$resultkey]["#text_field_type"]) ? $webform_elements[$resultkey]["#text_field_type"] : '';
				
				$form['document'][$i]['text_field_type'] = array(
					'#type' => 'select',      
					'#options' => [
						'' => 'Select validation',
						'textfield' => 'Text',
						'number' => 'Number (Integer)',
						'float' => 'Number (Float)',
						'email' => 'Email',
						'tel' => 'Telephone',
						'date' => 'Date',
						'datetime' => 'Date and Time'
					],
					'#class' => 'value-field',					
					'#theme_wrappers' => array(),
					'#default_value' => $text_field_type,					
					'#attributes' => array('style' => 'float: left;', 'class' => array('formvalidation')),
				);
				
				$form['document'][$i]['required_attr'] = array(
					'#type' => 'checkbox',				
					'#title' => 'Required',
					'#default_value' => $required_check,
					'#attributes' => array('style' => 'float:left;'),
					'#prefix' => '<div class="required_check">',
					'#suffix' => '</div>'
				);
				
				$form['document'][$i]['multiple_attr'] = array(
					'#type' => 'checkbox',				
					'#title' => 'Multiple',
					'#default_value' => $multiple_check,
					'#attributes' => array('style' => 'float:left;', 'class' => array('multiple_attr_field')),
					'#prefix' => '<div class="multiple_check">',
					'#suffix' => '</div>'
				);
				
				$form['document'][$i]['unique_attr'] = array(
					'#type' => 'checkbox',				
					'#title' => 'Unique',
					'#default_value' => $unique_check,
					'#attributes' => array('style' => 'float:left;'),
					'#prefix' => '<div class="unique_check">',
					'#suffix' => '</div>'
				);
				
				$form['document'][$i]['dropdown_options'] = array(
					'#type' => 'textfield',				
					'#default_value' => implode(",",$options),
					'#attributes' => array('style' => 'float:left; border: #c0c0c0 1px solid !important; max-width: 300px; margin-left: 7px;'),
					'#prefix' => '<div class="dropdown_values">',
					'#suffix' => '</div>'							
				);

					if(!empty($coll_ids)){
						$form['document'][$i]['field_format']['#options']['generic_element'] = t('Collection Relation');
						$form['document'][$i]['field_format']['#default_value'] = 'generic_element';
						
						$form['document'][$i]['entity_id'] = array(
							'#type' => 'hidden',
							'#default_value' => array_keys($coll_ids)[0],	 					
						);
						
						$form['document'][$i]['relative_entity'] = array(
							'#type' => 'textfield',
							'#default_value' => $rel_collection,
							'#class' => 'value-field',
							'#attributes' => array('style' => 'float: left; max-width: 250px; margin: 10px;','disabled' => TRUE),					   					
							'#theme_wrappers' => array(),
						);
					}
				
				$form['document'][$i]['end_div'] = array(
					'#markup' => '',							
					'#suffix' => '</div>',							
				);
						}
				$i++;
			}
		endforeach;	
//$form['document'][$i] = addsublevel('dsafd');
		

	$form_state->setCached(FALSE);

	$form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Changes'),
	  '#name' => 'save_changes',
    ];	
}
}else{
		$form['notice'] = [
			'#markup' => "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>"
		];
	}
	
	
    return $form;
  }
  
  
public function validateForm(array &$form, FormStateInterface $form_state) {
	
	$error_flag = 0;
	$con = \Drupal\Core\Database\Database::getConnection();
	$collection_url = $form_state->getValue('collection_url');
	if(!empty($form_state->getValue('webform_id'))){
		$webform_id = $form_state->getValue('webform_id');
		
		$query = $con->select('dataform__field_collection_url', 'd');
		$query->join('dataform__field_web_form_id','wf','wf.entity_id=d.entity_id');
		$query->fields('d', ['field_collection_url_value']);
		$query->fields('wf', ['field_web_form_id_value']);
		$query->condition('field_collection_url_value', "%" . $query->escapeLike($collection_url) . "%", 'LIKE');
		$result = $query->execute()->fetchObject();
		
		// Check if the url is already exist.
		if (!empty($result)) {
			if($result->field_web_form_id_value != $webform_id)
				$error_flag = 1;
		}
		
	}else{
		$query = $con->select('dataform__field_collection_url', 'd');
		$query->fields('d', ['field_collection_url_value']);
		$query->condition('field_collection_url_value', "%" . $query->escapeLike($collection_url) . "%", 'LIKE');
		$result = $query->execute()->fetchObject();
		
		// Check if the url is already exist.
		if (!empty($result)) {
			$error_flag = 1;
		}
	}
	
	if($error_flag == 1)
		$form_state->setErrorByName('collection_url', $this->t('This collection link is already used.'));
}
  
  
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {	
 
	global $base_url;
	$mongodb_collection = $_SESSION["data_mongodb_collection"];
	$document_values = $form_state->getValue("document");
	
	$webform_elements = [];
	foreach($document_values as $document_value):
		if ($document_value['webform_select'] == 1 || isset($document_value['document'])):
			if (isset($document_value['key'])) {			
				
				$multiple_attr = $required_attr = $unique_attr = FALSE;
				if(isset($document_value['multiple_attr']) && $document_value['multiple_attr'] == 1)
					$multiple_attr = TRUE;
				if(isset($document_value['required_attr']) && $document_value['required_attr'] == 1)
					$required_attr = TRUE;
				if(isset($document_value['unique_attr']) && $document_value['unique_attr'] == 1)
					$unique_attr = TRUE;
				
				if ($document_value['field_format'] == 'select') {
					$options = explode(",",$document_value['dropdown_options']);
					
					$dropdown_options = array();
					foreach($options as $option){
						$option = trim($option);
						$dropdown_options[$option] = $option;
					}
					
					$webform_elements[$document_value['key']] = [
					'#title' => $document_value['key'],
						'#type' => 'select',				
						'#multiple' =>	$multiple_attr,					
						'#options' => $dropdown_options
					];
				}elseif ($document_value['field_format'] == 'radios') {
					
					$options = explode(",",$document_value['dropdown_options']);
					$dropdown_options = array();
					foreach($options as $option){
						$option = trim($option);
						$dropdown_options[$option] = $option;
					}
					
					$webform_elements[$document_value['key']] = [
						'#title' => $document_value['key'],
						'#type' => 'radios',				
						'#options' => $dropdown_options,
					];
				}elseif ($document_value['field_format'] == 'boolean') {
					$webform_elements[$document_value['key']] = [
					'#title' => $document_value['key'],
					'#type' => 'checkbox'
					];
				}elseif ($document_value['field_format'] == 'webform_image_file') {
					$webform_elements[$document_value['key']] = [
					'#title' => $document_value['key'],
					'#type' => 'webform_image_file',				
					'#multiple' =>	$multiple_attr,				
					'#uri_scheme' => 's3'
					];
				}elseif ($document_value['field_format'] == 'generic_element') {
					$webform_elements[$document_value['key']] = [
					'#type' => 'element',
						'#multiple' =>	$multiple_attr,
					'#entity_id' => $document_value['entity_id'],
					'#field_type' => $document_value['relative_field_format'],
					];
				}elseif ($document_value['field_format'] == 'textfield') {
					$webform_elements[$document_value['key']] = [
						'#title' => $document_value['key'],
						'#type' => 'textfield',
						'#multiple' =>	$multiple_attr,
						'#unique' => $unique_attr,
						'#text_field_type' => isset($document_value['text_field_type']) ? $document_value['text_field_type'] : '',
					];
				}else {
					$webform_elements[$document_value['key']] = [
					'#title' => $document_value['key'],
					'#type' => $document_value['field_format'],
					];				
				}
				$webform_elements[$document_value['key']]['#required'] = $required_attr;
				if(isset($document_value['label_name']) && !empty($document_value['label_name']))
					$webform_elements[$document_value['key']]['#title'] = $document_value['label_name'];
			} else {
				
				if(array_find_deep($document_value['document'],1)){
				$webform_elements[$document_value['document']['key']]= [
					'#title' => $document_value['document']['key'],
					'#type' => 'details',
					'#open' => TRUE,
				];
				addsublevel_submit($document_value['document']['key'],$document_value['document'],$webform_elements);
				}
				//array_push($webform_elements,addsublevel_submit($document_value['document']['key'],$document_value['document']));
			}			
		endif;
    endforeach;	
	/*kint ($webform_elements);
	echo "<pre>";
	print_r($webform_elements);
	echo "</pre>";
	exit();*/
	
	$group_id = $_SESSION['group_id'];
	if(!empty($form_state->getValue('webform_id'))){
		$webform_id = $form_state->getValue('webform_id');
	}else{
		$webform_id = 'mdb_'.$group_id."_".REQUEST_TIME;
		//$webform_id = 'mdb_'.$mongodb_collection."_".substr(strtolower(str_replace(" ","_",$form_state->getValue('collection_title'))),0,4);
	}
	
	if(!empty($group_id)){
	$webform = \Drupal\webform\Entity\Webform::load($webform_id);
		$webform_title = $form_state->getValue('collection_title');
		
	if (!isset($webform)) {
		$webform = \Drupal\webform\Entity\Webform::create([
			'id' => $webform_id,
				'title' => $webform_title,
			'elements' => $webform_elements, 
			//'settings' => [
//				'page' => TRUE,
	//			'page_submit_path' => $form_state->getValue('collection_url'),
		//	],
		]);
		$webform->save();		
			
			$nodeform = Node::create([
			   'title' => $webform_title,
			   'type' => 'webform',
			   'webform' => $webform_id,
			   'status' => 1,
			]);  
			$nodeform->save();
			
			$dataform = DataForm::create([
			   'field_collection_name' => $mongodb_collection,
			   'field_mongodb_connection_ref' => $_SESSION['mongodb_nid'],
			   'field_collection_url' => $form_state->getValue('collection_url'),
			   'field_web_form_id' => $webform_id,
			   'field_webform_content_id' => $nodeform->nid,
			]);  
			$dataform->save();
			
			
		  // Load group and add content(node form) to group
		  $group = \Drupal::entityTypeManager()->getStorage('group')->load($group_id);
		  $group->addContent($nodeform, 'group_node:webform');
			
		  $urlredirect = UrlRedirect::create([
		   'id' => 'ur_' . $webform_id,
		   'label' => 'UrlRedirect - ' . $webform_id,
		   'path' => $form_state->getValue('collection_url'),
		   'redirect_path' => 'dataformsdocument?webform_id=' . $webform_id,
		   'checked_for' => "Role",
		   'roles' => array('authenticated' => "authenticated"),
		   'negate' => FALSE,
		   'message' => "No",
		   'status' => "1"	  
		  ]);
		  $urlredirect->save();
		  
			
	} else {
			$webform->set('title',$webform_title);		
		//$webform->setSettings(['page' => TRUE, 'page_submit_path' => $form_state->getValue('collection_url')]);		
		$webform->setElements($webform_elements);
		$webform->save();			

			// dataform update
			$query = \Drupal::entityQuery('dataform')
					->condition('field_web_form_id', $webform_id);
			$df_ids = $query->execute();
			foreach($df_ids as $df_id){
				$dataform_id = $df_id;
			}
			$dataform = DataForm::load($dataform_id);
			$webform_content_id = $dataform->field_webform_content_id->value;
			$dataform->set("field_collection_name",$mongodb_collection);
			$dataform->set("field_collection_url",$form_state->getValue('collection_url'));
			$dataform->save();
			
			// weform content uodate
			$nodeform = Node::load($webform_content_id);
			$nodeform->set('title',$webform_title);
			$nodeform->save();
			
			$urlredirect = UrlRedirect::load('ur_' . $webform_id);
			
			if (!isset($urlredirect)) {
				$urlredirect = UrlRedirect::create([
					'id' => 'ur_' . $webform_id,
					'label' => 'UrlRedirect - ' . $webform_id,
					'path' => $form_state->getValue('collection_url'),
					'redirect_path' => 'dataformsdocument?webform_id=' . $webform_id,
					'checked_for' => "Role",
					'roles' => array('authenticated' => "authenticated"),
					'negate' => FALSE,
					'message' => "No",
					'status' => "1"	  
				]);
				$urlredirect->save();
			} else {
				$urlredirect->set("path", $form_state->getValue('collection_url'));
				$urlredirect->save();				
			}
			
	}
	
		drupal_set_message("Success !");
		$redirect_url = $base_url . '/mongodb_api/listdataformdocument?mongodb_collection=' . $mongodb_collection.'&webform_id='.$webform_id;
	}else{
		drupal_set_message("Group id not found!");
		$redirect_url = $base_url . '/mongodb_api/listcollectionform?mongodb_collection=' . $mongodb_collection;
	}

	$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
	$response->send();
	return;
  }
}

function addsublevel($resultKey, $resultValue, $nlevelKey, $webform_elements, $webform_elements_key)
{
	
	$mongodb_collection = $_SESSION["data_mongodb_collection"];
	
	$j=0;
	$form['key'] = [		
		'#type' => 'hidden',
		'#default_value' => $resultKey,
	];
	
	$form['select_all'] = [
		'#type' => 'checkbox',
		'#title' => 'Select All',
		'#attributes' => array('data-attr' => 'check-'.$resultKey),
	];
	
	foreach($resultValue as $key => $value):		
		if (is_array($value) && is_asso($value)) {
			
			$form[$j] = [
				'#type' => 'details',
				'#title' => $key ,
				'#prefix' => '<div class="clearboth">',
				'#suffix' => '</div>',			
				'#open' => TRUE,
			];			
			
			if(isset($webform_elements[$key])){
				$sub_webform_elements_key = array_keys_multi($webform_elements[$key]);
				$sub_webform_elements = $webform_elements[$key];
			}else{
				$sub_webform_elements_key = $sub_webform_elements = array();
			}
			
			$form[$j]['document'] = addsublevel($key, $value, $nlevelKey.'.'.$key, $sub_webform_elements, $sub_webform_elements_key);
		} else {
			
			$form[$j]['webform_select'] = array(
				'#type' => 'checkbox',				
				'#prefix' => '<div class="clearboth">',    
				'#theme_wrappers' => array(),	
				'#default_value' => in_array($key, $webform_elements_key) ? true : false,
				'#attributes' => array('data-attr' => $resultKey),
			);	
					
			$form[$j]['key'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,
				'#default_value' => $key,	 
				'#class' => 'value-field',
				'#attributes' => array('style' => 'float: left; max-width: 200px; margin: 10px;','disabled' => 'disabled'),					   					
				'#theme_wrappers' => array(),
			);

			$label_value = isset($webform_elements[$key]["#title"]) ? $webform_elements[$key]["#title"] : '';
			$form[$j]['label_name'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,
				'#default_value' => $label_value,
				'#attributes' => array('style' => 'float: left; max-width: 200px; margin: 10px;','placeholder' => 'Rename '.$key),
			);
			
			$query = \Drupal::entityQuery('collection_relations')
					->condition('status', 1)
					->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'], '=')
					->condition('field_source_collection', trim($mongodb_collection), '=')
					->condition('field_source_key', trim($nlevelKey.'.'.$key), '=');
			$coll_ids = $query->execute();
			
			$disabledAttr = FALSE;
			if(!empty($coll_ids)){
				$coll_rel = CollectionRelations::load(array_keys($coll_ids)[0]);
				$rel_collection = $coll_rel->field_relative_collection->value;
				$rel_key 		= $coll_rel->field_relative_key->value;
				$rel_value 		= $coll_rel->field_relative_value->value;
				
				$disabledAttr = TRUE;
			}
			
			$form[$j]['field_format'] = array(
				'#type' => 'select',      
				'#required' => FALSE,
				'#options' => [
					'textfield' => t('Textfield'),
					'textarea' => t('Textarea'),
					'select' => t('Dropdown'),
					'radios' => 'Radio',
					'boolean' => t('Boolean'),
					'webform_image_file' => t('File'),
					//'generic_element' => t('Collection Relation'),
				],
				'#class' => 'value-field',					
				'#theme_wrappers' => array(),		
				'#attributes' => array('style' => 'float: left;','class' => array('formfieldformat'),'disabled' => $disabledAttr),
			);
			
			$options = array();					
			$multiple_check = $required_check = $unique_check = false;
			if (isset($webform_elements[$key])) {		
				if ($webform_elements[$key]['#type']	== 'select') {
					$form[$j]['field_format']['#default_value'] = 'select';
					foreach ($webform_elements[$key]['#options'] as $okey => $ovalue):
						$options[] = $ovalue;
					endforeach;
					
					if(isset($webform_elements[$key]["#multiple"]) && $webform_elements[$key]["#multiple"] == 1)
						$multiple_check = true;
				}else if ($webform_elements[$key]['#type'] == 'radios') {
					$form['document'][$i]['field_format']['#default_value'] = 'radios';
					foreach ($webform_elements[$key]['#options'] as $okey => $ovalue):
						$options[] = $ovalue;
					endforeach;
				}else if ($webform_elements[$key]['#type']	== 'checkbox') {
					$form[$j]['field_format']['#default_value'] = 'boolean';
				}else {					
					if(isset($webform_elements[$key]["#multiple"]) && $webform_elements[$key]["#multiple"] == 1)
						$multiple_check = true;
					$form[$j]['field_format']['#default_value'] = $webform_elements[$key]['#type'];
				}
				if(isset($webform_elements[$key]["#required"]) && $webform_elements[$key]["#required"] == 1)
					$required_check = true;
				if(isset($webform_elements[$key]["#unique"]) && $webform_elements[$key]["#unique"] == 1)
					$unique_check = true;
			}
			
			if(!empty($coll_ids)){
				$relative_field_format = '';
				if(isset($webform_elements[$key]["#field_type"]))
					$relative_field_format = $webform_elements[$key]["#field_type"];
				
				$form[$j]['relative_field_format'] = array(
					'#type' => 'select',      
					'#required' => FALSE,
					'#options' => [
						'radio' => 'Radio',
						'select' => 'Dropdown',
					],
					'#default_value' => $relative_field_format,
					'#theme_wrappers' => array(),		
					'#attributes' => array('style' => 'float: left;', 'class' => array('relative_field_format')),
				);
			}
			
			$text_field_type = isset($webform_elements[$key]["#text_field_type"]) ? $webform_elements[$key]["#text_field_type"] : '';
			
			$form[$j]['text_field_type'] = array(
				'#type' => 'select',      
				'#options' => [
					'' => 'Select validation',
					'textfield' => 'Text',
					'number' => 'Number (Integer)',
					'float' => 'Number (Float)',
					'email' => 'Email',
					'tel' => 'Telephone',
					'date' => 'Date',
					'datetime' => 'Date and Time'
				],
				'#class' => 'value-field',					
				'#theme_wrappers' => array(),
				'#default_value' => $text_field_type,				
				'#attributes' => array('style' => 'float: left;', 'class' => array('formvalidation')),
			);
			
			$form[$j]['required_attr'] = array(
				'#type' => 'checkbox',				
				'#title' => 'Required',
				'#default_value' => $required_check,
				'#attributes' => array('style' => 'float:left;'),
				'#prefix' => '<div class="required_check">',
				'#suffix' => '</div>'
			);
			
			$form[$j]['multiple_attr'] = array(
				'#type' => 'checkbox',				
				'#title' => 'Multiple',
				'#default_value' => $multiple_check,
				'#attributes' => array('style' => 'float:left;','class' => array('multiple_attr_field')),
				'#prefix' => '<div class="multiple_check">',
				'#suffix' => '</div>'
			);
			
			$form[$j]['unique_attr'] = array(
				'#type' => 'checkbox',				
				'#title' => 'Unique',
				'#default_value' => $unique_check,
				'#attributes' => array('style' => 'float:left;'),
				'#prefix' => '<div class="unique_check">',
				'#suffix' => '</div>'
			);
			
			$form[$j]['dropdown_options'] = array(
				'#type' => 'textfield',				
				'#default_value' => implode(",",$options),				
				'#attributes' => array('style' => 'float:left; border: #c0c0c0 1px solid !important; max-width: 300px; margin-left: 7px;'),
				'#prefix' => '<div class="dropdown_values">',
				'#suffix' => '</div>'					
			);
			
			if(!empty($coll_ids)){
				$form[$j]['field_format']['#options']['generic_element'] = t('Collection Relation');
				$form[$j]['field_format']['#default_value'] = 'generic_element';
				
				$form[$j]['entity_id'] = array(
					'#type' => 'hidden',
					'#default_value' => array_keys($coll_ids)[0],	 					
				);
				
				$form[$j]['relative_entity'] = array(
					'#type' => 'textfield',
					'#default_value' => $rel_collection,
					'#class' => 'value-field',
					'#attributes' => array('style' => 'float: left; max-width: 250px; margin: 10px;','disabled' => TRUE),					   					
					'#theme_wrappers' => array(),
					'#size' => 2000,									
				);
			}
			
			$form[$j]['end_div'] = array(
				'#markup' => '',							
				'#suffix' => '</div>',
			);
		}
		$j++;		
	endforeach;	  

	return $form;
	  
  }
  
function addsublevel_submit($doc_key, $document_values, &$webform_elements){
	//$webform_elements = [];	

	foreach($document_values as $document_key => $document_value):
	//kint ($document_key);
	//kint ($document_value);
	//kint (strcmp($document_key,'key') == 0);
		if ($document_value['webform_select'] == 1 || isset($document_value['document'])):
		if (strcmp($document_key,'key') != 0):		
			if (!isset($document_value['document'])) {			
				$multiple_attr = $required_attr = $unique_attr =FALSE;
				if(isset($document_value['multiple_attr']) && $document_value['multiple_attr'] == 1)
					$multiple_attr = TRUE;
				if(isset($document_value['required_attr']) && $document_value['required_attr'] == 1)
					$required_attr = TRUE;
				if(isset($document_value['unique_attr']) && $document_value['unique_attr'] == 1)
					$unique_attr = TRUE;
			
				if ($document_value['field_format'] == 'select') {
					
					$options = explode(",",$document_value['dropdown_options']);
					$dropdown_options = array();
					foreach($options as $option){
						$option = trim($option);
						$dropdown_options[$option] = $option;
					}
					
					$webform_elements[$doc_key][$document_value['key']] = [
					'#title' => $document_value['key'],
						'#type' => 'select',				
						'#multiple' =>	$multiple_attr,					
						'#options' => $dropdown_options
					];
				}elseif ($document_value['field_format'] == 'radios') {
					
					$options = explode(",",$document_value['dropdown_options']);
					$dropdown_options = array();
					foreach($options as $option){
						$option = trim($option);
						$dropdown_options[$option] = $option;
					}
					
					$webform_elements[$doc_key][$document_value['key']] = [
						'#title' => $document_value['key'],
						'#type' => 'radios',				
						'#options' => $dropdown_options,
					];
				}elseif ($document_value['field_format'] == 'boolean') {
					$webform_elements[$doc_key][$document_value['key']] = [
					'#title' => $document_value['key'],
					'#type' => 'checkbox',				
					];
				}elseif ($document_value['field_format'] == 'webform_image_file') {
					$webform_elements[$doc_key][$document_value['key']] = [
					'#title' => $document_value['key'],
					'#type' => 'webform_image_file',
					'#multiple' =>	$multiple_attr,					
					'#uri_scheme' => 's3',					
					];
				}elseif ($document_value['field_format'] == 'generic_element') {
					$webform_elements[$doc_key][$document_value['key']] = [
					'#type' => 'element',
						'#multiple' =>	$multiple_attr,
					'#entity_id' => $document_value['entity_id'],
					'#field_type' => $document_value['relative_field_format'],
					];
				}elseif ($document_value['field_format'] == 'textfield') {
					$webform_elements[$doc_key][$document_value['key']] = [
						'#title' => $document_value['key'],
						'#type' => 'textfield',
						'#multiple' =>	$multiple_attr,
						'#unique' => $unique_attr,
						'#text_field_type' => isset($document_value['text_field_type']) ? $document_value['text_field_type'] : '',
					];
				}else {
					$webform_elements[$doc_key][$document_value['key']] = [
					'#title' => $document_value['key'],
					'#type' => $document_value['field_format'],
					];				
				}
				$webform_elements[$doc_key][$document_value['key']]['#required'] = $required_attr;
				if(isset($document_value['label_name']) && !empty($document_value['label_name']))
					$webform_elements[$doc_key][$document_value['key']]['#title'] = $document_value['label_name'];
			} else {
				/*$webform_elements[$doc_key][$document_value['document']['key']] = [
					'#title' => $document_value['document']['key'],
					'#type' => 'details',
					'#open' => TRUE,
				];
				$webform_elements[$document_value['document']['key']][] = addsublevel_submit($document_value['document']['key'],$document_value['document']);*/
				
				if(array_find_deep($document_value['document'],1)){
					$webform_elements[$doc_key][$document_value['document']['key']] = [
						'#title' => $document_value['document']['key'],
						'#type' => 'details',
						'#open' => TRUE,
					];
					
					addsublevel_submit($document_value['document']['key'],$document_value['document'],$webform_elements[$doc_key]);
				}	
			}	
		/*else:
			$webform_elements[$doc_key] = [
					'#title' => $doc_key,
					'#type' => 'details',
					'#open' => TRUE,
			];				*/
		endif;
	endif;
    endforeach;	
	
	//return $webform_elements;
	  
}
  
function is_asso($a) {
	foreach(array_keys($a) as $key)
		if (!is_int($key))
			return TRUE;
	return FALSE;
}

function array_keys_multi(array $array)
{
    $keys = array();
    foreach ($array as $key => $value) {
		if(strpos($key, '#') === false) // added this condition for filtering attributes
			$keys[] = $key;
        if (is_array($array[$key])) {
            $keys = array_merge($keys, array_keys_multi($array[$key]));
        }
  }
    return $keys;
}

function array_find_deep($array, $search, $keys = array())
{
    foreach($array as $key => $value) {
        if (is_array($value)) {
            $sub = array_find_deep($value, $search, array_merge($keys, array($key)));
            if (count($sub)) {
                return $sub;
            }
        } elseif ($value === $search) {
			if($key == "webform_select" || $key == "select_all")
				return array_merge($keys, array($key));
			else
				return array();
        }
    }

    return array();
}
