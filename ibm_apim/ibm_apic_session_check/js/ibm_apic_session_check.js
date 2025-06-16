(function (Drupal) {
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

  Drupal.behaviors.sessionCheck = {
    attach: function (context, settings) {
      if (!context.__sessionCheckAttached) {
        context.__sessionCheckAttached = true;

        const basePath = settings.ibm_apic_session_check?.basePath || "/";
        const endpoint = basePath + "session-check";

        let wasLoggedIn = localStorage.getItem("wasLoggedIn") !== "false";

        window.addEventListener("storage", function (event) {
          if (event.key === "userLoggedOut" && event.newValue === "true") {
            window.location.href = window.location.pathname;
          }
        });

        function checkSession() {
          fetch(endpoint)
            .then((response) => response.json())
            .then((data) => {
              if (!data.logged_in && wasLoggedIn) {
                wasLoggedIn = false;
                localStorage.setItem("wasLoggedIn", "false");
                localStorage.setItem("userLoggedOut", "true");
                window.location.href = window.location.pathname;
              } else if (data.logged_in && !wasLoggedIn) {
                wasLoggedIn = true;
                localStorage.setItem("wasLoggedIn", "true");
              }
            })
            .catch((err) => console.error("Session check failed", err));
        }

        setInterval(checkSession, 20000);
      }
    },
  };
})(Drupal);
