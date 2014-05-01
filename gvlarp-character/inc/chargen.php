<?php

function vtm_default_chargen_settings() {

	return array(
		'attributes-method'    => "PST",
		'attributes-primary'   => 7,
		'attributes-secondary' => 5,
		'attributes-tertiary'  => 3,
		'attributes-points'    => 0,
		'abilities-primary'    => 13,
		'abilities-secondary'  => 9,
		'abilities-tertiary'   => 5,
		'abilities-max'        => 3,
		'disciplines-points'   => 3,
		'backgrounds-points'   => 5,
		'virtues-points'       => 7,
		'road-multiplier'      => 1,
		'merits-max'           => 7,
		'flaws-max'            => 7,
		'freebies-points'      => 15,
	);

}

function vtm_chargen_flow_steps($characterID) {

	$xp = vtm_get_total_xp(0, $characterID);
	
	$buttons = array (
		array(	'title'      => "Basic Information", 
				'function'   => 'vtm_render_basic_info',
				'validate'   => 'vtm_validate_basic_info',
				'save'       => 'vtm_save_basic_info'),
		array(	'title' => "Attributes", 
				'function'   => 'vtm_render_attributes',
				'validate'   => 'vtm_validate_attributes',
				'save'       => 'vtm_save_attributes'),
		array(	'title' => "Abilities", 
				'function'   => 'vtm_render_abilities',
				'validate'   => 'vtm_validate_abilities',
				'save'       => 'vtm_save_abilities'),
		array(	'title' => "Disciplines", 
				'function'   => 'vtm_render_chargen_disciplines',
				'validate'   => 'vtm_validate_disciplines',
				'save'       => 'vtm_save_disciplines'),
		array(	'title' => "Backgrounds", 
				'function'   => 'vtm_render_chargen_backgrounds',
				'validate'   => 'vtm_validate_backgrounds',
				'save'       => 'vtm_save_backgrounds'),
		array(	'title' => "Virtues", 
				'function'   => 'vtm_render_chargen_virtues',
				'validate'   => 'vtm_validate_virtues',
				'save'       => 'vtm_save_virtues'),
		array(	'title' => "Freebie Points", 
				'function'   => 'vtm_render_chargen_freebies',
				'validate'   => 'vtm_validate_freebies',
				'save'       => 'vtm_save_freebies'),
	);
	if ($xp > 0) {
		//echo "<li>xp = $xp</li>";
		array_push($buttons, array(
				'title' => "Spend Experience", 
				'function'   => 'vtm_render_chargen_xp',
				'validate'   => 'vtm_validate_xp',
				'save'       => 'vtm_save_xp'));
	}
	
	array_push($buttons,array(
				'title' => "Finishing Touches", 
				'function'   => 'vtm_render_finishing',
				'validate'   => 'vtm_validate_finishing',
				'save'       => 'vtm_save_finish'));
	array_push($buttons,array(
				'title' => "Extended Backgrounds", 
				'function'   => '',
				'validate'   => '',
				'save'       => ''));

	return $buttons;
}

function vtm_chargen_content_filter($content) {


	if (is_page(vtm_get_stlink_page('viewCharGen'))) {
		$mustbeloggedin = get_option('vtm_chargen_mustbeloggedin', '1') ? true : false;
		if (!$mustbeloggedin || (is_user_logged_in() && $mustbeloggedin))
			$content .= vtm_get_chargen_content();
		else
			$content .= "<p>You must be logged in to generate a character</p>";
	}
	return $content;
}

add_filter( 'the_content', 'vtm_chargen_content_filter' );


function vtm_get_chargen_content() {
	global $wpdb;

	$output = "";
	print_r($_POST);
	
	$characterID = vtm_get_chargen_characterID();
	$laststep    = isset($_POST['step']) ? $_POST['step'] : 0;
	$progress    = isset($_POST['progress']) ? $_POST['progress'] : array('0' => 1);
	$templateID  = vtm_get_templateid($characterID);
	
	if ($characterID == -1) {
		$output .= "<p>Invalid Reference</p>";
		$step = 0;
	} else {
		$step = vtm_get_step($characterID);
	}
	
	$output .= "<form id='chargen_form' method='post'>";
	
	// validate & save data from last step
	$dataok = vtm_validate_chargen($laststep, $templateID, $characterID);
	if ($dataok) {
		$characterID = vtm_save_progress($laststep, $characterID, $templateID);
		$progress[$laststep] = 1;
		
	} else {
		$step = $laststep;
		$progress[$laststep] = 0;
	}

	// setup progress
	for ($i = 0 ; $i <= 10 ; $i++) {
		$val = isset($progress[$i]) ? $progress[$i] : 0;
		$output .= "<input type='hidden' name='progress[$i]' value='$val' />\n";
	}
	
	// output flow buttons
	$output .= vtm_render_flow($step, $characterID, $progress, $templateID);
	$flow = vtm_chargen_flow_steps($characterID);
	
	$output .= "<div id='chargen-main'>";
	
	// output form to be filled in
	//echo "<li>step: $step, function: {$flow[$step-1]['function']}</li>";
	if ($step == 0)
		$output .= vtm_render_choose_template();
	else
		$output .= call_user_func($flow[$step-1]['function'], $step, $characterID, $templateID);

	// 3 buttons: Back, Check & Next
	$output .= vtm_render_submit($step);
	$output .= "</div></form>";
	
	return $output;
}

function vtm_render_submit($step) {

	$output = "";
	
	if ($step - 1 > 0)
		$output .= "<input type='submit' name='chargen-step[" . ($step - 1) . "]' class='button-chargen-step' value='< Step " . ($step - 1) . "' />";
	if ($step > 1)
		$output .= "<input type='submit' name='chargen-step[" . $step . "]' class='button-chargen-step' value='Update' />";
	if ($step + 1 <= 10)
		$output .= "<input type='submit' name='chargen-step[" . ($step + 1) . "]' class='button-chargen-step' value='Next >' />";
	else
		$output .= "<input type='submit' name='chargen-submit' class='button-chargen-step' value='Submit for Approval' />";

	return $output;
}


function vtm_get_step() {

	$step = 0;
	
	// output step based on what button has been pressed
	if (isset($_POST['chargen-step'])) {
		$buttons = array_keys($_POST['chargen-step']);
		$step = $buttons[0];
	}
		
	return $step;
}

function vtm_render_flow($step, $characterID, $progress, $templateID) {

	$output = "";
	
	$xp = vtm_get_total_xp(0, $characterID);
	
	$buttons = vtm_chargen_flow_steps($characterID);
	
	$output .= "<div id='vtm-chargen-flow'>\n";	
	$output .= "<input type='hidden' name='selected_template' value='$templateID' />\n";
	$output .= "<input type='hidden' name='characterID' value='$characterID' />\n";
	$output .= "<input type='hidden' name='step' value='$step' />\n";
	
	if ($step > 0) {
		$output .= "<ul>\n";
		$i = 0;
		foreach ($buttons as $stepinfo) {
			$stepid = $i + 1;
			$steptitle  = $stepinfo['title'];
			$dependancy = 1; //$stepinfo['dependency'];
			if ($step == $stepid) {
				$output .= "<li class='step-button step-selected'><span><strong>Step $stepid:</strong> $steptitle</span></li>";
			} 
			elseif (isset($progress[$dependancy]) && $progress[$dependancy]) {
				$output .= "<li class='step-button step-enable'><input type='submit' name='chargen-step[$stepid]' class='button-chargen-step' value='Step $stepid: $steptitle' /></li>\n";
			}
			else {
				$output .= "<li class='step-button step-disable'><span><strong>Step $stepid:</strong> $steptitle</span></li>\n";
			}
			$i++;
		}
		$output .= "</ul>\n";
	}
	$output .= "</ul></div>\n";

	return $output;

}

function vtm_render_basic_info($step, $characterID, $templateID) {
	global $current_user;
	global $wpdb;

	$output = "";
	
	$mustbeloggedin = get_option( 'vtm_chargen_mustbeloggedin' );
	$chargenacct    = get_option( 'vtm_chargen_wpaccount' );
	$clans          = vtm_get_clans();
	$natures        = vtm_get_natures();
	$config         = vtm_getConfig();
	
	$characterID = $characterID ? $characterID : (isset($_POST['characterID']) ? $_POST['characterID'] : -1);
	
	if ($characterID > 0) {
		// get from database
		$sql = "SELECT characters.NAME as charactername, 
					characters.EMAIL, 
					characters.WORDPRESS_ID, 
					characters.PLAYER_ID, 
					players.NAME as player,
					characters.PUBLIC_CLAN_ID,
					characters.PRIVATE_CLAN_ID,
					characters.NATURE_ID,
					characters.DEMEANOUR_ID,
					characters.CONCEPT,
					characters.SECT_ID
				FROM
					" . VTM_TABLE_PREFIX . "CHARACTER characters,
					" . VTM_TABLE_PREFIX . "PLAYER players
				WHERE
					characters.PLAYER_ID = players.ID
					AND characters.ID = %s";
		$sql = $wpdb->prepare($sql, $characterID);
		//echo "SQL: $sql<br />";
		$result = $wpdb->get_row($sql);
		//print_r($result);
		
		$email      = $result->EMAIL;
		$login      = $result->WORDPRESS_ID;
		$playerid   = $result->PLAYER_ID;
		$sectid     = $result->SECT_ID;
		$playername = stripslashes($result->player);
		$shownew    = 'off';
		$character  = stripslashes($result->charactername);
		
		$pub_clan    = $result->PUBLIC_CLAN_ID;
		$priv_clan   = $result->PRIVATE_CLAN_ID;
		$natureid    = $result->NATURE_ID;
		$demeanourid = $result->DEMEANOUR_ID;
		$concept     = stripslashes($result->CONCEPT);
		$playerset   = 1;
		
	
	} else {
		$email      = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
		$login      = isset($_POST['wordpress_id']) ? $_POST['wordpress_id'] : '';
		$playerid   = isset($_POST['playerID']) ? $_POST['playerID'] : '';
		$sectid     = isset($_POST['sect']) ? $_POST['sect'] : $config->HOME_SECT_ID;
		$playername = isset($_POST['player']) ? $_POST['player'] : '';
		$shownew    = isset($_POST['newplayer']) ? $_POST['newplayer'] : 'off';
		$character  = isset($_POST['character']) ? $_POST['character'] : '';
		$concept    = isset($_POST['concept']) ? $_POST['concept'] : '';
		
		$pub_clan    = 0;
		$priv_clan   = 0;
		$natureid    = 0;
		$demeanourid = 0;
		$playerset   = 0;
	}
	
	if (is_user_logged_in()) {
		get_currentuserinfo();
		$userid       = $current_user->ID;
		
		if ($userid != $chargenacct) {
			if (empty($email)) $email = $current_user->user_email;
			if (empty($login)) $login = $current_user->user_login;
			
			if (!empty($playername)) {
				// find another account with that email to guess the player
				$otherlogins = get_users("search=$email&exclude=$userid&number=1");
				$player      = vtm_get_player_from_login($otherlogins[0]->user_login);
				if (isset($player)) {
					$shownew    = 'off';
					$playername = $player->NAME;
					$playerid   = $player->ID;
				}
			} else {
				$playerid = vtm_get_player_name($playername);
			}
		}
	} 
	
	$output .= "<h3>Step $step: Basic Information</h3>\n";
	$output .= "<input type='hidden' name='playerID' value='$playerid'>\n";
	$output .= "<table>
		<tr>
			<th class='gvthleft'>Character Name*:</th>
			<td><input type='text' name='character' value='$character'> (ID: $characterID)</td>
		</tr>
		<tr>
			<th class='gvthleft'>Player Name*:</th>";
	if ($playerset) {
		$output .= "<td>$playername<input type='hidden' name='player' value='$playername'>";
	
	} else {
		$output .= "<td><input type='text' name='player' value='$playername'>";
		if ($shownew)
			$output .= "<input type='checkbox' name='newplayer' " . checked( 'on', $shownew, false) . "> : I am a new player";
	}
	$output .= "</td>
		</tr>
		<tr>
			<th class='gvthleft'>Actual Clan*:</th>
			<td><select name='priv_clan' autocomplete='off'>";
	foreach ($clans as $clan) {
		$output .= "<option value='{$clan->ID}' " . selected( $clan->ID, $priv_clan, false) . ">{$clan->NAME}</option>";
	}
	$output .= "</select></td>
		</tr>
		<tr>
			<th class='gvthleft'>Public Clan:</th>
			<td><select name='pub_clan' autocomplete='off'><option value='-1'>[Same as Actual]</option>";
	foreach ($clans as $clan) {
		$output .= "<option value='{$clan->ID}' " . selected( $clan->ID, $pub_clan, false) . ">{$clan->NAME}</option>";
	}
	$output .= "</select></td></tr><tr>
			<th class='gvthleft'>Sect:</th>
			<td><select name='sect' autocomplete='off'>";
	foreach (vtm_get_sects() as $sect) {
		$output .= "<option value='{$sect->ID}' " . selected( $sect->ID, $sectid, false) . ">{$sect->NAME}</option>";		
	}
	$output .= "</select></td></tr>";
	
	if ($config->USE_NATURE_DEMEANOUR == 'Y') {
		$output .= "<tr><th class='gvthleft'>Nature*:</th><td><select name='nature' autocomplete='off'>";
		foreach ($natures as $nature) {
			$output .= "<option value='" . $nature->ID . "' " . selected( $nature->ID, $natureid, false) . ">" . $nature->NAME . "</option>";
		}
		$output .= "</select></td></tr>
		<tr><th class='gvthleft'>Demeanour*:</th><td><select name='demeanour' autocomplete='off'>";
		foreach ($natures as $nature) {
			$output .= "<option value='" . $nature->ID . "' " . selected( $nature->ID, $demeanourid, false) . ">" . $nature->NAME . "</option>";
		}
		$output .= "</select></td></tr>";
	}	
	$output .= "<tr>
			<th class='gvthleft'>Preferred login name:</th>
			<td><input type='text' name='wordpress_id' value='$login'></td>
		</tr>
		<tr>
			<th class='gvthleft'>Email Address*:</th>
			<td><input type='text' name='email' value='$email'></td></tr>
		<tr>
			<th class='gvthleft'>Concept*:</th>
			<td><textarea name='concept' rows='3' cols='50'>$concept</textarea></td></tr>
		</table>";

	return $output;
}

function vtm_render_chargen_section($saved, $isPST, $pdots, $sdots, $tdots, $freedot,
	$items, $posted, $pendingfb, $pendingxp, $title, $postvariable) {

	$output = "";
	
	// Make a guess from saved levels which is Primary/Secondary/Tertiary
	if (count($saved) > 0) {
		if ($isPST) {
			$grouptotals = array();
			foreach  ($items as $item) {
				$key = sanitize_key($item->NAME);
				$grp = sanitize_key($item->GROUPING);
				if (isset($saved[$key])) {
					if (isset($grouptotals[$grp]))
						$grouptotals[$grp] += $saved[$key]->LEVEL - $freedot;
					else
						$grouptotals[$grp] = $saved[$key]->LEVEL - $freedot;
				}
			}
			//print_r($grouptotals);
			$groupselected = array();
			foreach ($grouptotals as $grp => $total) {
				switch($total) {
					case $pdots: $groupselected[$grp] = 1;break;
					case $sdots: $groupselected[$grp] = 2;break;
					case $tdots: $groupselected[$grp] = 3;break;
					default: $groupselected[$grp] = 0;
				}
			}
			//print_r($groupselected);
		}
	}

	$group = "";
	foreach ($items as $item) {
	
		// Heading and Primary/Secondary/Tertiary pull-down
		if (sanitize_key($item->GROUPING) != $group) {
			if ($group != "")
				$output .= "</table>\n";
			$group = sanitize_key($item->GROUPING);
			$output .= "<h4>{$item->GROUPING}</h4><p>";
			if ($isPST) {
				$val = isset($_POST[$group]) ? $_POST[$group] : (isset($groupselected[$group]) ? $groupselected[$group] : 0);
				$output .= vtm_render_pst_select($group, $val);
			}
			
			$output .= "</p>
				<input type='hidden' name='group[]' value='$group' />
				<table><tr><th>$title</th><th>Rating</th><th>Description</th></tr>\n";
		}
				
		// Display Data
		$output .= "<tr><td class=\"gvcol_key\">" . $item->NAME . "</td>";
		$output .= "<td>";
		
		$key     = sanitize_key($item->NAME);
		$level   = isset($posted[$key]) ? $posted[$key] : (isset($saved[$key]->LEVEL) ? $saved[$key]->LEVEL : 0);  // currently selected or saved level
		$pending = isset($pendingfb[$key]->value) ? $pendingfb[$key]->value : 0 ;         // level bought with freebies
		$pending = isset($pendingxp[$key]->value) ? $pendingxp[$key]->value : $pending ;  // level bought with xp
		$output .= vtm_render_dot_select($postvariable, $key, $level, $pending, $freedot, 5);
		
		$output .= "</td><td>";
		$output .= stripslashes($item->DESCRIPTION);
		$output .= "</td></tr>\n";
	
	}
	$output .= "</table>\n";

	return $output;
}

