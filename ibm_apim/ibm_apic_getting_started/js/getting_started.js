(function (Drupal, drupalSettings, once, $) {
  /********************************************************* {COPYRIGHT-TOP} ***
   * Licensed Materials - Property of IBM
   * 5725-L30, 5725-Z22
   *
   * (C) Copyright IBM Corporation 2025
   *
   * All Rights Reserved.
   * US Government Users Restricted Rights - Use, duplication or disclosure
   * restricted by GSA ADP Schedule Contract with IBM Corp.
   ********************************************************** {COPYRIGHT-END} **/

  "use strict";

  Drupal.behaviors.gettingStartedPanel = {
    attach: function (context, settings) {
      /**
       * Open a tutorial panel — tries the generic route first, then falls back to legacy.
       */
      function openTutorial(tutorialId) {
        // store the current tutorial ID and reset the step when changing tutorials
        const previousTutorial = localStorage.getItem(
          "gettingStartedCurrentTutorial"
        );
        if (previousTutorial !== tutorialId) {
          localStorage.removeItem("gettingStartedCurrentStep");
        }

        localStorage.setItem("gettingStartedPanelOpen", "true");
        localStorage.setItem("gettingStartedCurrentTutorial", tutorialId);

        const url = Drupal.url(`admin/getting-started/${tutorialId}`);

        const ajaxSettings = {
          dialogType: "dialog",
          dialogRenderer: "off_canvas",
          url: url
        };

        try {
          const ajax = Drupal.ajax(ajaxSettings);

          // Suppress any AJAX errors to prevent console noise
          ajax.error = function () {
            return false;
          };

          ajax.execute();
        } catch (err) {
          console.warn("AJAX execution failed:", err);
        }
      }



      // Handle tutorial link clicks (dynamic tutorialId)
      $(once("gs-tutorial-links", ".tutorial-link", context)).on("click", function (e) {
        e.preventDefault();
        const tutorialId = $(this).data("tutorial-id");
        if (tutorialId) {
          openTutorial(tutorialId);
        }
      });

      // launch button (open default or last tutorial)
      $(once("gs-launch", "#ibm-apic-gs-launch", context)).on(
        "click",
        function () {
          localStorage.setItem("gettingStartedPanelOpen", "true");

          // get the tutorial ID from the clicked element's data attribute
          const clickedTutorialId = $(this).data("tutorial-id");

          if (clickedTutorialId) {
            localStorage.setItem(
              "gettingStartedCurrentTutorial",
              clickedTutorialId
            );
          } else if (!localStorage.getItem("gettingStartedCurrentTutorial")) {
            // Use server-provided last tutorial ID if available, otherwise use default
            const serverLastTutorialId = drupalSettings.ibmApicGettingStarted?.lastTutorialId || "customize-portal-theme";
            localStorage.setItem(
              "gettingStartedCurrentTutorial",
              serverLastTutorialId
            );
          }
        }
      );

      // open last tutorial
      $(once("gs-auto-open", "body", context)).each(function () {
        if (
          localStorage.getItem("gettingStartedPanelOpen") === "true" &&
          $("#drupal-off-canvas").length === 0
        ) {
         // Priority order for tutorial ID:
        // 1. Page-specific tutorial ID
        // 2. localStorage saved tutorial ID
        // 3. Server-provided last tutorial ID (from drupalSettings)
        // 4. Default tutorial ID
        
        const pageSpecificTutorial = $("body").data("tutorial-id");
        const localStorageTutorial = localStorage.getItem("gettingStartedCurrentTutorial");
        const serverLastTutorialId = drupalSettings.ibmApicGettingStarted?.lastTutorialId;
        const defaultTutorialId = "customize-portal-theme";
        
        // Determine which tutorial to open
        let tutorialToOpen;
        
        if (pageSpecificTutorial) {
          // If we're on a specific tutorial page, use that
          tutorialToOpen = pageSpecificTutorial;
          // Reset step counter if switching tutorials
          if (localStorageTutorial && localStorageTutorial !== pageSpecificTutorial) {
            localStorage.removeItem("gettingStartedCurrentStep");
          }
        } else if (localStorageTutorial) {
          // Use localStorage if available
          tutorialToOpen = localStorageTutorial;
        } else if (serverLastTutorialId) {
          // Use server-provided last tutorial ID
          tutorialToOpen = serverLastTutorialId;
        } else {
          // Fall back to default
          tutorialToOpen = defaultTutorialId;
        }
        
        // Store the selected tutorial ID
        localStorage.setItem("gettingStartedCurrentTutorial", tutorialToOpen);
        
        // Open the tutorial
        openTutorial(tutorialToOpen);
        }
      });
      
      // observe off-canvas open/close for any tutorial
      $(once("gs-observer", "body", context)).each(function () {
        const observer = new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            // off-canvas opened
            if (mutation.addedNodes.length > 0) {
              for (let i = 0; i < mutation.addedNodes.length; i++) {
                const node = mutation.addedNodes[i];
                if (node.id === "drupal-off-canvas-wrapper") {
                  const dialogWrapper = $(node);
                  if (!dialogWrapper.length) continue;
                  /**
                   * Dialog Scroll Management
                   * 1. Setting up a MutationObserver to watch for content changes in the dialog.
                   * 2. Automatically scrolling to the top when content is initially loaded.
                   * 3. Using debouncing to delay scroll operations until after DOM rendering
                   */
                  let debounceScrollTimer = null;
                  const debounce = (func, wait) => {
                    return function () {
                      clearTimeout(debounceScrollTimer);
                      debounceScrollTimer = setTimeout(func, wait);
                    }
                  };
                  const contentObserver = new MutationObserver(debounce(() => dialogWrapper.scrollTop(0), 0));
                  const dialogContent = dialogWrapper.find("#drupal-off-canvas");
                  if (dialogContent.length) {
                    contentObserver.observe(dialogContent[0], { childList: true });
                  }
                }
              }
            }
            // off-canvas closed
            if (mutation.removedNodes.length > 0) {
              for (let i = 0; i < mutation.removedNodes.length; i++) {
                const node = mutation.removedNodes[i];
                if (node.id === "drupal-off-canvas-wrapper") {
                  localStorage.setItem("gettingStartedPanelOpen", "false");
                  localStorage.removeItem("gettingStartedCurrentStep");
                }
              }
            }
          });
        });
        observer.observe(document.body, { childList: true });
      });

      // handle next/prev buttons in tutorials
      $(once("gs-navigation", ".gs-actions", context)).each(function () {
        // We'll handle scrolling in the MutationObserver to avoid race conditions
        const $next = $("#gs-next", this);
        const $prev = $("#gs-prev", this);
        const $steps = $(".gs-step");
        if (!$steps.length) return;

        let currentStep = parseInt(localStorage.getItem("gettingStartedCurrentStep") || "0", 10);
        if (currentStep >= $steps.length) currentStep = 0;

        $steps.hide();
        $($steps[currentStep]).show();
        $prev.prop("disabled", currentStep === 0);
        $next.prop("disabled", currentStep === $steps.length - 1);

        const updateProgress = () => {
          $("#gs-progress").text(`Step ${currentStep + 1} of ${$steps.length}`);
          $("#last-step").text(`Step ${currentStep + 1}`);
          localStorage.setItem("gettingStartedCurrentStep", currentStep);
        };

        updateProgress();

        $next.on("click", function () {
          if (currentStep < $steps.length - 1) {
            $($steps[currentStep]).hide();
            currentStep++;
            $($steps[currentStep]).show();
            $prev.prop("disabled", false);
            if (currentStep === $steps.length - 1) {
              $next.prop("disabled", true);
            }
            updateProgress();
          }
        });

        $prev.on("click", function () {
          if (currentStep > 0) {
            $($steps[currentStep]).hide();
            currentStep--;
            $($steps[currentStep]).show();
            $next.prop("disabled", false);
            if (currentStep === 0) {
              $prev.prop("disabled", true);
            }
            updateProgress();
          }
        });
      });
    },
  };
})(Drupal, drupalSettings, once, jQuery);
