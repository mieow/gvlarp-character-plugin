<?php

function vtm_default_chargen_settings() {
	global $wpdb;
	
	$config = vtm_getConfig();
	$defaultgenid = $config->DEFAULT_GENERATION_ID;
	$defaultgenlvl = $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "GENERATION WHERE ID = %s", $defaultgenid));
	$limitgenlvl = $defaultgenlvl - 5;
	$limitgenid = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . VTM_TABLE_PREFIX . "GENERATION WHERE NAME = %s", $limitgenlvl));
	$limitgenid = $limitgenid ? $limitgenid : 1;

	return array(
		'attributes-method'    => "PST", 	// or 'point'
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
		'virtues-free-dots'    => 'humanityonly',  // 'yes', 'no', 'humanityonly'
		'virtues-points'       => 7,
		'road-multiplier'      => 1,
		'merits-max'           => 7,
		'flaws-max'            => 7,
		'freebies-points'      => 15,
		'rituals-method'       => 'point',  // 'discipline', 'accumulate', 'point' or 'none'
		'rituals-points'       => 1,
		'limit-sect-method'    => 'none', 	// 'none', 'only', 'exclude'
		'limit-sect-id'        => 1,
		'limit-road-method'    => 'none', 	// 'none', 'only', 'exclude'
		'limit-road-id'        => 1,
		'limit-generation-low' => $limitgenid,   		// generation ID
	);

}

function vtm_chargen_flow_steps($characterID, $templateID) {
	global $wpdb;

	$xp = vtm_get_total_xp(0, $characterID);
	$questions = count(vtm_get_chargen_questions($characterID));
	$settings = vtm_get_chargen_settings($templateID);
	$chargenstatus = $wpdb->get_var($wpdb->prepare("SELECT cgs.NAME FROM " . VTM_TABLE_PREFIX . "CHARACTER c, " . VTM_TABLE_PREFIX . "CHARGEN_STATUS cgs WHERE c.ID = %s AND c.CHARGEN_STATUS_ID = cgs.ID",$characterID));
	$feedback = $wpdb->get_var( $wpdb->prepare("SELECT NOTE_FROM_ST FROM " . VTM_TABLE_PREFIX . "CHARACTER_GENERATION WHERE CHARACTER_ID = %s", $characterID));
	$rituals = count(vtm_get_chargen_rituals($characterID));
	
	//print_r($settings);
	
	$buttons = array ();
	
	if (!empty($feedback)) {
		array_push($buttons,array(	
			'title'      => "Storyteller Feedback", 
			'function'   => 'vtm_render_feedback',
			'validate'   => 'vtm_validate_dummy',
			'save'       => 'vtm_save_dummy')
		);
	}
	
	array_push($buttons,array(	
		'title'      => "Basic Information", 
		'function'   => 'vtm_render_basic_info',
		'validate'   => 'vtm_validate_basic_info',
		'save'       => 'vtm_save_basic_info')
	);

	if ( ($settings['attributes-method'] == 'PST' && (
			$settings['attributes-tertiary'] > 0 || 
			$settings['attributes-secondary'] > 0 || 
			$settings['attributes-primary'] > 0)) ||
		 ($settings['attributes-method'] != 'PST' && $settings['attributes-points'] > 0)) {
		 
		array_push($buttons,array(
			'title' => "Attributes", 
			'function'   => 'vtm_render_attributes',
			'validate'   => 'vtm_validate_attributes',
			'save'       => 'vtm_save_attributes')
		);
	}
	if ( $settings['abilities-tertiary'] > 0 || 
		 $settings['abilities-secondary'] > 0 || 
		 $settings['abilities-primary'] > 0) {
		array_push($buttons,array(	'title' => "Abilities", 
				'function'   => 'vtm_render_abilities',
				'validate'   => 'vtm_validate_abilities',
				'save'       => 'vtm_save_abilities'));
	}
	if ($settings['disciplines-points'] > 0) {
		array_push($buttons,array(	'title' => "Disciplines", 
				'function'   => 'vtm_render_chargen_disciplines',
				'validate'   => 'vtm_validate_disciplines',
				'save'       => 'vtm_save_disciplines'));
	}
	if ($settings['backgrounds-points'] > 0) {
		array_push($buttons,array(	'title' => "Backgrounds", 
				'function'   => 'vtm_render_chargen_backgrounds',
				'validate'   => 'vtm_validate_backgrounds',
				'save'       => 'vtm_save_backgrounds'));
	}
	if ($settings['virtues-points'] > 0) {
		array_push($buttons,array(	'title' => "Virtues", 
				'function'   => 'vtm_render_chargen_virtues',
				'validate'   => 'vtm_validate_virtues',
				'save'       => 'vtm_save_virtues'));
	}
	if ($settings['freebies-points'] > 0) {
		array_push($buttons,array(	'title' => "Freebie Points", 
				'function'   => 'vtm_render_chargen_freebies',
				'validate'   => 'vtm_validate_freebies',
				'save'       => 'vtm_save_freebies'));
	}			
	if ($xp > 0) {
		array_push($buttons, array(
				'title'      => "Spend Experience", 
				'function'   => 'vtm_render_chargen_xp',
				'validate'   => 'vtm_validate_xp',
				'save'       => 'vtm_save_xp'));
	}
	if ($settings['rituals-method'] != 'none' && $rituals > 0) {
		array_push($buttons,array(	'title' => "Rituals", 
				'function'   => 'vtm_render_chargen_rituals',
				'validate'   => 'vtm_validate_rituals',
				'save'       => 'vtm_save_rituals'));
	}			
	
	array_push($buttons,array(
				'title'      => "Finishing Touches", 
				'function'   => 'vtm_render_finishing',
				'validate'   => 'vtm_validate_finishing',
				'save'       => 'vtm_save_finish'));
	
	// Only display if there are any background questions
	if ($questions > 0) {
		array_push($buttons,array(
					'title'      => "Extended Background", 
					'function'   => 'vtm_render_chargen_extbackgrounds',
					'validate'   => 'vtm_validate_history',
					'save'       => 'vtm_save_history'));
	}
	
	$title = $chargenstatus == 'Submitted' ? 'Review' : 'Submit';
	array_push($buttons,array(
				'title'      => $title, 
				'function'   => 'vtm_render_chargen_submit',
				'validate'   => 'vtm_validate_submit',
				'save'       => 'vtm_save_submit'));

	return $buttons;
}

function vtm_chargen_content_filter($content) {

	if (is_page(vtm_get_stlink_page('viewCharGen'))) {
		$mustbeloggedin = get_option('vtm_chargen_mustbeloggedin', '0') ? true : false;
		if (!$mustbeloggedin || (is_user_logged_in() && $mustbeloggedin))
			$content .= vtm_get_chargen_content();
		else
			$content .= "<p>You must be logged in to generate a character</p>\n";
	}
	return $content;
}

add_filter( 'the_content', 'vtm_chargen_content_filter' );


function vtm_get_chargen_content() {
	global $wpdb;

	$output = "";
	//print_r($_POST);
	
	$characterID = vtm_get_chargen_characterID();
	$laststep    = isset($_POST['step']) ? $_POST['step'] : 0;
	$progress    = isset($_POST['progress']) ? $_POST['progress'] : array('0' => 1);
	$templateID  = vtm_get_templateid($characterID);
	$emailconfirm = isset($_GET['confirm']);
		
	if ($characterID == -1) {
		$output .= "<div class='gvxp_error'><p>Invalid Reference</p>";
		if (isset($_POST['chargen_reference']) && $_POST['chargen_reference'] != '') {
			$split = explode("/",$_POST['chargen_reference']);
			if ($split[3] != '0000') {
				$output .= "<p>Check that you are logged
				in under the same account that you originally created the character under.</p>";
			}
		}
		$output .= "</div>\n";
		$step = 0;
		$chargenstatus = '';
	} else {
		$step = vtm_get_step($characterID, $templateID);
		$sql = $wpdb->prepare("SELECT cgs.NAME FROM " . VTM_TABLE_PREFIX . "CHARACTER c, " . VTM_TABLE_PREFIX . "CHARGEN_STATUS cgs WHERE c.ID = %s AND c.CHARGEN_STATUS_ID = cgs.ID",$characterID);
		//echo "<p>SQL: $sql</p>\n";
		$chargenstatus = $wpdb->get_var($sql);
		
		if ($emailconfirm) {
			$split = explode("/",$_GET['reference']);
			$chid = $split[0] * 1;
			$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_GENERATION",
					array('EMAIL_CONFIRMED' => 'Y'),
					array('CHARACTER_ID' => $chid)
				);
		
			if ($result) 
				echo "<p style='color:green'>Email address confirmed</p>\n";
			else if ($result !== 0) {
				$wpdb->print_error();
				echo "<p style='color:red'>Could not confirm email address</p>\n";
			}
		}
	}
	
	if ($step > 0 && isset($chargenstatus)) {
		$output .= "<p><strong>Character Generation Status:</strong> $chargenstatus, <strong>Character Reference:</strong> " . vtm_get_chargen_reference($characterID) . "</p>\n";
	}
	
	$output .= "<form id='chargen_form' method='post' autocomplete='off'>\n";
	
	// validate & save data from last step
	if ($chargenstatus == "Submitted") {
		$progress[$laststep] = 1;
	} else {
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
	}
	
	// output flow buttons
	$output .= vtm_render_flow($step, $characterID, $progress, $templateID);
	$flow = vtm_chargen_flow_steps($characterID, $templateID);
	
	$output .= "<div id='chargen-main'>\n";
	
	// output form to be filled in
	//echo "<li>step: $step, function: {$flow[$step-1]['function']}</li>\n";
	if ($step == 0)
		$output .= vtm_render_choose_template();
	else
		$output .= call_user_func($flow[$step-1]['function'], $step, $characterID, $templateID, $chargenstatus == 'Submitted');

	// 3 buttons: Back, Check & Next
	$output .= vtm_render_submit($step, count($flow), $chargenstatus);
	$output .= "</div></form>\n";
	
	return $output;
}

function vtm_render_submit($step, $finalstep, $chargenstatus) {

	$output = "";
	
	if ($step - 1 > 0)
		$output .= "<input type='submit' name='chargen-step[" . ($step - 1) . "]' class='button-chargen-step' value='< Step " . ($step - 1) . "' />\n";
	if ($step > 1 && $step < $finalstep && $chargenstatus != 'Submitted')
		$output .= "<input type='submit' name='chargen-step[" . $step . "]' class='button-chargen-step' value='Update' />\n";
	if ($step + 1 <= $finalstep)
		$output .= "<input type='submit' name='chargen-step[" . ($step + 1) . "]' class='button-chargen-step' value='Next >' />\n";
	elseif ($chargenstatus != 'Submitted')
		$output .= "<input type='submit' name='chargen-submit' class='button-chargen-step' value='Submit for Approval' />\n";

	return $output;
}


function vtm_get_step($characterID, $templateID) {

	$step = 0;
	
	// output step based on what button has been pressed
	if (isset($_POST['chargen-step'])) {
		$buttons = array_keys($_POST['chargen-step']);
		$step = $buttons[0];
	}
	elseif (isset($_POST['chargen-submit'])) {
		$buttons = vtm_chargen_flow_steps($characterID, $templateID);
		$step = count($buttons);
	}
	elseif (vtm_isST() && $characterID > 0) {
		$step = 1;
	}
	
	//echo "<li>Step $step ($characterID, $templateID)</li>\n";
	
	return $step;
}

function vtm_render_flow($step, $characterID, $progress, $templateID) {

	$output = "";
	
	$xp = vtm_get_total_xp(0, $characterID);
	
	$buttons = vtm_chargen_flow_steps($characterID, $templateID);
	
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
			$dependancy = 0; //$stepinfo['dependency'];
			if ($step == $stepid) {
				$output .= "<li class='step-button step-selected'><span><strong>Step $stepid:</strong> $steptitle</span></li>\n";
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
	$output .= "</div>\n";

	return $output;

}

function vtm_render_basic_info($step, $characterID, $templateID, $submitted) {
	global $current_user;
	global $wpdb;

	$output = "";
	
	$mustbeloggedin = get_option( 'vtm_chargen_mustbeloggedin' );
	$clans          = vtm_get_clans();
	$natures        = vtm_get_natures();
	$config         = vtm_getConfig();
	$settings = vtm_get_chargen_settings($templateID);
	
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
					characters.SECT_ID,
					chargen.EMAIL_CONFIRMED
				FROM
					" . VTM_TABLE_PREFIX . "CHARACTER characters
					LEFT JOIN (
						SELECT EMAIL_CONFIRMED, CHARACTER_ID
						FROM " . VTM_TABLE_PREFIX . "CHARACTER_GENERATION
						WHERE CHARACTER_ID = %s
					) chargen
					ON chargen.CHARACTER_ID = characters.ID,
					" . VTM_TABLE_PREFIX . "PLAYER players
				WHERE
					characters.PLAYER_ID = players.ID
					AND characters.ID = %s";
		$sql = $wpdb->prepare($sql, $characterID, $characterID);
		//echo "SQL: $sql<br />\n";
		$result = $wpdb->get_row($sql);
		//print_r($result);
		
		$email      = $result->EMAIL;
		$confirmed  = $result->EMAIL_CONFIRMED;
		$login      = $result->WORDPRESS_ID;
		$playerid   = $result->PLAYER_ID;
		$sectid     = $result->SECT_ID;
		$playername = htmlspecialchars(stripslashes($result->player), ENT_QUOTES);
		$shownew    = 'off';
		$character  = htmlspecialchars(stripslashes($result->charactername), ENT_QUOTES);
		
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
		$playername = isset($_POST['player']) ? htmlspecialchars(stripslashes($_POST['player']), ENT_QUOTES) : '';
		$shownew    = isset($_POST['newplayer']) ? $_POST['newplayer'] : 'off';
		$character  = isset($_POST['character']) ? htmlspecialchars(stripslashes($_POST['character']), ENT_QUOTES) : '';
		$concept    = isset($_POST['concept']) ? $_POST['concept'] : '';
		
		$pub_clan    = isset($_POST['pub_clan'])  ? $_POST['pub_clan']  : 0;
		$priv_clan   = isset($_POST['priv_clan']) ? $_POST['priv_clan'] : 0;
		$natureid    = isset($_POST['nature'])    ? $_POST['nature']    : 0;
		$demeanourid = isset($_POST['demeanour']) ? $_POST['demeanour'] : 0;
		$playerset   = 0;
		$confirmed   = 'N';
		
		if (isset($_POST['sect']))
			$sectid = $_POST['sect'];
		elseif ($settings['limit-sect-method'] == 'only')
			$sectid = $settings['limit-sect-id'];
		else
			$sectid = $config->HOME_SECT_ID;
		
		if (is_user_logged_in()) {
			get_currentuserinfo();
			$userid = $current_user->ID;
			
			if (empty($email)) $email = $current_user->user_email;
			
			$sql = "SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE WORDPRESS_ID = %s";
			$sql = $wpdb->prepare($sql, $current_user->user_login);
			$check = $wpdb->get_results($sql);
			if (empty($login) && count($check) == 0) $login = $current_user->user_login;
			
			if (empty($playername)) {
				// find other accounts with that email to guess the player
				$otherlogins = get_users("search=$email&exclude=$userid");
				foreach ($otherlogins as $other) {
					//echo "<li>{$other->user_login}</li>\n";
					$player      = vtm_get_player_from_login($other->user_login);
					if (isset($player)) {
						$shownew    = 'off';
						$playername = stripslashes(htmlspecialchars($player->NAME, ENT_QUOTES));
						$playerid   = $player->ID;
					}
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
			<td>\n";
	if ($submitted)
		$output .= $character;
	else
		$output .= "<input type='text' name='character' value='$character'>\n";
	$output .= " (ID: $characterID)</td>
		</tr>
		<tr>
			<th class='gvthleft'>Player Name*:</th>\n";
	if ($playerset) {
		$output .= "<td>$playername<input type='hidden' name='player' value='$playername'>\n";
	
	} else {
		$output .= "<td><input type='text' name='player' value='$playername'>\n";
		if ($shownew)
			$output .= "<input type='checkbox' name='newplayer' " . checked( 'on', $shownew, false) . "> : I am a new player";
	}
	$output .= "</td>
		</tr>
		<tr>
			<th class='gvthleft'>Actual Clan*:</th>
			<td>\n";
	if ($submitted) {
		$output .= $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "CLAN WHERE ID = %s", $priv_clan));
	} else {
		$output .= "<select name='priv_clan'>\n";
		foreach ($clans as $clan) {
			$output .= "<option value='{$clan->ID}' " . selected( $clan->ID, $priv_clan, false) . ">{$clan->NAME}</option>\n";
		}
		$output .= "</select>\n";
	}
	$output .= "</td>
		</tr>
		<tr>
			<th class='gvthleft'>Public Clan:</th>
			<td>\n";
	if ($submitted) {
		$output .= $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "CLAN WHERE ID = %s", $pub_clan));
	} else {
		$output .= "<select name='pub_clan'><option value='-1'>[Same as Actual]</option>\n";
		foreach ($clans as $clan) {
			$output .= "<option value='{$clan->ID}' " . selected( $clan->ID, $pub_clan, false) . ">{$clan->NAME}</option>\n";
		}
		$output .= "</select>\n";
	}
	$output .= "</td></tr><tr>
			<th class='gvthleft'>Sect:</th>
			<td>\n";
	if ($submitted) {
		$output .= $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "SECT WHERE ID = %s", $sectid));
	} 
	elseif ($settings['limit-sect-method'] == 'only') {
		$output .= "<input type='hidden' name='sect' value='{$settings['limit-sect-id']}' />\n";
		$output .= $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "SECT WHERE ID = %s", $sectid));
	}
	else {
		$output .= "<select name='sect'>\n";
		foreach (vtm_get_sects() as $sect) {
			if ($settings['limit-sect-method'] != 'exclude' ||
			    ($settings['limit-sect-method'] == 'exclude' && $settings['limit-sect-id'] != $sect->ID)) 
				$output .= "<option value='{$sect->ID}' " . selected( $sect->ID, $sectid, false) . ">{$sect->NAME}</option>\n";		
		}
		$output .= "</select>\n";
	}
	$output .= "</td></tr>\n";
	
	if ($config->USE_NATURE_DEMEANOUR == 'Y') {
		$output .= "<tr><th class='gvthleft'>Nature*:</th><td>\n";
		if ($submitted) {
			$output .= $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "NATURE WHERE ID = %s", $natureid));
		} else {
			$output .= "<select name='nature'>\n";
			foreach ($natures as $nature) {
				$output .= "<option value='" . $nature->ID . "' " . selected( $nature->ID, $natureid, false) . ">" . $nature->NAME . "</option>\n";
			}
			$output .= "</select>\n";
		}
		$output .= "</td></tr>
		<tr><th class='gvthleft'>Demeanour*:</th><td>\n";
		if ($submitted) {
			$output .= $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "NATURE WHERE ID = %s", $demeanourid));
		} else {
			$output .= "<select name='demeanour'>\n";
			foreach ($natures as $nature) {
				$output .= "<option value='" . $nature->ID . "' " . selected( $nature->ID, $demeanourid, false) . ">" . $nature->NAME . "</option>\n";
			}
			$output .= "</select>\n";
		}
		$output .= "</td></tr>\n";
	}	
	$output .= "<tr>
			<th class='gvthleft'>Login name*:</th>
			<td>\n";
	if ($submitted)
		$output .= $login;
	else
		$output .= "<input type='text' name='wordpress_id' value='$login'>\n";
	$output .= "</td>
		</tr>
		<tr>
			<th class='gvthleft'>Email Address*:</th>
			<td>\n";
	if ($submitted)
		$output .= $email;
	else
		$output .= "<input type='text' name='email' value='$email'>\n";
	if ($confirmed == 'Y') {
		$output .= "(confirmed)";
	} 
	elseif ($characterID > 0) {
		$output .= "<input type='submit' name='chargen-resend-email' class='' value='Resend confirmation email' />";
	}
	$output .= "</td></tr>
		<tr>
			<th class='gvthleft'>Concept*:</th>
			<td>\n";
	if ($submitted)
		$output .= $concept;
	else
		$output .= "<textarea name='concept' rows='3' cols='50'>$concept</textarea>\n";
	$output .= "</td></tr>
		</table>\n";

	return $output;
}

function vtm_render_feedback($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output = "";
	
	$output .= "<h3>Step $step: Storyteller Feedback</h3>\n";
	$feedback = $wpdb->get_var( $wpdb->prepare("SELECT NOTE_FROM_ST FROM " . VTM_TABLE_PREFIX . "CHARACTER_GENERATION WHERE CHARACTER_ID = %s", $characterID));

	$output .= "<p>Please review the feedback from the Storytellers and make any
				appropriate changes before resubmitting.</p>\n";
	$output .= "<p class='gvext_section'>" . stripslashes($feedback) . "</p>\n";

	return $output;
}

function vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp, $freebiecosts, 
		$postvariable, $showzeros, $issubmitted, $max2display = 5,
		$templatefree = array()) {
	
	$fulldoturl    = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$doturl        = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );

	$columns     = 3;
	$rowoutput   = "";
	
	// Get Posted data
	if (isset($_POST[$postvariable])) {
		$submitted = 1;
		$posted = $_POST[$postvariable];
	} else {
		$submitted = 0;
		$posted = array();
	}
	//print_r($pendingfb);

	$output = "";
	$maxitems = count($items);
	$itemcount = 0;
	if ($maxitems > 0) {
		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $item) {
			$colspan = $postvariable == 'freebie_merit' ? 1 : 2;
			$itemcount++;
			
			$loop = (isset($item->multiple) && $item->multiple == 'Y') ? 4 : 1;
			
			for ($j = 1 ; $j <= $loop ; $j++) {
				
				$name = sanitize_key($item->name);
				$key = (isset($item->multiple) && $item->multiple == 'Y') ? $name . "_" . $j : $name;
				if ($postvariable == 'freebie_stat' && $item->name == 'Path Rating') {
					$name = sanitize_key($item->grp);
				}
				// Need extra loops if we have some free dots
				if (isset($templatefree[$key]) && isset($item->multiple) && $item->multiple == 'Y')
					$loop++; 
			
				// Base level from free dots from template
				if (isset($templatefree[$key]->LEVEL))
					$levelfrom = $templatefree[$key]->LEVEL; 
				elseif (isset($templatefree[$name]->LEVEL) && $j == 1)
					// special case where there is only 1 free of a multiple skill so the key
					// was guessed wrongly by vtm_get_free_levels()
					$levelfrom = $templatefree[$name]->LEVEL; 
				else
					$levelfrom = 0;
			
				// Over-ridden by level from main table in database
				$levelfrom = isset($saved[$key]->level_from) ? $saved[$key]->level_from : $levelfrom;

				// Over-ridden by freebie point spends saved
				$current = isset($pendingfb[$key]) ? $pendingfb[$key]->value : $levelfrom;
				// Over-ridden by freebie point spends submitted
				$current = $submitted ? (isset($posted[$key]) ? $posted[$key] : $levelfrom) : $current;
				
				// xp point spends saved
				$levelxp = isset($pendingxp[$key]) ? $pendingxp[$key]->value : 0;
				
				// echo "<li>$key: from: $levelfrom, current: $current, xp: $levelxp, saved from: " .
				// (isset($saved[$key]->level_from) ? $saved[$key]->level_from : "not-set") . ", pendingfb: " .
				// (isset($pendingfb[$key]->value) ? $pendingfb[$key]->value : "not-set") . ", posted: " .
				// (isset($posted[$key]) ? $posted[$key] : "not-set") . ", submitted: $submitted</li>\n";
				
				// Specialisation
				if (isset($pendingfb[$key]) && $pendingfb[$key]->specialisation != '')
					$specialisation = $pendingfb[$key]->specialisation;
				elseif (isset($templatefree[$key]->SPECIALISATION)) 
					$specialisation = $templatefree[$key]->SPECIALISATION;
				elseif (isset($templatefree[$name]->SPECIALISATION) && $j == 1) 
					$specialisation = $templatefree[$name]->SPECIALISATION;
				else
					$specialisation = '';
				$specialisation = stripslashes($specialisation);
					
				// Pending Detail
				$detail = isset($pendingfb[$key]) ? $pendingfb[$key]->pending_detail : '';
				
				switch ($key) {
					case 'willpower': $max2display = 10; break;
				}
				
				if ($levelfrom > 0 || $showzeros) {
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
					$rowoutput .= "<input type='hidden' name='{$postvariable}_spec[" . $key . "]' value='$specialisation' />\n";
					$rowoutput .= "<input type='hidden' name='{$postvariable}_detail[" . $key . "]' value='$detail' />\n";
					$rowoutput .= "</td></tr>\n";
					
					$namehtml = "<span title='" . htmlspecialchars(stripslashes($item->description . ($specialisation == '' ? '' : " ($specialisation)")), ENT_QUOTES) . "'>" . stripslashes($item->name) . "</span>";
					
					if ($postvariable == 'freebie_merit') {
						$cost = $freebiecosts[$name][0][1];
						$cbid = "cb_{$key}_{$j}";
						//$rowoutput .= "<tr><td><span class='mfdotselect'>\n";
						$rowoutput .= "<tr><td class='mfdotselect'>\n";
						if ($issubmitted) {
							if ($current == $cost) {
								$rowoutput .= "<img src='$doturl' alt='X' /> ";
							} else {
								$rowoutput .= "<img src='$emptydoturl' alt='O' /> ";
							}
							$rowoutput .=  $namehtml . " ($cost)";
						} else {
							$rowoutput .= "<input type='checkbox' name='{$postvariable}[" . $key . "]' id='$cbid' value='$cost' ";
							$rowoutput .= checked($current, $cost, false);
							$rowoutput .= "/>\n";
							$rowoutput .= "<label for='$cbid'>" . $namehtml . " ($cost)</label>\n";
						}
						$rowoutput .= "</td></tr>\n";
					
					} else {
						//dots row
						$flag = 0;
						$rowoutput .= "<tr><th class='gvthleft'>" . $namehtml . "</th><td>\n";
						$rowoutput .= "<fieldset class='dotselect'>\n";
						for ($i=$max2display;$i>=1;$i--) {
							$radioid = "dot_{$key}_{$i}_{$j}";
							
							if ($levelfrom >= $i)
								// Base level from main table in database
								$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />\n";
							elseif ($issubmitted || (isset($pendingxp[$key]) && $pendingxp[$key]->value != 0) ) {
								// Lock if there are any xp spends for this item
								if ($current >= $i)
									$rowoutput .= "<img src='$doturl' alt='*' id='$radioid' />\n";
								elseif ($levelxp >= $i)
									$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />\n";
								else
									$rowoutput .= "<img src='$emptydoturl' alt='*' id='$radioid' />\n";
							} else {
								// Display dot to buy, if it can be bought
								if (isset($freebiecosts[$name][$levelfrom][$i])) {
									$cost = $freebiecosts[$name][$levelfrom][$i];
									$rowoutput .= "<input type='radio' id='$radioid' name='{$postvariable}[{$key}]' value='$i' ";
									$rowoutput .= checked($current, $i, false);
									$rowoutput .= " /><label for='$radioid' title='Level $i ($cost freebies)'";
									$rowoutput .= ">&nbsp;</label>\n";
									$flag = 1;
								}
								else {
									$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />\n";
								}
							}
						}
						if (!$issubmitted) {
							$radioid = "dot_{$key}_{$j}_clear";
							$rowoutput .= "<input type='radio' id='$radioid' name='{$postvariable}[{$key}]' value='0' ";
							$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
						}
						$rowoutput .= "</fieldset></td></tr>\n";
						
						// Ensure that freebie spends don't get lost when an XP
						// spend has blocked the user from changing the level
						if (!$flag && $current > 0) {
						
							$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
							$rowoutput .= "<input type='hidden' name='{$postvariable}[{$key}]' value='$current' />\n";
							$rowoutput .= "</td></tr>\n";
						
						}
					}
				}
			}
		}
	
	}
	
	if ($rowoutput != "") {
		$rowoutput .= "</table></td>";
		if ($col != $columns && $grpcount > $columns) {
			$rowoutput .= "<td class='gvxp_col' colspan='" . ($columns - $col) . "'></td>";
		}
		$rowoutput .= "</tr>\n";
	}
	
	return $rowoutput;
}

function vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
	$pendingxp, $postvariable, $showzeros, $issubmitted, $fbcosts = array(),
	$max2display = 5, $templatefree = array()) {

	$rowoutput = "";
	$columns = 3;
	$fulldoturl = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$doturl        = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );
	
	// Get Posted data
	if (isset($_POST[$postvariable])) {
		$submitted = 1;
		$posted = $_POST[$postvariable];
	} else {
		$submitted = 0;
		$posted = array();
	}
		
	$grp = "";
	$grpcount = 0;
	$col = 0;
	foreach ($items as $item) {
		$loop = (isset($item->multiple) && $item->multiple == 'Y') ? 4 : 1;

		for ($j = 1 ; $j <= $loop ; $j++) {
			$name = sanitize_key($item->name);
			$key = (isset($item->multiple) && $item->multiple == 'Y') ? $name . "_" . $j : $name;

			// Need extra loops if we have some free dots
			if (isset($templatefree[$key]) && isset($item->multiple) && $item->multiple == 'Y')
				$loop++; 

			switch ($key) {
				case 'willpower':   $max2display = 10; break;
				case 'conscience':  $max2display = 5; break;
				case 'conviction':  $max2display = 5; break;
				case 'selfcontrol': $max2display = 5; break;
				case 'courage':     $max2display = 5; break;
				case 'instinct':    $max2display = 5; break;
			}
			$colspan = ($postvariable == 'xp_merit' || $postvariable == 'xp_ritual') ? 1 : 2;
				
			// Base level from free dots from template
			if (isset($templatefree[$key]->LEVEL))
				$levelfrom = $templatefree[$key]->LEVEL; 
			elseif (isset($templatefree[$name]->LEVEL) && $j == 1)
				// special case where there is only 1 free of a multiple skill so the key
				// was guessed wrongly by vtm_get_free_levels()
				$levelfrom = $templatefree[$name]->LEVEL; 
			else
				$levelfrom = 0;
			
			// Over-ridden by level from main table in database
			$levelfrom = isset($saved[$key]->level_from) ? $saved[$key]->level_from : $levelfrom;

			// level from freebie point spends saved
			$levelfb = isset($pendingfb[$key]) ? $pendingfb[$key]->value : $levelfrom;

			// level from xp point spends saved
			$current = isset($posted[$key]) ? $posted[$key] : 
						(isset($pendingxp[$key]) ? $pendingxp[$key]->value : 0);
			
			// Specialisation
			if (isset($pendingxp[$key]) && $pendingxp[$key]->specialisation != '')
				$specialisation = $pendingxp[$key]->specialisation;
			elseif (isset($pendingfb[$key]) && $pendingfb[$key]->specialisation != '')
				$specialisation = $pendingfb[$key]->specialisation;
			elseif (isset($templatefree[$key]->SPECIALISATION)) 
				$specialisation = $templatefree[$key]->SPECIALISATION;
			else
				$specialisation = '';
			$specialisation = stripslashes($specialisation);

			$namehtml = "<span title='" . htmlspecialchars(stripslashes($item->description . ($specialisation == '' ? '' : " ($specialisation)")), ENT_QUOTES) . "'>" . stripslashes($item->name) . "</span>";
			
			// Merit stuff
			$meritcost  = $postvariable == 'xp_merit' ? $xpcosts[$name][0][1] : 0;
			$meritlevel = $postvariable == 'xp_merit' ? $fbcosts[$name][0][1] : 0;
			// Ritual stuff
			$ritualcost  = $postvariable == 'xp_ritual' ? $xpcosts[$name][0][1] : 0;
			$rituallevel = $postvariable == 'xp_ritual' ? $item->level : 0;
			
			//echo "<li>$key/$name - from: $levelfrom, fb: $levelfb, current: $current, spec: $specialisation</li>\n";
			if ($postvariable == 'xp_merit' && $meritcost > 0 && $meritlevel !== 0)
				$dodisplay = 1;
			elseif ($postvariable == 'xp_ritual' && $ritualcost > 0)
				$dodisplay = 1;
			elseif ($postvariable != 'xp_merit' && $postvariable != 'xp_ritual' && ($levelfrom > 0 || $showzeros))
				$dodisplay = 1;
			else
				$dodisplay = 0;
			
			//print "<li>$postvariable {$item->name} $key $j $dodisplay , cost: $meritcost, level: $meritlevel</li>";
			if ($dodisplay) {
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
				//$comment = isset()
				$rowoutput .= "<tr style='display:none'>
					<td colspan=$colspan>\n
					<input type='hidden' name='{$postvariable}_comment[$key]' value='$specialisation' />\n";
				$rowoutput .= "</td></tr>\n";

				if ($postvariable == 'xp_merit') {
					$cbid = "cb_{$j}_{$key}";
					$rowoutput .= "<tr><td class='mfdotselect'>\n";
					if ($issubmitted) {
						if ($current) {
							$rowoutput .= "<img src='$doturl' alt='X' /> ";
						} else {
							$rowoutput .= "<img src='$emptydoturl' alt='O' /> ";
						}
						$rowoutput .=  $namehtml . " ($meritlevel) - $meritcost XP";
					} else {
						$rowoutput .= "<input type='checkbox' name='{$postvariable}[" . $key . "]' id='$cbid' value='$meritlevel' ";
						if ($current) {
							$rowoutput .= checked($current, $current, false);
						}
						$rowoutput .= "/>\n";
						$rowoutput .= "<label for='$cbid'>" . $namehtml . " ($meritlevel) - $meritcost XP</label>\n";
					}
					$rowoutput .= "</td></tr>\n";
				} 
				elseif ($postvariable == 'xp_ritual') {
					$cbid = "cb_{$j}_{$key}";
					$rowoutput .= "<tr><td class='mfdotselect'>\n";
					if ($issubmitted) {
						if ($current) {
							$rowoutput .= "<img src='$doturl' alt='X' /> ";
						} else {
							$rowoutput .= "<img src='$emptydoturl' alt='O' /> ";
						}
						$rowoutput .=  $namehtml . " (level $rituallevel) - $ritualcost XP";
					} 
					elseif ($saved[$key]->level > 0) {
						$rowoutput .= "<img src='$fulldoturl' alt='X' />\n";
						$rowoutput .= $namehtml . " (level $rituallevel)\n";
					}
					else {
						$rowoutput .= "<input type='checkbox' name='{$postvariable}[" . $key . "]' id='$cbid' value='$rituallevel' ";
						if ($current) {
							$rowoutput .= checked($current, $current, false);
						}
						$rowoutput .= "/>\n";
						$rowoutput .= "<label for='$cbid'>" . $namehtml . " (level $rituallevel) - $ritualcost XP</label>\n";
					}
					$rowoutput .= "</td></tr>\n";
				}
				else {
					//dots row
					$rowoutput .= "<tr><th class='gvthleft'>" . $namehtml . "</th><td>\n";
					$rowoutput .= "<fieldset class='dotselect'>\n";
					for ($i=$max2display;$i>=1;$i--) {
						$radioid = "dot_{$key}_{$i}_{$j}";
						
						if ($levelfrom >= $i)
							$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />\n";
						elseif (isset($pendingfb[$key]) && $levelfb >= $i)
							$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />\n";
						elseif ($issubmitted) {
							if ($current >= $i)
								$rowoutput .= "<img src='$doturl' alt='*' id='$radioid' />\n";
							else
								$rowoutput .= "<img src='$emptydoturl' alt='*' id='$radioid' />\n";
						}
						elseif (isset($xpcosts[$name][$levelfb][$i])) {
							$cost = $xpcosts[$name][$levelfb][$i];
							$rowoutput .= "<input type='radio' id='$radioid' name='{$postvariable}[$key]' value='$i' ";
							$rowoutput .= checked($current, $i, false);
							$rowoutput .= " /><label for='$radioid' title='Level $i ($cost xp)'";
							$rowoutput .= ">&nbsp;</label>\n";
						}
						else {
							$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />\n";
						}
					}
					if (!$issubmitted) {
						$radioid = "dot_{$key}_{$j}_clear";
						$rowoutput .= "<input type='radio' id='$radioid' name='{$postvariable}[$key]' value='0' ";
						$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
					}
					$rowoutput .= "</fieldset></td></tr>\n";
				}
			}
		}
	}
	
	if ($rowoutput != "") {
		$rowoutput .= "</table></td>";
		if ($col != $columns && $grpcount > $columns) {
			$rowoutput .= "<td class='gvxp_col' colspan='" . ($columns - $col) . "'></td>";
		}
		$rowoutput .= "</tr>\n";
	}
	
	return $rowoutput;

}

function vtm_render_chargen_section($saved, $isPST, $pdots, $sdots, $tdots, $freedot,
	$items, $posted, $pendingfb, $pendingxp, $title, $postvariable, $submitted,
	$maxdots = 5, $templatefree = array()) {

	$output = "";

	$class = $postvariable == 'ritual_value' ? "class='ritrowselect'" : "";
	
	// Make a guess from saved levels which is Primary/Secondary/Tertiary
	if (count($saved) > 0 || count($posted) > 0) {
		if ($isPST) {
			$info = vtm_get_pst($saved, $posted, $items, $pdots, $sdots, $tdots,
				$freedot, $templatefree);
			//print_r($info);
		}
	} else {
		$info['pst']     = array();
		$info['totals']  = array();
		$info['correct'] = array();
	}

	//print_r($items);
	$group = "";
	foreach ($items as $item) {
	
		// Heading and Primary/Secondary/Tertiary pull-down
		if (sanitize_key($item->grp) != $group) {
			if ($group != "")
				$output .= "</table>\n";
			$group = sanitize_key($item->grp);
			$output .= "<h4>{$item->grp}</h4><p>\n";
			if ($isPST) {
				$output .= vtm_render_pst_select($group, $info);
			}
			
			$output .= "</p><input type='hidden' name='group[]' value='$group' />";
			if ($postvariable == 'ritual_value')
				$output .= "<table><tr><th>$title</th><th>Description</th></tr>\n";
			else
				$output .= "<table><tr><th>$title</th><th>Rating</th><th>Description</th></tr>\n";
		}
				
		// Display Data
		$key   = sanitize_key($item->name);
		$level = isset($posted[$key]) ? $posted[$key] : (isset($saved[$key]->level_from) ? $saved[$key]->level_from : 0);  // currently selected or saved level
		if (isset($templatefree[$key]))
			$tpfree = $templatefree[$key]->LEVEL;
		else
			$tpfree = $freedot;
		
		if ($postvariable == 'ritual_value') {
			$id = "id$key";
			$output .= "<tr><td $class>";
			if (isset($pendingxp[$key])) {
				$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
				$output .= "<img src='$freebiedoturl' alt='*' />";
			}
			else
				$output .= "<input id='$id' name='ritual_value[$key]' type='checkbox' " . checked( $item->level, $level, false) . " value='{$item->level}'>";
			$output .= "<label for='$id'>Level {$item->level} - " . stripslashes($item->name) . "</label>";
			//$output .= "</td>\n";
		} else {
			$output .= "<tr><td $class>" . stripslashes($item->name) . "</td>\n";
			$output .= "<td $class>";
			
			$pending = isset($pendingfb[$key]->value) ? $pendingfb[$key]->value : 0 ;         // level bought with freebies
			$pending = isset($pendingxp[$key]->value) ? $pendingxp[$key]->value : $pending ;  // level bought with xp
			
			if ($postvariable == 'virtue_value' && $key == 'courage' 
				&& (isset($pendingfb['willpower']) || isset($pendingxp['willpower'])))
				$output .= vtm_render_dot_select($postvariable, $key, $level, $pending, $tpfree, $maxdots, 1);
			else
				$output .= vtm_render_dot_select($postvariable, $key, $level, $pending, $tpfree, $maxdots, $submitted);
		}
		
		$output .= "</td><td $class>\n";
		$output .= stripslashes($item->description);
		$output .= "</td></tr>\n";
	
	}
	$output .= "</table>\n";

	return $output;
}

function vtm_render_attributes($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output = "";
	$settings   = vtm_get_chargen_settings($templateID);
	$items      = vtm_get_chargen_attributes($characterID);
	
	$pendingfb  = vtm_get_pending_freebies('STAT', $characterID);  
	$pendingxp  = vtm_get_pending_chargen_xp('STAT', $characterID);  
	$geninfo = vtm_calculate_generation($characterID);

	//print_r($pendingxp);
	
	$output .= "<h3>Step $step: Attributes</h3>\n";
	
	if ($settings['attributes-method'] == "PST") {
		// Primary, Secondary, Tertiary
		$output .= "<p>You have {$settings['attributes-primary']} dots to spend on your Primary attributes, {$settings['attributes-secondary']} to spend on Secondary and {$settings['attributes-tertiary']} to spend on Tertiary.</p>\n";
	} else {
		$output .= "<p>You have {$settings['attributes-points']} dots to spend on your attributes</p>\n";
	}

	// Get levels saved into database
	$sql = "SELECT 
				stats.NAME as name, cs.STAT_ID as itemid, cs.LEVEL as level_from, cs.COMMENT as comment
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
		1, $items, $stats, $pendingfb, $pendingxp, 'Attributes', 'attribute_value', 
		$submitted,$geninfo['MaxDot']);
	
	return $output;
}

function vtm_render_chargen_virtues($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output = "";
	$config    = vtm_getConfig();
	$settings  = vtm_get_chargen_settings($templateID);
	$items     = vtm_get_chargen_virtues($characterID);
	$pendingfb = vtm_get_pending_freebies('STAT', $characterID);  // name => value
	$pendingxp = vtm_get_pending_chargen_xp('STAT', $characterID);  // name => value
	
	//print_r($items);
	
	$pendingroad = vtm_get_pending_freebies('ROAD_OR_PATH', $characterID);
	
	$output .= "<h3>Step $step: Virtues</h3>\n";
	$output .= "<p>You have {$settings['virtues-points']} dots to spend on your virtues.</p>\n";
	
	// read initial values
	$sql = "SELECT stats.NAME as name, cstat.STAT_ID as itemid, cstat.LEVEL as level_from
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat,
				" . VTM_TABLE_PREFIX . "STAT stats
			WHERE
				cstat.STAT_ID = stats.ID
				AND stats.GROUPING = 'Virtue'
				AND CHARACTER_ID = %s ";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//print_r($saved);

	$virtues = isset($_POST['virtue_value']) ? $_POST['virtue_value'] : array();
	
	// Display Path pull-down
	if (isset($_POST['path'])) {
		$selectedpath = $_POST['path'];
	}
	elseif ($settings['limit-road-method'] == 'only') {
		$selectedpath = $settings['limit-road-id'];
	}
	else {
		$selectedpath = $wpdb->get_var($wpdb->prepare("SELECT ROAD_OR_PATH_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	}
	$output .= "<p><label><strong>Path of Enlightenment:</strong></label> ";
	if ($submitted) {
		$pathname = $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $selectedpath));
		$output .= "<span>$pathname</span>\n";
	} 
	elseif ($settings['limit-road-method'] == 'only' || count($pendingroad) > 0) {
		$pathname = $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $selectedpath));
		$output .= "<input type='hidden' name='path' value='$selectedpath' />";
		$output .= "<span>$pathname</span>\n";
	}
	else {
		$output .= "<select name='path'>\n";
		foreach (vtm_get_chargen_roads() as $path) {
		//echo "<p>method: {$settings['limit-road-method']}, id: {$settings['limit-road-id']}, pathid: {$path->ID}</p>";
			if ($settings['limit-road-method'] != 'exclude' || 
					($settings['limit-road-method'] == 'exclude' && $settings['limit-road-id'] != $path->ID)) {
				$output .= "<option value='{$path->ID}' " . selected($path->ID, $selectedpath, false) . ">" . stripslashes($path->NAME) . "</option>\n";
			}
		}
		$output .= "</select>\n";
	}
	$output .= "</p>\n";

	$statkey1 = vtm_get_virtue_statkey(1, $selectedpath);
	$statkey2 = vtm_get_virtue_statkey(2, $selectedpath);

	$pathitems = array (
		$statkey1 => $items[$statkey1],
		$statkey2 => $items[$statkey2],
		'courage' => $items['courage']
	);
	//print_r($pathitems);
	
	$freedot = vtm_has_virtue_free_dot($selectedpath, $settings);
	
	if (count($pendingroad) > 0) {
		$submitted = 1;
		$output .= "<p>Please remove freebie point spends on your path if you want to alter your Virtues</p>";
	}
	if (isset($pendingfb['willpower']) || isset($pendingxp['willpower'])) {
		$output .= "<p>Please remove freebie or experience spends on Willpower if you want to alter Courage.</p>";
	}
	
	$output .= vtm_render_chargen_section($saved, false, 0, 0, 0, 
		$freedot, $pathitems, $virtues, $pendingfb, $pendingxp, 'Virtues', 'virtue_value',
		$submitted, 5);
	
	return $output;
}

function vtm_render_chargen_freebies($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	
	// Work out how much points are currently available
	$points = $settings['freebies-points'];
	$spent = vtm_get_freebies_spent($characterID, $templateID);
	$remaining = $points - $spent;
	
	$output .= "<h3>Step $step: Freebie Points</h3>\n";
	$output .= "<p>\n";
	if ($settings['merits-max'] > 0)
		$output .= "You can have a maximum of {$settings['merits-max']} points of Merits. ";
	if ($settings['flaws-max'] > 0)
		$output .= "You can have a maximum of {$settings['flaws-max']} points of Flaws. ";
	$output .= "You have $points points available to spend on your character. $spent have been spent leaving 
	you $remaining points. Hover over the dot to show the freebie point cost.</p>\n";
	
	$sectiontitle   = array(
						'stat'       => "Attributes and Stats",
						'skill'      => "Abilities",
						'disc'       => "Disciplines",
						'path'       => "Paths",
						'background' => "Backgrounds",
						'merit'      => "Merits and Flaws",
					);
	$sectionorder   = array('stat', 'skill', 'background', 'disc', 'path', 'merit');
	
	$sectioncontent['stat']  = vtm_render_freebie_stats($characterID, $submitted);
	$sectioncontent['skill'] = vtm_render_freebie_skills($characterID, $submitted, $templateID);
	$sectioncontent['disc']  = vtm_render_freebie_disciplines($characterID, $submitted);
	$sectioncontent['path']  = vtm_render_freebie_paths($characterID, $submitted);
	$sectioncontent['background'] = vtm_render_freebie_backgrounds($characterID, $submitted, $templateID);
	$sectioncontent['merit'] = vtm_render_freebie_merits($characterID, $submitted);
	
	// DISPLAY TABLES 
	//-------------------------------
	$i = 0;
	foreach ($sectionorder as $section) {
		if (isset($sectioncontent[$section]) && $sectiontitle[$section] && $sectioncontent[$section]) {
			$jumpto[$i++] = "<a href='#gvid_fb_$section' class='gvfb_jump'>" . $sectiontitle[$section] . "</a>\n";
		}
	}
	$outputJump = "<p>Jump to section: " . implode(" | ", $jumpto) . "</p>\n";
	
	foreach ($sectionorder as $section) {
	
		if (isset($sectioncontent[$section]) && $sectioncontent[$section] != "" ) {
			$output .= "<h4 class='gvfb_head' id='gvid_fb_$section'>" . $sectiontitle[$section] . "</h4>\n";
			$output .= "$outputJump\n";
			$output .= $sectioncontent[$section];
		} 
		
	}
	
	return $output;
}

function vtm_render_chargen_xp($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	
	// Work out how much points are currently available
	$spent = vtm_get_chargen_xp_spent($characterID, $templateID);
	// points = total overall - all pending + just pending on this character
	$points = vtm_get_total_xp(0, $characterID) - vtm_get_pending_xp(0, $characterID) + $spent;
	$remaining = $points - $spent;

	$output .= "<h3>Step $step: Experience Points</h3>\n";
	$output .= "<p>\n";
	$output .= "You have $points points available to spend on your character. $spent have been spent leaving 
	you $remaining points. Hover over the dot to show the experience point cost.</p>\n";
	
	$sectiontitle   = array(
						'stat'       => "Attributes and Stats",
						'skill'      => "Abilities",
						'disc'       => "Disciplines",
						'path'       => "Paths",
						'merit'      => "Merits",
						'ritual'     => "Rituals",
					);
	$sectionorder   = array('stat', 'skill', 'disc', 'path', 'merit', 'ritual');
	
	$pendingSpends = array();
	$sectioncontent['stat']   = vtm_render_chargen_xp_stats($characterID, $submitted);
	$sectioncontent['skill']  = vtm_render_chargen_xp_skills($characterID, $submitted,$templateID);
	$sectioncontent['disc']   = vtm_render_xp_disciplines($characterID, $submitted);
	$sectioncontent['path']   = vtm_render_chargen_xp_paths($characterID, $submitted);
	$sectioncontent['merit']  = vtm_render_chargen_xp_merits($characterID, $submitted);
	$sectioncontent['ritual'] = vtm_render_chargen_xp_rituals($characterID, $submitted);
	
	// DISPLAY TABLES 
	//-------------------------------
	$i = 0;
	foreach ($sectionorder as $section) {
		if (isset($sectioncontent[$section]) && $sectiontitle[$section] && $sectioncontent[$section]) {
			$jumpto[$i++] = "<a href='#gvid_xp_$section' class='gvxp_jump'>" . $sectiontitle[$section] . "</a>\n";
		}
	}
	$outputJump = "<p>Jump to section: " . implode(" | ", $jumpto) . "</p>\n";
	
	foreach ($sectionorder as $section) {
	
		if (isset($sectioncontent[$section]) && $sectioncontent[$section] != "" ) {
			$output .= "<h4 class='gvxp_head' id='gvid_xp_$section'>" . $sectiontitle[$section] . "</h4>\n";
			$output .= "$outputJump\n";
			$output .= $sectioncontent[$section];
		} 
		
	}
	
	return $output;
}