function vtm_render_attributes($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings   = vtm_get_chargen_settings($templateID);
	$items      = vtm_get_chargen_attributes($characterID);
	
	$pendingfb  = vtm_get_pending_freebies('STAT', $characterID);  
	$pendingxp  = vtm_get_pending_chargen_xp('STAT', $characterID);  

	//print_r($pendingxp);
	
	$output .= "<h3>Step $step: Attributes</h3>";
	
	if ($settings['attributes-method'] == "PST") {
		// Primary, Secondary, Tertiary
		$output .= "<p>You have {$settings['attributes-primary']} dots to spend on your Primary attributes, {$settings['attributes-secondary']} to spend on Secondary and {$settings['attributes-tertiary']} to spend on Tertiary.</p>";
	} else {
		$output .= "<p>You have {$settings['attributes-points']} dots to spend on your attributes</p>";
	}

	// Get levels saved into database
	$sql = "SELECT 
				stats.NAME, cs.STAT_ID, cs.LEVEL, cs.COMMENT 
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cs,
				" . VTM_TABLE_PREFIX . "STAT stats
			WHERE 
				cs.STAT_ID = stats.ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K)); 
	//print_r($saved);
	
	// Get Posted data
	$stats = isset($_POST['attribute_value']) ? $_POST['attribute_value'] : array();
	
	$output .= vtm_render_chargen_section($saved, ($settings['attributes-method'] == "PST"), 
		$settings['attributes-primary'], $settings['attributes-secondary'], $settings['attributes-tertiary'], 
		1, $items, $stats, $pendingfb, $pendingxp, 'Attributes', 'attribute_value');
	
	return $output;
}
/*
function vtm_render_chargen_virtues($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$virtues  = vtm_get_chargen_virtues($characterID);
	$pending  = vtm_get_pending_freebies('STAT', 'freebie_stat', $characterID);  // name => value
	
	$output .= "<h3>Step $step: Virtues</h3>";
	$output .= "<p>You have {$settings['virtues-points']} dots to spend on your virtues.</p>";
	
	// read initial values
	$sql = "SELECT cstat.STAT_ID, cstat.LEVEL
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat,
				" . VTM_TABLE_PREFIX . "STAT stats
			WHERE
				cstat.STAT_ID = stats.ID
				AND stats.GROUPING = 'Virtue'
				AND CHARACTER_ID = %s ";
	$sql = $wpdb->prepare($sql, $characterID);
	$keys = $wpdb->get_col($sql);
	if (count($keys) > 0) {
		$vals = $wpdb->get_col($sql,1);
		$stats = array_combine($keys, $vals);
	}
	elseif (isset($_POST['virtue_value'])) {
		$stats = $_POST['virtue_value'];
	}
	
	$config = vtm_getConfig();
	
	if (isset($_POST['path'])) {
		$selectedpath = $_POST['path'];
	} else {
		$selectedpath = $wpdb->get_var($wpdb->prepare("SELECT ROAD_OR_PATH_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	}
	$statid1      = $wpdb->get_var($wpdb->prepare("SELECT STAT1_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $selectedpath));
	$statid2      = $wpdb->get_var($wpdb->prepare("SELECT STAT2_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $selectedpath));
	$courage      = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "STAT WHERE NAME = 'Courage'");
	$output .= "<p><label>Path of Enlightenment:</label><select name='path' autocomplete='off'>\n";
	foreach (vtm_get_chargen_roads() as $path) {
		$output .= "<option value='{$path->ID}' " . selected($path->ID, $selectedpath, false) . ">" . stripslashes($path->NAME) . "</option>";
	}
	$output .= "</select>\n";
	
	$output .= "<table><tr><th>Virtue</th><th>Rating</th><th>Description</th></tr>\n";
	
	// Stat1
	$key = sanitize_key($virtues[$statid1]->NAME);
	$output .= "<tr><td class=\"gvcol_key\">" . $virtues[$statid1]->NAME . "</td>";
	$output .= "<td>";
	$output .= vtm_render_dot_select("virtue_value", $statid1, 
					isset($stats[$statid1]) ? $stats[$statid1] : -1,
					1,5,isset($pending[$key]) ? $pending[$key] : 0);
	$output .= "</td><td>";
	$output .= stripslashes($virtues[$statid1]->DESCRIPTION);
	$output .= "</td></tr>\n";

	// Stat2
	$key = sanitize_key($virtues[$statid2]->NAME);
	$output .= "<tr><td class=\"gvcol_key\">" . $virtues[$statid2]->NAME . "</td>";
	$output .= "<td>";
	$output .= vtm_render_dot_select("virtue_value", $statid2, isset($stats[$statid2]) ? $stats[$statid2] : -1,
					1,5,isset($pending[$key]) ? $pending[$key] : 0);
	$output .= "</td><td>";
	$output .= stripslashes($virtues[$statid2]->DESCRIPTION);
	$output .= "</td></tr>\n";

	// Courage
	$key = sanitize_key($virtues[$courage]->NAME);
	$output .= "<tr><td class=\"gvcol_key\">" . $virtues[$courage]->NAME . "</td>";
	$output .= "<td>";
	$output .= vtm_render_dot_select("virtue_value", $courage, isset($stats[$courage]) ? $stats[$courage] : -1, 
						1,5,isset($pending[$key]) ? $pending[$key] : 0);
	$output .= "</td><td>";
	$output .= stripslashes($virtues[$courage]->DESCRIPTION);
	$output .= "</td></tr>\n";

	$output .= "</table>\n";
	
	return $output;
}
function vtm_render_chargen_freebies($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	
	// Work out how much points are currently available
	$points = $settings['freebies-points'];
	$spent = 0;
	$spent += vtm_get_freebies_spent('STAT', 'freebie_stat', $characterID);
	$spent += vtm_get_freebies_spent('SKILL', 'freebie_skill', $characterID);
	$spent += vtm_get_freebies_spent('DISCIPLINE', 'freebie_discipline', $characterID);
	$spent += vtm_get_freebies_spent('BACKGROUND', 'freebie_background', $characterID);
	$spent += vtm_get_freebies_spent('MERIT', 'freebie_merit', $characterID);
	$spent += vtm_get_freebies_spent('PATH', 'freebie_path', $characterID);
	$remaining = $points - $spent;
	
	$output .= "<h3>Step $step: Freebie Points</h3>";
	$output .= "<p>";
	if ($settings['merits-max'] > 0)
		$output .= "You can have a maximum of {$settings['merits-max']} points of Merits. ";
	if ($settings['flaws-max'] > 0)
		$output .= "You can have a maximum of {$settings['flaws-max']} points of Flaws. ";
	$output .= "You have $points points available to spend on your character. $spent have been spent leaving 
	you $remaining points. Hover over the dot to show the freebie point cost.</p>";
	
	$sectiontitle   = array(
						'stat'       => "Attributes and Stats",
						'skill'      => "Abilities",
						'disc'       => "Disciplines",
						'path'       => "Paths",
						'background' => "Backgrounds",
						'merit'      => "Merits and Flaws",
					);
	$sectionorder   = array('stat', 'skill', 'background', 'disc', 'path', 'merit');
	
	$pendingSpends = array();
	$sectioncontent['stat']  = vtm_render_freebie_stats($characterID, $pendingSpends, $points);
	$sectioncontent['skill'] = vtm_render_freebie_skills($characterID, $pendingSpends, $points);
	$sectioncontent['disc']  = vtm_render_freebie_disciplines($characterID, $pendingSpends, $points);
	$sectioncontent['background'] = vtm_render_freebie_backgrounds($characterID, $pendingSpends, $points);
	$sectioncontent['merit'] = vtm_render_freebie_merits($characterID, $pendingSpends, $points);
	$sectioncontent['path'] = vtm_render_freebie_paths($characterID, $pendingSpends, $points);
	
	// DISPLAY TABLES 
	//-------------------------------
	$i = 0;
	foreach ($sectionorder as $section) {
		if (isset($sectioncontent[$section]) && $sectiontitle[$section] && $sectioncontent[$section]) {
			$jumpto[$i++] = "<a href='#gvid_fb_$section' class='gvfb_jump'>" . $sectiontitle[$section] . "</a>";
		}
	}
	$outputJump = "<p>Jump to section: " . implode(" | ", $jumpto) . "</p>";
	
	foreach ($sectionorder as $section) {
	
		if (isset($sectioncontent[$section]) && $sectioncontent[$section] != "" ) {
			$output .= "<h4 class='gvfb_head' id='gvid_fb_$section'>" . $sectiontitle[$section] . "</h4>\n";
			$output .= "$outputJump\n";
			$output .= $sectioncontent[$section];
		} 
		
	}
	
	return $output;
}
function vtm_render_chargen_xp($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	
	// Work out how much points are currently available
	$points = vtm_get_total_xp(0, $characterID);
	$spent = 0;
	$spent += vtm_get_chargen_xp_spent('STAT', 'xp_stat', $characterID);
	$spent += vtm_get_chargen_xp_spent('SKILL', 'xp_skill', $characterID);
	$spent += vtm_get_chargen_xp_spent('DISCIPLINE', 'xp_discipline', $characterID);
	$spent += vtm_get_chargen_xp_spent('PATH', 'xp_path', $characterID);
	$spent += vtm_get_chargen_xp_spent('MERIT', 'xp_merit', $characterID);
	$remaining = $points - $spent;

	$output .= "<h3>Step $step: Experience Points</h3>";
	$output .= "<p>";
	$output .= "You have $points points available to spend on your character. $spent have been spent leaving 
	you $remaining points. Hover over the dot to show the experience point cost.</p>";
	
	$sectiontitle   = array(
						'stat'       => "Attributes and Stats",
						'skill'      => "Abilities",
						'disc'       => "Disciplines",
						'path'       => "Paths",
						'merit'      => "Merits and Flaws",
					);
	$sectionorder   = array('stat', 'skill', 'disc', 'path', 'merit');
	
	$pendingSpends = array();
	$sectioncontent['stat']  = vtm_render_chargen_xp_stats($characterID, $pendingSpends, $points);
	$sectioncontent['skill'] = vtm_render_chargen_xp_skills($characterID, $pendingSpends, $points);
	$sectioncontent['disc']  = vtm_render_xp_disciplines($characterID, $pendingSpends, $points);
	$sectioncontent['path']  = vtm_render_chargen_xp_paths($characterID, $pendingSpends, $points);
	$sectioncontent['merit'] = vtm_render_chargen_xp_merits($characterID, $pendingSpends, $points);
	
	// DISPLAY TABLES 
	//-------------------------------
	$i = 0;
	foreach ($sectionorder as $section) {
		if (isset($sectioncontent[$section]) && $sectiontitle[$section] && $sectioncontent[$section]) {
			$jumpto[$i++] = "<a href='#gvid_xp_$section' class='gvxp_jump'>" . $sectiontitle[$section] . "</a>";
		}
	}
	$outputJump = "<p>Jump to section: " . implode(" | ", $jumpto) . "</p>";
	
	foreach ($sectionorder as $section) {
	
		if (isset($sectioncontent[$section]) && $sectioncontent[$section] != "" ) {
			$output .= "<h4 class='gvxp_head' id='gvid_xp_$section'>" . $sectiontitle[$section] . "</h4>\n";
			$output .= "$outputJump\n";
			$output .= $sectioncontent[$section];
		} 
		
	}
	
	return $output;
}
function vtm_render_finishing($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$options  = vtm_getConfig();
	
	$output .= "<h3>Step $step: Finishing Touches</h3>";
	$output .= "<p>Please fill in more information on your character.</p>";
	
	// Calculate Generation
	$defaultgen = $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "GENERATION WHERE ID = %s", $options->DEFAULT_GENERATION_ID));
	$sql = "SELECT charbg.LEVEL 
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND charbg,
				" . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE
				charbg.CHARACTER_ID = %s
				AND charbg.BACKGROUND_ID = bg.ID
				AND bg.NAME = 'Generation'";
	$genfromgb  = $wpdb->get_var($wpdb->prepare($sql, $characterID));
	$sql = "SELECT LEVEL_TO 
			FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
			WHERE 
				ITEMTABLE = 'BACKGROUND'
				AND ITEMNAME = 'generation'
				AND CHARACTER_ID = %s";
	$genfromfreebie = $wpdb->get_var($wpdb->prepare($sql, $characterID));
	$generation     = $defaultgen - (isset($genfromfreebie) ? $genfromfreebie : $genfromgb);
	$generationID   = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . VTM_TABLE_PREFIX . "GENERATION WHERE NAME = %s", $generation));

	// Calculate Path
	$pathid    = $wpdb->get_var($wpdb->prepare("SELECT ROAD_OR_PATH_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$pathname  = $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $pathid));
	$statid1   = $wpdb->get_var($wpdb->prepare("SELECT STAT1_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $pathid));
	$statid2   = $wpdb->get_var($wpdb->prepare("SELECT STAT2_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $pathid));
	$sql = "SELECT LEVEL 
			FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT 
			WHERE STAT_ID = %s AND CHARACTER_ID = %s";
	$stat1      = $wpdb->get_var($wpdb->prepare($sql, $statid1, $characterID));
	$stat2      = $wpdb->get_var($wpdb->prepare($sql, $statid2, $characterID));
	$pathrating = ($stat1 + $stat2) * $settings['road-multiplier'];

	// Date of Birth
	$dob = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_BIRTH FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$dob_day   = isset($_POST['day_dob'])   ? $_POST['day_dob']   : (isset($dob) ? strftime("%d", strtotime($dob)) : '');
	$dob_month = isset($_POST['month_dob']) ? $_POST['month_dob'] : (isset($dob) ? strftime("%m", strtotime($dob)) : '');
	$dob_year  = isset($_POST['year_dob'])  ? $_POST['year_dob']  : (isset($dob) ? strftime("%Y", strtotime($dob)) : '');
	
	// Date of Embrace
	$doe = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_EMBRACE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$doe_day   = isset($_POST['day_doe'])   ? $_POST['day_doe']   : (isset($doe) ? strftime("%d", strtotime($doe)) : '');
	$doe_month = isset($_POST['month_doe']) ? $_POST['month_doe'] : (isset($doe) ? strftime("%m", strtotime($doe)) : '');
	$doe_year  = isset($_POST['year_doe'])  ? $_POST['year_doe']  : (isset($doe) ? strftime("%Y", strtotime($doe)) : '');
	
	// Date of Embrace
	$sire = $wpdb->get_var($wpdb->prepare("SELECT SIRE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$sire = isset($_POST['sire']) ? $_POST['sire'] : $sire;
	
	// Specialities Data
	$specialities = vtm_get_chargen_specialties($characterID);
	
	$output .= "<h4>Calculated Values</h4>\n";
	$output .= "<table>\n";
	$output .= "<tr><td>Generation:</td><td>$generation";
	$output .= "<input type='hidden' name='generationID' value='$generationID' />";
	$output .= "</td></tr>";
	$output .= "<tr><td>$pathname:</td><td>$pathrating";
	$output .= "<input type='hidden' name='pathrating' value='$pathrating' />";
	$output .= "</td></tr>";
	$output .= "</table>\n";

	$output .= "<h4>Important Dates</h4>\n";
	$output .= "<table>\n";
	$output .= "<tr><td>Date of Birth:</td><td>";
	$output .= vtm_render_date_entry("dob", $dob_day, $dob_month, $dob_year);
	$output .= "</td></tr>";
	$output .= "<tr><td>Date of Embrace:</th><td>";
	$output .= vtm_render_date_entry("doe", $doe_day, $doe_month, $doe_year);
	$output .= "</td></tr>";
	$output .= "</table>\n";

	$i = 0;
	$title = "";
	
	// Notes to ST
	$stnotes = $wpdb->get_var($wpdb->prepare("SELECT CHARGEN_NOTE_TO_ST FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$stnotes = isset($_POST['noteforST']) ? $_POST['noteforST'] : $stnotes;
	
	$spec_output = "";
	foreach ($specialities as $item) {
		if ($title != $item['title']) {
			$title = $item['title'];
			$spec_output .= "<tr><th colspan=3>$title</th></tr>";
		}
	
		// have a hidden row with the tablename and tableid info
		$spec_output .= "<tr style='display:none'><td colspan=3>
					<input type='hidden' name='tablename[]' value='{$item['updatetable']}' />
					<input type='hidden' name='tableid[]' value='{$item['tableid']}' />
					<input type='hidden' name='itemname[]' value='{$item['name']}' />
					</td></tr>";
		
		$spec = isset($_POST['comment'][$i]) ? $_POST['comment'][$i] : $item['comment'];
		$spec_output .= "<tr><td>" . stripslashes($item['name']) . "</td>
					<td>{$item['level']}</td>
					<td><input type='text' name='comment[]' value='$spec' />{$item['updatetable']} / {$item['tableid']}</td></tr>";
					
		$i++;
	}
	if ($spec_output != '') {
		$output .= "<h4>Specialities</h4>\n";
		$output .= "<p>Please enter specialities for the indicated Attributes and Abilities and provide
					a note on what any Merits and Flaws refer to.</p>
					
					<p>An example speciality for Stamina is 'tough'. An example note for the Merit 'Acute Sense'
					would be 'sight.'</p>";
		$output .= "<table>$spec_output\n";
		$output .= "</table>\n";
	}
	
	$output .= "<h4>Miscellaneous</h4>\n";
	$output .= "<table>\n";
	$output .= "<tr><td>Name of your Sire:</td><td>";
	$output .= "<input type='text' name='sire' value='$sire' />";
	$output .= "</td></tr>";
	$output .= "<tr><td>Notes for Storyteller:</th><td>";
	$output .= "<textarea name='noteforST' rows='5' cols='80'>$stnotes</textarea>"; // ADD COLUMN TO CHARACTER
	$output .= "</td></tr>";
	$output .= "</table>\n";
	
	return $output;
}
*/
function vtm_render_abilities($step, $characterID, $templateID) {
	global $wpdb;

	$output     = "";
	$settings   = vtm_get_chargen_settings($templateID);
	$items      = vtm_get_chargen_abilities($characterID);
	$pendingfb  = vtm_get_pending_freebies('SKILL', $characterID); 
	$pendingxp  = vtm_get_pending_chargen_xp('SKILL', $characterID); 
		
	$output .= "<h3>Step $step: Abilities</h3>";
	$output .= "<p>You have {$settings['abilities-primary']} dots to spend on your Primary abilities, {$settings['abilities-secondary']} to spend on Secondary and {$settings['abilities-tertiary']} to spend on Tertiary.</p>";
	

	// read/guess initial values
	$sql = "SELECT 
				skills.NAME, cs.SKILL_ID, cs.LEVEL, cs.COMMENT 
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_SKILL cs,
				" . VTM_TABLE_PREFIX . "SKILL skills
			WHERE 
				skills.ID = cs.SKILL_ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K)); 
	//echo "<li>SQL: $sql</li>";
	
	//print_r($pendingxp);
	
	// abilities Posted data
	$abilities = isset($_POST['ability_value']) ? $_POST['ability_value'] : array();
	
	$output .= vtm_render_chargen_section($saved, true, 
		$settings['abilities-primary'], $settings['abilities-secondary'], $settings['abilities-tertiary'], 
		0, $items, $abilities, $pendingfb, $pendingxp, 'Abilities', 'ability_value');

	return $output;
}

function vtm_render_chargen_disciplines($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings   = vtm_get_chargen_settings($templateID);
	$items      = vtm_get_chargen_disciplines($characterID);
	$pendingfb  = vtm_get_pending_freebies('DISCIPLINE', $characterID); 
	$pendingxp  = vtm_get_pending_chargen_xp('DISCIPLINE', $characterID); 
		
	$output .= "<h3>Step $step: Disciplines</h3>";
	$output .= "<p>You have {$settings['disciplines-points']} dots to spend on your Disciplines</p>";

	// read initial values
	$sql = "SELECT 
				disc.NAME, cd.DISCIPLINE_ID, cd.LEVEL 
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE cd,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE 
				disc.ID = cd.DISCIPLINE_ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K)); 

	$disciplines = isset($_POST['discipline_value']) ? $_POST['discipline_value'] : array();
	
	$output .= vtm_render_chargen_section($saved, false, 0, 0, 0, 
		0, $items, $disciplines, $pendingfb, $pendingxp, 'Disciplines', 'discipline_value');


	return $output;
}
/*
function vtm_render_chargen_backgrounds($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings    = vtm_get_chargen_settings($templateID);
	$backgrounds = vtm_get_chargen_backgrounds($characterID);
	$pending     = vtm_get_pending_freebies('BACKGROUND', 'freebie_background', $characterID);  // name => value
	
	$output .= "<h3>Step $step: Backgrounds</h3>";
	$output .= "<p>You have {$settings['backgrounds-points']} dots to spend on your Backgrounds</p>";
	

	// read initial values
	$sql = "SELECT BACKGROUND_ID, LEVEL FROM " . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND
			WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$keys = $wpdb->get_col($sql);
	if (count($keys) > 0) {
		$vals = $wpdb->get_col($sql,1);
		$mybg = array_combine($keys, $vals);
	}
	elseif (isset($_POST['background_value'])) {
		$mybg = $_POST['background_value'];
	}

	$output .= "<table><tr><th>Background</th><th>Rating</th><th>Description</th></tr>\n";
	foreach ($backgrounds as $background) {
		$output .= "<tr><td class=\"gvcol_key\">" . $background->NAME . "</td>";
		$output .= "<td>";
		
		$key = sanitize_key($background->NAME);
		$output .= vtm_render_dot_select("background_value", 
						$background->ID, 
						isset($mybg[$background->ID]) ? $mybg[$background->ID] : -1,
						0, 5, isset($pending[$key]) ? $pending[$key] : 0);
		$output .= "</td><td>";
		$output .= stripslashes($background->DESCRIPTION);
		$output .= "</td></tr>\n";
	}
	$output .= "</table>\n";
	
	return $output;
} */
function vtm_render_choose_template() {
	global $wpdb;

	$output = "";
	
	$output .= "<h3>Choose a template</h3>";
	
	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE WHERE VISIBLE = 'Y' ORDER BY NAME";
	$result = $wpdb->get_results($sql);
	
	$output .= "<p><label>Character Generation Template:</label> <select name='chargen_template'>";
	foreach ($result as $template) {
		$output .= "<option value='{$template->ID}'>{$template->NAME}</option>";
	}
	$output .= "</select></p>";
	
	$ref = isset($_GET['reference']) ? $_GET['reference'] : '';
	
	$output .= "<p>Or, update a character: 
		<label>Reference:</label> <input type='text' name='chargen_reference' value='$ref' size=5 ></p>";
	
	return $output;
}

function vtm_validate_chargen($laststep, $templateID, $characterID) {
	global $wpdb;
	global $current_user;

	$settings = vtm_get_chargen_settings($templateID);
	$flow = vtm_chargen_flow_steps($characterID);
	
	if ($laststep == 0) {
		$ok = 1;
		$errormessages = "";
	} else {
		$status = call_user_func($flow[$laststep-1]['validate'], $settings, $characterID);
		$ok = $status[0];
		$errormessages = $status[1];
	}
	
	if (!$ok)
		$errormessages .= "<li>Please correct the errors before continuing</li>\n";
	
	if ($errormessages != "") {
		echo "<div class='gvxp_error'><ul>$errormessages</ul></div>";
	}
	
	return $ok;
}

function vtm_save_progress($laststep, $characterID, $templateID) {
	
	$flow = vtm_chargen_flow_steps($characterID);
	if ($laststep != 0) {
		echo "<li>laststep: $laststep, function: {$flow[$laststep-1]['save']}</li>";
		$characterID = call_user_func($flow[$laststep-1]['save'], $characterID, $templateID);
	}

	return $characterID;
}

