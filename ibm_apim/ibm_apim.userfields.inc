<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2015, 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

function _ibm_apim_add_user_first_name_field() {
  // create first name
  $existingfield = field_info_field('field_first_name');
  if (!isset($existingfield)) {
    $field = array(
      'field_name' => 'field_first_name',
      'type' => 'text',
      'settings' => array('required' => FALSE, 'max_length' => '255')
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'field_first_name',
      'entity_type' => 'user',
      'label' => 'First name',
      'bundle' => 'user',
      'required' => 1,
      'default_value' => NULL,
      'widget' => array(
        'active' => 1,
        'weight' => '-50',
        'module' => 'text',
        'type' => 'text_textfield'
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
          'module' => 'text'
        )
      ),
      'description' => 'Enter your first name.',
      'settings' => array('user_register_form' => 1, 'weight' => -9)
    );
    field_create_instance($instance);
  }
}

function _ibm_apim_add_user_last_name_field() {
  // create last name
  $existingfield = field_info_field('field_last_name');
  if (!isset($existingfield)) {
    $field = array(
      'field_name' => 'field_last_name',
      'type' => 'text',
      'settings' => array('required' => FALSE, 'max_length' => '255')
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'field_last_name',
      'entity_type' => 'user',
      'label' => 'Last name',
      'bundle' => 'user',
      'required' => 1,
      'default_value' => NULL,
      'widget' => array(
        'active' => 1,
        'weight' => '-45',
        'module' => 'text',
        'type' => 'text_textfield'
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
          'module' => 'text'
        )
      ),
      'description' => 'Enter your last name.',
      'settings' => array('user_register_form' => 1, 'weight' => -8)
    );
    field_create_instance($instance);
  }
}

function _ibm_apim_add_user_developerorg_field() {
  // create developer org
  $existingfield = field_info_field('field_developer_organization');
  if (!isset($existingfield)) {
    $field = array(
      'field_name' => 'field_developer_organization',
      'type' => 'text',
      'cardinality' => -1,
      'settings' => array('required' => FALSE, 'max_length' => '255')
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'field_developer_organization',
      'entity_type' => 'user',
      'label' => 'Developer organization',
      'bundle' => 'user',
      'required' => 1,
      'default_value' => NULL,
      'widget' => array(
        'active' => 1,
        'weight' => '-40',
        'module' => 'text',
        'type' => 'text_textfield'
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
          'module' => 'text'
        )
      ),
      'description' => 'Enter your developer organization.',
      'settings' => array('user_register_form' => 1, 'weight' => -7)
    );
    field_create_instance($instance);
  }
  else {
    _ibm_apim_change_devorg_cardinality();
  }
}

function _ibm_apim_change_devorg_cardinality() {
  $existingfield = field_info_field('field_developer_organization');
  if (isset($existingfield)) {
    $existingfield['cardinality'] = -1;
    field_update_field($existingfield);
  }
}

function _ibm_apim_add_user_company_field() {
  // create company field
  $existingfield = field_info_field('field_company');
  if (!isset($existingfield)) {
    $field = array(
      'field_name' => 'field_company',
      'type' => 'text',
      'settings' => array('required' => FALSE, 'max_length' => '255')
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'field_company',
      'entity_type' => 'user',
      'label' => 'Company name',
      'bundle' => 'user',
      'required' => 0,
      'default_value' => NULL,
      'widget' => array(
        'active' => 1,
        'weight' => '-35',
        'module' => 'text',
        'type' => 'text_textfield'
      ),
      'display' => array(
        'default' => array(
          'type' => 'hidden',
          'module' => 'text'
        )
      ),
      'description' => 'Enter your company name.',
      'settings' => array('user_register_form' => 0, 'weight' => -6)
    );
    field_create_instance($instance);
  }
}

