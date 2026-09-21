(function (Drupal) {

  Drupal.behaviors.sessionCheck = {
    attach: function (context, settings) {
      if (!context.__sessionCheckAttached) {
        context.__sessionCheckAttached = true;

        if (!drupalSettings?.ibm_apic_session_check?.isLoggedIn) {
          return;
        }

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
                
                // Redirect to the same page, which will trigger the login redirect with preserved params
                window.location.href = window.location.pathname + window.location.search;
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
