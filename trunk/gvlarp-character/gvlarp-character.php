<?php
    /*  Plugin Name: GVLarp Character Plugin
        Plugin URI: http://www.gvlarp.com/character-plugin
        Description: Plugin to store and display PCs and NPCs of GVLarp
        Author: Lambert Behnke & Jane Houston
        Version: 1.7.0
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
		Version 1.7.0 PDF version of the character sheet now available. Added wp-admin pages to manage Merits 
					  & Flaws, Rituals, Backgrounds, Sourcebooks, Extended Background questions, Sectors, clans, 
					  page locations & PDF customisations. Added extended backgrounds, with functionality for 
					  STs to approve. Split off main PHP file into include files. Updated installation functions
					  for properly upgrading the database when plugin is activated.  Added initial table data 
					  during installation for ST links, Sectors, Extended Background questions, Generations,
					  player status, character status, Attributes/stats, Clans.

        Comments:

	*/

    /*
        DB Changes: 
		
		Version 1.7.0 
			Table PLAYER_TYPE, 		DESCRIPTION type changed to TINYTEXT
			Table PLAYER_STATUS, 	DESCRIPTION type changed to TINYTEXT
			Table ST_LINK, 			DESCRIPTION type changed to TINYTEXT
			Table ST_LINK, 			LINK type changed to TINYTEXT
			Table OFFICE, 			DESCRIPTION type changed to TINYTEXT
			Table XP_REASON, 		DESCRIPTION type changed to TINYTEXT
			Table PATH_REASON, 		DESCRIPTION type changed to TINYTEXT
			Table TEMPORARY_STAT_REASON, DESCRIPTION type changed to TINYTEXT
			Table CHARACTER_TYPE, 	DESCRIPTION type changed to TINYTEXT
			Table CHARACTER_STATUS, DESCRIPTION type changed to TINYTEXT
			Table CLAN, 			DESCRIPTION type changed to TINYTEXT
			Table CLAN, 			Added field CLAN_PAGE_LINK
			Table CLAN, 			Added field CLAN_FLAW
			Table COURT, 			DESCRIPTION type changed to TINYTEXT
			Table SOURCE_BOOK, 		DESCRIPTION type changed to TINYTEXT
			Table COST_MODEL, 		DESCRIPTION type changed to TINYTEXT
			Table STAT, 			DESCRIPTION type changed to TINYTEXT
			Table STAT, 			Added field SPECIALISATION_AT
			Table TEMPORARY_STAT, 	DESCRIPTION type changed to TINYTEXT
			Table SKILL, 			DESCRIPTION type changed to TINYTEXT
			Table SKILL, 			Added field MULTIPLE
			Table SKILL, 			Added field SPECIALISATION_AT
			Table BACKGROUND, 		DESCRIPTION type changed to TINYTEXT
			Table HAS_SECTOR, 		DESCRIPTION type changed to TINYTEXT
			Added table SECTOR
			Table MERIT, 			DESCRIPTION type changed to TINYTEXT
			Table DISCIPLINE, 		DESCRIPTION type changed to TINYTEXT
			Table PATH, 			DESCRIPTION type changed to TINYTEXT
			Table DISCIPLINE_POWER, DESCRIPTION type changed to TINYTEXT
			Table PATH_POWER, 		DESCRIPTION type changed to TINYTEXT
			Table RITUAL, 			DESCRIPTION type changed to TINYTEXT
			Table CHARACTER_MERIT, 	Added field APPROVED_DETAIL
			Table CHARACTER_MERIT, 	Added field PENDING_DETAIL
			Table CHARACTER_MERIT, 	Added field DENIED_DETAIL
			Table CHARACTER_BACKGROUND, 	Added field APPROVED_DETAIL
			Table CHARACTER_BACKGROUND, 	Added field PENDING_DETAIL
			Table CHARACTER_BACKGROUND, 	Added field DENIED_DETAIL
			Table CHARACTER_BACKGROUND, 	Added field SECTOR_ID
			Table COMBO_DISCIPLINE, QUOTE type changed to TEXT
			Table COMBO_DISCIPLINE, PORTRAIT type changed to TINYTEXT
			Table CONFIG, 			PROFILE_LINK type changed to TINYTEXT
			Table CONFIG, 			PLACEHOLDER_IMAGE type changed to TINYTEXT
			Removed table EXTENDED_CHARACTER_BACKGROUND
			Added table EXTENDED_BACKGROUND
			Added table CHARACTER_EXTENDED_BACKGROUND
			
         */
