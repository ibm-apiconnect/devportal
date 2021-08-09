Description
===========

In general, this module provides features that allow an IP address (or range of
IP addresses) to be whitelisted which restricts the functionality of users.
Currently there are two features implemented.

IP address ranges must be entered in CIDR notation separated with semi-colons
and no trailing semi-colon (e.g., 10.20.30.0/24;192.168.199.1/32;1.0.0.0/8). For
more information on CIDR notation, see
http://www.brassy.net/2007/mar/cidr_basic_subnetting.

Restrict login by IP
--------------------
When a user is restricted, that user will not be able to log in outside the
defined IP address ranges. It is also possible to specify global IP address
ranges, which apply to ALL users, including user1. Following a denied log-in
attempt, a user is redirected to an error page as specified by the site
administrator.

Restrict role by IP
-------------------
When a role is restricted, that role will not be available to users outside the
defined IP address ranges. Role restriction does not affect users' ability to
log in, only the availability of the restricted role to users. Role restrictions
are available for all roles, except "anonymous user" and "authenticated user."

### Install

1. Follow the instructions at
   https://drupal.org/documentation/install/modules-themes/modules-7.

### Upgrade

1. Follow the instructions at https://drupal.org/node/250790.

### Usage

1. All configuration can be managed from administration pages located at
   Administration > Configuration > People > Restrict by IP.
2. Individual user IP restrictions may also be set in the 'Restrict by IP'
   fieldset located on the user add/edit form.
3. IP restrictions are checked on every page load. If a user restriction is
   triggered, then the user will be logged out and sent to the specified
   'error page'. If a role restriction is triggered, then a user's session will
   be unaffected; however, the restricted role will no longer be available to
   the user.
4. To remove an IP restriction, simply delete its value, and submit the
   configuration form.

### Authors

Please see project page for current list of maintainers.

* Matt Rice (mrice [at] mattrice.org)
* Bobby Kramer (panthar1 [at] gmail.com)
* James Gross (jamesrgross [at] gmail.com)
