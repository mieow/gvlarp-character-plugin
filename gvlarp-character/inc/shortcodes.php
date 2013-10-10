<?php
function get_homecourt() {

	global $wpdb;
	
	$config = getConfig();

	$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX . "COURT
			WHERE ID = %s;";
	$list = $wpdb->get_results($wpdb->prepare($sql, $config->HOME_COURT_ID));
	
	return $list[0]->NAME;
}
function  get_loggedinclan($characterID) {
	global $wpdb;

	$sql = "SELECT pubclan.name as pub, privclan.name as priv
			FROM 
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "CLAN pubclan,
				" . GVLARP_TABLE_PREFIX . "CLAN privclan
			WHERE 
				chara.ID = %s
				AND chara.PUBLIC_CLAN_ID = pubclan.ID
				AND chara.PRIVATE_CLAN_ID = privclan.ID";
	$result = $wpdb->get_results($wpdb->prepare($sql, $characterID));
	
	return $result;
}
function  get_loggedincourt($characterID) {
	global $wpdb;

	$sql = "SELECT courts.name as court
			FROM 
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "COURT courts
			WHERE 
				chara.ID = %s
				AND courts.ID = chara.COURT_ID";
	$result = $wpdb->get_results($wpdb->prepare($sql, $characterID));
	
	return $result[0]->court;
}

function get_profilelink($profilelink, $wordpressid, $character) {
	$markup = '<a href="@PROFILELINK@?CHARACTER=@WORDPRESS@" @EXTRA@>@NAME@</a>';

	return str_replace(
		Array('@PROFILELINK@','@WORDPRESS@','@EXTRA@','@NAME@'),
			Array($profilelink, urlencode($wordpressid), "",$character),
			$markup
		);
}