function _ibm_apim_add_user_address_field() {
  // create address field
  $existingfield = field_info_field('field_address');
  if (!isset($existingfield)) {
    $field = array(
      'field_name' => 'field_address',
      'type' => 'addressfield',
      'module' => 'addressfield',
      'settings' => array('required' => FALSE)
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'field_address',
      'entity_type' => 'user',
      'label' => 'Address',
      'bundle' => 'user',
      'required' => 0,
      'default_value' => NULL,
      'widget' => array(
        'weight' => '-25',
        'active' => 1,
        'module' => 'addressfield',
        'type' => 'addressfield_standard',
        'settings' => array(
          'format_handlers' => array(
            'address' => 'address',
            'address-optional' => 'address-optional',
            'address-hide-postal-code' => 0,
            'address-hide-street' => 0,
            'address-hide-country' => 0,
            'organisation' => 0,
            'name-full' => 0,
            'name-oneline' => 0
          )
        )
      ),
      'display' => array(
        'default' => array(
          'type' => 'hidden',
          'module' => 'addressfield'
        )
      ),
      'description' => 'Enter your address.',
      'settings' => array('user_register_form' => 0, 'weight' => -4)
    );
    field_create_instance($instance);
  }
}

function _ibm_apim_add_user_phone_field() {
  // create phone field
  $existingfield = field_info_field('field_phone');
  if (!isset($existingfield)) {
    $field = array(
      'field_name' => 'field_phone',
      'type' => 'text',
      'settings' => array('required' => FALSE, 'max_length' => '255')
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'field_phone',
      'entity_type' => 'user',
      'label' => 'Phone number',
      'bundle' => 'user',
      'required' => 0,
      'default_value' => NULL,
      'widget' => array(
        'active' => 1,
        'weight' => '-20',
        'module' => 'text',
        'type' => 'text_textfield'
      ),
      'display' => array(
        'default' => array(
          'type' => 'hidden',
          'module' => 'text'
        )
      ),
      'description' => 'Enter your phone number.',
      'settings' => array('user_register_form' => 0, 'weight' => -5)
    );
    field_create_instance($instance);
  }
}

function _ibm_apim_add_user_uid_field() {
  // create uid field
  $existingfield = field_info_field('field_uid');
  if (!isset($existingfield)) {
    $field = array(
      'field_name' => 'field_uid',
      'type' => 'text',
      'settings' => array('required' => FALSE, 'max_length' => '191')
    );
    field_create_field($field);

    $instance = array(
      'field_name' => 'field_uid',
      'entity_type' => 'user',
      'label' => 'UID',
      'bundle' => 'user',
      'required' => 0,
      'default_value' => NULL,
      'widget' => array(
        'active' => 1,
        'weight' => '-42',
        'module' => 'text',
        'type' => 'text_textfield'
      ),
      'display' => array(
        'default' => array(
          'type' => 'hidden',
          'module' => 'text'
        )
      ),
      'description' => 'Enter your uid.',
      'settings' => array('user_register_form' => 0, 'weight' => -6)
    );
    field_create_instance($instance);
  }
}

function _ibm_apim_add_user_codesnippet_field() {
  // create codesnippet field
  $existingfield = field_info_field('field_codesnippet');
  if (!isset($existingfield)) {
    $field = array(
      'field_name' => 'field_codesnippet',
      'type' => 'list_text',
      'module' => 'list',
      'settings' => array(
        'required' => FALSE,
        'max_length' => '191',
        'allowed_values' => array(
          'curl' => 'cURL',
          'ruby' => 'Ruby',
          'python' => 'Python',
          'php' => 'PHP',
          'java' => 'Java',
          'node' => 'Node',
          'go' => 'Go',
          'swift' => 'Swift',
          'c' => 'C',
          'csharp' => 'C#',
        ),
        'allowed_values_function' => ''
      )
    );
    field_create_field($field);

    $instance = array(
      'field_name' => 'field_codesnippet',
      'entity_type' => 'user',
      'label' => 'Code Snippet Language',
      'bundle' => 'user',
      'required' => 0,
      'default_value' =>
        array(
          0 =>
            array(
              'value' => 'curl',
            ),
        ),
      'widget' => array(
        'active' => 1,
        'weight' => '-10',
        'type' => 'options_select',
        'module' => 'options'
      ),
      'display' => array(
        'default' => array(
          'type' => 'list_default',
          'module' => 'list'
        )
      ),
      'description' => 'Enter your preferred programming language to use as the default for API code snippets.',
      'settings' => array('user_register_form' => 0, 'weight' => -6)
    );
    field_create_instance($instance);
  }
}

?>