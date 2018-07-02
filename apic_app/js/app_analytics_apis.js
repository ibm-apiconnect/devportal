/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Setup global config for analytics
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.appApiAnalyticsConfig = {
    attach: function (context) {
      if (drupalSettings.analytics && drupalSettings.analytics.analyticsDir) {
        window.apiConnectAnalytics = [];
        window.apiConnectAnalytics.options = [];
        window.apiConnectAnalytics.options.analyticsDir = drupalSettings.analytics.analyticsDir;
      }

      if (drupalSettings.analytics && drupalSettings.analytics.proxyURL) {
        let anv = new window.AnalyticsNativeVis({
          settings: {
            url: drupalSettings.analytics.proxyURL,
            refreshRate: '1m'
          },
          user: {
            context: {

            },
            config: {

            }
          }
        });
        if (!drupalSettings.anv) {
          drupalSettings.anv = [];
        }

        if (!drupalSettings.anv.apiStatsTimeId) {
          drupalSettings.anv.apiStatsTimeId = anv.createTime({
            "field": "datetime",
            "values": ["30s", "1m", "30m", "1h", "1d", "7d", "30d"],
            "selectedValue": "1d"
          });
        }

        if (!drupalSettings.anv.static1hTime) {
          drupalSettings.anv.static1hTime = anv.createTime({
            "field": "datetime",
            "values": ["1h"],
            "selectedValue": "1h"
          });
        }

        if (!drupalSettings.anv.apiStatsWidget) {
          drupalSettings.anv.apiStatsWidget = anv.createWidget({
            "id": "api-stats",
            "time": drupalSettings.anv.apiStatsTimeId,
            "timeType": "split",
            "vis": [{
              "metrics": [{
                "field": "time_to_serve_request",
                "type": "average"
              }, {
                "field": "time_to_serve_request",
                "type": "maximum"
              }, {
                "field": "time_to_serve_request",
                "type": "minimum"
              }],
              "type": "line",
              "metadata": {
                "label": drupalSettings.analytics.translations.response_time
              }
            }],
            "metadata": {
              "title": drupalSettings.analytics.translations.api_stats,
              "dateSubtitle": true
            }
          });
        }

        if (!drupalSettings.anv.appIdFilter) {
          drupalSettings.anv.apiCallsWidget = anv.createWidget({
            "id": "api-calls",
            "time": drupalSettings.anv.static1hTime,
            "timeType": "single",
            "vis": [{
              "type": "info",
              "metrics": [{
                "field": "",
                "type": "raw",
                "size": 100
              }],
              "metadata": {
                "path": "_uri_path_",
                "date": "_datetime_",
                "responseCode": "_status_code_",
                "title": "_request_method_",
                "averageTime": "_time_to_serve_request_"
              }
            }],
            "metadata": {
              "scrollable": true,
              "title": drupalSettings.analytics.translations.calls_last_100,
              "height": "100%"
            }
          });
        }

        // Bar graph
        anv.render(drupalSettings.anv.apiStatsWidget, document.getElementById('apistats'));

        // Meters
        anv.render(drupalSettings.anv.apiCallsWidget, document.getElementById('apicalls'));
      } else {
        console.log("drupalSettings.analytics.proxyURL is not set");
      }
    }
  };

})(jQuery, Drupal, drupalSettings);


