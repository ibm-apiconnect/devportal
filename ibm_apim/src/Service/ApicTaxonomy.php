<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Throwable;

/**
 * Based on example code from http://cornel.co/article/create-taxonomy-tree-programmatically-including-vocabulary-creation-drupal-7
 */
class ApicTaxonomy {

  private string $FORUM_VOCABULARY = 'forums';

  private string $TAGS_VOCABULARY = 'tags';

  private string $APIS_FORUM_CONTAINER = 'APIs';

  private string $PHASE_TERM = 'Phase';

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
      $this->create_vocabulary_tree($vocabulary_tree);
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
  private function create_vocabulary_tree($vocabulary_tree, $vocabulary_name = 'tags'): void {
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
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(["name" => $name, "vid" => $vocabulary]);
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
   * @return mixed|string|null
   */
  private function get_vocabulary_by_name($vocabulary_name) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $vocabulary_name);
    $vocabularies = \Drupal::entityQuery('taxonomy_vocabulary')->accessCheck()->execute();
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
      $root_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(["name" => trim($pathParts[0]), "vid" => $vocabulary]);

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

  /**
   * Processes the categories within a given api:
   * - Creates the relevant taxonomy tree for the categories if they dont already exist
   * - Assigns the child category term to the api
   *
   * @param $api
   * @param $node
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function process_api_categories($api, $node): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $api['consumer_api']['x-ibm-configuration']['categories']);
    $categories = $api['consumer_api']['x-ibm-configuration']['categories'];
    $tids = $this->get_taxonomies_from_categories($categories);
    $currentTags = $node->get('apic_tags')->getValue();

    if (\is_array($tids) && !empty($tids)) {
      foreach ($tids as $tid) {
        if ($tid !== NULL && is_numeric($tid)) {
          $found = FALSE;
          foreach ($currentTags as $currentValue) {
            if (isset($currentValue['target_id']) && $currentValue['target_id'] === $tid) {
              $found = TRUE;
            }
          }
          if ($found === FALSE) {
            $currentTags[] = ['target_id' => $tid];
          }
        }
      }

      $node->set('apic_tags', $currentTags);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Asserts the relevant taxonomy hierarchy is in
   * place from any provided product categories
   *
   * @param array $product
   * @param \Drupal\node\NodeInterface $node
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function process_product_categories(array $product, NodeInterface $node): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $categories = $product['catalog_product']['info']['categories'];
    $tids = $this->get_taxonomies_from_categories($categories);

    if ($node !== NULL && $node->hasField('apic_tags')) {
      $currentTags = $node->apic_tags->getValue();
    }
    else {
      $currentTags = [];
    }

    if (is_array($tids) && !empty($tids)) {
      foreach ($tids as $tid) {
        if (isset($tid) && is_numeric($tid)) {
          $found = FALSE;
          foreach ($currentTags as $currentValue) {
            if (isset($currentValue['target_id']) && (string) $currentValue['target_id'] === (string) $tid) {
              $found = TRUE;
            }
          }
          if ($found === FALSE) {
            $currentTags[] = ['target_id' => $tid];
          }
        }
      }

      $node->set('apic_tags', $currentTags);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Processes the phase tag of an API.
   * - Creates the relevant taxonomy tree for the phase if it doesn't already exist
   * - Assigns the phase term to the API while preserving other tags the user might have added
   *
   * @param $node
   * @param $origPhase
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function process_phase_tag($node, $origPhase): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    if ($origPhase !== NULL) {
      $phase = ucfirst($origPhase);

      if ($phaseParentTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(["name" => $this->PHASE_TERM, "vid" => $this->TAGS_VOCABULARY])) {
        $phaseParentTerm = reset($phaseParentTerm);
        $phaseParentTid = $phaseParentTerm->id();
      }
      else {
        $phaseParentTid = $this->create_phase_term($this->PHASE_TERM, 0);
      }

      if ($phaseParentTid !== NULL) {

        if ($phaseTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(["name" => $phase, "vid" => $this->TAGS_VOCABULARY])) {
          $phaseTerm = reset($phaseTerm);
          $phaseTid = $phaseTerm->id();
        }
        else {
          $phaseTid = $this->create_phase_term($phase, $phaseParentTid);
        }

        // Remove any existing other phase tags and then add the new one
        // have to ensure we preserve other tags the user might have added
        $newTags = [];
        $existingTags = $node->apic_tags->getValue();
        if ($existingTags !== NULL && \is_array($existingTags)) {
          foreach ($existingTags as $existingTagArray) {
            if ($existingTagArray !== NULL && isset($existingTagArray['target_id'])) {
              $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($existingTagArray['target_id']);
              $parent = reset($parent);

              if ($parent === NULL || empty($parent) || ((string) $parent->id() !== (string) $phaseParentTid)) {
                $newTags[] = ['target_id' => $existingTagArray['target_id']];
              }
            }
          }
        }

        // Add the tag to the node
        if ($phaseTid !== NULL) {
          $newTags[] = ['target_id' => $phaseTid];

          $node->set('apic_tags', $newTags);
          $node->save();
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Creates a new phase term according to the name and
   * parent id provided.
   *
   * @param $name
   * @param $parent
   *
   * @return int|mixed|null|string
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function create_phase_term($name, $parent) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $phaseTerm = [
      'name' => $name,
      'vid' => $this->TAGS_VOCABULARY,
      'parent' => [$parent],
    ];

    $phase = $this->create_taxonomy_term($phaseTerm);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $phase->id());
    return $phase->id();
  }


  /**
   * Create a forum for this API
   *
   * @param null $apiName
   * @param null $apiDescription
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function create_api_forum($apiName = NULL, $apiDescription = NULL): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apiName);
    try {
      if ($apiName !== NULL) {
        $cleanName = $this->sanitise_api_name($apiName);

        if ($cleanName !== NULL && \Drupal::service('module_handler')->moduleExists('forum')) {
          $apisForumContainer = $this->assert_forum_container();

          if ($apisForumContainer === NULL || empty($apisForumContainer)) {
            \Drupal::logger('apic_api')->warning('Failed to find or create the APIs forum container for this API.', []);
            // Early return
            return;
          }

          // trim description
          if ($apiDescription !== NULL) {
            $moduleHandler = \Drupal::service('module_handler');
            if ($moduleHandler->moduleExists('ghmarkdown')) {
              // convert markdown to html
              $parser = new \Drupal\ghmarkdown\cebe\markdown\GithubMarkdown();
              $apiDescription = $parser->parse($apiDescription);
            }
            // convert html to plaintext
            $apiDescription = MailFormatHelper::htmlToText($apiDescription);
            $apiDescription = Unicode::truncate($apiDescription, 360, TRUE, TRUE, 4);
          }

          $this->assert_forum_term($cleanName, $apiDescription, $apisForumContainer);
        }
      }

    } catch (Throwable $e) {
      \Drupal::logger('apic_api')->error('The following error occurred while attempting to create the forum for api %apiName: %e', [
        '%apiName' => $apiName,
        '%e' => $e,
      ]);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Sanitises the API name provided to ensure that it is a valid forum name
   *
   * @param null|string $apiName
   *
   * @return null|string
   */
  public function sanitise_api_name($apiName = NULL): ?string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apiName);
    $cleanName = NULL;
    if ($apiName !== NULL) {
      $cleanName = Html::escape($apiName); // convert to plaintext
      $cleanName = preg_replace('/\s[\s]+/', '-', $cleanName); // Strip off multiple spaces
      $cleanName = preg_replace('/^[\-]+/', '', $cleanName); // Strip off the starting hyphens
      $cleanName = preg_replace('/[\-]+$/', '', $cleanName); // Strip off the ending hyphens
      $cleanName = mb_strimwidth($cleanName, 0, 128, '...'); // truncate string at 128 characters
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $cleanName);
    return $cleanName;
  }

  /**
   * Asserts that the 'APIs' forum container (taxonomy term) exists within
   * the 'forums' vocabulary
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\Entity\Term|mixed
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function assert_forum_container() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ($apisForumContainer = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(["name" => $this->APIS_FORUM_CONTAINER, "vid" => $this->FORUM_VOCABULARY])) {
      // Should only ever be one 'APIs' container (taxonomy term) so grab the first element from the array
      $apisForumContainer = reset($apisForumContainer);
    }
    else {
      $apisForumContainer = $this->create_forum_container();
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $apisForumContainer;
  }

  /**
   * Creates the 'APIs' forum container (taxonomy term) within the
   * 'forums' vocabulary
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\Entity\Term
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function create_forum_container() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $apisTerm = [
      'name' => $this->APIS_FORUM_CONTAINER,
      'description' => t('Get help and advice on the use of our APIs.'),
      'parent' => [0],
      'weight' => 0,
      'vid' => $this->FORUM_VOCABULARY,
      'forum_container' => 1,
    ];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->create_taxonomy_term($apisTerm);
  }

  /**
   * Asserts the forum (taxonomy term) for the published
   * API exists
   *
   * @param $name
   * @param $description
   * @param $container
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function assert_forum_term($name, $description, $container): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);

    if (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(["name" => $name, "vid" => $this->FORUM_VOCABULARY])) {
      \Drupal::logger('apic_api')->notice('Forum with the name %name already exists', ['%name' => $name]);
    }
    else {
      $this->create_forum_term($name, $description, $container);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Creates the forum (taxonomy term) for the newly published
   * API
   *
   * @param $name
   * @param $description
   * @param $container
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function create_forum_term($name, $description, $container): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $apiForumTerm = [
      'name' => $name,
      'description' => $description,
      'parent' => [$container->id()],
      'weight' => 0,
      'vid' => $this->FORUM_VOCABULARY,
      'forum_container' => 0,
    ];

    if ($this->create_taxonomy_term($apiForumTerm)) {
      \Drupal::logger('apic_api')->notice('Successfully created the forum for api %name.', ['%name' => $name]);
    }
    else {
      \Drupal::logger('apic_api')->warning('Failed to create the forum for api %name.', ['%name' => $name]);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Creates a new taxonomy term (forum) based on the term
   * passed in.
   *
   * @param $taxonomyTerm
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\Entity\Term
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function create_taxonomy_term($taxonomyTerm) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $taxonomyTerm);
    $term = Term::create($taxonomyTerm);
    $term->save();
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $term;
  }

  //Filtering the manually added API tags from normal categories to stop tags duplication when a new version of an API is created.

  /**
   * @param array $oldTags all the old tags on the old node
   * @param array $existingCategories the categories on the old node
   *
   * @return array just the old taxonomy tags
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function separate_categories(array $oldTags, array $existingCategories): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (empty($existingCategories) || empty($oldTags)) {
      $result = $oldTags;
    }
    else {
      $categoriesTargetIds = $this->get_taxonomies_from_categories($existingCategories);
      $tids = [];
      $filteredTags = [];
      foreach ($oldTags as $tid) {
        $tids[] = $tid['target_id'];
      }

      foreach (array_diff($tids, $categoriesTargetIds) as $tid) {
        $filteredTags[] = ['target_id' => $tid];
      }

      $result = $filteredTags;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);
    return $result;
  }

}
