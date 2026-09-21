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
      // Make storage key site-specific by including the base path and hostname
      const siteIdentifier = window.location.hostname + (drupalSettings.path.baseUrl || '/');
      const STORAGE_KEY = 'Drupal.darkMode.' + siteIdentifier.replace(/[^a-zA-Z0-9]/g, '_');

      function isAdminPage() {
        const path = window.location.pathname;
        return path.includes('/admin');
      }

      // API Explorer detection kept for reference; dark mode should still apply there
      function isApiExplorerPage() {
        const path = window.location.pathname;
        // API Explorer pages live under product/{id}/api/{id}
        // and usually include a hash (#/...) for the operation views.
        return path.includes('/product/') && path.includes('/api/') || window.location.hash.startsWith('#/');
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

      function syncAnalyticsTheme(theme) {
        const value = theme === 'g100' ? 'g100' : 'g10';
        // Make cookie site-specific by setting path to base URL
        const basePath = drupalSettings.path.baseUrl || '/';
        document.cookie = `analytics_theme=${value}; path=${basePath}; SameSite=Lax`;

        if (window.drupalSettings?.analytics) {
          window.drupalSettings.analytics.theme = value;
        }

        try {
          // Use site-specific key for analytics theme as well
          const analyticsKey = 'Drupal.analyticsTheme.' + siteIdentifier.replace(/[^a-zA-Z0-9]/g, '_');
          localStorage.setItem(analyticsKey, value);
        } catch (err) {}

        try {
          window.dispatchEvent(new CustomEvent('consumerAnalyticsThemeChange', { detail: { theme: value } }));
        } catch (err) {}
      }

      /**
       * Force browser to recalculate and repaint all styles
       * This triggers a reflow without reloading CSS files
       */
      function forceStyleRecalculation() {
        const scrollX = window.scrollX || window.pageXOffset;
        const scrollY = window.scrollY || window.pageYOffset;
        // Force a reflow by reading offsetHeight (triggers layout recalculation)
        document.body.offsetHeight;
        
        // Force repaint by temporarily hiding and showing the body
        const originalDisplay = document.body.style.display;
        document.body.style.display = 'none';
        
        // Trigger reflow
        document.body.offsetHeight;
        
        // Restore display
        document.body.style.display = originalDisplay;
        window.dispatchEvent(new Event('resize'));
        requestAnimationFrame(() => {
          document.querySelectorAll('*').forEach(element => {
            // Reading getComputedStyle forces style recalculation
            window.getComputedStyle(element).getPropertyValue('background-color');
          });

           if (window.scrollX !== scrollX || window.scrollY !== scrollY) {
            window.scrollTo(scrollX, scrollY);
        }
        });
      }

      /**
       * Apply dark mode state
       */
      function applyDarkMode(isDark) {
        // console.log('Applying dark mode:', isDark);
        const analyticsTheme = isDark ? 'g100' : 'g10';

        // Keep API Explorer theme in sync with the toggle
        // Explorer expects Carbon theme names: white/g10 (light) or g90/g100 (dark)
        try {
          const explorerTheme = isDark ? 'g100' : 'white';
          let themeChanged = false;

          if (window.apiConnectExplorer) {
            window.apiConnectExplorer.options = window.apiConnectExplorer.options || {};
            if (window.apiConnectExplorer.options.theme !== explorerTheme) {
              window.apiConnectExplorer.options.theme = explorerTheme;
              themeChanged = true;
            }
          }

          if (window.drupalSettings?.api?.explorer?.options) {
            if (window.drupalSettings.api.explorer.options.theme !== explorerTheme) {
              window.drupalSettings.api.explorer.options.theme = explorerTheme;
              themeChanged = true;
            }
          }

          try {
            // Use site-specific key for API Explorer theme
            const explorerKey = 'apiConnectExplorer.theme.' + siteIdentifier.replace(/[^a-zA-Z0-9]/g, '_');
            localStorage.setItem(explorerKey, explorerTheme);
          } catch (err) {}

          // Notify Explorer to re-read config when present
          if (themeChanged) {
            window.dispatchEvent(new CustomEvent('apiConnectExplorerUpdate', { detail: { theme: explorerTheme } }));
          }
        } catch (err) {
          // Swallow errors to avoid breaking dark mode toggle
        }

        // Explicitly disable portal dark mode styling on admin pages and API Explorer routes
        if (isAdminPage()) {
          body.setAttribute('data-dmt-mode', 'light');
          syncAnalyticsTheme('g10');
          return;
        }

        markAdminToolbarElements();

        if (isDark) {
          body.setAttribute('data-dmt-mode', 'dark');
          document.getElementById('dark-mode-toggle').setAttribute('aria-pressed', 'true');
        } else {
          // IMPORTANT: explicitly set light mode so FOUC CSS can release
          body.setAttribute('data-dmt-mode', 'light');
          document.getElementById('dark-mode-toggle').setAttribute('aria-pressed', 'false');
        }

        syncAnalyticsTheme(analyticsTheme);
        
        // Force browser to recalculate styles to ensure dark mode is applied immediately
        forceStyleRecalculation();
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
        // Check if toggle button exists - if not, force light mode
        const toggleButton = document.getElementById('dark-mode-toggle');
        if (toggleButton) {
          applyDarkMode(isDarkMode());
        } else {
          // No toggle button means module is disabled/uninstalled - force light mode
          applyDarkMode(false);
        }
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
