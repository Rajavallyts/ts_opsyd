<?php
namespace Drupal\mongodb_api\addgroupuser;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\dataform\Entity\DataForm;
use Drupal\url_redirect\Entity\UrlRedirect;
use Drupal\Core\Form\FormState;
use Drupal\user\Entity\User;

class addgroupuserForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addgroupuser';
  }
  
 /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	$form['mail'] = [
		'#title' => 'Email address',
		'#type' => 'textfield',
		'#required' => TRUE,
	];
	$form['name'] = [
		'#title' => 'Username',
		'#type' => 'textfield',
		'#required' => TRUE,
	];
	$form['pass'] = [
		'#title' => 'Password',
		'#type' => 'password',
		'#required' => TRUE,
	];	
	$form['submit'] = [
		'#type' => 'submit',
		'#value' => 'Add user',
		'#required' => TRUE,
	];
	
    return $form;	
	
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
	/*$user = \Drupal\user\Entity\User::create();
	$user->setPassword($form_state->getValue('pass1'));
	$user->enforceIsNew();
	$user->setEmail($form_state->getValue('mail'));
	$user->setUsername($form_state->getValue('name'));
	$user->activate();
	$user->save();	  */
	/*$form_state = new FormState();
$values['name'] = 'robo-user';
$values['mail'] = 'robouser@example.com';
$values['pass']['pass1'] = 'password';
$values['pass']['pass2'] = 'password';
$values['op'] = t('Create new account');
$form_state->setValues($values);*/
//\Drupal::formBuilder()->submitForm('user_register_form', $form_state);
  }
}  
