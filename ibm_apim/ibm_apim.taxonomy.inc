<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2015, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * Based on example code from http://cornel.co/article/create-taxonomy-tree-programmatically-including-vocabulary-creation-drupal-7
 */

/**
 * Loop through the Vocabularies and generate the taxonomy tree.
 *
 * @param $trees
 */
function ibm_apim_generate_tree($trees) {
  foreach ($trees as $vocabulary_name => $vocabulary_tree) {
    _ibm_apim_generate_create_vocabulary_tree('tags', $vocabulary_tree);
  }
}

/**
 * Initial preparing for processing the nested array.
 *
 * @param string $vocabulary_name
 * @param $vocabulary_tree
 */
function _ibm_apim_generate_create_vocabulary_tree($vocabulary_name = 'tags', $vocabulary_tree) {
  // Create vocabulary if it doesn't exist.
  $vocabulary = _ibm_apim_generate_get_vocabulary_by_name($vocabulary_name);
  if (!isset($vocabulary->vid)) {
    if (!function_exists('pathauto_cleanstring')) {
      module_load_include('inc', 'pathauto');
    }
    $vocabulary = new stdClass();
    $vocabulary->name = $vocabulary_name;
    if (function_exists('pathauto_cleanstring')) {
      $vocabulary_machine_name = pathauto_cleanstring($vocabulary_name);
    }
    else {
      $vocabulary_machine_name = drupal_strtolower(preg_replace('/[^a-z\d ]/i', '', check_plain($vocabulary_name)));
    }
    $vocabulary->machine_name = str_replace('-', '_', $vocabulary_machine_name);
    taxonomy_vocabulary_save($vocabulary);
  }

  // Test purpose, delete all terms before create them.
  //foreach (taxonomy_get_tree($vocabulary->vid) as $term) {
  //  taxonomy_term_delete($term->tid);
  //}
  // Create taxonomy tree recursively.
  _ibm_apim_generate_create_one_level_tree($vocabulary_tree, $vocabulary);
}

/**
 * Create taxonomy tree recursively.
 *
 * @param $vocabulary_tree
 * @param $vocabulary
 * @param int $parent
 */
function _ibm_apim_generate_create_one_level_tree($vocabulary_tree, $vocabulary, $parent = 0) {
  $weight = 0;
  foreach ($vocabulary_tree as $parent_term_name => $term_name) {
    // If one level.
    if (!is_array($term_name)) {
      $term = _ibm_apim_generate_create_term($term_name, $vocabulary, $parent, $weight);
    }
    // If two levels or more.
    else {
      $term = _ibm_apim_generate_create_term($parent_term_name, $vocabulary, $parent, $weight);
      // Go deeper.
      _ibm_apim_generate_create_one_level_tree($term_name, $vocabulary, $term->tid);
    }
    ++$weight;
  }

  return;
}

/**
 * Create the actual taxonomy term
 *
 * @param $name
 * @param $vocabulary
 * @param $parent
 * @param $weight
 * @return array|mixed|\stdClass
 */
function _ibm_apim_generate_create_term($name, $vocabulary, $parent, $weight) {
  $terms = taxonomy_get_term_by_name($name, $vocabulary->machine_name);
  if (empty($terms)) {
    $term = new stdClass();
    $term->name = $name;
    $term->vid = $vocabulary->vid;
    $term->parent = $parent;
  }
  else {
    foreach ($terms as $result) {
      $parents = taxonomy_get_parents($result->tid);
      if (empty($parents) && $parent == 0 || in_array($parent, array_keys($parents))) {
        // found existing term with correct parentage
        $term = $result;
      }
    }
    if (!isset($term)) {
      // need to create a new term
      $term = new stdClass();
      $term->name = $name;
      $term->vid = $vocabulary->vid;
      $term->parent = $parent;
    }
  }
  // Even if term exist already, we want to push our weight.
  $term->weight = $weight;
  taxonomy_term_save($term);

  return $term;
}

/**
 * Get vocabulary object by vocabulary name.
 *
 * @param $vocabulary_name
 * @return mixed
 */
function _ibm_apim_generate_get_vocabulary_by_name($vocabulary_name) {
  $vocabulary = taxonomy_vocabulary_machine_name_load($vocabulary_name);
  return $vocabulary;
}