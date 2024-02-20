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

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a 'Concepts' block.
 *
 * @Block(
 *   id = "ibm_apim_concepts",
 *   admin_label = @Translation("API Concepts Block"),
 *   category = @Translation("IBM API Developer Portal")
 * )
 */
class ApicConceptsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    // doesn't really need search content perms but wanted to restrict access to something
    // so that private sites hide this block too
    return AccessResult::allowedIfHasPermission($account, 'search content');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $eventsFound = \Drupal::service('apic_api.utils')->areEventAPIsPresent();
    $graphqlFound = FALSE;
    // url, title, description
    $concepts = [];

    if ($eventsFound) {
      $concepts[] = ['title' => t('Kafka'), 'url' => Url::fromUri('internal:/asyncapi-kafka')->toString(), 'description' => t('An event-driven framework for a distributed publish/subscribe platform')];
      $concepts[] = ['title' => t('AsyncAPI'), 'url' => Url::fromUri('internal:/asyncapi')->toString(), 'description' => t('A specification for describing and documenting event-driven APIs')];

      //$concepts[] = ['title' => t('MQ'), 'url' => Url::fromUri('/asyncapi-mq'), 'description' => t('Short description of MQ')];
    }
    if ($graphqlFound) {
      $concepts[] = ['title' => t('GraphQL'), 'url' => '', 'description' => t('Short description of GraphQL')];
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, [
      'theme' => 'ibm_apim_concepts_block',
      'eventsFound' => $eventsFound,
      'concepts' => $concepts,
    ]);
    return [
      '#eventsFound' => $eventsFound,
      '#concepts' => $concepts,
      '#theme' => 'ibm_apim_concepts_block',
    ];
  }

}