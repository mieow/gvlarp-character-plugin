<?php
function get_homedomain() {

	global $wpdb;
	
	$config = getConfig();

	$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX . "DOMAIN
			WHERE ID = %s;";
	$list = $wpdb->get_results($wpdb->prepare($sql, $config->HOME_DOMAIN_ID));
	
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
function  get_loggedindomain($characterID) {
	global $wpdb;

	$sql = "SELECT domains.name as domain
			FROM 
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "DOMAIN domains
			WHERE 
				chara.ID = %s
				AND domains.ID = chara.DOMAIN_ID";
	$result = $wpdb->get_results($wpdb->prepare($sql, $characterID));
	
	return $result[0]->domain;
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
		"domain"     => "home",
		"liststatus" => "Alive",
		"level"      => "all",
		"columns"    => "level,character,player,clan,domain,background,sector,comment,level",
		"heading"    => 1
		), $atts)
	);
	
	/* Match comment in background to: 
		sector					- matchtype = sector
		comment (e.g. sector)	- matchtype = comment
		
		match = <value> or loggedinclan
		
		domain = "" or <value> or loggedin or home
		
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
				domains.name as domain,
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
				" . GVLARP_TABLE_PREFIX . "DOMAIN domains
			WHERE
				chara.PLAYER_ID = player.ID
				AND chara.ID = char_bg.CHARACTER_ID
                AND player.PLAYER_STATUS_ID = pstatus.ID
				AND pstatus.NAME = 'Active'
				AND chara.CHARACTER_STATUS_ID = cstatus.ID
				AND chara.PUBLIC_CLAN_ID = pubclan.ID
				AND chara.PRIVATE_CLAN_ID = privclan.ID
				AND background.ID = char_bg.BACKGROUND_ID
				AND domains.ID = chara.DOMAIN_ID
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
				domains.name as domain,
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
				" . GVLARP_TABLE_PREFIX . "DOMAIN domains
			WHERE
				chara.PLAYER_ID = player.ID
                AND player.PLAYER_STATUS_ID = pstatus.ID
				AND pstatus.NAME = 'Active'
				AND chara.CHARACTER_STATUS_ID = cstatus.ID
				AND chara.PUBLIC_CLAN_ID = pubclan.ID
				AND chara.PRIVATE_CLAN_ID = privclan.ID
				AND domains.ID = chara.DOMAIN_ID
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
	
	if ($domain) {
		$sqlfilter = " AND domains.name = %s";
		$sqlmain .= $sqlfilter;
		$sqlzero .= $sqlfilter;
		
		if ($domain == 'loggedin')
			$domain = get_loggedindomain($characterID);
		if ($domain == 'home')
			$domain = get_homedomain();
		
		array_push($sqlmainargs, $domain);
		array_push($sqlzeroargs, $domain);
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
		$output = "<table class='gvplugin' id=\"gvid_blb\">\n";
		if ($heading) {
			$output .= "<tr>";
			foreach ($columnlist as $name) {
				if ($name == 'character') $output .= "<th>Character</th>";
				if ($name == 'player') $output .= "<th>Player</th>";
				if ($name == 'clan')   $output .= "<th>Clan</th>";
				if ($name == 'status') $output .= "<th>Character Status</th>";
				if ($name == 'domain')  $output .= "<th>Domain</th>";
				if ($name == 'background')   $output .= "<th>Background</th>";
				if ($name == 'comment')   $output .= "<th>Comment</th>";
				if ($name == 'sector')   $output .= "<th>Sector</th>";
				if ($name == 'level')  $output .= "<th>Level</th>";
			}
			$output .= "</tr>\n";
		}
		
		$config = getConfig();

		foreach ($result as $tablerow) {
			$col = 1;
			$output .= "<tr>";
			foreach ($columnlist as $name) {
				if ($name == 'character') $output .= "<td class='gvcol_$col gvcol_key'>" . get_profilelink($config->PROFILE_LINK, $tablerow->wordpress_id, $tablerow->charname) . "</td>";
				if ($name == 'player') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->playername}</td>";
				if ($name == 'clan')   $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->publicclan}</td>";
				if ($name == 'status') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->cstat}</td>";
				if ($name == 'domain')  $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->domain}</td>";
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

function print_merit_shortcode($atts, $content = null) {
	global $wpdb;
	
	$output = "";

	extract(shortcode_atts(array (
		"character" => "null",
		"merit"      => "Clan Friendship",
		"matchtype"  => "",
		"match"      => "",
		"domain"     => "home",
		"liststatus" => "Alive",
		"columns"    => "character,player,clan,domain,merit,comment,level",
		"heading"    => 1
		), $atts)
	);
	
	/* 
		match = <value> or loggedinclan
		
		domain = "" or <value> or loggedin or home
		
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
				merit.name as meritname,
				char_merit.level as level,
				char_merit.comment as comment,
				domains.name as domain
			FROM
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "PLAYER player,
				" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS cstatus,
				" . GVLARP_TABLE_PREFIX . "CLAN pubclan,
				" . GVLARP_TABLE_PREFIX . "CLAN privclan,
				" . GVLARP_TABLE_PREFIX . "MERIT merit,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT char_merit,
				" . GVLARP_TABLE_PREFIX . "DOMAIN domains
			WHERE
				chara.PLAYER_ID = player.ID
				AND chara.ID = char_merit.CHARACTER_ID
                AND player.PLAYER_STATUS_ID = pstatus.ID
				AND pstatus.NAME = 'Active'
				AND chara.CHARACTER_STATUS_ID = cstatus.ID
				AND chara.PUBLIC_CLAN_ID = pubclan.ID
				AND chara.PRIVATE_CLAN_ID = privclan.ID
				AND merit.ID = char_merit.MERIT_ID
				AND domains.ID = chara.DOMAIN_ID
				AND chara.VISIBLE = 'Y'
				AND chara.DELETED = 'N'
				AND merit.name = %s";
	$sqlmainargs = array($merit);
	
	if ($liststatus) {
		$list = explode(',',$liststatus);
		$sqlfilter = " AND ( cstatus.name = '";
		$sqlfilter .= implode("' OR cstatus.name = '",$list);
		$sqlfilter .= "')";
		
		$sqlmain .= $sqlfilter;
	}
	
	if ($match) {
		if ($match == 'loggedinclan') {
			$clans = get_loggedinclan($characterID);
			$sqlmain .= " AND (char_merit.comment = %s OR char_merit.comment = %s)";
			array_push($sqlmainargs, $clans[0]->priv, $clans[0]->pub);
		} else {
			$sqlmain .= " AND char_merit.comment = %s";
			array_push($sqlmainargs, $match);
		}
	}
	
	if ($domain) {
		$sqlfilter = " AND domains.name = %s";
		$sqlmain .= $sqlfilter;
		
		if ($domain == 'loggedin')
			$domain = get_loggedindomain($characterID);
		if ($domain == 'home')
			$domain = get_homedomain();
		
		array_push($sqlmainargs, $domain);
	}
		
	$sql = $sqlmain;
	$sqlargs = $sqlmainargs;
	
	$sql .= " ORDER BY level DESC, charname";

	$sql = $wpdb->prepare($sql, $sqlargs);
	$result = $wpdb->get_results($sql);
	
	//echo "<p>SQL: $sql<p>";
	//print_r($result);
	
	if (count($result)) {
		$columnlist = explode(',',$columns);
		$output = "<table class='gvplugin' id=\"gvid_mlb\">\n";
		if ($heading) {
			$output .= "<tr>";
			foreach ($columnlist as $name) {
				if ($name == 'character') $output .= "<th>Character</th>";
				if ($name == 'player') $output .= "<th>Player</th>";
				if ($name == 'clan')   $output .= "<th>Clan</th>";
				if ($name == 'status') $output .= "<th>Character Status</th>";
				if ($name == 'domain')  $output .= "<th>Domain</th>";
				if ($name == 'merit')   $output .= "<th>Merit</th>";
				if ($name == 'comment')   $output .= "<th>Comment</th>";
				if ($name == 'level')  $output .= "<th>Level</th>";
			}
			$output .= "</tr>\n";
		}
		
		$config = getConfig();

		foreach ($result as $tablerow) {
			$col = 1;
			$output .= "<tr>";
			foreach ($columnlist as $name) {
				if ($name == 'character') $output .= "<td class='gvcol_$col gvcol_key'>" . get_profilelink($config->PROFILE_LINK, $tablerow->wordpress_id, $tablerow->charname) . "</td>";
				if ($name == 'player') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->playername}</td>";
				if ($name == 'clan')   $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->publicclan}</td>";
				if ($name == 'status') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->cstat}</td>";
				if ($name == 'domain')  $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->domain}</td>";
				if ($name == 'merit') $output .= "<td class='gvcol_$col gvcol_val'>$merit</td>";
				if ($name == 'comment') $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->comment}</td>";
				if ($name == 'level')  $output .= "<td class='gvcol_$col gvcol_val'>{$tablerow->level}</td>";
				$col++;
			}
			$output .= "</tr>\n";
		}
		
		$output .= "</table>";
	} else {
		$output = "<p>No characters with the matching merit '$merit'</p>";
	}
	
	return $output;
}
add_shortcode('merit_table', 'print_merit_shortcode');

	
?>