function vtm_save_attributes($characterID) {
	global $wpdb;
	
	// List of attributes
	$attributes    = vtm_get_chargen_attributes();
	
	// Get saved into database
	$sql = "SELECT stats.NAME, cstat.STAT_ID, cstat.ID, cstat.COMMENT
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat,
				" . VTM_TABLE_PREFIX . "STAT stats
			WHERE 
				stats.ID = cstat.STAT_ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));

	// Get levels to be saved
	$new = $_POST['attribute_value'];
	
	foreach ($attributes as $attribute) {
		$key     = sanitize_key($attribute->NAME);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		$comment = isset($saved[$key]->COMMENT) ? $saved[$key]->COMMENT : '';
	
		$data = array(
			'CHARACTER_ID' => $characterID,
			'STAT_ID'      => $attribute->ID,
			'LEVEL'        => $value,
			'COMMENT'      => $comment
		);
		if (isset($saved[$key])) {
			// update
			$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_STAT",
				$data,
				array (
					'ID' => $saved[$key]->ID
				)
			);
		} else {
			// insert
			$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_STAT",
						$data,
						array ('%d', '%d', '%d', '%s')
					);
		}
	}
	
	// Delete appearance, if it's no longer needed
	if (isset($saved['appearance']) && !isset($new['appearance'])) {
		// Delete
		$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT
				WHERE CHARACTER_ID = %s AND STAT_ID = %s";
		$wpdb->get_results($wpdb->prepare($sql,$characterID,$saved['appearance']->STAT_ID));
	}
	return $characterID;

}
/*
function vtm_save_freebies($characterID, $templateID) {
	global $wpdb;

	
	// Delete current pending spends
	$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
			WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$result = $wpdb->get_results($sql);
	
	// (re)Add pending spends
	$new['STAT']          = isset($_POST['freebie_stat']) ? $_POST['freebie_stat'] : array();
	$freebiecosts['STAT'] = vtm_get_freebie_costs('STAT');
	$current['STAT']      = vtm_get_current_stats($characterID, OBJECT_K);
	$items['STAT']        = vtm_sanitize_array(vtm_get_chargen_stats($characterID, OBJECT_K));

	$new['SKILL']          = isset($_POST['freebie_skill']) ? $_POST['freebie_skill'] : array();
	$freebiecosts['SKILL'] = vtm_get_freebie_costs('SKILL');
	$current['SKILL']      = vtm_get_current_skills($characterID, OBJECT_K);
	$items['SKILL']        = vtm_sanitize_array(vtm_get_chargen_abilities($characterID, 1, OBJECT_K));

	$new['DISCIPLINE']          = isset($_POST['freebie_discipline']) ? $_POST['freebie_discipline'] : array();
	$freebiecosts['DISCIPLINE'] = vtm_get_freebie_costs('DISCIPLINE', $characterID);
	$current['DISCIPLINE']      = vtm_get_current_disciplines($characterID, OBJECT_K);
	$items['DISCIPLINE']        = vtm_sanitize_array(vtm_get_chargen_disciplines($characterID, OBJECT_K));

	$new['BACKGROUND']          = isset($_POST['freebie_background']) ? $_POST['freebie_background'] : array();
	$freebiecosts['BACKGROUND'] = vtm_get_freebie_costs('BACKGROUND', $characterID);
	$current['BACKGROUND']      = vtm_get_current_backgrounds($characterID, OBJECT_K);
	$items['BACKGROUND']        = vtm_sanitize_array(vtm_get_chargen_backgrounds($characterID, OBJECT_K));
	
	$new['MERIT']          = isset($_POST['freebie_merit']) ? $_POST['freebie_merit'] : array();
	$freebiecosts['MERIT'] = vtm_get_freebie_costs('MERIT', $characterID);
	$current['MERIT']      = vtm_get_current_merits($characterID, OBJECT_K);
	$items['MERIT']        = vtm_sanitize_array(vtm_get_chargen_merits($characterID, OBJECT_K));
	
	$new['PATH']          = isset($_POST['freebie_path']) ? $_POST['freebie_path'] : array();
	$freebiecosts['PATH'] = vtm_get_freebie_costs('PATH', $characterID);
	$current['PATH']      = vtm_get_current_paths($characterID, OBJECT_K);
	$items['PATH']        = vtm_sanitize_array(vtm_get_chargen_paths($characterID, OBJECT_K));
	
	//print_r($freebiecosts['PATH']);
	
	foreach ($new as $type => $row) {
		foreach ($row as $key => $value) {
			
			if ($value != 0) {
				$itemname = $key;
				if (isset($items[$type][$key]->NAME)) {
					$name = $items[$type][$key]->NAME;
				} else {
					$key  = preg_replace("/_\d+$/", "", $key);
					$name = $items[$type][$key]->NAME;
				}
							
				$chartableid = isset($current[$type][$name]->chartableid) ? $current[$type][$name]->chartableid : 0;
				$levelfrom   = isset($current[$type][$name]->level_from)  ? $current[$type][$name]->level_from  : 0;
				$amount      = ($type == 'MERIT') ? $freebiecosts[$type][$name][0][1] : $freebiecosts[$type][$name][$levelfrom][$value];
				$itemid      = $items[$type][$key]->ID;
				
				

				
				if ($value > $levelfrom || $type == 'MERIT') {
					$data = array (
						'CHARACTER_ID' => $characterID,
						'CHARTABLE'    => 'CHARACTER_' . $type,
						'CHARTABLE_ID' => $chartableid,
						'LEVEL_FROM'   => $levelfrom,
						'LEVEL_TO'     => $value,
						'AMOUNT'       => $amount,
						'ITEMTABLE'    => $type,
						//'ITEMNAME'     => stripslashes($name),
						'ITEMNAME'     => $itemname,
						'ITEMTABLE_ID' => $itemid
					);
					$wpdb->insert(VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND",
								$data,
								array (
									'%d', '%s', '%d', '%d',
									'%d', '%d', '%s', '%s',
									'%d'
								)
							);
					if ($wpdb->insert_id == 0) {
						echo "<p style='color:red'><b>Error:</b> $name could not be inserted</p>";
					}		
					//print_r($data);
				}
			}
		}
	}

	return $characterID;
}
function vtm_save_finish($characterID, $templateID) {
	global $wpdb;

	// Save CHARACTER information
	$dob = $_POST['year_dob'] . '-' . $_POST['month_dob'] . '-' . $_POST['day_dob'];
	$doe = $_POST['year_doe'] . '-' . $_POST['month_doe'] . '-' . $_POST['day_doe'];
	
	$data = array (
		'CHARGEN_NOTE_TO_ST'  => $_POST['noteforST'],
		'SIRE'                => $_POST['sire'],
		'DATE_OF_BIRTH'       => $dob,
		'DATE_OF_EMBRACE'     => $doe,
		'GENERATION_ID'       => $_POST['generationID'],
		'ROAD_OR_PATH_RATING' => $_POST['pathrating'],
	);
	
	$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER",
		$data,
		array (
			'ID' => $characterID
		),
		array('%s', '%s', '%s', '%s', '%d', '%s')
	);		
	
	
	// Save Specialities
	if (isset($_POST['itemname'])) {
		foreach ($_POST['itemname'] as $index => $name) {
			$comment = $_POST['comment'][$index];
			$id      = $_POST['tableid'][$index];
			$table   = $_POST['tablename'][$index];
			
			switch($table) {
				Case 'PENDING_FREEBIE_SPEND': $colname = 'SPECIALISATION'; break;
				Case 'PENDING_XP_SPEND':  	  $colname = 'SPECIALISATION'; break;
				default:                      $colname = 'COMMENT';
			}

			$data = array (
				$colname => $comment
			);
			$result = $wpdb->update(VTM_TABLE_PREFIX . $table, $data, array ('ID' => $id),array('%s'));		
			if ($result) 			echo "<p style='color:green'>Updated $name speciality with $comment</p>";
			else if ($result === 0) echo "<p style='color:orange'>No updates made to $name speciality</p>";
			else {
				$wpdb->print_error();
				echo "<p style='color:red'>Could not update $name speciality</p>";
			}
		}
	}

	return $characterID;
}
function vtm_save_xp($characterID, $templateID) {
	global $wpdb;

	$playerID = $wpdb->get_var($wpdb->prepare("SELECT PLAYER_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	
	// Delete current pending spends
	$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
			WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$result = $wpdb->get_results($sql);
	
	// (re)Add pending spends

	$new['STAT']       = isset($_POST['xp_stat']) ? $_POST['xp_stat'] : array();
	$comments['STAT']  = isset($_POST['xp_stat_comment']) ? $_POST['xp_stat_comment'] : array();
	$xpcosts['STAT']   = vtm_get_chargen_xp_costs('STAT', $characterID);
	$freebies['STAT']  = vtm_get_pending_freebies('STAT', 'freebie_stat', $characterID);
	$current['STAT']   = vtm_sanitize_array(vtm_get_current_stats($characterID, OBJECT_K));
	$items['STAT']     = vtm_sanitize_array(vtm_get_chargen_stats($characterID, OBJECT_K));

	$new['SKILL']       = isset($_POST['xp_skill']) ? $_POST['xp_skill'] : array();
	$comments['SKILL']  = isset($_POST['xp_skill_comment']) ? $_POST['xp_skill_comment'] : array();
	$xpcosts['SKILL']   = vtm_get_chargen_xp_costs('SKILL', $characterID);
	$freebies['SKILL']  = vtm_get_pending_freebies('SKILL', 'freebie_skill', $characterID);
	$current['SKILL']   = vtm_sanitize_array(vtm_get_current_skills($characterID, OBJECT_K));
	$items['SKILL']     = vtm_sanitize_array(vtm_get_chargen_abilities($characterID, 1, OBJECT_K));
	
	$new['DISCIPLINE']       = isset($_POST['xp_discipline']) ? $_POST['xp_discipline'] : array();
	$xpcosts['DISCIPLINE']   = vtm_get_chargen_xp_costs('DISCIPLINE', $characterID);
	$freebies['DISCIPLINE']  = vtm_get_pending_freebies('DISCIPLINE', 'freebie_discipline', $characterID);
	$current['DISCIPLINE']   = vtm_sanitize_array(vtm_get_current_disciplines($characterID, OBJECT_K));
	$items['DISCIPLINE']     = vtm_sanitize_array(vtm_get_chargen_disciplines($characterID, OBJECT_K));
	
	$new['PATH']       = isset($_POST['xp_path']) ? $_POST['xp_path'] : array();
	$xpcosts['PATH']   = vtm_get_chargen_xp_costs('PATH', $characterID);
	$freebies['PATH']  = vtm_get_pending_freebies('PATH', 'freebie_path', $characterID);
	$current['PATH']   = vtm_sanitize_array(vtm_get_current_paths($characterID, OBJECT_K));
	$items['PATH']     = vtm_sanitize_array(vtm_get_chargen_paths($characterID, OBJECT_K));

	$new['MERIT']       = isset($_POST['xp_merit']) ? $_POST['xp_merit'] : array();
	$comments['MERIT']  = isset($_POST['xp_merit_comment']) ? $_POST['xp_merit_comment'] : array();
	$xpcosts['MERIT']   = vtm_get_chargen_xp_costs('MERIT', $characterID);
	$freebies['MERIT']  = vtm_get_pending_freebies('MERIT', 'freebie_merit', $characterID);
	$current['MERIT']   = vtm_sanitize_array(vtm_get_current_merits($characterID, OBJECT_K));
	$items['MERIT']     = vtm_sanitize_array(vtm_get_chargen_merits($characterID, OBJECT_K));

	
	//print_r($items['STAT']);
	
	foreach ($new as $type => $row) {
		foreach ($row as $key => $value) {
		
			if ($value != 0) {
				$itemname = $key;
				if (isset($items[$type][$key]->NAME)) {
					$name = $items[$type][$key]->NAME;
				} else {
					$key  = preg_replace("/_\d+$/", "", $key);
					$name = $items[$type][$key]->NAME;
				}
				//echo "<li>origkey: $itemname, key: $key, value:$value, name: $name</li>";
	
				$chartableid = isset($current[$type][$key]->chartableid) ? $current[$type][$key]->chartableid : 0;
				$levelfrom   = isset($current[$type][$key]->level_from)  ? $current[$type][$key]->level_from  : 0;
				$levelfrom   = isset($freebies[$type][$key]) ? $freebies[$type][$key] : $levelfrom;
				$amount      = ($type == 'MERIT') ? $xpcosts[$type][$key][0][1] : $xpcosts[$type][$key][$levelfrom][$value];
				$itemid      = $items[$type][$key]->ID;
				$spec        = isset($comments[$type][$key]) ? $comments[$type][$key] : '';
				
				if ($value > $levelfrom || $type == 'MERIT') {
					$data = array (
						'PLAYER_ID'       => $playerID,
						'CHARACTER_ID'    => $characterID,
						'CHARTABLE'       => 'CHARACTER_' . $type,
						'CHARTABLE_ID'    => $chartableid,
						
						'CHARTABLE_LEVEL' => $value,
						'AWARDED'         => Date('Y-m-d'),
						'AMOUNT'          => -$amount,
						'COMMENT'         => "Character Generation: " . stripslashes($name) . " $levelfrom > $value",
						
						'SPECIALISATION'  => $spec,
						'TRAINING_NOTE'   => 'Character Generation',
						'ITEMTABLE'       => $type,
						'ITEMNAME'        => $itemname,
						
						'ITEMTABLE_ID'    => $itemid
					);
					//echo "<pre>";
					//print_r($data);
					//echo "</pre>";
					
					$wpdb->insert(VTM_TABLE_PREFIX . "PENDING_XP_SPEND",
								$data,
								array (
									'%d', '%d', '%s', '%d',
									'%d', '%s', '%d', '%s',
									'%s', '%s', '%s', '%s',
									'%d'
								)
							);
					if ($wpdb->insert_id == 0) {
						echo "<p style='color:red'><b>Error:</b> $name could not be inserted</p>";
					}		
				}
			}
		}
	}

	return $characterID;
}
*/
function vtm_save_abilities($characterID) {
	global $wpdb;

	$new       = $_POST['ability_value'];
	$abilities = vtm_get_chargen_abilities();
	
	// Get saved into database
	$sql = "SELECT skills.NAME, cskill.SKILL_ID, cskill.ID, cskill.LEVEL, cskill.COMMENT
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_SKILL cskill,
				" . VTM_TABLE_PREFIX . "SKILL skills
			WHERE 
				cskill.SKILL_ID = skills.ID
				AND cskill.CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));

	//print_r($new);
	//print_r($current);

	foreach ($abilities as $ability) {
		$key     = sanitize_key($ability->NAME);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		
		if ($value > 0) {
			$comment = isset($saved[$key]->COMMENT) ? $saved[$key]->COMMENT : '';
			
			$data = array(
				'CHARACTER_ID'  => $characterID,
				'SKILL_ID'      => $ability->ID,
				'LEVEL'         => $value,
				'COMMENT'		=> $comment
			);
			if (isset($saved[$key])) {
				if ($saved[$key]->LEVEL != $value) {
					//echo "<li>Updated $key at $value</li>";
					// update
					$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_SKILL",
						$data,
						array (
							'ID' => $saved[$key]->ID
						)
					);
				} //else {
					//echo "<li>No need to update $key</li>";
				//}
			} else {
				//echo "<li>Added $key at $value</li>";
				// insert
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_SKILL",
							$data,
							array ('%d', '%d', '%d', '%s')
						);
			}
		}
	}
		
	// Delete anything no longer needed
	foreach ($saved as $id => $value) {
	
		if (!isset($new[$id]) || $new[$id] <= 0) {
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_SKILL
					WHERE CHARACTER_ID = %s AND SKILL_ID = %s";
			$sql = $wpdb->prepare($sql,$characterID,$saved[$id]->SKILL_ID);
			//echo "<li>Delete $id ($sql)</li>";
			$wpdb->get_results($sql);
		}
	}

	return $characterID;
}

