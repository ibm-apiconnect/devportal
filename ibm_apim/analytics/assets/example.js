/*********************************************************
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 **********************************************************/
document.addEventListener("DOMContentLoaded", function() {

    let anv = new window.AnalyticsNativeVis({
        settings: {
            url: '/kbn/analytics/api/apiconnect/anv',
            refreshRate: '1m'
        },
        user: {
            context: {

            },
            config: {

            }
        }
    });

    let apiStatsTimeId = anv.createTime({
        "field": "datetime",
        "values": ["30s", "1m", "30m", "1h", "1d", "7d", "30d"],
        "selectedValue": "1d"
    });

    let static30dTime = anv.createTime({
      "field": "datetime",
      "values": ["30d"],
      "selectedValue": "30d"
    });

    let static1hTime = anv.createTime({
      "field": "datetime",
      "values": ["1h"],
      "selectedValue": "1h"
    });

    let apiStatsWidget = anv.createWidget({
      "id": "api-stats",
      "time": apiStatsTimeId,
      "timeType": "split",
      "vis": [{
        "metrics": [{
          "field": "time_to_serve_request",
          "type": "average"
        },{
          "field": "time_to_serve_request",
          "type": "maximum"
        },{
          "field": "time_to_serve_request",
          "type": "minimum"
        }],
        "type": "line",
        "metadata": {
          "label": "Response Time"
        }
      }],
      "metadata": {
        "title": "API Stats",
        "dateSubtitle": true
      }
    });

    let totalCallsWidget = anv.createWidget({
      "id": "total-calls",
      "time": static30dTime,
      "timeType": "single",
      "vis": [{
        "type": "num",
        "metrics": [{
          "type": "total"
        }],
        "metadata": {
          "title": "Total Calls",
          "units": "calls",
        }
      }]
    });

    let errorFilter = anv.createFilter({
      "field": "status_code",
      "values": ["4*||5*"],
      "selectedValue": "4*||5*"
    });

    let totalErrorsWidget = anv.createWidget({
      "id": "total-errors",
      "time": static30dTime,
      "filters": [ errorFilter ],
      "timeType": "single",
      "vis": [{
        "type": "num",
        "metrics": [{
          "type": "total"
        }],
        "metadata": {
          "title": "Total Errors",
          "units": "errors",
          "unitType": "error"
        }
      }]
    });

    let averageResponseTimeWidget = anv.createWidget({
      "id": "avg-response",
      "time": static30dTime,
      "timeType": "single",
      "vis": [{
        "type": "num",
        "metrics": [{
          "field": "time_to_serve_request",
          "type": "average"
        }],
        "metadata": {
          "title": "Average Response Time",
          "units": "ms"
         }
      }]
    });

    let apiCallsWidget = anv.createWidget({
      "id": "api-calls",
      "time": static30dTime,
      "timeType": "single",
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
        "title": "API Calls (Last 100)",
        "height": "450px"
      }
    });

    let apiCallsErrorsWidget = anv.createWidget({
      "id": "api-calls-errors",
      "time": static30dTime,
      "timeType": "single",
      "filters": [ errorFilter ],
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
        "title": "Errors (Last 100)",
        "height": "450px"
      }
    })

    /*let subscriptionsWidget = anv.createWidget({
      "id": "subscriptions",
      "time": static1hTime,
      "timeType": "single",
      "vis": [{
        "type": "meter",
        "metrics": [{
          "field": "product_name.keyword",
          "type": "split",
          "size": 10,
          "th": true
        }],
        "metadata": {
          "icon": "default",
          "description": "_plan_name_",
          "info": "_billing.amount_",
          "total": "_rate_limit.*.limit_"
        }
      }],
      "metadata": {
        "scrollable": true,
        "title": "Subscriptions",
        "height": "450px"
      }
    });*/

    // Single-stat widgets
    anv.render(totalCallsWidget, document.getElementById('totalcalls'));
    anv.render(totalErrorsWidget, document.getElementById('totalerrors'));
    anv.render(averageResponseTimeWidget, document.getElementById('averageresponsetime'));

    // Bar graph
    anv.render(apiStatsWidget, '#apistats');

    // Meters
    //anv.render(subscriptionsWidget, '#subscriptions');
    anv.render(apiCallsWidget, '#apicalls');
    anv.render(apiCallsErrorsWidget, '#apicallserrors');

});
