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
 * Remove form for consumerorg members.
 */
class RemoveUserForm extends ConfirmFormBase {

  /**
   * The node representing the consumerorg.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $orgId;

  /**
   * The id of the member to remove
   *
   * @var string
   */
  protected $memberId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_remove_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $memberId = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $current_user = \Drupal::currentUser();
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    if (!$userUtils->checkHasPermission('member:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = array();
      $form['description'] = array('#markup' => t('You do not have sufficient access to perform this action.'));

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => 'myorg',
      );

      return $form;
    } else {
      $org = $userUtils->getCurrentConsumerOrg();
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $org['url']);
      $nids = $query->execute();
      $this->org = NULL;
      if (isset($nids) && !empty($nids)) {
        $productnid = array_shift($nids);
        $this->org = Node::load($productnid);
      }
      $this->memberId = Html::escape($memberId);
      $found = FALSE;
      foreach ($this->org->consumerorg_members->getValue() as $arrayValue) {
        $member = unserialize($arrayValue['value']);
        if ($member->getUser()->getId() == $this->memberId) {
          if ($current_user->getAccountName() == $member->getUser()->getUsername()) {
            // return error as cannot remove yourself
            throw new BadRequestHttpException(t('Cannot remove yourself from a consumer organization.'));
          }
          else {
            $found = TRUE;
            $this->member = $member;
          }
        }
      }
      if ($found != TRUE) {
        // return error as memberId not in this consumerorg
        throw new NotFoundHttpException(t('Specified member not found in this consumer organization.'));
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return parent::buildForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to remove the user <em>@user?</em>', array('@user' => $this->member->getUser()->getUsername()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the user <em>@user?</em>', array('@user' => $this->member->getUser()->getUsername()));
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
    $member_url = $this->member->getUrl();

    $result = ApicRest::delete($member_url);

    if (isset($result)) {
      // remove from our db too
      $new_member_list = array();
      foreach ($this->org->consumerorg_members->getValue() as $arrayValue) {
        $member = unserialize($arrayValue['value']);
        if ($member->getUser()->getId() != $this->memberId) {
          $new_member_list[] = serialize($member);
        }
      }
      $this->org->set('consumerorg_members', $new_member_list);
      $this->org->save();
      drupal_set_message(t('User removed successfully.'));
      $current_user = \Drupal::currentUser();
      \Drupal::logger('consumerorg')->notice('Organization member @member removed from @orgname by @username', array('@orgname' => $this->org->getTitle(), '@member' => basename($this->member->getUrl()), '@username' => $current_user->getAccountName()));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
