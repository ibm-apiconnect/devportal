<?php

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
  
  return $items;
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
