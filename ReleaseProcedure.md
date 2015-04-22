# Introduction #

This is the process to go through before the version is available for release


# Details #

  1. HTML Validation
  1. Check formatting in latest versions of Firefox, IE and Chrome
  1. Prepare summary of changes, and update in
    1. Web page
    1. Top level PHP file
    1. List of Tests to perform
  1. Prepare summary of database changes - Top level PHP fle
  1. Update initial data tables
  1. Change table prefix and recreate tables from scratch, with imported data
    * Create a new character with everything in it
    * Check that it saves
    * Check that it shows up on reports
    * Check printable PDF, XP Spend, etc pages
    * Check that data tables are initialised correctly
  1. Load in snapshot/database state & data from the last release and repeat testing from previous step. Also check:
    * All database changes have actually happened and no error messages
  1. Testing
  1. Take a snapshot of the test and live databases
  1. SVN Tag (example)
```
cd gvlarp-character-plugin
svn update
svn copy gvlarp-character https://gvlarp-character-plugin.googlecode.com/svn/tags/version1-10 -m "Tagging the v1.10 release of the character plugin."
```