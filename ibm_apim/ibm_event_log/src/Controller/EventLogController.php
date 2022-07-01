<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2021, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_event_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\EventLogService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class EventLogController extends ControllerBase {

  /**
   * @var \Drupal\ibm_apim\Service\EventLogService
   */
  protected EventLogService $eventLogService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * EventLogController constructor.
   *
   * @param \Drupal\ibm_apim\Service\EventLogService $eventLogService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   */
  public function __construct(EventLogService $eventLogService, UserUtils $userUtils) {
    $this->eventLogService = $eventLogService;
    $this->userUtils = $userUtils;
  }

  public static function create(ContainerInterface $container): EventLogController {
    return new static($container->get('ibm_apim.event_log'), $container->get('ibm_apim.user_utils'));
  }

  /**
   * Provides the JSON output.
   */
  public function renderJson(): JsonResponse {

    return new JsonResponse([
      'data' => $this->getResults(),
      'method' => 'GET',
    ]);
  }

  /**
   * Get the event log entries for this consumer org.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException|\JsonException
   */
  public function getResults(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $org = $this->userUtils->getCurrentConsumerorg();
    $events = $this->eventLogService->getFeedForConsumerOrg($org['url'], 10);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $events;
  }

}
