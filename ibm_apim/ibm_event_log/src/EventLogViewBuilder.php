<?php


namespace Drupal\ibm_event_log;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for EventLogs.
 */
class EventLogViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    /* @var \Drupal\ibm_event_log\Entity\EventLog $entity */
    $build['output'] = \Drupal::service('ibm_apim.event_log')->createOutputMessage($entity);
    $build['#attached']['library'][] = 'ibm_event_log/eventlog';
    return $build;
  }

}
