<?php
/**
 * @file
 * Administration page callbacks for the pdurusers_export module.
 *
 * @ingroup pdurusers_export
 */

/**
 * Form builder. Configure pdurusers_export.
 *
 * @ingroup forms
 * @see     system_settings_form()
 */
function pdurusers_export_form($form, &$form_state) {

  $form['intro'] = array(
    '#markup' => '<p>' . t('This form is used to export a user mapping file for use when migrating an API Connect v5.0.x system to API Connect v2018. This mapping file is only required when using Portal Delegated User Registry since API Manager would not already have the necessary authentication provider information.', array()) . '</p>',
    '#weight' => -50
  );

  $default = variable_get('pdurusers_export_filename', strtolower(preg_replace('/\W+/', '_', variable_get('site_name', 'pdurusers_export')) . '_users'));
  $form['pdurusers_export_filename'] = array(
    '#type' => 'textfield',
    '#title' => t('Filename to save as'),
    '#default_value' => $default,
    '#required' => TRUE,
    '#field_suffix' => '.json',
  );

  $test_mode = variable_get('pdurusers_export_test_mode', FALSE);
  $form['pdurusers_export_test_mode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Preview mode (Enable to limit the export to only the first 10 users to check formatting.)'),
    '#default_value' => $test_mode,
  );

  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  // Add field to add 'blocked' users to the export as well.
  $form['advanced']['pdurusers_export_user_status'] = array(
    '#type' => 'select',
    '#title' => t('Export users with status'),
    '#default_value' => variable_get('pdurusers_export_user_status', 2),
    '#options' => array(
      1 => t('Active'),
      0 => t('Blocked'),
      2 => t('Both active and blocked'),
    ),
  );

  $options = array(-1 => t('- Default -'));
  foreach (range(128, 2048, 32) as $value) {
    $options[$value . 'M'] = format_size($value * 1024 * 1024);
  }
  $form['advanced']['pdurusers_export_memory_limit'] = array(
    '#type' => 'select',
    '#title' => t('Memory Limit', array(
      '@size' => format_size(variable_get('pdurusers_export_last_export_memory', 0)),
    )),
    '#description' => t('If you have many users you may need to set this value higher so you the web server does not run out of memory processing the export. <strong>Depending upon your server configuration, this may or may not have any effect!</strong> For more information refer to <a href="http://php.net/manual/en/function.ini-set.php">http://php.net/manual/en/function.ini-set.php</a>.'),
    '#default_value' => variable_get('pdurusers_export_memory_limit', 160),
    '#options' => $options,
  );

  $options = array(-1 => t('- Default -'));
  foreach (range(30, 1800, 30) as $value) {
    $options[$value] = format_interval($value);
  }
  $form['advanced']['pdurusers_export_max_execution'] = array(
    '#type' => 'select',
    '#title' => t('Maximum Execution Time', array(
      '@time' => format_interval(variable_get('pdurusers_export_last_export_time', 0)),
    )),
    '#description' => t('If you have many users you may need to set this value higher so you the web server does not timeout. <strong>Depending upon your server configuration, this may or may not have any effect!</strong> For more information refer to <a href="http://php.net/manual/en/function.set-time-limit.php">http://php.net/manual/en/function.set-time-limit.php</a>.'),
    '#default_value' => variable_get('pdurusers_export_max_execution', 60),
    '#options' => $options,
  );

  $form = system_settings_form($form);
  $form['actions']['submit']['#value'] = t('Download File');
  $form['#submit'][] = 'pdurusers_export_form_submit';

  return $form;
}

/**
 * Form submission handler
 */
function pdurusers_export_form_submit($form, &$form_state) {

  $values = $form_state['values'];

  $export = _pdurusers_export_values($values);

  drupal_add_http_header('Content-disposition', 'attachment; filename=' . $values['pdurusers_export_filename'] . '.json');
  drupal_add_http_header('Content-Type', 'application/json');
  echo json_encode($export, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT);

  drupal_exit();
}

