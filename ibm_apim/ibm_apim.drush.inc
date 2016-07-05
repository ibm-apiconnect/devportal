<?php

$GLOBALS['url'] = d()->uri;
$GLOBALS['profile'] = d()->profile;
$GLOBALS['install_locale'] = d()->language;
$GLOBALS['base_url'] = provision_get_base_url();

function ibm_apim_drush_command() {

  $items['ibm_apim-updatecatalog'] = array(
    'description' => 'Updates the catalog',
    'aliases' => array('ucat'),
    'arguments' => array(
      'catalog' => 'The JSON catalog payload',
    )
  );
  $items['ibm_apim-updateconfig'] = array(
    'description' => 'Updates the config',
    'aliases' => array('ucon'),
    'arguments' => array(
      'config' => 'The JSON config payload',
    )
  );
  $items['ibm_apim-bgsync'] = array(
    'description' => 'Runs background sync',
    'aliases' => array('bgsync')
  );
  $items['ibm_apim-listen'] = array(
  		'description' => 'Listens to stdin and runs the drush commands piped in',
  		'aliases' => array('listen')
  );
  $items['ibm_apim-send_welcome_email'] = array(
  		'description' => 'Sends a new welcome email',
  		'aliases' => array('welcomeemail')
  );
  
  return $items;
}

function _drush_ibm_apim_send_welcome_mail($url, $account, $language, $client_email, $onetime) {
	global $base_url;
	if ($client_email) {
		// Mail one time login URL and instructions.
		$from = variable_get('site_mail', ini_get('sendmail_from'));
		$mail_params['variables'] = array(
				'!username' => $account->name, '!site' => variable_get('site_name', 'Drupal'), '!login_url' => $onetime,
				'!uri' => $base_url, '!uri_brief' => preg_replace('!^https?://!', '', $base_url), '!mailto' => $account->mail,
				'!date' => format_date(time()), '!login_uri' => url('user', array('absolute' => TRUE)),
				'!edit_uri' => url('user/'. $account->uid .'/edit', array('absolute' => TRUE)));

		$mail_success = drupal_mail('ibm_apim_drush', 'welcome-admin', $account->mail, user_preferred_language($account), $mail_params, $from, TRUE);

		if ($mail_success) {
			drush_log(t('Sent welcome mail to @client', array('@client' => $client_email)), 'success');
		}
		else {
			drush_log(t('Could not send welcome mail to @client', array('@client' => $client_email)), 'warning');
		}
	}
}

function ibm_apim_drush_mail($key, &$message, $params) {
	require_once DRUPAL_ROOT . '/includes/install.inc';
	require_once '/var/aegir/.drush/provision/platform/provision_welcome_mail.inc';
	$message['subject'] = st($mail['subject'], $params['variables']);
	$message['body'][] = st($mail['body'], $params['variables']);
}

function drush_ibm_apim_send_welcome_email($client_email) {
	global $url;
	global $install_locale;
	global $base_url;

	// get the admin account
	$account = user_load(1);

	$edit['mail'] = $client_email;

	// temporarily disable drupal's default mail notification
	$prev = variable_get('user_mail_status_activated_notify', TRUE);
	variable_set('user_mail_status_activated_notify', FALSE);
	$account = user_save($account,  $edit);
	variable_set('user_mail_status_activated_notify', $prev);
	
	variable_set('site_mail', $client_email);

	//HACK HACK HACK. Why is the base_url set wrong when this is run. Don't know, but I
	//know that we always set ibm_apim_site_url to the proper base_url so just use this.
	$base_url = variable_get('ibm_apim_site_url', $base_url);

	$onetime = user_pass_reset_url($account);
	drush_log(t('Login url: !onetime', array('!onetime' => $onetime . '/login')), 'success');

	_drush_ibm_apim_send_welcome_mail($url, $account, $install_locale, $client_email, $onetime);
}

function drush_ibm_apim_listen() {
	drupal_set_message(t('Drush ibm_apim_listen listening to stdin'), 'success');
	_drush_log_drupal_messages();
	
	$cmdline = trim(fgets(STDIN));
	$attempt=0;
	$json="";
	while($cmdline) {
		drupal_set_message(t('Got command: %cmd', array('%cmd' => $cmdline)), 'success');
		_drush_log_drupal_messages();
		$pieces = explode(" ", $cmdline);
		$cmd=$pieces[0];
		$id=$pieces[1];
		$subid=$pieces[2];
		
		try {
			if ($cmd == "dapi") {
				drush_api_deleteapi($id);
			} elseif ($cmd == "dprod") {
				drush_product_deleteproduct($id);
			} elseif ($cmd == "dapp") {
				drush_application_deleteapp($id);
			} elseif ($cmd == "dsub") {
				drush_application_deletesub($id,$subid);
			} elseif ($cmd == "dorg") {
				drush_devorg_deleteorg($id);
			} elseif ($cmd == "bgsync") {
				drush_ibm_apim_bgsync();
			} else {
				//create or update
				if (! $json) {
					$json = trim(fgets(STDIN));
				}
				if ($cmd == "capi") {
					drush_api_createapi($json);
				} elseif ($cmd == "uapi") {
					drush_api_updateapi($json);
				} elseif ($cmd == "cprod") {
					drush_product_createproduct($json);
				} elseif ($cmd == "uprod") {
					drush_product_updateproduct($json);
				} elseif ($cmd == "capp") {
					drush_application_createapp($json);
				} elseif ($cmd == "uapp") {
					drush_application_updateapp($json);
				} elseif ($cmd == "csub") {
					drush_application_createsub($json);
				} elseif ($cmd == "usub") {
					drush_application_updatesub($json);
				} elseif ($cmd == "corg") {
					drush_devorg_createorg($json);
				} elseif ($cmd == "uorg") {
					drush_devorg_updateorg($json);
				}
			}
			
			$attempt=0;
		} catch (Exception $e) {
			drupal_set_message(t('Attempt %attempt. Caught exception: %message', array('%attempt' => $attempt, '%message' => $e->getMessage())), 'warning');
			$attempt++;
			
			if ($attempt > 2) {
				//Only try 3 times then give up.
				drupal_set_message(t('Giving up'), 'warning');
				$attempt=0;
			} 
		}

		if ($attempt == 0) {		
			$cmdline = trim(fgets(STDIN));
			$json="";
		}		

		_drush_log_drupal_messages();
	}
	
	drupal_set_message(t('Drush ibm_apim_listen exiting'), 'success');
}

function drush_ibm_apim_bgsync() {
  ibm_apim_cron();
  drupal_set_message(t('Background sync run.'), 'success');
}

function drush_ibm_apim_updatecatalog($catalog) {
  ibm_apim_update_catalog($catalog);
  drupal_set_message(t('Catalog updated.'), 'success');
}

function drush_ibm_apim_updateconfig($config) {
  ibm_apim_update_config($config);
  drupal_set_message(t('Config updated.'), 'success');
}
