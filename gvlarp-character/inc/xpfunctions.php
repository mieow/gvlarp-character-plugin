<?php

function gv_xp_spend_content_filter($content) {

  if (is_page(get_stlink_page('viewXPSpend')) && is_user_logged_in()) {
    $content .= print_xp_spend_table();
  }
  // otherwise returns the database content
  return $content;
}
add_filter( 'the_content', 'gv_xp_spend_content_filter' );

/*
	Called by print_xp_spend_table
*/
function doPendingXPSpend($character) {
	global $wpdb;
	$characterID = establishCharacterID($character);
	$playerID    = establishPlayerID($character);
		
	/* Stats */
	if (isset($_REQUEST['stat_level'])) {
		$newid = save_to_pending('stat', 'CHARACTER_STAT', 'STAT', 'STAT_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['skill_level'])) {
		$newid = save_to_pending('skill', 'CHARACTER_SKILL', 'SKILL', 'SKILL_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['newskill_level'])) {
		$newid = save_to_pending('newskill', 'CHARACTER_SKILL', 'SKILL', 'SKILL_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['disc_level'])) {
		$newid = save_to_pending('disc', 'CHARACTER_DISCIPLINE', 'DISCIPLINE', 'DISCIPLINE_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['newdisc_level'])) {
		$newid = save_to_pending('newdisc', 'CHARACTER_DISCIPLINE', 'DISCIPLINE', 'DISCIPLINE_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['path_level'])) {
		$newid = save_to_pending('path', 'CHARACTER_PATH', 'PATH', 'PATH_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['newpath_level'])) {
		$newid = save_to_pending('newpath', 'CHARACTER_PATH', 'PATH', 'PATH_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['ritual_level'])) {
		$newid = save_to_pending('ritual', 'CHARACTER_RITUAL', 'RITUAL', 'RITUAL_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['merit_level'])) {
		$newid = save_merit_to_pending('merit', 'CHARACTER_MERIT', 'MERIT', 'MERIT_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['newmerit_level'])) {
		$newid = save_merit_to_pending('newmerit', 'CHARACTER_MERIT', 'MERIT', 'MERIT_ID', $playerID, $characterID);
	}
}
	
/*
	master_xp_update GVLARP_FORM
*/
function handleMasterXP() {
	$counter = 1;
	while (isset($_POST['counter_' . $counter])) {
		$current_player_id = $_POST['counter_' . $counter];
		$current_xp_value  = $_POST[$current_player_id . '_xp_value'];
		if (is_numeric($current_xp_value) && ((int) $current_xp_value != 0)) {
			addPlayerXP($current_player_id,
				$_POST[$current_player_id . '_character'],
				$_POST[$current_player_id . '_xp_reason'],
				$current_xp_value,
				$_POST[$current_player_id . '_xp_comment']);
		}
		$counter++;
	}
}
	
/* Add XP to the database
	- called by handleMasterXP
	- and handleGVLarpForm
 */
function addPlayerXP($player, $character, $xpReason, $value, $comment) {
	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	$sql = "INSERT INTO " . $table_prefix . "PLAYER_XP (player_id, amount, character_id, xp_reason_id, comment, awarded)
					VALUES (%d, %d, %d, %d, %s, SYSDATE())";
	$wpdb->query($wpdb->prepare($sql, $player, ((int) $value), $character, $xpReason, $comment));
	
	touch_last_updated($character);
}

/* shortcode */

function print_xp_spend_table() {
	global $wpdb;
	
	$character   = establishCharacter($character);
	$characterID = establishCharacterID($character);
	
	$output = "";
	$step = $_REQUEST['step'];
		
	// Cancel Spends
	if (isset($_REQUEST['stat_cancel']))     cancel_pending($_REQUEST['stat_cancel']);
	
	// Back button
	if (isset($_REQUEST['xCancel'])) $step = "";
	
	/* VALIDATE SPENDS */
	switch ($step) {
		case 'supply_details':
			$outputError .= validate_spends($characterID);
			if (!empty($outputError)) {
				$output .= "<div class='gvxp_error'>$outputError</div>";
				$step = "";
			}
			break;
		case 'submit_spend':
			$outputError .= validate_details($characterID);
			if (!empty($outputError)) {
				$output .= "<div class='gvxp_error'>$outputError</div>";
				$step = "supply_details";
			}
			break;
	}
	switch ($step) {
		case 'supply_details':
			$output .= render_supply_details($character);
			break;
		case 'submit_spend':
			doPendingXPSpend($character);
		default:
			$output .= render_select_spends($character);
			break;
	
	}

	return $output;
}

function render_supply_details($character) {

	$output = "";
	$character   = establishCharacter($character);
	$characterID = establishCharacterID($character);

	$spent = 0;
	if (isset($_REQUEST['stat_level'])) {
		$spent += calc_submitted_spend('stat');
	}
	
	$output .= "<p>Spending $spent experience points.</p>\n";
	$output .= "<p>Please enter specialisations, if available, and describe how you are learning the selected items</p>";
	
	$output .= "<div class='gvplugin' id=\"gvid_xpst\">\n";
	$output .= "<form name=\"SPEND_XP_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">\n";
	
	if (isset($_REQUEST['stat_level'])) {
		$output .= render_stat_details();
	}
	$output .= "<input class='gvxp_submit' type='submit' name=\"xSubmit\" value=\"Spend XP\">\n";
	$output .= "<input class='gvxp_submit' type='submit' name=\"xCancel\" value=\"Back\">\n";

	if ($_POST['GVLARP_CHARACTER'] != "")
		$output .= "<input type='HIDDEN' name=\"GVLARP_CHARACTER\" value=\"" . $_POST['GVLARP_CHARACTER'] . "\" />\n";
	$output .= "<input type='HIDDEN' name=\"character\" value=\"" . $character . "\">\n";
	$output .= "<input type='HIDDEN' name=\"step\" value=\"submit_spend\">\n";
	$output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"applyXPSpend\" />\n";
	$output .= "</form></div>\n";
	
	return $output;
}

function render_select_spends($character) {

	$character   = establishCharacter($character);
	$characterID = establishCharacterID($character);
	
	$xp_total      = get_total_xp($characterID);
	$xp_pending    = get_pending_xp($characterID);
	$fulldoturl    = plugins_url( 'gvlarp-character/images/viewfulldot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/viewemptydot.jpg' );
	$pendingdoturl = plugins_url( 'gvlarp-character/images/pendingdot.jpg' );
	
	$sectioncontent = array();
	$sectionheading = array();
	$sectiontitle   = array(
						'stat' => "Attributes",
					);
	$sectioncols    = array();
	$sectionorder   = array('stat', 'skill', 'newskill', 'disc', 'newdisc', 'path', 'newpath',
							'ritual', 'merit', 'newmerit');

	$output = "<p class='gvxp_xpstatus'>You have $xp_total experience in total with $xp_pending points currently pending</p>";

	/* work out the maximum ratings for this character based on generation */
	$ratings = get_character_maximums($characterID);
	$maxRating = $ratings[0];
	$maxDiscipline = $ratings[1];
	
	/* get the current pending spends for this character */
	$pendingSpends = get_pending($characterID);
	
	/* Attributes/Stats */
	$sectioncontent['stat'] = render_stats($characterID, $maxRating, $pendingSpends);
	
	
	/* DISPLAY TABLES 
	-------------------------------*/
	$output .= "<div class='gvplugin' id=\"gvid_xpst\">\n";
	$output .= "<form name=\"SPEND_XP_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">\n";


	$jumpto = array();
	$i = 0;
	foreach ($sectionorder as $section) {
		if ($sectiontitle[$section] && $sectioncontent[$section]) {
			$jumpto[$i++] .= "<a href='#gvid_xpst_$section' class='gvxp_jump'>" . $sectiontitle[$section] . "</a>";
		}
	}
	$outputJump = "<p>Jump to section: " . implode(" | ", $jumpto) . "</p>";
	
	foreach ($sectionorder as $section) {
	
		if ($sectioncontent[$section]) {
			$output .= "<h4 class='gvxp_head' id='gvid_xpst_$section'>" . $sectiontitle[$section] . "</h4>\n";
			$output .= "$outputJump\n";
			$output .= $sectionheading[$section];
			$output .= $sectioncontent[$section];
			$output .= "<input class='gvxp_submit' type='submit' name=\"xSubmit\" value=\"Spend XP\">\n";
		} 
		
	}


	if ($_POST['GVLARP_CHARACTER'] != "") {
		$output .= "<input type='HIDDEN' name=\"GVLARP_CHARACTER\" value=\"" . $_POST['GVLARP_CHARACTER'] . "\" />\n";
	}

	if ($_POST['GVLARP_CHARACTER'] != "")
		$output .= "<input type='HIDDEN' name=\"GVLARP_CHARACTER\" value=\"" . $_POST['GVLARP_CHARACTER'] . "\" />\n";
	$output .= "<input type='HIDDEN' name=\"character\" value=\"" . $character . "\">\n";
	$output .= "<input type='HIDDEN' name=\"step\" value=\"supply_details\">\n";
	$output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"applyXPSpend\" />\n";
	$output .= "</form></div>\n";
	
	return $output;
	
}

function get_xp_cost($dbdata, $current, $new) {

	$cost = 0;
	
	$selected = $current;
	$row = 0;
	while ($selected < $new && $row < count($dbdata)) {
		if ($selected == $dbdata[$row]->CURRENT_VALUE) {
			if ($dbdata[$row]->CURRENT_VALUE == $dbdata[$row]->NEXT_VALUE ||
				$dbdata[$row]->XP_COST == 0 ||
				$dbdata[$row]->NEXT_VALUE > $new) {
				$cost = 0;
				break;
			} else {
				$cost += $dbdata[$row]->XP_COST;
				$selected = $dbdata[$row]->NEXT_VALUE;
			}
		}
		$row++;
	}
	
	return $cost;
}
function get_pending($characterID) {
	global $wpdb;

	$sql = "SELECT * FROM " . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID, $table);
	
	$result = $wpdb->get_results($sql);

	return $result;
}
function get_xp_costs_per_level($table, $tableid, $level) {
	global $wpdb;

	$sql = "SELECT steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.XP_COST
		FROM
			" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
			" . GVLARP_TABLE_PREFIX . "COST_MODEL models,
			" . GVLARP_TABLE_PREFIX . $table . " mytable
		WHERE
			steps.COST_MODEL_ID = models.ID
			AND mytable.COST_MODEL_ID = models.ID
			AND mytable.ID = %s
			AND steps.NEXT_VALUE > %s
		ORDER BY steps.CURRENT_VALUE ASC";

	$sql = $wpdb->prepare($sql, $tableid, $level);
	
	return $wpdb->get_results($sql);

}
function get_discipline_xp_costs_per_level($disciplineid, $level, $clanid) {
	global $wpdb;

	/* clan cost model */
	$clansql = "SELECT steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.XP_COST
				FROM 
					" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
					" . GVLARP_TABLE_PREFIX . "COST_MODEL cmodels,
					" . GVLARP_TABLE_PREFIX . "CLAN cclans,
					" . GVLARP_TABLE_PREFIX . "CLAN_DISCIPLINE cclandisciplines,
					" . GVLARP_TABLE_PREFIX . "DISCIPLINE cdisciplines
				WHERE
					cclans.CLAN_COST_MODEL_ID = cmodels.ID
					AND steps.COST_MODEL_ID = cmodels.ID
					AND cclans.ID = %s	
					AND cclans.ID = cclandisciplines.CLAN_ID
					AND cdisciplines.ID = cclandisciplines.DISCIPLINE_ID
					AND cdisciplines.ID = %s
					AND steps.NEXT_VALUE > %s";
	$clansql = $wpdb->prepare($clansql, $clanid, $disciplineid, $level);
	$result = $wpdb->get_results($clansql);
	/* echo "<pre>\nSQL2: $clansql\n";
	print_r($result);
	echo "</pre>"; */
	
	/* non-clan cost model */
	if (!$result) {
		$nonsql = "SELECT steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.XP_COST
					FROM 
						" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
						" . GVLARP_TABLE_PREFIX . "COST_MODEL ncmodels,
						" . GVLARP_TABLE_PREFIX . "CLAN ncclans
					WHERE
						ncclans.NONCLAN_COST_MODEL_ID = ncmodels.ID
						AND steps.COST_MODEL_ID = ncmodels.ID
						AND ncclans.ID = %s
						AND steps.NEXT_VALUE > %s";
		$nonsql = $wpdb->prepare($nonsql, $clanid, $level);
		$result = $wpdb->get_results($nonsql);
		/* echo "<pre>SQL1: $nonsql\n";
		print_r($result);
		echo "</pre>";  */
	}			
		
	/* 		
	$sql = "SELECT steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.XP_COST
		FROM
			" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
			" . GVLARP_TABLE_PREFIX . "COST_MODEL clanmodels
			LEFT JOIN
				($clansql) 
			" . GVLARP_TABLE_PREFIX . "DISCIPLINE discipline,
			" . GVLARP_TABLE_PREFIX . "CLAN clans,
			" . GVLARP_TABLE_PREFIX . "CLAN_DISCIPLINE clandisciplines
		WHERE
			steps.COST_MODEL_ID = models.ID
			AND discipline.COST_MODEL_ID = models.ID
			AND clans.ID = clandisciplines.CLAN_ID
			AND discipline.ID = %s
			AND steps.NEXT_VALUE > %s
		ORDER BY steps.CURRENT_VALUE ASC";

	$sql = $wpdb->prepare($sql, $tableid, $level);
	
	return $wpdb->get_results($sql); */
	
	return $result;

}
function get_character_maximums($characterID) {
	global $wpdb;
	
	$maxRating     = 5;
	$maxDiscipline = 5;

	$sql = "SELECT gen.max_rating, gen.max_discipline
				FROM " . GVLARP_TABLE_PREFIX . "CHARACTER chara,
					 " . GVLARP_TABLE_PREFIX . "GENERATION gen
				WHERE chara.generation_id = gen.id
				  AND chara.ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$characterMaximums = $wpdb->get_results($sql);
	foreach ($characterMaximums as $charMax) {
		$maxRating = $charMax->max_rating;
		$maxDiscipline = $charMax->max_discipline;
	}
	
	return array($maxRating, $maxDiscipline);
}

function render_stat_details() {
	
	$output = "";
	$rowoutput = "";
	
	$levels   = $_REQUEST['stat_level'];
	$names    = $_REQUEST['stat_name'];
	$specats  = $_REQUEST['stat_spec_at'];
	$specs    = $_REQUEST['stat_spec'];
	$itemids  = $_REQUEST['stat_itemid'];
	$trains   = $_REQUEST['stat_training'];
	$xpcosts  = $_REQUEST['stat_cost'];
	$comments = $_REQUEST['stat_comment'];
	
	foreach ($levels as $id => $level ) {
	
		if ($level > 0) {
			
			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td colspan=4>";
			$rowoutput .= "<input type='hidden' name='stat_level[" . $id . "]' value='$level' >\n";
			$rowoutput .= "<input type='hidden' name='stat_name[" . $id . "]' value='{$names[$id]}' >\n";
			$rowoutput .= "<input type='hidden' name='stat_cost[" . $id . "]' value='{$xpcosts[$id]}' >\n";
			$rowoutput .= "<input type='hidden' name='stat_comment[" . $id . "]' value='{$comments[$id]}' >\n";
			$rowoutput .= "<input type='hidden' name='stat_itemid[" . $id . "]' value='{$itemids[$id]}' >\n";
			$rowoutput .= "<input type='hidden' name='stat_spec_at[" . $id . "]' value='{$specats[$id]}' >\n";
			
			$rowoutput .= "</td></tr>";
		
			// Stat name
			$rowoutput .= "<tr><th class='gvthleft'>{$names[$id]}</th>";
			
			// Stat specialisation
			if ($specats[$id] > 0) {
				if (empty($specs[$id]) && $specats[$id] <= $level)
					$rowoutput .= "<td><input type='text' name='stat_spec[" . $id . "]' value='' size=15 maxlength=60></td>";
				else
					$rowoutput .= "<td>{$specs[$id]}<input type='hidden' name='stat_spec[" . $id . "]' value='{$specs[$id]}'></td>";
			} else {
				$rowoutput .= "<td>&nbsp;</td>";
			}
			
			// Spend information
			$rowoutput .= "<td>{$comments[$id]}</td>";
			
			// cost
			$rowoutput .= "<td>{$xpcosts[$id]}</td>";
			
			// Training
			$rowoutput .= "<td><input type='text'  name='stat_training[$id]' value='{$trains[$id]}' size=30 maxlength=160 /></td></tr>";
		}
	}

	if (!empty($rowoutput)) {
		$output .= "<table>\n";
		$output .= "<tr><th class='gvthead'>Stat name</th><th class='gvthead'>Specialisation</th><th class='gvthead'>Experience Spend</th><th class='gvthead'>XP Cost</th><th class='gvthead'>Training Note</th></tr>";
		$output .= "$rowoutput\n";
		$output .= "</table>\n";
	} 
	
	return $output;
}
function render_stats($characterID, $maxRating, $pendingSpends) {
	global $wpdb;
	
	$sql = "SELECT 
				stat.name, 
				cha_stat.level,
				cha_stat.comment,
				cha_stat.id, 
				stat.specialisation_at spec_at,
				stat.ID as item_id, 
				stat.GROUPING as grp,
				IF(level >= 1,'X', IF(%d < 1,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 1,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 1,'P','0')))) as dot1,
				IF(level >= 2,'X', IF(%d < 2,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 2,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 2,'P','0')))) as dot2,
				IF(level >= 3,'X', IF(%d < 3,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 3,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 3,'P','0')))) as dot3,
				IF(level >= 4,'X', IF(%d < 4,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 4,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 4,'P','0')))) as dot4,
				IF(level >= 5,'X', IF(%d < 5,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 5,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 5,'P','0')))) as dot5,
				IF(level >= 6,'X', IF(%d < 6,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 6,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 6,'P','0')))) as dot6,
				IF(level >= 7,'X', IF(%d < 7,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 7,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 7,'P','0')))) as dot7,
				IF(level >= 8,'X', IF(%d < 8,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 8,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 8,'P','0')))) as dot8,
				IF(level >= 9,'X', IF(%d < 9,'0', IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 9,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 9,'P','0')))) as dot9,
				IF(level >= 10,'X',IF(%d < 10,'0',IF(ISNULL(CHARTABLE_LEVEL),IF(NEXT_VALUE = 10,XP_COST,'0'),IF(CHARTABLE_LEVEL >= 10,'P','0')))) as dot10,
				steps.XP_COST,
				steps.NEXT_VALUE,
				NOT(ISNULL(CHARTABLE_LEVEL)) as has_pending, 
				pendingspend.ID as pending_id
			FROM " . GVLARP_TABLE_PREFIX . "CHARACTER_STAT cha_stat
				LEFT JOIN 
					(SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pending
					WHERE 
						pending.CHARACTER_ID = %s
						AND pending.CHARTABLE = 'CHARACTER_STAT'
					) as pendingspend
				ON
					pendingspend.CHARTABLE_ID = cha_stat.id,
				 " . GVLARP_TABLE_PREFIX . "STAT stat,
				 " . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
				 " . GVLARP_TABLE_PREFIX . "COST_MODEL models
			WHERE 
				cha_stat.STAT_ID      = stat.ID
				AND steps.COST_MODEL_ID = models.ID
				AND stat.COST_MODEL_ID = models.ID
				AND steps.CURRENT_VALUE = cha_stat.level
				AND cha_stat.CHARACTER_ID = %s
		   ORDER BY stat.ordering";
	$sql = $wpdb->prepare($sql, $maxRating,$maxRating,$maxRating,$maxRating,$maxRating,
								$maxRating,$maxRating,$maxRating,$maxRating,$maxRating,
								$characterID,$characterID);
	//echo "<p>SQL: $sql</p>";
	$character_stats_xp = $wpdb->get_results($sql);
	
	$rowoutput = render_spend_table('stat', $character_stats_xp, $maxRating, 3);
	
	if (!empty($rowoutput)) {
		$output .= "<table>\n";
		$output .= "$rowoutput\n";
		$output .= "</table>\n";
	} 

	return $output;

}

