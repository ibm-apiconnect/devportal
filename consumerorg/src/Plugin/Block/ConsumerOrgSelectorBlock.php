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

/**
 * @file
 * Contains \Drupal\consumerorg\Plugin\Block\ConsumerOrgSelectorBlock.
 */

namespace Drupal\consumerorg\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Consumer organization Selector' block.
 *
 * @Block(
 *   id = "consumer_org_select",
 *   admin_label = @Translation("Consumer organization Selection"),
 *   category = @Translation("IBM API Connect")
 * )
 */
class ConsumerOrgSelectorBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $current_user = \Drupal::currentUser();
    return AccessResult::allowedIf(!$current_user->isAnonymous() && $current_user->id() != 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['consumer_org_select_block:uid:' . \Drupal::currentUser()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary caching of this block per user.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $myorgs = array();
    $selected_name = NULL;
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $orgs = $userUtils->loadConsumerorgs();
    $options = ['attributes' => ['class' => 'elipsis-names']];

    $create_allowed = FALSE;
    $current_user = \Drupal::currentUser();
    // block anonymous and admin & self service onboarding must be enabled
    $selfService = \Drupal::state()->get('ibm_apim.selfSignUpEnabled');
    $config = \Drupal::config('ibm_apim.settings');
    $allow_consumerorg_creation = $config->get('allow_consumerorg_creation');
    if (!$current_user->isAnonymous() && $current_user->id() != 1 && $selfService != 0 && $allow_consumerorg_creation) {
      $create_allowed = TRUE;
    }

    if (isset($orgs)) {
      $selected = $userUtils->getCurrentConsumerorg();
      if (!isset($selected) || empty($selected)) {
        $selected = $userUtils->setCurrentConsumerorg();
        $userUtils->setOrgSessionData();
      }
      $selected_org_full = $consumerOrgService->get($selected['url']);
      if (!empty($selected_org_full)) {
        $selected_name = $selected_org_full->getTitle();
      }
      foreach ($orgs as $consumer_org) {
        $title = $consumer_org;
        $node = $consumerOrgService->get($consumer_org);
        $consumer_org_url = str_replace("/", "_", $consumer_org);
        if (isset($node)) {
          $title = $node->getTitle();
        }
        $myorgs[] = array(
          'title' => $title,
          'link_object' => Link::createFromRoute($title, 'ibm_apim.consumerorg_selection', ['orgUrl' => $consumer_org_url], $options)
            ->toString()
        );
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return array(
      '#theme' => 'consumerorg_select_block',
      '#orgs' => $myorgs,
      '#selected_name' => $selected_name,
      '#create_allowed' => $create_allowed
    );
  }
}