function vtm_render_finishing($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	//$options  = vtm_getConfig();
	
	$output .= "<h3>Step $step: Finishing Touches</h3>\n";
	$output .= "<p>Please fill in more information on your character.</p>\n";
	
	// Calculate Generation
	$generationInfo = vtm_calculate_generation($characterID);
	$generation   = $generationInfo['Gen'];
	$generationID = $generationInfo['ID'];

	// Calculate Path
	$pathid    = $wpdb->get_var($wpdb->prepare("SELECT ROAD_OR_PATH_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$pathname  = $wpdb->get_var($wpdb->prepare("SELECT NAME FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $pathid));
	$pathfreeb = $wpdb->get_var("SELECT LEVEL_TO FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND WHERE ITEMTABLE = 'ROAD_OR_PATH'");

	if ($pathfreeb) {
		$pathrating = $pathfreeb * $settings['road-multiplier'];
	} else {
		$statid1   = $wpdb->get_var($wpdb->prepare("SELECT STAT1_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $pathid));
		$statid2   = $wpdb->get_var($wpdb->prepare("SELECT STAT2_ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE ID = %s", $pathid));
		
		$sql = "SELECT cs.LEVEL
				FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT cs
				WHERE STAT_ID = %s AND CHARACTER_ID = %s";
		$stat1      = $wpdb->get_var($wpdb->prepare($sql, $statid1, $characterID));
		$stat2      = $wpdb->get_var($wpdb->prepare($sql, $statid2, $characterID));
		$pathrating = ($stat1 + $stat2) * $settings['road-multiplier'];
	}

	// Date of Birth
	$dob = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_BIRTH FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$dob_array = explode('-',$dob);
	$dob_day   = isset($_POST['day_dob'])   ? $_POST['day_dob']   : (isset($dob) ? strftime("%d", strtotime($dob)) : '');
	$dob_month = isset($_POST['month_dob']) ? $_POST['month_dob'] : (isset($dob) ? strftime("%m", strtotime($dob)) : '');
	$dob_year  = isset($_POST['year_dob'])  ? $_POST['year_dob']  : (isset($dob) ? $dob_array[0] : '');
	
	// Date of Embrace
	$doe = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_EMBRACE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$doe_array = explode('-',$doe);
	$doe_day   = isset($_POST['day_doe'])   ? $_POST['day_doe']   : (isset($doe) ? strftime("%d", strtotime($doe)) : '');
	$doe_month = isset($_POST['month_doe']) ? $_POST['month_doe'] : (isset($doe) ? strftime("%m", strtotime($doe)) : '');
	$doe_year  = isset($_POST['year_doe'])  ? $_POST['year_doe']  : (isset($doe) ? $doe_array[0] : '');
	
	// Date of Embrace
	$sire = $wpdb->get_var($wpdb->prepare("SELECT SIRE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$sire = isset($_POST['sire']) ? $_POST['sire'] : $sire;
	
	$output .= "<h4>Calculated Values</h4>\n";
	$output .= "<table>\n";
	$output .= "<tr><td>Generation:</td><td>$generation";
	$output .= "<input type='hidden' name='generationID' value='$generationID' />\n";
	$output .= "</td></tr>\n";
	if ($pathrating > 0) {
		$output .= "<tr><td>$pathname:</td><td>$pathrating";
		$output .= "<input type='hidden' name='pathrating' value='$pathrating' />\n";
		$output .= "</td></tr>\n";
	} 
	$output .= "</table>\n";

	$output .= "<h4>Important Dates</h4>\n";
	$output .= "<table>\n";
	$output .= "<tr><td>Date of Birth:</td><td>\n";
	$output .= vtm_render_date_entry("dob", $dob_day, $dob_month, $dob_year, $submitted);
	$output .= "</td></tr>\n";
	$output .= "<tr><td>Date of Embrace:</td><td>\n";
	$output .= vtm_render_date_entry("doe", $doe_day, $doe_month, $doe_year, $submitted);
	$output .= "</td></tr>\n";
	$output .= "</table>\n";

	// Notes to ST
	$stnotes = $wpdb->get_var($wpdb->prepare("SELECT NOTE_TO_ST FROM " . VTM_TABLE_PREFIX . "CHARACTER_GENERATION WHERE CHARACTER_ID = %s", $characterID));
	$stnotes = htmlspecialchars(stripslashes(isset($_POST['noteforST']) ? $_POST['noteforST'] : $stnotes), ENT_QUOTES);
	
	// Specialities Data
	
	// Get the list of things needing specialities
	$specialities = vtm_get_chargen_specialties($characterID);
	$freespecialities = vtm_get_free_levels('SKILL', $templateID);
	$specfinal = array();
	
	//print_r($specialities);
	
	$spec_output = "";
	$i = 0;
	foreach ($specialities as $item) {
		$hasinput = !$submitted;
	
		//echo "<li>Key: {$item['key']}, Name: {$item['name']}</li>";
	
		if (isset($freespecialities[sanitize_key($item['key'])]->SPECIALISATION)) {
			$spec = $freespecialities[sanitize_key($item['key'])]->SPECIALISATION;
			$freespecialities[sanitize_key($item['key'])]->LISTED = 'Y';
			$hasinput = 0;
		}
		elseif (isset($freespecialities[sanitize_key($item['name'])]->SPECIALISATION) && $freespecialities[sanitize_key($item['name'])]->MULTIPLE == 'N') {
			$spec = $freespecialities[sanitize_key($item['name'])]->SPECIALISATION;
			$freespecialities[sanitize_key($item['name'])]->LISTED = 'Y';
			$hasinput = 0;
		}
		elseif (isset($_POST['comment'][$i]))
			$spec = $_POST['comment'][$i];
		else
			$spec = $item['comment'];
		
		$specfinal[] = array (
			'title'     => htmlspecialchars($item['title'],     ENT_QUOTES),
			'tablename' => htmlspecialchars($item['updatetable'], ENT_QUOTES),
			'tableid'   => htmlspecialchars($item['tableid'],   ENT_QUOTES),
			'itemname'  => htmlspecialchars($item['key'],       ENT_QUOTES),
			'spec'      => htmlspecialchars(stripslashes($spec), ENT_QUOTES),
			'level'     => $item['level'],
			'name'      => htmlspecialchars($item['name'], ENT_QUOTES),
			'hasinput'  => $hasinput
		);
		$i++;
	}
	// And any free stuff we haven't bought already
	//print_r($freespecialities);
	foreach ($freespecialities as $key => $row) {
		if (!isset($row->LISTED)) {
		
			$specfinal[] = array (
				'title'     => htmlspecialchars('Additional Free', ENT_QUOTES),
				'tablename' => htmlspecialchars($row->ITEMTABLE, ENT_QUOTES),
				'tableid'   => htmlspecialchars($row->ITEMTABLE_ID, ENT_QUOTES),
				'itemname'  => htmlspecialchars($key, ENT_QUOTES),
				'spec'      => htmlspecialchars(stripslashes($row->SPECIALISATION), ENT_QUOTES),
				'level'     => $row->LEVEL,
				'name'      => htmlspecialchars($row->NAME, ENT_QUOTES),
				'hasinput'  => 0
			);
		}
	}
	
	// Output specialities
	$title = "";
	foreach ($specfinal as $item) {
		if ($title != $item['title']) {
			$title = htmlspecialchars($item['title'], ENT_QUOTES);
			$spec_output .= "<tr><th colspan=3>$title</th></tr>\n";
		}
		
		// have a hidden row with the tablename and tableid info
		$spec_output .= "<tr style='display:none'><td colspan=3>
					<input type='hidden' name='tablename[]' value='{$item['tablename']}' />
					<input type='hidden' name='tableid[]' value='{$item['tableid']}' />
					<input type='hidden' name='fullname[]' value='{$item['name']}' />
					</td></tr>\n";
					
		$spec_output .= "<tr><td>{$item['name']}</td>
					<td>{$item['level']}</td>
					<td>\n";
			
		// Only have an entry box for specialities that haven't been pre-set from the
		// character generation template
		if ($submitted)
			$spec_output .= $item['spec'];
		elseif ($item['hasinput']) 
			$spec_output .= "<input type='text' name='comment[]' value='{$item['spec']}' maxlength='25' />\n";
		else
			$spec_output .= "{$item['spec']}<input type='hidden' name='comment[]' value='{$item['spec']}' />\n";
		
		$spec_output .= "</td></tr>\n";
											
	}

	if ($spec_output != '') {
		$output .= "<h4>Specialities</h4>\n";
		$output .= "<p>Please enter specialities for the indicated Attributes and Abilities and provide
					a note on what any Merits and Flaws refer to.</p>
					
					<p>An example speciality for Stamina is 'tough'. An example note for the Merit 'Acute Sense'
					might be 'sight' and for 'Clan Friendship' might be 'Ventrue'</p>\n";
		$output .= "<table>$spec_output\n";
		$output .= "</table>\n";
	}
	
	$output .= "<h4>Miscellaneous</h4>\n";
	$output .= "<table>\n";
	$output .= "<tr><td>Name of your Sire:</td><td>\n";
	if ($submitted)
		$output .= $sire;
	else
		$output .= "<input type='text' name='sire' value='$sire' />\n";
	$output .= "</td></tr>\n";
	$output .= "<tr><td>Notes for Storyteller:</td><td>\n";
	if ($submitted)
		$output .= $stnotes;
	else
		$output .= "<textarea name='noteforST' rows='5' cols='80'>$stnotes</textarea>\n"; // ADD COLUMN TO CHARACTER
	$output .= "</td></tr>\n";
	$output .= "</table>\n";
	
	return $output;
}
function vtm_render_chargen_extbackgrounds($step, $characterID, $templateID, $submitted) {

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$options  = vtm_getConfig();
	
	$output .= "<h3>Step $step: History and Extended Backgrounds</h3>\n";
	$output .= "<p>Please fill in more information on your character.</p>\n";
	
	// Merits
	$questions = vtm_get_chargen_merit_questions($characterID);
	$posted    = isset($_POST['meritquestion']) ? $_POST['meritquestion'] : array();
	foreach ($questions as $question) {
		$id = $question->ID;
		$title = $question->NAME;
		if (!empty($question->SPECIALISATION)) $title .= " - " . $question->SPECIALISATION;
		$title .= " (" . $question->VALUE . ")";
		
		$text = isset($posted[$id]) ? $posted[$id] : (isset($question->PENDING_DETAIL) ? $question->PENDING_DETAIL : '');
		
		$output .= "<h4>$title</h4><p class='gvext_ques'>{$question->BACKGROUND_QUESTION}</p>\n";
		$output .= "<input type='hidden' name='meritquestion_title[$id]' value='" . htmlspecialchars($title, ENT_QUOTES) . "' />\n";
		if ($submitted)
			$output .= "<p class='gvext_section'>$text</p>\n";
		else
			$output .= "<p><textarea name='meritquestion[$id]' rows='4' cols='80'>$text</textarea></p>\n";
	}

	// Backgrounds
	$questions = vtm_get_chargen_background_questions($characterID);
	$posted    = isset($_POST['bgquestion']) ? $_POST['bgquestion'] : array();
	foreach ($questions as $question) {
		$id    = $question->ID;
		$title = $question->NAME . " " . $question->LEVEL;
		$text = isset($posted[$id]) ? $posted[$id] : (isset($question->PENDING_DETAIL) ? $question->PENDING_DETAIL : '');

		if (!empty($question->COMMENT)) $title .= " (" . $question->COMMENT . ")";		
		
		$output .= "<h4>$title</h4><p class='gvext_ques'>{$question->BACKGROUND_QUESTION}</p>\n";
		$output .= "<input type='hidden' name='bgquestion_title[$id]' value='" . htmlspecialchars($title, ENT_QUOTES) . "' />\n";
		$output .= "<input type='hidden' name='bgquestion_source[$id]' value='" . htmlspecialchars($question->source, ENT_QUOTES) . "' />\n";
		if ($submitted)
			$output .= "<p class='gvext_section'>$text</p>\n";
		else
			$output .= "<p><textarea name='bgquestion[$id]' rows='4' cols='80'>$text</textarea></p>\n";
		
	}

	// Extended
	$questions = vtm_get_chargen_questions($characterID);
	$posted    = isset($_POST['question']) ? $_POST['question'] : array();
		
	foreach ($questions as $question) {
		$id = $question->questID;
		$text = isset($posted[$id]) ? $posted[$id] : (isset($question->PENDING_DETAIL) ? $question->PENDING_DETAIL : '');
	
		$output .= "<h4>{$question->TITLE}</h4><p class='gvext_ques'>{$question->BACKGROUND_QUESTION}</p>\n";
		$output .= "<input type='hidden' name='question_title[$id]' value='{$question->TITLE}' />\n";
		if ($submitted)
			$output .= "<p class='gvext_section'>$text</p>\n";
		else
			$output .= "<p><textarea name='question[$id]' rows='4' cols='80'>$text</textarea></p>\n";
	}
	
	return $output;
}
function vtm_render_chargen_submit($step, $characterID, $templateID, $submitted) {

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$options  = vtm_getConfig();
	
	$output .= "<h3>Step $step: Summary and Submit</h3>\n";
	$output .= "<p>Below is a summary of the character generation status.</p>\n";
	
	// Not suitable to use _POST as it is only updated if all steps have been
	// gone through this session
	$flow = vtm_chargen_flow_steps($characterID, $templateID);
	foreach ($flow as $flowstep) {
		$progress[] = call_user_func($flowstep['validate'], $settings, $characterID, $templateID, 0);
	}
	
	$output .= "<table>\n";
	$index = 0;
	$done = 0;
	foreach ($progress as $result) {
		if ($index < (count($progress) - 1)) {
			$output .= "<tr>\n";
			if ($result[2]) $status = "Complete";
			elseif ($result[0]) $status = "In progress: {$result[1]}";
			else $status = "Error";
			
			if ($flow[$index]['title'] == 'Spend Experience' && $status != "Error") $status = "N/A";
			
			if ($status == "Error") $errinfo = "<ul>{$result[1]}</ul>"; else $errinfo = "";
			If ($status == "Complete" || $status == "N/A") $done++;
			
			$output .= "<td>Step " . ($index +1) .": {$flow[$index]['title']}</td>\n";
			$output .= "<td>$status $errinfo</td>\n";
			$output .= "</tr>\n";
			}
		$index++;
	}
	
	$output .= "</table>\n";
	
	$alldone = 0;
	if ($done == (count($progress) - 1)) {
		$alldone = 1;
		if ($submitted)
			$output .= "<p><strong>Your character has been submitted!</strong></p>\n";
		else
			$output .= "<p><strong>Your character is ready to submit!</strong></p>\n";
	}
	$output .= "<input type='hidden' name='status' value='$alldone' />\n";
	
	$link = vtm_get_stlink_url('printCharSheet');
	$link = add_query_arg('characterID', $characterID, $link);
	$output .= "<br /><p>Click to <a href='$link' title='Print Character'>Print your character</a></p>\n";
	
	return $output;
}
function vtm_render_abilities($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output     = "";
	$settings   = vtm_get_chargen_settings($templateID);
	$items      = vtm_get_chargen_abilities($characterID);
	$pendingfb  = vtm_get_pending_freebies('SKILL', $characterID); 
	$pendingxp  = vtm_get_pending_chargen_xp('SKILL', $characterID);
	$templatefree = vtm_get_free_levels('SKILL',$templateID);
		
	$output .= "<h3>Step $step: Abilities</h3>\n";
	$output .= "<p>You have {$settings['abilities-primary']} dots to spend on your Primary abilities, 
		{$settings['abilities-secondary']} to spend on Secondary and {$settings['abilities-tertiary']} to 
		spend on Tertiary.";
	if ($settings['abilities-max'] > 0)
		$output .= " The maximum you can spend on any one Ability at this stage is {$settings['abilities-max']}.";
	$output .= "</p>\n";
	

	// read/guess initial values
	$sql = "SELECT 
				skills.NAME as name, cs.SKILL_ID as itemid, cs.LEVEL as level_from, cs.COMMENT as comment
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_SKILL cs,
				" . VTM_TABLE_PREFIX . "SKILL skills
			WHERE 
				skills.ID = cs.SKILL_ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K)); 
	//echo "<li>SQL: $sql</li>\n";
	
	$geninfo = vtm_calculate_generation($characterID);
	//print_r($geninfo);
	
	// abilities Posted data
	$abilities = isset($_POST['ability_value']) ? $_POST['ability_value'] : array();
	
	$output .= vtm_render_chargen_section($saved, true, 
		$settings['abilities-primary'], $settings['abilities-secondary'], $settings['abilities-tertiary'], 
		0, $items, $abilities, $pendingfb, $pendingxp, 'Abilities', 'ability_value', 
		$submitted,$geninfo['MaxDot'], $templatefree);

	return $output;
}

function vtm_render_chargen_disciplines($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output = "";
	$settings   = vtm_get_chargen_settings($templateID);
	$items      = vtm_get_chargen_disciplines($characterID);
	$pendingfb  = vtm_get_pending_freebies('DISCIPLINE', $characterID); 
	$pendingxp  = vtm_get_pending_chargen_xp('DISCIPLINE', $characterID); 
	$geninfo    = vtm_calculate_generation($characterID);
		
	$output .= "<h3>Step $step: Disciplines</h3>\n";
	$output .= "<p>You have {$settings['disciplines-points']} dots to spend on your Disciplines</p>\n";

	// read initial values
	$sql = "SELECT 
				disc.NAME as name, cd.DISCIPLINE_ID as itemid, cd.LEVEL as level_from
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
		0, $items, $disciplines, $pendingfb, $pendingxp, 'Disciplines', 
		'discipline_value', $submitted,$geninfo['MaxDisc']);


	return $output;
}

function vtm_render_chargen_backgrounds($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$items    = vtm_get_chargen_backgrounds($characterID);
	$pending  = vtm_get_pending_freebies('BACKGROUND', $characterID);  // name => value
	$templatefree = vtm_get_free_levels('BACKGROUND',$templateID);
	
	$output .= "<h3>Step $step: Backgrounds</h3>\n";
	$output .= "<p>You have {$settings['backgrounds-points']} dots to spend on your Backgrounds</p>\n";
	
	// read initial values
	$sql = "SELECT bg.NAME as name, cbg.BACKGROUND_ID as itemid, cbg.LEVEL as level_from
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND cbg,
				" . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE 
				bg.ID = cbg.BACKGROUND_ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	// Work out how many dots we need
	$maxdots = $wpdb->get_var($wpdb->prepare("SELECT MAX_DISCIPLINE FROM " . VTM_TABLE_PREFIX . "GENERATION WHERE ID = %s", $settings['limit-generation-low']));

	$backgrounds = isset($_POST['background_value']) ? $_POST['background_value'] : array();

	$output .= vtm_render_chargen_section($saved, false, 0, 0, 0, 
		0, $items, $backgrounds, $pending, array(), 'Backgrounds', 'background_value', 
		$submitted, $maxdots, $templatefree);
	
	return $output;
} 

function vtm_render_chargen_rituals($step, $characterID, $templateID, $submitted) {
	global $wpdb;

	//print_r ($_POST);
	
	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$items    = vtm_get_chargen_rituals($characterID);
	$points   = vtm_get_chargen_ritual_points($characterID, $settings, $items);
	$pendingxp  = vtm_get_pending_chargen_xp('RITUAL', $characterID); 
		
	$output .= "<h3>Step $step: Rituals</h3>\n";
	foreach ($points as $discipline => $point)
		$output .= "<p>You have $point points to spend on your $discipline rituals.</p>\n";
	
	// read initial values
	$sql = "SELECT rit.NAME as name, disc.NAME as discipline, rit.LEVEL as level_from
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_RITUAL crit,
				" . VTM_TABLE_PREFIX . "RITUAL rit,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE 
				rit.ID = crit.RITUAL_ID
				AND rit.DISCIPLINE_ID = disc.ID
				AND crit.CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>$sql</p>";
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K)); 

	$rituals = isset($_POST['ritual_value']) ? $_POST['ritual_value'] : array();

	$output .= vtm_render_chargen_section($saved, false, 0, 0, 0, 
	     0, $items, $rituals, array(), $pendingxp, 'Ritual', 'ritual_value', $submitted);
	
	return $output;
} 

function vtm_render_choose_template() {
	global $wpdb;

	$output = "";
	
	$output .= "<h3>Choose a template</h3>\n";
	
	$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE WHERE VISIBLE = 'Y' ORDER BY NAME";
	$result = $wpdb->get_results($sql);
	
	$output .= "<p><label>Character Generation Template:</label> <select name='chargen_template'>\n";
	foreach ($result as $template) {
		$output .= "<option value='{$template->ID}'>{$template->NAME}</option>\n";
	}
	$output .= "</select></p>\n";
	
	$ref = isset($_GET['reference']) ? $_GET['reference'] : '';
	
	$output .= "<p>Or, update a character: 
		<label>Reference:</label> <input type='text' name='chargen_reference' value='$ref' size=20 ></p>\n";
	
	return $output;
}

function vtm_validate_chargen($laststep, $templateID, $characterID) {
	global $wpdb;
	global $current_user;

	$settings = vtm_get_chargen_settings($templateID);
	$flow = vtm_chargen_flow_steps($characterID, $templateID);
	
	
	if ($laststep == 0) {
		$ok = 1;
		$errormessages = "";
	} else {
		$status = call_user_func($flow[$laststep-1]['validate'], $settings, $characterID, $templateID);
		$ok = $status[0];
		$errormessages = $status[1];
	}
	//echo "<p>Do Validate ($laststep, $characterID, $ok, $errormessages, {$flow[$laststep-1]['validate']})</p>";
	
	if (!$ok)
		$errormessages .= "<li>Please correct the errors before continuing</li>\n";
	
	if ($errormessages != "") {
		echo "<div class='gvxp_error'><ul>$errormessages</ul></div>\n";
	}
	
	return $ok;
}

function vtm_save_progress($laststep, $characterID, $templateID) {
	
	$flow = vtm_chargen_flow_steps($characterID, $templateID);
	if ($laststep != 0) {
		//echo "<li>laststep: $laststep, function: {$flow[$laststep-1]['save']}</li>\n";
		$characterID = call_user_func($flow[$laststep-1]['save'], $characterID, $templateID);
	}

	return $characterID;
}

function vtm_save_attributes($characterID,$templateID) {
	global $wpdb;
	
	// List of attributes
	$attributes = vtm_get_chargen_attributes();
	
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
	$new = isset($_POST['attribute_value']) ? $_POST['attribute_value'] : array();
	
	foreach ($attributes as $attribute) {
		$key     = sanitize_key($attribute->name);
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

function vtm_save_rituals($characterID,$templateID) {
	global $wpdb;
	
	$rituals = vtm_get_chargen_rituals($characterID);
	
	// Get saved into database
	$sql = "SELECT rit.NAME, crit.RITUAL_ID, crit.ID
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_RITUAL crit,
				" . VTM_TABLE_PREFIX . "RITUAL rit
			WHERE 
				rit.ID = crit.RITUAL_ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));

	// Get levels to be saved
	$new = isset($_POST['ritual_value']) ? $_POST['ritual_value'] : array();
	
	foreach ($rituals as $ritual) {
		$key     = sanitize_key($ritual->name);
		$value   = isset($new[$key]) ? $new[$key] : 0;
	
		$data = array(
			'CHARACTER_ID' => $characterID,
			'RITUAL_ID'    => $ritual->ID,
			'LEVEL'        => $value
		);
		if (isset($saved[$key])) {
			// update
			$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_RITUAL",
				$data,
				array (
					'ID' => $saved[$key]->ID
				)
			);
		} 
		elseif (isset($new[$key])) {
			// insert
			$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_RITUAL",
						$data,
						array ('%d', '%d', '%d')
					);
		}
	}
	
	// Delete anything no longer needed
	foreach ($saved as $id => $value) {
		if (!isset($new[$id]) || $new[$id] <= 0) {
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_RITUAL
					WHERE CHARACTER_ID = %s AND RITUAL_ID = %s";
			$sql = $wpdb->prepare($sql,$characterID,$saved[$id]->RITUAL_ID);
			//echo "<li>Delete $id ($sql)</li>\n";
			$wpdb->get_results($sql);
		}
	}
	
	return $characterID;
}

function vtm_save_freebies($characterID, $templateID) {
	global $wpdb;


	// Delete current pending spends
	$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
			WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$result = $wpdb->get_results($sql);
	
	$freebiecosts['STAT']       = vtm_get_freebie_costs('STAT', $characterID);
	$freebiecosts['SKILL']      = vtm_get_freebie_costs('SKILL', $characterID);
	$freebiecosts['DISCIPLINE'] = vtm_get_freebie_costs('DISCIPLINE', $characterID);
	$freebiecosts['BACKGROUND'] = vtm_get_freebie_costs('BACKGROUND', $characterID);
	$freebiecosts['MERIT']      = vtm_get_freebie_costs('MERIT', $characterID);
	$freebiecosts['PATH']       = vtm_get_freebie_costs('PATH', $characterID);
	$freebiecosts['ROAD_OR_PATH'] = vtm_get_freebie_costs('ROAD_OR_PATH', $characterID);
	$freebiecosts['STAT'] = array_merge($freebiecosts['STAT'], vtm_get_freebie_costs('ROAD_OR_PATH'));

	$templatefree['SKILL']      = vtm_get_free_levels('SKILL', $templateID);
	
	$current['STAT']       = vtm_get_current_stats($characterID);
	$current['SKILL']      = vtm_get_current_skills($characterID);
	$current['DISCIPLINE'] = vtm_get_current_disciplines($characterID);
	$current['BACKGROUND'] = vtm_get_current_backgrounds($characterID);
	$current['MERIT']      = vtm_get_current_merits($characterID);
	$current['PATH']       = vtm_get_current_paths($characterID);
	$current['STAT'] = array_merge($current['STAT'], vtm_get_current_road($characterID));
			
	$bought['STAT']       = isset($_POST['freebie_stat']) ? $_POST['freebie_stat'] : array();
	$bought['SKILL']      = isset($_POST['freebie_skill']) ? $_POST['freebie_skill'] : array();
	$bought['DISCIPLINE'] = isset($_POST['freebie_discipline']) ? $_POST['freebie_discipline'] : array();
	$bought['BACKGROUND'] = isset($_POST['freebie_background']) ? $_POST['freebie_background'] : array();
	$bought['MERIT']      = isset($_POST['freebie_merit']) ? $_POST['freebie_merit'] : array();
	$bought['PATH']       = isset($_POST['freebie_path']) ? $_POST['freebie_path'] : array();

	$specialisation['STAT']       = isset($_POST['freebie_stat_spec']) ? $_POST['freebie_stat_spec'] : array();
	$specialisation['SKILL']      = isset($_POST['freebie_skill_spec']) ? $_POST['freebie_skill_spec'] : array();
	$specialisation['DISCIPLINE'] = isset($_POST['freebie_discipline_spec']) ? $_POST['freebie_discipline_spec'] : array();
	$specialisation['BACKGROUND'] = isset($_POST['freebie_background_spec']) ? $_POST['freebie_background_spec'] : array();
	$specialisation['MERIT']      = isset($_POST['freebie_merit_spec']) ? $_POST['freebie_merit_spec'] : array();
	$specialisation['PATH']       = isset($_POST['freebie_path_spec']) ? $_POST['freebie_path_spec'] : array();

	$pending_detail['MERIT']      = isset($_POST['freebie_merit_detail']) ? $_POST['freebie_merit_detail'] : array();
	$pending_detail['BACKGROUND'] = isset($_POST['freebie_background_detail']) ? $_POST['freebie_background_detail'] : array();
	
	//print_r($bought);
	// Add free skills to bought skills
	foreach ($templatefree as $type => $items) {
		foreach ($items as $key => $row) {
			if (isset($bought[$type][$key])) {
				if ($bought[$type][$key] < $row->LEVEL) {
					if (!isset($current[$type][$key]) || (isset($current[$type][$key]) && $current[$type][$key]->level_from < $row->LEVEL)) {
						$bought[$type][$key] = $row->LEVEL;
						//echo "<li>New bought level for $type $key is {$bought[$type][$key]}</li>";
					}
				} 
			} 
			elseif (!isset($current[$type][$key])) {
				//echo "<li>Adding $type $key to level {$row->LEVEL}</li>";
				$bought[$type][$key] = $row->LEVEL;
			}
		}
	}
	
	foreach ($bought as $type => $items) {
		foreach ($items as $key => $levelto) {
			$levelfrom   = isset($templatefree[$type][$key]->LEVEL)  ? $templatefree[$type][$key]->LEVEL  : 0;
			$levelfrom   = isset($current[$type][$key]->level_from)  ? $current[$type][$key]->level_from  : $levelfrom;
			$itemtable = $type;
			
			if ($levelto != 0) {
				$itemname = $key;
				if (isset($current[$type][$key]->name)) {
					$name = $current[$type][$key]->name;
				} else {
					$key  = preg_replace("/_\d+$/", "", $key);
					$name = $current[$type][$key]->name;
				}
							
				$chartableid = isset($current[$type][$key]->chartableid) ? $current[$type][$key]->chartableid : 0;
				
				// no cost for free stuff
				if ($type == 'MERIT')
					$amount = isset($freebiecosts[$type][$key][0][1]) ? $freebiecosts[$type][$key][0][1] : 0;
				elseif ($type == 'STAT' && $key == 'pathrating') {
					$pathname = sanitize_key($current[$type][$key]->grp);
					$itemtable = "ROAD_OR_PATH";
					//echo "<li>pathname: $pathname, from: $levelfrom, to: $levelto</li>";
					$amount = isset($freebiecosts[$type][$pathname][$levelfrom][$levelto]) ? $freebiecosts[$type][$pathname][$levelfrom][$levelto] : 0;
				}
				else
					$amount = isset($freebiecosts[$type][$key][$levelfrom][$levelto]) ? $freebiecosts[$type][$key][$levelfrom][$levelto] : 0;
				$itemid      = $current[$type][$key]->itemid;
				$detail     = isset($pending_detail[$type][$itemname]) ? $pending_detail[$type][$itemname] : '';
				if (isset($specialisation[$type][$itemname]) && $specialisation[$type][$itemname] != '')
					$spec = $specialisation[$type][$itemname];
				elseif (isset($current[$type][$key]->specialisation) && $current[$type][$key]->specialisation != '')
					$spec =  $current[$type][$key]->specialisation;
				elseif (isset($templatefree[$itemname]->SPECIALISATION)) 
					$spec = $templatefree[$itemname]->SPECIALISATION;
				else
					$spec = '';
					
				//echo "<li>itemname: $itemname, key: $key, from level $levelfrom to $levelto, spec: $spec, cost: $amount</li>\n";
				
				if ($levelto > $levelfrom || $type == 'MERIT') {
					$data = array (
						'CHARACTER_ID'   => $characterID,
						'CHARTABLE'      => 'CHARACTER_' . $type,
						'CHARTABLE_ID'   => $chartableid,
						'LEVEL_FROM'     => $levelfrom,
						
						'LEVEL_TO'       => $levelto,
						'AMOUNT'         => $amount,
						'ITEMTABLE'      => $itemtable,
						'ITEMNAME'       => $itemname,
						
						'ITEMTABLE_ID'   => $itemid,
						'SPECIALISATION' => $spec,
						'PENDING_DETAIL' => $detail
					);
					$wpdb->insert(VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND",
								$data,
								array (
									'%d', '%s', '%d', '%d',
									'%d', '%d', '%s', '%s',
									'%d', '%s', '%s'
								)
							);
					if ($wpdb->insert_id == 0) {
						echo "<p style='color:red'><b>Error:</b> $name could not be inserted</p>\n";
					}		
					//print_r($data);
				}
			}
		}
	}

	return $characterID;
}

