/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
(function ($, Drupal, drupalSettings) {

  function toggleTwistie(evt) {
    var parent = evt.target.parentNode;
    while (parent && !parent.classList.contains("apicTwistieTitle")) {
      parent = parent.parentNode;
    } // end while
    if (parent) {
      parent.parentNode.classList.toggle("apicTwistieOpen");
    }
  }
  function togglePlan(evt) {
    var parent = evt.target.parentNode;
    while (parent && !parent.classList.contains("apicTwistieTitle")) {
      parent = parent.parentNode;
    } // end while
    if (parent) {
      parent.parentNode.parentNode.classList.toggle("apicTwistieOpen");
    }
  }

  function toggleTwistieRow(evt) {
    var parent = evt.target.parentNode;
    while (parent && parent.tagName != "TR") {
      parent = parent.parentNode;
    } // end while
    if (parent) {
      parent.classList.toggle("apicTwistieOpen");
      parent.nextElementSibling.classList.toggle("visible");
    }
  }

  function showPlans(evt) {
    $('.apicPlansTableBody').addClass('hidden');
    $('th.apicFixedColumn.tlcorner').addClass('hidden');
    $('.multipleRateLimitsMessage').addClass('hidden');
    $('.planTableSelector .showPlans').addClass('hidden');
    $('.planTableSelector .showComparisonTable').removeClass('hidden');
    $('.multipleRateLimitsMessage').addClass('hidden');
  }

  function showTable(evt) {
    $('.apicPlansTableBody').removeClass('hidden');
    $('th.apicFixedColumn.tlcorner').removeClass('hidden');
    $('.multipleRateLimitsMessage').removeClass('hidden');
    $('.planTableSelector .showPlans').removeClass('hidden');
    $('.planTableSelector .showComparisonTable').addClass('hidden');
  }

  var api = {
    toggleTwistie: toggleTwistie, togglePlan: togglePlan, toggleTwistieRow: toggleTwistieRow, showPlans: showPlans, showTable: showTable
  };

  if (!Drupal.settings) {
    Drupal.settings = {};
  }
  Drupal.settings.product = api;

  Drupal.behaviors.rateLimits = {
    attach: function (context, settings) {
      if ($(document, context).tooltip && typeof($(document).tooltip) == "function") {
        // add support for data-title

        var html =
        $(document, context).tooltip({
          selector: "[data-ratelimits]", html: true, title: function () {
            var element = $(this);
            if (element.is("[data-ratelimits]")) {
              var data = $.parseJSON(element[0].dataset.ratelimits);
              var output = '<div class="ratePopup">';
              if (data['#rates'] && data['#rates'].length > 0) {
                if (data['#rateLabel']) {
                  output += '<span class="rateLabel">' + data['#rateLabel'] + '</span><br/>';
                }
                else {
                  output += '<span class="rateLabel">Rate limits</span><br/>';
                }
                  data['#rates'].forEach(function (rate) {
                  output += rate + '<br/>';
                });
              }
              if (data['#bursts'] && data['#bursts'].length > 0) {
                if (data['#burstLabel']) {
                  output += '<span class="burstLabel">' + data['#burstLabel'] + '</span><br/>';
                }
                else {
                  output += '<span class="burstLabel">Burst limits</span><br/>';
                }
                  data['#bursts'].forEach(function (rate) {
                  output += rate + '<br/>';
                });
              }
              output += '</div>';
              return output;
            }
          }
        });
      }
    }
  };
  Drupal.behaviors.productSelectSetup = {
    attach: function (context) {

      $('.productMultiSelect .productCards').masonry({
        // options
        itemSelector: '.node--view-mode-card', columnWidth: '.node--view-mode-card', gutter: 20
      });

    }
  };
})(jQuery, Drupal, drupalSettings);
