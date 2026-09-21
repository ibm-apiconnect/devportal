/**
 * @file
 * Clear Getting Started localStorage on login page.
 */

(function () {
  'use strict';

  // Clear Getting Started localStorage when login page loads
  // This ensures tutorial state is reset after logout
  if (typeof localStorage !== 'undefined') {
    localStorage.removeItem('gettingStartedPanelOpen');
    localStorage.removeItem('gettingStartedCurrentTutorial');
    localStorage.removeItem('gettingStartedCurrentStep');
  }
})();