function vtm_save_history($characterID, $templateID) {
	global $wpdb;

	$sql = "SELECT questions.ID as questID, cq.ID as id, cq.PENDING_DETAIL as detail
			FROM 
				" . VTM_TABLE_PREFIX . "EXTENDED_BACKGROUND questions,
				" . VTM_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND cq
			WHERE
				questions.ID = cq.QUESTION_ID
				AND cq.CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = $wpdb->get_results($sql, OBJECT_K);
	//echo "<p>SQL: $sql</p>\n";
	//print_r($saved);
	
	// Save Ext Background questions
	if (isset($_POST['question'])) {
		foreach ($_POST['question'] as $index => $text) {
		
			$data = array (
				'CHARACTER_ID'  	=> $characterID,
				'QUESTION_ID'		=> $index,
				'APPROVED_DETAIL'	=> '',
				'PENDING_DETAIL'	=> trim($text),
				'DENIED_DETAIL'		=> '',
			);
			//print_r($data);
			
			if (isset($saved[$index])) {
				//echo "<li>Updating id {$saved[$index]->id} for question $index</li>\n";
				$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND",
					$data,
					array ('ID' => $saved[$index]->id)
				);
			} else {
				//echo "<li>Adding question $index</li>\n";
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND",
							$data,
							array ('%d', '%d', '%s', '%s', '%s')
				);
				
			}
		}
	}
	// Save Merit/Flaw questions
	if (isset($_POST['meritquestion'])) {
		foreach ($_POST['meritquestion'] as $index => $text) {
		
			$data = array (
				'PENDING_DETAIL'	=> trim($text)
			);
			
			$wpdb->update(VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND",
				$data,
				array ('ID' => $index)
			);
		}
	}
	
	// Save Background questions
	if (isset($_POST['bgquestion'])) {
		foreach ($_POST['bgquestion'] as $index => $text) {
			$data = array (
				'PENDING_DETAIL'	=> trim($text)
			);
			
			$wpdb->update(VTM_TABLE_PREFIX . $_POST['bgquestion_source'][$index],
				$data,
				array ('ID' => $index)
			);
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
		'SIRE'                => $_POST['sire'],
		'DATE_OF_BIRTH'       => $dob,
		'DATE_OF_EMBRACE'     => $doe,
		'GENERATION_ID'       => $_POST['generationID'],
		'ROAD_OR_PATH_RATING' => isset($_POST['pathrating']) ? $_POST['pathrating'] : 0,
	);
	
	$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER",
		$data,
		array (
			'ID' => $characterID
		),
		array('%s', '%s', '%s', '%d', '%s')
	);		
	if (!$result && $result !== 0) {
		echo "<p style='color:red'>Failed to save:</p>\n";
		$wpdb->show_errors();
		$wpdb->print_error();
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}
	
	// Save CHARACTER_GENERATION information
	$data = array (
		'NOTE_TO_ST'  => trim($_POST['noteforST']),
	);
	$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_GENERATION",
		$data,
		array (
			'CHARACTER_ID' => $characterID
		),
		array('%s')
	);		
	
	// Save Specialities
	if (isset($_POST['itemname'])) {
	
		// Remove anything with a speciality to ensure that skills haven't dropped
		// since the last time the specialities were saved
		$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_STAT",  array('COMMENT' => ''), array('CHARACTER_ID' => $characterID));
		$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_SKILL", array('COMMENT' => ''), array('CHARACTER_ID' => $characterID));
		$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_MERIT", array('COMMENT' => ''), array('CHARACTER_ID' => $characterID));
		$wpdb->update(VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND", array('SPECIALISATION' => ''), array('CHARACTER_ID' => $characterID));
		$wpdb->update(VTM_TABLE_PREFIX . "PENDING_XP_SPEND",      array('SPECIALISATION' => ''), array('CHARACTER_ID' => $characterID));
		
		// Then re-add the ones we need
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
			/*if ($result) 			echo "<p style='color:green'>Updated $name speciality with $comment</p>\n";
			if ($result) 			echo "<p style='color:green'>Updated $name speciality with $comment</p>\n";
			else if ($result === 0) echo "<p style='color:orange'>No updates made to $name speciality</p>\n";
			else {
				$wpdb->print_error();
				echo "<p style='color:red'>Could not update $name speciality</p>\n";
			}*/
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
	
	$bought['STAT']       = isset($_POST['xp_stat']) ? $_POST['xp_stat'] : array();
	$bought['SKILL']      = isset($_POST['xp_skill']) ? $_POST['xp_skill'] : array();
	$bought['DISCIPLINE'] = isset($_POST['xp_discipline']) ? $_POST['xp_discipline'] : array();
	$bought['MERIT']      = isset($_POST['xp_merit']) ? $_POST['xp_merit'] : array();
	$bought['PATH']       = isset($_POST['xp_path']) ? $_POST['xp_path'] : array();
	$bought['RITUAL']     = isset($_POST['xp_ritual']) ? $_POST['xp_ritual'] : array();

	$templatefree['SKILL']      = vtm_get_free_levels('SKILL', $templateID);

	$comments['STAT']  = isset($_POST['xp_stat_comment'])   ? $_POST['xp_stat_comment'] : array();
	$comments['SKILL']  = isset($_POST['xp_skill_comment']) ? $_POST['xp_skill_comment'] : array();
	$comments['MERIT']  = isset($_POST['xp_merit_comment']) ? $_POST['xp_merit_comment'] : array();
	
	$freebiecosts['STAT']       = vtm_get_freebie_costs('STAT', $characterID);
	$freebiecosts['SKILL']      = vtm_get_freebie_costs('SKILL', $characterID);
	$freebiecosts['DISCIPLINE'] = vtm_get_freebie_costs('DISCIPLINE', $characterID);
	$freebiecosts['MERIT']      = vtm_get_freebie_costs('MERIT', $characterID);
	$freebiecosts['PATH']       = vtm_get_freebie_costs('PATH', $characterID);

	$current['STAT']       = vtm_get_current_stats($characterID);
	$current['SKILL']      = vtm_get_current_skills($characterID);
	$current['DISCIPLINE'] = vtm_get_current_disciplines($characterID);
	$current['MERIT']      = vtm_get_current_merits($characterID);
	$current['PATH']       = vtm_get_current_paths($characterID);
	$current['RITUAL']     = vtm_get_current_rituals($characterID);
	
	$freebies['STAT']       = vtm_get_pending_freebies('STAT', $characterID);
	$freebies['SKILL']      = vtm_get_pending_freebies('SKILL', $characterID);
	$freebies['DISCIPLINE'] = vtm_get_pending_freebies('DISCIPLINE', $characterID);
	$freebies['MERIT']      = vtm_get_pending_freebies('MERIT', $characterID);
	$freebies['PATH']       = vtm_get_pending_freebies('PATH', $characterID);
	
	$xpcosts['STAT']       = vtm_get_chargen_xp_costs('STAT', $characterID);
	$xpcosts['SKILL']      = vtm_get_chargen_xp_costs('SKILL', $characterID);
	$xpcosts['DISCIPLINE'] = vtm_get_chargen_xp_costs('DISCIPLINE', $characterID);
	$xpcosts['MERIT']      = vtm_get_chargen_xp_costs('MERIT', $characterID);
	$xpcosts['PATH']       = vtm_get_chargen_xp_costs('PATH', $characterID);
	$xpcosts['RITUAL']     = vtm_get_chargen_xp_costs('RITUAL', $characterID);

	$items['STAT']       = vtm_sanitize_array(vtm_get_chargen_stats($characterID, OBJECT_K));
	$items['SKILL']      = vtm_sanitize_array(vtm_get_chargen_abilities($characterID, 1, OBJECT_K));
	$items['DISCIPLINE'] = vtm_sanitize_array(vtm_get_chargen_disciplines($characterID, OBJECT_K));
	$items['MERIT']      = vtm_sanitize_array(vtm_get_chargen_merits($characterID, OBJECT_K));
	$items['PATH']       = vtm_sanitize_array(vtm_get_chargen_paths($characterID, OBJECT_K));
	$items['RITUAL']     = vtm_sanitize_array(vtm_get_chargen_rituals($characterID, OBJECT_K));

	// Add free skills to bought skills
	foreach ($templatefree as $type => $data) {
		foreach ($data as $key => $row) {
		
			//echo "<li>$key - {$row ->LEVEL} {$row->SPECIALISATION}</li>";
		
			// Ensure you have the free dot as a minimum value if 
			// addition spend on this item has been cancelled and it wasn't bought
			// with freebies
			if (isset($bought[$type][$key])) {
				//echo "<li>Bought level {$bought[$type][$key]} of $key</li>";
				if ($bought[$type][$key] < $row->LEVEL) {
					if (!isset($freebies[$type][$key])) {
						if (!isset($current[$type][$key]) || (isset($current[$type][$key]) && $current[$type][$key]->level_from < $row->LEVEL)) {
							$bought[$type][$key] = $row->LEVEL;
							//echo "<li>New bought level for $type $key is {$bought[$type][$key]}</li>";
						}
					} 
				}
			} 
			// Add the dot if you haven't already bought it
			elseif (!isset($current[$type][$key]) && !isset($freebies[$type][$key])) {
				//echo "<li>Adding $type $key to level {$row->LEVEL}</li>";
				$bought[$type][$key] = $row->LEVEL;
				$comments[$type][$key] = $row->SPECIALISATION;
			} 
			else {
				//echo "<li>Not bought $key</li>";
			}
		}
	}

	//echo "<pre>";
	//print_r($bought['DISCIPLINE']);
	//print_r($current['SKILL']);
	//print_r($freebies['SKILL']);
	//print_r($items);
	//print_r($templatefree);
	//echo "</pre>";
	
	foreach ($bought as $type => $row) {
		foreach ($row as $key => $value) {
		
			if ($value != 0) {
				$itemname = $key;
				if (isset($items[$type][$key]->name)) {
					$name = $items[$type][$key]->name;
				} else {
					$key  = preg_replace("/_\d+$/", "", $key);
					$name = $items[$type][$key]->name;
				}
				
				//echo "<li>origkey: $itemname, key: $key, value:$value, name: $name</li>\n";
				//print_r($templatefree[$type][$itemname]);

				if (isset($templatefree[$type][$key]->LEVEL))
					$freelevel = $templatefree[$type][$key]->LEVEL;
				elseif (isset($templatefree[$type][$itemname]->LEVEL))
					$freelevel = $templatefree[$type][$itemname]->LEVEL;
				else
					$freelevel = 0;
				$currlevel   = isset($current[$type][$key]->level_from)  ? $current[$type][$key]->level_from  : 0;
				$levelfrom   = max($currlevel, $freelevel);
				$levelfrom   = isset($freebies[$type][$itemname]->value) ? $freebies[$type][$itemname]->value : $levelfrom;

				$amount      = ($type == 'MERIT' || $type == 'RITUAL') ? $xpcosts[$type][$key][0][1] : (isset($xpcosts[$type][$key][$levelfrom][$value]) ? $xpcosts[$type][$key][$levelfrom][$value] : 0);
				$itemid      = $items[$type][$key]->ID;
				$spec        = isset($comments[$type][$itemname]) ? $comments[$type][$itemname] : '';

				$chartable = 'CHARACTER_' . $type;
				if (isset($current[$type][$key]->chartableid) && !empty($current[$type][$key]->chartableid)) {
					$chartableid = $current[$type][$key]->chartableid;
				}
				elseif (isset($freebies[$type][$itemname]->id)) {
					$chartableid = $freebies[$type][$itemname]->id;
					$chartable = 'PENDING_FREEBIE_SPEND';
				} 
				else {
					$chartableid = 0;
				}
			
				//echo "<li>$key/$itemname/$name - type: $type, from: $levelfrom, value: $value, spec: $spec, cost: -$amount</li>\n";
				
				if ($value > $levelfrom || $type == 'MERIT' || $type == 'RITUAL') {
					$data = array (
						'PLAYER_ID'       => $playerID,
						'CHARACTER_ID'    => $characterID,
						'CHARTABLE'       => $chartable,
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
					//echo "<pre>\n";
					//print_r($data);
					//echo "</pre>\n";
					
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
						echo "<p style='color:red'><b>Error:</b> $name could not be inserted</p>\n";
					}		
				}

			}
		}
	}

	return $characterID;
}

function vtm_save_abilities($characterID,$templateID) {
	global $wpdb;

	$new       = isset($_POST['ability_value']) ? $_POST['ability_value'] : array();
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

	// Get free stuff from template to get specialities
	$templatefree = vtm_get_free_levels('SKILL',$templateID);
	
	//print_r($new);
	//print_r($current);

	foreach ($abilities as $ability) {
		$key     = sanitize_key($ability->name);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		
		if ($value > 0) {
			if (isset($saved[$key]->COMMENT) && $saved[$key]->COMMENT != '')
				$comment = $saved[$key]->COMMENT ;
			elseif (isset($templatefree[$key]->SPECIALISATION))
				$comment = $templatefree[$key]->SPECIALISATION;
			else
				$comment = '';
			
			$data = array(
				'CHARACTER_ID'  => $characterID,
				'SKILL_ID'      => $ability->ID,
				'LEVEL'         => $value,
				'COMMENT'		=> $comment
			);
			if (isset($saved[$key])) {
				if ($saved[$key]->LEVEL != $value) {
					//echo "<li>Updated $key at $value</li>\n";
					// update
					$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_SKILL",
						$data,
						array (
							'ID' => $saved[$key]->ID
						)
					);
				} //else {
					//echo "<li>No need to update $key</li>\n";
				//}
			} else {
				//echo "<li>Added $key at $value</li>\n";
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
			//echo "<li>Delete $id ($sql)</li>\n";
			$wpdb->get_results($sql);
		}
	}

	return $characterID;
}

function vtm_save_disciplines($characterID,$templateID) {
	global $wpdb;

	$new = $_POST['discipline_value'];
	$disciplines = vtm_get_chargen_disciplines($characterID);
	
	$sql = "SELECT disc.NAME, cdisc.DISCIPLINE_ID, cdisc.ID, cdisc.LEVEL
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE cdisc,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE 
				cdisc.DISCIPLINE_ID = disc.ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));

	//echo "<li>SQL:$sql</li>\n";
	//print_r($new);
	//print_r($disciplines);
	//print_r($saved);

	foreach ($disciplines as $discipline) {
		$key     = sanitize_key($discipline->name);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		if ($value > 0) {
			$data = array(
				'CHARACTER_ID'  => $characterID,
				'DISCIPLINE_ID' => $discipline->ID,
				'LEVEL'         => $value
			);
			if (isset($saved[$key])) {
				if ($saved[$key]->LEVEL != $value) {
					//echo "<li>Updated $key at $value</li>\n";
					// update
					$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE",
						$data,
						array (
							'ID' => $saved[$key]->ID
						)
					);
				} //else {
					//echo "<li>No need to update $key</li>\n";
				//}
			} else {
				//echo "<li>Added $key at $value</li>\n";
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
			// Delete any selected rituals associated with a deleted discipline
			$sql = "SELECT crit.ID 
					FROM 
						" . VTM_TABLE_PREFIX . "CHARACTER_RITUAL crit,
						" . VTM_TABLE_PREFIX . "RITUAL rit
					WHERE 
						crit.CHARACTER_ID = %s 
						AND crit.RITUAL_ID = rit.ID
						AND rit.DISCIPLINE_ID = %s";
			$sql = $wpdb->prepare($sql,$characterID,$saved[$id]->DISCIPLINE_ID);
			//echo "<p>ritual SQL: $sql</p>";
			$rituals = $wpdb->get_col($sql);
			if (count($rituals) > 0) {
				foreach ($rituals as $rid) {
					$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_RITUAL
							WHERE ID = %s";
					$sql = $wpdb->prepare($sql,$rid);
					$wpdb->get_results($sql);
				}
				//echo "<li>Delete ritual $rid ($sql)</li>\n";
			}
		
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE
					WHERE CHARACTER_ID = %s AND DISCIPLINE_ID = %s";
			$sql = $wpdb->prepare($sql,$characterID,$saved[$id]->DISCIPLINE_ID);
			//echo "<li>Delete $id ($sql)</li>\n";
			$wpdb->get_results($sql);
		}
	}
	
	return $characterID;

}

function vtm_save_backgrounds($characterID,$templateID) {
	global $wpdb;

	$new = isset($_POST['background_value']) ? $_POST['background_value'] : array();
	$backgrounds = vtm_get_chargen_backgrounds($characterID);
	
	$sql = "SELECT bg.NAME, cbg.BACKGROUND_ID, cbg.ID, cbg.COMMENT, cbg.LEVEL
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND cbg,
				" . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE 
				cbg.BACKGROUND_ID = bg.ID
				AND cbg.CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));

	// Get free stuff from template to get specialities
	$templatefree = vtm_get_free_levels('BACKGROUND',$templateID);

	//print_r($new);
	//print_r($current);

	foreach ($backgrounds as $background) {
		$key     = sanitize_key($background->name);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		
		if ($value > 0) {
			if (isset($saved[$key]->COMMENT) && $saved[$key]->COMMENT != '')
				$comment = $saved[$key]->COMMENT ;
			elseif (isset($templatefree[$key]->SPECIALISATION))
				$comment = $templatefree[$key]->SPECIALISATION;
			else
				$comment = '';
			
			$data = array(
				'CHARACTER_ID'  => $characterID,
				'BACKGROUND_ID' => $background->ID,
				'LEVEL'         => $value,
				'COMMENT'       => $comment
			);
			if (isset($saved[$key])) {
				if ($saved[$key]->LEVEL != $value) {
					//echo "<li>Updated $key at $value</li>\n";
					// update
					$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND",
						$data,
						array (
							'ID' => $saved[$key]->ID
						)
					);
				} //else {
					//echo "<li>No need to update $key</li>\n";
				//}
			} else {
				//echo "<li>Added $key at $value</li>\n";
				// insert
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND",
							$data,
							array ('%d', '%d', '%d', '%s')
						);
			}
		}
	}
		
	// Delete anything no longer needed
	foreach ($saved as $id => $value) {
	
		if (!isset($new[$id]) || $new[$id] == 0) {
			//echo "<li>Deleted $id</li>\n";
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND
					WHERE CHARACTER_ID = %s AND BACKGROUND_ID = %s";
			$wpdb->get_results($wpdb->prepare($sql,$characterID,$saved[$id]->BACKGROUND_ID));
		}
	}

	return $characterID;
	
}

function vtm_save_virtues($characterID, $templateID) {
	global $wpdb;
	
	$new = isset($_POST['virtue_value']) ? $_POST['virtue_value'] : array();
	$selectedpath = $_POST['path'];
	
	// Update CHARACTER with road/path ID
	$data = array (
		'ROAD_OR_PATH_ID'     => $selectedpath
	);
	//print_r($data);
	$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER",
		array ('ROAD_OR_PATH_ID' => $selectedpath),
		array ('ID' => $characterID)
	);
	
	// Update Willpower based on Courage
	$wpid   = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "STAT WHERE NAME = 'Willpower'");
	$wpcsid = $wpdb->get_var($wpdb->prepare(
				"SELECT ID 
				FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT 
				WHERE CHARACTER_ID = %s and STAT_ID = %s", $characterID, $wpid));
	$data = array(
		'CHARACTER_ID' => $characterID,
		'STAT_ID'      => $wpid,
		'LEVEL'        => isset($new['courage']) ? $new['courage'] : 0
	);
	if (isset($wpcsid)) {
		// update
		$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_STAT",
			$data,
			array ('ID' => $wpcsid)
		);
	} 
	else {
		// insert
		//print_r($data);
		$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_STAT",
					$data,
					array ('%d', '%d', '%d')
				);
	}
	
	// Update CHARACTER_STAT with virtue ratings
	$virtues  = vtm_get_chargen_virtues();
	$statkey1 = vtm_get_virtue_statkey(1, $selectedpath);
	$statkey2 = vtm_get_virtue_statkey(2, $selectedpath);
	
	$statval1 = isset($new[$statkey1]) ? $new[$statkey1] : 0;
	$statval2 = isset($new[$statkey2]) ? $new[$statkey2] : 0;
	
	$sql = "SELECT stats.NAME, cstat.STAT_ID, cstat.ID
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat,
				" . VTM_TABLE_PREFIX . "STAT stats
			WHERE 
				stats.ID = cstat.STAT_ID
				AND stats.GROUPING = 'Virtue'
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	foreach ($virtues as $attribute) {
		$key     = sanitize_key($attribute->name);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		
		if ($key == $statkey1 || $key == $statkey2 || $key == 'courage') {
			$data = array(
				'CHARACTER_ID' => $characterID,
				'STAT_ID'      => $attribute->ID,
				'LEVEL'        => $value
			);
			if (isset($saved[$key])) {
				//echo "<li>Updated $key at $value</li>\n";
				// update
				$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_STAT",
					$data,
					array (
						'ID' => $saved[$key]->ID
					)
				);
			} 
			else {
				//echo "<li>Added $key at $value</li>\n";
				// insert
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_STAT",
							$data,
							array ('%d', '%d', '%d')
						);
			}
		}
	}
	

	// Delete anything no longer needed
	foreach ($saved as $id => $value) {
	
		if (!isset($new[$id])) {
			// Delete
			$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT
					WHERE CHARACTER_ID = %s AND STAT_ID = %s";
			$sql = $wpdb->prepare($sql,$characterID,$saved[$id]->STAT_ID);
			//echo "<li>Delete $id ($sql)</li>\n";
			$wpdb->get_results($sql);
		}
	}

	return $characterID;
}

