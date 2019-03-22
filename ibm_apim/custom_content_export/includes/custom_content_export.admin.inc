<?php
/**
 * @file
 * Administration page callbacks for the custom_content_export module.
 *
 * @ingroup custom_content_export
 */

/**
 * Form builder. Configure custom_content_export.
 *
 * @ingroup forms
 * @see     system_settings_form()
 */
function custom_content_export_form($form, &$form_state) {

  $form['intro'] = [
    '#markup' => '<p>' . t('If you have added any additional custom fields to any of the APIM Content types (APIs, Applications, Products, Consumer Organizations) or Users then this form will allow you to export that data in a format that can be imported into your APIC v2018 Developer Portal.') . '</p>' .
      '<p>' . t('It can also be used to export content that is only stored in the Portal database and not in API Manager, such as Application images or files attached to APIs and Products.') . '</p>' .
      '<p>' . t('The resulting zip file can then be imported into your IBM API Connect v2018 Developer Portal.') . '</p>',
    '#weight' => -50,
  ];

  $default = variable_get('custom_content_export_filename', strtolower(preg_replace('/\W+/', '_', variable_get('site_name', 'custom_content_export')) . '_content'));
  $form['custom_content_export_filename'] = [
    '#type' => 'textfield',
    '#title' => t('Filename to save as'),
    '#default_value' => $default,
    '#required' => TRUE,
    '#field_suffix' => '.zip',
  ];

  $form['advanced'] = [
    '#type' => 'fieldset',
    '#title' => t('Advanced Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $options = [-1 => t('- Default -')];
  foreach (range(128, 2048, 32) as $value) {
    $options[$value . 'M'] = format_size($value * 1024 * 1024);
  }
  $form['advanced']['custom_content_export_memory_limit'] = [
    '#type' => 'select',
    '#title' => t('Memory Limit', [
      '@size' => format_size(variable_get('custom_content_export_last_export_memory', 0)),
    ]),
    '#description' => t('If you have a lot of content you may need to set this value higher so you the web server does not run out of memory processing the export. <strong>Depending upon your server configuration, this may or may not have any effect!</strong> For more information refer to <a href="http://php.net/manual/en/function.ini-set.php">http://php.net/manual/en/function.ini-set.php</a>.'),
    '#default_value' => variable_get('custom_content_export_memory_limit', 160),
    '#options' => $options,
  ];

  $options = [-1 => t('- Default -')];
  foreach (range(30, 1800, 30) as $value) {
    $options[$value] = format_interval($value);
  }
  $form['advanced']['custom_content_export_max_execution'] = [
    '#type' => 'select',
    '#title' => t('Maximum Execution Time', [
      '@time' => format_interval(variable_get('custom_content_export_last_export_time', 0)),
    ]),
    '#description' => t('If you have a lot of content you may need to set this value higher so you the web server does not timeout. <strong>Depending upon your server configuration, this may or may not have any effect!</strong> For more information refer to <a href="http://php.net/manual/en/function.set-time-limit.php">http://php.net/manual/en/function.set-time-limit.php</a>.'),
    '#default_value' => variable_get('custom_content_export_max_execution', 60),
    '#options' => $options,
  ];

  $form = system_settings_form($form);
  $form['actions']['submit']['#value'] = t('Download File');
  $form['#submit'][] = 'custom_content_export_form_submit';

  return $form;
}

/**
 * Form submission handler
 */
function custom_content_export_form_submit($form, &$form_state) {

  $values = $form_state['values'];

  $values['custom_content_export_dir'] = file_directory_temp();
  $zipname = $values['custom_content_export_filename'] . '.zip';
  $zip = custom_content_export_create_zip($values);

  drupal_add_http_header('Content-disposition', 'attachment; filename=' . $zipname);

  // We are returning a zip, so tell the browser.
  drupal_add_http_header('Content-Type', 'application/zip');
  drupal_add_http_header('Content-Length', filesize(file_directory_temp() . '/' . $zipname));
  readfile(file_directory_temp() . '/' . $zipname);
  drupal_exit();
}

function custom_content_export_create_zip($values = []) {
  $export = _custom_content_export_values($values);
  if ($export !== NULL) {
    $jsonFP = fopen(file_directory_temp() . '/' . 'custom_content_export.json', 'w');
    fwrite($jsonFP, json_encode($export, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT));
    fclose($jsonFP);
  }

  $zipname = $values['custom_content_export_filename'] . '.zip';
  $zipdir = $values['custom_content_export_dir'];
  $zip = new ZipArchive;
  if ($zip->open($zipdir . '/' . $zipname, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) !== TRUE) {
    die ('An error occurred creating your ZIP file.');
  }
  // add the JSON config file
  $zip->addFile(file_directory_temp() . '/' . 'custom_content_export.json', 'custom_content_export.json');

  $ignoreList = [
    'adaptivetheme',
    'apiwsdl',
    'css',
    'ctools',
    'honeypot',
    'imagecache',
    'js',
    'locations',
    'styles',
    'tmp',
    '.htaccess',
  ];

  // add public files
  $filedir = 'public://';
  $path = drupal_realpath($filedir);
  if (is_dir($path)) {
    $iterator = new RecursiveDirectoryIterator($path);
    // skip dot files while iterating
    $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($files as $value) {
      if (is_dir($value)) {
        $value = realpath($value);
        $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $value);
        $include = TRUE;
        foreach ($ignoreList as $ignorePath) {
          if (strpos($relativePath, $ignorePath) === 0) {
            $include = FALSE;
          }
        }

        if ($include) {
          $zip->addEmptyDir(str_replace($path . DIRECTORY_SEPARATOR, '', 'files/public/' . $value . DIRECTORY_SEPARATOR));
        }
      }
      else {
        $value = realpath($value);
        $p = pathinfo($value);
        $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $p['dirname']);
        $include = TRUE;
        foreach ($ignoreList as $ignorePath) {
          if (strpos($relativePath, $ignorePath) === 0) {
            $include = FALSE;
          }
        }
        if ($include && $value !== $path . DIRECTORY_SEPARATOR . '.htaccess') {
          $zip->addFile($value, str_replace($path . '/', 'files/public/', $value));
        }
      }
    }
  }
  // add private files
  $filedir = 'private://';
  $path = drupal_realpath($filedir);
  if (is_dir($path)) {
    $iterator = new RecursiveDirectoryIterator($path);
    // skip dot files while iterating
    $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($files as $value) {
      if (is_dir($value)) {
        $value = realpath($value);
        $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $value);
        $include = TRUE;
        foreach ($ignoreList as $ignorePath) {
          if (strpos($relativePath, $ignorePath) === 0) {
            $include = FALSE;
          }
        }

        if ($include) {
          $zip->addEmptyDir(str_replace($path . DIRECTORY_SEPARATOR, '', 'files/private/' . $value . DIRECTORY_SEPARATOR));
        }
      }
      else {
        $value = realpath($value);
        $p = pathinfo($value);
        $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $p['dirname']);
        $include = TRUE;
        foreach ($ignoreList as $ignorePath) {
          if (strpos($relativePath, $ignorePath) === 0) {
            $include = FALSE;
          }
        }
        if ($include && $value !== $path . DIRECTORY_SEPARATOR . '.htaccess') {
          $zip->addFile($value, str_replace($path . '/', 'files/private/', $value));
        }
      }
    }
  }

  $zip->close();
  return $zip;
}

