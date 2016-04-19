/*
  @file
  Defines the simple modal behavior
*/

(function ($) {
  /*
    Add the class 'simple-dialog' to open links in a dialog
    You also need to specify 'rev="<selector>"' where the <selector>
    is the unique id of the container to load from the linked page.
    Any additional jquery ui dialog options can be passed through
    the rel tag using the format:
       rel="<option_name1>:<value1>;<option_name2>:<value2>;"
    e.g. <a href="financing/purchasing-options" class="simple-dialog"
          rel="width:900;resizable:false;position:[60,center]"
          rev="content-area" title="Purchasing Options">Link</a>
    NOTE: This method doesn't not bring javascript files over from
    the target page. You will need to make sure your javascript is
    either inline in the html that's being loaded, or in the head tag
    of the page you are on.
    ALSO: Make sure the jquery ui.dialog library has been added to the page
  */
  Drupal.behaviors.simpleDialog = {
    attach: function (context, settings) {

      // Create a container div for the modal if one isn't there already
      if ($("#simple-dialog-container").length == 0) {
        // Add a container to the end of the body tag to hold the dialog
        $('body').append('<div id="simple-dialog-container" style="display:none;"></div>');
        try {
          // Attempt to invoke the simple dialog
          if (typeof $('#simple-dialog-container').dialog === 'function') {
            $( "#simple-dialog-container", context).dialog({
              autoOpen: false,
              modal: true,
              close: function(event, ui) {
              // Clear the dialog on close. Not necessary for your average use
              // case, butis useful if you had a video that was playing in the
              // dialog so that it clears when it closes
                $('#simple-dialog-container').html('');
              }
            });
            var defaultOptions = Drupal.simpleDialog.explodeOptions('z-index:501;width:500;height:auto');
            $('#simple-dialog-container').dialog('option', defaultOptions);
          }
        }
        catch (err) {
          // Catch any errors and report
          Drupal.simpleDialog.log('[error] Simple Dialog: ' + err);
        }
      }

      // Add support for custom classes if necessary
      var classes = '';
      $('button.simple-dialog' + classes, context).each(function(event) {
        if (!event.metaKey && !$(this).hasClass('simpleDialogProcessed')) {

          // Add a class to show that this link has been processed already
          $(this).addClass('simpleDialogProcessed');

          $(this).click(function(event) {

            // prevent the navigation
            event.preventDefault();

            // Set up some variables
            var url = $(this).attr('data-href');

            var title = $(this).attr('data-title');
            if (!title) {
                title = $(this).text();
            }

            var selector = $(this).attr('data-name');
            var options = $(this).attr('data-rel');
            if (url && title && selector && $("#simple-dialog-container")) {
              // Set the custom options of the dialog
              $('#simple-dialog-container').dialog('option', options);
              // Set the title of the dialog
              $('#simple-dialog-container').dialog('option', 'title', title);
              // Add a little loader into the dialog while data is loaded
              $('#simple-dialog-container').html('<div class="simple-dialog-ajax-loader"></div>');

              // Change the height if it's set to auto
              if (options.height && options.height == 'auto') {
                $('#simple-dialog-container').dialog('option', 'height', 200);
              }

              // Use jQuery .get() to request the target page
              $.get(url, function(data) {

                // Re-apply the height if it's auto to accomodate the new content
                if (options.height && options.height == 'auto') {
                  $('#simple-dialog-container').dialog('option', 'height', options.height);
                }

                // Some trickery to make sure any inline javascript gets run.
                // Inline javascript gets removed/moved around when passed into
                // $() so you have to create a fake div and add the raw data into
                // it then find what you need and clone it. Fun.
                var dialogContent = $(data).find('#' + selector).clone();
                if (dialogContent) {
                  $('#simple-dialog-container').html( $( '<div></div>' ).html( dialogContent ) );
                }
                else {
                  $('#simple-dialog-container').html('<div class="simple-dialog-error">Error loading dialog content</div>');
                }

                // Attach any behaviors to the loaded content
                Drupal.attachBehaviors($('#simple-dialog-container'));

                // Trigger a custom event
                $('#simple-dialog-container').trigger('simpleDialogLoaded');
              })
              .fail(function() {
                // If the request fails
                $('#simple-dialog-container').html('<div class="simple-dialog-error">Error loading dialog content</div>');
              });

              // Open the dialog
              $('#simple-dialog-container').dialog('open');

              // Return false for good measure
              return false;
            }
          });
        }
      });

    }
  }

  // Create a namespace for our simple dialog module
  Drupal.simpleDialog = {};

  // Convert the options to an object
  Drupal.simpleDialog.explodeOptions = function (opts) {
    var options = opts.split(';');
    var explodedOptions = {};
    for (var i in options) {
      if (options[i]) {
        // Parse and Clean the option
        var option = Drupal.simpleDialog.cleanOption(options[i].split(':'));
        explodedOptions[option[0]] = option[1];
      }
    }
    return explodedOptions;
  }

  // Function to clean up the option.
  Drupal.simpleDialog.cleanOption = function(option) {
    // If it's a position option, we may need to parse an array
    if (option[0] == 'position' && option[1].match(/\[.*,.*\]/)) {
      option[1] = option[1].match(/\[(.*)\]/)[1].split(',');
      // Check if positions need be converted to int
      if (!isNaN(parseInt(option[1][0]))) {
        option[1][0] = parseInt(option[1][0]);
      }
      if (!isNaN(parseInt(option[1][1]))) {
        option[1][1] = parseInt(option[1][1]);
      }
    }
    // Convert text boolean representation to boolean
    if (option[1] === 'true') {
      option[1]= true;
    }
    else if (option[1] === 'false') {
      option[1] = false;
    }
    return option;
  }

  Drupal.simpleDialog.log = function(msg) {
    if (window.console) {
      window.console.log(msg);
    }

  }

})(jQuery);