function vtm_save_basic_info($characterID, $templateID) {
	global $wpdb;
	global $current_user;
		
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
			echo "<p style='color:red'><b>Error:</b> Player could not be added</p>\n";
		} 
	
	} else {
		$playerid = vtm_get_player_id(stripslashes($_POST['player']));
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
		$rating = $wpdb->get_var($wpdb->prepare("SELECT ROAD_OR_PATH_RATING FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$currentclanid = $wpdb->get_var($wpdb->prepare("SELECT PRIVATE_CLAN_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$discspends = count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND 
						WHERE CHARACTER_ID = %s AND (ITEMTABLE='DISCIPLINE' OR ITEMTABLE = 'PATH')", $characterID)));
		$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND 
						WHERE CHARACTER_ID = %s AND (ITEMTABLE='DISCIPLINE' OR ITEMTABLE = 'PATH')", $characterID)));
		$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE 
						WHERE CHARACTER_ID = %s", $characterID)));
		$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "CHARACTER_PATH 
						WHERE CHARACTER_ID = %s", $characterID)));
	} else {
		$generationid = $config->DEFAULT_GENERATION_ID;
		$path		  = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE NAME = 'Humanity';");
		$dob = '';
		$doe = '';
		$sire = '';
		$rating = 0;
		$currentclanid = 0;
		$discspends = 0;
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

		'ROAD_OR_PATH_RATING'		=> $rating,				// Set later in virtues
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
		'DELETED'					=> 'N'
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
			echo "<p style='color:green'>Updated Character</p>\n";
		else if ($result !== 0) {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update character</p>\n";
		}

		// Fix if row in CHARACTER_GENERATION table is missing
		$sql = "SELECT COUNT(ID) FROM " . VTM_TABLE_PREFIX . "CHARACTER_GENERATION WHERE CHARACTER_ID = %s";
		$count = $wpdb->get_var($wpdb->prepare($sql, $characterID));
		if (!isset($count) || $count == 0) {
			echo "<p style='color:red'>Fixing missing CHARACTER_GENERATION row</p>\n";
			if (is_user_logged_in()) {
				get_currentuserinfo();
				$loggedin = $current_user->user_login;
			} else {
				$loggedin = '';
			}
			// Add character generation info
			$dataarray = array (
				'CHARACTER_ID'     => $characterID,
				'TEMPLATE_ID'      => $templateID,
				'NOTE_TO_ST'       => '',
				'NOTE_FROM_ST'	   => '',
				
				'WORDPRESS_ID'     => $loggedin,
				'DATE_OF_APPROVAL' => ''
			);
			$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_GENERATION",
						$dataarray,
						array (
							'%d', 		'%d', 		'%s', 		'%s',
							'%s', 		'%s'
						)
					);
		
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
						'%s', 		'%s', 		'%s', 		'%s'
					)
				);
		$characterID = $wpdb->insert_id;
		if ($characterID == 0) {
			echo "<p style='color:red'><b>Error:</b> Character could not be added</p>\n";
		} else {
			if (is_user_logged_in()) {
				get_currentuserinfo();
				$loggedin = $current_user->user_login;
			} else {
				$loggedin = '';
			}
			// Add character generation info
			$dataarray = array (
				'CHARACTER_ID'     => $characterID,
				'TEMPLATE_ID'      => $templateID,
				'NOTE_TO_ST'       => '',
				'NOTE_FROM_ST'	   => '',
				
				'WORDPRESS_ID'     => $loggedin,
				'DATE_OF_APPROVAL' => ''
			);
			$wpdb->insert(VTM_TABLE_PREFIX . "CHARACTER_GENERATION",
						$dataarray,
						array (
							'%d', 		'%d', 		'%s', 		'%s',
							'%s', 		'%s'
						)
					);
			
			vtm_email_new_character($_POST['email'], $characterID, $playerid, 
				$_POST['character'], $_POST['priv_clan'], $_POST['player'], $_POST['concept'], $template);
		}
	}
	
	// Delete any spends on Disciplines and paths if the clan has changed
	if ($characterID > 0 && $currentclanid != $_POST['priv_clan'] && $discspends > 0) {
		$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
				WHERE CHARACTER_ID = %s AND (ITEMTABLE = 'DISCIPLINE' OR ITEMTABLE = 'PATH')";
		$wpdb->get_results($wpdb->prepare($sql,$characterID));
		$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
				WHERE CHARACTER_ID = %s AND (ITEMTABLE = 'DISCIPLINE' OR ITEMTABLE = 'PATH')";
		$wpdb->get_results($wpdb->prepare($sql,$characterID));
		$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE
				WHERE CHARACTER_ID = %s";
		$wpdb->get_results($wpdb->prepare($sql,$characterID));
		$sql = "DELETE FROM " . VTM_TABLE_PREFIX . "CHARACTER_PATH
				WHERE CHARACTER_ID = %s";
		$wpdb->get_results($wpdb->prepare($sql,$characterID));
		
	}
	
	// Resend the character confirmation email if the button was pressed
	if (isset($_POST['chargen-resend-email'])) {
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
		if (strpos($charref,'/') && strpos($charref,'/', strpos($charref,'/') + 1)) {
			$ref = explode('/',$charref);
			$id   = $ref[0] * 1;
			$pid  = $ref[1] * 1;
			$tid  = $ref[2] * 1;
			$wpid = $ref[3] * 1;
		
			// Check player ID is valid based on character ID
			$sql = "SELECT PLAYER_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s";
			$result = $wpdb->get_row($wpdb->prepare($sql, $id));
			if (count($result) == 0 || $result->PLAYER_ID != $pid)
				$id = -1;
		
			// Check that wordpress ID is that of the user that created the character
			//		Or that the current user is an ST 
			$mustbeloggedin = get_option('vtm_chargen_mustbeloggedin', '0');
			$correctlogin = vtm_get_chargenlogin($id);
			if (empty($correctlogin)) {
				$correctid = 0;
			} else {
				$bloguser = get_users('search=' . $correctlogin . '&number=1');
				$correctid = isset($bloguser[0]->ID) ? $bloguser[0]->ID : 0;
			}
			if (is_user_logged_in()) {
				get_currentuserinfo();
				$currentid = $current_user->ID;
			} else {
				$currentid = 0;
			}
			//echo "<li>CorrectLogin: $correctlogin, CorrectID: $correctid, current: $currentid, refid: $wpid</li>\n";
			
			if (!vtm_isST() && ($currentid != $wpid || $correctid != $currentid) )
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
		// if (is_user_logged_in()) {
			// get_currentuserinfo();
			// $wpid = $current_user->ID;
		// } else {
			// $wpid = 0;
		// }
		// $pid = vtm_get_player_id_from_characterID($id);
		// echo "<p>REF: $id-$wpid-$pid</p>\n";
	}
	elseif (isset($_GET['characterID']) && $_GET['characterID'] > 0 && vtm_isST()) {
		$id = $_GET['characterID'];
	} 
	else {
		$id = 0;
	}

	//echo "<li>ID: $id</li>\n";
	
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
	
	$playername = esc_sql($playername);
	
	if ($guess) {
		$playername = "%$playername%";
		$test = 'LIKE';
	} else {
		$test = '=';
	}
	
	$sql = "SELECT ID FROM " . VTM_TABLE_PREFIX . "PLAYER WHERE NAME $test %s";
	$sql = $wpdb->prepare($sql, $playername);
	//echo "<p>SQL: $sql</p>\n";
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

function vtm_get_chargenlogin($characterID) {
	global $wpdb;
	
	$sql = "SELECT WORDPRESS_ID 
		FROM 
			" . VTM_TABLE_PREFIX . "CHARACTER_GENERATION
		WHERE
			CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	return $wpdb->get_var($sql);

}

function vtm_get_templateid($characterID) {
	global $wpdb;
	
	$sql = "SELECT TEMPLATE_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER_GENERATION
		WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	
	if (isset($_POST['chargen_template'])) {
		if (isset($_POST['chargen_reference']) && $_POST['chargen_reference'] != "") {
			// look up what template the character was generated with
			$template = $wpdb->get_var($sql);
			//echo "Looked up template ID from character : $template<br />\n";
		} else {
			$template = $_POST['chargen_template'];
			//echo "Looked up template ID from Step 0 : $template<br />\n";
		}
	} 
	elseif (isset($_POST['selected_template']) && $_POST['selected_template'] != "") {
		$template = $_POST['selected_template'] ;
		//echo "Looked up template ID from last step : $template<br />\n";
	}
	else {
		$template = $wpdb->get_var($sql);
		//echo "Looked up template ID from character : $template<br />\n";
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

	/*if (is_user_logged_in()) {
		get_currentuserinfo();
		$userid       = $current_user->ID;
	} else {
		$userid = 0;
	}
	
	$ref = $characterID . '-' . $userid . '-' . $playerid; */
	$ref = vtm_get_chargen_reference($characterID);
	$clan = vtm_get_clan_name($clanid);
	$name = stripslashes($name);
	$tag = get_option( 'vtm_chargen_emailtag' );
	$toname = get_option( 'vtm_chargen_email_from_name', 'The Storytellers');
	$toaddr = get_option( 'vtm_chargen_email_from_address', get_bloginfo('admin_email') );
	$url = add_query_arg('reference', $ref, vtm_get_stlink_url('viewCharGen', true));
	$url = add_query_arg('confirm', true, $url);
	$player = stripslashes($player);
	
	$subject   = "$tag New Character Created: $name";
	$headers[] = "From: \"$toname\" <$toaddr>\n";
	$headers[] = "Cc: \"$toname\" <$toaddr>\n";
	
	$userbody = "Hello $player,
	
Your new character has been created:
	
	* Reference: $ref
	* Character Name: $name
	* Clan: $clan
	* Template: $template
	* Concept: 
	
" . stripslashes($concept) . "
	
Click this link to confirm your email address and to return to character generation: $url";
	
	//echo "<pre>$userbody</pre>\n";
	
	$result = wp_mail($email, $subject, $userbody, $headers);
	
	if (!$result)
		echo "<p>Failed to send email. Character Ref: $ref</p>\n";
	
}

function vtm_get_chargen_settings($templateID = 1) {
	global $wpdb;
	
	$sql = "SELECT NAME, VALUE FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_OPTIONS WHERE TEMPLATE_ID = %s";
	$sql = $wpdb->prepare($sql, $templateID);
	//echo "<p>SQL: $sql</p>\n";
	$result = $wpdb->get_results($sql);
	$settings = vtm_default_chargen_settings();
	
	if (count($result) == 0)
		return $settings;
	
	$keys = $wpdb->get_col($sql);
	$vals = $wpdb->get_col($sql,1);
	
	//print_r($result);
	//print_r($keys);
	//print_r($vals);

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
	
	//echo "<p>Clan: $clan</p>\n";
	
	$sql = "SELECT ID, NAME as name, DESCRIPTION as description, GROUPING as grp, SPECIALISATION_AT as specialisation_at
			FROM " . VTM_TABLE_PREFIX . "STAT
			WHERE
				$filter
			ORDER BY ORDERING";
	//echo "<p>SQL: $sql</p>\n";
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
		$filter = "WHERE NAME != 'Appearance'";
	
	$sql = "SELECT NAME as name, ID, DESCRIPTION as description, 
				GROUPING as grp, SPECIALISATION_AT as specialisation_at
			FROM " . VTM_TABLE_PREFIX . "STAT
			$filter
			ORDER BY ORDERING";
	//echo "<p>SQL: $sql</p>\n";
	$results = $wpdb->get_results($sql, $output_type);
	//print_r($results);
	return $results;

}

function vtm_get_chargen_virtues($characterID = 0) {
	global $wpdb;
			
	$sql = "SELECT NAME as name, ID, DESCRIPTION as description, GROUPING as grp, 
				SPECIALISATION_AT as specialisation_at
			FROM " . VTM_TABLE_PREFIX . "STAT
			WHERE
				GROUPING = 'Virtue'
			ORDER BY ORDERING";
	//echo "<p>SQL: $sql</p>\n";
	$results = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//print_r($results);
	
	return $results;

}

function vtm_get_virtue_statkey($statnum, $selectedpath) {
	global $wpdb;
	
	$statsql = "SELECT stat.NAME, stat.ID 
				FROM 
					" . VTM_TABLE_PREFIX . "ROAD_OR_PATH rop,
					" . VTM_TABLE_PREFIX . "STAT stat
				WHERE rop.ID = %s AND rop.STAT{$statnum}_ID = stat.ID";

	return sanitize_key($wpdb->get_var($wpdb->prepare($statsql, $selectedpath)));
	
}

function vtm_get_chargen_disciplines($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT disc.NAME as name, disc.ID, disc.DESCRIPTION as description, 
				IF(ISNULL(clandisc.DISCIPLINE_ID),'Non-Clan Discipline','Clan Discipline') as grp
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
			ORDER BY grp, name";
	$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>SQL: $sql</p>\n";
	$results = $wpdb->get_results($sql, $output_type);
	
	
	return $results;

}

function vtm_get_chargen_backgrounds($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT bg.NAME as name, bg.ID, bg.DESCRIPTION as description, bg.GROUPING as grp
			FROM " . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE
				bg.VISIBLE = 'Y'
			ORDER BY grp, name";

	$results = $wpdb->get_results($sql, $output_type);
	
	return $results;

}

function vtm_get_chargen_merits($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT item.NAME as name, item.ID, item.DESCRIPTION as description, item.GROUPING as grp, item.MULTIPLE as multiple
			FROM " . VTM_TABLE_PREFIX . "MERIT item
			WHERE
				item.VISIBLE = 'Y'
			ORDER BY GROUPING, VALUE DESC, NAME";
	//$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>SQL: $sql</p>\n";
	$results = $wpdb->get_results($sql, $output_type);
	
	return $results;

}
function vtm_get_chargen_rituals($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	// get rituals for disciplines bought with discipline points (and possibly
	// raised with freebie points and XP) and ones only bought with
	// freebies and/or XP
	
	$sql = "(SELECT item.NAME as name, item.ID, item.DESCRIPTION as description, item.LEVEL as level, 
					disc.NAME as grp, 
					IFNULL(xp.CHARTABLE_LEVEL, IFNULL(fb.LEVEL_TO,cdisc.LEVEL)) as discipline_level
			FROM 
				" . VTM_TABLE_PREFIX . "RITUAL item,
				" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE cdisc
				LEFT JOIN (
					SELECT ID, CHARTABLE_ID, LEVEL_TO
					FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
					WHERE
						CHARACTER_ID = %s 
						AND CHARTABLE = 'CHARACTER_DISCIPLINE'
				) fb
				ON
					fb.CHARTABLE_ID = cdisc.ID
				LEFT JOIN (
					SELECT ID, CHARTABLE_ID, CHARTABLE_LEVEL
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE
						CHARACTER_ID = %s 
						AND CHARTABLE = 'CHARACTER_DISCIPLINE'
				) xp
				ON
					xp.CHARTABLE_ID = cdisc.ID
				,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE
				item.VISIBLE = 'Y'
				AND item.DISCIPLINE_ID = cdisc.DISCIPLINE_ID
				AND item.DISCIPLINE_ID = disc.ID
				AND cdisc.CHARACTER_ID = %s
				AND item.LEVEL <= IFNULL(xp.CHARTABLE_LEVEL, IFNULL(fb.LEVEL_TO,cdisc.LEVEL))
			) UNION (
			SELECT item.NAME as name, item.ID, item.DESCRIPTION as description, item.LEVEL as level, 
					disc.NAME as grp, 
					IFNULL(xp.CHARTABLE_LEVEL, fb.LEVEL_TO) as discipline_level
			FROM 
				" . VTM_TABLE_PREFIX . "RITUAL item,
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND fb
				LEFT JOIN (
					SELECT ID, ITEMTABLE_ID, CHARTABLE_LEVEL
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE
						CHARACTER_ID = %s 
						AND ITEMTABLE = 'DISCIPLINE'
				) xp
				ON
					xp.ITEMTABLE_ID = fb.ITEMTABLE_ID
				,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE
				item.VISIBLE = 'Y'
				AND item.DISCIPLINE_ID = disc.ID
				AND fb.ITEMTABLE_ID = disc.ID
				AND fb.ITEMTABLE = 'DISCIPLINE'
				AND fb.CHARACTER_ID = %s 
				AND item.LEVEL <= IFNULL(xp.CHARTABLE_LEVEL, fb.LEVEL_TO)
			) UNION (
			SELECT item.NAME as name, item.ID, item.DESCRIPTION as description, item.LEVEL as level, 
					disc.NAME as grp, 
					xp.CHARTABLE_LEVEL as discipline_level
			FROM 
				" . VTM_TABLE_PREFIX . "RITUAL item,
				" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND xp,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE
				item.VISIBLE = 'Y'
				AND item.DISCIPLINE_ID = disc.ID
				AND xp.ITEMTABLE_ID = disc.ID
				AND xp.ITEMTABLE = 'DISCIPLINE'
				AND xp.CHARACTER_ID = %s 
				AND item.LEVEL <= xp.CHARTABLE_LEVEL
			)
			ORDER BY grp, LEVEL, NAME";
			
	$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID, $characterID, $characterID, $characterID);
	//echo "<p>SQL: $sql</p>\n";
	$results = vtm_sanitize_array($wpdb->get_results($sql, $output_type));
	//print_r($results);
	
	return $results;

}

function vtm_get_chargen_paths($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT path.NAME as name, path.ID, path.DESCRIPTION as description, disc.NAME as grp
			FROM 
				" . VTM_TABLE_PREFIX . "PATH path,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
				LEFT JOIN (
					SELECT ID, LEVEL, DISCIPLINE_ID
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE
					WHERE
						CHARACTER_ID = %s
				) as cp
				ON
					cp.DISCIPLINE_ID = disc.ID
			WHERE
				path.VISIBLE = 'Y'
				AND path.DISCIPLINE_ID = disc.ID
				AND NOT(ISNULL(cp.LEVEL))
			ORDER BY grp, name";
	$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>SQL: $sql</p>\n";
	$results = $wpdb->get_results($sql, $output_type);
	
	return $results;

}
function vtm_get_chargen_road($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT 'Path Rating' as name, road.ID, road.DESCRIPTION as description, road.NAME as grp
			FROM 
				" . VTM_TABLE_PREFIX . "ROAD_OR_PATH road,
				" . VTM_TABLE_PREFIX . "CHARACTER cha
			WHERE
				cha.ID = %s 
				AND cha.ROAD_OR_PATH_ID = road.ID";
	$sql = $wpdb->prepare($sql, $characterID);
	$results = $wpdb->get_results($sql, $output_type);
	//echo "<p>SQL: $sql</p>\n";
	//print_r($results);
	
	return $results;

}

function vtm_get_chargen_abilities($characterID = 0, $showsecondary = 0, $output_type = OBJECT) {
	global $wpdb;
	
	if ($showsecondary)
		$filter = "";
	else
		$filter = "AND (GROUPING = 'Talents' OR GROUPING = 'Skills' OR GROUPING = 'Knowledges')";
	
	$sql = "SELECT NAME as name, ID, DESCRIPTION as description, GROUPING as grp, 
				SPECIALISATION_AT as specialisation_at, MULTIPLE as multiple,
				CASE GROUPING WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ORDERING,
				MULTIPLE
			FROM " . VTM_TABLE_PREFIX . "SKILL
			WHERE
				VISIBLE = 'Y'
				$filter
			ORDER BY ORDERING DESC, NAME";
	//echo "<p>SQL: $sql</p>\n";
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

function vtm_render_dot_select($type, $itemid, $current, $pending, $free, $max, $submitted) {

	$output = "";
	$fulldoturl    = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$doturl        = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	
	if ($pending || $submitted) {
		$output .= "<input type='hidden' name='" . $type . "[" . $itemid . "]' value='$current' />\n";
	}
	$output .= "<fieldset class='dotselect'>\n";
	
	// Ensure that anything with a free dot is selected initially at that level or 
	// it won't be saved to the database
	if ($free > 0 && $current == 0)
		$current = $free;
	
	for ($index = $max ; $index > 0 ; $index--) {
		$radioid = "dot_{$type}_{$itemid}_{$index}";
		//echo "<li>$radioid: current:$current / index:$index / free:$free (" . ($index - $free) . ")</li>\n";
		if ($pending || $submitted) {
			if ($index <= $free)
				$output .= "<img src='$fulldoturl' alt='*' id='$radioid' />\n";
			elseif ($index <= $current )
				$output .= "<img src='$doturl' alt='*' id='$radioid' />\n";
			elseif ($index <= $pending)
				$output .= "<img src='$freebiedoturl' alt='*' id='$radioid' />\n";
			else
				$output .= "<img src='$emptydoturl' alt='*' id='$radioid' />\n";
		} else {
			$output .= "<input type='radio' id='$radioid' name='" . $type . "[" . $itemid . "]' value='$index' ";
			$output .= checked($current, $index, false);
			$output .= " /><label for='$radioid' title='$index'";
			
			if ($index <= $free)
				$output .= " class='freedot'";
			
			$output .= ">&nbsp;</label>\n";
		}
	}
	
	if ($free == 0 && $pending == 0 && !$submitted) {
		$radioid = "dot_{$type}_{$itemid}_clear";
		$output .= "<input type='radio' id='$radioid' name='" . $type . "[" . $itemid . "]' value='0' ";
		$output .= checked($current, 0, false);
		$output .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
	}
	
	$output .= "</fieldset>\n";
	
	
	return $output;

}

function vtm_render_pst_select($name, $info ) {

	$selected = isset($info['pst'][$name])     ? $info['pst'][$name]     : 0;
	$target   = isset($info['correct'][$name]) ? $info['correct'][$name] : 0;
	$actual   = isset($info['totals'][$name])  ? $info['totals'][$name]  : 0;

	$pst = "Primary/Secondary/Tertiary";
	switch ($selected) {
		case 1: 
			$pst = "Primary"; 
			break;
		case 2: 
			$pst = "Secondary"; 
			break;
		case 3: 
			$pst = "Tertiary"; 
			break;
		default:
			$selected = -1;
	}
	
	$spent = array();
	if ($selected > 0) {
		if ($actual > 0 && $actual != $target) {
			$pst .= " (spent $actual, target $target)";
		}
	} 
	elseif ($actual > 0) {
		$pst .= " (spent $actual)";
	}
	
	//$output = "<strong>$pst</strong><input name='$name' type='hidden' value='$selected' />\n";
	$output = "<strong>$pst</strong>\n";
	
	return $output;
}

function vtm_get_pst($saved, $posted, $items, $pdots, $sdots, $tdots, $freedot,
	$templatefree = array()) {

	$grouptotals = array();
	//print_r($templatefree);
	
	// Get all the groups
	// "physical" => 1
	foreach  ($items as $item) {
		$grouplist[sanitize_key($item->grp)] = 1;
	}
	//print_r($grouplist);
	
	// Work out how many dots have been spent in each group
	foreach  ($items as $item) {
		if (isset($item->multiple) && $item->multiple == 'Y')
			$key = sanitize_key($item->name) . "_" . $item->chartableid;
		else
			$key = sanitize_key($item->name);
		
		$grp = sanitize_key($item->grp);
		
		if (isset($posted[$key]))
			$level = $posted[$key];
		elseif (isset($saved[$key]->level_from))
			$level = $saved[$key]->level_from;
		else
			$level = 0;
			
		$freelevel = isset($templatefree[$key]->LEVEL) ? $templatefree[$key]->LEVEL : 0;
			
		//echo "<li>key: $key, grp: $grp, level: $level</li>";
		if ($level > 0  && isset($grouplist[$grp])) {
			if (isset($grouptotals[$grp]))
				$grouptotals[$grp] += max(0,$level - $freedot - $freelevel);
			elseif ($level > 0)
				$grouptotals[$grp] = max(0,$level - $freedot - $freelevel);
		}
	}
	//print_r($grouptotals);
	
	// Work out which groups are Primary, Secondary or Tertiary
	$groupselected = array();
	$matches = array(null,0,0,0);
	foreach ($grouptotals as $grp => $total) {
		switch($total) {
			case $pdots: 
				$groupselected[$grp] = 1;
				$matches[1] = 1;
				$grouplist[$grp] = 0;
				break;
			case $sdots: 
				$groupselected[$grp] = 2;
				$matches[2] = 1;
				$grouplist[$grp] = 0;
				break;
			case $tdots: 
				$groupselected[$grp] = 3;
				$matches[3] = 1;
				$grouplist[$grp] = 0;
				break;
			default: $groupselected[$grp] = 0;
		}
	}

	// Work out the last group, if other 2 are found
	if (array_sum($matches) == 2) {
		for ($i = 1; $i <= 3 ; $i++) {
			if ($matches[$i] == 0) {
				foreach ($grouplist as $grp => $notfound) {
					if ($notfound)
						$groupselected[$grp] = $i;
				}
			}
		}
	}
	
	//print_r($groupselected);
	$correct = array();
	foreach ($groupselected as $grp => $pst) {
		if ($pst == 1) $correct[$grp] = $pdots;
		if ($pst == 2) $correct[$grp] = $sdots;
		if ($pst == 3) $correct[$grp] = $tdots;
	}
	
	$out['pst']     = $groupselected;
	$out['totals']  = $grouptotals;
	$out['correct'] = $correct;
	
	//return array($groupselected, $grouptotals, array_keys($grouplist));
	return $out;
}

function vtm_render_freebie_stats($characterID, $submitted) {
	global $wpdb;
	
	$output      = "";
	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_get_freebie_costs('STAT');
	$freebiecosts = array_merge($freebiecosts, vtm_get_freebie_costs('ROAD_OR_PATH'));

	// display stats to buy
	$items = vtm_get_chargen_stats($characterID);
	$items = array_merge($items, vtm_get_chargen_road($characterID));
	
	// Current stats saved into db
	$saved = vtm_get_current_stats($characterID);
	$saved = array_merge($saved, vtm_get_current_road($characterID));
	
	// Current freebies saved into database
	$pendingfb = vtm_get_pending_freebies('STAT', $characterID);
	$pendingfb = array_merge($pendingfb, vtm_get_pending_freebies('ROAD_OR_PATH', $characterID));

	// Current bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('STAT', $characterID);  // name => value
	
	$geninfo = vtm_calculate_generation($characterID);
	
	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp,
			$freebiecosts, 'freebie_stat', 0, $submitted, $geninfo['MaxDot']);
	
	//print_r($saved);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";

	return $output;

}

function vtm_render_freebie_skills($characterID, $submitted, $templateID) {
	global $wpdb;
	
	$output  = "";
	$geninfo = vtm_calculate_generation($characterID);

	// COSTS OF skills - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_get_freebie_costs('SKILL');

	// display skills to buy
	$items = vtm_get_chargen_abilities($characterID, 1);

	// Current skills saved into db
	$saved = vtm_get_current_skills($characterID);
	
	// Current spent
	$pendingfb = vtm_get_pending_freebies('SKILL', $characterID);
	
	// Current bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('SKILL', $characterID);  // name => value

	// Get free stuff from template to get specialities
	$templatefree = vtm_get_free_levels('SKILL',$templateID);
	
	//print_r($templatefree);

	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp,
			$freebiecosts, 'freebie_skill', 1, $submitted, $geninfo['MaxDot'],
			$templatefree);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";

	return $output;

}

function vtm_render_freebie_disciplines($characterID, $submitted) {	
	$output      = "";

	$geninfo = vtm_calculate_generation($characterID);
	
	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_get_freebie_costs('DISCIPLINE', $characterID);

	$items = vtm_get_chargen_disciplines($characterID);

	// display stats to buy
	//	hover over radiobutton to show the cost
	$saved = vtm_get_current_disciplines($characterID);
	
	// Current spent
	$pendingfb = vtm_get_pending_freebies('DISCIPLINE', $characterID);
	
	// Current bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('DISCIPLINE', $characterID);  // name => value

	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp,
			$freebiecosts, 'freebie_discipline', 1, $submitted, $geninfo['MaxDisc']);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";

	return $output;

}

function vtm_render_xp_disciplines($characterID, $submitted) {
	global $wpdb;
	
	$output  = "";

	$xpcosts   = vtm_get_chargen_xp_costs('DISCIPLINE', $characterID);
	$items     = vtm_get_chargen_disciplines($characterID);
	$saved     = vtm_get_current_disciplines($characterID);
	$pendingfb = vtm_get_pending_freebies('DISCIPLINE', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('DISCIPLINE', $characterID);
	$geninfo   = vtm_calculate_generation($characterID);
	
	//print_r($xpcosts);
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
		$pendingxp, 'xp_discipline', 1, $submitted,array(), $geninfo['MaxDisc']);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";

	return $output;

}

