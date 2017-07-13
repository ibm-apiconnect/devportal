<h1>API Connect Developer Portal</h1>

<p>This repository contains the components necessary to create a developer portal for IBM API Connect 
using the Drupal content management system.</p>

<p><code>ibm_apim</code> = a drupal module containing the IBM API Connect integration<br/>
<code>connect_theme</code> = a configurable drupal theme<br/>
<code>ibm_apim_theme</code> = the drupal theme used in APIm v4<br/>
<code>apic_taxonomy_menu_block</code> = a block to browse a hierarchical taxonomy<br/>
<code>bannerblock</code> = front page welcome banner block<br/>
<code>clone</code> = fork of the node_clone module to fix some incompatibilities in it_<br/>
<code>forum_approval</code> = module to allow the approval of forum posts<br/>
<code>popularapi</code> = block to feature specific products or APIs (for example on the front page)<br/>
<code>socialblock</code> = block to display recent Twitter tweets and forum posts<br/>
<code>apim_profile</code> = a drupal installation profile to create a dev portal zip containing everything you need.</p>

<h2>Video / Demo</h2>
<p><a href="https://www.youtube.com/watch?v=rWM8wH_odGg">You can see a video of this portal in action on Youtube.</a> (Video now slightly out of date, we'll update it soon, honest!)</p>

<h2>Download</h2>
<p>The latest release of the code, in the form of a fully functional drupal distribution (containing the drupal core, all required modules, 
javascript libraries and theme) can be downloaded from the <a href="https://github.com/ibm-apiconnect/devportal/releases/latest">Latest Release</a> page.</p>

<h2>Defects / Feature Requests / Issues</h2>
<p>Please raise any defects, feature requests or general issues here on github using the Issues link to the right.</p>

<h2>Features of the Drupal Developer Portal</h2>

<h3>Full Content Management System</h3>
<ul><li>Multiple content types</li>
<li>Configurable customizable content types - add custom field, change the way they're displayed etc...</li>
<li>Easily create new content in the UI</li>
<li>User friendly (bookmarkable) URLs</li></ul>

<h3>Hook addons into different content types</h3>
<ul><li>enabled or disabled as a default per content type, and then toggled per individual content item</li>
<li>comments</li>
<li>ratings (with 6 different icon types)</li>
<li>Share on social media</li>
<li>Export to PDF / printable</li>
<li>Tagging</li></ul>

<h3>Configurable Role based access control</h3>
<ul><li>Create new roles e.g. content author or forum moderator</li>
<li>Define permissions per role</li></ul>

<h3>Forums</h3>
<ul><li>Option to automatically create a new forum for each API</li>
<li>Moderation</li>
<li>Captcha support (pluggable: images, maths, recaptcha, etc....)</li>
<li>WYSIWYG rich text editor</li></ul>

<h3>Blog</h3>
<ul><li>Multiple users can have their own blogs</li>
<li>WYSIWYG editor</li>
<li>RSS Feed</li>
<li>Integrates with comments, ratings, etc....</li></ul>

<h3>FAQ</h3>
<ul><li>Easily add new FAQ questions</li>
<li>optional addon to allow users to pose questions and have them answered by an admin / moderator and then published</li></ul>

<h3>Contact Form</h3>
<ul><li>Allow registered users / anonymous users to email enquiries to the site admin</li></ul>

<h3>Customizable Responsive Theme</h3>
<ul><li>Responsive theme with configurable layout options per form factor so can choose how the layout works on tablets vs mobiles vs desktops for example</li>
<li>Includes 5 different colour schemes</li>
<li>Create your own custom colour scheme, all colours in the UI can be changed</li>
<li>Change site logo</li>
<li>Change site favicon</li>
<li>Change site name</li>
<li>Change site slogan</li>
<li>Toggle whether any of the above are displayed or not</li></ul>

<h3>Configurable Password Policy</h3>
<ul><li>default is the same as APIm</li>
<li>must use 3 out of 4 character types, min password length of 8</li></ul>

<h3>Page not found (404) error handler</h3>
<ul><li>rather than report errors it automatically searches for what you were looking for</li></ul>

<h3>Support Ticket Management</h3>
<ul><li>Configurable helpline type support ticketing system</li>
<li>Can be expanded to have catagorization of tickets, dashboard, allow raising by email, etc..</li></ul>

<h3>Customizable page layouts</h3>
<ul><li>different pages can have different layouts</li>
<li>different numbers of columns, etc..</li>
<li>include different blocks such as a twitter feed or facebook feed, recent forum posts, comments, etc..</li></ul>

<h3>IBM API Connect Integration</h3>
<ul><li>Self-signup from the drupal portal</li>
<li>Users already known to APIm can simply login, no need to create another account (one is created automatically)</li>
<li>List APIs available to currently logged in user</li>
<li>APIs then stored in drupal database so the data is available for search (drupaldb content automatically updated every time it is accessed)</li>
<li>Can only access items in the drupal DB if can access the equivalent in APIm</li>
<li>Integrated SwaggerUI interface for browsing API resources and live testing those resources</li>
<li>Register, Edit & Delete applications</li>
<li>Upload & Remove images for those applications</li>
<li>Browse available plans per API</li>
<li>Browse documents attached to an API</li>
<li>Subscribe to a plan</li>
<li>Unsubscribe from a plan</li>
<li>See what plans an app is subscribed to</li>
<li>Configure notifications per application</li>
<li>See APIm notification messages</li></ul>

<h3>Security</h3>
<ul><li>Password policy</li>
<li>IP Address blocking</li>
<li>Brute force attack / flood protection</li>
<li>Configurable Captchas</li>
<li>Restricted html users can use in comments / content</li>
<li>Configurable auto logout</li></ul>

<h2>License</h2>
<p>This code is made available under the GNU Public License v2.</p>
<p>This is provided 'as-is' with no guarantee of official support but we will endeavour to respond to all Issues raised as best we can.</p>

<h2>Requirements</h2>
<p>You will require IBM API Connect v5.0.7.x to use this code.</p>

<h2>API Management v4</h2>
<p>The v4 version of the portal can be found on github here: <a href="https://github.com/apimanagement/drupalportal/">apimanagement/drupalportal</a></p>