function print_background_shortcode($atts, $content = null) {
	global $wpdb;
	
	$output = "";

	extract(shortcode_atts(array (
		"character" => "null",
		"background" => "Status",
		"matchtype"  => "",
		"match"      => "",
		"court"      => "home",
		"liststatus" => "Alive",
		"level"      => "all",
		"columns"    => "level,character,player,clan,court,background,sector,comment,level"
		), $atts)
	);
	
	/* Match comment in background to: 
		sector					- matchtype = sector
		comment (e.g. sector)	- matchtype = comment
		
		match = <value> or loggedinclan
		
		court = "" or <value> or loggedin or home
		
		level = "all" or "displayzeros" or <number>
	*/

	$character = establishCharacter($character);
	$characterID = establishCharacterID($character);
		
	$sqlmain = "SELECT chara.id,
				chara.wordpress_id,
				chara.name as charname,
				pubclan.name as publicclan,
				privclan.name as privateclan,
				player.name as playername,
				pstatus.name as pstat,
				cstatus.name as cstat,
				background.name as bgname,
				char_bg.level as level,
				char_bg.comment as comment,
				courts.name as court,
				sector.name as sectorname
			FROM
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "PLAYER player,
				" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS cstatus,
				" . GVLARP_TABLE_PREFIX . "CLAN pubclan,
				" . GVLARP_TABLE_PREFIX . "CLAN privclan,
				" . GVLARP_TABLE_PREFIX . "BACKGROUND background,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND char_bg
				LEFT JOIN
					" . GVLARP_TABLE_PREFIX . "SECTOR sector
				ON
					char_bg.SECTOR_ID = sector.ID
				,
				" . GVLARP_TABLE_PREFIX . "COURT courts
			WHERE
				chara.PLAYER_ID = player.ID
				AND chara.ID = char_bg.CHARACTER_ID
                AND player.PLAYER_STATUS_ID = pstatus.ID
				AND pstatus.NAME = 'Active'
				AND chara.CHARACTER_STATUS_ID = cstatus.ID
				AND chara.PUBLIC_CLAN_ID = pubclan.ID
				AND chara.PRIVATE_CLAN_ID = privclan.ID
				AND background.ID = char_bg.BACKGROUND_ID
				AND courts.ID = chara.COURT_ID
				AND chara.VISIBLE = 'Y'
				AND chara.DELETED = 'N'
				AND background.name = %s";
	$sqlmainargs = array($background);
	
	$sqlzero = "SELECT chara.id,
				chara.wordpress_id,
				chara.name as charname,
				pubclan.name as publicclan,
				privclan.name as privateclan,
				player.name as playername,
				pstatus.name as pstat,
				cstatus.name as cstat,
				%s as bgname,
				0 as level,
				\"\" as comment,
				courts.name as court,
				\"\" as sectorname
			FROM
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara
				LEFT JOIN
					(SELECT char_bgs.ID, background.NAME, char_bgs.CHARACTER_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND char_bgs,
						" . GVLARP_TABLE_PREFIX . "BACKGROUND background
					WHERE
						background.ID = char_bgs.BACKGROUND_ID
						AND background.name = %s
					) char_bg
				ON
					char_bg.CHARACTER_ID = chara.ID
				,
				" . GVLARP_TABLE_PREFIX . "PLAYER player,
				" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS cstatus,
				" . GVLARP_TABLE_PREFIX . "CLAN pubclan,
				" . GVLARP_TABLE_PREFIX . "CLAN privclan,
				" . GVLARP_TABLE_PREFIX . "COURT courts
			WHERE
				chara.PLAYER_ID = player.ID
                AND player.PLAYER_STATUS_ID = pstatus.ID
				AND pstatus.NAME = 'Active'
				AND chara.CHARACTER_STATUS_ID = cstatus.ID
				AND chara.PUBLIC_CLAN_ID = pubclan.ID
				AND chara.PRIVATE_CLAN_ID = privclan.ID
				AND courts.ID = chara.COURT_ID
				AND chara.VISIBLE = 'Y'
				AND chara.DELETED = 'N'
				AND ISNULL(char_bg.ID)
				";
	$sqlzeroargs = array($background, $background);
	
	if ($liststatus) {
		$list = explode(',',$liststatus);
		$sqlfilter = " AND ( cstatus.name = '";
		$sqlfilter .= implode("' OR cstatus.name = '",$list);
		$sqlfilter .= "')";
		
		$sqlmain .= $sqlfilter;
		$sqlzero .= $sqlfilter;
	}
	
	if ($matchtype == 'comment') {
		if ($match == 'loggedinclan') {
			$clans = get_loggedinclan($characterID);
			$sqlmain .= " AND (char_bg.comment = %s OR char_bg.comment = %s)";
			array_push($sqlmainargs, $clans[0]->priv, $clans[0]->pub);
		} else {
			$sqlmain .= " AND char_bg.comment = %s";
			array_push($sqlmainargs, $match);
		}
	}
	if ($matchtype == 'sector') {
		$sqlmain .= " AND sector.NAME = %s";
		array_push($sqlmainargs, $match);
	}
	
	if ($court) {
		$sqlfilter = " AND courts.name = %s";
		$sqlmain .= $sqlfilter;
		$sqlzero .= $sqlfilter;
		
		if ($court == 'loggedin')
			$court = get_loggedincourt($characterID);
		if ($court == 'home')
			$court = get_homecourt();
		
		array_push($sqlmainargs, $court);
		array_push($sqlzeroargs, $court);
	}
	
	if ($level != "all" && $level != 'displayzeros') {
		$sqlmain .= " AND char_bg.level = %s";
		array_push($sqlmainargs, $level);
	}
	
	$sql = $sqlmain;
	$sqlargs = $sqlmainargs;
	if ($level == 'displayzeros') {
		$sql .= "UNION $sqlzero";
		$sqlargs = array_merge($sqlmainargs, $sqlzeroargs);
	}
	elseif (!$level) {
		$sql = $sqlzero;
		$sqlargs = $sqlzeroargs;
	}
	
	$sql .= " ORDER BY level DESC, charname";

	$sql = $wpdb->prepare($sql, $sqlargs);
	$result = $wpdb->get_results($sql);
	
	//echo "<p>SQL: $sql<p>";
	//print_r($result);
	
	if (count($result)) {
		$columnlist = explode(',',$columns);
		$output = "<table class='gvplugin' id=\"gvid_slb\">\n<tr>";
		foreach ($columnlist as $name) {
			if ($name == 'character') $output .= "<th>Character</th>";
			if ($name == 'player') $output .= "<th>Player</th>";
			if ($name == 'clan')   $output .= "<th>Clan</th>";
			if ($name == 'status') $output .= "<th>Character Status</th>";
			if ($name == 'court')  $output .= "<th>Court</th>";
			if ($name == 'background')   $output .= "<th>Background</th>";
			if ($name == 'comment')   $output .= "<th>Comment</th>";
			if ($name == 'sector')   $output .= "<th>Sector</th>";
			if ($name == 'level')  $output .= "<th>Level</th>";
		}
		$output .= "</tr>\n";
		
		$config = getConfig();

		foreach ($result as $tablerow) {
			$col = 1;
			$output .= "<tr>";
			foreach ($columnlist as $name) {
				if ($name == 'character') $output .= "<td class='gvcol_$col gvcol_key'>" . get_profilelink($config->PROFILE_LINK, $tablerow->wordpress_id, $tablerow->charname) . "</td>";
				if ($name == 'player') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->playername}</td>";
				if ($name == 'clan')   $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->publicclan}</td>";
				if ($name == 'status') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->cstat}</td>";
				if ($name == 'court')  $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->court}</td>";
				if ($name == 'background') $output .= "<td class='gvcol_$col gvcol_val'>$background</td>";
				if ($name == 'comment') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->comment}</td>";
				if ($name == 'sector') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->sectorname}</td>";
				if ($name == 'level')  $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->level}</td>";
				$col++;
			}
			$output .= "</tr>\n";
		}
		
		$output .= "</table>";
	} else {
		$output = "<p>No characters with the matching $background background</p>";
	}
	
	return $output;
}
add_shortcode('background_table', 'print_background_shortcode');