function render_spend_table($type, $allxpdata, $maxRating, $columns) {

	$fulldoturl    = plugins_url( 'gvlarp-character/images/xpdot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/viewemptydot.jpg' );
	$pendingdoturl = plugins_url( 'gvlarp-character/images/pendingdot.jpg' );
	$levelsdata    = $_REQUEST['stat_level'];

	$max2display = get_max_dots($xpdata, $maxRating);
	$colspan = 2 + $max2display;
	$grp = "";
	$col = 0;
	$rowoutput = "";
	foreach ($allxpdata as $xpdata) {
		$id = $xpdata->id;
		
		// Hidden fields
		$rowoutput .= "<tr style='display:none'><td>\n";
		$rowoutput .= "<input type='hidden' name='{$type}_spec_at[" . $id . "]' value='" . $xpdata->spec_at . "' >";
		$rowoutput .= "<input type='hidden' name='{$type}_spec[" . $id . "]'    value='" . $xpdata->comment . "' >";
		$rowoutput .= "<input type='hidden' name='{$type}_curr[" . $id . "]'    value='" . $xpdata->level . "' >\n";
		$rowoutput .= "<input type='hidden' name='{$type}_itemid[" . $id . "]'  value='" . $xpdata->item_id . "' >\n";
		$rowoutput .= "<input type='hidden' name='{$type}_new[" . $id . "]'     value='" . ($xpdata->id == 0) . "' >\n";
		$rowoutput .= "<input type='hidden' name='{$type}_name[" . $id . "]'    value='" . $xpdata->name . "' >\n";
		$rowoutput .= "</td></tr>\n";
		
		// start column / new column
		if (isset($xpdata->grp)) {
			if ($grp != $xpdata->grp) {
				if (empty($grp)) {
					$rowoutput .= "<tr><td class='gvxp_col'><table><tr><th colspan=$colspan>{$xpdata->grp}</th></tr>";
					$col++;
				} 
				elseif ($col == $columns) {
					$rowoutput .= "</table></td></tr><tr><td class='gvxp_col'><table><tr><th colspan=$colspan>{$xpdata->grp}</th></tr>";
					$col = 1;
				}
				else {
					$rowoutput .= "</table></td><td class='gvxp_col'><table><tr><th colspan=$colspan>{$xpdata->grp}</th></tr>";
					$col++;
				}
				$grp = $xpdata->grp;
			}
		}
		
		//dots row
		$rowoutput .= "<tr><th class='gvthleft'>{$xpdata->name}</th>";
		for ($i=1;$i<=$max2display;$i++) {
			$dot = "dot" . $i;
			switch($xpdata->$dot) {
				case 'X':
					$rowoutput .= "<td class='gvxp_dot'><img src='$fulldoturl'></td>";
					break;
				case 'P':
					$rowoutput .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td>";
					break;
				case '0':
					$rowoutput .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
					break;
				default:
					$comment    = $xpdata->name . " " . $xpdata->level . " > " . $i;
				
					$rowoutput .= "<td class='gvxp_checkbox'>";
					$rowoutput .= "<input type='hidden'   name='{$type}_cost[" . $id . "]'    value='" . $xpdata->$dot . "' >";
					$rowoutput .= "<input type='hidden'   name='{$type}_comment[" . $id . "]' value='$comment' >";
					$rowoutput .= "<input type='CHECKBOX' name='{$type}_level[" . $id . "]'   value='$i' ";
					if (isset($levelsdata[$id]) && $i == $levelsdata[$id])
						$rowoutput .= "checked";
					$rowoutput .= ">";
					$rowoutput .= "</td>";
			}

		}
		if ($xpdata->NEXT_VALUE == $xpdata->level)
			$dot = "dot" . ($xpdata->NEXT_VALUE + 1);
		else
			$dot = "dot" . $xpdata->NEXT_VALUE;
		$xpcost = $xpdata->$dot ? "(" . $xpdata->$dot . " XP)" : "";
		if ($xpdata->has_pending)
			$rowoutput .= "<td class='gvxp_checkbox'><input type='CHECKBOX' name='{$type}_cancel[$id]' value='{$xpdata->pending_id}'><label>clear</label></td>";
		else
			$rowoutput .= "<td class=''>$xpcost</td>";
		$rowoutput .= "</tr>\n";
		
	}
	$rowoutput .= "</table></td></tr>\n";

	return $rowoutput;
}

