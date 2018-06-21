<?php
/**
  * @file
  * Contains \Drupal\mongodb_api\Controller\mongodb_apicontroller.
 */
     
namespace Drupal\mongodb_api\Controller;
     
use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity;
use Drupal\group\Entity\Group;
use Drupal\dataform\Entity\DataForm;
use Drupal\url_redirect\Entity\UrlRedirect;
use Drupal\collection_relations\Entity\CollectionRelations;
use Drupal\collection_field_relation\Entity\CollectionFieldRelation;
use Drupal\Core\Link;
use Drupal\Core\Url;
use \Drupal\file\Entity\File;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\menu_link_content\Entity\MenuLinkContent;
     
class mongodb_apiController extends ControllerBase {
  public function connectMongoDB()
  {
	  global $base_url;
	  if ($_SESSION['mongodb_token'] != "") {
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/close";
		$api_param = array ("token" => $_SESSION['mongodb_token']);
									 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $server_output = curl_exec ($ch);		
		curl_close ($ch);
		$_SESSION['mongodb_token'] = "";
		$_SESSION['mongodb_nid'] = "";
		$_SESSION['group_id'] = "";
		$_SESSION["data_mongodb_collection"] = "";
		$_SESSION["data_webform_id"] = "";
		$_SESSION["data_document_id"] = "";
		$_SESSION["doc_mongodb_collection"] = "";
		$_SESSION["doc_document_id"] = "";
	  }
	  
	  //return "yes";
	 
	  if (isset($_GET['mongodb_id'])){			
		$response = mongodb_api_connect($_GET['mongodb_id']);
		$json_result = json_decode($response, true);
		
		if ($json_result['success'] == 1) {
			$_SESSION['mongodb_token'] = $json_result['token'];
			$_SESSION['mongodb_nid'] = $_GET['mongodb_id'];
			$_SESSION['group_id'] = $_REQUEST['group_id'];
			drupal_set_message (t('Success - Mongo DB connection establised.'));
			$redirect_url = $base_url . '/mongodb_api/listcollection';
			$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			$response->send();
			return;
		}
		else
		{
			//drupal_set_message(t("Invalid Database information.  No connection establised to Mongo DB."), "error");
			$errormessage = "Invalid Database information.  No connection establised with <b>IP - " . $mongodb_node->title->value . "</b> and <b> name - " . $mongodb_node->field_db_name->value . "</b>"; 
			drupal_set_message(t($errormessage), "error");
			$redirect_url = $base_url . '/mongodb-list';
			$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			$response->send();
			return;
		}
	  }	
	
	return array(
      '#type' => 'markup',
      '#markup' => "yes",
    );
	
  }  
	
  public function listcollection() {
		\Drupal::service('page_cache_kill_switch')->trigger();
		$_SESSION['json_text'] = "";
		checkConnectionStatus();
	  
		global $base_url;
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
		if ($_SESSION['mongodb_token'] != ""){					
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections";

			$api_param = array ( "token" => $_SESSION['mongodb_token']);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);		
			drupal_set_message($server_output);
			curl_close ($ch);
			$json_result = json_decode($server_output, true);	
						
			$output_html = '';
			if (count ($json_result) > 0 ) {				
				foreach($json_result as $result):			
					$output_html .= "<div><a href='" . $base_url . "/mongodb_api/listdocument?mongodb_collection=".$result['name']."'>" . $result['name'] . "</a></div>";					
				endforeach;				
			} else {
		        $output_html = 'No collection found!';			
			}
		}	
		$tablerows = array ('#markup' => $output_html);
			