function vtm_render_freebie_paths($characterID, $submitted) {
	
	$output      = "";

	$freebiecosts = vtm_get_freebie_costs('PATH', $characterID);
	$items     = vtm_get_chargen_paths($characterID);
	$saved     = vtm_get_current_paths($characterID);
	$pendingfb = vtm_get_pending_freebies('PATH', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('PATH', $characterID);

	//print_r($currentpending);
	
	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp,
			$freebiecosts, 'freebie_path', 1, $submitted, 5);

	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";

	return $output;

} 

function vtm_render_freebie_backgrounds($characterID, $submitted, $templateID) {
	global $wpdb;
	
	$output      = "";
	$settings    = vtm_get_chargen_settings($templateID);
	$max2display = $wpdb->get_var($wpdb->prepare("SELECT MAX_DISCIPLINE FROM " . VTM_TABLE_PREFIX . "GENERATION WHERE ID = %s", $settings['limit-generation-low']));
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$dotstobuy   = 0;

	$freebiecosts = vtm_get_freebie_costs('BACKGROUND', $characterID);
	$items = vtm_get_chargen_backgrounds($characterID);
	$saved = vtm_get_current_backgrounds($characterID);
	$pendingfb = vtm_get_pending_freebies('BACKGROUND', $characterID);
		
	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, array(),
			$freebiecosts, 'freebie_background', 1, $submitted, $max2display);

	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";

	return $output;

}

function vtm_render_freebie_merits($characterID, $submitted) {
	global $wpdb;
	
	$output      = "";

	$freebiecosts = vtm_get_freebie_costs('MERIT', $characterID);
	$items     = vtm_get_chargen_merits($characterID);
	$saved     = vtm_get_current_merits($characterID);
	$pendingfb = vtm_get_pending_freebies('MERIT', $characterID);

	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, array(),
			$freebiecosts, 'freebie_merit', 1, $submitted);
	
	if ($rowoutput != "")
		$output .= "<table id='merit_freebie_table'>$rowoutput</table>\n";

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
			
			while ($from != $to && $to <= 10 && $to > 0) {
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
			
			while ($from != $to && $to <= 10 && $to > 0) {
				if ($data[$from]['FREEBIE_COST'] != 0) {
					$cost += $data[$from]['FREEBIE_COST'];
					$nonclancost[$i][$to] = $cost;
				}
				$from = $to;
				$to   = $data[$from]['NEXT_VALUE'];
				
			}
		}
		//print_r($data);
		//echo "<pre>\n";
		//print_r($clancost);
		//echo "</pre>\n";
		
		foreach ($items as $item) {
			$key = sanitize_key($item->NAME);
			if ($item->ISCLAN) {
				$outdata[$key] = $clancost;
			} else {
				$outdata[$key] = $nonclancost;
			}
		}
	} 
	elseif ($type == "MERIT") {

		$sql = "SELECT ID, NAME, COST FROM " . VTM_TABLE_PREFIX . "MERIT ORDER BY ID";
		$items = $wpdb->get_results($sql);
		
		foreach ($items as $item) {
			$key = sanitize_key($item->NAME);
			$outdata[$key][0][1] = $item->COST;
		
		}

	}
	else {
	
		$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . $type . " ORDER BY ID";
		$items = $wpdb->get_results($sql, OBJECT_K);
		
		foreach ($items as $item) {
			$key = sanitize_key($item->NAME);
		
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
					$from = isset($data[$i]['CURRENT_VALUE']) ? $data[$i]['CURRENT_VALUE'] : 0;
					$to   = isset($data[$i]['NEXT_VALUE']) ? $data[$i]['NEXT_VALUE'] : 0;
					$cost = 0;
					
					while ($from != $to && $to <= 10 && $to > 0) {
						if ($data[$from]['FREEBIE_COST'] != 0) {
							$cost += $data[$from]['FREEBIE_COST'];
							$outdata[$key][$i][$to] = $cost;
						}
						$from = $to;
						$to   = $data[$from]['NEXT_VALUE'];
						
						//echo "<li>name:{$item->NAME}, key: $key, i: $i, from: $from, to: $to</li>\n";
					}
				
				}
			} else {
				echo "<li>ERROR: Issue with cost model for {$item->NAME}. Please ask the admin to check and resave the cost model</li>\n";
			}
		}
	
	}
	
	// if ($type == "MERIT") {
		// print_r($data);
		// echo "<p>($type / $characterID) SQL: $sql</p>\n";
		// echo "<pre>\n";
		// print_r($outdata);
		// echo "</pre>\n";
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
		//echo "<pre>\n";
		//print_r($nonclancost);
		//echo "</pre>\n";
		
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
			$key = sanitize_key($item->NAME);
			$outdata[$key][0][1] = $item->XP_COST;
		}
	}
	elseif ($type == "RITUAL") {
		$sql = "SELECT ID, NAME, COST FROM " . VTM_TABLE_PREFIX . "RITUAL ORDER BY ID";
		$items = $wpdb->get_results($sql);
		
		foreach ($items as $item) {
			$key = sanitize_key($item->NAME);
			$outdata[$key][0][1] = $item->COST;
		}
	}
	else {
	
		$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX . $type . " ORDER BY ID";
		$items = $wpdb->get_results($sql, OBJECT_K);
		
		foreach ($items as $item) {
			$key = sanitize_key($item->NAME);
		
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
			$sql  = $wpdb->prepare($sql, $item->ID);
			$data = $wpdb->get_results($sql, ARRAY_A);
			
			if (count($data) > 0) {
				for ($i = 0 ; $i < 10 ; $i++) {
					$from = isset($data[$i]['CURRENT_VALUE']) ? $data[$i]['CURRENT_VALUE'] : 0;
					$to   = isset($data[$i]['NEXT_VALUE']) ? $data[$i]['NEXT_VALUE'] : 0;
					$cost = 0;
					
					while ($from != $to && $to <= 10) {
						if ($data[$from]['XP_COST'] != 0) {
							$cost += $data[$from]['XP_COST'];
							$outdata[$key][$i][$to] = $cost;
						}
						$from = $to;
						$to   = $data[$from]['NEXT_VALUE'];
						
						//echo "<li>name:{$item->NAME}, i: $i, from: $from, to: $to</li>\n";
					}
				
				}
			} else {
				echo "<li>ERROR: Issue with cost model for {$key}. Please ask the admin to check and resave the cost model</li>\n";
			}
		}
	
	}
	
	// if ($type == "STAT") {
		// print_r($data);
		// echo "<p>($type / $characterID) SQL: $sql</p>\n";
		// echo "<pre>\n";
		// print_r($outdata);
		// echo "</pre>\n";
	// }

	return $outdata;
}

function vtm_get_current_stats($characterID) {
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
	$items = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//echo "<p>SQL: $sql</p>";
	//print_r($items);
	
	return $items;
}
function vtm_get_current_road($characterID) {
	global $wpdb;

	$sql = "SELECT 
				'Path Rating'				as name, 
				stat1.LEVEL + stat2.LEVEL	as level_from,
				0	 						as chartableid, 
				'' 							as comment,
				road.ID 					as itemid, 
				road.name 	as grp
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER cha
				,
				" . VTM_TABLE_PREFIX . "ROAD_OR_PATH road
				LEFT JOIN (
					SELECT STAT_ID, LEVEL
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat
					WHERE
						CHARACTER_ID = %s
				) stat1
				ON 
					stat1.STAT_ID = road.STAT1_ID
				LEFT JOIN (
					SELECT STAT_ID, LEVEL
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER_STAT cstat
					WHERE
						CHARACTER_ID = %s
				) stat2
				ON 
					stat2.STAT_ID = road.STAT2_ID
			WHERE 
				cha.ROAD_OR_PATH_ID      = road.ID
				AND cha.ID = %s";
	$sql   = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
	//echo "<p>SQL: $sql</p>\n";
	$items = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	return $items;
}

function vtm_get_current_skills($characterID) {
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
	$items = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//echo "<p>SQL: $sql</p>\n";
	//print_r($items);
	
	return $items;
}

function vtm_get_current_backgrounds($characterID) {
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
	$items = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//echo "<p>SQL: $sql</p>\n";
	//print_r($items);
	
	return $items;
}

function vtm_get_current_merits($characterID) {
	global $wpdb;

	$sql = "SELECT 
				item.name, 
				0 						as level_from,
				IFNULL(cha_merit.ID,0) 	as chartableid, 
				item.ID 				as itemid, 
				item.GROUPING 			as grp,
				item.MULTIPLE			as multiple,
				cha_merit.LEVEL			as level_to
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
	$items = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//echo "<p>SQL: $sql</p>\n";
	//print_r($items);
	
	return $items;
}

function vtm_get_current_rituals($characterID) {
	global $wpdb;

	$sql = "SELECT 
				item.name, 
				0 						as level_from,
				IFNULL(cha_rit.ID,0) 	as chartableid, 
				item.ID 				as itemid, 
				disc.NAME 			    as discipline,
				IFNULL(cha_rit.LEVEL,0)	as level
			FROM 
				" . VTM_TABLE_PREFIX . "RITUAL item
				LEFT JOIN
					(SELECT ID, RITUAL_ID, LEVEL
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER_RITUAL
					WHERE
						CHARACTER_ID = %s
					) as cha_rit
				ON
					cha_rit.RITUAL_ID = item.ID,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disc
			WHERE 
				item.VISIBLE = 'Y'
				AND item.DISCIPLINE_ID = disc.ID
		    ORDER BY disc.NAME, item.LEVEL, item.name";
	$sql   = $wpdb->prepare($sql, $characterID);
	$items = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//echo "<p>SQL: $sql</p>\n";
	//print_r($items);
	
	return $items;
}

function vtm_get_current_disciplines($characterID) {
	global $wpdb;

	$sql = "SELECT
				item.name,
				IFNULL(chartable.level,0)		as level_from,
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
	$items = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//echo "<p>SQL: $sql</p>\n";
	//print_r($items);
	
	return $items;
}

function vtm_get_current_paths($characterID) {
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
	$items = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K));
	
	//echo "<p>SQL: $sql</p>\n";
	//print_r($items);
	
	return $items;
}

function vtm_get_freebies_spent($characterID, $templateID) {
	global $wpdb;

	$spent = 0;
		
	if (isset($_POST['freebie_stat'])       || isset($_POST['freebie_skill']) ||
		isset($_POST['freebie_discipline']) || isset($_POST['freebie_background']) ||
		isset($_POST['freebie_merit'])      || isset($_POST['freebie_path'])) {
			
		$freebiecosts['STAT']       = vtm_get_freebie_costs('STAT', $characterID);
		$freebiecosts['SKILL']      = vtm_get_freebie_costs('SKILL', $characterID);
		$freebiecosts['DISCIPLINE'] = vtm_get_freebie_costs('DISCIPLINE', $characterID);
		$freebiecosts['BACKGROUND'] = vtm_get_freebie_costs('BACKGROUND', $characterID);
		$freebiecosts['MERIT']      = vtm_get_freebie_costs('MERIT', $characterID);
		$freebiecosts['PATH']       = vtm_get_freebie_costs('PATH', $characterID);
		$freebiecosts['STAT'] = array_merge($freebiecosts['STAT'], vtm_get_freebie_costs('ROAD_OR_PATH'));
		
		$current['STAT']       = vtm_get_current_stats($characterID);
		$current['SKILL']      = vtm_get_current_skills($characterID);
		$current['DISCIPLINE'] = vtm_get_current_disciplines($characterID);
		$current['BACKGROUND'] = vtm_get_current_backgrounds($characterID);
		$current['MERIT']      = vtm_get_current_merits($characterID);
		$current['PATH']       = vtm_get_current_paths($characterID);
		$current['STAT'] = array_merge($current['STAT'], vtm_get_current_road($characterID));
				
		$bought['STAT']       = isset($_POST['freebie_stat']) ? $_POST['freebie_stat'] : array();
		$bought['SKILL']      = isset($_POST['freebie_skill']) ? $_POST['freebie_skill'] : array();
		$bought['DISCIPLINE'] = isset($_POST['freebie_discipline']) ? $_POST['freebie_discipline'] : array();
		$bought['BACKGROUND'] = isset($_POST['freebie_background']) ? $_POST['freebie_background'] : array();
		$bought['MERIT']      = isset($_POST['freebie_merit']) ? $_POST['freebie_merit'] : array();
		$bought['PATH']       = isset($_POST['freebie_path']) ? $_POST['freebie_path'] : array();

		$templatefree['SKILL']      = vtm_get_free_levels('SKILL', $templateID);
		
		foreach ($bought as $type => $items) {
			foreach ($items as $key => $levelto) {
				if (isset($templatefree[$type][$key]->LEVEL))
					$freelevel = $templatefree[$type][$key]->LEVEL;
				else
					$freelevel = 0;
				$currlevel   = isset($current[$type][$key]->level_from)  ? $current[$type][$key]->level_from  : 0;
				$levelfrom   = max($currlevel, $freelevel);
				$actualkey = preg_replace("/_\d+$/", "", $key);
			
				//echo "<li>Cost of $key ($actualkey) in $type from $levelfrom to $levelto </li>\n";

				if ($type == 'MERIT') {
					if (!isset($current[$type][$key])) {
						if (isset($current[$type][$actualkey]->multiple) && $current[$type][$actualkey]->multiple == 'Y') {
							$spent += isset($freebiecosts[$type][$actualkey][0][1]) ? $freebiecosts[$type][$actualkey][0][1] : 0;
							//echo "<li>Running total is $spent. Bought $actualkey ({$freebiecosts[$type][$actualkey][0][1]})</li>\n";
						}
					} else {
						$spent += isset($freebiecosts[$type][$key][0][1]) ? $freebiecosts[$type][$key][0][1] : 0;
						//echo "<li>Running total is $spent. Bought $key ({$freebiecosts[$type][$key][0][1]})</li>\n";
					}
				}
				elseif (!isset($current[$type][$key])) {
					//echo "$key becomes $actualkey<br />\n";
					if (isset($current[$type][$actualkey]->multiple) && $current[$type][$actualkey]->multiple == 'Y') {
						$levelfrom   = max($current[$type][$actualkey]->level_from, $freelevel);
						//echo "$actualkey - from: {$current[$actualkey]->level_from}, to: {$levelto}, cost: {$freebiecosts[$actualkey][$current[$actualkey]->level_from][$level_to]}<br />\n";
						$spent += isset($freebiecosts[$type][$actualkey][$levelfrom][$levelto]) ? $freebiecosts[$type][$actualkey][$levelfrom][$levelto] : 0;
						//echo "<li>Running total is $spent. Bought $actualkey from $levelfrom to $levelto ({$freebiecosts[$type][$actualkey][$levelfrom][$levelto]})</li>\n";
					}
				} 
				elseif ($type == 'STAT' && $key == 'pathrating') {
					$pathname = sanitize_key($current[$type][$key]->grp);
					//echo "$key becomes $pathname for Path of Enlightenment<br />\n";
					$spent += isset($freebiecosts[$type][$pathname][$levelfrom][$levelto]) ? $freebiecosts[$type][$pathname][$levelfrom][$levelto] : 0;
					//echo "<li>Running total is $spent. Bought $pathname to $levelto ({$freebiecosts[$type][$pathname][$levelfrom][$levelto]})</li>\n";
				}
				else {
					$spent += isset($freebiecosts[$type][$key][$levelfrom][$levelto]) ? $freebiecosts[$type][$key][$levelfrom][$levelto] : 0;
					//echo "<li>Running total is $spent. Bought $key to $levelto ({$freebiecosts[$type][$key][$levelfrom][$levelto]})</li>\n";
				}
			}
		
		}
		

	} else {
		$sql = "SELECT SUM(AMOUNT) FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
				WHERE CHARACTER_ID = %s";
		$sql = $wpdb->prepare($sql, $characterID);
		$spent = $wpdb->get_var($sql) * 1;
		
	}

	return $spent;
}

function vtm_get_chargen_xp_spent($characterID, $templateID) {
	global $wpdb;

	$spent = 0;
	if (isset($_POST['xp_stat'])       || isset($_POST['xp_skill']) ||
		isset($_POST['xp_discipline']) || isset($_POST['xp_background']) ||
		isset($_POST['xp_merit'])      || isset($_POST['xp_path'])) {
	
		$xpcosts['STAT']       = vtm_get_chargen_xp_costs('STAT', $characterID);
		$xpcosts['SKILL']      = vtm_get_chargen_xp_costs('SKILL', $characterID);
		$xpcosts['DISCIPLINE'] = vtm_get_chargen_xp_costs('DISCIPLINE', $characterID);
		$xpcosts['MERIT']      = vtm_get_chargen_xp_costs('MERIT', $characterID);
		$xpcosts['PATH']       = vtm_get_chargen_xp_costs('PATH', $characterID);
		
		$current['STAT']       = vtm_get_current_stats($characterID);
		$current['SKILL']      = vtm_get_current_skills($characterID);
		$current['DISCIPLINE'] = vtm_get_current_disciplines($characterID);
		$current['MERIT']      = vtm_get_current_merits($characterID);
		$current['PATH']       = vtm_get_current_paths($characterID);

		$pendingfb['STAT']       = vtm_get_pending_freebies('STAT', $characterID);
		$pendingfb['SKILL']      = vtm_get_pending_freebies('SKILL', $characterID);
		$pendingfb['DISCIPLINE'] = vtm_get_pending_freebies('DISCIPLINE', $characterID);
		$pendingfb['MERIT']      = vtm_get_pending_freebies('MERIT', $characterID);
		$pendingfb['PATH']       = vtm_get_pending_freebies('PATH', $characterID);
		
		$bought['STAT']       = isset($_POST['xp_stat']) ? $_POST['xp_stat'] : array();
		$bought['SKILL']      = isset($_POST['xp_skill']) ? $_POST['xp_skill'] : array();
		$bought['DISCIPLINE'] = isset($_POST['xp_discipline']) ? $_POST['xp_discipline'] : array();
		$bought['MERIT']      = isset($_POST['xp_merit']) ? $_POST['xp_merit'] : array();
		$bought['PATH']       = isset($_POST['xp_path']) ? $_POST['xp_path'] : array();

		$templatefree['SKILL']      = vtm_get_free_levels('SKILL', $templateID);

		foreach ($bought as $type => $items) {
			foreach ($items as $key => $level_to) {
			
				if (isset($templatefree[$type][$key]->LEVEL))
					$freelevel = $templatefree[$type][$key]->LEVEL;
				else
					$freelevel = 0;
				$currlevel   = isset($current[$type][$key]->level_from)  ? $current[$type][$key]->level_from  : 0;
				$levelfrom   = max($currlevel, $freelevel);
				$levelfrom = isset($pendingfb[$type][$key]->value) ? $pendingfb[$type][$key]->value : $levelfrom;
				
				if ($level_to != 0) {
					$actualkey = preg_replace("/_\d+$/", "", $key);
					
					if ($type == 'MERIT') {
						if (!isset($current[$type][$key])) {
							if (isset($current[$type][$actualkey]->multiple) && $current[$type][$actualkey]->multiple == 'Y') {
								$spent += isset($xpcosts[$type][$actualkey][0][1]) ? $xpcosts[$type][$actualkey][0][1] : 0;
								//echo "<li>$key / $actualkey, cost: {$xpcosts[$type][$actualkey][0][1]}</li>\n";
							}
						} else {
							//echo "<li>$key - from:$levelfrom, to:$level_to, cost: {$xpcosts[$type][$key][0][1]}</li>\n";
							$spent += isset($xpcosts[$type][$key][0][1]) ? $xpcosts[$type][$key][0][1] : 0;
						}
					} else {
						$spent += isset($xpcosts[$type][$actualkey][$levelfrom][$level_to]) ? $xpcosts[$type][$actualkey][$levelfrom][$level_to] : 0;
						//echo "<li>$key - $type, from $levelfrom to $level_to, cost: {$xpcosts[$type][$actualkey][$levelfrom][$level_to]}, running total: $spent</li>\n";
					}
				}
			}
		}
	} else {
	
		$sql = "SELECT SUM(AMOUNT) FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
				WHERE CHARACTER_ID = %s";
		$sql = $wpdb->prepare($sql, $characterID);
		$spent = -$wpdb->get_var($sql);
	
	}
	
	//echo "<li>spent on $table, $postvariable: $spent</li>\n";
	return $spent;
} 
function vtm_get_pending_freebies($table, $characterID) {
	global $wpdb;

	$sql = "SELECT freebie.ITEMNAME as name, freebie.LEVEL_TO as value,
			freebie.SPECIALISATION as specialisation, freebie.ID as id,
			freebie.PENDING_DETAIL as pending_detail
		FROM
			" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
		WHERE
			freebie.CHARACTER_ID = %s
			AND freebie.ITEMTABLE = '$table'";
	$sql = $wpdb->prepare($sql, $characterID);
	//echo "SQL: $sql</p>\n";
	
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
	//echo "SQL: $sql</p>\n";
	
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

function vtm_render_chargen_xp_stats($characterID, $submitted) {
	$output = "";

	$geninfo   = vtm_calculate_generation($characterID);

	// Get costs
	$xpcosts = vtm_get_chargen_xp_costs('STAT', $characterID);

	// display stats to buy
	$items = vtm_get_chargen_stats($characterID);

	// Get current stats in database
	$saved = vtm_get_current_stats($characterID);
	
	// Get Freebie points spent on stats
	$pendingfb = vtm_get_pending_freebies('STAT', $characterID);
	//print_r($pendingfb);
	
	// Currently bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('STAT', $characterID);  // name => value
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
		$pendingxp, 'xp_stat', 0, $submitted,array(),$geninfo['MaxDot']);

	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";
	
	return $output;
}

function vtm_render_chargen_xp_paths($characterID, $submitted) {
	$output = "";

	$xpcosts   = vtm_get_chargen_xp_costs('PATH', $characterID);
	$items     = vtm_get_chargen_paths($characterID);
	$saved     = vtm_get_current_paths($characterID);
	$pendingfb = vtm_get_pending_freebies('PATH', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('PATH', $characterID);
	//print_r($current_path);
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
		$pendingxp, 'xp_path', 1, $submitted,array(),5);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";
	
	return $output;
}

function vtm_render_chargen_xp_skills($characterID, $submitted,$templateID) {
	global $wpdb;
	
	$output = "";

	// Get costs
	$xpcosts   = vtm_get_chargen_xp_costs('SKILL', $characterID);
	$items     = vtm_get_chargen_abilities($characterID, 1);
	$saved     = vtm_get_current_skills($characterID);
	$pendingfb = vtm_get_pending_freebies('SKILL', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('SKILL', $characterID);
	$geninfo   = vtm_calculate_generation($characterID);
	$templatefree = vtm_get_free_levels('SKILL',$templateID);
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
	$pendingxp, 'xp_skill', 1, $submitted,array(), $geninfo['MaxDot'], $templatefree);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table>\n";

	return $output;

}

function vtm_render_chargen_xp_merits($characterID, $submitted) {
	global $wpdb;
	
	$output = "";

	// Get costs
	$xpcosts   = vtm_get_chargen_xp_costs('MERIT', $characterID);
	$fbcosts   = vtm_get_freebie_costs('MERIT', $characterID);
	$items     = vtm_get_chargen_merits($characterID);
	$saved     = vtm_get_current_merits($characterID);
	$pendingfb = vtm_get_pending_freebies('MERIT', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('MERIT', $characterID);

	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
		$pendingxp, 'xp_merit', 1, $submitted, $fbcosts);
	
	if ($rowoutput != "")
		$output .= "<table id='merit_xp_table'>$rowoutput</table>\n";

	return $output;

}
function vtm_render_chargen_xp_rituals($characterID, $submitted) {
	global $wpdb;
	
	$output = "";

	// Get costs
	$xpcosts   = vtm_get_chargen_xp_costs('RITUAL', $characterID);
	$fbcosts   = array();
	$items     = vtm_get_chargen_rituals($characterID, OBJECT_K);
	$saved     = vtm_get_current_rituals($characterID);
	$pendingfb = array();
	$pendingxp = vtm_get_pending_chargen_xp('RITUAL', $characterID);
	
	//print_r($items);
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
		$pendingxp, 'xp_ritual', 1, $submitted, $fbcosts);
	
	if ($rowoutput != "")
		$output .= "<table id='ritual_xp_table'>$rowoutput</table>\n";

	return $output;

}