define( 'GVLARP_CHARACTER_URL', plugin_dir_path(__FILE__) );define( 'GVLARP_TABLE_PREFIX', $wpdb->prefix . "GVLARP_" );
require_once GVLARP_CHARACTER_URL . 'inc/printable.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages.php';
require_once GVLARP_CHARACTER_URL . 'inc/install.php';
require_once GVLARP_CHARACTER_URL . 'inc/extendedbackground.php';
require_once GVLARP_CHARACTER_URL . 'inc/widgets.php';
require_once GVLARP_CHARACTER_URL . 'inc/android.php';
require_once GVLARP_CHARACTER_URL . 'inc/xpfunctions.php';

function register_plugin_styles() {
	wp_register_style( 'my-plugin', plugins_url( 'my-plugin/css/plugin.css' ) );
	wp_enqueue_style( 'my-plugin' );
}
function plugin_style()  
{ 
  wp_register_style( 'plugin-style', plugins_url( 'gvlarp-character/css/style-plugin.css' ) );
  wp_enqueue_style( 'plugin-style' );
}
add_action('wp_enqueue_scripts', 'plugin_style');

function get_booknames() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK;";
	$booklist = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $booklist;
}
function get_disciplines() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX . "DISCIPLINE;";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function get_costmodels() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX . "COST_MODEL;";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function get_sectors($showhidden = false) {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX . "SECTOR";
	if (!$showhidden)
		$sql .= " WHERE VISIBLE = 'Y'";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function get_stlink_page($stlinkvalue) {
	global $wpdb;

	$sql = "select DESCRIPTION, LINK from " . GVLARP_TABLE_PREFIX . "ST_LINK where VALUE = %s;";
	$results = $wpdb->get_results($wpdb->prepare($sql, $stlinkvalue));
	
	$pageid   = 0;
	$pagename = "Page not matched";
	if (count($results) == 1) {
		$pages = get_pages();
		foreach ( $pages as $page ) {
			if ('/' . get_page_uri( $page->ID ) == $results[0]->LINK) {
				$pageid = $page->ID;
				$pagename = $page->post_title;
			}
		}		
	}
	
	return $pagename;

}
function get_stlink_url($stlinkvalue) {
	global $wpdb;

	$sql = "select DESCRIPTION, LINK from " . GVLARP_TABLE_PREFIX . "ST_LINK where VALUE = %s;";
	$results = $wpdb->get_results($wpdb->prepare($sql, $stlinkvalue));
	
	$url = "Page not matched";
	if (count($results) == 1) {
		$url = $results[0]->LINK;
	}
	
	return $url;

}

function get_clans() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX . "CLAN;";
	$list = $wpdb->get_results($sql);
	
	return $list;
}


    function print_character_stats($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);

        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                                   <td class=\"gvcol_2 gvcol_spec\">" . stripslashes($current_stat->comment) . "</td>
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                                      <td class=\"gvcol_2 gvcol_spec\">" . stripslashes($current_skill->comment) . "</td>
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                                      <td class=\"gvcol_2 gvcol_spec\">" . stripslashes($current_discipline->comment) . "</td>
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
                                       <td class=\"gvcol_2 gvcol_spec\">" . stripslashes($current_combo_discipline->comment) . "</td></tr>";
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                                      <td class=\"gvcol_2 gvcol_spec\">" . stripslashes($current_path->comment) . "</td>
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
        $table_prefix = GVLARP_TABLE_PREFIX;
        $output    = "";
        $sqlOutput = "";

        if ($group == "Overview" || $group == "") {

            $sql = "SELECT rit.name, cha_rit.comment, rit.level
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
			$sql = $wpdb->prepare($sql, $character);
			
            $character_rituals = $wpdb->get_results($sql);
			/* print "<tr><td colspan=3>SQL: $sql</td></tr>"; */
            foreach ($character_rituals as $current_ritual) {
                $sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_ritual->name    . "</td>
                                          <td class=\"gvcol_2 gvcol_spec\">" . stripslashes($current_ritual->comment) . "</td>
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                                      <td class=\"gvcol_2 gvcol_spec\">" . stripslashes($current_merit->comment) . "</td>
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                                      <td class=\"gvcol_3 gvcol_spec\">" . stripslashes($current_office->comment)     . "</td></tr>";
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                                      <td class=\"gvcol_2 gvcol_spec\">" . stripslashes($current_background->comment) . "</td>
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
        $table_prefix = GVLARP_TABLE_PREFIX;

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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                $sqlOutput .= "<td class=\"gvcol_2 gvcol_val\">" . $characterOffice->charname . "</td><td class=\"gvcol_3 gvcol_val\">" . stripslashes($characterOffice->comment) . "</td></tr>";
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                                   " . $table_prefix . "CLAN clan,
								   " . $table_prefix . "CHARACTER_STATUS cstatus
                              WHERE target_char.PUBLIC_CLAN_ID = clan.ID
                                AND target_char.player_id = player.id
                                AND player.player_status_id = pstatus.id
                                AND target_char.court_id = court.id
                                AND pstatus.name = 'Active'
								AND target_char.character_status_id = cstatus.id
								AND cstatus.name = 'Alive'
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
                                    " . $table_prefix . "CHARACTER_BACKGROUND cha_back,
 								    " . $table_prefix . "CHARACTER_STATUS cstatus
                              WHERE source_clan.name = %s
                                 AND target_char.ID = cha_back.CHARACTER_ID
                                 AND cha_back.BACKGROUND_ID = back.id
                                 AND back.NAME = 'Clan Prestige'
                                 AND cha_back.comment = source_clan.name
                                 AND target_char.DELETED != 'Y'
                                 AND target_char.player_id = player.id
                                 AND target_char.court_id = target_court.id
								 AND target_char.character_status_id = cstatus.id
								 AND cstatus.name = 'Alive'
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
                                   " . $table_prefix . "CLAN clan,
 								   " . $table_prefix . "CHARACTER_STATUS cstatus
                              WHERE target_char.PUBLIC_CLAN_ID = source_char.PUBLIC_CLAN_ID
                                AND target_char.PUBLIC_CLAN_ID = clan.id
                                AND (target_char.VISIBLE = 'Y' OR target_char.id = source_char.id)
                                AND target_char.DELETED != 'Y'
                                AND target_char.player_id = player.id
                                AND target_char.court_id = court.id
								AND target_char.character_status_id = cstatus.id
                                AND player.player_status_id = pstatus.id
                                AND pstatus.name = 'Active'
                                AND source_char.DELETED != 'Y'
								AND cstatus.name = 'Alive'
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
                                    " . $table_prefix . "CHARACTER_BACKGROUND cha_back,
 								    " . $table_prefix . "CHARACTER_STATUS cstatus
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
								 AND target_char.character_status_id = cstatus.id
                                 AND player.player_status_id = pstatus.id
                                 AND pstatus.name = 'Active'
								 AND cstatus.name = 'Alive'
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
                                   <td colspan=2 class=\"gvcol_3 gvcol_val\">" . $currentCharacter->merit_name . " " . stripslashes($currentCharacter->comment) . "</td></tr>";
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;

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

					touch_last_updated($characterID);
					
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
        $table_prefix = GVLARP_TABLE_PREFIX;

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
						
                    } else {
						touch_last_updated($characterID);
					}

                    $sql = "INSERT INTO " . $table_prefix . "CHARACTER_TEMPORARY_STAT (character_id,
                                                                                       temporary_stat_id,
                                                                                       temporary_stat_reason_id,
                                                                                       awarded,
                                                                                       amount,
                                                                                       comment)
                            VALUES (%d, (SELECT id FROM " . $table_prefix . "TEMPORARY_STAT WHERE name = %s), %d, SYSDATE(), %d, %s )";
                    $sql = $wpdb->prepare($sql, $characterID, $stat, $_POST[$characterID . '_temp_stat_reason'], $current_temp_stat_value, $_POST[$characterID . '_temp_stat_comment']);

                    $result = $wpdb->query($sql);
					

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
        $table_prefix = GVLARP_TABLE_PREFIX;

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
                    . stripslashes($current_xp->comment)     . "</td><td class='gvcol_5 gvcol_val'>"
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
        $table_prefix = GVLARP_TABLE_PREFIX;

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
                    . stripslashes($current_path->comment)     . "</td><td class='gvcol_4 gvcol_val'>"
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
        $table_prefix = GVLARP_TABLE_PREFIX;

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
                    $clanPrestige .= " (" . stripslashes($currentPrestige->comment) . ")";
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
                    $clanEnmity .= stripslashes($currentMerit->comment);
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
            $quote    = stripslashes($profile->quote);
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

    function displayUpdatePlayer($playerID) {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
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

 
    function processPlayerUpdate($playerID) {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;

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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
                    $characterHarpyQuote  = stripslashes($characterProfile->QUOTE);
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
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\"" . $statName . "Comment\" value=\"" . stripslashes($currentStat->comment) . "\" /></td>"
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
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $skillName . "Comment\" value=\"" . stripslashes($characterSkill->comment)  . "\" /></td>"
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
                    . "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $disciplineName . "Comment\" value=\"" . stripslashes($characterDiscipline->comment)  . "\" /></td>"
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
                    . "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $backgroundName . "Comment\" value=\"" . stripslashes($characterBackground->comment)  . "\" /></td>"
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
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $meritName . "Comment\" value=\"" . stripslashes($characterMerit->comment)  . "\" /></td>"
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
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $comboDisciplineName . "Comment\" value=\"" . stripslashes($characterComboDiscipline->comment)  . "\" /></td>"
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
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $pathName . "Comment\" value=\"" . stripslashes($characterPath->comment)  . "\" /></td>"
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
                    . "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $ritualName . "Comment\" value=\"" . stripslashes($characterRitual->comment)  . "\" /></td>"
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
                    . "<td class='gvcol_4 gvcol_val'><input type='text' name=\""     . $officeName . "Comment\" value=\"" . stripslashes($characterOffice->comment)  . "\" /></td>"
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdateCharacter\" />";        $output .= "<input type='HIDDEN' name=\"characterID\" value=\"" . $characterID . "\" />";        $output .= "<table class='gvplugin' id=\"gvid_gvid_sdc\"><tr><td class=\"gvcol_1 gvcol_val\">";
        $output .= "<input type='submit' name=\"cSubmit\" value=\"Confirm Delete\" /></td><td>";
        $output .= "<input type='submit' name=\"cSubmit\" value=\"Abandon Delete\" /></td></tr></table></form>";
        return $output;
    }

    function deleteCharacter($characterID) {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;

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
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdateCharacter\" />";        $output .= "<table class='gvplugin' id=\"gvid_dcf\"><tr><td class=\"gvcol_1 gvcol_val\">";
        $output .= "<input type='submit' name=\"cSubmit\" value=\"Back to the character list\" /></td></tr></table></form>";
		
		touch_last_updated($characterID);
		
        return $output;
    }

    function processCharacterUpdate($characterID) {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;

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
		
		touch_last_updated($characterID);

        return $characterID;
    }

    function print_xp_spend($character) {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;

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
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "INSERT INTO " . $table_prefix . "PLAYER (name, player_type_id, player_status_id)
                        VALUES (%s, %d, %d)";
        $wpdb->query($wpdb->prepare($sql, $name, $typeID, $statusID));
    }

    function listPlayerType() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, name, description
                        FROM " . $table_prefix . "PLAYER_TYPE ptype
                        ORDER BY description";

        $playerTypes = $wpdb->get_results($sql);
        return $playerTypes;
    }

    function listPlayerStatus() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, name, description
                        FROM " . $table_prefix . "PLAYER_STATUS status
                        ORDER BY description";

        $playerTypes = $wpdb->get_results($sql);
        return $playerTypes;
    }

    function listSTLinks() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, value, description, link
                        FROM " . $table_prefix . "ST_LINK stlinks
                        ORDER BY ordering";

        $stLinks = $wpdb->get_results($sql);
        return $stLinks;
    }

    function listPlayers($playerStatus, $playerType) {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;

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
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CLAN
                        ORDER BY name";

        $clans = $wpdb->get_results($sql);
        return $clans;
    }

    function listGenerations() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, name
                FROM " . $table_prefix . "GENERATION
                ORDER BY BLOODPOOL, COST";

        $generations = $wpdb->get_results($sql);
        return $generations;
    }

    function listCharacterStatuses() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CHARACTER_STATUS
                        ORDER BY name";

        $characterStatuses = $wpdb->get_results($sql);
        return $characterStatuses;
    }

    function listCharacterTypes() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CHARACTER_TYPE
                        ORDER BY name";

        $characterTypes = $wpdb->get_results($sql);
        return $characterTypes;
    }

    function listRoadsOrPaths() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "ROAD_OR_PATH
                        ORDER BY name";

        $roadsOrPaths = $wpdb->get_results($sql);
        return $roadsOrPaths;
    }

    function listCourts() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "COURT
                        ORDER BY name";

        $courts = $wpdb->get_results($sql);
        return $courts;
    }

    function listOffices($showNotVisible) {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;

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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT id, name, grouping
                        FROM " . $table_prefix . "STAT
                        ORDER BY ordering";

        return $wpdb->get_results($sql);
    }

    function listXpReasons() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT id, name
                        FROM " . $table_prefix . "XP_REASON
                        ORDER BY id";

        return $wpdb->get_results($sql);
    }

    function listPathReasons() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT id, name
                    FROM " . $table_prefix . "PATH_REASON
                    ORDER BY id";

        return $wpdb->get_results($sql);
    }

    function listTemporaryStatReasons() {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT id, name
                    FROM " . $table_prefix . "TEMPORARY_STAT_REASON
                    ORDER BY id";

        return $wpdb->get_results($sql);

    }

    function listSkills($group, $showNotVisible) {
        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
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
        $table_prefix = GVLARP_TABLE_PREFIX;
        $sql = "SELECT * FROM " . $table_prefix . "CONFIG";

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

	function touch_last_updated($characterID) {
		global $wpdb;

		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER",
				array ('LAST_UPDATED' => Date('Y-m-d')),
				array ('ID' => $characterID)
			);
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