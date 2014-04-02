<?php

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
	
	$output .= "<form id='chargen_form' method='post'>";
	
	$output .= "<p>Last at step $laststep</p>";
	print_r($_POST);
	// validate & save data from last step
	$dataok = vtm_validate_chargen($laststep, $templateID);
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
		default:
			$output .= vtm_render_choose_template();
	}
	
	// 3 buttons: Back, Check & Next
	if ($step - 1 > 0)
		$output .= "<input type='submit' name='chargen-step[" . ($step - 1) . "]' class='button-chargen-step' value='< Back' />";
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
	echo "<p>Selected Step $step</p>";
		
	return $step;
}

function vtm_render_flow($step, $characterID, $progress, $templateID) {

	$output = "";
	
	$buttons = array (
		'1' => array('title' => "Basic Information", 'dependency' => 0),
		'2' => array('title' => "Attributes", 'dependency' => 1),
		'3' => array('title' => "Abilities", 'dependency' => 2),
		'4' => array('title' => "Disciplines", 'dependency' => 3),
		'5' => array('title' => "Backgrounds", 'dependency' => 4),
		'6' => array('title' => "Virtues", 'dependency' => 5),
		'7' => array('title' => "Merits and Flaws", 'dependency' => 6),
		'8' => array('title' => "Freebie Points", 'dependency' => 7),
		'9' => array('title' => "Spend Experience", 'dependency' => 8),			// WILL BE OPTIONAL
		'10' => array('title' => "Extended Backgrounds", 'dependency' => 9)
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
					$shownew = 0;
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
			<th>Player Name*:</th>
			<td><input type='text' name='player' value='$playername'>";
	if ($shownew)
		$output .= "<input type='checkbox' name='newplayer' " . checked( 'on', $shownew, false) . "> : I am a new player";
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
						$grouptotals[$attribute->GROUPING] += $stats[$attribute->ID];
					else
						$grouptotals[$attribute->GROUPING] = $stats[$attribute->ID];
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
	
	// DONT SHOW APPEARANCE FOR NOSFERATU
		
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
		
		$output .= vtm_render_dot_select("attribute_value", $attribute->ID, isset($stats[$attribute->ID]) ? $stats[$attribute->ID] : -1);
		$output .= "</td><td>";
		$output .= stripslashes($attribute->DESCRIPTION);
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

function vtm_validate_chargen($laststep, $templateID) {

	$ok = 1;
	
	$settings = vtm_get_chargen_settings($templateID);

	
	switch ($laststep) {
		case 0:
			echo "<p class=''>Validation OK from step $laststep</p>\n";
			break;
		case 1:
			// VALIDATE BASIC INFO
			//		- character name is not blank
			//		- new player? player name is not duplicated
			//		- old player? player name is found
			//		- login name doesn't already exist (except if it's the currently logged in acct)
			//		- email address is not blank and looks valid
			//		- concept is not blank
			
			if (!isset($_POST['character']) || empty($_POST['character'])) {
				echo "<p>Please enter a character name</p>";
				$ok = 0;
			}
			
			$playername = isset($_POST['player']) ? $_POST['player'] : '';
			if (empty($playername)) {
				echo "<p>Please enter a player name</p>";
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
							echo "<p>Could not find a player with the name '$playername'. Did you mean '" . vtm_get_player_name($playerid) . "'?</p>";
						}
						else
							echo "<p>Could not find a player with the name '$playername'. Are you a new player?</p>";
					}
				} else {
					// new player
					if (isset($playerid)) {
						$ok = 0;
						echo "<p>A player already exists with the name '$playername'. Are you a returning player?</p>";
					}
				}
			}
			
			if (isset($_POST['wordpress_id'])) {
				$login = $_POST['wordpress_id'];
				if (username_exists( $login )) {
					$ok = 0;
					echo "<p>An account already exists with the login name '$login'. Please choose another.</p>";
				}
				elseif (!validate_username( $login )) {
					$ok = 0;
					echo "<p>Login name '$login' is invalid. Please choose another.</p>";
				}
			}
			
			if (!isset($_POST['email']) || empty($_POST['email'])) {
					$ok = 0;
					echo "<p>Email address is missing.</p>";
			} else {
				$email = $_POST['email'];
				if (!is_email($email)) {
					$ok = 0;
					echo "<p>Email address '$email' does not seem to be a valid email address.</p>";
				}
			}
			
			if (!isset($_POST['concept']) || empty($_POST['concept'])) {
				echo "<p>Please enter your character concept.</p>";
				$ok = 0;
			}
			
			break;
		case 2:
			// VALIDATE ATTRIBUTES
			// P/S/T
			//		- P / S / T options only picked once
			//		- correct number of points spent in each group
			// Point Spent
			//		- point total correct
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
							echo "<p>You have not selected if $group is Primary, Secondary or Tertiary</p>";
							$ok = 0;
						} else {
							$check += $sectiontype;
							$sectiontotal = 0;
							foreach  ($attributes as $attribute) {
								if ($attribute->GROUPING == $group) {
									$sectiontotal += isset($values[$attribute->ID]) ? $values[$attribute->ID] : 0;
								}
							}
							//echo "<p>group $group: target = " . $target[$sectiontype-1] . ", total = $sectiontotal</p>";
							if ($sectiontotal > $target[$sectiontype-1]) {
								echo "<p>You have spent too many dots in $group</p>";
								$ok = 0;
							}
							elseif ($sectiontotal < $target[$sectiontype-1])  {
								echo "<p>You haven't spent enough dots in $group</p>";
								$ok = 0;
							}
						}
					}
					if ($ok && $check != 6) {
						echo "<p>Check that you have chosen Primary, Secondary and Tertiary once only for each type of Attribute</p>";
						$ok = 0;
					}
					
					
				} else {
					$target = $settings['attributes-points'];
					$total = array_sum(array_values($values));
					
					if ($total > $target) {
						echo "<p>You have spent too many points</p>";
						$ok = 0;
					}
					elseif ($total < $target)  {
						echo "<p>You haven't spent enough points</p>";
						$ok = 0;
					}
				}
			} else {
				echo "<p>You have not spent any dots</p>";
				$ok = 0;
			}
			
			break;
		case 3:
			break;
		default:
			$ok = 0;
	}
	
	if (!$ok)
		echo "<p class=''>Validation failed. Staying at step $laststep</p>\n";

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
	
	}

	return $characterID;
}