function vtm_save_disciplines($characterID) {
	global $wpdb;

	$new = $_POST['discipline_value'];
	$disciplines = vtm_get_chargen_disciplines($characterID);
	
	$sql = "SELECT disc.NAME, cdisc.DISCIPLINE_ID, cdisc.ID
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE cdisc,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE 
				cdisc.DISCIPLINE_ID = disc.ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));

	//echo "<li>SQL:$sql</li>";
	//print_r($new);
	//print_r($disciplines);
	//print_r($saved);

	foreach ($disciplines as $discipline) {
		$key     = sanitize_key($discipline->NAME);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		if ($value > 0) {
			$data = array(
				'CHARACTER_ID'  => $characterID,
				'DISCIPLINE_ID' => $discipline->ID,
				'LEVEL'         => $value
			);
			if (isset($saved[$key])) {
				if ($saved[$key]->LEVEL != $value) {
					echo "<li>Updated $key at $value</li>";
					// update
					$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE",
						$data,
						array (
							'ID' => $saved[$key]->ID
						)
					);
				} else {
					echo "<li>No need to update $key</li>";
				}
			} else {
				echo "<li>Added $key at $value</li>";
				// insert
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE",
							$data,
							array ('%d', '%d', '%d')
						);
			}
		}
	}
		
	// Delete anything no longer needed
	foreach ($saved as $id => $row) {
		if (!isset($new[$id]) || $new[$id] == 0) {
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE
					WHERE CHARACTER_ID = %s AND DISCIPLINE_ID = %s";
			$sql = $wpdb->prepare($sql,$characterID,$saved[$id]->DISCIPLINE_ID);
			echo "<li>Delete $id ($sql)</li>";
			$wpdb->get_results($sql);
		}
	}
	
	return $characterID;

}
/*
function vtm_save_backgrounds($characterID) {
	global $wpdb;


	$new = $_POST['background_value'];
	
	$sql = "SELECT cbg.BACKGROUND_ID, cbg.ID, bg.NAME
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND cbg,
				" . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE 
				cbg.BACKGROUND_ID = bg.ID
				AND cbg.CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$keys = $wpdb->get_col($sql);
	if (count($keys) > 0) {
		$vals = $wpdb->get_col($sql,1);
		$names = $wpdb->get_col($sql,2);
		$current = array_combine($keys, $vals);
	} else {
		$current = array();
	}
	//print_r($new);
	//print_r($current);

	foreach ($new as $id => $value) {
		if ($value > 0) {
			$data = array(
				'CHARACTER_ID' => $characterID,
				'BACKGROUND_ID'      => $id,
				'LEVEL'        => $value
			);
			if (isset($current[$id])) {
				//echo "<li>Updated $id at $value</li>";
				// update
				$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND",
					$data,
					array (
						'ID' => $current[$id]
					)
				);
			} else {
				//echo "<li>Added $id at $value</li>";
				// insert
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND",
							$data,
							array ('%d', '%d', '%d')
						);
			}
		}
	}
		
	// Delete anything no longer needed
	foreach ($current as $id => $value) {
	
		if (!isset($new[$id]) || $new[$id] == 0) {
			//echo "<li>Deleted $id</li>";
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND
					WHERE CHARACTER_ID = %s AND BACKGROUND_ID = %s";
			$wpdb->get_results($wpdb->prepare($sql,$characterID,$id));
		}
	}
	return $characterID;
	
}
function vtm_save_virtues($characterID, $templateID) {
	global $wpdb;

	$new      = $_POST['virtue_value'];
	$settings = vtm_get_chargen_settings($templateID);
	$virtues  = vtm_get_chargen_virtues();
	$selectedpath = $_POST['path'];
	$statid1  = $wpdb->get_var($wpdb->prepare("SELECT STAT1_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $selectedpath));
	$statid2  = $wpdb->get_var($wpdb->prepare("SELECT STAT2_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $selectedpath));
	$courage = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "STAT WHERE NAME = 'Courage'");
	$wpid    = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "STAT WHERE NAME = 'Willpower'");
	
	// Update CHARACTER with road/path ID and Rating
	$statval1 = isset($new[$statid1]) ? $new[$statid1] : 0;
	$statval2 = isset($new[$statid2]) ? $new[$statid2] : 0;
	$rating = ($statval1 + $statval2) * $settings['road-multiplier'];
	$data = array (
		'ROAD_OR_PATH_ID'     => $selectedpath,
		'ROAD_OR_PATH_RATING' => $rating
	);
	//print_r($data);
	$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER",
		$data,
		array (
			'ID' => $characterID
		)
	);
	
	// Update CHARACTER_STAT with virtue ratings
	$sql = "SELECT cstat.STAT_ID, cstat.ID, stats.NAME
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat,
				" . VTM_TABLE_PREFIX . "STAT stats
			WHERE 
				stats.ID = cstat.STAT_ID
				AND stats.GROUPING = 'Willpower'
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$willpower = $wpdb->get_row($sql);
	$value = isset($new[$courage]) ? $new[$courage] : 0;
	$data = array(
		'CHARACTER_ID' => $characterID,
		'STAT_ID'      => $wpid,
		'LEVEL'        => $value
	);
	if (isset($willpower->STAT_ID)) {
		// update
		$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_STAT",
			$data,
			array (
				'ID' => $willpower->STAT_ID
			)
		);
	} 
	else {
		// insert
		$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_STAT",
					$data,
					array ('%d', '%d', '%d')
				);
	}
	

	// Update CHARACTER_STAT with virtue ratings
	$sql = "SELECT cstat.STAT_ID, cstat.ID, stats.NAME
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat,
				" . VTM_TABLE_PREFIX . "STAT stats
			WHERE 
				stats.ID = cstat.STAT_ID
				AND stats.GROUPING = 'Virtue'
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$keys = $wpdb->get_col($sql);
	if (count($keys) > 0) {
		$vals = $wpdb->get_col($sql,1);
		$names = $wpdb->get_col($sql,2);
		$current = array_combine($keys, $vals);
	} else {
		$current = array();
	}

	foreach ($virtues as $attribute) {
		$attributeid = $attribute->ID;
		$value = isset($new[$attributeid]) ? $new[$attributeid] : 0;
		if ($attributeid == $statid1 || $attributeid == $statid2 || $attributeid == $courage) {
			$data = array(
				'CHARACTER_ID' => $characterID,
				'STAT_ID'      => $attributeid,
				'LEVEL'        => $value
			);
			if (isset($current[$attributeid])) {
				// update
				$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_STAT",
					$data,
					array (
						'ID' => $current[$attributeid]
					)
				);
			} 
			else {
				// insert
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_STAT",
							$data,
							array ('%d', '%d', '%d')
						);
			}
		}
	}
	
	// Delete anything no longer needed
	foreach ($current as $id => $value) {
	
		if (!isset($new[$id])) {
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT
					WHERE CHARACTER_ID = %s AND STAT_ID = %s";
			$sql = $wpdb->prepare($sql,$characterID,$id);
			//echo "<li>Delete $id ($sql)</li>";
			$wpdb->get_results($sql);
		}
	}
	
	return $characterID;
}
*/
function vtm_save_basic_info($characterID, $templateID) {
	global $wpdb;
		
	// New Player?
	if (isset($_POST['newplayer']) && $_POST['newplayer'] == 'on') {
	
		$playertypeid   = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "PLAYER_TYPE WHERE NAME = 'Player';");
		$playerstatusid = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "PLAYER_STATUS WHERE NAME = 'Active';");
	
		$dataarray = array (
			'NAME' 				=> $_POST['player'],
			'PLAYER_TYPE_ID' 	=> $playertypeid,
			'PLAYER_STATUS_ID' 	=> $playerstatusid
		);
		
		$wpdb->insert(VTM_TABLE_PREFIX . "PLAYER",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
					)
				);
		
		$playerid = $wpdb->insert_id;
		if ($playerid == 0) {
			echo "<p style='color:red'><b>Error:</b> Player could not be added</p>";
		} 
	
	} else {
		$playerid = vtm_get_player_id($_POST['player']);
	}
	
	// Character Data
	
	$config = vtm_getConfig();
	$domain 		= $config->HOME_DOMAIN_ID;
	$chartype	= $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARACTER_TYPE WHERE NAME = 'PC';");
	$charstatus	= $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARACTER_STATUS WHERE NAME = 'Alive';");
	$genstatus	= $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARGEN_STATUS WHERE NAME = 'In Progress';");
	$template	= $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE WHERE ID = %s;", $templateID));
	if (isset($_POST['pub_clan']) && $_POST['pub_clan'] > 0)
		$pub_clan = $_POST['pub_clan'];
	else
		$pub_clan = $_POST['priv_clan'];
	
	// Set defaults for new characters or get current values
	if ($characterID > 0) {
		$generationid = $wpdb->get_var($wpdb->prepare("SELECT GENERATION_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$path = $wpdb->get_var($wpdb->prepare("SELECT ROAD_OR_PATH_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$dob  = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_BIRTH FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$doe  = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_EMBRACE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$sire = $wpdb->get_var($wpdb->prepare("SELECT SIRE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	} else {
		$generationid = $config->DEFAULT_GENERATION_ID;
		$path		  = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE NAME = 'Humanity';");
		$dob = '';
		$doe = '';
		$sire = '';
	}
	
	$dataarray = array (
		'NAME'						=> $_POST['character'],
		'PUBLIC_CLAN_ID'			=> $pub_clan,
		'PRIVATE_CLAN_ID'			=> $_POST['priv_clan'],
		'GENERATION_ID'				=> $generationid,	// default from config, update later in backgrounds

		'DATE_OF_BIRTH'				=> $dob,				// Set later in ext backgrounds
		'DATE_OF_EMBRACE'			=> $doe,				// Set later in ext backgrounds
		'SIRE'						=> $sire,				// Set later in ext backgrounds
		'PLAYER_ID'					=> $playerid,

		'CHARACTER_TYPE_ID'			=> $chartype,		// player
		'CHARACTER_STATUS_ID'		=> $charstatus,		// active
		'CHARACTER_STATUS_COMMENT'	=> '',
		'ROAD_OR_PATH_ID'			=> $path,			// default from config

		'ROAD_OR_PATH_RATING'		=> 0,				// Set later in virtues
		'DOMAIN_ID'					=> $domain,			// default from config, update later in ext backgrounds
		'WORDPRESS_ID'				=> isset($_POST['wordpress_id']) ? $_POST['wordpress_id'] : '',
		'SECT_ID'					=> $_POST['sect'],

		'NATURE_ID'					=> isset($_POST['nature']) ? $_POST['nature'] : 0,
		'DEMEANOUR_ID'				=> isset($_POST['demeanour']) ? $_POST['demeanour'] : 0,
		'CHARGEN_STATUS_ID'			=> $genstatus,		// in progress
		'CONCEPT'					=> $_POST['concept'],

		'EMAIL'						=> $_POST['email'],
		'LAST_UPDATED'				=> Date('Y-m-d'),	// Today
		'VISIBLE'					=> 'Y',
		'DELETED'					=> 'N',

		'CHARGEN_TEMPLATE_ID'		=> $templateID
	);
	//print_r($dataarray);
	
	// new character or update character?
	if ($characterID > 0) {
		$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER",
					$dataarray,
					array (
						'ID' => $characterID
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated Character</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No changes made to character</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update character</p>";
		}
	} else {
		$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER",
					$dataarray,
					array (
						'%s', 		'%d', 		'%d', 		'%d',
						'%s', 		'%s', 		'%s', 		'%d', 
						'%d', 		'%d', 		'%s', 		'%d',
						'%d', 		'%d', 		'%s', 		'%d',
						'%d', 		'%d', 		'%d', 		'%s',
						'%s', 		'%s', 		'%s', 		'%s',
						'%d'
					)
				);
		$characterID = $wpdb->insert_id;
		if ($characterID == 0) {
			echo "<p style='color:red'><b>Error:</b> Character could not be added</p>";
		}
		
		vtm_email_new_character($_POST['email'], $characterID, $playerid, 
			$_POST['character'], $_POST['priv_clan'], $_POST['player'], $_POST['concept'], $template);
	}
	
	return $characterID;
} 

function vtm_get_chargen_characterID() {
	global $wpdb;
	global $current_user;
	
	// return -1: character reference is wrong
	// return 0: new character
	// return character ID

	if (isset($_POST['chargen_reference']) && $_POST['chargen_reference'] != '') {
		$charref = $_POST['chargen_reference'];
		if (strpos($charref,'-') && strpos($charref,'-', strpos($charref,'-') + 1)) {
			$ref = explode('-',$charref);
			$id   = $ref[0];
			$wpid = $ref[1];
			$pid  = $ref[2];
		
			$sql = "SELECT PLAYER_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s";
			$result = $wpdb->get_row($wpdb->prepare($sql, $id));
			
			if (count($result) == 0 || $result->PLAYER_ID != $pid)
				$id = -1;
			
			if (is_user_logged_in()) {
				get_currentuserinfo();
				if ($current_user->ID != $wpid && !vtm_isST())
					$id = -1;
			}
			elseif ($wpid != 0)
				$id = -1;
			
			// ensure character gen is in progress (and not approved)
			if ($id > 0) {
				$sql = "SELECT cgstat.NAME
						FROM
							" . VTM_TABLE_PREFIX . "CHARACTER cha,
							" . VTM_TABLE_PREFIX . "CHARGEN_STATUS cgstat
						WHERE
							cha.CHARGEN_STATUS_ID = cgstat.ID
							AND cha.ID = %s";
				$result = $wpdb->get_var($wpdb->prepare($sql, $id));
				
				if ($result == 'Approved')
					$id = -1;
			}
		} else {
			$id = -1;
		}
	}
	elseif (isset($_POST['characterID']) && $_POST['characterID'] > 0) {
		$id = $_POST['characterID'];
		if (is_user_logged_in()) {
			get_currentuserinfo();
			$wpid = $current_user->ID;
		} else {
			$wpid = 0;
		}
		$pid = vtm_get_player_id_from_characterID($id);
		//echo "<p>REF: $id-$wpid-$pid</p>";
	}
	else {
		$id = 0;
	}

	return $id;
}

function vtm_get_player_from_login($login) {
	global $wpdb;

	$sql = "SELECT players.ID, players.NAME 
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER characters,
				" . VTM_TABLE_PREFIX . "PLAYER players
			WHERE
				characters.PLAYER_ID = players.ID
				AND characters.wordpress_id = %s";
	$sql = $wpdb->prepare($sql, $login);
	$player = $wpdb->get_row($sql);
	
	return $player;
}

function vtm_get_player_id($playername, $guess = false) {
	global $wpdb;
	
	if (empty($playername))
		return;
	
	if ($guess) {
		$playername = "%$playername%";
		$test = 'LIKE';
	} else {
		$test = '=';
	}
	
	$sql = "SELECT ID FROM " . VTM_TABLE_PREFIX . "PLAYER WHERE NAME $test %s";
	$sql = $wpdb->prepare($sql, $playername);
	//echo "<p>SQL: $sql</p>";
	return $wpdb->get_var($sql);

}

function vtm_get_player_id_from_characterID($characterID) {
	global $wpdb;
	
	$sql = "SELECT players.ID 
		FROM 
			" . VTM_TABLE_PREFIX . "PLAYER players,
			" . VTM_TABLE_PREFIX . "CHARACTER charac
		WHERE
			players.ID = charac.PLAYER_ID
			AND charac.ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	return $wpdb->get_var($sql);

}

function vtm_get_templateid($characterID) {
	global $wpdb;
	
	if (isset($_POST['chargen_template'])) {
		if (isset($_POST['chargen_reference']) && $_POST['chargen_reference'] != "") {
			// look up what template the character was generated with
			$sql = "SELECT CHARGEN_TEMPLATE_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER
				WHERE ID = %s";
			$sql = $wpdb->prepare($sql, $characterID);
			$template = $wpdb->get_var($sql);
			//echo "Looked up template ID from character : $template<br />";
		} else {
			$template = $_POST['chargen_template'];
			//echo "Looked up template ID from Step 0 : $template<br />";
		}
	} else {
		$template = isset($_POST['selected_template']) ? $_POST['selected_template'] : "";
		//echo "Looked up template ID from last step : $template<br />";
	}
	
	return $template;
}
/*
function vtm_get_player_name($playerid) {
	global $wpdb;
		
	$sql = "SELECT NAME FROM " . VTM_TABLE_PREFIX . "PLAYER WHERE ID = %s";
	$sql = $wpdb->prepare($sql, $playerid);
	return $wpdb->get_var($sql);

}
function vtm_get_clan_name($clanid) {
	global $wpdb;
		
	$sql = "SELECT NAME FROM " . VTM_TABLE_PREFIX . "CLAN WHERE ID = %s";
	$sql = $wpdb->prepare($sql, $clanid);
	return $wpdb->get_var($sql);

}

function vtm_email_new_character($email, $characterID, $playerid, $name, $clanid, $player, $concept, $template) {
	global $current_user;

	if (is_user_logged_in()) {
		get_currentuserinfo();
		$userid       = $current_user->ID;
	} else {
		$userid = 0;
	}
	
	$ref = $characterID . '-' . $userid . '-' . $playerid;
	$clan = vtm_get_clan_name($clanid);
	$tag = get_option( 'vtm_chargen_emailtag' );
	$toname = get_option( 'vtm_chargen_email_from_name', 'The Storytellers');
	$toaddr = get_option( 'vtm_chargen_email_from_address', get_bloginfo('admin_email') );
	$url = add_query_arg('reference', $ref, vtm_get_stlink_url('viewCharGen', true));
	
	$subject   = "$tag New Character Created: $name";
	$headers[] = "From: \"$toname\" <$toaddr>";
	$headers[] = "Cc: \"$toname\" <$toaddr>";
	
	$userbody = "Hello $player,
	
Your new character has been created:
	
	* Reference: $ref
	* Character Name: $name
	* Clan: $clan
	* Template: $template
	* Concept: 
	
" . stripslashes($concept) . "
	
You can return to character generation by following this link: $url";
	
	echo "<pre>$userbody</pre>";
	
	$result = wp_mail($email, $subject, $userbody, $headers);
	
	if (!$result)
		echo "<p>Failed to send email. Character Ref: $ref</p>";
	
}
*/
function vtm_get_chargen_settings($templateID = 1) {
	global $wpdb;
	
	$sql = "SELECT NAME, VALUE FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_OPTIONS WHERE TEMPLATE_ID = %s";
	$sql = $wpdb->prepare($sql, $templateID);
	//echo "<p>SQL: $sql</p>";
	$result = $wpdb->get_results($sql);
	
	if (count($result) == 0)
		return array();
	
	$keys = $wpdb->get_col($sql);
	$vals = $wpdb->get_col($sql,1);
	
	//print_r($result);
	//print_r($keys);
	//print_r($vals);

	$settings = vtm_default_chargen_settings();
	$settings = array_merge($settings, array_combine($keys, $vals));
	
	return $settings;
	
}

function vtm_get_chargen_attributes($characterID = 0) {
	global $wpdb;
	
	$sql = "SELECT clans.NAME
			FROM
				" . VTM_TABLE_PREFIX . "CHARACTER chara,
				" . VTM_TABLE_PREFIX . "CLAN clans
			WHERE
				chara.PRIVATE_CLAN_ID = clans.ID
				AND chara.ID = %s";
	$clan = $wpdb->get_var($wpdb->prepare($sql, $characterID));
	
	$filter = "GROUPING = 'Physical' OR GROUPING = 'Social' OR GROUPING = 'Mental'";
	
	if (isset($clan) && ($clan == 'Nosferatu' || $clan == 'Samedi'))
		$filter = "NAME != 'Appearance' AND ($filter)";
	
	//echo "<p>Clan: $clan</p>";
	
	$sql = "SELECT ID, NAME, DESCRIPTION, GROUPING, SPECIALISATION_AT
			FROM " . VTM_TABLE_PREFIX . "STAT
			WHERE
				$filter
			ORDER BY ORDERING";
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql);
	
	return $results;

}
/*
function vtm_get_chargen_stats($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	$sql = "SELECT clans.NAME
			FROM
				" . VTM_TABLE_PREFIX . "CHARACTER chara,
				" . VTM_TABLE_PREFIX . "CLAN clans
			WHERE
				chara.PRIVATE_CLAN_ID = clans.ID
				AND chara.ID = %s";
	$clan = $wpdb->get_var($wpdb->prepare($sql, $characterID));
	
	$filter = "";
	if (isset($clan) && ($clan == 'Nosferatu' || $clan == 'Samedi'))
		$filter = "WHERE NAME != 'Appearance'";
	
	$sql = "SELECT NAME, ID, DESCRIPTION, GROUPING, SPECIALISATION_AT
			FROM " . VTM_TABLE_PREFIX . "STAT
			$filter
			ORDER BY ORDERING";
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql, $output_type);
	//print_r($results);
	return $results;

}
function vtm_get_chargen_virtues($characterID = 0) {
	global $wpdb;
			
	$sql = "SELECT ID, NAME, DESCRIPTION, GROUPING, SPECIALISATION_AT
			FROM " . VTM_TABLE_PREFIX . "STAT
			WHERE
				GROUPING = 'Virtue'
			ORDER BY ORDERING";
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql, OBJECT_K);
	
	//print_r($results);
	
	return $results;

}
*/
function vtm_get_chargen_disciplines($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT disc.NAME, disc.ID, disc.DESCRIPTION, 
				IF(ISNULL(clandisc.DISCIPLINE_ID),'Non-Clan Discipline','Clan Discipline') as GROUPING
			FROM " . VTM_TABLE_PREFIX . "DISCIPLINE disc
				LEFT JOIN (
					SELECT DISCIPLINE_ID, CLAN_ID
					FROM
						" . VTM_TABLE_PREFIX . "CLAN clans,
						" . VTM_TABLE_PREFIX . "CLAN_DISCIPLINE cd,
						" . VTM_TABLE_PREFIX . "CHARACTER chars
					WHERE
						chars.ID = %s
						AND chars.PRIVATE_CLAN_ID = clans.ID
						AND cd.CLAN_ID = clans.ID
				) as clandisc
				ON 
					clandisc.DISCIPLINE_ID = disc.id
			WHERE
				disc.VISIBLE = 'Y'
				OR NOT(ISNULL(clandisc.DISCIPLINE_ID))
			ORDER BY GROUPING, NAME";
	$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql, $output_type);
	
	
	return $results;

}
/*
function vtm_get_chargen_backgrounds($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT bg.NAME, bg.ID, bg.DESCRIPTION
			FROM " . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE
				bg.VISIBLE = 'Y'
			ORDER BY NAME";
	//$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql, $output_type);
	
	
	return $results;

}
function vtm_get_chargen_merits($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT item.NAME, item.ID, item.DESCRIPTION
			FROM " . VTM_TABLE_PREFIX . "MERIT item
			WHERE
				item.VISIBLE = 'Y'
			ORDER BY NAME";
	//$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql, $output_type);
	
	
	return $results;

}
function vtm_get_chargen_paths($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT path.NAME, path.ID, path.DESCRIPTION, disc.NAME as GROUPING
			FROM 
				" . VTM_TABLE_PREFIX . "PATH path,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE
				path.VISIBLE = 'Y'
				AND path.DISCIPLINE_ID = disc.ID
			ORDER BY GROUPING, NAME";
	//$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql, $output_type);
	
	
	return $results;

}
*/
function vtm_get_chargen_abilities($characterID = 0, $showsecondary = 0, $output_type = OBJECT) {
	global $wpdb;
	
	if ($showsecondary)
		$filter = "";
	else
		$filter = "AND (GROUPING = 'Talents' OR GROUPING = 'Skills' OR GROUPING = 'Knowledges')";
	
	$sql = "SELECT NAME, ID, DESCRIPTION, GROUPING, SPECIALISATION_AT, 
				CASE GROUPING WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ORDERING,
				MULTIPLE
			FROM " . VTM_TABLE_PREFIX . "SKILL
			WHERE
				VISIBLE = 'Y'
				$filter
			ORDER BY ORDERING DESC, NAME";
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql, $output_type);
	
	return $results;

}
/*
function vtm_get_chargen_roads() {
	global $wpdb;

	$sql = "SELECT ID, NAME
			FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH
			WHERE VISIBLE = 'Y'
			ORDER BY NAME";

	$roadsOrPaths = $wpdb->get_results($sql);
	return $roadsOrPaths;
}
*/
function vtm_render_dot_select($type, $itemid, $current, $pending, $free, $max) {

	$output = "";
	$fulldoturl = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$doturl = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl   = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	
	if ($pending) {
		$output .= "<input type='hidden' name='" . $type . "[" . $itemid . "]' value='$current' />";
	}
	$output .= "<fieldset class='dotselect'>";
	
	for ($index = $max ; $index > 0 ; $index--) {
		$radioid = "dot_{$type}_{$itemid}_{$index}";
		//echo "<li>$radioid: current:$current / index:$index / free:$free (" . ($index - $free) . ")</li>";
		if ($pending) {
			if ($index <= $free)
				$output .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
			elseif ($index <= $current )
				$output .= "<img src='$doturl' alt='*' id='$radioid' />";
			elseif ($index <= $pending)
				$output .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
			else
				$output .= "<img src='$emptydoturl' alt='*' id='$radioid' />";
		} else {
			$output .= "<input type='radio' id='$radioid' name='" . $type . "[" . $itemid . "]' value='$index' ";
			$output .= checked($current, $index, false);
			$output .= " /><label for='$radioid' title='$index'";
			
			if ($index <= $free)
				$output .= " class='freedot'";
			
			$output .= ">&nbsp;</label>\n";
		}
	}
	
	if ($free == 0 && $pending == 0) {
		$radioid = "dot_{$type}_{$itemid}_clear";
		$output .= "<input type='radio' id='$radioid' name='" . $type . "[" . $itemid . "]' value='0' ";
		$output .= checked($current, 0, false);
		$output .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
	}
	
	$output .= "</fieldset>\n";
	
	
	return $output;

}

