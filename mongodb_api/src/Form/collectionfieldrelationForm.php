<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\collection_relations\Entity\CollectionRelations;
use Drupal\collection_field_relation\Entity\CollectionFieldRelation;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AlertCommand;

class collectionfieldrelationForm extends FormBase {

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'collection_field_relation_form';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		global $base_url;
		checkConnectionStatus();
		
		if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != ""){					
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections";

			$api_param = array ( "token" => $_SESSION['mongodb_token']);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);		
			curl_close ($ch);
			$json_result = json_decode($server_output, true);	
						
			$collection_list = array();
			if (count ($json_result) > 0 ) {		
				foreach($json_result as $result):			
					$collection_list[$result['name']] = $result['name'];					
				endforeach;				
			}

			// field relation
			if((isset($_GET["add"]) && $_GET["add"] == "field") || isset($_GET["field_rel"])){
				$source_collection = $category_key = $sub_category_key = '';
				if(isset($_GET["field_rel"]) && $_GET["field_rel"] != ""){
					$field_rel = CollectionFieldRelation::load($_GET["field_rel"]);
					$source_collection = $field_rel->field_collection_name->value;
					$category_key = $field_rel->field_category_key->value;
					$sub_category_key = $field_rel->field_sub_category_key->value;
				}
				
				$form['coll_set_start'] = [
					'#markup' => '<div class="collections-field-set">'
				];
				
				$form['source_collection'] = [
					'#type' => 'select',
					'#name' => 'source_collection',
					'#title' => t('Source Collection'),
					'#required' => TRUE,
					'#options' => $collection_list,
					'#empty_option' => $this->t('Select'),
					'#default_value' => !empty($form_state->getValue("source_collection")) ? $form_state->getValue("source_collection") : $source_collection,
					'#ajax' => [
						'callback' => '::getKeyListForField',
					],
					'#prefix' => '<div id="source_collection">',
					'#suffix' => '</div>'
				];
				
				$form['coll_set_end'] = [
					'#markup' => '</div>'
				];
				
				$source_collection_options = '';
				if(!empty($source_collection)){
					$source_collection_options = $source_collection;
				}
				if(!empty($form_state->getValue("source_collection"))){
					$source_collection_options = $form_state->getValue("source_collection");
				}
				
				$form['coll_set1_start'] = [
					'#markup' => '<div class="collections-set">'
				];
				
				$form['category_key'] = [
					'#type' => 'select',
					'#title' => t('Category Key'),
					'#required' => TRUE,
					'#options' => getFieldKeyArray($source_collection_options),
					'#empty_option' => $this->t('Select'),
					'#default_value' => !empty($form_state->getValue("category_key")) ? $form_state->getValue("category_key") : $category_key,
					'#prefix' => '<div id="category_key">',
					'#suffix' => '</div>',
					'#validated' => TRUE
				];
				
				$form['coll_set1_end'] = [
					'#markup' => '</div>'
				];
				
				$form['coll_set2_start'] = [
					'#markup' => '<div class="collections-set">'
				];
				
				$form['sub_category_key'] = [
					'#type' => 'select',
					'#title' => t('Subcategory Key'),
					'#required' => TRUE,
					'#options' => getFieldKeyArray($source_collection_options),
					'#empty_option' => $this->t('Select'),
					'#default_value' => !empty($form_state->getValue("sub_category_key")) ? $form_state->getValue("sub_category_key") : $sub_category_key,
					'#prefix' => '<div id="sub_category_key">',
					'#suffix' => '</div>',
					'#validated' => TRUE
				];
				
				$form['coll_set2_end'] = [
					'#markup' => '</div>'
				];
				
				$form['field_rel'] = [
					'#type' => 'hidden',
					'#value' => isset($_GET["field_rel"]) ? $_GET["field_rel"] : ''
				];
			}
			
			if(isset($_GET["add"]) || isset($_GET["field_rel"])){
				$form['submit'] = [
					'#type' => 'submit',
					'#value' => t('Save Changes')
				];
			}else{
				$form['notice'] = [
					'#markup' => "<BR><BR>Please select a <a href='" . $base_url . "/mongodb_api/collectionrelation' alt='Collection Relation' title='Collection Field Relation'>Collection Relation</a>"
				];
			}
		}else{
			$form['notice'] = [
				'#markup' => "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>"
			];
		}
		
		return $form;
	}
	
	public function getKeyList(array &$form, FormStateInterface $form_state) {
		
		$ajax_response = new AjaxResponse();
		$triggering_element = $form_state->getTriggeringElement()["#name"];
		
		if($triggering_element == "source_collection"){
			$collection = $form_state->getValue("source_collection");
			if($form_state->getValue("source_collection") == $form_state->getValue("relative_collection")){
				$form["source_collection"]["#default_value"] = '';
				$ajax_response->addCommand(new ReplaceCommand('#source_collection',$form["source_collection"]));
				
				$form["source_key"]["#options"] = array();
				$ajax_response->addCommand(new ReplaceCommand('#source_key',$form["source_key"]));
				
				$ajax_response->addCommand(new AlertCommand(t("Please choose different collection name!")));
				return $ajax_response;
			}
		}else{
			$collection = $form_state->getValue("relative_collection");
			if($form_state->getValue("relative_collection") == $form_state->getValue("source_collection")){
				$form["relative_collection"]["#default_value"] = '';
				$ajax_response->addCommand(new ReplaceCommand('#relative_collection',$form["relative_collection"]));
	
				$form["relative_key"]["#options"] = array();
				$ajax_response->addCommand(new ReplaceCommand('#relative_key',$form["relative_key"]));
				$form["relative_value"]["#options"] = array();
				$ajax_response->addCommand(new ReplaceCommand('#relative_value',$form["relative_value"]));
	
				$ajax_response->addCommand(new AlertCommand(t("Please choose different collection name!")));
				return $ajax_response;
			}
		}
		
		$key_array = getFieldKeyArray($collection);
		if($triggering_element == "source_collection"){
			$form["source_key"]["#options"] = $key_array;
			$ajax_response->addCommand(new ReplaceCommand('#source_key',$form["source_key"]));
		}else{
			$form["relative_key"]["#options"] = $key_array;
			$ajax_response->addCommand(new ReplaceCommand('#relative_key',$form["relative_key"]));
			$form["relative_value"]["#options"] = $key_array;
			$ajax_response->addCommand(new ReplaceCommand('#relative_value',$form["relative_value"]));
		}	 

		return $ajax_response;
	}
	
	public function getKeyListForField(array &$form, FormStateInterface $form_state) {
		
		$ajax_response = new AjaxResponse();
		$collection = $form_state->getValue("source_collection");
		
		$key_array = getFieldKeyArray($collection);
		$form["category_key"]["#options"] = $key_array;
		$ajax_response->addCommand(new ReplaceCommand('#category_key',$form["category_key"]));
		$form["sub_category_key"]["#options"] = $key_array;
		$ajax_response->addCommand(new ReplaceCommand('#sub_category_key',$form["sub_category_key"]));

		return $ajax_response;
	}
	
	public function validateForm(array &$form, FormStateInterface $form_state) {
		
		$form_values = $form_state->getValues();		
		// field relation
		if((isset($_GET["add"]) && $_GET["add"] == "field") || isset($_GET["field_rel"])){
			$query = \Drupal::entityQuery('collection_field_relation')
				->condition('status', 1)
				->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'], '=')
				->condition('field_collection_name', trim($form_values["source_collection"]), '=')
				->condition('field_category_key', trim($form_values['category_key']), '=')
				->condition('field_sub_category_key', trim($form_values['sub_category_key']), '=');
			$coll_ids = $query->execute();
			
			if(!empty($coll_ids)){
				if($form_values["field_rel"] != array_keys($coll_ids)[0])
					$form_state->setErrorByName('source_collection', $this->t('This combination of relationship is already exist.'));
			}
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		
		global $base_url;
		$form_values = $form_state->getValues();
		// field relation
		if((isset($_GET["add"]) && $_GET["add"] == "field") || isset($_GET["field_rel"])){
			if(!empty($form_values["field_rel"])){
				$field_relation = CollectionFieldRelation::load($form_values["field_rel"]);
				$field_relation->set('field_mongodb_connection_ref',$_SESSION['mongodb_nid']);
				$field_relation->set('field_collection_name',trim($form_values["source_collection"]));
				$field_relation->set('field_category_key',trim($form_values['category_key']));
				$field_relation->set('field_sub_category_key',trim($form_values['sub_category_key']));
				$field_relation->save();
			}else{
				$field_relation = CollectionFieldRelation::create([
				   'field_mongodb_connection_ref' => $_SESSION['mongodb_nid'],
				   'field_collection_name' => trim($form_values["source_collection"]),
				   'field_category_key' => trim($form_values['category_key']),
				   'field_sub_category_key' => trim($form_values['sub_category_key'])
				]);  
				$field_relation->save();
			}
		}
	
		drupal_set_message(t("Changes saved Successfully."));
		$redirect_url = $base_url.'/mongodb_api/collectionrelation';
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
	}
}