function _pdurusers_export_values($values = array()) {
  if ($values['pdurusers_export_memory_limit'] != -1) {
    ini_set('memory_limit', $values['pdurusers_export_memory_limit']);
  }

  if ($values['pdurusers_export_max_execution'] != -1) {
    set_time_limit($values['pdurusers_export_max_execution']);
  }

  // Load the users
  $query = db_select('users', 'u');
  $query->leftJoin('authmap', 'a', 'u.uid = a.uid');
  $query->leftJoin('field_data_field_uid', 'f', 'u.uid = f.entity_id');
  $query->fields('u', array('uid', 'name', 'status'))->orderBy('uid');
  $query->addField('u', 'mail', 'email');
  $query->addField('u', 'created');
  $query->addField('u', 'data');
  $query->addField('u', 'access', 'last_access');
  $query->addField('u', 'login', 'last_login');
  $query->addField('u', 'pass');
  $query->addField('f', 'field_uid_value', 'apic_id');
  $query->addField('a', 'module');

  $query->condition('u.uid', 0, '<>'); // Exclude anonymous.
  $query->condition('u.uid', 1, '<>'); // Exclude admin.

  // Add status condition based on setting.
  $status = $values['pdurusers_export_user_status'] * 1;
  switch ($status) {
    case 0:
      $query->condition('status', 0);
      break;
    case 1:
      $query->condition('status', 1);
      break;
    case 2:
      // do not add a status condition because admin wants both
      break;
  }

  if ($values['pdurusers_export_test_mode']) {
    $query->range(0, 10);
  }

  $result = $query->execute()->fetchAll();

  $data_grid = array();

  // for ldap we need to gather a list of servers and their config so we can check users against them.
  $ldapservers = NULL;
  if (module_exists('ldap_servers')) {
    $ldapservers = ldap_servers_get_servers(NULL, 'all');
  }

  // Go through and create a row for each user.
  foreach ($result as $row) {
    $row = (array)$row;
    $row['last_login'] = empty($row['last_login']) ? '' : date(PDURUSERS_EXPORT_DEFAULT_DATE_FORMAT, $row['last_login']);
    $row['last_access'] = empty($row['last_access']) ? '' : date(PDURUSERS_EXPORT_DEFAULT_DATE_FORMAT, $row['last_access']);
    $row['created'] = empty($row['created']) ? '' : date(PDURUSERS_EXPORT_DEFAULT_DATE_FORMAT, $row['created']);
    if (!empty($row['data'])) {
      $row['data'] = unserialize($row['data']);
    }
    if (!empty($row['module'])) {
      if (mb_strpos($row['module'], 'ldap') === 0) {
        $row['provider'] = $row['module'];
        $row['module'] = 'ldap';
        $row['type'] = 'ldap';
        if ($ldapservers !== NULL ) {
          $row['ldap_servers'] = getLdapServersThatContainUser($ldapservers, $row['name']);
        }
      }
      elseif (mb_strpos($row['module'], 'openid_connect') === 0) {
        $row['provider'] = $row['module'];
        $row['module'] = 'oidc';
        $row['type'] = 'oidc';
      } else {
        $row['type'] = 'custom';
      }
    }
    else {
      if (!empty($row['data'])) {
        if (isset($row['data']['hybridauth']) && !empty($row['data']['hybridauth'])) {
          $row['type'] = 'oidc';
          $row['module'] = 'hybridauth';
          if (isset($row['data']['hybridauth']['provider']) && !empty($row['data']['hybridauth']['provider'])) {
            $row['provider'] = $row['data']['hybridauth']['provider'];
          }
        }
        elseif (isset($row['data']['ldap']) && !empty($row['data']['ldap'])) {
          $row['module'] = 'ldap';
          $row['type'] = 'ldap';
        }
        elseif (isset($row['data']['oidc_name']) && !empty($row['data']['oidc_name'])) {
          $row['module'] = 'oidc';
          $row['type'] = 'oidc';
        }
        else {
          $row['module'] = 'lur';
          $row['type'] = 'lur';
        }
      }
      else {
        $row['module'] = 'lur';
        $row['type'] = 'lur';
      }

    }
    // if not using LUR then dont want any hints of password hashes
    if ($row['module'] != 'lur') {
      unset($row['pass']);
    }
    // put all users from the same authmap on the same page
    if (!isset($data_grid[$row['module']])) {
      $data_grid[$row['module']] = array('config' => array('type' => $row['type'], 'module' => $row['module']), 'users' => array());
      switch ($row['module']) {
        case 'ldap':
          if ($ldapservers !== NULL) {
            $data_grid[$row['module']]['config']['servers'] = $ldapservers;
          }
          break;
        case 'oidc':
          // pull out OIDC config
          if (module_exists('openid_connect')) {
            $oidc_plugins = openid_connect_get_plugins(TRUE);
            if (isset($oidc_plugins)) {
              foreach($oidc_plugins as $key=>$client_plugin){
                module_load_include('inc', 'openid_connect', 'includes/openid_connect.admin');
                $client = openid_connect_get_client($client_plugin['name']);
                if (isset($client)) {
                  $oidc_plugins[$key]['endpoints'] = $client->getEndpoints();
                }
                $config = variable_get('openid_connect_client_' . $client_plugin['name'], NULL);
                if ($config !== NULL) {
                  $oidc_plugins[$key]['client_config'] = $config;
                }

              }
            }
            $data_grid[$row['module']]['config']['plugins'] = $oidc_plugins;
          }
          break;
        case 'hybridauth':
          // pull out hybridauth config
          if (module_exists('hybridauth')) {
            $enabled_providers = array_filter(variable_get('hybridauth_providers', array()));
            $data_grid[$row['module']]['config']['providers'] = $enabled_providers;
          }
          break;
      }
    }

    $data_grid[$row['module']]['users'][] = $row;
  }
  $config_data = _ibm_apim_get_apim_config();
  $path = drupal_get_path('module', 'ibm_apim') . '/ibm_apim.info';
  $info = drupal_parse_info_file($path);
  $export = array('modules' => $data_grid, 'catalog' => $config_data, 'version' => $info['version']);
  return $export;
}

/**
 * Gather all of the ldap servers a user is found in.
 * Checking ldap is an expensive operation so if there is only one server then just return that.
 *
 * @param $ldapservers
 * @param $username
 *
 * @return array ordered list of servers.
 */
function getLdapServersThatContainUser($ldapservers, $username): array {

  module_load_include('php', 'ldap_servers', 'LdapServer.class');
  $matches = [];
  $num_servers = sizeof($ldapservers);

  // We need to maintain an order on the list of servers we add to a user because of the way the d7 ldap module falls through the list of servers.
  // Unfortunately if this starts at 0 the array becomes a boring old array rather than an associative one (something is lost either in php
  // optimization or json serialization) so we will lose the explicit ordering. So that is why we start from 1.
  if ($num_servers === 1) {
    $matches = ['1' => array_shift($ldapservers)->sid];
  }
  else if ($num_servers > 1) {
    $count = 1;
    foreach ($ldapservers as $server) {
      $sid = $server->sid; // key for a server.
      $server = new LdapServer($sid);
      if ($server->userUserNameToExistingLdapEntry($username)) {
        $matches[$count++] = $sid;
      };

    }
  }

  return $matches;

}
