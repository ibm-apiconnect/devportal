# IBM CSP Extension

This module extends the Content Security Policy (CSP) module to automatically whitelist API endpoints in your Drupal site.

## Features

- Automatically extracts API endpoints from API nodes and adds them to the CSP connect-src directive
- Integrates with the CSP module's policy system
- Automatically enabled when the CSP module is enabled

## Installation

1. Install the module as you would any Drupal module
2. The module will be automatically enabled when the CSP module is enabled
3. Use the CSP module's built-in configuration at `/admin/config/system/csp` to add any additional manual whitelisting

## How it works

The module:

1. Scans all API nodes in the system to extract endpoint URLs
2. Examines product nodes to find additional API references
3. Supporting both OpenAPI 2.0 and 3.0 formats
4. Adds all these endpoints to the CSP connect-src directive
5. Ensures that API requests won't be blocked by the browser's CSP implementation

## Why Whitelist These Endpoints?

The module extracts and whitelists several types of endpoints from your API definitions:

1. **Swagger Host Endpoints**: These are the primary endpoints where your API is hosted. Without whitelisting these, any JavaScript fetch/XHR requests to these endpoints would be blocked by the browser's CSP, preventing API calls from working in the browser.

2. **Gateway Endpoints**: IBM API Connect often uses gateway endpoints that differ from the main host. These endpoints are specified in the `x-ibm-configuration.gateway` property and are used for actual API calls. If these aren't whitelisted, API calls routed through the gateway would fail.

3. **Server Endpoints (OpenAPI 3.0)**: In OpenAPI 3.0, the `servers` array replaces the `host` property from OpenAPI 2.0. These define the base URLs where the API is available. Without whitelisting these, newer API definitions would have their endpoints blocked.

4. **Product Node Endpoints**: Products in API Connect can contain references to APIs with their own endpoints. These might differ from the endpoints defined in the API itself, especially in multi-environment setups. Whitelisting these ensures that all product-specific API endpoints work correctly.

## Configuration

No additional configuration is needed. The module automatically extracts API endpoints from your content.

For manual whitelisting of additional endpoints, use the CSP module's built-in configuration at `/admin/config/system/csp`.

## Testing

The module includes comprehensive unit tests for all components:

- `ApiEndpointServiceTest`: Tests the service that extracts API endpoints from nodes
- `CspPolicySubscriberTest`: Tests the event subscriber that adds endpoints to the CSP policy
- `ModuleTest`: Tests the module's hook implementations
