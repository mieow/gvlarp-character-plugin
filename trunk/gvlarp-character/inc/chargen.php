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
	
	$characterID = vtm_get_chargen_characterID();
	$laststep = isset($_POST['step']) ? $_POST['step'] : 0;
	$progress = isset($_POST['progress']) ? $_POST['progress'] : array('0' => 1);
	$templateID = vtm_get_templateid($characterID);
	
	if ($characterID == -1) {
		$output .= "<p>Invalid Reference</p>";
		$step = 0;
	} else {
		$step = vtm_get_step($characterID);
	}
	
	$output .= "<form id='chargen_form' method='post'>($characterID)";
	
	//$output .= "<p>Last at step $laststep</p>";
	print_r($_POST);
	// validate & save data from last step
	$dataok = vtm_validate_chargen($laststep, $templateID, $characterID);
	if ($dataok) {
		$characterID = vtm_save_progress($laststep, $characterID, $templateID);
		$progress[$laststep] = 1;
		
	} else {
		$step = $laststep;
		$progress[$laststep] = 0;
	}
	//print_r($progress);
	// setup progress
	for ($i = 0 ; $i <= 10 ; $i++) {
		$val = isset($progress[$i]) ? $progress[$i] : 0;
		$output .= "<input type='hidden' name='progress[$i]' value='$val' />\n";
	}
	
	// output flow buttons
	$output .= vtm_render_flow($step, $characterID, $progress, $templateID);
	
	$output .= "<div id='chargen-main'>";
	
	// output form to be filled in
	switch ($step) {
		case 1:
			$output .= vtm_render_basic_info($step, $characterID);
			break;
		case 2:
			$output .= vtm_render_attributes($step, $characterID, $templateID);
			break;
		case 3:
			$output .= vtm_render_abilities($step, $characterID, $templateID);
			break;
		case 4:
			$output .= vtm_render_chargen_disciplines($step, $characterID, $templateID);
			break;
		case 5:
			$output .= vtm_render_chargen_backgrounds($step, $characterID, $templateID);
			break;
		case 6:
			$output .= vtm_render_chargen_virtues($step, $characterID, $templateID);
			break;
		case 7:
			$output .= vtm_render_chargen_freebies($step, $characterID, $templateID);
			break;
		default:
			$output .= vtm_render_choose_template();
	}
	
	// 3 buttons: Back, Check & Next
	if ($step - 1 > 0)
		$output .= "<input type='submit' name='chargen-step[" . ($step - 1) . "]' class='button-chargen-step' value='< Step " . ($step - 1) . "' />";
	if ($step > 1)
		$output .= "<input type='submit' name='chargen-step[" . $step . "]' class='button-chargen-step' value='Update' />";
	if ($step + 1 <= 10)
		$output .= "<input type='submit' name='chargen-step[" . ($step + 1) . "]' class='button-chargen-step' value='Next >' />";
	else
		$output .= "<input type='submit' name='chargen-submit' class='button-chargen-step' value='Submit for Approval' />";
	
	$output .= "</div></form>";
	
	return $output;
}

function vtm_get_step() {

	$step = 0;
	
	// output step based on what button has been pressed
	if (isset($_POST['chargen-step'])) {
		$buttons = array_keys($_POST['chargen-step']);
		$step = $buttons[0];
	}
	//echo "<p>Selected Step $step</p>";
		
	return $step;
}

function vtm_render_flow($step, $characterID, $progress, $templateID) {

	$output = "";
	
	$buttons = array (
		'1'  => array('title' => "Basic Information", 'dependency' => 0),
		'2'  => array('title' => "Attributes", 'dependency' => 1),
		'3'  => array('title' => "Abilities", 'dependency' => 2),
		'4'  => array('title' => "Disciplines", 'dependency' => 3),
		'5'  => array('title' => "Backgrounds", 'dependency' => 4),
		'6'  => array('title' => "Virtues", 'dependency' => 5),
		'7'  => array('title' => "Freebie Points", 'dependency' => 6),
		'8'  => array('title' => "Spend Experience", 'dependency' => 7),			// WILL BE OPTIONAL
		'9'  => array('title' => "Specialities", 'dependency' => 8),
		'10' => array('title' => "Extended Backgrounds", 'dependency' => 1)
	);
	
	$output .= "<div id='vtm-chargen-flow'>\n";	
	$output .= "<input type='hidden' name='selected_template' value='$templateID' />\n";
	$output .= "<input type='hidden' name='characterID' value='$characterID' />\n";
	$output .= "<input type='hidden' name='step' value='$step' />\n";
	
	if ($step > 0) {
		$output .= "<ul>\n";
		foreach ($buttons as $stepid => $stepinfo) {
			$steptitle  = $stepinfo['title'];
			$dependancy = $stepinfo['dependency'];
			if ($step == $stepid) {
				$output .= "<li class='step-button step-selected'><strong>Step $stepid:</strong> $steptitle</li>";
			} 
			elseif (isset($progress[$dependancy]) && $progress[$dependancy]) {
				$output .= "<li class='step-button step-enable'><input type='submit' name='chargen-step[$stepid]' class='button-chargen-step' value='Step $stepid: $steptitle' /></li>\n";
			}
			else {
				$output .= "<li class='step-button step-disable'><strong>Step $stepid:</strong> $steptitle</li>\n";
			}
		}
		$output .= "</ul>\n";
	}
	$output .= "</ul></div>\n";

	return $output;

}

