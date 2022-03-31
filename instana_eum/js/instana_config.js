(function(s,t,a,n){s[t]||(s[t]=a,n=s[a]=function(){n.q.push(arguments)},
n.q=[],n.v=2,n.l=1*new Date)})(window,"InstanaEumObject","ineum");

(function ($, Drupal, drupalSettings) {
    var apiKey = drupalSettings.instana_eum.api_key;
    var reportingUrl = drupalSettings.instana_eum.reporting_url;
    var trackPages = drupalSettings.instana_eum.track_pages;
    var trackAdmin = drupalSettings.instana_eum.track_admin;
    // var advancedSettings = drupalSettings.instana_eum.advanced_settings;
    ineum('reportingUrl', reportingUrl);
    ineum('key', apiKey);
    ineum('trackSessions');
    if(!trackAdmin) {
      ineum('ignoreUrls', [/.*\/admin\/.*/]);
    };
    if(trackPages){
      ineum('page', location.pathname);
    };
  })(jQuery, Drupal, drupalSettings);