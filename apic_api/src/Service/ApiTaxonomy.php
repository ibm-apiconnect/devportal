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

namespace Drupal\apic_api\Service;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\ibm_apim\Service\ApicTaxonomy;
use Drupal\taxonomy\Entity\Term;

class ApiTaxonomy {

  protected $apicTaxonomy;

  private $FORUM_VOCABULARY = 'forums';

  private $TAGS_VOCABULARY = 'tags';

  private $APIS_FORUM_CONTAINER = 'APIs';

  private $PHASE_TERM = 'Phase';

  public function __construct(ApicTaxonomy $apicTaxonomy) {
    $this->apicTaxonomy = $apicTaxonomy;
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
  public function process_categories($api, $node): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $api['consumer_api']['x-ibm-configuration']['categories']);
    $categories = $api['consumer_api']['x-ibm-configuration']['categories'];
    $tids = $this->apicTaxonomy->get_taxonomies_from_categories($categories);
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
      $node->save();

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
    if ($node !== NULL && $origPhase !== NULL) {
      $phase = ucfirst($origPhase);

      if ($phaseParentTerm = taxonomy_term_load_multiple_by_name($this->PHASE_TERM, $this->TAGS_VOCABULARY)) {
        $phaseParentTerm = reset($phaseParentTerm);
        $phaseParentTid = $phaseParentTerm->id();
      }
      else {
        $phaseParentTid = $this->create_phase_term($this->PHASE_TERM, 0);
      }

      if ($phaseParentTid !== NULL) {

        if ($phaseTerm = taxonomy_term_load_multiple_by_name($phase, $this->TAGS_VOCABULARY)) {
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

    } catch (Exception $e) {
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
    if ($apisForumContainer = taxonomy_term_load_multiple_by_name($this->APIS_FORUM_CONTAINER, $this->FORUM_VOCABULARY)) {
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

    if ($apiForum = taxonomy_term_load_multiple_by_name($name, $this->FORUM_VOCABULARY)) {
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

    if ($apiForumTerm = $this->create_taxonomy_term($apiForumTerm)) {
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
      $categoriesTargetIds = $this->apicTaxonomy->get_taxonomies_from_categories($existingCategories);
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
