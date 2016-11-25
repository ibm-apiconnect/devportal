<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

function apim_profile_book_content() {
  $content = array(
    'basic.html' => t('<p>You can configure multiple aspects of the Developer Portal as an administrator.</p>
<p>To learn how to configure specific aspects of the Developer Portal, use the following pages:</p>'),
    'changing_the_site_name.html' => t('<p class="shortdesc">You can change the site name in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the site name, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">System</span> heading, click <span class="ph uicontrol">Site
information.</span>.</span></li>
<li class="li step"><span class="ph cmd">Enter the new site name in the <span class="ph uicontrol">Site name</span> field.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> You have changed the name for the site.</li>
</ol>
'),
    'chnage_site_slogan.html' => t('<p class="shortdesc">You can change the site slogan in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the site slogan, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">System</span> heading, click <span class="ph uicontrol">Site
information.</span>.</span></li>
<li class="li step"><span class="ph cmd">Enter a new slogan in the <span class="ph uicontrol">Slogan</span> field.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> You have changed the slogan for the site.</li>
</ol>
'),
    'change_site_email.html' => t('<p class="shortdesc">You can change the site email address in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the site email address, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">System</span> heading, click <span class="ph uicontrol">Site
information.</span>.</span></li>
<li class="li step"><span class="ph cmd">Enter the new email address in the <span class="ph uicontrol">E-mail address</span> field.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> You have changed the email address for the site.</li>
</ol>
'),
    'change_logo.html' => t('<p class="shortdesc">You can change the site logo in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the site logo, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Appearance</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Settings</span> for the <span class="ph uicontrol">IBM API Connect</span>
theme.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Logo Image Settings</span> to expand the view.</span></li>
<li class="li step"><span class="ph cmd">Deselect the <span class="ph uicontrol">Use the default logo</span> check box.</span></li>
<li class="li step"><span class="ph cmd">Provide a path to an image on the server by entering it in the <span class="ph uicontrol">Path to custom
logo</span> field. Alternatively, you can browse for, and upload, an image under the
<span class="ph uicontrol">Upload logo image</span> subheading.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> You have chosen a logo for the site.</li>
</ol>
'),
    'change_shortcut_icon.html' => t('<p class="shortdesc">You can change the shortcut icon in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the shortcut icon, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Appearance</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Settings</span> for the <span class="ph uicontrol">IBM API Connect</span>
theme.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Shortcut Icon Settings</span> to expand the view.</span></li>
<li class="li step"><span class="ph cmd">Deselect the <span class="ph uicontrol">Use the default shortcut icon</span> check box.</span></li>
<li class="li step"><span class="ph cmd">Provide a path to an image on the server by entering it in the <span class="ph uicontrol">Path to custom
icon</span> field. Alternatively, you can browse for, and upload, an image under the
<span class="ph uicontrol">Upload icon image</span> subheading.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> You have changed the shortcut icon for the site.</li>
</ol>
'),
    'banner_image_change.html' => t('<p class="shortdesc">You can change the banner image that is displayed on the Home page when a user first logs
in to the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the front page banner image, complete the following
steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span>, then select the <span class="ph uicontrol">BLOCKS</span> tab.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">edit</span> in the <span class="ph uicontrol">Welcome Banner</span> block.</span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">Content</span> heading, to change the Content text and image for the
Welcome banner, you can either enter text into the content editor, or click the <span class="ph uicontrol">Edit HTML
Source</span> icon to edit or paste HTML directly that defines image and text specifications.
<div class="image"><img class="image" src="tutorial_portal_edit_HTML_source.jpg" alt="The content editor dashboard with the Edit HTML Source icon highlighted."></div></span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">Image</span> heading, browse for the required image, and click
<span class="ph uicontrol">Update</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save block</span> to save your changes.</span></li>
</ol>
'),
    'twitter_block.html' => t('<p class="shortdesc">You can retrieve and integrate data from a Twitter account of interest and display it
into a social block in a <span class="ph">Developer Portal</span> site.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>

<p class="p">You must have a Twitter App for the Twitter account of interest. The Twitter App contains
information that is used to configure the social block. For information on creating Twitter Apps,
see <a class="xref" href="https://apps.twitter.com/" target="_blank" title="(Opens in a new tab or window)">https://apps.twitter.com/</a>.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step"><span class="ph cmd">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol"> System</span> &gt; <span class="ph uicontrol"> Social Block Twitter OAuth</span></span>.</span></li>
<li class="li step"><span class="ph cmd">Using the information from your Twitter App, enter the <span class="ph uicontrol">Consumer key</span>,
<span class="ph uicontrol">Consumer secret</span>, <span class="ph uicontrol">Access token</span>, and <span class="ph uicontrol">Token
secret</span> in the corresponding fields.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> Twitter data from a Twitter account of interest has been integrated with the <span class="ph">Developer Portal</span>, and is displayed
on the social block of the site. </li>
</ol>
'),
    'edit_twitter_social_block.html' => t('<p class="shortdesc">After you have integrated Twitter data into the <span class="ph">Developer Portal</span> social block, you
can edit the content, appearance, and functionality of the social block.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>

<p class="p">You must have Twitter data integrated into the <span class="ph">Developer Portal</span> social block. For
more information, see <a class="xref" href="tapic_portal_twitter_block.html">Integrating Twitter data into the <span class="ph">Developer Portal</span> social
block</a>.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">On your social block, click the <span class="ph uicontrol">Settings</span> icon <img class="image" src="CMC_Settings_selected.jpg" alt="The Settings icon">, then click <span class="ph uicontrol">Edit Block</span>.</span> The <span class="keyword wintitle">Edit Social Block</span> window is displayed, with the <span class="ph uicontrol">Edit
Block</span> tab open.</li>
<li class="li step stepexpand"><span class="ph cmd">Configure your social block by configuring any of the following options:</span> <dl class="dl"><dt class="dt dlterm">Number of tiles to display</dt>
<dd class="dd">Configure the number of tiles that you want to display on the social block by entering a number
in the <span class="ph uicontrol">Number of tiles to display</span> field. The default number of tiles that are
displayed on the social block is 8, and there is a limit of 100 tiles.  </dd>
<dt class="dt dlterm">Display topics from all forums</dt>
<dd class="dd">If you want to display the topics from all forums, ensure that the <span class="ph uicontrol">Display topics
from all forums</span> check box is selected. If you want to specify which forum topics are
displayed, clear the check box and select the appropriate forums.</dd>
<dt class="dt dlterm">Get tweets from</dt>
<dd class="dd">From the <span class="ph uicontrol">Get tweets from</span> drop-down list, select whether you want to
display Twitter data from users or a search term. If you select <span class="ph uicontrol">User</span>, enter
the account name for the user, in the adjacent field. If you select <span class="ph uicontrol">Search
term</span>, enter the specific term that will return the corresponding Twitter data, in the
adjacent field. </dd>
<dt class="dt dlterm">Twitter cache duration</dt>
<dd class="dd">Enter the length of time, in minutes, that you want the Twitter data to remain in the cache of
the <span class="ph">Developer Portal</span>. The
default length of time is 60 minutes.</dd>
<dt class="dt dlterm">Types of tweets to display</dt>
<dd class="dd">Select the types of tweets that you want to display from the drop-down list. You can select to
display tweets, or tweets and replies.</dd>
</dl>
</li>
<li class="li step stepexpand"><span class="ph cmd">After you have configured the social block, click <span class="ph uicontrol">Save</span>.</span> Your social block is configured, and the configurations are implemented when you return
to the home page.</li>
</ol>
'),
    'popular_product.html' => t('<p class="shortdesc">You can display any popular Products on the front page of your <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>

<p class="p">You must have a product that is published to the <span class="ph">Developer Portal</span>.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">To create the Popular Product block in the <span class="ph">Developer Portal</span>:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph menucascade"><span class="ph uicontrol">Content</span> &gt; <span class="ph uicontrol"> Add Block</span> &gt; <span class="ph uicontrol"> Popular Product</span></span>.</span></li>
<li class="li substep"><span class="ph cmd">Insert a name in the <span class="ph uicontrol">Label</span> field to display on admin interface.</span></li>
<li class="li substep">Optional: <span class="ph cmd">Enter a title for your block in the <span class="ph uicontrol">Displayed Title</span> field.</span></li>
<li class="li substep"><span class="ph cmd">From the <span class="ph uicontrol">Product</span> drop-down list, select the product that you want to
display in the block.</span></li>
<li class="li substep"><span class="ph cmd">Click, <span class="ph uicontrol">Save</span>.</span> You have created a popular Product block.</li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">To display the popular Product block in the featured API pane of the <span class="ph">Developer Portal</span>:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span>, then click <span class="ph uicontrol">Pages</span>.</span></li>
<li class="li substep"><span class="ph cmd">Alongside the <span class="ph uicontrol">page-welcome</span> entry, click
<span class="ph uicontrol">Edit</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Layout</span>, select the layout for your front page from the
<span class="ph uicontrol">Category</span> drop down list.</span></li>
<li class="li substep"><span class="ph cmd">Select the radio button that corresponds to the panel distribution that you require, then click
<span class="ph uicontrol">Change</span>, then <span class="ph uicontrol"> Save</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Content</span>, then expand <span class="ph uicontrol">Featured APIs</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the list on the left of the window.</span></li>
<li class="li substep"><span class="ph cmd">Click the <span class="ph uicontrol">Settings</span> icon <img class="image" src="CMC_Settings_selected.png" alt="Settings icon"> for the featured API pane you want to add your popular Product block to, then click
<span class="ph uicontrol">Add content</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Blocks: Popular Product</span> and select your popular Product.</span></li>
<li class="li substep">Optional: <span class="ph cmd">If you want to override the original title with a custom title, select the <span class="ph uicontrol">Override
title</span> check box.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Finish</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Update</span>, then <span class="ph uicontrol">Save</span> to save your
changes.</span> Your popular Product block is displayed on the front page of your <span class="ph">Developer Portal</span>.</li>
</ol>
</li>
</ol>
'),
    'follow_social_media_links.html' => t('<p class="shortdesc">You can link to social media sites from the <span class="ph">Developer Portal</span>. You can configure
where and how these links are displayed and which sites you want to link to.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Blocks</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Under the <span class="ph uicontrol">Disabled</span> heading, locate the <span class="ph uicontrol">Follow
site</span> block.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Select the location for your social media block from the drop-down list for <span class="ph uicontrol">Follow
Site</span> in the <span class="ph uicontrol">REGION</span> column.</span> The Follow Site block will appear under the heading that corresponds to the region you
selected in the REGION drop-down list.</li>
<li class="li step stepexpand">Required: <span class="ph cmd">Click <span class="ph uicontrol">Save blocks</span> before you begin configuring your new block.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">configure</span> for the <span class="ph uicontrol">Follow Site</span> block.</span></li>
<li class="li step stepexpand"><span class="ph cmd">In the <span class="ph uicontrol">Block title</span> field, type the name of your block.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Under the <span class="ph uicontrol">Default block title</span> heading, select one of the following
leading text options for your block using the radio buttons for each option: </span> <ul class="ul"><li class="li"><span class="q">&quot;Follow <var class="keyword varname">your.portal.site</var> on&quot;</span></li>
<li class="li"><span class="q">&quot;Follow me on&quot;</span></li>
<li class="li"><span class="q">&quot;Follow us on&quot;</span></li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">Use the <span class="ph uicontrol">User pages</span> check box to decide whether you want your block to
display on your user&apos;s profile pages.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Use the <span class="ph uicontrol">Alignment</span> and <span class="ph uicontrol">Icon Style</span> drop-down lists
to adjust the appearance of your block.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save block</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Under the <span class="ph uicontrol">WEB SERVICES</span> heading, click <span class="ph uicontrol">Follow</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Type the URL under the <span class="ph uicontrol">URL</span> heading for each of the social media sites
you want your users to access. The following is an example of a link to the @ibmapiconnect twitter
handle: </span> <pre class="pre codeblock"><code>https://twitter.com/ibmapiconnect</code></pre>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Submit</span>.</span></li>
</ol>
'),
    'forum_moderation.html' => t('<p>One of the roles provided by default is the \'Forum Moderator\'. Users in this role can manage and maintain your community support on the site forums.</p>
<p>The pages in this section explain some of the actions that Forum Moderators can perform.</p>'),
    'create_new_forum.html' => t('<p class="shortdesc">You can create a new forum in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or Forum moderator access
to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To create a new forum, complete the following tasks:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Forums</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">+ Add forum</span>.</span></li>
<li class="li step"><span class="ph cmd">Fill in the <span class="ph uicontrol">Forum name</span> and <span class="ph uicontrol">Description</span> fields for
the new forum.</span></li>
<li class="li step"><span class="ph cmd">From the <span class="ph uicontrol">Parent</span> drop-down list, select the hierarchical position of the
new forum.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>. You have created a new forum.</span></li>
</ol>
'),
    'creating_new_forum_containers.html' => t('<p class="shortdesc">You can create new forum containers in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or Forum moderator access
to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To create a new forum containers, complete the following tasks:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Forums</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">+ Add container</span>.</span></li>
<li class="li step"><span class="ph cmd">Fill in the <span class="ph uicontrol">Container name</span> and <span class="ph uicontrol">Description</span> fields
for the new forum container.</span></li>
<li class="li step"><span class="ph cmd">From the <span class="ph uicontrol">Parent</span> drop-down list, select the hierarchical position of the
new forum container.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>. You have created a new forum container.</span></li>
</ol>
'),
    'configure_creation_new_forums_for_each_API.html' => t('<p class="shortdesc">You can choose whether or not to automatically create a new forum when an API is created
in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To configure the creation of new forums for each API, complete the following
tasks:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">SYSTEM</span> heading, click <span class="ph uicontrol">IBM API Connect</span>.</span></li>
<li class="li step"><span class="ph cmd">Select or deselect the <span class="q">&quot;<span class="ph uicontrol">Automatically create a forum per API</span>&quot;</span> check
box to turn the feature on or off.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>. </span> You have configured the creation of new forums for each API.</li>
</ol>
'),
    'mark_content_as_sticky_in_forum.html' => t('<p class="shortdesc">You can mark content in a forum as sticky in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or Forum moderator access
to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To mark content in a forum as sticky, complete the following tasks:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Select the check boxes next to the forum topics for which you want to make the content
sticky.</span></li>
<li class="li step"><span class="ph cmd">From the <span class="ph uicontrol">UPDATE OPTIONS</span> drop-down menu, select <span class="ph uicontrol">Make selected
content sticky</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Update</span>.</span> You have marked the selected forum content as sticky.</li>
</ol>
'),
    'locking_forum_topics.html' => t('<p class="shortdesc">You can lock forum topics in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or Forum moderator access
to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To lock forum topics, complete the following tasks:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">edit</span> for the forum topic that you want to lock.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Comments settings</span>. The view expands.</span></li>
<li class="li step"><span class="ph cmd">Click the <span class="ph uicontrol">Closed </span>radio button.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span> You have disallowed any further replies and marked the topic as locked.</li>
</ol>
'),
    'removing_forum_post.html' => t('<p class="shortdesc">You can remove forum posts in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or Forum moderator access
to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To remove forum posts, complete the following tasks:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">delete</span> for the forum topic that you want to remove.</span> You have removed the selected forum post.</li>
</ol>
'),
    'make_forum_private.html' => t('<p class="shortdesc">You can make preexisting forums private in the <span class="ph">Developer Portal</span>. You can also
configure other forum access features to customize forum privacy for different user roles. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To select the forum for which you want to control the access, complete the following
steps:</p><ol><li class="li step stepexpand"><span class="ph cmd">Log in to the <span class="ph">Developer Portal</span> as an
administrator.</span></li>
<li class="li step stepexpand"><span class="ph cmd">From the administrator dashboard, select <span class="ph uicontrol">Structure</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Forums</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Select <span class="ph uicontrol">Edit forum</span> for the forum you want to edit.</span> The Edit forum page displays.</li>
</ol><p class="li stepsection">To edit the access to your selected forum, complete the following steps:</p><ol start="5"><li class="li step stepexpand"><span class="ph cmd">Ensure that the <span class="ph uicontrol">ACCESS CONTROL</span> section is expanded, then navigate to the
<span class="ph uicontrol">ROLES</span> table.</span> In this table, you can see the user roles available in the <span class="ph">Developer Portal</span>.</li>
<li class="li step stepexpand"><span class="ph cmd">From this table, you can use the check boxes in the row corresponding to each user role to
assign the following permissions to each user:</span> <ul class="ul"><li class="li"><span class="ph uicontrol">VIEW THIS FORUM</span></li>
<li class="li"><span class="ph uicontrol">POST IN THIS FORUM</span></li>
<li class="li"><span class="ph uicontrol">EDIT POSTS</span></li>
<li class="li"><span class="ph uicontrol">DELETE POSTS</span></li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span></li>
</ol>'),
    'content_author.html' => t('<p>\'Content Authors\' can control and configure the content in the Developer Portal. Their actions include writing documentation and blog posts, but also ensuring that APIs and Products look their best and can be easily found by setting custom icons and taxonomy tags.</p>'),
    'adding_new_pages.html' => t('<p class="shortdesc">You can add basic pages for static content in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To add new pages, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">+ Add content</span>. </span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Basic page</span>.</span></li>
<li class="li step"><span class="ph cmd">Enter a title in the <span class="ph uicontrol">Title</span> field. Provide the main body content in the
<span class="ph uicontrol">Body</span> field.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span> You have added a new basic page.</li>
</ol>
'),
    'edit_sample_content.html' => t('<p class="shortdesc">You can edit sample content, such as basic pages, in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To edit sample content, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">edit</span> next to the content element you want to edit.</span></li>
<li class="li step"><span class="ph cmd">From this view, you can edit the sample content of the selected element as required.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>. </span> You have edited the sample content for the selected element.</li>
</ol>
'),
    'edit_site_comment.html' => t('<p class="shortdesc">You can edit site comments in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To edit a site comment, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">edit</span> under the comment to be edited. </span></li>
<li class="li step"><span class="ph cmd">Edit the content of the comment as required.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span> You have edited the selected comment.</li>
</ol>
'),
    'edit_tags_specific_item.html' => t('<p class="shortdesc">You can edit tags for a specific item in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To edit tags for a specific item, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">edit</span> next to the content element you want to edit.</span></li>
<li class="li step"><span class="ph cmd">From the <span class="ph uicontrol">Tags</span> drop-down menu, select <span class="ph uicontrol">&lt;create new
item&gt;</span>.</span> The <span class="ph uicontrol">Tags</span> window displays.</li>
<li class="li step"><span class="ph cmd">Enter a name for the new tag in the <span class="ph uicontrol">new</span> field. Click
<span class="ph uicontrol">Create</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Add</span>, then click <span class="ph uicontrol">Save</span>.</span> You have created a new tag for an individual item.</li>
</ol>
'),
    'post_new_blog_entries.html' => t('<p class="shortdesc">You can post new blog entries in the <span class="ph">Developer Portal</span>, and configure
them to suit your requirements. You can also turn blogs off.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To post a new blog entry, complete the following steps:</p><ul><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">+ Add content</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Blog post</span>.</span></li>
<li class="li step"><span class="ph cmd">Fill in the <span class="ph uicontrol">Title</span> and <span class="ph uicontrol">Main body</span> fields.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>. You have posted a new blog</span></li>
</ul><p class="li stepsection">To configure a blog, complete the following steps:</p><ul><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">edit</span> next to the blog that you want to configure.</span></li>
<li class="li step"><span class="ph cmd">You can configure the selected blog from this view.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>. You have configured the selected blog.</span></li>
</ul>'),
    'add_FAQs.html' => t('<p class="shortdesc">You can add new FAQs to the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To add new FAQs, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">+ Add content</span>. </span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">FAQ</span>.</span></li>
<li class="li step"><span class="ph cmd">Enter the question in the <span class="ph uicontrol">Question</span> field. Provide further details of the
question in the <span class="ph uicontrol">Detailed question</span> field.</span></li>
<li class="li step"><span class="ph cmd">Enter the answer in the <span class="ph uicontrol">Answer</span> field.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span> You have added a new FAQ and related answer.</li>
</ol>
'),
    'post_poll.html' => t('<p class="shortdesc">You can post a poll in the <span class="ph">Developer Portal</span>. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To post a poll, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">+ Add content</span>. </span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Poll</span>.</span></li>
<li class="li step"><span class="ph cmd">Enter the poll question in the <span class="ph uicontrol">Poll question</span> field.</span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">Choice</span> heading, add answers to the available fields as
choices.</span></li>
<li class="li step"><span class="ph cmd">From the <span class="ph uicontrol">Poll duration</span> drop-down menu, select a time limit for the
poll.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span> You have posted a poll.</li>
</ol>
<div class="section postreq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">What to do next</h2></div>See <a class="xref" href="tapim_portal_edit_sample_content.html">Editing sample
content</a> to learn how to add a links to your poll to sample content. </div>
'),
    'attach_files_to_api.html' => t('<p class="shortdesc">You can attach a file to APIs in the <span class="ph">Developer Portal</span>, and have them
displayed as documentation attachments. You can attach multiple files to APIs.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><div class="p">You can only attach a single file at a time. The default file upload size limit applies to the
files that you want to attach, and you can attach up to 10 files to an API by default. <div class="note note"><span class="notetitle">Note:</span> You can
change the default limit for the number of files that you want to attach. </div>
</div>

<p class="p">An administrator can configure the types of file that are uploaded, and the number of files that
a user can upload. </p>

<div class="p">The following file types can be uploaded: <ul class="ul"><li class="li"><span class="ph filepath">txt doc pdf</span></li>
<li class="li"><span class="ph filepath">xls ppt pptx</span></li>
<li class="li"><span class="ph filepath">docx xslx rtf</span></li>
<li class="li"><span class="ph filepath">odt ods odp md json</span></li>
<li class="li"><span class="ph filepath">yaml tgz tar zip</span></li>
</ul>
</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step"><span class="ph cmd">In the <span class="ph">Developer Portal</span>,
click the <span class="ph uicontrol">API Products</span> tab.</span></li>
<li class="li step"><span class="ph cmd">Click the API that you want to attach a file to.</span></li>
<li class="li step"><span class="ph cmd">Click the <span class="ph uicontrol">Edit</span> tab.</span></li>
<li class="li step"><span class="ph cmd">In the Documentation section, click <span class="ph uicontrol">Browse</span> and identify the file that
you want to upload, then click <span class="ph uicontrol">Open</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Upload</span>, then click <span class="ph uicontrol">Save</span>.</span></li>
<li class="li step">Optional: <span class="ph cmd">Clear the <span class="ph uicontrol">Display</span> check box for the file if you want to upload and
attach the file to the API, but not show it in the <span class="ph">Developer Portal</span>.</span></li>
<li class="li step">Optional: <span class="ph cmd">Enter a description for your file in the <span class="ph uicontrol">Description</span> field. This
description can be used as the label of the link to the file.</span></li>
<li class="li step">Optional: <span class="ph cmd">If you have uploaded multiple files, use the drag handle next to the file name and rearrange
them.</span></li>
</ol>
<div class="section result"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Results</h2></div>You have successfully attached a documentation file to your API. </div>
'),
    'embed_multimedia_in_site_content.html' => t('<p class="shortdesc">You can embed multimedia elements in site content in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To embed multimedia in site content, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard. </span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">TITLE</span> heading, click the title of the required content.</span></li>
<li class="li step"><span class="ph cmd">Select the <span class="ph uicontrol">Edit</span> tab.</span></li>
<li class="li step"><span class="ph cmd">Use the <span class="ph uicontrol">Insert/Edit Embedded Media</span> icon <img class="image" src="icon_embed_multimedia.jpg" alt="The insert/edit embedded media icon"> in the in the WYSIWYG editor to embed multimedia in the content.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span> You have embedded multimedia in the selected site content.</li>
</ol>
'),
    'cloning_static_doc.html' => t('<p class="shortdesc">You can clone single document pages and recreate them in the <span class="ph">Developer Portal</span>. This allows you
to clone and modify documents for large amounts of APIs from one document template. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Content</span> in the administrator dashboard.</span> A list of all the system content is displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">Under the <span class="ph uicontrol">OPERATIONS</span> column, click <span class="ph uicontrol">clone</span> for the
static node you want to clone. </span> A new page window opens with pre-filled content from the node you selected to clone.
From this view you can edit the following:<ul class="ul"><li class="li">The page title</li>
<li class="li">The summary</li>
<li class="li">The body </li>
<li class="li">The format</li>
<li class="li">The menu options to which the new page is assigned</li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span> You are redirected to the content view, where your new page appears as a content
node.</li>
</ol>
'),
    'configuring_layout.html' => t('<p>Everyone wants their site to look different. This might be to fit in with your organization\'s corporate branding, or just to look great.</p>
<p>You can customize the entire look and feel of the Developer Portal, and in the majority of cases, this can be easily done through the administrative web interface.</p>
<p>The site theme controls the colours of the site, but the layout of elements on the page and the pages themselves are configured separately. The pages below explain how.</p>'),
    'frontpage_configure.html' => t('<p class="shortdesc">You can configure the <span class="ph">Developer Portal</span> front
page.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To configure the front page, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span>, then click <span class="ph uicontrol">Pages</span>.</span></li>
<li class="li step"><span class="ph cmd">Alongside the <span class="ph uicontrol">page-welcome</span> entry, click <span class="ph uicontrol">Edit</span>.</span></li>
<li class="li step"><span class="ph cmd">Use the options provided to configure the front page.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Update and save</span> to save your changes.</span></li>
</ol>
<div class="section postreq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">What to do next</h2></div>You can create a custom page and specify role-based front pages. For more information, see
<a class="xref" href="tapim_portal_custom_pages.html">Creating custom pages</a>
and <a class="xref" href="tapim_portal_role_based_homepage.html">Configurable
role-based front pages</a>.</div>
'),
    'main_menu_change.html' => t('<p class="shortdesc">You can change the items in the main menu that is displayed on all pages in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div> <p class="p">The
following screen capture shows an example of a main menu:<div class="image"><img class="image" src="main_menu.png" alt="Main menu screen capture"></div>However, you can add new menu items, remove
items, and change the item order.</p>
 </div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the items in the main menu, complete the following
steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span>, then click <span class="ph uicontrol">Menus</span>.</span></li>
<li class="li step"><span class="ph cmd">Alongside the <span class="ph uicontrol">Main menu</span> entry, click <span class="ph uicontrol">list
links</span>.</span></li>
<li class="li step"><span class="ph cmd">Use the options provided to change the menu items.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span> to save
your changes.</span></li>
</ol>
'),
    'menu_add.html' => t('<p class="shortdesc">You can add a new menu to the <span class="ph">Developer Portal</span>, and define the
items that are included in the menu.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To add a menu, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span>, then click <span class="ph uicontrol">Menus</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Add menu</span>, provide a title, and an optional description, and click <span class="ph uicontrol">Save</span>.</span></li>
<li class="li step"><span class="ph cmd">To add items to your menu, click <span class="ph uicontrol">Add link</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span> to save your changes.</span></li>
</ol>
<div class="section postreq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">What to do next</h2></div><p class="p">A new block for your menu is created automatically. You can use block configuration options to
control which pages your menu is displayed on, and where it is positioned. For more information, see
<a class="xref" href="tapim_portal_block_change.html">Changing the blocks displayed
on <span class="ph">Developer Portal</span>
pages</a>.</p>
</div>
'),
    'block_change.html' => t('<p class="shortdesc">You can change the blocks that are displayed on <span class="ph">Developer Portal</span> pages.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div>Blocks in the <span class="ph">Developer Portal</span> are visible to all
users, regardless of whether blocks have access restrictions placed on them. The visibility of
blocks to all users also extends to the popular Products block. </div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the blocks that are displayed on a page, complete the following steps:</p><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span>, then click <span class="ph uicontrol">Blocks</span>.</span> All of the blocks in the system are displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">You can complete the following tasks:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">To create a custom block, Click <span class="ph uicontrol">Add block</span>.</span></li>
<li class="li substep"><span class="ph cmd">To change the region in which a block is positioned, select the region in the drop down list alongside the required block.</span></li>
<li class="li substep"><span class="ph cmd">To change the block title, the region settings, and the visibility settings, click <span class="ph uicontrol">configure</span> alongside the required block.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save blocks</span> to save your changes.</span></li>
</ol>
'),
    'custom_pages.html' => t('<p class="shortdesc">You can create custom pages within the <span class="ph">Developer Portal</span>. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator or content author access to
complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">In the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Structure</span> &gt; <span class="ph uicontrol"> Pages</span></span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">+ Add custom page</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Define the Basic settings: </span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Enter a name for the page in the <span class="ph uicontrol">Administrative title</span> field.</span> The page is named, and a configurable machine name is created. </li>
<li class="li substep"><span class="ph cmd">Provide a description for the page in the <span class="ph uicontrol">Administrative description</span>
box.</span></li>
<li class="li substep"><span class="ph cmd">Enter the URL path that is required to get to the page in the <span class="ph uicontrol">Path</span>
field.</span></li>
<li class="li substep">Optional: <span class="ph cmd">Select the <span class="ph uicontrol">Make this your site home page</span> check box if you want the panel
to be your homepage.</span></li>
<li class="li substep">Optional: <span class="ph cmd">Select the <span class="ph uicontrol">Use this page in an admin overlay</span> check box if you want to
use the page in an admin overlay.</span></li>
<li class="li substep"><span class="ph cmd">Select the variant type from the <span class="ph uicontrol">Variant type</span> list.</span></li>
<li class="li substep">Optional: <span class="ph cmd">Select any of the <span class="ph uicontrol">Optional features</span> check boxes that satisfy your
requirements.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Continue</span>. </span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Select the layout of page from the <span class="ph uicontrol">Category</span> list, and then select the
check box associated with the block formation that satisfies your layout specifications.</span> For example, if you want to have your page split into two columns with two blocks above and
below, you can select the <span class="ph uicontrol">Columns: 2</span> category, and the <span class="ph uicontrol">Two column
stacked</span> check box.</li>
<li class="li step stepexpand">Optional: <span class="ph cmd">Define the Panel settings:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Provide a title for the variant in the <span class="ph uicontrol">Administrative title</span> field.</span></li>
<li class="li substep"><span class="ph cmd">Select the <span class="ph uicontrol">Disable Drupal blocks/regions</span> check box if you want to
disable all of the regions displayed in the theme.</span></li>
<li class="li substep"><span class="ph cmd">Define the CSS classes to remove from the body element of the page in the <span class="ph uicontrol">Remove
body CSS classes</span> field.</span></li>
<li class="li substep"><span class="ph cmd">Define the CSS classes to add to the body element of the page in the <span class="ph uicontrol">Add body CSS
classes</span> field.</span></li>
<li class="li substep"><span class="ph cmd">Enter a CSS ID for the page in the <span class="ph uicontrol">CSS ID</span> field.</span></li>
<li class="li substep"><span class="ph cmd">Enter any additional custom CSS code to embed into the page in the <span class="ph uicontrol">CSS
code</span> field.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Continue</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Define the Panel content: </span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Select the <span class="ph uicontrol">Title type</span> from the list.</span></li>
<li class="li substep"><span class="ph cmd">Specify a title for the panel in the <span class="ph uicontrol">Title</span> field.</span></li>
<li class="li substep"><span class="ph cmd">Specify the content of the blocks by clicking the <span class="ph uicontrol">Settings</span> icon <img class="image" src="Icon_settings.jpg" alt="The Settings icon"> for the appropriate block.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Finish</span>, then <span class="ph uicontrol">Save</span>.</span> You have created a custom page within the <span class="ph">Developer Portal</span>.</li>
</ol>
<div class="section postreq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">What to do next</h2></div>You can associate this page for a specific role. For more information, see <a class="xref" href="tapim_portal_role_based_homepage.html">Configurable role-based
front pages</a>. <p class="p">You can clone a default, created, or custom page in the <span class="ph">Developer Portal</span>. For more
information, see <a class="xref" href="tapic_portal_cloning_pages.html">Cloning
pages</a>.</p>
</div>
'),
    'adding_context.html' => t('<p class="shortdesc">You can add context to sections of the <span class="ph">Developer Portal</span> to manage
contextual conditions and reactions. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">When you add and configure a context, you can choose the conditions that trigger the context to
activate and produce a specified reaction. Contexts can be grouped together with tags.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">In the administrator dashboard, click <span class="ph uicontrol">Modules</span>. </span></li>
<li class="li step stepexpand"><span class="ph cmd">In the <span class="ph uicontrol">Filter list</span> field, enter <span class="ph uicontrol">Context</span>. </span></li>
<li class="li step stepexpand"><span class="ph cmd">Set the Context and Context UI modules to the <span class="ph uicontrol">ON</span> position, then click
<span class="ph uicontrol">Save configuration</span>.</span> <div class="note note"><span class="notetitle">Note:</span> To improve performance, turn the Context UI module to the <span class="ph uicontrol">OFF</span> position
after you have completed the configuration of your contexts.</div>
</li>
<li class="li step stepexpand"><span class="ph cmd">In administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Structure</span> &gt; <span class="ph uicontrol"> Context</span></span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">+ Add</span> in the <span class="keyword wintitle">Context</span> window.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Enter a unique ID for your context in the <span class="ph uicontrol">Name</span> field.</span></li>
<li class="li step stepexpand">Optional: <span class="ph cmd">If you want to create a group for your contexts, or already have a group created for your
contexts, enter the name of the group in the <span class="ph uicontrol">Tag</span> field.</span></li>
<li class="li step stepexpand">Optional: <span class="ph cmd">Enter a description of the context definition in the <span class="ph uicontrol">Description</span>
field.</span></li>
<li class="li step stepexpand"><span class="ph cmd">From the <span class="ph uicontrol">Conditions</span> list, select a condition that will trigger the
activation of the context.</span> After a condition is selected, a description of the condition appears with the configurable
options. <div class="note note"><span class="notetitle">Note:</span> You can add multiple conditions for a context.</div>
</li>
<li class="li step stepexpand"><span class="ph cmd">Configure the selected condition.</span></li>
<li class="li step stepexpand"><span class="ph cmd">From the <span class="ph uicontrol">Reactions</span> list, select a reaction that is produced after the
context is activated. </span> After a reaction is selected, a description of the reaction appears with the configurable
options. <div class="note note"><span class="notetitle">Note:</span> You can add multiple reactions for a context.</div>
</li>
<li class="li step stepexpand"><span class="ph cmd">Configure the selected reaction.</span></li>
<li class="li step stepexpand">Optional: <span class="ph cmd">Select the <span class="ph uicontrol">Require all conditions</span> check box if you want the context to
active only after all of the conditions are met.</span> <div class="note note"><span class="notetitle">Note:</span> If you do not select the <span class="ph uicontrol">Require all conditions</span> check box, the context
is activated after any specified condition within the context is met.</div>
</li>
<li class="li step stepexpand"><span class="ph cmd">After you have specified your conditions and reactions, click
<span class="ph uicontrol">Save</span>.</span> You have specified and configured the conditions and reactions of a context.</li>
</ol>
'),
    'role_based_homepage.html' => t('<p class="shortdesc">In the <span class="ph">Developer Portal</span>, you can configure
the front page according to the role of the user. Unauthenticated users can see a different front
page to authenticated users and users with different roles can see different front pages according
to their role. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div>If you want to have different front pages for different users, you can create the page and
then apply the custom URL alias that is provided for that page, to the appropriate role&apos;s front page
settings. For more information on creating the page, see <a class="xref" href="tapim_portal_custom_pages.html">Creating custom pages</a>.</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Log in to the <span class="ph">Developer Portal</span> as an
administrator.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Under the <span class="ph uicontrol">FRONT PAGE</span> heading, click
<span class="ph uicontrol">Settings</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Under the <span class="ph uicontrol">ROLES</span> heading, configure the front pages for the following
roles by expanding the role you want to work with:</span> <ul class="ul"><li class="li"><span class="ph uicontrol">FORUM MODERATOR</span></li>
<li class="li"><span class="ph uicontrol">CONTENT AUTHOR</span></li>
<li class="li"><span class="ph uicontrol">ADMINISTRATOR</span></li>
<li class="li"><span class="ph uicontrol">AUTHENTICATED USER</span></li>
<li class="li"><span class="ph uicontrol">ANONYMOUS USER</span></li>
</ul>
For each of these roles, you can use the radio buttons to select one of the following front
page modes: <div class="note note"><span class="notetitle">Note:</span> when a configurable mode is selected, the necessary inputs to configure the mode
displays under the list of modes.</div>
<ul class="ul"><li class="li"><span class="ph uicontrol">Skip</span> - the front page will not be customized for this role.</li>
<li class="li"><span class="ph uicontrol">Themed</span> - your default theme will apply to the front page for this role. </li>
<li class="li"><span class="ph uicontrol">Full</span> - you can fully customize the front page for this user, including
the layout of the page.</li>
<li class="li"><span class="ph uicontrol">Redirect</span> - users in this role will be redirected to a specified
page.</li>
<li class="li"><span class="ph uicontrol">Alias</span> - users in this role see the path that they typed, displayed as a
front page.</li>
<li class="li"> </li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save Settings</span>.</span></li>
</ol>
'),
    'configuring_content.html' => t('<p>You can customise the content that is stored in the Developer Portal. This could involve creating new content types, adding extra metadata to existing types, or setting up a custom review and approval workflow.</p>
<p>See the pages below for more information.</p>'),
    'create_content_type.html' => t('<p class="shortdesc">Content types specify the types of content that you can add the <span class="ph">Developer Portal</span>. You can create
and configure content types within the <span class="ph">Developer Portal</span>. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><div class="p">After you create your content type, it is added to the following list of default content types
that are present in the <span class="ph">Developer Portal</span>: <ul class="ul"><li class="li">API</li>
<li class="li">Application</li>
<li class="li">Article</li>
<li class="li">Basic page</li>
<li class="li">Blog entry</li>
<li class="li">FAQ</li>
<li class="li">Forum topic </li>
<li class="li">Product</li>
<li class="li">Poll</li>
<li class="li">Support ticket</li>
</ul>
</div>

<p class="p">The content type that you create, and the default content types, can be edited and configured
further to your specifications.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph menucascade"><span class="ph uicontrol">Structure</span> &gt; <span class="ph uicontrol"> Content types</span></span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">+Add content type</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Enter a name for your content type in the <span class="ph uicontrol">Name</span> field.</span></li>
<li class="li step stepexpand">Optional: <span class="ph cmd">Enter the text that you want to appear on the <span class="keyword wintitle">Add new content</span> page, in the
<span class="ph uicontrol">Description</span> field. </span></li>
<li class="li step stepexpand"><span class="ph cmd">Enter a title field label for your content type in the <span class="ph uicontrol">Title field label</span>
field.</span></li>
<li class="li step stepexpand">Optional: <span class="ph cmd">Configure any of the following options that are available for your content type: </span> <ul class="ul"><li class="li">Submission form settings</li>
<li class="li">Publishing options</li>
<li class="li">Display settings</li>
<li class="li">Comment settings</li>
<li class="li">Content approval</li>
<li class="li">Fasttoggle settings</li>
<li class="li">Multilingual settings</li>
<li class="li">Menu settings </li>
<li class="li">Notifications settings</li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">After you have configured your content type to your specifications, click <span class="ph uicontrol">Save
content type</span>.</span> You have created a new content type.</li>
</ol>
<div class="section postreq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">What to do next</h2></div>You can add fields to your new content type by clicking <span class="ph uicontrol">manage
fields</span> in the <span class="q">&quot;Content types&quot;</span> window.</div>
'),
    'add_field.html' => t('<p class="shortdesc">Fields can be added to content types in the <span class="ph">Developer Portal</span> for customization,
and to add functionality. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><div class="p">You can specify the type of data that a field can store, and the form element to edit the data
with. The type of form element that is available is dependent on the type of data that your field
stores.<div class="note note"><span class="notetitle">Note:</span> You cannot delete any of the existing fields for the following <span class="ph">IBM&#xAE; API Connect</span> content types: <ul class="ul"><li class="li">API</li>
<li class="li">Product</li>
<li class="li">Application</li>
</ul>
</div>
</div>

<div class="p"><div class="note note"><span class="notetitle">Note:</span> You can specify the number of values that a field can store, including an unlimited amount.
You can configure the value after the field is created. Reducing the number of values that can be
stored after the field is created might lead to data loss.</div>
</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph menucascade"><span class="ph uicontrol">Structure</span> &gt; <span class="ph uicontrol"> Content types</span></span>, and identify the content type that you want to add a field to. </span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">manage fields</span> for the content type.</span></li>
<li class="li step"><span class="ph cmd">Enter a label for your field in the <span class="ph uicontrol">Label</span> field.</span> The label that you specify for your field is displayed in the UI.</li>
<li class="li step">Optional: <span class="ph cmd">If you want to edit the machine name that is automatically created based on your label, click
<span class="ph uicontrol">Edit</span> and enter the new name.</span> You cannot change the machine name after the field is created.</li>
<li class="li step"><span class="ph cmd">Specify the type of data that your field can store by selecting the type from the
<span class="ph uicontrol">Field type</span> list.</span> You cannot change the field type after the field is created. </li>
<li class="li step"><span class="ph cmd">If possible, select the form element to edit the data from the <span class="ph uicontrol">Widget</span>
list.</span> Field types have multiple display options that define how a field is displayed to users. You
can change the widget and display options after the field is created.</li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span> You have added a field to your content type.</li>
</ol>
'),
    'carousel.html' => t('<p class="shortdesc">You can customize your <span class="ph">Developer Portal</span> home page to
display images in a carousel. The image carousel provides your <span class="ph">Developer Portal</span> home page with a
continuous slide show that is customized to your specifications. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">By implementing an image carousel, you can replace the default welcome banner or any previously
set images. </p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Modules</span>, ensure that <span class="ph uicontrol">Views UI</span> is set to
<samp class="ph systemoutput">ON</samp>, and click <span class="ph uicontrol">Save configuration</span>.</span> <div class="note note"><span class="notetitle">Note:</span> Having the <span class="ph uicontrol">Views UI</span> in the <samp class="ph systemoutput">ON</samp> state slows
the performance in the <span class="ph">Developer Portal</span>. Ensure that it is
set to <samp class="ph systemoutput">ON</samp> only for the setup process. </div>
</li>
<li class="li step stepexpand"><span class="ph cmd">Create a content type for your carousel.</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span>, then click <span class="ph menucascade"><span class="ph uicontrol">Content types</span> &gt; <span class="ph uicontrol"> + Add content type</span></span>.</span></li>
<li class="li substep"><span class="ph cmd">Enter the name of your content type in the <span class="ph uicontrol">Name</span> field. </span> For example, <var class="keyword varname">Slide show Picture</var>.</li>
<li class="li substep">Optional: <span class="ph cmd">Specify the description of your content type in the <span class="ph uicontrol">Description</span> field. </span>  For example, <var class="keyword varname">A picture to display in the slideshow</var>.</li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Save content type</span>, then click <span class="ph uicontrol">manage fields</span>
for your newly created content type. </span></li>
<li class="li substep"><span class="ph cmd">Delete the <samp class="ph systemoutput">Body</samp> field.</span></li>
<li class="li substep"><span class="ph cmd">Label your field in the <span class="ph uicontrol">Add a new field</span> section. </span> For example, <var class="keyword varname">slide image</var>.</li>
<li class="li substep">Optional: <span class="ph cmd">Label your machine name by clicking <span class="ph uicontrol">edit</span> next to the label that is
generated automatically.</span></li>
<li class="li substep"><span class="ph cmd">Select <span class="ph uicontrol">Image</span> as your field type from the list.</span></li>
<li class="li substep"><span class="ph cmd">Ensure that the widget type is <span class="ph uicontrol">Image</span>, then click
<span class="ph uicontrol">Save</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Choose File</span> to assign the image that you want as your default
image for your slide show if no other images are found. </span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Save field settings</span>, then click <span class="ph uicontrol">Save
settings</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Upload the images that you want to appear in your carousel.</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph menucascade"><span class="ph uicontrol">Content</span> &gt; <span class="ph uicontrol">+ Add content</span></span>, then click the content type that you added in step 2. </span></li>
<li class="li substep"><span class="ph cmd">Enter a title for your image in the <span class="ph uicontrol">Title</span> field. </span> For example, if you had an image of a lighthouse, you can label the content as
<var class="keyword varname">Lighthouse</var>.</li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Choose File</span> to find an image for your slide show, click
<span class="ph uicontrol">Upload</span> to upload the image, then click <span class="ph uicontrol">Save</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">+ Clone content</span> to open a clone of the image you created. Replace
the title and image of the clone with what you want the next slide to contain.</span></li>
<li class="li substep"><span class="ph cmd">Repeat the cloning and editing process until you are satisfied with the content your slides,
and the number of them.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Create a view for your carousel.</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span>, then click <span class="ph menucascade"><span class="ph uicontrol">Views</span> &gt; <span class="ph uicontrol"> + Add new view</span></span>.</span></li>
<li class="li substep"><span class="ph cmd">Label your view.</span> For example, Slide show.</li>
<li class="li substep"><span class="ph cmd">Clear the <span class="ph uicontrol">Create page</span> check box, and select the <span class="ph uicontrol">Create a
block</span> check box.</span></li>
<li class="li substep"><span class="ph cmd">Select <span class="ph uicontrol">Slick carousel</span> in the display format section.</span></li>
<li class="li substep"><span class="ph cmd">Enter the number of slides you have for your carousel in the <span class="ph uicontrol">Items per
page</span> field, then click <span class="ph uicontrol">Continue and edit</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Title</span> and enter <kbd class="ph userinput">&lt;none&gt;</kbd> into the text
field.</span></li>
<li class="li substep"><span class="ph cmd">In the Format section, click <span class="ph uicontrol">Settings</span> for Slick Carousel, and then set
Skin Main to <span class="ph uicontrol">Default</span>.</span></li>
<li class="li substep"><span class="ph cmd">In the <span class="ph uicontrol">Fields</span> section, click <span class="ph menucascade"><span class="ph uicontrol">Add</span> &gt; <span class="ph uicontrol"> Content: slide image</span> &gt; <span class="ph uicontrol"> Apply</span></span>.</span></li>
<li class="li substep"><span class="ph cmd">Clear the check box for <span class="ph uicontrol">Create a label</span>, then click
<span class="ph uicontrol">Apply</span>.</span></li>
<li class="li substep"><span class="ph cmd">In the <span class="ph uicontrol">Fields</span> section, click <span class="ph menucascade"><span class="ph uicontrol">Content: Title</span> &gt; <span class="ph uicontrol"> Remove</span></span>.</span></li>
<li class="li substep"><span class="ph cmd">In the <span class="ph uicontrol">Filter Criteria</span> section, click <span class="ph menucascade"><span class="ph uicontrol">Content: Published</span> &gt; <span class="ph uicontrol"> Remove</span></span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Enable the carousel functionality on your <span class="ph">Developer Portal</span> home page. </span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph menucascade"><span class="ph uicontrol">Media</span> &gt; <span class="ph uicontrol"> Slick carousel</span></span>.</span></li>
<li class="li substep"><span class="ph cmd">For <span class="ph uicontrol">Default</span>, click <span class="ph uicontrol">edit</span>, then click the
<span class="ph uicontrol">settings</span> tab in the list on the left. </span></li>
<li class="li substep"><span class="ph cmd">Select <span class="ph uicontrol">Autoplay</span>, then click <span class="ph uicontrol">Save</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Configure your home page to host the carousel.</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span>, then click <span class="ph uicontrol">Pages</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">edit</span> for the <span class="ph uicontrol">Welcome</span> page, then click
<span class="ph uicontrol">content</span> in the <span class="ph uicontrol">Panel</span> section.</span></li>
<li class="li substep"><span class="ph cmd">To remove the welcome banner, click the <span class="ph uicontrol">Settings</span> icon in the
<span class="ph uicontrol">View</span> section for the <span class="keyword wintitle">Top</span> pane, and click
<span class="ph uicontrol">Remove</span>. </span></li>
<li class="li substep"><span class="ph cmd">For the <span class="keyword wintitle">Top</span> pane, click <span class="ph menucascade"><span class="ph uicontrol">Settings</span> &gt; <span class="ph uicontrol">Add content</span> &gt; <span class="ph uicontrol"> Miscellaneous</span> &gt; <span class="ph uicontrol"> View: <var class="keyword varname">name_of_your_view</var></span></span>. </span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Finish</span>, then click <span class="ph uicontrol">Update and save</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Modules</span>, and set <span class="ph uicontrol">Views UI</span> to
<samp class="ph systemoutput">OFF</samp>.</span></li>
</ol>
<div class="section result"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Results</h2></div>Your <span class="ph">Developer Portal</span>
front page has a slide show carousel. </div>
<div class="section postreq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">What to do next</h2></div>The default color for the carousel arrows are orange. You can override the default color by
inserting the following code into the <span class="ph filepath">overrides.css</span> file in a custom theme:
<pre class="pre codeblock"><code>/* carousel */
.slick-prev::before, .slick-next::before {
    color: #ffffff;
}             </code></pre>

For more information on editing custom themes, see <a class="xref" href="tutorial_portal_customizing_css.html" title="You can configure an overrides.css file in a theme to customize the appearance of your Developer Portal home page.">Customizing themes for the Developer Portal</a>.</div>
'),
    'configure_appearance_ratings.html' => t('<p class="shortdesc">You can configure the appearance of ratings in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><div class="p">You can configure the appearance of ratings for only the following content types in the <span class="ph">Developer Portal</span>: <ul class="ul"><li class="li"><span class="ph uicontrol">APIs</span></li>
<li class="li"><span class="ph uicontrol">Applications</span></li>
<li class="li"><span class="ph uicontrol">Products</span></li>
</ul>
</div>

<div class="note note"><span class="notetitle">Note:</span> The <span class="ph uicontrol">Applications</span> and <span class="ph uicontrol">Products</span> content types
rating functionality is set to hidden by default. </div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">These following steps describe how to customize the rating on a selected API,
Application, or Product. To ensure that the configured rating is also displayed in the list view of
<span class="ph uicontrol">APIs</span>, <span class="ph uicontrol">Applications</span>, or
<span class="ph uicontrol">Products</span>, then you must repeat these steps for the
<span class="ph uicontrol">Teaser</span> view found in the upper right of the <span class="keyword wintitle">Manage
Display</span> window:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Structure</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Content types</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Manage display</span> next to the required content type.</span> </li>
<li class="li step"><span class="ph cmd">From the <span class="ph uicontrol">Format</span> drop-down menu, select the style of the
<span class="ph uicontrol">Rating</span> field. </span></li>
<li class="li step">Optional: <span class="ph cmd">If you have selected the rating format to be displayed <kbd class="ph userinput">As Stars</kbd>, the
<span class="ph uicontrol">Settings</span> icon <img class="image" src="Icon_settings.jpg" alt="The settings icon"> is visible . Click this icon to refine the display configuration further, then click
<span class="ph uicontrol">Update</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save</span>. You have configured the appearance of the ratings feature
for the required content type.</span></li>
</ol>
'),
    'configure_social_sharing_widget.html' => t('<p class="shortdesc">You can configure the social sharing widget in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To configure the social sharing widget in the <span class="ph">Developer Portal</span>, complete the
following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Under the <span class="ph uicontrol">System</span> heading, click <span class="ph uicontrol">AddToAny</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Placement</span> to expand the Placement view.</span></li>
<li class="li step"><span class="ph cmd">Select the check boxes to display the social sharing widget for the required content types.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>. You have configured the social sharing widget
feature for the required content types.</span></li>
</ol>
'),
    'file_size_limitations.html' => t('<p class="shortdesc">You can configure the limitations that are placed on images that are uploaded in the <span class="ph">Developer Portal</span>. There is a
default profile that can be configured with different image uploading limitations and you can assign
it to different user roles within the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><div class="p">The configurable options that are available for profiles include:<ul class="ul"><li class="li">Maximum file size per upload</li>
<li class="li">Directory quota</li>
<li class="li">Total user quota</li>
<li class="li">Permitted file extensions</li>
<li class="li">Maximum image dimensions</li>
<li class="li">Maximum number of files per operation</li>
<li class="li">Directory paths</li>
<li class="li">Thumbnails</li>
</ul>
 After you have configured the limitations for any profiles that you have created, you can
assign them to roles on a public and private file level. </div>

<div class="note note"><span class="notetitle">Note:</span> To disable image upload completely, disable the <span class="ph uicontrol">IMCE</span> module. For more
information, see <a class="xref" href="tapim_portal_disable_module.html" title="You can disable an entire module in the Developer Portal if you want to improve performance, or remove functionality.">Disabling modules</a>.</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph uicontrol">IMCE</span> in the Media
section.</span> A <span class="ph uicontrol">Default</span> profile is present.</li>
<li class="li step stepexpand"><span class="ph cmd">You can either edit the <span class="ph uicontrol">Default</span> profile with the file limitations that
you require, or you can create a new profile:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Add new profile</span></span></li>
<li class="li substep"><span class="ph cmd">Enter a name for your profile in the <span class="ph uicontrol">Profile name</span> field.</span></li>
<li class="li substep"><span class="ph cmd">Configure your profile with the file limitations you require. After you have configured your
profile, click <span class="ph uicontrol">Save configuration</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand">Optional: <span class="ph cmd">If you have already created other profiles, and want to import their settings, click on the
appropriate profile that are listed next to <span class="ph uicontrol">Import settings from other
profiles</span> in <span class="ph uicontrol">Add new profile</span> .</span></li>
<li class="li step stepexpand"><span class="ph cmd">In the Role-profile assignments section, assign the relevant profile to the roles that are
available in the <span class="ph">Developer Portal</span>. </span></li>
<li class="li step stepexpand">Optional: <span class="ph cmd">If a user has multiple roles, you can assign weights to them.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> You have assigned configured profiles to roles within the <span class="ph">Developer Portal</span>.</li>
</ol>
'),
    'support_ticket.html' => t('<p class="shortdesc">You can configure the support ticket system in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To configure the support ticket system, complete the following steps:</p><ol><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Support ticketing system</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Settings</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">You can configure elements of the support ticket system from this view.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> You have configured the support ticketing system.</li>
</ol><p class="li stepsection">To enable additional support ticket functionality:</p><ol start="5"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Modules</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">In the list of module categories on the left, click <span class="ph uicontrol">Support</span>, then enable
any of the following modules that you require by setting them to <span class="ph uicontrol">ON</span>:  </span> <ul class="ul"><li class="li">Support charting</li>
<li class="li">Support Mail Commands</li>
<li class="li">Support Overview</li>
<li class="li">Support Project Management</li>
<li class="li">Support Reference</li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">After you have enabled the modules that you require, click <span class="ph uicontrol">Save
configuration</span>.</span> You have enabled additional support ticket functionality.  </li>
</ol>'),
    'workbench_moderation.html' => t('<p class="shortdesc">By configuring workbench moderation, you can use a dashboard to manage the review and
approval process for content types in the <span class="ph">Developer Portal</span>. You can specify
which roles can access the workbench dashboard by assigning them the appropriate
permissions.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">By using workbench moderation, you can configure the workflow of your <span class="ph">Developer Portal</span> site, and you can
moderate content types, such as APIs and Products, with the workbench dashboard. However, you cannot
moderate Developer organizations or applications with the workbench dashboard. </p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">To enable the workbench moderation module:</span> <ol type="a" class="ol substeps"><li class="li substep substepexpand"><span class="ph cmd">On the administrator dashboard, click <span class="ph uicontrol">Modules</span>.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">In the <span class="ph uicontrol">Search</span> text field, enter <kbd class="ph userinput">Workbench</kbd>.</span> The <span class="ph uicontrol">Workbench</span> and <span class="ph uicontrol">Workbench Moderation</span>
modules appear.</li>
<li class="li substep substepexpand"><span class="ph cmd">Select the toggle buttons for the <span class="ph uicontrol">Workbench</span> and <span class="ph uicontrol">Workbench
Moderation</span> modules so that a green tick is displayed.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> <div class="note note"><span class="notetitle">Note:</span> You must rebuild your content access permissions after completing this step.</div>
</li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">To enable revisions and moderation for specific content types:</span> <ol type="a" class="ol substeps"><li class="li substep substepexpand"><span class="ph cmd">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Structure</span> &gt; <span class="ph uicontrol">Content types</span></span>.</span> The <span class="ph uicontrol">Content types</span> window is displayed.</li>
<li class="li substep substepexpand"><span class="ph cmd">Click <span class="ph uicontrol">edit</span> for the content type that you want to enable revisions and
moderation for.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">In the list to the lower left of the window, click <span class="ph uicontrol">Publishing options</span>
and select the check boxes for <span class="ph uicontrol">Create new revision</span> and <span class="ph uicontrol">Enable
moderation of revisions</span>.</span> <div class="note important"><span class="importanttitle">Important:</span> You must ensure that the <span class="ph uicontrol">Published</span> check box is not
selected. </div>
</li>
<li class="li substep substepexpand">Optional: <span class="ph cmd">Select the default moderation state of the content type from the <span class="ph uicontrol">Default moderation
state</span> drop-down list.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save content type</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">To configure workbench states and transitions:</span> <ol type="a" class="ol substeps"><li class="li substep substepexpand"><span class="ph cmd">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol"> Workbench Moderation</span></span>.</span></li>
<li class="li substep substepexpand">Optional: <span class="ph cmd">Enter a name and description for your new desired state in the corresponding text fields for
<span class="ph uicontrol">New state</span>.</span> <div class="note note"><span class="notetitle">Note:</span> You must rebuild your views after completing this step.</div>
</li>
<li class="li substep substepexpand">Optional: <span class="ph cmd">Select the <span class="ph uicontrol">Delete</span> check boxes adjacent to any existing states that you
want to delete.</span> <div class="note note"><span class="notetitle">Note:</span> You must rebuild your views after completing this step.</div>
</li>
<li class="li substep substepexpand"><span class="ph cmd">Select the <span class="ph uicontrol">Transitions</span> tab.</span></li>
<li class="li substep substepexpand">Optional: <span class="ph cmd">Enter a name for your new desired transition in the <span class="ph uicontrol">Transition Name</span> text
field. </span></li>
<li class="li substep substepexpand">Optional: <span class="ph cmd">Select the two transition states for your new transition state from the two <span class="ph uicontrol">New
transition</span> drop-down lists. </span></li>
<li class="li substep substepexpand">Optional: <span class="ph cmd">Select the <span class="ph uicontrol">Delete</span> check boxes adjacent to any existing states that you
want to delete.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save</span> after you have completed your configurations.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">To set up the permissions for a specific content type for the Content Author role:</span> <ol type="a" class="ol substeps"><li class="li substep substepexpand"><span class="ph cmd">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">People</span> &gt; <span class="ph uicontrol"> Permissions</span></span>.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Ensure that the following check boxes are selected for the <span class="ph uicontrol">Content
Author</span> role:</span> <ul class="ul"><li class="li">View content revisions</li>
<li class="li">View all unpublished content </li>
<li class="li">View the moderation messages on a node</li>
<li class="li">Use &quot;My drafts&quot; workbench tab</li>
<li class="li">Use &quot;Needs review&quot; workbench tab</li>
<li class="li"><var class="keyword varname">Content_type_that_you_want_to_moderate</var>: Edit own content </li>
<li class="li"><var class="keyword varname">Content_type_that_you_want_to_moderate</var>: Create new content</li>
</ul>
</li>
<li class="li substep substepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save permissions</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">To identify the permissions that specific roles require to access the workbench dashboard: </span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">In the <span class="keyword wintitle">Workbench Moderation</span> window, select the <span class="ph uicontrol">Check
permissions</span> tab.</span></li>
<li class="li substep"><span class="ph cmd">Select the user role that you want to investigate from the <span class="ph uicontrol">Drupal role</span>
drop-down list.</span></li>
<li class="li substep"><span class="ph cmd">Select the type of moderation task that you want the role to be enabled to perform from the
<span class="ph uicontrol">Moderation task</span> drop-down list.</span></li>
<li class="li substep"><span class="ph cmd">Select the check boxes that correspond to the content types that you want the user to perform
moderation tasks on. </span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Check permissions</span>.</span> A list of recommended actions are displayed at the top of the <span class="keyword wintitle">Workbench
Moderation</span> window. By following the recommended actions, you can assign the permissions
that are required for a role to access the workbench dashboard. </li>
</ol>
</li>
</ol>
'),
    'using_workbench_moderation.html' => t('<p class="shortdesc">If you have published content from <span class="ph">API Manager</span> UI to your <span class="ph">Developer Portal</span> or created content
within the <span class="ph">Developer Portal</span>,
you can edit and review the content through the workbench before you publish it in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>

<p class="p">You must have content that is published to the <span class="ph">Developer Portal</span> from the <span class="ph">API Manager</span> UI.</p>

<p class="p">Your role must have the necessary permissions that enable you to perform the tasks. For more
information on assigning the necessary tasks, see <a class="xref" href="tapic_portal_workbench_moderation.html">Configuring workbench
moderation</a>.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">In addition to editing content through the workbench, you can specify whether the content needs
further review, or even publish the content if you have the permission.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">On the administrator dashboard, click <span class="ph uicontrol">My Workbench</span>.</span> The <span class="keyword wintitle">My Workbench</span> window is displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">If you have the necessary permissions to edit your content type:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">My drafts</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click the <span class="ph uicontrol">Title</span> of the content type that you want to edit, then click
the <span class="ph uicontrol">Edit draft</span> tab.</span></li>
<li class="li substep"><span class="ph cmd">Edit your content to your specification, then click <span class="ph uicontrol">Save</span>.</span> You are redirected to the <span class="ph uicontrol">View draft</span> tab.</li>
<li class="li substep"><span class="ph cmd">After you have finished editing your content type, ensure that <span class="ph uicontrol">Needs
Review</span> is selected from the <span class="ph uicontrol">Set moderation state</span> field, then
click <span class="ph uicontrol">Apply</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">If you have the necessary permissions to review your content type:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">On the administrator dashboard, click <span class="ph uicontrol">My Workbench</span>.</span> The <span class="keyword wintitle">My Workbench</span> window is displayed.</li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Needs review</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click the <span class="ph uicontrol">Title</span> of the content type that you want to review.</span></li>
<li class="li substep">Optional: <span class="ph cmd">If the content that you are reviewing requires editing, click <span class="ph uicontrol">Edit
draft</span>, apply the changes to your content type, then click
<span class="ph uicontrol">Save</span>.</span> You are redirected to the <span class="ph uicontrol">View draft</span> tab.</li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">If you have the necessary permissions to publish your content type:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">On the administrator dashboard, click <span class="ph uicontrol">My Workbench</span>.</span> The <span class="keyword wintitle">My Workbench</span> window is displayed.</li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Needs review</span>.</span></li>
<li class="li substep"><span class="ph cmd">In the <span class="ph uicontrol">Set moderation state</span> column, click <span class="ph uicontrol">Change to
Published</span> for the content type that you want to publish.</span></li>
</ol>
</li>
</ol>
<div class="section result"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Results</h2></div>You have published your content type, and the content type is removed from the
<span class="ph uicontrol">Needs review</span> tab.</div>
'),
    'managing_users.html' => t('<p>The Developer Portal has a thorough permissions architecture. The pages in this section illustrate how it can be customised to add new roles and permissions.</p>'),
    'creating_and_customising_a_new_role.html' => t('<p class="shortdesc">You can create and customize new roles in the <span class="ph">Developer Portal</span>.</p>
<div class="p">You can assign the following roles to users in the <span class="ph">Developer Portal</span>: <dl class="dl"><dt class="dt dlterm">Administrator</dt>
<dd class="dd">An Administrator can use the administrator dashboard to customize and configure the <span class="ph">Developer Portal</span>. An Administrator
is able to perform any task within the <span class="ph">Developer Portal</span> that does not
involve the configuration of APIs, Products, and Apps. </dd>
<dt class="dt dlterm">Content Author</dt>
<dd class="dd">A Content Author is a curator of content for the <span class="ph">Developer Portal</span>. A Content Author
can perform the following actions: <ul class="ul"><li class="li">Set taxonomies on the content</li>
<li class="li">Provide custom icons for content</li>
<li class="li">Upload additional documentation files</li>
<li class="li">Create documentation pages in the <span class="ph">Developer Portal</span>.</li>
</ul>
</dd>
<dt class="dt dlterm">Forum Moderator</dt>
<dd class="dd">A Forum Moderator can moderate the content of a forum in the <span class="ph">Developer Portal</span>.</dd>
</dl>
</div>
<div class="note note"><span class="notetitle">Note:</span> The email address that is used to create an Administrator account for the <span class="ph">Developer Portal</span> must be unique. It
is not possible to create an account with the email address that is associated with the
Administrator account. Any attempts to create an account with the associated email address results
in the new account not functioning correctly, and returning the following error message when
attempting to log in: <samp class="ph codeph">A user already exists with this email address</samp>.</div>
<p class="p">To learn how to create and customize new roles in the <span class="ph">Developer Portal</span>, use the following
topic links: </p>
'),
    'create_a_new_role.html' => t('<p class="shortdesc">You can create a new role within the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To create a new role, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">People</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Select the <span class="ph uicontrol">PERMISSIONS</span> tab.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Roles</span>.</span></li>
<li class="li step"><span class="ph cmd">Enter a name for the role in the value box and click <span class="ph uicontrol">Add role</span>. </span> You have created a new role in the <span class="ph">Developer Portal</span>.</li>
</ol>
'),
    'assigning_people_to_a_role.html' => t('<p class="shortdesc">You can assign people to a role in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To assign people to a role, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">People</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Select the <span class="ph uicontrol">LIST</span> tab.</span></li>
<li class="li step"><span class="ph cmd">Select the check boxes for the target users.</span></li>
<li class="li step"><span class="ph cmd">From the drop-down list under the <span class="ph uicontrol">Update Options</span> heading, select a role
from the <span class="ph uicontrol">Add a role to the selected users</span> subheading. </span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Update</span>.</span> You have assigned the selected users to the required role.</li>
</ol>
'),
    'assigning_permissions_to_a_new_role.html' => t('<p class="shortdesc">You can assign permissions to a new role in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To assign permissions for a role, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">People</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Select the <span class="ph uicontrol">PERMISSIONS</span> tab.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Permissions</span>.</span></li>
<li class="li step"><span class="ph cmd">Assign a permission to a role by selecting its check box in the relevant column.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Save permissions</span>.</span>  You have assigned permissions to the target role</li>
</ol>
'),
    'content_author_permissions.html' => t('<p class="shortdesc">In <span class="ph">IBM&#xAE; API Connect</span>, you can restrict
access to content for Developer organizations through an access content list. However, in the <span class="ph">Developer Portal</span> you can assign
permissions that override the access content list and allow users to access and edit all of the
content for Products, APIs, and Applications. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><div class="p">A Content Author is a curator of content for the <span class="ph">Developer Portal</span>. A Content Author
can perform the following actions: <ul class="ul"><li class="li">Set taxonomies on the content</li>
<li class="li">Provide custom icons for content</li>
<li class="li">Upload additional documentation files</li>
<li class="li">Create documentation pages in the <span class="ph">Developer Portal</span>.</li>
</ul>
</div>

<div class="p">By default, Content Authors are assigned the following user permissions:<dl class="dl"><dt class="dt dlterm">Edit any Product content</dt>
<dd class="dd"> The user can access and edit all of the Products available in the Catalog that the <span class="ph">Developer Portal</span> is associated
with. The user can also access APIs that belong to those Products.</dd>
<dt class="dt dlterm">Edit any API content</dt>
<dd class="dd">The user can access and edit all of the APIs that are available in the Catalog, but not the
Products with which they are associated.</dd>
</dl>
</div>

<div class="p">The <span class="ph uicontrol">Edit any Application</span> permission grants the user access to all of the
Applications that are in the <span class="ph">Developer Portal</span>, but is not
assigned to any user or role by default.<div class="note note"><span class="notetitle">Note:</span> There are security implications if you grant the
<span class="ph uicontrol">Edit any Application</span> permission, and it should be assigned only to trusted
roles. </div>
</div>

<p class="p">A user can configure, attach, and upload content if they are assigned the appropriate
permissions.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To assign permissions that grant users access to all content, proceed with one of the
following steps: </p><ul class="ul steps-unordered"><li class="li step"><span class="ph cmd">Assign a user to the Content Author role. For more information, see <a class="xref" href="tapim_portal_assigning_people_to_a_role.html">Assigning people to a
role</a>.</span></li>
<li class="li step"><span class="ph cmd">Create a custom role, to which you can add the relevant permissions and add users to your new
custom role. For more information, see <a class="xref" href="tapim_portal_create_a_new_role.html">Creating a new role</a>, <a class="xref" href="tapim_portal_assigning_permissions_to_a_new_role.html">Assigning
permissions to a new role</a>, and <a class="xref" href="tapim_portal_assigning_people_to_a_role.html">Assigning people to a role</a>.</span></li>
</ul>
'),
    'user_reg_config.html' => t('<p class="shortdesc">You can customize the options that are displayed to users when registering to use the <span class="ph">Developer Portal</span>.</p>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To customize the user registration process, complete the following steps:</p><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard, then click
<span class="ph uicontrol">IBM API Connect</span>.</span> The <span class="keyword wintitle">IBM API Connect</span> dialog
box is displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">You can complete the following tasks:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">To control which fields are shown during the registration, select the corresponding check boxes in the <span class="ph uicontrol">USER REGISTRATION</span> pane.</span></li>
<li class="li substep"><span class="ph cmd">To configure the display of terms and conditions during user registration, click the link provided; for more information, see <a class="xref" href="tapim_tsandcs_config.html" title="You can define and customize a terms and conditions statement that your users must accept before they can register to use your Developer Portal.">Customizing the terms and conditions statement</a>.</span></li>
<li class="li substep"><span class="ph cmd">To configure the use of CAPTCHAs during user login or registration, click the link provided; for more information, see <a class="xref" href="tapim_captcha_config.html" title="You can add a CAPTCHA challenge to any of your Developer Portal pages.">Configuring CAPTCHAS</a>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span> to save your changes.</span></li>
</ol>
'),
    'blocking_and_unblocking_specific_users.html' => t('<p class="shortdesc">You can block and unblock specific users in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To block or unblock a specific user, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">People</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Select the <span class="ph uicontrol">LIST</span> tab.</span></li>
<li class="li step"><span class="ph cmd">Select the check box next to the target user.</span></li>
<li class="li step"><span class="ph cmd">From the drop-down list under <span class="ph uicontrol">Update Options</span>, select <span class="ph uicontrol">Block
the selected users</span> or <span class="ph uicontrol">Unblock the selected users</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Update</span>.</span> You have blocked or unblocked specific users from the <span class="ph">Developer Portal</span>.</li>
</ol>
'),
    'managing_security.html' => t('<p>It\'s important to maintain a secure website and comply with all of the necessary rules in your organization. Many different security features are included in the Developer Portal and can be customised to suit individual requirements.</p>
<p>There are also very flexible options available for customizing the authentication mechanism your Developer Portal site uses. See the \'Portal Delegated User Registry\' section for more information.</p>'),
    'captcha_config.html' => t('<p class="shortdesc">You can add a CAPTCHA challenge to any of your <span class="ph">Developer Portal</span> pages.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div>The types of CAPTCHA challenges that are available include <span class="ph uicontrol">Image</span> and
<span class="ph uicontrol">Math</span> based CAPTCHAs. Both types of CAPTCHA challenges are configurable in the
<span class="keyword wintitle">CAPTCHA</span> dialog box. </div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To configure CAPTCHAS, complete the following steps:</p><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph uicontrol">CAPTCHA</span>.</span> The <span class="keyword wintitle">CAPTCHA</span> dialog box is displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">Use the CAPTCHA configuration options provided as required. For example, you can complete the following tasks:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Specify the CAPTCHA challenge type.</span></li>
<li class="li substep"><span class="ph cmd">Specify which forms must include a CAPTCHA challenge.</span></li>
<li class="li substep"><span class="ph cmd">Add a description to the CAPTCHA.</span></li>
<li class="li substep"><span class="ph cmd">Enable CAPTCHA statistics.</span></li>
<li class="li substep"><span class="ph cmd">Select the <span class="ph uicontrol">IMAGE CAPTCHA</span> tab to configure the character, font, and image settings.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span> to save your changes.</span></li>
</ol>
'),
    'tsandcs_config.html'=> t('<p class="shortdesc">You can define and customize a terms and conditions statement that your users must accept
before they can register to use your <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To define or customize a terms and conditions statement,
complete the following steps:</p><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph uicontrol">Legal</span>.</span> The <span class="keyword wintitle">Legal</span> dialog box is displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">You can complete the following tasks:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Enter the text of the terms and conditions statement.</span></li>
<li class="li substep"><span class="ph cmd">Choose the display style.</span></li>
<li class="li substep"><span class="ph cmd">Define additional check boxes that the user must select
before completing the registration.</span></li>
<li class="li substep"><span class="ph cmd">Enter an explanation of the changes that have been made
to the terms and conditions since the last version.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">To preview your terms and conditions statement, click <span class="ph uicontrol">Preview</span>.</span> A preview is displayed in the display pane of the <span class="keyword wintitle">Legal</span> dialog
box.</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save</span> to save your changes.</span></li>
</ol>
'),
    'blocked_ip_addresses.html' => t('<p class="shortdesc">You can block specific IP addresses from accessing your <span class="ph">Developer Portal</span> site.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To manage blocked IP addresses, complete the following steps:</p><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph uicontrol">IP address blocking</span>.</span> The <span class="keyword wintitle">IP address blocking</span> dialog box is displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">You can complete the following tasks:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">To block an IP address, enter the address in the <span class="ph uicontrol">IP address</span> field, and click <span class="ph uicontrol">Add</span>.</span> The address is added to the <span class="ph uicontrol">BLOCKED IP ADDRESSES</span> list.</li>
<li class="li substep"><span class="ph cmd">To unblock an IP address by removing it from the list, click <span class="ph uicontrol">delete</span> alongside the address that you want to unblock.</span></li>
</ol>
<div class="note note"><span class="notetitle">Note:</span> there is automatic log in &apos;flood&apos; protection built into the <span class="ph">Developer Portal</span>. This feature
means that an excess of log in attempts from the same IP address in one hour will cause that IP to
be temporarily blocked. </div>
</li>
</ol>
'),
    'flood_control.html' => t('<p class="shortdesc">You can configure the flood control for your <span class="ph">Developer Portal</span> at an IP and user
level. You can also configure flood control for <span class="ph">Developer Portal</span> contact forms. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard, then click
<span class="ph uicontrol">Login Security</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">In the <span class="keyword wintitle">Login Security</span> window, configure the following options to your
specifications: </span> <ul class="ul"><li class="li">Track time</li>
<li class="li">Maximum number of login failures before blocking a user</li>
<li class="li">Maximum number of login failures before soft blocking a host </li>
<li class="li">Maximum number of login failures before blocking a host </li>
<li class="li">Maximum number of login failures before detecting an ongoing attack </li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">Select the check boxes that satisfy your notification requirements in the
<span class="keyword wintitle">Notifications</span> section: </span> <div class="note note"><span class="notetitle">Note:</span> The <span class="ph uicontrol">Disable login failure error message</span> is selected by default</div>

<ul class="ul"><li class="li">Disable login failure error message</li>
<li class="li">Notify the user about the number of remaining login attempts </li>
<li class="li">Display last login timestamp</li>
<li class="li">Display last access timestamp  </li>
</ul>
</li>
<li class="li step stepexpand">Optional: <span class="ph cmd">Specify a user for the <span class="ph uicontrol">Select who should get an email message when a user is blocked
by this module</span> field.</span></li>
<li class="li step stepexpand">Optional: <span class="ph cmd">Specify a user for the <span class="ph uicontrol">Select who should get an email message when an ongoing attack
is detected</span> field.</span></li>
<li class="li step stepexpand">Optional: <span class="ph cmd">You can edit the notification texts that are displayed by configuring the options in the
<span class="ph uicontrol">Edit Notification Texts</span> tab.</span></li>
<li class="li step stepexpand"><span class="ph cmd">After you have configured your login security options, click <span class="ph uicontrol">Save
configuration</span>.</span> You have successfully configured the flood control of your <span class="ph">Developer Portal</span>.</li>
</ol>
'),
    'session_timeout_config.html' => t('<p class="shortdesc">You can specify session timeout settings to control when a user is automatically logged
out of the <span class="ph">Developer Portal</span>.
You can also restrict the number of sessions a single user has available to them.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To change the blocks that are displayed on a page, complete the following
steps:</p><ol><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph uicontrol">Auto
Logout</span>.</span> The <span class="keyword wintitle">Auto Logout</span> dialog box is displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">Use the session timeout configuration options provided as required. For example, you can
complete the following tasks:</span> <div class="note note"><span class="notetitle">Note:</span> The default session timeout is 30 minutes.</div>
<ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Specify the length of inactivity time, in seconds, before a user is automatically logged
out.</span></li>
<li class="li substep"><span class="ph cmd">Specify session timeout values for individual user roles.</span></li>
<li class="li substep"><span class="ph cmd">Provide a redirection URL at logout.</span></li>
<li class="li substep"><span class="ph cmd">Provide session expiry message texts.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span> to save your changes.</span> You have specified session timeout settings for a user.</li>
</ol><p class="li stepsection">To restrict the number of sessions a user has available to them:</p><ol start="4"><li class="li step stepexpand"><span class="ph cmd">In the <span class="ph uicontrol">Configuration</span> tab, click <span class="ph uicontrol">Session
limit</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Specify the default maximum number of sessions for a user by entering the number into the
<span class="ph uicontrol">Default maximum number of active sessions </span> field.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Select the radio button that enforces the action that is taken in the <span class="ph uicontrol">When the
session limit is exceeded</span> section.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Select the message that the user receives when they are logged out from the <span class="ph uicontrol">Logged
out message severity</span> list.</span> The default message is <samp class="ph systemoutput">Error</samp>.</li>
<li class="li step stepexpand"><span class="ph cmd">Define the logged out message the user receives in the <span class="ph uicontrol">Logged out
message</span> field, then click <span class="ph uicontrol">Save configuration</span>.</span> You have created session limit restrictions for a user. </li>
</ol>'),
    'logout_refresh.html' => t('<p class="shortdesc">To ensure that confidential data is not left displayed on the screen, you can force the
browser to refresh the page after you are logged out of the <span class="ph">Developer Portal</span>. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then <span class="ph uicontrol">Ejector Seat settings</span>
in the People section.</span></li>
<li class="li step"><span class="ph cmd">Define how often a check happens to see whether you are logged out by selecting a time in the
<span class="ph uicontrol">AJAX check rate</span> list.</span></li>
<li class="li step"><span class="ph cmd">Select the radio button that defines whether you want the check to run in the background, in
the <span class="ph uicontrol">Run AJAX check in background</span> section.</span> You have defined the rate and activity of the when the browser forces a refresh after
you are logged out. </li>
</ol>
'),
    'pword_policy_config.html' => t('<p class="shortdesc">You can configure your site password policy for logging into the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div>Any passwords that are stored in the <span class="ph">API Manager</span> must comply with the <span class="ph">API Manager</span> password policy. You can configure
your password policy to be the same as or stricter than the <span class="ph">API Manager</span> password policy. </div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To configure your site password policy, complete the following steps:</p><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph uicontrol">Password policies</span>.</span> The <span class="keyword wintitle">Password policies</span> dialog box is displayed.</li>
<li class="li step stepexpand"><span class="ph cmd">Use the password policy options provided as required. For example, you can complete the following tasks:</span> <ol type="a" class="ol substeps"><li class="li substep substepexpand"><span class="ph cmd">Choose to display the password restrictions on the password change page.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Define a password expiration warning email.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Change the default password policy, by selecting the <span class="ph uicontrol">LIST</span> tab, then clicking <span class="ph uicontrol">edit</span>. To save your changes, click <span class="ph uicontrol">Save changes.</span>.</span> The following examples are password policy options:<ul class="ul"><li class="li">Specify that the password must not match any of a specified number of previous passwords.</li>
<li class="li">Control the password complexity based on character types.</li>
<li class="li">Require a minimum number of characters of different types.</li>
<li class="li">Define the minimum password length.</li>
<li class="li">Specify that the password must not contain the user name.</li>
<li class="li">Specify the minimum number of hours between password changes.</li>
<li class="li">Restrict digit placement.</li>
</ul>
</li>
<li class="li substep substepexpand"><span class="ph cmd">Force password change on first time login, by selecting the <span class="ph uicontrol">FORCE PASSWORD CHANGE</span> tab. To save your changes, click <span class="ph uicontrol">Save changes.</span></span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span> to save your password policy settings.</span></li>
</ol>
'),
    'password_reset_link.html' => t('<p class="shortdesc">You can configure the timeout length for one-time links that are sent from the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><div class="p">By completing the steps in this task, you are configuring the timeout length for one-time links
that are sent by the <span class="ph">Developer Portal</span> only. <div class="note note"><span class="notetitle">Note:</span> You
are not affecting the user activation or password reset links that are used by any other
identification provider type in <span class="ph">API Manager</span>.</div>
</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol">People</span> &gt; <span class="ph uicontrol">Account settings</span></span>. The <span class="keyword wintitle">Account settings</span> window is displayed.</span></li>
<li class="li step stepexpand"><span class="ph cmd">In the <span class="keyword wintitle">Account settings</span> window, navigate to the <span class="ph uicontrol">Password
reset</span> section.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Enter your specified value for the timeout in the <span class="ph uicontrol">User Password Reset Link
Timeout</span> field.</span> <div class="note note"><span class="notetitle">Note:</span> The timeout value is specified in seconds, and is 86400 seconds by default.</div>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> The timeout length for the one-time links that are sent from the <span class="ph">Developer Portal</span> is
configured.</li>
</ol>
'),
    'no_autocomplete.html' => t('<p class="shortdesc">You can define whether browsers remember your user name and password. The option to turn
the autocomplete functionality off is enabled by default.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph uicontrol">No Autocomplete</span>
in the People section.</span></li>
<li class="li step"><span class="ph cmd">Select the check boxes that correspond to where you want autocomplete not to function, then
click <span class="ph uicontrol">Save configuration</span>.</span> You have defined whether your user name and password is remembered by the
browser.</li>
</ol>
'),
    'restricting_users.html' => t('<p class="shortdesc">You can restrict access to a user or role by IP address in the <span class="ph">Developer Portal</span>. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">By restricting log in to a user by IP address, the user will not be able to log in to a user
outside IP address ranges that you define. You can also specify global IP address ranges, which
apply to all users. IP restrictions are checked on every page load. If a user restriction is
triggered by an attempt at logging in being denied, then the user will be logged out and sent to the
&apos;error page&apos; that is specified by the site administrator. </p>

<div class="p">By restricting access to a role by IP address, that role will not be available to users outside
your defined IP address ranges. If a role restriction is triggered, the user&apos;s session will be
unaffected, but the restricted role will no longer be available to the user. Role restriction
affects only the availability of the restricted role to users. Role restrictions are available for
all roles, except <kbd class="ph userinput">anonymous user</kbd> and <kbd class="ph userinput">authenticated
user</kbd>.<div class="note note"><span class="notetitle">Note:</span> IP address ranges must be entered in CIDR notation separated with semi-colons
and no trailing semi-colon. For more information on CIDR notation, see <a class="xref" href="https://www-01.ibm.com/support/knowledgecenter/SS2MBL_9.0.2/PCAAdmin/PCA/Cfg/CIDRFormat_47.dita?lang=en" target="_blank" title="(Opens in a new tab or window)">CIDR format</a>.</div>
</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">In the <span class="ph">Developer Portal</span>,
click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol"> People</span> &gt; <span class="ph uicontrol"> Restrict by IP</span></span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Select one of the following options:</span> <ul class="ul choices"><li class="li choice">To restrict log in by IP address, complete the following steps:<ol class="ol" type="a"><li class="li">Click the <span class="ph uicontrol">Restrict login by IP</span> tab.</li>
<li class="li">Click <span class="ph uicontrol">Add new IP restrcition for user</span>.</li>
<li class="li">Enter a user name for the range of IP addresses that you want to restrict log in for in the
<span class="ph uicontrol">Username</span> field. </li>
<li class="li">Enter the IP address range (in CIDR notation) that you do not want to restrict log in for in the
<span class="ph uicontrol">Allowed IP range</span> field, then click <span class="ph uicontrol">Save restriction</span>.
Your new log in restriction is added to the table in the <span class="keyword wintitle">Current login
restrictions</span> window. To edit a log in restriction, click <span class="ph uicontrol">edit</span> for
the required restriction in this window.</li>
<li class="li">Enter the address of the page to which the user will be redirected to if they are not allowed to
log in, in the <span class="ph uicontrol">Login denied error page</span>.</li>
<li class="li">To restrict log in for all users, enter the global IP address ranges (in
CIDR notation) in the <span class="ph uicontrol">Restrict global login to allowed IP range </span> field.</li>
<li class="li">Click <span class="ph uicontrol">Save configuration</span>.</li>
</ol>
</li>
<li class="li choice">To restrict role by IP address, complete the following steps:<ol class="ol" type="a"><li class="li">Click the <span class="ph uicontrol">Restrict role by IP</span> tab.</li>
<li class="li">Click on the name of the role that you want to restrict. The roles that you can restrict include
Administrator, Content Author, and Forum Moderator.</li>
<li class="li">Enter the IP address range (in CIDR notation) that you do not want to restrict log in for in the
<span class="ph uicontrol">Allowed IP range</span> field, then click <span class="ph uicontrol">Save confirguation</span>.
You have restricted a role by IP. </li>
</ol>
</li>
<li class="li choice">To remove an IP restriction, delete the value associated with the restriction, then click
<span class="ph uicontrol">Save configuration</span>.</li>
</ul>
</li>
</ol>
'),
    'two_factor_auth.html' => t('<p class="shortdesc">You can enable two-factor authentication for the <span class="ph">Developer Portal</span>. This feature adds
an extra level of security for users attempting to access the <span class="ph">Developer Portal</span>.  </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">You can also enable the <span class="q">&quot;Trusted Browsers&quot;</span> feature, to allow trusted browsers to bypass the
two-factor authentication process for 30 days. When two-factor authentication is enabled, you can
configure who needs to use two-factor authentication to access the <span class="ph">Developer Portal</span></p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To enable two-factor authentication, complete the following steps:</p><ol><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Under <span class="ph uicontrol">PEOPLE</span>, click <span class="ph uicontrol">Two-factor
Authentication</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Select the <span class="ph uicontrol">Enable TFA</span> check box. </span> The view expands to show the configuration options for two-factor
authentication.</li>
<li class="li step stepexpand"><span class="ph cmd">Under <span class="ph uicontrol">Login plugins</span>, you can optionally select the <span class="ph uicontrol">Trusted
Browsers</span> check box. The view expands to show the configuration options for trusted
browsers. </span> This feature enables users to mark specific web browsers as trusted, which will cause the TFA
token request from that browser to be skipped for 30 days.</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span> Two-factor authentication is now available for administrators.</li>
</ol><p class="li stepsection">To configure who needs to use two factor authentication to access the <span class="ph">Developer Portal</span>, complete the
following steps:</p><ol start="6"><li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">People</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Select the <span class="ph uicontrol">PERMISSIONS</span> tab.</span></li>
<li class="li step stepexpand"><span class="ph cmd">In the <span class="ph uicontrol">Filter list</span> field, type <kbd class="ph userinput">TFA</kbd> .</span> The following permissions will show under the <span class="ph uicontrol">PERMISSION</span> heading:<ul class="ul"><li class="li"><span class="ph uicontrol">Set up TFA for account</span> - Sets who can turn on two-factor authentication
for their account, by default this is only administrators.</li>
<li class="li"><span class="ph uicontrol">Require TFA process</span> - Forces users in a specific role to use two-factor
authentication. If two-factor authentication is enabled for a role but not for a specific user with
that role assigned to them, they will be prompted to set up two-factor authentication when they log
in. </li>
<li class="li"><span class="ph uicontrol">Bypass force TFA setup</span> - Enables a specific role to bypass the two-factor
authentication and still log in. This can be useful for the prevention of the admin user being
locked out of the <span class="ph">Developer Portal</span>.</li>
<li class="li"><span class="ph uicontrol">Administer TFA</span> - Allows the modification of two-factor authentication
settings. This permission should only granted to users with administrator roles. </li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">Select and deselect the check boxes in the columns for each role to assign the previously
listed permissions to each role. </span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save permissions</span>.</span> You have enabled and configured two-factor authentication. </li>
</ol>'),
    'security_questions.html' => t('<p class="shortdesc">You can enable security questions for when a user wants to reset their <span class="ph">Developer Portal</span> account password.
There are default security questions already configured when you allow security questions in the <span class="ph">Developer Portal</span>, which you can
remove, as well as adding your own security questions. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Log in to the <span class="ph">Developer Portal</span> as an
administrator.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Modules</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Switch the slider for the <span class="ph uicontrol">Security questions</span> module to
<span class="ph uicontrol">ON</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span> in the administrator dashboard.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Security questions</span> under the <span class="ph uicontrol">PEOPLE</span>
heading.</span></li>
<li class="li step stepexpand"><span class="ph cmd"> Review the default security questions and settings, use the following information to configure
the security questions and settings:</span> <p class="lines"></p>
From the <span class="ph uicontrol">QUESTIONS</span> tab, you can complete the following tasks: <ul class="ul"><li class="li"><dl class="dl"><dt class="dt dlterm">Add a new security question</dt>
<dd class="dd">Type your new security question in to the <span class="ph uicontrol">Add a question</span> field, then
click <span class="ph uicontrol">Add</span>.</dd>
</dl>
</li>
</ul>
<ul class="ul"><li class="li"><dl class="dl"><dt class="dt dlterm">Remove default security questions</dt>
<dd class="dd">Click <span class="ph uicontrol">delete</span> next to the security question, or questions, which you want
to remove.</dd>
</dl>
</li>
</ul>
<p class="lines">From the <span class="ph uicontrol">SETTINGS</span> tab, you can complete the following tasks:</p>
<ul class="ul"><li class="li"><dl class="dl"><dt class="dt dlterm">Change the number of security questions required upon password reset</dt>
<dd class="dd">Use the drop-down list under the <span class="ph uicontrol">Required number of questions</span> heading to
select the number of security questions you want the user to answer when they reset their
password.</dd>
</dl>
</li>
</ul>
<ul class="ul"><li class="li"><dl class="dl"><dt class="dt dlterm">Allow the user to enter their own security questions when setting up their <span class="ph">Developer Portal</span> user account</dt>
<dd class="dd">If you want the user to be able to set their own security questions, select the
<span class="ph uicontrol">User-defined</span> questions check box. </dd>
</dl>
</li>
</ul>
<ul class="ul"><li class="li"><dl class="dl"><dt class="dt dlterm">Protect additional forms using a security question</dt>
<dd class="dd">You must use the check boxes and select at least one of the features under the
<span class="ph uicontrol">PROTECTED FORMS</span> heading to enable the security questions feature: <ul class="ul"><li class="li"><span class="ph uicontrol">Password reset request</span></li>
<li class="li"><span class="ph uicontrol">User login</span></li>
<li class="li"><span class="ph uicontrol">Show &quot;remember this computer&quot; option on protected forms</span></li>
</ul>
</dd>
</dl>
</li>
</ul>
<ul class="ul"><li class="li"><dl class="dl"><dt class="dt dlterm">Choose how longer a user will be blocked from the site after giving an incorrect security
answer</dt>
<dd class="dd">Use the drop-down list under the <span class="ph uicontrol">FLOOD CONTROL</span> heading to select the
number of hours that a user will be blocked from the site if they answer a security question
wrong.</dd>
</dl>
</li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span></li>
</ol>
'),
    'misc_security.html' => t('<p class="shortdesc">You can improve the security of your website by configuring various options that are
available in the Security kit module, in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div>You can configure the options in the Security kit to protect your website from the
following types of attacks: <ul class="ul"><li class="li">Cross-site scripting</li>
<li class="li">Cross-site request forgery</li>
<li class="li">Clickjacking</li>
<li class="li">X-Content-Type-Options</li>
<li class="li">X-XSS-Protection</li>
<li class="li">HTTP Strict Transport Security </li>
</ul>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Configuration</span>, then click <span class="ph uicontrol">Security kit</span> in
the System section.</span></li>
<li class="li step"><span class="ph cmd">In the <span class="ph uicontrol">Security kit</span> module, configure your website with the security
options available. </span></li>
<li class="li step"><span class="ph cmd">After you have finished configuring the options, click <span class="ph uicontrol">Save
configuration</span>.</span></li>
</ol>
'),
    'pdur.html' => t('<p class="shortdesc">By enabling the Portal Delegated User Registry in the <span class="ph">API Manager</span> UI, you can improve the flexibility
of user registry and account management using the additional configuration options that are
available in the <span class="ph">Developer Portal</span>.</p>
<div class="p">To enable the Portal Delegated User Registry in the <span class="ph">API Manager</span> UI: <ol class="ol"><li class="li">Click <span class="ph uicontrol">Dashboard</span> in the <span class="keyword wintitle">Navigation</span> pane, then click
the catalog for which you want to enable the use of external authentication provider
credentials.</li>
<li class="li">Click <span class="ph menucascade"><span class="ph uicontrol">Settings</span> &gt; <span class="ph uicontrol">Portal</span></span>.</li>
<li class="li">Select the <span class="ph uicontrol">IBM Developer Portal</span> radio button to enable the <span class="ph">Developer Portal</span> site.</li>
<li class="li">Enter the URL of your <span class="ph">Developer Portal</span> site. </li>
<li class="li">In the <span class="ph uicontrol">User Registration and Invitation</span> section, select <span class="ph uicontrol">Portal
Delegated User Registry</span> from the <span class="ph uicontrol">User Registry</span> drop-down list.
The <var class="keyword varname">catalog_name</var> is the name of the catalog that you are working in and applying
the registry settings to. </li>
<li class="li">Click <span class="ph uicontrol">Save</span>.</li>
<li class="li">After a few minutes, you receive an email with a link to your <span class="ph">Developer Portal</span> site for that
catalog. The link is a single use only link for the administrator account. When the link is active
and you have accessed it, you can change the password of this administrator account.</li>
</ol>
</div>
'),
    'social_login.html' => t('<p class="shortdesc">You can use the log in credentials that are used with external authentication providers,
to access the <span class="ph">Developer Portal</span>.
Using external authentication provider credentials reduces the number of authentication credentials
that a user has. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>

<p class="p">You must be the Owner or Administrator of a Developer organization in the <span class="ph">API Manager</span>.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><div class="p">You can use your external authentication provider credentials instead of an LDAP or local user
registry. You can specify the use of authentication credentials for following external
authentication providers: <ul class="ul"><li class="li">Facebook</li>
<li class="li">Google</li>
<li class="li">LinkedIn</li>
<li class="li">Windows Live</li>
<li class="li">Twitter</li>
<li class="li">GitHub</li>
<li class="li">Slack</li>
</ul>
<div class="note note"><span class="notetitle">Note:</span> You can enable multiple external authentication providers at one time.</div>
</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">In the <span class="ph">API Manager</span>
UI:</p><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Enable Portal Delegated User Registry by completing the steps in <a class="xref" href="capic_portal_pdur.html">Portal Delegated User
Registry</a>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">In the <span class="ph">Developer Portal</span>,
log in as an administrator to complete the following steps:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Ensure that the <span class="ph uicontrol">HybridAuth</span> module is enabled. For more information, see
<a class="xref" href="tapim_portal_disable_module.html">Disabling modules</a>,
and enable the module if necessary. </span> When you enable <span class="ph uicontrol">Portal Delegated User Registry</span> in the <span class="ph">API Manager</span> UI, the module is enabled
automatically.</li>
<li class="li substep"><span class="ph cmd">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol">People</span> &gt; <span class="ph uicontrol"> HybridAuth</span></span>.</span></li>
<li class="li substep"><span class="ph cmd">From the list of authentication providers that are displayed, click the check box for the
authentication provider that has the authentication credentials that you want to use, then click
<span class="ph uicontrol">Settings</span>. </span> The <span class="ph uicontrol">Application settings</span> tab contains text fields that must be filled
with specific values. Information on obtaining the specific values for each authentication provider
can be found on their <span class="ph uicontrol">Application settings</span> tabs.</li>
<li class="li substep"><span class="ph cmd">Fill in the required fields for the authentication provider.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span></li>
</ol>
</li>
</ol>
<div class="section result"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Results</h2></div>The icon for the external authentication provider that you have specified is displayed at
the log in screen for the <span class="ph">Developer Portal</span>. By clicking on
the icon, you are directed to the external authentication provider and are requested to enter your
authentication credentials. After you enter and submit the authentication credentials, you are
redirected back to your <span class="ph">Developer Portal</span> site. </div>
'),
    'application_settings_facebook.html' => t('<p class="shortdesc">To enable the use of authentication credentials for Facebook, you must specify the
Application setting values for your Facebook account.</p>
<div class="section"><div class="p"><div class="note note"><span class="notetitle">Note:</span> You must have an account with Facebook to obtain the Application setting values.</div>
</div>

<p class="p">You must enter your <span class="ph uicontrol">Application ID</span> and <span class="ph uicontrol">Application consumer
secret</span> in the corresponding fields. You can obtain these values by completing the
following steps:</p>

<ol class="ol"><li class="li">Creating a new application at <a class="xref" href="https://developers.facebook.com/apps" target="_blank" title="(Opens in a new tab or window)">https://developers.facebook.com/apps</a>. You can gain the details by clicking
<span class="ph uicontrol">+ Add a New App</span>, selecting <span class="ph uicontrol">Website</span> as your platform,
entering the values that satisfy your requirements, then clicking <span class="ph uicontrol">Create App
ID</span>.</li>
<li class="li">In the <span class="ph uicontrol">Tell us about your website</span> section, you must set the
<span class="ph uicontrol">Site URL</span> to <samp class="ph codeph">https://<var class="keyword varname">site_url</var>.com/</samp>, then
click <span class="ph uicontrol">Next</span>.</li>
<li class="li">Click <span class="ph uicontrol">Skip Quick Start</span>, then you can find the <span class="ph uicontrol">Application
ID</span> and <span class="ph uicontrol">Application consumer secret</span> in the
<span class="ph uicontrol">Settings</span> tab of your Facebook App. </li>
<li class="li">In the <span class="ph uicontrol">Basic</span> section in the <span class="ph uicontrol">Settings</span> tab, enter
your <span class="ph uicontrol">App Domain</span> value, which must be in the format of
<samp class="ph codeph">example_app_domain_name.com</samp>, as opposed to
<samp class="ph codeph">www.example_app_domain_name.com</samp>. Click <span class="ph uicontrol">Save Changes</span>.</li>
<li class="li">Click <span class="ph uicontrol">Add Product</span>, then <span class="ph uicontrol">Facebook Login</span>. In the
<span class="ph uicontrol">Valid OAuth redirects URIs</span> field, enter the <span class="ph uicontrol">Callback
URL</span> to
<samp class="ph codeph">https://<var class="keyword varname">site_url</var>.com/hybridauth/endpoint?hauth.done=Facebook</samp>.
Click <span class="ph uicontrol">Save Changes</span>. </li>
<li class="li">Click <span class="ph uicontrol">App Review</span>, then select <span class="ph uicontrol">Yes</span> in response to
<span class="ph uicontrol">Make Appname public?</span>. Click <span class="ph uicontrol">Confirm</span>.</li>
</ol>
</div>
'),
    'application_settings_google.html' => t('<p class="shortdesc">To enable the use of authentication credentials for Google, you must specify the
Application setting values for your Google account.</p>
<div class="section"><div class="note note"><span class="notetitle">Note:</span> You must have an account with Google to obtain the Application setting values.</div>

<div class="p">Enter your Client ID and Client secret. You can get these by completing the following steps:<ol class="ol"><li class="li">Create a new application at <a class="xref" href="https://console.developers.google.com" target="_blank" title="(Opens in a new tab or window)">https://console.developers.google.com</a>. After you have clicked on the link,
click <span class="ph uicontrol">+ Add a New App</span>, then select <span class="ph uicontrol">website</span> as the
platform that you want to get started with.</li>
<li class="li">Click <span class="ph uicontrol">Select a project</span>, then select <span class="ph uicontrol">Create a
project</span> from the drop-down list and enter a name for your project in the
<span class="ph uicontrol">Project name</span> text field. Click <span class="ph uicontrol">Advanced options</span> and
specify your <span class="ph uicontrol">App Engine location</span>, then click
<span class="ph uicontrol">Create</span>.</li>
<li class="li">On the developer homepage, search for and enable the following APIs: <ul class="ul"><li class="li">Contacts API</li>
<li class="li">Google+ API</li>
</ul>
</li>
<li class="li">Click <span class="ph menucascade"><span class="ph uicontrol">Credentials</span> &gt; <span class="ph uicontrol">Create credentials</span> &gt; <span class="ph uicontrol"> OAuth client ID</span> &gt; <span class="ph uicontrol">Configure consent screen</span></span>. Enter the Product name that is show to the user, then click
<span class="ph uicontrol">Save</span>.</li>
<li class="li">Select <span class="ph uicontrol">Web application</span> for the <span class="ph uicontrol">Application type</span>,
then enter a value for the <span class="ph uicontrol">Name</span>.</li>
<li class="li">Set <span class="ph uicontrol">Authorized JavaScript Origins</span> to
<samp class="ph codeph">https://<var class="keyword varname">site_url</var>.com</samp>, <span class="ph uicontrol">Authorized Redirect
URIs</span> to
<samp class="ph codeph">https://<var class="keyword varname">site_url</var>.com/hybridauth/endpoint?hauth.done=Google</samp>,then
click <span class="ph uicontrol">Create</span>. After you have created the application, the client ID and
Client secret is displayed.</li>
</ol>
</div>
</div>
'),
    'application_settings_twitter.html' => t('<p class="shortdesc">To enable the use of authentication credentials for Twitter, you must specify the
Application setting values for your Twitter account.</p>
<div class="section"><div class="note note"><span class="notetitle">Note:</span> You must have an account with Twitter to obtain the Application setting values.</div>

<div class="p">Enter your consumer key and private key. You can get these by completing the following steps: <ol class="ol"><li class="li">Create a new application at <a class="xref" href="https://dev.twitter.com/apps" target="_blank" title="(Opens in a new tab or window)">https://dev.twitter.com/apps</a>. Click <span class="ph uicontrol">Create New
App</span>.</li>
<li class="li">Enter values for the following fields: <ul class="ul"><li class="li">Name</li>
<li class="li">Description</li>
<li class="li">Website (the URL of your <span class="ph">Developer Portal</span> site)</li>
</ul>
</li>
<li class="li">Set <span class="ph uicontrol">Callback URL</span> to
<samp class="ph codeph">https://<var class="keyword varname">site_url</var>.com/hybridauth/endpoint?hauth.done=Twitter</samp>.</li>
<li class="li">Click <span class="ph uicontrol">Yes, I agree</span>, then click <span class="ph uicontrol">Create your user
application</span>.</li>
<li class="li">Click <span class="ph uicontrol">Keys and Access Tokens</span> to display the consumer key and private
key.</li>
</ol>
</div>
</div>
'),
    'application_settings_linkedin.html' => t('<p class="shortdesc">To enable the use of authentication credentials for LinkedIn, you must specify the
Application setting values for your LinkedIn account.</p>
<div class="section"><div class="note note"><span class="notetitle">Note:</span> You must have an account with LinkedIn to obtain the Application setting values.</div>

<div class="p">Enter your Client ID and Client secret. You can get these by completing the following steps: <ol class="ol"><li class="li">Create a new application at <a class="xref" href="https://www.linkedin.com/secure/developer" target="_blank" title="(Opens in a new tab or window)">https://www.linkedin.com/secure/developer</a>. Enter values for the
<span class="ph uicontrol">Company Name</span> and <span class="ph uicontrol">Description</span> text fields.</li>
<li class="li">Add an image for the <span class="ph uicontrol">Application Logo</span>. <div class="note note"><span class="notetitle">Note:</span> The image must be exactly
80x80 pixels.</div>
</li>
<li class="li">Enter values for the following fields: <ul class="ul"><li class="li">Website URL</li>
<li class="li">Business Email</li>
<li class="li">Business Phone</li>
</ul>
</li>
<li class="li">Agree to the <span class="ph uicontrol">LinkedIn API Terms of Use</span>, then click
<span class="ph uicontrol">Submit</span>.</li>
<li class="li">In <span class="ph uicontrol">Default Application Permissions</span>, ensure that
<span class="ph uicontrol">r_basicprofile</span> and <span class="ph uicontrol">r_emailaddress</span> are selected.</li>
<li class="li">Set the <span class="ph uicontrol">OAuth Redirect URL</span> to
<samp class="ph codeph">https://<var class="keyword varname">site_url</var>.com/hybridauth/endpoint?hauth.done=LinkedIn</samp>,
and <span class="ph uicontrol">Integration URL</span> to https://<var class="keyword varname">site_url</var>.com.</li>
<li class="li">Your Client ID and Client secret are displayed.</li>
</ol>
</div>
</div>
'),
    'application_settings_slack.html' => t('<p class="shortdesc">To enable the use of authentication credentials for Slack, you must specify the
Application setting values for your Slack account.</p>
<div class="section"><div class="note note"><span class="notetitle">Note:</span> You must have an account with Slack to obtain the Application setting values. The account must
be authorized to use its authentication credentials to access the <span class="ph">Developer Portal</span> site.</div>

<div class="p">Enter your Client ID and Client secret. You can get these by completing the following steps: <ol class="ol"><li class="li">Create a new application at <a class="xref" href="https://api.slack.com/apps" target="_blank" title="(Opens in a new tab or window)">https://api.slack.com/apps</a>. Click <span class="ph uicontrol">Create App</span>.</li>
<li class="li">Enter a name for your App in the <span class="ph uicontrol">App name</span> field, then select the team
that your account belongs to from the <span class="ph uicontrol">Team</span> drop-down list.</li>
<li class="li">Enter a description of your App in the <span class="ph uicontrol">Short Description</span> field, and set
the <span class="ph uicontrol">Redirect URI(s)</span> to
<samp class="ph codeph">https://<var class="keyword varname">site_url</var>.com/hybridauth/endpoint?hauth.done=Slack</samp>.</li>
<li class="li">Click <span class="ph uicontrol">Create App</span>, and locate the <span class="ph uicontrol">Client ID</span> and
<span class="ph uicontrol">Client secret</span> in the <span class="ph uicontrol">App Credentials</span> section.</li>
</ol>
</div>
</div>
'),
    'application_settings_windows_live.html' => t('<p class="shortdesc">To enable the use of authentication credentials for Windows Live, you must specify the
Application setting values for your Windows Live account.</p>
<div class="section"><div class="note note"><span class="notetitle">Note:</span> You must have an account with Windows Live to obtain the Application setting values.</div>

<div class="p">Enter your Client ID and Client secret. You can get these by completing the following steps:<ol class="ol"><li class="li">Create a new application at <a class="xref" href="https://account.live.com/developers/applications/create" target="_blank" title="(Opens in a new tab or window)">https://account.live.com/developers/applications/create</a>. Click
<span class="ph uicontrol">New Application Registration</span>, then enter a value in the
<span class="ph uicontrol">Name</span> field. </li>
<li class="li">Click <span class="ph uicontrol">Create application</span>.</li>
<li class="li">Click <span class="ph uicontrol">Add Platform</span>, then select <span class="ph uicontrol">Web</span>.</li>
<li class="li">Click <span class="ph uicontrol">Allow Implicit Flow</span>, then set the <span class="ph uicontrol">Redirect
URIs</span> to https://<var class="keyword varname">site_url</var>.com.</li>
<li class="li">Click <span class="ph uicontrol">Save</span>. Your Client ID and Client secret is displayed.</li>
</ol>
</div>
</div>
'),
    'application_settings_github.html' => t('<p class="shortdesc">To enable the use of authentication credentials for GitHub, you must specify the
Application setting values for your GitHub account.</p>
<div class="section"><div class="note note"><span class="notetitle">Note:</span> You must have an account with GitHub to obtain the Application setting values.</div>

<div class="p">Enter your Client ID and Client secret. You can get these by completing the following steps:<ol class="ol"><li class="li">Create a new application at <a class="xref" href="https://github.com/settings/applications/new" target="_blank" title="(Opens in a new tab or window)">https://github.com/settings/applications/new</a>. Click
<span class="ph uicontrol">Register new OAuth application</span> and enter information for the following fields:<ul class="ul"><li class="li">Application name</li>
<li class="li">Homepage URL</li>
</ul>
</li>
<li class="li">Set <span class="ph uicontrol">Authorization Callback URL</span> to
<samp class="ph codeph">https://<var class="keyword varname">site_url</var>.com/hybridauth/endpoint?hauth.done=GitHub</samp></li>
<li class="li">Click <span class="ph uicontrol">Register application</span>. Your Client ID and Client secret are
displayed.</li>
</ol>
</div>
</div>
'),
    'configure_ldap.html' => t('<p class="shortdesc">Your <span class="ph">Developer Portal</span> site can manage users and user registries. Therefore, you can configure your LDAP user registry
in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">By configuring your LDAP user registry in the <span class="ph">Developer Portal</span>, you can enable
more configurable user registries.</p>

<div class="note note"><span class="notetitle">Note:</span> You cannot create users in LDAP.</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Enable Portal Delegated User Registry by completing the steps in <a class="xref" href="capic_portal_pdur.html">Portal Delegated User
Registry</a>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">To enable LDAP configuration in the <span class="ph">Developer Portal</span>: </span> <ol type="a" class="ol substeps"><li class="li substep substepexpand"><span class="ph cmd">Log into your <span class="ph">Developer Portal</span> site as the
administrator.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">On the administrator dashboard, click <span class="ph uicontrol">Modules</span>.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Search for, and enable, the following modules:</span> <ul class="ul"><li class="li">LDAP Servers</li>
<li class="li">LDAP User Module</li>
<li class="li">LDAP Authentication</li>
</ul>
 Then, click <span class="ph uicontrol">Save configuration</span>.</li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">Click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol"> People</span> &gt; <span class="ph uicontrol"> LDAP Configuration</span></span></span></li>
<li class="li step stepexpand"><span class="ph cmd">To configure your LDAP registry settings:</span> <ol type="a" class="ol substeps"><li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Settings</span>, then select the check box in the <span class="ph uicontrol">Require
HTTPS on Credential Pages</span>.</span></li>
<li class="li substep"><span class="ph cmd">Click <span class="ph uicontrol">Save configuration</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">To configure your LDAP registry server: </span> <ol type="a" class="ol substeps"><li class="li substep substepexpand"><span class="ph cmd">Click <span class="ph menucascade"><span class="ph uicontrol">Servers</span> &gt; <span class="ph uicontrol">Add LDAP Server Configuration</span></span>.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">In the <span class="ph uicontrol">Connection settings</span> settings, enter values for the following
fields:</span> <ul class="ul"><li class="li">Machine name for this server configuration</li>
<li class="li">Name</li>
</ul>
</li>
<li class="li substep substepexpand"><span class="ph cmd">Select the check box for <span class="ph uicontrol">Enabled</span>.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Select your type of LDAP server from the <span class="ph uicontrol">LDAP Server Type</span> drop-down
list.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Enter the IP address or domain name of your LDAP server in the <span class="ph uicontrol">LDAP
server</span> text field.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">Enter you port number in the <span class="ph uicontrol">LDAP port</span> text field.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">In the <span class="ph uicontrol">Binding Method</span> section, select <span class="ph uicontrol">Anonymous Bind for
search, then Bind with User Credentials</span>.</span></li>
<li class="li substep substepexpand"><span class="ph cmd">In the <span class="ph uicontrol">LDAP User to Drupal User Relationship</span> section, enter values for
the following fields:</span> <ul class="ul"><li class="li">Base DNs for LDAP users, groups, and other entries</li>
<li class="li">AuthName attribute</li>
</ul>
</li>
<li class="li substep substepexpand">Optional: <span class="ph cmd">Enter values for the following fields:</span> <ul class="ul"><li class="li">AccountName attribute</li>
<li class="li">Email attribute</li>
<li class="li">Email template</li>
<li class="li">Thumbnail attribute</li>
<li class="li">Persistent and Unique User ID attribute</li>
<li class="li">Expression for user DN. Required when &quot;Bind with Users Credentials&quot; method selected</li>
<li class="li">Testing Drupal Username </li>
<li class="li">DN of testing username</li>
</ul>
</li>
<li class="li substep substepexpand"><span class="ph cmd">Click <span class="ph uicontrol">Add</span>.</span></li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">To configure the LDAP Authentication: </span> <ol type="a" class="ol substeps"><li class="li substep substepexpand"><span class="ph cmd">Click the <span class="ph uicontrol">Authentication</span> tab, then in the <span class="ph uicontrol">LDAP Authentication
Settings</span> section, select the check box for your LDAP server that is found under
<span class="ph uicontrol">Authentication LDAP Server Configurations</span>.</span> <div class="note note"><span class="notetitle">Note:</span> If you are using any external authentication providers, or have local <span class="ph">Developer Portal</span> users, the
<span class="ph uicontrol">Mixed mode</span> radio button must be selected. If a user does not exist in an LDAP
registry, their account is created in the local <span class="ph">Developer Portal</span> database. If you
only want to use LDAP, select the <span class="ph uicontrol">Only LDAP Authentication is allowed except for user
1</span> radio button. </div>
</li>
<li class="li substep substepexpand"><span class="ph cmd">In the <span class="ph uicontrol">User Login Interface</span> section, enter values for the following
fields:</span> <ul class="ul"><li class="li">Username Description Text</li>
<li class="li">Password Description Text</li>
<li class="li">LDAP Account User Help URL</li>
<li class="li">LDAP Account User Help Link Text</li>
</ul>
</li>
<li class="li substep substepexpand"><span class="ph cmd">In the <span class="ph uicontrol">Email</span> section, select the check box for <span class="ph uicontrol">Don&apos;t show an
email field on user forms</span>, then click <span class="ph uicontrol">Save</span>.</span></li>
</ol>
</li>
</ol>
'),
    'account_approval.html' => t('<p class="shortdesc">When using the Portal Delegated User Registry, you can specify whether new accounts
require administrator approval.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>

<p class="p">You must have enabled Portal Delegated User Registry in the <span class="ph">API Manager</span> UI to complete the procedure. For
more information, see <a class="xref" href="capic_portal_pdur.html">Portal
Delegated User Registry</a>.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">You can change the account settings to enable account approval for all new accounts including
external authentication providers, or you can change the account settings to enable account approval
for only the external authentication providers. </p>

<div class="p"><div class="note note"><span class="notetitle">Note:</span> You can change the account settings for approving new accounts only if you are using the
Portal Delegated User Registry.</div>
</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ul class="ul steps-unordered"><li class="li step stepexpand"><span class="ph cmd">If you want to change the account settings to enable approval for all new accounts including
external authentication providers:<br></span> <ol class="ol"><li class="li">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol">People</span> &gt; <span class="ph uicontrol">Account settings</span></span>.</li>
<li class="li">In the <span class="ph uicontrol">Registration and cancellation</span> section, select the check box that
satisfies your account approval requirements:<ul class="ul"><li class="li">Administrators only</li>
<li class="li">Visitors</li>
<li class="li">Visitors, but administrator approval is required</li>
</ul>
</li>
<li class="li">To enable the <span class="ph uicontrol">Require e-mail verification when a visitor
creates an account</span> function, select the adjacent check box.</li>
<li class="li">Click <span class="ph uicontrol">Save configuration</span>.</li>
</ol>
</li>
<li class="li step stepexpand"><span class="ph cmd">If you want to change the account settings to enable approval for only external authentication
providers:<br></span> <ol class="ol"><li class="li">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol">People</span> &gt; <span class="ph uicontrol">HybridAuth</span></span>.</li>
<li class="li">In the <span class="keyword wintitle">HybridAuth</span> window, click <span class="ph uicontrol">Account
settings</span>.</li>
<li class="li">In the <span class="ph uicontrol">Account settings</span> section, select the check box that satisfies your
account approval requirements:<ul class="ul"><li class="li">Follow core: Visitors</li>
<li class="li">Visitors</li>
<li class="li">Visitors, but administrator approval is required</li>
<li class="li">Nobody, only login for existing accounts is possible</li>
</ul>
</li>
<li class="li">In the <span class="ph uicontrol">E-mail verification</span> section, select the
check box that satisfies your e-mail verification requirements:<ul class="ul"><li class="li">Follow core: Don&apos;t require e-mail verification</li>
<li class="li">Require e-mail verification</li>
<li class="li">Don&apos;t require email verification</li>
</ul>
</li>
<li class="li">Click <span class="ph uicontrol">Save configuration</span>.</li>
</ol>
</li>
</ul>
'),
    'modify_emails.html' => t('<p class="shortdesc">You can modify the templates that are used for the emails that are sent by the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>

<p class="p">You must have enabled Portal Delegated User Registry in the <span class="ph">API Manager</span> UI to complete the procedure. For
more information, see <a class="xref" href="capic_portal_pdur.html">Portal
Delegated User Registry</a>.</p>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div><p class="p">Token are used in the templates, and are replaced with specified values when an email is
sent.</p>

<div class="p"><div class="note note"><span class="notetitle">Note:</span> You can customize the template that is used for user activation or password reset only if you
are using the Portal Delegated User Registry.</div>
</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">On the administrator dashboard, click <span class="ph menucascade"><span class="ph uicontrol">Configuration</span> &gt; <span class="ph uicontrol">People</span> &gt; <span class="ph uicontrol">Account settings</span></span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">In the <span class="ph uicontrol">E-mails</span> section, you can modify the content of any of the
following template options:</span> <ul class="ul"><li class="li">Welcome (new user created by administrator)</li>
<li class="li">Welcome (awaiting approval)</li>
<li class="li">Welcome (no approval required)</li>
<li class="li">Account activation</li>
<li class="li">Account blocked</li>
<li class="li">Account cancellation confirmation </li>
<li class="li">Account canceled</li>
<li class="li">Password recovery</li>
</ul>
</li>
<li class="li step stepexpand"><span class="ph cmd">After you have modified the template, click <span class="ph uicontrol">Save configuration</span>.</span></li>
</ol>
'),
    'extending.html' => t('<p>If you have very specific requirements for your Developer Portal site, it becomes necessary to extend the provided functionality. This can be achieved simply with easy to use well documented programmatic APIs.</p>
<p>The pages in this section detail how to install additional contributed modules and themes, as well as how to write your own.</p>'),
    'custom_modules.html' => t('<p class="shortdesc">You can create custom modules in the <span class="ph">Developer Portal</span>. Custom modules
can be created to use the custom hooks in the <span class="ph">Developer Portal</span>. </p>
<div class="section"><p class="p">For more information on creating custom modules, see the Drupal developer guide at <a class="xref" href="https://www.drupal.org/documentation/" target="_blank" title="(Opens in a new tab or window)">https://www.drupal.org/documentation/</a>.</p>
By creating custom modules, you can exploit the
following custom hooks that are available in the <span class="ph">Developer Portal</span>:<pre class="pre codeblock"><code>hook_ibm_apim_application_create
hook_ibm_apim_application_delete
hook_ibm_apim_application_edit
hook_ibm_apim_application_clientsecret_reset
hook_ibm_apim_application_clientid_reset
hook_ibm_apim_application_unsubscribe
hook_ibm_apim_application_subscribe
hook_ibm_apim_application_creds_update
hook_ibm_apim_application_creds_create
hook_ibm_apim_application_creds_delete</code></pre>
</div>
'),
    'additional_modules.html' => t('<p class="shortdesc">You can install additional modules in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To install additional modules, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Modules</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Install new modules</span>.</span></li>
<li class="li step"><span class="ph cmd">You can enter a path to the module in the <span class="ph uicontrol">Install from a URL</span> field.
Alternatively, you can upload a module under the <span class="ph uicontrol">Upload a module or theme archive to
install</span> heading.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Install</span>. </span></li>
</ol>
'),
    'disable_module.html' => t('<p class="shortdesc">You can disable an entire module in the <span class="ph">Developer Portal</span> if you want to
improve performance, or remove functionality. </p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Modules</span>.</span></li>
<li class="li step"><span class="ph cmd">Either enter the name of the module that you want to disable in the <span class="ph uicontrol">Filter
list</span> field, or scroll through the list of modules.</span></li>
<li class="li step"><span class="ph cmd">After you have identified the module that you want to disable, set the module to
<span class="ph uicontrol">OFF</span>, then click <span class="ph uicontrol">Save configuration</span>. </span> You have disabled the module. To enable the module, repeat the steps, but set the module
to <span class="ph uicontrol">ON</span> instead of <span class="ph uicontrol">OFF</span>.</li>
</ol>
'),
    'install_adiitional_themes.html' => t('<p class="shortdesc">You can install additional themes in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><p class="li stepsection">To install additional themes, complete the following steps:</p><ol class="ol steps"><li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Appearance</span> in the administrator dashboard.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">+ Install new theme</span>.</span></li>
<li class="li step"><span class="ph cmd">You can enter a path to the theme in the <span class="ph uicontrol">Install from a URL</span> field.
Alternatively, you can upload a theme under the <span class="ph uicontrol">Upload a module or theme archive to
install</span> heading.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Install</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Enable newly added themes</span>.</span></li>
<li class="li step"><span class="ph cmd">Click <span class="ph uicontrol">Enable and set default</span> for the theme that you want to be displayed
in the <span class="ph">Developer Portal</span>.</span> Your theme is displayed in the <span class="ph">Developer Portal</span> when you navigate
to the home page.</li>
</ol>
'),
    'override_content_types.html' => t('<p class="shortdesc">You can apply a modified content type template to override a default content type
template in the <span class="ph">Developer Portal</span>.</p>
<div class="section prereq"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Before you begin</h2></div><p class="p">You must have administrator access to complete this task.</p>

<div class="p">You must also obtain a custom theme and a copy of the existing template. To obtain a copy of the
existing template: <ul class="ul"><li class="li">If you have access to your <span class="ph">Developer Portal</span> node, you can
obtain a copy of the template by following the path:
<span class="ph filepath">/var/aegir/platforms/{platformname}/sites/all/modules/ibm_apim/templates</span>.</li>
<li class="li">If you do not have access to your <span class="ph">Developer Portal</span> node,you can
download a copy of the template, here: <a class="xref" href="https://github.com/ibm-apiconnect/devportal/tree/master/ibm_apim/templates" target="_blank" title="(Opens in a new tab or window)">https://github.com/ibm-apiconnect/devportal/tree/master/ibm_apim/templates</a></li>
</ul>
</div>
</div>
<div class="section context"><div class="tasklabel"><h2 class="sectiontitle tasklabel">About this task</h2></div>After you have applied a modified content types template, the changes will take precedence
over the default content types template.<div class="note note"><span class="notetitle">Note:</span> You cannot modify any files on the file system of the
<span class="ph">Developer Portal</span>.</div>
</div>
<div class="tasklabel"><h2 class="sectiontitle tasklabel">Procedure</h2></div><ol class="ol steps"><li class="li step stepexpand"><span class="ph cmd">Apply any modifications to the default template that you have obtained. </span> <div class="note note"><span class="notetitle">Note:</span> You must check the templates in the latest API manager releases to check whether you need to
make equivalent changes to your overrides to maintain functional equivalence.</div>
</li>
<li class="li step stepexpand"><span class="ph cmd">Create a directory in the custom theme and name it <span class="ph filepath">templates</span>.</span></li>
<li class="li step stepexpand"><span class="ph cmd">Add your modified template to <span class="ph filepath">templates</span>.</span> <div class="note note"><span class="notetitle">Note:</span> The modified template file name must be identical to the name of the template file that it is
overriding. </div>
</li>
<li class="li step stepexpand"><span class="ph cmd">Install, enable and set the custom theme with the modified template as default in the <span class="ph">Developer Portal</span>. For more
information, see <a class="xref" href="tapim_portal_install_adiitional_themes.html" title="You can install additional themes in the Developer Portal.">Installing additional themes</a></span></li>
</ol>
<div class="section result"><div class="tasklabel"><h2 class="sectiontitle tasklabel">Results</h2></div>You have modified and applied a template to override the default content types in the <span class="ph">Developer Portal</span>.</div>
'),
    'tutorial_portal_customizing_css.html' => t('<p class="shortdesc">You can configure an <span class="ph filepath">overrides.css</span> file in a theme to customize the
appearance of your <span class="ph">Developer Portal</span> home
page.</p>
<div class="section"><h2 class="title sectiontitle">Before you begin</h2>
<p class="p">You must have administrator access to complete this task.</p>

<p class="p">Download the pre-supplied sample theme, <a class="xref" href="banka_connect_theme_custom_css.zip">banka_connect_theme_custom_css.zip</a>.</p>
</div>
<div class="section"><h2 class="title sectiontitle">Customizing the <span class="ph filepath">overrides.css</span> file</h2>
<ol class="ol"><li class="li liexpand">Download the <span class="ph filepath">overrides.css</span> file from the pre-supplied sample theme from
<span class="ph filepath">\banka_connect_theme\css</span>, and open it in your chosen editor.</li>
<li class="li liexpand">Customize the <span class="ph filepath">overrides.css</span> file by entering hex codes, and pixel values
when applicable, in the correct positions for the following elements: <dl class="dl"><dt class="dt dlterm">Body of the home page</dt>
<dd class="dd"><pre class="pre codeblock"><code>body.page-home #columns.no-menu-bar {		
  background-color: #fff;</code></pre>
</dd>
<dt class="dt dlterm">Header of the home page</dt>
<dd class="dd"><pre class="pre codeblock"><code>#page &gt; header {								
  background-color: #a0a0a0;</code></pre>
</dd>
<dt class="dt dlterm">Menu bar and border</dt>
<dd class="dd"><pre class="pre codeblock"><code>#menu-bar {									
  background-color: #a0a0a0;
  border-bottom: 10px solid #ff9900;</code></pre>
</dd>
<dt class="dt dlterm">Border of the footer</dt>
<dd class="dd"><pre class="pre codeblock"><code>#page &gt; footer, #page &gt; footer .block-title {			
  border-top: 10px solid #ff9900;
  background-color: #dee0e2;</code></pre>
</dd>
<dt class="dt dlterm">Footer</dt>
<dd class="dd"><pre class="pre codeblock"><code>#page &gt; footer a {
  color: #454A4C;</code></pre>
</dd>
</dl>
</li>
<li class="li liexpand">After you have entered the hex codes to complete the elements, save the
<span class="ph filepath">overrides.css</span> file.</li>
</ol>
</div>
<div class="section"><h2 class="title sectiontitle">Implementing the customized <span class="ph filepath">overrides.css</span> file</h2></div>
<div class="section"><ol class="ol"><li class="li liexpand">After you have finished customizing the <span class="ph filepath">overrides.css</span> file, add it back to
the compressed file in <span class="ph filepath">\banka_connect_theme\css</span>. </li>
<li class="li liexpand">Install the compressed file as a theme in the <span class="ph">Developer Portal</span>. For more
information, see <a class="xref" href="../com.ibm.apic.devportal.doc/tapim_portal_install_adiitional_themes.html">Installing additional themes</a>.</li>
<li class="li liexpand">Return to the <span class="ph">Developer Portal</span> home page by
clicking the <span class="ph uicontrol">Home</span> icon <img class="image" src="tutorial_portal_home_icon.jpg" alt="The Home Icon"> from the administrator dashboard. You can now see your custom theme. <div class="image"><img class="image" src="tutorial_portal_admin_custom_css.jpg" alt="The Developer Portal home page with an installed custom theme."></div></li>
</ol>
</div>
<div class="section"><h2 class="title sectiontitle">What you did in this tutorial</h2>
<div class="p">In this tutorial, you have completed the following tasks:<ul class="ul"><li class="li">Customized the elements in an <span class="ph filepath">overrides.css</span> file with hex codes and pixel
values.</li>
<li class="li">Installed the theme with the customized <span class="ph filepath">overrides.css</span> file in the <span class="ph">Developer Portal</span>.</li>
</ul>
</div>
</div>
')
  );


  return $content;
}
