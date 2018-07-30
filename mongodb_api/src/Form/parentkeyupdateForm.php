<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class parentkeyupdateForm extends FormBase{
	/**
	* {@inheritdoc}
	*/
	public function getFormId(){
		return 'parentkey_update';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state){
		
		global $base_url;
		checkConnectionStatus();
		if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {
			$document_id = $_GET['document_id'];
			$collection_name = $_GET['mongodb_collection'];
			
			$key = $_GET['key'];
			
			if (strpos($key, '___') !== false) {
				$keyparts = explode("___",$key);
			}
			
			$cur_key = isset($keyparts) ? $keyparts[ count($keyparts)-1 ] : $key;
			
			$form['api_result'] = array (
				'#type' => 'markup',
				'#markup' => "<b><a href='".$base_url."/mongodb_api/listdocument?mongodb_collection=".$collection_name."' target='_self'>".$collection_name."</a> > <a href='".$base_url. "/mongodb_api/managedocument?mongodb_collection=".$collection_name."&document_id=".$document_id."' target='_self'>".$document_id."</a> > ".$cur_key. "</b><br><br>",
			);
			
			$form['key'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,
				'#default_value' => $cur_key,	 
				'#class' => 'value-field',
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;','disabled' => 'disabled'),  
				'#prefix' => '<div class="clearboth">',
				'#size' => 2000,
			);
			
			$form['valuee'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',	  
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),				
				'#suffix' => '</div><br>',
				'#size' => 2000,
			);
			
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
			$document_id = $_GET['document_id'];
			$collection_name = $_GET['mongodb_collection'];
			
			$key = $_GET['key'];
			if (strpos($key, '___') !== false) {
				$keyparts = explode("___",$key);
			}
			$value = $form_state->getValue("valuee");
			
			if(isset($keyparts)){
				// prefix
				$updateWith = '{"';
				foreach($keyparts as $keypart){
					$updateWith .= $keypart.'.';
				}
				$updateWith = substr($updateWith,0, strlen($updateWith)-1);
				
				$updateWith .= '":"';
				
				//suffix
				for($i=0;$i<count($keyparts)-1;$i++){
					$updateWith .= $keyparts[$i].'.';
				}
				$updateWith = substr($updateWith,0, strlen($updateWith)-1).'.'.$value;
				$updateWith .= '"}';
			}else{
				$updateWith = '{"'.$key.'":"'.$value.'"}';
			}
			
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
			drupal_set_message("Updated changes successfully");
			curl_close ($ch);	
			$showHideJson = \Drupal::config('mongodb_api.settings')->get('json_setting');
			if($showHideJson == "Yes")
				drupal_set_message($server_output);
			
			$redirect_url = $base_url.'/mongodb_api/managedocument?collection_name='.$collection_name.'&document_id='.$document_id;
			$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			$response->send();
			return;
		}
	}
}