function get_max_dots($data, $maxRating) {
	$max2display = 5;
	if ($maxRating > 5)
		$max2display = 10;
	else {
		/* check what the character has, in case they have the merit that increases
		something above max */
		if (count($data)) 
			foreach ($data as $row) {
				if ($row->level > $max2display)
					$max2display = 10;
			}
	}
	return $max2display;
}

function pending_level ($pendingdata, $chartableid, $itemid) {

	$result = 0;
		
	if ($chartableid != 0) {
		foreach ($pendingdata as $row)
			if ($row->CHARTABLE_ID == $chartableid) {
				$result = $row->CHARTABLE_LEVEL;
				break;
			}
	} else {
		foreach ($pendingdata as $row)
			if ($row->ITEMTABLE_ID == $itemid && $row->CHARTABLE_ID == 0) {
				$result = $row->CHARTABLE_LEVEL;
				break;
			}
	}
	
	/* echo "<p>charid: $chartableid, itemid: $itemid, result: $result</p>"; */
	return $result;

}
function pending_id ($pendingdata, $chartableid, $itemid) {

	$result = 0;
		
	if ($chartableid != 0) {
		foreach ($pendingdata as $row)
			if ($row->CHARTABLE_ID == $chartableid) {
				$result = $row->ID;
				break;
			}
	} else {
		foreach ($pendingdata as $row)
			if ($row->ITEMTABLE_ID == $itemid && $row->CHARTABLE_ID == 0) {
				$result = $row->ID;
				break;
			}
	}
	
	/* echo "<p>charid: $chartableid, itemid: $itemid, result: $result</p>"; */
	return $result;

}

