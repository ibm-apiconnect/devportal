/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Setup global config for analytics
 */

(function($, Drupal, drupalSettings) {

  Drupal.behaviors.subsAnalyticsConfig = {
    attach: function(context) {

      if (drupalSettings.analytics && drupalSettings.analytics.analyticsDir) {
        window.apiConnectAnalytics = [];
        window.apiConnectAnalytics.options = [];
        window.apiConnectAnalytics.options.analyticsDir = drupalSettings.analytics.analyticsDir;
      }

      if (drupalSettings.analytics.proxyURL) {
        let anv = new window.AnalyticsNativeVis({
          settings: {
            url: drupalSettings.analytics.proxyURL,
            refreshRate: '1m'
          },
          user: {
            context: {},
            config: {}
          }
        });
        if (!drupalSettings.anv) {
          drupalSettings.anv = [];
        }

        if (!drupalSettings.anv.appIdFilter) {
          drupalSettings.anv.appIdFilter = anv.createFilter({
            "field": "app_id",
            "values": [ drupalSettings.application.id ],
            "selectedValue": drupalSettings.application.id
          });
        }

        if (!drupalSettings.anv.static1hTime) {
          drupalSettings.anv.static1hTime = anv.createTime({
            "field": "datetime",
            "values": [ "1h" ],
            "selectedValue": "1h"
          });
        }

        if (!drupalSettings.anv.subscriptionsWidget) {
          drupalSettings.anv.subscriptionsWidget = anv.createWidget({
            "id": "subscriptions",
            "time": drupalSettings.anv.static1hTime,
            "timeType": "single",
            "filters": [ drupalSettings.anv.appIdFilter ],
            "vis": [ {
              "type": "meter",
              "metrics": [ {
                "field": "product_name.keyword",
                "type": "split",
                "size": 10,
                "th": true
              } ],
              "metadata": {
                "icon": "default",
                "description": "_plan_name_",
                "info": "_billing.amount_",
                "total": "_rate_limit.*.limit_"
              }
            } ],
            "metadata": {
              "scrollable": true,
              "title": drupalSettings.analytics.translations.subscriptions,
              "height": "450px"
            }
          });
        }

        // Meters
        anv.render(drupalSettings.anv.subscriptionsWidget, document.getElementById('subscriptionsWidget'));

      } else {
        console.log("drupalSettings.analytics.proxyURL is not set");
      }
    }
  };

})(jQuery, Drupal, drupalSettings);


