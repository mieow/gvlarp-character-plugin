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
	$dataok = vtm_validate_chargen($laststep);
	if ($dataok) {
		$characterID = vtm_save_progress($laststep, $characterID);
	} else {
		$step = $laststep;
	}
	
	// output flow buttons
	$output .= vtm_render_flow($step, $characterID);
	
	$output .= "<div id='chargen-main'>";
	
	// output form to be filled in
	switch ($step) {
		case 1:
			$output .= vtm_render_basic_info($step, $characterID);
			break;
		case 2:
			$output .= vtm_render_attributes($step, $characterID);
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

function vtm_render_flow($step, $characterID) {

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
	
	// Obtain this information from a DB table. Save to the table on each check.
	$complete = array (
		'0' => 1,
		'1' => 0,
		'2' => 0,
		'3' => 0,
		'4' => 0,
		'5' => 0,
		'6' => 0,
		'7' => 0,
		'8' => 0,
		'9' => 0,
		'10' => 0,			// WILL BE OPTIONAL
		'11' => 0
	);
	
	$output .= "<div id='vtm-chargen-flow'>\n";
	
	$template = isset($_POST['chargen_template']) ? $_POST['chargen_template'] : ( isset($_POST['selected_template']) ? $_POST['selected_template'] : "");
	$output .= "<input type='hidden' name='selected_template' value='$template' />\n";
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
			elseif ($complete[$dependancy]) {
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
function vtm_render_attributes($step) {

	$output = "";
	
	$output .= "<h3>Step $step: Attributes</h3>";

	return $output;
}

function vtm_render_choose_template() {
	global $wpdb;

	$output = "";
	
	$output .= "<h3>Choose a template</h3>";
	
	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE WHERE VISIBLE = 'Y' ORDER BY NAME";
	$result = $wpdb->get_results($sql);
	print_r($result);
	
	$output .= "<p><label>Character Generation Template:</label> <select name='chargen_template'>";
	foreach ($result as $template) {
		$output .= "<option value='{$template->ID}'>{$template->NAME}</option>";
	}
	$output .= "</select></p>";
	
	$output .= "<p>Or, update a character: 
		<label>Reference:</label> <input type='text' name='chargen_reference' value='' size=5 ></p>";
	
	return $output;
}

function vtm_validate_chargen($laststep) {

	$ok = 1;
	
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
			break;
		default:
			$ok = 0;
	}
	
	if (!$ok)
		echo "<p class=''>Validation failed. Staying at step $laststep</p>\n";

	return $ok;
}

function vtm_save_progress($laststep, $characterID) {
	
	
	switch ($laststep) {
		case 1:
			$characterID = vtm_save_basic_info($characterID);
			break;
	
	}

	return $characterID;
}

function vtm_save_basic_info($characterID) {
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

	);
	print_r($dataarray);
	
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
					)
				);
		$characterID = $wpdb->insert_id;
		if ($characterID == 0) {
			echo "<p style='color:red'><b>Error:</b> Character could not be added</p>";
		}
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
		} else {
			$id = -1;
		}
	}
	elseif (isset($_POST['characterID'])) {
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
function vtm_get_player_name($playerid) {
	global $wpdb;
		
	$sql = "SELECT NAME FROM " . VTM_TABLE_PREFIX . "PLAYER WHERE ID = %s";
	$sql = $wpdb->prepare($sql, $playerid);
	return $wpdb->get_var($sql);

}
?>