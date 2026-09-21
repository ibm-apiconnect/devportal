(function ($, Drupal, once) {
  ("use strict");
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

  Drupal.behaviors.copyCodeButton = {
    attach: function (context, settings) {
      once(
        "copy-code-button",
        ".command-block pre, .example-block pre",
        context
      ).forEach(function (preElement) {
        const wrapper = document.createElement("div");
        wrapper.className = "code-container";

        const copyButton = document.createElement("button");
        copyButton.className = "copy-btn";
        copyButton.textContent = "copy";

        const codeElement = preElement.querySelector("code");

        if (!codeElement) {
          return;
        }

        preElement.parentNode.insertBefore(wrapper, preElement);
        wrapper.appendChild(copyButton);
        wrapper.appendChild(preElement);

        copyButton.addEventListener("click", async function () {
          const text = codeElement.textContent.trim();

          try {
            await navigator.clipboard.writeText(text);
            copyButton.textContent = "copied!";
            copyButton.classList.add("copied");
            setTimeout(function () {
              copyButton.textContent = "copy";
              copyButton.classList.remove("copied");
            }, 2000);
          } catch (err) {
            console.error("copy failed:", err);
          }
        });
      });
    },
  };
})(jQuery, Drupal, once);
