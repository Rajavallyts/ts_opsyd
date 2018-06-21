<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;

class addgroupuserForm extends FormBase {

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'add_groupuser';
	}
  
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		
		\Drupal::service('page_cache_kill_switch')->trigger();
		global $base_url;
		
		$form["mail"] = [
			'#type' => 'email',
			'#title' => t("Email address"),
			'#required' => TRUE,
			'#description' => t("A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.")
		];
		
		$form["username"] = [
			'#type' => 'textfield',
			'#title' => t("Username"),
			'#required' => TRUE,
			'#description' => t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign.")
		];
		
		$form['submit'] = [
			'#type' => 'submit',
			'#name' => 'add_user',
			'#value' => t('Add User'),
			'#button_type' => 'primary',
		];
		
		return $form;
	}
	
	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		
		$con = \Drupal\Core\Database\Database::getConnection();
		$form_values = $form_state->getValues();
		
		// username validation
		$user_name = trim($form_values['username']);
		if (isset($form_values['username'])) {
		  if ($error = custom_user_validate_name($user_name)) {
			 $form_state->setErrorByName('username', $error);
		  }
		  elseif ((bool) $con->select('users_field_data', 'users')->fields('users', ['uid'])->condition('name', $con->escapeLike($user_name), 'LIKE')->range(0, 1)->execute()->fetchField()) {

			$form_state->setErrorByName('username',  t('The name %username is already taken.', array('%username' => $user_name)));
		  }
		}
		
		// email validation
		$mail = trim($form_values['mail']);
		if (isset($form_values['mail'])) {
		  if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
			 $form_state->setErrorByName('mail',  t('The e-mail address %mail is not valid.', array('%mail' => $email)));
		  }
		  elseif ((bool) $con->select('users_field_data', 'users')->fields('users', ['mail'])->condition('mail', $con->escapeLike($mail), 'LIKE')->range(0, 1)->execute()->fetchField()) {

			$form_state->setErrorByName('mail',  t('The name %mail is already taken.', array('%mail' => $mail)));
		  }
		}
	}
	
	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {	 
		global $base_url;
		
		$form_values = $form_state->getValues();
		
		$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
		$user = User::create();

		//Mandatory settings
		$user->setPassword(user_password());
		$user->enforceIsNew();
		$user->setEmail(trim($form_values["mail"]));
		$user->setUsername(trim($form_values["username"]));
		$user->addRole('datauser');

		//Optional settings
		$user->set("init", 'mail');
		$user->set("langcode", $language);
		$user->set("preferred_langcode", $language);
		$user->set("preferred_admin_langcode", $language);
		$user->activate();

		//Save user
		$user->save();
		
		// adding as group memeber
		$group_id = $_SESSION['group_id'];
		if(!empty($group_id)){
			$group = Group::load($group_id);
			$group->addContent($user, 'group_membership');
		}
		
		_user_mail_notify('register_admin_created', $user);
		
		drupal_set_message("User with uid " . $user->id() . " saved!\n");
		$redirect_url = $base_url.'/mongodb_api/assigndataform';
		$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
		$response->send();
		return;
	}
}

/**
 * Verify the syntax of the given name.
 */
function custom_user_validate_name($name) {
  if (!$name) {
    return t('You must enter a username.');
  }
  if (substr($name, 0, 1) == ' ') {
    return t('The username cannot begin with a space.');
  }
  if (substr($name, -1) == ' ') {
    return t('The username cannot end with a space.');
  }
  if (strpos($name, '  ') !== FALSE) {
    return t('The username cannot contain multiple spaces in a row.');
  }
  if (preg_match('/[^\x{80}-\x{F7} a-z0-9@_.\'-]/i', $name)) {
    return t('The username contains an illegal character.');
  }
  if (preg_match('/[\x{80}-\x{A0}' .         // Non-printable ISO-8859-1 + NBSP
                  '\x{AD}' .                // Soft-hyphen
                  '\x{2000}-\x{200F}' .     // Various space characters
                  '\x{2028}-\x{202F}' .     // Bidirectional text overrides
                  '\x{205F}-\x{206F}' .     // Various text hinting characters
                  '\x{FEFF}' .              // Byte order mark
                  '\x{FF01}-\x{FF60}' .     // Full-width latin
                  '\x{FFF9}-\x{FFFD}' .     // Replacement characters
                  '\x{0}-\x{1F}]/u',        // NULL byte and control characters
                  $name)) {
    return t('The username contains an illegal character.');
  }
  if (strlen($name) > 60) {
    return t('The username %name is too long: it must be %max characters or less.', array('%name' => $name, '%max' => 60));
  }
}