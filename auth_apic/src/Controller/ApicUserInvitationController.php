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

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ibm_apim\Service\SiteConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApicUserInvitationController extends ControllerBase {

  protected $jwtParser;
  protected $siteConfig;

  public function __construct(TokenParserInterface $tokenParser, SiteConfig $site_config) {
    $this->jwtParser = $tokenParser;
    $this->siteConfig = $site_config;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('auth_apic.jwtparser'), $container->get('ibm_apim.site_config'));
  }

  public function process() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $invitationToken = \Drupal::request()->query->get('activation');
    if(empty($invitationToken)) {
      drupal_set_message(t('Missing invitation token. Unable to proceed.'), 'error');
      return $this->redirect('<front>');
    }

    $jwt = $this->jwtParser->parse($invitationToken);
    if ($jwt === NULL || $jwt->getUrl() === NULL) {
      drupal_set_message(t('Invalid invitation token. Contact the system administrator for assistance'), 'error');
      \Drupal::logger('auth_apic')->notice('Invalid invitation Token: %token', array("%token" => $jwt));
      return $this->redirect('<front>');
    }

    // The idea here is that we already have user registration and sign-in forms
    // and we want to re-use those rather than creating essentially duplicates
    // for this slightly different flow. So we set a session variable here to signal
    // that we are on this invited user flow so that the reused sign-in and create
    // account forms know to behave differently.
    if($jwt) {
      $_SESSION['auth_apic']['invitation_object'] = $jwt;

      // check the user email address and attempt to find a matching local account
      $invited_email = $jwt->getPayload()['email'];
      $existing_account = \Drupal::service('auth_apic.usermanager')->findUserInDatabase($invited_email);

      // redirect based on whether we think this user has an account or needs to register
      if(isset($existing_account) && $existing_account !== FALSE) {
        return $this->redirect('user.login');
      } else {
        return $this->redirect('user.register');
      }
    }
    else {
      $contact_link = \Drupal::l(t('contact'), \Drupal\Core\Url::fromRoute('contact.site_page'));
      drupal_set_message(t('Unable to proceed with invitation process. @contact_link the site administrator.', array('@contact_link' => $contact_link)));
      return $this->redirect('<front>');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
