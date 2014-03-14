<?php
    /*  Plugin Name: GVLarp Character Plugin
        Plugin URI: http://www.gvlarp.com/character-plugin
        Description: Management of Characters and Players for Vampire-the Masquerade
        Author: Lambert Behnke & Jane Houston
        Version: 1.8.0
        Author URI: http://www.gvlarp.com
    */

    /*  Copyright 2014 Lambert Behnke and Jane Houston

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
		Version 1.8.0 Alot more admin pages created. Caitiff XP Spends now supported. Nature/Demeanour, sect 
					  membership, assigning xp by character now supported. Solar Calc widget incorporated.
					  Shortcodes ‘status_list_block’, ‘dead_character_table’ and ‘prestige_list_block’ replaced 
					  with ‘background_table’ and ‘merit_table’ to support display of other backgrounds 
					  (e.g. Anarch Status). Removed shortcode ‘xp_spend_table’. Page now generated with content 
					  filter. XP Spend page re-written to allow multiple spends at the one time. Also shows 
					  pending spends and allows them to be cancelled.
		Version 1.9.0 Admin-related shortcodes have been moved to WP admin pages. Generation table can now be
					  managed and the default gen for new characters defined.  Sort fixed on XP Approval page
					  and you can only assign XP to visible characters.  On Xp spend page, only get check-boxes
					  if the character has enough XP. Can buy over WP 5. Thaum paths now show up on printable
					  sheet. Combo-disciplines can be purchased with XP. Ritual descriptions show up  on
					  printable sheet. Character info doesn't get displayed if you aren't logged in. Pending
					  XP changes don't get deleted if approval fails. Extended Background widget added. Show
					  Feeding domains in a googlemaps api shortcode. Fixed PDF report issue for data generated 
					  on the 2nd page and onward
        Comments:

	*/

    /*
        DB Changes: 
		
		Version 1.9.10
			Table GENERATION,			Removed column COST
			Table MERIT,				Added column PROFILE_DISPLAY_ID
			Table CONFIG,				Added column DISPLAY_BACKGROUND_IN_PROFILE
			Table CHARACTER,			Restored column VISIBLE
			Table CHARACTER,			Constraint on NATURE_ID removed
			Table CHARACTER,			Constraint on DEMEANOUR_ID removed
			Table CHARACTER_BACKGROUND,	Constraint on SECTOR_ID removed
			Added PROFILE_DISPLAY Table
			Added MAPOWNER Table
			Added MAPDOMAIN Table
			
         */
global $wpdb;

define( 'VTM_CHARACTER_URL', plugin_dir_path(__FILE__) );
define( 'VTM_TABLE_PREFIX', $wpdb->prefix . "vtm_" );
require_once VTM_CHARACTER_URL . 'inc/printable.php';
require_once VTM_CHARACTER_URL . 'inc/install.php';
require_once VTM_CHARACTER_URL . 'inc/extendedbackground.php';
require_once VTM_CHARACTER_URL . 'inc/widgets.php';
require_once VTM_CHARACTER_URL . 'inc/android.php';
require_once VTM_CHARACTER_URL . 'inc/xpfunctions.php';
require_once VTM_CHARACTER_URL . 'inc/shortcodes.php';
require_once VTM_CHARACTER_URL . 'inc/adminpages.php';
require_once VTM_CHARACTER_URL . 'inc/viewcharacter.php';
require_once VTM_CHARACTER_URL . 'inc/profile.php';

$title = "V:tM Character Management";

/* STYLESHEETS
------------------------------------------------------ */

function vtm_feedingmap_admin_css() {
	wp_enqueue_style('plugin-admin-style', plugins_url('gvlarp-character/css/style-admin.css'));
}
add_action('admin_enqueue_scripts', 'vtm_feedingmap_admin_css');

function vtm_plugin_style()  
{ 
  wp_register_style( 'plugin-style', plugins_url( 'gvlarp-character/css/style-plugin.css' ) );
  wp_enqueue_style( 'plugin-style' );
}
add_action('wp_enqueue_scripts', 'vtm_plugin_style');

/* JAVASCRIPT
----------------------------------------------------------------- */
function vtm_feedingmap_scripts() {
	wp_enqueue_script( 'feedingmap-setup-api', plugins_url('gvlarp-character/js/googleapi.js'));
}

add_action( 'wp_enqueue_scripts', 'vtm_feedingmap_scripts' );
add_action('admin_enqueue_scripts', 'vtm_feedingmap_scripts');


/* FUNCTIONS
----------------------------------------------------------------- */