function _custom_content_export_values($values = []) {
  if ($values['custom_content_export_memory_limit'] !== -1) {
    ini_set('memory_limit', $values['custom_content_export_memory_limit']);
  }

  if ($values['custom_content_export_max_execution'] !== -1) {
    set_time_limit($values['custom_content_export_max_execution']);
  }
  $contentTypes = ['api', 'application', 'devorg', 'product'];
  $data_grid = [];
  foreach ($contentTypes as $contentType) {
    $fields = [];
    if (module_exists($contentType)) {
      switch ($contentType) {
        case 'api':
          $fields = _api_get_custom_fields();
          break;
        case 'product':
          $fields = _product_get_custom_fields();
          break;
        case 'devorg':
          $fields = _devorg_get_custom_fields();
          break;
        case 'application':
          $fields = _application_get_custom_fields();
          break;
      }
    }

    $contentTypeConfig = [];
    if (!empty($fields)) {
      // Get the field and instance config information
      foreach ($fields as $field) {
        $fieldInfo = field_info_field($field);
        $instanceInfo = field_info_instance('node', $field, $contentType);
        $config = [];
        if ($fieldInfo !== NULL) {
          $config['info'] = $fieldInfo;
        }
        if ($instanceInfo !== NULL) {
          $config['instance'] = $instanceInfo;
        }
        if ($config !== NULL && !empty($config)) {
          $contentTypeConfig[$field] = $config;
        }
      }
    }
    $content = [];

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', $contentType);
    $result = $query->execute();
    if (!empty($result['node'])) {
      $nids = array_keys($result['node']);

      if ($nids !== NULL && !empty($nids)) {
        $fieldCount = count($fields);
        // need to get the values of these fields too as this data is not in apim but these also aren't custom fields
        switch ($contentType) {
          case 'api':
            $fields[] = 'api_image';
            $fields[] = 'api_attachments';
            break;
          case 'product':
            $fields[] = 'product_image';
            $fields[] = 'product_attachments';
            break;
          case 'application':
            $fields[] = 'application_image';
            break;
        }

        foreach ($nids as $nid) {
          $node = node_load($nid);
          // only include nodes if there are custom fields or they have either image or attachment set
          if ($fieldCount > 0 || ($contentType === 'api' && ((isset($node->api_image) && !empty($node->api_image)) || (isset($node->api_attachments) && !empty($node->api_attachments)))) ||
            ($contentType === 'product' && ((isset($node->product_image) && !empty($node->product_image)) || (isset($node->product_attachments) && !empty($node->product_attachments)))) ||
            ($contentType === 'application' && (isset($node->application_image) && !empty($node->application_image)))) {

            $nodeInfo = ['nid' => $nid, 'title' => $node->title];
            switch ($contentType) {
              case 'api':
                $nodeInfo['ref'] = $node->api_ref[$node->language][0]['value'];
                $nodeInfo['id'] = $node->api_apiid[$node->language][0]['value'];
                $nodeInfo['version'] = $node->api_version[$node->language][0]['value'];
                $nodeInfo['name'] = $node->api_xibmname[$node->language][0]['value'];
                break;
              case 'product':
                $nodeInfo['ref'] = $node->product_ref[$node->language][0]['value'];
                $nodeInfo['id'] = $node->product_id[$node->language][0]['value'];
                $nodeInfo['version'] = $node->product_version[$node->language][0]['value'];
                $nodeInfo['name'] = $node->product_name[$node->language][0]['value'];
                break;
              case 'application':
                $nodeInfo['id'] = $node->application_apiid[$node->language][0]['value'];
                break;
              case 'devorg':
                $nodeInfo['id'] = $node->devorg_id[$node->language][0]['value'];
                $nodeInfo['name'] = $node->devorg_name[$node->language][0]['value'];
                break;
            }
            foreach ($fields as $field) {
              // include entire field attribute to include multi-lingual and multiple cardinality values
              $nodeInfo[$field] = $node->$field;
            }
            $content[] = $nodeInfo;
          }
        }
      }
    }

    $data_grid[$contentType] = ['config' => $contentTypeConfig, 'content' => $content];
  }

  // users
  $all_fields = field_info_instances('user', 'user');
  $keys = array_keys($all_fields);
  $defaultFields = ['field_first_name', 'field_last_name', 'field_developer_organization', 'field_uid', 'field_codesnippet'];
  $customUserFields = array_diff($keys, $defaultFields);
  $userConfig = [];
  if (!empty($customUserFields)) {
    // Get the field and instance config information
    foreach ($customUserFields as $field) {
      $fieldInfo = field_info_field($field);
      $instanceInfo = field_info_instance('user', $field, 'user');
      $config = [];
      if ($fieldInfo !== NULL) {
        $config['info'] = $fieldInfo;
      }
      if ($instanceInfo !== NULL) {
        $config['instance'] = $instanceInfo;
      }
      if ($config !== NULL && !empty($config)) {
        $userConfig[$field] = $config;
      }
    }
  }
  $userContent = [];
  $fieldCount = count($customUserFields);
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'user');
  $result = $query->execute();
  if (!empty($result['user'])) {
    $uids = array_keys($result['user']);
    if ($uids !== NULL && !empty($uids)) {
      foreach ($uids as $uid) {
        if ($uid !== 1 && $uid !== 0) {
          $user = user_load($uid);
          // only include users if there are custom fields or they have a picture / custom role
          if ($fieldCount > 0 || (isset($user->picture) && !empty($user->picture)) || array_values($user->roles) !== ['authenticated user']) {
            $userInfo = [
              'uid' => $uid,
              'username' => $user->name,
              'mail' => $user->mail,
              'id' => $user->field_uid['und'][0]['value'],
            ];
            if (isset($user->data['remote_id'])) {
              $userInfo['remote_id'] = $user->data['remote_id'];
            }
            if (isset($user->picture)) {
              $userInfo['picture'] = $user->picture;
            }
            if (isset($user->signature)) {
              $userInfo['signature'] = ['value' => $user->signature, 'format' => $user->signature_format];
            }
            if (isset($user->timezone)) {
              $userInfo['timezone'] = $user->timezone;
            }
            if (isset($user->language)) {
              $userInfo['language'] = $user->language;
            }
            if (isset($user->roles)) {
              $userInfo['roles'] = $user->roles;
            }
            if (isset($user->field_codesnippet)) {
              $userInfo['field_codesnippet'] = $user->field_codesnippet;
            }
            foreach ($customUserFields as $field) {
              // include entire field attribute to include multi-lingual and multiple cardinality values
              $userInfo[$field] = $user->$field;
            }
            $userContent[] = $userInfo;
          }
        }
      }
    }
  }
  $data_grid['user'] = ['config' => $userConfig, 'content' => $userContent];

  $config_data = _ibm_apim_get_apim_config();
  $path = drupal_get_path('module', 'ibm_apim') . '/ibm_apim.info';
  $info = drupal_parse_info_file($path);
  $export = ['payload' => $data_grid, 'catalog' => $config_data, 'version' => $info['version']];
  return $export;
}