function pending_training ($pendingdata, $chartableid, $itemid) {

	$result = 0;
		
	if ($chartableid != 0) {
		foreach ($pendingdata as $row)
			if ($row->CHARTABLE_ID == $chartableid) {
				$result = $row->TRAINING_NOTE;
				break;
			}
	} else {
		foreach ($pendingdata as $row)
			if ($row->ITEMTABLE_ID == $itemid && $row->CHARTABLE_ID == 0) {
				$result = $row->TRAINING_NOTE;
				break;
			}
	}
	
	/* echo "<p>charid: $chartableid, itemid: $itemid, result: $result</p>"; */
	return $result;

}

function calc_submitted_spend($type) {
	$spend = 0;

	$costs =  $_REQUEST[$type . '_cost'];

	foreach ($_REQUEST[$type . '_level'] as $id => $level) {
		if ($level)
			$spend += $costs[$id];
	}
	/* print_r($costlvls); */
	
	return $spend;
	
}

function save_to_pending ($type, $table, $itemtable, $itemidname, $playerID, $characterID) {
	global $wpdb;

	$newid = "";

	$levels          = $_REQUEST[$type . '_level'];
	$specialisations = $_REQUEST[$type . '_spec'];
	$training        = $_REQUEST[$type . '_training'];
	$itemid          = $_REQUEST[$type . '_itemid'];
	$costlvls        = $_REQUEST[$type . '_cost'];
	$comments        = $_REQUEST[$type . '_comment'];
			
	foreach ($levels as $id => $level) {
		
		if ($level) {
			$dataarray = array (
				'PLAYER_ID'       => $playerID,
				'CHARACTER_ID'    => $characterID,
				'CHARTABLE'       => $table,
				'CHARTABLE_ID'    => $id,
				'CHARTABLE_LEVEL' => $level,
				'AWARDED'         => Date('Y-m-d'),
				'AMOUNT'          => $costlvls[$id] * -1,
				'COMMENT'         => $comments[$id],
				'SPECIALISATION'  => $specialisations[$id],
				'TRAINING_NOTE'   => $training[$id],
				'ITEMTABLE'       => $itemtable,
				'ITEMNAME'        => $itemidname,
				'ITEMTABLE_ID'    => $itemid[$id]
			);
			
			$wpdb->insert(GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND",
						$dataarray,
						array (
							'%d',
							'%d',
							'%s',
							'%d',
							'%d',
							'%s',
							'%d',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%d'
						)
					);
			
			$newid = $wpdb->insert_id;
			if ($newid  == 0) {
				echo "<p style='color:red'><b>Error:</b> XP Spend failed for data (";
				print_r($dataarray);
				$wpdb->print_error();
				echo ")</p>";
			} 
		}
	
	}	
	
	return $newid;
							
}


