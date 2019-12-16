## API Connect Portal NLS Export Scripts

There are several parts to generating the content which is to be translated for the API Connect portal. This directory 
contains the scripts which provide the inputs to a drush command which will produce the final set of files which 
represent the complete set of files which need to be translated.

### Overall process of generating the content for translation

To generate all of the content there are several scripts/ drush commands involved, the following table documents these 
and more detail can be seen below.

| Script/ Command  | Purpose | 
| ---------------- | ------- | 
| download_public_drupal_translations.sh |  Download all existing translations for modules/ themes the portal uses. |
| export_pots.sh | Export all of the current translatable strings from the modules/ themes the portal uses. |
| drush nlsexport | Generate the content for translation, including the raw .pot files (templates === English only scripts), .po files for existing translations (which become translation memories), .po files which contain just the set of strings which still require translation. | 

### download_public_drupal_translations.sh

Lists all of the modules known about (both enabled and not) then attempts to download the existing translations from ftp.drupal.org (translation site to browse is https://localize.drupal.org/).

It downloads the files to `/tmp/translation_files/existing_drupal_pos`

Note: if `drush pm-list` doesn't return a version for the module then it is skipped.

### export_pots.sh

Dependent on the potx module (https://www.drupal.org/project/potx). This is bundled on the portal but not enabled so you will need to do this before running this script, use `drush en potx` or the GUI to do this. 

Usage = `export_pots.sh PLATFORM_DIR` i.e. export_pots.sh \`drush dd\` 

Loops over several lists of modules/ themes:
- Non-core modules - produce a .pot file per module.
- Core modules - produce a single .pot file for all core modules (this is the format downloaded from ftp.drupal.org)
- All themes - Required as potx has a --modules option but not themes so we have to work off paths for these.

It exports a complete set of untranslated strings to `/tmp/translation_files/required_pots`

### drush nlsexport

This command generates a set of files required for translation by the IBM translation centres. It takes the outputs from the previous scripts as its input - the defaulted parameters are the locations listed as the output dirs for the above command. It also has a defaulted parameter for output = `/tmp/translation_files/output`.
 
This uses the output of export_pots.sh as the definitive list of what needs to be translated.

1. For each project (which is a more general term for modules and themes in drupal), an output directory named \<projectname\>-\<projectversion\> is created.
1. The original .pot file is always copied into the output directory.
1. Then for each language it checks whether there is any existing translations from either:

- the public drupal site (i.e. downloaded from download_existing_transtlations.sh)
- in the sites/all/translations directory. These are the most up to date translations that we have.

If these exist then they are merged (with the public translations taking precedence) and used to diff against the .pot files to produce the following files:

| Output filename | Description |
| --- | --- |
| \<projectname\>-\<projectversion\>-memories.\<lang\>.po | current translations to be loaded as memories in the translation centres. |
| \<projectname\>-\<projectversion\>-translationrequired.\<lang\>.po | the set of strings which require translation. |

i.e. diff-8.x-1.0-rc1-memories.de.po and diff-8.x-1.0-rc1-translationrequired.de.po

Notes:
- If you see memories files but no translationrequired for a project, then this signifies that we have translations for all of the strings already so no need to translate anything.

### Subsequent steps

Zip up the complete contents of the /tmp/translation_files/output directory, this is the file which needs to be sent off for translation.
