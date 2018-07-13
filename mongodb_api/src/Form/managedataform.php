<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\collection_relations\Entity\CollectionRelations;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DrupalDateTime;


class managedataform extends FormBase {
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
	  $server_output = "";  
	   \Drupal::service('page_cache_kill_switch')->trigger();
	   checkConnectionStatus();
	  
	$form['#tree'] = TRUE;
	$form['#attached']['library'][] = 'mongodb_api.customcss';      
	
	$mongodb_collection = $webform_id = $document_id = '';
	
	if (isset($_GET['mongodb_collection']) && isset($_GET['webform_id']) && isset($_GET['document_id'])) {
		$mongodb_collection = $_SESSION["data_mongodb_collection"] = $_GET['mongodb_collection'];
		$webform_id = $_SESSION["data_webform_id"] = $_GET['webform_id'];
		$document_id = $_SESSION["data_document_id"] = $_GET['document_id'];
	}else if (isset($_GET['mongodb_collection']) && isset($_GET['webform_id']) && !isset($_GET['document_id'])) {
		$mongodb_collection = $_SESSION["data_mongodb_collection"] = $_GET['mongodb_collection'];
		$webform_id = $_SESSION["data_webform_id"] = $_GET['webform_id'];
		$_SESSION["data_document_id"] = '';
	}else{
		if(isset($_SESSION["data_mongodb_collection"]) && isset($_SESSION["data_webform_id"]) && isset($_SESSION['data_document_id'])){
			$mongodb_collection = $_SESSION["data_mongodb_collection"];
			$webform_id = $_SESSION["data_webform_id"];
			$document_id = $_SESSION["data_document_id"];
		}
	}
		
