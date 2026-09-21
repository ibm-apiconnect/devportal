<?php
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
