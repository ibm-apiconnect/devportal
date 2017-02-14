<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Content importing functions for APIm sample nodes.
 */

/**
 * Create sample nodes
 */
function apim_profile_create_nodes() {
  apim_profile_create_getting_started_page();
  apim_profile_create_support_page();
  apim_profile_create_terms_page();
  apim_profile_create_privacy_page();
  apim_profile_create_faqs();
}


function apim_profile_create_getting_started_page() {
  $content = array(
    'type' => 'page',
    'title' => t("Getting started"),
    'summary' => t('This is a getting started page'),
    'body' => t("<ol class=\"numberedList\"><li><h3>Browse the available APIs</h3><p>Take a look at our APIs to see what choices are available. Is there an API you can exploit in one of your applications? Use the supplied APIs to quickly construct a fully featured application.</p><p><a href=\"product\">Explore our APIs</a></p></li><li><h3>Sign Up</h3><p>Found an API? Sign up now to create an account and get started. It is free to join.</p><p><a href=\"user/register\">Create an account</a></p></li><li><h3>Register an application</h3><p>Before you can use an API you have to register your application. When you register an application, the application is assigned a unique client ID and client secret. You must use the client ID when you call an API that requires you to identify your application by using a client ID, or a client ID and client secret. Check the API description for the details.</p></li><li><h3>Select a plan</h3><p>Finally, now that your application is registered, you need to subscribe to a plan. The plan determines the number of API calls that your application can make. Some plans are free and no approval is required, some require approval, and some require approval and a monthly subscription. Think about what you need and choose the most suitable plan.</p></li></ol><p>All done! You are signed up, registered, and ready to go.</p>"),
    'description' => t("How to get started using our APIs"),
    'menu' => 'main-menu',
    'question' => '',
    'weight' => 1
  );
  _apim_profile_create_node($content);
}

function apim_profile_create_support_page() {
  $content = array(
    'type' => 'page',
    'title' => t("Looking for help?"),
    'summary' => '',
    'body' => t("<ul class=\"supportList\"><li><h3>Developer forum</h3><p>Search and participate in our developer forum. Ask questions because the experienced developers in the forum community might know the answers and are happy to help.<br><a href=\"forum\">Visit developer forum</a></p></li><li><h3>FAQ</h3><p>Look in our frequently asked questions section to see if you can find the details that you need<br><a href=\"faq-page\">Visit Frequently Asked Questions</a></p></li><li><h3>Support Tickets</h3><p>Should it be necessary this section is where you can see open support tickets or raise a new one.<br><a href=\"support\">Raise a support ticket</a></p></li><li><h3>Contact us</h3><p>To ask a question or to send us feedback, open and complete the Contact Form. <br><a href=\"contact\">Contact us</a></p></li></ul>"),
    'description' => t('Need some help?'),
    'menu' => 'main-menu',
    'link_title' => t('Support'),
    'question' => '',
    'weight' => 30
  );
  _apim_profile_create_node($content);
}

function apim_profile_create_terms_page() {
  $content = array(
    'type' => 'page',
    'title' => t("Terms of use"),
    'summary' => t('This is the terms of use page'),
    'body' => t("<p>The terms of service that are outlined here are a legal agreement between you and our company and by using this website you agree to these terms of service. Take time to read and understand what the terms are. If you do not agree to these terms, do not use this website. Without notification, the terms can be changed at any time. The content of this website might also change.</p>"),
    'description' => '',
    'menu' => NULL,
    'question' => ''
  );
  _apim_profile_create_node($content);
}

function apim_profile_create_privacy_page() {
  $content = array(
    'type' => 'page',
    'title' => t("Privacy policy"),
    'summary' => t('This is the privacy policy page'),
    'body' => t("<p>We respect your privacy and we want to maintain your trust. This website may use session and persistent cookies to collect the user session ID for session management, or for functional purposes. You can disable these cookies, but if you disable the cookies you also remove the function they provide. </p>"),
    'description' => '',
    'menu' => NULL,
    'question' => ''
  );
  _apim_profile_create_node($content);
}

