# IBM API Connect Session Check

This sub-module is used to check if a user session is still active.

If the user is logged out in another tab or window, the current page will refresh automatically to reflect the logout.

### How it works

A small JavaScript runs in the background and checks /session-check every 20 seconds.

If the user is logged out, it refreshes the page.
