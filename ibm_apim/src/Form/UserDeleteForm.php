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

namespace Drupal\ibm_apim\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Delete form for users.
 */
class UserDeleteForm extends ConfirmFormBase {


  private $current_org_owner;

  private $owner_of_orgs;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $current_user = \Drupal::currentUser();
    $userUtils = \Drupal::service('ibm_apim.user_utils');

    $this->owner_of_orgs = $userUtils->loadOwnedConsumerorgs();
    if (sizeof($this->owner_of_orgs) > 1) {
      $message = t('You cannot delete your account because you own more than 1 organization.');
      drupal_set_message($message, 'error');

      $form = array();
      $form['description'] = ['#markup' => t('You are the owner of multiple consumer organizations. You can delete your account only when you are the owner of a single organization. Please transfer the ownership of, or delete, the other organizations before you delete your account.')];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => '<front>',
      ];

      return $form;
    }
    else {
      $org = $userUtils->getCurrentConsumerOrg();
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $org['url']);
      $nids = $query->execute();
      $this->orgId = NULL;
      if (isset($nids) && !empty($nids)) {
        $productnid = array_shift($nids);
        $this->orgId = Node::load($productnid);
      }
      // TODO check current user is the devorg owner

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return parent::buildForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (sizeof($this->owner_of_orgs) == 1) {
      return $this->t('Are you sure you want to delete your account? This action cannot be undone. This action will also remove the organization you own. This permanently removes access to the organization, and all of its applications and subscriptions, for all members of the organization. Please note that once an organization has been deleted, it cannot be reactivated. You might want to consider changing ownership of your Developer organizations, before deleting your account.');
    }
    else {
      return $this->t('Are you sure you want to delete your account? This action cannot be undone.');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete your account?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/me';
    $result = ApicRest::delete($url);
    if (isset($result)) {
      // TODO need to handle deleting their devorg too

      drupal_set_message(t('Account deleted successfully.'));
      $current_user = \Drupal::currentUser();
      \Drupal::logger('consumerorg')->notice('Account deleted by @username', array(
          '@username' => $current_user->getAccountName()
        ));
      user_cancel(array('user_cancel_notify' => FALSE), $current_user->uid, 'user_cancel_reassign');
      // Since user_cancel() is not invoked via Form API, batch processing needs
      // to be invoked manually and should redirect to the front page after
      // completion.
      batch_process('');
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