function apim_profile_create_faqs() {
  $content = array(
    'type' => 'faq',
    'title' => t("How do I reset my application client secret?"),
    'summary' => '',
    'body' => t("<p>Your application client secret is stored encrypted so we cannot retrieve the unencrypted version to tell you the value if you forget it.</p><p>You can reset it, which will update the stored value and return the new value to you.</p><p>To do that click 'Apps' in the main menu, click on the application in question and then you can click the 'Reset' link in the 'Client Secret' section.</p><p>Your new secret will be displayed at the top of the page.</p>"),
    'description' => '',
    'menu' => NULL,
    'question' => t('<p>I have forgotten my application client secret. How can I reset it?</p>')
  );
  _apim_profile_create_node($content);
  $content = array(
    'type' => 'faq',
    'title' => t("What are plans?"),
    'summary' => '',
    'body' => t("<p><span>A plan is collection of API resources or subsets of resources from one or more API. A plan can contain a mixture of HTTP GET, PUT, POST, and DELETE verbs from different APIs or it can contain all the GET verbs from various APIs. A plan can have a common rate limit for all the resources or each resource can have a different rate limit. Rate limits specify how many requests an application is allowed to make during a specified time interval.</span></p><p>Use this Developer<span class=\"ph\">&nbsp;Portal</span>&nbsp;to browse the different plans that are available to you and select a plan that is most suitable for your requirements. Some plans have restricted access that you must request access to use. When you submit your request, the organization is notified, the API administrator assesses your request and they might contact you for more details. Other plans are available to use straight away.</p>"),
    'description' => '',
    'menu' => NULL,
    'question' => t('<p>I just want to use an API? What are plans?</p>')
  );
  _apim_profile_create_node($content);
  $content = array(
    'type' => 'faq',
    'title' => t("How do I register an application?"),
    'summary' => '',
    'body' => t("<p>When you add an application you are provided with a client ID and client secret for the application. You must supply the client ID when you call an API that requires you to identify your application by using a client ID, or a client ID and client secret.</p><p>To register an application click on Apps in the main menu and then click on the 'Register an application' link. Once you have provided an application name, description, etc you will be shown your application client ID and client secret.</p><p><span id=\"task_devportal_registerapps__MakeANoteOfYourClientSecret.\" class=\"ph cmd\">Make a note of your client secret because it is only displayed once.</span>&nbsp;You must supply the client secret when you call an API that requires you to identify your application by using a Client ID and Client secret.</p>"),
    'description' => '',
    'menu' => NULL,
    'question' => t('<p>&nbsp</p>')
  );
  _apim_profile_create_node($content);
  $content = array(
    'type' => 'faq',
    'title' => t("How do I see my API usage?"),
    'summary' => '',
    'body' => t("<p>The numbers of requests, for different APIs, that your application has made are shown on your application page.</p><p>Click 'Apps' in the main menu and then click on your application. Under 'Subscribed Plans' you will see all plans your application is subscribed to.&nbsp;</p><p>For each API contained in that plan you can see the usage compared to the rate limit of the plan.</p>"),
    'description' => '',
    'menu' => NULL,
    'question' => t('<p>The plan I subscribed to has a limited amount of calls per API. How can I monitor my usage against that?</p>')
  );
  _apim_profile_create_node($content);
  $content = array(
    'type' => 'faq',
    'title' => t("How can I test an API?"),
    'summary' => '',
    'body' => t("<p>It is possible to test an API from this Developer Portal.</p><p>When looking at the details of an API you will see a table of the operations contained in the API. This will show what method they are (GET, POST, PUT, DELETE, PATCH, HEAD or OPTIONS) and what path the Resource uses.</p><p>If you click on the Resource you will see more information about it, what parameters it might take, what it returns, what possible return codes it might use and what they mean.</p><p>There is also a 'Try' button which enables you to try the Resource out direct from the Developer Portal.</p><p>If the API requires a client ID or a client secret for identification then you can specify these at the top of the 'Try' section.</p>"),
    'description' => '',
    'menu' => NULL,
    'question' => t('<p>Is it possible to test an API before signing up to a plan?</p>')
  );
  _apim_profile_create_node($content);
  variable_set('ibm_apim_fix_faq_typo', 1);
}

