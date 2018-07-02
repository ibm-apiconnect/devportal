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

  Drupal.behaviors.appAnalyticsConfig = {
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

        if (!drupalSettings.anv.appIdFilter) {
          drupalSettings.anv.appIdFilter = anv.createFilter({
            "field": "app_id",
            "values": [drupalSettings.application.id],
            "selectedValue": drupalSettings.application.id
          });
        }

        if (!drupalSettings.anv.apiStatsTimeId) {
          drupalSettings.anv.apiStatsTimeId = anv.createTime({
            "field": "datetime",
            "values": ["30s", "1m", "30m", "1h", "1d", "7d", "30d"],
            "selectedValue": "1d"
          });
        }

        if (!drupalSettings.anv.static30dTime) {
          drupalSettings.anv.static30dTime = anv.createTime({
            "field": "datetime",
            "values": ["30d"],
            "selectedValue": "30d"
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
            "filters": [drupalSettings.anv.appIdFilter],
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

        if (!drupalSettings.anv.totalCallsWidget) {
          drupalSettings.anv.totalCallsWidget = anv.createWidget({
            "id": "total-calls",
            "time": drupalSettings.anv.static30dTime,
            "timeType": "single",
            "filters": [drupalSettings.anv.appIdFilter],
            "vis": [{
              "type": "num",
              "metrics": [{
                "type": "total"
              }],
              "metadata": {
                "title": drupalSettings.analytics.translations.total_calls,
                "units": "calls"
              }
            }]
          });
        }

        if (!drupalSettings.anv.errorFilter) {
          drupalSettings.anv.errorFilter = anv.createFilter({
            "field": "status_code",
            "values": ["4*||5*"],
            "selectedValue": "4*||5*"
          });
        }

        if (!drupalSettings.anv.totalErrorsWidget) {
          drupalSettings.anv.totalErrorsWidget = anv.createWidget({
            "id": "total-errors",
            "time": drupalSettings.anv.static30dTime,
            "filters": [drupalSettings.anv.errorFilter, drupalSettings.anv.appIdFilter],
            "timeType": "single",
            "vis": [{
              "type": "num",
              "metrics": [{
                "type": "total"
              }],
              "metadata": {
                "title": drupalSettings.analytics.translations.total_errors,
                "units": "errors",
                "unitType": "error"
              }
            }]
          });
        }

        if (!drupalSettings.anv.averageResponseTimeWidget) {
          drupalSettings.anv.averageResponseTimeWidget = anv.createWidget({
            "id": "avg-response",
            "time": drupalSettings.anv.static30dTime,
            "timeType": "single",
            "filters": [drupalSettings.anv.appIdFilter],
            "vis": [{
              "type": "num",
              "metrics": [{
                "field": "time_to_serve_request",
                "type": "average"
              }],
              "metadata": {
                "title": drupalSettings.analytics.translations.average_response_time,
                "units": "ms"
              }
            }]
          });
        }

        if (!drupalSettings.anv.apiCallsWidget) {
          drupalSettings.anv.apiCallsWidget = anv.createWidget({
            "id": "api-calls",
            "time": drupalSettings.anv.static30dTime,
            "timeType": "single",
            "filters": [drupalSettings.anv.appIdFilter],
            "vis": [{
              "type": "info",
              "metrics": [{
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

        if (!drupalSettings.anv.apiCallsErrorsWidget) {
          drupalSettings.anv.apiCallsErrorsWidget = anv.createWidget({
            "id": "api-calls-errors",
            "time": drupalSettings.anv.static30dTime,
            "timeType": "single",
            "filters": [drupalSettings.anv.errorFilter, drupalSettings.anv.appIdFilter],
            "vis": [{
              "type": "info",
              "metrics": [{
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
              "title": drupalSettings.analytics.translations.errors_last_100,
              "height": "100%"
            }
          });
        }

        // Single-stat widgets
        anv.render(drupalSettings.anv.totalCallsWidget, document.getElementById('totalcalls'));
        anv.render(drupalSettings.anv.totalErrorsWidget, document.getElementById('totalerrors'));
        anv.render(drupalSettings.anv.averageResponseTimeWidget, document.getElementById('averageresponsetime'));

        // Bar graph
        anv.render(drupalSettings.anv.apiStatsWidget, document.getElementById('appAnalyticsSummary'));

        // Meters
        anv.render(drupalSettings.anv.apiCallsErrorsWidget, document.getElementById('subscriptionsWidget'));
        anv.render(drupalSettings.anv.apiCallsWidget, document.getElementById('apicallhistory'));
      } else {
        console.log("drupalSettings.analytics.proxyURL is not set");
      }
    }
  };

})(jQuery, Drupal, drupalSettings);


