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
	if (isset($_REQUEST['disc_level'])) {
		$newid = save_to_pending('disc', 'CHARACTER_DISCIPLINE', 'DISCIPLINE', 'DISCIPLINE_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['path_level'])) {
		$newid = save_to_pending('path', 'CHARACTER_PATH', 'PATH', 'PATH_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['ritual_level'])) {
		$newid = save_to_pending('ritual', 'CHARACTER_RITUAL', 'RITUAL', 'RITUAL_ID', $playerID, $characterID);
	}
	if (isset($_REQUEST['merit_level'])) {
		$newid = save_to_pending('merit', 'CHARACTER_MERIT', 'MERIT', 'MERIT_ID', $playerID, $characterID);
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

/* shortcode */

function print_xp_spend_table() {
	global $wpdb;
	
	$character   = establishCharacter($character);
	$characterID = establishCharacterID($character);
	$playerID    = establishPlayerID($character);
	
	$output = "";
	$step = $_REQUEST['step'];
		
	// Cancel Spends
	$docancel = (isset($_REQUEST['stat_cancel']) 
				|| isset($_REQUEST['skill_cancel'])
				|| isset($_REQUEST['disc_cancel'])
				|| isset($_REQUEST['path_cancel'])
				|| isset($_REQUEST['ritual_cancel'])
				|| isset($_REQUEST['merit_cancel'])
				);
	if (isset($_REQUEST['stat_cancel']))    cancel_pending($_REQUEST['stat_cancel']);
	if (isset($_REQUEST['skill_cancel']))   cancel_pending($_REQUEST['skill_cancel']);
	if (isset($_REQUEST['disc_cancel']))    cancel_pending($_REQUEST['disc_cancel']);
	if (isset($_REQUEST['path_cancel']))    cancel_pending($_REQUEST['path_cancel']);
	if (isset($_REQUEST['ritual_cancel']))  cancel_pending($_REQUEST['ritual_cancel']);
	if (isset($_REQUEST['merit_cancel']))   cancel_pending($_REQUEST['merit_cancel']);
	
	// Back button
	if (isset($_REQUEST['xCancel'])) $step = "";
	
	/* VALIDATE SPENDS */
	switch ($step) {
		case 'supply_details':
			$outputError .= validate_spends($playerID, $characterID, $docancel);
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
	if (isset($_REQUEST['stat_level'])) $spent += calc_submitted_spend('stat');
	if (isset($_REQUEST['skill_level'])) $spent += calc_submitted_spend('skill');
	if (isset($_REQUEST['disc_level'])) $spent += calc_submitted_spend('disc');
	if (isset($_REQUEST['path_level'])) $spent += calc_submitted_spend('path');
	if (isset($_REQUEST['ritual_level'])) $spent += calc_submitted_spend('ritual');
	if (isset($_REQUEST['merit_level'])) $spent += calc_submitted_spend('merit');
	
	$output .= "<p>Spending $spent experience points.</p>\n";
	$output .= "<p>Please enter specialisations, if available, and describe how you are learning the selected items</p>";
	
	$output .= "<div class='gvplugin' id=\"gvid_xpst\">\n";
	$output .= "<form name=\"SPEND_XP_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">\n";
	
	if (isset($_REQUEST['stat_level'])) {
		$output .= render_details_section('stat');
	}
	if (isset($_REQUEST['skill_level'])) {
		$output .= render_details_section('skill');
	}
	if (isset($_REQUEST['disc_level'])) {
		$output .= render_details_section('disc');
	}
	if (isset($_REQUEST['path_level'])) {
		$output .= render_details_section('path');
	}
	if (isset($_REQUEST['ritual_level'])) {
		$output .= render_details_section('ritual');
	}
	if (isset($_REQUEST['merit_level'])) {
		$output .= render_details_section('merit');
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
	$playerID    = establishPlayerID($character);
	
	$xp_total      = get_total_xp($playerID, $characterID);
	$xp_pending    = get_pending_xp($characterID);
	$fulldoturl    = plugins_url( 'gvlarp-character/images/viewfulldot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/viewemptydot.jpg' );
	$pendingdoturl = plugins_url( 'gvlarp-character/images/pendingdot.jpg' );
	
	$sectioncontent = array();
	$sectionheading = array();
	$sectiontitle   = array(
						'stat'  => "Attributes",
						'skill' => "Abilities",
						'disc'  => "Disciplines",
						'path'  => "Paths",
						'ritual' => "Rituals",
						'merit'  => "Merits and Flaws"
					);
	$sectioncols    = array();
	$sectionorder   = array('stat', 'skill', 'disc', 'path',
							'ritual', 'merit');

	$output = "<p class='gvxp_xpstatus'>You have $xp_total experience in total with $xp_pending points currently pending</p>";

	/* work out the maximum ratings for this character based on generation */
	$ratings = get_character_maximums($characterID);
	$maxRating = $ratings[0];
	$maxDiscipline = $ratings[1];
	
	/* get the current pending spends for this character */
	$pendingSpends = get_pending($characterID);
	
	$sectioncontent['stat']   = render_stats($characterID, $maxRating, $pendingSpends);
	$sectioncontent['skill']  = render_skills($characterID, $maxRating, $pendingSpends);
	$sectioncontent['disc']   = render_disciplines($characterID, $maxDiscipline, $pendingSpends);
	$sectioncontent['path']   = render_paths($characterID, 5, $pendingSpends);
	$sectioncontent['ritual'] = render_rituals($characterID, 5, $pendingSpends);
	$sectioncontent['merit']  = render_merits($characterID, $pendingSpends);
	
	
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
			$output .= "<input class='gvxp_submit' type='submit' name=\"xSubmit\" value=\"Next >\">\n";
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

function render_details_section($type) {
	
	$output = "";
	$rowoutput = "";
	
	$ids      = $_REQUEST[$type . '_id'];
	$levels   = $_REQUEST[$type . '_level'];
	$names    = $_REQUEST[$type . '_name'];
	$specats  = $_REQUEST[$type . '_spec_at'];
	$specs    = $_REQUEST[$type . '_spec'];
	$itemids  = $_REQUEST[$type . '_itemid'];
	$trains   = $_REQUEST[$type . '_training'];
	$xpcosts  = $_REQUEST[$type . '_cost'];
	$comments = $_REQUEST[$type . '_comment'];
	
	//print_r($specs);
	//print_r($levels);
	
	foreach ($levels as $index => $level ) {
	
		if ($level != 0) {
			
			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td colspan=4>";
			$rowoutput .= "<input type='hidden' name='{$type}_level[" . $index . "]' value='$level' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_name[" . $index . "]' value='{$names[$index]}' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_id[" . $index . "]' value='{$ids[$index]}' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_cost[" . $index . "]' value='{$xpcosts[$index]}' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_comment[" . $index . "]' value='{$comments[$index]}' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_itemid[" . $index . "]' value='{$itemids[$index]}' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_spec_at[" . $index . "]' value='{$specats[$index]}' >\n";
			
			$rowoutput .= "</td></tr>";
		
			// name
			$rowoutput .= "<tr><th class='gvthleft'>{$names[$index]}</th>";
			
			// specialisation
			if ($specats[$index] == 'Y') {
				if (empty($specs[$index]))
					$rowoutput .= "<td><input type='text' name='{$type}_spec[" . $index . "]' value='' size=15 maxlength=60></td>";
				else
					$rowoutput .= "<td>{$specs[$index]}<input type='hidden' name='{$type}_spec[" . $index . "]' value='{$specs[$index]}'></td>";
			} elseif ($specats[$index] > 0) {
				if (empty($specs[$index]) && $specats[$index] <= $level)
					$rowoutput .= "<td><input type='text' name='{$type}_spec[" . $index . "]' value='' size=15 maxlength=60></td>";
				else
					$rowoutput .= "<td>{$specs[$index]}<input type='hidden' name='{$type}_spec[" . $index . "]' value='{$specs[$index]}'></td>";
			} else {
				$rowoutput .= "<td>&nbsp;</td>";
			}
			
			// Spend information
			$rowoutput .= "<td>{$comments[$index]}</td>";
			
			// cost
			$rowoutput .= "<td>{$xpcosts[$index]}</td>";
			
			// Training
			$rowoutput .= "<td><input type='text'  name='{$type}_training[$index]' value='{$trains[$index]}' size=30 maxlength=160 /></td></tr>";
		}
	}

	if (!empty($rowoutput)) {
		$output .= "<table>\n";
		$output .= "<tr><th class='gvthead'>Name</th><th class='gvthead'>Specialisation</th><th class='gvthead'>Experience Spend</th><th class='gvthead'>XP Cost</th><th class='gvthead'>Training Note</th></tr>";
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
				pendingspend.CHARTABLE_LEVEL,
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
	$sql = $wpdb->prepare($sql, $characterID,$characterID);
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
function render_skills($characterID, $maxRating, $pendingSpends) {
	global $wpdb;
	
	/* All the skills currently had, with pending
		plus all the pending skills not already had
		
		Then list all the available skills to buy, current level, pending and new level
	*/
	
	$sqlCharacterSkill = "SELECT
					skill.name as name, 
					cha_skill.level as level, 
					cha_skill.comment as comment, 
					cha_skill.id as id,
					skill.specialisation_at as spec_at, 
					skill.id as item_id,
					skill.grouping as grp,
					CASE skill.grouping WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ordering,
					pending.CHARTABLE_LEVEL,
					steps.XP_COST,
					steps.NEXT_VALUE, 
					skill.COST_MODEL_ID as COST_MODEL_ID,
					NOT(ISNULL(pending.CHARTABLE_LEVEL)) as has_pending, 
					pending.ID as pending_id
				FROM
					" . GVLARP_TABLE_PREFIX . "CHARACTER_SKILL cha_skill
					LEFT JOIN
						(SELECT ID, CHARTABLE_ID, CHARTABLE_LEVEL
						FROM
							" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							CHARACTER_ID = %s
							AND CHARTABLE = 'CHARACTER_SKILL'
						) as pending
					ON
						pending.CHARTABLE_ID = cha_skill.ID,
					" . GVLARP_TABLE_PREFIX . "SKILL skill,
					" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
					" . GVLARP_TABLE_PREFIX . "COST_MODEL models
				WHERE
					cha_skill.CHARACTER_ID = %s
					AND cha_skill.SKILL_ID = skill.ID
					AND steps.COST_MODEL_ID = models.ID
					AND skill.COST_MODEL_ID  = models.ID
					AND steps.CURRENT_VALUE = cha_skill.level";
	$sqlPending = "SELECT
					skill.name as name, 
					0 as level, 
					pending.SPECIALISATION as comment, 
					0 as id,
					skill.specialisation_at as spec_at, 
					skill.id as item_id,
					skill.grouping as grp,
					CASE skill.grouping WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ordering,
					pending.CHARTABLE_LEVEL,
					steps.XP_COST,
					steps.NEXT_VALUE, 
					skill.COST_MODEL_ID as COST_MODEL_ID,
					1 as has_pending, 
					pending.ID as pending_id
				FROM
					" . GVLARP_TABLE_PREFIX . "SKILL skill,
					" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
					" . GVLARP_TABLE_PREFIX . "COST_MODEL models,
					" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pending
				WHERE
					pending.CHARACTER_ID = %s
					AND pending.CHARTABLE = 'CHARACTER_SKILL'
					AND pending.ITEMTABLE_ID = skill.ID
					AND pending.CHARTABLE_ID = 0
					AND steps.COST_MODEL_ID = models.ID
					AND skill.COST_MODEL_ID  = models.ID
					AND steps.CURRENT_VALUE = 0";					
	
	$sql = "$sqlCharacterSkill
			UNION
			$sqlPending
			ORDER BY ordering DESC, grp, name, level DESC, comment";
	
	
	$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID, $characterID, $characterID);
    //echo "<p>SQL: $sql</p>";
	$character_skills_xp = reformat_skills_xp($wpdb->get_results($sql));
	
	$sql = "SELECT
				skill.name as name, 
				0 as level, 
				\"\" as comment, 
				0 as id,
				skill.specialisation_at as spec_at, 
				skill.id as item_id,
				skill.grouping as grp,
				CASE skill.grouping WHEN 'Talents' THEN 3 WHEN 'Skills' THEN 2 WHEN 'Knowledges' THEN 1 ELSE 0 END as ordering,
				0 as CHARTABLE_LEVEL,
				steps.XP_COST,
				steps.NEXT_VALUE, 
				skill.COST_MODEL_ID as COST_MODEL_ID,
				0 as has_pending, 
				0 as pending_id,
				skill.VISIBLE,
				skill.MULTIPLE
			FROM
				" . GVLARP_TABLE_PREFIX . "SKILL skill,
				" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
				" . GVLARP_TABLE_PREFIX . "COST_MODEL models
			WHERE
				steps.CURRENT_VALUE = 0
				AND steps.COST_MODEL_ID = models.ID
				AND skill.COST_MODEL_ID  = models.ID
			ORDER BY ordering DESC, grp, name";
	$skills_list = $wpdb->get_results($sql);
	
    //echo "<p>SQL: $sql</p>";
	//print_r($skills_list);
	
	$rowoutput = render_skill_spend_table('skill', $skills_list, $character_skills_xp, 
						$maxRating, 3);
	
	if (!empty($rowoutput)) {
		$output .= "<table>\n";
		$output .= "$rowoutput\n";
		$output .= "</table>\n";
	} 

	return $output;

}

function render_skills_row ($type, $rownum, $max2display, $maxRating, $datarow, $levelsdata) {

	$fulldoturl    = plugins_url( 'gvlarp-character/images/xpdot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/viewemptydot.jpg' );
	$pendingdoturl = plugins_url( 'gvlarp-character/images/pendingdot.jpg' );

	$rowoutput = "";
		// Hidden fields
	$rowoutput .= "<tr style='display:none'><td>\n";
	$rowoutput .= "<input type='hidden' name='{$type}_spec_at[" . $rownum . "]' value='" . $datarow->spec_at . "' >";
	$rowoutput .= "<input type='hidden' name='{$type}_spec[" . $rownum . "]'    value='" . $datarow->comment . "' >";
	$rowoutput .= "<input type='hidden' name='{$type}_curr[" . $rownum . "]'    value='" . $datarow->level . "' >\n";
	$rowoutput .= "<input type='hidden' name='{$type}_itemid[" . $rownum . "]'  value='" . $datarow->item_id . "' >\n";
	$rowoutput .= "<input type='hidden' name='{$type}_id[" . $rownum . "]'      value='" . $datarow->id . "' >\n";
	$rowoutput .= "<input type='hidden' name='{$type}_name[" . $rownum . "]'    value='" . $datarow->name . "' >\n";
	$rowoutput .= "</td></tr>\n";
	
	// start column / new column
/* 	if (isset($datarow->grp)) {
		if ($grp != $datarow->grp) {
			if (empty($grp)) {
				$rowoutput .= "<tr><td class='gvxp_col'><table><tr><th colspan=$colspan>{$datarow->grp}</th></tr>";
				$col++;
			} 
			elseif ($col == $columns) {
				$rowoutput .= "</table></td></tr><tr><td class='gvxp_col'><table><tr><th colspan=$colspan>{$datarow->grp}</th></tr>";
				$col = 1;
			}
			else {
				$rowoutput .= "</table></td><td class='gvxp_col'><table><tr><th colspan=$colspan>{$datarow->grp}</th></tr>";
				$col++;
			}
			$grp = $datarow->grp;
		}
	}
 */	
	//dots row
	$xpcost = 0;
	$rowoutput .= "<tr><th class='gvthleft'><span";
	if ($datarow->comment)
		$rowoutput .= " alt='{$datarow->comment}' title='{$datarow->comment}' class='gvxp_spec' ";
	$rowoutput .= ">{$datarow->name}</span></th>";
	for ($i=1;$i<=$max2display;$i++) {
	
		if ($datarow->level >= $i)
			$rowoutput .= "<td class='gvxp_dot'><img src='$fulldoturl'></td>";
		elseif ($maxRating < $i)
			$rowoutput .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
		elseif ($datarow->CHARTABLE_LEVEL)
			if ($datarow->CHARTABLE_LEVEL >= $i)
				$rowoutput .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td>";
			else
				$rowoutput .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
		else
			if ($datarow->NEXT_VALUE == $i) {
			
				if ($datarow->NEXT_VALUE > $datarow->level)
					$xpcost = $datarow->XP_COST;
				
				$comment    = $datarow->name . " " . $datarow->level . " > " . $i;
			
				$rowoutput .= "<td class='gvxp_checkbox'>";
				$rowoutput .= "<input type='hidden'   name='{$type}_cost[" . $rownum . "]'    value='" . $xpcost . "' >";
				$rowoutput .= "<input type='hidden'   name='{$type}_comment[" . $rownum . "]' value='$comment' >";
				$rowoutput .= "<input type='CHECKBOX' name='{$type}_level[" . $rownum . "]'   value='$i' ";
				if (isset($levelsdata[$rownum]) && $i == $levelsdata[$rownum])
					$rowoutput .= "checked";
				$rowoutput .= ">";
				$rowoutput .= "</td>";
			}
			else
				$rowoutput .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
				
	}
	
		
	$xpcost = $xpcost ? "(" . $xpcost . " XP)" : "";
	if ($datarow->has_pending)
		//$rowoutput .= "<td class='gvxp_checkbox'><input type='CHECKBOX' name='{$type}_cancel[$rownum]' value='{$datarow->pending_id}'><label>clear</label></td>";
		$rowoutput .= "<td class='gvcol_cost'><input class='gvxp_clear' type='submit' name=\"{$type}_cancel[{$datarow->pending_id}]\" value=\"Clear\"></td>";
	else
		$rowoutput .= "<td class='gvcol_cost'>$xpcost</td>";
	$rowoutput .= "</tr>\n";

	
	return $rowoutput;

}

function reformat_skills_xp ($input) {

	$arrayout = array();
	
	foreach ($input as $row) {
		if (array_key_exists($row->item_id, $arrayout)) {
			array_push($arrayout[$row->item_id],$row);
		} else {
			$arrayout[$row->item_id] = array($row);
		}
	
	}
	
	//print_r($arrayout);
	
	return $arrayout;

}


function render_disciplines($characterID, $maxRating, $pendingSpends) {
	global $wpdb;
	
	$sql = "SELECT
				disc.name,
				clans.name as clanname,
				NOT(ISNULL(clandisc.DISCIPLINE_ID)) as isclan,
				IF(ISNULL(clandisc.DISCIPLINE_ID),'Non-Clan Discipline','Clan Discipline') as grp,
				cha_disc.level,
				disc.ID as item_id,
				pendingspend.CHARTABLE_LEVEL,
				IF(ISNULL(clandisc.DISCIPLINE_ID),nonclansteps.XP_COST,clansteps.XP_COST) as XP_COST,
				IF(ISNULL(clandisc.DISCIPLINE_ID),nonclansteps.NEXT_VALUE,clansteps.NEXT_VALUE) as NEXT_VALUE,
				NOT(ISNULL(CHARTABLE_LEVEL)) as has_pending,
				pendingspend.ID as pending_id
			FROM
				" . GVLARP_TABLE_PREFIX . "DISCIPLINE disc
				LEFT JOIN
					(SELECT ID, LEVEL, CHARACTER_ID, DISCIPLINE_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "CHARACTER_DISCIPLINE
					WHERE
						CHARACTER_ID = %s
					) cha_disc
				ON
					cha_disc.DISCIPLINE_ID = disc.ID
				LEFT JOIN
					(SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID, ITEMTABLE_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pending
					WHERE	
						pending.CHARACTER_ID = %s
						AND pending.CHARTABLE = 'CHARACTER_DISCIPLINE'
					) as pendingspend
				ON
					pendingspend.ITEMTABLE_ID = disc.id
				LEFT JOIN
					(SELECT DISCIPLINE_ID, CLAN_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "CLAN clans,
						" . GVLARP_TABLE_PREFIX . "CLAN_DISCIPLINE cd,
						" . GVLARP_TABLE_PREFIX . "CHARACTER chars
					WHERE
						chars.ID = %s
						AND chars.PRIVATE_CLAN_ID = clans.ID
						AND cd.CLAN_ID = clans.ID
					) as clandisc
				ON
					clandisc.DISCIPLINE_ID = disc.id
				,
				" . GVLARP_TABLE_PREFIX . "CHARACTER chars,
				" . GVLARP_TABLE_PREFIX . "CLAN clans,
				" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP clansteps,
				" . GVLARP_TABLE_PREFIX . "COST_MODEL clanmodels,
				" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP nonclansteps,
				" . GVLARP_TABLE_PREFIX . "COST_MODEL nonclanmodels
			WHERE
				clansteps.COST_MODEL_ID = clanmodels.ID
				AND clans.CLAN_COST_MODEL_ID = clanmodels.ID
				AND nonclansteps.COST_MODEL_ID = nonclanmodels.ID
				AND clans.NONCLAN_COST_MODEL_ID = nonclanmodels.ID
				AND chars.PRIVATE_CLAN_ID = clans.ID
				AND chars.ID = %s
				AND (NOT(ISNULL(clandisc.DISCIPLINE_ID)) OR disc.VISIBLE = 'Y')
				AND (
					(ISNULL(cha_disc.LEVEL) AND clansteps.CURRENT_VALUE = 0)
					OR clansteps.CURRENT_VALUE = cha_disc.level
				)
				AND (
					(ISNULL(cha_disc.LEVEL) AND nonclansteps.CURRENT_VALUE = 0)
					OR nonclansteps.CURRENT_VALUE = cha_disc.level
				)
			ORDER BY grp, disc.name";
	$sql = $wpdb->prepare($sql, $characterID,$characterID,$characterID,$characterID);
    //echo "<p>SQL: $sql</p>";
	$character_data = $wpdb->get_results($sql);
	
	$rowoutput = render_spend_table('disc', $character_data, $maxRating, 3);
	
	if (!empty($rowoutput)) {
		$output .= "<table>\n";
		$output .= "$rowoutput\n";
		$output .= "</table>\n";
	} 

	return $output;

}
function render_paths($characterID, $maxRating, $pendingSpends) {
	global $wpdb;
	
	$sql = "SELECT
				path.name,
				disc.name as grp,
				char_disc.level as disclevel,
				cha_path.level,
				path.ID as item_id,
				pendingspend.CHARTABLE_LEVEL,
				steps.XP_COST as XP_COST,
				steps.NEXT_VALUE as NEXT_VALUE,
				NOT(ISNULL(CHARTABLE_LEVEL)) as has_pending,
				pendingspend.ID as pending_id
			FROM
				" . GVLARP_TABLE_PREFIX . "DISCIPLINE disc,
				" . GVLARP_TABLE_PREFIX . "PATH path
				LEFT JOIN
					(SELECT ID, LEVEL, COMMENT, CHARACTER_ID, PATH_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "CHARACTER_PATH
					WHERE
						CHARACTER_ID = %s
					) cha_path
				ON
					cha_path.PATH_ID = path.ID
				LEFT JOIN 
					(SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID, ITEMTABLE_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pending
					WHERE 
						pending.CHARACTER_ID = %s
						AND pending.CHARTABLE = 'CHARACTER_PATH'
					) as pendingspend
				ON
					pendingspend.ITEMTABLE_ID = path.id
				LEFT JOIN
					(SELECT *
					FROM
						" . GVLARP_TABLE_PREFIX . "CHARACTER_DISCIPLINE 
					WHERE
						CHARACTER_ID = %s
					) as char_disc
				ON
					char_disc.DISCIPLINE_ID = path.DISCIPLINE_ID
				,
				 " . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
				 " . GVLARP_TABLE_PREFIX . "COST_MODEL models
			WHERE 
				steps.COST_MODEL_ID = models.ID
				AND path.COST_MODEL_ID = models.ID
				AND disc.ID = path.DISCIPLINE_ID
				AND char_disc.DISCIPLINE_ID = disc.ID
				AND 
					(char_disc.level >= cha_path.level
					OR ISNULL(cha_path.level)
					)
				AND path.VISIBLE = 'Y'
				AND (
					(ISNULL(cha_path.LEVEL) AND steps.CURRENT_VALUE = 0)
					OR steps.CURRENT_VALUE = cha_path.level
				)
		   ORDER BY grp, path.name";
	$sql = $wpdb->prepare($sql, $characterID,$characterID,$characterID,$characterID);
    //echo "<p>SQL: $sql</p>";
	$character_data = $wpdb->get_results($sql);
	
	$rowoutput = render_spend_table('path', $character_data, $maxRating, 3);
	
	if (!empty($rowoutput)) {
		$output .= "<table>\n";
		$output .= "$rowoutput\n";
		$output .= "</table>\n";
	} 

	return $output;

}
function render_rituals($characterID, $maxRating, $pendingSpends) {
	global $wpdb;
	
	$sql = "SELECT
				ritual.name,
				ritual.level as rituallevel,
				disc.name as grp,		
				NOT(ISNULL(cha_ritual.level)) as level,
				ritual.ID as item_id,
				pendingspend.CHARTABLE_LEVEL,
				ritual.COST as XP_COST,
				1 as NEXT_VALUE,
				NOT(ISNULL(CHARTABLE_LEVEL)) as has_pending,
				pendingspend.ID as pending_id
			FROM
				" . GVLARP_TABLE_PREFIX . "DISCIPLINE disc,
				" . GVLARP_TABLE_PREFIX . "RITUAL ritual
				LEFT JOIN
					(SELECT ID, LEVEL, COMMENT, CHARACTER_ID, RITUAL_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "CHARACTER_RITUAL
					WHERE
						CHARACTER_ID = %s
					) cha_ritual
				ON
					cha_ritual.RITUAL_ID = ritual.ID
				LEFT JOIN 
					(SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID, ITEMTABLE_ID
					FROM
						" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pending
					WHERE 
						pending.CHARACTER_ID = %s
						AND pending.CHARTABLE = 'CHARACTER_RITUAL'
					) as pendingspend
				ON
					pendingspend.ITEMTABLE_ID = ritual.id
				LEFT JOIN
					(SELECT *
					FROM
						" . GVLARP_TABLE_PREFIX . "CHARACTER_DISCIPLINE 
					WHERE
						CHARACTER_ID = %s
					) as char_disc
				ON
					char_disc.DISCIPLINE_ID = ritual.DISCIPLINE_ID
			WHERE 
				disc.ID = ritual.DISCIPLINE_ID
				AND char_disc.level >= ritual.level
				AND ritual.VISIBLE = 'Y'
		   ORDER BY grp, ritual.level, ritual.name";
	$sql = $wpdb->prepare($sql, $characterID,$characterID,$characterID,$characterID);
    //echo "<p>SQL: $sql</p>";
	$character_data = $wpdb->get_results($sql);
	
	$rowoutput = render_ritual_spend_table('ritual', $character_data, 2);
	
	if (!empty($rowoutput)) {
		$output .= "<table>\n";
		$output .= "$rowoutput\n";
		$output .= "</table>\n";
	} 
	
	return $output;

}
function render_merits($characterID, $pendingSpends) {
	global $wpdb;
	
	$sql = "SELECT 
				merit.name, 
				cha_merit.level,
				cha_merit.comment,
				cha_merit.id,
				merit.has_specialisation,
				merit.ID as item_id, 
				IF(cha_merit.level < 0,'Remove Flaws','Buy Merits') as grp,
				pendingspend.CHARTABLE_LEVEL as CHARTABLE_LEVEL,
				merit.XP_COST,
				NOT(ISNULL(pendingspend.ID)) as has_pending,
				pendingspend.ID as pending_id,
				merit.VISIBLE
			FROM
				" . GVLARP_TABLE_PREFIX . "MERIT merit,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT cha_merit
				LEFT JOIN 
					(SELECT ID, CHARTABLE_LEVEL, CHARTABLE_ID, ITEMTABLE_ID, SPECIALISATION
					FROM
						" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pending
					WHERE 
						pending.CHARACTER_ID = %s
						AND pending.CHARTABLE = 'CHARACTER_MERIT'
					) as pendingspend
				ON
					pendingspend.CHARTABLE_ID = cha_merit.ID
			WHERE	
				cha_merit.MERIT_ID = merit.ID
				AND cha_merit.CHARACTER_ID = %s
			UNION
			SELECT
				merit.name, 
				merit.value as level,
				pendingspend.SPECIALISATION as comment,
				0 as id, 
				merit.has_specialisation,
				merit.ID as item_id, 
				IF(merit.value < 0,'Remove Flaws','Buy Merits') as grp,
				pendingspend.CHARTABLE_LEVEL,
				merit.XP_COST,
				1 as has_pending, 
				pendingspend.ID as pending_id,
				merit.VISIBLE
			FROM
				" . GVLARP_TABLE_PREFIX . "MERIT merit,
				" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pendingspend
			WHERE
				merit.ID = pendingspend.ITEMTABLE_ID
				AND pendingspend.CHARACTER_ID = %s
				AND pendingspend.CHARTABLE = 'CHARACTER_MERIT'
				AND merit.value >= 0
			ORDER BY grp DESC, level DESC, name";
			
	$sql = $wpdb->prepare($sql, $characterID,$characterID,$characterID);
    //echo "<p>SQL: $sql</p>";
	$character_merit_xp = reformat_skills_xp($wpdb->get_results($sql));
	
	$sql = "SELECT
				merit.ID as item_id,
				merit.name,
				merit.value as level,
				\"\" as comment,
				merit.has_specialisation,
				IF(merit.value < 0,'Remove Flaws','Buy Merits') as grp,
				merit.XP_COST,
				merit.VISIBLE,
				merit.MULTIPLE
			FROM
				" . GVLARP_TABLE_PREFIX . "MERIT merit
			ORDER BY grp DESC, level DESC, name";
	$merits_list = $wpdb->get_results($sql);
    //echo "<p>SQL: $sql</p>";
	
	$rowoutput = render_merit_spend_table('merit', $merits_list, $character_merit_xp, 2);
	
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
	$levelsdata    = $_REQUEST[$type . '_level'];

	$max2display = get_max_dots($xpdata, $maxRating);
	$colspan = 2 + $max2display;
	$grp = "";
	$col = 0;
	$rowoutput = "";
	if (count($allxpdata)>0) {
		$id = 0;
		foreach ($allxpdata as $xpdata) {
			//$id = $xpdata->id;
			
			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td>\n";
			$rowoutput .= "<input type='hidden' name='{$type}_spec_at[" . $id . "]' value='" . $xpdata->spec_at . "' >";
			$rowoutput .= "<input type='hidden' name='{$type}_spec[" . $id . "]'    value='" . $xpdata->comment . "' >";
			$rowoutput .= "<input type='hidden' name='{$type}_curr[" . $id . "]'    value='" . $xpdata->level . "' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_itemid[" . $id . "]'  value='" . $xpdata->item_id . "' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_id[" . $id . "]'      value='" . $xpdata->id . "' >\n";
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
			$xpcost = 0;
			$rowoutput .= "<tr><th class='gvthleft'><span";
			if ($xpdata->comment)
				$rowoutput .= " alt='{$xpdata->comment}' title='{$xpdata->comment}' class='gvxp_spec' ";
			$rowoutput .= ">{$xpdata->name}</span></th>";
			for ($i=1;$i<=$max2display;$i++) {
			
				if ($xpdata->level >= $i)
					$rowoutput .= "<td class='gvxp_dot'><img src='$fulldoturl'></td>";
				elseif ($maxRating < $i)
					$rowoutput .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
				elseif ($xpdata->CHARTABLE_LEVEL)
					if ($xpdata->CHARTABLE_LEVEL >= $i)
						$rowoutput .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td>";
					else
						$rowoutput .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
				else
					if ($xpdata->NEXT_VALUE == $i) {
					
						if ($xpdata->NEXT_VALUE > $xpdata->level)
							$xpcost = $xpdata->XP_COST;
						
						$comment    = $xpdata->name . " " . $xpdata->level . " > " . $i;
					
						$rowoutput .= "<td class='gvxp_checkbox'>";
						$rowoutput .= "<input type='hidden'   name='{$type}_cost[" . $id . "]'    value='" . $xpcost . "' >";
						$rowoutput .= "<input type='hidden'   name='{$type}_comment[" . $id . "]' value='$comment' >";
						$rowoutput .= "<input type='CHECKBOX' name='{$type}_level[" . $id . "]'   value='$i' ";
						if (isset($levelsdata[$id]) && $i == $levelsdata[$id])
							$rowoutput .= "checked";
						$rowoutput .= ">";
						$rowoutput .= "</td>";
					}
					else
						$rowoutput .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
						
			}
			
				
			$xpcost = $xpcost ? "(" . $xpcost . " XP)" : "";
			if ($xpdata->has_pending)
				//$rowoutput .= "<td class='gvxp_checkbox'><input type='CHECKBOX' name='{$type}_cancel[$id]' value='{$xpdata->pending_id}'><label>clear</label></td>";
				$rowoutput .= "<td class='gvcol_cost'><input class='gvxp_clear' type='submit' name=\"{$type}_cancel[{$xpdata->pending_id}]\" value=\"Clear\"></td>";
			else
				$rowoutput .= "<td class='gvcol_cost'>$xpcost</td>";
			$rowoutput .= "</tr>\n";
			
			$id++;
		}
	}
	$rowoutput .= "</table></td></tr>\n";

	return $rowoutput;
}


function render_skill_spend_table($type, $list, $allxpdata, $maxRating, $columns) {

	$levelsdata    = $_REQUEST[$type . '_level'];

	$max2display = get_max_dots($xpdata, $maxRating);
	$colspan = 2 + $max2display;
	$multipleonce = array();
	$grp = "";
	$col = 0;
	$rowoutput = "";
	//print_r($allxpdata);
	if (count($list)>0) {
		$id = 0;
		foreach ($list as $skill) {
		
			if (isset($skill->grp)) {
				if ($grp != $skill->grp) {
					if (empty($grp)) {
						$rowoutput .= "<tr><td class='gvxp_col'><table><tr><th colspan=$colspan>{$skill->grp}</th></tr>";
						$col++;
					} 
					elseif ($col == $columns) {
						$rowoutput .= "</table></td></tr><tr><td class='gvxp_col'><table><tr><th colspan=$colspan>{$skill->grp}</th></tr>";
						$col = 1;
					}
					else {
						$rowoutput .= "</table></td><td class='gvxp_col'><table><tr><th colspan=$colspan>{$skill->grp}</th></tr>";
						$col++;
					}
					$grp = $skill->grp;
				}
			}		
			
			if (array_key_exists($skill->item_id, $allxpdata)) {
				//loop through array
				foreach ($allxpdata[$skill->item_id] as $xpdatarow) {
					//echo "<li>$id : {$xpdatarow->name} : {$xpdatarow->comment}</li>";
					$rowoutput .= render_skills_row($type, $id, $max2display, $maxRating, $xpdatarow, $levelsdata);
					$id++;
					if ($skill->MULTIPLE == 'Y' && !array_key_exists($skill->item_id,$multipleonce)) {
						$multipleonce[$skill->item_id] = 1;
						$rowoutput .= render_skills_row($type, $id, $max2display, $maxRating, $skill, $levelsdata);
						$id++;
					}
				}
			} else {
				if ($skill->VISIBLE == 'Y') {
					$rowoutput .= render_skills_row($type, $id, $max2display, $maxRating, $skill, $levelsdata);
					$id++;
				}
			}
			
		}
	}
	$rowoutput .= "</table></td></tr>\n";

	return $rowoutput;
}

function render_ritual_spend_table($type, $allxpdata, $columns) {

	$fulldoturl    = plugins_url( 'gvlarp-character/images/xpdot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/viewemptydot.jpg' );
	$pendingdoturl = plugins_url( 'gvlarp-character/images/pendingdot.jpg' );
	$levelsdata    = $_REQUEST[$type . '_level'];

	$max2display = get_max_dots($xpdata, $maxRating);
	$colspan = 2 + $max2display;
	$grp = "";
	$col = 0;
	$rowoutput = "";
	if (count($allxpdata)>0) {
		$id = 0;
		foreach ($allxpdata as $xpdata) {
			//$id = $xpdata->id;
			
			// Hidden fields
			$rowoutput .= "<tr style='display:none'><td>\n";
			$rowoutput .= "<input type='hidden' name='{$type}_curr[" . $id . "]'    value='" . $xpdata->level . "' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_itemid[" . $id . "]'  value='" . $xpdata->item_id . "' >\n";
			$rowoutput .= "<input type='hidden' name='{$type}_id[" . $id . "]'      value='" . $xpdata->id . "' >\n";
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
			$xpcost = 0;
			$rowoutput .= "<tr><th class='gvthleft'><span>(Level {$xpdata->rituallevel}) {$xpdata->name}</span></th>";
			
			if ($xpdata->level || $xpdata->level === 0)
				$rowoutput .= "<td class='gvxp_dot'><img src='$fulldoturl'></td>";
			elseif ($xpdata->CHARTABLE_LEVEL)
				$rowoutput .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td>";
			else {
				$xpcost = $xpdata->XP_COST;
				
				$comment    = "Learn Level {$xpdata->rituallevel} {$xpdata->grp} ritual {$xpdata->name}";
			
				$rowoutput .= "<td class='gvxp_checkbox'>";
				$rowoutput .= "<input type='hidden'   name='{$type}_cost[" . $id . "]'    value='" . $xpcost . "' >";
				$rowoutput .= "<input type='hidden'   name='{$type}_comment[" . $id . "]' value='$comment' >";
				$rowoutput .= "<input type='CHECKBOX' name='{$type}_level[" . $id . "]'   value='{$xpdata->rituallevel}' ";
				if (isset($levelsdata[$id]))
					$rowoutput .= "checked";
				$rowoutput .= ">";
				$rowoutput .= "</td>";
			}
						
			
				
			$xpcost = $xpcost ? "(" . $xpcost . " XP)" : "";
			if ($xpdata->has_pending)
				$rowoutput .= "<td class='gvcol_cost'><input class='gvxp_clear' type='submit' name=\"{$type}_cancel[{$xpdata->pending_id}]\" value=\"Clear\"></td>";
			else
				$rowoutput .= "<td class='gvcol_cost'>$xpcost</td>";
			$rowoutput .= "</tr>\n";
			
			$id++;
		}
	}
	$rowoutput .= "</table></td></tr>\n";

	return $rowoutput;
}
function render_merit_spend_table($type, $list, $allxpdata, $columns) {

	$levelsdata    = $_REQUEST[$type . '_level'];

	
	$multipleonce = array();
	$colspan = 3;
	$grp = "";
	$col = 0;
	$rowoutput = "";
	if (count($list)>0) {
		$id = 0;
		foreach ($list as $merit) {
		
			// start column / new column
			if (isset($merit->grp)) {
				if ($grp != $merit->grp) {
					if (empty($grp)) {
						$rowoutput .= "<tr><td class='gvxp_col'><table><tr><th colspan=$colspan>{$merit->grp}</th></tr>";
						$col++;
					} 
					elseif ($col == $columns) {
						$rowoutput .= "</table></td></tr><tr><td class='gvxp_col'><table><tr><th colspan=$colspan>{$merit->grp}</th></tr>";
						$col = 1;
					}
					else {
						$rowoutput .= "</table></td><td class='gvxp_col'><table><tr><th colspan=$colspan>{$merit->grp}</th></tr>";
						$col++;
					}
					$grp = $merit->grp;
				}
			}
		
			if (array_key_exists($merit->item_id, $allxpdata)) {
				//loop through array
				foreach ($allxpdata[$merit->item_id] as $xpdatarow) {
					//echo "<li>$id : {$xpdatarow->name} : {$xpdatarow->comment}</li>";
					$rowoutput .= render_merits_row($type, $id, $xpdatarow, $levelsdata);
					$id++;
					if ($merit->MULTIPLE == 'Y' 
						&& !array_key_exists($merit->item_id,$multipleonce)
						&& $merit->level >= 0 
						&& $merit->XP_COST > 0
						) {
						//echo "<li>$id : {$merit->name} : {$merit->comment}</li>";
						$multipleonce[$merit->item_id] = 1;
						$rowoutput .= render_merits_row($type, $id, $merit, $levelsdata);
						$id++;
					}
				}
			} else {
				if ($merit->VISIBLE == 'Y' && $merit->level >= 0 && $merit->XP_COST > 0 ) {
					//echo "<li>$id : {$merit->name} : {$merit->comment}</li>";
					$rowoutput .= render_merits_row($type, $id, $merit, $levelsdata);
					$id++;
				}
			}
			
		}
	}
	$rowoutput .= "</table></td></tr>\n";

	return $rowoutput;
}

function render_merits_row ($type, $id, $xpdata, $levelsdata) {

	$fulldoturl    = plugins_url( 'gvlarp-character/images/xpdot.jpg' );
	$emptydoturl   = plugins_url( 'gvlarp-character/images/viewemptydot.jpg' );
	$pendingdoturl = plugins_url( 'gvlarp-character/images/pendingdot.jpg' );

	$rowoutput = "";

	// Hidden fields
	$rowoutput .= "<tr style='display:none'><td>\n";
	$rowoutput .= "<input type='hidden' name='{$type}_curr[" . $id . "]'    value='" . $xpdata->level . "' >\n";
	$rowoutput .= "<input type='hidden' name='{$type}_itemid[" . $id . "]'  value='" . $xpdata->item_id . "' >\n";
	$rowoutput .= "<input type='hidden' name='{$type}_id[" . $id . "]'      value='" . $xpdata->id . "' >\n";
	$rowoutput .= "<input type='hidden' name='{$type}_name[" . $id . "]'    value='" . $xpdata->name . "' >\n";
	$rowoutput .= "<input type='hidden' name='{$type}_spec_at[" . $id . "]' value='" . $xpdata->has_specialisation . "' >\n";
	$rowoutput .= "<input type='hidden' name='{$type}_spec[" . $id . "]'    value='" . $xpdata->comment . "' >";
	$rowoutput .= "</td></tr>\n";
	
	
	//dots row
	
	$xpcost = $xpdata->XP_COST;
	$rowoutput .= "<tr><th class='gvthleft'><span>(Level {$xpdata->level}) {$xpdata->name}";
	if ($xpdata->comment)
		$rowoutput .= " - {$xpdata->comment}";
	$rowoutput .= "</span></th>";
	
	if ($xpdata->has_pending)
		$rowoutput .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td>";
	elseif ($xpdata->level < 0)  // flaw
		if($xpcost) {
			$comment    = "Buy off level " . ($xpdata->level * -1) . " Flaw {$xpdata->name}";
			$rowoutput .= "<td class='gvxp_checkbox'>";
			$rowoutput .= "<input type='hidden'   name='{$type}_cost[" . $id . "]'    value='" . $xpcost . "' >";
			$rowoutput .= "<input type='hidden'   name='{$type}_comment[" . $id . "]' value='$comment' >";
			$rowoutput .= "<input type='CHECKBOX' name='{$type}_level[" . $id . "]'   value='{$xpdata->level}' ";
			if (isset($levelsdata[$id]))
				$rowoutput .= "checked";
			$rowoutput .= ">";
			$rowoutput .= "</td>";
		} else
			$rowoutput .= "<td></td>";
	else
		if ($xpdata->id)
			$rowoutput .= "<td></td>";
		else {
			$comment    = "Buy level {$xpdata->level} Merit {$xpdata->name}";
			$rowoutput .= "<td class='gvxp_checkbox'>";
			$rowoutput .= "<input type='hidden'   name='{$type}_cost[" . $id . "]'    value='" . $xpcost . "' >";
			$rowoutput .= "<input type='hidden'   name='{$type}_comment[" . $id . "]' value='$comment' >";
			$rowoutput .= "<input type='CHECKBOX' name='{$type}_level[" . $id . "]'   value='{$xpdata->level}' ";
			if (isset($levelsdata[$id]))
				$rowoutput .= "checked";
			$rowoutput .= ">";
			$rowoutput .= "</td>";
		} 
		
	$xpcost = $xpdata->XP_COST ? "(" . $xpdata->XP_COST . " XP)" : "";
	if ($xpdata->has_pending)
		$rowoutput .= "<td class='gvcol_cost'><input class='gvxp_clear' type='submit' name=\"{$type}_cancel[{$xpdata->pending_id}]\" value=\"Clear\"></td>";
	elseif ($xpdata->id && $xpdata->level >= 0)
		$rowoutput .= "<td class='gvcol_cost'></td>";
	else
		$rowoutput .= "<td class='gvcol_cost'>$xpcost</td>";
	$rowoutput .= "</tr>\n";

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
		if (!empty($level))
			$spend += $costs[$id];
	}
	//print_r($costs);
	//print_r($_REQUEST[$type . '_level']);
	
	return $spend;
	
}

function render_ritual_row($xpdata, $pending, $levelsdata,
						$training, $trainerrors, $traindefault, $max2display,
						$pendingdoturl) {

	$id = $xpdata->item_id;

	$output = "";
    $output .= "<tr style='display:none'><td>\n";
	$output .= "<input type='hidden' name='ritual_curr[" . $id . "]' value='" . $xpdata->level . "' >\n";
	$output .= "<input type='hidden' name='ritual_itemid[" . $id . "]' value='" . $xpdata->item_id . "' >\n";
	$output .= "<input type='hidden' name='ritual_new[" . $id . "]' value='" . ($xpdata->id == 0) . "' >\n";
    $output .= "</td></tr>\n";
    $output .= "<tr>\n";
	/* Name */
	$output .= "<th colspan=2 class='gvthleft'>" . $xpdata->name . "\n";
	$output .= " </th>\n";

	$pendinglvl = pending_level($pending, $xpdata->id, $xpdata->item_id);

	$radiogroup = "ritual_level[" . $id . "]";
	$radiovalue = $xpdata->level;
	
	/* echo "<p>id: $id<p>";
	print_r($levelsdata); */

	for ($i=1;$i<=$max2display;$i++){
		if ($i == $xpdata->level) {
			if ($pendinglvl) {
				$output .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td><td>&nbsp;</td>";
			} else {
				$output .= "<td class='gvxp_radio'><input type='RADIO' name='$radiogroup' value='$radiovalue' ";
				if (isset($levelsdata[$id]))
					$output .= "checked";
				$output .= ">";
				
				$output .= "<input type='hidden' name='ritual_cost_" . $i . "[" . $id . "]' value='" . $xpdata->cost . "' >";
				$output .= "<input type='hidden' name='ritual_comment_" . $i . "[" . $id . "]' value='Learn Ritual " . $xpdata->name . "' ></td>";
				$output .= "</td>";

				$output .= "<td>" . $xpdata->cost . "</td>";
			}
		} else {
			$output .= "<td>&nbsp;</td><td>&nbsp;</td>";
		}
	}

	if ($pendinglvl)
		$output .= "<td class='gvxp_radio'>&nbsp;</td>"; /* no change dot */
	else
		$output .= "<td class='gvxp_radio'><input type='RADIO' name='ritual_level[{$id}]' value='0' selected='selected'></td>"; /* no change dot */
	
	/* no training note if you cannot buy */
	$trainingString = isset($training[$id]) ? $training[$id] : $traindefault;
	$output .= "<td";
	if ($trainerrors[$id])
		$output .= " class='gvcol_error'";
	if ($pendinglvl)
		$output .= ">" . pending_training($pending, $xpdata->id, $xpdata->item_id) . "</td></tr></td>";
	else
		$output .= "><input type='text'  name='ritual_training[" . $id . "]' value='" . $trainingString ."' size=30 maxlength=160 /></td></tr></td>";


	
	return $output;
}

function save_to_pending ($type, $table, $itemtable, $itemidname, $playerID, $characterID) {
	global $wpdb;

	$newid = "";

	$ids             = $_REQUEST[$type . '_id'];
	$levels          = $_REQUEST[$type . '_level'];
	$specialisations = $_REQUEST[$type . '_spec'];
	$training        = $_REQUEST[$type . '_training'];
	$itemid          = $_REQUEST[$type . '_itemid'];
	$costlvls        = $_REQUEST[$type . '_cost'];
	$comments        = $_REQUEST[$type . '_comment'];
	
	//print_r($_REQUEST);
	//echo "<pre>";
	//print_r($itemid);
	//echo "</pre>";
	
	foreach ($levels as $index => $level) {
		
		if ($level) {
			$dataarray = array (
				'PLAYER_ID'       => $playerID,
				'CHARACTER_ID'    => $characterID,
				'CHARTABLE'       => $table,
				'CHARTABLE_ID'    => $ids[$index],
				'CHARTABLE_LEVEL' => $level,
				'AWARDED'         => Date('Y-m-d'),
				'AMOUNT'          => $costlvls[$index] * -1,
				'COMMENT'         => $comments[$index],
				'SPECIALISATION'  => $specialisations[$index],
				'TRAINING_NOTE'   => $training[$index],
				'ITEMTABLE'       => $itemtable,
				'ITEMNAME'        => $itemidname,
				'ITEMTABLE_ID'    => $itemid[$index]
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

function save_merit_to_pending ($type, $table, $itemtable, $itemidname, $playerID, $characterID) {
	global $wpdb;

	$newid = "";

	$ids             = $_REQUEST[$type . '_id'];
	$levels          = $_REQUEST[$type . '_level'];
	$specialisations = $_REQUEST[$type . '_spec'];
	$training        = $_REQUEST[$type . '_training'];
	$itemid          = $_REQUEST[$type . '_itemid'];
	$costlvls        = $_REQUEST[$type . '_cost'];
	$comments        = $_REQUEST[$type . '_comment'];
	
	//print_r($_REQUEST);
	//echo "<pre>";
	//print_r($itemid);
	//echo "</pre>";
	
	foreach ($levels as $index => $level) {
		
		if ($level) {
			$dataarray = array (
				'PLAYER_ID'       => $playerID,
				'CHARACTER_ID'    => $characterID,
				'CHARTABLE'       => $table,
				'CHARTABLE_ID'    => $ids[$index],
				'CHARTABLE_LEVEL' => $level,
				'AWARDED'         => Date('Y-m-d'),
				'AMOUNT'          => $costlvls[$index] * -1,
				'COMMENT'         => $comments[$index],
				'SPECIALISATION'  => $specialisations[$index],
				'TRAINING_NOTE'   => $training[$index],
				'ITEMTABLE'       => $itemtable,
				'ITEMNAME'        => $itemidname,
				'ITEMTABLE_ID'    => $itemid[$index]
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


/* function get_total_xp($characterID) {
	global $wpdb;
	
	$sql = "SELECT SUM(AMOUNT) as COST FROM " . GVLARP_TABLE_PREFIX . "PLAYER_XP WHERE CHARACTER_ID = %s";

	$sql = $wpdb->prepare($sql, $characterID);
	$result = $wpdb->get_results($sql);
	$xptotal = $result[0]->COST;
	
	return $xptotal;
}
 */
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
	
	foreach ($data as $pendingid => $button) {
		$sql = "DELETE FROM " . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND
				WHERE ID = %d";
		$sql = $wpdb->prepare($sql, $pendingid);
		/* echo "<p>SQL: $sql</p>"; */
		$result = $wpdb->get_results($sql);
	}
}

function validate_spends($playerID, $characterID, $docancel) {

	$xp_total   = get_total_xp($playerID, $characterID);
	$xp_pending = get_pending_xp($characterID);
	$xp_spent    = 0;
	$outputError = "";
	
	if (isset($_REQUEST['stat_level'])) $xp_spent += calc_submitted_spend('stat');
	if (isset($_REQUEST['skill_level'])) $xp_spent += calc_submitted_spend('skill');
	if (isset($_REQUEST['disc_level'])) $xp_spent += calc_submitted_spend('disc');
	if (isset($_REQUEST['path_level'])) $xp_spent += calc_submitted_spend('path');
	if (isset($_REQUEST['ritual_level'])) $xp_spent += calc_submitted_spend('ritual');
	if (isset($_REQUEST['merit_level'])) $xp_spent += calc_submitted_spend('merit');
	
	if ($xp_spent > ($xp_total - $xp_pending)) {
		$outputError .= "<p>You don't have enough experience left</p>";
	}
	
	if (!$xp_spent)
		if ($docancel)
			$outputError .= "<p>Experience spend has been cleared</p>";
		else
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

	if (isset($_REQUEST['skill_level'])) {
		$spec_at      = $_REQUEST['skill_spec_at'];
		$skilllevels   = $_REQUEST['skill_level'];
		$skilltraining = $_REQUEST['skill_training'];
		$skill_specialisations = $_REQUEST['skill_spec'];
		if (isset($skill_specialisations))
			foreach ($skill_specialisations as $id => $specialisation) {
				if ($spec_at[$id] <= $skilllevels[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$skill_spec_error[$id] = 1;
				}
			}
		
		if (count($skill_spec_error))
			$outputError .= "<p>Please fix missing or invalid <a href='#gvid_xpst_skill'>Ability</a> specialisations</p>";
			
		foreach ($skilltraining as $id => $trainingnote) {
			if ($skilllevels[$id] &&
				($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
				$skill_train_error[$id] = 1;
			}
		}
		if (count($skill_train_error))
			$outputError .= "<p>Please fix missing <a href='#gvid_xpst_skill'>Ability</a> training notes</p>";

	}
	if (isset($_REQUEST['disc_level'])) {
		$levels   = $_REQUEST['disc_level'];
		$training = $_REQUEST['disc_training'];
					
		foreach ($training as $id => $trainingnote) {
			if ($levels[$id] &&
				($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
				$disc_train_error[$id] = 1;
			}
		}
		if (count($disc_train_error))
			$outputError .= "<p>Please fix missing <a href='#gvid_xpst_disc'>Discipline</a> training notes</p>";

	}
	if (isset($_REQUEST['path_level'])) {
		$levels   = $_REQUEST['path_level'];
		$training = $_REQUEST['path_training'];
					
		foreach ($training as $id => $trainingnote) {
			if ($levels[$id] &&
				($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
				$path_train_error[$id] = 1;
			}
		}
		if (count($path_train_error))
			$outputError .= "<p>Please fix missing <a href='#gvid_xpst_path'>Path</a> training notes</p>";

	}
	if (isset($_REQUEST['ritual_level'])) {
		$levels   = $_REQUEST['ritual_level'];
		$training = $_REQUEST['ritual_training'];
					
		foreach ($training as $id => $trainingnote) {
			if ($levels[$id] &&
				($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
				$ritual_train_error[$id] = 1;
			}
		}
		if (count($ritual_train_error))
			$outputError .= "<p>Please fix missing <a href='#gvid_xpst_ritual'>Ritual</a> training notes</p>";

	}
	if (isset($_REQUEST['merit_level'])) {
		$levels   = $_REQUEST['merit_level'];
		$training = $_REQUEST['merit_training'];
		$has_spec = $_REQUEST['merit_spec_at'];
		$specialisations = $_REQUEST['merit_spec'];
					
		if (isset($specialisations))
			foreach ($specialisations as $id => $specialisation) {
				if ($has_spec[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$merit_spec_error[$id] = 1;
				}
			}
		
		if (count($merit_spec_error))
			$outputError .= "<p>Please fix missing or invalid <a href='#gvid_xpst_merit'>Merit of Flaw</a> specialisations</p>";

		foreach ($training as $id => $trainingnote) {
			if ($levels[$id] &&
				($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
				$merit_train_error[$id] = 1;
			}
		}
		if (count($merit_train_error))
			$outputError .= "<p>Please fix missing <a href='#gvid_xpst_ritual'>Merit or Flaw</a> training notes</p>";

	}

	
	return $outputError;
	
}
?>