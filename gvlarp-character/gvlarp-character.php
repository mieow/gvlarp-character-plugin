<?php
    /*  Plugin Name: GVLarp Character Plugin
        Plugin URI: http://www.gvlarp.com/character-plugin
        Description: Plugin to store and display PCs and NPCs of GVLarp
        Author: Lambert Behnke
        Version: 1.6.0
        Author URI: http://www.gvlarp.com
    */

    /*  Copyright 2013  Lambert Behnke  (email : Lambert.Behnke@gmail.com)

        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License, version 2, as
        published by the Free Software Foundation.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
    */

    /*  Version 1.0   Initial Release
        Version 1.1   Character Admin added, various bug fixes, XP spend table limit, status table
        Version 1.2   Preferred values hardcoded as initially selected, Player Admin introduced, added ability
                      for STs to see Character Sheet and other pages as selected character, officials
                      selectable by court or by court and position.
        Version 1.2.1 Prevent a player from being added with name New Player
        Version 1.2.2 Put ST Links into a DB Table
        Version 1.3.0 DB description size increases, book ref for paths, rituals, merits/flaw, Improve XP
                      spends, User XP spends, ST XP spend approvals, Humanity changes expansion, Character
                      edit improvements, add submit button to top.
        Version 1.3.1 Add training note to XP Spends, Hide inactive characters from Prestige/Master Path table
        Version 1.3.2 Profile, CSS class ids, Single width skill choice in edit character with separate groups
                      for Talents, Skills, Knowledges
        Version 1.4.0 Fixed CLAN table link column, create default entry in ROAD_OR_PATH on character creation
        Version 1.5.0 Character Admin default selection (PC, Not Visible, View Sheet), Character Edit increase
                      Harpy Quote to textarea, longer box for Portrait URL, Portrait (Show cstatus/comment,
                      placeholder image, option to change Display Name/Password), Fixed status list (display
                      zeros & dead characters), Added Clan Discipline Discount Configuration (simple),
                      Obituary Page, Merit/Flaw can be bought (off) with XP, Escape single/double quotes in
                      harpy comment. CSS classes and ids added in remaining tables
        Version 1.6.0 Specialisations and multiple versions of same skill during XP spend, High and Low Gen
                      support on xp spend. Added Temporary Blood and Temporary Willpower master table, Status
                      table only show active characters, On Clan Prestige Table add court, allow 5 rituals to
                      be added at the same time, master path table exclude not visible chars and make "Path
                      Change" default option, Prestige List character name links to profile, On XP Approval
                      and Profile character name links to character sheet for STs only, create monthly WP gain
                      table

        Comments:
        On XP Expenditure page
            Only show hidden skills (e.g. Dodge) if they already have it on their sheet
            Works ... VISIBLE=N

        On Master Path table:
            Only Show PCs (This was in list for last version � thought I�d checked it)
            Works ... add group=PC

        Future Development / Outstanding
    On Character Admin, after you have clicked the �Display Characters� button to take you to a character list:
        Instead of selecting a character with a radio button, then clicking the button to select, have the character
        name as a link that takes you directly to the page
    Add a WP Admin Page for editing/adding/etc
    (have a look at my theme code and wordpress.org for info on doing admin pages. It�s pretty easy to get a basic page there.):
        The configuration table
        Merits and Flaws
        Rituals
    Expanded Backgrounds � Characters can: (design will be provided by Jane)
        Update their born/died dates
        Explain their Merits and Flaws
        Explain their Clan Flaw
        List their ghouls
        List their havens
        List their Contacts
        List their Allies
        List their Retainers
        List any buildings they own and businesses they control
        List any items they own (e.g. artifacts, occult items, weapons)
        Character History/back story
    Expanded Backgrounds � additional info (design will be provided by Jane)
        Any changes need to be approved by storytellers
        Show differences between old and new entries, if possible
        Display background sections in tabs
        Insert XP spend table as an additional tab

            Version 1.7.0 Existing Spends on XP Spend,
            Version 2.0.0 Player Character Creator
         */

    /*
            DB Changes: 1.6.0 Add    MULTIPLE to skill
                                     SPECIALISATION_AT to skill
                                     SPECIALISATION_AT to stat
                                     SPECIALISATION to pending_xp_spend
                                     MAX_DISCIPLINE to generation
                                     TEMPORARY_STAT
                                     CHARACTER_TEMPORARY_STAT
                                     TEMPORARY_STAT_REASON
                                     EXTENDED_CHARACTER_BACKGROUND
                              Change COST_MODEL_STEPS for spends above 5
         */
