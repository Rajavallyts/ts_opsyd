<?php

/**
 * @file
 * Contains collection_field_relation.page.inc.
 *
 * Page callback for Collection field relation entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Collection field relation templates.
 *
 * Default template: collection_field_relation.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_collection_field_relation(array &$variables) {
  // Fetch CollectionFieldRelation Entity Object.
  $collection_field_relation = $variables['elements']['#collection_field_relation'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
