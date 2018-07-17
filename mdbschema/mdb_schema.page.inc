<?php

/**
 * @file
 * Contains mdb_schema.page.inc.
 *
 * Page callback for Mdbschema entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Mdbschema templates.
 *
 * Default template: mdb_schema.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_mdb_schema(array &$variables) {
  // Fetch MDBSchema Entity Object.
  $mdb_schema = $variables['elements']['#mdb_schema'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
