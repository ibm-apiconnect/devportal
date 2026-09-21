(function ($, Drupal, once) {
  ("use strict");

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
