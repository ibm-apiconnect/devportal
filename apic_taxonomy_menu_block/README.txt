APIC TAXONOMY MENU BLOCK
*******************

This module is a fork of the Taxonomy Menu Block module from: https://www.drupal.org/project/taxonomy_menu_block

It was forked in order to ensure APIC ACLs were enforced.

------------

This module allows you to make menu blocks out of your taxonomies. 
A Taxonomy Menu Block will output a (nested) unordered list of term names, 
linked to that term's detail page.
Install the module and go to admin/structure/block 
to start adding Taxonomy Menu Blocks

Options:
--------
1. Display the whole tree
Displays the whole vocabulary. 

2. Fixed parent
Render only the sub-terms of a certain parent. 

3. Dynamic parent
Only the currently active branch will be displayed. For example, 
if we have the following vocabulary:

- Term one
- Term two
-- Term three
-- Term four
-- -- Term five
-- -- Term six
- Term seven
-- Term eight
-- Term nine

If you are viewing Term two or any sub-term of Term two or a node coupled 
to Term two or any sub-term of Term two, all the children of Term two 
are considered the currently active branch, and will be displayed.

Visibility of blocks are controlled via the standard Block visibility settings. 
"Dynamic parent" blocks will only show on term pages (taxonomy/term/%) and 
certain node pages, depending if those nodes have a term reference field 
referencing to the vocabulary you chose in your configuration.

Functionalities:
----------------
* Active trail is followed, even on nodes attached to a certain term
* Works on multilingual sites
* Ability to alter rendered data through hooks, see .api.php file
* Lightweight because of smart caching

