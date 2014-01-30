<?php


function get_shortcode_id($base) {
	static $shortcode_id;
	$shortcode_id++;
	return $base . "_" . $shortcode_id;
}

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

function get_profilelink($wordpressid, $character) {
	$markup = '<a href="@PROFILELINK@?CHARACTER=@WORDPRESS@" @EXTRA@>@NAME@</a>';

	return str_replace(
		Array('@PROFILELINK@','@WORDPRESS@','@EXTRA@','@NAME@'),
			Array(get_stlink_url('viewProfile'), urlencode($wordpressid), "",$character),
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
		$output = "<table class='gvplugin' id=\"" . get_shortcode_id("gvid_blb") . "\">\n";
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
		
		foreach ($result as $tablerow) {
			$col = 1;
			$output .= "<tr>";
			foreach ($columnlist as $name) {
				if ($name == 'character') $output .= "<td class='gvcol_$col gvcol_key'>" . get_profilelink($tablerow->wordpress_id, $tablerow->charname) . "</td>";
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
		$xp = get_total_xp($this->player_id, $characterID);
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
		$output = "<table class='gvplugin' id=\"" . get_shortcode_id("gvid_mlb") . "\">\n";
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
		
		//$config = getConfig();

		foreach ($result as $tablerow) {
			$col = 1;
			$output .= "<tr>";
			foreach ($columnlist as $name) {
				if ($name == 'character') $output .= "<td class='gvcol_$col gvcol_key'>" . get_profilelink($tablerow->wordpress_id, $tablerow->charname) . "</td>";
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

function print_character_xp_table($atts, $content=null) {
	extract(shortcode_atts(array ("character" => "null", "group" => "", "maxrecords" => "20"), $atts));
	
	if (!is_user_logged_in()) {
		return "You must be logged in to view this content";
	}
	
	$character   = establishCharacter($character);
	$characterID = establishCharacterID($character);
	$playerID    = establishPlayerID($character);

	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	
	$config = getConfig();
	$filteron = $config->ASSIGN_XP_BY_PLAYER == 'Y' ? "PLAYER_ID" : "CHARACTER_ID";
	$filterid = $config->ASSIGN_XP_BY_PLAYER == 'Y' ? $playerID   : $characterID;

	if ($group != "total" && $group != "TOTAL") {
		$sqlSpent = "SELECT 
					player.name as player_name,
					chara.name as char_name,
					xp_reason.name as reason_name,
					xp_spent.amount,
					xp_spent.comment,
					xp_spent.awarded
				FROM
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
					" . GVLARP_TABLE_PREFIX . "XP_REASON xp_reason,
					" . GVLARP_TABLE_PREFIX . "PLAYER_XP xp_spent,
					" . GVLARP_TABLE_PREFIX . "PLAYER player
				WHERE
					chara.ID = xp_spent.CHARACTER_ID
					AND player.ID = chara.PLAYER_ID
					AND xp_reason.ID = xp_spent.XP_REASON_ID
					AND chara.DELETED != 'Y'
					AND xp_spent.$filteron = %s";
					
		$sqlPending = "SELECT 
					player.name as player_name,
					chara.name as char_name,
					\"Pending\" as reason_name,
					pending.amount,
					pending.comment,
					pending.awarded
				FROM
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
					" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pending,
					" . GVLARP_TABLE_PREFIX . "PLAYER player
				WHERE
					player.ID = chara.PLAYER_ID
					AND pending.CHARACTER_ID = chara.ID
					AND pending.PLAYER_ID = player.ID
					AND chara.DELETED != 'Y'
					AND pending.$filteron = %s";
		
		$sql = "$sqlSpent
				UNION
				$sqlPending
				ORDER BY awarded, comment";
		$sql = $wpdb->prepare($sql, $filterid, $filterid);	
		$character_xp = $wpdb->get_results($sql);

		$output = "<table class='gvplugin' id=\"" . get_shortcode_id("cxpt") . "\">
						   <tr><th class=\"gvthead gvcol_1\">Character</th>
							   <th class=\"gvthead gvcol_2\">XP Reason</th>
							   <th class=\"gvthead gvcol_3\">XP Amount</th>
							   <th class=\"gvthead gvcol_4\">Comment</th>
							   <th class=\"gvthead gvcol_5\">Date of award</th></tr>\n";

		$arr = array();
		$i = 0;
		foreach ($character_xp as $current_xp) {
			$arr[$i] = "<tr><td class=\"gvcol_1 gvcol_key\">" . $current_xp->char_name   . "</td><td class=\"gvcol_2 gvcol_val\">"
				. $current_xp->reason_name . "</td><td class=\"gvcol_3 gvcol_val\">"
				. $current_xp->amount      . "</td><td class='gvcol_4 gvcol_val'>"
				. stripslashes($current_xp->comment)     . "</td><td class='gvcol_5 gvcol_val'>"
				. $current_xp->awarded     . "</td></tr>\n";
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

		$output .= "</table>";
		
	}
	else {

		$output = get_total_xp($playerID, $characterID);

	}

	return $output;
}
add_shortcode('character_xp_table', 'print_character_xp_table');


function print_map($atts, $content = null) {
	global $wpdb;

	$output = "";

	/* Attributes:
	//		map size
	//		show/hide map key */
	extract(shortcode_atts(array (
		"showmapkey"    => 1,
		"height"        => 250,
		"width"         => 400
		), $atts)
	);

	$apikey = get_option('feedingmap_google_api');
	$lat    = get_option('feedingmap_centre_lat', '55.862982');
	$long   = get_option('feedingmap_centre_long', '-4.242325');
	$zoom   = get_option('feedingmap_zoom', '8');
	$type   = get_option('feedingmap_map_type', 'ROADMAP');

	/* Get Domains */
	$sql = "SELECT domains.*, owners.FILL_COLOUR, owners.VISIBLE as SHOWOWNER
			FROM 
				" . GVLARP_TABLE_PREFIX . "MAPDOMAIN domains,
				" . GVLARP_TABLE_PREFIX . "MAPOWNER owners
			WHERE 
				owners.ID = domains.OWNER_ID
				AND domains.VISIBLE = 'Y'";
	$domains = $wpdb->get_results($sql);
	
	
	/* Define the LatLng coordinates for the polygon's path. */
	$output .= "<script type='text/javascript'><!--
	var infoWindow;
	function loadDomains(map) {
		infoWindow = new google.maps.InfoWindow({maxWidth: 200});

		var domains = {\n";
	$initial = '';
	foreach ($domains as $domain) {
		// d#:{id:#, fill:'#xxxxxx', coords:[...]}, ...
		$output .= sprintf('%1$sd%2$d:{id:%2$d,', $initial."\t\t\t", $domain->ID);
		if (empty($initial))
			$initial = ",\n";

		// Domain name & description
		$output .= sprintf("name:'%s',desc:'%s',", htmlentities($domain->NAME, ENT_QUOTES), htmlentities($domain->DESCRIPTION, ENT_QUOTES));

		// Polygon fill colour
		$output .= sprintf("fill:'%s',", $domain->SHOWOWNER == 'Y' ? htmlentities($domain->FILL_COLOUR, ENT_QUOTES) : '#FFFFFF');

		// Coordinate list
		$output .= 'coords:[';
		$coordlist = explode("\n", $domain->COORDINATES);
		foreach($coordlist as $key => $coord) {
			$latlong = explode(',', preg_replace('/\s+/','',$coord));
			if (is_numeric($latlong[0]) && is_numeric($latlong[1]))
				$coordlist[$key] = sprintf('[%s,%s]', $latlong[0], $latlong[1]);
			else
				unset($coordlist[$key]);
		}
		$output .= implode(',', $coordlist);
		$output .= "]";

		// d#:{ ... }
		$output .= '}';
	}
	$output .= "
		};
		for (var tag in domains) {
			var domain = domains[tag];

			// Convert the lat/long pairs to objects
			// Calculate the bounding box of the domain
			domain.bounds = null;
			for (var i = 0; i < domain.coords.length; i++) {
				var pair = domain.coords[i];
				domain.coords[i] = new google.maps.LatLng(pair[0], pair[1]);
				if (domain.bounds)
					domain.bounds.extend(domain.coords[i]);
				else
					domain.bounds = new google.maps.LatLngBounds(domain.coords[i]);
			}

			// Create and add the domain polygon to the map
			var poly = new google.maps.Polygon({
				paths: domain.coords,
				strokeColor: '#000000',
				strokeOpacity: 0.8,
				strokeWeight: 1,
				fillColor: domain.fill,
				fillOpacity: 0.35
			});
			poly.domain = domain;
			poly.setMap(map);
			domains[tag].polygon = poly;

			// Add a listener for the click event.
			domain.listener = google.maps.event.addListener(poly, 'click', function(event) {
				infoWindow.setPosition(this.domain.bounds.getCenter());
				infoWindow.setContent('<div><b>'+this.domain.name+'</b><br>'+this.domain.desc+'</div>');
				infoWindow.open(map);
			});
		}
	}
	--></script>\n";

	$output .= "<input type='hidden' name='feedingmap_apikey' id='feedingmap_apikeyID' value=\"$apikey\">\n";
	$output .= "<input type='hidden' name='feedingmap_clat'   id='feedingmap_clatID'   value=\"$lat\">\n";
	$output .= "<input type='hidden' name='feedingmap_clong'  id='feedingmap_clongID'  value=\"$long\">\n";
	$output .= "<input type='hidden' name='feedingmap_zoom'   id='feedingmap_zoomID'  value=\"$zoom\">\n";
	$output .= "<input type='hidden' name='feedingmap_type'   id='feedingmap_typeID'  value=\"$type\">\n";
	$output .= "<input type='button' name='Reload' value='Refresh' onclick=\"initialize()\">\n";
	$output .= "<p id=\"feedingmap_status\">Start</p>\n";
	$output .= "<div id=\"feedingmap\" style=\"height:{$height}px; width:{$width}px\">\n";
	$output .= "<div id=\"map-canvas\" style=\"width: 100%; height: 100%\"></div>\n";
	$output .= "</div>\n";

	/* Map Key */
	$sql = "SELECT * FROM " . GVLARP_TABLE_PREFIX . "MAPOWNER WHERE VISIBLE = 'Y'";
	$owners = $wpdb->get_results($sql);
    $output .= "<table class=\"feedingmapkey\">\n";
	$output .= "<tr><th colspan=2>Map Key</th></tr>\n";
	foreach ($owners as $owner) {
		$output .= "<tr><td>". stripslashes($owner->NAME) . "</td>";
		$output .= "<td style='background-color:" . $owner->FILL_COLOUR . ";width:10px'>&nbsp;</td></tr>\n";
	}
	$output .= "</table>\n\n";

	return $output;
}
add_shortcode('feeding_map', 'print_map');

function print_character_road_or_path_table($atts, $content=null) {
	extract(shortcode_atts(array ("character" => "null", "group" => "", "maxrecords" => "20"), $atts));
	
	if (!is_user_logged_in()) {
		return "You must be logged in to view this content";
	}
	
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
		$output .= "<table class='gvplugin' id=\"" . get_shortcode_id("gvid_crpt") . "\">
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

function print_character_details($atts, $content=null) {
	extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
	
	if (!is_user_logged_in()) {
		return "You must be logged in to view this content";
	}

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
						   domains.name domain,
						   path.name path_name,
						   path_totals.path_value,
						   chara.ID 
					FROM " . $table_prefix . "CHARACTER chara,
						 " . $table_prefix . "CLAN pub_clan,
						 " . $table_prefix . "CLAN priv_clan,
						 " . $table_prefix . "GENERATION gen,
						 " . $table_prefix . "CHARACTER_STATUS status,
						 " . $table_prefix . "DOMAIN domains,
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
					  AND chara.domain_id           = domains.id
					  AND chara.road_or_path_id     = path.id
					  AND chara.id                  = path_totals.character_id";

	$character_details = $wpdb->get_row($wpdb->prepare($sql, $character));

	$config = getConfig();
	
	if ($config->USE_NATURE_DEMEANOUR == 'Y') {
			
		$sql = "SELECT 
					natures.name as nature,
					demeanours.name as demeanour
				FROM
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
					" . GVLARP_TABLE_PREFIX . "NATURE natures,
					" . GVLARP_TABLE_PREFIX . "NATURE demeanours
				WHERE
					chara.NATURE_ID = natures.ID
					AND chara.DEMEANOUR_ID = demeanours.ID
					AND chara.ID = %s";
		$result = $wpdb->get_row($wpdb->prepare($sql, $character_details->ID));
	
		$character_details->nature   = $result->nature;
		$character_details->demeanour = $result->demeanour;
		
	}

	
	if ($group == "") {
		$output  = "<table class='gvplugin' id=\"" . get_shortcode_id("gvid_cdb") . "\"><tr><td class=\"gvcol_1 gvcol_key\">Character_name</td><td class=\"gvcol_2 gvcol_val\">" . $character_details->char_name       . "</td></tr>";
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
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Current Domain</td><td class=\"gvcol_2 gvcol_val\">"        . $character_details->domain           . "</td></tr>";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Road or Path name</td><td class=\"gvcol_2 gvcol_val\">"     . $character_details->path_name       . "</td></tr>";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Road or Path rating</td><td class=\"gvcol_2 gvcol_val\">"   . $character_details->path_value      . "</td></tr>";
		
		if ($config->USE_NATURE_DEMEANOUR == 'Y') {
			
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Nature</td><td class=\"gvcol_2 gvcol_val\">" . $character_details->nature      . "</td></tr>";
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Demeanour</td><td class=\"gvcol_2 gvcol_val\">" . $character_details->demeanour      . "</td></tr>";
		
		}
		
		$output .= "</table>";
	}
	else {
		$output = "<span class=\"gvcol_val\" id=\"gvid_cdeb_" . $group . "\">" . $character_details->$group . "</span>";
	}

	return $output;
}
add_shortcode('character_detail_block', 'print_character_details');

function print_character_offices($atts, $content=null) {
	extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
	$character = establishCharacter($character);
	
	if (!is_user_logged_in()) {
		return "You must be logged in to view this content";
	}

	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	$output    = "";
	$sqlOutput = "";
	$sql = "SELECT office.name office_name, domain.name domain_name, coffice.comment
					FROM " . $table_prefix . "CHARACTER_OFFICE coffice,
						 " . $table_prefix . "OFFICE office,
						 " . $table_prefix . "DOMAIN domain,
						 " . $table_prefix . "CHARACTER chara
					WHERE coffice.OFFICE_ID    = office.ID
					  AND coffice.CHARACTER_ID = chara.ID
					  AND coffice.DOMAIN_ID     = domain.ID
					  AND chara.DELETED != 'Y'
					  AND chara.WORDPRESS_ID = %s
				   ORDER BY office.ordering, office.name, domain.name";

	$character_offices = $wpdb->get_results($wpdb->prepare($sql, $character));

	foreach ($character_offices as $current_office) {
		$sqlOutput .="<tr><td class=\"gvcol_1 gvcol_key\">"  . $current_office->office_name . "</td>
								  <td class=\"gvcol_2 gvcol_val\">"  . $current_office->domain_name  . "</td>
								  <td class=\"gvcol_3 gvcol_spec\">" . stripslashes($current_office->comment)     . "</td></tr>";
	}

	if ($sqlOutput != "") {
		$output = "<table class='gvplugin' id=\"" . get_shortcode_id("cxpt") . "\" >" . $sqlOutput . "</table>";
	}
	else {
		$output = "";
	}

	return $output;
}
add_shortcode('character_offices_block', 'print_character_offices');

function print_character_temp_stats($atts, $content=null) {
	extract(shortcode_atts(array ("character" => "null", "stat" => "Willpower"), $atts));
	$character = establishCharacter($character);

	if (!is_user_logged_in()) {
		return "You must be logged in to view this content";
	}
	
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
			$output = "<span id=\"" . get_shortcode_id("gvid_ctw_willpower") . "\" class=\"gvcol_val\">" . $sqlOutput . "</span>";
		}
		else if ($stat == "Blood") {
			$output = "<span id=\"" . get_shortcode_id("gvid_ctw_bloodpool") . "\" class=\"gvcol_val\">" . $sqlOutput . "</span>";
		}
	}
	else {
		$output = "";
	}

	return $output;
}
add_shortcode('character_temp_stats', 'print_character_temp_stats');

function print_office_block($atts, $content=null) {
	extract(shortcode_atts(array ("domain" => "Glasgow", "office" => ""), $atts));

	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	$output    = "";
	$sqlOutput = "";

	$sql = "SELECT chara.name charname, office.name oname, domain.name domainname, office.ordering, coffice.comment
					FROM " . $table_prefix . "CHARACTER chara,
						 " . $table_prefix . "CHARACTER_OFFICE coffice,
						 " . $table_prefix . "OFFICE office,
						 " . $table_prefix . "DOMAIN domain
					WHERE coffice.character_id = chara.id
					  AND coffice.office_id    = office.id
					  AND coffice.domain_id     = domain.id
					  AND chara.deleted        = 'N'
					  AND domain.name = %s ";
	if (!isSt()) {
		$sql .= " AND office.visible = 'Y' AND chara.visible = 'Y' ";
	}
	if ($office != null && $office != "") {
		$sql .= " AND office.name = %s ";
	}
	$sql .= "ORDER BY domainname, office.ordering, charname";

	if ($office != null && $office != "") {
		$characterOffices = $wpdb->get_results($wpdb->prepare($sql, $domain, $office));
	}
	else {
		$characterOffices = $wpdb->get_results($wpdb->prepare($sql, $domain));
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
			$output = "<table class='gvplugin' id=\"" . get_shortcode_id("gvid_cob") . "\">" . $sqlOutput . "</table>";
		}
		else {
			$output = "No office holders found for the domain of " . $domain;
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
			$output = "No current holder of " . $office . " in " . $domain . " found.";
		}
	}
	return $output;
}
add_shortcode('office_block', 'print_office_block');



?>