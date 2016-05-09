<?php

/**
 * @file
 * Content importing functions for APIm sample nodes.
 */

/**
 * Create sample nodes
 */
function apim_profile_create_nodes() {
  $startednode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'Getting Started',
    'log' => '',
    'status' => '1',
    'comment' => '1',
    'promote' => '0',
    'sticky' => '0',
    'vuuid' => '313492b4-758b-4b36-9562-b6cc320ed44e',
    'nid' => NULL,
    'type' => 'page',
    'language' => 'en',
    'created' => '1410168777',
    'changed' => '1412678800',
    'tnid' => '0',
    'translate' => '0',
    'uuid' => '64b69aaa-b7e1-409d-9741-e954f8b031b5',
    'revision_timestamp' => '1412678800',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => "<ol class=\"numberedList\"><li><h3>Browse the available APIs</h3><p>Take a look at our APIs to see what choices are available. Is there an API you can exploit in one of your applications? Use the supplied APIs to quickly construct a fully featured application.<br><a href=\"product\">Explore our APIs</a></p></li><li><h3>Sign Up</h3><p>Found an API? Sign up now to create an account and get started. It is free to join.<br><a href=\"user/register\">Create an account</a></p></li><li><h3>Register an application</h3><p>Before you can use an API you have to register your application. When you register an application, the application is assigned a unique client ID and client secret. You must use the client ID when you call an API that requires you to identify your application by using a client ID, or a client ID and client secret. Check the API description for the details.</p></li><li><h3>Select a plan</h3><p>Finally, now that your application is registered, you need to subscribe to a plan. The plan determines the number of API calls that your application can make. Some plans are free and no approval is required, some require approval, and some require approval and a monthly subscription. Think about what you need and choose the most suitable plan.</p></li></ol><p>All done! You are signed up, registered, and ready to go.</p>",
          'summary' => 'This is a getting started page',
          'format' => 'full_html',
          'safe_value' => "<ol class=\"numberedList\"><li><h3>Browse the available APIs</h3><p>Take a look at our APIs to see what choices are available. Is there an API you can exploit in one of your applications? Use the supplied APIs to quickly construct a fully featured application.<br><a href=\"product\">Explore our APIs</a></p></li><li><h3>Sign Up</h3><p>Found an API? Sign up now to create an account and get started. It is free to join.<br><a href=\"user/register\">Create an account</a></p></li><li><h3>Register an application</h3><p>Before you can use an API you have to register your application. When you register an application, the application is assigned a unique client ID and client secret. You must use the client ID when you call an API that requires you to identify your application by using a client ID, or a client ID and client secret. Check the API description for the details.</p></li><li><h3>Select a plan</h3><p>Finally, now that your application is registered, you need to subscribe to a plan. The plan determines the number of API calls that your application can make. Some plans are free and no approval is required, some require approval, and some require approval and a monthly subscription. Think about what you need and choose the most suitable plan.</p></li></ol><p>All done! You are signed up, registered, and ready to go.</p>",
          'safe_summary' => "<p>This is a getting started page</p>"))),
    'rdf_mapping' => array(
      'rdftype' => array('foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1410168777',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'b:0;',
    'entity_view_prepared' => TRUE,
    'menu' => array(
      'link_title' => 'Getting Started',
      'mlid' => 0,
      'plid' => '0',
      'menu_name' => 'main-menu',
      'weight' => '1',
      'options' => array(),
      'module' => 'menu',
      'expanded' => '0',
      'hidden' => '0',
      'has_children' => '0',
      'customized' => 0,
      'parent_depth_limit' => 8,
      'description' => 'How to get started using our APIs',
      'enabled' => 1));
  node_save($startednode);

  $supportnode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'Looking for help?',
    'log' => '',
    'status' => '1',
    'comment' => '1',
    'promote' => '0',
    'sticky' => '0',
    'vuuid' => '8cfcce3c-722c-4362-af11-90f0521d211b',
    'nid' => NULL,
    'type' => 'page',
    'language' => 'en',
    'created' => '1415631012',
    'changed' => '1415631012',
    'tnid' => '0',
    'translate' => '0',
    'uuid' => 'bb1997cf-3b34-4f9c-adb5-9781f8ebc2c9',
    'revision_timestamp' => '1415631012',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => '<ul class="supportList"><li><h3>Developer forum</h3><p>Search and participate in our developer forum. Ask questions because the experienced developers in the forum community might know the answers and are happy to help.<br><a href="forum">Visit developer forum</a></p></li><li><h3>FAQ</h3><p>Look in our frequently asked questions section to see if you can find the details that you need<br><a href="faq-page">Visit Frequently Asked Questions</a></p></li><li><h3>Support Tickets</h3><p>Should it be necessary this section is where you can see open support tickets or raise a new one.<br><a href="support">Raise a support ticket</a></p></li><li><h3>Contact us</h3><p>To ask a question or to send us feedback, open and complete the Contact Form. <br><a href="contact">Contact us</a></p></li></ul>',
          'summary' => '',
          'format' => 'full_html',
          'safe_value' => '<ul class="supportList"><li><h3>Developer forum</h3><p>Search and participate in our developer forum. Ask questions because the experienced developers in the forum community might know the answers and are happy to help.<br><a href="forum">Visit developer forum</a></p></li><li><h3>FAQ</h3><p>Look in our frequently asked questions section to see if you can find the details that you need<br><a href="faq-page">Visit Frequently Asked Questions</a></p></li><li><h3>Support Tickets</h3><p>Should it be necessary this section is where you can see open support tickets or raise a new one.<br><a href="support">Raise a support ticket</a></p></li><li><h3>Contact us</h3><p>To ask a question or to send us feedback, open and complete the Contact Form. <br><a href="contact">Contact us</a></p></li></ul>',
          'safe_summary' => ''))),
    'rdf_mapping' => array(
      'rdftype' => array('foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1415631012',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'b:0;',
    'menu' => array(
      'link_title' => 'Support',
      'mlid' => 0,
      'plid' => '0',
      'menu_name' => 'main-menu',
      'weight' => '30',
      'options' => array(),
      'module' => 'menu',
      'expanded' => '0',
      'hidden' => '0',
      'has_children' => '0',
      'customized' => 0,
      'parent_depth_limit' => 8,
      'description' => 'Need some help?',
      'enabled' => 1));
  node_save($supportnode);

  $termsnode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'Terms of use',
    'log' => '',
    'status' => '1',
    'comment' => '1',
    'promote' => '0',
    'sticky' => '0',
    'vuuid' => '313492b4-758b-4b36-9562-b6cc320ed44f',
    'nid' => NULL,
    'type' => 'page',
    'language' => 'en',
    'created' => '1410168777',
    'changed' => '1412678800',
    'tnid' => '0',
    'translate' => '0',
    'uuid' => '64b69aaa-b7e1-409d-9741-e954f8b031b6',
    'revision_timestamp' => '1412678800',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => "<p>The terms of service that are outlined here are a legal agreement between you and our company and by using this website you agree to these terms of service. Take time to read and understand what the terms are. If you do not agree to these terms, do not use this website. Without notification, the terms can be changed at any time. The content of this website might also change.</p>",
          'summary' => 'This is the terms of use page',
          'format' => 'full_html',
          'safe_value' => "<p>The terms of service that are outlined here are a legal agreement between you and our company and by using this website you agree to these terms of service. Take time to read and understand what the terms are. If you do not agree to these terms, do not use this website. Without notification, the terms can be changed at any time. The content of this website might also change.</p>\n",
          'safe_summary' => "<p>This is the terms of use page</p>\n"))),
    'rdf_mapping' => array(
      'rdftype' => array('foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1410168777',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'b:0;',
    'entity_view_prepared' => TRUE);
  node_save($termsnode);

  $privacynode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'Privacy policy',
    'log' => '',
    'status' => '1',
    'comment' => '1',
    'promote' => '0',
    'sticky' => '0',
    'vuuid' => '313492b4-758b-4b36-9562-b6cc320ed450',
    'nid' => NULL,
    'type' => 'page',
    'language' => 'en',
    'created' => '1410168777',
    'changed' => '1412678800',
    'tnid' => '0',
    'translate' => '0',
    'uuid' => '64b69aaa-b7e1-409d-9741-e954f8b031b7',
    'revision_timestamp' => '1412678800',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => "<p>We respect your privacy and we want to maintain your trust. This website may use session and persistent cookies to collect the user session ID for session management, or for functional purposes. You can disable these cookies, but if you disable the cookies you also remove the function they provide. </p>",
          'summary' => 'This is the privacy policy page',
          'format' => 'full_html',
          'safe_value' => "<p>We respect your privacy and we want to maintain your trust. This website may use session and persistent cookies to collect the user session ID for session management, or for functional purposes. You can disable these cookies, but if you disable the cookies you also remove the function they provide. </p>",
          'safe_summary' => 'This is the privacy policy page'))),
    'rdf_mapping' => array(
      'rdftype' => array('foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1410168777',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'b:0;',
    'entity_view_prepared' => TRUE);
  node_save($privacynode);

  // FAQ nodes
  $resetsecretnode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'How do I reset my application client secret?',
    'log' => '',
    'status' => '1',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
    'nid' => NULL,
    'type' => 'faq',
    'language' => 'en',
    'created' => '1417508096',
    'changed' => '1417508096',
    'tnid' => '0',
    'translate' => '0',
    'revision_timestamp' => '1417508096',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => "<p>Your application client secret is stored encrypted so we cannot retrieve the unencrypted version to tell you the value if you forget it.</p><p>You can reset it, which will update the stored value and return the new value to you.</p><p>To do that click 'Apps' in the main menu, click on the application in question and then you can click the 'Reset' link in the 'Client Secret' section.</p><p>Your new secret will be displayed at the top of the page.</p>",
          'summary' => '',
          'format' => 'filtered_html',
          'safe_value' => "<p>Your application client secret is stored encrypted so we cannot retrieve the unencrypted version to tell you the value if you forget it.</p>\n<p>You can reset it, which will update the stored value and return the new value to you.</p>\n<p>To do that click 'Apps' in the main menu, click on the application in question and then you can click the 'Reset' link in the 'Client Secret' section.</p>\n<p>Your new secret will be displayed at the top of the page.</p>\n",
          'safe_summary' => ''))),
    'field_detailed_question' => array(
      'und' => array(
        array(
          'value' => '<p>I have forgotten my application client secret. How can I reset it?</p>',
          'format' => 'filtered_html',
          'safe_value' => "<p>I have forgotten my application client secret. How can I reset it?</p>\n"))),
    'rdf_mapping' => array(
      'rdftype' => array('sioc:Item', 'foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1417508096',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'a:1:{s:7:"contact";i:1;}',
    'entity_view_prepared' => TRUE);
  node_save($resetsecretnode);

  $whatplannode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'What are plans?',
    'log' => '',
    'status' => '1',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
    'nid' => NULL,
    'type' => 'faq',
    'language' => 'en',
    'created' => '1417513784',
    'changed' => '1417513798',
    'tnid' => '0',
    'translate' => '0',
    'revision_timestamp' => '1417513798',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => '<p><span>A plan is collection of API resources or subsets of resources from one or more API. A plan can contain a mixture of HTTP GET, PUT, POST, and DELETE verbs from different APIs or it can contain all the GET verbs from various APIs. A plan can have a common rate limit for all the resources or each resource can have a different rate limit. Rate limits specify how many requests an application is allowed to make during a specified time interval.</span></p><p>Use this Developer<span class="ph">&nbsp;Portal</span>&nbsp;to browse the different plans that are available to you and select a plan that is most suitable for your requirements. Some plans have restricted access that you must request access to use. When you submit your request, the organization is notified, the API administrator assesses your request and they might contact you for more details. Other plans are available to use straight away.</p>',
          'summary' => '',
          'format' => 'filtered_html',
          'safe_value' => "<p><span>A plan is collection of API resources or subsets of resources from one or more API. A plan can contain a mixture of HTTP GET, PUT, POST, and DELETE verbs from different APIs or it can contain all the GET verbs from various APIs. A plan can have a common rate limit for all the resources or each resource can have a different rate limit. Rate limits specify how many requests an application is allowed to make during a specified time interval.</span></p>\n<p>Use this Developer<span class=\"ph\"> Portal</span> to browse the different plans that are available to you and select a plan that is most suitable for your requirements. Some plans have restricted access that you must request access to use. When you submit your request, the organization is notified, the API administrator assesses your request and they might contact you for more details. Other plans are available to use straight away.</p>\n",
          'safe_summary' => ''))),
    'field_detailed_question' => array(
      'und' => array(
        array(
          'value' => '<p>I just want to use an API? What are plans?</p>',
          'format' => 'filtered_html',
          'safe_value' => "<p>I just want to use an API? What are plans?</p>\n"))),
    'rdf_mapping' => array(
      'rdftype' => array('sioc:Item', 'foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1417513784',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'a:1:{s:7:"contact";i:1;}',
    'entity_view_prepared' => TRUE);
  node_save($whatplannode);

  $registerappnode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'How do I register an application?',
    'log' => '',
    'status' => '1',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
    'nid' => NULL,
    'type' => 'faq',
    'language' => 'en',
    'created' => '1417514088',
    'changed' => '1417514088',
    'tnid' => '0',
    'translate' => '0',
    'revision_timestamp' => '1417514088',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => "<p>When you add an application you are provided with a client ID and client secret for the application. You must supply the client ID when you call an API that requires you to identify your application by using a client ID, or a client ID and client secret.</p><p>To register an application click on Apps in the main menu and then click on the 'Register an application' link. Once you have provided an application name, description, etc you will be shown your application client ID and client secret.</p><p><span id=\"task_devportal_registerapps__MakeANoteOfYourClientSecret.\" class=\"ph cmd\">Make a note of your client secret because it is only displayed once.</span>&nbsp;You must supply the client secret when you call an API that requires you to identify your application by using a Client ID and Client secret.</p>",
          'summary' => '',
          'format' => 'filtered_html',
          'safe_value' => "<p>When you add an application you are provided with a client ID and client secret for the application. You must supply the client ID when you call an API that requires you to identify your application by using a client ID, or a client ID and client secret.</p>\n<p>To register an application click on Apps in the main menu and then click on the 'Register an application' link. Once you have provided an application name, description, etc you will be shown your application client ID and client secret.</p>\n<p><span id=\"task_devportal_registerapps__MakeANoteOfYourClientSecret.\" class=\"ph cmd\">Make a note of your client secret because it is only displayed once.</span> You must supply the client secret when you call an API that requires you to identify your application by using a Client ID and Client secret.</p>\n",
          'safe_summary' => ''))),
    'field_detailed_question' => array(
      'und' => array(
        array(
          'value' => '<p><span>&nbsp;</span></p>',
          'format' => 'filtered_html',
          'safe_value' => "<p><span> </span></p>\n"))),
    'rdf_mapping' => array(
      'rdftype' => array('sioc:Item', 'foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1417514088',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'a:1:{s:7:"contact";i:1;}',
    'entity_view_prepared' => TRUE);
  node_save($registerappnode);

  $apiusagenode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'How do I see my API usage?',
    'log' => '',
    'status' => '1',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
    'nid' => NULL,
    'type' => 'faq',
    'language' => 'en',
    'created' => '1417514520',
    'changed' => '1417514520',
    'tnid' => '0',
    'translate' => '0',
    'revision_timestamp' => '1417514520',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => "<p>The numbers of requests, for different APIs, that your application has made are shown on your application page.</p><p>Click 'Apps' in the main menu and then click on your application. Under 'Subscribed Plans' you will see all plans your application is subscribed to.&nbsp;</p><p>For each API contained in that plan you can see the usage compared to the rate limit of the plan.</p>",
          'summary' => '',
          'format' => 'filtered_html',
          'safe_value' => "<p>The numbers of requests, for different APIs, that your application has made are shown on your application page.</p>\n<p>Click 'Apps' in the main menu and then click on your application. Under 'Subscribed Plans' you will see all plans your application is subscribed to. </p>\n<p>For each API contained in that plan you can see the usage compared to the rate limit of the plan.</p>\n",
          'safe_summary' => ''))),
    'field_detailed_question' => array(
      'und' => array(
        array(
          'value' => '<p>The plan I subscribed to has a limited amount of calls per API. How can I monitor my usage against that?</p>',
          'format' => 'filtered_html',
          'safe_value' => "<p>The plan I subscribed to has a limited amount of calls per API. How can I monitor my usage against that?</p>\n"))),
    'rdf_mapping' => array(
      'rdftype' => array('sioc:Item', 'foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1417514520',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'a:1:{s:7:"contact";i:1;}',
    'entity_view_prepared' => TRUE);
  node_save($apiusagenode);

  $testapinode = (object) array(
    'vid' => NULL,
    'uid' => '1',
    'title' => 'How can I test an API?',
    'log' => '',
    'status' => '1',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
    'nid' => NULL,
    'type' => 'faq',
    'language' => 'en',
    'created' => '1417515201',
    'changed' => '1417515300',
    'tnid' => '0',
    'translate' => '0',
    'revision_timestamp' => '1417515300',
    'revision_uid' => '1',
    'body' => array(
      'und' => array(
        array(
          'value' => "<p>It is possible to test an API from this Developer Portal.</p><p>When looking at the details of an API you will see a table of the operations contained in the API. This will show what method they are (GET, POST, PUT, DELETE, PATCH, HEAD or OPTIONS) and what path the Resource uses.</p><p>If you click on the Resource you will see more information about it, what parameters it might take, what it returns, what possible return codes it might use and what they mean.</p><p>There is also a 'Try' button which enables you to try the Resource out direct from the Developer Portal.</p><p>If the API requires a client ID or a client secret for identification then you can specify these at the top of the 'Try' section.</p>",
          'summary' => '',
          'format' => 'filtered_html',
          'safe_value' => "<p>It is possible to test an API from this Developer Portal.</p>\n<p>When looking at the details of an API you will see a table of the operations contained in the API. This will show what method they are (GET, POST, PUT, DELETE, PATCH, HEAD or OPTIONS) and what path the Resource uses.</p>\n<p>If you click on the Resource you will see more information about it, what parameters it might take, what it returns, what possible return codes it might use and what they mean.</p>\n<p>There is also a 'Try' button which enables you to try the Resource out direct from the Developer Portal.</p>\n<p>If the API requires a client ID or a client secret for identification then you can specify these at the top of the 'Try' section.</p>\n",
          'safe_summary' => ''))),
    'field_detailed_question' => array(
      'und' => array(
        array(
          'value' => '<p>Is it possible to test an API before signing up to a plan?</p>',
          'format' => 'filtered_html',
          'safe_value' => "<p>Is it possible to test an API before signing up to a plan?</p>\n"))),
    'rdf_mapping' => array(
      'rdftype' => array('sioc:Item', 'foaf:Document'),
      'title' => array('predicates' => array('dc:title')),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'changed' => array(
        'predicates' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601'),
      'body' => array('predicates' => array('content:encoded')),
      'uid' => array('predicates' => array('sioc:has_creator'), 'type' => 'rel'),
      'name' => array('predicates' => array('foaf:name')),
      'comment_count' => array(
        'predicates' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer'),
      'last_activity' => array(
        'predicates' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'callback' => 'date_iso8601')),
    'cid' => '0',
    'last_comment_timestamp' => '1417515201',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
    'name' => 'admin',
    'picture' => '0',
    'data' => 'a:1:{s:7:"contact";i:1;}',
    'entity_view_prepared' => TRUE);
  node_save($testapinode);
}
?>