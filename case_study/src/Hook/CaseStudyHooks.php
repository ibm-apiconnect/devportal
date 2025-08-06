<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Provides APIC integration.
 */
namespace Drupal\case_study\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\page_manager\Entity\Page;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class CaseStudyHooks {
  /**
   * Add twig templates
   *
   * @param $existing
   * @param $type
   * @param $theme
   * @param $path
   *
   * @return array
   */

  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'productcasestudy_block' => [
        'variables' => [
          'productCaseStudies' => NULL,
        ],
      ],
      'casestudy_block' => [
        'variables' => [
          'caseStudies' => NULL,
        ],
      ],
    ];
  }

  /**
  * Implements hook_module_preuninstall().
  */
  #[Hook('module_preuninstall')]
  public function preInstall($module, bool $is_syncing) {
    if ($module == 'case_study') {
      $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
      $entities = $storage_handler->loadByProperties(["type" => "case_study"]);
      // Delete all case_study entities
      $storage_handler->delete($entities);

      $page = Page::load('welcome');
      // should only be one page variant, so we'll just grab the first one
      if ($page !== NULL) {
          $page_variants = $page->getVariants();
          reset($page_variants);
          $page_variant = array_values($page_variants)[0];
          $blocks = $page_variant->get('variant_settings')['blocks'];
          $blockUUID = '';
          foreach ($blocks as $blockKey => $blockValue) {
            if ($blockValue['id'] == 'casestudy_block') {
              $blockUUID = $blockKey;
              break;
            }
          }
          if (!empty($blockUUID)) {
            $variant_plugin = $page_variant->getVariantPlugin();
            $variant_plugin->removeBlock($blockUUID);
            $page_variant->save();
          }
      }
    }
  }

  /**
   * Need to use our custom content type templates
   *
   * @param $variables
   * @param $hook
   * @param $info
   */
  #[Hook('preprocess')]
  public function preprocess(&$variables, $hook, &$info) {
    if ($hook === 'node') {
      $contentTypeToShow = $variables['node']->bundle();
      if ($contentTypeToShow === 'case_study') {
        ibm_apim_entry_trace(__FUNCTION__, NULL);

        //Load the view mode names.
        $allViewModes = \Drupal::service('entity_display.repository')->getViewModes('node');
        //View mode for this request.
        $viewModeToUse = $variables['view_mode'];
        //Is the view mode defined for nodes?
        if (array_key_exists($viewModeToUse, $allViewModes)) {
          //Is there a template file for the view modes in the module?
          if ($viewModeToUse === 'full') {
            $templateFileName = 'node--' . $contentTypeToShow;
          }
          else {
            $templateFileName = 'node--' . $contentTypeToShow . '--' . $viewModeToUse;
          }

          $themeName = \Drupal::theme()->getActiveTheme()->getName();
          $themePath = \Drupal::theme()->getActiveTheme()->getPath();
          if (file_exists($themePath . '/templates/node/' . $templateFileName . '.html.twig')) {
            $info['theme path'] = \Drupal::service('extension.list.theme')->getPath($themeName);
            $info['path'] = \Drupal::service('extension.list.theme')->getPath($themeName) . '/templates/node';
            $info['template'] = $templateFileName;
          }
          elseif (file_exists($themePath . '/templates/' . $templateFileName . '.html.twig')) {
            $info['theme path'] = \Drupal::service('extension.list.theme')->getPath($themeName);
            $info['path'] = \Drupal::service('extension.list.theme')->getPath($themeName) . '/templates';
            $info['template'] = $templateFileName;
          }
          else {
            $templateFilePath = \Drupal::service('extension.list.module')->getPath('case_study') . '/templates/' . $templateFileName . '.html.twig';
            if (file_exists($templateFilePath)) {
              $info['theme path'] = \Drupal::service('extension.list.module')->getPath('case_study');
              $info['path'] = \Drupal::service('extension.list.module')->getPath('case_study') . '/templates';
              $info['template'] = $templateFileName;
            }
          }
        }
        $variables['images_path'] = base_path() . \Drupal::service('extension.list.module')->getPath('case_study');
        $variables['#attached']['library'][] = 'case_study/basic';

        ibm_apim_exit_trace(__FUNCTION__, NULL);
      }
    }
  }

  /**
   * Implements hook_form_alter().
   * * 
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   * 
   * This code is a hook_form_alter function that adds a new validation function to a form. 
   * The validation function checks if there are any repeated target IDs in the field_associated_api_products field. 
   * If there are, it sets an error message for the field. 
   */
  #[Hook('form_alter')]
  public static function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Add a new validation function.
    if (isset($form_id) && (($form_id === 'node_case_study_edit_form') || ($form_id === 'node_case_study_form'))) {
      $form['#validate'][] = [self::class, 'case_study_node_form_submit'];
    }
  }

  /**
   * Additional validate handler for case_study_form_alter().
   */
  public static function case_study_node_form_submit($form, FormStateInterface $form_state)
   {
    if (self::checkForRepeatedTargetIDs($form_state->getValue('field_associated_api_products'))) {
      $form_state->setErrorByName('Associated API Products', t('A product should not be selected more than once.'));
    }
  }

  /**
   * @param $array of associated $array
   * @return boolean
   * 
   * The checkForRepeatedTargetIDs function iterates through the $array of associated $arrays target IDs and checks if any of them are repeated. 
   * If a repeated target ID is found, the function returns true, indicating that there are repeated IDs. Otherwise, it returns false.
   */
  public static function checkForRepeatedTargetIDs($arr)
  {
    $seen = array();
    if (isset($arr)) {
      foreach ($arr as $value) {
        if (is_array($value) && isset($value['target_id'])) {
          $target_id = $value["target_id"];
          if (isset($seen[$target_id])) {
            return true;
          }
          $seen[$target_id] = true;
        }
      }
    }
    return false;
  }
 }