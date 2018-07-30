<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Ajax\OpenModalDialogCommand;
use \Drupal\Core\Ajax\CloseModalDialogCommand;

class adddocumentForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_document';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {	
	global $base_url;
	checkConnectionStatus();
	if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {
		$form['#tree'] = TRUE;
		$form['#prefix'] = "<div id='adddocument_wrapper'>";
		$form['#suffix'] = "</div>";
		$form['document'] = [
			'#type' => 'fieldset',     
			'#prefix' => "<div id='names-fieldset-wrapper'>",
			'#suffix' => '</div>',
		];
		$form['validator'] = array(
			'#type' => 'hidden',
			'#name' => 'validator'
		);

		$num_docs = $form_state->get('num_document');
		if (empty($num_docs)) {
		   $document_field = $form_state->set('num_document', 1);
		}
		for($i = 0; $i < $num_docs; $i++){
			$form['document'][$i]['key'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
				'#prefix' => '<div class="clearboth">',       				
				'#theme_wrappers' => array(),
				'#size' => 2000,
				'#required' => $i == 0 ? TRUE : FALSE,
			);
			$form['document'][$i]['valuee'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',	  
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),				
				'#suffix' => '</div><br>',
				'#theme_wrappers' => array(),
				'#size' => 2000,
				'#required' => $i == 0 ? TRUE : FALSE,
			);	
		}				

		$form['document']['actions'] = [
			'#type' => 'actions',
			'#class' => 'clearboth',
		];

		$form['document']['actions']['add_name'] = [
			'#type' => 'submit',
			'#value' => t('Add one more'),
			'#submit' => array('::addOne'),
			'#ajax' => [
				'callback' => '::addmoreCallback',
				'wrapper' => "names-fieldset-wrapper",		
			],		
			'#prefix' => '<div class="clearboth">',       
		];
		if ($form_state->get('num_document') > 1) {
			$form['document']['actions']['remove_name'] = [
				'#type' => 'submit',		 
				'#value' => t('Remove one'),
				'#submit' => array('::removeCallback'),
				'#ajax' => [
					'callback' => '::addmoreCallback',
					'wrapper' => "names-fieldset-wrapper",
				],		  
				'#suffix' => '</div><br>',
			];
		}
		$form_state->setCached(FALSE);

		$form['submit'] = [
			'#type' => 'submit',
			'#value' => t('Submit'),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
/*	global $base_url;
    parent::validateForm($form, $form_state);
    $document_values = $form_state->getValues("document");
	foreach($document_values['document'] as $document_value)
	{
		if ((trim($document_value['key']) == "")|| (trim($document_value['valuee']) == "")) {
			$form_state->setErrorByName('validator', "Invalid Key Value pair");
			/*$response = new AjaxResponse();

    // Get the modal form using the form builder.
   // $modal_form = $this->formBuilder->getForm('Drupal\mongodb_api\addcollectionForm');
   $form_obj = new \Drupal\mongodb_api\addcollectionForm\addcollectionForm();
$modal_form = \Drupal::formBuilder()->getForm($form_obj);
 //  $modal_form = \Drupal::formBuilder()->getForm('add_collection');

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand('My Modal Form', $modal_form, ['width' => '800']));

    return $response; */
		//	$form_state->setRebuild();
	/*		drupal_set_message(t('Atleast one correct Key Value pair required to create document.'), 'error');
			$redirect_url = $base_url . '/mongodb_api/listdocument?mongodb_collection=' . $_GET['mongodb_collection'];
			  $response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			  $response->send();
		      return;
		}	
	}*/	
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	     global $base_url;
	     $updateWith = "{";
		 $document_values = $form_state->getValues("document");
		 
		 foreach($document_values['document'] as $document_value)
		 {
			 if (isset($document_value['valuee'])) {
				 if ($document_value['valuee'] != "") {
					$updateWith .= '"' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
				 }
			 }
		 }
		 $updateWith = substr($updateWith,0, strlen($updateWith)-1) . "}";		 
	   
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $_GET['mongodb_collection'] ."/insert";
		$api_param = array ( 		    
		"token" => $_SESSION['mongodb_token'], 
		"document" => $updateWith);
								 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);		
		curl_close ($ch);
		
		$showHideJson = \Drupal::config('mongodb_api.settings')->get('json_setting');
		if($showHideJson == "Yes")
			drupal_set_message($server_output);
		$json_result = json_decode($server_output, true);
		if (isset($json_result['success'])) {
			if ($json_result['success'] == 1) {
			  drupal_set_message ("Added document successfully");
			  $redirect_url = $base_url . '/mongodb_api/listdocument?mongodb_collection=' . $_GET['mongodb_collection'];
			  $response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			  $response->send();
			  return;
			}
		}	
	}
	
	/**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_document');
    return $form['document'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_document');
    $add_button = $name_field + 1;
    $form_state->set('num_document', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_document');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_document', $remove_button);
    }
    $form_state->setRebuild();
  }
}