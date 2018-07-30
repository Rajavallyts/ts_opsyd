<?php
/**
  * @file
  * Contains \Drupal\mongodb_api\Controller\mongodb_apicontroller.
 */
     
namespace Drupal\mongodb_api\Controller;
     
use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Drupal\dataform\Entity\DataForm;
use Drupal\url_redirect\Entity\UrlRedirect;
use Drupal\collection_relations\Entity\CollectionRelations;
use Drupal\collection_field_relation\Entity\CollectionFieldRelation;
use Drupal\Core\Link;
use Drupal\Core\Url;
use \Drupal\file\Entity\File;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\menu_link_content\Entity\MenuLinkContent;
     
class lifetimeapicontroller extends ControllerBase {
	
  public function listcollection() {
		\Drupal::service('page_cache_kill_switch')->trigger();
		$_SESSION['json_text'] = "";
		checkConnectionStatus();
	  
		global $base_url;
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
		if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != ""){					
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

public function dataformsdocument() {

	global $base_url;
	\Drupal::service('page_cache_kill_switch')->trigger();
	
	$corporate_id = \Drupal::config('isftech_setting.settings')->get('isftech_conn_corporate');
	$francises_id = \Drupal::config('isftech_setting.settings')->get('isftech_conn_francises');
	$location_id = \Drupal::config('isftech_setting.settings')->get('isftech_conn_location');
	
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
			mongodb_api_connect($mongodb_nid);
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
				
					$header_title = (isset($webform_elements[$field]["#title"]) && !empty($webform_elements[$field]["#title"])) ? $webform_elements[$field]["#title"] : ucfirst($field);
					if($webform_elements[$field]["#type"] != "details" && $webform_elements[$field]["#multiple"] != 1)
						$output_html .= "<th>" . $header_title . "</th>";
				endforeach;
				$output_html .= "<th>Action</th></tr></thead><tbody>";
				
				$location_array = [];
				$current_user = User::load(\Drupal::currentUser()->id());
				/* $locations = $current_user->get('field_locations')->getValue();
				foreach($locations as $location){
					$location_array[] = $location["value"];
				} */
				if(!empty($current_user->get('field_locations')->value)){
					if(strpos($current_user->get('field_locations')->value,",") !== false){
						$locations = explode(",",$current_user->get('field_locations')->value);
						foreach($locations as $location){
							$location_array[] = $location;
						}
					}else{
						$location_array[] = $current_user->get('field_locations')->value;
					}
				}
				
				$group = $current_user->get('field_entity')->value;
				$group = Group::load($group);

				foreach($json_result as $result):
					$doc_key = array_search($result["Franchise"], array_column($documents, 'FranchiseID'));
				if(($webform_id == $corporate_id || $webform_id == $francises_id) && !empty($result["Franchise"])){

					if(($webform_id == $corporate_id && $documents[$doc_key]["Corporate"] == "Yes") || ($webform_id == $francises_id && $documents[$doc_key]["Corporate"] == "No") || $webform_id != $corporate_id && $webform_id != $francises_id){
					if(in_array("site_admin",\Drupal::currentUser()->getRoles())){
					$output_html .= "<tr>";		
					$colcount = 0;

					foreach ($webform_elements_keys as $field):
					
						if($webform_elements[$field]["#type"] != "details" && $webform_elements[$field]["#multiple"] != 1){
							if(in_array($field, $relative_column)){											
							$td_value = isset($rel_td_array[$field][$result[$field]]) ? $rel_td_array[$field][$result[$field]] : '';
							}else{
								if(isset($result[$field]) && !empty($result[$field])){
									$regex = '/^[^?]*\.(jpg|jpeg|gif|png)/m';
									/* if(is_array($result[$field])){
										$image_thumb = '';
										foreach($result[$field] as $cur_field){
											if(preg_match($regex, $cur_field, $match)){
												$file_info = pathinfo($cur_field);
												$image_thumb .= '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($cur_field).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
											}
										}
										
										$td_value = $image_thumb;
										
									}else{ */
										if(preg_match($regex, $result[$field], $match)){
											
											$file_info = pathinfo($result[$field]);
											
											$td_value = '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($result[$field]).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
										}else{
											$td_value = custom_teaser($result[$field],50);
										}
									//}
								}else{
									$td_value = '';
								}
							}
							
							$output_html .= "<td>" . $td_value . "</td>";
						}
					endforeach;
					
					$output_html .= "<td><a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":600}' href='".$base_url."/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."&document_id=".$result['_id']."'>Edit</a></td></tr>";
					}else{
						
						/* if($_SERVER["REMOTE_ADDR"] == "103.213.192.114"){
							print "<pre>group---"; print_r($group->label()); print "</pre>";
							print "<pre>entity---"; print_r($documents[$doc_key]['Entity Name']); print "</pre>";
							print "<pre>franchise---"; print_r($documents[$doc_key]['FranchiseID']); print "</pre>";
							print "<pre>location_array---"; print_r($location_array); print "</pre>";
						} */
						
						if(in_array("group_admin",\Drupal::currentUser()->getRoles())){
							if(!empty($group) && $group->label() == $documents[$doc_key]['Entity Name']){ 
							
								$output_html .= "<tr>";
								$colcount = 0;

								foreach ($webform_elements_keys as $field):
								
									if($webform_elements[$field]["#type"] != "details" && $webform_elements[$field]["#multiple"] != 1){
										if(in_array($field, $relative_column)){											
										$td_value = isset($rel_td_array[$field][$result[$field]]) ? $rel_td_array[$field][$result[$field]] : '';
										}else{
											if(isset($result[$field]) && !empty($result[$field])){
												$regex = '/^[^?]*\.(jpg|jpeg|gif|png)/m';
												/* if(is_array($result[$field])){
													$image_thumb = '';
													foreach($result[$field] as $cur_field){
														if(preg_match($regex, $cur_field, $match)){
															$file_info = pathinfo($cur_field);
															$image_thumb .= '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($cur_field).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
														}
													}
													
													$td_value = $image_thumb;
													
												}else{ */
													if(preg_match($regex, $result[$field], $match)){
														
														$file_info = pathinfo($result[$field]);
														
														$td_value = '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($result[$field]).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
													}else{
														$td_value = custom_teaser($result[$field],50);
													}
												//}
											}else{
												$td_value = '';
											}
										}
										
										$output_html .= "<td>" . $td_value . "</td>";
									}
								endforeach;
								
								$output_html .= "<td><a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":600}' href='".$base_url."/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."&document_id=".$result['_id']."'>Edit</a></td></tr>";
							}
						}else{
							if(!empty($group) && $group->label() == $documents[$doc_key]['Entity Name'] && in_array($documents[$doc_key]['FranchiseID'], $location_array)){ 
							
								$output_html .= "<tr>";
								$colcount = 0;

								foreach ($webform_elements_keys as $field):
								
									if($webform_elements[$field]["#type"] != "details" && $webform_elements[$field]["#multiple"] != 1){
										if(in_array($field, $relative_column)){											
										$td_value = isset($rel_td_array[$field][$result[$field]]) ? $rel_td_array[$field][$result[$field]] : '';
										}else{
											if(isset($result[$field]) && !empty($result[$field])){
												$regex = '/^[^?]*\.(jpg|jpeg|gif|png)/m';
												/* if(is_array($result[$field])){
													$image_thumb = '';
													foreach($result[$field] as $cur_field){
														if(preg_match($regex, $cur_field, $match)){
															$file_info = pathinfo($cur_field);
															$image_thumb .= '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($cur_field).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
														}
													}
													
													$td_value = $image_thumb;
													
												}else{ */
													if(preg_match($regex, $result[$field], $match)){
														
														$file_info = pathinfo($result[$field]);
														
														$td_value = '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($result[$field]).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
													}else{
														$td_value = custom_teaser($result[$field],50);
													}
												//}
											}else{
												$td_value = '';
											}
										}
										
										$output_html .= "<td>" . $td_value . "</td>";
									}
								endforeach;
								
								$output_html .= "<td><a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":600}' href='".$base_url."/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."&document_id=".$result['_id']."'>Edit</a></td></tr>";
							}
						}
					}
					}
				}else{
					$output_html .= "<tr>";		
					$colcount = 0;

					foreach ($webform_elements_keys as $field):
					
					
						if($webform_elements[$field]["#type"] != "details" && $webform_elements[$field]["#multiple"] != 1){
							if(in_array($field, $relative_column)){											
							$td_value = isset($rel_td_array[$field][$result[$field]]) ? $rel_td_array[$field][$result[$field]] : '';
							}else{
								if(isset($result[$field]) && !empty($result[$field])){
									$regex = '/^[^?]*\.(jpg|jpeg|gif|png)/m';
									/* if(is_array($result[$field])){
										$image_thumb = '';
										foreach($result[$field] as $cur_field){
											if(preg_match($regex, $cur_field, $match)){
												$file_info = pathinfo($cur_field);
												$image_thumb .= '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($cur_field).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
											}
										}
										
										$td_value = $image_thumb;
										
									}else{ */
										if(preg_match($regex, $result[$field], $match)){
											
											$file_info = pathinfo($result[$field]);
											
											$td_value = '<span class="thumb-file thumb-file--image"><a class="image-link" href="'.file_create_url($result[$field]).'" target="_blank">'.$file_info["basename"].'</a></span><br/>';
										}else{
											$td_value = custom_teaser($result[$field],50);
										}
									//}
								}else{
									$td_value = '';
								}
							}
							
							$output_html .= "<td>" . $td_value . "</td>";
						}
					endforeach;
					
					$output_html .= "<td><a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":600}' href='".$base_url."/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."&document_id=".$result['_id']."'>Edit</a></td></tr>";
				}
				endforeach;	

				$output_html .= "</tbody></table>";
			} else {
				$output_html = 'No document found!';
			}

			if($webform_id == $corporate_id)
				$add_title_text = "Corporate Tech";
			elseif($webform_id == $francises_id)
				$add_title_text = "Franchise Tech";
			elseif($webform_id == $location_id)
				$add_title_text = "Locations";
			else
				$add_title_text = ucfirst($mongodb_collection);
			$prefix = "<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":600}' href='" . $base_url . "/mongodb_api/managedataform?mongodb_collection=".$mongodb_collection."&webform_id=".$webform_id."'>Add ".$add_title_text."</a><BR>";
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
				if(in_array($coll_rel->field_source_collection->value, $collection_lists)){
					
					$table1 .= '<tr>
										<td>'.$coll_rel->field_source_collection->value.'</td>
										<td>'.$coll_rel->field_relative_collection->value.'</td>
										<td>'.str_replace("###",".",$coll_rel->field_source_key->value).'</td>
										<td>'.str_replace("###",".",$coll_rel->field_relative_key->value).'</td>
										<td>'.str_replace("###",".",$coll_rel->field_relative_value->value).'</td>
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
				if(in_array($coll_rel->field_collection_name->value, $collection_lists)){
					
					$table2 .= '<tr>
										<td>'.$coll_rel->field_collection_name->value.'</td>
										<td>'.str_replace("###",".",$coll_rel->field_category_key->value).'</td>
										<td>'.str_replace("###",".",$coll_rel->field_sub_category_key->value).'</td>
										<td><a href="'.$base_url.'/mongodb_api/collectionfieldrelation/manage?field_rel='.$coll_id.'">'.t("Edit").'</a></td>
										<td><a href="'.$base_url.'/mongodb_api/collectionfieldrelation/delete/confirm?field_rel='.$coll_id.'">'.t("Delete").'</a></td>
									</tr>';
				}
			}
			$table2 .= "</tbody></table>";
		}else {
			$table2 = t("No Collection Category Relationships found.");
		}
		
		$tablerows2 = array ('#markup' => $table2);
		$output_html[] = [
			'#prefix' => '<a href="'.$base_url.'/mongodb_api/collectionfieldrelation/manage?add=field">'.t("Add Collection Category Relationship").'</a>',
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
	}else{
		drupal_set_message(t("Something went wrong."),"warning");
	}
	
	$redirect_url = $base_url.'/mongodb_api/collectionrelation';
	$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
	$response->send();
	return;
  }
  
  public function collectionfieldrelationsdelete() {
	global $base_url;  
	
	if(isset($_GET["field_rel"])){
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