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

use Drupal\Core\Url;
use Drupal\system\Controller\SystemController;

/**
 * Class IbmApimThemeController
 *
 * @package Drupal\ibm_apim\Controller
 */
class IbmApimThemeController extends SystemController {

  /**
   * {@inheritdoc}
   */
  public function themesPage() {
    // Let the original controller do what it should
    $build = parent::themesPage();
    if (is_array($build) && !empty($build)) {
      foreach ($build as $buildKey => $buildComponent) {
        if (isset($buildComponent['#theme_groups']['installed']) && !empty($buildComponent['#theme_groups']['installed'])) {
          foreach ($buildComponent['#theme_groups']['installed'] as $installedKey => $theme_group) {
            if (isset($theme_group->operations) && !empty($theme_group->operations)) {
              foreach ($theme_group->operations as $operationKey => $operation) {
                if (isset($operation['url']) && $operation['url']->getRouteName() === 'system.theme_uninstall') {
                  $build[$buildKey]['#theme_groups']['installed'][$installedKey]->operations[$operationKey]['title'] = t('Disable');
                  $arguments = $build[$buildKey]['#theme_groups']['installed'][$installedKey]->operations[$operationKey]['attributes']['title']->getArguments();
                  $build[$buildKey]['#theme_groups']['installed'][$installedKey]->operations[$operationKey]['attributes']['title'] = t('Disable @theme theme', $arguments);
                }
              }
            }
          }
        }
        if (isset($buildComponent['#theme_groups']['uninstalled']) && !empty($buildComponent['#theme_groups']['uninstalled'])) {
          foreach ($buildComponent['#theme_groups']['uninstalled'] as $installedKey => $theme_group) {
            if (isset($theme_group->operations) && !empty($theme_group->operations)) {
              foreach ($theme_group->operations as $operationKey => $operation) {
                if (isset($operation['url']) && $operation['url']->getRouteName() === 'system.theme_install') {
                  $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->operations[$operationKey]['title'] = t('Enable');
                  $arguments = $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->operations[$operationKey]['attributes']['title']->getArguments();
                  $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->operations[$operationKey]['attributes']['title'] = t('Enable @theme theme', $arguments);
                }
                if (isset($operation['url']) && $operation['url']->getRouteName() === 'system.theme_set_default') {
                  $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->operations[$operationKey]['title'] = t('Enable and set as default');
                  $arguments = $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->operations[$operationKey]['attributes']['title']->getArguments();
                  $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->operations[$operationKey]['attributes']['title'] = t('Enable @theme as default theme', $arguments);
                }
              }
              // add Delete option to custom themes
              $themeInstallDir = \Drupal::getContainer()->getParameter('site.path') . '/themes';
              if (mb_strpos($theme_group->getPath(), $themeInstallDir) === 0) {

                $themeInfo = $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->info;
                $themeMachineName = $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->getName();
                $query['theme'] = $themeMachineName;
                $build[$buildKey]['#theme_groups']['uninstalled'][$installedKey]->operations[] = [
                  'title' => $this->t('Delete'),
                  'url' => Url::fromRoute('ibm_apim.theme_delete'),
                  'query' => $query,
                  'attributes' => ['title' => $this->t('Delete @theme theme', ['@theme' => $themeInfo['name']])],
                ];
              }
            }
          }
        }

        if (isset($buildComponent['#theme_group_titles'])) {
          // this is to avoid 'Cannot use string offset as an array' errors in php
          $array = &$build[$buildKey];
          $array['#theme_group_titles']['installed'] = $this->formatPlural(count($build[$buildKey]['#theme_groups']['installed']), 'Enabled theme', 'Enabled themes');
          if (!empty($array['#theme_groups']['uninstalled'])) {
            $array['#theme_group_titles']['uninstalled'] = $this->formatPlural(count($build[$buildKey]['#theme_groups']['uninstalled']), 'Disabled theme', 'Disabled themes');
          }
        }
      }
    }
    return $build;
  }
}
