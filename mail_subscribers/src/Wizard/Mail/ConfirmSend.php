<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class ConfirmSend extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mail_subscribers_wizard_confirm_send';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $cached_values = $form_state->getTemporaryValue('wizard');
    $objectType = $cached_values['objectType'];
    $subject = $cached_values['subject'];
    $message = $cached_values['message'];
    $role = $cached_values['role'];
    $direct = $cached_values['direct'];
    $carbon_copy = $cached_values['carbon_copy'];
    $send_original = $cached_values['send_original'];
    $send_unique = $cached_values['send_unique'];
    $priority = $cached_values['priority'];
    $receipt = $cached_values['receipt'];
    $headers = $cached_values['headers'];

    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Please confirm the below settings are correct and then click Next to send the message.') . '</p>',
      '#weight' => -100,
    ];

    if ($objectType === 'product') {
      $nodes = Node::loadMultiple($cached_values['products']);
      if (!empty($nodes)) {
        if ($role === 'owners') {
          $titles = [];
          foreach ($nodes as $node) {
            $titles[] = $node->getTitle();
          }
          $titles = implode(', ', $titles);
          $form['product'] = [
            '#markup' => '<p>' . $this->t('Email all owners of consumer organizations subscribing to any of the following products: @titles', ['@titles' => $titles]) . '</p>',
            '#weight' => -80,
          ];
        }
        else {
          $titles = [];
          foreach ($nodes as $node) {
            $titles[] = $node->getTitle();
          }
          $titles = implode(', ', $titles);
          $form['product'] = [
            '#markup' => '<p>' . $this->t('Email all members of consumer organizations subscribing to any of the following products: @titles', ['@titles' => $titles]) . '</p>',
            '#weight' => -80,
          ];
        }
      }
    }
    elseif ($objectType === 'plan') {
      $plans = $cached_values['plans'];
      $nodes = Node::loadMultiple($cached_values['products']);
      if (!empty($nodes)) {
        if ($role === 'owners') {
          $plansList = '';
          foreach ($nodes as $node) {
            $productTitle = $node->getTitle();
            $planTitle = $plans[$node->id()]['title'];

            // Add each product and plan pair to the list
            $plansList .= $this->t('@product: @plan', [
              '@product' => $productTitle,
              '@plan' => $planTitle,
            ]) . '<br />';
          }

          $form['plan'] = [
            '#markup' => '<p>' . $this->t('Emailing all consumer organization owners subscribing to the following plans:') . '</p>' .
            '<p>' . $plansList . '</p>',
            '#weight' => -80,
          ];
        }
        else {
          $plansList = '';
          foreach ($nodes as $node) {
            $productTitle = $node->getTitle();
            $planTitle = $plans[$node->id()]['title'];

            // Add each product and plan pair to the list
            $plansList .= $this->t('@product: @plan', [
              '@product' => $productTitle,
              '@plan' => $planTitle,
            ]) . '<br />';
          }

          $form['plan'] = [
            '#markup' => '<p>' . $this->t('Emailing all consumer organization members subscribing to the following plans:') . '</p>' .
              '<p>' . $plansList . '</p>',
            '#weight' => -80,
          ];
        }
      }
    }
    elseif ($objectType === 'api') {
      $nodes = Node::loadMultiple($cached_values['apis']);
      if ($role === 'owners') {

        $titles = [];
        foreach ($nodes as $node) {
          $titles[] = $node->getTitle();
        }
        $titles = implode(', ', $titles);

        $form['api'] = [
          '#markup' => '<p>' . $this->t('Email all owners of consumer organizations subscribing to plans containing APIs: %titles.', ['%titles' => $titles]) . '</p>',
          '#weight' => -80,
        ];
      }
      else {
        $titles = [];
        foreach ($nodes as $node) {
          $titles[] = $node->getTitle();
        }
        $titles = implode(', ', $titles);

        $form['api'] = [
          '#markup' => '<p>' . $this->t('Email all members of consumer organizations subscribing to plans containing APIs: %titles.', ['%titles' => $titles]) . '</p>',
          '#weight' => -80,
        ];
      }
    } elseif ($objectType === 'consumerorg') {
      if ($role === 'owners') {
        $form['all'] = [
          '#markup' => '<p>' . $this->t('Email all owners of all consumer organization the following message:') . '</p>',
          '#weight' => -80,
        ];
      }
      else {
        $form['all'] = [
          '#markup' => '<p>' . $this->t('Email all members of all consumer organization the following message:') . '</p>',
          '#weight' => -80,
        ];
      }
    } elseif ($objectType === 'all') {
      if ($role === 'owners') {
        $form['all'] = [
          '#markup' => '<p>' . $this->t('Email all owners of all consumer organization the following message:') . '</p>',
          '#weight' => -80,
        ];
      }
      else {
        $form['all'] = [
          '#markup' => '<p>' . $this->t('Email all members of all consumer organization the following message:') . '</p>',
          '#weight' => -80,
        ];
      }
    }
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $subject,
      '#disabled' => TRUE,
      '#weight' => -70,
    ];
    $form['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#default_value' => $message['value'],
      '#wysiwyg' => FALSE,
      '#disabled' => TRUE,
      '#weight' => -60,
    ];

    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => [
        0 => $this->t('none'),
        1 => $this->t('highest'),
        2 => $this->t('high'),
        3 => $this->t('normal'),
        4 => $this->t('low'),
        5 => $this->t('lowest'),
      ],
      '#default_value' => $priority,
      '#disabled' => TRUE,
      '#weight' => -50,
    ];
    $form['receipt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Request receipt'),
      '#default_value' => $receipt,
      '#disabled' => TRUE,
      '#weight' => -40,
    ];
    $form['headers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional headers'),
      '#rows' => 4,
      '#default_value' => $headers,
      '#disabled' => TRUE,
      '#weight' => -30,
    ];

    $form['direct'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send the message directly using the Batch API.'),
      '#default_value' => $direct,
      '#disabled' => TRUE,
      '#weight' => -20,
    ];
    $form['send_unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only send one email per user even if they\'re in multiple lists.'),
      '#description' => $this->t("This will ensure each user only receives the email once, even if they're part of multiple selected apis, products, plans or consumer orgs."),
      '#default_value' => $send_unique,
      '#disabled' => TRUE,
      '#weight' => -20,
    ];
    $form['carbon_copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send a copy of the message to the copy recipient.'),
      '#description' => $this->t("This will send a copy of each email that is generated to the copy recipient. Because the email may contain context specific tokens, new email content is generated for each recipient with the correct token replacement. This option will send a copy of all generated emails to the copy recipient configured in the site settings."),
      '#default_value' => $carbon_copy,
      '#disabled' => TRUE,
      '#weight' => -10,
    ];
    $form['send_original'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send a copy of the original message to the copy recipient.'),
      '#description' => $this->t("This will send a single copy of the email to the copy recipient without the replacement of any tokens that may have been selected."),
      '#default_value' => $send_original,
      '#disabled' => TRUE,
      '#weight' => -10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $cached_values = $form_state->getTemporaryValue('wizard');

    $mailService = \Drupal::service('mail_subscribers.mail_service');

    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $config = \Drupal::config('mail_subscribers.settings');
    $site_config = \Drupal::config('system.site');
    $from = [];
    $from_name = $config->get('from_name');
    $from_mail = $config->get('from_mail');
    $from['name'] = ($from_mail !== NULL && !empty($from_name)) ? $from_name : $site_config->get('name');
    $from['mail'] = ($from_mail !== NULL && !empty($from_mail)) ? $from_mail : $site_config->get('mail');
    $rc = NULL;
    if ($cached_values['objectType'] === 'product' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailProductOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'product' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailProductMembers($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'plan' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailProductOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'plan' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailProductMembers($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'api' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailApiOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'api' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailApiMembers($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'consumerorg' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailConsumerorgOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'consumerorg' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailConsumerorgMembers($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'all' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailAllOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'all' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailAllMembers($cached_values, $from, $langcode);
    }

    $cached_values['result'] = $rc;

    $result_state = \Drupal::state()->get('mail_subscribers.result');
    if ($result_state === NULL) {
      $result_state = [];
    }
    if (isset($cached_values['instance'])) {
      $result_state[$cached_values['instance']] = $rc;
      // clear out any old results
      $TTL = 86400;
      $now = time();
      foreach ($result_state as $key => $value) {
        if ($now > ((int) $key + $TTL)) {
          unset($result_state[$key]);
        }
      }
      \Drupal::state()->set('mail_subscribers.result', $result_state);
    }
    $form_state->setTemporaryValue('wizard', $cached_values);
    \Drupal::service('tempstore.private')->get('mail_subscribers')->set('objectType', $cached_values['objectType']);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
