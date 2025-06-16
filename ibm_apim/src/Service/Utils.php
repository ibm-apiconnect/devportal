<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Drupal\Component\Datetime\DateTimePlus;
use Exception;
use Throwable;
use Psr\Log\LoggerInterface;
use \Drupal\ibm_apim\ApicRest;

if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));

/**
 * Miscellaneous utility functions.
 */
class Utils {
  private LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  public function __construct(LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }


  /**
   * Creates a Key Entity referencing the correct env var
  */
   public function saveEncKey($keyName, $envVarName): void {
    $oldKey = \Drupal\key\Entity\Key::load($keyName);
    if ($oldKey !== NULL) {
      $oldKey->delete();
    }
    $key = \Drupal\key\Entity\Key::create([
      'id' => $keyName,
      'label' => $keyName,
      'key_type' => 'encryption',
      'key_type_settings' => ['key_size' => 256],
      'key_provider' => 'env',
      'key_provider_settings' => [
        'strip_line_breaks' => TRUE,
        'base64_encoded' => TRUE,
        'env_variable' => $envVarName,
      ],
      'key_input' => 'none',
      'key_input_settings' => [],
    ]);
    $key->save();
  }

  /**
   * delete an enckey
   *
   * @return void
   */
  public function deleteEncKey($keyName): void {
    $oldKey = \Drupal\key\Entity\Key::load($keyName);
    if ($oldKey !== NULL) {
      $oldKey->delete();
    }
  }

  /**
   * delete an encryption profile
   *
   * @return void
   */
  public function deleteEncProfile($profileName): void {
    $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($profileName);
    if ($encryptionProfile) {
      $encryptionProfile->delete();
    }
  }

  /**
   * delete ALL old enckeys
   *
   * @return void
   */
  public function deletePreviousEncKeys($profileName): void {
    for($i=0; $i < 64; $i++) {
      $this->deleteEncKey('oldenckey' .$i);
    }
    $this->deleteEncProfile($profileName);
  }

  /**
   * Creates an encryption profile
   *
   * @param mixed $profileName
   * @param mixed $keyName
   * @return void
   */
  public function createEncryptionProfile($profileName, $keyName): void {
    $this->deleteEncProfile($profileName);
    $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::create([
      'id' => $profileName,
      'label' => $profileName,
      'encryption_method' => 'real_aes',
      'encryption_key' => $keyName,
      'encryption_method_configuration' => [],
    ]);
    $encryptionProfile->save();
  }

  /**
   *  loads an encryption profile
   * @param mixed $profileName
   * @throws \Exception
   * @return \Drupal\ibm_apim\Service\EncryptionProfile
   */
  public function loadEncryptionProfile($profileName): \Drupal\encrypt\Entity\EncryptionProfile {
    // check there is an encryption profile
    $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($profileName);
    if (! $encryptionProfile) {
      throw new \Exception("Failed to find an EncryptionProfile with the name: $profileName");
    }
    return $encryptionProfile;
  }

  /**
   * Set a node field to a string value, or an empty array if the string value is ''
   * to do a database update or not.
   *
   * @param $node
   * @param $name
   * @param $value
   *
   */
    public function setNodeValue($node, $name, $value)
    {
      if ($value && $value != '') {
        $node->set($name, $value);
      } else {
        $node->set($name, []);
      }
    }