function vtm_render_pst_select($name, $selected) {

	$output = "<select name='$name' autocomplete='off'>\n";
	$output .= "<option value='-1'>[Select]</option>\n";
	$output .= "<option value='1' " . selected($selected, 1, false) . ">Primary</option>\n";
	$output .= "<option value='2' " . selected($selected, 2, false) . ">Secondary</option>\n";
	$output .= "<option value='3' " . selected($selected, 3, false) . ">Tertiary</option>\n";
	$output .= "</select>\n";
	
	return $output;
}
/*
function vtm_render_freebie_stats($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output = "";
	$rowoutput = "";
	$max2display = 5;
	$columns = 3;
	$fulldoturl = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl   = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$doturl = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );
	
	// PENDING_FREEBIE_SPEND
	//	characterID
	//	chartable		= CHARACTER_STAT
	//	chartableid		= ID of entry in CHARACTER_STAT
	//	level_from		= Level stat is currently at
	//	level_to		= Level stat is going to
	//	amount			= cost
	//	itemtable		= STAT
	//	itemname		= Name of Stat, e.g. Strength
	//	itemid			= ID of entry in STAT

	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_sanitize_array(vtm_get_freebie_costs('STAT'));

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_get_current_stats($characterID);
	
	// Current spent
	$current_stat = vtm_get_pending_freebies('STAT', 'freebie_stat', $characterID);

	// Current bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('STAT', 'xp_stat', $characterID);  // name => value

	
	//print_r($items);
	
	if (count($items) > 0) {
		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $item) {
			
			$tmp_max2display = $max2display;
			switch ($item->name) {
				case 'Willpower':    
					$tmp_max2display = 10;
					$maxRating = 10;
					break;
				case 'Conscience':   
					$tmp_max2display = 5;
					$maxRating = 5;
					break;
				case 'Conviction':
					$tmp_max2display = 5;
					$maxRating = 5;
					break;
				case 'Self Control': 
					$tmp_max2display = 5;
					$maxRating = 5;
					break;
				case 'Courage':      
					$tmp_max2display = 5;
					$maxRating = 5;
					break;
				case 'Instinct':     
					$tmp_max2display = 5;
					$maxRating = 5;
					break;
			}
			$colspan = 2 + $tmp_max2display;
			
			// start column / new column
			if (isset($item->grp)) {
				if ($grp != $item->grp) {
					$grpcount++;
					if (empty($grp)) {
						$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					} 
					elseif ($col == $columns) {
						$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col = 1;
					}
					else {
						$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					}
					$grp = $item->grp;
				}
			}

			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
			$rowoutput .= "</td></tr>\n";
			
			//dots row
			$key = sanitize_key($item->name);
			$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
			$rowoutput .= "<fieldset class='dotselect'>";
			for ($i=$tmp_max2display;$i>=1;$i--) {
				$radioid = "dot_{$key}_{$item->itemid}_{$i}";
				$current = isset($current_stat[$key]) ? $current_stat[$key] : 0;
				
				if ($item->level_from >= $i)
					$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
				elseif (isset($pendingxp[$key]) && $pendingxp[$key] != 0) {
					if ($current >= $i)
						$rowoutput .= "<img src='$doturl' alt='*' id='$radioid' />";
					elseif ($pendingxp[$key] >= $i)
						$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
					else
						$rowoutput .= "<img src='$emptydoturl' alt='*' id='$radioid' />";
				} else {
					if (isset($freebiecosts[$key][$item->level_from][$i])) {
						$cost = $freebiecosts[$key][$item->level_from][$i];
						$rowoutput .= "<input type='radio' id='$radioid' name='freebie_stat[$key]' value='$i' ";
						$rowoutput .= checked($current, $i, false);
						$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
						$rowoutput .= ">&nbsp;</label>\n";
					}
					else {
						$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
					}
				}
			}
			$radioid = "dot_{$key}_{$item->itemid}_clear";
			$rowoutput .= "<input type='radio' id='$radioid' name='freebie_stat[$key]' value='0' ";
			//$rowoutput .= checked($current, 0, false);
			$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
			$rowoutput .= "</fieldset></td></tr>\n";
		
		}
	
	}
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}
function vtm_render_freebie_skills($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output      = "";
	$rowoutput   = "";
	$max2display = 5;
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl   = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$doturl = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );
	
	// PENDING_FREEBIE_SPEND
	//	characterID
	//	chartable		= CHARACTER_STAT
	//	chartableid		= ID of entry in CHARACTER_STAT
	//	level_from		= Level stat is currently at
	//	level_to		= Level stat is going to
	//	amount			= cost
	//	itemtable		= STAT
	//	itemname		= Name of Stat, e.g. Strength
	//	itemid			= ID of entry in STAT

	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_get_freebie_costs('SKILL');

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_get_current_skills($characterID);
	
	// Current spent
	$currentpending = vtm_get_pending_freebies('SKILL', 'freebie_skill', $characterID);
	
	// Current bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('SKILL', 'xp_skill', $characterID);  // name => value

	//print_r($currentpending);
	
	if (count($items) > 0) {
		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $item) {
		
			$loop = ($item->multiple == 'Y') ? 5 : 1;
			
			$tmp_max2display = $max2display;
			$colspan = 2 + $tmp_max2display;
			
			for ($j = 1 ; $j <= $loop ; $j++) {
				$skillname = ($item->multiple == 'Y') ? $item->name . "_" . $j : $item->name;
			
				// start column / new column
				if (isset($item->grp)) {
					if ($grp != $item->grp) {
						$grpcount++;
						if (empty($grp)) {
							$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
							$col++;
						} 
						elseif ($col == $columns) {
							$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
							$col = 1;
						}
						else {
							$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
							$col++;
						}
						$grp = $item->grp;
					}
				}

				// Hidden fields
				$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
				$rowoutput .= "</td></tr>\n";
				
				//dots row
				$key = sanitize_key($skillname);
				$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
				$rowoutput .= "<fieldset class='dotselect'>";
				for ($i=$tmp_max2display;$i>=1;$i--) {
					$radioid = "dot_{$key}_{$j}_{$item->itemid}_{$i}";
					$current = isset($currentpending[$key]) ? $currentpending[$key] : 0;
					
					if ($item->level_from >= $i)
						$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
					
					elseif (isset($pendingxp[$key]) && $pendingxp[$key] != 0) {
						if ($current >= $i)
							$rowoutput .= "<img src='$doturl' alt='*' id='$radioid' />";
						elseif ($pendingxp[$key] >= $i)
							$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
						else
							$rowoutput .= "<img src='$emptydoturl' alt='*' id='$radioid' />";
					} else {
						if (isset($freebiecosts[$item->name][$item->level_from][$i])) {
							$cost = $freebiecosts[$item->name][$item->level_from][$i];
							$rowoutput .= "<input type='radio' id='$radioid' name='freebie_skill[$key]' value='$i' ";
							$rowoutput .= checked($current, $i, false);
							$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
							$rowoutput .= ">&nbsp;</label>\n";
						}
						elseif ($current >= $i)
							$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
						else {
							$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
							//$rowoutput .= "itemname: {$item->name}, skillname: $skillname, levelfrom: {$item->level_from} i: $i";
						}
					}
				}
				$radioid = "dot_{$key}_{$item->itemid}_clear";
				$rowoutput .= "<input type='radio' id='$radioid' name='freebie_skill[$key]' value='0' ";
				//$rowoutput .= checked($current, 0, false);
				$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
				$rowoutput .= "</fieldset></td></tr>\n";
			}
		}
	
	}
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}
function vtm_render_freebie_disciplines($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output      = "";
	$rowoutput   = "";
	$max2display = 5;
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl   = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$doturl = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );


	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_sanitize_array(vtm_get_freebie_costs('DISCIPLINE', $characterID));

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_get_current_disciplines($characterID);
	
	// Current spent
	$currentpending = vtm_get_pending_freebies('DISCIPLINE', 'freebie_discipline', $characterID);
	
	// Current bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('DISCIPLINE', 'xp_discipline', $characterID);  // name => value

	//print_r($freebiecosts);
	
	if (count($items) > 0) {
		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $item) {
					
			$tmp_max2display = $max2display;
			$colspan = 2 + $tmp_max2display;
			
			$levelfrom = isset($item->level_from) ? $item->level_from : 0;
		
			// start column / new column
			if (isset($item->grp)) {
				if ($grp != $item->grp) {
					$grpcount++;
					if (empty($grp)) {
						$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					} 
					elseif ($col == $columns) {
						$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col = 1;
					}
					else {
						$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					}
					$grp = $item->grp;
				}
			}

			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
			$rowoutput .= "</td></tr>\n";
			
			//dots row
			$key = sanitize_key($item->name);
			$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
			$rowoutput .= "<fieldset class='dotselect'>";
			for ($i=$tmp_max2display;$i>=1;$i--) {
				$radioid = "dot_{$key}_{$item->itemid}_{$i}";
				$current = isset($currentpending[$key]) ? $currentpending[$key] : 0;
				
				if ($levelfrom >= $i)
					$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
				elseif (isset($pendingxp[$key]) && $pendingxp[$key] != 0) {
					if ($current >= $i)
						$rowoutput .= "<img src='$doturl' alt='*' id='$radioid' />";
					elseif ($pendingxp[$key] >= $i)
						$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
					else
						$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
				} else {
					if (isset($freebiecosts[$key][$levelfrom][$i])) {
						$cost = $freebiecosts[$key][$levelfrom][$i];
						$rowoutput .= "<input type='radio' id='$radioid' name='freebie_discipline[$key]' value='$i' ";
						$rowoutput .= checked($current, $i, false);
						$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
						$rowoutput .= ">&nbsp;</label>\n";
					}
					else {
						$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
					}
				}
			}
			$radioid = "dot_{$key}_{$item->itemid}_clear";
			$rowoutput .= "<input type='radio' id='$radioid' name='freebie_discipline[$key]' value='0' ";
			$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
			$rowoutput .= "</fieldset></td></tr>\n";
		}
	
	}
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}
function vtm_render_xp_disciplines($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output      = "";
	$rowoutput   = "";
	$max2display = 5;
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );


	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$xpcosts = vtm_get_chargen_xp_costs('DISCIPLINE', $characterID);
	//print_r($xpcosts);

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_sanitize_array(vtm_get_current_disciplines($characterID, OBJECT_K));
	
	// Current Freebie spent
	$freebies = vtm_get_pending_freebies('DISCIPLINE', 'freebie_discipline', $characterID);
	
	// Get currently selected
	$pending = vtm_get_pending_chargen_xp('DISCIPLINE', 'xp_discipline', $characterID);
	//print_r($currentpending);
	
	if (count($items) > 0) {
		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $key => $item) {
					
			$tmp_max2display = $max2display;
			$colspan = 2 + $tmp_max2display;
			
			$item->level_from = isset($item->level_from) ? $item->level_from : 0;
		
			// start column / new column
			if (isset($item->grp)) {
				if ($grp != $item->grp) {
					$grpcount++;
					if (empty($grp)) {
						$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					} 
					elseif ($col == $columns) {
						$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col = 1;
					}
					else {
						$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					}
					$grp = $item->grp;
				}
			}

			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
			$rowoutput .= "</td></tr>\n";
			
			//dots row
			$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
			$rowoutput .= "<fieldset class='dotselect'>";
			for ($i=$tmp_max2display;$i>=1;$i--) {
				$radioid = "dot_{$key}_{$item->itemid}_{$i}";
				$current = isset($pending[$key]) ? $pending[$key] : 0;
				$levelfrom = isset($freebies[$key]) ? $freebies[$key] : $item->level_from;
				
				if ($item->level_from >= $i)
					$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
				elseif (isset($freebies[$key]) && $freebies[$key] >= $i)
					$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
				elseif (isset($xpcosts[$key][$levelfrom][$i])) {
					$cost = $xpcosts[$key][$levelfrom][$i];
					$rowoutput .= "<input type='radio' id='$radioid' name='xp_discipline[$key]' value='$i' ";
					$rowoutput .= checked($current, $i, false);
					$rowoutput .= " /><label for='$radioid' title='Level $i ($cost xp)'";
					$rowoutput .= ">&nbsp;</label>\n";
				}
				else {
					$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
				}
			}
			$radioid = "dot_{$key}_{$item->itemid}_clear";
			$rowoutput .= "<input type='radio' id='$radioid' name='xp_discipline[$key]' value='0' ";
			$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
			$rowoutput .= "</fieldset></td></tr>\n";
		}
	
	}
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}
function vtm_render_freebie_paths($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output      = "";
	$rowoutput   = "";
	$max2display = 5;
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl   = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$doturl = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );

	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_sanitize_array(vtm_get_freebie_costs('PATH', $characterID));

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_get_current_paths($characterID);
	
	// Current spent
	$currentpending = vtm_get_pending_freebies('PATH', 'freebie_path', $characterID);
	
	// Current bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('PATH', 'xp_path', $characterID);  // name => value

	//print_r($currentpending);
	
	if (count($items) > 0) {
		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $item) {
					
			$tmp_max2display = $max2display;
			$colspan = 2 + $tmp_max2display;
			
			$levelfrom = isset($item->level_from) ? $item->level_from : 0;
		
			// start column / new column
			if (isset($item->grp)) {
				if ($grp != $item->grp) {
					$grpcount++;
					if (empty($grp)) {
						$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					} 
					elseif ($col == $columns) {
						$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col = 1;
					}
					else {
						$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					}
					$grp = $item->grp;
				}
			}

			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
			$rowoutput .= "</td></tr>\n";
			
			//dots row
			$key = sanitize_key($item->name);
			$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
			$rowoutput .= "<fieldset class='dotselect'>";
			for ($i=$tmp_max2display;$i>=1;$i--) {
				$radioid = "dot_{$key}_{$item->itemid}_{$i}";
				$current = isset($currentpending[$key]) ? $currentpending[$key] : 0;
				
				if ($levelfrom >= $i)
					$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
				elseif (isset($pendingxp[$key]) && $pendingxp[$key] != 0) {
					if ($current >= $i)
						$rowoutput .= "<img src='$doturl' alt='*' id='$radioid' />";
					elseif ($pendingxp[$key] >= $i)
						$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
					else
						$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
				} else {
					if (isset($freebiecosts[$key][$levelfrom][$i])) {
						$cost = $freebiecosts[$key][$levelfrom][$i];
						$rowoutput .= "<input type='radio' id='$radioid' name='freebie_path[$key]' value='$i' ";
						$rowoutput .= checked($current, $i, false);
						$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
						$rowoutput .= ">&nbsp;</label>\n";
					}
					else {
						$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
					}
				}
			}
			$radioid = "dot_{$key}_{$item->itemid}_clear";
			$rowoutput .= "<input type='radio' id='$radioid' name='freebie_path[$key]' value='0' ";
			$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
			$rowoutput .= "</fieldset></td></tr>\n";
		}
	
	} 
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}
function vtm_render_freebie_backgrounds($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output      = "";
	$rowoutput   = "";
	$max2display = 5;
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$dotstobuy   = 0;

	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_get_freebie_costs('BACKGROUND', $characterID);

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_get_current_backgrounds($characterID);
	
	// Current spent
	$currentpending = vtm_get_pending_freebies('BACKGROUND', 'freebie_background', $characterID);
	
	//print_r($currentpending);
	
	if (count($items) > 0) {
		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $item) {
					
			$tmp_max2display = $max2display;
			$colspan = 2 + $tmp_max2display;
			
			$levelfrom = isset($item->level_from) ? $item->level_from : 0;
		
			// start column / new column
			if (isset($item->grp)) {
				if ($grp != $item->grp) {
					$grpcount++;
					if (empty($grp)) {
						$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					} 
					elseif ($col == $columns) {
						$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col = 1;
					}
					else {
						$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
						$col++;
					}
					$grp = $item->grp;
				}
			}

			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
			$rowoutput .= "</td></tr>\n";
			
			//dots row
			$key = sanitize_key($item->name);
			$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
			$rowoutput .= "<fieldset class='dotselect'>";
			for ($i=$tmp_max2display;$i>=1;$i--) {
				$radioid = "dot_{$key}_{$item->itemid}_{$i}";
				$current = isset($currentpending[$key]) ? $currentpending[$key] : 0;
				
				if ($levelfrom >= $i)
					$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
				elseif (isset($freebiecosts[$item->name][$levelfrom][$i])) {
					$cost = $freebiecosts[$item->name][$levelfrom][$i];
					$rowoutput .= "<input type='radio' id='$radioid' name='freebie_background[$key]' value='$i' ";
					$rowoutput .= checked($current, $i, false);
					$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
					$rowoutput .= ">&nbsp;</label>\n";
					$dotstobuy++;
				}
				else {
					$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
					$rowoutput .= "itemname: {$item->name}, name: $item->name, levelfrom: {$item->level_from} i: $i";
				}
			}
			$radioid = "dot_{$key}_{$item->itemid}_clear";
			$rowoutput .= "<input type='radio' id='$radioid' name='freebie_background[$key]' value='0' ";
			//$rowoutput .= checked($current, 0, false);
			$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
			$rowoutput .= "</fieldset></td></tr>\n";
		}
	
	}
	
	if ($rowoutput != "" & $dotstobuy > 0)
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}
function vtm_render_freebie_merits($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output      = "";
	$rowoutput   = "";
	$columns     = 3;
	//$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	//$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$dotstobuy   = 0;

	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_get_freebie_costs('MERIT', $characterID);

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_get_current_merits($characterID);
	
	// Current spent
	$currentpending = vtm_get_pending_freebies('MERIT', 'freebie_merit', $characterID);
	
	//print_r($currentpending);
	
	if (count($items) > 0) {
 		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $item) {
								
			$loop      = ($item->multiple == 'Y') ? 4 : 1;
			$levelfrom = isset($item->level_from) ? $item->level_from : 0;
			$cost      = $freebiecosts[$item->name][0][1];
		
			for ($j = 1 ; $j <= $loop ; $j++) {
				$meritname = ($item->multiple == 'Y') ? $item->name . "_" . $j : $item->name;
				$key = sanitize_key($meritname);
				$current   = isset($currentpending[$key]) ? $currentpending[$key] : 0;
			
				// start column / new column
				if (isset($item->grp)) {
					if ($grp != $item->grp) {
						$grpcount++;
						if (empty($grp)) {
							$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th>{$item->grp}</th></tr>\n";
							$col++;
						} 
						elseif ($col == $columns) {
							$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th>{$item->grp}</th></tr>\n";
							$col = 1;
						}
						else {
							$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th>{$item->grp}</th></tr>\n";
							$col++;
						}
						$grp = $item->grp;
					}
				}

				// Hidden fields
				//$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
				//$rowoutput .= "</td></tr>\n";
				
				//dots row
				$cbid = "cb_{$j}_{$item->itemid}";
				$rowoutput .= "<tr><td><span>";
				$rowoutput .= "<input type='checkbox' name='freebie_merit[" . $key . "]' id='$cbid' value='$cost' ";
				$rowoutput .= checked($current, $cost, false);
				$rowoutput .= "/>\n";
				$rowoutput .= "<label for='$cbid'>" . stripslashes($item->name) . " ($cost)</label>\n";
				$rowoutput .= "</span></td></tr>\n";
			}
		}

	} 
	
	if ($rowoutput != "")
		$output .= "<table id='merit_freebie_table'>$rowoutput</table></td></tr></table>\n";

	return $output;

}
function vtm_get_freebie_costs($type, $characterID = 0) {
	global $wpdb;

	$outdata = array();
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	
	if ($type == 'DISCIPLINE') {
	
		// Get list of disciplines
		$sql = "SELECT ID, NAME, NOT(ISNULL(clandisc.DISCIPLINE_ID)) as ISCLAN					
				FROM 
					" . VTM_TABLE_PREFIX . "DISCIPLINE disc
					LEFT JOIN
						(SELECT DISCIPLINE_ID, CLAN_ID
						FROM
							" . VTM_TABLE_PREFIX . "CLAN clans,
							" . VTM_TABLE_PREFIX . "CLAN_DISCIPLINE cd,
							" . VTM_TABLE_PREFIX . "CHARACTER chars
						WHERE
							chars.ID = %s
							AND chars.PRIVATE_CLAN_ID = clans.ID
							AND cd.CLAN_ID = clans.ID
						) as clandisc
					ON
						clandisc.DISCIPLINE_ID = disc.id";
		$sql = $wpdb->prepare($sql, $characterID);
		$items = $wpdb->get_results($sql, OBJECT_K);
		
		// Get clan and non-clan cost model IDs
		$sql = "SELECT CLAN_COST_MODEL_ID, NONCLAN_COST_MODEL_ID
				FROM
					" . VTM_TABLE_PREFIX . "CLAN clans,
					" . VTM_TABLE_PREFIX . "CHARACTER cha
				WHERE
					cha.PRIVATE_CLAN_ID = clans.ID
					AND cha.ID = %s";
		$costmodels = $wpdb->get_row($wpdb->prepare($sql, $characterID));

		// Get clan and non-clan costs
		$sql = "SELECT 
					steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.FREEBIE_COST
				FROM
					" . VTM_TABLE_PREFIX . "COST_MODEL_STEP steps,
					" . VTM_TABLE_PREFIX . "COST_MODEL models
				WHERE
					steps.COST_MODEL_ID = models.ID
					AND models.ID = %s
				ORDER BY
					steps.CURRENT_VALUE";
		$data    = $wpdb->get_results($wpdb->prepare($sql, $costmodels->CLAN_COST_MODEL_ID), ARRAY_A);
		$clancost = array();
		for ($i = 0 ; $i < 10 ; $i++) {
			$from = $data[$i]['CURRENT_VALUE'];
			$to   = $data[$i]['NEXT_VALUE'];
			$cost = 0;
			
			while ($from != $to && $to <= 10) {
				if ($data[$from]['FREEBIE_COST'] != 0) {
					$cost += $data[$from]['FREEBIE_COST'];
					$clancost[$i][$to] = $cost;
				}
				$from = $to;
				$to   = $data[$from]['NEXT_VALUE'];
				
			}
		
		}
		$data = $wpdb->get_results($wpdb->prepare($sql, $costmodels->NONCLAN_COST_MODEL_ID), ARRAY_A);
		$nonclancost = array();
		for ($i = 0 ; $i < 10 ; $i++) {
			$from = $data[$i]['CURRENT_VALUE'];
			$to   = $data[$i]['NEXT_VALUE'];
			$cost = 0;
			
			while ($from != $to && $to <= 10) {
				if ($data[$from]['FREEBIE_COST'] != 0) {
					$cost += $data[$from]['FREEBIE_COST'];
					$nonclancost[$i][$to] = $cost;
				}
				$from = $to;
				$to   = $data[$from]['NEXT_VALUE'];
				
			}
		}
		//echo "<pre>";
		//print_r($nonclancost);
		//echo "</pre>";
		
		foreach ($items as $item) {
			if ($item->ISCLAN) {
				$outdata[$item->NAME] = $clancost;
			} else {
				$outdata[$item->NAME] = $nonclancost;
			}
		}
	} 
	elseif ($type == "MERIT") {
		$sql = "SELECT ID, NAME, COST FROM " . VTM_TABLE_PREFIX . "MERIT ORDER BY ID";
		$items = $wpdb->get_results($sql);
		
		foreach ($items as $item) {
			$outdata[$item->NAME][0][1] = $item->COST;
		
		}
	
	}
	else {
	
		$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . $type . " ORDER BY ID";
		$items = $wpdb->get_results($sql, OBJECT_K);
		
		foreach ($items as $item) {
		
			$sql = "SELECT 
						steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.FREEBIE_COST
					FROM
						" . VTM_TABLE_PREFIX . $type . " itemtable,
						" . VTM_TABLE_PREFIX . "COST_MODEL_STEP steps,
						" . VTM_TABLE_PREFIX . "COST_MODEL models
					WHERE
						itemtable.COST_MODEL_ID = models.ID
						AND steps.COST_MODEL_ID = models.ID
						AND itemtable.ID = %s
					ORDER BY
						itemtable.ID, steps.CURRENT_VALUE";
			$sql = $wpdb->prepare($sql, $item->ID);
			$data    = $wpdb->get_results($sql, ARRAY_A);
			
			if (count($data) > 0) {
				for ($i = 0 ; $i < 10 ; $i++) {
					$from = $data[$i]['CURRENT_VALUE'];
					$to   = $data[$i]['NEXT_VALUE'];
					$cost = 0;
					
					while ($from != $to && $to <= 10) {
						if ($data[$from]['FREEBIE_COST'] != 0) {
							$cost += $data[$from]['FREEBIE_COST'];
							$outdata[$item->NAME][$i][$to] = $cost;
						}
						$from = $to;
						$to   = $data[$from]['NEXT_VALUE'];
						
						//echo "<li>name:{$item->NAME}, i: $i, from: $from, to: $to</li>";
					}
				
				}
			} else {
				echo "<li>ERROR: Issue with cost model for {$item->NAME}. Please ask the admin to check and resave the cost model</li>";
			}
		}
	
	}
	
	// if ($type == "PATH") {
		// print_r($data);
		// echo "<p>($type / $characterID) SQL: $sql</p>";
		// echo "<pre>";
		// print_r($outdata);
		// echo "</pre>";
	// }

	return $outdata;
}
function vtm_get_chargen_xp_costs($type, $characterID = 0) {
	global $wpdb;

	$outdata = array();
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	
	if ($type == 'DISCIPLINE') {

		// Get list of disciplines
		$sql = "SELECT ID, NAME, NOT(ISNULL(clandisc.DISCIPLINE_ID)) as ISCLAN					
				FROM 
					" . VTM_TABLE_PREFIX . "DISCIPLINE disc
					LEFT JOIN
						(SELECT DISCIPLINE_ID, CLAN_ID
						FROM
							" . VTM_TABLE_PREFIX . "CLAN clans,
							" . VTM_TABLE_PREFIX . "CLAN_DISCIPLINE cd,
							" . VTM_TABLE_PREFIX . "CHARACTER chars
						WHERE
							chars.ID = %s
							AND chars.PRIVATE_CLAN_ID = clans.ID
							AND cd.CLAN_ID = clans.ID
						) as clandisc
					ON
						clandisc.DISCIPLINE_ID = disc.id";
		$sql = $wpdb->prepare($sql, $characterID);
		$items = $wpdb->get_results($sql, OBJECT_K);
		
		// Get clan and non-clan cost model IDs
		$sql = "SELECT CLAN_COST_MODEL_ID, NONCLAN_COST_MODEL_ID
				FROM
					" . VTM_TABLE_PREFIX . "CLAN clans,
					" . VTM_TABLE_PREFIX . "CHARACTER cha
				WHERE
					cha.PRIVATE_CLAN_ID = clans.ID
					AND cha.ID = %s";
		$costmodels = $wpdb->get_row($wpdb->prepare($sql, $characterID));

		// Get clan and non-clan costs
		$sql = "SELECT 
					steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.XP_COST
				FROM
					" . VTM_TABLE_PREFIX . "COST_MODEL_STEP steps,
					" . VTM_TABLE_PREFIX . "COST_MODEL models
				WHERE
					steps.COST_MODEL_ID = models.ID
					AND models.ID = %s
				ORDER BY
					steps.CURRENT_VALUE";
		$data    = $wpdb->get_results($wpdb->prepare($sql, $costmodels->CLAN_COST_MODEL_ID), ARRAY_A);
		$clancost = array();
		for ($i = 0 ; $i < 10 ; $i++) {
			$from = $data[$i]['CURRENT_VALUE'];
			$to   = $data[$i]['NEXT_VALUE'];
			$cost = 0;
			
			while ($from != $to && $to <= 10) {
				if ($data[$from]['XP_COST'] != 0) {
					$cost += $data[$from]['XP_COST'];
					$clancost[$i][$to] = $cost;
				}
				$from = $to;
				$to   = $data[$from]['NEXT_VALUE'];
				
			}
		
		}
		$data = $wpdb->get_results($wpdb->prepare($sql, $costmodels->NONCLAN_COST_MODEL_ID), ARRAY_A);
		$nonclancost = array();
		for ($i = 0 ; $i < 10 ; $i++) {
			$from = $data[$i]['CURRENT_VALUE'];
			$to   = $data[$i]['NEXT_VALUE'];
			$cost = 0;
			
			while ($from != $to && $to <= 10) {
				if ($data[$from]['XP_COST'] != 0) {
					$cost += $data[$from]['XP_COST'];
					$nonclancost[$i][$to] = $cost;
				}
				$from = $to;
				$to   = $data[$from]['NEXT_VALUE'];
				
			}
		}
		//echo "<pre>";
		//print_r($nonclancost);
		//echo "</pre>";
		
		foreach ($items as $item) {
			$name = sanitize_key($item->NAME);
			if ($item->ISCLAN) {
				$outdata[$name] = $clancost;
			} else {
				$outdata[$name] = $nonclancost;
			}
		}
		
	} 
	elseif ($type == "MERIT") {
		$sql = "SELECT ID, NAME, XP_COST FROM " . VTM_TABLE_PREFIX . "MERIT ORDER BY ID";
		$items = $wpdb->get_results($sql);
		
		foreach ($items as $item) {
			$name = sanitize_key($item->NAME);
			$outdata[$name][0][1] = $item->XP_COST;
		}
	}
	else {
	
		$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . $type . " ORDER BY ID";
		$items = $wpdb->get_results($sql, OBJECT_K);
		
		foreach ($items as $item) {
		
			$name = sanitize_key($item->NAME);
		
			$sql = "SELECT 
						steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.XP_COST
					FROM
						" . VTM_TABLE_PREFIX . $type . " itemtable,
						" . VTM_TABLE_PREFIX . "COST_MODEL_STEP steps,
						" . VTM_TABLE_PREFIX . "COST_MODEL models
					WHERE
						itemtable.COST_MODEL_ID = models.ID
						AND steps.COST_MODEL_ID = models.ID
						AND itemtable.ID = %s
					ORDER BY
						itemtable.ID, steps.CURRENT_VALUE";
			$sql = $wpdb->prepare($sql, $item->ID);
			$data    = $wpdb->get_results($sql, ARRAY_A);
			
			if (count($data) > 0) {
				for ($i = 0 ; $i < 10 ; $i++) {
					$from = $data[$i]['CURRENT_VALUE'];
					$to   = $data[$i]['NEXT_VALUE'];
					$cost = 0;
					
					while ($from != $to && $to <= 10) {
						if ($data[$from]['XP_COST'] != 0) {
							$cost += $data[$from]['XP_COST'];
							$outdata[$name][$i][$to] = $cost;
						}
						$from = $to;
						$to   = $data[$from]['NEXT_VALUE'];
						
						//echo "<li>name:{$item->NAME}, i: $i, from: $from, to: $to</li>";
					}
				
				}
			} else {
				echo "<li>ERROR: Issue with cost model for {$name}. Please ask the admin to check and resave the cost model</li>";
			}
		}
	
	}
	
	// if ($type == "STAT") {
		// print_r($data);
		// echo "<p>($type / $characterID) SQL: $sql</p>";
		// echo "<pre>";
		// print_r($outdata);
		// echo "</pre>";
	// }

	return $outdata;
}

function vtm_get_current_stats($characterID, $output_type = OBJECT) {
	global $wpdb;

	$sql = "SELECT 
				stat.name, 
				cha_stat.level	as level_from,
				cha_stat.id 	as chartableid, 
				cha_stat.comment as comment,
				stat.ID 		as itemid, 
				stat.GROUPING 	as grp
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cha_stat,
				" . VTM_TABLE_PREFIX . "STAT stat
			WHERE 
				cha_stat.STAT_ID      = stat.ID
				AND cha_stat.CHARACTER_ID = %s
		   ORDER BY stat.ordering";
	$sql   = $wpdb->prepare($sql, $characterID);
	$items = $wpdb->get_results($sql, $output_type);
	
	return $items;
}
function vtm_get_current_skills($characterID, $output_type = OBJECT) {
	global $wpdb;

	$sql = "SELECT 
				skill.name, 
				IFNULL(cha_skill.LEVEL,0) as level_from,
				IFNULL(cha_skill.ID,0) 	  as chartableid, 
				skill.ID 		as itemid, 
				skill.GROUPING 	as grp,
				CASE skill.grouping WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ordering,
				skill.MULTIPLE  as multiple
			FROM 
				" . VTM_TABLE_PREFIX . "SKILL skill
				LEFT JOIN
					(SELECT ID, SKILL_ID, LEVEL
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER_SKILL cha_skill
					WHERE
						CHARACTER_ID = %s
					) as cha_skill
				ON
					cha_skill.SKILL_ID = skill.ID
			WHERE 
				skill.VISIBLE = 'Y'
		    ORDER BY ordering DESC, skill.name";
	$sql   = $wpdb->prepare($sql, $characterID);
	$items = $wpdb->get_results($sql, $output_type);
	
	//echo "<p>SQL: $sql</p>";
	//print_r($items);
	
	return $items;
}
function vtm_get_current_backgrounds($characterID, $output_type = OBJECT) {
	global $wpdb;

	$sql = "SELECT 
				item.name, 
				IFNULL(cha_bg.LEVEL,0) 	as level_from,
				IFNULL(cha_bg.ID,0) 	as chartableid, 
				item.ID 				as itemid, 
				item.GROUPING 			as grp
			FROM 
				" . VTM_TABLE_PREFIX . "BACKGROUND item
				LEFT JOIN
					(SELECT ID, BACKGROUND_ID, LEVEL
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND cha_bg
					WHERE
						CHARACTER_ID = %s
					) as cha_bg
				ON
					cha_bg.BACKGROUND_ID = item.ID
			WHERE 
				item.VISIBLE = 'Y'
		    ORDER BY item.GROUPING, item.name";
	$sql   = $wpdb->prepare($sql, $characterID);
	$items = $wpdb->get_results($sql, $output_type);
	
	//echo "<p>SQL: $sql</p>";
	//print_r($items);
	
	return $items;
}
function vtm_get_current_merits($characterID, $output_type = OBJECT) {
	global $wpdb;

	$sql = "SELECT 
				item.name, 
				0 						as level_from,
				IFNULL(cha_merit.ID,0) 	as chartableid, 
				item.ID 				as itemid, 
				item.GROUPING 			as grp,
				item.MULTIPLE			as multiple
			FROM 
				" . VTM_TABLE_PREFIX . "MERIT item
				LEFT JOIN
					(SELECT ID, MERIT_ID, LEVEL
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER_MERIT cha_merit
					WHERE
						CHARACTER_ID = %s
					) as cha_merit
				ON
					cha_merit.MERIT_ID = item.ID
			WHERE 
				item.VISIBLE = 'Y'
		    ORDER BY item.GROUPING, item.COST DESC, item.name";
	$sql   = $wpdb->prepare($sql, $characterID);
	$items = $wpdb->get_results($sql, $output_type);
	
	//echo "<p>SQL: $sql</p>";
	//print_r($items);
	
	return $items;
}
function vtm_get_current_disciplines($characterID, $output_type = OBJECT) {
	global $wpdb;

	$sql = "SELECT
				item.name,
				chartable.level		as level_from,
				chartable.ID 		as chartableid,
				item.ID 			as itemid,
				IF(ISNULL(clandisc.DISCIPLINE_ID),'Non-Clan Discipline','Clan Discipline') as grp
			FROM
				" . VTM_TABLE_PREFIX . "DISCIPLINE item
				LEFT JOIN
					(SELECT ID, LEVEL, CHARACTER_ID, DISCIPLINE_ID
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE
					WHERE
						CHARACTER_ID = %s
					) chartable
				ON
					chartable.DISCIPLINE_ID = item.ID
				LEFT JOIN
					(SELECT DISCIPLINE_ID, CLAN_ID
					FROM
						" . VTM_TABLE_PREFIX . "CLAN clans,
						" . VTM_TABLE_PREFIX . "CLAN_DISCIPLINE cd,
						" . VTM_TABLE_PREFIX . "CHARACTER chars
					WHERE
						chars.ID = %s
						AND chars.PRIVATE_CLAN_ID = clans.ID
						AND cd.CLAN_ID = clans.ID
					) as clandisc
				ON
					clandisc.DISCIPLINE_ID = item.id
			WHERE
				NOT(ISNULL(clandisc.DISCIPLINE_ID))
				OR item.VISIBLE = 'Y' 
			ORDER BY grp, item.name";

	$sql   = $wpdb->prepare($sql, $characterID, $characterID);
	$items = $wpdb->get_results($sql, $output_type);
	
	//echo "<p>SQL: $sql</p>";
	//print_r($items);
	
	return $items;
}
function vtm_get_current_paths($characterID, $output_type = OBJECT) {
	global $wpdb;

	$sql = "SELECT
				item.name,
				0				as level_from,
				0 				as chartableid,
				item.ID 		as itemid,
				disc.name 		as grp,
				cha_disc.level	as maximum
			FROM
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc,
				" . VTM_TABLE_PREFIX . "PATH item,
				" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE cha_disc
			WHERE
				item.DISCIPLINE_ID = disc.ID
				AND cha_disc.CHARACTER_ID = %s
				AND cha_disc.DISCIPLINE_ID = disc.ID
				AND item.VISIBLE = 'Y' 
			ORDER BY grp, item.name";

	$sql   = $wpdb->prepare($sql, $characterID, $characterID);
	$items = $wpdb->get_results($sql, $output_type);
	
	//echo "<p>SQL: $sql</p>";
	//print_r($items);
	
	return $items;
}
function vtm_get_freebies_spent($table, $postvariable, $characterID) {
	global $wpdb;

	$spent = 0;
	$freebiecosts = vtm_get_freebie_costs($table, $characterID);
		
	if (isset($_POST[$postvariable])) {
		switch ($table) {
			case 'STAT':
				$current = vtm_get_current_stats($characterID, OBJECT_K);
				break;
			case 'SKILL':
				$current = vtm_get_current_skills($characterID, OBJECT_K);
				break;
			case 'DISCIPLINE':
				$current = vtm_get_current_disciplines($characterID, OBJECT_K);
				break;
			case 'BACKGROUND':
				$current = vtm_get_current_backgrounds($characterID, OBJECT_K);
				break;
			case 'MERIT':
				$current = vtm_get_current_merits($characterID, OBJECT_K);
				break;
			case 'PATH':
				$current = vtm_get_current_paths($characterID, OBJECT_K);
				break;
			default:
				$current = array();
		}
		$current      = vtm_sanitize_array($current);
		$freebiecosts = vtm_sanitize_array($freebiecosts);
		
		//print_r($current);
		
		$bought = $_POST[$postvariable];
		foreach ($bought as $name => $level_to) {
			$levelfrom = isset($current[$name]->level_from) ? $current[$name]->level_from : 0;
			$actualname = preg_replace("/_\d+$/", "", $name);
		
			if ($table == 'MERIT') {
				if (!isset($current[$name])) {
					if (isset($current[$actualname]->multiple) && $current[$actualname]->multiple == 'Y') {
						$spent += isset($freebiecosts[$actualname][0][1]) ? $freebiecosts[$actualname][0][1] : 0;
						//echo "<li>Running total is $spent. Bought $actualname ({$freebiecosts[$actualname][0][1]})</li>";
					}
				} else {
					$spent += isset($freebiecosts[$name][0][1]) ? $freebiecosts[$name][0][1] : 0;
					//echo "<li>Running total is $spent. Bought $name ({$freebiecosts[$name][0][1]})</li>";
				}
			}
			elseif (!isset($current[$name])) {
				//echo "$name becomes $actualname, <br />";
				if (isset($current[$actualname]->multiple) && $current[$actualname]->multiple == 'Y') {
					//echo "$name - from: {$current[$actualname]->level_from}, to: {$level_to}, cost: {$freebiecosts[$actualname][$current[$actualname]->level_from][$level_to]}<br />";
					$spent += isset($freebiecosts[$actualname][$current[$actualname]->level_from][$level_to]) ? $freebiecosts[$actualname][$current[$actualname]->level_from][$level_to] : 0;
					//echo "<li>Running total is $spent. Bought $actualname to $level_to ({$freebiecosts[$actualname][$current[$actualname]->level_from][$level_to]})</li>";
				}
			} else {
				$spent += isset($freebiecosts[$name][$levelfrom][$level_to]) ? $freebiecosts[$name][$levelfrom][$level_to] : 0;
				//echo "<li>Running total is $spent. Bought $name to $level_to ({$freebiecosts[$name][$levelfrom][$level_to]})</li>";
			}
		}
	} else {
		$sql = "SELECT SUM(AMOUNT) FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
				WHERE CHARACTER_ID = %s AND ITEMTABLE = %s";
		$sql = $wpdb->prepare($sql, $characterID, $table);
		$spent = $wpdb->get_var($sql);
	}

	return $spent;
}
function vtm_get_chargen_xp_spent($table, $postvariable, $characterID) {
	global $wpdb;

	$spent = 0;
	
	if (isset($_POST[$postvariable])) {
		$xpcosts  = vtm_get_chargen_xp_costs($table, $characterID);
		$bought   = $_POST[$postvariable];

		switch ($table) {
			case 'STAT':
				$freebies = vtm_get_pending_freebies($table, 'freebie_stat', $characterID);
				$current  = vtm_get_current_stats($characterID, OBJECT_K);
				break;
			case 'SKILL':
				$freebies = vtm_get_pending_freebies($table, 'freebie_skill', $characterID);
				$current  = vtm_get_current_skills($characterID, OBJECT_K);
				break;
			case 'DISCIPLINE':
				$freebies = vtm_get_pending_freebies($table, 'freebie_discipline', $characterID);
				$current  = vtm_get_current_disciplines($characterID, OBJECT_K);
				break;
			case 'MERIT':
				$freebies = vtm_get_pending_freebies($table, 'freebie_merit', $characterID);
				$current  = vtm_get_current_merits($characterID, OBJECT_K);
				break;
			default:
				$current = array();
		}
		$current = vtm_sanitize_array($current);
				
		//print_r($freebies);
		
		foreach ($bought as $key => $level_to) {
		
			$levelfrom = isset($current[$key]->level_from) ? $current[$key]->level_from : 0;
			$levelfrom = isset($freebies[$key]) ? $freebies[$key] : $levelfrom;
			
			if ($level_to != 0) {
				$actualkey = preg_replace("/_\d+$/", "", $key);
				
				if ($table == 'MERIT') {
					if (!isset($current[$key])) {
						if (isset($current[$actualkey]->multiple) && $current[$actualkey]->multiple == 'Y') {
							$spent += isset($xpcosts[$actualkey][0][1]) ? $xpcosts[$actualkey][0][1] : 0;
							//echo "<li>$key / $actualkey, cost: {$xpcosts[$actualkey][0][1]}</li>";
						}
					} else {
						//echo "<li>$key - from:$levelfrom, to:$level_to, cost: {$xpcosts[$key][0][1]}</li>";
						$spent += isset($xpcosts[$key][0][1]) ? $xpcosts[$key][0][1] : 0;
					}
				} else {
					//echo "<li>$key - from:$levelfrom, to:$level_to, cost: {$xpcosts[$key][$levelfrom][$level_to]}</li>";
					$spent += isset($xpcosts[$key][$levelfrom][$level_to]) ? $xpcosts[$key][$levelfrom][$level_to] : 0;
				}
			}
		}
	} else {

		// Saving status and updating spend? or loading to populate new page?
		$laststep = $_POST['step'];
		$thisstep = vtm_get_step();
		if ($laststep == $thisstep) {
			//echo "<li>Nothing spent on $table</li>";
			$spent = 0;
		} else {
			$sql = "SELECT SUM(AMOUNT) FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s AND ITEMTABLE = %s";
			$sql = $wpdb->prepare($sql, $characterID, $table);
			$spent = -$wpdb->get_var($sql);
		}
	}
	//echo "<li>spent on $table, $postvariable: $spent</li>";
	return $spent;
} */
function vtm_get_pending_freebies($table, $characterID) {
	global $wpdb;

	$sql = "SELECT freebie.ITEMNAME as name, freebie.LEVEL_TO as value,
			freebie.SPECIALISATION as specialisation
		FROM
			" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
		WHERE
			freebie.CHARACTER_ID = %s
			AND freebie.ITEMTABLE = '$table'";
	$sql = $wpdb->prepare($sql, $characterID);
	//echo "SQL: $sql</p>";
	
	$pending = $wpdb->get_results($sql, OBJECT_K);
	$pending = vtm_sanitize_array($pending);

	return $pending;
}