		$output_html = [
			'#prefix'=> "<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":500}' href='" . $base_url . "/mongodb_api/addCollection'>Add Collection</a><BR><BR>" . mongodb_parseJSON($server_output),		
			'#type' => 'table',
			'#header' => [t('List of Collections')],			
			'#rows' => [
				[render($tablerows)]
			]
		];
		
	return $output_html;
 } 
 
   public function listdataform() {
		\Drupal::service('page_cache_kill_switch')->trigger();
		$_SESSION['json_text'] = "";
		checkConnectionStatus();
	  
		global $base_url;
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
		if ($_SESSION['mongodb_token'] != ""){					
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections";

			$api_param = array ( "token" => $_SESSION['mongodb_token']);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);		
			drupal_set_message($server_output);
			curl_close ($ch);
			$json_result = json_decode($server_output, true);	
						
			$output_html = '';
			if (count ($json_result) > 0 ) {				
				foreach($json_result as $result):			
					$output_html .= "<div><!--<a href='" . $base_url . "/mongodb_api/collectionsetting?mongodb_collection=" . $result['name'] . "' class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}'><img src='" . $base_url . "/sites/default/files/settings_icon.png' width='25px' height='25px'></a>&nbsp;--><a href='" . $base_url . "/mongodb_api/listcollectionform?mongodb_collection=".$result['name']."'>" . $result['name'] . "</a></div>";					
				endforeach;				
			} else {
		        $output_html = 'No collection found!';			
			}
		}else{
			$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
		}
		$tablerows = array ('#markup' => $output_html);
			
		$output_html = [
		//	'#prefix'=> "<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":500}' href='" . $base_url . "/mongodb_api/addCollection'>Add Collection</a><BR><BR>" . mongodb_parseJSON($server_output),		
			'#type' => 'table',
			'#header' => [t('List of Collections')],			
			'#rows' => [
				[render($tablerows)]
			]
		];
		
	return $output_html;
  }

