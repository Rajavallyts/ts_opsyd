<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class keyupdateForm extends FormBase{
	/**
	* {@inheritdoc}
	*/
	public function getFormId(){
		return 'key_update';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state){
		global $base_url;
		checkConnectionStatus();
		if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {
		
			if (isset($_GET['mongodb_collection'])) {
				$document_id = $_GET['document_id'];
				$collection_name = $_GET['mongodb_collection'];
	$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $collection_name ."/findByID";		  
				$api_param = array ( "token" => $_SESSION['mongodb_token'], "id" => $document_id);
										 
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$server_output = curl_exec ($ch);		
				curl_close ($ch);	
				$json_result = json_decode($server_output, true);
			}

			$i=0;

			$form['#tree'] = TRUE;

			$form['document'] = [
			'#type' => 'fieldset',
			'#title' => $this->t(' Existing Key - New Key '),
			'#prefix' => "<div>",
			'#suffix' => '</div>',
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
			];
			
			if(!empty($json_result)){
				foreach($json_result as $resultkey => $resultValue){
					if (($resultkey != "_id")) {								

						if (is_array($resultValue) && is_asso($resultValue)){
							$form['document'][$i] = array(
								'#type' => 'details',
								'#title' => $resultkey ,
								'#prefix' => '<div class="clearboth">',
								'#suffix' => '</div>',
								'#open' => TRUE,
							);
							
							$form['document'][$i]['edit'] = array(
								'#markup' => "<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='".$base_url."/mongodb_api/parentkeyupdate?mongodb_collection=".$collection_name."&document_id=".$document_id."&key=".$resultkey."'>edit ".$resultkey."</a>"
							);
							
							$form['document'][$i]['key'] = array(
								'#type' => 'hidden',
								'#default_value' => $resultkey,						
							);

							$form['document'][$i]['document'] = add_sublevel($resultkey, $resultkey, $resultValue);
						} else {
					
						$form['document'][$i]['key'] = array(
							'#type' => 'textfield',      
							'#required' => FALSE,
							'#default_value' => $resultkey,	 
							'#class' => 'value-field',
								'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;','disabled' => 'disabled'),  
							'#prefix' => '<div class="clearboth">',
							'#size' => 2000,
						);
						
						$form['document'][$i]['valuee'] = array(
							'#type' => 'textfield',      
							'#required' => FALSE,	  	  
							'#class' => 'value-field',	  
							'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),				
							'#suffix' => '</div><br>',
							'#size' => 2000,
						);	
						}
						
						$i++;
					}
				}
			
			}
			
			$form_state->setCached(FALSE);

			$form['submit'] = [
			  '#type' => 'submit',
			  '#value' => t('Save Changes'),
			  '#name' => 'save_changes',
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
	public function submitForm(array &$form, FormStateInterface $form_state){
		global $base_url;
		
		if (isset($_GET['mongodb_collection'])) {

			$updateWith = "{";
			$document_values = $form_state->getValue("document");
			
			foreach($document_values as $document_value)
			{
				$doc_key = $document_value['key'];
				if(isset($document_value['document']) && count($document_value['document']) > 0){
					$updateWith .= add_sublevel_submit($doc_key,$document_value['document']);
				}else{					
					if (isset($document_value['valuee']) && $document_value['valuee'] != "") {
						$updateWith .= '"' . $doc_key . '":"' . $document_value['valuee'] . '",';
					}
				}
			}
			$updateWith = substr($updateWith,0, strlen($updateWith)-1) . "}";		 
			
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $_GET['mongodb_collection'] ."/updateKeys";		  
			$api_param = array ( 
								"token" => $_SESSION['mongodb_token'],
								"query" => '{"_id":"'.$_GET['document_id'].'"}',
								"updateWith" => $updateWith,
							);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);
			drupal_set_message("Changes saved successfully");
			curl_close ($ch);	
		}
		//drupal_set_message($server_output);
		$redirect_url = $base_url.'/mongodb_api/managedocument?mongodb_collection='.$_GET['mongodb_collection'].'&document_id='.$_GET['document_id'];
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
	}
}

function add_sublevel($resultKey, $parentKey, $resultValue)
{
	global $base_url;
	$document_id = $_GET['document_id'];
	$collection_name = $_GET['mongodb_collection'];
	$j=0;

	foreach($resultValue as $key => $value):		
		if (is_array($value) && is_asso($value)) {

			$form[$j] = array(
				'#type' => 'details',
				'#title' => $key,
				'#prefix' => '<div class="clearboth">',
				'#suffix' => '</div>',			
				'#open' => TRUE,
			);

			$form[$j]['edit'] = array(
				'#markup' => "<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='".$base_url."/mongodb_api/parentkeyupdate?mongodb_collection=".$collection_name."&document_id=".$document_id."&key=".$parentKey."___".$key."'>edit ".$key."</a>",
			);

			$form[$j]['key'] = array(
				'#type' => 'hidden',
				'#default_value' => $key,						
			);
			
			$form[$j]['document'] = add_sublevel($key, $parentKey."___".$key, $value);
		} else {
			$form[$j]['key'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,
				'#default_value' => $key,	 
				'#class' => 'value-field',
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;','disabled' => 'disabled'),  
				'#prefix' => '<div class="clearboth">',
				'#size' => 2000,
			);

			$form[$j]['valuee'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',	  
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),				
				'#suffix' => '</div><br>',
				'#size' => 2000,
			);
		}
		$j++;	
	endforeach;	  

	return $form;

}

function add_sublevel_submit($key, $document_values){
	$updateWith = '';
	foreach($document_values as $document_value)
	{
		$doc_key = $document_value['key'];
		
		if(isset($document_value['document']) && count($document_value['document']) > 0){
			$updateWith .= add_sublevel_submit($key.'.'.$doc_key,$document_value['document']);
		}else{
			if (isset($document_value['valuee']) && $document_value['valuee'] != "") {
				$updateWith .= '"'.$key.".".$doc_key . '":"'.$key.".". $document_value['valuee'] . '",';
			}
		}
	}
	return $updateWith;
}
  
function is_asso($a) {
	foreach(array_keys($a) as $key)
		if (!is_int($key))
			return TRUE;
	return FALSE;
}