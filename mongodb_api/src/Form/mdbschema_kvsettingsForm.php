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

class mdbschema_kvsettingsForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mdbschema_kvsettingsForm';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	global $base_url;
	
	if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != ""){		
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
			$i = 0;
			foreach($json_result as $result):
				if ($result['name'] != 'system.indexes') {
					$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $result['name'] . "/find";
					$api_param = array ( "token" => $_SESSION['mongodb_token']);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$server_output = curl_exec ($ch);					
					curl_close ($ch);
					$json_collectionresult = json_decode($server_output, true);	
					$keylist = [];
					
					foreach($json_collectionresult as $colresult):
						foreach($colresult as $innercolkey => $innercolvalue):
							$keylist[] = $innercolkey;
						endforeach;
					endforeach;
					
					$form['collections'][$i] = [
						'#type' => 'fieldset',
						'#title' => $result['name'],
						'#collapsible' => TRUE,
					];
					$form['collections'][$i]['doclist'] = [
						'#type' => 'markup',
						'#markup' => implode(", " , array_unique($keylist)), //count($json_collectionresult),
					];
					$i++;
				}
			endforeach;
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
						'#markup' => 'There is some change in mongodb schema, Please contact your site administrator.',
					];
				}
			}else{
				drupal_set_message("Mongodb schema setup is missing.", "warning");
				$form['description'] = [
					'#type' => 'markup',
					'#markup' => 'There is some change in mongodb schema, Please contact your site administrator.',
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
	 
  }

}
