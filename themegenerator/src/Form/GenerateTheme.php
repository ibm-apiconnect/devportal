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
namespace Drupal\themegenerator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\themegenerator\Generator;

/**
 * Self sign up / create new user form.
 */
class GenerateTheme extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'themegenerator_generate_theme';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $intro_text = '<p>' . t('The first step in customizing the branding of your Developer Portal is to create a custom sub-theme.') . '</p>';
    $intro_text .= '<p>' . t('Complete the form below and you will be presented with a custom sub-theme to download.') . '</p>';
    $intro_text .= '<p>' . t("Your custom sub-theme will inherit all styling from the APIC 'connect_theme' unless overridden in your overrides.css file. Add custom CSS, JS, or Drupal hooks as explained in the documentation:") . ' <a href="https://www.ibm.com/support/knowledgecenter/en/SSMNED_2018/com.ibm.apic.devportal.doc/tapic_portal_using_subtheme_drupal8.html" target="_new">' . t('Knowledge Center') . '</a></p>';

    $form['intro'] = [
      '#markup' => $intro_text
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Sub-theme name'),
      '#required' => TRUE,
      '#description' => t("A custom theme name, for example: 'mycustom_theme' or 'banka_theme'. The name does not need to end in '_theme' but it is a common convention."),
      '#size' => 20,
      '#maxlength' => 20
    ];

    $options = array(
      'css' => t('CSS'),
      'scss' => t('SCSS')
    );

    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Sub-theme type'),
      '#options' => $options,
      '#description' => t('Your sub-theme can be setup to use either CSS or SCSS. SCSS is an extension to CSS and is for more advanced theme developers.'),
      '#default_value' => 'css',
    );

    $template_options = array(
      'connect_theme' => t('Default'),
      'mono' => t('Business Mono'),
      'blue' => t('Sapphire Blue'),
      'green' => t('Emerald Green'),
      'brown' => t('Golden Brown'),
      'red' => t('Ruby Red')
    );

    $form['template'] = array(
      '#type' => 'radios',
      '#title' => t('Template'),
      '#options' => $template_options,
      '#description' => t('Your sub-theme can use one of several different base templates, either the default connect_theme, or one of several different colour variants.'),
      '#default_value' => 'connect_theme',
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Generate'),
    ];

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $name = $form_state->getValue('name');

    if (!isset($name) || empty($name)) {
      $form_state->setErrorByName('name', $this->t('Sub-theme name is a required field.'));
    }
    if (!preg_match('/^[a-z0-9_\-]+$/', $name)) {
      $form_state->setErrorByName('name', $this->t('The sub-theme name can only contain the following characters: a-z0-9_-'));
    }
    if (strlen($name) > 20) {
      $form_state->setErrorByName('name', $this->t('The sub-theme name must be less than 20 characters long.'));
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $name = trim($form_state->getValue('name'));
    $type = trim($form_state->getValue('type'));
    if ($type != 'scss') {
      $type = 'css';
    }
    $template = trim($form_state->getValue('template'));
    if (!isset($template)) {
      $template = 'connect_theme';
    }

    $theme = Generator::generate($name, $type, $template);

    if (isset($theme) && !empty($theme)) {
      $url = file_create_url($theme['zipPath']);
      $messageHtml = '<a href="' . $url . '">' . $name . '.zip</a>';
      $messageHtml = \Drupal\Core\Render\Markup::create($messageHtml);
      drupal_set_message(t('Success. Your sub-theme can be downloaded here: @htmlLink. This download will be available for 24 hours.', array(
        '@htmlLink' => $messageHtml
      )));
    }
    else {
      drupal_set_message(t('An error has occurred.'), 'error');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}