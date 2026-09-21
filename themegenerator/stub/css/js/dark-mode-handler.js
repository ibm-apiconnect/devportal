/**
 * Handles applying and syncing the dark mode attribute to match Drupal's Dark Mode Toggle.
 * Works with the "Dark Mode Toggle" module and custom sun/moon toggle button.
 */

// (console logs left commented out for future debugging)

// console.log('Dark mode handler JS file loaded!');

(function (Drupal) {
  'use strict';

  // console.log('Drupal object available:', typeof Drupal !== 'undefined');

  Drupal.behaviors.darkModeHandler = {
    attach: function (context, settings) {
      // console.log('Dark mode behavior attach called', context);

      const body = document.body;
      const STORAGE_KEY = 'Drupal.darkMode';

      function isAdminPage() {
        const path = window.location.pathname;
        return path.includes('/admin');
      }

      function markAdminToolbarElements() {
        const toolbarSelectors = [
          '#toolbar-administration',
          '.toolbar-bar',
          '.toolbar-tray',
          '.toolbar-menu',
          '.toolbar-icon',
          '.toolbar-tab',
          '.toolbar-item',
          '.toolbar-oriented',
          '[id^="toolbar-"]',
          '[class*="toolbar-"]',
          '.contextual',
          '.contextual-region',
          '.contextual-links'
        ];

        toolbarSelectors.forEach(selector => {
          const elements = document.querySelectorAll(selector);
          elements.forEach(element => {
            element.setAttribute('data-exclude-dark-mode', 'true');
          });
        });
      }

      /**
       * Apply dark mode state
       */
      function applyDarkMode(isDark) {
        // console.log('Applying dark mode:', isDark);

        if (isAdminPage()) {
          // console.log('Admin page detected - dark mode disabled');
          body.removeAttribute('data-dmt-mode');
          return;
        }

        markAdminToolbarElements();

        if (isDark) {
          body.setAttribute('data-dmt-mode', 'dark');
        } else {
          // IMPORTANT: explicitly set light mode so FOUC CSS can release
          body.setAttribute('data-dmt-mode', 'light');
        }
      }

      /**
       * Get current dark mode state
       */
      function isDarkMode() {
        const attrValue = body.getAttribute('data-dmt-mode');
        const stored = localStorage.getItem(STORAGE_KEY);

        // If body already has an explicit mode, trust it
        if (attrValue === 'dark') {
          return true;
        }
        if (attrValue === 'light') {
          return false;
        }

        // Check localStorage for explicit user preference
        if (stored === 'dark') {
          return true;
        }

        // Default to light mode (for 'light', null, or any other value)
        return false;
      }

      /**
       * Toggle dark mode
       */
      function toggleDarkMode() {
        const currentlyDark = isDarkMode();
        const newState = !currentlyDark;

        // console.log('Toggling dark mode from', currentlyDark, 'to', newState);

        localStorage.setItem(STORAGE_KEY, newState ? 'dark' : 'light');
        applyDarkMode(newState);
      }

      // Initialize: Apply initial dark mode state on first load
      if (context === document) {
        // console.log('Initializing dark mode on document load');
        applyDarkMode(isDarkMode());
      }

      // Set up toggle button click handler
      const toggleButton = document.getElementById('dark-mode-toggle');
      // console.log('Looking for toggle button, found:', toggleButton);

      if (toggleButton && !toggleButton.hasAttribute('data-dark-mode-processed')) {
        toggleButton.setAttribute('data-dark-mode-processed', 'true');

        toggleButton.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          // console.log('Toggle button clicked!');
          toggleDarkMode();
        });

        // console.log('Dark mode toggle button initialized successfully');
      }

      // Listen for storage changes (if user toggles in another tab)
      if (context === document) {
        window.addEventListener('storage', (e) => {
          if (e.key === STORAGE_KEY) {
            // console.log('Storage changed in another tab:', e.newValue);
            applyDarkMode(e.newValue === 'dark');
          }
        });
      }
    }
  };

})(Drupal);
