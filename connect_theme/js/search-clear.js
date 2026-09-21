/**
 * @file
 * Search input clear button functionality.
 *
 * Implements Carbon Design System search clear button behavior.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.searchClear = {
    attach: function (context, settings) {
      // Find all text inputs in views exposed forms and search inputs
      const searchInputs = context.querySelectorAll('.bx--search-input, .bx--text-input, .views-exposed-form input[type="text"]');

      searchInputs.forEach(function (input) {
        // Only process each input once
        if (input.dataset.searchClearProcessed) {
          return;
        }
        input.dataset.searchClearProcessed = 'true';

        // Check if input is in an input-group
        let inputGroup = input.closest('.input-group');
        const formItem = input.closest('.bx--form-item, .form-item');
        let closeButton;
        
        // Create input-group wrapper if it doesn't exist
        if (!inputGroup && formItem) {
          const parentElement = input.parentNode;
          if (!parentElement.classList.contains('input-group')) {
            const newInputGroup = document.createElement('div');
            newInputGroup.className = 'input-group';
            parentElement.insertBefore(newInputGroup, input);
            newInputGroup.appendChild(input);
            inputGroup = newInputGroup;
          }
        }
        
        // Check if close button already exists
        if (inputGroup) {
          closeButton = inputGroup.querySelector('.bx--search-close');
        } else if (formItem) {
          closeButton = formItem.querySelector('.bx--search-close');
        } else {
          closeButton = input.nextElementSibling;
          if (closeButton && !closeButton.classList.contains('bx--search-close')) {
            closeButton = null;
          }
        }
        
        if (!closeButton) {
          // Create the close button
          closeButton = document.createElement('button');
          closeButton.className = 'bx--search-close';
          closeButton.type = 'button';
          closeButton.setAttribute('aria-label', Drupal.t('Clear input'));
          closeButton.setAttribute('title', Drupal.t('Clear input'));
          closeButton.innerHTML = '<svg focusable="false" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="16" height="16" viewBox="0 0 32 32" aria-hidden="true"><path d="M24 9.4L22.6 8 16 14.6 9.4 8 8 9.4 14.6 16 8 22.6 9.4 24 16 17.4 22.6 24 24 22.6 17.4 16 24 9.4z"></path></svg>';
          
          if (inputGroup) {
            // For input-group, insert button after the input but before the addon
            const addon = inputGroup.querySelector('.input-group-addon');
            if (addon) {
              inputGroup.insertBefore(closeButton, addon);
            } else {
              inputGroup.appendChild(closeButton);
            }
          } else {
            // For other cases, insert after input
            input.parentNode.insertBefore(closeButton, input.nextSibling);
          }
        }

        // Function to clear the input
        function clearInput() {
          input.value = '';
          input.focus();
          
          // Trigger input event to notify other scripts
          const event = new Event('input', { bubbles: true });
          input.dispatchEvent(event);
          
          // Also trigger change event for form handling
          const changeEvent = new Event('change', { bubbles: true });
          input.dispatchEvent(changeEvent);
        }

        // Handle close button click
        closeButton.addEventListener('click', clearInput);

        // Handle keyboard interaction on close button
        closeButton.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            clearInput();
          }
        });
      });
    }
  };

})(Drupal);