define( 'GVLARP_CHARACTER_URL', plugin_dir_path(__FILE__) );define( 'GVLARP_TABLE_PREFIX', $wpdb->prefix . "GVLARP_" );require GVLARP_CHARACTER_URL . 'inc/adminpages.php';
require GVLARP_CHARACTER_URL . 'inc/install.php';
    function print_character_stats($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $grouping_sector = "";
        $output    = "";
        $sqlOutput = "";
        if ($group != "") {
            $sql = "SELECT stat.name, cha_stat.comment, cha_stat.level
                            FROM " . $table_prefix . "CHARACTER_STAT cha_stat,
                                 " . $table_prefix . "STAT stat,
                                 " . $table_prefix . "CHARACTER chara
                            WHERE cha_stat.STAT_ID      = stat.ID
                              AND cha_stat.CHARACTER_ID = chara.ID
                              AND chara.DELETED != 'Y'
                              AND chara.WORDPRESS_ID = %s
                              AND stat.grouping = %s
                           ORDER BY stat.grouping, stat.ordering";

            $character_stats = $wpdb->get_results($wpdb->prepare($sql, $character, $group));
            $grouping_sector = "_" . $group;
        }
        else {
            $sql = "SELECT stat.name, cha_stat.comment, cha_stat.level
                            FROM " . $table_prefix . "CHARACTER_STAT cha_stat,
                                 " . $table_prefix . "STAT stat,
                                 " . $table_prefix . "CHARACTER chara
                            WHERE cha_stat.STAT_ID      = stat.ID
                              AND cha_stat.CHARACTER_ID = chara.ID
                              AND chara.DELETED != 'Y'
                              AND chara.WORDPRESS_ID = %s
                           ORDER BY stat.grouping, stat.ordering";

            $character_stats = $wpdb->get_results($wpdb->prepare($sql, $character));
        }

        foreach ($character_stats as $current_stat) {
            $sqlOutput .= "<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_stat->name    . "</td>
                                   <td class=\"gvcol_2 gvcol_spec\">" . $current_stat->comment . "</td>
                                   <td class=\"gvcol_3 gvdot_";
            if ($current_stat->name == 'Willpower') {
                $sqlOutput .= "10";
            }
            else {
                $sqlOutput .=  "5";
            }
            $sqlOutput .= "_" . $current_stat->level . "\"></td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_cstb". $grouping_sector . "\">" . $sqlOutput . "</table>";
        }
        else {
            $output = "";
        }

        return $output;
    }
    add_shortcode('character_stat_block', 'print_character_stats');

    function print_character_skills($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";
        if ($group != "") {
            $sql = "SELECT skill.name, cha_skill.comment, cha_skill.level
                            FROM " . $table_prefix . "CHARACTER_SKILL cha_skill,
                                 " . $table_prefix . "SKILL skill,
                                 " . $table_prefix . "CHARACTER chara
                            WHERE cha_skill.SKILL_ID     = skill.ID
                              AND cha_skill.CHARACTER_ID = chara.ID
                              AND chara.DELETED != 'Y'
                              AND chara.WORDPRESS_ID = %s
                              AND skill.GROUPING     = %s
                           ORDER BY skill.grouping, skill.name";

            $character_skills = $wpdb->get_results($wpdb->prepare($sql, $character, $group));
        }
        else {
            $sql = "SELECT skill.name, cha_skill.comment, cha_skill.level
                            FROM " . $table_prefix . "CHARACTER_SKILL cha_skill,
                                 " . $table_prefix . "SKILL skill,
                                 " . $table_prefix . "CHARACTER chara
                            WHERE cha_skill.SKILL_ID     = skill.ID
                              AND cha_skill.CHARACTER_ID = chara.ID
                              AND chara.DELETED != 'Y'
                              AND chara.WORDPRESS_ID = %s
                           ORDER BY skill.grouping, skill.name";

            $character_skills = $wpdb->get_results($wpdb->prepare($sql, $character));
        }

        foreach ($character_skills as $current_skill) {
            $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_skill->name    . "</td>
                                      <td class=\"gvcol_2 gvcol_spec\">" . $current_skill->comment . "</td>
                                      <td class=\"gvcol_3 gvdot_5_"      . $current_skill->level   . "\"></td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_cskb\">" . $sqlOutput . "</table>";
        }
        else {
            $output = "";
        }

        return $output;
    }
    add_shortcode('character_skill_block', 'print_character_skills');

    function print_character_details($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";

        $sql = "SELECT chara.name char_name,
                               pub_clan.name pub_clan,
                               priv_clan.name priv_clan,
                               chara.date_of_birth,
                               chara.date_of_embrace,
                               gen.name gen,
                               gen.bloodpool,
                               gen.blood_per_round,
                               chara.sire,
                               status.name status,
                               chara.character_status_comment status_comment,
                               court.name court,
                               path.name path_name,
                               path_totals.path_value
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CLAN pub_clan,
                             " . $table_prefix . "CLAN priv_clan,
                             " . $table_prefix . "GENERATION gen,
                             " . $table_prefix . "CHARACTER_STATUS status,
                             " . $table_prefix . "COURT court,
                             " . $table_prefix . "ROAD_OR_PATH path,
                             (SELECT character_path.character_id, SUM(character_path.amount) path_value
                              FROM " . $table_prefix . "CHARACTER_ROAD_OR_PATH character_path
                              GROUP BY character_path.character_id) path_totals
                        WHERE chara.WORDPRESS_ID = %s
                          AND chara.public_clan_id      = pub_clan.id
                          AND chara.private_clan_id     = priv_clan.id
                          AND chara.generation_id       = gen.id
                          AND chara.DELETED != 'Y'
                          AND chara.character_status_id = status.id
                          AND chara.court_id            = court.id
                          AND chara.road_or_path_id     = path.id
                          AND chara.id                  = path_totals.character_id";

        $character_details = $wpdb->get_row($wpdb->prepare($sql, $character));

        if ($group == "") {
            $output  = "<table class='gvplugin' id=\"gvid_cdb\"><tr><td class=\"gvcol_1 gvcol_key\">Character_name</td><td class=\"gvcol_2 gvcol_val\">" . $character_details->char_name       . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Public Clan</td><td class=\"gvcol_2 gvcol_val\">"           . $character_details->pub_clan        . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Private Clan</td><td class=\"gvcol_2 gvcol_val\">"          . $character_details->priv_clan       . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Date of Birth</td><td class=\"gvcol_2 gvcol_val\">"         . $character_details->date_of_birth   . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Date of Embrace</td><td class=\"gvcol_2 gvcol_val\">"       . $character_details->date_of_embrace . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Generation</td><td class=\"gvcol_2 gvcol_val\">"            . $character_details->gen             . "th</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Max Bloodpool</td><td class=\"gvcol_2 gvcol_val\">"         . $character_details->bloodpool       . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Max blood per round</td><td class=\"gvcol_2 gvcol_val\">"   . $character_details->blood_per_round . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Sire's Name</td><td class=\"gvcol_2 gvcol_val\">"           . $character_details->sire            . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Character Status</td><td class=\"gvcol_2 gvcol_val\">"      . $character_details->status          . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Status Comment</td><td class=\"gvcol_2 gvcol_val\">"        . $character_details->status_comment  . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Current Court</td><td class=\"gvcol_2 gvcol_val\">"         . $character_details->court           . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Road or Path name</td><td class=\"gvcol_2 gvcol_val\">"     . $character_details->path_name       . "</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Road or Path rating</td><td class=\"gvcol_2 gvcol_val\">"   . $character_details->path_value      . "</td></tr>";
            $output .= "</table>";
        }
        else {
            $output = "<span class=\"gvcol_val\" id=\"gvid_cdeb_" . $group . "\">" . $character_details->$group . "</span>";
        }

        return $output;
    }
    add_shortcode('character_detail_block', 'print_character_details');

    function print_character_disciplines($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";
        $sqlComboOutput = "";

        $sql = "SELECT dis.name, cha_dis.comment, cha_dis.level
                        FROM " . $table_prefix . "DISCIPLINE dis,
                             " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
                             " . $table_prefix . "CHARACTER chara
                        WHERE dis.ID = cha_dis.DISCIPLINE_ID
                          AND cha_dis.CHARACTER_ID = chara.ID
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                        ORDER BY dis.name";

        $character_disciplines = $wpdb->get_results($wpdb->prepare($sql, $character));

        foreach ($character_disciplines as $current_discipline) {
            $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_discipline->name    . "</td>
                                      <td class=\"gvcol_2 gvcol_spec\">" . $current_discipline->comment . "</td>
                                      <td class=\"gvcol_3 gvdot_5_"      . $current_discipline->level   . "\"></td></tr>";
        }

        $sql = "SELECT combo_dis.name, cha_combo_dis.comment
                        FROM " . $table_prefix . "COMBO_DISCIPLINE combo_dis,
                             " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE cha_combo_dis,
                             " . $table_prefix . "CHARACTER chara
                        WHERE combo_dis.ID = cha_combo_dis.COMBO_DISCIPLINE_ID
                          AND cha_combo_dis.CHARACTER_ID = chara.ID
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                        ORDER BY combo_dis.name";

        $character_combo_disciplines = $wpdb->get_results($wpdb->prepare($sql, $character));

        foreach ($character_combo_disciplines as $current_combo_discipline) {
            $sqlComboOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_combo_discipline->name    . "</td>
                                       <td class=\"gvcol_2 gvcol_spec\">" . $current_combo_discipline->comment . "</td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_cdib\">" . $sqlOutput;
            if ($sqlComboOutput != "") {
                $output .= "<tr><td colspan=3><table class='gvplugin' id=\"gvid_ccd\">" . $sqlComboOutput . "</table></td></tr>";
            }
            $output .= "</table>";
        }

        return $output;
    }
    add_shortcode('character_discipline_block', 'print_character_disciplines');

    function print_character_paths($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";

        $sql = "SELECT path.name, cha_path.comment, cha_path.level
                        FROM " . $table_prefix . "CHARACTER_PATH cha_path,
                             " . $table_prefix . "PATH path,
                             " . $table_prefix . "DISCIPLINE dis,
                             " . $table_prefix . "CHARACTER chara
                        WHERE cha_path.CHARACTER_ID = chara.ID
                          AND cha_path.path_id = path.id
                          AND path.DISCIPLINE_ID = dis.id
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                        ORDER BY dis.name, path.name";

        $character_paths = $wpdb->get_results($wpdb->prepare($sql, $character));

        foreach ($character_paths as $current_path) {
            $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_path->name    . "</td>
                                      <td class=\"gvcol_2 gvcol_spec\">" . $current_path->comment . "</td>
                                      <td class=\"gvcol_3 gvdot_5_"      . $current_path->level   . "\"></td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_cpb\">" . $sqlOutput . "</table>";
        }
        else {
            $output = "";
        }

        return $output;
    }
    add_shortcode('character_path_block', 'print_character_paths');

    function print_character_rituals($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";

        if ($group == "Overview" || $group == "") {

            $sql = "SELECT rit.name, cha_rit.comment, cha_rit.level
                            FROM " . $table_prefix . "DISCIPLINE dis,
                                 " . $table_prefix . "CHARACTER_RITUAL cha_rit,
                                 " . $table_prefix . "RITUAL rit,
                                 " . $table_prefix . "CHARACTER chara
                            WHERE dis.ID = rit.DISCIPLINE_ID
                              AND cha_rit.CHARACTER_ID = chara.ID
                              AND cha_rit.ritual_id = rit.id
                              AND chara.DELETED != 'Y'
                              AND chara.WORDPRESS_ID = %s
                            ORDER BY dis.name, rit.level, rit.name";

            $character_rituals = $wpdb->get_results($wpdb->prepare($sql, $character));

            foreach ($character_rituals as $current_ritual) {
                $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_ritual->name    . "</td>
                                          <td class=\"gvcol_2 gvcol_spec\">" . $current_ritual->comment . "</td>
                                          <td class=\"gvcol_3 gvcol_val\">"  . $current_ritual->level   . "</td></tr>";
            }
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_crb\">" . $sqlOutput . "</table>";
        }
        else {
            $output = "";
        }

        return $output;
    }
    add_shortcode('character_ritual_block', 'print_character_rituals');

    function print_character_merits($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";
        $sql = "SELECT merit.name, cha_merit.comment, cha_merit.level
                        FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
                             " . $table_prefix . "MERIT merit,
                             " . $table_prefix . "CHARACTER chara
                        WHERE cha_merit.MERIT_ID     = merit.ID
                          AND cha_merit.CHARACTER_ID = chara.ID
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                       ORDER BY merit.value DESC, merit.name";

        $character_merits = $wpdb->get_results($wpdb->prepare($sql, $character));

        foreach ($character_merits as $current_merit) {
            $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_merit->name    . "</td>
                                      <td class=\"gvcol_2 gvcol_spec\">" . $current_merit->comment . "</td>
                                      <td class=\"gvcol_3 gvcol_val\">"  . $current_merit->level   . "</td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_cmb\">" . $sqlOutput . "</table>";
        }
        else {
            $output = "";
        }

        return $output;
    }
    add_shortcode('character_merit_block', 'print_character_merits');

    function print_character_offices($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";
        $sql = "SELECT office.name office_name, court.name court_name, coffice.comment
                        FROM " . $table_prefix . "CHARACTER_OFFICE coffice,
                             " . $table_prefix . "OFFICE office,
                             " . $table_prefix . "COURT court,
                             " . $table_prefix . "CHARACTER chara
                        WHERE coffice.OFFICE_ID    = office.ID
                          AND coffice.CHARACTER_ID = chara.ID
                          AND coffice.COURT_ID     = court.ID
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                       ORDER BY office.ordering, office.name, court.name";

        $character_offices = $wpdb->get_results($wpdb->prepare($sql, $character));

        foreach ($character_offices as $current_office) {
            $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_office->office_name . "</td>
                                      <td class=\"gvcol_2 gvcol_val\">"  . $current_office->court_name  . "</td>
                                      <td class=\"gvcol_3 gvcol_spec\">" . $current_office->comment     . "</td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_cob\">" . $sqlOutput . "</table>";
        }
        else {
            $output = "";
        }

        return $output;
    }
    add_shortcode('character_offices_block', 'print_character_offices');

    function print_character_backgrounds($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";
        $sql = "SELECT back.name, cha_back.comment, cha_back.level
                        FROM " . $table_prefix . "CHARACTER_BACKGROUND cha_back,
                             " . $table_prefix . "BACKGROUND back,
                             " . $table_prefix . "CHARACTER chara
                        WHERE cha_back.BACKGROUND_ID = back.ID
                          AND cha_back.CHARACTER_ID  = chara.ID
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                       ORDER BY back.name";

        $character_backgrounds = $wpdb->get_results($wpdb->prepare($sql, $character));

        foreach ($character_backgrounds as $current_background) {
            $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_background->name    . "</td>
                                      <td class=\"gvcol_2 gvcol_spec\">" . $current_background->comment . "</td>
                                      <td class=\"gvcol_3 gvdot_5_"      . $current_background->level   . "\"></td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_cbb\">" . $sqlOutput . "</table>";
        }
        else {
            $output = "";
        }

        return $output;
    }
    add_shortcode('character_background_block', 'print_character_backgrounds');

    function print_character_temp_stats($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "stat" => "Willpower"), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sqlOutput = "";
        $sql = "SELECT char_temp_stat.character_id, SUM(char_temp_stat.amount) total_temp_stat
                FROM " . $table_prefix . "CHARACTER_TEMPORARY_STAT char_temp_stat,
                     " . $table_prefix . "CHARACTER chara,
                     " . $table_prefix . "TEMPORARY_STAT tstat
                WHERE char_temp_stat.character_id      = chara.id
                  AND char_temp_stat.temporary_stat_id = tstat.id
                  AND tstat.name         = %s
                  AND chara.WORDPRESS_ID = %s
                GROUP BY char_temp_stat.character_id, char_temp_stat.temporary_stat_id";

        $character_temp_stats = $wpdb->get_results($wpdb->prepare($sql, $stat, $character));

        foreach ($character_temp_stats as $current_temp_stat) {
            $sqlOutput = $current_temp_stat->total_temp_stat;
        }

        $output = "";
        if ($sqlOutput != "") {
            if ($stat == "Willpower") {
                $output = "<span id=\"gvid_ctw_willpower\" class=\"gvcol_val\">" . $sqlOutput . "</span>";
            }
            else if ($stat == "Blood") {
                $output = "<span id=\"gvid_ctw_bloodpool\" class=\"gvcol_val\">" . $sqlOutput . "</span>";
            }
        }
        else {
            $output = "";
        }

        return $output;
    }
    add_shortcode('character_temp_stats', 'print_character_temp_stats');

    function print_office_block($atts, $content=null) {
        extract(shortcode_atts(array ("court" => "Glasgow", "office" => ""), $atts));

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";

        $sql = "SELECT chara.name charname, office.name oname, court.name courtname, office.ordering, coffice.comment
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER_OFFICE coffice,
                             " . $table_prefix . "OFFICE office,
                             " . $table_prefix . "COURT court
                        WHERE coffice.character_id = chara.id
                          AND coffice.office_id    = office.id
                          AND coffice.court_id     = court.id
                          AND chara.deleted        = 'N'
                          AND court.name = %s ";
        if (!isSt()) {
            $sql .= " AND office.visible = 'Y' AND chara.visible = 'Y' ";
        }
        if ($office != null && $office != "") {
            $sql .= " AND office.name = %s ";
        }
        $sql .= "ORDER BY courtname, office.ordering, charname";

        if ($office != null && $office != "") {
            $characterOffices = $wpdb->get_results($wpdb->prepare($sql, $court, $office));
        }
        else {
            $characterOffices = $wpdb->get_results($wpdb->prepare($sql, $court));
        }

        if ($office == null || $office == "") {
            $currentOffice = "";
            $lastOffice    = "";

            foreach ($characterOffices as $characterOffice) {
                $currentOffice = $characterOffice->oname;
                if ($currentOffice != $lastOffice) {
                    $sqlOutput .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterOffice->oname . "</td>";
                    $lastOffice = $currentOffice;
                }
                else {
                    $sqlOutput .= "<tr><td class=\"gvcol_1 gvcol_key\">&nbsp;</td>";
                }
                $sqlOutput .= "<td class=\"gvcol_2 gvcol_val\">" . $characterOffice->charname . "</td><td class=\"gvcol_3 gvcol_val\">" . $characterOffice->comment . "</td></tr>";
            }

            if ($sqlOutput != "") {
                $output = "<table class='gvplugin' id=\"gvid_cob\">" . $sqlOutput . "</table>";
            }
            else {
                $output = "No office holders found for the court of " . $court;
            }
        }
        else {
            foreach ($characterOffices as $characterOffice) {
                if ($output != "") {
                    $output .= ", ";
                }
                $output .= $characterOffice->charname;
            }
            if ($output == "") {
                $output = "No current holder of " . $office . " in " . $court . " found.";
            }
        }
        return $output;
    }
    add_shortcode('office_block', 'print_office_block');

    function print_prestige_character_list($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "clan" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";

        $leftQuery = "";
        if ($clan != "" && isST()) {
            $leftQuery = "SELECT target_char.id chara_id,
                                     target_char.name chara_name,
                                     target_char.wordpress_id wordpress_id,
                                     clan.name clan_name,
                                     court.name court_name
                              FROM " . $table_prefix . "CHARACTER target_char,
                                   " . $table_prefix . "PLAYER player,
                                   " . $table_prefix . "PLAYER_STATUS pstatus,
                                   " . $table_prefix . "COURT court,
                                   " . $table_prefix . "CLAN clan
                              WHERE target_char.PUBLIC_CLAN_ID = clan.ID
                                AND target_char.player_id = player.id
                                AND player.player_status_id = pstatus.id
                                AND target_char.court_id = court.id
                                AND pstatus.name = 'Active'
                                AND clan.name = %s";

            $rightQuery = "SELECT target_char.id chara2_id,
                                      target_char.name chara2_name,
                                      target_char.wordpress_id chara2_wordpress_id,
                                      target_clan.name target_clan_name,
                                      target_court.name target_court_name,
                                      cha_back.level
                               FROM " . $table_prefix . "CHARACTER target_char,
                                    " . $table_prefix . "CHARACTER source_char,
                                    " . $table_prefix . "PLAYER player,
                                    " . $table_prefix . "PLAYER_STATUS pstatus,
                                    " . $table_prefix . "CLAN source_clan,
                                    " . $table_prefix . "CLAN target_clan,
                                    " . $table_prefix . "COURT target_court,
                                    " . $table_prefix . "BACKGROUND back,
                                    " . $table_prefix . "CHARACTER_BACKGROUND cha_back
                               WHERE source_clan.name = %s
                                 AND target_char.ID = cha_back.CHARACTER_ID
                                 AND cha_back.BACKGROUND_ID = back.id
                                 AND back.NAME = 'Clan Prestige'
                                 AND cha_back.comment = source_clan.name
                                 AND target_char.DELETED != 'Y'
                                 AND target_char.player_id = player.id
                                 AND target_char.court_id = target_court.id
                                 AND player.player_status_id = pstatus.id
                                 AND pstatus.name = 'Active'
                                 AND source_char.DELETED != 'Y'
                                 AND target_char.PUBLIC_CLAN_ID = target_clan.ID";
        }
        else {
            $leftQuery = "SELECT target_char.id chara_id,
                                     target_char.name chara_name,
                                     target_char.wordpress_id wordpress_id,
                                     clan.name clan_name,
                                     court.name court_name
                              FROM " . $table_prefix . "CHARACTER target_char,
                                   " . $table_prefix . "CHARACTER source_char,
                                   " . $table_prefix . "PLAYER player,
                                   " . $table_prefix . "PLAYER_STATUS pstatus,
                                   " . $table_prefix . "COURT court,
                                   " . $table_prefix . "CLAN clan
                              WHERE target_char.PUBLIC_CLAN_ID = source_char.PUBLIC_CLAN_ID
                                AND target_char.PUBLIC_CLAN_ID = clan.id
                                AND (target_char.VISIBLE = 'Y' OR target_char.id = source_char.id)
                                AND target_char.DELETED != 'Y'
                                AND target_char.player_id = player.id
                                AND target_char.court_id = court.id
                                AND player.player_status_id = pstatus.id
                                AND pstatus.name = 'Active'
                                AND source_char.DELETED != 'Y'
                                AND source_char.WORDPRESS_ID = %s";

            $rightQuery = "SELECT target_char.id chara2_id,
                                      target_char.name chara2_name,
                                      target_char.wordpress_id chara2_wordpress_id,
                                      target_clan.name target_clan_name,
                                      target_court.name target_court_name,
                                      cha_back.level
                               FROM " . $table_prefix . "CHARACTER target_char,
                                    " . $table_prefix . "CHARACTER source_char,
                                    " . $table_prefix . "PLAYER player,
                                    " . $table_prefix . "PLAYER_STATUS pstatus,
                                    " . $table_prefix . "CLAN source_clan,
                                    " . $table_prefix . "CLAN target_clan,
                                    " . $table_prefix . "COURT target_court,
                                    " . $table_prefix . "BACKGROUND back,
                                    " . $table_prefix . "CHARACTER_BACKGROUND cha_back
                               WHERE source_char.PUBLIC_CLAN_ID = source_clan.id
                                 AND target_char.ID = cha_back.CHARACTER_ID
                                 AND cha_back.BACKGROUND_ID = back.id
                                 AND (target_char.VISIBLE = 'Y' OR target_char.id = source_char.id)
                                 AND back.NAME = 'Clan Prestige'
                                 AND cha_back.comment = source_clan.name
                                 AND target_char.PUBLIC_CLAN_ID = target_clan.ID
                                 AND target_char.DELETED != 'Y'
                                 AND target_char.player_id = player.id
                                 AND target_char.court_id = target_court.id
                                 AND player.player_status_id = pstatus.id
                                 AND pstatus.name = 'Active'
                                 AND source_char.DELETED != 'Y'
                                 AND source_char.WORDPRESS_ID = %s";
        }

        $sql = "SELECT chara_name Character_Name,
                           clan_name Character_Clan,
                           ifnull(level, 0) Prestige,
                           court_name Court,
                           wordpress_id Wordpress_Id
                    FROM (" . $leftQuery . ") as clan_list
                          LEFT JOIN (" . $rightQuery . ") as prestige_list
                          on (clan_list.chara_id = prestige_list.chara2_id)
                    union
                    SELECT chara2_name Character_Name,
                           target_clan_name Character_Clan,
                           ifnull(level, 0) Prestige,
                           target_court_name Court,
                           chara2_wordpress_id Wordpress_Id
                    FROM (" . $leftQuery . ") as clan_list
                          RIGHT JOIN (" . $rightQuery . ") as prestige_list
                          on (clan_list.chara_id = prestige_list.chara2_id)
                    ORDER BY prestige DESC";

        if ($clan != "" && isST()) {
            $sql = $wpdb->prepare($sql, $clan, $clan, $clan, $clan);
        }
        else {
            $sql = $wpdb->prepare($sql, $character, $character, $character, $character);
        }

        $prestige_characters = $wpdb->get_results($sql);

        $config = getConfig();

        foreach ($prestige_characters as $current_character) {
            $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">
                                  <a href=\"" . $config->PROFILE_LINK . "?CHARACTER=" . urlencode($current_character->Wordpress_Id) . "\">"
                . $current_character->Character_Name . "</a></td>
                                  <td class=\"gvcol_2 gvcol_val\">" . $current_character->Character_Clan . "</td>
                                  <td class=\"gvcol_3 gvcol_val\">" . $current_character->Court          . "</td>
                                  <td class='gvcol_4 gvcol_val'>" . $current_character->Prestige       . "</td></tr>";
        }

        if ($clan != "" && isST()) {
            $sql = "SELECT chara.name character_name,
                               chara.wordpress_id wordpress_id,
                               merit.name merit_name,
                               cmerit.comment,
                               court.name court_name
                            FROM " . $table_prefix . "CHARACTER chara,
                                 " . $table_prefix . "COURT court,
                                 " . $table_prefix . "PLAYER player,
                                 " . $table_prefix . "PLAYER_STATUS pstatus,
                                 " . $table_prefix . "CHARACTER_MERIT cmerit,
                                 " . $table_prefix . "MERIT merit
                            WHERE chara.id = cmerit.character_id
                              AND merit.id = cmerit.merit_id
                              AND chara.deleted != 'Y'
                              AND chara.player_id = player.id
                              AND chara.court_id = court.id
                              AND player.player_status_id = pstatus.id
                              AND pstatus.name = 'Active'
                              AND merit.name IN ('Clan Enmity', 'Clan Friendship')
                              AND cmerit.comment = %s
                            ORDER BY merit.name DESC, chara.name";
            $sql = $wpdb->prepare($sql, $clan);
        }
        else {
            $sql = "SELECT target_char.name character_name,
                               chara.wordpress_id wordpress_id,
                               merit.name merit_name,
                               cmerit.comment,
                               court.name court_name
                            FROM " . $table_prefix . "CHARACTER source_char,
                                 " . $table_prefix . "CHARACTER target_char,
                                 " . $table_prefix . "COURT court
                                 " . $table_prefix . "PLAYER player,
                                 " . $table_prefix . "PLAYER_STATUS pstatus,
                                 " . $table_prefix . "CHARACTER_MERIT cmerit,
                                 " . $table_prefix . "MERIT merit,
                                 " . $table_prefix . "CLAN source_clan
                            WHERE target_char.id = cmerit.character_id
                              AND merit.id = cmerit.merit_id
                              AND source_char.public_clan_id = source_clan.id
                              AND target_char.PUBLIC_CLAN_ID = target_clan.ID
                              AND target_char.DELETED != 'Y'
                              AND target_char.player_id = player.id
                              AND target_char.deleted != 'Y'
                              AND target_char.court_id = court.id
                              AND (target_char.VISIBLE = 'Y' OR target_char.id = source_char.id)
                              AND merit.name IN ('Clan Enmity', 'Clan Friendship')
                              AND cmerit.comment = source_clan.name
                              AND source_char.WORDPRESS_ID = %s
                            ORDER BY merit.name DESC, target_char.name";
            $sql = $wpdb->prepare($sql, $character);
        }

        $characters = $wpdb->get_results($sql);

        foreach ($characters as $currentCharacter) {
            $sqlOutput .= "<tr><td class=\"gvcol_1 gvcol_key\">
                                       <a href=\"" . $config->PROFILE_LINK . "?CHARACTER=" . urlencode($current_character->Wordpress_Id) . "\">"
                . $currentCharacter->character_name . "</a></td>
                                   <td class=\"gvcol_2 gvcol_key\">" . $currentCharacter->court_name . "</td>
                                   <td colspan=2 class=\"gvcol_3 gvcol_val\">" . $currentCharacter->merit_name . " " . $currentCharacter->comment . "</td></tr>";
        }

        if ($sqlOutput != "") {
            $output .= "<table class='gvplugin' id=\"gvid_plb\">" . $sqlOutput . "</table>";
        }
        else {
            if ($clan != "" && isST()) {
                $output .= "No information found for clan (" . $clan . ")";
            }
            else {
                $output .= "No information found for character (" . $character . ")";
            }
        }

        return $output;
    }
    add_shortcode('prestige_list_block', 'print_prestige_character_list');

    function print_status_character_list($atts, $content=null) {
        extract(shortcode_atts(array ("court" => "Glasgow", "displayzeros" => "N", "statusvalue" => "All"), $atts));

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";
        $sqlOutput = "";

        $config = getConfig();

        $coreClause = "WHERE court.ID = chara.COURT_ID
                             AND court.name = %s
                             AND chara.public_clan_id = clan.id
                             AND chara.player_id = player.id
                             AND player.player_status_id = pstatus.id
                             AND pstatus.name = 'Active'
                             AND chara.character_status_id = cstatus.id
                             AND cstatus.name = 'Alive'
                             AND chara.DELETED != 'Y'
                             AND chara.visible = 'Y' ";

        if (((int) $statusvalue) > 0) {
            $coreClause .= "  AND cback.level = " . $statusvalue . " ";
        }

        $coreTables = " " . $table_prefix . "CHARACTER chara,
                            " . $table_prefix . "CHARACTER_STATUS cstatus,
                            " . $table_prefix . "CLAN clan,
                            " . $table_prefix . "PLAYER player,
                            " . $table_prefix . "PLAYER_STATUS pstatus,
                            " . $table_prefix . "COURT court ";

        $mainQueryBody = "FROM " . $coreTables   . ",
                                   " . $table_prefix . "CHARACTER_BACKGROUND cback,
                                   " . $table_prefix . "BACKGROUND background "
            . $coreClause . " AND chara.ID            = cback.CHARACTER_ID
                                  AND cback.BACKGROUND_ID = background.ID
                                  AND background.name     = 'Status' ";

        $sql = "SELECT chara.name, cback.level, clan.name clanname, chara.wordpress_id "
            . $mainQueryBody;

        if ($statusvalue == "0") {
            $sql = "SELECT chara.name, \"0\" level, clan.name clanname, chara.wordpress_id
                            FROM " . $coreTables
                . $coreClause . "
                              AND chara.ID NOT IN (SELECT chara.ID "
                . $mainQueryBody . ") ";
            $sql = $wpdb->prepare($sql, $court, $court);
        }
        elseif ($displayzeros == "Y" && $statusvalue == "All") {

            $sql = "SELECT chara.name, cback.level, clan.name clanname, chara.wordpress_id "
                . $mainQueryBody .
                "union
                     SELECT chara.name, \"0\" level, clan.name clanname, chara.wordpress_id
                     FROM " . $coreTables
                . $coreClause . "
                               AND chara.ID NOT IN (SELECT chara.ID "
                . $mainQueryBody . ") ";

            $sql = $wpdb->prepare($sql, $court, $court, $court);
        }
        else {
            $sql = $wpdb->prepare($sql, $court);
        }
        $sql .= "ORDER BY level DESC, name";

        $status_characters = $wpdb->get_results($sql);

        foreach ($status_characters as $current_character) {
            $sqlOutput .="<tr>";
            if ($statusvalue == "All") {
                $sqlOutput .= "<td class=\"gvcol_1 gvcol_val\">" . $current_character->level . "</td>";
            }
            $sqlOutput .= "<td class=\"gvcol_2 gvcol_key\"><a href=\"" . $config->PROFILE_LINK . "?CHARACTER=" . urlencode($current_character->wordpress_id) . "\">" . $current_character->name . "</a></td><td class=\"gvcol_3 gvcol_val\">" . $current_character->clanname . "</td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_slb\">" . $sqlOutput . "</table>";
        }

        return $output;
    }
    add_shortcode('status_list_block', 'print_status_character_list');

    function print_new_player_table($atts, $content=null) {
        $playerTypes  = listPlayerType();
        $playerStatus = listPlayerStatus();

        $output  = "<form name=\"NP_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<table class='gvplugin' id=\"gvid_npt\"><tr><th class=\"gvthleft\">Player Name</th><td class=\"gvcol_2 gvcol_val\"><input type=text name=\"player_name\" size=30 maxlength=55 /></td></tr>";

        $output .= "<tr><th class=\"gvthleft\">Player Type</th><td class=\"gvcol_2 gvcol_val\"><select name=\"player_type\">";
        foreach ($playerTypes as $type) {
            $output .= "<option value=" . $type->ID . ">" . $type->description . "</option>";
        }
        $output .= "</select></td></tr>";

        $output .= "<tr><th class=\"gvthleft\">Player Status</th><td class=\"gvcol_2 gvcol_val\"><select name=\"player_status\">";
        foreach ($playerStatus as $status) {
            $output .="<option value=" . $status->ID . ">" . $status->description . "</option>";
        }
        $output .= "</select></td></tr>";

        $output .= "<tr style='display:none'><td colspan=2><input type='HIDDEN' name=\"GVLARP_FORM\" value=\"new_player\" /></td></tr>";
        $output .= "<tr><td colspan=2><input type='submit' name=\"pSubmit\" value=\"Submit Player\" /></td></tr></table></form>";
        return $output;
    }
    add_shortcode('new_player_table', 'print_new_player_table');

    function print_master_xp_table($atts, $content=null) {
        if (!isST()) {
            return "Only STs can view the master XP table";
        }

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $xpReasons = listXpReasons();

        $xpOptions = "";
        foreach ($xpReasons as $reason) {
            $xpOptions .= "<option value=\"" . $reason->id . "\">" . $reason->name . "</option>";
        }

        $sql = "SELECT player.name             player_name,
                               player.id               player_id,
                               chara.name              character_name,
                               chara.id                character_id,
                               chara.character_type_id ctype,
                               xp_totals.total_xp
                        FROM " . $table_prefix . "PLAYER player,
                             " . $table_prefix . "PLAYER_STATUS pstatus,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER_STATUS cstatus,
                             (SELECT player_xp.player_id player_id, SUM(player_xp.amount) total_xp
                              FROM " . $table_prefix . "PLAYER_XP player_xp
                              GROUP BY player_xp.player_id) xp_totals
                        WHERE chara.player_id = player.id
                          AND chara.character_status_id = cstatus.id
                          AND player.player_status_id = pstatus.id
                          AND pstatus.name = 'Active'
                          AND cstatus.name = 'Alive'
                          AND chara.VISIBLE = 'Y'
                          AND chara.DELETED != 'Y'
                          AND player.id = xp_totals.player_id
                        ORDER BY player_name, ctype, character_name";

        $xp_records = $wpdb->get_results($sql);

        $output  = "<form name=\"Master_XP_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"master_xp_update\" />";
        $output .= "<table class='gvplugin' id=\"gvid_mxpt\"><tr><td colspan=6><input type='submit' name=\"submit_new_xp\" value=\"Submit XP Changes\" /></tr>";
        $output .= "<tr><th class=\"gvthead gvcol_1\">Player</th>
                            <th class=\"gvthead gvcol_2\">Current XP</th>
                            <th class=\"gvthead gvcol_3\">Character</th>
                            <th class=\"gvthead gvcol_4\">XP Reason</th>
                            <th class=\"gvthead gvcol_5\">XP Change</th>
                            <th class=\"gvthead gvcol_6\">Comment</th></tr>";

        $last_player = "";
        $current_player = "";
        $current_player_id = "";
        $counter = 0;

        foreach ($xp_records as $current_record) {
            $current_player = $current_record->player_name;
            if ($current_player != $last_player) {
                $counter++;
                if ($last_player != "") {
                    $output .= "</select></td>
                                        <td class='gvcol_4 gvcol_val'><select name=\"" . $current_player_id . "_xp_reason\">" . $xpOptions . "</select>
                                        </td><td class='gvcol_5 gvcol_val'>
                                        <input type='text' name=\"" . $current_player_id . "_xp_value\" size=5 maxlength=3 />
                                        </td><td class='gvcol_6 gvcol_val'>
                                        <input type='text' name=\"" . $current_player_id . "_xp_comment\" size=30 maxlength=100 />
                                        </td></tr>";
                }
                $last_player = $current_player;
                $current_xp        = $current_record->total_xp;
                $current_player_id = $current_record->player_id;
                $output .= "<tr><td colspan=6><input type='HIDDEN' name=\"counter_" . $counter . "\" value=\"" . $current_player_id . "\"></td></tr><tr>
                                        <th class=\"gvthleft\">" . $current_player . "</th><td class=\"gvcol_2 gvcol_val\">" . $current_xp . "</td>
                                        <td class=\"gvcol_3 gvcol_val\"><select name=\"" . $current_player_id . "_character\">";
            }
            $output .= "<option value=\"" . $current_record->character_id . "\">" . $current_record->character_name . "</option>";
        }
        if ($last_player != "") {
            $output .= "</select></td>
                                <td class='gvcol_4 gvcol_val'><select name=\"" . $current_player_id . "_xp_reason\">" . $xpOptions . "</select>
                                </td><td class='gvcol_5 gvcol_val'>
                                <input type='text' name=\"" . $current_player_id . "_xp_value\" size=5 maxlength=3 />
                                </td><td class='gvcol_6 gvcol_val'>
                                <input type='text' name=\"" . $current_player_id . "_xp_comment\" size=30 maxlength=100 />
                                </td></tr>";
        }
        $output .= "<tr><td colspan=6><input type='submit' name=\"submit_new_xp\" value=\"Submit XP Changes\" /></td>
                            </table></form>";

        return $output;
    }
    add_shortcode('master_xp_table', 'print_master_xp_table');

    function print_dead_character_table() {
        extract(shortcode_atts(array ("court" => "Glasgow", "group" => "PC", "active" => "Y", "invisibles" => "N"), $atts));

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output    = "";

        $activeSector = "";
        if ($active == "Y") {
            $activeSector = " AND pstatus.name = 'Active' ";
        }

        $visibleSector = "";
        if ($invisibles != "Y") {
            $visibleSector = " AND chara.VISIBLE = 'Y' ";
        }

        $sql = "SELECT chara.id              character_id,
                           chara.name            character_name,
                           status_values.status  status_value,
                           chara.wordpress_id,
                           public_clan.name      clan_name
                    FROM " . $table_prefix . "CHARACTER chara,
                         " . $table_prefix . "CHARACTER_TYPE ctype,
                         " . $table_prefix . "CHARACTER_STATUS cstatus,
                         " . $table_prefix . "COURT court,
                         " . $table_prefix . "PLAYER player,
                         " . $table_prefix . "PLAYER_STATUS pstatus,
                         " . $table_prefix . "CLAN public_clan,
                         (SELECT chara.id character_id, cback.level status, chara.name character_name
                          FROM " . $table_prefix . "CHARACTER chara,
                               " . $table_prefix . "CHARACTER_BACKGROUND cback,
                               " . $table_prefix . "BACKGROUND back
                          WHERE chara.id = cback.character_id
                            AND cback.BACKGROUND_ID = back.id
                            AND back.name = 'Status'
                          union SELECT chara.id character_id, 0 status, chara.name character_name
                                FROM " . $table_prefix . "CHARACTER chara
                                WHERE id NOT IN (SELECT chara.id character_id
                                                 FROM " . $table_prefix . "CHARACTER chara,
                                                      " . $table_prefix . "CHARACTER_BACKGROUND cback,
                                                      " . $table_prefix . "BACKGROUND back
                                                 WHERE chara.id = cback.character_id
                                                   AND cback.BACKGROUND_ID = back.id
                                                   AND back.name = 'Status')) status_values
                    WHERE chara.CHARACTER_TYPE_ID   = ctype.id
                      AND chara.CHARACTER_STATUS_ID = cstatus.id
                      AND chara.PLAYER_ID           = player.id
                      AND chara.ID                  = status_values.character_id
                      AND chara.PUBLIC_CLAN_ID      = public_clan.ID
                      AND player.PLAYER_STATUS_ID   = pstatus.id
                      AND chara.COURT_ID            = court.id
                      AND cstatus.name  IN ('Dead', 'Missing', 'Imprisoned')
                      " . $activeSector . $visibleSector;

        $queryEnd = "  AND chara.DELETED = 'N'
                         ORDER BY status_value DESC, character_name";

        if ($group == "All" && $court == "All") {
            $sql .= $queryEnd;
        }
        else if ($group == "All") {
            $sql .= "  AND court.name = %s " . $queryEnd;
            $sql = $wpdb->prepare($sql, $court);
        }
        else if ($court == "All") {
            $sql .= "  AND ctype.name = %s " . $queryEnd;
            $sql = $wpdb->prepare($sql, $group);
        }
        else {
            $sql .= "AND court.name = %s AND ctype.name = %s " . $queryEnd;
            $sql = $wpdb->prepare($sql, $court, $group);
        }

        $deadCharacters = $wpdb->get_results($sql);

        $config = getConfig();

        $sqlOutput = "";
        foreach ($deadCharacters as $deadCharacter) {
            $sqlOutput .= "<tr><td class=\"gvcol_1 gvcol_val\">" . $deadCharacter->status_value . "</td>
                               <td class=\"gvcol_2 gvcol_key\"><a href=\"" . $config->PROFILE_LINK . "?CHARACTER=" . urlencode($deadCharacter->wordpress_id) . "\">" . $deadCharacter->character_name . "</a></td>
                               <td class=\"gvcol_3 gvcol_val\">" . $deadCharacter->clan_name . "</td></tr>";
        }

        if ($sqlOutput != "") {
            $output = "<table class='gvplugin' id=\"gvid_dct\"><tr><th class=\"gvthead gvcol_1\">Status</th>
                                                                         <th class=\"gvthead gvcol_2\">Character</th>
                                                                         <th class=\"gvthead gvcol_3\">Clan</th></tr>" . $sqlOutput . "</table>";
        }
        return $output;
    }
    add_shortcode('dead_character_table', 'print_dead_character_table');

    function print_master_path_table($atts, $content=null) {
        extract(shortcode_atts(array ("group" => ""), $atts));

        if (!isST()) {
            return "Only STs can view the master path table";
        }

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $output = "";

        if ($_POST['GVLARP_FORM'] == "master_path_update"
            && $_POST['submit_new_path'] == "Submit Path Changes") {

            $counter = 0;
            $characterCounter = 0;
            while (isset($_POST['counter_' . $counter])) {
                $characterID = $_POST['counter_' . $counter];
                $current_path_value  = $_POST[$characterID . '_path_value'];
                if (is_numeric($current_path_value) && ((int) $current_path_value != 0)) {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_ROAD_OR_PATH (character_id,
                                                                                             path_reason_id,
                                                                                             awarded,
                                                                                             amount,
                                                                                             comment)
                                    VALUES (%s, %s, SYSDATE(), %d, %s )";
                    $wpdb->query($wpdb->prepare($sql, $characterID, $_POST[$characterID . '_path_reason'], $current_path_value, $_POST[$characterID . '_path_comment']));

                    $characterCounter++;
                }
                $counter++;
            }

            if ($characterCounter == 0) {
                $output .= "No characters updated<br />";
            }
            else if ($characterCounter == 1) {
                $output .= "One character updated<br />";
            }
            else {
                $output .= $characterCounter . " characters updated<br />";
            }
        }

        $pathReasons = listPathReasons();

        $pathOptions = "";
        foreach ($pathReasons as $reason) {
            $pathOptions .= "<option value=\"" . $reason->id . "\"";
            if ($reason->name == "Path change") {
                $pathOptions .= " SELECTED";
            }
            $pathOptions .= ">" . $reason->name . "</option>";
        }

        $grouping = "";
        if ($group != "") {
            $sql = "SELECT chara.name char_name, rop.name path_name, total_path, ctype.id, chara.id char_id
                            FROM " . $table_prefix . "CHARACTER chara,
                                 " . $table_prefix . "CHARACTER_TYPE ctype,
                                 " . $table_prefix . "CHARACTER_STATUS cstatus,
                                 " . $table_prefix . "ROAD_OR_PATH rop,
                                 " . $table_prefix . "PLAYER player,
                                 " . $table_prefix . "PLAYER_STATUS pstatus,
                                 (SELECT character_path.character_id, SUM(character_path.amount) total_path
                                  FROM " . $table_prefix . "CHARACTER_ROAD_OR_PATH character_path
                                  GROUP BY character_path.character_id) path_totals
                            WHERE path_totals.character_id = chara.id
                              AND chara.character_type_id = ctype.id
                              AND chara.road_or_path_id = rop.id
                              AND chara.player_id = player.id
                              AND chara.character_status_id = cstatus.id
                              AND player.player_status_id = pstatus.id
                              AND chara.visible = 'Y'
                              AND chara.DELETED != 'Y'
                              AND pstatus.name = 'Active'
                              AND cstatus.name != 'Dead'
                              AND cytpe.name = %s
                            ORDER BY ctype.id, chara.name";

            $path_records = $wpdb->get_results($wpdb->prepare($sql, $group));
        }
        else {
            $sql = "SELECT chara.name char_name, rop.name path_name, total_path, ctype.id, chara.id char_id
                            FROM " . $table_prefix . "CHARACTER chara,
                                 " . $table_prefix . "CHARACTER_TYPE ctype,
                                 " . $table_prefix . "CHARACTER_STATUS cstatus,
                                 " . $table_prefix . "ROAD_OR_PATH rop,
                                 " . $table_prefix . "PLAYER player,
                                 " . $table_prefix . "PLAYER_STATUS pstatus,
                                 (SELECT character_path.character_id, SUM(character_path.amount) total_path
                                  FROM " . $table_prefix . "CHARACTER_ROAD_OR_PATH character_path
                                  GROUP BY character_path.character_id) path_totals
                            WHERE path_totals.character_id = chara.id
                              AND chara.character_type_id = ctype.id
                              AND chara.road_or_path_id = rop.id
                              AND chara.player_id = player.id
                              AND chara.character_status_id = cstatus.id
                              AND player.player_status_id = pstatus.id
                              AND chara.visible = 'Y'
                              AND chara.DELETED != 'Y'
                              AND pstatus.name = 'Active'
                              AND cstatus.name != 'Dead'
                            ORDER BY ctype.id, chara.name";

            $path_records = $wpdb->get_results($sql);
        }


        $output .= "<form name=\"Master_Path_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"master_path_update\" />";
        $output .= "<table class='gvplugin' id=\"gvid_mpt\"><tr><td colspan=6><input type='submit' name=\"submit_new_path\" value=\"Submit Path Changes\" /></tr>";
        $output .= "<tr><th class=\"gvthead gvcol_1\">Character</th>
                            <th class=\"gvthead gvcol_2\">Path Name</th>
                            <th class=\"gvthead gvcol_3\">Current Path</th>
                            <th class=\"gvthead gvcol_4\">Path Reason</th>
                            <th class=\"gvthead gvcol_5\">Path Change</th>
                            <th class=\"gvthead gvcol_6\">Comment</th></tr>";

        $counter = 0;
        foreach ($path_records as $current_record) {
            $characterID = $current_record->char_id;
            $output .= "<tr>
                                    <th class=\"gvthleft\"><input type='HIDDEN' name=\"counter_" . $counter . "\" value=\"" . $characterID . "\">" . $current_record->char_name . "</th>
                                    <td class=\"gvcol_2 gvcol_val\">" . $current_record->path_name . "</td>
                                    <td class=\"gvcol_3 gvcol_val\">" . $current_record->total_path . "</td>
                                    <td class='gvcol_4 gvcol_val'><select name=\"" . $characterID . "_path_reason\">" . $pathOptions . "</select></td>
                                    <td class='gvcol_5 gvcol_val'><input type='text' name=\"" . $characterID . "_path_value\" size=5 maxlength=3 /></td>
                                    <td class='gvcol_6 gvcol_val'><input type='text' name=\"" . $characterID . "_path_comment\" size=30 maxlength=100 /></td></tr>";
            $counter++;
        }
        $output .= "<tr><td colspan=6><input type='submit' name=\"submit_new_path\" value=\"Submit Path Changes\" /></td></tr>
                            </table></form>";

        return $output;
    }
    add_shortcode('master_path_table', 'print_master_path_table');

    function print_master_temp_stat_table($atts, $content=null) {
        extract(shortcode_atts(array ("stat" => "Willpower", "group" => "PC", "monthly" => "N"), $atts));

        if (!isST()) {
            return "Only STs can view the temporary stat table.";
        }
        else if ($stat != "Willpower" && $stat != "Blood") {
            return "Illegal stat (" . $stat . ") only Blood and Willpower are supported.";
        }

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $output = "";
        if ($_POST['GVLARP_FORM'] == "master_temp_stat_update"
            && $_POST['submit_new_temp_stat'] == "Submit Temporary " . $stat . " Changes") {

            $counter = 0;
            $characterCounter = 0;
            while (isset($_POST['counter_' . $counter])) {
                $characterID = $_POST['counter_' . $counter];
                $current_temp_stat_value  = $_POST[$characterID . '_temp_stat_value'];

                if (is_numeric($current_temp_stat_value) && ((int) $current_temp_stat_value != 0)) {

                    $current_value = $_POST[$characterID . '_temp_stat_current'];
                    $max_value     = $_POST[$characterID . '_temp_stat_max'];
                    $character     = $_POST[$characterID . '_char_name'];

                    if (((int) $current_value) + ((int) $current_temp_stat_value) > ((int) $max_value)) {
                        $current_temp_stat_value = ((int) $max_value) - ((int) $current_value);
                        $output .= "Change for " . $character . " capped at max value (" . $max_value . ")<br />";
                    }
                    else if (((int) $current_value) + ((int) $current_temp_stat_value) < 0) {
                        $current_temp_stat_value = ((int) $current_value) * -1;
                        $output .= "Change for " . $character . " capped at 0<br />";
                    }

                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_TEMPORARY_STAT (character_id,
                                                                                       temporary_stat_id,
                                                                                       temporary_stat_reason_id,
                                                                                       awarded,
                                                                                       amount,
                                                                                       comment)
                            VALUES (%d, (SELECT id FROM " . $table_prefix . "TEMPORARY_STAT WHERE name = %s), %d, SYSDATE(), %d, %s )";
                    $sql = $wpdb->prepare($sql, $characterID, $stat, $_POST[$characterID . '_temp_stat_reason'], $current_temp_stat_value, $_POST[$characterID . '_temp_stat_comment']);

                    $wpdb->query($sql);

                    $characterCounter++;
                }
                $counter++;
            }

            if ($characterCounter == 0) {
                $output .= "No characters updated<br />";
            }
            else if ($characterCounter == 1) {
                $output .= "One character updated<br />";
            }
            else {
                $output .= $characterCounter . " characters updated<br />";
            }
        }

        $monthlyWP = false;
        if ($monthly == "Y") {
            $monthlyWP = true;
        }

        $tempStatReasons = listTemporaryStatReasons();

        $tempStatOptions = "";
        foreach ($tempStatReasons as $reason) {
            $tempStatOptions .= "<option value=\"" . $reason->id . "\"";
            if ($monthlyWP && $reason->name == "Monthly Gain") {
                $tempStatOptions .= " SELECTED";
            }
            $tempStatOptions .= ">" . $reason->name . "</option>";
        }

        if ($group != "" && $group != "All") {
            $sql = "SELECT chara.name char_name,
                               total_temp_stat,
                               gen.bloodpool max_blood,
                               cstat.level max_wp,
                               ctype.id,
                               chara.id char_id
                        FROM " . $table_prefix . "TEMPORARY_STAT temp_stat,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "GENERATION gen,
                             " . $table_prefix . "CHARACTER_STAT cstat,
                             " . $table_prefix . "STAT stat,
                             " . $table_prefix . "CHARACTER_TYPE ctype,
                             " . $table_prefix . "CHARACTER_STATUS cstatus,
                             " . $table_prefix . "PLAYER player,
                             " . $table_prefix . "PLAYER_STATUS pstatus,
                             (SELECT char_temp_stat.character_id, char_temp_stat.temporary_stat_id, SUM(char_temp_stat.amount) total_temp_stat
                              FROM " . $table_prefix . "CHARACTER_TEMPORARY_STAT char_temp_stat
                              GROUP BY char_temp_stat.character_id, char_temp_stat.temporary_stat_id) temp_stat_totals
                        WHERE temp_stat_totals.character_id = chara.id
                          AND temp_stat_totals.temporary_stat_id = temp_stat.id
                          AND chara.character_type_id = ctype.id
                          AND chara.player_id = player.id
                          AND chara.character_status_id = cstatus.id
                          AND chara.generation_id = gen.id
                          AND chara.id = cstat.character_id
                          AND cstat.stat_id = stat.id
                          AND stat.name = 'Willpower'
                          AND player.player_status_id = pstatus.id
                          AND chara.DELETED != 'Y'
                          AND pstatus.name = 'Active'
                          AND cstatus.name != 'Dead'
                          AND temp_stat.name = %s
                          AND ctype.name = %s
                        ORDER BY ctype.id, chara.name";

            $sql = $wpdb->prepare($sql, $stat, $group);
        }
        else {
            $sql = "SELECT chara.name char_name,
                               total_temp_stat,
                               gen.bloodpool max_blood,
                               cstat.level max_wp,
                               ctype.id,
                               chara.id char_id
                        FROM " . $table_prefix . "TEMPORARY_STAT temp_stat,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "GENERATION gen,
                             " . $table_prefix . "CHARACTER_STAT cstat,
                             " . $table_prefix . "STAT stat,
                             " . $table_prefix . "CHARACTER_TYPE ctype,
                             " . $table_prefix . "CHARACTER_STATUS cstatus,
                             " . $table_prefix . "PLAYER player,
                             " . $table_prefix . "PLAYER_STATUS pstatus,
                             (SELECT char_temp_stat.character_id, char_temp_stat.temporary_stat_id, SUM(char_temp_stat.amount) total_temp_stat
                              FROM " . $table_prefix . "CHARACTER_TEMPORARY_STAT char_temp_stat
                              GROUP BY char_temp_stat.character_id, char_temp_stat.temporary_stat_id) temp_stat_totals
                        WHERE temp_stat_totals.character_id = chara.id
                          AND temp_stat_totals.temporary_stat_id = temp_stat.id
                          AND chara.character_type_id = ctype.id
                          AND chara.player_id = player.id
                          AND chara.character_status_id = cstatus.id
                          AND chara.generation_id = gen.id
                          AND chara.id = cstat.character_id
                          AND cstat.stat_id = stat.id
                          AND stat.name = 'Willpower'
                          AND player.player_status_id = pstatus.id
                          AND chara.DELETED != 'Y'
                          AND pstatus.name = 'Active'
                          AND cstatus.name != 'Dead'
                          AND temp_stat.name = %s
                        ORDER BY ctype.id, chara.name";

            $sql = $wpdb->prepare($sql, $stat);
        }

        $tempStats = $wpdb->get_results($sql);

        $output .= "<form name=\"Master_Temp_Stat_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"master_temp_stat_update\" />";
        $output .= "<table class='gvplugin' id=\"gvid_mtst\"><tr><td colspan=6><input type='submit' name=\"submit_new_temp_stat\" value=\"Submit Temporary " . $stat . " Changes\" /></tr>";
        $output .= "<tr><th class=\"gvthead gvcol_1\">Character</th>
                        <th class=\"gvthead gvcol_2\">Current " . $stat . "</th>
                        <th class=\"gvthead gvcol_3\">Max " . $stat . "</th>
                        <th class=\"gvthead gvcol_4\">Change Reason</th>
                        <th class=\"gvthead gvcol_5\">Change Amount</th>
                        <th class=\"gvthead gvcol_6\">Comment</th></tr>";

        $counter = 0;
        foreach ($tempStats as $current_record) {
            $characterID = $current_record->char_id;
            $output .=  "<tr>
                             <th class=\"gvthleft\"><input type='HIDDEN' name=\"counter_" . $counter . "\" value=\"" . $characterID . "\">" . $current_record->char_name . "<input type='HIDDEN' name=\"" . $characterID . "_char_name\" value=\"" . $current_record->char_name . "\"></th>
                             <td class=\"gvcol_2 gvcol_val\">" . $current_record->total_temp_stat . "</td>
                             <input type='HIDDEN' name=\"" . $characterID . "_temp_stat_current\" value=\"" . $current_record->total_temp_stat . "\">
                             <td class=\"gvcol_3 gvcol_val\">";
            if ($stat == "Willpower") {
                $output .= $current_record->max_wp;
                $output .= "<input type='HIDDEN' name=\"" . $characterID . "_temp_stat_max\" value=\"" . $current_record->max_wp . "\">";
            }
            else if ($stat == "Blood") {
                $output .= $current_record->max_blood;
                $output .= "<input type='HIDDEN' name=\"" . $characterID . "_temp_stat_max\" value=\"" . $current_record->max_blood . "\">";
            }
            $output .= "</td><td class='gvcol_4 gvcol_val'><select name=\"" . $characterID . "_temp_stat_reason\">" . $tempStatOptions . "</select></td>
                                 <td class='gvcol_5 gvcol_val'><input type='text' name=\"" . $characterID . "_temp_stat_value\" ";
            if ($monthlyWP) {
                $output .= "value=\"1\" ";
            }
            $output .= "size=5 maxlength=3 /></td>
                                 <td class='gvcol_6 gvcol_val'><input type='text' name=\"" . $characterID . "_temp_stat_comment\" ";
            if ($monthlyWP) {
                $output .= " value=\"Monthly WP Gain\" ";
            }
            $output .= "size=30 maxlength=100 /></td></tr>";
            $counter++;
        }

        $output .= "<tr><td colspan=6><input type='submit' name=\"submit_new_temp_stat\" value=\"Submit Temporary " . $stat . " Changes\" /></td></tr>
                            </table></form>";

        return $output;
    }
    add_shortcode('master_temp_stat_table', 'print_master_temp_stat_table');

    function print_character_xp_table($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => "", "maxrecords" => "20"), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $innerQuery = "SELECT xp_totals.player_id player_id, (total_xp + ifnull(total_pending, 0)) total_xp
                               FROM (SELECT player_xp.player_id player_id, SUM(player_xp.amount) total_xp
                                     FROM " . $table_prefix . "PLAYER_XP player_xp
                                     GROUP BY player_xp.player_id) xp_totals
                                     LEFT JOIN (SELECT pending_xp.player_id player_id, SUM(pending_xp.amount) total_pending
                                                FROM " . $table_prefix . "PENDING_XP_SPEND pending_xp
                                                GROUP BY pending_xp.player_id) pending_totals ON pending_totals.player_id = xp_totals.player_id ";

        $sql = "SELECT chara2.name char_name, xp_reason.name reason_name, player_xp.amount, player_xp.comment, player_xp.awarded, total_xp
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER chara2,
                             " . $table_prefix . "PLAYER_XP player_xp,
                             " . $table_prefix . "XP_REASON xp_reason,
                            (" . $innerQuery   . ") xp_totals
                        WHERE chara.player_id = player_xp.player_id
                          AND player_xp.character_id = chara2.id
                          AND xp_totals.player_id = chara.player_id
                          AND chara.DELETED != 'Y'
                          AND chara2.DELETED != 'Y'
                          AND player_xp.xp_reason_id = xp_reason.id
                          AND chara.WORDPRESS_ID = %s
                        union
                        SELECT chara.name char_name, 'Pending XP Spend' reason_name, pending_xp.amount, pending_xp.comment, pending_xp.awarded, total_xp
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER chara2,
                             " . $table_prefix . "PENDING_XP_SPEND pending_xp,
                            (" . $innerQuery   . ") xp_totals
                        WHERE chara.player_id = pending_xp.player_id
                          AND pending_xp.character_id = chara2.id
                          AND xp_totals.player_id = chara.player_id
                          AND chara.DELETED != 'Y'
                          AND chara2.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                        ORDER BY awarded";

        $character_xp = $wpdb->get_results($wpdb->prepare($sql, $character, $character));

        if ($group != "total" && $group != "TOTAL") {
            $output = "<table class='gvplugin' id=\"cxpt\">
                               <tr><th class=\"gvthead gvcol_1\">Character</th>
                                   <th class=\"gvthead gvcol_2\">XP Reason</th>
                                   <th class=\"gvthead gvcol_3\">XP Amount</th>
                                   <th class=\"gvthead gvcol_4\">Comment</th>
                                   <th class=\"gvthead gvcol_5\">Date of award</th></tr>\n";

            $arr = array();
            $i = 0;
            $xp_total = 0;
            foreach ($character_xp as $current_xp) {
                $arr[$i] = "<tr><td class=\"gvcol_1 gvcol_key\">" . $current_xp->char_name   . "</td><td class=\"gvcol_2 gvcol_val\">"
                    . $current_xp->reason_name . "</td><td class=\"gvcol_3 gvcol_val\">"
                    . $current_xp->amount      . "</td><td class='gvcol_4 gvcol_val'>"
                    . $current_xp->comment     . "</td><td class='gvcol_5 gvcol_val'>"
                    . $current_xp->awarded     . "</td></tr>\n";
                $xp_total = (int) $current_xp->total_xp;
                $i++;
            }

            $pageSize = 20;
            if ((int) $maxrecords > 0) {
                $pageSize = (int) $maxrecords;
            }
            $j = 0;
            if ($i > $pageSize) {
                $j = $i - $pageSize;
            }

            while ($j < $i) {
                $output .= $arr[$j];
                $j++;
            }

            $output .= "<tr><td colspan=3 class=\"gvsummary\">Total amount of XP to spend</td>
                                <td colspan=2 class=\"gvsummary\">" . $xp_total . "</td></tr>\n";

            if (isSt()) {
                $output .= "<tr><td colspan=5 class=\"gvsummary\">" . print_xp_spend($character) . "</td></tr>\n";
            }
            $output .= "</table>";
        }
        else {
            $total_xp = 0;
            foreach ($character_xp as $current_xp) {
                $total_xp = (int) $current_xp->total_xp;
            }

            $output = $total_xp;
        }

        return $output;
    }
    add_shortcode('character_xp_table', 'print_character_xp_table');

    function print_character_road_or_path_table($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => "", "maxrecords" => "20"), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT chara.name char_name, preason.name reason_name, cpath.amount, cpath.comment, cpath.awarded, total_path
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER_ROAD_OR_PATH cpath,
                             " . $table_prefix . "PATH_REASON preason,
                             (SELECT character_path.character_id, SUM(character_path.amount) total_path
                              FROM " . $table_prefix . "CHARACTER_ROAD_OR_PATH character_path
                              GROUP BY character_path.character_id) path_totals
                        WHERE path_totals.character_id = chara.id
                          AND chara.DELETED != 'Y'
                          AND cpath.path_reason_id = preason.id
                          AND cpath.character_id = chara.ID
                          AND chara.WORDPRESS_ID = %s
                        ORDER BY cpath.awarded, cpath.id";

        $character_path = $wpdb->get_results($wpdb->prepare($sql, $character));

        if ($group != "total" && $group != "TOTAL") {
            $output .= "<table class='gvplugin' id=\"gvid_crpt\">
                               <tr><th class=\"gvthead gvcol_1\">Path Reason</th>
                                   <th class=\"gvthead gvcol_2\">Path Amount</th>
                                   <th class=\"gvthead gvcol_3\">Comment</th>
                                   <th class=\"gvthead gvcol_4\">Date of award</th></tr>";

            $arr = array();
            $i = 0;
            $path_total = 0;
            foreach ($character_path as $current_path) {
                $arr[$i] = "<tr><td class=\"gvcol_1 gvcol_val\">" . $current_path->reason_name . "</td><td class=\"gvcol_2 gvcol_val\">"
                    . $current_path->amount      . "</td><td class=\"gvcol_3 gvcol_val\">"
                    . $current_path->comment     . "</td><td class='gvcol_4 gvcol_val'>"
                    . $current_path->awarded     . "</td></tr>";
                $path_total = (int) $current_path->total_path;
                $i++;
            }

            $pageSize = 20;
            if ((int) $maxrecords > 0) {
                $pageSize = (int) $maxrecords;
            }
            $j = 0;
            if ($i > $pageSize) {
                $j = $i - $pageSize;
            }

            while ($j < $i) {
                $output .= $arr[$j];
                $j++;
            }

            $output .= "<tr><td colspan=2>Total </td>
                                    <td class=\"gvsummary\" colspan=2>" . $path_total . "</td></tr>";

            $output .= "</table>";
        }
        else {
            $total_path = 0;
            foreach ($character_path as $current_path) {
                $total_path = (int) $current_path->total_path;
            }

            $output = $total_path;
        }

        return $output;
    }
    add_shortcode('character_road_or_path_table', 'print_character_road_or_path_table');

    function print_character_update_table($atts, $content=null) {
        extract(shortcode_atts(array ("group"           => "",
            "characterstatus" => "",
            "playername"      => "",
            "playerstatus"    => "",
            "visible"         => "Y"), $atts));

        $output = "";
        if (!isSt()) {
            $output = "<br /><h2>Only STs can access the character update table!</h2><br />";
        }
        else {
            $characterID = $_POST['characterID'];
            if ($_POST['GVLARP_FORM'] == "displayUpdateCharacter"
                && $characterID != ""
                && $_POST['cSubmit'] == "Update Character") {
                $output = displayUpdateCharacter($characterID);
            }
            elseif ($_POST['GVLARP_FORM'] == "displayUpdateCharacter"
                && $characterID != ""
                && $_POST['cSubmit'] == "Delete Character") {
                if ((int) ($characterID) > 0) {
                    $output = displayDeleteCharacter($characterID);
                }
                else {
                    $output = "Cannot delete character with illegal ID (" . $characterID . ")";
                }
            }
            elseif ($_POST['GVLARP_FORM'] == "displayUpdateCharacter"
                && $characterID != ""
                && $_POST['cSubmit'] == "Confirm Delete") {
                $output = deleteCharacter($characterID);
            }
            elseif ($_POST['GVLARP_FORM'] == "displayUpdateCharacter"
                && $_POST['cSubmit'] == "Submit character changes") {
                $characterID  = processCharacterUpdate($characterID);
                $output  = "<br /><center><strong>Update successful</strong></center><br />";
                $output .= displayUpdateCharacter($characterID);
            }
            elseif (($_POST['GVLARP_FORM'] == "displayUpdateCharacter"
                && $_POST['cSubmit'] == "Display Characters")) {

                $group           = $_POST['characterGroup'];
                $characterstatus = $_POST['characterStatus'];
                $playername      = $_POST['playerName'];
                $playerstatus    = $_POST['playerStatus'];
                $showNotVisible  = $_POST['showNotVisible'];

                $characters  = listCharacters($group, $characterstatus, $playername, $playerstatus, $showNotVisible);

                $stLinks = listSTLinks();
                $formUrl    = $_SERVER['REQUEST_URI'];
                $buttonText = "";

                foreach ($stLinks as $stLink) {
                    if ($_POST['actionToPerform'] == $stLink->value) {
                        $formUrl = get_site_url() . $stLink->link;
                        $buttonText = $stLink->description;
                    }
                }

                // CFU characters for update
                $output  = "<form name=\"CFU_Form\" method='post' action=\"" . $formUrl . "\">";
                $output .= "<table class='gvplugin' id=\"gvid_uctcs\">
                                <tr><th class=\"gvthead gvcol_1\">Character Name</th>
                                    <th class=\"gvthead gvcol_2\">Type</th>
                                    <th class=\"gvthead gvcol_3\">Character Status</th>
                                    <th class=\"gvthead gvcol_4\">Player Name</th>
                                    <th class=\"gvthead gvcol_5\">Player Status</th>
                                    <th class=\"gvthead gvcol_6\">Visible</th>
                                    <th class=\"gvthead gvcol_7\">&nbsp;</th></tr>";

                foreach ($characters as $character) {
                    $output .= "<tr><td class=\"gvcol_1 gvcol_key\">"  . $character->cname       .
                        "</td><td class=\"gvcol_2 gvcol_val\">" . $character->typename    .
                        "</td><td class=\"gvcol_3 gvcol_val\">" . $character->cstatusname .
                        "</td><td class='gvcol_4 gvcol_val'>" . $character->pname       .
                        "</td><td class='gvcol_5 gvcol_val'>" . $character->pstatusname .
                        "</td><td class='gvcol_6 gvcol_val'>" . $character->visible;
                    if ($formUrl == $_SERVER['REQUEST_URI']) {
                        $output .= "</td><td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"characterID\" value=\"" . $character->id . "\" /></td></tr>";
                    }
                    else {
                        $output .= "</td><td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"GVLARP_CHARACTER\" value=\"" . $character->wid . "\" /></td></tr>";
                    }
                }
                if ($formUrl == $_SERVER['REQUEST_URI']) {
                    $output .= "<tr><td class=\"gvcol_1 gvcol_key\">New Character</td>
                                        <td class=\"gvcol_2 gvcol_val\"></td>
                                        <td class=\"gvcol_3 gvcol_val\"></td>
                                        <td class='gvcol_4 gvcol_val'></td>
                                        <td class='gvcol_5 gvcol_val'></td>
                                        <td class='gvcol_6 gvcol_val'></td><td class=\"gvcol_7 gvcol_val\">" .
                        "<input type='RADIO' name=\"characterID\" value=\"0\" /></td></tr>";
                }
                $output .= "<tr style='display:none'><td colspan=7><input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdateCharacter\" /></td></tr>";
                $output .= "<tr><td colspan=7>";

                // Show original buttons if we are editing character otherwise jump to page
                if ($formUrl == $_SERVER['REQUEST_URI']) {
                    $output .= "<table class='gvplugin' id=\"gvid_ucs\"><tr><td class=\"gvcol_1 gvcol_val\">";
                    $output .= "<tr><td><input type='submit' name=\"cSubmit\" value=\"Update Character\" /></td><td>";
                    $output .= "<tr><td><input type='submit' name=\"cSubmit\" value=\"Delete Character\" /></td><td>";
                    $output .= "<tr><td><input type='submit' name=\"cSubmit\" value=\"Back to Category Selection\" /></td></tr></table>";
                }
                else {
                    $output .= "<tr><td colspan=7><input type='submit' name=\"cSubmit\" value=\"" . $buttonText . "\">";
                }
                $output .= "</td></tr></table></form>";
            }
            else {
                $output  = "<form name=\"CSF_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
                $output .= "<table class='gvplugin' id=\"gvid_ucts\"><tr><th class=\"gvthead gvcol_1\">Category</th>
                                                                               <th class=\"gvthead gvcol_2\">Selection</th></tr>";

                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Character Group</td>
                                    <td class=\"gvcol_2 gvcol_val\"><select name=\"characterGroup\"><option value=\"\">All Groups</option>";
                $characterTypes = listCharacterTypes();
                foreach ($characterTypes as $characterType) {
                    $output .= "<option";
                    if ($characterType->name == 'PC') {
                        $output .= " SELECTED";
                    }
                    $output .= ">" . $characterType->name . "</option>";
                }
                $output .= "</select></td></tr>";

                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Character Status</td>
                                    <td class=\"gvcol_2 gvcol_val\"><select name=\"characterStatus\"><option value=\"\">All Statuses</option>";
                $characterStatuses = listCharacterStatuses();
                foreach ($characterStatuses as $characterStatus) {
                    $output .= "<option";
                    if ($characterStatus->name == 'Alive'){
                        $output .= " SELECTED";
                    }
                    $output .= ">" . $characterStatus->name . "</option>";
                }
                $output .= "</select></td></tr>";

                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Player Name</td>
                                    <td class=\"gvcol_2 gvcol_val\"><select name=\"playerName\"><option value=\"\">All Players</option>";
                $players = listPlayers("", "");
                foreach ($players as $player) {
                    $output .= "<option>" . $player->name . "</option>";
                }
                $output .= "</select></td></tr>";

                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Player Status</td>
                                    <td class=\"gvcol_2 gvcol_val\"><select name=\"playerStatus\"><option value=\"\">All Statuses</option>";
                $playerStatuses = listPlayerStatus();
                foreach ($playerStatuses as $playerStatus) {
                    $output .= "<option";
                    if ($playerStatus->name == 'Active'){
                        $output .= " SELECTED";
                    }
                    $output .= ">" . $playerStatus->name . "</option>";
                }
                $output .= "</select></td></tr>";

                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Show not visible characters</td>
                                    <td class=\"gvcol_2 gvcol_val\"><select name=\"showNotVisible\">";
                $output .= "<option value=\"Y\">Yes</option><option value=\"\" SELECTED>No</option></select></td></tr>";

                $stLinks = listSTLinks();
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Action to perform</td>
                                    <td class=\"gvcol_2 gvcol_val\"><select name=\"actionToPerform\">";
                $output .= "<option value='update'>Create/Update/Delete Character</option>";
                foreach ($stLinks as $stLink) {
                    $output .= "<option value=\"" . $stLink->value . "\"";
                    if ($stLink->description == 'View Character Sheet') {
                        $output .= " SELECTED";
                    }
                    $output .= ">" . $stLink->description . "</option>";
                }
                $output .= "</select></td></tr>";

                $output .= "<tr style='display:none'><td colspan=2><input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdateCharacter\" /></td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\" colspan=2><input type='submit' name=\"cSubmit\" value=\"Display Characters\"></td></tr></table></form>";
            }
        }
        return $output;
    }
    add_shortcode('update_character_table', 'print_character_update_table');

    function print_player_admin($atts, $content=null) {
        extract(shortcode_atts(array ("playerstatus" => "",
            "playerType"   => ""), $atts));
        $output = "";
        if (!isSt()) {
            $output = "<br /><h2>Only STs can access the player update table!</h2><br />";
        }
        else {
            $playerID = $_POST['playerID'];
            if ($_POST['GVLARP_FORM'] == "displayUpdatePlayer"
                && $playerID != ""
                && $_POST['cSubmit'] == "Update Player") {
                $output = displayUpdatePlayer($playerID);
            }
            elseif ($_POST['GVLARP_FORM'] == "displayUpdatePlayer"
                && $_POST['cSubmit'] == "Submit player changes") {
                $processOut = processPlayerUpdate($playerID);
                if ((int) ($processOut) > 0) {
                    $playerID = $processOut;
                    $output  = "<br /><center><strong>Update successful</strong></center><br />";
                }
                else {
                    $playerID = 0;
                    $output  = "<br /><center><strong>Update failed<br />" . $processOut . "</strong></center><br />";
                }
                $output .= displayUpdatePlayer($playerID);
            }
            elseif (($_POST['GVLARP_FORM'] == "displayUpdatePlayer"
                && $_POST['cSubmit'] == "Display Players")) {

                $playerstatus = $_POST['playerStatus'];
                $playertype   = $_POST['playerType'];

                $players  = listPlayers($playerstatus, $playertype);

                // CFU characters for update
                $output  = "<form name=\"PFU_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
                $output .= "<table class='gvplugin' id='gvid_ppa'><tr><th class=\"gvthead gvcol_1\">Player Name</th>
                                                                            <th class=\"gvthead gvcol_2\">Player Type</th>
                                                                            <th class=\"gvthead gvcol_3\">Player Status</th>
                                                                            <th class=\"gvthead gvcol_4\">&nbsp;</th></tr>";

                foreach ($players as $player) {
                    $output .= "<tr><td class=\"gvcol_1 gvcol_key\">"  . $player->name       .
                        "</td><td class=\"gvcol_2 gvcol_val\">"  . $player->typename   .
                        "</td><td class=\"gvcol_3 gvcol_val\">"  . $player->statusname .
                        "</td><td class='gvcol_4 gvcol_val'><input type='RADIO' name=\"playerID\" value=\"" . $player->ID . "\" /></td></tr>";
                }
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">New Player</td>
                                    <td class=\"gvcol_2 gvcol_val\"></td>
                                    <td class=\"gvcol_3 gvcol_val\"></td>
                                    <td class='gvcol_4 gvcol_val'>" .
                    "<input type='RADIO' name=\"playerID\" value=\"0\" /></td></tr>";
                $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdatePlayer\" /></td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\" colspan=4>";

                $output .= "<table class='gvplugin' id=\"gvid_ppa_in1\"><tr><td class=\"gvcol_1 gvcol_val\">";
                $output .= "<input type='submit' name=\"cSubmit\" value=\"Update Player\" /></td><td class=\"gvcol_2 gvcol_val\">";
                $output .= "<input type='submit' name=\"cSubmit\" value=\"Back to Category Selection\" /></td></tr></table>";
                $output .= "</td></tr></table></form>";
            }
            else {
                $output  = "<form name=\"PSF_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
                $output .= "<table class='gvplugin' id=\"gvid_ppa_in2\"><tr><th class=\"gvthead gvcol_1\">Category</th>
                                                                                  <th class=\"gvthead gvcol_2\">Selection</th></tr>";

                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Player Type</td>
                                    <td class=\"gvcol_2 gvcol_val\"><select name=\"playerType\"><option value=\"\">All Types</option>";
                $playerTypes = listPlayerType();
                foreach ($playerTypes as $playerType) {
                    $output .= "<option value=\"" . $playerType->ID . "\"";
                    if ($playerType->name == 'Player'){
                        $output .= " SELECTED";
                    }
                    $output .= ">" . $playerType->name . "</option>";
                }
                $output .= "</select></td></tr>";

                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Player Status</td>
                                    <td class=\"gvcol_2 gvcol_val\"><select name=\"playerStatus\"><option value=\"\">All Statuses</option>";
                $playerStatuses = listPlayerStatus();
                foreach ($playerStatuses as $playerStatus) {
                    $output .= "<option value=\"" . $playerStatus->ID . "\"";
                    if ($playerStatus->name == 'Active'){
                        $output .= " SELECTED";
                    }
                    $output .= ">" . $playerStatus->name . "</option>";
                }
                $output .= "</select></td></tr>";

                $output .= "<tr style='display:none'><td colspan=2><input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdatePlayer\" /></td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\" colspan=2><input type='submit' name=\"cSubmit\" value=\"Display Players\"></td></tr></table></form>";
            }
        }
        return $output;
    }
    add_shortcode('player_admin', 'print_player_admin');

    function print_xp_spend_table($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "visible" => "N"), $atts));
        $character = establishCharacter($character);

        if (!isST()) {
            $visible = "N";
        }

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        // Replacing all underscores with strings
        $defaultTrainingString = "Tell us who is teaching you or how you are learning.";
        $defaultSpecialisation = "New Specialisation";
        $trainingNote          = $_POST['trainingNote'];
        $xpSpend               = $_POST['xp_spend'];
        $specialisation        = $_POST['spec_' . $xpSpend];

        $output    = "";

        $postedCharacter = $_POST['character'];

        if ($_POST['GVLARP_FORM'] == "applyXPSpend"
            && $postedCharacter != ""
            && $_POST['xSubmit'] == "Spend XP") {
            if (strlen($xpSpend) > 4 && substr($xpSpend, -5) == "_spec") {
                if ($specialisation == "" || $specialisation == $defaultSpecialisation) {
                    $output = "<p>No valid specialisation provided, XP Spend aborted</p>";
                }
            }

            if(!isST()) {
                if ($trainingNote == "" || $trainingNote == $defaultTrainingString) {
                    $output .= "<p>No training note was supplied, XP spend aborted</p>";
                }
            }

            if ($output == "") {
                if (!isST()) {
                    $output = "<p>" . doPendingXPSpend($postedCharacter, $_POST['xp_spend'], $trainingNote, $specialisation) . "</p>";
                }
                else {
                    $output = "<p>" . doXPSpend($postedCharacter, $_POST['xp_spend'], 0, $specialisation) . "</p>";
                }
            }
        }

        $attributes = array ("character" => $character, "group" => "TOTAL", "maxrecords" => "1");
        $xp_total = print_character_xp_table($attributes, null);

        $maxRating     = 5;
        $maxDiscipline = 5;

        $sql = "SELECT gen.max_rating, gen.max_discipline
                    FROM " . $table_prefix . "CHARACTER chara,
                         " . $table_prefix . "GENERATION gen
                    WHERE chara.generation_id = gen.id
                      AND chara.WORDPRESS_ID = %s";

        $characterMaximums = $wpdb->get_results($wpdb->prepare($sql, $character));
        foreach ($characterMaximums as $charMax) {
            $maxRating = $charMax->max_rating;
            $maxDiscipline = $charMax->max_discipline;
        }

        $sqlOutput = "";
        $sql = "SELECT stat.name, cha_stat.comment, cha_stat.level, cha_stat.id, cmstep.xp_cost, cmstep.next_value, stat.specialisation_at spec_at
                        FROM " . $table_prefix . "CHARACTER_STAT cha_stat,
                             " . $table_prefix . "STAT stat,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "COST_MODEL_STEP cmstep
                        WHERE cha_stat.STAT_ID      = stat.ID
                          AND cha_stat.CHARACTER_ID = chara.ID
                          AND stat.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                          AND cmstep.current_value  = cha_stat.level
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                       ORDER BY stat.ordering";

        $character_stats_xp = $wpdb->get_results($wpdb->prepare($sql, $character));

        foreach ($character_stats_xp as $stat_xp) {
            $specialisationIndicator = "";
            $sqlOutput .= "<tr><th class=\"gvthleft\">" . $stat_xp->name . "</th><td class=\"gvcol_2 gvcol_val\">";
            if ($stat_xp->next_value == $stat_xp->spec_at) {
                $specialisationIndicator = "_spec";
                $sqlOutput .= "<input type='text' name=\"spec_stat_" . $stat_xp->id . $specialisationIndicator . "\" value=\"" . $defaultSpecialisation . "\" size=20 maxlength=60>";
            }
            else {
                $sqlOutput .= $stat_xp->comment;
            }
            $sqlOutput .= "</td><td class=\"gvcol_3 gvcol_val\">" . $stat_xp->level
                . "</td><td class='gvcol_4 gvcol_val'>=></td>";
            if (((int)$stat_xp->next_value) > ((int) $stat_xp->level)
                && ((((int)$stat_xp->next_value) <= $maxRating) || $stat_xp->name == "Willpower")) {
                $sqlOutput .= "<td class='gvcol_5 gvcol_val'>" . $stat_xp->next_value
                    .  "</td><td class='gvcol_6 gvcol_val'>" . $stat_xp->xp_cost . "</td>";
                if (((int) $xp_total) >= ((int)$stat_xp->xp_cost)) {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\">
                                      <input type='RADIO' name=\"xp_spend\" value=\"stat_" . $stat_xp->id . $specialisationIndicator . "\" /></td>";
                }
                else {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
                }
                $sqlOutput .= "</tr>";
            }
            else {
                $sqlOutput .= "<td colspan=3 class='gvcol_5 gvcol_val'>No xp spend available</td>";
            }
        }

        $output .= "<form name=\"SPEND_XP_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<table class='gvplugin' id=\"gvid_xpst\">";

        if ($sqlOutput != "") {
            $output .= "<tr><th class=\"gvthead gvcol_1\">Name</th>
                                <th class=\"gvthead gvcol_2\">Specialisation</th>
                                <th class=\"gvthead gvcol_3\">Current</th>
                                <th class=\"gvthead gvcol_4\"></th>
                                <th class=\"gvthead gvcol_5\">New</th>
                                <th class=\"gvthead gvcol_6\">Cost</th>
                                <th class=\"gvthead gvcol_7\"></th></tr>";
            $output .= $sqlOutput;
        }

        $visibleSector = "";
        if ($visible == 'N') {
            $visibleSector = " AND skill.visible = 'Y' ";
        }

        $sql = "SELECT skill.name,
                           cha_skill.comment,
                           cha_skill.level,
                           cha_skill.id,
                           skill.specialisation_at spec_at,
                           cmstep.xp_cost,
                           cmstep.next_value,
                           1 ordering
                    FROM " . $table_prefix . "CHARACTER_SKILL cha_skill,
                         " . $table_prefix . "SKILL skill,
                         " . $table_prefix . "CHARACTER chara,
                         " . $table_prefix . "COST_MODEL_STEP cmstep
                    WHERE cha_skill.SKILL_ID     = skill.ID
                      AND cha_skill.CHARACTER_ID = chara.ID
                      AND skill.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                      AND cmstep.current_value   = cha_skill.level
                      AND chara.DELETED != 'Y'
                      AND chara.WORDPRESS_ID = %s
                    union
                    SELECT skill.name,
                           '' comment,
                           0 level,
                           skill.id,
                           skill.specialisation_at spec_at,
                           cmstep.xp_cost,
                           cmstep.next_value,
                           2 ordering
                    FROM " . $table_prefix . "SKILL skill,
                         " . $table_prefix . "COST_MODEL_STEP cmstep
                    WHERE skill.COST_MODEL_ID  = cmstep.COST_MODEL_ID
                      AND cmstep.current_value = 0 "
            . $visibleSector . "
                      AND (skill.id NOT IN (SELECT skill_id
                                            FROM " . $table_prefix . "CHARACTER_SKILL cha_skill,
                                                 " . $table_prefix . "CHARACTER chara
                                            WHERE chara.ID = cha_skill.CHARACTER_ID
                                              AND chara.WORDPRESS_ID = %s)
                           OR skill.multiple = 'Y')
                    ORDER BY ordering, name";

        $character_skill_xp = $wpdb->get_results($wpdb->prepare($sql, $character, $character));

        $sqlOutput = "";
        foreach ($character_skill_xp as $skill_xp) {
            $specialisationIndicator = "";

            $sqlOutput .= "<tr><th class=\"gvthleft\">" . $skill_xp->name . "</th><td class=\"gvcol_2 gvcol_val\">";
            if ($skill_xp->next_value == $skill_xp->spec_at
                && ((int)$skill_xp->next_value) > ((int) $skill_xp->level)
                && ((int)$xp_total) >= ((int)$skill_xp->xp_cost)
                && ((int)$skill_xp->next_value) <= $maxRating) {
                $specialisationIndicator = "_spec";
                $sqlOutput .= "<input type='text' name=\"spec_skill_";
                if ((int)$skill_xp->level == 0) {
                    $sqlOutput .= "new_";
                }
                $sqlOutput .= $skill_xp->id . $specialisationIndicator;
                $sqlOutput .= "\" value=\"" . $defaultSpecialisation . "\" size=20 maxlength=60>";
            }
            else {
                $sqlOutput .= $skill_xp->comment;
            }

            $sqlOutput .= "</td><td class=\"gvcol_3 gvcol_val\">" . $skill_xp->level
                . "</td><td class='gvcol_4 gvcol_val'>=></td>";

            if (((int)$skill_xp->next_value) > ((int) $skill_xp->level)
                && ((int)$skill_xp->next_value) <= $maxRating) {
                $sqlOutput .= "<td class='gvcol_5 gvcol_val'>" . $skill_xp->next_value
                    .  "</td><td class='gvcol_6 gvcol_val'>" . $skill_xp->xp_cost . "</td>";
                if (((int) $xp_total) >= ((int)$skill_xp->xp_cost)) {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"skill_";
                    if ((int)$skill_xp->level == 0) {
                        $sqlOutput .= "new_";
                    }
                    $sqlOutput .= $skill_xp->id . $specialisationIndicator . "\" /></td>";
                }
                else {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
                }
                $sqlOutput .= "</tr>";
            }
            else {
                $sqlOutput .= "<td class='gvcol_5 gvcol_val' colspan=3>No xp spend available</td>";
            }
        }

        if ($sqlOutput != "") {
            $output .= "<tr><th class=\"gvthead gvcol_1\">Name</th>
                                <th class=\"gvthead gvcol_2\">Specialisation</th>
                                <th class=\"gvthead gvcol_3\">Current</th>
                                <th class=\"gvthead gvcol_4\"></th>
                                <th class=\"gvthead gvcol_5\">New</th>
                                <th class=\"gvthead gvcol_6\">Cost</th>
                                <th class=\"gvthead gvcol_7\"></th></tr>"
                . $sqlOutput;
        }

        if ($visible == 'N') {
            $visibleSector = " AND new_dis.dis_vis = 'Y' ";
        }

        $config = getConfig();
        $clanDisciplineDiscount = $config->CLAN_DISCIPLINE_DISCOUNT;
        $factor =  0;
        if ($clanDisciplineDiscount == 0) {
            $offset = 0;
        }
        else if ($clanDisciplineDiscount < 1) {
            $offset = 0;
            $factor = $clanDisciplineDiscount;
        }
        else {
            $offset = $clanDisciplineDiscount;
        }

        $innerTable = "(SELECT dis.name dis_name, " . $offset . " xp_offset, " . $factor . " xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis,
                                     " . $table_prefix . "CHARACTER chara,
                                     " . $table_prefix . "CLAN_DISCIPLINE clandis
                                WHERE chara.private_clan_id = clandis.CLAN_ID
                                  AND clandis.DISCIPLINE_id = dis.ID
                                  AND chara.wordpress_id = %s
                                union
                                SELECT dis.name dis_id, 0 xp_offset, 0 xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis
                                WHERE dis.ID NOT IN (SELECT dis.ID
                                                     FROM " . $table_prefix . "DISCIPLINE dis,
                                                          " . $table_prefix . "CHARACTER chara,
                                                          " . $table_prefix . "CLAN_DISCIPLINE clandis
                                                     WHERE chara.private_clan_id = clandis.CLAN_ID
                                                       AND clandis.DISCIPLINE_id = dis.ID
                                                       AND chara.wordpress_id = %s)) new_dis ";

        $sql = "SELECT new_dis.dis_name, cha_dis.comment, cha_dis.level, cha_dis.id, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.next_value, 1 ordering
                        FROM " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "COST_MODEL_STEP cmstep,
                             $innerTable
                        WHERE cha_dis.DISCIPLINE_ID  = new_dis.dis_id
                          AND cha_dis.CHARACTER_ID   = chara.ID
                          AND new_dis.cmid           = cmstep.COST_MODEL_ID
                          AND cmstep.current_value   = cha_dis.level
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                        union
                        SELECT new_dis.dis_name, '' comment, 0 level, dis_id id, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.next_value, 2 ordering
                        FROM " . $table_prefix . "COST_MODEL_STEP cmstep,
                             " . $innerTable . "
                        WHERE new_dis.cmid = cmstep.COST_MODEL_ID
                          AND cmstep.current_value = 0 "
            . $visibleSector . "
                          AND new_dis.dis_id NOT IN (SELECT discipline_id
                                                     FROM " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
                                                          " . $table_prefix . "CHARACTER chara
                                                     WHERE chara.ID = cha_dis.CHARACTER_ID
                                                     AND chara.WORDPRESS_ID = %s)
                        ORDER BY ordering, dis_name";

        $sql = $wpdb->prepare($sql, $character, $character, $character, $character, $character, $character);
        $character_discipline_xp = $wpdb->get_results($sql);

        $sqlOutput = "";
        foreach ($character_discipline_xp as $dis_xp) {
            $sqlOutput .= "<tr><th class=\"gvthleft\">" . $dis_xp->dis_name
                . "</th><td class=\"gvcol_2 gvcol_val\">" . $dis_xp->comment
                . "</td><td class=\"gvcol_3 gvcol_val\">" . $dis_xp->level
                . "</td><td class='gvcol_4 gvcol_val'>=></td>";

            if (((int)$dis_xp->next_value) > ((int) $dis_xp->level)
                && ((int)$dis_xp->next_value) <= $maxDiscipline) {
                $sqlOutput .= "<td class='gvcol_5 gvcol_val'>" . $dis_xp->next_value
                    .  "</td><td class='gvcol_6 gvcol_val'>" . $dis_xp->xp_cost . "</td>";
                if (((int) $xp_total) >= ((int)$dis_xp->xp_cost)) {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"dis_";
                    if ((int)$dis_xp->level == 0) {
                        $sqlOutput .= "new_";
                    }
                    $sqlOutput .= $dis_xp->id . "\" /></td>";
                }
                else {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
                }
            }
            else {
                $sqlOutput .= "<td class='gvcol_5 gvcol_val' colspan=3>No xp spend available</td>";
            }
            $sqlOutput .= "</tr>";
        }

        if ($sqlOutput != "") {
            $output .= "<tr><th class=\"gvthead gvcol_1\">Name</th>
                                <th class=\"gvthead gvcol_2\">&nbsp;</th>
                                <th class=\"gvthead gvcol_3\">Current</th>
                                <th class=\"gvthead gvcol_4\"></th>
                                <th class=\"gvthead gvcol_5\">New</th>
                                <th class=\"gvthead gvcol_6\">Cost</th>
                                <th class=\"gvthead gvcol_7\"></th></tr>";
            $output .= $sqlOutput;
        }

        /*******************************************************************************************/

        $visibleSector = "";
        if ($visible == 'N') {
            $visibleSector = " AND path.visible = 'Y' ";
        }

        $sql = "SELECT path.name path_name, dis.name dis_name, cha_path.level, cha_path.id, cmstep.xp_cost, cmstep.next_value, cha_dis.level cha_dis_level, 1 ordering
                        FROM " . $table_prefix . "CHARACTER_PATH cha_path,
                             " . $table_prefix . "PATH path,
                             " . $table_prefix . "DISCIPLINE dis,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
                             " . $table_prefix . "COST_MODEL_STEP cmstep
                        WHERE cha_path.PATH_ID       = path.ID
                          AND path.DISCIPLINE_ID     = dis.ID
                          AND cha_path.CHARACTER_ID  = chara.ID
                          AND path.COST_MODEL_ID     = cmstep.COST_MODEL_ID
                          AND cmstep.current_value   = cha_path.level
                          AND cha_dis.CHARACTER_ID   = chara.ID
                          AND cha_dis.DISCIPLINE_ID  = dis.ID
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                        union
                        SELECT path.name path_name, dis.name dis_name, 0 level, path.id, cmstep.xp_cost, cmstep.next_value, cha_dis.level cha_dis_level, 2 ordering
                        FROM " . $table_prefix . "PATH path,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "DISCIPLINE dis,
                             " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
                             " . $table_prefix . "COST_MODEL_STEP cmstep
                        WHERE path.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                          AND cmstep.current_value  = 0
                          AND cha_dis.CHARACTER_ID  = chara.ID
                          AND cha_dis.DISCIPLINE_ID = path.DISCIPLINE_ID
                          AND path.DISCIPLINE_ID    = dis.ID
                          AND cha_dis.level        >= cmstep.next_value
                          AND chara.WORDPRESS_ID = %s
                          AND chara.DELETED != 'Y' "
            . $visibleSector . "
                          AND path.id NOT IN (SELECT path_id
                                              FROM " . $table_prefix . "CHARACTER_PATH cha_path,
                                                   " . $table_prefix . "CHARACTER chara
                                              WHERE chara.ID = cha_path.CHARACTER_ID
                                                AND chara.WORDPRESS_ID = %s)
                        ORDER BY dis_name, ordering, path_name";

        $character_path_xp = $wpdb->get_results($wpdb->prepare($sql, $character, $character, $character));

        $sqlOutput = "";
        foreach ($character_path_xp as $path_xp) {
            $sqlOutput .= "<tr><th class=\"gvthleft\">" . $path_xp->path_name
                . "</th><td class=\"gvcol_2 gvcol_val\">" . $path_xp->dis_name
                . "</td><td class=\"gvcol_3 gvcol_val\">" . $path_xp->level
                . "</td><td class='gvcol_4 gvcol_val'>=></td>";

            if (((int)$path_xp->next_value) >  ((int) $path_xp->level)
                && ((int)$path_xp->next_value) <= ((int) $path_xp->cha_dis_level)
                && ((int)$path_xp->next_value) <= $maxDiscipline) {
                $sqlOutput .= "<td class='gvcol_5 gvcol_val'>" . $path_xp->next_value
                    .  "</td><td class='gvcol_6 gvcol_val'>" . $path_xp->xp_cost . "</td>";
                if (((int) $xp_total) >= ((int)$path_xp->xp_cost)) {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"path_";
                    if ((int)$path_xp->level == 0) {
                        $sqlOutput .= "new_";
                    }
                    $sqlOutput .= $path_xp->id . "\" /></td>";
                }
                else {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
                }
                $sqlOutput .= "</tr>";
            }
            else {
                $sqlOutput .= "<td class='gvcol_5 gvcol_val' colspan=3>No xp spend available</td>";
            }
        }

        if ($sqlOutput != "") {
            $output .= "<tr><th class=\"gvthead gvcol_1\">Path</th>
                                <th class=\"gvthead gvcol_2\">Discipline</th>
                                <th class=\"gvthead gvcol_3\">Current</th>
                                <th class=\"gvthead gvcol_4\"></th>
                                <th class=\"gvthead gvcol_5\">New</th>
                                <th class=\"gvthead gvcol_6\">Cost</th>
                                <th class=\"gvthead gvcol_7\"></th></tr>"
                . $sqlOutput;
        }

        /*******************************************************************************************/

        $visibleSector = "";
        if ($visible == 'N') {
            $visibleSector = " AND rit.visible = 'Y' ";
        }

        $sql = "SELECT rit.name rit_name, dis.name dis_name, rit.level, rit.id, rit.cost, 1 ordering
                        FROM " . $table_prefix . "CHARACTER_RITUAL cha_rit,
                             " . $table_prefix . "RITUAL rit,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "DISCIPLINE dis
                        WHERE cha_rit.RITUAL_ID     = rit.ID
                          AND cha_rit.CHARACTER_ID  = chara.ID
                          AND rit.DISCIPLINE_ID     = dis.ID
                          AND chara.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s
                        union
                        SELECT rit.name rit_name, dis.name dis_name, rit.level, rit.id, rit.cost, 2 ordering
                        FROM " . $table_prefix . "RITUAL rit,
                             " . $table_prefix . "DISCIPLINE dis,
                             " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis
                        WHERE cha_dis.CHARACTER_ID = chara.ID
                          AND cha_dis.DISCIPLINE_ID = rit.DISCIPLINE_ID
                          AND rit.DISCIPLINE_ID = dis.ID
                          AND cha_dis.level >= rit.level
                          AND chara.WORDPRESS_ID = %s
                          AND chara.DELETED != 'Y' "
            . $visibleSector . "
                          AND rit.id NOT IN (SELECT ritual_id
                                               FROM " . $table_prefix . "CHARACTER_RITUAL cha_rit,
                                                    " . $table_prefix . "CHARACTER chara
                                               WHERE chara.ID = cha_rit.CHARACTER_ID
                                                 AND chara.WORDPRESS_ID = %s)
                        ORDER BY dis_name, ordering, level, rit_name";

        $character_ritual_xp = $wpdb->get_results($wpdb->prepare($sql, $character, $character, $character));

        $sqlOutput = "";
        foreach ($character_ritual_xp as $ritual_xp) {
            $sqlOutput .= "<tr><th class=\"gvthleft\">" . $ritual_xp->rit_name
                . "</th><td class=\"gvcol_2 gvcol_val\">" . $ritual_xp->dis_name
                . "</td><td class=\"gvcol_3 gvcol_val\">" . $ritual_xp->level
                . "</td>";

            if ($ritual_xp->ordering == 2
                && ((int)$ritual_xp->level) <= $maxDiscipline) {
                $sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=2>&nbsp;</td><td class='gvcol_6 gvcol_val'>" . $ritual_xp->cost . "</td>";
                if (((int) $xp_total) >= ((int)$ritual_xp->cost)) {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"ritual_new_";
                    $sqlOutput .= $ritual_xp->id . "\" /></td>";
                }
                else {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
                }
                $sqlOutput .= "</tr>";
            }
            else {
                $sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=4>Ritual known</td>";
            }
        }

        if ($sqlOutput != "") {
            $output .= "<tr><th class=\"gvthead gvcol_1\">Ritual</th>
                                <th class=\"gvthead gvcol_2\">Discipline</th>
                                <th class=\"gvthead gvcol_3\">Level</th>
                                <th class=\"gvthead gvcol_4\"></th>
                                <th class=\"gvthead gvcol_5\"></th>
                                <th class=\"gvthead gvcol_6\">Cost</th>
                                <th class=\"gvthead gvcol_7\"></th></tr>"
                . $sqlOutput;
        }

        /*****************************************************************/

        $visibleSector = "";
        if ($visible == 'N') {
            $visibleSector = " AND merit.visible = 'Y' ";
        }

        $sql = "SELECT merit.name merit_name, merit.id, merit.value, merit.xp_cost, cha_merit.id cha_merit_id, 1 ordering
                    FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
                         " . $table_prefix . "MERIT merit,
                         " . $table_prefix . "CHARACTER chara
                    WHERE cha_merit.MERIT_ID     = merit.ID
                      AND cha_merit.CHARACTER_ID = chara.ID
                      AND chara.DELETED != 'Y'
                      AND chara.WORDPRESS_ID = %s
                      AND merit.value > 0
                    union
                    SELECT merit.name merit_name, merit.id, merit.value, merit.xp_cost, cha_merit.id cha_merit_id, 2 ordering
                    FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
                         " . $table_prefix . "MERIT merit,
                         " . $table_prefix . "CHARACTER chara
                    WHERE cha_merit.MERIT_ID     = merit.ID
                      AND cha_merit.CHARACTER_ID = chara.ID
                      AND chara.DELETED != 'Y'
                      AND chara.WORDPRESS_ID = %s
                      AND merit.value < 0
                      AND merit.xp_cost = 0
                    union
                    SELECT merit.name merit_name, merit.id, merit.value, merit.xp_cost, cha_merit.id cha_merit_id, 3 ordering
                    FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
                         " . $table_prefix . "MERIT merit,
                         " . $table_prefix . "CHARACTER chara
                    WHERE cha_merit.MERIT_ID     = merit.ID
                      AND cha_merit.CHARACTER_ID = chara.ID
                      AND chara.DELETED != 'Y'
                      AND chara.WORDPRESS_ID = %s
                      AND merit.value < 0
                      AND merit.xp_cost != 0
                    union
                    SELECT merit.name merit_name, merit.id, merit.value, merit.xp_cost, 0 cha_merit_id, 4 ordering
                    FROM " . $table_prefix . "MERIT merit
                    WHERE merit.xp_cost != 0
                      AND merit.value > 0 "
            . $visibleSector . "
                      AND (merit.id NOT IN (SELECT merit_id
                                            FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
                                                 " . $table_prefix . "CHARACTER chara
                                            WHERE chara.ID = cha_merit.CHARACTER_ID
                                              AND chara.WORDPRESS_ID = %s)
                           OR merit.id IN (SELECT id
                                           FROM " . $table_prefix . "MERIT
                                           WHERE multiple = 'Y'))
                      ORDER BY ordering, merit_name";

        $sql = $wpdb->prepare($sql, $character, $character, $character, $character);
        $character_merit_xp = $wpdb->get_results($sql);

        $sqlOutput = "";
        foreach ($character_merit_xp as $merit_xp) {
            $sqlOutput .= "<tr><th class=\"gvthleft\" colspan=2>" . $merit_xp->merit_name
                . "</th><td class=\"gvcol_3 gvcol_val\">" . $merit_xp->value
                . "</td>";

            if ($merit_xp->ordering == 3) {
                $sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=2>&nbsp;</td><td class='gvcol_6 gvcol_val'>" . $merit_xp->xp_cost . "</td>";
                if (((int) $xp_total) >= ((int) $merit_xp->xp_cost)) {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"merit_";
                    $sqlOutput .= $merit_xp->cha_merit_id . "\" /></td>";
                }
                else {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
                }
            }
            else if ($merit_xp->ordering == 4) {
                $sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=2>&nbsp;</td><td class='gvcol_6 gvcol_val'>" . $merit_xp->xp_cost . "</td>";
                if (((int) $xp_total) >= ((int) $merit_xp->xp_cost)) {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"merit_new_";
                    $sqlOutput .= $merit_xp->id . "\" /></td>";
                }
                else {
                    $sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
                }
            }
            else {
                $meritFlaw = "Merit already acquired";
                if ($merit_xp->ordering == 2) {
                    $meritFlaw = "Flaw cannot be bought off";
                }
                $sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=4>" . $meritFlaw . "</td>";
            }
            $sqlOutput .= "</tr>";
        }

        if ($sqlOutput != "") {
            $output .= "<tr><th class=\"gvthead gvcol_1\" colspan=2>Merit</th>
                                <th class=\"gvthead gvcol_3\">Value</th>
                                <th class=\"gvthead gvcol_4\"></th>
                                <th class=\"gvthead gvcol_5\"></th>
                                <th class=\"gvthead gvcol_6\">Cost</th>
                                <th class=\"gvthead gvcol_7\"></th></tr>"
                . $sqlOutput;
        }

        /*****************************************************************/


        if ($_POST['GVLARP_CHARACTER'] != "") {
            $output .= "tr style='display:none'><td colspan=7><input type='HIDDEN' name=\"GVLARP_CHARACTER\" value=\"" . $_POST['GVLARP_CHARACTER'] . "\" /></td></tr>\n";
        }

        $output .= "<tr style='display:none'><td colspan=7><input type='HIDDEN' name=\"character\" value=\"" . $character . "\"></td></tr>\n";
        $output .= "<tr style='display:none'><td colspan=7><input type='HIDDEN' name=\"GVLARP_FORM\" value=\"applyXPSpend\" /></td></tr>\n";
        $output .= "<tr><td colspan=7>Training Notes: <input type='text' name=\"trainingNote\" value=\"" . $defaultTrainingString ."\" size=80 maxlength=160 /></td></tr>\n";
        $output .= "<tr><td colspan=7><input type='submit' name=\"xSubmit\" value=\"Spend XP\"></td></tr></table></form>\n";

        return $output;
    }
    add_shortcode('xp_spend_table', 'print_xp_spend_table');

    function print_xp_approval_table($atts, $content=null) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $output    = "";
        $sqlOutput = "";

        if (!isST()) {
            return "<br /><h2>Only STs can access the pending XP table!</h2><br />";
        }

        if ($_POST['GVLARP_FORM'] == "displayPendingXPSpends"
            && $_POST['pxSubmit'] == "Approve XP Spends") {

            $approvedXPSpends = $_POST['approved_xp_spends'];
            $count = count($approvedXPSpends);

            $character      = "";
            $characterName  = "";
            $xpSpendCode    = "";
            $xpSpendComment = "";
            $xpTrainingNote = "";
            $xpSpendOutput  = "";
            $specialisation = "";
            $xpCost         = "";

            for ($i = 0; $i < $count; $i++) {
                $sql = "SELECT chara.name cname, chara.wordpress_id wpid, pxps.code, pxps.comment, pxps.specialisation, pxps.amount
                                FROM " . $table_prefix . "PENDING_XP_SPEND pxps,
                                 " . $table_prefix . "CHARACTER chara
                                WHERE pxps.character_id = chara.id
                                  AND pxps.id = %d";

                $xpSpendDetails = $wpdb->get_results($wpdb->prepare($sql, $approvedXPSpends[$i]));
                foreach ($xpSpendDetails as $xpSpend) {
                    $character = $xpSpend->wpid;
                    $xpSpendCode    = $xpSpend->code;
                    $characterName  = $xpSpend->cname;
                    $xpSpendComment = $xpSpend->comment;
                    $specialisation = $xpSpend->specialisation;
                    $xpCost         = $xpSpend->amount;
                }

                $xpSpendOutput = doXPSpend($character, $xpSpendCode, $xpCost, $specialisation);

                if (substr($xpSpendOutput, 0, 8) == "XP Spend") {
                    $delete = "DELETE FROM " . $table_prefix . "PENDING_XP_SPEND
                                       WHERE id = %d";
                    $wpdb->query($wpdb->prepare($delete, $approvedXPSpends[$i]));

                    $output .= $xpSpendOutput . "<br />";
                }
                else {
                    $output .= "Xp Spend (" . $xpSpendComment . ") for " . $characterName
                        . " failed with comment: <br />" . $xpSpendOutput . "<br />";
                }
            }
        }

        $deniedXPSpends = $_POST['denied_xp_spends'];
        $count = count($deniedXPSpends);

        for ($i = 0; $i < $count; $i++) {
            $sql = "SELECT chara.name cname, pxps.comment, pxps.amount
                            FROM " . $table_prefix . "PENDING_XP_SPEND pxps,
                             " . $table_prefix . "CHARACTER chara
                            WHERE pxps.character_id = chara.id
                              AND pxps.id = %d";

            $xpSpendDetails = $wpdb->get_results($wpdb->prepare($sql, $deniedXPSpends[$i]));
            foreach ($xpSpendDetails as $xpSpend) {
                $characterName  = $xpSpend->cname;
                $xpSpendComment = $xpSpend->comment;
                $xpCost         = $xpSpend->amount;
            }

            $delete = "DELETE FROM " . $table_prefix . "PENDING_XP_SPEND
                               WHERE id = %d";
            $wpdb->query($wpdb->prepare($delete, $deniedXPSpends[$i]));

            $output .= "Denied " . $xpSpendComment . " to " . $characterName . ". " . $xpCost . " XP freed up<br />";
        }

        $sql = "SELECT chara.name cname,
                           chara.wordpress_id wpid,
                           pxps.id,
                           pxps.awarded,
                           pxps.amount,
                           pxps.comment,
                           pxps.specialisation,
                           pxps.training_note
                    FROM " . $table_prefix . "PENDING_XP_SPEND pxps,
                         " . $table_prefix . "CHARACTER chara
                    WHERE pxps.character_id = chara.id
                    ORDER BY chara.name, awarded";

        $pending_xp_spends = $wpdb->get_results($sql);

        $viewCharacterSheetLink = "";
        $stLinks = listSTLinks();
        foreach ($stLinks as $stLink) {
            if ($stLink->description == 'View Character Sheet') {
                $viewCharacterSheetLink = get_site_url() . $stLink->link;
            }
        }

        foreach ($pending_xp_spends as $xp_spend) {
            $sqlOutput .= "<tr><td class=\"gvcol_1 gvcol_key\">";
            if ($viewCharacterSheetLink == "") {
                $sqlOutput .= $xp_spend->cname;
            }
            else {
                $sqlOutput .= "<a href=\"" . $viewCharacterSheetLink . "?CHARACTER=" . urldecode($xp_spend->wpid) . "\">"
                    . $xp_spend->cname . "</a>";
            }

            $sqlOutput .= "</td><td class=\"gvcol_2 gvcol_val\">" . $xp_spend->comment
                .  "</td><td class=\"gvcol_3 gvcol_val\">" . $xp_spend->specialisation
                .  "</td><td class='gvcol_4 gvcol_val'>" . $xp_spend->training_note
                .  "</td><td class='gvcol_5 gvcol_val'>" . $xp_spend->awarded
                .  "</td><td class='gvcol_6 gvcol_val'>" . $xp_spend->amount
                .  "</td><td class=\"gvcol_7 gvcol_val\"><input type=\"checkbox\" name=\"approved_xp_spends[]\" value=\"" . $xp_spend->id . "\">"
                .  "</td><td class=\"gvcol_8 gvcol_val\"><input type=\"checkbox\" name=\"denied_xp_spends[]\" value=\"" . $xp_spend->id . "\">"
                .  "</td></tr>";
        }

        if ($sqlOutput == "") {
            $output .= "There are no pending XP Spends<br />";
        }
        else {
            $output .= "<form name=\"PXP_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
            $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayPendingXPSpends\" />";
            $output .= "<table class='gvplugin' id=\"gvid_xpa\"><tr><th class=\"gvthead gvcol_1\">Character Name</th>
                                                                          <th class=\"gvthead gvcol_2\">XP Spend</th>
                                                                          <th class=\"gvthead gvcol_3\">Specialisation</th>
                                                                          <th class=\"gvthead gvcol_4\">Training</th>
                                                                          <th class=\"gvthead gvcol_5\">Requested</th>
                                                                          <th class=\"gvthead gvcol_6\">XP Cost</th>
                                                                          <th class=\"gvthead gvcol_7\">Approve</th>
                                                                          <th class=\"gvthead gvcol_8\">Deny</th></tr>";
            $output .= $sqlOutput;
            $output .= "<tr><td colspan=8><input type='submit' name=\"pxSubmit\" value=\"Approve XP Spends\"></td></tr></table></form>";
        }

        return $output;
    }
    add_shortcode('xp_approval', 'print_xp_approval_table');

    function print_character_profile($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => "Full", "pwchange" => "False"), $atts));

        if (isset($_POST['CHARACTER'])) {
            $character = $_POST['CHARACTER'];
        }
        else if (isset($_GET['CHARACTER'])) {
            $character = $_GET['CHARACTER'];
        }

        $password = true;

        $currentUser      = wp_get_current_user();
        $currentCharacter = $currentUser->user_login;
        $observerClan     = "";

        if ($character == "null" || $character == "") {
            if ($currentCharacter == null || $currentCharacter == "") {
                return "You need to specify a character or be logged in to view the profiles.<br />";
            }
            $character = $currentCharacter;
        }

        $showAll = false;
        if (isST() || $character == $currentCharacter) {
            $showAll = true;
        }
        $output = "";

        $showUpdateTable = true;
        $displayName = $currentUser->display_name;
        if (isST()) {
            $user = get_userdatabylogin($character);
            $displayName = $user->display_name;
            $userID = $user->ID;

            if ($userID == null || $userID == "" || !((int) ($userID) > 0)) {
                $showUpdateTable = false;
            }
        }

        if ($_POST['GVLARP_FORM'] == "updateProfile" && $showAll && $showUpdateTable) {

            $oldDisplayName = $currentUser->display_name;
            if (isST()) {
                $user = get_userdatabylogin($character);
                $oldDisplayName = $user->display_name;
            }
            $newDisplayName = $_POST['displayName'];

            if ($newDisplayName != null
                && $newDisplayName != ""
                && $newDisplayName != $oldDisplayName) {
                changeDisplayName($character, $newDisplayName);
                $output .= "Changed display name to <b>" . $newDisplayName . "</b><br />";
                $currentUser = wp_get_current_user();
            }

            if ($password == true) {
                $newPassword1 = $_POST['newPassword1'];
                $newPassword2 = $_POST['newPassword2'];

                if ($newPassword1 != null && $newPassword1 != ""
                    && $newPassword2 != null && $newPassword2 != "") {
                    if (changePassword($character, $newPassword1, $newPassword2)) {
                        $output .= "Successfully changed password<br />";
                        $currentUser = wp_get_current_user();
                    }
                    else {
                        $output .= "Failed to change password likely they didn't match<br />";
                    }
                }
            }
        }

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT chara.id                        cid,
                           chara.name                      cname,
                           chara.character_status_comment  cstat_comment,
                           chara.wordpress_id              wpid,
                           cstat.name                      cstat_name,
                           player.name                     pname,
                           court.name                      court,
                           pub_clan.name                   public_clan,
                           pub_clan.icon_link              public_icon,
                           priv_clan.name                  private_clan,
                           priv_clan.icon_link             private_icon
                    FROM " . $table_prefix . "CHARACTER chara,
                         " . $table_prefix . "CHARACTER_STATUS cstat,
                         " . $table_prefix . "PLAYER player,
                         " . $table_prefix . "COURT court,
                         " . $table_prefix . "CLAN pub_clan,
                         " . $table_prefix . "CLAN priv_clan
                    WHERE chara.PUBLIC_CLAN_ID = pub_clan.ID
                      AND chara.PRIVATE_CLAN_ID = priv_clan.ID
                      AND chara.COURT_ID = court.ID
                      AND chara.PLAYER_ID = player.ID
                      AND chara.CHARACTER_STATUS_ID = cstat.ID
                      AND chara.DELETED = 'N'
                      AND chara.WORDPRESS_ID = %s";

        $sql = $wpdb->prepare($sql, $character);
        $character_details = $wpdb->get_results($sql);

        $characterID               = -1;
        $characterName             = "";
        $playerName                = "";
        $courtName                 = "";
        $publicClan                = "";
        $privateClan               = "";
        $publicIcon                = "";
        $privateIcon               = "";
        $status                    = 0;
        $clanPrestige              = 0;
        $clanFriendship            = "";
        $clanEnmity                = "";
        $positions                 = "";
        $quote                     = "";
        $imageURL                  = "";
        $characterCondition        = "";
        $characterConditionComment = "";
        $wordpressId               = "";

        foreach ($character_details as $character_detail) {
            $characterID               = $character_detail->cid;
            $characterName             = $character_detail->cname;
            $wordpressId               = $character_detail->wpid;
            $characterCondition        = $character_detail->cstat_name;
            $characterConditionComment = $character_detail->cstat_comment;
            $playerName                = $character_detail->pname;
            $courtName                 = $character_detail->court;
            $publicClan                = $character_detail->public_clan;
            $privateClan               = $character_detail->private_clan;
            $publicIcon                = $character_detail->public_icon;
            $privateIcon               = $character_detail->private_icon;
        }

        if ($characterID == -1) {
            return "No information found for (" . $character . ")<br />";
        }

        $sql = "SELECT cback.level
                        FROM " . $table_prefix . "CHARACTER_BACKGROUND cback,
                             " . $table_prefix . "BACKGROUND back
                        WHERE cback.BACKGROUND_ID = back.ID
                          AND cback.CHARACTER_ID = %d
                          AND back.name = 'Status' ";

        $sql = $wpdb->prepare($sql, $characterID);
        // $output .= "Character ID: " . $characterID . "<br />" . $sql . "<br />";
        $characterStatus = $wpdb->get_results($sql);

        foreach ($characterStatus as $currentStatus) {
            $status = $currentStatus->level;
        }

        $sql = "SELECT pub_clan.name public_clan
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CLAN pub_clan
                        WHERE chara.PUBLIC_CLAN_ID = pub_clan.ID
                          AND chara.DELETED = 'N'
                          AND chara.WORDPRESS_ID = %s";

        $sql = $wpdb->prepare($sql, $currentCharacter);
        // $output .= "Current Character: " . $currentCharacter . "<br />" . $sql . "<br />";
        $observerClans = $wpdb->get_results($sql);
        foreach ($observerClans as $currentClan) {
            $observerClan = $currentClan->public_clan;
        }

        $sql = "SELECT cback.level, cback.comment
                        FROM " . $table_prefix . "CHARACTER_BACKGROUND cback,
                             " . $table_prefix . "BACKGROUND back
                        WHERE cback.BACKGROUND_ID = back.ID
                          AND cback.CHARACTER_ID = %d
                          AND back.name = 'Clan Prestige' ";

        if (!$showAll) {
            $sql .= "  AND cback.COMMENT = '" . $observerClan . "' ";
        }

        $sql = $wpdb->prepare($sql, $characterID);
        // $output .= "Character ID: " . $characterID . "<br />" . $sql . "<br />";
        $clanPrestiges = $wpdb->get_results($sql);

        foreach ($clanPrestiges as $currentPrestige) {
            if ($showAll || $currentPrestige->comment == $observerClan) {
                $clanPrestige = $currentPrestige->level;
                if ($currentPrestige->comment != $publicClan) {
                    $clanPrestige .= " (" . $currentPrestige->comment . ")";
                }
            }
        }

        $sql = "SELECT cmerit.level, cmerit.comment, merit.name
                        FROM " . $table_prefix . "CHARACTER_MERIT cmerit,
                             " . $table_prefix . "MERIT merit
                        WHERE cmerit.merit_ID = merit.ID
                          AND cmerit.CHARACTER_ID = %d
                          AND (merit.name = 'Clan Friendship' OR merit.name = 'Clan Enmity') ";

        if (!$showAll) {
            $sql .= "  AND cmerit.COMMENT = '" . $observerClan . "' ";
        }

        $sql = $wpdb->prepare($sql, $characterID);
        // $output .= "Character ID: " . $characterID . "<br />" . $sql . "<br />";
        $clanMerits = $wpdb->get_results($sql);

        foreach ($clanMerits as $currentMerit) {
            if ($showAll || $currentMerit->comment == $observerClan) {
                if ($currentMerit->name == 'Clan Friendship') {
                    if ($clanFriendship != "") {
                        $clanFriendship .= "<br />";
                    }
                    $clanFriendship .= $currentMerit->comment;
                }
                else {
                    if ($clanEnmity != "") {
                        $clanEnmity .= "<br />";
                    }
                    $clanEnmity .= $currentMerit->comment;
                }
            }
        }

        $sql = "SELECT office.name office_name, court.name court_name
                        FROM " . $table_prefix . "CHARACTER_OFFICE coffice,
                             " . $table_prefix . "OFFICE office,
                             " . $table_prefix . "COURT court
                        WHERE coffice.character_id = %d
                          AND coffice.office_id    = office.id
                          AND coffice.court_id     = court.id
                        ORDER BY office.ordering";

        $sql = $wpdb->prepare($sql, $characterID);
        // $output .= "Character ID: " . $characterID . "<br />" . $sql . "<br />";
        $offices = $wpdb->get_results($sql);

        foreach ($offices as $currentOffice) {
            if ($positions != "") {
                $positions .= "<br />";
            }
            $positions .= $currentOffice->office_name;
            if ($courtName != $currentOffice->court_name) {
                $positions .= " (" . $currentOffice->court_name . ")";
            }
        }

        $sql = "SELECT profile.quote, profile.portrait
                        FROM " . $table_prefix . "CHARACTER_PROFILE profile
                        WHERE profile.CHARACTER_ID = %d";

        $sql = $wpdb->prepare($sql, $characterID);
        // $output .= "Character ID: " . $characterID . "<br />" . $sql . "<br />";
        $profiles = $wpdb->get_results($sql);

        foreach ($profiles as $profile) {
            $quote    = $profile->quote;
            $imageURL = $profile->portrait;
        }

        if ($imageURL == "") {
            $config = getConfig();
            $imageURL = $config->PLACEHOLDER_IMAGE;
        }

        /*************************************************************************/

        if ($showAll && $showUpdateTable) {
            $output .= "<form name=\"PROFILE_UPDATE_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
            $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"updateProfile\" />";
            if (isset($_POST['CHARACTER'])) {
                $output .= "<input type='HIDDEN' name=\"CHARACTER\" value=\"" . $character . "\" />";
            }
        }

        $viewCharacterSheetLink = "";
        if (isST()) {
            $stLinks = listSTLinks();
            foreach ($stLinks as $stLink) {
                if ($stLink->description == 'View Character Sheet') {
                    $viewCharacterSheetLink = get_site_url() . $stLink->link;
                }
            }
        }
        $output .= "<table class='gvplugin' id=\"gvid_prof_out\"><tr><th colspan=2 class=\"gvthhead\">";

        if ($viewCharacterSheetLink == "") {
            $output .= $characterName;
        }
        else {
            $output .= "<a href=\"" . $viewCharacterSheetLink . "?CHARACTER=" . urldecode($wordpressId) . "\">"
                .  $characterName . "</a>";
        }
        $output .= "</th></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_val\"><p><img src=\"";

        if ($showAll) {
            $output .= $privateIcon;
        }
        else {
            $output .= $publicIcon;
        }

        $output .= "\">" . $quote . "<p><table class='gvplugin' id=\"gvid_prof_in\">";

        $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Player:</td><td class=\"gvcol_2 gvcol_val\">" . $playerName . "</td></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Clan:</td><td class=\"gvcol_2 gvcol_val\">";
        if ($showAll && $publicClan != $privateClan) {
            $output .= $publicClan . " (" . $privateClan . ")";
        }
        else {
            $output .= $publicClan;
        }
        $output .= "</td></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Resides:</td><td class=\"gvcol_2 gvcol_val\">" . $courtName . "</td></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Status:</td><td class=\"gvcol_2 gvcol_val\">" . $status . "</td></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Condition:</td><td class=\"gvcol_2 gvcol_val\">" . $characterCondition;
        if ($characterConditionComment != "") {
            $output .= " (" . $characterConditionComment . ")";
        }
        $output .= "</td></tr>";
        if ($showAll || $observerClan == $publicClan) {
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Clan Prestige:</td><td class=\"gvcol_2 gvcol_val\">" . $clanPrestige . "</td></tr>";
        }

        if ($clanFriendship != "") {
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Clan Friendship:</td><td class=\"gvcol_2 gvcol_val\">" . $clanFriendship . "</td></tr>";
        }
        if ($clanEnmity != "") {
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Clan Enmity:</td><td class=\"gvcol_2 gvcol_val\">" . $clanEnmity . "</td></tr>";
        }
        if ($positions != "") {
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Positions:</td><td class=\"gvcol_2 gvcol_val\">" . $positions . "</td></tr>";
        }

        if ($showAll && $showUpdateTable) {
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Display Name:</td><td class=\"gvcol_2 gvcol_val\">";
            $output .= "<input type='text' size=50 maxlength=50 name=\"displayName\" value=\"" . $displayName . "\">";
            $output .= "</td></tr>";

            if ($password == true) {
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">New Password:</td><td class=\"gvcol_2 gvcol_val\">";
                $output .= "<input type=\"password\" name=\"newPassword1\">";
                $output .= "</td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Confirm New Password:</td><td class=\"gvcol_2 gvcol_val\">";
                $output .= "<input type=\"password\" name=\"newPassword2\">";
                $output .= "</td></tr>";
            }

            $buttonText = "Change Display Name";
            if ($password == true) {
                $buttonText .= "/Password";
            }
            $output .= "<tr><td colspan=2 class=\"gvcol_1 gvcol_submit\">";
            $output .= "<input type='submit' name=\"profileUpdate\" value=\"" . $buttonText . "\">";
            $output .= "</td></tr>";
        }

        $output .= "</table></p>";

        if ($character == $currentCharacter) {
            $output .= "</form>";
        }

        //        if (!$showAll) {
        //            $output .= "<p>Click to send <a href=\"\">private message</a></p>";
        //        }
        $output .= "</td><td class=\"gvcol_2 gvcol_img\"><img src=\"" . $imageURL . "\"></td></tr></table>";

        return $output;
    }
    add_shortcode('character_profile', 'print_character_profile');

    function print_character_expanded_backgrounds($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);
        $output = "";

        if ($_POST['GVLARP_FORM'] == "displayExpandedBackgrounds"
            && $_POST['ebSubmit'] == "Submit Background Updates") {
            $characterID = establishCharacterID($character);

            if ($characterID == $_POST['characterID']) {
                $maxCounter = $_POST['maxCounter'];

                $updateCounter = 0;
                if (((int) $maxCounter) > 0) {
                    for ($i = 0; $i < $maxCounter; $i++) {
                        $proposed = stripslashes($_POST['proposed_' . $i]);
                        if ($proposed != null && $proposed != "") {
                            if (isset($_POST['ecbid_' . $i])) {
                                updateExtendedBackground($_POST['ecbid_' . $i], $proposed);
                            }
                            else {
                                addExtendedBackground($_POST['title_' . $i], $_POST['code_' . $i], $proposed, $characterID);
                            }
                            $updateCounter++;
                        }
                    }
                    $output .= $updateCounter . " update";
                    if ($updateCounter != 1) {
                        $output .= "s";
                    }
                    $output .= " have been made to extended backgrounds<br />";
                }
                else {
                    $output .= "Max Counter " . $maxCounter . " is not a positive int<br />No updates initiated<br />";
                }
            }
            else {
                $output .= "Stored character ID (" . $_POST['characterID']
                    .  ") is different from established ID (" . $characterID
                    .  ")<br />No updates initiated<br />";
            }
        }

        $output .= displayCharacterExtendedBackground($character);
        return $output;
    }
    add_shortcode('character_expanded_background', 'print_character_expanded_backgrounds');

    function print_extended_character_background_approvals($atts, $content=null) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $output    = "";
        $sqlOutput = "";

        if (!isST()) {
            return "<br /><h2>Only STs can access the extended background approval section!</h2><br />";
        }


        if ($_POST['GVLARP_FORM'] == "displayPendingExtendedBackgrounds"
            && $_POST['pebSubmit'] == "Approve/Deny Expanded Backgrounds") {

            $approvedExpBackgrounds = $_POST['approved_extBack'];
            $count = count($approvedExpBackgrounds);

            for ($i = 0; $i < $count; $i++) {
                approveExtendedBackground($approvedExpBackgrounds[$i]);
            }

            if ($count > 0) {
                $output .= $count . " extended background";
                if ($count != 1) {
                    $output .= "s";
                }
                $output .= " approved<br />";
            }

            $deniedExpBackgrounds = $_POST['denied_extBack'];
            $count = count($deniedExpBackgrounds);

            for ($i = 0; $i < $count; $i++) {
                $id = $deniedExpBackgrounds[$i];
                $reason = $_POST['reason_' . $id];

                denyExtendedBackground($id, $reason);
            }

            if ($count > 0) {
                $output .= $count . " extended background";
                if ($count != 1) {
                    $output .= "s";
                }
                $output .= " denied<br />";
            }
        }

        $sql = "SELECT chara.name, exchaback.id, exchaback.title, exchaback.proposed_text, exchaback.current_text
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND exchaback,
                         " . $table_prefix . "CHARACTER chara
                    WHERE exchaback.character_id = chara.id
                      AND exchaback.current_accepted = 'W'
                    ORDER BY chara.name, exchaback.id ";

        $extBackgrounds = $wpdb->get_results($sql);

        foreach ($extBackgrounds as $extBackground) {
            $sqlOutput .= "<tr><td class=\"gvcol_1 gvcol_val\" width=50%>" . $extBackground->name . "</td><td class=\"gvcol_2 gvcol_val\" width=50%>" . $extBackground->title . "</td></tr>"
                .  "<tr><td class=\"gvcol_1 gvcol_val\">" . $extBackground->current_text . "</td><td class=\"gvcol_2 gvcol_val\">" . $extBackground->proposed_text . "</td></tr>"
                .  "<tr><td class=\"gvcol_1 gvcol_val\"><table class='gvplugin' id=\"gvid_inpeb\"><tr><td class=\"gvcol_1 gvcol_val\" align=\"center\">
                           Approve <input type=\"checkbox\" name=\"approved_extBack[]\" value=\"" . $extBackground->id . "\"></td><td class=\"gvcol_2 gvcol_val\" align=\"center\">"
                .  "Deny <input type=\"checkbox\" name=\"denied_extBack[]\" value=\"" . $extBackground->id . "\"></td><td class=\"gvcol_3 gvcol_val\" align=\"right\">Reason:</td></tr></table>"
                .  "</td><td class=\"gvcol_2 gvcol_val\"><input type='text' name=\"reason_" . $extBackground->id . "\" size=50 maxlength=200></td></tr>";
        }

        if ($sqlOutput == "") {
            $output .= "No expanded background updates waiting for approval";
        }
        else {
            $output .= "<form name=\"PEB_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
            $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayPendingExtendedBackgrounds\" />";
            $output .= "<table class='gvplugin' id=\"gvid_peb\">";
            $output .= $sqlOutput;
            $output .= "<tr><td colspan=2><input type='submit' name=\"pebSubmit\" value=\"Approve/Deny Expanded Backgrounds\"></td></tr></table></form>";        }

        return $output;
    }
    add_shortcode('extended_background_approvals', 'print_extended_character_background_approvals');

    function print_name_value_pairs($atts, $content=null) {
        $output = "";
        if (isST()) {
            $output .= "<table>";
            foreach($_POST as $key=>$value) {
                $output .= "<tr><td>" . $key . "</td><td>" . $value . "</td></tr>";
            }
            $output .= "</table>";
        }
        return $output;
    }
    add_shortcode('debug_name_value_pairs', 'print_name_value_pairs');

    function displaySingleExtendedBackground($extendedBackgroundID, $counter) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT title, current_text, proposed_text, current_accepted
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE id = %d ";

        $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $extendedBackgroundID));

        $title           = "";
        $currentText     = "";
        $proposedText    = "";
        $currentAccepted = "";

        foreach ($characterBackgrounds as $characterBackground) {
            $title           = $characterBackground->title;
            $currentText     = $characterBackground->current_text;
            $proposedText    = $characterBackground->proposed_text;
            $currentAccepted = $characterBackground->current_accepted;
        }

        if ($title == "") {
            return "No extended character background found with id (" . $extendedBackgroundID . ")";
        }

        $output  = "<table class='gvplugin' id=\"gvid_ecb\"><tr><th class=\"gvthead gvcol_1\">" . $title . "</th></tr>";
        $output .= "<tr style='display:none'><td><input type='HIDDEN' name=\"ecbid_" .$counter . "\" value=\"" . $extendedBackgroundID . "\"></td></tr>";
        if ($currentAccepted == "Y" || $currentAccepted == "R") {
            if ($currentText != null && $currentText != "") {
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Current</td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\">" . $currentText . "</td></tr>";
            }
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Proposed</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_val\">"
                . "<textarea name=\"proposed_" . $counter . "\" rows=\"5\" cols=\"100\">"
                . $proposedText . "</textarea></td></tr>";
        }
        else {
            if ($currentText != null && $currentText != "") {
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Current</td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\">" . $currentText . "</td></tr>";
            }

            if ($proposedText != null && $proposedText != "") {
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Proposed</td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\">" . $proposedText . "</td></tr>";
            }
        }
        $output .= "</table>";

        return $output;
    }

    function displayNewExtendedBackground($title, $code, $counter) {
         $output .= "<input type='HIDDEN' name=\"code_"        . $counter . "\" value=\"" . $code        . "\">";        $output .= "<input type='HIDDEN' name=\"title_"       . $counter . "\" value=\"" . $title       . "\">";		$output  = "<table class='gvplugin' id=\"gvid_ecb\"><tr><th class=\"gvthead gvcol_1\">" . $title . "</th></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Proposed</td></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_val\">"
            . "<textarea name=\"proposed_" . $counter . "\" rows=\"5\" cols=\"100\"></textarea></td></tr></table>";
        return $output;
    }

    function addExtendedBackground($title, $code, $proposed, $characterID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "INSERT INTO " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND (character_id,
                                                                                    title,
                                                                                    code,
                                                                                    proposed_text,
                                                                                    current_accepted)
                    VALUES (%d, %s, %s, %s, 'W')";
        $sql = $wpdb->prepare($sql, $characterID, $title, $code, $proposed);

        $wpdb->query($sql);
    }

    function updateExtendedBackground($id, $proposed) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT current_accepted
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE id = %d ";

        $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $id));

        $currentAccepted = "";

        foreach ($characterBackgrounds as $characterBackground) {
            $currentAccepted = $characterBackground->current_accepted;
        }

        if ($currentAccepted == "Y") {
            $updateSQL = "UPDATE " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                              SET current_accepted = 'W',
                                  proposed_text    = %s
                              WHERE id = %d";

            $wpdb->query($wpdb->prepare($updateSQL, $proposed, $id));
        }
        else if ($currentAccepted == "R") {
            $updateSQL = "UPDATE " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                              SET current_accepted = 'W',
                                  current_text     = %s,
                                  proposed_text    = ''
                              WHERE id = %d";

            $wpdb->query($wpdb->prepare($updateSQL, $proposed, $id));
        }
    }

    function approveExtendedBackground($id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT title, current_text, proposed_text, current_accepted
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE id = %d ";

        $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $id));

        $proposedText    = "";

        foreach ($characterBackgrounds as $characterBackground) {
            $proposedText    = $characterBackground->proposed_text;
        }

        $updateSQL = "UPDATE " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                          SET current_text = %s,
                              current_accepted = 'Y',
                              proposed_text = ''
                          WHERE id = %d";

        $wpdb->query($wpdb->prepare($updateSQL, $proposedText, $id));
    }

    function denyExtendedBackground($id, $reason) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT title, current_text, proposed_text, current_accepted
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE id = %d ";

        $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $id));

        $proposedText    = "";

        foreach ($characterBackgrounds as $characterBackground) {
            $proposedText    = $characterBackground->proposed_text;
        }

        $proposedText = "The proposed update below has been refused.\n\n" . $reason . "\n\n" . $proposedText;

        $updateSQL = "UPDATE " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                          SET current_accepted = 'R',
                              proposed_text = %s
                          WHERE id = %d";

        $wpdb->query($wpdb->prepare($updateSQL, $proposedText, $id));
    }

    function displayCharacterExtendedBackground($character) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $characterID = establishCharacterID($character);

        $sql = "SELECT id, title, code
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE character_id = %d ";

        $sql = $wpdb->prepare($sql, $characterID);

        $expandedCharacterBackgrounds = $wpdb->get_results($sql);

        $i = 0;
        $output  = "<form name=\"EBF_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayExpandedBackgrounds\" />";
        $output .= "<input type='HIDDEN' name=\"characterID\" value=\"". $characterID . "\" />";
        $output .= "<input type='HIDDEN' name=\"GVLARP_CHARACTER\" value=\"" . $character . "\" />";

        $clanFlaw = extractExtendedCharacterBackground($expandedCharacterBackgrounds, "clan_flaw");
        if ($clanFlaw != null) {
            $output .= displaySingleExtendedBackground($clanFlaw->id, $i);
        }
        else {
            $output .= displayNewExtendedBackground("Clan Flaw", "clan_flaw", $i);
        }
        $i++;

        $clanFlaw = extractExtendedCharacterBackground($expandedCharacterBackgrounds, "character_history");
        if ($clanFlaw != null) {
            $output .= displaySingleExtendedBackground($clanFlaw->id, $i);
        }
        else {
            $output .= displayNewExtendedBackground("Character History", "character_history", $i);
        }
        $i++;

        $sql = "SELECT merit.name, cmerit.id, cmerit.comment
                    FROM " . $table_prefix . "CHARACTER_MERIT cmerit,
                         " . $table_prefix . "MERIT merit
                    WHERE cmerit.character_id = %d
                      AND cmerit.merit_id     = merit.id
                    ORDER BY cmerit.level DESC, merit.name";

        $sql = $wpdb->prepare($sql, $characterID);
        $characterMerits = $wpdb->get_results($sql);


        foreach ($characterMerits as $characterMerit) {
            $currentCode = "merit_" . $characterMerit->id;
            $currentExtendedBackground = extractExtendedCharacterBackground($expandedCharacterBackgrounds, $currentCode);
            if ($currentExtendedBackground != null) {
                $output .= displaySingleExtendedBackground($currentExtendedBackground->id, $i);
            }
            else {
                $title = $characterMerit->name;
                if ($characterMerit->comment != null && $characterMerit->comment != "") {
                    $title .= " (" . $characterMerit->comment . ")";
                }
                $output .= displayNewExtendedBackground($title, $currentCode, $i);
            }
            $i++;
        }

        $sql = "SELECT background.name, cbackground.id, cbackground.comment
                    FROM " . $table_prefix . "CHARACTER_BACKGROUND cbackground,
                         " . $table_prefix . "BACKGROUND background
                    WHERE cbackground.character_id  = %d
                      AND cbackground.background_id = background.id
                    ORDER BY cbackground.level DESC, background.name";

        $sql = $wpdb->prepare($sql, $characterID);
        $characterBackgrounds = $wpdb->get_results($sql);

        foreach ($characterBackgrounds as $characterBackground) {
            $currentCode = "background_" . $characterBackground->id;
            $currentExtendedBackground = extractExtendedCharacterBackground($expandedCharacterBackgrounds, $currentCode);
            if ($currentExtendedBackground != null) {
                $output .= displaySingleExtendedBackground($currentExtendedBackground->id, $i);
            }
            else {
                $title = $characterBackground->name;
                if ($characterBackground->comment != null && $characterBackground->comment != "") {
                    $title .= " (" . $characterBackground->comment . ")";
                }
                $output .= displayNewExtendedBackground($title, $currentCode, $i);
            }
            $i++;
        }

        $output .= "<input type='HIDDEN' name=\"maxCounter\" value=\"" . $i . "\">";
        $output .= "<input type='HIDDEN' name=\"characterID\" value=\"" . $characterID . "\">";
        $output .= "<center><input type='submit' name=\"ebSubmit\" value=\"Submit Background Updates\"></center></form>";

        return $output;
    }

    function extractExtendedCharacterBackground($characterBackgrounds, $code) {

        foreach ($characterBackgrounds as $characterBackground) {
            if ($characterBackground->code == $code) {
                return $characterBackground;
            }
        }

        return null;
    }

    function displayUpdatePlayer($playerID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output = "";

        if ($playerID == "0" || (int) ($playerID) > 0) {
            $playerTypes    = listPlayerType();
            $playerStatuses = listPlayerStatus();

            $playerName     = "New Player";
            $playerTypeId   = "";
            $playerStatusId = "";

            if ((int) ($playerID) > 0) {
                $sql = "SELECT name, player_type_id, player_status_id
                                FROM " . $table_prefix . "PLAYER
                                WHERE id = %d";
                $playerDetails = $wpdb->get_results($wpdb->prepare($sql, $playerID));

                foreach ($playerDetails as $playerDetail) {
                    $playerName     = $playerDetail->name;
                    $playerTypeId   = $playerDetail->player_type_id;
                    $playerStatusId = $playerDetail->player_status_id;
                }
            }
            $output  = "<form name=\"PLAYER_UPDATE_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
            $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdatePlayer\" />";
            $output .= "<input type='HIDDEN' name=\"playerID\" value=\"" . $playerID . "\" />";

            $output .= "<table class='gvplugin' id=\"gvid_dup\"><tr><td class=\"gvcol_1 gvcol_key\">Player Name</td><td class=\"gvcol_2 gvcol_val\">
                                <input type='text' maxlength=60 name=\"playerName\" value=\"" . $playerName . "\"></td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Player Type</td><td class=\"gvcol_2 gvcol_val\"><select name=\"playerType\">";
            foreach ($playerTypes as $playerType) {
                $output .= "<option value=\"" . $playerType->ID . "\"";
                if ($playerType->ID == $playerTypeId || ($playerID == 0 && $playerType->name == 'Player')) {
                    $output .= " SELECTED";
                }
                $output .= ">" . $playerType->name . "</OPTION>";
            }
            $output .= "</select></td></tr>";

            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Player Status</td><td class=\"gvcol_2 gvcol_val\"><select name=\"playerStatus\">";
            foreach ($playerStatuses as $playerStatus) {
                $output .= "<option value=\"" . $playerStatus->ID . "\"";
                if ($playerStatus->ID == $playerStatusId || ($playerID == 0 && $playerStatus->name == 'Active')) {
                    $output .= " SELECTED";
                }
                $output .= ">" . $playerStatus->name . "</OPTION>";
            }
            $output .= "</select></td></tr></table>";
            $output .= "<table class='gvplugin' id=\"gvid_spc\">
                            <tr><td class=\"gvcol_1 gvcol_val\"><input type='submit' name=\"cSubmit\" value=\"Submit player changes\" /></td>
                                <td class=\"gvcol_2 gvcol_val\"><input type='submit' name=\"cSubmit\" value=\"Back to the player list\" /></td></tr></table>";
            $output .= "</form>";
        }
        else {
            $output .= "We encountered an illegal Player ID (". $playerID . ")";
        }
        return $output;
    }

    function doXPSpend($character, $xpSpendString, $xpBonus, $specialisation) {
        if (!isST()) {
            return "Only STs can spend XP on characters";
        }

        if ($xpBonus == null || $xpBonus == "") {
            $xpBonus = 0;
        }

        $character   = establishCharacter($character);
        $characterID = establishCharacterID($character);
        $playerID    = establishPlayerID($character);
        $xpReasonID  = establishXPReasonID("XP Spend");
        $tokens = explode("_", $xpSpendString);
        $attributes = array ("character" => $character, "group" => "TOTAL", maxrecords => "1");
        $xp_total = print_character_xp_table($attributes, null);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $checkSQL = "";
        $updateTraitSQL = "";
        $xp_cost = 1000;
        $traitName = "";
        $currentValue = "";
        $newValue     = "";

        $config = getConfig();
        $clanDisciplineDiscount = $config->CLAN_DISCIPLINE_DISCOUNT;
        $factor =  0;
        if ($clanDisciplineDiscount == 0) {
            $offset = 0;
        }
        else if ($clanDisciplineDiscount < 1) {
            $offset = 0;
            $factor = $clanDisciplineDiscount;
        }
        else {
            $offset = $clanDisciplineDiscount;
        }

        $innerTable = "(SELECT dis.name dis_name, " . $offset . " xp_offset, " . $factor . " xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis,
                                     " . $table_prefix . "CHARACTER chara,
                                     " . $table_prefix . "CLAN_DISCIPLINE clandis
                                WHERE chara.private_clan_id = clandis.CLAN_ID
                                  AND clandis.DISCIPLINE_id = dis.ID
                                  AND chara.wordpress_id = %s
                                union
                                SELECT dis.name dis_id, 0 xp_offset, 0 xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis
                                WHERE dis.ID NOT IN (SELECT dis.ID
                                                     FROM " . $table_prefix . "DISCIPLINE dis,
                                                          " . $table_prefix . "CHARACTER chara,
                                                          " . $table_prefix . "CLAN_DISCIPLINE clandis
                                                     WHERE chara.private_clan_id = clandis.CLAN_ID
                                                       AND clandis.DISCIPLINE_id = dis.ID
                                                       AND chara.wordpress_id = %s)) new_dis ";

        if ($tokens [0] == "stat") {
            $checkSQL = "SELECT stat.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "STAT stat,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_STAT cstat
                                 WHERE stat.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE  = cstat.level
                                   AND cstat.STAT_ID         = stat.ID
                                   AND cstat.CHARACTER_ID    = %s
                                   AND cstat.id              = %d";

            $updateStats = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updateStats as $updateStat) {
                $xp_cost      = $updateStat->xp_cost;
                $traitName    = $updateStat->name;
                $currentValue = $updateStat->current_value;
                $newValue     = $updateStat->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "UPDATE " . $table_prefix . "CHARACTER_STAT
                                           SET level   = %d,
                                               comment = %s
                                           WHERE ID = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $newValue, $specialisation, $tokens [1]);
            }
        }
        else if ($tokens [0] == "skill" && $tokens [1] == "new") {
            $checkSQL = "SELECT skill.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "SKILL skill,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE skill.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND skill.id              = %d";

            $newSkills = $wpdb->get_results($wpdb->prepare($checkSQL, $tokens [2]));
            foreach ($newSkills as $newSkill) {
                $xp_cost      = $newSkill->xp_cost;
                $traitName    = $newSkill->name;
                $currentValue = $newSkill->current_value;
                $newValue     = $newSkill->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_SKILL (character_id, skill_id, level, comment)
                                           VALUES (%d, %d, %d, %s)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue, $specialisation);
            }
        }
        else if ($tokens [0] == "skill") {
            $checkSQL = "SELECT skill.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "SKILL skill,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_SKILL cskill
                                 WHERE skill.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE  = cskill.level
                                   AND cskill.SKILL_ID       = skill.ID
                                   AND cskill.CHARACTER_ID   = %d
                                   AND cskill.id             = %d";

            $updateSkills = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updateSkills as $updateSkill) {
                $xp_cost      = $updateSkill->xp_cost;
                $traitName    = $updateSkill->name;
                $currentValue = $updateSkill->current_value;
                $newValue     = $updateSkill->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "UPDATE " . $table_prefix . "CHARACTER_SKILL
                                           SET level   = %d,
                                               comment = %s
                                           WHERE ID    = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $newValue, $specialisation, $tokens [1]);
            }
        }
        else if ($tokens [0] == "dis" && $tokens [1] == "new") {
            $checkSQL = "SELECT new_dis.dis_name name, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $innerTable   . ",
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE new_dis.cmid          = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND new_dis.dis_id        = %d";

            $newDisciplines = $wpdb->get_results($wpdb->prepare($checkSQL, $tokens [2]));
            foreach ($newDisciplines as $newDiscipline) {
                $xp_cost      = $newDiscipline->xp_cost;
                $traitName    = $newDiscipline->name;
                $currentValue = $newDiscipline->current_value;
                $newValue     = $newDiscipline->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_DISCIPLINE (character_id, discipline_id, level)
                                           VALUES (%d, %d, %d)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue);
            }
        }
        else if ($tokens [0] == "dis") {
            $checkSQL = "SELECT new_dis.dis_name name, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $innerTable . ",
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cdis
                                 WHERE new_dis.cmid         = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE = cdis.level
                                   AND cdis.DISCIPLINE_ID   = dis_id
                                   AND cdis.CHARACTER_ID    = %d
                                   AND cdis.id              = %d";

            $updateDisciplines = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updateDisciplines as $updateDiscipline) {
                $xp_cost      = $updateDiscipline->xp_cost;
                $traitName    = $updateDiscipline->name;
                $currentValue = $updateDiscipline->current_value;
                $newValue     = $updateDiscipline->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "UPDATE " . $table_prefix . "CHARACTER_DISCIPLINE
                                           SET level = %d
                                           WHERE ID  = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $newValue, $tokens [1]);
            }
        }
        else if ($tokens [0] == "path" && $tokens [1] == "new") {
            $checkSQL = "SELECT path.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "PATH path,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE path.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND cha_dis.DISCIPLINE_ID = path.discipline_id
                                   AND cha_dis.level        >= cmstep.next_value
                                   AND cha_dis.CHARACTER_ID  = %d
                                   AND path.id               = %d";

            $newPaths = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [2]));
            foreach ($newPaths as $newPath) {
                $xp_cost      = $newPath->xp_cost;
                $traitName    = $newPath->name;
                $currentValue = $newPath->current_value;
                $newValue     = $newPath->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_PATH (character_id, path_id, level)
                                           VALUES (%d, %d, %d)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue);
            }

        }
        else if ($tokens [0] == "path") {
            $checkSQL = "SELECT path.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "PATH path,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_PATH cpath,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cdis
                                 WHERE path.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE = cpath.level
                                   AND cpath.PATH_ID        = path.ID
                                   AND cdis.CHARACTER_ID    = cpath.CHARACTER_ID
                                   AND cdis.DISCIPLINE_ID   = path.DISCIPLINE_ID
                                   AND cdis.level          >= cmstep.next_value
                                   AND cpath.CHARACTER_ID   = %d
                                   AND cpath.id             = %d";

            $updatePaths = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updatePaths as $updatePath) {
                $xp_cost      = $updatePath->xp_cost;
                $traitName    = $updatePath->name;
                $currentValue = $updatePath->current_value;
                $newValue     = $updatePath->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "UPDATE " . $table_prefix . "CHARACTER_PATH
                                           SET level = %d
                                           WHERE ID  = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $newValue, $tokens [1]);
            }
        }
        else if ($tokens [0] == "ritual" && $tokens [1] == "new") {
            $checkSQL = "SELECT rit.name, rit.cost xp_cost,  0 current_value, rit.level next_value
                                 FROM " . $table_prefix . "RITUAL rit,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis
                                 WHERE cha_dis.DISCIPLINE_ID = rit.DISCIPLINE_ID
                                   AND cha_dis.level        >= rit.level
                                   AND cha_dis.CHARACTER_ID  = %d
                                   AND rit.id                = %d";

            $newRituals = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, ((int) $tokens [2])));
            foreach ($newRituals as $newRitual) {
                $xp_cost      = $newRitual->xp_cost;
                $traitName    = $newRitual->name;
                $currentValue = $newRitual->current_value;
                $newValue     = $newRitual->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_RITUAL (character_id, ritual_id, level)
                                           VALUES (%d, %d, %d)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue);
            }
        }
        else if ($tokens [0] == "merit" && $tokens [1] == "new") {
            $checkSQL = "SELECT merit.name, merit.xp_cost, 0 current_value, merit.value next_value
                             FROM " . $table_prefix . "MERIT merit
                             WHERE merit.id = %d ";
            $newMerits = $wpdb->get_results($wpdb->prepare($checkSQL, $tokens [2]));
            foreach ($newMerits as $newMerit) {
                $xp_cost      = $newMerit->xp_cost;
                $traitName    = $newMerit->name;
                $currentValue = $newMerit->current_value;
                $newValue     = $newMerit->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_MERIT (character_id, merit_id, level)
                                       VALUES (%d, %d, %d)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue);
            }
        }
        else if ($tokens [0] == "merit") {
            $checkSQL = "SELECT merit.name, merit.xp_cost, 'Afflicted' current_value, 'Removed' next_value
                             FROM " . $table_prefix . "MERIT merit,
                                  " . $table_prefix . "CHARACTER_MERIT cmerit
                             WHERE cmerit.merit_id = merit.id
                               AND cmerit.CHARACTER_ID = %d
                               AND cmerit.id           = %d ";
            $updateMerits = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updateMerits as $updateMerit) {
                $xp_cost      = $updateMerit->xp_cost;
                $traitName    = $updateMerit->name;
                $currentValue = "Afflicted";
                $newValue     = "Cured";
            }

            if ($newValue != "") {
                $updateTraitSQL = "DELETE FROM " . $table_prefix . "CHARACTER_MERIT
                                       WHERE CHARACTER_ID = %d
                                         AND ID           = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [1]);
            }
        }

        else {
            return "Illegal combination of characer (" . $character . ") and xpSpendString (" . $xpSpendString . ")";
        }

        if ($updateTraitSQL == "") {
            return "Could not find trait to update for character (" . $character . ") and xpSpendString (" . $xpSpendString . ") "
                . $newValue . "<p>" . $checkSQL . "</p>";
        }

        // Minus the XP Bonus because its the cost and hence a negative
        if ((((int) $xp_total) - $xpBonus - ((int) $xp_cost)) < 0) {
            return "Not enough XP (" . $xp_total . ") for update (" . $traitName . " " . $currentValue . " > " . $newValue . ") which costs (" . $xp_cost . ")";
        }
        $comment = $traitName . " " . $currentValue . " > " . $newValue;
        $updateXPSQL = "INSERT INTO " . $table_prefix . "PLAYER_XP (player_id,
                                                                            character_id,
                                                                            xp_reason_id,
                                                                            awarded,
                                                                            amount,
                                                                            comment)
                                VALUES (%d, %d, %d, SYSDATE(), %d, %s)";

        $negativeCost = $xp_cost * -1;
        $wpdb->query($wpdb->prepare($updateXPSQL, $playerID, $characterID, $xpReasonID, $negativeCost, $comment));


        $wpdb->query($updateTraitSQL);

        return "XP Spend (" . $comment . ") applied to " . $character . ", " . $xp_cost . " XP deducted.";
    }

    function doPendingXPSpend($character, $xpSpendString, $trainingNote, $specialisation) {
        $character   = establishCharacter($character);
        $characterID = establishCharacterID($character);
        $playerID    = establishPlayerID($character);
        $tokens = explode("_", $xpSpendString);
        $attributes = array ("character" => $character, "group" => "TOTAL", "maxrecords" => "1");
        $xp_total = print_character_xp_table($attributes, null);

        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $checkSQL = "";
        $xp_cost = 1000;
        $traitName = "";
        $currentValue = "";
        $newValue     = "";

        $config = getConfig();
        $clanDisciplineDiscount = $config->CLAN_DISCIPLINE_DISCOUNT;
        $factor =  0;
        if ($clanDisciplineDiscount == 0) {
            $offset = 0;
        }
        else if ($clanDisciplineDiscount < 1) {
            $offset = 0;
            $factor = $clanDisciplineDiscount;
        }
        else {
            $offset = $clanDisciplineDiscount;
        }

        $innerTable = "(SELECT dis.name dis_name, " . $offset . " xp_offset, " . $factor . " xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis,
                                     " . $table_prefix . "CHARACTER chara,
                                     " . $table_prefix . "CLAN_DISCIPLINE clandis
                                WHERE chara.private_clan_id = clandis.CLAN_ID
                                  AND clandis.DISCIPLINE_id = dis.ID
                                  AND chara.wordpress_id = %s
                                union
                                SELECT dis.name dis_id, 0 xp_offset, 0 xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis
                                WHERE dis.ID NOT IN (SELECT dis.ID
                                                     FROM " . $table_prefix . "DISCIPLINE dis,
                                                          " . $table_prefix . "CHARACTER chara,
                                                          " . $table_prefix . "CLAN_DISCIPLINE clandis
                                                     WHERE chara.private_clan_id = clandis.CLAN_ID
                                                       AND clandis.DISCIPLINE_id = dis.ID
                                                       AND chara.wordpress_id = %s)) new_dis ";

        if ($tokens [0] == "stat") {
            $checkSQL = "SELECT stat.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "STAT stat,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_STAT cstat
                                 WHERE stat.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE  = cstat.level
                                   AND cstat.STAT_ID         = stat.ID
                                   AND cstat.CHARACTER_ID    = %d
                                   AND cstat.id              = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $characterID, $tokens [1]);
        }
        else if ($tokens [0] == "skill" && $tokens [1] == "new") {
            $checkSQL = "SELECT skill.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "SKILL skill,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE skill.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND skill.id              = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $tokens [2]);
        }
        else if ($tokens [0] == "skill") {
            $checkSQL = "SELECT skill.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "SKILL skill,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_SKILL cskill
                                 WHERE skill.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE  = cskill.level
                                   AND cskill.SKILL_ID       = skill.ID
                                   AND cskill.CHARACTER_ID   = %d
                                   AND cskill.id             = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $characterID, $tokens [1]);
        }
        else if ($tokens [0] == "dis" && $tokens [1] == "new") {
            $checkSQL = "SELECT new_dis.dis_name name, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $innerTable   . ",
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE new_dis.cmid          = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND new_dis.dis_id        = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $character, $character, $tokens [2]);
        }
        else if ($tokens [0] == "dis") {
            $checkSQL = "SELECT new_dis.dis_name name, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $innerTable . ",
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cdis
                                 WHERE new_dis.cmid         = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE = cdis.level
                                   AND cdis.DISCIPLINE_ID   = dis_id
                                   AND cdis.CHARACTER_ID    = %d
                                   AND cdis.id              = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $character, $character, $characterID, $tokens [1]);

        }
        else if ($tokens [0] == "path" && $tokens [1] == "new") {
            $checkSQL = "SELECT path.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "PATH path,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE path.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value = 0
                                   AND path.id              = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $tokens [2]);

        }
        else if ($tokens [0] == "path") {
            $checkSQL = "SELECT path.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "PATH path,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_PATH cpath,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cdis
                                 WHERE path.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE = cpath.level
                                   AND cpath.PATH_ID        = path.ID
                                   AND cdis.CHARACTER_ID    = cpath.CHARACTER_ID
                                   AND cdis.DISCIPLINE_ID   = path.DISCIPLINE_ID
                                   AND cdis.level          >= cmstep.next_value
                                   AND cpath.CHARACTER_ID   = %d
                                   AND cpath.id             = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $characterID, $tokens [1]);

        }
        else if ($tokens [0] == "ritual" && $tokens [1] == "new") {
            $checkSQL = "SELECT rit.name, rit.cost xp_cost,  0 current_value, 'Learned' next_value
                                 FROM " . $table_prefix . "RITUAL rit
                                 WHERE rit.id = %d ";
            $checkSQL = $wpdb->prepare($checkSQL, $tokens [2]);
        }
        else if ($tokens [0] == "merit" && $tokens [1] == "new") {
            $checkSQL = "SELECT merit.name, merit.xp_cost, 0 current_value, 'Acquired' next_value
                             FROM " . $table_prefix . "MERIT merit
                             WHERE merit.id = %d ";
            $checkSQL = $wpdb->prepare($checkSQL, $tokens [2]);
        }
        else if ($tokens [0] == "merit") {
            $checkSQL = "SELECT merit.name, merit.xp_cost, 'Afflicted' current_value, 'Removed' next_value
                             FROM " . $table_prefix . "MERIT merit,
                                  " . $table_prefix . "CHARACTER_MERIT cmerit
                             WHERE cmerit.merit_id = merit.id
                               AND cmerit.CHARACTER_ID = %d
                               AND cmerit.id           = %d ";
            $checkSQL = $wpdb->prepare($checkSQL, $characterID, $tokens [1]);
        }
        else {
            return "Illegal combination of characer (" . $character . ") and xpSpendString (" . $xpSpendString . ")";
        }

        $xpSpendDetails = $wpdb->get_results($checkSQL);
        foreach ($xpSpendDetails as $xpSpend) {
            $xp_cost      = $xpSpend->xp_cost;
            $traitName    = $xpSpend->name;
            $currentValue = $xpSpend->current_value;
            $newValue     = $xpSpend->next_value;
        }

        if ($traitName == "") {
            return "Could not find trait to update for characer (" . $character . ") and xpSpendString (" . $xpSpendString . ") "
                . $newValue . "<p>" . $checkSQL . "</p>";
        }

        if ((((int) $xp_total) - ((int) $xp_cost)) < 0) {
            return "Not enough XP (" . $xp_total . ") for update (" . $traitName . " " . $currentValue . " > " . $newValue . ") which costs (" . $xp_cost . ")";
        }

        $comment = $traitName . " " . $currentValue . " > " . $newValue;
        $duplicateSQL = "SELECT id
                                 FROM " . $table_prefix . "PENDING_XP_SPEND
                                 WHERE character_id = %d
                                   AND comment      = %s
                                   AND code         = %s";

        $duplicateID = "";
        $duplicates = $wpdb->get_results($wpdb->prepare($duplicateSQL, $characterID, $comment, $xpSpendString));
        foreach ($duplicates as $duplicate) {
            $duplicateID = $duplicate->id;
        }

        if ($duplicateID != "") {
            return "A duplicate pending xp Spend (" . $comment . ") for " . $character . " is already pending.";
        }

        $duplicateSkill = "";
        if ($tokens [0] == "skill" && $tokens [2] == "spec") {
            $duplicateSQL = "SELECT skill.name, cskill_outer.comment
                                 FROM " . $table_prefix . "CHARACTER_SKILL cskill_inner,
                                      " . $table_prefix . "CHARACTER_SKILL cskill_outer,
                                      " . $table_prefix . "SKILL skill
                                 WHERE cskill_inner.CHARACTER_ID   = %d
                                   AND cskill_inner.id             = %d
                                   AND cskill_outer.skill_id       = cskill_inner.skill_id
                                   AND cskill_inner.skill_id       = skill.id
                                   AND cskill_outer.CHARACTER_ID   = cskill_inner.CHARACTER_ID";
            $duplicates = $wpdb->get_results($wpdb->prepare($duplicateSQL, $characterID, $tokens [1]));

            foreach ($duplicates as $duplicate) {
                if ($duplicate->comment == $specialisation) {
                    $duplicateSkill = $duplicates->name;
                }
            }
        }
        else if ($tokens [0] == "skill" && $tokens [3] == "spec") {
            $duplicateSQL = "SELECT skill.name, cskill.comment
                                 FROM " . $table_prefix . "CHARACTER_SKILL cskill,
                                      " . $table_prefix . "SKILL cskill
                                 WHERE cskill.CHARACTER_ID   = %d
                                   AND skill.id              = %d
                                   AND skill.skill_id        = cskill.skill_id";
            $duplicates = $wpdb->get_results($wpdb->prepare($duplicateSQL, $characterID, $tokens [2]));

            foreach ($duplicates as $duplicate) {
                if ($duplicate->comment == $specialisation) {
                    $duplicateSkill = $duplicates->name;
                }
            }
        }

        if ($duplicateSkill != "") {
            return "A duplicate skill (" . $duplicateSkill . ") with the same specialisation (" . $specialisation . ") already exists, XP Spend aborted";
        }

        $pendingXPSQL = "INSERT INTO " . $table_prefix . "PENDING_XP_SPEND(player_id,
                                                                                   character_id,
                                                                                   code,
                                                                                   awarded,
                                                                                   amount,
                                                                                   comment,
                                                                                   training_note,
                                                                                   specialisation)
                             VALUES (%d, %d, %s, SYSDATE(), %d, %s, %s, %s)";

        $negativeXpCost = $xp_cost * -1;

        $pendingXPSQL = $wpdb->prepare($pendingXPSQL, $playerID, $characterID, $xpSpendString, $negativeXpCost, $comment, $trainingNote, $specialisation);

        $wpdb->query($pendingXPSQL);

        return "PENDING XP Spend (" . $traitName . " " . $currentValue . " > " . $newValue . ") applied to " . $character
            . " " . $xp_cost . " XP reserved.";
    }

    function processPlayerUpdate($playerID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $playerName   = $_POST['playerName'];
        $playerStatus = $_POST['playerStatus'];
        $playerType   = $_POST['playerType'];

        if (!((int) $playerID > 0)) {
            if ($playerName == 'New Player') {
                return "Cannot add player with name New Player";
            }
            else {
                $sql = "SELECT id FROM " . $table_prefix . "PLAYER WHERE name = %s";
                $existingIDs = $wpdb->get_results($wpdb->prepare($sql, $playerName));
                foreach ($existingIDs as $existingID) {
                    return "Player with that name already exists";
                }
            }
        }

        if ((int) $playerID > 0) {
            $sql = "UPDATE " . $table_prefix . "PLAYER
                            SET name             = '" . $playerName   . "',
                                player_type_id   =  " . $playerType   . ",
                                player_status_id =  " . $playerStatus . "
                            WHERE id = " . $playerID;
        }
        else {
            $sql = "INSERT INTO " . $table_prefix . "PLAYER (name, player_type_id, player_status_id)
                            VALUES (%s, %d, %d)";
        }
        $wpdb->query($wpdb->prepare($sql, $playerName, $playerType, $playerStatus));

        if (!((int) $playerID > 0)) {
            $sql = "SELECT id
                            FROM " . $table_prefix . "PLAYER
                            WHERE name = %s";
            $playerIDs = $wpdb->get_results($wpdb->prepare($sql, $playerName));
            foreach ($playerIDs as $id) {
                $playerID = $id->id;
            }
        }
        return $playerID;
    }

    function displayUpdateCharacter($characterID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output = "";

        if ($characterID == "0" || (int) ($characterID) > 0) {
            $players           = listPlayers("", "");       // ID, name
            $clans             = listClans();               // ID, name
            $generations       = listGenerations();         // ID, name
            $courts            = listCourts();              // ID, name
            $characterTypes    = listCharacterTypes();      // ID, name
            $characterStatuses = listCharacterStatuses();   // ID, name
            $roadsOrPaths      = listRoadsOrPaths();        // ID, name

            $characterName             = "New Name";
            $characterPublicClanId     = "";
            $characterPrivateClanId    = "";
            $characterGenerationId     = "";
            $characterDateOfBirth      = "";
            $characterDateOfEmbrace    = "";
            $characterSire             = "";
            $characterPlayerId         = "";
            $characterTypeId           = "";
            $characterStatusId         = "";
            $characterStatusComment    = "";
            $characterRoadOrPathId     = "";
            $characterRoadOrPathRating = "";
            $characterCourtId          = "";
            $characterWordpressName    = "";
            $characterVisible          = "Y";

            $characterHarpyQuote       = "";
            $characterPortraitURL      = "";

            if ((int) ($characterID) > 0) {

                $sql = "SELECT NAME,
                                       PUBLIC_CLAN_ID,
                                       PRIVATE_CLAN_ID,
                                       GENERATION_ID,
                                       DATE_OF_BIRTH,
                                       DATE_OF_EMBRACE,
                                       SIRE,
                                       PLAYER_ID,
                                       CHARACTER_TYPE_ID,
                                       CHARACTER_STATUS_ID,
                                       CHARACTER_STATUS_COMMENT,
                                       ROAD_OR_PATH_ID,
                                       ROAD_OR_PATH_RATING,
                                       COURT_ID,
                                       WORDPRESS_ID,
                                       VISIBLE
                                FROM " . $table_prefix . "CHARACTER
                                WHERE ID = %d";

                $characterDetails = $wpdb->get_results($wpdb->prepare($sql, $characterID));

                foreach ($characterDetails as $characterDetail) {
                    $characterName             = $characterDetail->NAME;
                    $characterPublicClanId     = $characterDetail->PUBLIC_CLAN_ID;
                    $characterPrivateClanId    = $characterDetail->PRIVATE_CLAN_ID;
                    $characterGenerationId     = $characterDetail->GENERATION_ID;
                    $characterDateOfBirth      = $characterDetail->DATE_OF_BIRTH;
                    $characterDateOfEmbrace    = $characterDetail->DATE_OF_EMBRACE;
                    $characterSire             = $characterDetail->SIRE;
                    $characterPlayerId         = $characterDetail->PLAYER_ID;
                    $characterTypeId           = $characterDetail->CHARACTER_TYPE_ID;
                    $characterStatusId         = $characterDetail->CHARACTER_STATUS_ID;
                    $characterStatusComment    = $characterDetail->CHARACTER_STATUS_COMMENT;
                    $characterRoadOrPathId     = $characterDetail->ROAD_OR_PATH_ID;
                    $characterRoadOrPathRating = $characterDetail->ROAD_OR_PATH_RATING;
                    $characterCourtId          = $characterDetail->COURT_ID;
                    $characterWordpressName    = $characterDetail->WORDPRESS_ID;
                    $characterVisible          = $characterDetail->VISIBLE;
                }

                $sql = "SELECT QUOTE, PORTRAIT
                                FROM " . $table_prefix . "CHARACTER_PROFILE
                                WHERE CHARACTER_ID = %d";

                $characterProfiles = $wpdb->get_results($wpdb->prepare($sql, $characterID));

                foreach ($characterProfiles as $characterProfile) {
                    $characterHarpyQuote  = $characterProfile->QUOTE;
                    $characterPortraitURL = $characterProfile->PORTRAIT;
                }
            }

            $output  = "<form name=\"CHARACTER_UPDATE_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
            $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdateCharacter\" />";
            $output .= "<table class='gvplugin' id=\"gvid_ucti\">
                        <tr><td class=\"gvcol_1 gvcol_val\"><input type='submit' name=\"cSubmit\" value=\"Submit character changes\" /></td>
                            <td class=\"gvcol_2 gvcol_val\"><input type='submit' name=\"cSubmit\" value=\"Back to the character list\" /></td></tr></table>";

            if ((int) ($characterID) > 0) { $output .= "<input type='HIDDEN' name=\"characterID\" value=\"" . $characterID . "\" />"; }
            $output .= "<table class='gvplugin' id=\"gvid_uctu\">
                            <tr><td class=\"gvcol_1 gvcol_key\">Character Name</td>
                                <td class=\"gvcol_2 gvcol_val\" colspan=2><input type='text' maxlength=60 name=\"charName\" value=\"" . $characterName . "\"></td>
                                <td class=\"gvcol_4 gvcol_key\">Player Name</td>
                                <td class='gvcol_5 gvcol_val' colspan=2><select name=\"charPlayer\">";
            foreach ($players as $player) {
                $output .= "<option value=\"" . $player->ID . "\" ";
                if ($player->ID == $characterPlayerId) {
                    $output .= "SELECTED";
                }
                $output .= ">" . $player->name . "</option>";
            }
            $output .= "</select></td></tr>";

            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Public Clan</td>
                            <td class=\"gvcol_2 gvcol_val\" colspan=2><select name=\"charPubClan\">";
            foreach ($clans as $clan) {
                $output .= "<option value=\"" . $clan->ID . "\" ";
                if ($clan->ID == $characterPublicClanId) {
                    $output .= "SELECTED";
                }
                $output .= ">" . $clan->name . "</option>";
            }
            $output .= "</select></td><td class=\"gvcol_4 gvcol_key\">Private Clan</td>
                                      <td class='gvcol_5 gvcol_val' colspan=2><select name=\"charPrivClan\">";
            foreach ($clans as $clan) {
                $output .= "<option value=\"" . $clan->ID . "\" ";
                if ($clan->ID == $characterPrivateClanId) {
                    $output .= "SELECTED";
                }
                $output .= ">" . $clan->name . "</option>";
            }
            $output .= "</select></td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Generation</td>
                            <td class=\"gvcol_2 gvcol_val\" colspan=2><select name=\"charGen\">";
            foreach ($generations as $generation) {
                $output .= "<option value=\"" . $generation->ID . "\" ";
                if ($generation->ID == $characterGenerationId) {
                    $output .= "SELECTED";
                }
                $output .= ">" . $generation->name . "th</option>";
            }
            $output .= "</select></td><td class=\"gvcol_4 gvcol_key\">Sire</td>
                               <td class='gvcol_5 gvcol_val' colspan=2><input type='text' maxlength=60 name=\"charSire\" value=\"" . $characterSire . "\" /></td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Date of birth</td>
                               <td class=\"gvcol_2 gvcol_val\" colspan=2><input type='text' maxlength=10 name=\"charDoB\" value=\"" . $characterDateOfBirth . "\" /> YYYY-MM-DD</td>
                                    <td class=\"gvcol_4 gvcol_key\">Date of Embrace</td>
                               <td class='gvcol_5 gvcol_val' colspan=2><input type='text' maxlength=10 name=\"charDoE\" value=\"" . $characterDateOfEmbrace . "\" /> YYYY-MM-DD</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Road or Path</td>
                                <td class=\"gvcol_2 gvcol_val\"><select name=\"charRoadOrPath\">";
            foreach ($roadsOrPaths as $roadOrPath) {
                $output .= "<option value=\"" . $roadOrPath->ID . "\" ";
                if ($roadOrPath->ID == $characterRoadOrPathId || ($characterID == 0 && $roadOrPath->name == 'Humanity')) {
                    $output .= "SELECTED";
                }
                $output .= ">" . $roadOrPath->name . "</option>";
            }
            $output .= "</select></td><td class=\"gvcol_3 gvcol_val\"><input type='text' maxlength=3 name=\"charRoadOrPathRating\" value=\"" . $characterRoadOrPathRating . "\" /></td>";
            $output .= "<td class=\"gvcol_4 gvcol_key\">Court</td>
                            <td class='gvcol_5 gvcol_val' colspan=2><select name=\"charCourt\">";
            foreach ($courts as $court) {
                $output .= "<option value=\"" . $court->ID . "\" ";
                if ($court->ID == $characterCourtId || ($characterID == 0 && $court->name == 'Glasgow')) {
                    $output .= "SELECTED";
                }
                $output .= ">" . $court->name . "</option>";
            }
            $output .= "</select></td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Character Type</td>
                                <td class=\"gvcol_2 gvcol_val\" colspan=2><select name=\"charType\">";
            foreach ($characterTypes as $characterType) {
                $output .= "<option value=\"" . $characterType->ID . "\" ";
                if ($characterType->ID == $characterTypeId || ($characterID == 0 && $characterType->name == 'PC')) {
                    $output .= "SELECTED";
                }
                $output .= ">" . $characterType->name . "</option>";
            }
            $output .= "</select></td><td class=\"gvcol_4 gvcol_key\">Character Status</td>
                                          <td class='gvcol_5 gvcol_val'><select name=\"charStatus\">";
            foreach ($characterStatuses as $characterStatus) {
                $output .= "<option value=\"" . $characterStatus->ID . "\" ";
                if ($characterStatus->ID == $characterStatusId) {
                    $output .= "SELECTED";
                }
                $output .= ">" . $characterStatus->name . "</option>";
            }
            $output .= "</select></td><td class='gvcol_6 gvcol_val'><input type='text' maxlength=30 name=\"charStatusComment\" value=\"" . $characterStatusComment . "\" /></td></tr>";

            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Harpy Quote</td><td class=\"gvcol_2 gvcol_val\" colspan=5><textarea name=\"charHarpyQuote\" rows=\"5\" cols=\"100\">" . $characterHarpyQuote . "</textarea></td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Portrait URL</td><td class=\"gvcol_2 gvcol_val\" colspan=5><input type='text' maxlength=250 size=100 name=\"charPortraitURL\" value=\"" . $characterPortraitURL . "\" /></td></tr>";

            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Visible</td><td class=\"gvcol_2 gvcol_val\" colspan=2><select name=\"charVisible\"><option value=\"Y\" ";
            if ($characterVisible == "Y" ) {
                $output .= "SELECTED";
            }
            $output .= ">Yes</option><option value=\"N\" ";
            if ($characterVisible != "Y") {
                $output .= "SELECTED";
            }
            $output .= ">No</option></select></td><td class=\"gvcol_4 gvcol_key\">WordPress Account</td>
                                                      <td class='gvcol_5 gvcol_val' colspan=2><input type='text' maxlength=30 name=\"charWordPress\" value=\"" . $characterWordpressName . "\" /></td></tr>";
            $output .= "</table>";

            $sql = "SELECT stat.name,
                                   stat.grouping,
                                   stat.id statid,
                                   cstat.level,
                                   cstat.comment,
                                   cstat.id cstatid
                            FROM " . $table_prefix . "CHARACTER_STAT cstat,
                                 " . $table_prefix . "STAT stat
                            WHERE cstat.stat_id = stat.id
                              AND character_id = %d
                            ORDER BY stat.ordering";

            $characterStats = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $arr = array("0" => "0");
            foreach ($characterStats as $characterStat) {
                $arr[$characterStat->name] = $characterStat;
            }
            $stats = listStats();

            $output .= "<hr /><table class='gvplugin' id=\"gvid_uctsto\">
                                  <tr><td class=\"gvcol_1 gvcol_val\"><table class='gvplugin' id=\"gvid_uctsti\">
                                          <tr><th class=\"gvthead gvcol_1\">Stat Name</th>
                                              <th class=\"gvthead gvcol_2\">Value</th>
                                              <th class=\"gvthead gvcol_3\">Comment</th>
                                              <th class=\"gvthead gvcol_4\">Delete</th></tr>";

            $i = 0;
            foreach ($stats as $stat) {
                if ($i == 3) {
                    $output .= "</table></td><td class=\"gvcol\"><table class='gvplugin' id=\"gvid_uctsti\"><tr><th class=\"gvthead gvcol_1\">Stat Name</th>
                                                                                                      <th class=\"gvthead gvcol_2\">Value</th>
                                                                                                      <th class=\"gvthead gvcol_3\">Comment</th>
                                                                                                      <th class=\"gvthead gvcol_4\">Delete</th></tr>";
                }
                elseif ($i == 9) {
                    $output .= "</table></td><td class=\"gvcol\"><table class='gvplugin' id=\"gvid_uctsti\">";
                }
                elseif ($i == 6) {
                    $output .= "</table></td></tr><tr><td class=\"gvcol\"><table class='gvplugin' id=\"gvid_uctsti\">";
                }
                elseif ($i == 14) {
                    $output .= "</table></td></tr><tr><td class=\"gvcol\"><table class='gvplugin' id=\"gvid_uctsti\">";
                }
                $statName = $stat->name;
                $currentStat = $arr[$statName];
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $stat->name . "</td>"
                    . "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($statName, $currentStat->level, 0, 10) . "</td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\"" . $statName . "Comment\" value=\"" . $currentStat->comment . "\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'>";

                if ($currentStat->grouping == "Virtue"  && $statName != "Courage") {
                    $output .= "<input type=\"checkbox\" name=\"" . $statName . "Delete\" value=\"" . $currentStat->cstatid . "\" />";
                }

                $output .= "<input type='HIDDEN' name=\"" . $statName . "ID\" value=\"" . $currentStat->cstatid . "\" />"
                    . "</td></tr>";
                $i++;
            }
            $output .= "</table></td></tr></table><hr />";

            $sql = "SELECT skill.name,
                                   skill.grouping,
                                   skill.id skillid,
                                   cskill.level,
                                   cskill.comment,
                                   cskill.id cskillid
                            FROM " . $table_prefix . "CHARACTER_SKILL cskill,
                                 " . $table_prefix . "SKILL skill
                            WHERE cskill.skill_id = skill.id
                              AND character_id = %d
                            ORDER BY skill.grouping DESC, skill.name";

            $characterSkills = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $lastGroup = "Something";
            $currentGroup = "";

            $skillCount = 0;
            $arr = array();
            foreach($characterSkills as $characterSkill) {
                $currentGroup = $characterSkill->grouping;
                if ($currentGroup != $lastGroup) {
                    if ($lastGroup != "Something") {
                        $output .= "</table><br />";
                    }
                    $output .= "<table class='gvplugin' id=\"gvid_uctskg\"><tr><th class=\"gvthead gvcol_1\">" . $characterSkill->grouping . " name</th>
                                                                                     <th class=\"gvthead gvcol_2\">Value</th>
                                                                                     <th class=\"gvthead gvcol_3\">Comment</th>
                                                                                     <th class=\"gvthead gvcol_4\">Delete</th></tr>";
                    $lastGroup = $currentGroup;
                }

                $skillName = "skill" . $skillCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterSkill->name . "</td>"
                    . "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($skillName, $characterSkill->level, 0, 10) . "</td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $skillName . "Comment\" value=\"" . $characterSkill->comment  . "\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $skillName . "Delete\" value=\""  . $characterSkill->cskillid . "\" />"
                    .     "<input type='HIDDEN' name=\""   . $skillName . "ID\" value=\""      . $characterSkill->cskillid . "\" /></td></tr>";

                $skillCount++;
            }
            $output .= "</table><br />";
            $output .= "<input type='HIDDEN' name=\"maxOldSkillCount\" value=\"" . $skillCount . "\" />";

            $output .= "<table class='gvplugin' id=\"gvid_uctskn\"><tr><th class=\"gvthead gvcol_1\">New skill name</th>
                                                                             <th class=\"gvthead gvcol_2\">Value</th>
                                                                             <th class=\"gvthead gvcol_3\">Comment</th>
                                                                             <th class=\"gvthead gvcol_4\">Delete</th></tr>";

            $skillBlock = "";
            $skills = listSkills("", "Y");
            foreach ($skills as $skill) {
                $skillBlock .= "<option value=\"" . $skill->id . "\">" . $skill->name . "</option>";
            }

            for ($i = 0; $i < 20; ) {
                $skillName = "skill" . $skillCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $skillName . "SID\">" . $skillBlock . "</select></td>"
                    . "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($skillName, "", 0, 10) . "</td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\"" . $skillName . "Comment\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'></td></tr>";

                $i++;
                $skillCount++;
            }
            $output .= "<input type='HIDDEN' name=\"maxNewSkillCount\" value=\"" . $skillCount . "\" />";
            $output .= "</table><hr />";

            /*******************************************************************************************/
            /*******************************************************************************************/

            $sql = "SELECT discipline.name,
                                   discipline.id disid,
                                   cdiscipline.level,
                                   cdiscipline.comment,
                                   cdiscipline.id cdisciplineid
                            FROM " . $table_prefix . "CHARACTER_DISCIPLINE cdiscipline,
                                 " . $table_prefix . "DISCIPLINE discipline
                            WHERE cdiscipline.discipline_id = discipline.id
                              AND character_id = %d
                            ORDER BY discipline.name";

            $characterDisciplines = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $output .= "<table class='gvplugin' id=\"gvid_uctdi\"><tr><th class=\"gvthead gvcol_1\">Discipline name</th>
                                                                            <th class=\"gvthead gvcol_2\">Value</th>
                                                                            <th class=\"gvthead gvcol_3\">Comment</th>
                                                                            <th class=\"gvthead gvcol_4\">Delete</th>
                                                                            <th class=\"gvthead gvcol_5\">Discipline name</th>
                                                                            <th class=\"gvthead gvcol_6\">Value</th>
                                                                            <th class=\"gvthead gvcol_7\">Comment</th>
                                                                            <th class=\"gvthead gvcol_8\">Delete</th></tr>";
            $colOffset = 0;
            $i = 0;
            $disciplineCount = 0;
            $arr = array();
            foreach($characterDisciplines as $characterDiscipline) {
                if ($i % 2 == 0) {
                    $output .= "<tr>";
                }
                $colOffset = 4 * ($i % 2);

                $disciplineName = "discipline" . $disciplineCount;
                $output .= "<td class=\"gvcol_" . (1 + $colOffset) . " gvcol_key\">" . $characterDiscipline->name . "</td>"
                    . "<td class=\"gvcol_" . (2 + $colOffset) . " gvcol_val\">" . printSelectCounter($disciplineName, $characterDiscipline->level, 0, 10) . "</td>"
                    . "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $disciplineName . "Comment\" value=\"" . $characterDiscipline->comment  . "\" /></td>"
                    . "<td class=\"gvcol_" . (4 + $colOffset) . " gvcol_val\"><input type=\"checkbox\" name=\"" . $disciplineName . "Delete\" value=\""  . $characterDiscipline->cdisciplineid . "\" />"
                    .     "<input type='HIDDEN' name=\""   . $disciplineName . "ID\" value=\""      . $characterDiscipline->cdisciplineid . "\" /></td>";

                $i++;
                $disciplineCount++;
                if ($i % 2 == 0) {
                    $output .= "</tr>";
                }
            }
            if ($i % 2 != 0) {
                $output .= "</tr>";
            }

            $output .= "<tr style='display:none'><td colspan=8><input type='HIDDEN' name=\"maxOldDisciplineCount\" value=\"" . $disciplineCount . "\" /></td></tr>";

            $disciplineBlock = "";
            $disciplines = listDisciplines("Y");
            foreach ($disciplines as $discipline) {
                $disciplineBlock .= "<option value=\"" . $discipline->id . "\">" . $discipline->name . "</option>";
            }

            for ($i = 0; $i < 4; ) {
                if ($i % 2 == 0) {
                    $output .= "<tr>";
                }
                $disciplineName = "discipline" . $disciplineCount;
                $output .= "<td class=\"gvcol_" . (1 + $colOffset) . " gvcol_key\"><select name=\"" . $disciplineName . "SID\">" . $disciplineBlock . "</select></td>"
                    . "<td class=\"gvcol_" . (2 + $colOffset) . " gvcol_val\">" . printSelectCounter($disciplineName, "", 0, 10) . "</td>"
                    . "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $disciplineName . "Comment\" /></td>"
                    . "<td class=\"gvcol_" . (4 + $colOffset) . " gvcol_val\"></td>";

                $i++;
                $disciplineCount++;
                if ($i % 2 == 0) {
                    $output .= "</tr>";
                }
            }
            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewDisciplineCount\" value=\"" . $disciplineCount . "\" /></td></tr>";
            $output .= "</table><hr />";

            /*******************************************************************************************/
            /*******************************************************************************************/

            $sql = "SELECT background.name,
                                   background.grouping,
                                   background.id statid,
                                   cbackground.level,
                                   cbackground.comment,
                                   cbackground.id cbackgroundid
                            FROM " . $table_prefix . "CHARACTER_BACKGROUND cbackground,
                                 " . $table_prefix . "BACKGROUND background
                            WHERE cbackground.background_id = background.id
                              AND character_id = %d
                            ORDER BY background.name";

            $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $output .= "<table class='gvplugin' id=\"gvid_uctdi\"><tr><th class=\"gvthead gvcol_1\">Background name</th>
                                                                            <th class=\"gvthead gvcol_2\">Value</th>
                                                                            <th class=\"gvthead gvcol_3\">Comment</th>
                                                                            <th class=\"gvthead gvcol_4\">Delete</th>
                                                                            <th class=\"gvthead gvcol_5\">Background name</th>
                                                                            <th class=\"gvthead gvcol_6\">Value</th>
                                                                            <th class=\"gvthead gvcol_7\">Comment</th>
                                                                            <th class=\"gvthead gvcol_8\">Delete</th></tr>";
            $i = 0;
            $backgroundCount = 0;
            $arr = array();
            foreach($characterBackgrounds as $characterBackground) {
                if ($i % 2 == 0) {
                    $output .= "<tr>";
                }
                $colOffset = 4 * ($i % 2);

                $backgroundName = "background" . $backgroundCount;
                $output .= "<td class=\"gvcol_" . (1 + $colOffset) . " gvcol_key\">" . $characterBackground->name . "</td>"
                    . "<td class=\"gvcol_" . (2 + $colOffset) . " gvcol_val\">" . printSelectCounter($backgroundName, $characterBackground->level, 0, 10) . "</td>"
                    . "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $backgroundName . "Comment\" value=\"" . $characterBackground->comment  . "\" /></td>"
                    . "<td class=\"gvcol_" . (4 + $colOffset) . " gvcol_val\"><input type=\"checkbox\" name=\"" . $backgroundName . "Delete\" value=\""  . $characterBackground->cbackgroundid . "\" />"
                    .     "<input type='HIDDEN' name=\""   . $backgroundName . "ID\" value=\""      . $characterBackground->cbackgroundid . "\" /></td>";

                $i++;
                $backgroundCount++;
                if ($i % 2 == 0) {
                    $output .= "</tr>";
                }
            }
            if ($i % 2 != 0) {
                $output .= "</tr>";
            }

            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldBackgroundCount\" value=\"" . $backgroundCount . "\" /></td></tr>";

            $backgroundBlock = "";
            $backgrounds = listBackgrounds("", "Y");
            foreach ($backgrounds as $background) {
                $backgroundBlock .= "<option value=\"" . $background->id . "\">" . $background->name . "</option>";
            }

            for ($i = 0; $i < 6; ) {
                if ($i % 2 == 0) {
                    $output .= "<tr>";
                }
                $backgroundName = "background" . $backgroundCount;
                $output .= "<td class=\"gvcol_" . (1 + $colOffset) . " gvcol_key\"><select name=\"" . $backgroundName . "SID\">" . $backgroundBlock . "</select></td>"
                    . "<td class=\"gvcol_" . (2 + $colOffset) . " gvcol_val\">" . printSelectCounter($backgroundName, "", 0, 10) . "</td>"
                    . "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $backgroundName . "Comment\" /></td>"
                    . "<td class=\"gvcol_" . (4 + $colOffset) . " gvcol_val\"></td>";

                $i++;
                $backgroundCount++;
                if ($i % 2 == 0) {
                    $output .= "</tr>";
                }
            }
            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewBackgroundCount\" value=\"" . $backgroundCount . "\" /></td></tr>";
            $output .= "</table><hr />";

            /*******************************************************************************************/

            $sql = "SELECT merit.name,
                                   merit.grouping,
                                   merit.id statid,
                                   merit.value,
                                   cmerit.level,
                                   cmerit.comment,
                                   cmerit.id cmeritid
                            FROM " . $table_prefix . "CHARACTER_MERIT cmerit,
                                 " . $table_prefix . "MERIT merit
                            WHERE cmerit.merit_id = merit.id
                              AND character_id = %d
                            ORDER BY merit.name";

            $characterMerits = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $output .= "<table class='gvplugin' id=\gvid_uctme\"><tr><th class=\"gvthead gvcol_1\">Merit name</th>
                                                                           <th class=\"gvthead gvcol_2\">Value</th>
                                                                           <th class=\"gvthead gvcol_3\">Comment</th>
                                                                           <th class=\"gvthead gvcol_4\">Delete</th></tr>";
            $meritCount = 0;
            $arr = array();
            foreach($characterMerits as $characterMerit) {
                $meritName = "merit" . $meritCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterMerit->name . " (" . $characterMerit->value . ")</td>"
                    . "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($meritName, $characterMerit->level, -7, 7) . "</td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $meritName . "Comment\" value=\"" . $characterMerit->comment  . "\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $meritName . "Delete\" value=\""  . $characterMerit->cmeritid . "\" />"
                    .     "<input type='HIDDEN' name=\""   . $meritName . "ID\" value=\""      . $characterMerit->cmeritid . "\" /></td></tr>";

                $i++;
                $meritCount++;
            }

            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldMeritCount\" value=\"" . $meritCount . "\" /></td></tr>";

            $meritBlock = "";
            $merits = listMerits("", "Y");
            foreach ($merits as $merit) {
                $meritBlock .= "<option value=\"" . $merit->id . "\">" . $merit->name . " (" . $merit->value . ")</option>";
            }

            for ($i = 0; $i < 6; $i++) {
                $meritName = "merit" . $meritCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $meritName . "SID\">" . $meritBlock . "</select></td>"
                    . "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($meritName, "", -7, 7) . "</td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $meritName . "Comment\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'></td></tr>";

                $meritCount++;
            }
            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewMeritCount\" value=\"" . $meritCount . "\" /></td></tr>";
            $output .= "</table><hr />";

            /*******************************************************************************************/


            $sql = "SELECT combo_discipline.name,
                                   combo_discipline.id disid,
                                   ccombo_discipline.comment,
                                   ccombo_discipline.id ccombo_disciplineid
                            FROM " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE ccombo_discipline,
                                 " . $table_prefix . "COMBO_DISCIPLINE combo_discipline
                            WHERE ccombo_discipline.combo_discipline_id = combo_discipline.id
                              AND character_id = %d
                            ORDER BY combo_discipline.name";

            $characterComboDisciplines = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $output .= "<table class='gvplugin' id=\"gvid_uctcd\"><tr><th class=\"gvthead gvcol_1\">Combo Discipline name</th>
                                                                            <th class=\"gvthead gvcol_2\">Value</th>
                                                                            <th class=\"gvthead gvcol_3\">Comment</th>
                                                                            <th class=\"gvthead gvcol_4\">Delete</th></tr>";

            $comboDisciplineCount = 0;
            $arr = array();
            foreach($characterComboDisciplines as $characterComboDiscipline) {
                $comboDisciplineName = "comboDiscipline" . $comboDisciplineCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterComboDiscipline->name . "</td>"
                    . "<td class=\"gvcol_2 gvcol_val\">Learned<input type='HIDDEN' name=\"" . $comboDisciplineName . "\" value=\"0\" /></td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $comboDisciplineName . "Comment\" value=\"" . $characterComboDiscipline->comment  . "\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $comboDisciplineName . "Delete\" value=\""  . $characterComboDiscipline->ccombo_disciplineid . "\" />"
                    .     "<input type='HIDDEN' name=\""   . $comboDisciplineName . "ID\" value=\""      . $characterComboDiscipline->ccombo_disciplineid . "\" /></td></tr>";

                $comboDisciplineCount++;
            }
            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldComboDisciplineCount\" value=\"" . $comboDisciplineCount . "\" /></td></tr>";

            $comboDisciplineBlock = "";
            $comboDisciplines = listComboDisciplines("Y");
            foreach ($comboDisciplines as $comboDiscipline) {
                $comboDisciplineBlock .= "<option value=\"" . $comboDiscipline->id . "\">" . $comboDiscipline->name . "</option>";
            }

            $comboDisciplineName = "comboDiscipline" . $comboDisciplineCount;
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $comboDisciplineName . "SID\">" . $comboDisciplineBlock . "</select></td>"
                . "<td class=\"gvcol_2 gvcol_val\"><select name=\"" . $comboDisciplineName . "\"><option value=\"-100\">Not Learned</option><option value=\"1\">Learned</option></select></td>"
                . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\"" . $comboDisciplineName . "Comment\" /></td>"
                . "<td class='gvcol_4 gvcol_val'></td></tr>";
            $comboDisciplineCount++;

            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewComboDisciplineCount\" value=\"" . $comboDisciplineCount . "\" /></td></tr>";
            $output .= "</table><hr />";

            /*******************************************************************************************/
            /*******************************************************************************************/

            $sql = "SELECT path.name,
                                   path.id disid,
                                   dis.name disname,
                                   cpath.level,
                                   cpath.comment,
                                   cpath.id cpathid
                            FROM " . $table_prefix . "CHARACTER_PATH cpath,
                                 " . $table_prefix . "PATH path,
                                 " . $table_prefix . "DISCIPLINE dis
                            WHERE cpath.path_id = path.id
                              AND path.discipline_id = dis.id
                              AND character_id = %d
                            ORDER BY disname, path.name";

            $characterPaths = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $output .= "<table class='gvplugin' id=\"gvid_uctpa\"><tr><th class=\"gvthead gvcol_1\">Path name</th>
                                                                            <th class=\"gvthead gvcol_2\">Value</th>
                                                                            <th class=\"gvthead gvcol_3\">Comment</th>
                                                                            <th class=\"gvthead gvcol_4\">Delete</th></tr>";

            $pathCount = 0;
            $arr = array();
            foreach($characterPaths as $characterPath) {
                $pathName = "path" . $pathCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterPath->name . " (" . substr($characterPath->disname, 0, 5)  .")</td>"
                    . "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($pathName, $characterPath->level, 0, 10) . "</td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $pathName . "Comment\" value=\"" . $characterPath->comment  . "\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $pathName . "Delete\" value=\""  . $characterPath->cpathid . "\" />"
                    .     "<input type='HIDDEN' name=\""   . $pathName . "ID\" value=\""      . $characterPath->cpathid . "\" /></td></tr>";

                $i++;
                $pathCount++;
            }

            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldPathCount\" value=\"" . $pathCount . "\" /></td></tr>";

            $pathBlock = "";
            $paths = listPaths("Y");
            foreach ($paths as $path) {
                $pathBlock .= "<option value=\"" . $path->id . "\">" . $path->name . " (" . substr($path->disname, 0, 5)  .")</option>";
            }

            for ($i = 0; $i < 2; $i++) {
                $pathName = "path" . $pathCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $pathName . "SID\">" . $pathBlock . "</select></td>"
                    . "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($pathName, "", 0, 10) . "</td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $pathName . "Comment\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'></td></tr>";
                $pathCount++;
            }
            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewPathCount\" value=\"" . $pathCount . "\" /></td></tr>";
            $output .= "</table><hr />";

            /*******************************************************************************************/
            /*******************************************************************************************/

            $sql = "SELECT ritual.name,
                                   ritual.id disid,
                                   ritual.level ritlevel,
                                   dis.name disname,
                                   critual.level,
                                   critual.comment,
                                   critual.id critualid
                            FROM " . $table_prefix . "CHARACTER_RITUAL critual,
                                 " . $table_prefix . "RITUAL ritual,
                                 " . $table_prefix . "DISCIPLINE dis
                            WHERE critual.ritual_id = ritual.id
                              AND ritual.discipline_id = dis.id
                              AND character_id = %d
                            ORDER BY disname, level, ritual.name";

            $characterRituals = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $output .= "<table class='gvplugin' id=\"gvid_uctri\"><tr><th class=\"gvthead gvcol_1\">Ritual name</th>
                                                                            <th class=\"gvthead gvcol_2\">Value</th>
                                                                            <th class=\"gvthead gvcol_3\">Comment</th>
                                                                            <th class=\"gvthead gvcol_4\">Delete</th></tr>";

            $ritualCount = 0;
            $arr = array();
            foreach($characterRituals as $characterRitual) {
                $ritualName = "ritual" . $ritualCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterRitual->name . " (" . substr($characterRitual->disname, 0, 5)  . " " . $characterRitual->ritlevel .")</td>"
                    . "<td class=\"gvcol_2 gvcol_val\">Learned<input type='HIDDEN' name=\"" . $ritualName . "\" value=\"0\" /></td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $ritualName . "Comment\" value=\"" . $characterRitual->comment  . "\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $ritualName . "Delete\" value=\""  . $characterRitual->critualid . "\" />"
                    .     "<input type='HIDDEN' name=\""   . $ritualName . "ID\" value=\""      . $characterRitual->critualid . "\" /></td></tr>";

                $i++;
                $ritualCount++;
            }

            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldRitualCount\" value=\"" . $ritualCount . "\" /></td></tr>";

            $ritualBlock = "";
            $rituals = listRituals("Y");
            foreach ($rituals as $ritual) {
                $ritualBlock .= "<option value=\"" . $ritual->id . "\">" . $ritual->name . " (" . substr($ritual->disname, 0, 5)  . " " . $ritual->level . ")</option>";
            }

            for ($i = 0; $i < 5; $i++) {
                $ritualName = "ritual" . $ritualCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $ritualName . "SID\">" . $ritualBlock . "</select></td>"
                    . "<td class=\"gvcol_2 gvcol_val\"><select name=\"" . $ritualName . "\"><option value=\"-100\">Not Learned</option><option value=\"1\">Learned</option></select></td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $ritualName . "Comment\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'></td></tr>";
                $ritualCount++;
            }
            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewRitualCount\" value=\"" . $ritualCount . "\" /></td></tr>";
            $output .= "</table><hr />";

            /*******************************************************************************************/
            /*******************************************************************************************/

            $sql = "SELECT office.name,
                                   office.id disid,
                                   court.name courtname,
                                   coffice.comment,
                                   coffice.id cofficeid
                            FROM " . $table_prefix . "CHARACTER_OFFICE coffice,
                                 " . $table_prefix . "OFFICE office,
                                 " . $table_prefix . "COURT court
                            WHERE coffice.office_id = office.id
                              AND coffice.court_id  = court.id
                              AND character_id = %d
                            ORDER BY office.ordering, office.name, court.name";

            $characterOffices = $wpdb->get_results($wpdb->prepare($sql, $characterID));

            $output .= "<table class='gvplugin' id=\"gvid_uctof\"><tr><th class=\"gvthead gvcol_1\">Office name</th>
                                                                            <th class=\"gvthead gvcol_2\">Court</th>
                                                                            <th class=\"gvthead gvcol_3\">Status</th>
                                                                            <th class=\"gvthead gvcol_4\">Comment</th>
                                                                            <th class=\"gvthead gvcol_5\">Delete</th></tr>";

            $officeCount = 0;
            $arr = array();
            foreach($characterOffices as $characterOffice) {
                $officeName = "office" . $officeCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterOffice->name . "</td>"
                    . "<td class=\"gvcol_2 gvcol_val\">" . $characterOffice->courtname . "</td>"
                    . "<td class=\"gvcol_3 gvcol_val\">In office<input type='HIDDEN' name=\"" . $officeName . "\" value=\"0\" /></td>"
                    . "<td class='gvcol_4 gvcol_val'><input type='text' name=\""     . $officeName . "Comment\" value=\"" . $characterOffice->comment  . "\" /></td>"
                    . "<td class='gvcol_5 gvcol_val'><input type=\"checkbox\" name=\"" . $officeName . "Delete\" value=\""  . $characterOffice->cofficeid . "\" />"
                    .     "<input type='HIDDEN' name=\""   . $officeName . "ID\" value=\""      . $characterOffice->cofficeid . "\" /></td></tr>";
                $i++;
                $officeCount++;
            }

            $output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldOfficeCount\" value=\"" . $officeCount . "\" /></td></tr>";

            $officeBlock = "";
            $offices = listOffices("Y");
            foreach ($offices as $office) {
                $officeBlock .= "<option value=\"" . $office->ID . "\">" . $office->name . "</option>";
            }

            $courtBlock = "";
            $courts = listCourts();
            foreach ($courts as $court) {
                $courtBlock .= "<option value=\"" . $court->ID ."\">" . $court->name . "</option>";
            }

            for ($i = 0; $i < 2; $i++) {
                $officeName = "office" . $officeCount;
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $officeName . "OID\">" . $officeBlock . "</select></td>"
                    . "<td class=\"gvcol_2 gvcol_val\"><select name=\"" . $officeName . "CID\">" . $courtBlock . "</select></td>"
                    . "<td class=\"gvcol_3 gvcol_val\"><select name=\"" . $officeName . "\"><option value=\"-100\">Not in office</option><option value=\"1\">In office</option></select></td>"
                    . "<td class='gvcol_4 gvcol_val'><input type='text' name=\""     . $officeName . "Comment\" /></td>"
                    . "<td class='gvcol_5 gvcol_val'></td></tr>";
                $officeCount++;
            }
            $output .= "<tr style='display:none'><td colspan=5><input type='HIDDEN' name=\"maxNewOfficeCount\" value=\"" . $officeCount . "\" /></td></tr>";
            $output .= "</table><hr />";

            /*******************************************************************************************/
            /*******************************************************************************************/

            $output .= "<table class='gvplugin' id=\"gvid_scc\"><tr><td class=\"gvcol_1 gvcol_val\"><input type='submit' name=\"cSubmit\" value=\"Submit character changes\" /></td>
                                                                          <td class=\"gvcol_1 gvcol_val\"><input type='submit' name=\"cSubmit\" value=\"Back to the character list\" /></td></tr></table>";
            $output .= "</form>";
        }
        else {
            $output .= "We encountered an illegal Character ID (". $characterID . ")";
        }
        return $output;
    }

    function displayDeleteCharacter($characterID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $output = "";

        $sql = "SELECT chara.name cname, player.name pname
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "PLAYER player
                        WHERE chara.player_id = player.id
                          AND chara.ID = %d";

        $characterDetails = $wpdb->get_results($wpdb->prepare($sql, $characterID));

        foreach ($characterDetails as $characterDetail) {
            $characterName = $characterDetail->cname;
            $playerName    = $characterDetail->pname;
        }

        $output = "<strong>Are you sure you want to delete: <br />" . $characterName . "<br />played by:<br />" . $playerName . "?</strong><br />"
            . "Deleting the character will <strong>permanently</strong> and <strong>irrevocably</strong> "
            . "remove them for the character database.<br />"
            . "Remember you can mark the character as absent or deactivate the player to remove them from most lists.<br />";

        $output .= "<form name=\"CD_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdateCharacter\" />";        $output .= "<input type='HIDDEN' name=\"characterID\" value=\"" . $characterID . "\" />";        $output .= "<table class='gvplugin' id=\"gvid_gvid_sdc\"><tr><td class=\"gvcol_1 gvcol_val\">";
        $output .= "<input type='submit' name=\"cSubmit\" value=\"Confirm Delete\" /></td><td>";
        $output .= "<input type='submit' name=\"cSubmit\" value=\"Abandon Delete\" /></td></tr></table></form>";
        return $output;
    }

    function deleteCharacter($characterID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "UPDATE " . $table_prefix . "CHARACTER
                        SET DELETED = 'Y'
                        WHERE ID = %d";

        $wpdb->query($wpdb->prepare($sql, $characterID));

        $output = "Problem with delete, contact webmaster";

        $sql = "SELECT name
                        FROM " . $table_prefix . "CHARACTER
                        WHERE ID = %d
                          AND DELETED = 'Y'";

        $characterNames = $wpdb->get_results($wpdb->prepare($sql, $characterID));
        $sqlOutput = "";

        foreach ($characterNames as $characterName) {
            $sqlOutput .= $characterName->name . " ";
        }

        if ($sqlOutput != "") {
            $output = "Deleted character " . $sqlOutput;
        }

        $output .= "<br /><form name=\"CD_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdateCharacter\" />";        $output .= "<table class='gvplugin' id=\"gvid_dcf\"><tr><td class=\"gvcol_1 gvcol_val\">";
        $output .= "<input type='submit' name=\"cSubmit\" value=\"Back to the character list\" /></td></tr></table></form>";
        return $output;
    }

    function processCharacterUpdate($characterID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $characterName             = $_POST['charName'];
        $characterPlayer           = $_POST['charPlayer'];
        $characterPublicClan       = $_POST['charPubClan'];
        $characterPrivateClan      = $_POST['charPrivClan'];
        $characterGeneration       = $_POST['charGen'];
        $characterSire             = $_POST['charSire'];
        $characterDateOfBirth      = $_POST['charDoB'];
        $characterDateOfEmbrace    = $_POST['charDoE'];
        $characterRoadOrPath       = $_POST['charRoadOrPath'];
        $characterRoadOrPathRating = $_POST['charRoadOrPathRating'];
        $characterCourt            = $_POST['charCourt'];
        $characterType             = $_POST['charType'];
        $characterStatus           = $_POST['charStatus'];
        $characterStatusComment    = $_POST['charStatusComment'];
        $characterVisible          = $_POST['charVisible'];
        $characterWordPress        = $_POST['charWordPress'];

        if (get_magic_quotes_gpc()) {
            $characterHarpyQuote = stripslashes($_POST['charHarpyQuote']);
        }
        else {
            $characterHarpyQuote = $_POST['charHarpyQuote'];
        }
        $characterPortraitURL      = $_POST['charPortraitURL'];

        if ((int) $characterID > 0) {
            $sql = "UPDATE " . $table_prefix . "CHARACTER
                            SET name                     = %s,
                                public_clan_id           = %d,
                                private_clan_id          = %d,
                                generation_id            = %d,
                                date_of_birth            = %s,
                                date_of_embrace          = %s,
                                sire                     = %s,
                                player_id                = %d,
                                character_type_id        = %d,
                                character_status_id      = %d,
                                character_status_comment = %s,
                                road_or_path_id          = %d,
                                road_or_path_rating      = %d,
                                court_id                 = %d,
                                wordpress_id             = %s,
                                visible                  = %s
                            WHERE ID = " . $characterID;
            $sql = $wpdb->prepare($sql, $characterName,             $characterPublicClan,    $characterPrivateClan,   $characterGeneration,
                $characterDateOfBirth,      $characterDateOfEmbrace, $characterSire,          $characterPlayer,
                $characterType,             $characterStatus,        $characterStatusComment, $characterRoadOrPath,
                $characterRoadOrPathRating, $characterCourt,         $characterWordPress,     $characterVisible);
        }
        else {
            $sql = "INSERT INTO " . $table_prefix . "CHARACTER (name,                public_clan_id,      private_clan_id,          generation_id,
                                                                date_of_birth,       date_of_embrace,     sire,                     player_id,
                                                                character_type_id,   character_status_id, character_status_comment, road_or_path_id,
                                                                road_or_path_rating, court_id,            wordpress_id,             visible,
                                                                deleted)
                            VALUES (%s, %d, %d, %d, %s, %s, %s, %d, %d, %d, %s, %d, %d, %d, %s, %s, 'N')";
            $sql = $wpdb->prepare($sql, $characterName,             $characterPublicClan,    $characterPrivateClan,   $characterGeneration,
                $characterDateOfBirth,      $characterDateOfEmbrace, $characterSire,          $characterPlayer,
                $characterType,             $characterStatus,        $characterStatusComment, $characterRoadOrPath,
                $characterRoadOrPathRating, $characterCourt,         $characterWordPress,     $characterVisible);
        }
        $wpdb->query($sql);

        if (!((int) $characterID > 0)) {
            $sql = "SELECT id
                            FROM " . $table_prefix . "CHARACTER
                            WHERE name         = %s
                              AND wordpress_id = %s";
            $characterIDs = $wpdb->get_results($wpdb->prepare($sql, $characterName, $characterWordPress));
            foreach ($characterIDs as $id) {
                $characterID = $id->id;
            }

            $sql = "INSERT INTO ". $table_prefix . "CHARACTER_PROFILE (character_id, quote, portrait)
                            VALUES (%d, %s, %s)";
            $wpdb->query($wpdb->prepare($sql, $characterID, $characterHarpyQuote, $characterPortraitURL));

            $xpReasonID = establishXPReasonID('Initial XP');
            $sql = "INSERT INTO " . $table_prefix . "PLAYER_XP (player_id, character_id, xp_reason_id, awarded, amount, comment) "
                . "VALUES (%d, %d, %d, SYSDATE(), 0, 'New character added')";
            $wpdb->query($wpdb->prepare($sql, $characterPlayer, $characterID, $xpReasonID));

            $pathReasonID = establishPathReasonID('Initial');
            $sql = "INSERT INTO ". $table_prefix . "CHARACTER_ROAD_OR_PATH (character_id, path_reason_id, awarded, amount, comment) "
                .  "VALUES (%d, %d, SYSDATE(), %d, 'Character creation')";
            $wpdb->query($wpdb->prepare($sql, $characterID, $pathReasonID, $characterRoadOrPathRating));

            $tempStatReasonID = establishTempStatReasonID('Initial');
            $tempStatID = establishTempStatID('Blood');
            $sql = "INSERT INTO " . $table_prefix . "CHARACTER_TEMPORARY_STAT (character_id, temporary_stat_id, temporary_stat_reason_id, awarded, amount, comment) "
                . " VALUES (%d, %d, %d, SYSDATE(), 10, 'Character creation')";
            $wpdb->query($wpdb->prepare($sql, $characterID, $tempStatID, $tempStatReasonID));
        }
        else {
            $sql = "SELECT id
                            FROM " . $table_prefix . "CHARACTER_PROFILE
                            WHERE character_id = %d";

            $profileIDs = $characterIDs = $wpdb->get_results($wpdb->prepare($sql, $characterID));
            $profileID = -1;

            foreach ($profileIDs as $currentProfileID) {
                $profileID = $currentProfileID->id;
            }

            if ($profileID == -1) {
                $sql = "INSERT INTO ". $table_prefix . "CHARACTER_PROFILE (character_id, quote, portrait)
                                VALUES (%d, %s, %s)";
                $sql = $wpdb->prepare($sql, $characterID, $characterHarpyQuote, $characterPortraitURL);
            }
            else {
                $sql = "UPDATE " . $table_prefix . "CHARACTER_PROFILE
                                SET quote    = %s,
                                    portrait = %s
                                WHERE id = %d";
                $sql = $wpdb->prepare($sql, $characterHarpyQuote, $characterPortraitURL, $profileID);
            }
            $wpdb->query($sql);
        }

        $stats = listStats();
        foreach ($stats as $stat) {
            $currentStat = str_replace(" ", "_", $stat->name);
            if ($_POST[$currentStat] != "" && $_POST[$currentStat] != "-100") {
                if ((int) $_POST[$currentStat . "Delete"] > 0) {
                    $sql = "DELETE FROM " . $table_prefix . "CHARACTER_STAT WHERE id = %d";
                    $sql = $wpdb->prepare($sql, $_POST[$currentStat . "Delete"]);
                }
                elseif ((int) $_POST[$currentStat . "ID"] > 0) {
                    $sql = "UPDATE " . $table_prefix . "CHARACTER_STAT
                                    SET level   =  %d,
                                        comment =  %s
                                    WHERE id = %d";
                    $sql = $wpdb->prepare($sql, $_POST[$currentStat], $_POST[$currentStat . "Comment"], $_POST[$currentStat . "ID"]);
                }
                else {
                    if ($currentStat == "Willpower") {
                        $tempStatReasonID = establishTempStatReasonID('Initial');
                        $tempStatID       = establishTempStatID('Willpower');
                        $sql = "INSERT INTO " . $table_prefix . "CHARACTER_TEMPORARY_STAT (character_id, temporary_stat_id, temporary_stat_reason_id, awarded, amount, comment) "
                            . " VALUES (%d, %d, %d, SYSDATE(), %d, 'Character creation')";
                        $wpdb->query($wpdb->prepare($sql, $characterID, $tempStatID, $tempStatReasonID, $_POST[$currentStat]));
                    }

                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_STAT (character_id, stat_id, level, comment)
                                    VALUES (%d, %d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $stat->id, $_POST[$currentStat], $_POST[$currentStat . "Comment"]);
                }
                $wpdb->query($sql);
            }
        }

        $maxOldSkillCount = $_POST['maxOldSkillCount'];
        $maxSkillCount    = $_POST['maxNewSkillCount'];
        $skillCounter = 0;
        $currentSkill = "";

        while ($skillCounter < $maxSkillCount) {
            $currentSkill = "skill" . $skillCounter;
            if ($_POST[$currentSkill] != "" && $_POST[$currentSkill] != "-100") {
                if ($skillCounter < $maxOldSkillCount) {
                    if ((int) $_POST[$currentSkill . "Delete"] > 0) {
                        $sql = "DELETE FROM " . $table_prefix . "CHARACTER_SKILL WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentSkill . "Delete"]);
                    }
                    elseif ((int) $_POST[$currentSkill . "ID"] > 0) {
                        $sql = "UPDATE " . $table_prefix . "CHARACTER_SKILL
                                        SET level   = %d,
                                            comment = %s
                                        WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentSkill], $_POST[$currentSkill . "Comment"], $_POST[$currentSkill . "ID"]);
                    }
                }
                else {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_SKILL (character_id, skill_id, level, comment)
                                    VALUES (%d, %d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $_POST[$currentSkill . "SID"], $_POST[$currentSkill], $_POST[$currentSkill . "Comment"]);
                }
                $wpdb->query($sql);
            }
            $skillCounter++;
        }

        $maxOldDisciplineCount = $_POST['maxOldDisciplineCount'];
        $maxDisciplineCount    = $_POST['maxNewDisciplineCount'];
        $disciplineCounter = 0;
        $currentDiscipline = "";

        while ($disciplineCounter < $maxDisciplineCount) {
            $currentDiscipline = "discipline" . $disciplineCounter;
            if ($_POST[$currentDiscipline] != "" && $_POST[$currentDiscipline] != "-100") {
                if ($disciplineCounter < $maxOldDisciplineCount) {
                    if ((int) $_POST[$currentDiscipline . "Delete"] > 0) {
                        $sql = "DELETE FROM " . $table_prefix . "CHARACTER_DISCIPLINE WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentDiscipline . "Delete"]);
                    }
                    elseif ((int) $_POST[$currentDiscipline . "ID"] > 0) {
                        $sql = "UPDATE " . $table_prefix . "CHARACTER_DISCIPLINE
                                        SET level   = %d,
                                            comment = %s
                                        WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentDiscipline], $_POST[$currentDiscipline . "Comment"], $_POST[$currentDiscipline . "ID"]);
                    }
                }
                else {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_DISCIPLINE (character_id, discipline_id, level, comment)
                                    VALUES (%d, %d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $_POST[$currentDiscipline . "SID"], $_POST[$currentDiscipline], $_POST[$currentDiscipline . "Comment"]);
                }
                $wpdb->query($sql);
            }
            $disciplineCounter++;
        }

        $maxOldComboDisciplineCount = $_POST['maxOldComboDisciplineCount'];
        $maxComboDisciplineCount    = $_POST['maxNewComboDisciplineCount'];
        $comboDisciplineCounter = 0;
        $currentComboDiscipline = "";
        while ($comboDisciplineCounter < $maxComboDisciplineCount) {
            $currentComboDiscipline = "comboDiscipline" . $comboDisciplineCounter;
            if ($_POST[$currentComboDiscipline] != "" && $_POST[$currentComboDiscipline] != "-100") {
                if ($comboDisciplineCounter < $maxOldComboDisciplineCount) {
                    if ((int) $_POST[$currentComboDiscipline . "Delete"] > 0) {
                        $sql = "DELETE FROM " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentComboDiscipline . "Delete"]);
                    }
                    elseif ((int) $_POST[$currentComboDiscipline . "ID"] > 0) {
                        $sql = "UPDATE " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE
                                        SET comment = %s
                                        WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentComboDiscipline . "Comment"], $_POST[$currentComboDiscipline . "ID"]);
                    }
                }
                else {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE (character_id, combo_discipline_id, comment)
                                    VALUES (%d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $_POST[$currentComboDiscipline . "SID"], $_POST[$currentComboDiscipline . "Comment"]);
                }
                $wpdb->query($sql);
                $sql = "";
            }
            $comboDisciplineCounter++;
        }

        $maxOldPathCount = $_POST['maxOldPathCount'];
        $maxPathCount    = $_POST['maxNewPathCount'];
        $pathCounter = 0;
        $currentPath = "";

        while ($pathCounter < $maxPathCount) {
            $currentPath = "path" . $pathCounter;
            if ($_POST[$currentPath] != "" && $_POST[$currentPath] != "-100") {
                if ($pathCounter < $maxOldPathCount) {
                    if ((int) $_POST[$currentPath . "Delete"] > 0) {
                        $sql = "DELETE FROM " . $table_prefix . "CHARACTER_PATH WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentPath . "Delete"]);
                    }
                    elseif ((int) $_POST[$currentPath . "ID"] > 0) {
                        $sql = "UPDATE " . $table_prefix . "CHARACTER_PATH
                                        SET level   = %d,
                                            comment = %s
                                        WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentPath], $_POST[$currentPath . "Comment"], $_POST[$currentPath . "ID"]);
                    }
                }
                else {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_PATH (character_id,
                                                                                     path_id,
                                                                                     level,
                                                                                     comment)
                                    VALUES (%d, %d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $_POST[$currentPath . "SID"], $_POST[$currentPath], $_POST[$currentPath . "Comment"]);
                }
                $wpdb->query($sql);
            }
            $pathCounter++;
        }

        $maxOldRitualCount = $_POST['maxOldRitualCount'];
        $maxRitualCount    = $_POST['maxNewRitualCount'];
        $ritualCounter = 0;
        $currentRitual = "";

        while ($ritualCounter < $maxRitualCount) {
            $currentRitual = "ritual" . $ritualCounter;
            if ($_POST[$currentRitual] != "" && $_POST[$currentRitual] != "-100") {
                if ($ritualCounter < $maxOldRitualCount) {
                    if ((int) $_POST[$currentRitual . "Delete"] > 0) {
                        $sql = "DELETE FROM " . $table_prefix . "CHARACTER_RITUAL WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentRitual . "Delete"]);
                    }
                    elseif ((int) $_POST[$currentRitual . "ID"] > 0) {
                        $sql = "UPDATE " . $table_prefix . "CHARACTER_RITUAL
                                        SET level   = %d,
                                            comment = %s
                                        WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentRitual], $_POST[$currentRitual . "Comment"], $_POST[$currentRitual . "ID"]);
                    }
                }
                else {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_RITUAL (character_id, ritual_id, level, comment)
                                    VALUES (%d, %d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $_POST[$currentRitual . "SID"], $_POST[$currentRitual], $_POST[$currentRitual . "Comment"]);
                }
                $wpdb->query($sql);
            }
            $ritualCounter++;
        }

        $maxOldBackgroundCount = $_POST['maxOldBackgroundCount'];
        $maxBackgroundCount    = $_POST['maxNewBackgroundCount'];
        $backgroundCounter = 0;
        $currentBackground = "";

        while ($backgroundCounter < $maxBackgroundCount) {
            $currentBackground = "background" . $backgroundCounter;
            if ($_POST[$currentBackground] != "" && $_POST[$currentBackground] != "-100") {
                if ($backgroundCounter < $maxOldBackgroundCount) {
                    if ((int) $_POST[$currentBackground . "Delete"] > 0) {
                        $sql = "DELETE FROM " . $table_prefix . "CHARACTER_BACKGROUND WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentBackground . "Delete"]);
                    }
                    elseif ((int) $_POST[$currentBackground . "ID"] > 0) {
                        $sql = "UPDATE " . $table_prefix . "CHARACTER_BACKGROUND
                                        SET level   = %d,
                                            comment = %s
                                        WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentBackground], $_POST[$currentBackground . "Comment"], $_POST[$currentBackground . "ID"]);
                    }
                }
                else {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_BACKGROUND (character_id, background_id, level, comment)
                                    VALUES (%d, %d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $_POST[$currentBackground . "SID"], $_POST[$currentBackground], $_POST[$currentBackground . "Comment"]);
                }
                $wpdb->query($sql);
            }
            $backgroundCounter++;
        }

        $maxOldMeritCount = $_POST['maxOldMeritCount'];
        $maxMeritCount    = $_POST['maxNewMeritCount'];
        $meritCounter = 0;
        $currentMerit = "";

        while ($meritCounter < $maxMeritCount) {
            $currentMerit = "merit" . $meritCounter;
            if ($_POST[$currentMerit] != "" && $_POST[$currentMerit] != "-100") {
                if ($meritCounter < $maxOldMeritCount) {
                    if ((int) $_POST[$currentMerit . "Delete"] > 0) {
                        $sql = "DELETE FROM " . $table_prefix . "CHARACTER_MERIT WHERE id =  %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentMerit . "Delete"]);
                    }
                    elseif ((int) $_POST[$currentMerit . "ID"] > 0) {
                        $sql = "UPDATE " . $table_prefix . "CHARACTER_MERIT
                                        SET level   = %d,
                                            comment = %s
                                        WHERE id = %d";
                        $sql = $wpdb->prepare($sql, $_POST[$currentMerit], $_POST[$currentMerit . "Comment"], $_POST[$currentMerit . "ID"]);
                    }
                }
                else {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_MERIT (character_id, merit_id, level, comment)
                                    VALUES (%d, %d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $_POST[$currentMerit . "SID"], $_POST[$currentMerit], $_POST[$currentMerit . "Comment"]);
                }
                $wpdb->query($sql);
            }
            $meritCounter++;
        }

        $maxOldOfficeCount = $_POST['maxOldOfficeCount'];
        $maxOfficeCount    = $_POST['maxNewOfficeCount'];
        $officeCounter = 0;
        $currentOffice = "";

        while ($officeCounter < $maxOfficeCount) {
            $currentOffice = "office" . $officeCounter;
            if ($_POST[$currentOffice] != "" && $_POST[$currentOffice] != "-100") {
                if ($officeCounter < $maxOldOfficeCount) {
                    if ((int) $_POST[$currentOffice . "Delete"] > 0) {
                        $sql = "DELETE FROM " . $table_prefix . "CHARACTER_OFFICE WHERE id = " . $_POST[$currentOffice . "Delete"];
                    }
                    elseif ((int) $_POST[$currentOffice . "ID"] > 0) {
                        $sql = "UPDATE " . $table_prefix . "CHARACTER_OFFICE
                                        SET comment = '" . $_POST[$currentOffice . "Comment"]  . "'
                                        WHERE id = " . $_POST[$currentOffice . "ID"];
                    }
                }
                else {
                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_OFFICE (character_id, office_id, court_id, comment)
                                    VALUES (%d, %d, %d, %s)";
                    $sql = $wpdb->prepare($sql, $characterID, $_POST[$currentOffice . "OID"], $_POST[$currentOffice . "CID"], $_POST[$currentOffice . "Comment"]);
                }
                $wpdb->query($sql);
            }
            $officeCounter++;
        }

        return $characterID;
    }

    function print_xp_spend($character) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $xpReasons = listXpReasons();

        $sql = "SELECT chara2.id, chara2.name, chara2.player_id
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER chara2,
                             " . $table_prefix . "CHARACTER_STATUS char_status
                        WHERE chara.player_id = chara2.player_id
                          AND chara2.character_status_id = char_status.id
                          AND char_status.name = 'Alive'
                          AND chara.DELETED != 'Y'
                          AND chara2.DELETED != 'Y'
                          AND chara.WORDPRESS_ID = %s";

        $xp_characters = $wpdb->get_results($wpdb->prepare($sql, $character));

        $player_id = "";
        $sqlOutput = "";
        foreach ($xp_characters as $current_character) {
            $sqlOutput .= "<option value=" . $current_character->id . ">" . $current_character->name . "</option>\n";
            $player_id = $current_character->player_id;
        }

        if ($sqlOutput != "") {
            $output  = "<form name=\"Master_XP_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
            $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"player_xp\" />";
            $output .= "<input type='HIDDEN' name=\"player\" value=\"" . $player_id . "\" />\n";
            $output .= "<table class='gvplugin' id=\"pxps\"><tr><td class=\"gvcol_1 gvcol_val\">Add xp change</td>"
                . "<td class=\"gvcol_2 gvcol_val\"><select name=\"character\">"
                . $sqlOutput . "</select></td><td class=\"gvcol_3 gvcol_val\">\n<select name=\"xp_type\">";
            foreach ($xpReasons as $current_reason) {
                $output .= "<option value=\"" . $current_reason->id . "\">" . $current_reason->name . "</option>\n";
            }
            $output .= "</select></td><td class='gvcol_4 gvcol_val'><input type='text' name=\"xp_value\" size=5 maxlength=3 /></td>";
            $output .= "<td class='gvcol_5 gvcol_val'><input type='text' name=\"comment\" size=30 maxlength=110 /></td>";
            $output .= "<td class='gvcol_6 gvcol_val'><input type='submit' name=\"submit\" value=\"Submit XP change\" /></td>";
            $output .= "</tr>\n</table></form>";
        }
        else {
            $output = "<p>No characters found</p>";
        }
        return $output;
    }

    function handleMasterXP() {
        $counter = 1;
        while (isset($_POST['counter_' . $counter])) {
            $current_player_id = $_POST['counter_' . $counter];
            $current_xp_value  = $_POST[$current_player_id . '_xp_value'];
            if (is_numeric($current_xp_value) && ((int) $current_xp_value != 0)) {
                addPlayerXP($current_player_id,
                    $_POST[$current_player_id . '_character'],
                    $_POST[$current_player_id . '_xp_reason'],
                    $current_xp_value,
                    $_POST[$current_player_id . '_xp_comment']);
            }
            $counter++;
        }
    }

    function printSelectCounter($name, $selectedValue, $lowerValue, $upperValue) {
        $output = "<select name=\"" . $name . "\">";
        if ($selectedValue == "") {
            $selectedValue = "-100";
            $output .= "<option value=\"-100\">No Value</option>";
        }
        for ($i = $lowerValue; $i <= $upperValue; $i++) {
            $output .= "<option";
            if ((int) $selectedValue == $i) {
                $output .= " selected";
            }
            $output .= ">" . $i . "</option>";
        }
        $output .= "</select>";
        return $output;
    }

    function addNewPlayer($name, $typeID, $statusID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "INSERT INTO " . $table_prefix . "PLAYER (name, player_type_id, player_status_id)
                        VALUES (%s, %d, %d)";
        $wpdb->query($wpdb->prepare($sql, $name, $typeID, $statusID));
    }

    function addPlayerXP($player, $character, $xpReason, $value, $comment) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "INSERT INTO " . $table_prefix . "PLAYER_XP (player_id, amount, character_id, xp_reason_id, comment, awarded)
                        VALUES (%d, %d, %d, %d, %s, SYSDATE())";
        $wpdb->query($wpdb->prepare($sql, $player, ((int) $value), $character, $xpReason, $comment));
    }

    function listPlayerType() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, name, description
                        FROM " . $table_prefix . "PLAYER_TYPE ptype
                        ORDER BY description";

        $playerTypes = $wpdb->get_results($sql);
        return $playerTypes;
    }

    function listPlayerStatus() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, name, description
                        FROM " . $table_prefix . "PLAYER_STATUS status
                        ORDER BY description";

        $playerTypes = $wpdb->get_results($sql);
        return $playerTypes;
    }

    function listSTLinks() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, value, description, link
                        FROM " . $table_prefix . "ST_LINK stlinks
                        ORDER BY ordering";

        $stLinks = $wpdb->get_results($sql);
        return $stLinks;
    }

    function listPlayers($playerStatus, $playerType) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $statusClause = "";
        if ($playerStatus != null && $playerStatus != "") {
            $statusClause = " AND player_status_id = %d ";
        }

        $typeClause = "";
        if ($playerType != null && $playerType != "") {
            $typeClause = " AND player_type_id = %d ";
        }

        $sql = "SELECT player.ID, player.name, pstatus.name statusname, ptype.name typename
                        FROM " . $table_prefix . "PLAYER player,
                             " . $table_prefix . "PLAYER_STATUS pstatus,
                             " . $table_prefix . "PLAYER_TYPE ptype
                        WHERE player.player_status_id = pstatus.id
                          AND player.player_type_id   = ptype.id
                          " . $statusClause . $typeClause . "
                        ORDER BY name";

        if ($playerStatus != null && $playerStatus != "" && $playerType != null && $playerType != "") {
            $sql = $wpdb->prepare($sql, $playerStatus, $playerType);
        }
        else if ($playerStatus != null && $playerStatus != "") {
            $sql = $wpdb->prepare($sql, $playerStatus);
        }
        else if ($playerType != null && $playerType != "") {
            $sql = $wpdb->prepare($sql, $playerType);
        }

        $players = $wpdb->get_results($sql);
        return $players;
    }

    function listClans() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CLAN
                        ORDER BY name";

        $clans = $wpdb->get_results($sql);
        return $clans;
    }

    function listGenerations() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, name
                FROM " . $table_prefix . "GENERATION
                ORDER BY BLOODPOOL, COST";

        $generations = $wpdb->get_results($sql);
        return $generations;
    }

    function listCharacterStatuses() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CHARACTER_STATUS
                        ORDER BY name";

        $characterStatuses = $wpdb->get_results($sql);
        return $characterStatuses;
    }

    function listCharacterTypes() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CHARACTER_TYPE
                        ORDER BY name";

        $characterTypes = $wpdb->get_results($sql);
        return $characterTypes;
    }

    function listRoadsOrPaths() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "ROAD_OR_PATH
                        ORDER BY name";

        $roadsOrPaths = $wpdb->get_results($sql);
        return $roadsOrPaths;
    }

    function listCourts() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "COURT
                        ORDER BY name";

        $courts = $wpdb->get_results($sql);
        return $courts;
    }

    function listOffices($showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $visible_sector  = " VISIBLE = 'Y' ";
        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "OFFICE ";
        if ($visible_sector != "") {
            $sql .= "WHERE " . $visible_sector;
        }
        $sql .= " ORDER BY ordering, name";

        $offices = $wpdb->get_results($sql);
        return $offices;
    }

    function listCharacters($group, $activeCharacter, $playerName, $activePlayer, $showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $grouping_sector = "";
        $activeCharacter_sector = "";
        $activePlayer_sector = "";
        $playerName_sector = "";
        $visible_sector  = " AND chara.VISIBLE = 'Y' ";

        if ($group != "") {
            $grouping_sector = "AND ctype.name = %s ";
        }
        if ($activeCharacter != "") {
            $activeCharacter_sector = "AND cstatus.name = %s ";
        }
        if ($activePlayer != "") {
            $activePlayer_sector = "AND pstatus.name = %s ";
        }
        if ($playerName != "") {
            $playerName_sector = "AND player.name = %s ";
        }
        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }

        $sql = "SELECT chara.id,
                               chara.name cname,
                               ctype.name typename,
                               cstatus.name cstatusname,
                               chara.visible,
                               player.name pname,
                               pstatus.name pstatusname,
                               chara.wordpress_id wid
                        FROM " . $table_prefix . "CHARACTER chara,
                             " . $table_prefix . "CHARACTER_TYPE ctype,
                             " . $table_prefix . "CHARACTER_STATUS cstatus,
                             " . $table_prefix . "PLAYER player,
                             " . $table_prefix . "PLAYER_STATUS pstatus
                       WHERE chara.character_type_id = ctype.id
                         AND chara.character_status_id = cstatus.id
                         AND chara.player_id = player.id
                         AND player.player_status_id = pstatus.id
                         AND chara.DELETED != 'Y' "
            . $grouping_sector
            . $visible_sector
            . $activeCharacter_sector
            . $activePlayer_sector
            . $playerName_sector . "
                       ORDER BY cstatus.id, ctype.id, chara.name";

        if ($group != "" && $activeCharacter != "" && $activePlayer != "" && $playerName != "") {
            $sql = $wpdb->prepare($sql, $group, $activeCharacter, $activePlayer, $playerName);
        }
        else if ($group != "" && $activeCharacter != "" && $activePlayer != "") {
            $sql = $wpdb->prepare($sql, $group, $activeCharacter, $activePlayer);
        }
        else if ($group != "" && $activeCharacter != "" && $playerName != "") {
            $sql = $wpdb->prepare($sql, $group, $activeCharacter, $playerName);
        }
        else if ($group != "" && $activePlayer != "" && $playerName != "") {
            $sql = $wpdb->prepare($sql, $group, $activePlayer, $playerName);
        }
        else if ($activeCharacter != "" && $activePlayer != "" && $playerName != "") {
            $sql = $wpdb->prepare($sql, $activeCharacter, $activePlayer, $playerName);
        }
        else if ($group != "" && $activeCharacter != "") {
            $sql = $wpdb->prepare($sql, $group, $activeCharacter);
        }
        else if ($group != "" && $activePlayer != "") {
            $sql = $wpdb->prepare($sql, $group, $activePlayer);
        }
        else if ($group != "" && $playerName != "") {
            $sql = $wpdb->prepare($sql, $group, $playerName);
        }
        else if ($activeCharacter != "" && $activePlayer != "") {
            $sql = $wpdb->prepare($sql, $activeCharacter, $activePlayer);
        }
        else if ($activeCharacter != "" && $playerName != "") {
            $sql = $wpdb->prepare($sql, $activeCharacter, $playerName);
        }
        else if ($activePlayer != "" && $playerName != "") {
            $sql = $wpdb->prepare($sql, $activePlayer, $playerName);
        }
        else if ($group != "") {
            $sql = $wpdb->prepare($sql, $group);
        }
        else if ($activeCharacter != "") {
            $sql = $wpdb->prepare($sql, $activeCharacter);
        }
        else if ($activePlayer != "") {
            $sql = $wpdb->prepare($sql, $activePlayer);
        }
        else if ($playerName != "") {
            $sql = $wpdb->prepare($sql, $playerName);
        }

        $characters = $wpdb->get_results($sql);
        return $characters;
    }

    function listStats() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id, name, grouping
                        FROM " . $table_prefix . "STAT
                        ORDER BY ordering";

        return $wpdb->get_results($sql);
    }

    function listXpReasons() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id, name
                        FROM " . $table_prefix . "XP_REASON
                        ORDER BY id";

        return $wpdb->get_results($sql);
    }

    function listPathReasons() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id, name
                    FROM " . $table_prefix . "PATH_REASON
                    ORDER BY id";

        return $wpdb->get_results($sql);
    }

    function listTemporaryStatReasons() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id, name
                    FROM " . $table_prefix . "TEMPORARY_STAT_REASON
                    ORDER BY id";

        return $wpdb->get_results($sql);

    }

    function listSkills($group, $showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $grouping_sector = "";
        $visible_sector  = " VISIBLE = 'Y' ";

        if ($group != "") {
            $grouping_sector = " grouping = %s ";
        }
        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }

        $sql = "SELECT id, name, description, grouping, visible
                        FROM  " . $table_prefix . "SKILL ";
        if ($grouping_sector != "" || $visible_sector != "") {
            $sql .= "WHERE ";
            if ($grouping_sector != "" && $visible_sector != "") {
                $sql .= $grouping_sector . " AND " . $visible_sector;
            }
            elseif ($grouping_sector != "") {
                $sql .= $grouping_sector;
            }
            else {
                $sql .= $visible_sector;
            }
        }
        $sql .= " ORDER BY name";

        if ($grouping_sector != "") {
            $sql = $wpdb->prepare($sql, $group);
        }

        $skills = $wpdb->get_results($sql);
        return $skills;
    }

    function listDisciplines($showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $visible_sector  = " VISIBLE = 'Y' ";

        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }

        $sql = "SELECT id, name, description, visible
                        FROM  " . $table_prefix . "DISCIPLINE ";
        if ($visible_sector != "") {
            $sql .= "WHERE " . $visible_sector;
        }
        $sql .= " ORDER BY name";

        $disciplines = $wpdb->get_results($sql);
        return $disciplines;
    }

    function listComboDisciplines($showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $visible_sector  = " VISIBLE = 'Y' ";

        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }

        $sql = "SELECT id, name, description, visible
                        FROM  " . $table_prefix . "COMBO_DISCIPLINE ";
        if ($visible_sector != "") {
            $sql .= "WHERE " . $visible_sector;
        }
        $sql .= " ORDER BY name";

        $combo_disciplines = $wpdb->get_results($sql);
        return $combo_disciplines;
    }

    function listPaths($showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $visible_sector  = " AND VISIBLE = 'Y' ";

        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }

        $sql = "SELECT path.id, path.name, path.description, path.visible, discipline.name disname
                        FROM  " . $table_prefix . "PATH path, "
            . $table_prefix . "DISCIPLINE discipline
                        WHERE path.discipline_id = discipline.id  " . $visible_sector .
            " ORDER BY disname, path.name";

        $paths = $wpdb->get_results($sql);
        return $paths;
    }

    function listRituals($showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $visible_sector  = " AND VISIBLE = 'Y' ";

        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }

        $sql = "SELECT ritual.id, ritual.name, ritual.description, ritual.level, ritual.visible, discipline.name disname
                        FROM  " . $table_prefix . "RITUAL ritual, "
            . $table_prefix . "DISCIPLINE discipline
                        WHERE ritual.discipline_id = discipline.id  " . $visible_sector .
            " ORDER BY disname, ritual.level, ritual.name";

        $rituals = $wpdb->get_results($sql);
        return $rituals;
    }

    function listBackgrounds($group, $showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $grouping_sector = "";
        $visible_sector  = " VISIBLE = 'Y' ";

        if ($group != "") {
            $grouping_sector = " grouping = %s ";
        }
        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }

        $sql = "SELECT id, name, description, grouping, visible
                        FROM  " . $table_prefix . "BACKGROUND ";
        if ($grouping_sector != "" || $visible_sector != "") {
            $sql .= "WHERE ";
            if ($grouping_sector != "" && $visible_sector != "") {
                $sql .= $grouping_sector . " AND " . $visible_sector;
            }
            elseif ($grouping_sector != "") {
                $sql .= $grouping_sector;
            }
            else {
                $sql .= $visible_sector;
            }
        }
        $sql .= " ORDER BY name";

        if ($grouping_sector != "") {
            $sql = $wpdb->prepare($sql, $group);
        }

        $backgrounds = $wpdb->get_results($sql);
        return $backgrounds;
    }

    function listMerits($group, $showNotVisible) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $grouping_sector = "";
        $visible_sector  = " VISIBLE = 'Y' ";

        if ($group != "") {
            $grouping_sector = " grouping = %s ";
        }
        if ($showNotVisible == "Y") {
            $visible_sector = "";
        }

        $sql = "SELECT id, name, description, grouping, visible, value
                        FROM  " . $table_prefix . "MERIT ";
        if ($grouping_sector != "" || $visible_sector != "") {
            $sql .= "WHERE ";
            if ($grouping_sector != "" && $visible_sector != "") {
                $sql .= $grouping_sector . " AND " . $visible_sector;
            }
            elseif ($grouping_sector != "") {
                $sql .= $grouping_sector;
            }
            else {
                $sql .= $visible_sector;
            }
        }
        $sql .= " ORDER BY name";

        if ($grouping_sector != "")  {
            $sql = $wpdb->prepare($sql, $group);
        }

        $merits = $wpdb->get_results($sql);
        return $merits;
    }

    function isST() {
        $result = false;
        $current_user = wp_get_current_user();
        $roles = $current_user->roles;

        foreach ($roles as $current_role) {
            if ($current_role == "administrator"
                || $current_role == "storyteller") {
                $result = true;
            }
        }

        return $result;
    }

    function establishCharacter($character) {
        if (isST()) {
            if (isset($_POST['GVLARP_CHARACTER'])) {
                $character = $_POST['GVLARP_CHARACTER'];
            }
            elseif (isset($_GET['CHARACTER'])) {
                $character = $_GET['CHARACTER'];
            }
            elseif ($character == null || $character == "null" || $character == "") {
                $current_user = wp_get_current_user();
                $character = $current_user->user_login;
            }
        }
        else {
            $current_user = wp_get_current_user();
            $character = $current_user->user_login;
        }

        return $character;
    }

    function establishCharacterID($character) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id
                        FROM " . $table_prefix . "CHARACTER
                        WHERE WORDPRESS_ID = %s";
        $characterIDs = $wpdb->get_results($wpdb->prepare($sql, $character));
        $cid = null;
        foreach ($characterIDs as $characterID) {
            $cid = $characterID->id;
        }
        return $cid;
    }

    function establishPlayerID($character) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT player_id
                        FROM " . $table_prefix . "CHARACTER
                        WHERE WORDPRESS_ID = %s";
        $playerIDs = $wpdb->get_results($wpdb->prepare($sql, $character));
        $pid = null;
        foreach ($playerIDs as $playerID) {
            $pid = $playerID->player_id;
        }
        return $pid;
    }

    function establishXPReasonID($xpReasonString) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id
                    FROM " . $table_prefix . "XP_REASON
                    WHERE NAME = %s";
        $reasonIDs = $wpdb->get_results($wpdb->prepare($sql, $xpReasonString));
        $rid = null;
        foreach ($reasonIDs as $reasonID) {
            $rid = $reasonID->id;
        }
        return $rid;
    }

    function establishPathReasonID($pathReasonString) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id
                        FROM " . $table_prefix . "PATH_REASON
                        WHERE NAME = %s";
        $reasonIDs = $wpdb->get_results($wpdb->prepare($sql, $pathReasonString));
        $rid = null;
        foreach ($reasonIDs as $reasonID) {
            $rid = $reasonID->id;
        }
        return $rid;
    }

    function establishTempStatID($tempStatString) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id
                FROM " . $table_prefix . "TEMPORARY_STAT
                WHERE NAME = %s";
        $reasonIDs = $wpdb->get_results($wpdb->prepare($sql, $tempStatString));
        $rid = null;
        foreach ($reasonIDs as $reasonID) {
            $rid = $reasonID->id;
        }
        return $rid;
    }

    function establishTempStatReasonID($tempStatReasonString) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT id
                FROM " . $table_prefix . "TEMPORARY_STAT_REASON
                WHERE NAME = %s";
        $reasonIDs = $wpdb->get_results($wpdb->prepare($sql, $tempStatReasonString));
        $rid = null;
        foreach ($reasonIDs as $reasonID) {
            $rid = $reasonID->id;
        }
        return $rid;
    }

    function getConfig() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "SELECT PROFILE_LINK, PLACEHOLDER_IMAGE, CLAN_DISCIPLINE_DISCOUNT
                FROM " . $table_prefix . "CONFIG";

        $configs = $wpdb->get_results($sql);
        foreach ($configs as $config) {
            return $config;
        }
    }

    function changeDisplayName ($character, $newDisplayName) {
        if (!isST()) {
            $current_user = wp_get_current_user();
            $userID = $current_user->ID;
        }
        else {
            $user = get_userdatabylogin($character);
            $userID = $user->ID;
        }

        $args = array ('ID' => $userID, 'display_name' => $newDisplayName);
        wp_update_user($args);
        return true;
    }

    function changePassword($character, $newPassword1, $newPassword2) {
        if (!isST()) {
            $current_user = wp_get_current_user();
            $userID = $current_user->ID;
        }
        else {
            $user = get_userdatabylogin($character);
            $userID = $user->ID;
        }

        if ($newPassword1 == $newPassword2) {
            wp_set_password($newPassword1, $userID);
            return true;
        }
        else {
            return false;
        }
    }

    function handleGVLarpForm() {
        switch($_POST['GVLARP_FORM']) {
            case "new_player":
                addNewPlayer($_POST['player_name'], $_POST['player_type'], $_POST['player_status']);
                break;
            case "player_xp":
                addPlayerXP($_POST['player'], $_POST['character'], $_POST['xp_type'], $_POST['xp_value'], $_POST['comment']);
                break;
            case "master_xp_update":
                handleMasterXP();
                break;
        }
    }

    if (isset($_POST['GVLARP_FORM'])) {
        handleGVLarpForm();
    }

    /*
            function numberToDots($base, $input) {
                $number = (int) $input;
                if ($number > -1 && $number < 11) {
                    $basepath = WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__), "", plugin_basename(__FILE__));
                    return "<img src=\"" . $basepath . "images/reddot-" . $base . "-" .$number . ".gif\" />";
                }
                else {
                    return "Illegal number of dots (" . $input . ")";
                }
            }
        */
?>