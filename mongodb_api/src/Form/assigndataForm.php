<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity;
use Drupal\group\Entity\Group;
use Drupal\dataform\Entity\DataForm;
use Drupal\opsyd_subgroups\Entity\OpsydSubgroups;

class assigndataForm extends FormBase {

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'assign_dataform';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		
		\Drupal::service('page_cache_kill_switch')->trigger();
		global $base_url;
		
		if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != ""){
			$form['add_user'] = [
				'#markup' => '<!-- <a class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:500}" href="'.$base_url.'/addsubgroup">'.t("Create a Subgroup").'</a>&nbsp;&nbsp; --><a class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:500}" href="'.$base_url.'/addgroupuser">'.t("Create a New User").'</a>',
			];
			
			$group_id = $_SESSION['group_id'];
			
			$cur_connection_id = $_SESSION['mongodb_nid'];
			
			$webform_array = $users_array = array();
			if(!empty($group_id)){		
				$group = Group::load($group_id);		
				foreach($group->getContent() as $key => $content){
					if(strpos($content->getGroupContentType()->id(), 'group_node-webform') !== false){
						$node_details = $content->getEntity();
						$webform_id = $node_details->get("webform")->getValue()[0]["target_id"];
						
						$query = \Drupal::entityQuery('dataform')
							->condition('field_web_form_id', $webform_id);
						$df_ids = $query->execute();
						foreach($df_ids as $df_id){
							$dataform_id = $df_id;
						}
						$dataform = DataForm::load($dataform_id);
						$connection_id = $dataform->field_mongodb_connection_ref->value;
						
						if($cur_connection_id == $connection_id ){
						
							$webform = \Drupal\webform\Entity\Webform::load($webform_id);
							if($webform){
								$webform_title = $webform->get("title");
								$webform_array[$webform_id]  = $webform_title;
							}
						}
					}
					if(strpos($content->getGroupContentType()->id(), 'group_membership') !== false){
						$user_details = $content->getEntity();
						
						if(in_array("datauser",$user_details->getRoles())){
							$user_id	= $user_details->uid->value;
							$user_name	= $user_details->name->value;
							$users_array[$user_id] = $user_name;
						}
					}
				}
			}
			
			/* $subgroup_list = array();
			
			$query = \Drupal::entityQuery('opsyd_subgroups')
				->condition('status', 1)
				->condition('field_parent_group_id', $group_id, '=');
			$subgroups = $query->execute();
			
			if(!empty($subgroups)){
				
				foreach($subgroups as $subgroup_id){
					$subgroup = OpsydSubgroups::load($subgroup_id);
					$subgroup_list[$subgroup_id] = $subgroup->field_sub_group_name->value;
				}
			} */
			
			$form['webforms'] = [
				'#type' => 'select',
				'#title' => t('Choose data forms'),
				'#required' => TRUE,
				'#options' => $webform_array,
				'#empty_option' => $this->t('Select'),
				'#ajax' => [
					'callback' => '::updateUserValue',
					'wrapper' => 'user-wrapper',
				],
			];
			
			$form['user_wrapper'] = [
			  '#type' => 'container',
			  '#attributes' => ['id' => 'user-wrapper'],
			];

			$cur_webform = $form_state->getValue("webforms");
			$form['user_wrapper']['datauser_hidden'] = [
				'#type' => 'hidden',
				'#value' => ($cur_webform != "") ? $this->getUserArray($cur_webform) : '',
			];
			
			$form['datauser'] = [
				'#type' => 'select',
				'#title' => t('Choose a data user'),
				'#multiple' => TRUE,
				'#required' => TRUE,
				'#options' => $users_array,
				'#empty_option' => $this->t('Select'),
			];

			/* $form['subgroup'] = [
				'#type' => 'select',
				'#title' => t('Choose a Subgroup'),
				'#multiple' => TRUE,
				'#options' => $subgroup_list,
				'#empty_option' => $this->t('Select'),
			]; */

			$form['submit_update'] = [
				'#type' => 'submit',
				'#name' => 'update_btn',
				'#value' => t('Update'),
				'#button_type' => 'primary',
			];
		}else{
			$form['notice'] = [
				'#markup' => "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>"
			];
		}
		
		return $form;
	}
	
	/**
	* Ajax callback for the data form dropdown.
	*/
	public function updateUserValue(array $form, FormStateInterface $form_state) {
		return $form['user_wrapper'];
	}
	
	protected function getUserArray($cur_webform) {
		
		$query = \Drupal::entityQuery('dataform')
					->condition('field_web_form_id', $cur_webform);
		$df_ids = $query->execute();
		$dataform_id = '';
		foreach($df_ids as $df_id){
			$dataform_id = $df_id;
		}

		$dataform = DataForm::load($dataform_id);
		$user_id_txt = $dataform->field_user_access_list->value;
		
		$user_txt = '';
		$user_ids = explode(",",$user_id_txt);
		foreach(array_filter($user_ids) as $user_id){
			$user_details = user_load($user_id);
			$user_txt .= $user_details->uid->value.",";
		}
		
		return rtrim($user_txt,",");
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {	 
		global $base_url;
		
		$webform_id = $form_state->getValue("webforms");
		$query = \Drupal::entityQuery('dataform')
				->condition('field_web_form_id', $webform_id);
		$df_ids = $query->execute();
		foreach($df_ids as $df_id){
			$dataform_id = $df_id;
		}
		$dataform = DataForm::load($dataform_id);
		
		$update_txt = '';
		$remove_array = array();
		$user_ids = $form_state->getValue("datauser");
		
		$user_id_txt = implode(",",$user_ids);
		$dataform->set("field_user_access_list",$user_id_txt);
		
		$dataform->save();
		
		drupal_set_message ("Users updated successfully");
		$redirect_url = $base_url.'/mongodb_api/assigndataform';
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
	}
}