function vtm_get_pending_chargen_xp($table, $characterID) {
	global $wpdb;
	
	$sql = "SELECT ITEMNAME as name, CHARTABLE_LEVEL as value, 
			SPECIALISATION as specialisation
		FROM
			" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
		WHERE
			CHARACTER_ID = %s
			AND ITEMTABLE = '$table'";
	$sql = $wpdb->prepare($sql, $characterID);
	//echo "SQL: $sql</p>";
	
	$pending = $wpdb->get_results($sql, OBJECT_K);
	$pending = vtm_sanitize_array($pending);
	
	return $pending;
}

function vtm_sanitize_array($array) {

	if (count($array) == 0) {
		return array();
	} else {
		$keys = array_keys($array);
		$values = array_values($array);
		
		return array_combine(array_map("vtm_sanitize_keys",$keys), $values);
	} 
}

function vtm_sanitize_keys($a) {
	return sanitize_key($a);
}
/*
function vtm_render_chargen_xp_stats($characterID, $pendingSpends, $points) {
	$output = "";
	$rowoutput = "";
	$max2display = 5;
	$columns = 3;
	$fulldoturl = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );

	//$current_post = isset($_POST['xp_stat']) ? $_POST['xp_stat'] : array();

	// Get costs
	$xpcosts = vtm_get_chargen_xp_costs('STAT', $characterID);

	// Get current stats in database
	$current_stat = vtm_sanitize_array(vtm_get_current_stats($characterID, OBJECT_K));
	
	// Get Freebie points spent on stats
	$freebies = vtm_get_pending_freebies('STAT', 'freebie_stat', $characterID);
	
	// Get currently selected
	$pending = vtm_get_pending_chargen_xp('STAT', 'xp_stat', $characterID);
	//print_r($pending);
	
	$grp = "";
	$grpcount = 0;
	$col = 0;
	foreach ($current_stat as $key => $item) {
		switch ($key) {
			case 'willpower': $max2display = 10; break;
			case 'conscience': $max2display = 5; break;
			case 'conviction': $max2display = 5; break;
			case 'selfcontrol': $max2display = 5; break;
			case 'courage': $max2display = 5; break;
			case 'instinct': $max2display = 5; break;
		}
		$colspan = 2;
		
		// start column / new column
		if (isset($item->grp)) {
			if ($grp != $item->grp) {
				$grpcount++;
				if (empty($grp)) {
					$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
					$col++;
				} 
				elseif ($col == $columns) {
					$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
					$col = 1;
				}
				else {
					$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
					$col++;
				}
				$grp = $item->grp;
			}
		}
		
		// Hidden fields
		$comment = isset()
		$rowoutput .= "<tr style='display:none'>
			<input type='hidden' name='xp_stat_comment[$key]' value='{$item->comment}' />
			<td colspan=$colspan>\n";
		$rowoutput .= "</td></tr>\n";

			//dots row
		$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
		$rowoutput .= "<fieldset class='dotselect'>";
		for ($i=$max2display;$i>=1;$i--) {
			$radioid = "dot_{$key}_{$item->itemid}_{$i}";
			$current = isset($pending[$key]) ? $pending[$key] : 0;
			$levelfrom = isset($freebies[$key]) ? $freebies[$key] : $item->level_from;
			
			if ($item->level_from >= $i)
				$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
			elseif (isset($freebies[$key]) && $freebies[$key] >= $i)
				$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
			elseif (isset($xpcosts[$key][$levelfrom][$i])) {
				$cost = $xpcosts[$key][$levelfrom][$i];
				$rowoutput .= "<input type='radio' id='$radioid' name='xp_stat[$key]' value='$i' ";
				$rowoutput .= checked($current, $i, false);
				$rowoutput .= " /><label for='$radioid' title='Level $i ($cost xp)'";
				$rowoutput .= ">&nbsp;</label>\n";
			}
			else {
				$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />($levelfrom/$i)";
			}
		}
		$radioid = "dot_{$key}_{$item->itemid}_clear";
		$rowoutput .= "<input type='radio' id='$radioid' name='xp_stat[$key]' value='0' ";
		$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
		$rowoutput .= "</fieldset></td></tr>\n";
		
	}
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";
	
	return $output;
}
function vtm_render_chargen_xp_paths($characterID, $pendingSpends, $points) {
	$output = "";
	$rowoutput = "";
	$max2display = 5;
	$columns = 3;
	$fulldoturl = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );

	// Get costs
	$xpcosts = vtm_get_chargen_xp_costs('PATH', $characterID);

	// Get current stats in database
	$current_path = vtm_sanitize_array(vtm_get_current_paths($characterID, OBJECT_K));
	
	// Get Freebie points spent on stats
	$freebies = vtm_get_pending_freebies('PATH', 'freebie_path', $characterID);
	
	// Get currently selected
	$pending = vtm_get_pending_chargen_xp('PATH', 'xp_path', $characterID);
	
	//print_r($current_path);
	
	$grp = "";
	$grpcount = 0;
	$col = 0;
	foreach ($current_path as $key => $item) {
		//echo "<li>$key: {$item->level_from}</li>";
		$colspan = 2;
		
		// start column / new column
		if (isset($item->grp)) {
			if ($grp != $item->grp) {
				$grpcount++;
				if (empty($grp)) {
					$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
					$col++;
				} 
				elseif ($col == $columns) {
					$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
					$col = 1;
				}
				else {
					$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
					$col++;
				}
				$grp = $item->grp;
			}
		}
		//dots row
		$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
		$rowoutput .= "<fieldset class='dotselect'>";
		for ($i=$max2display;$i>=1;$i--) {
			$radioid = "dot_{$key}_{$item->itemid}_{$i}";
			$current = isset($pending[$key]) ? $pending[$key] : 0;
			$levelfrom = isset($freebies[$key]) ? $freebies[$key] : $item->level_from;
			
			if ($item->level_from >= $i)
				$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
			elseif (isset($freebies[$key]) && $freebies[$key] >= $i)
				$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
			elseif (isset($xpcosts[$key][$levelfrom][$i])) {
				$cost = $xpcosts[$key][$levelfrom][$i];
				$rowoutput .= "<input type='radio' id='$radioid' name='xp_path[$key]' value='$i' ";
				$rowoutput .= checked($current, $i, false);
				$rowoutput .= " /><label for='$radioid' title='Level $i ($cost xp)'";
				$rowoutput .= ">&nbsp;</label>\n";
			}
			else {
				$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />($levelfrom/$i)";
			}
		}
		$radioid = "dot_{$key}_{$item->itemid}_clear";
		$rowoutput .= "<input type='radio' id='$radioid' name='xp_path[$key]' value='0' ";
		$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
		$rowoutput .= "</fieldset></td></tr>\n";
		
	}
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";
	
	return $output;
}
function vtm_render_chargen_xp_skills($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output      = "";
	$rowoutput   = "";
	$max2display = 5;
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );

	// Get costs
	$xpcosts = vtm_get_chargen_xp_costs('SKILL', $characterID);

	// Get skills in database
	$items = vtm_sanitize_array(vtm_get_current_skills($characterID, OBJECT_K));
	
	// Get Freebie points spent on stats
	$freebies = vtm_get_pending_freebies('SKILL', 'freebie_skill', $characterID);
	
	// Get currently selected
	$pending = vtm_get_pending_chargen_xp('SKILL', 'xp_skill', $characterID);
	
	//echo "<pre>";
	//print_r($xpcosts);
	//echo "</pre>";
	
	if (count($items) > 0) {
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $key => $item) {
		
			$loop = ($item->multiple == 'Y') ? 5 : 1;
			
			$max2display;
			$colspan = 2;
			
			for ($j = 1 ; $j <= $loop ; $j++) {
				$actualkey = ($item->multiple == 'Y') ? $key . "_" . $j : $key;
			
				// start column / new column
				if (isset($item->grp)) {
					if ($grp != $item->grp) {
						$grpcount++;
						if (empty($grp)) {
							$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
							$col++;
						} 
						elseif ($col == $columns) {
							$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
							$col = 1;
						}
						else {
							$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
							$col++;
						}
						$grp = $item->grp;
					}
				}

				// Hidden fields
				$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
				$rowoutput .= "</td></tr>\n";
				
				//dots row
				$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
				$rowoutput .= "<fieldset class='dotselect'>";
				for ($i=$max2display;$i>=1;$i--) {
					$radioid = "dot_{$actualkey}_{$j}_{$item->itemid}_{$i}";
					$current = isset($pending[$actualkey]) ? $pending[$actualkey] : 0;
					$levelfrom = isset($freebies[$actualkey]) ? $freebies[$actualkey] : $item->level_from;

					if ($item->level_from >= $i)
						$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
					elseif (isset($freebies[$actualkey]) && $freebies[$actualkey] >= $i)
						$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
					elseif (isset($xpcosts[$key][$levelfrom][$i])) {
						$cost = $xpcosts[$key][$levelfrom][$i];
						$rowoutput .= "<input type='radio' id='$radioid' name='xp_skill[$actualkey]' value='$i' ";
						$rowoutput .= checked($current, $i, false);
						$rowoutput .= " /><label for='$radioid' title='Level $i ($cost xp)'";
						$rowoutput .= ">&nbsp;</label>\n";
					}
					else {
						$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
					}
				}
				$radioid = "dot_{$actualkey}_{$j}_{$item->itemid}_clear";
				$rowoutput .= "<input type='radio' id='$radioid' name='xp_skill[$actualkey]' value='0' ";
				$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
				$rowoutput .= "</fieldset></td></tr>\n";
			}
		}
	
	}
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}
function vtm_render_chargen_xp_merits($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output      = "";
	$rowoutput   = "";
	$max2display = 5;
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );

	// Get costs
	$xpcosts = vtm_get_chargen_xp_costs('MERIT', $characterID);
	$freebiecosts = vtm_get_freebie_costs('MERIT', $characterID);

	// Get skills in database
	$items = vtm_sanitize_array(vtm_get_current_merits($characterID, OBJECT_K));
	
	// Get Freebie points spent on stats
	$freebies = vtm_get_pending_freebies('MERIT', 'freebie_merit', $characterID);
	
	// Get currently selected
	$pending = vtm_get_pending_chargen_xp('MERIT', 'xp_merit', $characterID);
	
	//echo "<pre>";
	//print_r($xpcosts);
	//echo "</pre>";
	
	if (count($items) > 0) {
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $key => $item) {
		
			$loop = ($item->multiple == 'Y') ? 4 : 1;
			$cost = $xpcosts[$key][0][1];
			$meritlevel = $freebiecosts[$item->name][0][1];
			
			$max2display;
			$colspan = 2;
			
			if ($cost != 0 && $meritlevel > 0) {
			
				for ($j = 1 ; $j <= $loop ; $j++) {
					$actualkey = ($item->multiple == 'Y') ? $key . "_" . $j : $key;
					$levelfrom = isset($item->level_from) ? $item->level_from : 0;
					$levelfrom = isset($freebies[$actualkey]) ? $freebies[$actualkey] : $item->level_from;
					$current   = isset($pending[$actualkey]) ? $pending[$actualkey] : 0;
		
					// start column / new column
					if (isset($item->grp)) {
						if ($grp != $item->grp) {
							$grpcount++;
							if (empty($grp)) {
								$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
								$col++;
							} 
							elseif ($col == $columns) {
								$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
								$col = 1;
							}
							else {
								$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->grp}</th></tr>\n";
								$col++;
							}
							$grp = $item->grp;
						}
					}

					// Hidden fields
					$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
					$rowoutput .= "</td></tr>\n";
					
					//dots row
					$cbid = "cb_{$j}_{$item->itemid}";
					$rowoutput .= "<tr><td><span>";
					if (!$levelfrom) {
						$rowoutput .= "<input type='checkbox' name='xp_merit[" . $actualkey . "]' id='$cbid' value='$meritlevel' ";
						$rowoutput .= checked($current, $meritlevel, false);
						$rowoutput .= "/>\n";
					}
					$rowoutput .= "<label for='$cbid'>" . stripslashes($item->name) . " ($meritlevel) - $cost XP</label>\n";
					$rowoutput .= "</span></td></tr>\n";
				}
			}
		}
	
	}
	
	if ($rowoutput != "")
		$output .= "<table id='merit_xp_table'>$rowoutput</table></td></tr></table>\n";

	return $output;

}
*/
function vtm_validate_basic_info($settings, $characterID) {
	global $current_user;

	$ok = 1;
	$errormessages = "";
	
	// VALIDATE BASIC INFO
	//		- error: character name is not blank
	//		- error: new player? player name is not duplicated
	//		- error: old player? player name is found
	//		- error: login name doesn't already exist (except if it's the currently logged in acct)
	//		- error: email address is not blank and looks valid
	//		- error: concept is not blank
	
	if (!isset($_POST['character']) || empty($_POST['character'])) {
		$errormessages .= "<li>ERROR: Please enter a character name</li>";
		$ok = 0;
	}
	
	$playername = isset($_POST['player']) ? $_POST['player'] : '';
	if (empty($playername)) {
		$errormessages .= "<li>ERROR: Please enter a player name</li>";
		$ok = 0;
	} else {
		$playeridguess = isset($_POST['playerID']) ? $_POST['playerID'] : -1;
		$playerid = vtm_get_player_id($playername);
		
		if (!isset($_POST['newplayer']) || (isset($_POST['newplayer']) && !$_POST['newplayer']) ) {
			// old player
			if (!isset($playerid)) {
				$ok = 0;
				// can't find playername.  make a guess
				$playerid = vtm_get_player_id($playername, true);
				if (isset($playerid)) {
					$errormessages .= "<li>ERROR: Could not find a player with the name '$playername'. Did you mean '" . vtm_get_player_name($playerid) . "'?</li>";
				}
				else
					$errormessages .= "<li>ERROR: Could not find a player with the name '$playername'. Are you a new player?</li>";
			}
		} else {
			// new player
			if (isset($playerid)) {
				$ok = 0;
				$errormessages .= "<li>ERROR: A player already exists with the name '$playername'. Are you a returning player?</li>";
			}
		}
	}
	
	if (isset($_POST['wordpress_id'])) {
		$login = $_POST['wordpress_id'];
		get_currentuserinfo();
		if (username_exists( $login ) && $login != $current_user->user_login) {
			$ok = 0;
			$errormessages .= "<li>ERROR: An account already exists with the login name '$login'. Please choose another.</li>";
		}
		elseif (!validate_username( $login )) {
			$ok = 0;
			$errormessages .= "<li>ERROR: Login name '$login' is invalid. Please choose another.</li>";
		}
	}
	
	if (!isset($_POST['email']) || empty($_POST['email'])) {
			$ok = 0;
			$errormessages .= "<li>ERROR: Email address is missing.</li>";
	} else {
		$email = $_POST['email'];
		if (!is_email($email)) {
			$ok = 0;
			$errormessages .= "<li>ERROR: Email address '$email' does not seem to be a valid email address.</li>";
		}
	}
	
	if (!isset($_POST['concept']) || empty($_POST['concept'])) {
		$errormessages .= "<li>ERROR: Please enter your character concept.</li>";
		$ok = 0;
	}

	return array($ok, $errormessages);
}

