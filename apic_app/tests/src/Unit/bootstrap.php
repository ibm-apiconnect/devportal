<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2021, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
/**
 * @file
 * Bootstrap file for apic_app unit tests.
 *
 * Defines stub functions needed by form classes during unit testing.
 */

// Define stub functions in global namespace
if (!function_exists('ibm_apim_entry_trace')) {
  function ibm_apim_entry_trace($function = NULL, $args = NULL) {
    // Stub function for unit tests - does nothing
  }
}

if (!function_exists('ibm_apim_exit_trace')) {
  function ibm_apim_exit_trace($function = NULL, $result = NULL) {
    // Stub function for unit tests - does nothing
  }
}
