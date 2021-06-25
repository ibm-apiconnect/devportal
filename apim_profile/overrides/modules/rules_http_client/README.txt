This module adds an action wrapping of drupal_http_request to use Rules for
making http requests. It has many general uses, because the Hypertext Transfer
Protocol is the foundation of web data communication.

For Drupal in particular, it is a great complement to the RestWS module. Simply,
set the Accept Header parameter to "application/xml" and the remote Drupal
instance will serve an XML file which can be parsed using Rules XPath Parser.

Rules HTTP Client was originally created by Mitchell Tannenbaum (mitchell) and
is currently co-maintained by Stuart Clark (Deciphered) and Benjamin Melançon
(mlncn).



Required modules
--------------------------------------------------------------------------------

* Rules - http://drupal.org/project/rules