function getFieldKeyArray($collection){	

/* $api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $collection ."/find";
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
	$json_result = $new_json;
	
	$key_array = array('' => t('Select'));
	if(!empty($json_result)){
		foreach($json_result as $resultkey => $resultValue){
			//if($resultkey != "_id"){
				if (is_array($resultValue) && field_is_associative($resultValue)){
					$sub_key_arrays = fieldnestedkeylevel($resultkey, $resultValue);
					$sub_arrays = explode("$$$",rtrim($sub_key_arrays,"$$$"));
					foreach($sub_arrays as $sub_array){
						$key_array[$sub_array] = str_replace("###",".",$sub_array);
					}
				} else {					
					$key_array[$resultkey] = $resultkey;
				}
			//}
		}
	} */
	
	$key_array = [];
	if(!empty($collection)){
		$query = \Drupal::entityQuery('collection_relations')
			->condition('status', 1)
			->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'], '=')
			->condition('field_source_collection', trim($collection), '=');	
		$coll_ids = $query->execute();
				
		if(!empty($coll_ids)){
			foreach($coll_ids as $coll_id){
				$coll_rel = CollectionRelations::load($coll_id);
				$key_array[$coll_rel->field_source_key->value] = str_replace("###",".",$coll_rel->field_source_key->value);
			}
		}
	}
	
	return $key_array;
}

function fieldnestedkeylevel($subKey, $subResultValue){
	$array_index = '';
	foreach($subResultValue as $key => $value){
		if (is_array($value) && field_is_associative($value)){
			$array_index .= fieldnestedkeylevel($subKey.'$$$'.$key, $value);
		} else {					
			$array_index .= $subKey.'###'.$key."$$$";
		}
	}
	
	return $array_index;
}

function field_is_associative($a) {
	foreach(array_keys($a) as $key)
		if (!is_int($key))
			return TRUE;
	return FALSE;
}