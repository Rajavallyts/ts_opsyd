<?php

/**
 * @file
 * Contains collection_relations.page.inc.
 *
 * Page callback for Collection relations entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Collection relations templates.
 *
 * Default template: collection_relations.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_collection_relations(array &$variables) {
  // Fetch CollectionRelations Entity Object.
  $collection_relations = $variables['elements']['#collection_relations'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
