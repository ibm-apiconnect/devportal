<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Based on example code from http://cornel.co/article/create-taxonomy-tree-programmatically-including-vocabulary-creation-drupal-7
 */
class ApicTaxonomy {

  /**
   * Loop over the categories and get all the tids
   *
   * @param array $categories
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function get_taxonomies_from_categories($categories = []): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $categories);

    $config = \Drupal::config('ibm_apim.settings');
    $create_enabled = (boolean) $config->get('categories')['create_taxonomies_from_categories'];
    $tids = [];
    if (is_array($categories) && !empty($categories)) {
      foreach ($categories as $category) {
        if ($create_enabled === TRUE) {
          $this->create_taxonomy_from_path($category);
        }
        $tid = $this->get_taxonomy_id_from_path($category);
        if (isset($tid) && is_numeric($tid)) {
          $tids[] = $tid;
        }
      }
    }
    $tids = array_unique($tids);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $tids);
    return $tids;
  }

  /**
   * @param $path
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function create_taxonomy_from_path($path): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $path);

    // only do create if its enabled
    $config = \Drupal::config('ibm_apim.settings');
    $create_enabled = (boolean) $config->get('categories')['create_taxonomies_from_categories'];
    if ($create_enabled === TRUE) {
      $taxonomy = [];
      // trim leading and trailing slashes
      $path = trim($path, "/");
      // trim enclosing quotes
      $path = preg_replace('/^([\'"])(.*)\\1$/', '\\2', $path);
      // replace any double slashes with single
      $path = preg_replace('#/+#', '/', $path);
      $pathParts = explode('/', $path);
      // only create taxonomy trees less than 20 levels deep
      if (count($pathParts) < 20) {
        $current = &$taxonomy;
        foreach ($pathParts as $key => $value) {
          $value = trim($value);
          if ($value !== '') {
            if ($key !== (count($pathParts) - 1)) {
              if (!is_array($current)) {
                $current = [];
              }
              $current = &$current[$value];
            }
            else {
              $current[] = $value;
            }
          }
        }
        $this->generate_tree(['tags' => $taxonomy]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }


  /**
   * Loop through the Vocabularies and generate the taxonomy tree.
   *
   * @param $trees
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function generate_tree($trees): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    foreach ($trees as $vocabulary_name => $vocabulary_tree) {
      $this->create_vocabulary_tree('tags', $vocabulary_tree);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Initial preparing for processing the nested array.
   *
   * @param string $vocabulary_name
   * @param $vocabulary_tree
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function create_vocabulary_tree($vocabulary_name = 'tags', $vocabulary_tree): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $vocabulary_name);

    // Create vocabulary if it doesn't exist.
    $vocabulary = $this->get_vocabulary_by_name($vocabulary_name);
    if (!isset($vocabulary)) {
      $vocabulary = Vocabulary::create([
        'name' => $vocabulary_name,
      ]);
      $vocabulary->save();
    }

    // Test purpose, delete all terms before create them.
    //foreach (taxonomy_get_tree($vocabulary->vid) as $term) {
    //  taxonomy_term_delete($term->tid);
    //}
    // Create taxonomy tree recursively.
    $this->create_one_level_tree($vocabulary_tree, $vocabulary);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Create taxonomy tree recursively.
   *
   * @param $vocabulary_tree
   * @param $vocabulary
   * @param int $parent
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function create_one_level_tree($vocabulary_tree, $vocabulary, $parent = 0): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $weight = 0;
    foreach ($vocabulary_tree as $parent_term_name => $term_name) {
      // If one level.
      if (!is_array($term_name)) {
        $term = $this->create_term($term_name, $vocabulary, $parent, $weight);
      }
      // If two levels or more.
      else {
        $term = $this->create_term($parent_term_name, $vocabulary, $parent, $weight);
        // Go deeper.
        $this->create_one_level_tree($term_name, $vocabulary, $term->id());
      }
      ++$weight;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Create the actual taxonomy term
   *
   * @param $name
   * @param $vocabulary
   * @param $parent
   * @param $weight
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\Entity\Term
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function create_term($name, $vocabulary, $parent, $weight) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $terms = taxonomy_term_load_multiple_by_name($name, $vocabulary);
    if (empty($terms)) {
      $term = Term::create([
        'name' => $name,
        'vid' => $vocabulary,
        'parent' => $parent,
      ]);
    }
    else {
      foreach ($terms as $result) {
        $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($result->get('tid')->value);
        if ((empty($parents) && $parent === 0) || array_key_exists($parent, $parents)) {
          // found existing term with correct parentage
          $term = $result;
        }
      }
      if (!isset($term)) {
        // need to create a new term
        $term = Term::create([
          'name' => $name,
          'vid' => $vocabulary,
          'parent' => $parent,
        ]);
      }
    }
    // Even if term exist already, we want to push our weight.
    $term->setWeight($weight);
    $term->save();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $term;
  }

  /**
   * Get vocabulary object by vocabulary name.
   *
   * @param $vocabulary_name
   *
   * @return mixed
   */
  private function get_vocabulary_by_name($vocabulary_name) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $vocabulary_name);
    $vocabularies = taxonomy_vocabulary_get_names();
    $returnValue = $vocabularies[$vocabulary_name] ?? NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * Get the target taxonomy ID for a given taxonomy path
   * This will return the TID of the end term in the path
   *
   * @param $path
   *
   * @return mixed|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function get_taxonomy_id_from_path($path) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $path);

    $vocabulary = 'tags';
    // trim leading and trailing slashes
    $path = trim($path, "/");
    // trim enclosing quotes
    $path = preg_replace('/^([\'"])(.*)\\1$/', '\\2', $path);
    // replace any double slashes with single
    $path = preg_replace('#/+#', '/', $path);
    $pathParts = explode('/', $path);
    $answer = [];
    if (isset($pathParts[0])) {
      $root_tids = taxonomy_term_load_multiple_by_name(trim($pathParts[0]), $vocabulary);

      foreach ($root_tids as $root_tid) {
        // only want root level tags so ignore ones with parents
        if (empty($parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($root_tid->get('tid')->value))) {
          $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary, $root_tid->get('tid')->value);
          if (empty($tree) || count($pathParts) === 1) {
            // root level tag, no children or
            // has children but we want the root tag
            $answer[] = $root_tid->get('tid')->value;
          }
          else {
            $found = FALSE;
            $parent_tid = $root_tid->get('tid')->value;
            foreach ($pathParts as $key => $value) {
              if ($key !== 0) {
                // skip root level element
                $value = trim($value);
                foreach ($tree as $tag) {
                  // check has right name and the right parent (case insensitive)
                  if (isset($tag->parents) && in_array($parent_tid, $tag->parents, FALSE) && strtolower($tag->name) === strtolower($value)) {
                    $parent_tid = $tag->tid;
                    if ($key === (count($pathParts) - 1)) {
                      // if we've got the last part of the path then we've found the right bit
                      $found = TRUE;
                    }
                  }
                }
              }
            }
            if ($found === TRUE) {
              $answer[] = $parent_tid;
            }
          }
        }
      }
    }
    $returnValue = $answer[0] ?? NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }
}