<?php

/**
 * Implementation of hook_rules_event_info().
 */
function mail_subscribers_rules_event_info() {
  $defaults = array(
    'group' => t('Mail Subscribers'),
  );
  return array(
    'mail_subscribers_email_sent' => $defaults + array(
        'label' => t('After sending an individual email'),
        'variables' => array(
          'mail_subscribers_email_message' => array(
            'type' => 'mail_subscribers_email_message',
            'label' => t('e-mail message'),
            'description' => t('All information about the message.')
          ),
        ),
      ),
    'mail_subscribers_all_email_added_to_spool' => $defaults + array(
        'label' => t('After adding all e-mails to the spool'),
        'variables' => array(
          'mail_subscribers_email_count' => array(
            'type' => 'integer',
            'label' => t('message count'),
            'description' => t('The number of messages added to the spool.')
          ),
        ),
      ),
    'mail_subscribers_email_added_to_spool' => $defaults + array(
        'label' => t('After adding an individual e-mail to the spool'),
        'variables' => array(
          'mail_subscribers_email_message' => array(
            'type' => 'mail_subscribers_email_message',
            'label' => t('e-mail message'),
            'description' => t('All information about the message.')
          ),
        ),
      ),
  );
}

/**
 * Implementation of hook_rules_data_info().
 */
function mail_subscribers_rules_data_info() {
  return array(
    'mail_subscribers_email_message' => array(
      'label' => t('Views Send e-mail message'),
      'group' => t('Views Send'),
      'wrap' => TRUE,
      'property info' => _mail_subscribers_email_message_property_info(),
    ),
  );
}