function vtm_validate_basic_info($settings, $characterID, $templateID, $usepost = 1) {
	global $current_user;
	global $wpdb;

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	$wpdb->show_errors();

	// VALIDATE BASIC INFO
	//		- error: character name is not blank
	//		- error: new player? player name is not duplicated
	//		- error: old player? player name is found
	//		- error: login name doesn't already exist (except if it's the currently logged in acct)
	//		- error: email address is not blank and looks valid
	//		- error: concept is not blank
	//		- error: email address not confirmed
	
	if (!$usepost) {
	
		$sql = "SELECT ch.NAME, ch.PLAYER_ID, ch.WORDPRESS_ID, ch.EMAIL, ch.CONCEPT,
					pl.NAME as player, ch.PRIVATE_CLAN_ID
				FROM
					" . VTM_TABLE_PREFIX . "CHARACTER ch,
					" . VTM_TABLE_PREFIX . "PLAYER pl
				WHERE
					ch.PLAYER_ID = pl.id
					AND ch.ID = %s";
		$sql = $wpdb->prepare($sql, $characterID);
		$row = $wpdb->get_row($sql);
		//echo "<p>SQL: $sql</p>\n";
		//print_r($row);
	
		$dbcharacter = stripslashes($row->NAME);
		$dbplayer = stripslashes($row->player);
		$dbplayerID = $row->PLAYER_ID;
		$dbnewplayer = 'off';
		$dbwordpressID = $row->WORDPRESS_ID;
		$dbemail = $row->EMAIL;
		$dbconcept = stripslashes($row->CONCEPT);
		$dbclanid = stripslashes($row->CONCEPT);
	}
	
	$postcharacter  = $usepost ? (isset($_POST['character'])    ? $_POST['character']    : '') : $dbcharacter;
	$playername     = $usepost ? (isset($_POST['player'])       ? stripslashes($_POST['player'])       : '') : $dbplayer;
	$playeridguess  = $usepost ? (isset($_POST['playerID'])     ? $_POST['playerID']      : -1) : $dbplayerID;
	$postnewplayer  = $usepost ? (isset($_POST['newplayer'])    ? $_POST['newplayer']    : 'off') : $dbnewplayer;
	$login          = $usepost ? (isset($_POST['wordpress_id']) ? $_POST['wordpress_id'] : '') : $dbwordpressID;
	$email          = $usepost ? (isset($_POST['email'])        ? $_POST['email']        : '') : $dbemail;
	$postconcept    = $usepost ? (isset($_POST['concept'])      ? $_POST['concept']      : '') : $dbconcept;
	$postclanid     = $usepost ? (isset($_POST['priv_clan'])    ? $_POST['priv_clan']    : 0) : $dbclanid;
		
	if (empty($postcharacter)) {
		$errormessages .= "<li>ERROR: Please enter a character name</li>\n";
		$ok = 0;
		$complete = 0;
	}
	
	if (empty($playername)) {
		$errormessages .= "<li>ERROR: Please enter a player name</li>\n";
		$ok = 0;
		$complete = 0;
	} else {
		$playerid = vtm_get_player_id($playername);
		
		if ($postnewplayer == 'off') {
			// old player
			if (!isset($playerid)) {
				$ok = 0;
				$complete = 0;
				// can't find playername.  make a guess
				$playerid = vtm_get_player_id($playername, true);
				if (isset($playerid)) {
					$errormessages .= "<li>ERROR: Could not find a player with the name '$playername'. Did you mean '" . vtm_get_player_name($playerid) . "'?</li>\n";
				}
				else
					$errormessages .= "<li>ERROR: Could not find a player with the name '$playername'. Are you a new player?</li>\n";
			}
		} else {
			// new player
			if (isset($playerid)) {
				$ok = 0;
				$complete = 0;
				$errormessages .= "<li>ERROR: A player already exists with the name '$playername'. Are you a returning player?</li>\n";
			}
		}
	}
	
	if (empty($login)) {
		$errormessages .= "<li>ERROR: Please enter a login name</li>\n";
		$ok = 0;
		$complete = 0;
	}
	else {
		get_currentuserinfo();
		if (username_exists( $login ) && $login != $current_user->user_login) {
			$ok = 0;
			$complete = 0;
			$errormessages .= "<li>ERROR: An account already exists with the login name '$login'. Please choose another.</li>\n";
		}
		elseif (!validate_username( $login )) {
			$ok = 0;
			$complete = 0;
			$errormessages .= "<li>ERROR: Login name '$login' is invalid. Please choose another.</li>\n";
		}
		else {
			if ($characterID > 0) {
				$sql = "SELECT NAME FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE WORDPRESS_ID = %s AND ID != %s";
				$sql = $wpdb->prepare($sql, $login, $characterID);
			} else {
				$sql = "SELECT NAME FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE WORDPRESS_ID = %s";
				$sql = $wpdb->prepare($sql, $login);
			}
			$names = $wpdb->get_col($sql);
			if (count($names) > 0) {
				$ok = 0;
				$complete = 0;
				$errormessages .= "<li>ERROR: Login name '$login' has already been chosen for another character.</li>\n";
			}
		}
	}
	
	if (empty($email)) {
			$ok = 0;
			$complete = 0;
			$errormessages .= "<li>ERROR: Email address is missing.</li>\n";
	} else {
		if (!is_email($email)) {
			$ok = 0;
			$complete = 0;
			$errormessages .= "<li>ERROR: Email address '$email' does not seem to be a valid email address.</li>\n";
		}
	}
	
	if (empty($postconcept)) {
		$errormessages .= "<li>ERROR: Please enter your character concept.</li>\n";
		$ok = 0;
		$complete = 0;
	}
	
	$currentclanid = $wpdb->get_var($wpdb->prepare("SELECT PRIVATE_CLAN_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$discspends = count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND 
						WHERE CHARACTER_ID = %s AND (ITEMTABLE='DISCIPLINE' OR ITEMTABLE = 'PATH')", $characterID)));
	$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND 
						WHERE CHARACTER_ID = %s AND (ITEMTABLE='DISCIPLINE' OR ITEMTABLE = 'PATH')", $characterID)));
	$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE 
						WHERE CHARACTER_ID = %s", $characterID)));
	$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "CHARACTER_PATH 
						WHERE CHARACTER_ID = %s", $characterID)));

	if ($currentclanid != $postclanid && $postclanid != 0 && $discspends > 0) {
		$errormessages .= "<li>WARNING: All spends on Disciplines will be deleted due to the change in Clan</li>\n";
	}
	
	// Email address must be confirmed
	$confirm = $wpdb->get_var($wpdb->prepare("SELECT EMAIL_CONFIRMED FROM " . VTM_TABLE_PREFIX . "CHARACTER_GENERATION
		WHERE CHARACTER_ID = %s", $characterID));
	if ($confirm !== 'Y') {
		$complete = 0;
		$errormessages .= "<li>WARNING: You must confirm your email address by clicking the link that was emailed to you before
							your character can be submitted</li>";
	}
						
	return array($ok, $errormessages, $complete);
}

function vtm_validate_abilities($settings, $characterID, $templateID, $usepost = 1) {

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	$templatefree = vtm_get_free_levels('SKILL', $templateID);
	
	// VALIDATE ABILITIES
	// P/S/T
	//		- WARN/ERROR: correct number of points spent in each group
	// 		- ERROR: check that nothing is over the max
	
	if (!$usepost) {
		$saved = vtm_get_current_skills($characterID);
		foreach($saved as $row) {
			$dbvalues[sanitize_key($row->name)] = $row->level_from;
		}
	}
	
	$posted = isset($_POST['ability_value']) ? $_POST['ability_value'] : array();
	$postvalues = $usepost ? $posted : $dbvalues;
	
	if (count($postvalues) > 0) {
		
		$target = $settings['abilities-primary'] + $settings['abilities-secondary'] + $settings['abilities-tertiary'];
		$check = 0;
		
		$total = 0;
		foreach ($postvalues as $att => $val) {
			$free = isset($templatefree[$att]->LEVEL) ? $templatefree[$att]->LEVEL : 0;
			$total += max(0,$val - $free);
		}
		
		if ($total > $target) {
			$errormessages .= "<li>ERROR: You have spent too many points</li>\n";
			$ok = 0;
			$complete = 0;
		}
		elseif ($total < $target)  {
			$errormessages .= "<li>WARNING: You haven't spent enough points</li>\n";
			$complete = 0;
		}
			
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>\n";
		$complete = 0;
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_attributes($settings, $characterID, $templateID, $usepost = 1) {

	$ok = 1;
	$errormessages = "";
	$complete = 1;
		
	if (!$usepost) {
		$saved = vtm_get_current_stats($characterID);
		$dbvalues = array();
		//print_r($saved);
		foreach($saved as $row) {
			if ($row->grp == 'Physical' || $row->grp == 'Mental' || $row->grp == 'Social')
				$dbvalues[sanitize_key($row->name)] = $row->level_from;
		}
		//print_r($dbvalues);
	}
		
	$posted = isset($_POST['attribute_value']) ? $_POST['attribute_value'] : array();
	$postvalues = $usepost ? $posted : $dbvalues;
	
	// VALIDATE ATTRIBUTES
	// P/S/T
	//		- WARN/ERROR: correct number of points spent in each group
	// Point Spent
	//		- WARN/ERROR: point total correct
	if (count($postvalues) > 0) {

		if ($settings['attributes-method'] == 'PST') {
			$target = $settings['attributes-primary'] + $settings['attributes-secondary'] + $settings['attributes-tertiary'];
		} else {
			$target = $settings['attributes-points'];
		}
		
		$total = 0;
		foreach ($postvalues as $att => $val)
			$total += max(0,$val - 1);
		
		if ($total > $target) {
			$errormessages .= "<li>ERROR: You have spent too many points</li>\n";
			$ok = 0;
			$complete = 0;
		}
		elseif ($total < $target)  {
			$errormessages .= "<li>WARNING: You haven't spent enough points</li>\n";
			$complete = 0;
		}
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>\n";
		$complete = 0;
	}
	

	return array($ok, $errormessages, $complete);
}

function vtm_validate_disciplines($settings, $characterID, $templateID, $usepost = 1) {

	$ok = 1;
	$errormessages = "";
	$complete = 1;

	if (!$usepost) {
		$disciplines = vtm_get_current_disciplines($characterID);
		$dbvalues = array();
		foreach ($disciplines as $disc) {
			$dbvalues[sanitize_key($disc->name)] = $disc->level_from;
		}
	}
	
	$postvalues = $usepost ? 
				(isset($_POST['discipline_value']) ? $_POST['discipline_value'] : array()) :
				$dbvalues;

	// VALIDATE DISCIPLINES
	//		- spend the right amount of points
	if (count($postvalues) > 0) {
		$values = $postvalues;
		
		$total = 0;
		foreach  ($values as $id => $val) {
			$total += max(0,$val);
		}
		
		if ($total > $settings['disciplines-points']) {
			$errormessages .= "<li>ERROR: You have spent too many dots</li>\n";
			$ok = 0;
			$complete = 0;
		}
		elseif ($total < $settings['disciplines-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>\n";
			$complete = 0;
		}
			
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>\n";
		$complete = 0;
	}

	
	return array($ok, $errormessages, $complete);
}

function vtm_validate_backgrounds($settings, $characterID, $templateID, $usepost = 1) {
	global $wpdb;

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	$templatefree = vtm_get_free_levels('BACKGROUND', $templateID);
	
	if (!$usepost) {
		$sql = "SELECT bg.NAME, cbg.LEVEL 
				FROM 
					" . VTM_TABLE_PREFIX . "BACKGROUND bg,
					" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND cbg
				WHERE
					bg.ID = cbg.BACKGROUND_ID
					AND cbg.CHARACTER_ID = %s";
		$sql = $wpdb->prepare($sql, $characterID);
		$keys = $wpdb->get_col($sql, 0);
		$vals = $wpdb->get_col($sql, 1);
	
		if (count($keys) > 0)
			$dbvalues = vtm_sanitize_array(array_combine($keys, $vals));
		else
			$dbvalues = array();
		//echo "<p>SQL: $sql</p>\n";
		//print_r($dbvalues);
	}
	
	$postvalues = $usepost ? 
				(isset($_POST['background_value']) ? $_POST['background_value'] : array()) :
				$dbvalues;
				
	// VALIDATE BACKGROUNDS
	//		- all points spent
	if (isset($postvalues)) {
		$values = $postvalues;
						
		$total = 0;
		foreach ($values as $att => $val) {
			$free = isset($templatefree[$att]->LEVEL) ? $templatefree[$att]->LEVEL : 0;
			$total += max(0,$val - $free);
		}
		
		if ($total > $settings['backgrounds-points']) {
			$errormessages .= "<li>ERROR: You have spent too many dots</li>\n";
			$ok = 0;
			$complete = 0;
		}
		elseif ($total < $settings['backgrounds-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>\n";
			$complete = 0;
		}
							
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>\n";
		$complete = 0;
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_virtues($settings, $characterID, $templateID, $usepost = 1) {
	global $wpdb;

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	if (!$usepost) {
		$stats = vtm_get_current_stats($characterID);
		$dbvalues = array();
		foreach ($stats as $stat) {
			if ($stat->grp == 'Virtue')
				$dbvalues[sanitize_key($stat->name)] = $stat->level_from;
		}
		
		$dbpath = $wpdb->get_var($wpdb->prepare("SELECT ROAD_OR_PATH_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	}
	
	$postvalues = $usepost ? 
				(isset($_POST['virtue_value']) ? $_POST['virtue_value'] : array()) :
				$dbvalues;
	$postpath = $usepost ? 
				(isset($_POST['path']) ? $_POST['path'] : 0) :
				$dbpath;
	
	//print_r($postvalues);
	
	// VALIDATE VIRTUES
	//		- all points spent
	//		- point spent on the correct virtues
	if (count($postvalues) > 0) {
		$values = $postvalues;
		
		$selectedpath = $postpath;
		$statkey1 = vtm_get_virtue_statkey(1, $selectedpath);
		$statkey2 = vtm_get_virtue_statkey(2, $selectedpath);
		
		$total = 0;
		$statfail = 0;
		foreach  ($values as $key => $val) {
			$level = $val - vtm_has_virtue_free_dot($selectedpath, $settings);
			$total += $level;
			
			if ($key != $statkey1 && $key != $statkey2 && $key != 'courage') {
				$statfail = 1;
			} 
			elseif ($level == 0) {
				$errormessages .= "<li>WARNING: Virtues must each have at least 1 dot</li>\n";
				$complete = 0;
			}
			
		}
		
		if ($total > $settings['virtues-points']) {
			$errormessages .= "<li>ERROR: You have spent too many dots</li>\n";
			$ok = 0;
			$complete = 0;
		}
		elseif ($total < $settings['virtues-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>\n";
			$complete = 0;
		}
		if ($statfail) {
			$errormessages .= "<li>ERROR: Please update Virtues for the selected path</li>\n";
			$ok = 0;
			$complete = 0;
		}
		
							
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>\n";
		$complete = 0;
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_freebies($settings, $characterID, $templateID, $usepost = 1) {

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	if (!$usepost) {
		$dbmerit = array();
		$dbpath = array();
		$dbdisc = array();
		$items = vtm_get_pending_freebies('MERIT', $characterID);
		foreach ($items as $item) {
			$dbmerit[sanitize_key($item->name)] = $item->value;
		}
		$items = vtm_get_pending_freebies('PATH', $characterID);
		foreach ($items as $item) {
			$dbpath[sanitize_key($item->name)] = $item->value;
		}
		$items = vtm_get_pending_freebies('DISCIPLINE', $characterID);
		foreach ($items as $item) {
			$dbdisc[sanitize_key($item->name)] = $item->value;
		}
	}
	$postmerit = $usepost ? 
				(isset($_POST['freebie_merit']) ? $_POST['freebie_merit'] : array()) :
				$dbmerit;
	$postpath = $usepost ? 
				(isset($_POST['freebie_path']) ? $_POST['freebie_path'] : array()) :
				$dbpath;
	$postdisc = $usepost ? 
				(isset($_POST['freebie_discipline']) ? $_POST['freebie_discipline'] : array()) :
				$dbdisc;
	
	// VALIDATE FREEBIE POINTS
	//		Right number of points spent
	//		Not too many merits bought
	//		Not too many flaws bought
	//		Level of paths bought do not exceed level of discipline
	$meritsspent = 0;
	$flawsgained = 0;
	if (count($postmerit) > 0) {
		$bought = $postmerit;
		foreach ($bought as $name => $level_to) {
			if ($level_to > 0)
				$meritsspent += $level_to;
			else
				$flawsgained += -$level_to;
		}
		if ($settings['merits-max'] > 0 && $meritsspent > $settings['merits-max']) {
			$errormessages .= "<li>ERROR: You have bought too many points of Merits</li>\n";
			$ok = 0;
			$complete = 0;
		}
		if ($settings['flaws-max'] > 0 && $flawsgained > $settings['flaws-max']) {
			$errormessages .= "<li>ERROR: You have gained too many points from Flaws</li>\n";
			$ok = 0;
			$complete = 0;
		}
	}
	
	$points = $settings['freebies-points'];
	
	$spent = 0;
	
	$spent += vtm_get_freebies_spent($characterID, $templateID);
	
	if ($spent == 0) {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>\n";
		$complete = 0;
	}
	elseif ($spent > $points) {
		$errormessages .= "<li>ERROR: You have spent too many dots</li>\n";
		$ok = 0;
		$complete = 0;
	}
	elseif ($spent < $points) {
		$errormessages .= "<li>WARNING: You haven't spent enough dots</li>\n";
		$complete = 0;
	}
	
	if (count($postpath) > 0) {
		$pathinfo = vtm_get_current_paths($characterID);
		$bought = $postpath;
		foreach ($bought as $path => $level) {
			$disciplinekey = sanitize_key($pathinfo[$path]->grp);
			$max = isset($postdisc[$disciplinekey]) ? $postdisc[$disciplinekey] : $pathinfo[$path]->maximum;
		
			if ($level > $max) {
				$errormessages .= "<li>ERROR: The level in " . stripslashes($pathinfo[$path]->name) . " cannot be greater than the {$pathinfo[$path]->grp} rating</li>\n";
				$ok = 0;
				$complete = 0;
			}
		}
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_rituals($settings, $characterID, $templateID, $usepost = 1) {

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	$target = vtm_get_chargen_ritual_points($characterID, $settings, vtm_get_chargen_rituals($characterID));
	//print_r($target);
	
	if (!$usepost) {
		$dbvalues = array();
		$dbgroups = array();
		$dball = array();
		
		$items = vtm_get_current_rituals($characterID);
		$discipline = "";
		foreach ($items as $item) {
			$key = sanitize_key($item->name);
			
			$dbvalues[$key] = $item->level;
			
			if ($discipline != $item->discipline) {
				$dbgroups[] = sanitize_key($item->discipline);
				$discipline = $item->discipline;
			}
		}
		//print_r($dbvalues);
		//print_r($dbgroups);
	}
	
	$postvalues = $usepost ? 
				(isset($_POST['ritual_value']) ? $_POST['ritual_value'] : array()) :
				$dbvalues;
	$postgroups = $usepost ? 
				(isset($_POST['group']) ? $_POST['group'] : array()) :
				$dbgroups;
	$postall    = $usepost ? 
				(isset($_POST) ? $_POST : array()) :
				$dball;
	
	if (count($postvalues) > 0) {
		$values = $postvalues;
		
		$groups = $postgroups;
		$rituals = vtm_get_chargen_rituals($characterID);
		//print_r($rituals);

		$check = 0;
		
		foreach ($groups as $group) {
			$disctotal = 0;
			$groupname = "";
			foreach ($rituals as $ritual) {
				$key = sanitize_key($ritual->name);
				if (sanitize_key($ritual->grp) == $group) {
					$disctotal += isset($values[$key]) ? max(0,$values[$key]) : 0;
					$groupname = $ritual->grp;
				}
			}
			
			if (isset($target[$group])) {
				if ($disctotal > $target[$group]) {
					$errormessages .= "<li>ERROR: You have spent too many points on $groupname Rituals</li>\n";
					$ok = 0;
					$complete = 0;
				}
				elseif ($disctotal < $target[$group])  {
					$errormessages .= "<li>WARNING: You haven't spent enough points on $groupname Rituals</li>\n";
					$complete = 0;
				}
			}
			elseif ($disctotal > 0) {
					$errormessages .= "<li>ERROR: You have spent points on $groupname Rituals but you don't have the discipline</li>\n";
					$ok = 0;
					$complete = 0;
			}
		}
	
	} else {
		$errormessages .= "<li>WARNING: You have not spent any points of rituals</li>\n";
		$complete = 0;
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_finishing($settings, $characterID, $templateID, $usepost = 1) {
	global $wpdb;

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	if (!$usepost) {
		$dbvalues = array();
		$dbcomments = array();
		
		$specialities = vtm_get_chargen_specialties($characterID);
		foreach ($specialities as $spec) {
			$dbvalues[]   = $spec['name'];
			$dbcomments[] = $spec['comment'];
		}
		
		$dob = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_BIRTH FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$dob_array = explode('-',$dob);
		$dbday_dob   = isset($_POST['day_dob'])   ? $_POST['day_dob']   : (isset($dob) ? strftime("%d", strtotime($dob)) : '');
		$dbmonth_dob = isset($_POST['month_dob']) ? $_POST['month_dob'] : (isset($dob) ? strftime("%m", strtotime($dob)) : '');
		$dbyear_dob  = isset($_POST['year_dob'])  ? $_POST['year_dob']  : (isset($dob) ? $dob_array[0] : '0000');
		
		$doe = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_EMBRACE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$doe_array = explode('-',$doe);
		$dbday_doe   = isset($_POST['day_doe'])   ? $_POST['day_doe']   : (isset($doe) ? strftime("%d", strtotime($doe)) : '');
		$dbmonth_doe = isset($_POST['month_doe']) ? $_POST['month_doe'] : (isset($doe) ? strftime("%m", strtotime($doe)) : '');
		$dbyear_doe  = isset($_POST['year_doe'])  ? $_POST['year_doe']  : (isset($dob) ? $doe_array[0] : '0000');
		
		$dbsire = $wpdb->get_var($wpdb->prepare("SELECT SIRE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	}
	
	//print_r($_POST);
	
	$postvalues = $usepost ? 
				(isset($_POST['fullname']) ? $_POST['fullname'] : array()) :
				$dbvalues;
	$postcomments = $usepost ? 
				(isset($_POST['comment']) ? $_POST['comment'] : array()) :
				$dbcomments;
	$postsire      = $usepost ? (isset($_POST['sire'])      ? $_POST['sire']      : '')     : $dbsire;
	$postday_dob   = $usepost ? (isset($_POST['day_dob'])   ? $_POST['day_dob']   : '')     : $dbday_dob;
	$postmonth_dob = $usepost ? (isset($_POST['month_dob']) ? $_POST['month_dob'] : '')     : $dbmonth_dob;
	$postyear_dob  = $usepost ? (isset($_POST['year_dob'])  ? $_POST['year_dob']  : '0000') : $dbyear_dob;
	$postday_doe   = $usepost ? (isset($_POST['day_doe'])   ? $_POST['day_doe']   : '')     : $dbday_doe;
	$postmonth_doe = $usepost ? (isset($_POST['month_doe']) ? $_POST['month_doe'] : '')     : $dbmonth_doe;
	$postyear_doe  = $usepost ? (isset($_POST['year_doe'])  ? $_POST['year_doe']  : '0000') : $dbyear_doe;

	// All specialities are entered
	// Sire name is entered
	// Dates are not the default dates
	
	if (count($postvalues) > 0) {
		foreach ($postvalues as $index => $name) {
		//print "<li>Speciality for $index/$name is $postcomments[$index]</li>";
			if (!isset($postcomments[$index]) || $postcomments[$index] == '') {
				$errormessages .= "<li>WARNING: Please specify a speciality for $name</li>\n";
				$complete = 0;
			}
		}
	}
	if ($postsire == '') {
		$errormessages .= "<li>WARNING: Please enter the name of your sire, or enter 'unknown' if your character does not know.</li>\n";
		$complete = 0;
}
	if ($postday_dob == 0 || $postmonth_dob == 0 || $postyear_dob == '0000') {
		$errormessages .= "<li>WARNING: Please enter your character's Date of Birth.</li>\n";
		$complete = 0;
	}
	if ($postday_doe == 0 || $postmonth_doe == 0 || $postyear_doe == '0000') {
		$errormessages .= "<li>WARNING: Please enter your character's Date of Embrace.</li>\n";
		$complete = 0;
	}
	if ($postyear_dob > date("Y") * 1) {
		$errormessages .= "<li>ERROR: Your character's Date of Birth cannot be in the future.</li>\n";
		$ok = 0;
		$complete = 0;
	}
	if ($postyear_doe > date("Y") * 1) {
		$errormessages .= "<li>ERROR: Your character's Date of Embrace cannot be in the future.</li>\n";
		$ok = 0;
		$complete = 0;
	}
	if ($postyear_dob != floor($postyear_dob)) {
		$errormessages .= "<li>ERROR: Your character's Date of Birth cannot be a decimal number.</li>\n";
		$ok = 0;
		$complete = 0;
	}
	if ($postyear_doe != floor($postyear_doe)) {
		$errormessages .= "<li>ERROR: Your character's Date of Embrace cannot be a decimal number.</li>\n";
		$ok = 0;
		$complete = 0;
	}
	if ($postyear_doe < $postyear_dob) {
		$errormessages .= "<li>ERROR: Your character's Date of Embrace cannot be before their Date of Birth.</li>\n";
		$ok = 0;
		$complete = 0;
	}
	

	return array($ok, $errormessages, $complete);
}

function vtm_validate_history($settings, $characterID, $templateID, $usepost = 1) {
	global $wpdb;

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	if (!$usepost) {
		$dbvalues = array();
		$dbtitles = array();
		$dbquestions = vtm_get_chargen_questions($characterID);
		foreach ($dbquestions as $question) {
			$dbvalues[] = $question->PENDING_DETAIL;
			$dbtitles[] = $question->TITLE;
		}
		$meritdbvalues = array();
		$meritdbtitles = array();
		$meritquestions = vtm_get_chargen_merit_questions($characterID);
		foreach ($meritquestions as $question) {
			$meritdbvalues[] = $question->PENDING_DETAIL;
			
			$title = $question->NAME;
			if (!empty($question->SPECIALISATION)) $title .= $question->SPECIALISATION;
			$title .= " (" . $question->VALUE . ")";
			$meritdbtitles[] = $title;
		}
		$bgdbvalues = array();
		$bgdbtitles = array();
		$bgquestions = vtm_get_chargen_background_questions($characterID);
		foreach ($bgquestions as $question) {
			$bgdbvalues[] = $question->PENDING_DETAIL;
			
			$title = $question->NAME . " " . $question->LEVEL;
			if (!empty($question->COMMENT)) $title .= " (" . $question->COMMENT . ")";	

			$bgdbtitles[] = $title;
		}
	} else {
		$dbquestions = array();
		$meritquestions = array();
		$bgquestions = array();
	}
	
	$postvalues = $usepost ? 
				(isset($_POST['question']) ? $_POST['question'] : array()) :
				$dbvalues;
	$posttitles = $usepost ? 
				(isset($_POST['question_title']) ? $_POST['question_title'] : array()) :
				$dbtitles;
	$meritpostvalues = $usepost ? 
				(isset($_POST['meritquestion']) ? $_POST['meritquestion'] : array()) :
				$meritdbvalues;
	$meritposttitles = $usepost ? 
				(isset($_POST['meritquestion_title']) ? $_POST['meritquestion_title'] : array()) :
				$meritdbtitles;
	$bgpostvalues = $usepost ? 
				(isset($_POST['bgquestion']) ? $_POST['bgquestion'] : array()) :
				$bgdbvalues;
	$bgposttitles = $usepost ? 
				(isset($_POST['bgquestion_title']) ? $_POST['bgquestion_title'] : array()) :
				$bgdbtitles;
	// All questions are entered
	
	if (count($postvalues) > 0) {
		foreach ($postvalues as $index => $text) {
			if (!isset($postvalues[$index]) || $postvalues[$index] == '') {
				$errormessages .= "<li>WARNING: Please fill in the '{$posttitles[$index]}' question.</li>\n";
				$complete = 0;
			}
		}
	} elseif (count($dbquestions) > 0) {
		$errormessages .= "<li>WARNING: Please fill in the background questions.</li>\n";
		$complete = 0;
	}
	if (count($meritpostvalues) > 0) {
		foreach ($meritpostvalues as $index => $text) {
			if (!isset($meritpostvalues[$index]) || $meritpostvalues[$index] == '') {
				$errormessages .= "<li>WARNING: Please fill in the '{$meritposttitles[$index]}' Merit/Flaw question.</li>\n";
				$complete = 0;
			}
		}
	} elseif (count($meritquestions) > 0) {
		$errormessages .= "<li>WARNING: Please fill in the Merit/Flaw questions.</li>\n";
		$complete = 0;
	}
	if (count($bgpostvalues) > 0) {
		foreach ($bgpostvalues as $index => $text) {
			if (!isset($bgpostvalues[$index]) || $bgpostvalues[$index] == '') {
				$errormessages .= "<li>WARNING: Please fill in the '{$bgposttitles[$index]}' Background question.</li>\n";
				$complete = 0;
			}
		}
	} elseif (count($bgquestions) > 0) {
		$errormessages .= "<li>WARNING: Please fill in the Background questions.</li>\n";
		$complete = 0;
	}
	return array($ok, $errormessages, $complete);
}

function vtm_validate_xp($settings, $characterID, $templateID, $usepost = 1) {

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	if (!$usepost) {
		$dbpath = array();
		$dbdisc = array();
		$items = vtm_get_pending_chargen_xp('PATH', $characterID);
		foreach ($items as $item) {
			$dbpath[sanitize_key($item->name)] = $item->value;
		}
		$items = vtm_get_pending_chargen_xp('DISCIPLINE', $characterID);
		foreach ($items as $item) {
			$dbdisc[sanitize_key($item->name)] = $item->value;
		}
	}
	
	$postpath = $usepost ? 
				(isset($_POST['xp_path']) ? $_POST['xp_path'] : array()) :
				$dbpath;
	$postdisc = $usepost ? 
				(isset($_POST['xp_discipline']) ? $_POST['xp_discipline'] : array()) :
				$dbdisc;
	
	// VALIDATE XP POINTS
	//		Not too many points spent
	//		Level of paths bought do not exceed level of discipline
	$points = vtm_get_total_xp(0, $characterID);
	$spent = 0;
	$spent += vtm_get_chargen_xp_spent($characterID, $templateID);
	
	if ($spent > $points) {
		$errormessages .= "<li>ERROR: You have spent too many dots</li>\n";
		$ok = 0;
		$complete = 0;
	}

	if (count($postpath) > 0) {
		$pathinfo = vtm_get_current_paths($characterID);
		$freebies = vtm_get_pending_freebies("DISCIPLINE", $characterID);
		$bought = $postpath;
		foreach ($bought as $path => $level) {
			$disciplinekey = sanitize_key($pathinfo[$path]->grp);
			
			$max = 	isset($postdisc[$disciplinekey]) && $postdisc[$disciplinekey] != 0 ? 
					$postdisc[$disciplinekey] : 
						(isset($freebies[$disciplinekey]) ?
						$freebies[$disciplinekey] :
						$pathinfo[$path]->maximum);
		
			if ($level > $max) {
				$errormessages .= "<li>ERROR: The level $level in " . stripslashes($pathinfo[$path]->name) . " cannot be greater than the {$pathinfo[$path]->grp} rating of $max</li>\n";
				$ok = 0;
				$complete = 0;
			}
		}
	}

	return array($ok, $errormessages, $complete);
}

function vtm_render_date_entry($fieldname, $day, $month, $year, $submitted) {

	if ($submitted) {
		$output = date_i18n(get_option('date_format'),strtotime("$year-$month-$day"));
	} else {
		$output ="
		<fieldset>
		<label>Month</label>
		<select id='month_$fieldname' name='month_$fieldname' >
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
		<label>Day</label>
		<select id='day_$fieldname'  name='day_$fieldname' >
			<option value='0'>[Select]</option>\n";
		for ($i = 1; $i <= 31 ; $i++) {
			$val = sprintf("%02d", $i);
			$output .= "<option value='$val' " . selected($val, $day, false) . ">$i</option>\n";
		}
	  
		$output .= "</select> -
		<label>Year</label>
		<input type='text' name='year_$fieldname' size=5 value='$year' />
		</fieldset>\n";
	}

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
		
	// STATS & SKILLS - stats & skills from table, with freebies and XP
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
				pendingfreebie.ITEMNAME     as freebiekey,
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				pendingxp.ITEMNAME		 	as xpkey,
				stat.SPECIALISATION_AT 		as specialisation_at,
				stat.ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "STAT stat,
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cs
				LEFT JOIN (
					SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID, SPECIALISATION, ITEMNAME
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s
						AND CHARTABLE = 'CHARACTER_STAT'
				) pendingxp
				ON 
					pendingxp.CHARTABLE_ID = cs.ID
				LEFT JOIN (
					SELECT ID, LEVEL_TO, CHARTABLE_ID, SPECIALISATION, ITEMNAME
					FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
					WHERE CHARACTER_ID = %s
						AND CHARTABLE = 'CHARACTER_STAT'
				) pendingfreebie
				ON
					pendingfreebie.CHARTABLE_ID = cs.ID
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
				pendingfreebie.ITEMNAME     as freebiekey,
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				pendingxp.ITEMNAME          as xpkey,
				skill.SPECIALISATION_AT 	as specialisation_at,
				CASE skill.GROUPING WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "SKILL skill,
				" . VTM_TABLE_PREFIX . "CHARACTER_SKILL cs
				LEFT JOIN (
					SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID, SPECIALISATION, ITEMNAME
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s
						AND CHARTABLE = 'CHARACTER_SKILL'
				) pendingxp
				ON 
					pendingxp.CHARTABLE_ID = cs.ID
				LEFT JOIN (
					SELECT ID, LEVEL_TO, CHARTABLE_ID, SPECIALISATION, ITEMNAME
					FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
					WHERE CHARACTER_ID = %s
						AND CHARTABLE = 'CHARACTER_SKILL'
				) pendingfreebie
				ON
					pendingfreebie.CHARTABLE_ID = cs.ID
			WHERE
				cs.SKILL_ID = skill.ID
				AND cs.CHARACTER_ID = %s
			ORDER BY
				skill.ORDERING DESC, skill.NAME)";
	$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID, $characterID, $characterID, $characterID, $characterID);
	$results1 = $wpdb->get_results($sql);
	
	//print "<p>SQL: $sql</p>";
	
	// SKILLS from freebie spends with pending XP
	$sql = "SELECT 				
				'SKILL'					as type,
				'Abilities' 			as typename,
				skill.NAME 				as itemname, 
				skill.GROUPING 			as grp, 
				0 					    as level,
				0						as id,
				''					    as spec,
				freebie.LEVEL_TO 	    as freebielevel,
				freebie.ID 			    as freebieid,
				freebie.SPECIALISATION  as freebiespec,
				freebie.ITEMNAME        as freebiekey,
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				pendingxp.ITEMNAME          as xpkey,
				skill.SPECIALISATION_AT 	as specialisation_at,
				CASE skill.GROUPING WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
					LEFT JOIN (
						SELECT ID, CHARTABLE_ID, CHARTABLE_LEVEL, SPECIALISATION, ITEMNAME
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							CHARTABLE = 'PENDING_FREEBIE_SPEND'
							AND ITEMTABLE = 'SKILL'
							AND CHARACTER_ID = %s
					) pendingxp
					ON
						pendingxp.CHARTABLE_ID = freebie.ID,
				" . VTM_TABLE_PREFIX . "SKILL skill
			WHERE
				freebie.CHARACTER_ID = %s
				AND skill.ID = freebie.ITEMTABLE_ID
				AND freebie.ITEMTABLE = 'SKILL'
				AND freebie.CHARTABLE_ID = 0";
	$sql = $wpdb->prepare($sql, $characterID, $characterID);
	$results2 = $wpdb->get_results($sql);
	
	//echo "<p>SQL: $sql</p>\n";
	
	// SKILLS from pending XP
	$sql = "SELECT 				
				'SKILL'					as type,
				'Abilities' 			as typename,
				skill.NAME 				as itemname, 
				skill.GROUPING 			as grp, 
				0 					    as level,
				0						as id,
				''					    as spec,
				0 	    			    as freebielevel,
				0 			            as freebieid,
				''  			        as freebiespec,
				''                      as freebiekey,
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				pendingxp.ITEMNAME          as xpkey,
				skill.SPECIALISATION_AT 	as specialisation_at,
				CASE skill.GROUPING WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND pendingxp,
				" . VTM_TABLE_PREFIX . "SKILL skill
			WHERE
				pendingxp.CHARACTER_ID = %s
				AND skill.ID = pendingxp.ITEMTABLE_ID
				AND pendingxp.ITEMTABLE = 'SKILL'
				AND pendingxp.CHARTABLE_ID = 0";
	$sql = $wpdb->prepare($sql, $characterID, $characterID);
	$results3 = $wpdb->get_results($sql);
	
	//echo "<p>SQL: $sql</p>\n";
	
	$results = array_merge($results1, $results2, $results3);
	//print_r($results3);
	
	foreach ($results as $row) {
		$level = max($row->level, $row->freebielevel, $row->xplevel);
		if ($level >= $row->specialisation_at && $row->specialisation_at > 0) {
			if (isset($row->xplevel)) {
				$updatetable = 'PENDING_XP_SPEND';
				$tableid     = $row->xpid;
				$comment     = $row->xpspec;
				$key         = sanitize_key($row->xpkey);
			}
			elseif (isset($row->freebielevel)) {
				$updatetable = 'PENDING_FREEBIE_SPEND';
				$tableid     = $row->freebieid;
				$comment     = $row->freebiespec;
				$key         = sanitize_key($row->freebiekey);
			}
			else {
				$updatetable = 'CHARACTER_' . $row->type;
				$tableid     = $row->id;
				$comment     = $row->spec;
				$key         = sanitize_key($row->itemname);
			}
			array_push($specialities, array(
					'name'    => $row->itemname,
					'title'    => $row->typename,
					'updatetable' => $updatetable,
					'tableid' => $tableid,
					'level'   => $level,
					'grp'     => $row->grp,
					'comment' => stripslashes($comment),
					'spec_at' => $row->specialisation_at,
					'key'     => $key));
		}
	}
	
	// MERITS
	$sql = "(SELECT
				'MERIT'					as type,
				'Merits and Flaws'		as typename,
				merit.NAME 				as itemname, 
				merit.GROUPING 			as grp, 
				merit.VALUE 			as level,
				0					    as id,
				''				        as spec,
				pendingfreebie.ID 		as freebieid,
				pendingfreebie.SPECIALISATION as freebiespec,
				pendingfreebie.ITEMNAME     as freebiekey,
				0 			            as xpid,
				''                      as xpspec,
				''					 	as xpkey,
				merit.HAS_SPECIALISATION as has_specialisation
			FROM
				" . VTM_TABLE_PREFIX . "MERIT merit,
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND pendingfreebie
			WHERE
				merit.ID = pendingfreebie.ITEMTABLE_ID
				AND pendingfreebie.ITEMTABLE = 'MERIT'
				AND pendingfreebie.CHARACTER_ID = %s
			ORDER BY
				merit.VALUE DESC, merit.NAME)
			UNION
			(SELECT
				'MERIT'					as type,
				'Merits and Flaws'		as typename,
				merit.NAME 				as itemname, 
				merit.GROUPING 			as grp, 
				merit.VALUE 			as level,
				0					    as id,
				''				        as spec,
				0					    as freebieid,
				''				        as freebiespec,
				''					    as freebiekey,
				pendingxp.ID 			as xpid,
				pendingxp.SPECIALISATION as xpspec,
				pendingxp.ITEMNAME		as xpkey,
				merit.HAS_SPECIALISATION as has_specialisation
			FROM
				" . VTM_TABLE_PREFIX . "MERIT merit,
				" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND pendingxp
			WHERE
				merit.ID = pendingxp.ITEMTABLE_ID
				AND pendingxp.ITEMTABLE = 'MERIT'
				AND pendingxp.CHARACTER_ID = %s
			ORDER BY
				merit.VALUE DESC, merit.NAME)";
	$sql = $wpdb->prepare($sql, $characterID, $characterID);
	//echo "<p>SQL: $sql</p>\n";
	$results = $wpdb->get_results($sql);
	
	//print_r($results);
	
	foreach ($results as $row) {
		if ($row->has_specialisation == 'Y' && (isset($row->id) || isset($row->freebieid) || isset($row->xpid))) {
			if (isset($row->xpid) && $row->xpid > 0) {
				$updatetable = 'PENDING_XP_SPEND';
				$tableid     = $row->xpid;
				$comment     = $row->xpspec;
				$key         = sanitize_key($row->xpkey);
			}
			elseif (isset($row->freebieid)) {
				$updatetable = 'PENDING_FREEBIE_SPEND';
				$tableid     = $row->freebieid;
				$comment     = $row->freebiespec;
				$key         = sanitize_key($row->freebiekey);
			}
			else {
				$updatetable = 'CHARACTER_' . $row->type;
				$tableid     = $row->id;
				$comment     = $row->spec;
				$key         = sanitize_key($row->itemname);
			}
			array_push($specialities, array(
					'name'    => $row->itemname,
					'title'    => $row->typename,
					'updatetable' => $updatetable,
					'tableid' => $tableid,
					'level'   => $row->level,
					'grp'     => $row->grp,
					'comment' => $comment,
					'spec_at' => 1,
					'key'     => $key));
		}
	}
	
	//echo "<p>SQL: $sql</p>\n";
	//echo "<pre>\n";
	//print_r($specialities);
	//echo "</pre>\n";
	return $specialities;
} 

function vtm_get_chargen_questions($characterID) {
	global $wpdb;
	
	$sql = "SELECT questions.TITLE, questions.ORDERING, questions.GROUPING, questions.BACKGROUND_QUESTION, 
				tempcharmisc.APPROVED_DETAIL, tempcharmisc.PENDING_DETAIL, tempcharmisc.DENIED_DETAIL, 
				tempcharmisc.ID AS miscID, questions.ID as questID
			FROM " . VTM_TABLE_PREFIX . "CHARACTER characters, 
				 " . VTM_TABLE_PREFIX . "EXTENDED_BACKGROUND questions
				LEFT JOIN (
					SELECT charmisc.APPROVED_DETAIL, charmisc.PENDING_DETAIL, charmisc.DENIED_DETAIL, 
						charmisc.ID AS ID, charmisc.QUESTION_ID, characters.ID as charID
					FROM " . VTM_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND charmisc, 
						 " . VTM_TABLE_PREFIX . "CHARACTER characters
					WHERE characters.ID = charmisc.CHARACTER_ID
				) tempcharmisc 
				ON questions.ID = tempcharmisc.QUESTION_ID AND tempcharmisc.charID = %d
			WHERE characters.ID = %d
				AND questions.VISIBLE = 'Y'
				AND questions.REQD_AT_CHARGEN = 'Y'
			ORDER BY questions.ORDERING ASC";
			
	/* $content = "<p>SQL: $sql</p>\n"; */
	
	$questions = $wpdb->get_results($wpdb->prepare($sql, $characterID, $characterID));
	return $questions;

}

function vtm_get_chargen_merit_questions($characterID) {
	global $wpdb;
	
	$sql = "SELECT fb.ID,
				merits.NAME, merits.BACKGROUND_QUESTION, fb.SPECIALISATION,
				fb.PENDING_DETAIL, merits.VALUE
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND fb,
				" . VTM_TABLE_PREFIX . "MERIT merits
			WHERE
				fb.CHARACTER_ID = %s
				AND fb.ITEMTABLE = 'MERIT'
				AND fb.ITEMTABLE_ID = merits.ID
				AND merits.BACKGROUND_QUESTION != ''";
	$sql = $wpdb->prepare($sql, $characterID);
	$questions = $wpdb->get_results($sql);
	
	return $questions;
}
function vtm_get_chargen_background_questions($characterID) {
	global $wpdb;
	
	
	$sql = "(SELECT cbg.ID, 'CHARACTER_BACKGROUND' as source,
				bg.NAME, bg.BACKGROUND_QUESTION, cbg.COMMENT,
				cbg.PENDING_DETAIL, 
				cbg.LEVEL
			FROM
				" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND cbg
				LEFT JOIN (
					SELECT ID, CHARTABLE_ID, LEVEL_TO
					FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
					WHERE CHARACTER_ID = %s 
						AND CHARTABLE = 'CHARACTER_BACKGROUND'
				) fb
				ON
					fb.CHARTABLE_ID = cbg.ID,
				" . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE
				cbg.CHARACTER_ID = %s
				AND cbg.BACKGROUND_ID = bg.ID
				AND bg.BACKGROUND_QUESTION != ''
				AND ISNULL(fb.ID))
			UNION
			(SELECT fb.ID, 'PENDING_FREEBIE_SPEND' as source,
				bg.NAME, bg.BACKGROUND_QUESTION, fb.SPECIALISATION as COMMENT,
				fb.PENDING_DETAIL, fb.LEVEL_TO as LEVEL
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND fb,
				" . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE
				fb.CHARACTER_ID = %s
				AND fb.ITEMTABLE_ID = bg.ID
				AND fb.ITEMTABLE = 'BACKGROUND'
				AND bg.BACKGROUND_QUESTION != ''
			)";
	$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
	$questions = $wpdb->get_results($sql);
	
	//echo "<p>SQL: $sql</p>\n";
	//print_r($questions);
	
	return $questions;
}
function vtm_validate_submit($settings, $characterID, $templateID, $usepost = 1) {

	if (isset($_POST['chargen-submit'])) {
		if ($_POST['status'] == 1)
			return array(1, "Character has been submitted", 1);
		else
			return array(0, "<LI>ERROR: Complete your character before submitting</li>", 0);
	} else {
		return array(1, "", 0);
	}
	
}
function vtm_save_submit($characterID, $templateID) {
	global $wpdb;
	global $current_user;
	
	$wpdb->show_errors();
	
	// Exit if we aren't actually submitting the character
	if (!isset($_POST['chargen-submit']) || !isset($_POST['status']) || $_POST['status'] != 1) {
		return $characterID;
	}

	// Update Character Generation Status
	$submittedid = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARGEN_STATUS WHERE NAME = 'Submitted'");
	
	$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER",
				array ('CHARGEN_STATUS_ID' => $submittedid),
				array ('ID' => $characterID)
			);
	
	// Send Email to storytellers
	if (!$result && $result !== 0) {
		echo "<p>ERROR: Submission of character failed. Contact the webadmin with your character name</p>\n";
	} else {
	
		$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_GENERATION",
				array ('NOTE_FROM_ST' => ''),
				array ('ID' => $characterID)
		);
	
		if (is_user_logged_in()) {
			get_currentuserinfo();
			$userid       = $current_user->ID;
		} else {
			$userid = 0;
		}
		
		$sql = "SELECT c.NAME as name, c.EMAIL as email, c.CONCEPT as concept,
					p.NAME as player, p.ID as playerID, c.PRIVATE_CLAN_ID as clanid
				FROM " . VTM_TABLE_PREFIX . "CHARACTER c,
					" . VTM_TABLE_PREFIX . "PLAYER p
				WHERE c.ID = %s
					AND c.PLAYER_ID = p.ID";
		$results = $wpdb->get_row($wpdb->prepare($sql, $characterID));
		
		$playerid = $results->playerID;
		$ref = vtm_get_chargen_reference($characterID);
		$tag    = get_option( 'vtm_chargen_emailtag' );
		$toname = get_option( 'vtm_chargen_email_from_name', 'The Storytellers');
		$toaddr = get_option( 'vtm_chargen_email_from_address', get_bloginfo('admin_email') );
		$fromname = stripslashes($results->player);
		$fromemail = $results->email;
		$character = stripslashes($results->name);
		$concept = $results->concept;
		$clan = vtm_get_clan_name($results->clanid);
		$url    = add_query_arg('reference', $ref, vtm_get_stlink_url('viewCharGen', true));
		
		$subject = "$tag Character Submitted";
		$headers[] = "From: \"$fromname\" <$fromemail>\n";
		
		$body = "Hello Storytellers,
		
A new character has been submitted:

	* Reference: $ref
	* Character Name: $character
	* Player: $fromname
	* Clan: $clan
	* Concept: 
	
" . stripslashes($concept) . "
	
You can view this character by following this link: $url";
	
		//echo "<pre>$body</pre>\n";

		$result = wp_mail($toaddr, $subject, $body, $headers);
		
		if (!$result)
			echo "<p>Failed to send email. Character Ref: $ref</p>\n";

	}

	return $characterID;
}
function vtm_validate_dummy($settings, $characterID, $templateID, $usepost = 1) {
	return array(1, "", 1);

}
function vtm_save_dummy($characterID, $templateID) {
	return $characterID;
}

function vtm_get_chargen_reference($characterID) {

	$cid = sprintf("%04d", $characterID);
	$pid = sprintf("%04d", vtm_get_player_id_from_characterID($characterID));
	$tid = sprintf("%02d", vtm_get_templateid($characterID));
	
	$login = vtm_get_chargenlogin($characterID);
	if (isset($login)) {
		//echo "<li>$login</li>\n";
		$bloguser = get_users('search=' . $login . '&number=1');
		//print_r($bloguser);
		$wpid = isset($bloguser[0]->ID) ? sprintf("%04d", $bloguser[0]->ID) : '0000';
	} else {
		$wpid = '0000';
	}
	
	$ref = "$cid/$pid/$tid/$wpid";
	//echo "<li>Reference: $ref</li>\n";
	return $ref;

}

function vtm_get_chargen_ritual_points($characterID, $settings, $items) {
	global $wpdb;
	
	foreach ($items as $ritual) {
	
		$key = sanitize_key($ritual->grp);
	
		if ($settings['rituals-method'] == 'none') {
			$points[$key] = 0;
		}
		elseif ($settings['rituals-method'] == 'point') {
			//echo "<li>point - {$settings['rituals-points']}</li>";
			$points[$key] = $settings['rituals-points'];
		}
		elseif ($settings['rituals-method'] == 'discipline') {
			//echo "<li>discipline - {$ritual->discipline_level}</li>";
			$points[$key] = $ritual->discipline_level;
		}
		else {
			//echo "<li>accumulate</li>";
			$points[$key] = 0;
			for ($i = $ritual->discipline_level ; $i >= 1 ; $i--)
				$points[$key] += $i;
		}
	}
	
	return $points;
}

function vtm_has_virtue_free_dot($selectedpath, $settings) {
	global $wpdb;

	if ($settings['virtues-free-dots'] == 'yes')
		$freedot = 1;
	elseif ($settings['virtues-free-dots'] == 'no')
		$freedot = 0;
	else {
		$humanityid = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . VTM_TABLE_PREFIX . "ROAD_OR_PATH WHERE NAME = %s", 'Humanity'));
		if ($humanityid == $selectedpath)
			$freedot = 1;
		else
			$freedot = 0;
	}

	return $freedot;
}

function vtm_calculate_generation($characterID) {
	global $wpdb;
	
	$options = vtm_getConfig();
	
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

	$results   = $wpdb->get_row($wpdb->prepare("SELECT ID, MAX_RATING, MAX_DISCIPLINE FROM " . VTM_TABLE_PREFIX . "GENERATION WHERE NAME = %s", $generation));

	$data = array(
		'ID' => $results->ID,
		'Gen' => $generation,
		'MaxDot' => $results->MAX_RATING,
		'MaxDisc' => $results->MAX_DISCIPLINE
	);
	
	//print "<li>Dot limit for {$generation}th generation is {$results->MAX_RATING}/{$results->MAX_DISCIPLINE}</li>";
	
	return $data;
	
}

function vtm_get_free_levels($table,$templateID) {
	global $wpdb;

	$duplicates = array();
	
	$sql = "SELECT item.NAME, item.ID, ctd.SPECIALISATION, ctd.LEVEL,
				ctd.ITEMTABLE, ctd.ITEMTABLE_ID,
				IFNULL(sector.ID,0) as SECTOR_ID, 
				IFNULL(sector.NAME,'') as SECTOR,
				ctd.MULTIPLE
			FROM 
				" . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_DEFAULTS ctd
				LEFT JOIN (
					SELECT ID, NAME
					FROM " . VTM_TABLE_PREFIX . "SECTOR
				) sector
				ON sector.ID = ctd.SECTOR_ID,
				" . VTM_TABLE_PREFIX . $table . " item
			WHERE 
				ctd.TEMPLATE_ID = %s 
				AND ctd.ITEMTABLE_ID = item.ID
				AND ctd.ITEMTABLE = %s";
	$sql = $wpdb->prepare($sql, $templateID, $table);
	$results = $wpdb->get_results($sql);
	
	//print_r($results);
	
	$out = array();
	$indexes = array();
	foreach ($results as $row) {
		$key = sanitize_key($row->NAME);
		if ($row->MULTIPLE == 'Y') {
			if (isset($indexes[$key])) {
				$indexes[$key]++;
			} else {
				$indexes[$key] = 1;
			}
			$out[$key . "_" . $indexes[$key]] = $row;
		} else {
			$out[$key] = $row;
		}
		
	}
	
	//print_r($out);
	// $checkdups = array();
	// foreach ($results as $row) {
		// $key = sanitize_key($row->NAME);
		// if (isset($duplicates[$key])) {
			// $duplicates[$key]++;
		// }
		// elseif (isset($checkdups[$key])) {
			// $duplicates[$key] = 1;
		// }
		
		// $checkdups[$key] = 1;
	// }
	
	// //print_r($checkdups);
	
	// // Final output - to have a unique key if multiple skills have been
	// // added with the same name. Can't use the MULTIPLE field from the database
	// // as BACKGROUNDs don't have that column
	// $out = array();
	// $indexes = array();
	// foreach ($results as $row) {
		// $key = sanitize_key($row->NAME);
		// if (isset($duplicates[$key])) {
			// if (isset($indexes[$key])) {
				// $indexes[$key]++;
			// } else {
				// $indexes[$key] = 1;
			// }
			// $out[$key . "_" . $indexes[$key]] = $row;
		// } else {
			// $out[$key] = $row;
		// }
		
	// }
	
	return $out;
}

?>