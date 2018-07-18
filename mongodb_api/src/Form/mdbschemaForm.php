<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mdbschema\Entity\MDBSchema;
use Drupal\dataform\Entity\DataForm;
use Drupal\webform\Entity\Webform;
use Drupal\url_redirect\Entity\UrlRedirect;
use Drupal\collection_relations\Entity\CollectionRelations;
use Drupal\collection_field_relation\Entity\CollectionFieldRelation;

class mdbschemaForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mdbschemaForm';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	global $base_url;
	
	if ($_SESSION['mongodb_token'] != ""){		
		$query = \Drupal::entityQuery('mdb_schema')
							->condition('status', 1)
							->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'], '=');
		$mdbschemas = $query->execute();
		
		$old_list = [];
		$mdbschema_id = '';
		if(count($mdbschemas) > 0){
			$mdbschema_id = array_keys($mdbschemas)[0];			
			$mdbschema = MDBSchema::load($mdbschema_id);			
			$oldlist = $mdbschema->field_mongodb_collections->value;
			$old_list = explode(", ", $oldlist);
		}
		
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

		$latest_list = [];
		if (count ($json_result) > 0 ) {				
			foreach($json_result as $result):
				if ($result['name'] != 'system.indexes')
				$latest_list[] = $result['name'];
			endforeach;
		}
		
		if(in_array("administrator",\Drupal::currentUser()->getRoles())){
			
			$form['existing_setup'] = [
				'#type' => 'markup',
				'#markup' => "<BR><b>Old Collections list</b><br>" . implode(", ", $old_list),
			];
			
			$form['current_setup'] = [
				'#type' => 'markup',
				'#markup' => "<BR><BR><b>Latest Collections list</b><br>" . implode(", ", $latest_list),
			];
			
			$form['old_collection_array'] = [
				'#type' => 'hidden',
				'#value' => $old_list,
			];
			$form['current_collection_array'] = [
				'#type' => 'hidden',
				'#value' => $latest_list,
			];
			$form['current_setup_collection'] = [
				'#type' => 'hidden',
				'#value' => implode(", ", $latest_list),
			];
			$form['mdb_schema_id'] = [
				'#type' => 'hidden',
				'#value' => !empty($mdbschema_id) ? $mdbschema_id : '',
			];
			
			if (implode(", ", $old_list) == implode(", ", $latest_list)) {
				$form['conclusion'] = [
					'#type' => 'markup',
					'#markup' => "<BR><BR>No updates found in Mongodb.",
				];
			} else {
				$form['conclusion'] = [
					'#type' => 'markup',
					'#markup' => "<BR><BR>Updates found in Mongodb. Confirm your latest changes by clicking Save Schema.<BR><b>Note: All drupal setting related to missing collection will be deleted permanently.</b>",
				];
				$form['actions']['#type'] = 'actions';
				$form['actions']['submit'] = array(
					'#name' => 'submit_button',
					'#type' => 'submit',
					'#value' => $this->t('Save Schema'),
					'#button_type' => 'primary',
				);
				$form['actions']['cancel'] = array (
					'#name' => 'cancel_button',
					'#type' => 'submit',
					'#value' => $this->t('No need. I will in check Mongodb.'),
				);
			}
		}else{
			if(count($mdbschemas) > 0){
				if (implode(", ", $old_list) == implode(", ", $latest_list)) {
					$form['description'] = [
						'#type' => 'markup',
						'#markup' => 'No updates found in Mongodb.',
					];
				}else{
					drupal_set_message("Mongodb schema changes found.", "warning");
					$form['description'] = [
						'#type' => 'markup',
						'#markup' => 'Please contact your site administrator.',
					];
				}
			}else{
				drupal_set_message("Mongodb schema setup is missing.", "warning");
				$form['description'] = [
					'#type' => 'markup',
					'#markup' => 'Please contact your site administrator.',
				];
			}			
		}
	}else {
		$form['description'] = [
			'#type' => 'markup',
			'#markup' => 'No connection established to mongodb. Please <a href="' . $base_url . '/mongodb-list" target="_self">Connect</a>',
		];
	}
	
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	  
	global $base_url;
	$triggering_element = $form_state->getTriggeringElement()["#name"];

	if($triggering_element == "submit_button"){
		$form_values = $form_state->getValues();
		$cur_collection = $form_values["current_setup_collection"];
		if(!empty($form_values["mdb_schema_id"])){
			// remove old dataform, webform, node, group and urlredirect
			$cur_collection_array = $form_values["current_collection_array"];
			$old_collection_array = $form_values["old_collection_array"];
			$coll_diff = array_diff($old_collection_array, $cur_collection_array);
			
			$coll_diff = array('Product');
			
			if(!empty($coll_diff)){
				$query = \Drupal::entityQuery('dataform')
						->condition('status', 1)
						->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'])
						->condition('field_collection_name', $coll_diff, 'IN');
				$df_ids = $query->execute();

				if(!empty($df_ids)){
					foreach($df_ids as $df_id){
						$dataform = DataForm::load($df_id);
						if(!empty($dataform)){
							
							$nid = $dataform->field_webform_content_id->value;
							$webform_id = $dataform->field_web_form_id->value;

							// remove webform from group
							$group = \Drupal::entityTypeManager()->getStorage('group')->load($_SESSION["group_id"]);
							if(!empty($group)){
								foreach($group->getContent() as $key => $content){
									if($content->getGroupContentType()->get('content_plugin') == 'group_node:webform'){
										if($content->getEntity()->id() == $nid){
											$content->delete();
										}
									}
								}
							}
							
							// delete node
							$node = node_load($nid);
							if(!empty($node)){
								$node->delete();
							}
							
							// delete webform
							$webform = Webform::load($webform_id);
							if(!empty($webform)){
								$webform->delete();
							}
							
							//urlredirect
							$urlredirect = UrlRedirect::load('ur_' . $webform_id);
							if (!empty($urlredirect)) {			 
								$urlredirect->delete();
							}
							
							// delete dataform
							$dataform->delete();
						}
					}
				}
				
				// delete collection relation
				$query = \Drupal::entityQuery('collection_relations')
						->condition('status', 1)
						->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'])
						->condition('field_relative_collection', $coll_diff, 'IN');
				$coll_ids = $query->execute();
				
				if(!empty($coll_ids)){
					foreach($coll_ids as $coll_id){
						$coll_relation = CollectionRelations::load($coll_id);
						if(!empty($coll_relation)){
							$coll_relation->delete();
						}
					}
				}
				
				// delete collection field relation
				$query = \Drupal::entityQuery('collection_field_relation')
						->condition('status', 1)
						->condition('field_mongodb_connection_ref', $_SESSION['mongodb_nid'])
						->condition('field_collection_name', $coll_diff, 'IN');
				$coll_field_ids = $query->execute();
				
				if(!empty($coll_field_ids)){
					foreach($coll_field_ids as $coll_field_id){
						$coll_field_relation = CollectionFieldRelation::load($coll_field_id);
						if(!empty($coll_field_relation)){
							$coll_field_relation->delete();
						}
					}
				}
			}
			
			// schema update
			$mdbschema = MDBSchema::load($form_values["mdb_schema_id"]);
			$mdbschema->set('field_mongodb_collections',$cur_collection);
			$mdbschema->save();
		}else{
			$mdbschema = MDBSchema::create([
				'field_mongodb_collections' => $cur_collection,
				'field_mongodb_connection_ref' => $_SESSION['mongodb_nid']
			]);			
			$mdbschema->save();
		}
	}else{
		$redirect_url = $base_url.'/mongodb-list';
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
	}
  }
}