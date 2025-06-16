<?php

namespace Drupal\ibm_apim\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  const TYPE_URI = 'data/drupal/config';
  const DEVEL_EDIT_PATH = '/devel/config/edit/';

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'configSave',
      ConfigEvents::DELETE => 'configDelete',
    ];
  }


  private function getConfigID(ConfigCrudEvent $event) {
    return $GLOBALS['real_base_url'] . ConfigEventsSubscriber::DEVEL_EDIT_PATH . $event->getConfig()->getName();
  }


  /**
   * React to a config object being saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config crud event.
   */
  public function configSave(ConfigCrudEvent $event) {
    // Cannot rely on isNew to decide if this is a create as it is always FALSE. Best we can do
    // is assume that empty originalData means it is a create event
    \Drupal::service('ibm_apim.utils')->logAuditEvent(empty($event->getConfig()->getOriginal()) ? 'PORTAL_CREATE_SITE_CONFIG' : 'PORTAL_UPDATE_SITE_CONFIG', 'success', ConfigEventsSubscriber::TYPE_URI, $this->getConfigID($event), $event->getConfig()->getrawData());
  }

  /**
   * React to a config object being deleted.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config crud event.
   */
  public function configDelete(ConfigCrudEvent $event) {
    \Drupal::service('ibm_apim.utils')->logAuditEvent('PORTAL_DELETE_SITE_CONFIG', 'success', ConfigEventsSubscriber::TYPE_URI, $this->getConfigID($event));
  }

}