function get_total_xp($characterID) {
	global $wpdb;
	
	$sql = "SELECT SUM(AMOUNT) as COST FROM " . GVLARP_TABLE_PREFIX . "PLAYER_XP WHERE CHARACTER_ID = %s";

	$sql = $wpdb->prepare($sql, $characterID);
	$result = $wpdb->get_results($sql);
	$xptotal = $result[0]->COST;
	
	return $xptotal;
}
function get_pending_xp($characterID) {
	global $wpdb;
	
	$sql = "SELECT SUM(AMOUNT) as COST FROM " . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND
			WHERE CHARACTER_ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	$result = $wpdb->get_results($sql);
	$xp_pending = $result[0]->COST * -1;
	
	return $xp_pending;
}

function establishPrivateClanID($characterID) {
	global $wpdb;
	
	$sql = "SELECT PRIVATE_CLAN_ID FROM " . GVLARP_TABLE_PREFIX . "CHARACTER WHERE ID = %s";
	$sql = $wpdb->prepare($sql, $characterID);
	/* echo "<pre>$sql</pre>"; */
	$result = $wpdb->get_results($sql);
	
	return $result[0]->PRIVATE_CLAN_ID;
}

function cancel_pending($data) {
	global $wpdb;
	
	foreach ($data as $item => $pendingid) {
		$sql = "DELETE FROM " . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND
				WHERE ID = %d";
		$sql = $wpdb->prepare($sql, $pendingid);
		/* echo "<p>SQL: $sql</p>"; */
		$result = $wpdb->get_results($sql);
	}
}

