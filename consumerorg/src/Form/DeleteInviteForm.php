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

namespace Drupal\consumerorg\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Delete user invitation form for consumerorg members.
 */
class DeleteInviteForm extends ConfirmFormBase {

  /**
   * The node representing the consumerorg.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $orgId;

  /**
   * The id of the invitation to delete
   *
   * @var string
   */
  protected $inviteId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_delete_invitation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $inviteId = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $current_user = \Drupal::currentUser();
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    if (!$userUtils->checkHasPermission('member:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = array();
      $form['description'] = array('#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>');

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => 'myorg',
        '#attributes' => array('class' => array('button'))
      );
      $themeHandler = \Drupal::service('theme_handler');
      if ($themeHandler->themeExists('bootstrap')) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }

      return $form;
    } else {
      $org = $userUtils->getCurrentConsumerOrg();
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $org['url']);
      $nids = $query->execute();
      $this->orgId = NULL;
      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $this->orgId = Node::load($nid);
      }
      $this->inviteId = Html::escape($inviteId);
      $found = FALSE;
      foreach ($this->orgId->consumerorg_invites->getValue() as $arrayValue) {
        $invite = unserialize($arrayValue['value']);
        if ($invite['id'] == $this->inviteId) {
          $found = TRUE;
        }
      }
      if ($found != TRUE) {
        // return error as inviteId not in this consumerorg
        throw new NotFoundHttpException(t('Specified invite not found in this consumer organization.'));
      }
      $form =  parent::buildForm($form, $form_state);
      $themeHandler = \Drupal::service('theme_handler');
      if ($themeHandler->themeExists('bootstrap')) {
        if (isset($form['actions']['submit'])) {
          $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('trash');
        }
        if (isset($form['actions']['cancel'])) {
          $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
        }
      }

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to delete the invitation to this user?');
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
    return $this->t('Are you sure you want to delete the invitation to this user?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('ibm_apim.myorg');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $orgid = $this->orgId->consumerorg_id->value;

    $url = '/orgs/' . $orgid . '/member-invitations/' . $this->inviteId;
    $result = ApicRest::delete($url);
    if (isset($result) && $result->code === 200) {
      // remove from our db too
      $new_invite_list = array();
      foreach ($this->orgId->consumerorg_invites->getValue() as $arrayValue) {
        $invite = unserialize($arrayValue['value']);
        if ($invite['id'] !== $this->inviteId) {
          $new_invite_list[] = serialize($invite);
        }
      }
      $this->orgId->set('consumerorg_invites', $new_invite_list);
      $this->orgId->save();

      drupal_set_message(t('Invitation deleted.'));
      $current_user = \Drupal::currentUser();
      \Drupal::logger('consumerorg')->notice('Organization invitation @id deleted for @orgname by @username', array('@orgname' => $this->orgId->getTitle(), '@id' => $this->inviteId, '@username' => $current_user->getAccountName()));
    }
    // invalidate myorg page cache
    \Drupal::service('cache_tags.invalidator')->invalidateTags(array('myorg:url:' . $this->orgId->consumerorg_url->value));

    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