function vtm_validate_abilities($settings, $characterID) {

	$ok = 1;
	$errormessages = "";
	
	// VALIDATE ABILITIES
	// P/S/T
	//		- ERROR: P / S / T options only picked once
	//		- WARN/ERROR: correct number of points spent in each group
	// 		- ERROR: check that nothing is over the max
	if (isset($_POST['ability_value'])) {
		$values = $_POST['ability_value'];
		
		$groups = $_POST['group'];
		$abilities = vtm_get_chargen_abilities();
		$target = array($settings['abilities-primary'], $settings['abilities-secondary'], $settings['abilities-tertiary']);
		$check = 0;
		
		foreach ($_POST['group'] as $group) {
			$sectiontype = $_POST[$group];
			if ($sectiontype == -1) {
				$errormessages .= "<li>ERROR: You have not selected if $group is Primary, Secondary or Tertiary</li>";
				$ok = 0;
			} else {
				$check += $sectiontype;
				$sectiontotal = 0;
				foreach  ($abilities as $skill) {
					$key = sanitize_key($skill->NAME);
					if (sanitize_key($skill->GROUPING) == $group) {
						$sectiontotal += isset($values[$key]) ? max(0,$values[$key]) : 0;
						if (isset($values[$key]) && $values[$key] > $settings['abilities-max']) {
							$errormessages .= "<li>ERROR: Abilities should not go higher than level {$settings['abilities-max']}. Please reduce the dots spend in {$skill->NAME}</li>";
							$ok = 0;
						}
					}
				}
				//echo "<p>group $group: target = " . $target[$sectiontype-1] . ", total = $sectiontotal</li>";
				if ($sectiontotal > $target[$sectiontype-1]) {
					$errormessages .= "<li>ERROR: You have spent too many dots in $group</li>";
					$ok = 0;
				}
				elseif ($sectiontotal < $target[$sectiontype-1])  {
					$errormessages .= "<li>WARNING: You haven't spent enough dots in $group</li>";
					//$ok = 0;
				}
			}
		}
		if ($ok && $check != 6) {
			$errormessages .= "<li>ERROR: Check that you have chosen Primary, Secondary and Tertiary once only for each type of Ability</li>";
			$ok = 0;
		}
			
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		//$ok = 0;
	}

	return array($ok, $errormessages);
}

