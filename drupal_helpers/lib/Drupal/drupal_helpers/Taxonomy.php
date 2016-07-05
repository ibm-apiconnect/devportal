<?php

namespace Drupal\drupal_helpers;

if (!module_exists('taxonomy')) {
  throw new Exception('Taxonomy module is not present.');
}

/**
 * Class Taxonomy.
 *
 * @package Drupal\drupal_helpers
 */
class Taxonomy {

  /**
   * Create form element options from terms in provided vocabulary.
   *
   * @param string $machine_name
   *   Vocabulary machine name.
   * @param string $depth_prefix
   *   Depth indentation prefix. Defaults to '-'.
   *
   * @return array
   *   Array of options keyed by term id and suitable for use with FAPI elements
   *   that support '#options' property.
   */
  public static function formElementOptions($machine_name, $depth_prefix = '-') {
    $options = [];

    $vocab = taxonomy_vocabulary_machine_name_load($machine_name);
    $terms = taxonomy_get_tree($vocab->vid);

    foreach ($terms as $term) {
      $options[$term->tid] = str_repeat($depth_prefix, $term->depth) . $term->name;
    }

    return $options;
  }

  /**
   * Find term by name.
   *
   * Retrieve the very first occurrence of the term in the search result set.
   *
   * @param string $name
   *   Term name.
   * @param string $machine_name
   *   Vocabulary machine name. Defaults to NULL.
   *
   * @return object
   *   Term object if found, FALSE otherwise.
   */
  public static function termByName($name, $machine_name = NULL) {
    $term = taxonomy_get_term_by_name($name, $machine_name);
    if (!empty($term)) {
      $term = reset($term);

      return $term;
    }

    return FALSE;
  }

}
