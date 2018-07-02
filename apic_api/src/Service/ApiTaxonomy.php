<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_api\Service;

use Drupal\Component\Utility\Unicode;
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
   * @param $api
   * @param $node
   */
  public function process_categories($api, $node) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $api['consumer_api']['x-ibm-configuration']['categories']);
    $categories = $api['consumer_api']['x-ibm-configuration']['categories'];
    $tids = $this->apicTaxonomy->get_taxonomies_from_categories($categories);
    $currenttags = $node->get('apic_tags')->getValue();

    if (is_array($tids) && !empty($tids)) {
      foreach ($tids as $tid) {
        if (isset($tid) && is_numeric($tid)) {
          $found = FALSE;
          foreach ($currenttags as $currentvalue) {
            if (isset($currentvalue['target_id']) && $currentvalue['target_id'] == $tid) {
              $found = TRUE;
            }
          }
          if ($found == FALSE) {
            $currenttags[] = array('target_id' => $tid);
          }
        }
      }

      $node->set('apic_tags', $currenttags);
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
   */
  public function process_phase_tag($node, $origPhase) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    if (isset($node) && isset($origPhase)) {
      $phase = ucfirst($origPhase);

      if ($phaseParentTerm = taxonomy_term_load_multiple_by_name($this->PHASE_TERM, $this->TAGS_VOCABULARY)) {
        $phaseParentTerm = reset($phaseParentTerm);
        $phaseParentTid = $phaseParentTerm->id();
      }
      else {
        $phaseParentTid = $this->create_phase_term($this->PHASE_TERM, 0);
      }

      if (isset($phaseParentTid)) {

        if ($phaseTerm = taxonomy_term_load_multiple_by_name($phase, $this->TAGS_VOCABULARY)) {
          $phaseTerm = reset($phaseTerm);
          $phaseTid = $phaseTerm->id();
        }
        else {
          $phaseTid = $this->create_phase_term($phase, $phaseParentTid);
        }

        // Remove any existing other phase tags and then add the new one
        // have to ensure we preserve other tags the user might have added
        $newTags = array();
        $existingTags = $node->apic_tags->getValue();
        if (isset($existingTags) && is_array($existingTags)) {
          foreach ($existingTags as $existingTagArray) {
            if (isset($existingTagArray) && isset($existingTagArray['target_id'])) {
              $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($existingTagArray['target_id']);
              $parent = reset($parent);

              if ((!isset($parent)) || (empty($parent)) || ($parent->id() != $phaseParentTid)) {
                $newTags[] = array('target_id' => $existingTagArray['target_id']);
              }
            }
          }
        }

        // Add the tag to the node
        if (isset($phaseTid)) {
          $newTags[] = array('target_id' => $phaseTid);

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
   * @return mixed
   */
  private function create_phase_term($name, $parent) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $phaseTerm = array(
      'name' => $name,
      'vid' => $this->TAGS_VOCABULARY,
      'parent' => array($parent)
    );

    $phase = $this->create_taxonomy_term($phaseTerm);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $phase->id());
    return $phase->id();
  }


  /**
   * Create a forum for this API
   *
   * @param null $apiName
   * @param null $apiDescription
   */
  function create_api_forum($apiName = NULL, $apiDescription = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apiName);
    try {
      if (isset($apiName)) {
        $cleanName = $this->sanitise_api_name($apiName);

        if (\Drupal::service('module_handler')->moduleExists('forum') && isset($cleanName)) {
          $apisForumContainer = $this->assert_forum_container();

          if (!isset($apisForumContainer) || empty($apisForumContainer)) {
            \Drupal::logger('apic_api')->warning('Failed to find or create the APIs forum container for this API.', array());
            // Early return
            return;
          }

          // trim description
          if (isset($apiDescription)) {
            $apiDescription = Unicode::truncate($apiDescription, 360, TRUE, TRUE, 4);
          }

          $this->assert_forum_term($cleanName, $apiDescription, $apisForumContainer);
        }
      }

    } catch (Exception $e) {
      \Drupal::logger('apic_api')->error('The following error occurred while attempting to create the forum for api %apiName: %e', array(
          '%apiName' => $apiName,
          '%e' => $e
        ));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Sanitises the API name provided to ensure that it is a valid forum name
   *
   * @param null $apiName
   * @return mixed
   */
  public function sanitise_api_name($apiName = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apiName);
    $cleanName = NULL;
    if (isset($apiName)) {
      $cleanName = preg_replace('/\%/', ' percentage', $apiName);
      $cleanName = preg_replace('/\@/', ' at ', $cleanName);
      $cleanName = preg_replace('/\&/', ' and ', $cleanName);
      $cleanName = preg_replace('/\s[\s]+/', '-', $cleanName); // Strip off multiple spaces
      $cleanName = preg_replace('/[^A-Za-z0-9-_.,:\s]+/', '-', $cleanName); // Strip off non-alpha-numeric
      $cleanName = preg_replace('/^[\-]+/', '', $cleanName); // Strip off the starting hyphens
      $cleanName = preg_replace('/[\-]+$/', '', $cleanName); // Strip off the ending hyphens
      $cleanName = mb_strimwidth($cleanName, 0, 128, "..."); // truncate string at 128 characters
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $cleanName);
    return $cleanName;
  }

  /**
   * Asserts that the 'APIs' forum container (taxonomy term) exists within
   * the 'forums' vocabulary
   *
   * @return mixed
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
   * @return mixed
   */
  private function create_forum_container() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $apisTerm = array(
      'name' => $this->APIS_FORUM_CONTAINER,
      'description' => t('Get help and advice on the use of our APIs.'),
      'parent' => array(0),
      'weight' => 0,
      'vid' => $this->FORUM_VOCABULARY,
      'forum_container' => 1
    );
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->create_taxonomy_term($apisTerm);
  }

  /**
   * Asserts the forum (taxonomy term) for the published
   * API exists
   * @param $name - forum (term) name
   * @param $description - forum (term) description
   * @param $container - the forum (term) container this api forum (term) will belong to
   * @return null
   */
  private function assert_forum_term($name, $description, $container) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);

    if ($apiForum = taxonomy_term_load_multiple_by_name($name, $this->FORUM_VOCABULARY)) {
      \Drupal::logger('apic_api')->notice('Forum with the name %name already exists', array('%name' => $name));
    }
    else {
      $this->create_forum_term($name, $description, $container);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return NULL;
  }

  /**
   * Creates the forum (taxonomy term) for the newly published
   * API
   * @param $name - forum (term) name
   * @param $description - forum (term) description
   * @param $container - the forum (term) container this api forum (term) will belong to
   */
  private function create_forum_term($name, $description, $container) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $apiForumTerm = array(
      'name' => $name,
      'description' => $description,
      'parent' => array($container->id()),
      'weight' => 0,
      'vid' => $this->FORUM_VOCABULARY,
      'forum_container' => 0
    );

    if ($apiForumTerm = $this->create_taxonomy_term($apiForumTerm)) {
      \Drupal::logger('apic_api')->notice('Successfully created the forum for api %name.', array('%name' => $name));
    }
    else {
      \Drupal::logger('apic_api')->warning('Failed to create the forum for api %name.', array('%name' => $name));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Creates a new taxonomy term (forum) based on the term
   * passed in.
   *
   * @param $taxonomyTerm
   * @return mixed - return the term object saved to the database
   */
  private function create_taxonomy_term($taxonomyTerm) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $taxonomyTerm);
    $term = Term::create($taxonomyTerm);
    $term->save();
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $term;
  }
}