	if ($_SESSION['mongodb_token'] != ""){
	  if (!empty($mongodb_collection) && !empty($webform_id)) {
		  $webform = \Drupal\webform\Entity\Webform::load($webform_id);
		  $webform_elements = $webform->getElementsDecoded();
		  
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

		if($document_id)
			$title_prefix = $this->t('Edit ');
		else
			$title_prefix = $this->t('Add ');

		//if (count ($json_result) > 0 ) {	
		$i=0;			
		
		$form['document'] = [
			'#type' => 'fieldset',
			'#title' => $title_prefix.ucfirst($mongodb_collection),
			'#prefix' => "<div>",
			'#suffix' => '</div>',
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
		];
		
		//foreach($json_result as $resultkey => $resultValue):
			//if (in_array($resultkey, $webform_elements_keys)):
			foreach ($webform_elements_keys as $field):
					
					$form['document'][$i]['dkey'] = array(
						'#type' => 'hidden',
						'#default_value' => $field,						
					);
					
					$required_attr = '';
					if(isset($webform_elements[$field]["#required"]) && $webform_elements[$field]["#required"] == 1)
						$required_attr = 1;
					
					$field_label = (isset($webform_elements[$field]["#title"]) && !empty($webform_elements[$field]["#title"])) ? $webform_elements[$field]["#title"] : ucfirst($field);
					
					if($webform_elements[$field]["#type"] == "details"){
						$form['document'][$i] = [
							'#type' => 'details',
							'#title' => $field ,
							'#prefix' => '<div class="clearboth">',
							'#suffix' => '</div>',
							'#open' => TRUE,
						];
						
						// for "details field", again we need to add dkey
						$form['document'][$i]['dkey'] = array(
							'#type' => 'hidden',										
							'#default_value' => $field,						
						);
				
						$form['document'][$i]['document'] = addsublevel($field, $webform_elements[$field],(isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : array(), $form_state);
						
					}else if($webform_elements[$field]["#type"] == "select"){
						$multiple_attr = '';
						if(isset($webform_elements[$field]["#multiple"]) && $webform_elements[$field]["#multiple"] == 1)
							$multiple_attr = 1;
						
						$form['document'][$i]['select'] = array(			
							'#type' => 'select',
							'#title' => $field_label,
							'#multiple' =>	$multiple_attr,
							'#required' =>	$required_attr,
							'#options' => $webform_elements[$field]["#options"],
							'#default_value' => (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : '',
						);

					}else if($webform_elements[$field]["#type"] == "radios"){
						
						$form['document'][$i]['radios'] = array(			
							'#type' => 'radios',
							'#title' => $field_label,
							'#required' =>	$required_attr,
							'#options' => $webform_elements[$field]["#options"],
							'#default_value' => (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : '',
						);
					}else if($webform_elements[$field]["#type"] == "checkbox"){
						$checkbox_val = 0;
					if(isset($json_result) && isset($json_result[$field])){
							if($json_result[$field] == "TRUE")
								$checkbox_val = 1;
						}
						$form['document'][$i]['checkbox'] = array(			
							'#type' => 'checkbox',
							'#title' => $field_label,
							'#default_value' => $checkbox_val,
							'#required' =>	$required_attr,
						);
					}else if($webform_elements[$field]["#type"] == "textarea"){
						$form['document'][$i]['dvalue'] = array(			
							'#type' => 'textarea',
							'#title' => $field_label,
							'#default_value' => (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : '',
							'#required' =>	$required_attr,
						);
					}else if($webform_elements[$field]["#type"] == "webform_image_file"){
						$multiple_attr = '';
						if(isset($webform_elements[$field]["#multiple"]) && $webform_elements[$field]["#multiple"] == 1)
							$multiple_attr = 1;
						
						if(isset($json_result[$field])){
							if(is_array($json_result[$field])){
								$fid = array();
								if(!empty($json_result[$field])){
									foreach($json_result[$field] as $image_uri){
										// get existing fid
										$isFile = \Drupal::database()->select("file_managed","f")
												->fields("f",array("fid"))
												->condition("uri",$image_uri,"=")
												->execute()
												->fetchAssoc();
										if(!empty($isFile))
											$fid[] = $isFile["fid"];
									}
								}
							}else{
								$fid = '';
								if(!empty($json_result[$field])){
									// get existing fid
									$isFile = \Drupal::database()->select("file_managed","f")
									->fields("f",array("fid"))
									->condition("uri",$json_result[$field],"=")
									->execute()
									->fetchAssoc();
									if(!empty($isFile))
										$fid = $isFile;
								}
							}
						}else{
							if($multiple_attr)
								$fid = array();
							else
								$fid = '';
						}
						
						$form['document'][$i]['image'] = array(			
							'#type' => 'managed_file',				
							'#title' => $field_label,
							'#multiple' =>	$multiple_attr,
							'#required' =>	$required_attr,
							'#upload_location' => 's3://'.date("Y-m"), /* s3://2018-04 */
							'#default_value' => $fid,
						);
						
						$form['document'][$i]['image_info'] = array(			
							'#type' => 'hidden',
							'#default_value' => $multiple_attr,
						);
						
					}else if($webform_elements[$field]["#type"] == "element"){
						$multiple_attr = '';
						if(isset($webform_elements[$field]["#multiple"]) && $webform_elements[$field]["#multiple"] == 1)
							$multiple_attr = 1;
						
						$coll_rel = CollectionRelations::load($webform_elements[$field]["#entity_id"]);
						$rel_collection = $coll_rel->field_relative_collection->value;
						$rel_key 		= $coll_rel->field_relative_key->value;
						if (strpos($rel_key, '###') !== false) {
							$rel_keys = explode("###",$rel_key);
							$rel_key = $rel_keys[count($rel_keys)-1];
						}
						$rel_value 		= $coll_rel->field_relative_value->value;
						if (strpos($rel_value, '###') !== false) {
							$rel_values = explode("###",$rel_value);
							$rel_value = $rel_values[count($rel_values)-1];
						}
						
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/". $rel_collection."/find";
						$api_param = array ( "token" => $_SESSION['mongodb_token']);
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$document_lists = curl_exec ($ch);		
						curl_close ($ch);
						$documents = json_decode($document_lists, true);

						if($webform_elements[$field]["#field_type"] == 'select'){
						$relative_options = array('' => 'Select');
						foreach($documents as $document){
							if(isset($document[$rel_value]))
							$relative_options[$document[$rel_key]] = $document[$rel_value];
						}
						$form['document'][$i]['relational'] = array(			
							'#type' => 'select',
							'#title' => $field_label,
							'#required' =>	$required_attr,
							'#options' => $relative_options,
							'#multiple' =>	$multiple_attr,
							'#default_value' => (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : '',
						);
					}else{
							$relative_options = array();
							foreach($documents as $document){
								if(isset($document[$rel_value]))
									$relative_options[$document[$rel_key]] = $document[$rel_value];
							}
							$form['document'][$i]['relational'] = array(			
								'#type' => 'radios',
								'#title' => $field_label,
								'#options' => $relative_options,
								'#required' =>	$required_attr,
								'#default_value' => (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : ''
							);
						}
					}else{
						
						$multiple_attr = '';
						if(isset($webform_elements[$field]["#multiple"]) && $webform_elements[$field]["#multiple"] == 1)
							$multiple_attr = 1;
						
						$text_field_type = 'textfield';
						$attributes_array = [];
						if(!empty($webform_elements[$field]["#text_field_type"])){
							$text_field_type =  $webform_elements[$field]["#text_field_type"];
							if($text_field_type = "email")
								$attributes_array["pattern"] = "[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$";
						}
						
						if($multiple_attr){
							// Gather the number of names in the form already.
							$num_names = $form_state->get('num_names_'.$i);
							// We have to ensure that there is at least one name field.
							if ($num_names === NULL) {
								if(isset($json_result[$field]) && count($json_result[$field]) > 0){
									$name_field = $form_state->set('num_names_'.$i, count($json_result[$field]));
									$num_names = count($json_result[$field]);
								}else{
									$name_field = $form_state->set('num_names_'.$i, 1);
									$num_names = 1;
								}
							}
							
							$form['document'][$i]['names_fieldset'] = [
							  '#type' => 'fieldset',
							  '#prefix' => '<div id="names-fieldset-wrapper-'.$i.'">',
							  '#suffix' => '</div>',
							];
							
							$text_field_value = array();
							for ($k = 0; $k < $num_names; $k++) {
								if(isset($json_result[$field]) && !empty($json_result[$field])){
									if(!empty($webform_elements[$field]["#text_field_type"]) && $text_field_type == "datetime"){
										if(is_array($json_result[$field])){
											$text_field_value[$k] = '';
											$timestamp = strtotime($json_result[$field][$k]);
											if(!empty($timestamp))
												$text_field_value[$k] = DrupalDateTime::createFromTimestamp($timestamp);
										}else{
											$text_field_value[0] = '';
											$timestamp = strtotime($json_result[$field]);
											if(!empty($timestamp))
												$text_field_value[0] = DrupalDateTime::createFromTimestamp($timestamp);
										}
									}else{
										if(is_array($json_result[$field])){
											$text_field_value[$k] = $json_result[$field][$k];
										}else{
											$text_field_value[0] = $json_result[$field];
										}
									}
								}
								else
									$text_field_value[$k] = '';
								
								$form['document'][$i]['names_fieldset']['dvalue'][$k] = array(
									'#type' => $text_field_type,
									'#title' => $field_label,
									'#required' =>	$required_attr,
									'#default_value' => $text_field_value[$k],
									'#attributes' => $attributes_array
								);
								
								if(!empty($webform_elements[$field]["#text_field_type"])){
									if($text_field_type == "number" || $text_field_type == "float"){
										if($text_field_type == "number")
											$form['document'][$i]['names_fieldset']['dvalue'][$k]["#step"] = 1;
										else
											$form['document'][$i]['names_fieldset']['dvalue'][$k]["#step"] = "any";
										$form['document'][$i]['names_fieldset']['dvalue'][$k]["#type"] = "number";
									}
								}
								
							}
							
							$form['document'][$i]['names_fieldset']['actions'] = [
							  '#type' => 'actions',
							];
							$form['document'][$i]['names_fieldset']['actions']['add_name'] = [
							  '#type' => 'submit',
							  '#name' => 'add_one_'.$i,
							  '#value' => t('Add one more'),
							  '#submit' => ['::addOne'],
							  '#ajax' => [
								'callback' => '::addmoreCallback',
								'wrapper' => 'names-fieldset-wrapper-'.$i,
							  ],
							];
							// If there is more than one name, add the remove button.
							if ($num_names > 1) {
							  $form['document'][$i]['names_fieldset']['actions']['remove_name'] = [
								'#type' => 'submit',
								'#name' => 'remove_one_'.$i,
								'#value' => t('Remove one'),
								'#submit' => ['::removeCallback'],
								'#ajax' => [
								  'callback' => '::addmoreCallback',
								  'wrapper' => 'names-fieldset-wrapper-'.$i,
								],
							  ];
							}
							
						}else{
							$text_field_value = (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : '';
							if(!empty($webform_elements[$field]["#text_field_type"]) && $text_field_type == "datetime"){
								if(isset($json_result) && isset($json_result[$field])){
									$text_field_value = '';
									$timestamp = strtotime($json_result[$field]);
									if(!empty($timestamp))
										$text_field_value = DrupalDateTime::createFromTimestamp($timestamp);
								}
							}
							
							if(isset($webform_elements[$field]["#unique"]) && $webform_elements[$field]["#unique"] == 1){
								$form['document'][$i]['dunique'] = array(
									'#type' => 'hidden',
									'#default_value' => 1,
								);
							}
							
							$form['document'][$i]['dvalue'] = array(
								'#type' => $text_field_type,
								'#title' => $field_label,
								'#required' =>	$required_attr,
								'#default_value' => $text_field_value,
								'#attributes' => $attributes_array
							);
							
							if(!empty($webform_elements[$field]["#text_field_type"])){
								if($text_field_type == "number" || $text_field_type == "float"){
									if($text_field_type == "number")
										$form['document'][$i]['dvalue']["#step"] = 1;
									else
										$form['document'][$i]['dvalue']["#step"] = "any";
									$form['document'][$i]['dvalue']["#type"] = "number";
								}
							}
						}
					}
				$i++;
			//endif;
		endforeach;	
		
		$form_state->setCached(FALSE);

			$form["doc_json"] = array(
				'#type' => 'hidden',
				'#value' => $server_output
			);

		$form['submit'] = [
			'#type' => 'submit',
			'#value' => t('Save Changes'),
			'#name' => 'save_changes',
		];
	
		/* } else {
			$form['noelement'] = array(
				'#type' => 'markup',
				'#markup' => "<BR><BR>No document selected. <a href='" . $base_url . "/mongodb_api/listcollection'>Select Document</a>",	
			);					
		} */
		}
		}else{
			$form['notice'] = [
				'#markup' => "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>"
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
	$triggered_element = $form_state->getTriggeringElement()["#name"];
	$iterator = explode("_",$triggered_element);
	
    return $form['document'][$iterator[2]]['names_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
	$triggered_element = $form_state->getTriggeringElement()["#name"];
	$iterator = explode("_",$triggered_element);
	  
    $name_field = $form_state->get('num_names_'.$iterator[2]);
    $add_button = $name_field + 1;
    $form_state->set('num_names_'.$iterator[2], $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
	$triggered_element = $form_state->getTriggeringElement()["#name"];
	$iterator = explode("_",$triggered_element);
	  
    $name_field = $form_state->get('num_names_'.$iterator[2]);
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_names_'.$iterator[2], $remove_button);
    }
    $form_state->setRebuild();
  }
  
  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreSubCallback(array &$form, FormStateInterface $form_state) {
	$triggered_parents = $form_state->getTriggeringElement()["#array_parents"];
	$parents_counts = count($triggered_parents) - 2;
	
	$return_element = $form;
	for($m = 0; $m < $parents_counts; $m++){
		$return_element = $return_element[$triggered_parents[$m]];		
	}
	
	return $return_element;
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addSubOne(array &$form, FormStateInterface $form_state) {
	$triggered_element = $form_state->getTriggeringElement()["#name"];
	$iterator = explode("_",$triggered_element);
	  
    $name_field = $form_state->get('sub_num_names_'.$iterator[2]);
    $add_button = $name_field + 1;
    $form_state->set('sub_num_names_'.$iterator[2], $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeSubCallback(array &$form, FormStateInterface $form_state) {
	$triggered_element = $form_state->getTriggeringElement()["#name"];
	$iterator = explode("_",$triggered_element);
	  
    $name_field = $form_state->get('sub_num_names_'.$iterator[2]);
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('sub_num_names_'.$iterator[2], $remove_button);
    }
    $form_state->setRebuild();
  }
  
public function validateForm(array &$form, FormStateInterface $form_state) {
	$mongodb_collection = $_SESSION["data_mongodb_collection"];
	$document_id = $_SESSION["data_document_id"];
	
	$i=0;
	$document_values = $form_state->getValue("document");

	foreach($document_values as $document_value){
		if(isset($document_value['document']) && count($document_value['document']) > 0){
			$error_element = "document][$i]";
			sublevel_validation($document_value['document'], $document_value['dkey'], $error_element, $form_state);
		}else{
			if (isset($document_value['dunique'])) {
				if (isset($document_value['dvalue'])&& $document_value['dvalue'] != "") {
					
					if(isset($document_value['dvalue'])&& $document_value['dvalue'] != ""){
						$search_string = strtolower($document_value['dvalue']);
						$error_field_type = "dvalue";
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

					$documents = json_decode($server_output, true);
					$exist = 0;
					foreach($documents as $document){
						if(!empty($document_id) && $document_id == $document["_id"]){
							if (isset($document[$document_value['dkey']]) && strpos(strtolower($document[$document_value['dkey']]),$search_string) !== false ) {
								$exist++;
							}
							if($exist == 2){
								$form_state->setErrorByName("document][$i][".$error_field_type, $this->t("'$search_string' value is already exist in a document."));
								break;
							}
						}else{
							if (isset($document[$document_value['dkey']]) && strpos(strtolower($document[$document_value['dkey']]),$search_string) !== false ) {
								$form_state->setErrorByName("document][$i][".$error_field_type, $this->t("'$search_string' value is already exist in a document."));
								break;
							}
						}
					}
				}
			}
		}
		$i++;
	}
}

/*
* {@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {	
	global $base_url;

	$mongodb_collection = $_SESSION["data_mongodb_collection"];
	$webform_id = $_SESSION["data_webform_id"];
	$document_id = $_SESSION["data_document_id"];
	
	$updateWith = "{";
	$document_values = $form_state->getValue("document");

	$email_id = "";
	 foreach($document_values as $document_value)
	 {
		if(isset($document_value['document']) && count($document_value['document']) > 0){
			$updateWith .= '"' . $document_value['dkey'] . '":{';
			$updateWith .= addsublevel_submit($document_value['document']);
			$updateWith .= "},";
		}else{
			if (isset($document_value['dvalue'])) {
				 if ($document_value['dvalue'] != "") {
					$updateWith .= '"' . $document_value['dkey'] . '":"' . $document_value['dvalue'] . '",';
				 }
			}
					 
			if (isset($document_value['names_fieldset'])) {
				 if ($document_value['names_fieldset'] != "") {
					$dval_txt = '';
					foreach($document_value['names_fieldset']['dvalue'] as $dvalue){
						$dval_txt .= '"'.$dvalue.'",';
					}
					$updateWith .= '"' . $document_value['dkey'] . '":[' . rtrim($dval_txt,",") . '],';
				 }
			}
			 
			 if (isset($document_value['checkbox'])) {
				 if ($document_value['checkbox'] == 1) {
						$updateWith .= '"' . $document_value['dkey'] . '":true,';
				 }
				 
				 if ($document_value['checkbox'] == 0) {
						$updateWith .= '"' . $document_value['dkey'] . '":false,';
				 }
			 }
			 
			 if (isset($document_value['select'])) {
				 if(!empty($document_value['select'])){
					 if(is_array($document_value['select'])){
						$selected_value = '';
						foreach($document_value['select'] as $select_val){
							$selected_value .= '"' . $select_val.'",';
						}
						$updateWith .= '"' . $document_value['dkey'] . '":[' . rtrim($selected_value,",") . '],';
					 }else{
						$updateWith .= '"' . $document_value['dkey'] . '":"' . $document_value['select'] . '",';
					 }
				 }else{
					$updateWith .= '"' . $document_value['dkey'] . '":"",';
				 }
			 }
				 
			 if (isset($document_value['radios'])) {
				 if ($document_value['radios'] != "") {
					$updateWith .= '"' . $document_value['dkey'] . '":"' . $document_value['radios'] . '",';
				 }
			 }
			 
			 if (isset($document_value['relational'])) {
				 if(!empty($document_value['relational'])){
					 if(is_array($document_value['relational'])){
						$selected_value = '';
						foreach($document_value['relational'] as $select_val){
							$selected_value .= '"' . $select_val.'",';
						}
						$updateWith .= '"' . $document_value['dkey'] . '":[' . rtrim($selected_value,",") . '],';
					 }else{
						$updateWith .= '"' . $document_value['dkey'] . '":"' . $document_value['relational'] . '",';
					 }
				 }else{
					$updateWith .= '"' . $document_value['dkey'] . '":"",';
				 }
			 }
				 
			 if (isset($document_value['image'])) {
				if(is_array($document_value['image']) && $document_value['image_info'] == 1){
					$file_uri = '';
					if(!empty($document_value['image'])){
						foreach($document_value['image'] as $image){
						 
							// set file status permanent
							$con = \Drupal\Core\Database\Database::getConnection();
							$con->update('file_managed')
								->fields(['status' => 1])
								->condition('fid',$image,"=")
								->execute();

							// get file uri
							$file_source = \Drupal::database()->select("file_managed","f")
							->fields("f",array("uri"))
							->condition("fid",$image,"=")
							->execute()
							->fetchAssoc();
							$file_uri .= '"' . $file_source["uri"].'",';
						}
						$updateWith .= '"' . $document_value['dkey'] . '":[' . rtrim($file_uri,",") . '],';
					}else
						$updateWith .= '"' . $document_value['dkey'] . '":[],';
				}else{
					if(!empty($document_value['image'])){
						$file_source = array();
						// set file status permanent
						$con = \Drupal\Core\Database\Database::getConnection();
						$con->update('file_managed')
							->fields(['status' => 1])
							->condition('fid',$document_value['image'][0],"=")
							->execute();

						// get file uri
						$file_source = \Drupal::database()->select("file_managed","f")
									->fields("f",array("uri"))
									->condition("fid",$document_value['image'][0],"=")
									->execute()
									->fetchAssoc();
						$updateWith .= '"' . $document_value['dkey'] . '":"' . $file_source["uri"] . '",';
					}else
						$updateWith .= '"' . $document_value['dkey'] . '":"",';
				}
			 }
		}
	}
	$updateWith = substr($updateWith,0, strlen($updateWith)-1) . "}";
	
	if(!empty($form_state->getValue("doc_json"))){
		$existing_data = json_decode($form_state->getValue("doc_json"));
		$form_data = json_decode($updateWith);
		
		foreach($form_data as $key => $data){
			if(is_object($data)){
				$existing_data->$key = (object) subdoc_replace($data,$existing_data->$key);
			}else{
				$existing_data->$key = $data;
			}
		}
		
		$updateWith = json_encode($existing_data);
	}
		 
	if(!empty($document_id)){
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $mongodb_collection ."/update";
		 $api_param = array ( 
		    "query" => '{"_id":"'.$document_id.'"}', 
			"token" => $_SESSION['mongodb_token'], 
			"updateWith" => $updateWith
		);
	}else{
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $mongodb_collection ."/insert";
		$api_param = array ( 
			"token" => $_SESSION['mongodb_token'], 
			"document" => $updateWith
		);
	}
									 
	
	 $ch = curl_init();
	 curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
	 curl_setopt($ch, CURLOPT_POST, 1);
	 curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
	 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	 $server_output = curl_exec ($ch);
	 curl_close ($ch);
		 
	drupal_set_message($server_output);
	if(!empty($document_id))
		 drupal_set_message("Changes updated successfully");
	else
		drupal_set_message("New data added successfully");
		
	$current_user = \Drupal::currentUser();
	$roles = $current_user->getRoles();	
	if(in_array("datauser",$roles))
		$redirect_url = $base_url . '/dataformsdocument?webform_id='.$webform_id;
	else
		$redirect_url = $base_url . '/mongodb_api/listdataformdocument?mongodb_collection='.$mongodb_collection."&webform_id=".$webform_id;
		
	  $response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
	  $response->send();
	  return;
  }  
  
  
}

function sublevel_validation($document_values, $parent_key, $error_element, FormStateInterface $form_state){
	$mongodb_collection = $_SESSION["data_mongodb_collection"];
	$document_id = $_SESSION["data_document_id"];
	$j=0;
	
	foreach($document_values as $document_value){
		if(isset($document_value['document']) && count($document_value['document']) > 0){
			$sub_error_element = $error_element."[document][$j]";
			sublevel_validation($document_value['document'], $parent_key."##".$document_value['dkey'], $sub_error_element, $form_state);
			
		}else{
			if (isset($document_value['dunique'])) {
				if (isset($document_value['dvalue'])&& $document_value['dvalue'] != "") {
					
					if(isset($document_value['dvalue'])&& $document_value['dvalue'] != ""){
						$search_string = strtolower($document_value['dvalue']);
						$error_field_type = "dvalue";
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

					$documents = json_decode($server_output, true);
					$exist = 0;
					$mongo_value = '';
					foreach($documents as $document){
						
						// begin - walk to child element
						$parent_array = $document;
						if(strpos($parent_key,'##') !== false){
							$keys = explode("##",$parent_key);
							foreach($keys as $key){
								if(isset($parent_array[$key]))
									$parent_array = $parent_array[$key];
								else
									break;
							}
							if(isset($parent_array[$document_value['dkey']]))
								$mongo_value = strtolower($parent_array[$document_value['dkey']]);
						}else{
							if(isset($document[$parent_key][$document_value['dkey']]))
								$mongo_value = strtolower($document[$parent_key][$document_value['dkey']]);
						}
						// end
						
						if(!empty($document_id) && $document_id == $document["_id"]){
							if (!empty($mongo_value) && strpos($mongo_value,$search_string) !== false ) {
								$exist++;
							}
							if($exist == 2){
								$form_state->setErrorByName($error_element."[document][$j][".$error_field_type, "'$search_string' value is already exist in a document.");
								break;
							}
						}else{
							if (!empty($mongo_value) && strpos($mongo_value,$search_string) !== false ) {
								$form_state->setErrorByName($error_element."[document][$j][".$error_field_type, "'$search_string' value is already exist in a document.");
								break;
							}
						}
					}
				}
			}
		}
		$j++;
	}
}

function addsublevel_submit($document_values){
	
	$updateWith = '';
	foreach($document_values as $document_value)
	{
		if(isset($document_value['document']) && count($document_value['document']) > 0){
			$updateWith .= '"' . $document_value['dkey'] . '":{';
			$updateWith .= addsublevel_submit($document_value['document']);
			$updateWith .= "},";
		}else{
			 if (isset($document_value['dvalue'])) {
				 if ($document_value['dvalue'] != "") {
					$updateWith .= '"' . $document_value['dkey'] . '":"' . $document_value['dvalue'] . '",';
				 }
			 }
					 
			 if (isset($document_value['names_fieldset'])) {
				 if ($document_value['names_fieldset'] != "") {
					$dval_txt = '';
					foreach($document_value['names_fieldset']['dvalue'] as $dvalue){
						$dval_txt .= '"'.$dvalue.'",';
					}
					$updateWith .= '"' . $document_value['dkey'] . '":[' . rtrim($dval_txt,",") . '],';
				 }
			 } 
					 
			 if (isset($document_value['checkbox'])) {
				 if ($document_value['checkbox'] == 1) {
					$updateWith .= '"' . $document_value['dkey'] . '":true,';
				 }
				 
				 if ($document_value['checkbox'] == 0) {
					$updateWith .= '"' . $document_value['dkey'] . '":false,';
				 }
			 }
			 
			 if (isset($document_value['select'])) {
				 if(!empty($document_value['select'])){
					 if(is_array($document_value['select'])){
						$selected_value = '';
						foreach($document_value['select'] as $select_val){
							$selected_value .= '"' . $select_val.'",';
						}
						$updateWith .= '"' . $document_value['dkey'] . '":[' . rtrim($selected_value,",") . '],';
					 }else{
						$updateWith .= '"' . $document_value['dkey'] . '":"' . $document_value['select'] . '",';
					 }
				 }else{
					$updateWith .= '"' . $document_value['dkey'] . '":"",';
				 }
			 }
			 
			 if (isset($document_value['radios'])) {
				 if ($document_value['radios'] != "") {
					$updateWith .= '"' . $document_value['dkey'] . '":"' . $document_value['radios'] . '",';
				 }
			 }
			 
			 if (isset($document_value['relational'])) {
				 if(!empty($document_value['relational'])){
					 if(is_array($document_value['relational'])){
						$selected_value = '';
						foreach($document_value['relational'] as $select_val){
							$selected_value .= '"' . $select_val.'",';
						}
						$updateWith .= '"' . $document_value['dkey'] . '":[' . rtrim($selected_value,",") . '],';
					 }else{
						$updateWith .= '"' . $document_value['dkey'] . '":"' . $document_value['relational'] . '",';
					 }
				 }else{
					$updateWith .= '"' . $document_value['dkey'] . '":"",';
				 }
			 }
			 
			 if (isset($document_value['image'])) {
				if(is_array($document_value['image']) && $document_value['image_info'] == 1){
					if(!empty($document_value['image'])){
						$file_uri = '';
						foreach($document_value['image'] as $image){
						 
							// set file status permanent
							$con = \Drupal\Core\Database\Database::getConnection();
							$con->update('file_managed')
								->fields(['status' => 1])
								->condition('fid',$image,"=")
								->execute();

							// get file uri
							$file_source = \Drupal::database()->select("file_managed","f")
										->fields("f",array("uri"))
										->condition("fid",$image,"=")
										->execute()
										->fetchAssoc();
							$file_uri .= '"' . $file_source["uri"].'",';
						}
						$updateWith .= '"' . $document_value['dkey'] . '":[' . rtrim($file_uri,",") . '],';
					}else
						$updateWith .= '"' . $document_value['dkey'] . '":[],';
				}else{
					if(!empty($document_value['image'])){
						$file_source = array();
						// set file status permanent
						$con = \Drupal\Core\Database\Database::getConnection();
						$con->update('file_managed')
							->fields(['status' => 1])
							->condition('fid',$document_value['image'][0],"=")
							->execute();
				
						// get file uri
						$file_source = \Drupal::database()->select("file_managed","f")
							->fields("f",array("uri"))
									->condition("fid",$document_value['image'][0],"=")
							->execute()
							->fetchAssoc();
						$updateWith .= '"' . $document_value['dkey'] . '":"' . $file_source["uri"] . '",';
					}else
						$updateWith .= '"' . $document_value['dkey'] . '":"",';
				}
			 }
		}
	}
	
	return substr($updateWith,0, strlen($updateWith)-1);
}

function addsublevel($webform_elements, $json_result = array(), $form_state)
{
	$j=0;
	
	$webform_elements_keys = array_keys_multi($webform_elements);
	foreach ($webform_elements_keys as $field):
		
		if(isset($webform_elements[$field])){
			$form[$j]['dkey'] = array(
				'#type' => 'hidden',										
				'#default_value' => $field,						
			);
			
			$required_attr = '';
			if(isset($webform_elements[$field]["#required"]) && $webform_elements[$field]["#required"] == 1)
				$required_attr = 1;
			
			$field_label = (isset($webform_elements[$field]["#title"]) && !empty($webform_elements[$field]["#title"])) ? $webform_elements[$field]["#title"] : ucfirst($field);
			
			if($webform_elements[$field]["#type"] == "details"){
				$form[$j] = [
					'#type' => 'details',
					'#title' => $field,
					'#prefix' => '<div class="clearboth">',
					'#suffix' => '</div>',
					'#open' => TRUE,
				];
				
				// for "details field", again we need to add dkey
				$form[$j]['dkey'] = array(
					'#type' => 'hidden',										
					'#default_value' => $field,						
				);
				
				$form[$j]['document'] = addsublevel($field, $webform_elements[$field],(isset($json_result[$field])) ? $json_result[$field] : array(), $form_state);
				
			}else if($webform_elements[$field]["#type"] == "select"){
				$multiple_attr = '';
				if(isset($webform_elements[$field]["#multiple"]) && $webform_elements[$field]["#multiple"] == 1)
					$multiple_attr = 1;
				
				$form[$j]['select'] = array(
					'#type' => 'select',
					'#title' => $field_label,
					'#multiple' =>	$multiple_attr,
					'#required' =>	$required_attr,
					'#options' => $webform_elements[$field]["#options"],
					'#default_value' => (isset($json_result[$field])) ? $json_result[$field] : '',
				);
				
			}else if($webform_elements[$field]["#type"] == "radios"){
				$form[$j]['radios'] = array(			
					'#type' => 'radios',
					'#title' => $field_label,
					'#required' =>	$required_attr,
					'#options' => $webform_elements[$field]["#options"],
					'#default_value' => (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : '',
				);
			}else if($webform_elements[$field]["#type"] == "checkbox"){
				$checkbox_val = 0;
				if(isset($json_result[$field])){
					if($json_result[$field] == "TRUE")
						$checkbox_val = 1;
				}
				$form[$j]['checkbox'] = array(			
					'#type' => 'checkbox',
					'#title' => $field_label,
					'#default_value' => $checkbox_val,
					'#required' =>	$required_attr,
				);
			}else if($webform_elements[$field]["#type"] == "textarea"){
				$form[$j]['dvalue'] = array(			
					'#type' => 'textarea',				
					'#title' => $field_label,
					'#default_value' => (isset($json_result[$field])) ? $json_result[$field] : '',
					'#required' =>	$required_attr,
				);
			}else if($webform_elements[$field]["#type"] == "webform_image_file"){
				$multiple_attr = '';
				if(isset($webform_elements[$field]["#multiple"]) && $webform_elements[$field]["#multiple"] == 1)
					$multiple_attr = 1;
				
				if(isset($json_result[$field])){
					if(is_array($json_result[$field])){
						if(!empty($json_result[$field])){
							$fid = array();
							foreach($json_result[$field] as $image_uri){
								// get existing fid
								$isFile = \Drupal::database()->select("file_managed","f")
										->fields("f",array("fid"))
										->condition("uri",$image_uri,"=")
										->execute()
										->fetchAssoc();
								if(!empty($isFile))
									$fid[] = $isFile["fid"];
							}
						}
					}else{
						$fid = '';
						if(!empty($json_result[$field])){
							// get existing fid
							$isFile = \Drupal::database()->select("file_managed","f")
								->fields("f",array("fid"))
								->condition("uri",$json_result[$field],"=")
								->execute()
								->fetchAssoc();
							if(!empty($isFile))
								$fid = $isFile;
						}
					}
				}else{
					if($multiple_attr)
						$fid = array();
					else
						$fid = '';
				}
				$form[$j]['image'] = array(		
					'#type' => 'managed_file',				
					'#title' => $field_label,
					'#multiple' =>	$multiple_attr,
					'#upload_location' => 's3://'.date("Y-m"), /* s3://2018-04 */
					'#default_value' => $fid,
					'#required' =>	$required_attr,
				);
				
				$form[$j]['image_info'] = array(			
					'#type' => 'hidden',
					'#default_value' => $multiple_attr,
				);
			}else if($webform_elements[$field]["#type"] == "element"){
				$multiple_attr = '';
				if(isset($webform_elements[$field]["#multiple"]) && $webform_elements[$field]["#multiple"] == 1)
					$multiple_attr = 1;
				
				$coll_rel = CollectionRelations::load($webform_elements[$field]["#entity_id"]);
				$rel_collection = $coll_rel->field_relative_collection->value;
				$rel_key 		= $coll_rel->field_relative_key->value;
				if (strpos($rel_key, '###') !== false) {
					$rel_keys = explode("###",$rel_key);
					$rel_key = $rel_keys[count($rel_keys)-1];
				}
				$rel_value 		= $coll_rel->field_relative_value->value;
				if (strpos($rel_value, '###') !== false) {
					$rel_values = explode("###",$rel_value);
					$rel_value = $rel_values[count($rel_values)-1];
				}
				
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/". $rel_collection."/find";
				$api_param = array ( "token" => $_SESSION['mongodb_token']);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$document_lists = curl_exec ($ch);		
				curl_close ($ch);
				$documents = json_decode($document_lists, true);

				if($webform_elements[$field]["#field_type"] == 'select'){
				$relative_options = array('' => 'Select');
				foreach($documents as $document){
					if(isset($document[$rel_value]))
					$relative_options[$document[$rel_key]] = $document[$rel_value];
				}
				$form[$j]['relational'] = array(			
					'#type' => 'select',
					'#title' => $field_label,
					'#multiple' =>	$multiple_attr,
					'#required' =>	$required_attr,
					'#options' => $relative_options,
					'#default_value' => (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : ''
				);
				}else{
					$relative_options = array();
					foreach($documents as $document){
						if(isset($document[$rel_value]))
							$relative_options[$document[$rel_key]] = $document[$rel_value];
					}
					$form[$j]['relational'] = array(			
						'#type' => 'radios',
						'#title' => $field_label,
						'#options' => $relative_options,
						'#required' =>	$required_attr,
						'#default_value' => (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : ''
					);
				}
			}else{
				
				$multiple_attr = '';
				if(isset($webform_elements[$field]["#multiple"]) && $webform_elements[$field]["#multiple"] == 1)
					$multiple_attr = 1;
				
				$text_field_type = 'textfield';
				$attributes_array = [];
				if(!empty($webform_elements[$field]["#text_field_type"])){
					$text_field_type =  $webform_elements[$field]["#text_field_type"];
					if($text_field_type = "email")
						$attributes_array["pattern"] = "[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$";
				}
				
				if($multiple_attr){
					// Gather the number of names in the form already.
					$sub_num_names = $form_state->get('sub_num_names_'.$j);
					// We have to ensure that there is at least one name field.
					if ($sub_num_names === NULL) {
						if(isset($json_result[$field]) && count($json_result[$field]) > 0){
							$name_field = $form_state->set('sub_num_names_'.$j, count($json_result[$field]));
							$sub_num_names = count($json_result[$field]);
						}else{
							$name_field = $form_state->set('sub_num_names_'.$j, 1);
							$sub_num_names = 1;
						}
					}
					
					$form[$j]['names_fieldset'] = [
					  '#type' => 'fieldset',
					  '#prefix' => '<div id="names-fieldset-wrapper-'.$j.'">',
					  '#suffix' => '</div>',
					];
					
					$text_field_value = array();
					for ($l = 0; $l < $sub_num_names; $l++) {
						if(isset($json_result[$field]) && !empty($json_result[$field])){
							if(!empty($webform_elements[$field]["#text_field_type"]) && $text_field_type == "datetime"){
								if(is_array($json_result[$field])){
									$text_field_value[$l] = '';
									$timestamp = strtotime($json_result[$field][$l]);
									if(!empty($timestamp))
										$text_field_value[$l] = DrupalDateTime::createFromTimestamp($timestamp);
								}else{
									$text_field_value[0] = '';
									$timestamp = strtotime($json_result[$field]);
									if(!empty($timestamp))
										$text_field_value[0] = DrupalDateTime::createFromTimestamp($timestamp);
								}
							}else{
								if(is_array($json_result[$field])){
									$text_field_value[$l] = $json_result[$field][$l];
								}else{
									$text_field_value[0] = $json_result[$field];
								}
							}
						}
						else
							$text_field_value[$l] = '';
						
						$form[$j]['names_fieldset']['dvalue'][$l] = array(
							'#type' => $text_field_type,
							'#title' => $field_label,
							'#required' =>	$required_attr,
							'#default_value' => $text_field_value[$l],
							'#attributes' => $attributes_array
						);
							
						if(!empty($webform_elements[$field]["#text_field_type"])){
							if($text_field_type == "number" || $text_field_type == "float"){
								if($text_field_type == "number")
									$form[$j]['names_fieldset']['dvalue'][$l]["#step"] = 1;
								else
									$form[$j]['names_fieldset']['dvalue'][$l]["#step"] = "any";
								$form[$j]['names_fieldset']['dvalue'][$l]["#type"] = "number";
							}
						}
					}
					
					$form[$j]['names_fieldset']['actions'] = [
					  '#type' => 'actions',
					];
					$form[$j]['names_fieldset']['actions']['add_name'] = [
					  '#type' => 'submit',
					  '#name' => 'add_one_'.$j,
					  '#value' => t('Add one more'),
					  '#submit' => ['::addSubOne'],
					  '#ajax' => [
						'callback' => '::addmoreSubCallback',
						'wrapper' => 'names-fieldset-wrapper-'.$j,
					  ],
					];
					// If there is more than one name, add the remove button.
					if ($sub_num_names > 1) {
					  $form[$j]['names_fieldset']['actions']['remove_name'] = [
						'#type' => 'submit',
						'#name' => 'remove_one_'.$j,
						'#value' => t('Remove one'),
						'#submit' => ['::removeSubCallback'],
						'#ajax' => [
						  'callback' => '::addmoreSubCallback',
						  'wrapper' => 'names-fieldset-wrapper-'.$j,
						],
					  ];
					}
					
				}else{
					$text_field_value = (isset($json_result) && isset($json_result[$field])) ? $json_result[$field] : '';
					if(!empty($webform_elements[$field]["#text_field_type"]) && $text_field_type == "datetime"){
						if(isset($json_result) && isset($json_result[$field])){
							$text_field_value = '';
							$timestamp = strtotime($json_result[$field]);
							if(!empty($timestamp))
								$text_field_value = DrupalDateTime::createFromTimestamp($timestamp);
						}
					}
					
					if(isset($webform_elements[$field]["#unique"]) && $webform_elements[$field]["#unique"] == 1){
						$form[$j]['dunique'] = array(
							'#type' => 'hidden',
							'#default_value' => 1,
						);
					}
				
					$form[$j]['dvalue'] = array(
						'#type' => $text_field_type,
						'#title' => $field_label,
						'#required' =>	$required_attr,
						'#default_value' => $text_field_value,
						'#attributes' => $attributes_array
					);
					
					if(!empty($webform_elements[$field]["#text_field_type"])){
						if($text_field_type == "number" || $text_field_type == "float"){
							if($text_field_type == "number")
								$form[$j]['dvalue']["#step"] = 1;
							else
								$form[$j]['dvalue']["#step"] = "any";
							$form[$j]['dvalue']["#type"] = "number";
						}
					}
				}
			}
			$j++;
		}
	endforeach;	 

	return $form;
}

function subdoc_replace($values,$exist_data){
	
	foreach($values as $key => $data){
		if(is_object($data)){
			$exist_data->$key = (object) subdoc_replace($data,$exist_data->$key);
		}else
			$exist_data->$key = $data;
	}
	
	return $exist_data;
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