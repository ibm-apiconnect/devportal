<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
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
 *   category = @Translation("IBM API Developer Portal")
 * )
 */
class ConsumerOrgSelectorBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    $current_user = \Drupal::currentUser();
    return AccessResult::allowedIf(!$current_user->isAnonymous() && (int) $current_user->id() !== 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['consumer_org_select_block:uid:' . \Drupal::currentUser()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    // Vary caching of this block per user.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $myorgs = [];
    $selected_name = NULL;
    $selected_id = NULL;
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $orgs = $userUtils->loadConsumerorgs();
    $options = ['attributes' => ['class' => 'elipsis-names']];

    $create_allowed = FALSE;
    $current_user = \Drupal::currentUser();
    // block anonymous and admin & self service onboarding must be enabled
    $selfService = (boolean) \Drupal::state()->get('ibm_apim.selfSignUpEnabled');
    $config = \Drupal::config('ibm_apim.settings');
    $allow_consumerorg_creation = (boolean) $config->get('allow_consumerorg_creation');
    if ($selfService !== FALSE && $allow_consumerorg_creation === TRUE && !$current_user->isAnonymous() && (int) $current_user->id() !== 1) {
      $create_allowed = TRUE;
    }

    if ($orgs !== NULL && !empty($orgs)) {
      $selected = $userUtils->getCurrentConsumerorg();
      if ($selected === NULL || empty($selected)) {
        $selected = $userUtils->setCurrentConsumerorg();
        $userUtils->setOrgSessionData();
      }
      $selected_org_full = $consumerOrgService->get($selected['url']);
      if (!empty($selected_org_full)) {
        $selected_name = $selected_org_full->getTitle();
        $selected_id = $selected_org_full->getId();
      }
      foreach ($orgs as $consumer_org) {
        $title = $consumer_org;
        $id = $consumer_org;
        $cOrg = $consumerOrgService->get($consumer_org);
        $consumer_org_url = str_replace('/', '_', $consumer_org);
        if ($cOrg !== NULL) {
          $title = $cOrg->getTitle();
          $id = $cOrg->getId();
        }
        $myorgs[] = [
          'title' => $title,
          'id' => $id,
          'link_object' => Link::createFromRoute($title, 'ibm_apim.consumerorg_selection', ['orgUrl' => $consumer_org_url], $options)
            ->toString(),
        ];
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return [
      '#theme' => 'consumerorg_select_block',
      '#orgs' => $myorgs,
      '#selected_name' => $selected_name,
      '#selected_id' => $selected_id,
      '#create_allowed' => $create_allowed,
    ];
  }

}
