<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Ajax\CloseModalDialogCommand;

class addsubdocument extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_subdocument';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	global $base_url;
	checkConnectionStatus();
	if (isset($_SESSION['mongodb_token']) && $_SESSION['mongodb_token'] != "") {
	   $form['api_result'] = array (
			'#type' => 'markup',
			'#markup' => "<b>" . $_GET['mongodb_collection']. " > " . $_GET['document_id'] . "</b><br><br>",
		 );
		 
		  $form['#tree'] = TRUE;
		  $form['#prefix'] = "<div id='adddocument_wrapper'>";
		  $form['#suffix'] = "</div>";
		  $form['subdocument_key'] = [
			'#type' => 'textfield',
			'#required' => TRUE,
			'#title' => t('Document Key'),
		  ];
		  $form['document_id'] = [
			'#type' => 'hidden',
			'#value' => $_GET['document_id'],
		  ];
		  
		  $form['subdocument'] = [
		   '#type' => 'fieldset',     
		   '#prefix' => "<div id='subnames-fieldset-wrapper'>",
		   '#suffix' => '</div>',
		   '#title' => $this->t(' Sub Document [Key - Value]  '),
		  ];
		  $form['validator'] = array(
			'#type' => 'hidden',
			'#name' => 'validator');
			

		   $num_docs = $form_state->get('num_subdocument');
		   if (empty($num_docs)) {
			   $document_field = $form_state->set('num_subdocument', 1);
		   }
			for($i = 0; $i < $num_docs; $i++){
				$form['subdocument'][$i]['key'] = array(
					'#type' => 'textfield',      
					'#required' => FALSE,	  	  
					'#class' => 'value-field',
					'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
					'#prefix' => '<div class="clearboth">',       				
					'#theme_wrappers' => array(),
					'#size' => 2000,
					'#required' => $i == 0 ? TRUE : FALSE,
				);
				$form['subdocument'][$i]['valuee'] = array(
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
		
		$form['subdocument']['actions'] = [
			'#type' => 'actions',
			'#class' => 'clearboth',
		];

		$form['subdocument']['actions']['add_name'] = [
			'#type' => 'submit',
			'#value' => t('Add one more'),
			'#submit' => array('::addOne'),
			'#ajax' => [
			  'callback' => '::addmoreCallback',
			  'wrapper' => "subnames-fieldset-wrapper",		
			],		
			'#prefix' => '<div class="clearboth">',       
		];
		if ($form_state->get('num_subdocument') > 1) {
			$form['subdocument']['actions']['remove_name'] = [
			  '#type' => 'submit',		 
			  '#value' => t('Remove one'),
			  '#submit' => array('::removeCallback'),
			  '#ajax' => [
				'callback' => '::addmoreCallback',
				'wrapper' => "subnames-fieldset-wrapper",
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
		 if (isset($_GET['editkey'])) {
			$updateWith =  '{"' .$_GET['editkey'] . "." . $form_state->getValue("subdocument_key") . '":{'; 
		 } else {
			$updateWith =  '{"' . $form_state->getValue("subdocument_key") . '":{';
		 }
		 $document_values = $form_state->getValues("subdocument");
		 
		 foreach($document_values['subdocument'] as $document_value)
		 {
			 if (isset($document_value['valuee'])) {
				 if ($document_value['valuee'] != "") {
					$updateWith .= '"' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
				 }
			 }
		 }
		 $updateWith = substr($updateWith,0, strlen($updateWith)-1) . "}}";		 
			
$api_endpointurl = \Drupal::config('mongodb_api.settings')->get('endpointurl')."/collections/" . $_GET['mongodb_collection'] ."/update";
		$api_param = array ( 
		"query" => '{"_id":"'.$_GET['document_id'].'"}', 
		"token" => $_SESSION['mongodb_token'], 
		"updateWith" => $updateWith);


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
			  $redirect_url = $base_url . '/mongodb_api/managedocument?mongodb_collection=' . $_GET['mongodb_collection'] . "&document_id=" . $_GET['document_id'];
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
    $name_field = $form_state->get('num_subdocument');
    return $form['subdocument'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_subdocument');
    $add_button = $name_field + 1;
    $form_state->set('num_subdocument', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_subdocument');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_subdocument', $remove_button);
    }
    $form_state->setRebuild();
  }
}