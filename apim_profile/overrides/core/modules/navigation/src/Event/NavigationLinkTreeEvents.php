<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
declare(strict_types=1);

namespace Drupal\navigation\Event;

/**
 * Defines events for the navigation link tree.
 *
 * @see \Drupal\navigation\Event\NavigationLinkTreeEvents
 */
final class NavigationLinkTreeEvents {

  /**
   * Name of the event fired when manipulating the navigation menu link tree.
   *
   * This event allows modules to perform an action whenever the navigation link
   * tree is being built.
   *
   * @Event
   *
   * @see \Drupal\navigation\Event\NavigationLinkTreeEvents
   *
   * @var string
   */
  const MANIPULATE = 'navigation_link_tree.manipulate';

}