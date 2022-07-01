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

namespace Drupal\apic_app\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to upload an application image.
 */
class UploadImageForm extends FormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * UploadImageForm constructor.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): UploadImageForm {
    // Load the service required to construct this class
    return new static($container->get('messenger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_upload_image_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($appId !== NULL) {
      $this->node = $appId;
    }
    $form['intro'] = ['#markup' => '<p>' . t('Use this form to upload an image or icon for this application.') . '</p>'];

    $form['image'] = [
      '#type' => 'file',
      '#title' => t('Select an image'),
      '#description' => t('Upload a file, allowed extensions: jpg, jpeg, png, gif'),
    ];
    $form['remove'] = [
      '#type' => 'submit',
      '#value' => t('Remove image'),
      '#submit' => ['::removeImage'],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => ['::cancelForm'],
    ];
    $form['#attached']['library'][] = 'apic_app/basic';
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * @return \Drupal\Core\Url
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getCancelUrl(): Url {
    return $this->node->toUrl();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Form\FormStateInterface
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function cancelForm(array &$form, FormStateInterface $form_state): FormStateInterface {
    return $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Form\FormStateInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeImage(array &$form, FormStateInterface $form_state): FormStateInterface {
    // clear the image
    $fid = $this->node->get("application_image")->getValue();
    if (isset($fid[0]['target_id'])) {
      $img = \Drupal::entityTypeManager()->getStorage('file')->load($fid[0]['target_id']);
      if (isset($img)) {
        $img->delete();
      }
    }
    $this->node->set('application_image', NULL);
    $this->node->save();

    // Calling all modules implementing 'hook_apic_app_image_delete':
    \Drupal::moduleHandler()->invokeAll('apic_app_image_delete', ['node' => $this->node, 'appId' => $this->node->application_id->value]);

    $currentUser = \Drupal::currentUser();
    \Drupal::logger('apic_app')->notice('Application @appname image has been removed by @username', [
      '@appname' => $this->node->getTitle(),
      '@username' => $currentUser->getAccountName(),
    ]);

    $this->messenger->addMessage(t('Application image has been removed.'));

    return $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appId = $this->node->application_id->value;
    // Get name of the file that was left by form validate function.
    $appImgDir = 'private://application';
    \Drupal::service('file_system')->prepareDirectory($appImgDir, FileSystemInterface::CREATE_DIRECTORY);
    $fileTemp = file_save_upload('image', [
      'file_validate_is_image' => [], // Validates file is really an image.
      'file_validate_size' => [2 * 1024 * 1024], // file size less than 2mb
      'file_validate_extensions' => ['png gif jpg jpeg'],
    ], $appImgDir, FileSystemInterface::EXISTS_RENAME); // Validate extensions.

    if (empty($fileTemp)) {
      $this->messenger->addError(t('Failed to retrieve uploaded file.'));
    }
    else {
      // Make it a permanent file so it doesn't get deleted by cron.
      $fileTemp->status = FILE_STATUS_PERMANENT;
      // Save.
      $fileTemp->save();

      //delete old image
      $fid = $this->node->get("application_image")->getValue();
      if (isset($fid[0]['target_id'])) {
        $img = \Drupal::entityTypeManager()->getStorage('file')->load($fid[0]['target_id']);
        if (isset($img)) {
          $img->delete();
        }
      }

      // update local db
      $this->node->set('application_image', $fileTemp);
      $this->node->save();

      // Calling all modules implementing 'hook_apic_app_image_create':
      \Drupal::moduleHandler()->invokeAll('apic_app_image_create', ['node' => $this->node, 'appId' => $appId]);

      $currentUser = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appname image uploaded by @username', [
        '@appname' => $this->node->getTitle(),
        '@username' => $currentUser->getAccountName(),
      ]);
      $this->messenger->addMessage(t('Application image updated.'));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