public function listcollectionform() {
	\Drupal::service('page_cache_kill_switch')->trigger();
	$_SESSION['json_text'] = $prefix = "";
	checkConnectionStatus();
  
	global $base_url;

	$mongodb_collection = '';
	if (isset($_GET['mongodb_collection'])) {
		$mongodb_collection = $_SESSION["data_mongodb_collection"] = $_GET['mongodb_collection'];
		if(isset($_SESSION["data_webform_id"]))
			unset($_SESSION["data_webform_id"]);
		if(isset($_SESSION["data_document_id"]))
			unset($_SESSION["data_document_id"]);
	}else{
		if(isset($_SESSION["data_mongodb_collection"]))
			$mongodb_collection = $_SESSION["data_mongodb_collection"];
	}
	
	if ($_SESSION['mongodb_token'] != ""){
		if (!empty($mongodb_collection)) {
			$cur_connection_id = $_SESSION['mongodb_nid'];
			
			$group_id = $_SESSION['group_id'];
			
			$webform_ids = array();
			if(!empty($group_id)){
				$group = Group::load($group_id);
				foreach($group->getContent() as $key => $content){
					if(strpos($content->getGroupContentType()->id(), 'group_node-webform') !== false){
						$nodeDetails = $content->getEntity();
						$webform_ids[]  = $nodeDetails->get("webform")->getValue()[0]["target_id"];
					}
				}
			}
			
			$output_html = '';
			
			$flag = 0;
			if (count($webform_ids)) {				
				foreach($webform_ids as $webform_id):
				
					$query = \Drupal::entityQuery('dataform')
							->condition('field_web_form_id', $webform_id);
					$df_ids = $query->execute();
					foreach($df_ids as $df_id){
						$dataform_id = $df_id;
					}
					$dataform = DataForm::load($dataform_id);
					$collection_name = $dataform->field_collection_name->value;
					$connection_id = $dataform->field_mongodb_connection_ref->value;
					
					if($mongodb_collection == $collection_name && $cur_connection_id == $connection_id ){
						
						$webform = \Drupal\webform\Entity\Webform::load($webform_id);
						
							if(!empty($webform)){
							
						$output_html .= "<div><a href='" . $base_url . "/mongodb_api/collectionsetting?mongodb_collection=". $mongodb_collection."&webform_id=".$webform_id."' class='' data-dialog-type='modal' data-dialog-options='{\"width\":900}'><img src='http://tools.opsyd.com/sites/default/files/settings_icon.png' width='25px' height='25px'></a>&nbsp;<a href='" . $base_url . "/mongodb_api/listdataformdocument?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."'>". $webform->label(). "</a></div>";	
							}
						$flag++;
					}
					
				endforeach;				
			}
			if($flag == 0){
				$output_html = 'No Data form found!';			
			}
		
			$prefix = "<a class='' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='" . $base_url . "/mongodb_api/collectionsetting?mongodb_collection=".$mongodb_collection."'>Add Data Form</a><BR><BR>";
			
		}else {
			$output_html = "<BR>Please select <a href='" . $base_url . "/mongodb_api/listdataform' alt='Collection list' title='Collection list'>Collection</a><BR>";
		}
	}else{
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
	}
	
	$tablerows = array ('#markup' => $output_html);
		
	$output_html = [
		'#prefix'=> $prefix,		
		'#type' => 'table',
		'#header' => [t('List of Collection Data Forms')],			
		'#rows' => [
			[render($tablerows)]
		]
	];
	
	return $output_html;
}
  
 public function listdocument() {
	 
		global $base_url;
	checkConnectionStatus();
		 \Drupal::service('page_cache_kill_switch')->trigger();
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
		$prefix = '';
		if ($_SESSION['mongodb_token'] != ""){
		
		$mongodb_collection = '';
		if (isset($_GET['mongodb_collection'])) {							
			$mongodb_collection = $_SESSION["doc_mongodb_collection"] = $_GET['mongodb_collection'];
			if(isset($_SESSION["doc_document_id"]))
				unset($_SESSION["doc_document_id"]);
		}else{
			if(isset($_SESSION["doc_mongodb_collection"]))
				$mongodb_collection = $_SESSION["doc_mongodb_collection"];
		}
		
		
		if(!empty($mongodb_collection)){
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/".$mongodb_collection."/find";
				$api_param = array ( "token" => $_SESSION['mongodb_token']);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$server_output = curl_exec ($ch);		
				curl_close ($ch);
				
				$json_result = json_decode($server_output, true);							
				$output_html = '';
				$dcount = 0;
				//echo (count($json_result));
				//exit();
				if (count ($json_result) > 0 ) {
					$output_html .= '<table id="datadocument_list" class="display nowrap" style="width:100%"><thead><tr><th>List of Collections</th></tr></thead><tbody>';
				
					foreach($json_result as $result):			
					$output_html .= "<tr><td><a href='".$base_url."/mongodb_api/managedocument?mongodb_collection=".$mongodb_collection;
						$inner_html = "";
						$inner_id = "";
						$fieldcount = count($result);
						foreach ($result as $resultkey => $resultValue):							
							if ($resultkey == "_id") {
							$dcount++;
							$inner_id = $resultValue;
							$inner_html = "(" . $dcount . ")&nbsp;&nbsp;{ObjectId('" . $resultValue . "')}";
							}
						//	if ($resultkey != "_id")
							//$inner_html .= "'" . $resultkey . "':'" . $resultValue . "',";
						endforeach;
						$inner_html .= "{" . $fieldcount . " fields}";
						//$inner_html .= substr($inner_html, 0, strlen($inner_html)-2);						
						$output_html .= "&document_id=". $inner_id ."'>". $inner_html . "</a></td></tr>";						
					endforeach;
					$output_html .= "</tbody></table>";
				} else {
					$output_html = 'No document found!';			
				}
				
				$prefix = !empty($mongodb_collection) ? "<B>" . $mongodb_collection . "</b><BR><BR><a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='" . $base_url . "/mongodb_api/addDocument?mongodb_collection=".$mongodb_collection."'>Add Document</a>&nbsp;&nbsp;&nbsp;<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='" . $base_url . "/mongodb_api/addJSON?mongodb_collection=".$mongodb_collection."'>Add JSON</a><BR><BR>" .mongodb_parseJSON($server_output) : "" . mongodb_parseJSON($server_output);
			} else {
				$output_html = "<BR><BR>No collection selected. <a href='" . $base_url . "/mongodb_api/listcollection' alt='Collection list' title='Collection list'>Collection List</a>";
			}
		
		}	
		$tablerows = array ('#markup' => $output_html);
		$output_html = [
			'#prefix'=> $prefix,
			'#type' => 'table',
			//'#header' => [t('List of Collections')],
			//'#rows' => $html
			'#rows' => [
				[render($tablerows)]
			]
		];
		
	return $output_html;
  } 
  
