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

namespace Drupal\ibm_apim\Controller;

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\Messenger;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class IbmApimController
 *
 * @package Drupal\ibm_apim\Controller
 */
class IbmApimController extends ControllerBase {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected ConsumerOrgService $consumerOrgService;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * IbmApimController constructor.
   *
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(UserUtils $userUtils,
                              ConsumerOrgService $consumer_org_service, Messenger $messenger) {
    $this->userUtils = $userUtils;
    $this->consumerOrgService = $consumer_org_service;
    $this->messenger = $messenger;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Controller\ControllerBase|\Drupal\ibm_apim\Controller\IbmApimController
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.consumerorg'),
      $container->get('messenger')
    );
  }

  /**
   * @return array
   * @throws \JsonException
   */
  public function version(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $version = '';

    $filename = 	\Drupal::service('extension.list.profile')->getPath('apim_profile') . '/apic_version.yaml';
    if (file_exists($filename)) {
      $yaml = yaml_parse_file(	\Drupal::service('extension.list.profile')->getPath('apim_profile') . '/apic_version.yaml');
      if (isset($yaml['version'])) {
        $version .= $yaml['version'];
      }
      if (isset($yaml['build'])) {
        $version .= '( ' . $yaml['build'] . ' )';
      }
    }
    $markup = '<p>' . t('IBM API Developer Portal version %ver', ['%ver' => $version]) . '</p>';
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('apic_api')) {
      $filename = \Drupal::service('extension.list.module')->getPath('apic_api') . '/explorer/app/version.json';
      if (file_exists($filename)) {
        $contents = file_get_contents(\Drupal::service('extension.list.module')->getPath('apic_api') . '/explorer/app/version.json');
        $json = json_decode($contents, TRUE, 512, JSON_THROW_ON_ERROR);
        $markup .= '<p>' . t('API Explorer version %ver (%build)', [
            '%ver' => $json['version']['version'],
            '%build' => $json['version']['buildDate'],
          ]) . '</p>';
      }
    }
    $filename = \Drupal::service('extension.list.module')->getPath('ibm_apim') . '/analytics/version.json';
    if (file_exists($filename)) {
      $contents = file_get_contents(\Drupal::service('extension.list.module')->getPath('ibm_apim') . '/analytics/version.json');
      $json = json_decode($contents, TRUE, 512, JSON_THROW_ON_ERROR);
      $markup .= '<p>' . t('Consumer Analytics version %ver (%build)', [
          '%ver' => $json['version']['version'],
          '%build' => $json['version']['buildDate'],
        ]) . '</p>';
    }

    $build = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $version);
    return $build;
  }

  /**
   * @param null $orgUrl
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function setConsumerorg($orgUrl = NULL): RedirectResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $orgUrl);

    if ($orgUrl === NULL) {
      $this->messenger->addError(t('No consumer organization provided. Aborting'));
    }
    else {
      // orgUrl may have been escaped by us previous so convert it back
      if (strpos($orgUrl, '_') !== FALSE) {
        $orgUrl = str_replace('_', '/', $orgUrl);
      }
      $success = FALSE;
      $title = '';
      // check the specified org ID is actually one we're a member of
      $orgs = $this->consumerOrgService->getList();
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $orgUrl);

      $nids = $query->execute();
      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $title = $node->getTitle();
          if (in_array($nid, $orgs, FALSE)) {
            $this->userUtils->setCurrentConsumerorg($orgUrl);
            $this->userUtils->setOrgSessionData();
            $success = TRUE;
          }
        }
      }
      if ($success === TRUE) {
        $this->messenger->addMessage(t('Switched consumer organization to @title.', ['@title' => $title]));
      }
      else {
        $this->messenger->addError(t('An error occurred switching consumer organization.'));
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->redirect('<front>');
  }

  /**
   * @return array
   */
  public function getStarted(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $userHasAppManage = $userUtils->checkHasPermission('app:manage');
    $config = \Drupal::config('ibm_apim.settings');
    $show_register_app = (boolean) $config->get('show_register_app');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return [
      '#theme' => 'ibm_apim_get_started',
      '#userHasAppManage' => $userHasAppManage,
      '#show_register_app' => $show_register_app,
      '#attached' => [
        'library' => 'ibm_apim/core',
      ],
    ];
  }

  /**
   * @return array
   */
  public function support(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $moduleHandler = \Drupal::service('module_handler');


    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return [
      '#theme' => 'ibm_apim_support',
      '#forum' => $moduleHandler->moduleExists('forum'),
      '#contact' => $moduleHandler->moduleExists('contact_block'),
      '#social' => $moduleHandler->moduleExists('social_media_links'),
      '#attached' => [
        'library' => 'ibm_apim/core',
      ],
    ];
  }

  /**
   * @return array
   */
  public function noperms(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return [
      '#theme' => 'ibm_apim_noperms',
      '#attached' => [
        'library' => 'ibm_apim/core',
      ],
    ];
  }

}