  /**
   * create A hash value for the $node. This can be used to check if we need
   * to do a database update or not.
   *
   * @param $node
   *
   * @return string
   */
  public function generateNodeHash($node, $label = NULL): string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__);
    }

    $use_node_hash = getenv('PORTAL_USE_NODE_HASH');

    if ($use_node_hash !==  'false') {
      $use_node_hash = \Drupal::state()->get('ibm_apim.use_node_hash');
    }

    $hashVal = $label;
    if ($use_node_hash !== 'false') {
      $nodeArray = $node->toArray();
      $data = json_encode($nodeArray);
      $hashVal = hash('xxh128', $data);

      $dumpHashedJson = \Drupal::state()->get('ibm_apim.dump_hashed_json');
      if ($dumpHashedJson === 'true' && $label) {
        file_put_contents('/tmp/' . $label, $data);
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $hashVal);
    }
    return $hashVal;
  }

  /**
   * create A hash value for the $node. This can be used to check if we need
   * to do a database update or not.
   *
   * @param $existing
   * @param $updated
   *
   * @return string
   */

  public function hashMatch($existingHash, $updatedEntity, $label = NULL): string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__);
    }

    $updatedHash = $this->generateNodeHash($updatedEntity, $label);
    $matched = ($existingHash == $updatedHash );

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $matched);
    }
    return $matched;
  }

  /**
   * @param $haystack
   * @param $needle
   *
   * @return bool
   */
  public function startsWith($haystack, $needle): bool {
    return $needle === '' || mb_strpos($haystack, $needle) === 0;
  }

  /**
   * @param $haystack
   * @param $needle
   *
   * @return bool
   */
  public function endsWith($haystack, $needle): bool {
    return $needle === '' || mb_substr($haystack, -mb_strlen($needle)) === $needle;
  }

  /**
   * Utility method to truncate a string at a specified length and append an ellipsis
   *
   * @param $string
   * @param int $length
   * @param string $append
   *
   * @return string
   */
  public function truncate_string($string, $length = 191, $append = '…'): string {
    $string = trim($string);
    if (mb_strlen($string) > $length) {
      $string = mb_substr($string, 0, $length - mb_strlen($append));
      $string = trim($string) . $append;
    }
    return $string;
  }

  /**
   * Utility function to convert drupal locale names to standard ones APIM expects
   *
   * @param $lang_name
   *
   * @return null|string
   */
  public function convert_lang_name($lang_name): ?string {
    $returnValue = NULL;
    if (isset($lang_name)) {
      $langNameLower = strtolower($lang_name);
      if ($langNameLower === 'zh_hans' || $langNameLower === 'zh-hans') {
        $lang_name = 'zh-cn';
      }
      elseif ($langNameLower === 'zh_hant' || $langNameLower === 'zh-hant') {
        $lang_name = 'zh-tw';
      }
      $returnValue = str_replace('_', '-', $lang_name);
    }
    return $returnValue;
  }

  /**
   * Utility function to convert standard locale names APIM expects to drupal ones
   *
   * @param $lang_name
   *
   * @return null|string
   */
  public function convert_lang_name_to_drupal($lang_name): ?string {
    $returnValue = NULL;
    if (isset($lang_name)) {
      $langNameLower = strtolower($lang_name);
      if ($langNameLower === 'zh-cn' || $langNameLower === 'zh_cn') {
        $lang_name = 'zh_hans';
      }
      elseif ($langNameLower === 'zh_tw' || $langNameLower === 'zh-tw') {
        $lang_name = 'zh_hant';
      }
      $returnValue = str_replace('-', '_', $lang_name);
    }
    return $returnValue;
  }

  /**
   * Custom Function to return random numbers.
   *
   * @param int $n
   *
   * @return int
   * @throws \Exception
   */
  public function random_num($n = 5): int {
    return random_int(0, 10 ** $n);
  }

  /**
   * Base64 encode (URL safe)
   *
   * @param $input
   *
   * @return string|null
   */
  public function base64_url_encode($input): ?string {
    return strtr(base64_encode($input), '+/=', '-_,');
  }

  /**
   * Base64 decode (URL safe)
   *
   * @param $input
   *
   * @return string
   */
  public function base64_url_decode($input): string {
    return base64_decode(strtr($input, '-_,', '+/=')) ?? '';
  }

  /**
   * Returns a list of all the modules in the main shared modules directory (non-site specific)
   *
   * @return array
   */
  public function get_bundled_modules(): array {
    return $this->get_bundled_content('modules');
  }

  /**
   * Returns a list of all the themes in the main shared modules directory (non-site specific)
   *
   * @return array
   */
  public function get_bundled_themes(): array {
    return $this->get_bundled_content('themes');
  }

  /**
   * Returns a list of all the content of the specified type in the main shared modules directory (non-site specific)
   * e.g. pass 'themes' to get all the themes in drupal_root/themes or 'foo' to get drupal_root/foo content
   *
   * @param $contentDirName
   *
   * @return array
   */
  private function get_bundled_content($contentDirName): array {
    $content_dir = DRUPAL_ROOT . '/' . $contentDirName;
    $dirs = array_filter(glob($content_dir . '/*', GLOB_ONLYDIR), 'is_dir');
    $content_list = [];
    foreach ($dirs as $dir) {
      $parts = pathinfo($dir);
      $content_list[] = $parts['basename'];
    }
    return $content_list;
  }

  /**
   * Localised numbers
   *
   * @param $number
   * @param null $decimals
   * @param null $dec_point
   * @param null $thousands_sep
   *
   * @return string
   */
  public function format_number_locale($number, $decimals = NULL, $dec_point = NULL, $thousands_sep = NULL): string {
    if ($decimals === NULL || $dec_point === NULL || $thousands_sep === NULL) {
      $locale = localeconv();
      if ($decimals === NULL) {
        $decimals = $locale['int_frac_digits'];
      }
      if ($dec_point === NULL) {
        $dec_point = $locale['decimal_point'];
      }
      if ($thousands_sep === NULL) {
        $thousands_sep = $locale['thousands_sep'];
      }
    }
    if ($decimals > 4) {
      $decimals = 4;
    }

    return number_format($number, $decimals, $dec_point, $thousands_sep);
  }

  /**
   * Used to provide common translations to analytics in both myorg and applications
   *
   * @return array
   */
  public function analytics_translations(): array {

    return [
      'api_calls' => t('API Calls'),
      'api_stats' => t('API Stats'),
      'subscriptions' => t('Subscriptions'),
      'response_time' => t('Response Time'),
      'average_response_time' => t('Average Response Time'),
      'total_errors' => t('Total Errors'),
      'total_calls' => t('Total Calls'),
      'calls_last_100' => t('API Calls (Last 100)'),
      'errors_last_100' => t('Errors (Last 100)'),
      '30s' => t('30s'),
      '1m' => t('1m'),
      '30m' => t('30m'),
      '1h' => t('1h'),
      '1d' => t('1d'),
      '7d' => t('7d'),
      '30d' => t('30d'),
      'last_30days' => t('Last 30 days'),
      'calls' => t('calls'),
      'errors' => t('errors'),
      '30secs' => t('30 secs'),
      '1min' => t('1 min'),
      '30mins' => t('30 mins'),
      '1hr' => t('1 hr'),
      '1day' => t('1 day'),
      '7days' => t('7 days'),
      '30days' => t('30 days'),
    ];
  }

  /**
   * Recursively delete a directory
   *
   * @param $path
   */
  public function file_delete_recursive($path): void {
    if (isset($path)) {
      if (is_dir($path)) { // Path is directory
        $files = scandir($path, SCANDIR_SORT_NONE);
        foreach ($files as $file) {
          if ($file !== '.' && $file !== '..') {
            $this->file_delete_recursive($path . '/' . $file); // Recursive call
          }
        }
        rmdir($path);
      }
      else {
        unlink($path); // Delete the file
      }
    }
  }

  /**
   * Delete any empty leftover folders
   *
   * @param $extensionType
   */
  public function clear_empty_extension_folders(string $extensionType = 'module'): void {
    $sitePath = \Drupal::getContainer()->getParameter('site.path');
    if (empty($sitePath) || $sitePath === NULL) {
      \Drupal::logger('ibm_apim')->error('Could not detect site path. Not checking for empty folders');
      return;
    }

    $path = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $sitePath . DIRECTORY_SEPARATOR . $extensionType . 's';
    if (is_dir($path)) { // Path is directory
      foreach(glob($path . '/*', GLOB_ONLYDIR) as $dir) {
        if ($this->is_dir_empty($dir)) {
          \Drupal::logger('ibm_apim')
          ->info('Detected empty directory in @extension. Deleting @path', ['@extension' => $extensionType, '@path' => $dir]);
          rmdir($dir);
        }
      }
    }
  }

  /**
   * Check if directory is empty. Its checks two as scandir returns . and ..
   *
   * @param $extensionType
   */
  function is_dir_empty($dir) {
    return (count(scandir($dir)) == 2);
  }


  /**
   * Return a map of the custom extension directory => machine name which are found within <site path>/(themes/modules)
   *
   * @param string the extension type you want to get directories of. theme or module
   *
   * @return array
   *
   * @throws \InvalidArgumentException
   */
  public function mapCustomDirectoriesToMachineName(string $extensionType): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ($extensionType !== 'module' && $extensionType !== 'theme') {
      throw new \InvalidArgumentException('extensionType must be of type of "module" or "theme". Got: ' . $extensionType);
    }

    $map = array();

    $customExtensionDirs = $this->getCustomExtensionDirectories($extensionType);
    foreach ($customExtensionDirs as $directory) {
      $map[$directory] = $this->getCustomExtensionMachineName($directory);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $map);
    return $map;
  }

  /**
   * List the directories under <site path>/(themes/modules)
   *
   * @param string the extension type you want to get directories of. theme or module
   *
   * @return array directories of custom extension
   */
  public function getCustomExtensionDirectories(string $extensionType): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $sitePath = DRUPAL_ROOT . DIRECTORY_SEPARATOR . \Drupal::getContainer()->getParameter('site.path') . DIRECTORY_SEPARATOR . $extensionType . 's';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sitePath, \FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

    $cexs = [];
    foreach($iterator as $fileinfo) {
        if($fileinfo->isDir() && count(glob($fileinfo->getRealpath() . DIRECTORY_SEPARATOR . '*\.info\.yml')) === 1) {
          $cexs[] = $fileinfo->getRealPath();
        }
    }

    $cexs = array_filter($cexs, function($value) use ($cexs) {
        foreach($cexs as $cex) {
          if ($cex !== $value && str_contains($value, $cex)) {
            return false;
          }
        }
        return true;
    });

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $cexs);
    return $cexs;
  }

  /**
   * Gets the machine based off the start of the info yml file name
   *
   * @param string directory of the extension you want to get the machine name of
   *
   * @return string machine name of module directory
   */
  public function getCustomExtensionMachineName (string $cexDirectory): string {
    $cex_machine_name = '';
    $info_yml = $cexDirectory . DIRECTORY_SEPARATOR . '*.info.yml';
    $info_yml = @array_pop(glob($info_yml));
    if (!$this->isHidden($info_yml)) {
      $info_yml = \basename($info_yml);
      $cex_machine_name = \substr($info_yml, 0, -\strlen('.info.yml'));
    }

    return $cex_machine_name;
  }

  /**
   * Return information about all of the modules which are eligible for deletion.
   *
   * Custom modules which are listed satisfy the following criteria:
   *  - are installed in <site_dir>
   *  - have a valid info.yml file
   *  - are not marked as hidden in the info.yml
   *  - don't have any enabled submodules.
   *  - double installed modules, regardless of if they are enabled.
   *
   * @return array options to pass into tableselect
   */
  public function getDisabledCustomExtensions(string $extensionType): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $siteConfig = \Drupal::service('ibm_apim.site_config');

    if ($extensionType !== 'module' && $extensionType !== 'theme') {
      throw new \Exception('extensionType must be of type of "module" or "theme". Got: ' . $extensionType);
    }

    $custom_extension_dirs = $this->getCustomExtensionDirectories($extensionType);

    $uninstall_list = [];
    $extensionHandler = $extensionType === 'module' ? \Drupal::service('module_handler') : \Drupal::service('theme_handler');
    $doubleInstalledModules = $siteConfig->getModulesBothShippedAndCustomInstalled();
    $moduleBlocklist = $siteConfig->getBlockList();

    foreach ($custom_extension_dirs as $cex) {
      $extension_name = $this->getCustomExtensionMachineName($cex);
      $exists = $extensionType === 'module' ? $extensionHandler->moduleExists($extension_name) : $extensionHandler->themeExists($extension_name);
      $cexDoubleInstalled = in_array($extension_name, $doubleInstalledModules);
      if ($exists && $cexDoubleInstalled === false) {
        \Drupal::logger('ibm_apim')->debug('getDisabledCustomExtensions: %cex is enabled so not listed.', ['%cex' => $cex]);
      }
      else {

        $info_yml = $cex . '/*.info.yml';
        $info_yml = @array_pop(glob($info_yml));

        if (file_exists($info_yml) && !$this->isHidden($info_yml)) {

          $subExtensions = $this->getSubExtensions($cex, $extensionType);
          $enabled_submodule = FALSE;

          foreach ($subExtensions as $sm) {
            $exists = $extensionType === 'module' ? $extensionHandler->moduleExists($sm) : $extensionHandler->themeExists($sm);
            if ($exists) {
              \Drupal::logger('ibm_apim')
                ->info('getDisabledCustomExtensions: not listing %cm as sub-module %sm is still enabled.', ['%cm' => $extension_name, '%sm' => $sm]);
              $enabled_submodule = TRUE;
            }
          }

          if (!$enabled_submodule) {
            if (empty($subExtensions)) {
              $info_msg = t('No sub-modules found.');
            }
            else {
              $info_msg = t('The following sub-modules will also be deleted: %modules', ['%modules' => \implode(', ', $subExtensions)]);
            }

            if ($cexDoubleInstalled) {
              $info_msg =  t('<strong>RECCOMMENDED</strong>: Please delete this module using the Developer Portal Toolkit CLI. It is already provided as part of this Developer Portal. ' . $info_msg);
            } elseif ($moduleBlocklist && in_array($extension_name, $moduleBlocklist, false)) {
              $info_msg = t('<strong>RECCOMMENDED</strong>: Please delete this module. The installation of this module will be blocked. ' . $info_msg);
            }

            $module = [
              'module' => \yaml_parse_file($info_yml)['name'],
              'machine_name' => $extension_name,
              'info' => $info_msg,
            ];

            if ($cexDoubleInstalled) {
              $module['#disabled'] = true;
            }
            $uninstall_list[$extension_name] = $module;
          }
        }
        else {
          \Drupal::logger('ibm_apim')
            ->debug('getDisabledCustomModules: info.yml not found or module marked as hidden for %cm.', ['%cm' => $extension_name]);
        }

      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $uninstall_list;
  }

  /**
   * Check hidden property in info.yml file.
   * TODO: honour `$settings['extension_discovery_scan_tests'] = TRUE`
   *      (see https://www.drupal.org/docs/8/creating-custom-modules/let-drupal-8-know-about-your-module-with-an-infoyml-file)
   *
   * @param $info_yml - full path to info.yml
   *
   * @return bool is hidden?
   */
  public function isHidden($info_yml): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $info_yml);
    $info = \yaml_parse_file($info_yml);
    $hidden = FALSE;
    if (\array_key_exists('hidden', $info) && $info['hidden'] !== NULL && (boolean) $info['hidden'] === TRUE) {
      \Drupal::logger('ibm_apim')->debug('isHidden: module marked as hidden in %info_yml', ['%info_yml' => basename($info_yml)]);
      $hidden = TRUE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $hidden);
    return $hidden;
  }

  /**
   * Search for info.yml files in sub directories under a custom extension (theme/module).
   * Exclude hidden extensions from the returned list.
   *
   * @param string $parent custom module name.
   * @param string $extensionType custom extension type (theme/module)
   *
   * @return array
   */
  public function getSubExtensions(string $parent, string $extensionType): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $parent);
    $subs = [];
    $dir = new RecursiveDirectoryIterator($parent);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, '/^.+\.info.yml$/i', RegexIterator::GET_MATCH);

    foreach ($files as $file) {
      $info_yml_full_path = array_shift($file);

      if (!$this->isHidden($info_yml_full_path)) {
        $info_yml = \basename($info_yml_full_path);
        $extension_name = \substr($info_yml, 0, -\strlen('.info.yml'));
        if ($extension_name !== basename($parent)) {
          $subs[] = $extension_name;
        }
      }

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $subs);
    return $subs;
  }


   /**
   * @param $customFields
   * @param $formState
   *
   * @return array
   */
  public function handleFormCustomFields($customFields, $formState): array {
    $customFieldValues = [];
    if (!empty($customFields)) {
      $userInputs = $formState->getUserInput();
      foreach ($customFields as $customField) {
        $value = $formState->getValue($customField);
        if (isset($value[0]['value']) && $value[0]['value'] instanceof DateTimePlus) {
          $format = "Y-m-d";
          if (isset($userInputs[$customField][0]['value']) && is_array($userInputs[$customField][0]['value']) && count($userInputs[$customField][0]['value']) === 2) {
            $format = "Y-m-d\TH:i:s";
          }
          $value = array_column($value, 'value');
          foreach ($value as $key => $val) {
            if (isset($val)) {
              $value[$key] = $val->format($format);
            }
          }
        }
        elseif (isset($userInputs[$customField], $value)) {
          $input = $userInputs[$customField];
          //Remove unnecessary fields based on user Input
          if (is_array($input)) {
            foreach (array_keys($value) as $key) {
              if (!array_key_exists($key, $input)) {
                unset($value[$key]);
              }
              elseif (!empty($value[$key]) && is_array($value[$key])) {
                foreach (array_keys($value[$key]) as $attr) {
                  if ((is_array($input[$key]) && !array_key_exists($attr, $input[$key])) || $value[$key][$attr] === 'upload') {
                    unset($value[$key][$attr]);
                  }
                }
              }
            }
          }
        }
        //Don't need an array for only 1 element
        if (is_array($value) && count($value) === 1) {
          $value = array_pop($value);
        }
        $customFieldValues[$customField] = $value;
      }
    }
    return $customFieldValues;
  }

  /**
   * @{inheritdoc}
   *
   * @param ApicUser $apic_user
   * @param \Drupal\user\Entity\User $user
   * @param FormStateInterface $form_state
   * @param string $view_mode
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveCustomFields($node, $customFields, $customFieldValues, $isJSON, $save = TRUE): void {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    if (isset($node) && !empty($customFields)) {
      foreach ($customFields as $customField) {
        $isList = False;
        if ($node->hasField($customField)) {
          if (isset($customFieldValues[$customField])) {
            $value = $customFieldValues[$customField];
            if ($isJSON) {
              if (strpos($value, '[') === 0) {
                $isList = True;
              }
              try {
                $value = json_decode($value, True, 512, JSON_THROW_ON_ERROR);
              } catch (Throwable $e) {
              }
            }
            \Drupal::logger('ibm_apim')->info('saving custom field: @customfield', ['@customfield' => $customField]);
            $type = $node->get($customField)->getFieldDefinition()->getType();
            if ($isList || (!$isJSON && is_array($value))) {
              $valueToSave = [];
              foreach ($value as $element) {
                $element = $this->handleFieldType($element, $type);
                $valueToSave[] = $element;
              }
              $value = $valueToSave;
            } else {
              $value = $this->handleFieldType($value, $type);
            }
            $node->set($customField,$value);
          }
        }
        if ($save) {
          $node->save();
        }
      }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }
}

  private function handleFieldType($value, $type) {
    if ($type === 'address') {
      if (isset($value['address'])) {
        $value = $value['address'];
      }
    }
    if ($type === 'timestamp') {
      try {
        if (is_array($value)) {
          $value = array_shift($value);
        }
        $time = DateTimePlus::createFromFormat('Y-m-d\TH:i:s', $value);
        if ($time) {
          $value = $time->getTimestamp();
        }
      } catch (Exception $e) {
      }
    }
    return $value;
  }

  public function snapshotDebug($msg, $values = []) {
    if (isset($GLOBALS['__PHPUNIT_ISOLATION_BLACKLIST']) || (boolean) $this->configFactory->get('ibm_apim.devel_settings')->get('snapshot_debug')) {
      $this->logger->debug($msg, $values);
    }
  }

  private function flattenDrupalValues($inputData) {
    $data = [];
    if (is_array($inputData)) {
      if (array_is_list($inputData) && count($inputData) === 1 && isset($inputData[0]['value']) ) {
        $data = $inputData[0]['value'];
      } elseif ( isset($inputData['value']) && count($inputData) === 1) {
        $data = $inputData['value'];
      } elseif (array_is_list($inputData)) {
        foreach($inputData as $value) {
          if (isset($value['value'])) {
            array_push($data, $value['value']);
          } else {
            array_push($data, $value);
          }
        }
        if (count($data) === 1) {
          $data = $data[0];
        }
      } else {
        $data = [];
        foreach($inputData as $key => $value) {
          $data[$key] = $this->flattenDrupalvalues($value);

          switch($key) {
            case 'access':
            case 'apic_created_at':
            case 'apic_updated_at':
            case 'changed':
            case 'content_translation_created':
            case 'created':
            case 'datestamp':
            case '_info_file_ctime':
            case 'login':
            case 'mtime':
            case 'revision_created':
            case 'revision_timestamp':
            case 'timestamp':
              if (is_int($data[$key]) || preg_match('/^\d+$/', $data[$key])) {
                $ts=new \DateTime( 'NOW', new \DateTimeZone('UTC'));
                $ts->setTimestamp($data[$key]);
                $data[$key] = $ts->format('Y-m-d\Th-i-s.vP');
              }
          }
        }
      }
    } else {
      $data = $inputData;
    }

    return $data;
  }

  public function logAuditEvent($eventName, $outcome, $targetType, $targetId, $requestData = NULL, $responseData = NULL) {
    // The code for this function is included from the container image so that we can share the code between Drupal code and the scripts
    try {
      $site_config = \Drupal::service('ibm_apim.site_config');
      $platformApiEndpoint = $site_config->getPlatformApimEndpoint();
      if (isset($platformApiEndpoint)) {
        $auditUrl = substr($platformApiEndpoint, 0, -4);
      }else{
        return;
      }
        // getting the platform token stored under ibm_apic_mail.token
      $apiToken = \Drupal::state()->get('ibm_apic_mail.token');
      if ($apiToken === NULL || !isset($apiToken['access_token'], $apiToken['expires_in']) || $apiToken['expires_in'] < time()) {
        $clientId = $site_config->getClientId() ?? '';
        $clientSecret = $site_config->getClientSecret() ?? '';
        if(empty($clientId) || empty($clientSecret)) {
          return;
        }
        $apiToken = ApicRest::getPlatformToken();
      }
      if (isset($apiToken['access_token'])) {
        $bearerToken=$apiToken['access_token'];
      }
      require '/etc/devportal/audit.php';
    } catch (Throwable $e) {
      fprintf(STDERR, $e->getMessage());
    }
  }

  public function setInternalAuditFlag(): bool {
    $value = $GLOBALS['ibmApimAuditInternalEvent'] ?? FALSE;
    $GLOBALS['ibmApimAuditInternalEvent'] = TRUE;
    return $value;
  }

  public function resetInternalAuditFlag(bool $value): void {
    $GLOBALS['ibmApimAuditInternalEvent'] = $value;
  }
}