public function listdataformdocument() {
	 
		global $base_url;
		 \Drupal::service('page_cache_kill_switch')->trigger();
		 checkConnectionStatus();
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
		if ($_SESSION['mongodb_token'] != ""){
			
			$mongodb_collection = $webform_id = '';
			if (isset($_GET['mongodb_collection']) && isset($_GET['webform_id'])) {
				$mongodb_collection = $_SESSION["data_mongodb_collection"] = $_GET['mongodb_collection'];
				$webform_id = $_SESSION["data_webform_id"] = $_GET['webform_id'];
			}else{
				if(isset($_SESSION["data_mongodb_collection"]) && isset($_SESSION["data_webform_id"])){
					$mongodb_collection = $_SESSION["data_mongodb_collection"];
					$webform_id = $_SESSION["data_webform_id"];
				}
			}
			
			if (!empty($mongodb_collection)){	
				$webform = \Drupal\webform\Entity\Webform::load($webform_id);
				$setdataform = FALSE;
				if (isset($webform))
					$setdataform = TRUE;
				
				if ($setdataform) {
					$webform_elements = $webform->getElementsDecoded();
					$webform_elements_keys = array_keys($webform_elements);
			
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/".$mongodb_collection."/find";				
						$api_param = array ( "token" => $_SESSION['mongodb_token']);
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$server_output = curl_exec ($ch);		
						curl_close ($ch);
						$dcount = 0;
						$output_html = "";
						$json_result = json_decode($server_output, true);
						
						if (count ($json_result) > 0 ) {				
							$output_html = '<table id="dataform_list" class="display nowrap" style="width:100%"><thead><tr>';
						
							$hide_column = $relative_column = $rel_td_array = array();
							foreach ($webform_elements_keys as $field):
								$query = \Drupal::entityQuery('collection_relations')
										->condition('status', 1)
										->condition('field_source_collection', trim($mongodb_collection), '=')
										->condition('field_source_key', $field, '=');
								$coll_ids = $query->execute();
								if(!empty($coll_ids)){
								$relative_column[] = $field;
								
									$coll_rel = CollectionRelations::load(array_keys($coll_ids)[0]);
									$rel_collection = $coll_rel->field_relative_collection->value;
									$rel_key 		= $coll_rel->field_relative_key->value;
									$rel_value 		= $coll_rel->field_relative_value->value;
									
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

									foreach($documents as $document){
										if(isset($document[$rel_value]))
											$rel_td_array[$field][$document[$rel_key]] = $document[$rel_value];
									}
								}
						
							if($webform_elements[$field]["#type"] != "details") // && !is_array($json_result[0][$field])
								$output_html .= "<th>" . $field . "</th>";
							else
								$hide_column[] = $field;
						endforeach;
						$output_html .= "<th>Action</th></tr></thead><tbody>";
					
						foreach($json_result as $result):										
							$output_html .= "<tr>";		
							$colcount = 0;
														
							foreach ($webform_elements_keys as $field):
								if(!in_array($field,$hide_column)){
									if(in_array($field, $relative_column)){											
										$td_value = isset($rel_td_array[$field][$result[$field]]) ? $rel_td_array[$field][$result[$field]] : '';
									}else{
										if(isset($result[$field]) && !empty($result[$field])){
											$regex = '/^[^?]*\.(jpg|jpeg|gif|png)/m';
											if(is_array($result[$field])){
												$image_thumb = '';
												foreach($result[$field] as $cur_field){
													if(preg_match($regex, $cur_field, $match)){
														$file_info = pathinfo($cur_field);
														/* $image_file = \Drupal::database()->select("file_managed","f")
																	->fields("f",array("filename"))
																	->condition("uri",$cur_field,"=")
																	->execute()
																	->fetchAssoc(); */
														
														$image_thumb .= '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($cur_field).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
													}
												}
												
												$td_value = $image_thumb;
												
											}else{
												if(preg_match($regex, $result[$field], $match)){
													
													$file_info = pathinfo($result[$field]);
													
													$td_value = '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($result[$field]).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
												}else{
													$td_value = custom_teaser($result[$field],50);
												}
											}
										}else{
											$td_value = '';
										}
									}
									
									$output_html .= "<td>" . $td_value . "</td>";
								}
							endforeach;
								
							$output_html .= "<td><a href='".$base_url."/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."&document_id=".$result['_id']."'>Edit</a></td></tr>";
						endforeach;	

						$output_html .= "</tbody></table>";
					} else {
						$output_html = 'No document found!';			
					}
					
					$prefix = "<a href='" . $base_url . "/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."'>Add ".ucfirst($mongodb_collection)."</a><BR>";
				} else {
				$output_html = "No Dataform available. Please set <a href='" . $base_url . "/mongodb_api/collectionsetting?mongodb_collection=" . $mongodb_collection . "' class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":500}'>Data Form</a><BR>";
			}
			} else {
				$output_html = "<BR>Please select <a href='" . $base_url . "/mongodb_api/listdataform' alt='Collection list' title='Collection list'>Collection</a><BR>";
			}
		}	
		$tablerows = array ('#markup' => $output_html);
		$output_html = [
			'#prefix'=> $prefix,
			'#type' => 'table',
			'#rows' => [
				[render($tablerows)]
			]
		];
		
	return $output_html;
} 


  public function dataforms()
  {
	global $base_url;
	\Drupal::service('page_cache_kill_switch')->trigger();

	$group_ids = [];
	$output_html = '';
	$membership_loader = \Drupal::service('group.membership_loader');
	if(!empty($membership_loader)){
		foreach ($membership_loader->loadByUser(\Drupal::currentUser()) as $group_membership) {
			$group_ids[] = $group_membership->getGroup()->id();
		}
	}
	
	$webform_ids = array();
	if(!empty($group_ids)){
		foreach($group_ids as $group_id){
		$group = Group::load($group_id);
		foreach($group->getContent() as $key => $content){
			if(strpos($content->getGroupContentType()->id(), 'group_node-webform') !== false){
				$nodeDetails = $content->getEntity();
				$webform_ids[]  = $nodeDetails->get("webform")->getValue()[0]["target_id"];
			}
		}
	}
	}
	
	$mongodb_collection = '';
	if (!empty($webform_ids)) {
		foreach($webform_ids as $webform_id):
			
			$query = \Drupal::entityQuery('dataform')
					->condition('field_web_form_id', $webform_id);
			$df_ids = $query->execute();
			foreach($df_ids as $df_id){
				$dataform_id = $df_id;
  } 
			$dataform = DataForm::load($dataform_id);
			
			$user_exists = $dataform->field_user_access_list->value;
			$user_exists_array = explode(",",$user_exists);
			
			$collection_url = '';
			if((in_array(\Drupal::currentUser()->id(),$user_exists_array)) || (in_array("datamanager",\Drupal::currentUser()->getRoles()))){
				
				$webform = \Drupal\webform\Entity\Webform::load($webform_id);
				$urlredirect = UrlRedirect::load('ur_' . $webform_id);
				if (isset($urlredirect)) {			 
					$collection_url = $urlredirect->get('path');			
				}
				
				$output_html .= "<div><a href='" . $base_url . "/" . $collection_url."'>". $webform->label(). "</a></div>";	
			}
			
		endforeach;				
	}else {
		$output_html = "No Dataform available.";
	}
	
	$tablerows = array ('#markup' => $output_html);
		
	$output_html = [		
		'#type' => 'table',
		'#header' => [t('List of Data Forms')],			
		'#rows' => [
			[render($tablerows)]
		]
	];

	return $output_html;
}