function vtm_get_stat_info() {
	global $wpdb;

	$sql = "SELECT NAME, ID FROM " . VTM_TABLE_PREFIX . "STAT;";
	$statinfo = $wpdb->get_results($sql, OBJECT_K);
	
	return $statinfo;
}
function vtm_get_booknames() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "SOURCE_BOOK;";
	$booklist = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $booklist;
}
function vtm_get_disciplines() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "DISCIPLINE;";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function vtm_get_costmodels() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "COST_MODEL;";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function vtm_get_natures() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "NATURE;";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function vtm_get_backgrounds() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "BACKGROUND WHERE VISIBLE = 'Y';";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function vtm_get_profile_display() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "PROFILE_DISPLAY;";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function vtm_get_sectors($showhidden = false) {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "SECTOR";
	if (!$showhidden)
		$sql .= " WHERE VISIBLE = 'Y'";
	$list = $wpdb->get_results($wpdb->prepare($sql,''));
	
	return $list;
}
function vtm_get_stlink_page($stlinkvalue) {
	global $wpdb;

	$sql = "select DESCRIPTION, LINK from " . VTM_TABLE_PREFIX . "ST_LINK where VALUE = %s;";
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
function vtm_get_stlink_url($stlinkvalue) {
	global $wpdb;

	$sql = "select DESCRIPTION, LINK from " . VTM_TABLE_PREFIX . "ST_LINK where VALUE = %s;";
	$results = $wpdb->get_results($wpdb->prepare($sql, $stlinkvalue));
	
	$url = "Page not matched";
	if (count($results) == 1) {
		$url = $results[0]->LINK;
	}
	
	return $url;

}
function vtm_get_total_xp($playerID = 0, $characterID = 0) {
	global $wpdb;
	
	$config = vtm_getConfig();
	$filteron = $config->ASSIGN_XP_BY_PLAYER == 'Y' ? "PLAYER_ID" : "CHARACTER_ID";
	$filterid = $config->ASSIGN_XP_BY_PLAYER == 'Y' ? $playerID   : $characterID;
	
	$sql = "SELECT SUM(xpspends.amount) as total
			FROM
				" . VTM_TABLE_PREFIX . "PLAYER_XP as xpspends
			WHERE
				xpspends.$filteron = '%s'";
	$sql = $wpdb->prepare($sql, $filterid);
	$result = $wpdb->get_var($sql);
	
	return $result;

}
function vtm_get_clans() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "CLAN ORDER BY NAME;";
	$list = $wpdb->get_results($sql);
	
	return $list;
}
function vtm_get_domains() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "DOMAIN;";
	$list = $wpdb->get_results($sql);
	
	return $list;
}
function vtm_get_player_status() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "PLAYER_STATUS;";
	$list = $wpdb->get_results($sql);
	
	return $list;
}
function vtm_get_player_type() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "PLAYER_TYPE ORDER BY NAME;";
	$list = $wpdb->get_results($sql);
	
	//print_r($list);
	
	return $list;
}
function vtm_get_generations() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "GENERATION ORDER BY BLOODPOOL, MAX_DISCIPLINE;";
	$list = $wpdb->get_results($sql);
	
	//print_r($list);
	
	return $list;
}
	function vtm_get_sects() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "SECT;";
	$list = $wpdb->get_results($sql);
	
	return $list;
}
 
    function vtm_print_name_value_pairs($atts, $content=null) {
        $output = "";
        if (isST()) {
            $output .= "<table>";
            foreach($_POST as $key=>$value) {
				$output .= "<tr><td>" . $key . "</td><td>";
				if (is_array($value))
					foreach($value as $key2 => $val2) {
						$output .= "$key2 = $val2,";
					}
				$output .= "</td></tr>";
            }
            $output .= "</table>";
        }
        return $output;
    }
    add_shortcode('debug_name_value_pairs', 'vtm_print_name_value_pairs');

    function vtm_printSelectCounter($name, $selectedValue, $lowerValue, $upperValue) {
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

    function vtm_listPlayerType() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, name, description
                        FROM " . $table_prefix . "PLAYER_TYPE ptype
                        ORDER BY description";

        $playerTypes = $wpdb->get_results($sql);
        return $playerTypes;
    }

    function vtm_listPlayerStatus() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, name, description
                        FROM " . $table_prefix . "PLAYER_STATUS status
                        ORDER BY description";

        $playerTypes = $wpdb->get_results($sql);
        return $playerTypes;
    }

    function vtm_listSTLinks() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, value, description, link
                        FROM " . $table_prefix . "ST_LINK stlinks
                        ORDER BY ordering";

        $stLinks = $wpdb->get_results($sql);
        return $stLinks;
    }
	
    function vtm_listPlayers($playerStatus, $playerType) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;

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

    function vtm_listClans() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CLAN
                        ORDER BY name";

        $clans = $wpdb->get_results($sql);
        return $clans;
    }

    function vtm_listGenerations() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, name
                FROM " . $table_prefix . "GENERATION
                ORDER BY BLOODPOOL, MAX_DISCIPLINE";

        $generations = $wpdb->get_results($sql);
        return $generations;
    }

    function vtm_listCharacterStatuses() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CHARACTER_STATUS
                        ORDER BY name";

        $characterStatuses = $wpdb->get_results($sql);
        return $characterStatuses;
    }

    function vtm_listCharacterTypes() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "CHARACTER_TYPE
                        ORDER BY name";

        $characterTypes = $wpdb->get_results($sql);
        return $characterTypes;
    }

    function vtm_listRoadsOrPaths() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "ROAD_OR_PATH
                        ORDER BY name";

        $roadsOrPaths = $wpdb->get_results($sql);
        return $roadsOrPaths;
    }

    function vtm_listDomains() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT ID, name
                        FROM " . $table_prefix . "DOMAIN
                        ORDER BY name";

        $domains = $wpdb->get_results($sql);
        return $domains;
    }

    function vtm_listOffices($showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;

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

    function vtm_listCharacters($group, $activeCharacter, $playerName, $activePlayer, $showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_listStats() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT id, name, grouping
                        FROM " . $table_prefix . "STAT
                        ORDER BY ordering";

        return $wpdb->get_results($sql);
    }

    function vtm_listXpReasons() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT id, name
                        FROM " . $table_prefix . "XP_REASON
                        ORDER BY id";

        return $wpdb->get_results($sql);
    }

    function vtm_listPathReasons() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT id, name
                    FROM " . $table_prefix . "PATH_REASON
                    ORDER BY id";

        return $wpdb->get_results($sql);
    }

    function vtm_listTemporaryStatReasons() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT id, name
                    FROM " . $table_prefix . "TEMPORARY_STAT_REASON
                    ORDER BY id";

        return $wpdb->get_results($sql);

    }

    function vtm_listSkills($group, $showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_listDisciplines($showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_listComboDisciplines($showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_listPaths($showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_listRituals($showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_listBackgrounds($group, $showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_listMerits($group, $showNotVisible) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_isST() {
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

    function vtm_establishCharacter($character) {
        if (vtm_isST()) {
            if (isset($_POST['VTM_CHARACTER'])) {
                $character = $_POST['VTM_CHARACTER'];
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

    function vtm_establishCharacterID($character) {
        global $wpdb;

        $sql = "SELECT id
                FROM " . VTM_TABLE_PREFIX . "CHARACTER
				WHERE WORDPRESS_ID = %s";
        $cid = $wpdb->get_var($wpdb->prepare($sql, $character));

        return $cid;
    }

    function vtm_establishPlayerID($character) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_establishXPReasonID($xpReasonString) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_establishPathReasonID($pathReasonString) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_establishTempStatID($tempStatString) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_establishTempStatReasonID($tempStatReasonString) {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
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

    function vtm_getConfig() {
        global $wpdb;
        $table_prefix = VTM_TABLE_PREFIX;
        $sql = "SELECT * FROM " . $table_prefix . "CONFIG";

        $configs = $wpdb->get_results($sql);
        foreach ($configs as $config) {
            return $config;
        }
    }

    function vtm_changeDisplayNameByID ($userID, $newDisplayName) {
        $args = array ('ID' => $userID, 'display_name' => $newDisplayName);
        wp_update_user($args);
        return true;
    }

    function vtm_changePasswordByID($userID, $newPassword1, $newPassword2) {

        if ($newPassword1 == $newPassword2) {
            wp_set_password($newPassword1, $userID);
            return true;
        }
        else {
            return false;
        }
    }

	function vtm_touch_last_updated($characterID) {
		global $wpdb;

		$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER",
				array ('LAST_UPDATED' => Date('Y-m-d')),
				array ('ID' => $characterID)
			);
	}
	
    function vtm_handleGVLarpForm() {
        switch($_POST['VTM_FORM']) {
            case "new_player":
                vtm_addNewPlayer($_POST['player_name'], $_POST['player_type'], $_POST['player_status']);
                break;
            case "player_xp":
                vtm_addPlayerXP($_POST['player'], $_POST['character'], $_POST['xp_type'], $_POST['xp_value'], $_POST['comment']);
                break;
            case "master_xp_update":
               vtm_handleMasterXP();
                break;
        }
    }

    if (isset($_POST['VTM_FORM'])) {
        vtm_handleGVLarpForm();
    }

    /*
            function vtm_numberToDots($base, $input) {
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