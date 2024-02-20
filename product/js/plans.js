/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2021, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

(function ($, Drupal) {

  let planModalShowing = false;

  function togglePlanDetails(planIndex) {
    const modal = $(`.planDetail--modal_wrapper[data-planindex="${planIndex}"]`);

    planModalShowing = !planModalShowing
    modal.attr('aria-hidden', planModalShowing ? 'false' : 'true');
    modal.toggle();
  }

  function hasOpenTwistie(rows) {
    return rows.toArray().some((element) => !$(element).hasClass("hiddenRow"))
  }

  function openTwistieArrow(twistie) {
    if(!twistie.hasClass('flip')){
      twistie.addClass('flip');
    }
  }

  function closeTwistieArrow(twistie) {
    if(twistie.hasClass('flip')){
      twistie.removeClass('flip');
    }
  }

  function toggleTwistie(rowIndex) {
    const row = $(`[data-twistierowid="row_${rowIndex}"]`);
    row.toggleClass("hiddenRow");
    const twistie = $(`[data-twistieid="twistie_${rowIndex}"]`);
    twistie.toggleClass("flip");

    toggleHeaderTwistie();
  }

  function toggleHeaderTwistie() {
    const twistieHeader = $('[data-twistieheaderid="twistie_header"]')
    const rows = $('[data-twistierowid*="row_"]');
    if (hasOpenTwistie(rows)) {
      openTwistieArrow(twistieHeader)
    } else {
      closeTwistieArrow(twistieHeader)
    }
  }

  function toggleAllTwisties() {
    const rows = $('[data-twistierowid*="row_"]');
    if(hasOpenTwistie(rows)) {
      closeTwisties();
    } else {
      openTwisties();
    }
    toggleHeaderTwistie();
  }

  function openTwisties() {
    const rows = $('[data-twistierowid*="row_"]');
    rows.each(function() {
      if($(this).hasClass('hiddenRow')){
        $(this).removeClass('hiddenRow');
      }
    });

    const twisties = $('[data-twistieid*="twistie_"]');
    twisties.each(function() {
      openTwistieArrow($(this));
    });
  }

  function closeTwisties() {
    const rows = $('[data-twistierowid*="row_"]');
    rows.each(function() {
      if(!$(this).hasClass('hiddenRow')){
        $(this).addClass('hiddenRow')
      }
    })

    const twisties = $('[data-twistieid*="twistie_"]');
    twisties.each(function() {
      closeTwistieArrow($(this));
    });

    const twistieHeader = $('[data-twistieheaderid="twistie_header"]');
    closeTwistieArrow(twistieHeader);
    }

  let api = {
    togglePlanDetails,
    toggleTwistie,
    closeTwisties,
    toggleAllTwisties
  };
  if (!Drupal.settings) {
    Drupal.settings = {};
  }
  Drupal.settings.plans = api;

  Drupal.behaviors.plansTable = {
    attach: function(context, settings) {
        if(!Drupal.behaviors.plansTable.click_set){
          $('#twistieHeader', context).on("click", function() {
            toggleAllTwisties();
          });

          $('.planDetail--modalClose').on("click", function() {
            closeTwisties();
          });

          $('.rowTwistie').on("click", function() {
            var index = $(this).data('rowid');
            toggleTwistie(index);
          });

          Drupal.behaviors.plansTable.click_set = true;
        }
    }
  };
})(jQuery, Drupal);