public function dataformsdocument() {

	global $base_url;
	\Drupal::service('page_cache_kill_switch')->trigger();
	
	$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
	
	$webform_id = '';
	if (isset($_GET['webform_id'])) {
		$webform_id = $_SESSION["mduser_webform_id"] = $_GET['webform_id'];
	}else{
		if(isset($_SESSION["mduser_webform_id"]))
			$webform_id = $_SESSION["mduser_webform_id"];
	}
		
	if (!empty($webform_id)) {
		$webform = \Drupal\webform\Entity\Webform::load($webform_id);
		
		$query = \Drupal::entityQuery('dataform')
				->condition('field_web_form_id', $webform_id);
		$df_ids = $query->execute();
		
		foreach($df_ids as $df_id){
			$dataform_id = $df_id;
		}
		$dataform = DataForm::load($dataform_id);
		$mongodb_collection = $dataform->field_collection_name->value;
		$mongodb_nid = $dataform->field_mongodb_connection_ref->value;

		if (isset($mongodb_nid)){			
			$response = mongodb_api_connect($mongodb_nid);
			$json_result = json_decode($response, true);

			if ($json_result['success'] == 1) {
				$_SESSION['mongodb_token'] = $json_result['token'];
				$_SESSION['mongodb_nid'] = $mongodb_nid;
			}
			else
			{
				drupal_set_message(t("Invalid Database information. Please contact your Data Manager."));
			}
		}
		
		if ($webform) {
			$webform_elements = $webform->getElementsDecoded();
			$webform_elements_keys = array_keys($webform_elements);
	
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/".$mongodb_collection."/find";						
			$api_param = array ( "token" => $_SESSION['mongodb_token']);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);		
			curl_close ($ch);
			$dcount = 0;
			$output_html = "";
			$json_result = json_decode($server_output, true);		
			
			if (count ($json_result) > 0 ) {				
				$output_html = '<table id="dataform_list" class="display nowrap" style="width:100%"><thead><tr>';
			
				$hide_column = $relative_column = $rel_td_array = array();
				foreach ($webform_elements_keys as $field):
					$query = \Drupal::entityQuery('collection_relations')
							->condition('status', 1)
							->condition('field_source_collection', trim($mongodb_collection), '=')
							->condition('field_source_key', $field, '=');
					$coll_ids = $query->execute();
					if(!empty($coll_ids)){
						$relative_column[] = $field;
						
						$coll_rel = CollectionRelations::load(array_keys($coll_ids)[0]);
						$rel_collection = $coll_rel->field_relative_collection->value;
						$rel_key 		= $coll_rel->field_relative_key->value;
						$rel_value 		= $coll_rel->field_relative_value->value;
						
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

						foreach($documents as $document){
							if(isset($document[$rel_value]))
								$rel_td_array[$field][$document[$rel_key]] = $document[$rel_value];
						}
					}
				
					if($webform_elements[$field]["#type"] != "details") // && !is_array($json_result[0][$field])
						$output_html .= "<th>" . $field . "</th>";
					else
						$hide_column[] = $field;
				endforeach;
				$output_html .= "<th>Action</th></tr></thead><tbody>";
			
				foreach($json_result as $result):										
					$output_html .= "<tr>";		
					$colcount = 0;

					foreach ($webform_elements_keys as $field):
						if(!in_array($field,$hide_column)){
							if(in_array($field, $relative_column)){											
							$td_value = isset($rel_td_array[$field][$result[$field]]) ? $rel_td_array[$field][$result[$field]] : '';
							}else{
								if(isset($result[$field]) && !empty($result[$field])){
									$regex = '/^[^?]*\.(jpg|jpeg|gif|png)/m';
									if(is_array($result[$field])){
										$image_thumb = '';
										foreach($result[$field] as $cur_field){
											if(preg_match($regex, $cur_field, $match)){
												$file_info = pathinfo($cur_field);
												/* $image_file = \Drupal::database()->select("file_managed","f")
															->fields("f",array("filename"))
															->condition("uri",$cur_field,"=")
															->execute()
															->fetchAssoc(); */
												
												$image_thumb .= '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($cur_field).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
											}
										}
										
										$td_value = $image_thumb;
										
									}else{
										if(preg_match($regex, $result[$field], $match)){
											
											$file_info = pathinfo($result[$field]);
											
											$td_value = '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($result[$field]).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
										}else{
											$td_value = custom_teaser($result[$field],50);
										}
									}
								}else{
									$td_value = '';
								}
							}
							
							$output_html .= "<td>" . $td_value . "</td>";
						}
					endforeach;
					
					$output_html .= "<td><a href='".$base_url."/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."&document_id=".$result['_id']."'>Edit</a></td></tr>";
				endforeach;	

				$output_html .= "</tbody></table>";
			} else {
				$output_html = 'No document found!';			
			}
		
			$prefix = "<a href='" . $base_url . "/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."'>Add ".ucfirst($mongodb_collection)."</a><BR>";
		}else {
			$output_html = "No Dataform form found.<br/>";
		}
		
	} else {
		$output_html = "<BR>Please select <a href='" . $base_url . "/dataforms' alt='Data Form' title='Data Form list'>Data Form</a><BR>";
	}
	
	$tablerows = array ('#markup' => $output_html);
	$output_html = [
		'#prefix'=> $prefix,
		'#type' => 'table',
		'#rows' => [
			[render($tablerows)]
		]
	];
		
	return $output_html;
  }
  
  public function collectionrelationslist() {
	
	global $base_url;
	\Drupal::service('page_cache_kill_switch')->trigger();
	checkConnectionStatus();
	
	$collection_lists = array();
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
		if (count ($json_result) > 0 ) {				
			foreach($json_result as $result):			
				$collection_lists[] = $result['name'];					
			endforeach;				
		}
	
		// collection relationships
		$query = \Drupal::entityQuery('collection_relations')
				->condition('status', 1)
				->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'], '=')
				->sort('created', 'DESC')
				->pager(10);
		$coll_ids = $query->execute();

		if(!empty($coll_ids)){
			$table1 = '<table><thead>
							<tr>
								<th>'.t("Source Collection").'</th>
								<th>'.t("Relative Collection").'</th>
								<th>'.t("Source Key").'</th>
								<th>'.t("Relative Key").'</th>
								<th>'.t("Relative Value").'</th>
								<th colspan="2">'.t("Action").'</th>
							</tr>
							</thead><tbody>';
			foreach ($coll_ids as $coll_id) {
				$coll_rel = CollectionRelations::load($coll_id);
				
				$row["field_source_collection"] = $coll_rel->field_source_collection->value;
				
				if(in_array($row["field_source_collection"], $collection_lists)){
					$row["field_relative_collection"] = $coll_rel->field_relative_collection->value;
					$row["field_source_key"] = $coll_rel->field_source_key->value;
					$row["field_relative_key"] = $coll_rel->field_relative_key->value;
					
					$table1 .= '<tr>
										<td>'.$coll_rel->field_source_collection->value.'</td>
										<td>'.$coll_rel->field_relative_collection->value.'</td>
										<td>'.$coll_rel->field_source_key->value.'</td>
										<td>'.$coll_rel->field_relative_key->value.'</td>
										<td>'.$coll_rel->field_relative_value->value.'</td>
										<td><a href="'.$base_url.'/mongodb_api/collectionrelation/manage?coll_rel='.$coll_id.'">'.t("Edit").'</a></td>
										<td><a href="'.$base_url.'/mongodb_api/collectionrelation/delete/confirm?coll_rel='.$coll_id.'">'.t("Delete").'</a></td>
									</tr>';
				}
			}
			$table1 .= "</tbody></table>";
		}else {
			$table1 = t("No Collection Relationships found.");
		}
		
		$tablerows1 = array ('#markup' => $table1);
		$output_html[] = [
			'#prefix' => '<a href="'.$base_url.'/mongodb_api/collectionrelation/manage?add=coll">'.t("Add Collection Relationship").'</a>',
			'#type' => 'table',
			'#header' => [t('List of collection realationships')],			
			'#rows' => [
				[render($tablerows1)]
			]
		];
		
		// collection field relationships
		$query = \Drupal::entityQuery('collection_field_relation')
				->condition('status', 1)
				->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'], '=')
				->sort('created', 'DESC')
				->pager(10);
		$coll_ids = $query->execute();

		if(!empty($coll_ids)){
			$table2 = '<table><thead>
							<tr>
								<th>'.t("Source Collection").'</th>
								<th>'.t("Category Key").'</th>
								<th>'.t("Subcategory Key").'</th>
								<th colspan="2">'.t("Action").'</th>
							</tr>
							</thead><tbody>';
			foreach ($coll_ids as $coll_id) {
				$coll_rel = CollectionFieldRelation::load($coll_id);
				
				$row["field_collection_name"] = $coll_rel->field_collection_name->value;
				
				if(in_array($row["field_collection_name"], $collection_lists)){
					$row["field_collection_name"] = $coll_rel->field_collection_name->value;
					$row["field_category_key"] = $coll_rel->field_category_key->value;
					$row["field_sub_category_key"] = $coll_rel->field_sub_category_key->value;
					
					$table2 .= '<tr>
										<td>'.$coll_rel->field_collection_name->value.'</td>
										<td>'.$coll_rel->field_category_key->value.'</td>
										<td>'.$coll_rel->field_sub_category_key->value.'</td>
										<td><a href="'.$base_url.'/mongodb_api/collectionrelation/manage?field_rel='.$coll_id.'">'.t("Edit").'</a></td>
										<td><a href="'.$base_url.'/mongodb_api/collectionrelation/delete/confirm?field_rel='.$coll_id.'">'.t("Delete").'</a></td>
									</tr>';
				}
			}
			$table2 .= "</tbody></table>";
		}else {
			$table2 = t("No Collection Category Relationships found.");
		}
		
		$tablerows2 = array ('#markup' => $table2);
		$output_html[] = [
			'#prefix' => '<a href="'.$base_url.'/mongodb_api/collectionrelation/manage?add=field">'.t("Add Collection Category Relationship").'</a>',
			'#type' => 'table',
			'#header' => [t('List of collection category realationships')],			
			'#rows' => [
				[render($tablerows2)]
			]
		];
		
	}else{
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
	}	

	return $output_html;
  }
  
  public function collectionrelationsdelete() {
	global $base_url;  
	
	if(isset($_GET["coll_rel"])){
		// delete collection relationship
		$coll_rel = CollectionRelations::load($_GET["coll_rel"]);
		$coll_rel->delete();
		drupal_set_message(t("Collection Relationship deleted successfully."));
	}else if(isset($_GET["field_rel"])){
		// delete collection field relationship
		$field_rel = CollectionFieldRelation::load($_GET["field_rel"]);
		$field_rel->delete();
		drupal_set_message(t("Collection Field Relationship deleted successfully."));
	}else{
		drupal_set_message(t("Something went wrong."),"warning");
	}
	
	$redirect_url = $base_url.'/mongodb_api/collectionrelation';
	$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
	$response->send();
	return;
  }
  
   public function listmongodb() {
		\Drupal::service('page_cache_kill_switch')->trigger();
		$_SESSION['json_text'] = "";
		checkConnectionStatus();
	  
		global $base_url;
		$output_html = [];
		
		$active_con_nid = isset($_SESSION["mongodb_nid"]) ? $_SESSION["mongodb_nid"] : '';
	
		$membership_loader = \Drupal::service('group.membership_loader');
		if(!empty($membership_loader)){
			foreach ($membership_loader->loadByUser(\Drupal::currentUser()) as $group_membership):
				$group_id[] = $group_membership->getGroup()->id();
			endforeach;
		}
		
		$connection_nid = array();
		if(!empty($group_id)){
			$group_storage = \Drupal::entityManager()->getStorage('group');

		foreach($group_storage->loadMultiple($group_id) as $group):
			foreach($group->getContent() as $key => $content){
						$nodeDetails = $content->getEntity();
						if ($nodeDetails->bundle() == "mongodb_information") {						
							
							$nid = $nodeDetails->id();
							
							$nodeTitle = new FormattableMarkup('<span class="con-title">:con_title</span>&nbsp;&nbsp;<span class="indicator :con_class"></span>',[':con_title'=>$nodeDetails->getTitle(),':con_class'=>($nid == $active_con_nid) ? 'active' : '']);
												
							$action = new FormattableMarkup('<a href=":connectlink">@connectname</a>&nbsp;&nbsp;<a class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:500}" href=":editlink">@editname</a>&nbsp;&nbsp;<a class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:500}" href=":deletelink">@deletename</a>', [':connectlink' =>  $base_url .'/mongodb_api/connect_mongodb?mongodb_id=' . $nid . '&group_id=' . $group->get('id')->getValue()[0]['value'] , '@connectname' => $this->t('Connect'), ':editlink' => $base_url . '/node/' . $nid . '/edit', '@editname' => $this->t('Edit'), ':deletelink' => $base_url . '/node/' . $nid . '/delete?destination=' . $base_url . '/mongodb-list', '@deletename' => $this->t('Delete')]);						
							$output_html[] = [$nodeTitle,$group->get('label')->getValue()[0]['value'], $action];
						}
			}
		endforeach;
   }
  
		$tablerows = array ('#markup' => $output_html);
			
		$output_html = [
			'#prefix'=> "<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":500}' href='" . $base_url . "/node/add/mongodb_information'>Add MonogDB</a>",		
			'#type' => 'table',
			'#header' => [t('Connection Information'), t('Group'), t('Action')],			
			'#rows' =>  $output_html,
		];
		
	return $output_html;
 } 
}

function custom_teaser($text,$length = null){
	if(strlen($text) > $length)
		$teaser = substr($text,0,$length).'...';
	else
		$teaser = $text;
	return $teaser;
}