<?php

/**
 * @file
 * Contains opsyd_subgroups.page.inc.
 *
 * Page callback for Opsyd subgroups entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Opsyd subgroups templates.
 *
 * Default template: opsyd_subgroups.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_opsyd_subgroups(array &$variables) {
  // Fetch OpsydSubgroups Entity Object.
  $opsyd_subgroups = $variables['elements']['#opsyd_subgroups'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
