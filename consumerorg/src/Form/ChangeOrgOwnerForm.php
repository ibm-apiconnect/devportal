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

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Form to edit the consumerorg owner.
 */
class ChangeOrgOwnerForm extends FormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $orgNode;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_change_owner_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    if (!$userUtils->checkHasPermission('settings:manage')) {
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
      $this->orgNode = NULL;
      if (isset($nids) && !empty($nids)) {
        $productnid = array_shift($nids);
        $this->orgNode = Node::load($productnid);
      }
      $members = \Drupal::service('ibm_apim.consumerorg')->getMembers($this->orgNode->consumerorg_url->value);
      if ($members) {
        $values = array();
        // If there is only one member, do not allow change
        if (count($members) === 1) {
          drupal_set_message(t('Cannot change ownership: only one user in developer organization %org', array("%org" => $this->orgNode->getTitle())), 'error');
        }
        else {
          foreach ($members as $member) {
            // Don't include the current user in the list
            $user = User::load(\Drupal::currentUser()->id());
            if ($member->getUser()->getUrl() != $user->get('apic_url')->value) {
              $values[$member->getUser()->getUrl()] = $member->getUser()->getUsername();
            }
          }
          $form['new_owner'] = array(
            '#title' => t('New Owner'),
            '#type' => 'select',
            '#description' => t("Select the user to take ownership."),
            '#options' => $values
          );

          // TODO : we need to display a list of roles here that the old owner.
          // These are the roles to assign to the old owner now that he isn't the owner any more

          $form['actions']['#type'] = 'actions';
          $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
          );
        }
      }
      else {
        drupal_set_message(t('Failed to retrieve member list for developer organization %org', array("%org" => $this->orgNode->getTitle())), 'error');

        $form = array();
        $form['description'] = array('#markup' => t('Could not get member list for this organization so can not transfer ownership.'));

        $form['cancel'] = array(
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => Url::fromRoute('ibm_apim.myorg'),
        );
        return $form;
      }

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->redirect('ibm_apim.myorg');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $new_owner = $form_state->getValue('new_owner');
    $orgurl = $this->orgNode->consumerorg_url->value;

    if (empty($new_owner)) {
      drupal_set_message(t('A new owner is required.'), 'error');
    }
    else {
      // update APIm
      $selected_user_url = \Drupal::service('ibm_apim.apim_utils')->createFullyQualifiedUrl($new_owner);
      $newdata = array('new_owner_associate_url' => $selected_user_url);

      $url = $orgurl . '/transfer-owner';
      $result = ApicRest::post($url, json_encode($newdata));
      if (isset($result) && ($result->code == 200 && $result->code < 300)) {
        // update our db too
        $this->orgId->set('consumerorg_owner', $new_owner);
        $this->orgId->save();

        drupal_set_message(t('Organization name updated.'));
        $current_user = \Drupal::currentUser();
        \Drupal::logger('consumerorg')->notice('Consumer organization owner for @orgname changed by @username', array(
          '@orgname' => $this->orgId->getTitle(),
          '@username' => $current_user->getAccountName()
        ));

        $session_store = \Drupal::service('tempstore.private')->get('ibm_apim');
        $session_store->set('consumer_organizations', NULL);

        $userUtils = \Drupal::service('ibm_apim.user_utils');
        $userUtils->refreshUserData();
        $userUtils->setCurrentConsumerorg($orgurl);
        \Drupal::service('cache_tags.invalidator')
          ->invalidateTags(array('config:block.block.consumerorganizationselection'));
      }
    }
    $form_state->setRedirectUrl(Url::fromRoute('ibm_apim.myorg'));
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
