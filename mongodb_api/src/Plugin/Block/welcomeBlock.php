<?php

namespace Drupal\mongodb_api\Plugin\Block;

use Drupal\user\Entity\User;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Welcome' Block.
 *
 * @Block(
 *   id = "welcome_block",
 *   admin_label = @Translation("Welcome block"),
 *   category = @Translation("Welcome message at header"),
 * )
 */
class welcomeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
	 
	  $user = User::load(\Drupal::currentUser()->id());
	  $first_name = $user->field_first_name->value;
	  $last_name = $user->field_last_name->value;	 
    return array(
      '#markup' => $this->t('Welcome @first_name @last_name', array('@first_name' => $first_name, '@last_name' => $last_name)),
    );
  }

    public function getCacheMaxAge() {
		return 0;
	}
}
