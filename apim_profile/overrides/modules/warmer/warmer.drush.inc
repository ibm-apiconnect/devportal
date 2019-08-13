<?php

use Drupal\warmer\Commands\WarmerCommands;

function warmer_drush_command() {

  $items['warmer-list'] = [
    'description' => 'Lists the warmer plugins',
    'aliases' => ['warmer:list'],
  ];
  $items['warmer-enqueue'] = [
    'description' => 'Updates the site config',
    'aliases' => ['warmer:enqueue'],
    'arguments' => [
      'warmer_ids' => 'Comma separated list of warmer ids',
    ],
  ];
  $items['warmer-setcdn'] = [
    'description' => 'Sets the CDN warmer',
    'aliases' => ['warmer:setcdn'],
    'arguments' => [
      'urls' => 'Comma separated list of urls',
    ],
  ];
  return $items;
}

/**
 * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
 */
function drush_warmer_list() {
  $commands = new WarmerCommands(\Drupal::service('plugin.manager.warmer'), \Drupal::service('warmer.queue_manager'), \Drupal::service('warmer.drush8queuecommand'));
  return $commands->list();
}

/**
 * @param $warmer_ids
 *
 * @throws \Exception
 */
function drush_warmer_enqueue($warmer_ids) {
  $commands = new WarmerCommands(\Drupal::service('plugin.manager.warmer'), \Drupal::service('warmer.queue_manager'), \Drupal::service('warmer.drush8queuecommand'));
  $warmer_ids = explode(',', $warmer_ids);
  return $commands->enqueue($warmer_ids, ['run-queue' => TRUE]);
}

/**
 * @param $urls
 */
function drush_warmer_setcdn($urls) {
  $urls = explode(',', $urls);
  $existingConfig = \Drupal::config('warmer.settings');
  $headers = [];
  $frequency = '300';
  $batchSize = '50';
  if ($existingConfig !== null) {
    $cdn = $existingConfig->get('cdn');
    if (isset($cdn['urls'])) {
      $urls = array_merge($urls,$cdn['urls']);
      $urls = array_unique($urls);
    }
    if (isset($cdn['headers'])) {
      $headers = $cdn['headers'];
    }
    if (isset($cdn['frequency'])) {
      $frequency = $cdn['frequency'];
    }
    if (isset($cdn['batchSize'])) {
      $batchSize = $cdn['batchSize'];
    }
  }
  \Drupal::service('config.factory')
    ->getEditable('warmer.settings')
    ->set('cdn', ['id' => 'cdn', 'frequency' => $frequency, 'batchSize' => $batchSize, 'urls' => $urls, 'headers' => $headers])
    ->save();
}