function vtm_render_basic_info($step, $characterID) {
	global $current_user;
	global $wpdb;

	$output = "";
	
	$mustbeloggedin = get_option( 'vtm_chargen_mustbeloggedin' );
	$chargenacct = get_option( 'vtm_chargen_wpaccount' );
	$clans = vtm_get_clans();
	$natures = vtm_get_natures();
	$config = vtm_getConfig();
	
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
					characters.CONCEPT
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
		
		$email = $result->EMAIL;
		$login = $result->WORDPRESS_ID;
		$playerid = $result->PLAYER_ID;
		$playername = stripslashes($result->player);
		$shownew = 'off';
		$character = stripslashes($result->charactername);
		
		$pub_clan = $result->PUBLIC_CLAN_ID;
		$priv_clan = $result->PRIVATE_CLAN_ID;
		$natureid = $result->NATURE_ID;
		$demeanourid = $result->DEMEANOUR_ID;
		$concept = stripslashes($result->CONCEPT);
		$playerset = 1;
		
	
	} else {
		$email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
		$login = isset($_POST['wordpress_id']) ? $_POST['wordpress_id'] : '';
		$playerid = isset($_POST['playerID']) ? $_POST['playerID'] : '';
		$playername = isset($_POST['player']) ? $_POST['player'] : '';
		$shownew = isset($_POST['newplayer']) ? $_POST['newplayer'] : 'off';
		$character = isset($_POST['character']) ? $_POST['character'] : '';
		$concept = isset($_POST['concept']) ? $_POST['concept'] : '';
		
		$pub_clan = 0;
		$priv_clan = 0;
		$natureid = 0;
		$demeanourid = 0;
		$playerset = 0;
	}
	
	if (is_user_logged_in()) {
		get_currentuserinfo();
		$userid       = $current_user->ID;
		
		if ($userid != $chargenacct) {
			if (empty($email))
				$email = $current_user->user_email;
			if (empty($login))
				$login = $current_user->user_login;
			
			if (!empty($playername)) {
				// find another account with that email to guess the player
				$otherlogins = get_users("search=$email&exclude=$userid&number=1");
				$player = vtm_get_player_from_login($otherlogins[0]->user_login);
				if (isset($player)) {
					$shownew = 'off';
					$playername = $player->NAME;
					$playerid = $player->ID;
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
			<th>Character Name*:</th>
			<td><input type='text' name='character' value='$character'> (ID: $characterID)</td>
		</tr>
		<tr>
			<th>Player Name*:</th>";
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
			<th>Actual Clan*:</th>
			<td><select name='priv_clan' autocomplete='off'>";
	foreach ($clans as $clan) {
		$output .= "<option value='{$clan->ID}' " . selected( $clan->ID, $priv_clan, false) . ">{$clan->NAME}</option>";
	}
	$output .= "</select></td>
		</tr>
		<tr>
			<th>Public Clan:</th>
			<td><select name='pub_clan' autocomplete='off'><option value='-1'>[Same as Actual]</option>";
	foreach ($clans as $clan) {
		$output .= "<option value='{$clan->ID}' " . selected( $clan->ID, $pub_clan, false) . ">{$clan->NAME}</option>";
	}
	$output .= "</select></td></tr>";
	
	if ($config->USE_NATURE_DEMEANOUR == 'Y') {
		$output .= "<tr><th>Nature*:</th><td><select name='nature' autocomplete='off'>";
		foreach ($natures as $nature) {
			$output .= "<option value='" . $nature->ID . "' " . selected( $nature->ID, $natureid, false) . ">" . $nature->NAME . "</option>";
		}
		$output .= "</select></td></tr>
		<tr><th>Demeanour*:</th><td><select name='demeanour' autocomplete='off'>";
		foreach ($natures as $nature) {
			$output .= "<option value='" . $nature->ID . "' " . selected( $nature->ID, $demeanourid, false) . ">" . $nature->NAME . "</option>";
		}
		$output .= "</select></td></tr>";
	}	
	$output .= "<tr>
			<th>Preferred login name:</th>
			<td><input type='text' name='wordpress_id' value='$login'></td>
		</tr>
		<tr>
			<th>Email Address*:</th>
			<td><input type='text' name='email' value='$email'></td></tr>
		<tr>
			<th>Concept*:</th>
			<td><textarea name='concept' rows='3' cols='50'>$concept</textarea></td></tr>
		</table>";

	return $output;
}
function vtm_render_attributes($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings   = vtm_get_chargen_settings($templateID);
	$attributes = vtm_get_chargen_attributes($characterID);
	$pending    = vtm_get_pending_freebies('STAT', 'freebie_stat', $characterID);  // name => value
	
	$output .= "<h3>Step $step: Attributes</h3>";
	
	if ($settings['attributes-method'] == "PST") {
		// Primary, Secondary, Tertiary
		$output .= "<p>You have {$settings['attributes-primary']} dots to spend on your Primary attributes, {$settings['attributes-secondary']} to spend on Secondary and {$settings['attributes-tertiary']} to spend on Tertiary.</p>";
	} else {
		$output .= "<p>You have {$settings['attributes-points']} dots to spend on your attributes</p>";
	}
	
	// read/guess initial values
	$sql = "SELECT STAT_ID, LEVEL FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT
			WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$keys = $wpdb->get_col($sql);
	if (count($keys) > 0) {
		$vals = $wpdb->get_col($sql,1);
		$stats = array_combine($keys, $vals);
		
		if ($settings['attributes-method'] == "PST") {
			$grouptotals = array();
			foreach  ($attributes as $attribute) {
				if (isset($stats[$attribute->ID])) {
					if (isset($grouptotals[$attribute->GROUPING]))
						$grouptotals[$attribute->GROUPING] += $stats[$attribute->ID] - 1;
					else
						$grouptotals[$attribute->GROUPING] = $stats[$attribute->ID] - 1;
				}
			}
			$groupselected = array();
			foreach ($grouptotals as $grp => $total) {
				switch($total) {
					case $settings['attributes-primary']: $groupselected[$grp] = 1;break;
					case $settings['attributes-secondary']: $groupselected[$grp] = 2;break;
					case $settings['attributes-tertiary']: $groupselected[$grp] = 3;break;
					default: $groupselected[$grp] = 0;
				}
			}
		}
		
	}
	elseif (isset($_POST['attribute_value'])) {
		$stats = $_POST['attribute_value'];
	}
			
	$group = "";
	foreach ($attributes as $attribute) {
		if ($attribute->GROUPING != $group) {
			if ($group != "")
				$output .= "</table>\n";
			$group = $attribute->GROUPING;
			$output .= "<h4>$group</h4><p>";
			if ($settings['attributes-method'] == "PST") {
				$val = isset($_POST[$group]) ? $_POST[$group] : (isset($groupselected[$group]) ? $groupselected[$group] : 0);
				$output .= vtm_render_pst_select($group, $val);
			}
			$output .= "</p>
				<input type='hidden' name='group[]' value='$group' />
				<table><tr><th>Attribute</th><th>Rating</th><th>Description</th></tr>\n";
		}
		$output .= "<tr><td class=\"gvcol_key\">" . $attribute->NAME . "</td>";
		$output .= "<td>";
		
		$output .= vtm_render_dot_select("attribute_value", 
						$attribute->ID, 
						isset($stats[$attribute->ID]) ? $stats[$attribute->ID] : -1, 
						1,5,isset($pending[$attribute->NAME]) ? $pending[$attribute->NAME] : 0);
		$output .= "</td><td>";
		$output .= stripslashes($attribute->DESCRIPTION);
		$output .= "</td></tr>\n";
	}
	$output .= "</table>\n";
	
	return $output;
}
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
	$output .= "<tr><td class=\"gvcol_key\">" . $virtues[$statid1]->NAME . "</td>";
	$output .= "<td>";
	$output .= vtm_render_dot_select("virtue_value", $statid1, 
					isset($stats[$statid1]) ? $stats[$statid1] : -1,
					1,5,isset($pending[$virtues[$statid1]->NAME]) ? $pending[$virtues[$statid1]->NAME] : 0);
	$output .= "</td><td>";
	$output .= stripslashes($virtues[$statid1]->DESCRIPTION);
	$output .= "</td></tr>\n";

	// Stat2
	$output .= "<tr><td class=\"gvcol_key\">" . $virtues[$statid2]->NAME . "</td>";
	$output .= "<td>";
	$output .= vtm_render_dot_select("virtue_value", $statid2, isset($stats[$statid2]) ? $stats[$statid2] : -1,
					1,5,isset($pending[$virtues[$statid2]->NAME]) ? $pending[$virtues[$statid2]->NAME] : 0);
	$output .= "</td><td>";
	$output .= stripslashes($virtues[$statid2]->DESCRIPTION);
	$output .= "</td></tr>\n";

	// Courage
	$output .= "<tr><td class=\"gvcol_key\">" . $virtues[$courage]->NAME . "</td>";
	$output .= "<td>";
	$output .= vtm_render_dot_select("virtue_value", $courage, isset($stats[$courage]) ? $stats[$courage] : -1, 
						1,5,isset($pending[$virtues[$courage]->NAME]) ? $pending[$virtues[$courage]->NAME] : 0);
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
	$meritsspent = 0;
	$flawsgained = 0;
	$points = $settings['freebies-points'] - $meritsspent + $flawsgained;
	$spent = 0;
	$spent += vtm_get_freebies_spent('STAT', 'freebie_stat', $characterID);
	$spent += vtm_get_freebies_spent('SKILL', 'freebie_skill', $characterID);
	$spent += vtm_get_freebies_spent('DISCIPLINE', 'freebie_discipline', $characterID);
	$spent += vtm_get_freebies_spent('BACKGROUND', 'freebie_background', $characterID);
	$spent += vtm_get_freebies_spent('MERIT', 'freebie_merit', $characterID);
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
						'background' => "Backgrounds",
						'merit'      => "Merits and Flaws",
						'path'       => "Paths",
					);
	$sectionorder   = array('stat', 'skill', 'background', 'disc', 'path', 'merit');
	
	$pendingSpends = array();
	$sectioncontent['stat']  = vtm_render_freebie_stats($characterID, $pendingSpends, $points);
	$sectioncontent['skill'] = vtm_render_freebie_skills($characterID, $pendingSpends, $points);
	$sectioncontent['disc']  = vtm_render_freebie_disciplines($characterID, $pendingSpends, $points);
	$sectioncontent['background'] = vtm_render_freebie_backgrounds($characterID, $pendingSpends, $points);
	$sectioncontent['merit'] = vtm_render_freebie_merits($characterID, $pendingSpends, $points);
	
	/* DISPLAY TABLES 
	-------------------------------*/
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
function vtm_render_abilities($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings   = vtm_get_chargen_settings($templateID);
	$abilities  = vtm_get_chargen_abilities($characterID);
	$pending    = vtm_get_pending_freebies('SKILL', 'freebie_skill', $characterID);  // name => value
		
	$output .= "<h3>Step $step: Abilities</h3>";
	$output .= "<p>You have {$settings['abilities-primary']} dots to spend on your Primary abilities, {$settings['abilities-secondary']} to spend on Secondary and {$settings['abilities-tertiary']} to spend on Tertiary.</p>";
	

	// read/guess initial values
	$sql = "SELECT SKILL_ID, LEVEL FROM " . VTM_TABLE_PREFIX . "CHARACTER_SKILL
			WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$keys = $wpdb->get_col($sql);
	if (count($keys) > 0) {
		$vals = $wpdb->get_col($sql,1);
		$skills = array_combine($keys, $vals);
		
		$grouptotals = array();
		foreach  ($abilities as $skill) {
			if (isset($skills[$skill->ID])) {
				if (isset($grouptotals[$skill->GROUPING]))
					$grouptotals[$skill->GROUPING] += $skills[$skill->ID];
				else
					$grouptotals[$skill->GROUPING] = $skills[$skill->ID];
			}
		}
		$groupselected = array();
		foreach ($grouptotals as $grp => $total) {
			switch($total) {
				case $settings['abilities-primary']: $groupselected[$grp] = 1;break;
				case $settings['abilities-secondary']: $groupselected[$grp] = 2;break;
				case $settings['abilities-tertiary']: $groupselected[$grp] = 3;break;
				default: $groupselected[$grp] = 0;
			}
		}
		
	}
	elseif (isset($_POST['ability_value'])) {
		$skills = $_POST['ability_value'];
	}
	$group = "";
	foreach ($abilities as $skill) {
		if ($skill->GROUPING != $group) {
			if ($group != "")
				$output .= "</table>\n";
			$group = $skill->GROUPING;
			$output .= "<h4>$group</h4><p>";
			$val = isset($_POST[$group]) ? $_POST[$group] : (isset($groupselected[$group]) ? $groupselected[$group] : 0);
			$output .= vtm_render_pst_select($group, $val);
			$output .= "</p>
				<input type='hidden' name='group[]' value='$group' />
				<table><tr><th>Ability</th><th>Rating</th><th>Description</th></tr>\n";
		}
		$output .= "<tr><td class=\"gvcol_key\">" . $skill->NAME . "</td>";
		$output .= "<td>";
		
		$output .= vtm_render_dot_select("ability_value", 
						$skill->ID, 
						isset($skills[$skill->ID]) ? $skills[$skill->ID] : -1, 
						0, 5, isset($pending[$skill->NAME]) ? $pending[$skill->NAME] : 0);
		$output .= "</td><td>";
		$output .= stripslashes($skill->DESCRIPTION);
		$output .= "</td></tr>\n";
	}
	$output .= "</table>\n";
	
	return $output;
}

function vtm_render_chargen_disciplines($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings    = vtm_get_chargen_settings($templateID);
	$disciplines = vtm_get_chargen_disciplines($characterID);
	$pending     = vtm_get_pending_freebies('DISCIPLINE', 'freebie_discipline', $characterID);  // name => value
		
	$output .= "<h3>Step $step: Disciplines</h3>";
	$output .= "<p>You have {$settings['disciplines-points']} dots to spend on your Disciplines</p>";
	

	// read initial values
	$sql = "SELECT DISCIPLINE_ID, LEVEL FROM " . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE
			WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$keys = $wpdb->get_col($sql);
	if (count($keys) > 0) {
		$vals = $wpdb->get_col($sql,1);
		$mydisc = array_combine($keys, $vals);
	}
	elseif (isset($_POST['discipline_value'])) {
		$mydisc = $_POST['discipline_value'];
	}
	$group = "";
	foreach ($disciplines as $discipline) {
		if ($discipline->GROUPING != $group) {
			if ($group != "")
				$output .= "</table>\n";
			$group = $discipline->GROUPING;
			$output .= "<h4>$group</h4>";
			$output .= "<table><tr><th>Discipline</th><th>Rating</th><th>Description</th></tr>\n";
		}
		$output .= "<tr><td class=\"gvcol_key\">" . $discipline->NAME . "</td>";
		$output .= "<td>";
		
		$output .= vtm_render_dot_select("discipline_value", 
						$discipline->ID, 
						isset($mydisc[$discipline->ID]) ? $mydisc[$discipline->ID] : -1,
						0, 5, isset($pending[$discipline->NAME]) ? $pending[$discipline->NAME] : 0);
		$output .= "</td><td>";
		$output .= stripslashes($discipline->DESCRIPTION);
		$output .= "</td></tr>\n";
	}
	$output .= "</table>\n";
	
	return $output;
}
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
		
		$output .= vtm_render_dot_select("background_value", 
						$background->ID, 
						isset($mybg[$background->ID]) ? $mybg[$background->ID] : -1,
						0, 5, isset($pending[$background->NAME]) ? $pending[$background->NAME] : 0);
		$output .= "</td><td>";
		$output .= stripslashes($background->DESCRIPTION);
		$output .= "</td></tr>\n";
	}
	$output .= "</table>\n";
	
	return $output;
}
function vtm_render_choose_template() {
	global $wpdb;

	$output = "";
	
	$output .= "<h3>Choose a template</h3>";
	
	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE WHERE VISIBLE = 'Y' ORDER BY NAME";
	$result = $wpdb->get_results($sql);
	//print_r($result);
	
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

	$ok = 1;
	
	$settings = vtm_get_chargen_settings($templateID);

	$errormessages = "";
	
	switch ($laststep) {
		case 0:
			break;
		case 1:
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
				if (username_exists( $login )) {
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
			
			break;
		case 2:
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
								if ($attribute->GROUPING == $group) {
									$sectiontotal += isset($values[$attribute->ID]) ? $values[$attribute->ID] - 1 : 0;
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
						//$ok = 0;
					}
				}
			} else {
				$errormessages .= "<li>WARNING: You have not spent any dots</li>";
				//$ok = 0;
			}
			
			break;
		case 3:
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
							if ($skill->GROUPING == $group) {
								$sectiontotal += isset($values[$skill->ID]) ? max(0,$values[$skill->ID]) : 0;
								if (isset($values[$skill->ID]) && $values[$skill->ID] > $settings['abilities-max']) {
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
			break;
		case 4:
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
			break;
		case 5:
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
			
			break;
		case 6:
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
			
			break;
		case 7:
			// VALIDATE FREEBIE POINTS
			//		Right number of points spent
			$meritsspent = 0;
			$flawsgained = 0;
			$points = $settings['freebies-points'] - $meritsspent + $flawsgained;
			
			$spent = 0;
			
			$spent += vtm_get_freebies_spent('STAT', 'freebie_stat', $characterID);
			$spent += vtm_get_freebies_spent('SKILL', 'freebie_skill', $characterID);
			$spent += vtm_get_freebies_spent('DISCIPLINE', 'freebie_discipline', $characterID);
			$spent += vtm_get_freebies_spent('BACKGROUND', 'freebie_background', $characterID);
			
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
			
			break;
		default:
			$ok = 0;
	}
	
	if (!$ok)
		$errormessages .= "<li>Please correct the errors before continuing</li>\n";
	
	if ($errormessages != "") {
		echo "<div class='gvxp_error'><ul>$errormessages</ul></div>";
	}
	

	return $ok;
}

function vtm_save_progress($laststep, $characterID, $templateID) {
	
	switch ($laststep) {
		case 1:
			$characterID = vtm_save_basic_info($characterID, $templateID);
			break;
		case 2:
			vtm_save_attributes($characterID);
			break;
		case 3:
			vtm_save_abilities($characterID);
			break;
		case 4:
			vtm_save_disciplines($characterID);
			break;
		case 5:
			vtm_save_backgrounds($characterID);
			break;
		case 6:
			vtm_save_virtues($characterID, $templateID);
			break;
		case 7:
			vtm_save_freebies($characterID, $templateID);
			break;
	
	}

	return $characterID;
}

function vtm_save_attributes($characterID) {
	global $wpdb;

	$newattributes = $_POST['attribute_value'];
	$attributes    = vtm_get_chargen_attributes();
	
	$sql = "SELECT cstat.STAT_ID, cstat.ID, stats.NAME
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat,
				" . VTM_TABLE_PREFIX . "STAT stats
			WHERE 
				stats.ID = cstat.STAT_ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$keys = $wpdb->get_col($sql);
	if (count($keys) > 0) {
		$vals = $wpdb->get_col($sql,1);
		$names = $wpdb->get_col($sql,2);
		$curattributes = array_combine($keys, $vals);
		$map = array_combine($names, $keys);
	} else {
		$curattributes = array();
		$map = array();
	}
	
	foreach ($attributes as $attribute) {
		$attributeid = $attribute->ID;
		$value = isset($newattributes[$attributeid]) ? $newattributes[$attributeid] : 0;
	
		$data = array(
			'CHARACTER_ID' => $characterID,
			'STAT_ID'      => $attributeid,
			'LEVEL'        => $value
		);
		if (isset($curattributes[$attributeid])) {
			// update
			$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_STAT",
				$data,
				array (
					'ID' => $curattributes[$attributeid]
				)
			);
		} else {
			// insert
			$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_STAT",
						$data,
						array ('%d', '%d', '%d')
					);
		}
	}
	
	// Delete appearance, if it's no longer needed
	if (isset($map['Appearance']) && !isset($newattributes[$map['Appearance']])) {
		// Delete
		$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT
				WHERE CHARACTER_ID = %s AND STAT_ID = %s";
		$wpdb->get_results($wpdb->prepare($sql,$characterID,$curattributes[$map['Appearance']]));
	}

}
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
	$items['STAT']        = vtm_get_chargen_stats($characterID, OBJECT_K);

	$new['SKILL']          = isset($_POST['freebie_skill']) ? $_POST['freebie_skill'] : array();
	$freebiecosts['SKILL'] = vtm_get_freebie_costs('SKILL');
	$current['SKILL']      = vtm_get_current_skills($characterID, OBJECT_K);
	$items['SKILL']        = vtm_get_chargen_abilities($characterID, 1, OBJECT_K);

	$new['DISCIPLINE']          = isset($_POST['freebie_discipline']) ? $_POST['freebie_discipline'] : array();
	$freebiecosts['DISCIPLINE'] = vtm_get_freebie_costs('DISCIPLINE', $characterID);
	$current['DISCIPLINE']      = vtm_get_current_disciplines($characterID, OBJECT_K);
	$items['DISCIPLINE']        = vtm_get_chargen_disciplines($characterID, OBJECT_K);

	$new['BACKGROUND']          = isset($_POST['freebie_background']) ? $_POST['freebie_background'] : array();
	$freebiecosts['BACKGROUND'] = vtm_get_freebie_costs('BACKGROUND', $characterID);
	$current['BACKGROUND']      = vtm_get_current_backgrounds($characterID, OBJECT_K);
	$items['BACKGROUND']        = vtm_get_chargen_backgrounds($characterID, OBJECT_K);
	
	foreach ($new as $type => $row) {
		foreach ($row as $name => $value) {
			if ($value > 0) {
				// Check for things like Lore_1 - multiples
				if (!isset($freebiecosts[$type][$name])) {
					$actualname = preg_replace("/_\d+$/", "", $name);
					//echo "$name becomes $actualname: {$items[$type][$actualname]->MULTIPLE}<br />";
					if (isset($items[$type][$actualname]->MULTIPLE) && $items[$type][$actualname]->MULTIPLE == 'Y') {
						$chartableid = isset($current[$type][$actualname]->chartableid) ? $current[$type][$actualname]->chartableid : 0;
						$levelfrom   = isset($current[$type][$actualname]->level_from)  ? $current[$type][$actualname]->level_from  : 0;
						$amount      = $freebiecosts[$type][$actualname][$levelfrom][$value];
						$itemid      = $items[$type][$actualname]->ID;
					} else {
						$chartableid = 0;
						$levelfrom   = 0;
						$amount      = 0;
						$itemid      = 0;
					}
				} else {
					$chartableid = isset($current[$type][$name]->chartableid) ? $current[$type][$name]->chartableid : 0;
					$levelfrom   = isset($current[$type][$name]->level_from)  ? $current[$type][$name]->level_from  : 0;
					$amount      = $freebiecosts[$type][$name][$levelfrom][$value];
					$itemid      = $items[$type][$name]->ID;
				}
				
				if ($value > $levelfrom) {
					$data = array (
						'CHARACTER_ID' => $characterID,
						'CHARTABLE'    => 'CHARACTER_' . $type,
						'CHARTABLE_ID' => $chartableid,
						'LEVEL_FROM'   => $levelfrom,
						'LEVEL_TO'     => $value,
						'AMOUNT'       => $amount,
						'ITEMTABLE'    => $type,
						'ITEMNAME'     => $name,
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

}
function vtm_save_abilities($characterID) {
	global $wpdb;


	$new = $_POST['ability_value'];
	
	$sql = "SELECT cskill.SKILL_ID, cskill.ID, skills.NAME
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_SKILL cskill,
				" . VTM_TABLE_PREFIX . "SKILL skills
			WHERE 
				cskill.SKILL_ID = skills.ID
				AND cskill.CHARACTER_ID = %s";
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
				'SKILL_ID'      => $id,
				'LEVEL'        => $value
			);
			if (isset($current[$id])) {
				//echo "<li>Updated $id at $value</li>";
				// update
				$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_SKILL",
					$data,
					array (
						'ID' => $current[$id]
					)
				);
			} else {
				//echo "<li>Added $id at $value</li>";
				// insert
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_SKILL",
							$data,
							array ('%d', '%d', '%d')
						);
			}
		}
	}
		
	// Delete anything no longer needed
	foreach ($current as $id => $value) {
	
		if (!isset($new[$id]) || $new[$id] <= 0) {
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_SKILL
					WHERE CHARACTER_ID = %s AND SKILL_ID = %s";
			$sql = $wpdb->prepare($sql,$characterID,$id);
			echo "<li>Delete $id ($sql)</li>";
			$wpdb->get_results($sql);
		}
	}
	
}
function vtm_save_disciplines($characterID) {
	global $wpdb;


	$new = $_POST['discipline_value'];
	
	$sql = "SELECT cdisc.DISCIPLINE_ID, cdisc.ID, disc.NAME
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE cdisc,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE 
				cdisc.DISCIPLINE_ID = disc.ID
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
	//print_r($new);
	//print_r($current);

	foreach ($new as $id => $value) {
		if ($value > 0) {
			$data = array(
				'CHARACTER_ID'  => $characterID,
				'DISCIPLINE_ID' => $id,
				'LEVEL'         => $value
			);
			if (isset($current[$id])) {
				// update
				$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE",
					$data,
					array (
						'ID' => $current[$id]
					)
				);
			} else {
				// insert
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE",
							$data,
							array ('%d', '%d', '%d')
						);
			}
		}
	}
		
	// Delete anything no longer needed
	foreach ($current as $id => $value) {
	
		if (!isset($new[$id]) || $new[$id] == 0) {
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE
					WHERE CHARACTER_ID = %s AND DISCIPLINE_ID = %s";
			$wpdb->get_results($wpdb->prepare($sql,$characterID,$id));
		}
	}

}
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
			echo "<li>Delete $id ($sql)</li>";
			$wpdb->get_results($sql);
		}
	}
	
}

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
	$generationid 	= $config->DEFAULT_GENERATION_ID;
	$domain 		= $config->HOME_DOMAIN_ID;
	$sect 			= $config->HOME_SECT_ID;
	$chartype	= $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARACTER_TYPE WHERE NAME = 'PC';");
	$charstatus	= $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARACTER_STATUS WHERE NAME = 'Alive';");
	$path		= $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE NAME = 'Humanity';");
	$genstatus	= $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARGEN_STATUS WHERE NAME = 'In Progress';");
	$template	= $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE WHERE ID = %s;", $templateID));
	if (isset($_POST['pub_clan']) && $_POST['pub_clan'] > 0)
		$pub_clan = $_POST['pub_clan'];
	else
		$pub_clan = $_POST['priv_clan'];
	
	$dataarray = array (
		'NAME'						=> $_POST['character'],
		'PUBLIC_CLAN_ID'			=> $pub_clan,
		'PRIVATE_CLAN_ID'			=> $_POST['priv_clan'],
		'GENERATION_ID'				=> $generationid,	// default from config, update later in backgrounds

		'DATE_OF_BIRTH'				=> 0,				// Set later in ext backgrounds
		'DATE_OF_EMBRACE'			=> 0,				// Set later in ext backgrounds
		'SIRE'						=> '',				// Set later in ext backgrounds
		'PLAYER_ID'					=> $playerid,

		'CHARACTER_TYPE_ID'			=> $chartype,		// player
		'CHARACTER_STATUS_ID'		=> $charstatus,		// active
		'CHARACTER_STATUS_COMMENT'	=> '',
		'ROAD_OR_PATH_ID'			=> $path,			// default from config

		'ROAD_OR_PATH_RATING'		=> 0,				// Set later in virtues
		'DOMAIN_ID'					=> $domain,			// default from config, update later in ext backgrounds
		'WORDPRESS_ID'				=> isset($_POST['wordpress_id']) ? $_POST['wordpress_id'] : '',
		'SECT_ID'					=> $sect,			// default from config

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
						'%d', 		'%d', 		'%s', 		'%d', 
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

	// any initial tables to set up?
	
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
				if ($current_user->ID != $wpid)
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
		$filter = "WHERE NAME != 'Appearance' AND ($filter)";
	
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

function vtm_get_chargen_roads() {
	global $wpdb;

	$sql = "SELECT ID, NAME
			FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH
			WHERE VISIBLE = 'Y'
			ORDER BY NAME";

	$roadsOrPaths = $wpdb->get_results($sql);
	return $roadsOrPaths;
}

function vtm_render_dot_select($type, $itemid, $current, $free = 1, $max = 5, $pending = 0) {

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

	$output = "<select name='$name'>\n";
	$output .= "<option value='-1'>[Select]</option>\n";
	$output .= "<option value='1' " . selected($selected, 1, false) . ">Primary</option>\n";
	$output .= "<option value='2' " . selected($selected, 2, false) . ">Secondary</option>\n";
	$output .= "<option value='3' " . selected($selected, 3, false) . ">Tertiary</option>\n";
	$output .= "</select>\n";
	
	return $output;
}

function vtm_render_freebie_stats($characterID, $pendingSpends, $points) {
	global $wpdb;
	
	$output = "";
	$rowoutput = "";
	$max2display = 5;
	$columns = 3;
	$fulldoturl = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	
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
	$freebiecosts = vtm_get_freebie_costs('STAT');

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_get_current_stats($characterID);
	
	// Current spent
	$current_stat = vtm_get_pending_freebies('STAT', 'freebie_stat', $characterID);
	
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
			$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
			$rowoutput .= "<fieldset class='dotselect'>";
			for ($i=$tmp_max2display;$i>=1;$i--) {
				$radioid = "dot_{$item->name}_{$item->itemid}_{$i}";
				$current = isset($current_stat[$item->name]) ? $current_stat[$item->name] : 0;
				
				if ($item->level_from >= $i)
					$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
				elseif (isset($freebiecosts[$item->name][$item->level_from][$i])) {
					$cost = $freebiecosts[$item->name][$item->level_from][$i];
					$rowoutput .= "<input type='radio' id='$radioid' name='freebie_stat[" . $item->name . "]' value='$i' ";
					$rowoutput .= checked($current, $i, false);
					$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
					$rowoutput .= ">&nbsp;</label>\n";
				}
				else {
					$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
				}
			}
			$radioid = "dot_{$item->name}_{$item->itemid}_clear";
			$rowoutput .= "<input type='radio' id='$radioid' name='freebie_stat[" . $item->name . "]' value='0' ";
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
	
	//print_r($items);
	
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
				$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
				$rowoutput .= "<fieldset class='dotselect'>";
				for ($i=$tmp_max2display;$i>=1;$i--) {
					$radioid = "dot_{$skillname}_{$j}_{$item->itemid}_{$i}";
					$current = isset($currentpending[$skillname]) ? $currentpending[$skillname] : 0;
					
					if ($item->level_from >= $i)
						$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
					elseif (isset($freebiecosts[$item->name][$item->level_from][$i])) {
						$cost = $freebiecosts[$item->name][$item->level_from][$i];
						$rowoutput .= "<input type='radio' id='$radioid' name='freebie_skill[" . $skillname . "]' value='$i' ";
						$rowoutput .= checked($current, $i, false);
						$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
						$rowoutput .= ">&nbsp;</label>\n";
					}
					else {
						$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
						//$rowoutput .= "itemname: {$item->name}, skillname: $skillname, levelfrom: {$item->level_from} i: $i";
					}
				}
				$radioid = "dot_{$skillname}_{$item->itemid}_clear";
				$rowoutput .= "<input type='radio' id='$radioid' name='freebie_skill[" . $skillname . "]' value='0' ";
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


	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_get_freebie_costs('DISCIPLINE', $characterID);

	// display stats to buy
	//	hover over radiobutton to show the cost
	$items = vtm_get_current_disciplines($characterID);
	
	// Current spent
	$currentpending = vtm_get_pending_freebies('DISCIPLINE', 'freebie_discipline', $characterID);
	
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
			$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
			$rowoutput .= "<fieldset class='dotselect'>";
			for ($i=$tmp_max2display;$i>=1;$i--) {
				$radioid = "dot_{$item->name}_{$item->itemid}_{$i}";
				$current = isset($currentpending[$item->name]) ? $currentpending[$item->name] : 0;
				
				if ($levelfrom >= $i)
					$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
				elseif (isset($freebiecosts[$item->name][$levelfrom][$i])) {
					$cost = $freebiecosts[$item->name][$levelfrom][$i];
					$rowoutput .= "<input type='radio' id='$radioid' name='freebie_discipline[" . $item->name . "]' value='$i' ";
					$rowoutput .= checked($current, $i, false);
					$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
					$rowoutput .= ">&nbsp;</label>\n";
				}
				else {
					$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
					//$rowoutput .= "itemname: {$item->name}, name: $item->name, levelfrom: {$item->level_from} i: $i";
				}
			}
			$radioid = "dot_{$item->name}_{$item->itemid}_clear";
			$rowoutput .= "<input type='radio' id='$radioid' name='freebie_discipline[" . $item->name . "]' value='0' ";
			//$rowoutput .= checked($current, 0, false);
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
			$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->name) . "</span></th><td>\n";
			$rowoutput .= "<fieldset class='dotselect'>";
			for ($i=$tmp_max2display;$i>=1;$i--) {
				$radioid = "dot_{$item->name}_{$item->itemid}_{$i}";
				$current = isset($currentpending[$item->name]) ? $currentpending[$item->name] : 0;
				
				if ($levelfrom >= $i)
					$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
				elseif (isset($freebiecosts[$item->name][$levelfrom][$i])) {
					$cost = $freebiecosts[$item->name][$levelfrom][$i];
					$rowoutput .= "<input type='radio' id='$radioid' name='freebie_background[" . $item->name . "]' value='$i' ";
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
			$radioid = "dot_{$item->name}_{$item->itemid}_clear";
			$rowoutput .= "<input type='radio' id='$radioid' name='freebie_background[" . $item->name . "]' value='0' ";
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
								
			$current = isset($currentpending[$item->name]) ? $currentpending[$item->name] : 0;
			$levelfrom = isset($item->level_from) ? $item->level_from : 0;
			$cost      = $freebiecosts[$item->name][0][1];
		
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
			$cbid = "cb_{$item->itemid}";
			$rowoutput .= "<tr><td><span>";
			$rowoutput .= "<input type='checkbox' name='freebie_merit[" . $item->name . "]' id='$cbid' value='$cost' ";
			$rowoutput .= checked($current, $cost, false);
			$rowoutput .= "/>\n";
			$rowoutput .= "<label for='$cbid'>" . stripslashes($item->name) . " ($cost)</label>\n";
			$rowoutput .= "</span></td></tr>\n";
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
	
	// if ($type == "MERIT") {
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
				item.MULTIPLE			as MULTIPLE
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
			default:
				$current = array();
		}
		$bought = $_POST[$postvariable];
		foreach ($bought as $name => $level_to) {
			$levelfrom = isset($current[$name]->level_from) ? $current[$name]->level_from : 0;
		
			if (!isset($current[$name])) {
				$actualname = preg_replace("/_\d+$/", "", $name);
				//echo "$name becomes $actualname, <br />";
				if (isset($current[$actualname]->multiple) && $current[$actualname]->multiple == 'Y') {
					//echo "$name - from: {$current[$actualname]->level_from}, to: {$level_to}, cost: {$freebiecosts[$actualname][$current[$actualname]->level_from][$level_to]}<br />";
					$spent += isset($freebiecosts[$actualname][$current[$actualname]->level_from][$level_to]) ? $freebiecosts[$actualname][$current[$actualname]->level_from][$level_to] : 0;
				}
			} else {
				$spent += isset($freebiecosts[$name][$levelfrom][$level_to]) ? $freebiecosts[$name][$levelfrom][$level_to] : 0;
			}
			//echo "<li>Running total is $spent. Bought $name to $level_to</li>";
		}
	} else {
		$sql = "SELECT SUM(AMOUNT) FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
				WHERE CHARACTER_ID = %s AND ITEMTABLE = %s";
		$sql = $wpdb->prepare($sql, $characterID, $table);
		$spent = $wpdb->get_var($sql);
	}

	return $spent;
}

function vtm_get_pending_freebies($table, $postvariable, $characterID) {
	global $wpdb;

	$pending = array();
	if (isset($_POST[$postvariable])) {
		$pending = $_POST[$postvariable];
	} 
	else {
		$sql = "SELECT freebie.ITEMNAME as name, freebie.LEVEL_TO as value
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
			WHERE
				freebie.CHARACTER_ID = %s
				AND freebie.ITEMTABLE = '$table'";
		$sql = $wpdb->prepare($sql, $characterID);
		//echo "SQL: $sql</p>";
		$keys = $wpdb->get_col($sql);
		if (count($keys) > 0) {
			$vals = $wpdb->get_col($sql,1);
			$pending = array_combine($keys, $vals);
		} 
	}

	return $pending;
}
?>