function vtm_validate_attributes($settings, $characterID) {

	$ok = 1;
	$errormessages = "";
	
	// VALIDATE ATTRIBUTES
	// P/S/T
	//		- ERROR: P / S / T options only picked once
	//		- WARN/ERROR: correct number of points spent in each group
	// Point Spent
	//		- WARN/ERROR: point total correct
	if (isset($_POST['attribute_value'])) {
		$values = $_POST['attribute_value'];
		
		if ($settings['attributes-method'] == 'PST') {
						
			$groups = $_POST['group'];
			$attributes = vtm_get_chargen_attributes();
			$target = array($settings['attributes-primary'], $settings['attributes-secondary'], $settings['attributes-tertiary']);
			$check = 0;
			
			foreach ($_POST['group'] as $group) {
				$sectiontype = $_POST[$group];
				if ($sectiontype == -1) {
					$errormessages .= "<li>ERROR: You have not selected if $group is Primary, Secondary or Tertiary</li>";
					$ok = 0;
				} else {
					$check += $sectiontype;
					$sectiontotal = 0;
					foreach  ($attributes as $attribute) {
						if (sanitize_key($attribute->GROUPING) == $group) {
							$key     = sanitize_key($attribute->NAME);
							$sectiontotal += isset($values[$key]) ? $values[$key] - 1 : 0;
						}
					}
					//echo "<li>group $group: target = " . $target[$sectiontype-1] . ", total = $sectiontotal</li>";
					if ($sectiontotal > $target[$sectiontype-1]) {
						$errormessages .= "<li>ERROR: You have spent too many dots in $group</li>";
						$ok = 0;
					}
					elseif ($sectiontotal < $target[$sectiontype-1])  {
						$errormessages .= "<li>WARNING: You haven't spent enough dots in $group</li>";
					}
				}
			}
			if ($ok && $check != 6) {
				$errormessages .= "<li>ERROR: Check that you have chosen Primary, Secondary and Tertiary once only for each type of Attribute</li>";
				$ok = 0;
			}
			
			
		} else {
			$target = $settings['attributes-points'];
			$total = 0;
			foreach ($values as $att => $val)
				$total += $val - 1;
			
			if ($total > $target) {
				$errormessages .= "<li>ERROR: You have spent too many points</li>";
				$ok = 0;
			}
			elseif ($total < $target)  {
				$errormessages .= "<li>WARNING: You haven't spent enough points</li>";
			}
		}
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
	}
	return array($ok, $errormessages);
}

function vtm_validate_disciplines($settings, $characterID) {

	$ok = 1;
	$errormessages = "";

	// VALIDATE DISCIPLINES
	//		- spend the right amount of points
	if (isset($_POST['discipline_value'])) {
		$values = $_POST['discipline_value'];
		
		$total = 0;
		foreach  ($values as $id => $val) {
			$total += max(0,$val);
		}
		
		if ($total > $settings['disciplines-points']) {
			$errormessages .= "<li>ERROR: You have spent too many dots</li>";
			$ok = 0;
		}
		elseif ($total < $settings['disciplines-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
			//$ok = 0;
		}
			
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		//$ok = 0;
	}

	
	return array($ok, $errormessages);
}
/*
function vtm_validate_backgrounds($settings, $characterID) {

	$ok = 1;
	$errormessages = "";
	
	// VALIDATE BACKGROUNDS
	//		- all points spent
	if (isset($_POST['background_value'])) {
		$values = $_POST['background_value'];
						
		$total = 0;
		foreach  ($values as $id => $val) {
			$total += $val;
		}
		
		if ($total > $settings['backgrounds-points']) {
			$errormessages .= "<li>ERROR: You have spent too many dots</li>";
			$ok = 0;
		}
		elseif ($total < $settings['backgrounds-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
			//$ok = 0;
		}
							
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		//$ok = 0;
	}

	return array($ok, $errormessages);
}
function vtm_validate_virtues($settings, $characterID) {
	global $wpdb;

	$ok = 1;
	$errormessages = "";
	
	// VALIDATE VIRTUES
	//		- all points spent
	//		- point spent on the correct virtues
	if (isset($_POST['virtue_value'])) {
		$values = $_POST['virtue_value'];
		
		$selectedpath = $_POST['path'];
		$statid1 = $wpdb->get_var($wpdb->prepare("SELECT STAT1_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $selectedpath));
		$statid2 = $wpdb->get_var($wpdb->prepare("SELECT STAT2_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $selectedpath));
		$courage = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "STAT WHERE NAME = 'Courage'");
		
		$total = 0;
		$statfail = 0;
		foreach  ($values as $id => $val) {
			$total += $val - 1;
			
			if ($id != $statid1 && $id != $statid2 && $id != $courage) {
				$statfail = 1;
			}
			
		}
		
		if ($total > $settings['virtues-points']) {
			$errormessages .= "<li>ERROR: You have spent too many dots</li>";
			$ok = 0;
		}
		elseif ($total < $settings['virtues-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
			//$ok = 0;
		}
		if ($statfail) {
			$errormessages .= "<li>ERROR: Please update Virtues for the selected path</li>";
			$ok = 0;
		}
		
							
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		//$ok = 0;
	}

	return array($ok, $errormessages);
}
function vtm_validate_freebies($settings, $characterID) {

	$ok = 1;
	$errormessages = "";
	
	// VALIDATE FREEBIE POINTS
	//		Right number of points spent
	//		Not too many merits bought
	//		Not too many flaws bought
	//		Level of paths bought do not exceed level of discipline
	$meritsspent = 0;
	$flawsgained = 0;
	if (isset( $_POST['freebie_merit'])) {
		$bought = $_POST['freebie_merit'];
		foreach ($bought as $name => $level_to) {
			if ($level_to > 0)
				$meritsspent += $level_to;
			else
				$flawsgained += -$level_to;
		}
		if ($settings['merits-max'] > 0 && $meritsspent > $settings['merits-max']) {
			$errormessages .= "<li>ERROR: You have bought too many points of Merits</li>";
			$ok = 0;
		}
		if ($settings['flaws-max'] > 0 && $flawsgained > $settings['flaws-max']) {
			$errormessages .= "<li>ERROR: You have gained too many points from Flaws</li>";
			$ok = 0;
		}
	}
	
	$points = $settings['freebies-points'];
	
	$spent = 0;
	
	$spent += vtm_get_freebies_spent('STAT',       'freebie_stat', $characterID);
	$spent += vtm_get_freebies_spent('SKILL',      'freebie_skill', $characterID);
	$spent += vtm_get_freebies_spent('DISCIPLINE', 'freebie_discipline', $characterID);
	$spent += vtm_get_freebies_spent('BACKGROUND', 'freebie_background', $characterID);
	$spent += vtm_get_freebies_spent('MERIT',      'freebie_merit', $characterID);
	$spent += vtm_get_freebies_spent('PATH',       'freebie_path', $characterID);
	
	if ($spent == 0) {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
	}
	elseif ($spent > $points) {
		$errormessages .= "<li>ERROR: You have spent too many dots ($spent / $points)</li>";
		$ok = 0;
	}
	elseif ($spent < $points) {
		$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
	}
	
	if (isset($_POST['freebie_path'])) {
		$pathinfo = vtm_sanitize_array(vtm_get_current_paths($characterID, OBJECT_K));
		$bought = $_POST['freebie_path'];
		foreach ($bought as $path => $level) {
			$disciplinekey = sanitize_key($pathinfo[$path]->grp);
			$max = isset($_POST['freebie_discipline'][$disciplinekey]) ? $_POST['freebie_discipline'][$disciplinekey] : $pathinfo[$path]->maximum;
		
			if ($level > $max) {
				$errormessages .= "<li>ERROR: The level in " . stripslashes($pathinfo[$path]->name) . " cannot be greater than the {$pathinfo[$path]->grp} rating</li>";
				$ok = 0;
			}
		}
	}

	return array($ok, $errormessages);
}
function vtm_validate_finishing($settings, $characterID) {

	$ok = 1;
	$errormessages = "";
	
	// All specialities are entered
	// Sire name is entered
	// Dates are not the default dates
	
	if (isset($_POST['itemname'])) {
		foreach ($_POST['itemname'] as $index => $name) {
			if (!isset($_POST['comment'][$index]) || $_POST['comment'][$index] == '') {
				$errormessages .= "<li>ERROR: Please specify a speciality for $name</li>";
				$ok = 0;
			}
		}
	}
	if (!isset($_POST['sire']) || $_POST['sire'] == '') {
		$errormessages .= "<li>ERROR: Please enter the name of your sire, or enter 'unknown' if your character does not know.</li>";
		$ok = 0;
}
	if ($_POST['day_dob'] == 0 || $_POST['month_dob'] == 0) {
		$errormessages .= "<li>ERROR: Please enter your character's Date of Birth.</li>";
		$ok = 0;
	}
	if ($_POST['day_doe'] == 0 || $_POST['month_doe'] == 0) {
		$errormessages .= "<li>ERROR: Please enter your character's Date of Embrace.</li>";
		$ok = 0;
	}

	return array($ok, $errormessages);
}
function vtm_validate_xp($settings, $characterID) {

	$ok = 1;
	$errormessages = "";
	
	// VALIDATE XP POINTS
	//		Right number of points spent
	//		Level of paths bought do not exceed level of discipline
	$points = vtm_get_total_xp(0, $characterID);
	$spent = 0;
	$spent += vtm_get_chargen_xp_spent('STAT', 'xp_stat', $characterID);
	$spent += vtm_get_chargen_xp_spent('SKILL', 'xp_skill', $characterID);
	$spent += vtm_get_chargen_xp_spent('DISCIPLINE', 'xp_discipline', $characterID);
	$spent += vtm_get_chargen_xp_spent('PATH', 'xp_path', $characterID);
	$spent += vtm_get_chargen_xp_spent('MERIT', 'xp_merit', $characterID);
	
	if ($spent == 0) {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
	}
	elseif ($spent > $points) {
		$errormessages .= "<li>ERROR: You have spent too many dots</li>";
		$ok = 0;
	}
	elseif ($spent < $points) {
		$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
	}

	if (isset($_POST['xp_path'])) {
		$pathinfo = vtm_sanitize_array(vtm_get_current_paths($characterID, OBJECT_K));
		$freebies = vtm_get_pending_freebies("DISCIPLINE", "freebie_discipline", $characterID);
		$bought = $_POST['xp_path'];
		foreach ($bought as $path => $level) {
			$disciplinekey = sanitize_key($pathinfo[$path]->grp);
			
			$max = 	isset($_POST['xp_discipline'][$disciplinekey]) && $_POST['xp_discipline'][$disciplinekey] != 0 ? 
					$_POST['xp_discipline'][$disciplinekey] : 
						(isset($freebies[$disciplinekey]) ?
						$freebies[$disciplinekey] :
						$pathinfo[$path]->maximum);
		
			if ($level > $max) {
				$errormessages .= "<li>ERROR: The level $level in " . stripslashes($pathinfo[$path]->name) . " cannot be greater than the {$pathinfo[$path]->grp} rating of $max</li>";
				$ok = 0;
			}
		}
	}

	return array($ok, $errormessages);
}

function vtm_render_date_entry($fieldname, $day, $month, $year) {

	$output ="
	<fieldset>
	<label for='month_$fieldname'>Month</label>
	<select id='month_$fieldname' name='month_$fieldname' autocomplete='off' />
		<option value='0'>[Select]</option>      
		<option value='01' " . selected('01', $month, false) . ">January</option>      
		<option value='02' " . selected('02', $month, false) . ">February</option>      
		<option value='03' " . selected('03', $month, false) . ">March</option>      
		<option value='04' " . selected('04', $month, false) . ">April</option>      
		<option value='05' " . selected('05', $month, false) . ">May</option>      
		<option value='06' " . selected('06', $month, false) . ">June</option>      
		<option value='07' " . selected('07', $month, false) . ">July</option>      
		<option value='08' " . selected('08', $month, false) . ">August</option>      
		<option value='09' " . selected('09', $month, false) . ">September</option>      
		<option value='10' " . selected('10', $month, false) . ">October</option>      
		<option value='11' " . selected('11', $month, false) . ">November</option>      
		<option value='12' " . selected('12', $month, false) . ">December</option>      
	</select> -
	<label for='day_$fieldname'>Day</label>
	<select id='day_$fieldname'  name='day_$fieldname' autocomplete='off' />
		<option value='0'>[Select]</option>";
	for ($i = 1; $i <= 31 ; $i++) {
		$val = sprintf("%02d", $i);
		$output .= "<option value='$val' " . selected($val, $day, false) . ">$i</option>\n";
	}
  
	$output .= "</select> -
	<label for='year_$fieldname'>Year</label>
	<input type='text' name='year_$fieldname' size=5 value='$year' />
	</fieldset>\n";

	return $output;
}
function vtm_get_chargen_specialties($characterID) {
	global $wpdb;

	// array ( 0 => array (
	//			'updatetable'  => 'CHARACTER_STAT|PENDING_XP_SPEND|PENDING_FREEBIE_SPEND',
	//			'tableid'      => <id of entry of table>
	//			'name'         => <name of stat>
	//		)
	// )
	$specialities = array();

	// 
	
	// MERITS
	
	// STATS & SKILLS
	$sql = "(SELECT 
				'STAT'					as type,
				'Attributes' 			as typename,
				stat.NAME 				as itemname, 
				stat.GROUPING 			as grp, 
				cs.LEVEL 					as level,
				cs.id						as id,
				cs.comment					as spec,
				pendingfreebie.LEVEL_TO 	as freebielevel,
				pendingfreebie.ID 			as freebieid,
				pendingfreebie.SPECIALISATION as freebiespec,
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				stat.SPECIALISATION_AT 		as specialisation_at,
				stat.ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "STAT stat,
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cs
				LEFT JOIN (
					SELECT ID, CHARTABLE_LEVEL, ITEMTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s
						AND ITEMTABLE = 'STAT'
				) pendingxp
				ON 
					pendingxp.ITEMTABLE_ID = cs.STAT_ID
				LEFT JOIN (
					SELECT ID, LEVEL_TO, ITEMTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
					WHERE CHARACTER_ID = %s
						AND ITEMTABLE = 'STAT'
				) pendingfreebie
				ON
					pendingfreebie.ITEMTABLE_ID = cs.STAT_ID
			WHERE
				cs.CHARACTER_ID = %s
				AND stat.ID = cs.STAT_ID
			ORDER BY
				stat.ORDERING)
			UNION
			(SELECT 
				'SKILL'					as type,
				'Abilities' 			as typename,
				skill.NAME 				as itemname, 
				skill.GROUPING 			as grp, 
				cs.LEVEL 					as level,
				cs.id						as id,
				cs.comment					as spec,
				pendingfreebie.LEVEL_TO 	as freebielevel,
				pendingfreebie.ID 			as freebieid,
				pendingfreebie.SPECIALISATION as freebiespec,
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				skill.SPECIALISATION_AT 	as specialisation_at,
				CASE skill.GROUPING WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "SKILL skill
				LEFT JOIN (
					SELECT ID, LEVEL, SKILL_ID, COMMENT
					FROM " . VTM_TABLE_PREFIX . "CHARACTER_SKILL
					WHERE
						CHARACTER_ID = %s
				) cs
				ON
					cs.SKILL_ID = skill.ID
				LEFT JOIN (
					SELECT ID, CHARTABLE_LEVEL, ITEMTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s
						AND ITEMTABLE = 'SKILL'
				) pendingxp
				ON 
					pendingxp.ITEMTABLE_ID = skill.ID
				LEFT JOIN (
					SELECT ID, LEVEL_TO, ITEMTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
					WHERE CHARACTER_ID = %s
						AND ITEMTABLE = 'SKILL'
				) pendingfreebie
				ON
					pendingfreebie.ITEMTABLE_ID = skill.ID
			ORDER BY
				skill.ORDERING DESC, skill.NAME)";
	$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID, $characterID, $characterID, $characterID);
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql);
	
	foreach ($results as $row) {
		$level = max($row->level, $row->freebielevel, $row->xplevel);
		if ($level >= $row->specialisation_at && $row->specialisation_at > 0) {
			if (isset($row->xplevel)) {
				$updatetable = 'PENDING_XP_SPEND';
				$tableid     = $row->xpid;
				$comment     = $row->xpspec;
			}
			elseif (isset($row->freebielevel)) {
				$updatetable = 'PENDING_FREEBIE_SPEND';
				$tableid     = $row->freebieid;
				$comment     = $row->freebiespec;
			}
			else {
				$updatetable = 'CHARACTER_' . $row->type;
				$tableid     = $row->id;
				$comment     = $row->spec;
			}
			array_push($specialities, array(
					'name'    => $row->itemname,
					'title'    => $row->typename,
					'updatetable' => $updatetable,
					'tableid' => $tableid,
					'level'   => $level,
					'grp'     => $row->grp,
					'comment' => $comment,
					'spec_at' => $row->specialisation_at));
		}
	}
	
	$sql = "SELECT
				'MERIT'					as type,
				'Merits and Flaws'		as typename,
				merit.NAME 				as itemname, 
				merit.GROUPING 			as grp, 
				merit.VALUE 			as level,
				cm.id					as id,
				cm.COMMENT				as spec,
				pendingfreebie.ID 		as freebieid,
				pendingfreebie.SPECIALISATION as freebiespec,
				pendingxp.ID 			as xpid,
				pendingxp.SPECIALISATION as xpspec,
				merit.HAS_SPECIALISATION    as has_specialisation
			FROM
				" . VTM_TABLE_PREFIX . "MERIT merit
				LEFT JOIN (
					SELECT ID, LEVEL, MERIT_ID, COMMENT
					FROM " . VTM_TABLE_PREFIX . "CHARACTER_MERIT
					WHERE
						CHARACTER_ID = %s
				) cm
				ON
					cm.MERIT_ID = merit.ID
				LEFT JOIN (
					SELECT ID, CHARTABLE_LEVEL, ITEMTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s
						AND ITEMTABLE = 'MERIT'
				) pendingxp
				ON 
					pendingxp.ITEMTABLE_ID = merit.ID
				LEFT JOIN (
					SELECT ID, LEVEL_TO, ITEMTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
					WHERE CHARACTER_ID = %s
						AND ITEMTABLE = 'MERIT'
				) pendingfreebie
				ON
					pendingfreebie.ITEMTABLE_ID = merit.ID
			ORDER BY
				merit.VALUE DESC, merit.NAME";
	$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
	$results = $wpdb->get_results($sql);
	
	foreach ($results as $row) {
		if ($row->has_specialisation == 'Y' && (isset($row->id) || isset($row->freebieid) || isset($row->xpid))) {
			if (isset($row->xplevel)) {
				$updatetable = 'PENDING_XP_SPEND';
				$tableid     = $row->xpid;
				$comment     = $row->spec;
			}
			elseif (isset($row->freebielevel)) {
				$updatetable = 'PENDING_FREEBIE_SPEND';
				$tableid     = $row->freebieid;
				$comment     = $row->freebiespec;
			}
			else {
				$updatetable = 'CHARACTER_' . $row->type;
				$tableid     = $row->id;
				$comment     = $row->xpspec;
			}
			array_push($specialities, array(
					'name'    => $row->itemname,
					'title'    => $row->typename,
					'updatetable' => $updatetable,
					'tableid' => $tableid,
					'level'   => $row->level,
					'grp'     => $row->grp,
					'comment' => $comment,
					'spec_at' => 1));
		}
	}
			
	
	//echo "<p>SQL: $sql</p>";
	//print_r($specialities);
	return $specialities;
} */
?>