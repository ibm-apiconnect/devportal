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

/**
 * @file
 * Mainly used to disable selection of forum container "APIs" while creating and editing a forum topic
 */
(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.disableParentTerms = {
      attach: function (context, settings) {
        const disableTids = drupalSettings.forum_container?.disableTerms || [];
  
        disableTids.forEach(function (tid) {
          $('#edit-taxonomy-forums option[value="' + tid + '"]', context).attr('disabled', true);
        });
      }
    };
  })(jQuery, Drupal, drupalSettings);
  
  