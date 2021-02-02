<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Form;


use DirectoryIterator;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomModulesDeleteForm extends FormBase {

  protected $sitePath;

  protected $moduleHandler;

  protected $logger;

  protected $keyValueExpirable;

  public function __construct(string $site_path,
                              ModuleHandlerInterface $module_handler,
                              LoggerInterface $logger,
                              KeyValueStoreExpirableInterface $key_value_expirable) {
    $this->sitePath = $site_path;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
    $this->keyValueExpirable = $key_value_expirable;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site.path'),
      $container->get('module_handler'),
      $container->get('logger.channel.ibm_apim'),
      $container->get('keyvalue.expirable')->get('ibm_apim_custommodule_delete')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId(): string {
    return 'ibm_apim_custommodule_delete_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $form['preamble'] = [
      '#type' => 'item',
      '#description' => 'This form allows you to delete any modules you have installed. Any modules shipped with IBM API Connect cannot be deleted. For a module to appear in the list it, and all of its sub-modules, need to be disabled.',
    ];
    $header = [
      'module' => $this->t('Module'),
      'info' => $this->t('Information'),
    ];
    $options = $this->getDisabledCustomModules();
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No deletable modules found.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Delete'),
    ];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (empty(array_filter($form_state->getValue('table')))) {
      $form_state->setErrorByName('', $this->t('No modules selected.'));
      $form_state->setRedirect('ibm_apim.custommodules_delete');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // Save all the values in an expirable key value store.
    $modules = $form_state->getValue('table');
    $uninstall = array_keys(array_filter($modules));
    $account = $this->currentUser()->id();

    $this->keyValueExpirable->setWithExpire($account, $uninstall, 6 * 60 * 60);

    // Redirect to the confirm form.
    $form_state->setRedirect('ibm_apim.custommodules_delete_confirm');
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Return information about all of the modules which are eligible for deletion.
   *
   * Custom modules which are listed satisfy the following criteria:
   *  - are installed in <site_dir>
   *  - have a valid info.yml file
   *  - are not marked as hidden in the info.yml
   *  - don't have any enabled submodules.
   *
   * @return array options to pass into tableselect
   */
  private function getDisabledCustomModules(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $custom_module_dirs = $this->getCustomModuleDirectories();

    $uninstall_list = [];

    foreach ($custom_module_dirs as $cm) {
      if ($this->moduleHandler->moduleExists($cm)) {
        $this->logger->debug('Delete form, %cm is enabled so not listed.', ['%cm' => $cm]);
      }
      else {

        $info_yml = DRUPAL_ROOT . '/' . $this->sitePath . '/modules' . '/' . $cm . '/' . $cm . '.info.yml';

        if (file_exists($info_yml) && !$this->isHidden($info_yml)) {

          $submodules = $this->getSubModules($cm);
          $enabled_submodule = FALSE;

          foreach ($submodules as $sm) {
            if ($this->moduleHandler->moduleExists($sm)) {
              $this->logger->info('Delete form, not listing %cm as sub-module %sm is still enabled', ['%cm' => $cm, '%sm' => $sm]);
              $enabled_submodule = TRUE;
            }
          }

          if (!$enabled_submodule) {
            if (empty($submodules)) {
              $info_msg = $this->t('No sub-modules found.');
            }
            else {
              $info_msg = $this->t('The following sub-modules will also be deleted: %modules', ['%modules' => \implode(', ', $submodules)]);
            }

            $module = [
              'module' => \yaml_parse_file($info_yml)['name'],
              'info' => $info_msg,
            ];
            $uninstall_list[$cm] = $module;
          }
        }
        else {
          $this->logger->debug('Delete form, info.yml not found or module marked as hidden for %cm.', ['%cm' => $cm]);
        }

      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $uninstall_list;

  }

  /**
   * List the directories under <site path>/modules
   *
   * @return array directory names (= custom module names)
   */
  private function getCustomModuleDirectories(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $custom_modules = [];
    $dir = new DirectoryIterator($this->sitePath . '/modules');
    foreach ($dir as $fileinfo) {
      if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        $custom_modules[] = $fileinfo->getFilename();
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $custom_modules);
    return $custom_modules;
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
  private function isHidden($info_yml): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $info_yml);
    $info = \yaml_parse_file($info_yml);
    $hidden = FALSE;
    if (\array_key_exists('hidden', $info) && $info['hidden'] !== NULL && (boolean) $info['hidden'] === TRUE) {
      $this->logger->debug('Delete form, module marked as hidden in %info_yml', ['%info_yml' => basename($info_yml)]);
      $hidden = TRUE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $hidden);
    return $hidden;
  }

  /**
   * Search for info.yml files in sub directories under a custom module.
   * Exclude hidden modules from the returned list.
   *
   * @param string $parent custom module name.
   *
   * @return array
   */
  private function getSubModules(string $parent): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $parent);
    $subs = [];
    $dir = new RecursiveDirectoryIterator($this->sitePath . '/modules/' . $parent);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, '/^.+\.info.yml$/i', RegexIterator::GET_MATCH);

    foreach ($files as $file) {
      $info_yml_full_path = DRUPAL_ROOT . '/' . \array_shift($file);

      if (!$this->isHidden($info_yml_full_path)) {
        $info_yml = \basename($info_yml_full_path);
        $module_name = \substr($info_yml, 0, -\strlen('.info.yml'));
        if ($module_name !== $parent) {
          $subs[] = $module_name;
        }
      }

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $subs);
    return $subs;

  }

}