function validate_spends($characterID) {

	$xp_total   = get_total_xp($characterID);
	$xp_pending = get_pending_xp($characterID);
	$xp_spent    = 0;
	$outputError = "";
	
	if (isset($_REQUEST['stat_level'])) $xp_spent += calc_submitted_spend('stat');
	
	if ($xp_spent > ($xp_total - $xp_pending)) {
		$outputError .= "<p>You don't have enough experience left</p>";
	}
	
	if (!$xp_spent)
		$outputError .= "<p>You have not spent any experience</p>";
	
	/* echo "<p>Spent $xp_spent, Total: $xp_total, Pending: $xp_pending</p>"; */
	
	return $outputError;
	
}
function validate_details($characterID) {

	$outputError = "";
	
	if (isset($_REQUEST['stat_level'])) {
		$spec_at      = $_REQUEST['stat_spec_at'];
		$statlevels   = $_REQUEST['stat_level'];
		$stattraining = $_REQUEST['stat_training'];
		$stat_specialisations = $_REQUEST['stat_spec'];
		if (isset($stat_specialisations))
			foreach ($stat_specialisations as $id => $specialisation) {
				if ($spec_at[$id] <= $statlevels[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$stat_spec_error[$id] = 1;
				}
			}
		
		if (count($stat_spec_error))
			$outputError .= "<p>Please fix missing or invalid <a href='#gvid_xpst_stat'>Attribute</a> specialisations</p>";
			
		foreach ($stattraining as $id => $trainingnote) {
			if ($statlevels[$id] &&
				($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
				$stat_train_error[$id] = 1;
			}
		}
		if (count($stat_train_error))
			$outputError .= "<p>Please fix missing <a href='#gvid_xpst_stat'>Attribute</a> training notes</p>";

	}

	
	return $outputError;
	
}
?>