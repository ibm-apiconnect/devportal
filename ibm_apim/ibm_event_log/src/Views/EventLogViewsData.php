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

namespace Drupal\ibm_event_log\Views;

use Drupal\views\EntityViewsData;

/**
 * Class EventLogViewsData
 *
 * @package Drupal\ibm_event_log\Views
 */
class EventLogViewsData extends EntityViewsData {

  /**
   * @return array
   */
  public function getViewsData(): array {
    return parent::getViewsData();
  }

}