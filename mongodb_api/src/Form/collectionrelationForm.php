<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\collection_relations\Entity\CollectionRelations;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AlertCommand;

class collectionrelationForm extends FormBase {

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'collection_relationform';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		global $base_url;
		checkConnectionStatus();
		
		if ($_SESSION['mongodb_token'] != ""){					
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
		
		$source_collection = $relative_collection = $source_key = $relative_key = $relative_value = '';
		if(isset($_GET["coll_ref"])){
			$coll_rel = CollectionRelations::load($_GET["coll_ref"]);
			$source_collection = $coll_rel->field_source_collection->value;
			$relative_collection = $coll_rel->field_relative_collection->value;
			$source_key = $coll_rel->field_source_key->value;
			$relative_key = $coll_rel->field_relative_key->value;
			$relative_value = $coll_rel->field_relative_value->value;
		}
		
		$form['coll_set1_start'] = [
			'#markup' => '<div class="collections-set">'
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
				'callback' => '::getKeyList',
			],
			'#prefix' => '<div id="source_collection">',
			'#suffix' => '</div>'
		];
		
		if(!empty($source_collection)){
			$source_collection_options = $source_collection;
		}
		if(!empty($form_state->getValue("source_collection"))){
			$source_collection_options = $form_state->getValue("source_collection");
		}
		$form['source_key'] = [
			'#type' => 'select',
			'#title' => t('Source key'),
			'#required' => TRUE,
			'#options' => getKeyArray($source_collection_options),
			'#empty_option' => $this->t('Select'),
			'#default_value' => !empty($form_state->getValue("source_key")) ? $form_state->getValue("source_key") : $source_key,
			'#prefix' => '<div id="source_key">',
			'#suffix' => '</div>',
			'#validated' => TRUE
		];
		
		$form['coll_set1_end'] = [
			'#markup' => '</div>'
		];
		
		$form['coll_set2_start'] = [
			'#markup' => '<div class="collections-set">'
		];
		
		$form['relative_collection'] = [
			'#type' => 'select',
			'#name' => 'relative_collection',
			'#title' => t('Relative Collection'),
			'#required' => TRUE,
			'#options' => $collection_list,
			'#empty_option' => $this->t('Select'),
			'#default_value' => !empty($form_state->getValue("relative_collection")) ? $form_state->getValue("relative_collection") : $relative_collection,
			'#ajax' => [
				'callback' => '::getKeyList',
			],
			'#prefix' => '<div id="relative_collection">',
			'#suffix' => '</div>'
		];
		
		if(!empty($relative_collection)){
			$relative_collection_options = $relative_collection;
		}
		if(!empty($form_state->getValue("relative_collection"))){
			$relative_collection_options = $form_state->getValue("relative_collection");
		}
		$form['relative_key'] = [
			'#type' => 'select',
			'#title' => t('Relative key'),
			'#required' => TRUE,
			'#options' => getKeyArray($relative_collection_options),
			'#empty_option' => $this->t('Select'),
			'#default_value' => !empty($form_state->getValue("relative_key")) ? $form_state->getValue("relative_key") : $relative_key,
			'#prefix' => '<div id="relative_key">',
			'#suffix' => '</div>',
			'#validated' => TRUE
		];
		
		$form['relative_value'] = [
			'#type' => 'select',
			'#title' => t('Relative value'),
			'#required' => TRUE,
			'#options' => getKeyArray($relative_collection_options),
			'#empty_option' => $this->t('Select'),
			'#default_value' => !empty($form_state->getValue("relative_value")) ? $form_state->getValue("relative_value") : $relative_value,
			'#prefix' => '<div id="relative_value">',
			'#suffix' => '</div>',
			'#validated' => TRUE
		];
		
		$form['coll_set2_end'] = [
			'#markup' => '</div>'
		];
		
		$form['coll_ref'] = [
			'#type' => 'hidden',
			'#value' => isset($_GET["coll_ref"]) ? $_GET["coll_ref"] : ''
		];
		
		$form['submit'] = [
			'#type' => 'submit',
			'#value' => t('Save Changes')
		];
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
		
		$key_array = getKeyArray($collection);
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
	
	public function validateForm(array &$form, FormStateInterface $form_state) {
		
		$form_values = $form_state->getValues();
		$query = \Drupal::entityQuery('collection_relations')
			->condition('status', 1)
			->condition('field_source_collection', trim($form_values["source_collection"]), '=')
			->condition('field_relative_collection', trim($form_values['relative_collection']), '=')
			->condition('field_source_key', trim($form_values['source_key']), '=')
			->condition('field_relative_key', trim($form_values['relative_key']), '=')
			->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'], '=');
			//->condition('field_relative_value', trim($form_values['relative_value']), '=');
		$coll_ids = $query->execute();
		
		if(!empty($coll_ids)){
			if($form_values["coll_ref"] != array_keys($coll_ids)[0])
				$form_state->setErrorByName('source_collection', $this->t('This combination of relationship is already exist.'));
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		
		global $base_url;
		$form_values = $form_state->getValues();
		
		if(!empty($form_values["coll_ref"])){
			$coll_relation = CollectionRelations::load($form_values["coll_ref"]);
			$coll_relation->set('field_source_collection',trim($form_values["source_collection"]));
			$coll_relation->set('field_relative_collection',trim($form_values['relative_collection']));
			$coll_relation->set('field_source_key',trim($form_values['source_key']));
			$coll_relation->set('field_relative_key',trim($form_values['relative_key']));
			$coll_relation->set('field_relative_value',trim($form_values['relative_value']));
			$coll_relation->set('field_mongodb_connection_ref',$_SESSION['mongodb_nid']);
			$coll_relation->save();
		}else{
			$coll_relation = CollectionRelations::create([
			   'field_source_collection' => trim($form_values["source_collection"]),
			   'field_relative_collection' => trim($form_values['relative_collection']),
			   'field_source_key' => trim($form_values['source_key']),
			   'field_relative_key' => trim($form_values['relative_key']),
			   'field_relative_value' => trim($form_values['relative_value']),
			   'field_mongodb_connection_ref' => $_SESSION['mongodb_nid']
			]);  
			$coll_relation->save();
		}
	
		drupal_set_message(t("Changes saved Successfully."));
		$redirect_url = $base_url.'/mongodb_api/collectionrelation';
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
	}
}

function getKeyArray($collection){
	
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $collection ."/find";
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
	
	$key_array = array();
	if(!empty($json_result)){
		foreach($json_result as $resultkey => $resultValue){
			//if($resultkey != "_id"){
				if (is_array($resultValue) && is_associative($resultValue)){
				$sub_key_arrays = nestedkeylevel($resultkey, $resultValue);
				$sub_arrays = explode("##",rtrim($sub_key_arrays,"##"));
				foreach($sub_arrays as $sub_array){
						$key_array[$sub_array] = $sub_array;
					}
				} else {					
					$key_array[$resultkey] = $resultkey;
				}
			//}
		}
	}
	
	return $key_array;
}

function nestedkeylevel($subKey, $subResultValue){
	$array_index = '';
	foreach($subResultValue as $key => $value){
		if (is_array($value) && is_associative($value)){
			$array_index .= nestedkeylevel($subKey.'.'.$key, $value);
		} else {					
			$array_index .= $subKey.'.'.$key."##";
		}
	}
	
	return $array_index;
}

function is_associative($a) {
	foreach(array_keys($a) as $key)
		if (!is_int($key))
			return TRUE;
	return FALSE;
}