/**
 * Utility function to create nodes during the install
 *
 * @param array $content
 * @throws \Exception
 */
function _apim_profile_create_node($content = array()) {
  $type = $content['type'];
  $title = $content['title'];
  $summary = $content['summary'];
  $body = $content['body'];
  $question = $content['question'];
  $description = $content['description'];
  $menu = $content['menu'];
  // Prepare objects with defaults.
  $node = new stdClass();
  $node->nid = NULL;
  $node->type = $type;
  $node->uid = 1;
  $node->status = 1;
  $node->comment = 1;
  $node->promote = 0;
  $node->sticky = 0;
  node_object_prepare($node);
  // Create the node in the site's default language.
  $lang_default = 'en';
  $node->language = $lang_default;
  // Set title and body fields.
  $node->title = $title;
  $node->body[$lang_default] = array(
    array(
      'value' => $body,
      'summary' => $summary,
      'safe_summary' => '<p>' . $summary . '</p>',
      'format' => 'filtered_html',
      'safe_value' => $body
    )
  );
  if ($type == 'faq') {
    $node->field_detailed_question[$lang_default] = array(
      array(
        'value' => $question,
        'format' => 'filtered_html',
        'safe_value' => $question
      )
    );
  }
  $node->rdf_mapping = array(
    'rdftype' => array('foaf:Document'),
    'title' => array('predicates' => array('dc:title')),
    'created' => array(
      'predicates' => array('dc:date', 'dc:created'),
      'datatype' => 'xsd:dateTime',
      'callback' => 'date_iso8601'
    ),
    'changed' => array(
      'predicates' => array('dc:modified'),
      'datatype' => 'xsd:dateTime',
      'callback' => 'date_iso8601'
    ),
    'body' => array('predicates' => array('content:encoded')),
    'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
    'name' => array('predicates' => array('foaf:name')),
    'comment_count' => array(
      'predicates' => array('sioc:num_replies'),
      'datatype' => 'xsd:integer'
    ),
    'last_activity' => array(
      'predicates' => array('sioc:last_activity_date'),
      'datatype' => 'xsd:dateTime',
      'callback' => 'date_iso8601'
    )
  );
  if (isset($menu)) {
    // Create a menu entry.
    // for translated menu entries the initial one has to be set to 'und'
    // very weird quirk of drupal
    $link = array(
      'enabled' => TRUE,
      'link_title' => isset($content['link_title']) ? $content['link_title'] : $node->title,
      'description' => $description,
      'menu_name' => $menu,
      'customized' => 1,
      'language' => 'und',
      'weight' => $content['weight']
    );
    $node->menu = $link;
  }
  // Save node.
  node_save($node);
  if (isset($menu)) {
    // Enable menu translation and dependencies, if not already enabled.
    // Note: ensure the menu is set to "Translate and Localize", otherwise
    // all links will appear in all languages!
    if (!module_exists('i18n_menu')) {
      module_enable(array('i18n_menu'), TRUE);
    }
    // Create a translation set to link the original menu items created above
    // to the menu translations that we will create further below.
    $menu_translation_set = i18n_translation_set_create('menu_link');
    variable_set('ibm_apim_translation_set_id', $menu_translation_set->tsid);

    $default_menu_item = menu_link_load($node->menu['mlid']);
    $default_menu_item['i18n_tsid'] = $menu_translation_set->tsid;
    $default_menu_item['customized'] = 1;
    menu_link_save($default_menu_item);
  }
}
