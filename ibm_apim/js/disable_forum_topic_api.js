
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
  
  