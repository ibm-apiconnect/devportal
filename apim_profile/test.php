<?php
/**
 * Implement hook_install().
 *
 * Perform actions to set up the site for this profile.
 */

/**
 * BatchAPI callback.
 *
 * @see apim_profile_install()
 */

function _log($str) {
  file_put_contents("/tmp/apim_profile.log", $str . "\n", FILE_APPEND | LOCK_EX);
}

function _apim_profile_enable_module($module, $module_name, &$context) {
  module_enable(array($module), FALSE);
  $context['message'] = st('Installed %module module.', array('%module' => $module_name));
}

function apim_profile_install_tasks_alter(&$tasks, $install_state) {
  print "EGS alter";
  $tasks['install_select_profile']['display'] = FALSE;
}

function apim_profile_select_profile(&$install_state){
  print "EGS select";
  $install_state['parameters']['profile'] = 'apim_profile';
}

_log("done standard");
print "EGS install\n";

print "done standard\n";
//Turn on some extra modules
$modules = array(
    'API' => 'api',
    'Plan' => 'plan',
    'Application' => 'application'
);

$operations = array();

foreach ($modules as $name => $m) {
  print "modules: " . $name . " => " . $m . "\n";
  //$operations[] = array('_apim_profile_enable_module', array($m, $name));
}

print "EGS done\n";
