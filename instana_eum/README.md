# Instana EUM Module
A module to integrate Instana with Drupal so we can easily collect logs and metrics.

## Installation

### Uploading a .zip file
---
Create a zip of the main directory of this module and upload it to the drupal site. Once the module has been uploaded it will appear on the list under the "Monitoring" tab.
> For more information about installing Drupal modules see: https://www.drupal.org/docs/extending-drupal/installing-modules

## Setup

### Basic Module Configuration
---
After installing the module on your drupal website:
- Along the top toolbar, navigate to "Configuration".
- On the Configuration page, navigate to the "System" panel.
- Click on "Instana EUM Configuration".
- Before the next step we will need to know the Reporting URL and API Key of your Instana server. If you do not know how to find these values, see [Finding Instana connection parameters](#-finding-instana-connection-parameters).
- Fill out at least the two required fields with your Instana connection parameters and click "Save Configuration":
  - API Key
  - Reporting URL
- Once saved, data will be sent back to Instana so navigate around your site a little to create some events then check back in your Instana dashboard to verify that data is being sent correctly. 
- It may take a couple of seconds for all of your events to come through in Instana so don't panic if numbers aren't what you expect immediately. 
- Once you have verified the connection, it may be worth exploring the [other configuration parameters](#-extra-configuration-options) to get the most out of the Instana EUM Module for your use case!


### Finding Instana connection parameters
---
With access to an instana server:

- In the Instana dashboard, navigate to "Websites and Mobile Applications" on the left hand side.
- Find your application and click on the application name. If you do not already have an Application in Instana then create one.
- Navigate to the configuration tab.
- In the section "Tracking Script" you will see a code block. Inside the block there will be two lines for setting up the `reportingUrl` and `key` that look like this:
  ```
  ineum('reportingUrl', 'YOUR_INSTANA_URL');
  ineum('key', 'YOUR_INSTANA_KEY');
  ```

## Extra Configuration options
  - Track individual pages
    - Allows instana to track about pages individually.
  - Admin page tracking
    - When enabled tracks traffic in Drupal admin pages, or any url containing '/admin'.
  - Advanced settings
    - Currently not in use. Plan is to allow users to specify their own configurations the using ineum() API.
  
## How it works
Under the hood, this module injects the Instana EUM Web Agent JS into every page and allows the user to configure it nicely with a configuration form.

