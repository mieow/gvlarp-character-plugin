# Instructions Using Wordpress #

  1. Export the latest release
```
svn export http://gvlarp-character-plugin.googlecode.com/svn/tags/version1-10 gvlarp-character
```
  1. Zip the folder
  1. Log into the admin account on the wordpress website
  1. De-activate the character plugin, if already installed
  1. Delete the plugin entirely (don't worry - you aren't deleting the database tables so nothing will be lost)
  1. Add New Plugin -> upload
  1. Activate Plugin

# Instructions Using FTP #

  1. Deactivate the plugin via Wordpress admin
  1. Export the latest release
```
svn export http://gvlarp-character-plugin.googlecode.com/svn/tags/version1-10 gvlarp-character
```
  1. Upload the exported files to `/public_html/wp-content/plugins/gvlarp-character/`
  1. Re-activate the plugin

# After Installation #

  1. Go to Characters->Configuration. Check any new options and click \[options\](save.md) and any other save button with new options/sections
  1. Go through version log and make any appropriate page content/data table/menu updates


## V1.10 to V1.11 ##

  * Review each of the character generation templates and update settings

## V1.9 to V1.10 ##

  * Fix lost PDF colours on Config page
  * Configure Character Generation options
  * Check and re-save each of your cost models
  * Update the Wordpress Role for each clan
  * Configure any extra columns you want on the sign-in report
  * Set up Character Generation templates

  * Update to latest Downloads plugin, upload and set up templates

## V1.8 to V1.9 ##

  * New options in configuration to update
  * google feeding map options
  * Remove Player Admin page
  * Remove Path Upadate page/shortcode
  * Remove Change WP pages
  * Empty page contents for View Character
  * Empty page contents for Profile
  * Add backgrounds widget

  * Setup google map
  * Willpower Spend button and stat table