add_shortcode('integration_alo_easymail', 'get_character_from_email');
function get_character_from_email ($email, $setting = 'name') {
	global $wpdb;

	$sqlCharID = "SELECT chara.id, chara.name, paths.name as pathname, chara.player_id
			FROM	
				" . $wpdb->prefix . "GVLARP_CHARACTER chara,
				" . $wpdb->prefix . "users wpusers,
				" . $wpdb->prefix . "GVLARP_ROAD_OR_PATH paths
			WHERE
				wpusers.user_email = %s
				AND chara.WORDPRESS_ID = wpusers.user_login
				AND chara.DELETED = 'N'
				AND chara.VISIBLE = 'Y'
				AND paths.ID = chara.ROAD_OR_PATH_ID
			ORDER BY chara.CHARACTER_STATUS_ID ASC, chara.LAST_UPDATED DESC, chara.name
			LIMIT 1";
	$result = $wpdb->get_results($wpdb->prepare($sqlCharID, $email));
	$id   = $result[0]->id;
	$name = $result[0]->name;
	$path = $result[0]->pathname;
	$playerid = $result[0]->player_id;
		
	if ($setting == 'xptotal') {
		$sql = "SELECT SUM(xpspends.amount) as total
				FROM
					" . $wpdb->prefix . "GVLARP_PLAYER_XP as xpspends
				WHERE
					xpspends.PLAYER_ID = '%s'";
		$result = $wpdb->get_results($wpdb->prepare($sql, $playerid));
		$xp = $result[0]->total;
	}
	
	if ($setting == 'rating') {

		$sql = "SELECT
					SUM(cha_path.AMOUNT) as rating
				FROM
					" . $wpdb->prefix . "GVLARP_CHARACTER_ROAD_OR_PATH cha_path
				WHERE
					cha_path.CHARACTER_ID = %s";
		$result = $wpdb->get_results($wpdb->prepare($sql, $id));
		$rating = $result[0]->rating;
		
	}

	switch ($setting) {
		case 'name':    $output = $name; break;
		case 'path':    $output = $path; break;
		case 'rating':  $output = $rating; break;
		case 'xptotal': $output = $xp; break;
		default:
			$output = $setting;
	}
		
	return $output;

}



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

	
	
?>