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

namespace Drupal\ibm_apim\Controller;

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ibm_apim\Service\UserUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IbmApimController extends ControllerBase {

  protected $userUtils;
  protected $consumerOrgService;

  public function __construct(UserUtils $userUtils,
                              ConsumerOrgService $consumer_org_service) {
    $this->userUtils = $userUtils;
    $this->consumerOrgService = $consumer_org_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.consumerorg')
    );
  }

  public function version() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $version = '';

    $filename = drupal_get_path('profile', 'apim_profile') . '/apic_version.yaml';
    if (file_exists($filename)) {
      $yaml = yaml_parse_file(drupal_get_path('profile', 'apim_profile') . '/apic_version.yaml');
      if (isset($yaml['version'])) {
        $version .= $yaml['version'];
      }
      if (isset($yaml['build'])) {
        $version .= '( ' . $yaml['build'] . ' )';
      }
    }
    $markup = '<p>' . t('IBM API Connect Developer Portal version %ver', array('%ver' => $version)) . '</p>';
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('apic_api')) {
      $filename = drupal_get_path('module', 'apic_api') . '/explorer/app/version.json';
      if (file_exists($filename)) {
        $contents = file_get_contents(drupal_get_path('module', 'apic_api') . '/explorer/app/version.json');
        $json = json_decode($contents, TRUE);
        $markup .= '<p>' . t('API Explorer version %ver (%build)', array(
            '%ver' => $json['version']['version'],
            '%build' => $json['version']['buildDate']
          )) . '</p>';
      }
    }

    $build = array(
      '#type' => 'markup',
      '#markup' => $markup,
    );

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $version);
    return $build;
  }

  public function setConsumerorg($orgUrl = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $orgUrl);

    // orgUrl may have been escaped by us previous so convert it back
    if(strpos($orgUrl,"_") !== false) {
        $orgUrl = str_replace("_","/", $orgUrl);
    }

    // check the specified org ID is actually one we're a member of
    $orgs = $this->consumerOrgService->getList();
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $orgUrl);

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      if (in_array($nid, $orgs)) {
        $this->userUtils->setCurrentConsumerorg($orgUrl);
        $this->userUtils->setOrgSessionData();
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->redirect('<front>');
  }

  public function getStarted() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $userHasAppManage = $userUtils->checkHasPermission('app:manage');
    $config = \Drupal::config('ibm_apim.settings');
    $show_register_app = $config->get('show_register_app');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return array(
      '#theme' => 'ibm_apim_get_started',
      '#userHasAppManage' => $userHasAppManage,
      '#show_register_app' => $show_register_app,
      '#attached' => array(
        'library' => 'ibm_apim/core',
      ),
    );
  }

  public function support() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $moduleHandler = \Drupal::service('module_handler');


    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return array(
      '#theme' => 'ibm_apim_support',
      '#forum' => $moduleHandler->moduleExists('forum'),
      '#contact' => $moduleHandler->moduleExists('contact_block'),
      '#social' => $moduleHandler->moduleExists('social_media_links'),
      '#attached' => array(
        'library' => 'ibm_apim/core',
      ),
    );
  }

  public function noperms() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return array(
      '#theme' => 'ibm_apim_noperms',
      '#attached' => array(
        'library' => 'ibm_apim/core',
      ),
    );
  }
}