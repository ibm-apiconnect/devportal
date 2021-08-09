<?php

namespace Drupal\restrict_by_ip;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\restrict_by_ip\IPToolsInterface;
use Drupal\restrict_by_ip\Exception\IPOutOfRangeException;

/**
 * Class LoginFirewall.
 *
 * @package Drupal\restrict_by_ip
 */
class LoginFirewall implements LoginFirewallInterface {

  protected $ipTools;
  protected $config;
  protected $logger;
  protected $urlGenerator;

  public function __construct(
    IPToolsInterface $ip_tools,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    UnroutedUrlAssemblerInterface $url_generator) {

    $this->ipTools = $ip_tools;
    $this->config = $config_factory->get('restrict_by_ip.settings');
    $this->logger = $logger_factory->get('restrict_by_ip');
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(AccountInterface $account) {
    if ($account->isAuthenticated() && !$this->isLoginAllowed($account)) {
      $user_ip = $this->ipTools->getUserIP();

      // Log the error with the ip address.
      $this->logger->notice(t('Login denied from @ip for %name.', [
        '%name' => $account->getAccountName(),
        '@ip' => $user_ip,
      ]));

      user_logout();

      // Redirect after logout.
      $path = $this->config->get('error_page');
      $options = ['absolute' => TRUE];
      if ($path) {
        $redirect = $this->urlGenerator->assemble('base:' . $path, $options);
      }
      else {
        $redirect = Url::fromRoute('<current>', [], $options)->toString();
      }

      $response = new RedirectResponse($redirect, RedirectResponse::HTTP_FOUND);
      $response->send();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isLoginAllowed(AccountInterface $account) {
    if ($global = $this->checkGlobalRestriction()) {
      return TRUE;
    }

    if ($user = $this->checkUserRestriction($account)) {
      return TRUE;
    }

    // If there are no global or user restrictions set, allow login.
    if ($global === NULL && $user === NULL) {
      return TRUE;
    }

    // The global, user, or both restrictions failed, so deny.
    return FALSE;
  }

  /**
   * Validates the global IP restrictions, if set.
   *
   * @return null|bool
   *   Null if there is no global restrictions.
   *   True/False whether the current users IP is in global IP whitelist.
   */
  private function checkGlobalRestriction() {
    $global_data = $this->config->get('login_range');

    // If there is no global IP restriction configured, we allow all.
    if (strlen($global_data) == 0) {
      return NULL;
    }

    return $this->checkIpRestriction($global_data);
  }

  /**
   * Validates the user IP restrictions, if set.
   *
   * @param AccountInterface $account
   *   The user account to check IP restrictions against.
   *
   * @return null|bool
   *   Null if there is no user restrictions.
   *   True/False whether the current users IP is in user IP whitelist.
   */
  private function checkUserRestriction(AccountInterface $account) {
    $user_data = $this->config->get('user.' . $account->id());

    // If there is no user IP restriction configured, we allow all.
    if (strlen($user_data) == 0) {
      return NULL;
    }

    return $this->checkIpRestriction($user_data);
  }

  /**
   * Given IP restrictions, validate the current users IP is among them.
   *
   * @param string $ip
   *   A semi-colon delimited list of IP address ranges in CIDR notation.
   *
   * @return bool
   *   Whether the current users IP is in any given IP address range.
   */
  private function checkIpRestriction($ip) {
    $user_ip = $this->ipTools->getUserIP();

    $valid = FALSE;
    $ranges = explode(';', $ip);

    foreach ($ranges as $range) {
      try {
        $this->ipTools->validateCIDR($user_ip, $range);
      }
      catch (IPOutOfRangeException $e) {
        continue;
      }

      $valid = TRUE;
    }

    return $valid;
  }

}
