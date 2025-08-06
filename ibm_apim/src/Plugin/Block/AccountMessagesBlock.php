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

namespace Drupal\ibm_apim\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Provides an Account Status Messages Block.
 *
 * @Block(
 *   id = "ibm_apim_account_status_messages",
 *   admin_label = @Translation("Account Status Messages"),
 *   category = @Translation("Account Status Messages"),
 * )
 */
class AccountMessagesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function build(): array {
    $build = [];
    $current_user = \Drupal::currentUser();

    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    $currentUser = \Drupal::currentUser();
    if (isset($org) && isset($org['url']) && isset($current_user) && !$current_user->isAnonymous()) {
      $consumerOrg = \Drupal::service('ibm_apim.consumerorg')->get($org['url']);
      $user = User::load($currentUser->id());
      if (!$user->isAnonymous()) {
        $roles = $consumerOrg->getRolesForMember($user->get('apic_url')->value);
        if (count($roles) == 1 && $roles[0]->getTitle() == 'Member') {
          $messages['config'][] = t("WARNING: Account only has a member role for the org \"@orgName\", some features of the Developer Portal maybe restricted. Contact your Consumer Org owner to be granted permissions such as Administrator, Developer or Viewer.", ['@orgName' => $consumerOrg->getTitle()]);
        }
      }
    }

    if (isset($messages) && !empty($messages)) {
      $build['#header'] = t('There are issues with your account:');
      $build['#messages'] = $messages;
      $build['#theme'] = 'ibm_apim_account_status_messages_block';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}
