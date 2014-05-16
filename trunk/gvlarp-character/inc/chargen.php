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

function vtm_chargen_flow_steps($characterID, $templateID) {

	$xp = vtm_get_total_xp(0, $characterID);
	$questions = count(vtm_get_chargen_questions($characterID));
	$settings = vtm_get_chargen_settings($templateID);
	//print_r($settings);
	
	$buttons = array (
		array(	'title'      => "Basic Information", 
				'function'   => 'vtm_render_basic_info',
				'validate'   => 'vtm_validate_basic_info',
				'save'       => 'vtm_save_basic_info')
	);
	if ( ($settings['attributes-method'] == 'PST' && (
			$settings['attributes-tertiary'] > 0 || 
			$settings['attributes-secondary'] > 0 || 
			$settings['attributes-primary'] > 0)) ||
		 ($settings['attributes-method'] != 'PST' && $settings['attributes-points'] > 0)) {
		 
		array_push($buttons,array(	'title' => "Attributes", 
				'function'   => 'vtm_render_attributes',
				'validate'   => 'vtm_validate_attributes',
				'save'       => 'vtm_save_attributes'));
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
	
	array_push($buttons,array(
				'title'      => "Submit", 
				'function'   => 'vtm_render_chargen_submit',
				'validate'   => 'vtm_validate_submit',
				'save'       => 'vtm_save_submit'));

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
	//print_r($_POST);
	
	$characterID = vtm_get_chargen_characterID();
	$laststep    = isset($_POST['step']) ? $_POST['step'] : 0;
	$progress    = isset($_POST['progress']) ? $_POST['progress'] : array('0' => 1);
	$templateID  = vtm_get_templateid($characterID);
	
	if ($characterID == -1) {
		$output .= "<p>Invalid Reference</p>";
		$step = 0;
		$chargenstatus = '';
	} else {
		$step = vtm_get_step($characterID, $templateID);
		$sql = $wpdb->prepare("SELECT cgs.NAME FROM " . VTM_TABLE_PREFIX . "CHARACTER c, " . VTM_TABLE_PREFIX . "CHARGEN_STATUS cgs WHERE c.ID = %s AND c.CHARGEN_STATUS_ID = cgs.ID",$characterID);
		//echo "<p>SQL: $sql</p>";
		$chargenstatus = $wpdb->get_var($sql);
	}
	
	$output .= "<p>Character Generation Status: $chargenstatus</p>";
	$output .= "<form id='chargen_form' method='post'>";
	
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
	
	$output .= "<div id='chargen-main'>";
	
	// output form to be filled in
	//echo "<li>step: $step, function: {$flow[$step-1]['function']}</li>";
	if ($step == 0)
		$output .= vtm_render_choose_template();
	else
		$output .= call_user_func($flow[$step-1]['function'], $step, $characterID, $templateID, $chargenstatus);

	// 3 buttons: Back, Check & Next
	$output .= vtm_render_submit($step, count($flow), $chargenstatus);
	$output .= "</div></form>";
	
	return $output;
}

function vtm_render_submit($step, $finalstep, $chargenstatus) {

	$output = "";
	
	if ($step - 1 > 0)
		$output .= "<input type='submit' name='chargen-step[" . ($step - 1) . "]' class='button-chargen-step' value='< Step " . ($step - 1) . "' />";
	if ($step > 1 && $step < $finalstep && $chargenstatus != 'Submitted')
		$output .= "<input type='submit' name='chargen-step[" . $step . "]' class='button-chargen-step' value='Update' />";
	if ($step + 1 <= $finalstep)
		$output .= "<input type='submit' name='chargen-step[" . ($step + 1) . "]' class='button-chargen-step' value='Next >' />";
	elseif ($chargenstatus != 'Submitted')
		$output .= "<input type='submit' name='chargen-submit' class='button-chargen-step' value='Submit for Approval' />";

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

function vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp, $freebiecosts, $postvariable, $showzeros) {
	
	$fulldoturl    = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$doturl        = plugins_url( 'gvlarp-character/images/cg_selectdot.jpg' );

	$max2display = 5;
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
	//print_r($posted);

	$output = "";
	if (count($items) > 0) {
		$id = 0;
		$grp = "";
		$grpcount = 0;
		$col = 0;
		foreach ($items as $item) {
			$colspan = 2;
			
			$loop = (isset($item->MULTIPLE) && $item->MULTIPLE == 'Y') ? 4 : 1;
			
			for ($j = 1 ; $j <= $loop ; $j++) {
				
				$name = sanitize_key($item->NAME);
				$key = (isset($item->MULTIPLE) && $item->MULTIPLE == 'Y') ? $name . "_" . $j : $name;
			
				// Base level from main table in database
				$levelfrom = isset($saved[$key]->level_from) ? $saved[$key]->level_from : 0;

				// Over-ridden by freebie point spends saved
				$current = isset($pendingfb[$key]) ? $pendingfb[$key]->value : $levelfrom;
				// Over-ridden by freebie point spends submitted
				$current = $submitted ? (isset($posted[$key]) ? $posted[$key] : 0) : $current;
				
				// xp point spends saved
				$levelxp = isset($pendingxp[$key]) ? $pendingxp[$key]->value : 0;
				//echo "<li>$key: from: $levelfrom, current: $current, xp: $levelxp</li>";
				
				// Specialisation
				$specialisation = isset($pendingfb[$key]) ? $pendingfb[$key]->specialisation : '';
				// Pending Detail
				$detail = isset($pendingfb[$key]) ? $pendingfb[$key]->pending_detail : '';
				
				switch ($key) {
					case 'willpower': $max2display = 10; break;
				}
				
				if ($levelfrom > 0 || $showzeros) {
					// start column / new column
					if (isset($item->GROUPING)) {
						if ($grp != $item->GROUPING) {
							$grpcount++;
							if (empty($grp)) {
								$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->GROUPING}</th></tr>\n";
								$col++;
							} 
							elseif ($col == $columns) {
								$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->GROUPING}</th></tr>\n";
								$col = 1;
							}
							else {
								$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->GROUPING}</th></tr>\n";
								$col++;
							}
							$grp = $item->GROUPING;
						}
					}

					// Hidden fields
					$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
					$rowoutput .= "<input type='hidden' name='{$postvariable}_spec[" . $key . "]' value='$specialisation' />";
					$rowoutput .= "<input type='hidden' name='{$postvariable}_detail[" . $key . "]' value='$detail' />";
					$rowoutput .= "</td></tr>\n";
					
					if ($postvariable == 'freebie_merit') {
						$cost = $freebiecosts[$name][0][1];
						$cbid = "cb_{$key}_{$j}";
						$rowoutput .= "<tr><td><span>";
						$rowoutput .= "<input type='checkbox' name='{$postvariable}[" . $key . "]' id='$cbid' value='$cost' ";
						$rowoutput .= checked($current, $cost, false);
						$rowoutput .= "/>\n";
						$rowoutput .= "<label for='$cbid'>" . stripslashes($item->NAME) . " ($cost)</label>\n";
						$rowoutput .= "</span></td></tr>\n";
					
					} else {
						//dots row
						$flag = 0;
						$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->NAME) . "</span></th><td>\n";
						$rowoutput .= "<fieldset class='dotselect'>";
						for ($i=$max2display;$i>=1;$i--) {
							$radioid = "dot_{$key}_{$i}_{$j}";
							
							if ($levelfrom >= $i)
								// Base level from main table in database
								$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
							elseif (isset($pendingxp[$key]) && $pendingxp[$key]->value != 0) {
								// Lock if there are any xp spends for this item
								if ($current >= $i)
									$rowoutput .= "<img src='$doturl' alt='*' id='$radioid' />";
								elseif ($levelxp >= $i)
									$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
								else
									$rowoutput .= "<img src='$emptydoturl' alt='*' id='$radioid' />";
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
									$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
								}
							}
						}
						$radioid = "dot_{$key}_{$j}_clear";
						$rowoutput .= "<input type='radio' id='$radioid' name='{$postvariable}[{$key}]' value='0' ";
						$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
						$rowoutput .= "</fieldset></td></tr>\n";
						
						// Ensure that freebie spends don't get lost when an XP
						// spend has blocked the user from changing the level
						if (!$flag && $current > 0) {
						
							$rowoutput .= "<tr style='display:none'><td colspan=$colspan>\n";
							$rowoutput .= "<input type='hidden' name='{$postvariable}[{$key}]' value='$current' />";
							$rowoutput .= "</td></tr>\n";
						
						}
					}
				}
			}
		}
	
	}
	
	return $rowoutput;
}

function vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
	$pendingxp, $postvariable, $showzeros, $fbcosts = array()) {

	$rowoutput = "";
	$max2display = 5;
	$columns = 3;
	$fulldoturl = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$freebiedoturl = plugins_url( 'gvlarp-character/images/cg_freebiedot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	
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
		$loop = (isset($item->MULTIPLE) && $item->MULTIPLE == 'Y') ? 4 : 1;

		for ($j = 1 ; $j <= $loop ; $j++) {
			$name = sanitize_key($item->NAME);
			$key = (isset($item->MULTIPLE) && $item->MULTIPLE == 'Y') ? $name . "_" . $j : $name;
			switch ($key) {
				case 'willpower':   $max2display = 10; break;
				case 'conscience':  $max2display = 5; break;
				case 'conviction':  $max2display = 5; break;
				case 'selfcontrol': $max2display = 5; break;
				case 'courage':     $max2display = 5; break;
				case 'instinct':    $max2display = 5; break;
			}
			$colspan = 2;
				
			// Base level from main table in database
			$levelfrom = isset($saved[$key]->level_from) ? $saved[$key]->level_from : 0;

			// level from freebie point spends saved
			$levelfb = isset($pendingfb[$key]) ? $pendingfb[$key]->value : $levelfrom;

			// level from xp point spends saved
			$current = isset($posted[$key]) ? $posted[$key] : 
						(isset($pendingxp[$key]) ? $pendingxp[$key]->value : 0);
			
			// Specialisation
			$specialisation = isset($pendingxp[$key]) ? $pendingxp[$key]->specialisation : '';
			
			// Merit stuff
			$meritcost  = $postvariable == 'xp_merit' ? $xpcosts[$name][0][1] : 0;
			$meritlevel = $postvariable == 'xp_merit' ? $fbcosts[$name][0][1] : 0;
			
			//echo "<li>$key/$name - from: $levelfrom, fb: $levelfb, current: $current, spec: $specialisation</li>";
			if ($postvariable == 'xp_merit' && $meritcost > 0 && $meritlevel > 0)
				$dodisplay = 1;
			elseif ($postvariable != 'xp_merit' && ($levelfrom > 0 || $showzeros))
				$dodisplay = 1;
			else
				$dodisplay = 0;
			
			if ($dodisplay) {
				// start column / new column
				if (isset($item->GROUPING)) {
					if ($grp != $item->GROUPING) {
						$grpcount++;
						if (empty($grp)) {
							$rowoutput .= "<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->GROUPING}</th></tr>\n";
							$col++;
						} 
						elseif ($col == $columns) {
							$rowoutput .= "</table>\n</td></tr>\n<tr><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->GROUPING}</th></tr>\n";
							$col = 1;
						}
						else {
							$rowoutput .= "</table>\n</td><td class='gvxp_col'>\n<table>\n<tr><th colspan=$colspan>{$item->GROUPING}</th></tr>\n";
							$col++;
						}
						$grp = $item->GROUPING;
					}
				}
				
				// Hidden fields
				//$comment = isset()
				$rowoutput .= "<tr style='display:none'>
					<input type='hidden' name='{$postvariable}_comment[$key]' value='$specialisation' />
					<td colspan=$colspan>\n";
				$rowoutput .= "</td></tr>\n";

				if ($postvariable == 'xp_merit') {
					$cbid = "cb_{$j}_{$key}";
					$rowoutput .= "<tr><td><span>";
					$rowoutput .= "<input type='checkbox' name='{$postvariable}[" . $key . "]' id='$cbid' value='$meritlevel' ";
					if ($current) {
						$rowoutput .= checked($current, $current, false);
					}
					$rowoutput .= "/>\n";
					$rowoutput .= "<label for='$cbid'>" . stripslashes($item->NAME) . " ($meritlevel) - $meritcost XP</label>\n";
					$rowoutput .= "</span></td></tr>\n";
				} else {
					//dots row
					$rowoutput .= "<tr><th class='gvthleft'><span>" . stripslashes($item->NAME) . "</span></th><td>\n";
					$rowoutput .= "<fieldset class='dotselect'>";
					for ($i=$max2display;$i>=1;$i--) {
						$radioid = "dot_{$key}_{$i}_{$j}";
						
						if ($levelfrom >= $i)
							$rowoutput .= "<img src='$fulldoturl' alt='*' id='$radioid' />";
						elseif (isset($pendingfb[$key]) && $levelfb >= $i)
							$rowoutput .= "<img src='$freebiedoturl' alt='*' id='$radioid' />";
						elseif (isset($xpcosts[$name][$levelfrom][$i])) {
							$cost = $xpcosts[$name][$levelfrom][$i];
							$rowoutput .= "<input type='radio' id='$radioid' name='{$postvariable}[$key]' value='$i' ";
							$rowoutput .= checked($current, $i, false);
							$rowoutput .= " /><label for='$radioid' title='Level $i ($cost xp)'";
							$rowoutput .= ">&nbsp;</label>\n";
						}
						else {
							$rowoutput .= "<img src='$emptydoturl' alt='X' id='$radioid' />";
						}
					}
					$radioid = "dot_{$key}_{$j}_clear";
					$rowoutput .= "<input type='radio' id='$radioid' name='{$postvariable}[$key]' value='0' ";
					$rowoutput .= " /><label for='$radioid' title='Clear' class='cleardot'>&nbsp;</label>\n";
					$rowoutput .= "</fieldset></td></tr>\n";
				}
			}
		}
	}
	return $rowoutput;

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

function vtm_render_chargen_virtues($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$config    = vtm_getConfig();
	$settings  = vtm_get_chargen_settings($templateID);
	$items     = vtm_get_chargen_virtues($characterID);
	$pendingfb = vtm_get_pending_freebies('STAT', $characterID);  // name => value
	$pendingxp = vtm_get_pending_freebies('STAT', $characterID);  // name => value
	
	$output .= "<h3>Step $step: Virtues</h3>";
	$output .= "<p>You have {$settings['virtues-points']} dots to spend on your virtues.</p>";
	
	// read initial values
	$sql = "SELECT stats.NAME, cstat.STAT_ID, cstat.LEVEL
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
	} else {
		$selectedpath = $wpdb->get_var($wpdb->prepare("SELECT ROAD_OR_PATH_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	}
	$output .= "<p><label>Path of Enlightenment:</label><select name='path' autocomplete='off'>\n";
	foreach (vtm_get_chargen_roads() as $path) {
		$output .= "<option value='{$path->ID}' " . selected($path->ID, $selectedpath, false) . ">" . stripslashes($path->NAME) . "</option>";
	}
	$output .= "</select></p>\n";

	$statkey1 = vtm_get_virtue_statkey(1, $selectedpath);
	$statkey2 = vtm_get_virtue_statkey(2, $selectedpath);

	$pathitems = array (
		$statkey1 => $items[$statkey1],
		$statkey2 => $items[$statkey2],
		'courage' => $items['courage']
	);
	
	$output .= vtm_render_chargen_section($saved, false, 0, 0, 0, 
		1, $pathitems, $virtues, $pendingfb, $pendingxp, 'Virtues', 'virtue_value');
	
	return $output;
}

function vtm_render_chargen_freebies($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	
	// Work out how much points are currently available
	$points = $settings['freebies-points'];
	$spent = vtm_get_freebies_spent($characterID);
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
	$sectioncontent['stat']  = vtm_render_freebie_stats($characterID);
	$sectioncontent['skill'] = vtm_render_freebie_skills($characterID);
	$sectioncontent['disc']  = vtm_render_freebie_disciplines($characterID);
	$sectioncontent['path']  = vtm_render_freebie_paths($characterID);
	$sectioncontent['background'] = vtm_render_freebie_backgrounds($characterID);
	$sectioncontent['merit'] = vtm_render_freebie_merits($characterID, $pendingSpends, $points);
	
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
	$spent = vtm_get_chargen_xp_spent($characterID);
	// points = total overall - all pending + just pending on this character
	$points = vtm_get_total_xp(0, $characterID) - vtm_get_pending_xp(0, $characterID) + $spent;
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
						'merit'      => "Merits",
					);
	$sectionorder   = array('stat', 'skill', 'disc', 'path', 'merit');
	
	$pendingSpends = array();
	$sectioncontent['stat']  = vtm_render_chargen_xp_stats($characterID);
	$sectioncontent['skill'] = vtm_render_chargen_xp_skills($characterID);
	$sectioncontent['disc']  = vtm_render_xp_disciplines($characterID);
	$sectioncontent['path']  = vtm_render_chargen_xp_paths($characterID);
	$sectioncontent['merit'] = vtm_render_chargen_xp_merits($characterID);
	
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
	$sql = "SELECT cs.LEVEL
			FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT cs
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
	if ($pathrating > 0) {
		$output .= "<tr><td>$pathname:</td><td>$pathrating";
		$output .= "<input type='hidden' name='pathrating' value='$pathrating' />";
		$output .= "</td></tr>";
	} 
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
					<td><input type='text' name='comment[]' value='$spec' />";
		//$spec_output .= "{$item['updatetable']} / {$item['tableid']}";
		$spec_output .= "</td></tr>\n";
					
		$i++;
	}
	if ($spec_output != '') {
		$output .= "<h4>Specialities</h4>\n";
		$output .= "<p>Please enter specialities for the indicated Attributes and Abilities and provide
					a note on what any Merits and Flaws refer to.</p>
					
					<p>An example speciality for Stamina is 'tough'. An example note for the Merit 'Acute Sense'
					might be 'sight' and for 'Clan Friendship' might be 'Ventrue'</p>";
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
function vtm_render_chargen_extbackgrounds($step, $characterID, $templateID) {

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$options  = vtm_getConfig();
	
	$output .= "<h3>Step $step: History and Extended Backgrounds</h3>";
	$output .= "<p>Please fill in more information on your character.</p>";
	
	// Merits
	$questions = vtm_get_chargen_merit_questions($characterID);
	$posted    = isset($_POST['meritquestion']) ? $_POST['meritquestion'] : array();
	foreach ($questions as $question) {
		$id = $question->ID;
		$title = $question->NAME;
		if (!empty($question->SPECIALISATION)) $title .= " - " . $question->SPECIALISATION;
		$title .= " (" . $question->VALUE . ")";
		
		$text = isset($posted[$id]) ? $posted[$id] : (isset($question->PENDING_DETAIL) ? $question->PENDING_DETAIL : '');
		
		$output .= "<h4>$title</h4><p>{$question->BACKGROUND_QUESTION}</p>";
		$output .= "<input type='hidden' name='meritquestion_title[$id]' value='" . htmlspecialchars($title, ENT_QUOTES) . "' \>";
		$output .= "<p><textarea name='meritquestion[$id]' rows='4' cols='80'>$text</textarea></p>";
	}

	// Backgrounds
	$questions = vtm_get_chargen_background_questions($characterID);
	$posted    = isset($_POST['bgquestion']) ? $_POST['bgquestion'] : array();
	foreach ($questions as $question) {
		$id    = $question->ID;
		$title = $question->NAME . " " . $question->LEVEL;
		$text = isset($posted[$id]) ? $posted[$id] : (isset($question->PENDING_DETAIL) ? $question->PENDING_DETAIL : '');

		if (!empty($question->COMMENT)) $title .= " (" . $question->COMMENT . ")";		
		
		$output .= "<h4>$title</h4><p>{$question->BACKGROUND_QUESTION}</p>";
		$output .= "<input type='hidden' name='bgquestion_title[$id]' value='" . htmlspecialchars($title, ENT_QUOTES) . "' \>";
		$output .= "<input type='hidden' name='bgquestion_source[$id]' value='" . htmlspecialchars($question->source, ENT_QUOTES) . "' \>";
		$output .= "<p><textarea name='bgquestion[$id]' rows='4' cols='80'>$text</textarea></p>";
		
	}

	// Extended
	$questions = vtm_get_chargen_questions($characterID);
	$posted    = isset($_POST['question']) ? $_POST['question'] : array();
		
	foreach ($questions as $question) {
		$id = $question->questID;
		$text = isset($posted[$id]) ? $posted[$id] : (isset($question->PENDING_DETAIL) ? $question->PENDING_DETAIL : '');
	
		$output .= "<h4>{$question->TITLE}</h4><p>{$question->BACKGROUND_QUESTION}</p>";
		$output .= "<input type='hidden' name='question_title[$id]' value='{$question->TITLE}' \>";
		$output .= "<p><textarea name='question[$id]' rows='4' cols='80'>$text</textarea></p>";
	}
	
	return $output;
}
function vtm_render_chargen_submit($step, $characterID, $templateID) {

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$options  = vtm_getConfig();
	
	$output .= "<h3>Step $step: Summary and Submit</h3>";
	$output .= "<p>Below is a summary of the character generation status.</p>";
	
	// Not suitable to use _POST as it is only updated if all steps have been
	// gone through this session
	$flow = vtm_chargen_flow_steps($characterID, $templateID);
	foreach ($flow as $flowstep) {
		$progress[] = call_user_func($flowstep['validate'], $settings, $characterID, 0);
	}
	
	$output .= "<p><table>\n";
	$index = 0;
	$done = 0;
	foreach ($progress as $result) {
		if ($index < (count($progress) - 1)) {
			$output .= "<tr>";
			if ($result[2]) $status = "Complete";
			elseif ($result[0]) $status = "In progress";
			else $status = "Error";
			
			if ($flow[$index]['title'] == 'Spend Experience' && $status != "Error") $status = "N/A";
			
			if ($status == "Error") $errinfo = "({$result[1]})"; else $errinfo = "";
			If ($status == "Complete" || $status == "N/A") $done++;
			
			$output .= "<td>Step " . ($index +1) .": {$flow[$index]['title']}</td>";
			$output .= "<td>$status</td>";
			$output .= "</tr>";
			}
		$index++;
	}
	
	$output .= "</table></p>\n";
	
	$alldone = 0;
	if ($done == (count($progress) - 1)) {
		$alldone = 1;
		$output .= "<p><strong>Your character is ready to submit!</strong></p>";
	}
	$output .= "<input type='hidden' name='status' value='$alldone' />";
	
	$link = vtm_get_stlink_url('printCharSheet');
	$link = add_query_arg('characterID', $characterID, $link);
	$output .= "<p>Click to <a href='$link' alt='Print Character'>Print your character</a></p>";
	
	return $output;
}
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

function vtm_render_chargen_backgrounds($step, $characterID, $templateID) {
	global $wpdb;

	$output = "";
	$settings = vtm_get_chargen_settings($templateID);
	$items    = vtm_get_chargen_backgrounds($characterID);
	$pending  = vtm_get_pending_freebies('BACKGROUND', $characterID);  // name => value
	
	$output .= "<h3>Step $step: Backgrounds</h3>";
	$output .= "<p>You have {$settings['backgrounds-points']} dots to spend on your Backgrounds</p>";
	
	// read initial values
	$sql = "SELECT bg.NAME, cbg.BACKGROUND_ID, cbg.LEVEL 
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND cbg,
				" . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE 
				bg.ID = cbg.BACKGROUND_ID
				AND CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$saved = vtm_sanitize_array($wpdb->get_results($sql, OBJECT_K)); 

	$backgrounds = isset($_POST['background_value']) ? $_POST['background_value'] : array();

	$output .= vtm_render_chargen_section($saved, false, 0, 0, 0, 
		0, $items, $backgrounds, $pending, array(), 'Backgrounds', 'background_value');
	
	return $output;
} 
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
	$flow = vtm_chargen_flow_steps($characterID, $templateID);
	
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
	
	$flow = vtm_chargen_flow_steps($characterID, $templateID);
	if ($laststep != 0) {
		//echo "<li>laststep: $laststep, function: {$flow[$laststep-1]['save']}</li>";
		$characterID = call_user_func($flow[$laststep-1]['save'], $characterID, $templateID);
	}

	return $characterID;
}

function vtm_save_attributes($characterID) {
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
	
	$current['STAT']       = vtm_get_current_stats($characterID);
	$current['SKILL']      = vtm_get_current_skills($characterID);
	$current['DISCIPLINE'] = vtm_get_current_disciplines($characterID);
	$current['BACKGROUND'] = vtm_get_current_backgrounds($characterID);
	$current['MERIT']      = vtm_get_current_merits($characterID);
	$current['PATH']       = vtm_get_current_paths($characterID);
			
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
	
	//print_r($freebiecosts);
	
	foreach ($bought as $type => $items) {
		foreach ($items as $key => $levelto) {
			$levelfrom   = isset($current[$type][$key]->level_from)  ? $current[$type][$key]->level_from  : 0;
			
			if ($levelto != 0) {
				$itemname = $key;
				if (isset($current[$type][$key]->name)) {
					$name = $current[$type][$key]->name;
				} else {
					$key  = preg_replace("/_\d+$/", "", $key);
					$name = $current[$type][$key]->name;
				}
							
				$chartableid = isset($current[$type][$key]->chartableid) ? $current[$type][$key]->chartableid : 0;
				if ($type == 'MERIT')
					$amount = isset($freebiecosts[$type][$key][0][1]) ? $freebiecosts[$type][$key][0][1] : 0;
				else
					$amount = isset($freebiecosts[$type][$key][$levelfrom][$levelto]) ? $freebiecosts[$type][$key][$levelfrom][$levelto] : 0;
				$itemid      = $current[$type][$key]->itemid;
				$spec        = isset($specialisation[$type][$itemname]) && $specialisation[$type][$itemname] != '' ?
								$specialisation[$type][$itemname] : 
								(isset($current[$type][$key]->specialisation) ? $current[$type][$key]->specialisation : '');
				$detail     = isset($pending_detail[$type][$itemname]) ? $pending_detail[$type][$itemname] : '';
				//echo "<li>itemname: $itemname, key: $key, levelto: $levelto, from: $levelfrom</li>";
				
				if ($levelto > $levelfrom || $type == 'MERIT') {
					$data = array (
						'CHARACTER_ID'   => $characterID,
						'CHARTABLE'      => 'CHARACTER_' . $type,
						'CHARTABLE_ID'   => $chartableid,
						'LEVEL_FROM'     => $levelfrom,
						
						'LEVEL_TO'       => $levelto,
						'AMOUNT'         => $amount,
						'ITEMTABLE'      => $type,
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
						echo "<p style='color:red'><b>Error:</b> $name could not be inserted</p>";
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
	//echo "<p>SQL: $sql</p>";
	//print_r($saved);
	
	// Save Ext Background questions
	if (isset($_POST['question'])) {
		foreach ($_POST['question'] as $index => $text) {
		
			$data = array (
				'CHARACTER_ID'  	=> $characterID,
				'QUESTION_ID'		=> $index,
				'APPROVED_DETAIL'	=> '',
				'PENDING_DETAIL'	=> $text,
				'DENIED_DETAIL'		=> '',
			);
			//print_r($data);
			
			if (isset($saved[$index])) {
				//echo "<li>Updating id {$saved[$index]->id} for question $index</li>";
				$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND",
					$data,
					array ('ID' => $saved[$index]->id)
				);
			} else {
				//echo "<li>Adding question $index</li>";
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
				'PENDING_DETAIL'	=> $text
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
				'PENDING_DETAIL'	=> $text
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
		'CHARGEN_NOTE_TO_ST'  => $_POST['noteforST'],
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
			//if ($result) 			echo "<p style='color:green'>Updated $name speciality with $comment</p>";
			//else if ($result === 0) echo "<p style='color:orange'>No updates made to $name speciality</p>";
			//else {
			//	$wpdb->print_error();
			//	echo "<p style='color:red'>Could not update $name speciality</p>";
			//}
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

	$items['STAT']       = vtm_sanitize_array(vtm_get_chargen_stats($characterID, OBJECT_K));
	$items['SKILL']      = vtm_sanitize_array(vtm_get_chargen_abilities($characterID, 1, OBJECT_K));
	$items['DISCIPLINE'] = vtm_sanitize_array(vtm_get_chargen_disciplines($characterID, OBJECT_K));
	$items['MERIT']      = vtm_sanitize_array(vtm_get_chargen_merits($characterID, OBJECT_K));
	$items['PATH']       = vtm_sanitize_array(vtm_get_chargen_paths($characterID, OBJECT_K));
	
	//print_r($freebies['SKILL']);
	
	foreach ($bought as $type => $row) {
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
				//print_r($freebies[$type][$itemname]);

				$levelfrom   = isset($current[$type][$key]->level_from)  ? $current[$type][$key]->level_from  : 0;
				$levelfrom   = isset($freebies[$type][$itemname]->value) ? $freebies[$type][$itemname]->value : $levelfrom;
				$amount      = ($type == 'MERIT') ? $xpcosts[$type][$key][0][1] : $xpcosts[$type][$key][$levelfrom][$value];
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
				
				if ($value > $levelfrom || $type == 'MERIT') {
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
					///echo "<pre>";
					///print_r($data);
					///echo "</pre>";
					
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

function vtm_save_abilities($characterID) {
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
	
	$sql = "SELECT disc.NAME, cdisc.DISCIPLINE_ID, cdisc.ID, cdisc.LEVEL
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
					//echo "<li>Updated $key at $value</li>";
					// update
					$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE",
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
			//echo "<li>Delete $id ($sql)</li>";
			$wpdb->get_results($sql);
		}
	}
	
	return $characterID;

}

function vtm_save_backgrounds($characterID) {
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

	//print_r($new);
	//print_r($current);

	foreach ($backgrounds as $background) {
		$key     = sanitize_key($background->NAME);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		
		if ($value > 0) {
			$comment = isset($saved[$key]->COMMENT) ? $saved[$key]->COMMENT : '';
			
			$data = array(
				'CHARACTER_ID'  => $characterID,
				'BACKGROUND_ID' => $background->ID,
				'LEVEL'         => $value,
				'COMMENT'       => $comment
			);
			if (isset($saved[$key])) {
				if ($saved[$key]->LEVEL != $value) {
					//echo "<li>Updated $key at $value</li>";
					// update
					$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND",
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
			//echo "<li>Deleted $id</li>";
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
		'LEVEL'        => $new['courage']
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
		$key     = sanitize_key($attribute->NAME);
		$value   = isset($new[$key]) ? $new[$key] : 0;
		
		if ($key == $statkey1 || $key == $statkey2 || $key == 'courage') {
			$data = array(
				'CHARACTER_ID' => $characterID,
				'STAT_ID'      => $attribute->ID,
				'LEVEL'        => $value
			);
			if (isset($saved[$key])) {
				//echo "<li>Updated $key at $value</li>";
				// update
				$wpdb->update(VTM_TABLE_PREFIX . "CHARACTER_STAT",
					$data,
					array (
						'ID' => $saved[$key]->ID
					)
				);
			} 
			else {
				//echo "<li>Added $key at $value</li>";
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
			//echo "<li>Delete $id ($sql)</li>";
			$wpdb->get_results($sql);
		}
	}

	return $characterID;
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
	
	//echo "<pre>$userbody</pre>";
	
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
			
	$sql = "SELECT NAME, ID, DESCRIPTION, GROUPING, SPECIALISATION_AT
			FROM " . VTM_TABLE_PREFIX . "STAT
			WHERE
				GROUPING = 'Virtue'
			ORDER BY ORDERING";
	//echo "<p>SQL: $sql</p>";
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
	
	
	$sql = "SELECT bg.NAME, bg.ID, bg.DESCRIPTION, bg.GROUPING
			FROM " . VTM_TABLE_PREFIX . "BACKGROUND bg
			WHERE
				bg.VISIBLE = 'Y'
			ORDER BY GROUPING, NAME";

	$results = $wpdb->get_results($sql, $output_type);
	
	return $results;

}

function vtm_get_chargen_merits($characterID = 0, $output_type = OBJECT) {
	global $wpdb;
	
	
	$sql = "SELECT item.NAME, item.ID, item.DESCRIPTION, item.GROUPING, item.MULTIPLE
			FROM " . VTM_TABLE_PREFIX . "MERIT item
			WHERE
				item.VISIBLE = 'Y'
			ORDER BY GROUPING, VALUE DESC, NAME";
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
			ORDER BY GROUPING, NAME";
	$sql = $wpdb->prepare($sql, $characterID);
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
	
	$sql = "SELECT NAME, ID, DESCRIPTION, GROUPING, SPECIALISATION_AT, MULTIPLE,
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

function vtm_render_freebie_stats($characterID) {
	global $wpdb;
	
	$output      = "";
	// COSTS OF STATS - if entry doesn't exist then you can't buy it
	//	$cost['<statname>'] = array( '<from>' => array( '<to>' => <cost>))
	$freebiecosts = vtm_get_freebie_costs('STAT');

	// display stats to buy
	$items = vtm_get_chargen_stats($characterID);
	
	// Current stats saved into db
	$saved = vtm_get_current_stats($characterID);
	
	// Current freebies saved into database
	$pendingfb = vtm_get_pending_freebies('STAT', $characterID);

	// Current bought with XP
	$pendingxp  = vtm_get_pending_chargen_xp('STAT', $characterID);  // name => value
		
	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp,
			$freebiecosts, 'freebie_stat', 0);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}

function vtm_render_freebie_skills($characterID) {
	global $wpdb;
	
	$output      = "";

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

	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp,
			$freebiecosts, 'freebie_skill', 1);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}

function vtm_render_freebie_disciplines($characterID) {	
	$output      = "";

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
			$freebiecosts, 'freebie_discipline', 1);
	
	//print_r($freebiecosts);
	
	/*
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
	*/
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}

function vtm_render_xp_disciplines($characterID) {
	global $wpdb;
	
	$output  = "";

	$xpcosts   = vtm_get_chargen_xp_costs('DISCIPLINE', $characterID);
	$items     = vtm_get_chargen_disciplines($characterID);
	$saved     = vtm_get_current_disciplines($characterID);
	$pendingfb = vtm_get_pending_freebies('DISCIPLINE', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('DISCIPLINE', $characterID);
	
	//print_r($xpcosts);
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, $pendingxp, 'xp_discipline', 1);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}

function vtm_render_freebie_paths($characterID) {
	
	$output      = "";

	$freebiecosts = vtm_get_freebie_costs('PATH', $characterID);
	$items     = vtm_get_chargen_paths($characterID);
	$saved     = vtm_get_current_paths($characterID);
	$pendingfb = vtm_get_pending_freebies('PATH', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('PATH', $characterID);

	//print_r($currentpending);
	
	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, $pendingxp,
			$freebiecosts, 'freebie_path', 1);

	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

} 

function vtm_render_freebie_backgrounds($characterID) {
	global $wpdb;
	
	$output      = "";
	$max2display = 5;
	$columns     = 3;
	$fulldoturl  = plugins_url( 'gvlarp-character/images/cg_freedot.jpg' );
	$emptydoturl = plugins_url( 'gvlarp-character/images/cg_emptydot.jpg' );
	$dotstobuy   = 0;

	$freebiecosts = vtm_get_freebie_costs('BACKGROUND', $characterID);
	$items = vtm_get_chargen_backgrounds($characterID);
	$saved = vtm_get_current_backgrounds($characterID);
	$pendingfb = vtm_get_pending_freebies('BACKGROUND', $characterID);
		
	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, array(),
			$freebiecosts, 'freebie_background', 1);

	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}

function vtm_render_freebie_merits($characterID) {
	global $wpdb;
	
	$output      = "";

	$freebiecosts = vtm_get_freebie_costs('MERIT', $characterID);
	$items     = vtm_get_chargen_merits($characterID);
	$saved     = vtm_get_current_merits($characterID);
	$pendingfb = vtm_get_pending_freebies('MERIT', $characterID);

	$rowoutput = vtm_render_freebie_section($items, $saved, $pendingfb, array(),
			$freebiecosts, 'freebie_merit', 1);
	
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
		//print_r($data);
		//echo "<pre>";
		//print_r($clancost);
		//echo "</pre>";
		
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
					$from = $data[$i]['CURRENT_VALUE'];
					$to   = $data[$i]['NEXT_VALUE'];
					$cost = 0;
					
					while ($from != $to && $to <= 10) {
						if ($data[$from]['FREEBIE_COST'] != 0) {
							$cost += $data[$from]['FREEBIE_COST'];
							$outdata[$key][$i][$to] = $cost;
						}
						$from = $to;
						$to   = $data[$from]['NEXT_VALUE'];
						
						//echo "<li>name:{$item->NAME}, key: $key, i: $i, from: $from, to: $to</li>";
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
			$key = sanitize_key($item->NAME);
			$outdata[$key][0][1] = $item->XP_COST;
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
					$from = $data[$i]['CURRENT_VALUE'];
					$to   = $data[$i]['NEXT_VALUE'];
					$cost = 0;
					
					while ($from != $to && $to <= 10) {
						if ($data[$from]['XP_COST'] != 0) {
							$cost += $data[$from]['XP_COST'];
							$outdata[$key][$i][$to] = $cost;
						}
						$from = $to;
						$to   = $data[$from]['NEXT_VALUE'];
						
						//echo "<li>name:{$item->NAME}, i: $i, from: $from, to: $to</li>";
					}
				
				}
			} else {
				echo "<li>ERROR: Issue with cost model for {$key}. Please ask the admin to check and resave the cost model</li>";
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
	
	//echo "<p>SQL: $sql</p>";
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
	
	//echo "<p>SQL: $sql</p>";
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
	
	//echo "<p>SQL: $sql</p>";
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
	
	//echo "<p>SQL: $sql</p>";
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
	
	//echo "<p>SQL: $sql</p>";
	//print_r($items);
	
	return $items;
}

function vtm_get_freebies_spent($characterID) {
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
		
		$current['STAT']       = vtm_get_current_stats($characterID);
		$current['SKILL']      = vtm_get_current_skills($characterID);
		$current['DISCIPLINE'] = vtm_get_current_disciplines($characterID);
		$current['BACKGROUND'] = vtm_get_current_backgrounds($characterID);
		$current['MERIT']      = vtm_get_current_merits($characterID);
		$current['PATH']       = vtm_get_current_paths($characterID);
				
		$bought['STAT']       = isset($_POST['freebie_stat']) ? $_POST['freebie_stat'] : array();
		$bought['SKILL']      = isset($_POST['freebie_skill']) ? $_POST['freebie_skill'] : array();
		$bought['DISCIPLINE'] = isset($_POST['freebie_discipline']) ? $_POST['freebie_discipline'] : array();
		$bought['BACKGROUND'] = isset($_POST['freebie_background']) ? $_POST['freebie_background'] : array();
		$bought['MERIT']      = isset($_POST['freebie_merit']) ? $_POST['freebie_merit'] : array();
		$bought['PATH']       = isset($_POST['freebie_path']) ? $_POST['freebie_path'] : array();
		
		foreach ($bought as $type => $items) {
			foreach ($items as $key => $levelto) {
				$levelfrom = isset($current[$type][$key]->level_from) ? $current[$type][$key]->level_from : 0;
				$actualkey = preg_replace("/_\d+$/", "", $key);
			
				//echo "<li>Cost of $key ($actualkey) in $type from $levelfrom to $levelto </li>";

				if ($type == 'MERIT') {
					if (!isset($current[$type][$key])) {
						if (isset($current[$type][$actualkey]->multiple) && $current[$type][$actualkey]->multiple == 'Y') {
							$spent += isset($freebiecosts[$type][$actualkey][0][1]) ? $freebiecosts[$type][$actualkey][0][1] : 0;
							//echo "<li>Running total is $spent. Bought $actualkey ({$freebiecosts[$type][$actualkey][0][1]})</li>";
						}
					} else {
						$spent += isset($freebiecosts[$type][$key][0][1]) ? $freebiecosts[$type][$key][0][1] : 0;
						//echo "<li>Running total is $spent. Bought $key ({$freebiecosts[$type][$key][0][1]})</li>";
					}
				}
				elseif (!isset($current[$type][$key])) {
					//echo "$key becomes $actualkey<br />";
					if (isset($current[$type][$actualkey]->multiple) && $current[$type][$actualkey]->multiple == 'Y') {
						//echo "$name - from: {$current[$actualkey]->level_from}, to: {$levelto}, cost: {$freebiecosts[$actualname][$current[$actualname]->level_from][$level_to]}<br />";
						$spent += isset($freebiecosts[$type][$actualkey][$current[$type][$actualkey]->level_from][$levelto]) ? $freebiecosts[$type][$actualkey][$current[$type][$actualkey]->level_from][$levelto] : 0;
						//echo "<li>Running total is $spent. Bought $actualkey to $levelto ({$freebiecosts[$type][$actualkey][$current[$actualkey]->level_from][$levelto]})</li>";
					}
				} else {
					$spent += isset($freebiecosts[$type][$key][$levelfrom][$levelto]) ? $freebiecosts[$type][$key][$levelfrom][$levelto] : 0;
					//echo "<li>Running total is $spent. Bought $key to $levelto ({$freebiecosts[$type][$key][$levelfrom][$levelto]})</li>";
				}
			}
		
		}
		
		/*

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
		*/
	} else {
		$sql = "SELECT SUM(AMOUNT) FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
				WHERE CHARACTER_ID = %s";
		$sql = $wpdb->prepare($sql, $characterID);
		$spent = $wpdb->get_var($sql);
	}

	return $spent;
}

function vtm_get_chargen_xp_spent($characterID) {
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

		foreach ($bought as $type => $items) {
			foreach ($items as $key => $level_to) {
			
				$levelfrom = isset($current[$type][$key]->level_from) ? $current[$type][$key]->level_from : 0;
				$levelfrom = isset($pendingfb[$type][$key]->value) ? $pendingfb[$type][$key]->value : $levelfrom;
				
				if ($level_to != 0) {
					$actualkey = preg_replace("/_\d+$/", "", $key);
					
					if ($type == 'MERIT') {
						if (!isset($current[$type][$key])) {
							if (isset($current[$type][$actualkey]->multiple) && $current[$type][$actualkey]->multiple == 'Y') {
								$spent += isset($xpcosts[$type][$actualkey][0][1]) ? $xpcosts[$type][$actualkey][0][1] : 0;
								//echo "<li>$key / $actualkey, cost: {$xpcosts[$type][$actualkey][0][1]}</li>";
							}
						} else {
							//echo "<li>$key - from:$levelfrom, to:$level_to, cost: {$xpcosts[$type][$key][0][1]}</li>";
							$spent += isset($xpcosts[$type][$key][0][1]) ? $xpcosts[$type][$key][0][1] : 0;
						}
					} else {
						$spent += isset($xpcosts[$type][$actualkey][$levelfrom][$level_to]) ? $xpcosts[$type][$actualkey][$levelfrom][$level_to] : 0;
						//echo "<li>$key - $type, from $levelfrom to $level_to, cost: {$xpcosts[$type][$actualkey][$levelfrom][$level_to]}, running total: $spent</li>";
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
	/*
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
	*/
	//echo "<li>spent on $table, $postvariable: $spent</li>";
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

function vtm_render_chargen_xp_stats($characterID) {
	$output = "";

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
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, $pendingxp, 'xp_stat', 0);

	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";
	
	return $output;
}

function vtm_render_chargen_xp_paths($characterID) {
	$output = "";

	$xpcosts   = vtm_get_chargen_xp_costs('PATH', $characterID);
	$items     = vtm_get_chargen_paths($characterID);
	$saved     = vtm_get_current_paths($characterID);
	$pendingfb = vtm_get_pending_freebies('PATH', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('PATH', $characterID);
	//print_r($current_path);
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, 
		$pendingxp, 'xp_path', 1);
	
	/*
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
	*/
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";
	
	return $output;
}

function vtm_render_chargen_xp_skills($characterID) {
	global $wpdb;
	
	$output = "";

	// Get costs
	$xpcosts   = vtm_get_chargen_xp_costs('SKILL', $characterID);
	$items     = vtm_get_chargen_abilities($characterID, 1);
	$saved     = vtm_get_current_skills($characterID);
	$pendingfb = vtm_get_pending_freebies('SKILL', $characterID);
	$pendingxp = vtm_get_pending_chargen_xp('SKILL', $characterID);
	
	$rowoutput = vtm_render_chargen_xp_section($items, $saved, $xpcosts, $pendingfb, $pendingxp, 'xp_skill', 1);
	
	if ($rowoutput != "")
		$output .= "<table>$rowoutput</table></td></tr></table>\n";

	return $output;

}

function vtm_render_chargen_xp_merits($characterID) {
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
		$pendingxp, 'xp_merit', 1, $fbcosts);
	
	if ($rowoutput != "")
		$output .= "<table id='merit_xp_table'>$rowoutput</table></td></tr></table>\n";

	return $output;

}

function vtm_validate_basic_info($settings, $characterID, $usepost = 1) {
	global $current_user;
	global $wpdb;

	$ok = 1;
	$errormessages = "";
	$complete = 1;

	// VALIDATE BASIC INFO
	//		- error: character name is not blank
	//		- error: new player? player name is not duplicated
	//		- error: old player? player name is found
	//		- error: login name doesn't already exist (except if it's the currently logged in acct)
	//		- error: email address is not blank and looks valid
	//		- error: concept is not blank
	
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
		//echo "<p>SQL: $sql</p>";
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
	$playername     = $usepost ? (isset($_POST['player'])       ? $_POST['player']       : '') : $dbplayer;
	$playeridguess  = $usepost ? (isset($_POST['playerID'])     ? $_POST['playerID']      : -1) : $dbplayerID;
	$postnewplayer  = $usepost ? (isset($_POST['newplayer'])    ? $_POST['newplayer']    : 'off') : $dbnewplayer;
	$login          = $usepost ? (isset($_POST['wordpress_id']) ? $_POST['wordpress_id'] : '') : $dbwordpressID;
	$email          = $usepost ? (isset($_POST['email'])        ? $_POST['email']        : '') : $dbemail;
	$postconcept    = $usepost ? (isset($_POST['concept'])      ? $_POST['concept']      : '') : $dbconcept;
	$postclanid     = $usepost ? (isset($_POST['priv_clan'])    ? $_POST['priv_clan']    : 0) : $dbclanid;
		
	if (empty($postcharacter)) {
		$errormessages .= "<li>ERROR: Please enter a character name</li>";
		$ok = 0;
		$complete = 0;
	}
	
	if (empty($playername)) {
		$errormessages .= "<li>ERROR: Please enter a player name</li>";
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
					$errormessages .= "<li>ERROR: Could not find a player with the name '$playername'. Did you mean '" . vtm_get_player_name($playerid) . "'?</li>";
				}
				else
					$errormessages .= "<li>ERROR: Could not find a player with the name '$playername'. Are you a new player?</li>";
			}
		} else {
			// new player
			if (isset($playerid)) {
				$ok = 0;
				$complete = 0;
				$errormessages .= "<li>ERROR: A player already exists with the name '$playername'. Are you a returning player?</li>";
			}
		}
	}
	
	if (empty($login)) {
		get_currentuserinfo();
		if (username_exists( $login ) && $login != $current_user->user_login) {
			$ok = 0;
			$complete = 0;
			$errormessages .= "<li>ERROR: An account already exists with the login name '$login'. Please choose another.</li>";
		}
		elseif (!validate_username( $login )) {
			$ok = 0;
			$complete = 0;
			$errormessages .= "<li>ERROR: Login name '$login' is invalid. Please choose another.</li>";
		}
	}
	
	if (empty($email)) {
			$ok = 0;
			$complete = 0;
			$errormessages .= "<li>ERROR: Email address is missing.</li>";
	} else {
		if (!is_email($email)) {
			$ok = 0;
			$complete = 0;
			$errormessages .= "<li>ERROR: Email address '$email' does not seem to be a valid email address.</li>";
		}
	}
	
	if (empty($postconcept)) {
		$errormessages .= "<li>ERROR: Please enter your character concept.</li>";
		$ok = 0;
		$complete = 0;
	}
	
	$currentclanid = $wpdb->get_var($wpdb->prepare("SELECT PRIVATE_CLAN_ID FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	$discspends = count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND 
						WHERE CHARACTER_ID = %s AND (ITEMTABLE='DISCIPLINE' OR ITEMTABLE = 'PATH')", $characterID)));
	$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND 
						WHERE CHARACTER_ID = %s AND (ITEMTABLE='DISCIPLINE' OR (ITEMTABLE = 'PATH')", $characterID)));
	$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE 
						WHERE CHARACTER_ID = %s", $characterID)));
	$discspends += count($wpdb->get_var($wpdb->prepare("SELECT ID 
						FROM " . VTM_TABLE_PREFIX . "CHARACTER_PATH 
						WHERE CHARACTER_ID = %s", $characterID)));

	if ($currentclanid != $postclanid && $postclanid != 0 && $discspends > 0) {
		$errormessages .= "<li>WARNING: All spends on Disciplines will be deleted due to the change in Clan</li>";
	}
						
	return array($ok, $errormessages, $complete);
}

function vtm_validate_abilities($settings, $characterID, $usepost = 1) {

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	// VALIDATE ABILITIES
	// P/S/T
	//		- ERROR: P / S / T options only picked once
	//		- WARN/ERROR: correct number of points spent in each group
	// 		- ERROR: check that nothing is over the max
	
	if (!$usepost) {
		$dbvalues = array();
		$dbgroups = array();
		$dball = array();
		$skills = vtm_get_current_skills($characterID);
		$grp = "";
		foreach ($skills as $skill) {
			if ($skill->multiple == 'Y') {
				$key = sanitize_key($skill->name) . "_" . $skill->chartableid;
			} else {
				$key = sanitize_key($skill->name);
			}
			$dbvalues[$key] = $skill->level_from;
			
			if ($grp != $skill->grp && $skill->level_from > 0) {
				$dbgroups[] = sanitize_key($skill->grp);
				$grp =  $skill->grp;
			}
		}
		// guess sections
		$grouptotals = array();
		foreach  ($skills as $item) {
			$key = sanitize_key($item->name);
			$grp = sanitize_key($item->grp);
			if ($item->level_from > 0) {
				if (isset($grouptotals[$grp]))
					$grouptotals[$grp] += $item->level_from;
				else
					$grouptotals[$grp] = $item->level_from;
			}
		}
		//print_r($grouptotals);
		$dball = array();
		foreach ($grouptotals as $grp => $total) {
			switch($total) {
				case $settings['abilities-primary']  : $dball[$grp] = 1;break;
				case $settings['abilities-secondary']: $dball[$grp] = 2;break;
				case $settings['abilities-tertiary'] : $dball[$grp] = 3;break;
				default: $dball[$grp] = -1;
			}
		}
		//print_r($dball);
	}
	
	$postvalues = $usepost ? 
				(isset($_POST['ability_value']) ? $_POST['ability_value'] : array()) :
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
		$abilities = vtm_get_chargen_abilities();
		$target = array($settings['abilities-primary'], $settings['abilities-secondary'], $settings['abilities-tertiary']);
		$check = 0;
		
		foreach ($groups as $group) {
			$sectiontype = $postall[$group];
			if ($sectiontype == -1) {
				$errormessages .= "<li>ERROR: You have not selected if $group is Primary, Secondary or Tertiary</li>";
				$ok = 0;
				$complete = 0;
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
							$complete = 0;
						}
					}
				}
				//echo "<p>group $group: target = " . $target[$sectiontype-1] . ", total = $sectiontotal</li>";
				if ($sectiontotal > $target[$sectiontype-1]) {
					$errormessages .= "<li>ERROR: You have spent too many dots in $group</li>";
					$ok = 0;
					$complete = 0;
				}
				elseif ($sectiontotal < $target[$sectiontype-1])  {
					$errormessages .= "<li>WARNING: You haven't spent enough dots in $group</li>";
					$complete = 0;
				}
			}
		}
		if ($ok && $check != 6) {
			$errormessages .= "<li>ERROR: Check that you have chosen Primary, Secondary and Tertiary once only for each type of Ability</li>";
			$ok = 0;
			$complete = 0;
		}
			
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		$complete = 0;
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_attributes($settings, $characterID, $usepost = 1) {

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
	if (!$usepost) {
		$dbvalues = array();
		$dbgroups = array();
		$dball = array();
		
		$items = vtm_get_current_stats($characterID);
		$grp = "";
		foreach ($items as $item) {
			$key = sanitize_key($item->name);
			if ($item->grp == 'Physical' || $item->grp == 'Mental' || $item->grp == 'Social') {
				$dbvalues[$key] = $item->level_from;
				
				if ($grp != $item->grp) {
					$dbgroups[] = sanitize_key($item->grp);
					$grp =  $item->grp;
				}
			}
		}
		$grouptotals = array();
		foreach  ($items as $item) {
			$key = sanitize_key($item->name);
			$grp = sanitize_key($item->grp);
			if ($item->level_from > 0) {
				if (isset($grouptotals[$grp]))
					$grouptotals[$grp] += $item->level_from - 1;
				else
					$grouptotals[$grp] = $item->level_from - 1;
			}
		}
		//print_r($grouptotals);
		$dball = array();
		foreach ($grouptotals as $grp => $total) {
			switch($total) {
				case $settings['attributes-primary']  : $dball[$grp] = 1;break;
				case $settings['attributes-secondary']: $dball[$grp] = 2;break;
				case $settings['attributes-tertiary'] : $dball[$grp] = 3;break;
				default: $dball[$grp] = -1;
			}
		}
		//print_r($dball);
		
	}
	
	$postvalues = $usepost ? 
				(isset($_POST['attribute_value']) ? $_POST['attribute_value'] : array()) :
				$dbvalues;
	$postgroups = $usepost ? 
				(isset($_POST['group']) ? $_POST['group'] : array()) :
				$dbgroups;
	$postall    = $usepost ? 
				(isset($_POST) ? $_POST : array()) :
				$dball;
	
	// VALIDATE ATTRIBUTES
	// P/S/T
	//		- ERROR: P / S / T options only picked once
	//		- WARN/ERROR: correct number of points spent in each group
	// Point Spent
	//		- WARN/ERROR: point total correct
	if (count($postvalues) > 0) {
		$values = $postvalues;
		
		if ($settings['attributes-method'] == 'PST') {
						
			$groups = $postgroups;
			$attributes = vtm_get_chargen_attributes();
			$target = array($settings['attributes-primary'], $settings['attributes-secondary'], $settings['attributes-tertiary']);
			$check = 0;
			
			foreach ($groups as $group) {
				$sectiontype = $postall[$group];
				if ($sectiontype == -1) {
					$errormessages .= "<li>ERROR: You have not selected if $group is Primary, Secondary or Tertiary</li>";
					$ok = 0;
					$complete = 0;
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
						$complete = 0;
						$ok = 0;
					}
					elseif ($sectiontotal < $target[$sectiontype-1])  {
						$errormessages .= "<li>WARNING: You haven't spent enough dots in $group</li>";
						$complete = 0;
					}
				}
			}
			if ($ok && $check != 6) {
				$errormessages .= "<li>ERROR: Check that you have chosen Primary, Secondary and Tertiary once only for each type of Attribute</li>";
				$ok = 0;
				$complete = 0;
			}
			
			
		} else {
			$target = $settings['attributes-points'];
			$total = 0;
			foreach ($values as $att => $val)
				$total += $val - 1;
			
			if ($total > $target) {
				$errormessages .= "<li>ERROR: You have spent too many points</li>";
				$ok = 0;
				$complete = 0;
			}
			elseif ($total < $target)  {
				$errormessages .= "<li>WARNING: You haven't spent enough points</li>";
				$complete = 0;
			}
		}
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		$complete = 0;
	}
	return array($ok, $errormessages, $complete);
}

function vtm_validate_disciplines($settings, $characterID, $usepost = 1) {

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
			$errormessages .= "<li>ERROR: You have spent too many dots</li>";
			$ok = 0;
			$complete = 0;
		}
		elseif ($total < $settings['disciplines-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
			$complete = 0;
		}
			
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		$complete = 0;
	}

	
	return array($ok, $errormessages, $complete);
}

function vtm_validate_backgrounds($settings, $characterID, $usepost = 1) {
	global $wpdb;

	$ok = 1;
	$errormessages = "";
	$complete = 1;
	
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
		//echo "<p>SQL: $sql</p>";
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
		foreach  ($values as $id => $val) {
			$total += $val;
		}
		
		if ($total > $settings['backgrounds-points']) {
			$errormessages .= "<li>ERROR: You have spent too many dots</li>";
			$ok = 0;
			$complete = 0;
		}
		elseif ($total < $settings['backgrounds-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
			$complete = 0;
		}
							
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		$complete = 0;
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_virtues($settings, $characterID, $usepost = 1) {
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
			$total += $val - 1;
			
			if ($key != $statkey1 && $key != $statkey2 && $key != 'courage') {
				$statfail = 1;
			}
			
		}
		
		if ($total > $settings['virtues-points']) {
			$errormessages .= "<li>ERROR: You have spent too many dots</li>";
			$ok = 0;
			$complete = 0;
		}
		elseif ($total < $settings['virtues-points'])  {
			$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
			$complete = 0;
		}
		if ($statfail) {
			$errormessages .= "<li>ERROR: Please update Virtues for the selected path</li>";
			$ok = 0;
			$complete = 0;
		}
		
							
	} else {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		$complete = 0;
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_freebies($settings, $characterID, $usepost = 1) {

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
			$errormessages .= "<li>ERROR: You have bought too many points of Merits</li>";
			$ok = 0;
			$complete = 0;
		}
		if ($settings['flaws-max'] > 0 && $flawsgained > $settings['flaws-max']) {
			$errormessages .= "<li>ERROR: You have gained too many points from Flaws</li>";
			$ok = 0;
			$complete = 0;
		}
	}
	
	$points = $settings['freebies-points'];
	
	$spent = 0;
	
	$spent += vtm_get_freebies_spent($characterID);
	
	if ($spent == 0) {
		$errormessages .= "<li>WARNING: You have not spent any dots</li>";
		$complete = 0;
	}
	elseif ($spent > $points) {
		$errormessages .= "<li>ERROR: You have spent too many dots ($spent / $points)</li>";
		$ok = 0;
		$complete = 0;
	}
	elseif ($spent < $points) {
		$errormessages .= "<li>WARNING: You haven't spent enough dots</li>";
		$complete = 0;
	}
	
	if (count($postpath) > 0) {
		$pathinfo = vtm_get_current_paths($characterID);
		$bought = $postpath;
		foreach ($bought as $path => $level) {
			$disciplinekey = sanitize_key($pathinfo[$path]->grp);
			$max = isset($postdisc[$disciplinekey]) ? $postdisc[$disciplinekey] : $pathinfo[$path]->maximum;
		
			if ($level > $max) {
				$errormessages .= "<li>ERROR: The level in " . stripslashes($pathinfo[$path]->name) . " cannot be greater than the {$pathinfo[$path]->grp} rating</li>";
				$ok = 0;
				$complete = 0;
			}
		}
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_finishing($settings, $characterID, $usepost = 1) {
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
		$dbday_dob   = isset($_POST['day_dob'])   ? $_POST['day_dob']   : (isset($dob) ? strftime("%d", strtotime($dob)) : '');
		$dbmonth_dob = isset($_POST['month_dob']) ? $_POST['month_dob'] : (isset($dob) ? strftime("%m", strtotime($dob)) : '');
		
		$doe = $wpdb->get_var($wpdb->prepare("SELECT DATE_OF_EMBRACE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
		$dbday_doe   = isset($_POST['day_doe'])   ? $_POST['day_doe']   : (isset($doe) ? strftime("%d", strtotime($doe)) : '');
		$dbmonth_doe = isset($_POST['month_doe']) ? $_POST['month_doe'] : (isset($doe) ? strftime("%m", strtotime($doe)) : '');
		
		$dbsire = $wpdb->get_var($wpdb->prepare("SELECT SIRE FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s", $characterID));
	}
	
	$postvalues = $usepost ? 
				(isset($_POST['itemname']) ? $_POST['itemname'] : array()) :
				$dbvalues;
	$postcomments = $usepost ? 
				(isset($_POST['comment']) ? $_POST['comment'] : array()) :
				$dbcomments;
	$postsire      = $usepost ? (isset($_POST['sire']) ? $_POST['sire'] : '') : $dbsire;
	$postday_dob   = $usepost ? (isset($_POST['day_dob']) ? $_POST['day_dob'] : '') : $dbday_dob;
	$postmonth_dob = $usepost ? (isset($_POST['month_dob']) ? $_POST['month_dob'] : '') : $dbmonth_dob;
	$postday_doe   = $usepost ? (isset($_POST['day_doe']) ? $_POST['day_doe'] : '') : $dbday_doe;
	$postmonth_doe = $usepost ? (isset($_POST['month_doe']) ? $_POST['month_doe'] : '') : $dbmonth_doe;

	// All specialities are entered
	// Sire name is entered
	// Dates are not the default dates
	
	if (count($postvalues) > 0) {
		foreach ($postvalues as $index => $name) {
			if (!isset($postcomments[$index]) || $postcomments[$index] == '') {
				$errormessages .= "<li>WARNING: Please specify a speciality for $name</li>";
				$complete = 0;
			}
		}
	}
	if ($postsire == '') {
		$errormessages .= "<li>ERROR: Please enter the name of your sire, or enter 'unknown' if your character does not know.</li>";
		$complete = 0;
}
	if ($postday_dob == 0 || $postmonth_dob == 0) {
		$errormessages .= "<li>ERROR: Please enter your character's Date of Birth.</li>";
		$ok = 0;
		$complete = 0;
	}
	if ($postday_doe == 0 || $postmonth_doe == 0) {
		$errormessages .= "<li>ERROR: Please enter your character's Date of Embrace.</li>";
		$ok = 0;
		$complete = 0;
	}

	return array($ok, $errormessages, $complete);
}

function vtm_validate_history($settings, $characterID, $usepost = 1) {
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
				$errormessages .= "<li>WARNING: Please fill in the '{$posttitles[$index]}' question.</li>";
				$complete = 0;
			}
		}
	} elseif (count($dbquestions) > 0) {
		$errormessages .= "<li>WARNING: Please fill in the background questions.</li>";
		$complete = 0;
	}
	if (count($meritpostvalues) > 0) {
		foreach ($meritpostvalues as $index => $text) {
			if (!isset($meritpostvalues[$index]) || $meritpostvalues[$index] == '') {
				$errormessages .= "<li>WARNING: Please fill in the '{$meritposttitles[$index]}' Merit/Flaw question.</li>";
				$complete = 0;
			}
		}
	} elseif (count($meritquestions) > 0) {
		$errormessages .= "<li>WARNING: Please fill in the Merit/Flaw questions.</li>";
		$complete = 0;
	}
	if (count($bgpostvalues) > 0) {
		foreach ($bgpostvalues as $index => $text) {
			if (!isset($bgpostvalues[$index]) || $bgpostvalues[$index] == '') {
				$errormessages .= "<li>WARNING: Please fill in the '{$bgposttitles[$index]}' Background question.</li>";
				$complete = 0;
			}
		}
	} elseif (count($bgquestions) > 0) {
		$errormessages .= "<li>WARNING: Please fill in the Background questions.</li>";
		$complete = 0;
	}
	return array($ok, $errormessages, $complete);
}

function vtm_validate_xp($settings, $characterID, $usepost = 1) {

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
	$spent += vtm_get_chargen_xp_spent($characterID);
	
	if ($spent > $points) {
		$errormessages .= "<li>ERROR: You have spent too many dots</li>";
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
				$errormessages .= "<li>ERROR: The level $level in " . stripslashes($pathinfo[$path]->name) . " cannot be greater than the {$pathinfo[$path]->grp} rating of $max</li>";
				$ok = 0;
				$complete = 0;
			}
		}
	}

	return array($ok, $errormessages, $complete);
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
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				stat.SPECIALISATION_AT 		as specialisation_at,
				stat.ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "STAT stat,
				" . VTM_TABLE_PREFIX . "CHARACTER_STAT cs
				LEFT JOIN (
					SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s
						AND CHARTABLE = 'CHARACTER_STAT'
				) pendingxp
				ON 
					pendingxp.CHARTABLE_ID = cs.ID
				LEFT JOIN (
					SELECT ID, LEVEL_TO, CHARTABLE_ID, SPECIALISATION
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
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				skill.SPECIALISATION_AT 	as specialisation_at,
				CASE skill.GROUPING WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "SKILL skill,
				" . VTM_TABLE_PREFIX . "CHARACTER_SKILL cs
				LEFT JOIN (
					SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s
						AND CHARTABLE = 'CHARACTER_SKILL'
				) pendingxp
				ON 
					pendingxp.CHARTABLE_ID = cs.ID
				LEFT JOIN (
					SELECT ID, LEVEL_TO, CHARTABLE_ID, SPECIALISATION
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
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
				skill.SPECIALISATION_AT 	as specialisation_at,
				CASE skill.GROUPING WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ORDERING
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
					LEFT JOIN (
						SELECT ID, CHARTABLE_ID, CHARTABLE_LEVEL, SPECIALISATION
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
				pendingxp.CHARTABLE_LEVEL 	as xplevel,
				pendingxp.ID 				as xpid,
				pendingxp.SPECIALISATION 	as xpspec,
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
	
	//echo "<p>SQL: $sql</p>";
	
	$results = array_merge($results1, $results2, $results3);
	
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
	
	// MERITS
	$sql = "SELECT
				'MERIT'					as type,
				'Merits and Flaws'		as typename,
				merit.NAME 				as itemname, 
				merit.GROUPING 			as grp, 
				merit.VALUE 			as level,
				0					    as id,
				''				        as spec,
				pendingfreebie.ID 		as freebieid,
				pendingfreebie.SPECIALISATION as freebiespec,
				pendingxp.ID 			as xpid,
				pendingxp.SPECIALISATION as xpspec,
				merit.HAS_SPECIALISATION as has_specialisation
			FROM
				" . VTM_TABLE_PREFIX . "MERIT merit,
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND pendingfreebie
				LEFT JOIN (
					SELECT ID, CHARTABLE_LEVEL, ITEMTABLE_ID, SPECIALISATION
					FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
					WHERE CHARACTER_ID = %s
						AND ITEMTABLE = 'MERIT'
				) pendingxp
				ON 
					pendingxp.ITEMTABLE_ID = pendingfreebie.ITEMTABLE_ID
			WHERE
				merit.ID = pendingfreebie.ITEMTABLE_ID
				AND pendingfreebie.ITEMTABLE = 'MERIT'
				AND pendingfreebie.CHARACTER_ID = %s
			ORDER BY
				merit.VALUE DESC, merit.NAME";
	$sql = $wpdb->prepare($sql, $characterID, $characterID);
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql);
	
	//print_r($results);
	
	foreach ($results as $row) {
		if ($row->has_specialisation == 'Y' && (isset($row->id) || isset($row->freebieid) || isset($row->xpid))) {
			if (isset($row->xpid)) {
				$updatetable = 'PENDING_XP_SPEND';
				$tableid     = $row->xpid;
				$comment     = $row->xpspec;
			}
			elseif (isset($row->freebieid)) {
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
					'level'   => $row->level,
					'grp'     => $row->grp,
					'comment' => $comment,
					'spec_at' => 1));
		}
	}
			
	
	//echo "<p>SQL: $sql</p>";
	//echo "<pre>";
	//print_r($specialities);
	//echo "</pre>";
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
			
	/* $content = "<p>SQL: $sql</p>"; */
	
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
	
	//echo "<p>SQL: $sql</p>";
	//print_r($questions);
	
	return $questions;
}
function vtm_validate_submit($settings, $characterID, $usepost = 1) {

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
	
	// Update Character Generation Status
	$submittedid = $wpdb->get_var("SELECT ID FROM " . VTM_TABLE_PREFIX . "CHARGEN_STATUS WHERE NAME = 'Submitted'");
	
	$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARACTER",
				array ('CHARGEN_STATUS_ID' => $submittedid),
				array ('ID' => $characterID)
			);
	
	// Send Email to storytellers
	if (!$result && $result !== 0) {
		echo "<p>ERROR: Submission of character failed. Contact the webadmin with your character name</p>";
	} else {
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
		$ref    = $characterID . '-' . $userid . '-' . $playerid;
		$tag    = get_option( 'vtm_chargen_emailtag' );
		$toname = get_option( 'vtm_chargen_email_from_name', 'The Storytellers');
		$toaddr = get_option( 'vtm_chargen_email_from_address', get_bloginfo('admin_email') );
		$fromname = $results->player;
		$fromemail = $results->email;
		$character = $results->name;
		$concept = $results->concept;
		$clan = vtm_get_clan_name($results->clanid);
		$url    = add_query_arg('reference', $ref, vtm_get_stlink_url('viewCharGen', true));
		
		$subject = "$tag Character Submitted";
		$headers[] = "From: \"$fromname\" <$fromemail>";
		
		$body = "Hello Storytellers,
		
A new character has been submitted:

	* Reference: $ref
	* Character Name: $character
	* Player: $fromname
	* Clan: $clan
	* Concept: 
	
" . stripslashes($concept) . "
	
You can view this character by following this link: $url";
	
		//echo "<pre>$body</pre>";

		$result = wp_mail($toaddr, $subject, $body, $headers);
		
		if (!$result)
			echo "<p>Failed to send email. Character Ref: $ref</p>";

	}

	return $characterID;
}
function vtm_validate_dummy($settings, $characterID, $usepost = 1) {
	return array(1, "Dummy Validation OK", 1);

}
function vtm_save_dummy($characterID, $templateID) {
	return $characterID;
}


?>