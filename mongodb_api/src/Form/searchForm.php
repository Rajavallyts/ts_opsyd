<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class searchForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mongodb_search';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  global $base_url;	  
	  $server_output = "";	  
	  checkConnectionStatus();
	  $_SESSION['mongodb_search'] = '';
	  if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {	
		$form['search_text'] = [
		  '#type' => 'textfield',
		  '#required' => TRUE,		  
		];	

		$form['submit'] = [
		  '#type' => 'submit',
		  '#value' => t('Search'),
		];		
		
		if (!empty($form_state->get("form_status"))) {
			$form['description'] = [
				'#type' => 'markup',
				'#markup' => $form_state->get("form_status"),
			];
		}
		
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
	 $mongodb_search = strtolower($form_state->getValue("search_text"));	
	 $_SESSION['mongodb_search'] = $mongodb_search;
	 global $base_url;
	 
	 if (isset($mongodb_search)) {			 
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections";		  
	  $api_param = array ( 		    
		"token" => $_SESSION['mongodb_token'] );			
								 
	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
	  curl_setopt($ch, CURLOPT_POST, 1);
	  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	  $server_output = curl_exec ($ch);		
	  curl_close ($ch);
	  
	  $output_html = "<BR><BR>" . t('Search Result for') . "<strong> " .$mongodb_search . "</strong><br>";
	  $resultCount = 0;
	  $json_result = json_decode($server_output, true);				
	  $collectionlist = array();
		if (count ($json_result) > 0 ) {				
			foreach($json_result as $result):			
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $result['name'] ."/find";				
				$api_param = array ( "token" => $_SESSION['mongodb_token']);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$server_output = curl_exec ($ch);		
				curl_close ($ch);          
	
				if (strpos(strtolower($server_output), $mongodb_search) > 0) {
					$output_html .= "<br><div><b>" . $result['name'] . "</b></div>";
					$json_documents = json_decode($server_output, true);						
					$output_subhtml = '';
					$dcount = 0;
					foreach($json_documents as $doc):										
						$json_obj = json_encode($doc);
						if (strpos(strtolower($json_obj),$mongodb_search) > 0 ) 
						{
							$resultCount++;
							$output_subhtml .= "<div><a href='" . $base_url . "/mongodb_api/managedocument?mongodb_collection=".$result['name'];
							$inner_html = "";
							$inner_id = "";
							$fieldcount = count($doc);
							foreach ($doc as $resultkey => $resultValue):							
								if ($resultkey == "_id") {
									$dcount++;
									$inner_id = $resultValue;
									$inner_html = "(" . $dcount . ")&nbsp;&nbsp;{ObjectId('" . $resultValue . "')}";
								}
							endforeach;
							$inner_html .= "{" . $fieldcount . " fields}";
							$output_subhtml .= "&document_id=". $inner_id ."'>". $inner_html . "</a></div>";
						}
					endforeach;			
					$output_html .=  $output_subhtml;
				}				
			endforeach;
			
			if ($resultCount == 0)
			$output_html = "<BR><BR>" . t("No record matched your search keyword");
		
		
		
			$form_state->set("form_status", $output_html);		
		}			
		$form_state->setRebuild();			
	}
  }
}