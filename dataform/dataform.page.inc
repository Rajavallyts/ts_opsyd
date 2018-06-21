<?php

/**
 * @file
 * Contains dataform.page.inc.
 *
 * Page callback for Data Form Setting entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Data Form Setting templates.
 *
 * Default template: dataform.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_dataform(array &$variables) {
  // Fetch DataForm Entity Object.
  $dataform = $variables['elements']['#dataform'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