function vtm_save_attributes($characterID) {
	global $wpdb;

	$newattributes = $_POST['attribute_value'];
	
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
	
	foreach ($newattributes as $attributeid => $value) {
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
				WHERE ID = %s";
		$wpdb->get_results($wpdb->prepare($sql,$curattributes[$map['Appearance']]));
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
			$_POST['character'], $_POST['priv_clan'], $_POST['player'], $_POST['concept'], $templateID);
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
			
			if ($result->PLAYER_ID != $pid)
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
		echo "<p>REF: $id-$wpid-$pid</p>";
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
			echo "Looked up template ID from character : $template<br />";
		} else {
			$template = $_POST['chargen_template'];
			echo "Looked up template ID from Step 0 : $template<br />";
		}
	} else {
		$template = isset($_POST['selected_template']) ? $_POST['selected_template'] : "";
		echo "Looked up template ID from last step : $template<br />";
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

	return array_combine($keys, $vals);
	
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

function vtm_render_dot_select($type, $itemid, $current, $free = 1, $max = 5) {

	$output = "";
	$fulldoturl = plugins_url( 'gvlarp-character/images/viewfulldot.jpg' );
	
	$output .= "<fieldset class='dotselect'>";
	
	//$output .= "<img alt='*' width=14 src='$fulldoturl'>";
	for ($i = ($max - $free) ; $i >= 0 ; $i--) {
		$radioid = "dot_{$type}_{$itemid}_{$i}";
		$output .= "<input type='radio' id='$radioid' name='" . $type . "[" . $itemid . "]' value='$i' ";
		$output .= checked($current, $i, false);
		$output .= " /><label for='$radioid' title='" . ($i + $free) . "'";
		
		if ($i < $free)
			$output .= " class='freedot'";
		
		$output .= ">&nbsp;</label>\n";
	}
	
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
?>