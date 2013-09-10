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
	$character   = establishCharacter($character);
	$characterID = establishCharacterID($character);
	$playerID    = establishPlayerID($character);
	
	$table_prefix = GVLARP_TABLE_PREFIX;
	
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
	$character   = establishCharacter($character);
	$characterID = establishCharacterID($character);
	
	if (!isST()) {
		$visible = "N";
	}

	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	
	/* Exit if this character has been deleted */
	/* TO DO */
	
	/* how much XP is available to spend */
	$xp_total   = get_total_xp($characterID);
	$xp_pending = get_pending_xp($characterID);
	

	// Replacing all underscores with strings
	$defaultTrainingString = "Tell us how you are learning this";
	$defaultSpecialisation = "New Specialisation";
	$trainingNote          = $_POST['trainingNote'];
	$fulldoturl            = plugins_url( 'gvlarp-character/images/viewfulldot.jpg' );
	$emptydoturl           = plugins_url( 'gvlarp-character/images/viewemptydot.jpg' );
	$pendingdoturl         = plugins_url( 'gvlarp-character/images/pendingdot.jpg' );
	
	$sectioncontent        = array();
	$sectionheading        = array();
	$sectiontitle          = array();
	$sectioncols           = array();
	$sectionorder          = array('stat', 'skill', 'newskill', 'disc', 'newdisc', 'path', 'newpath',
								'ritual', 'merit', 'newmerit');

	$output    = "";
	$outputError = "";

	$postedCharacter = $_POST['character'];

	if ($_POST['GVLARP_FORM'] == "applyXPSpend"
		&& $postedCharacter != ""
		&& $_POST['xSubmit'] == "Spend XP") {
		
		$xp_spent = 0;
		
		if (isset($_REQUEST['stat_level'])) {
			$spec_at = $_REQUEST['stat_spec_at'];
			$statlevels = $_REQUEST['stat_level'];
			$stat_specialisations = $_REQUEST['stat_spec'];
			$stattraining = $_REQUEST['stat_training'];
			foreach ($stat_specialisations as $id => $specialisation) {
				if ($spec_at[$id] <= $statlevels[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$stat_spec_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_spend('stat');
			
			if (count($stat_spec_error))
				$outputError .= "<li>Please fix missing or invalid <a href='#gvid_xpst_stat'>Attribute</a> specialisations</li>";
				
			foreach ($stattraining as $id => $trainingnote) {
				if ($statlevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$stat_train_error[$id] = 1;
				}
			}
			if (count($stat_train_error))
				$outputError .= "<li>Please fix missing <a href='#gvid_xpst_stat'>Attribute</a> training notes</li>";
	
		}
		if (isset($_REQUEST['skill_level'])) {
			$spec_at = $_REQUEST['skill_spec_at'];
			$skilllevels = $_REQUEST['skill_level'];
			$skilltraining = $_REQUEST['skill_training'];
			$skill_specialisations = $_REQUEST['skill_spec'];
			foreach ($skill_specialisations as $id => $specialisation) {
				if ($spec_at[$id] <= $skilllevels[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$skill_spec_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_spend('skill');
			if (count($skill_spec_error))
				$outputError .= "<li>Please fix missing or invalid <a href='#gvid_xpst_skill'>Ability</a> specialisations</li>";
				
			foreach ($skilltraining as $id => $trainingnote) {
				if ($skilllevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$skill_train_error[$id] = 1;
				}
			}
			if (count($skill_train_error))
				$outputError .= "<li>Please fix missing <a href='#gvid_xpst_skill'>Ability</a> training notes</li>";
	
		}
		if (isset($_REQUEST['newskill_level'])) {
			$spec_at = $_REQUEST['newskill_spec_at'];
			$newskilllevels = $_REQUEST['newskill_level'];
			$newskill_specialisations = $_REQUEST['newskill_spec'];
			$newskilltraining = $_REQUEST['newskill_training'];
			foreach ($newskill_specialisations as $id => $specialisation) {
				if ($spec_at[$id] <= $newskilllevels[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$newskill_spec_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_spend('newskill');
			if (count($newskill_spec_error))
				$outputError .= "<li>Please fix missing or invalid <a href='#gvid_xpst_newskill'>New Ability</a> specialisations</li>";
				
			foreach ($newskilltraining as $id => $trainingnote) {
				if ($newskilllevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$newskill_train_error[$id] = 1;
				}
			}
			if (count($newskill_train_error))
				$outputError .= "<li>Please fix missing <a href='#gvid_xpst_newskill'>New Ability</a> training notes</li>";
	
		}
		if (isset($_REQUEST['disc_level'])) {
			$disclevels = $_REQUEST['disc_level'];
			$disctraining = $_REQUEST['disc_training'];
				
			foreach ($disctraining as $id => $trainingnote) {
				if ($disclevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$disc_train_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_spend('disc');
			if (count($disc_train_error))
				$outputError .= "<li>Please fix missing <a href='#gvid_xpst_disc'>Discipline</a> training notes</li>";
	
		}
		if (isset($_REQUEST['newdisc_level'])) {
			$newdisclevels = $_REQUEST['newdisc_level'];
			$newdisctraining = $_REQUEST['newdisc_training'];
				
			foreach ($newdisctraining as $id => $trainingnote) {
				if ($newdisclevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$newdisc_train_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_spend('newdisc');
			if (count($newdisc_train_error))
				$outputError .= "<li>Please fix missing <a href='#gvid_xpst_newdisc'>New Discipline</a> training notes</li>";
	
		}
		if (isset($_REQUEST['path_level'])) {
			$pathlevels = $_REQUEST['path_level'];
			$pathtraining = $_REQUEST['path_training'];
				
			foreach ($pathtraining as $id => $trainingnote) {
				if ($pathlevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$path_train_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_spend('path');
			if (count($path_train_error))
				$outputError .= "<li>Please fix missing <a href='#gvid_xpst_path'>Path</a> training notes</li>";
	
		}
		if (isset($_REQUEST['newpath_level'])) {
			$newpathlevels = $_REQUEST['newpath_level'];
			$newpathtraining = $_REQUEST['newpath_training'];
				
			foreach ($newpathtraining as $id => $trainingnote) {
				if ($newpathlevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$newpath_train_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_spend('newpath');
			if (count($newpath_train_error))
				$outputError .= "<li>Please fix missing <a href='#gvid_xpst_newpath'>New Path</a> training notes</li>";
	
		}
		if (isset($_REQUEST['ritual_level'])) {
			$rituallevels = $_REQUEST['ritual_level'];
			$ritualtraining = $_REQUEST['ritual_training'];
				
			foreach ($ritualtraining as $id => $trainingnote) {
				if ($rituallevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$ritual_train_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_spend('ritual');
			if (count($ritual_train_error))
				$outputError .= "<li>Please fix missing <a href='#gvid_xpst_ritual'>Ritual</a> training notes</li>";
		}
		if (isset($_REQUEST['merit_level'])) {
			$meritlevels = $_REQUEST['merit_level'];
			$merittraining = $_REQUEST['merit_training'];
				
			foreach ($merittraining as $id => $trainingnote) {
				if ($meritlevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$merit_train_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_merit_spend('merit');
			if (count($merit_train_error))
				$outputError .= "<p>Please fix missing <a href='#gvid_xpst_merit'>Flaw</a> training notes</p>";
		}
		if (isset($_REQUEST['newmerit_level'])) {
			$newmeritlevels = $_REQUEST['newmerit_level'];
			$newmerittraining = $_REQUEST['newmerit_training'];
			$newmerit_specialisations = $_REQUEST['newmerit_spec'];
			$newmerit_hasspec = $_REQUEST['newmerit_hasspec'];
			
			foreach ($newmerit_specialisations as $id => $specialisation) {
				if ($newmeritlevels[$id] && $newmerit_hasspec[$id] && ($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$newmerit_spec_error[$id] = 1;
				}
			}
			if (count($newmerit_spec_error))
				$outputError .= "<li>Please fix missing or invalid <a href='#gvid_xpst_newmerit'>Merit</a> specialisations</li>";
				
				
			foreach ($newmerittraining as $id => $trainingnote) {
				if ($newmeritlevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$newmerit_train_error[$id] = 1;
				}
			}
			$xp_spent += calc_submitted_merit_spend('newmerit');
			if (count($newmerit_train_error))
				$outputError .= "<p>Please fix missing <a href='#gvid_xpst_newmerit'>Merit</a> training notes</p>";
		}
		
		if (isset($_REQUEST['stat_cancel']))     cancel_pending($_REQUEST['stat_cancel']);
		if (isset($_REQUEST['skill_cancel']))    cancel_pending($_REQUEST['skill_cancel']);
		if (isset($_REQUEST['newskill_cancel'])) cancel_pending($_REQUEST['newskill_cancel']);
		if (isset($_REQUEST['disc_cancel']))     cancel_pending($_REQUEST['disc_cancel']);
		if (isset($_REQUEST['newdisc_cancel']))  cancel_pending($_REQUEST['newdisc_cancel']);
		if (isset($_REQUEST['path_cancel']))     cancel_pending($_REQUEST['path_cancel']);
		if (isset($_REQUEST['newpath_cancel']))  cancel_pending($_REQUEST['newpath_cancel']);
		if (isset($_REQUEST['ritual_cancel']))   cancel_pending($_REQUEST['ritual_cancel']);
		if (isset($_REQUEST['merit_cancel']))    cancel_pending($_REQUEST['merit_cancel']);
		if (isset($_REQUEST['newmerit_cancel'])) cancel_pending($_REQUEST['newmerit_cancel']);
		
		/* check you have enough XP left */
		if ($xp_spent > ($xp_total - $xp_pending)) {
			$outputError .= "<li>You don't have enough experience left</li>";
		}
		
		
		if ($outputError == "") {
		
			$output = "<p>" . doPendingXPSpend($postedCharacter) . "</p>";
			
			/* clear new spends */
			$newskilllevels  = array();
			$newdisclevels   = array();
			$newpathlevels   = array();
			$newmeritlevels  = array();
			
			$output .= "<p>$xp_spent experience spent</p>";
			
		} else 
			$output .= "<div class='gvxp_error'>$outputError</div>";

	}
	$xp_pending = get_pending_xp($characterID); /* update pending after spend */
	$outputXP = "<p class='gvxp_xpstatus'>You have $xp_total experience in total with $xp_pending points currently pending</p>";
	$output .= $outputXP;

	/* work out the maximum ratings for this character based on generation */
	$maxRating     = 5;
	$maxDiscipline = 5;

	$sql = "SELECT gen.max_rating, gen.max_discipline
				FROM " . $table_prefix . "CHARACTER chara,
					 " . $table_prefix . "GENERATION gen
				WHERE chara.generation_id = gen.id
				  AND chara.ID = %s";

	$characterMaximums = $wpdb->get_results($wpdb->prepare($sql, $characterID));
	foreach ($characterMaximums as $charMax) {
		$maxRating = $charMax->max_rating;
		$maxDiscipline = $charMax->max_discipline;
	}

	/* Attributes 
	------------------------------------------------------------------------*/
	$sectiontitle['stat'] = "Attributes";
	/* get current stat levels */
	$sqlOutput = "";
	$sql = "SELECT stat.name, cha_stat.comment, cha_stat.level, cha_stat.id, 
				stat.specialisation_at spec_at, stat.ID as stat_id
				FROM " . $table_prefix . "CHARACTER_STAT cha_stat,
					 " . $table_prefix . "STAT stat
				WHERE cha_stat.STAT_ID      = stat.ID
				  AND cha_stat.CHARACTER_ID = %s
			   ORDER BY stat.ordering";
	$character_stats_xp = $wpdb->get_results($wpdb->prepare($sql, $characterID));

	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_stats_xp, $maxRating);
	$sectioncols['stat'] = $max2display;
	
	/* get pending stat levels */
	$stat_spends = get_pending($characterID, 'CHARACTER_STAT');

	foreach ($character_stats_xp as $stat_xp) {
		
		/* get costs per level */
		$stat_costs = get_xp_costs_per_level("STAT", $stat_xp->stat_id, $stat_xp->level);
		
		$sqlOutput .= render_spend_row("stat", $stat_xp, $stat_specialisations,
						$stat_spec_error, $stat_spends, $max2display, $maxRating,
						$stat_costs, $statlevels, $stattraining, $stat_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl);
		
		
		
	}
	
	$sectioncontent['stat'] = $sqlOutput;

	$sqlOutput = "<tr><th class=\"gvthead gvcol_1\">Name</th><th class='gvthead gvcol_2'>Specialisation</th>";
	for($i=1;$i<=$max2display;$i++) {
		$sqlOutput .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
	}
	$sqlOutput .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">Clear</th>";
	$sqlOutput .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
	$sectionheading['stat'] = $sqlOutput;
			
	
	/* Abilities - current
	------------------------------------------------------------*/
	$sectiontitle['skill'] = "Current Abilities";
	/* current skills */
	$sql = "SELECT skill.name, cha_skill.comment, cha_skill.level, cha_skill.id,
				skill.specialisation_at spec_at, skill.ID as item_id
			FROM
				" . $table_prefix . "CHARACTER_SKILL cha_skill,
				" . $table_prefix . "SKILL skill
			WHERE
				cha_skill.SKILL_ID = skill.ID
				AND cha_skill.CHARACTER_ID = %s
			ORDER BY skill.name ASC";
	$sql = $wpdb->prepare($sql, $characterID);
	$character_skills_xp = $wpdb->get_results($sql);
			
	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_skills_xp, $maxRating);
	$sectioncols['skill'] = $max2display;
	
	/* get pending levels */
	$skill_spends = get_pending($characterID, 'CHARACTER_SKILL');
	
	$sqlOutput = "";
	foreach ($character_skills_xp as $skillxp) {

		/* get costs per level */
		$skill_costs = get_xp_costs_per_level("SKILL", $skillxp->item_id, $skillxp->level);
		
		$sqlOutput .= render_spend_row("skill", $skillxp, $skill_specialisations,
						$skill_spec_error, $skill_spends, $max2display, $maxRating,
						$skill_costs, $skilllevels, $skilltraining, $skill_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl);
		
		
		
	}
	
	$sectioncontent['skill'] = $sqlOutput;
	
	$sqlOutput = "<tr><th class=\"gvthead gvcol_1\">Name</th><th class='gvthead gvcol_2'>Specialisation</th>";
	for($i=1;$i<=$max2display;$i++) {
		$sqlOutput .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
	}
	$sqlOutput .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">Clear</th>";
	$sqlOutput .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
	$sectionheading['skill'] = $sqlOutput;
	
	/* Abilities - new / multiple
	------------------------------------------------------------*/
	$sectiontitle['newskill'] = "New Abilities";
	/* new skills */
	$sql = "SELECT skill.name, \"\" as comment, 0 as level, 0 as id,
				skill.specialisation_at spec_at, skill.ID as item_id
			FROM
				" . $table_prefix . "SKILL skill
				LEFT JOIN
					(
					SELECT DISTINCT SKILL_ID 
					FROM 
						" . $table_prefix . "CHARACTER_SKILL
					WHERE
						CHARACTER_ID = %d
					) as char_skill
				ON
					skill.ID = char_skill.SKILL_ID
			WHERE ";
	if (!isST())
		$sql .= "skill.VISIBLE = 'Y' AND ";
	$sql .= "(skill.MULTIPLE = 'Y' OR ISNULL(char_skill.SKILL_ID))
			ORDER BY skill.name ASC";
	$sql = $wpdb->prepare($sql, $characterID);
	/* echo "<pre>$sql</pre>"; */
	$character_newskills_xp = $wpdb->get_results($sql);

	/* get pending levels */
	$newskill_spends = get_pending($characterID, 'CHARACTER_SKILL');

	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_newskills_xp, $maxRating);
	$sectioncols['newskill'] = $max2display;

	$sqlOutput = "";
	foreach ($character_newskills_xp as $skillxp) {

		/* get costs per level */
		$newskill_costs = get_xp_costs_per_level("SKILL", $skillxp->item_id, $skillxp->level);
					
		$sqlOutput .= render_spend_row("newskill", $skillxp, $newskill_specialisations,
						$newskill_spec_error, $newskill_spends, $max2display, $maxRating,
						$newskill_costs, $newskilllevels, $newskilltraining, $newskill_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl);
		
	}
	$sectioncontent['newskill'] = $sqlOutput;
	$sectionheading['newskill'] = $sectionheading['skill'];
	
	/* Disciplines - current
	------------------------------------------------------------*/
	$sectiontitle['disc'] = "Current Disciplines";
	$sql = "SELECT discipline.name, cha_dis.comment, cha_dis.level, cha_dis.id,
				discipline.ID as item_id, characters.PRIVATE_CLAN_ID as clanid
			FROM
				" . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
				" . $table_prefix . "DISCIPLINE discipline,
				" . $table_prefix . "CHARACTER characters
			WHERE
				cha_dis.DISCIPLINE_ID = discipline.ID
				AND characters.ID = cha_dis.CHARACTER_ID
				AND cha_dis.CHARACTER_ID = %s
			ORDER BY discipline.name ASC";
	$sql = $wpdb->prepare($sql, $characterID);
	
	$character_discipline_xp = $wpdb->get_results($sql);
	/* echo "<pre>SQL: $sql\n";
	print_r($character_discipline_xp);
	echo "</pre>"; */
			
	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_discipline_xp, $maxDiscipline);
	$sectioncols['disc'] = $max2display;
	
	/* get pending levels */
	$disc_spends = get_pending($characterID, 'CHARACTER_DISCIPLINE');
	
	$sqlOutput = "";
	foreach ($character_discipline_xp as $discxp) {

		/* get costs per level */
		$disc_costs = get_discipline_xp_costs_per_level($discxp->item_id, $discxp->level, $discxp->clanid);
		
		$sqlOutput .= render_spend_row("disc", $discxp, $disc_specialisations,
						$disc_spec_error, $disc_spends, $max2display, $maxRating,
						$disc_costs, $disclevels, $disctraining, $disc_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl, 1);
	}
	
	$sectioncontent['disc'] = $sqlOutput;
	
	$sqlOutput = "<tr><th colspan=2 class=\"gvthead gvcol_1\">Name</th>";
	for($i=1;$i<=$max2display;$i++) {
		$sqlOutput .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
	}
	$sqlOutput .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">Clear</th>";
	$sqlOutput .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
	$sectionheading['disc'] = $sqlOutput;

	/* Disciplines - new
	------------------------------------------------------------*/
	$sectiontitle['newdisc'] = "New Disciplines";
	$sql = "SELECT discipline.name, \"\" as comment, 0 as level, 0 as id,
				discipline.ID as item_id
			FROM
				" . $table_prefix . "DISCIPLINE discipline
				LEFT JOIN
					(
					SELECT DISTINCT DISCIPLINE_ID 
					FROM 
						" . $table_prefix . "CHARACTER_DISCIPLINE
					WHERE
						CHARACTER_ID = %d
					) as char_disc
				ON
					discipline.ID = char_disc.DISCIPLINE_ID
			WHERE ISNULL(char_disc.DISCIPLINE_ID) ";
	if (!isST())
		$sql .= "AND discipline.VISIBLE = 'Y' ";
	$sql .= "ORDER BY discipline.name ASC";
	
	$sql = $wpdb->prepare($sql, $characterID);
	/* echo "<pre>$sql</pre>"; */
	$character_newdisc_xp = $wpdb->get_results($sql);

	/* get pending levels */
	$newdisc_spends = get_pending($characterID, 'CHARACTER_DISCIPLINE');

	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_newdiscs_xp, $maxDiscipline);
	$sectioncols['newdisc'] = $max2display;

	$clanid = establishPrivateClanID($characterID);
	
	$sqlOutput = "";
	foreach ($character_newdisc_xp as $discxp) {

		/* get costs per level */
		$newdisc_costs = get_discipline_xp_costs_per_level($discxp->item_id, $discxp->level, $clanid);
					
		$sqlOutput .= render_spend_row("newdisc", $discxp, $newdisc_specialisations,
						$newdisc_spec_error, $newdisc_spends, $max2display, $maxRating,
						$newdisc_costs, $newdisclevels, $newdisctraining, $newdisc_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl, 1);
		
	}
	$sectioncontent['newdisc'] = $sqlOutput;
	$sectionheading['newdisc'] = $sectionheading['disc'];


	/* Magik Paths
	------------------------------------------------------------*/
	$sectiontitle['path'] = "Current Paths";
	$sql = "SELECT path.name, cha_path.comment, cha_path.level, cha_path.id,
				path.ID as item_id
			FROM
				" . $table_prefix . "CHARACTER_PATH cha_path,
				" . $table_prefix . "PATH path
			WHERE
				cha_path.PATH_ID = path.ID
				AND cha_path.CHARACTER_ID = %s
			ORDER BY path.name ASC";
	$sql = $wpdb->prepare($sql, $characterID);
	/* echo "<pre>$sql</pre>"; */
	$character_path_xp = $wpdb->get_results($sql);
			
	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_path_xp, $maxRating);
	$sectioncols['path'] = $max2display;
	
	/* get pending levels */
	$path_spends = get_pending($characterID, 'CHARACTER_PATH');
	
	$sqlOutput = "";
	foreach ($character_path_xp as $pathxp) {

		/* get costs per level */
		$path_costs = get_xp_costs_per_level("PATH", $pathxp->item_id, $pathxp->level);
		
		$sqlOutput .= render_spend_row("path", $pathxp, $path_specialisations,
						$path_spec_error, $path_spends, $max2display, $maxRating,
						$path_costs, $pathlevels, $pathtraining, $path_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl, 1);
	}
	$sectioncontent['path'] = $sqlOutput;

	
	$sqlOutput = "<tr><th colspan=2 class=\"gvthead gvcol_1\">Name</th>";
	for($i=1;$i<=$max2display;$i++) {
		$sqlOutput .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
	}
	$sqlOutput .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">Clear</th>";
	$sqlOutput .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
	$sectionheading['path'] = $sqlOutput;

	/* Magik Paths - new
	------------------------------------------------------------*/
	$sectiontitle['newpath'] = "New Paths";
	$sql = "SELECT path.name, \"\" as comment, 0 as level, 0 as id,
				path.ID as item_id
			FROM
				" . $table_prefix . "CHARACTER_DISCIPLINE char_disc,
				" . $table_prefix . "DISCIPLINE discipline,
				" . $table_prefix . "PATH path
				LEFT JOIN
					(
					SELECT DISTINCT PATH_ID 
					FROM 
						" . $table_prefix . "CHARACTER_PATH
					WHERE
						CHARACTER_ID = %d
					) as char_path
				ON
					path.ID = char_path.PATH_ID
			WHERE
				path.DISCIPLINE_ID = discipline.ID
				AND char_disc.DISCIPLINE_ID = discipline.ID
				AND char_disc.CHARACTER_ID = %s
				AND ISNULL(char_path.PATH_ID) ";
	if (!isST())
		$sql .= "AND path.VISIBLE = 'Y' ";
	$sql .= "ORDER BY path.name ASC";
	
	$sql = $wpdb->prepare($sql, $characterID, $characterID);
	$character_newpath_xp = $wpdb->get_results($sql);

	/* get pending levels */
	$newpath_spends = get_pending($characterID, 'CHARACTER_PATH');

	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_newpath_xp, $maxRating);
	$sectioncols['newpath'] = $max2display;

	$sqlOutput = "";
	foreach ($character_newpath_xp as $pathxp) {

		/* get costs per level */
		$newpath_costs = get_xp_costs_per_level("PATH", $pathxp->item_id, $pathxp->level);
					
		$sqlOutput .= render_spend_row("newpath", $pathxp, $newpath_specialisations,
						$newpath_spec_error, $newpath_spends, $max2display, $maxRating,
						$newpath_costs, $newpathlevels, $newpathtraining, $newpath_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl, 1);
		
	}
	$sectioncontent['newpath'] = $sqlOutput;
	$sectionheading['newpath'] = $sectionheading['path'];


	/* Rituals
	------------------------------------------------------------*/
	$sectiontitle['ritual'] = "Rituals";
	$sql = "SELECT ritual.name, \"\" as comment, ritual.level, 0 as id,
				ritual.ID as item_id, ritual.cost
			FROM
				" . $table_prefix . "CHARACTER_DISCIPLINE char_disc,
				" . $table_prefix . "DISCIPLINE discipline,
				" . $table_prefix . "RITUAL ritual
				LEFT JOIN
					(
					SELECT DISTINCT RITUAL_ID 
					FROM 
						" . $table_prefix . "CHARACTER_RITUAL
					WHERE
						CHARACTER_ID = %d
					) as char_ritual
				ON
					ritual.ID = char_ritual.RITUAL_ID
			WHERE
				ritual.DISCIPLINE_ID = discipline.ID
				AND char_disc.DISCIPLINE_ID = discipline.ID
				AND char_disc.CHARACTER_ID = %s
				AND ritual.level <= char_disc.level
				AND ISNULL(char_ritual.RITUAL_ID) ";
	if (!isST())
		$sql .= "AND ritual.VISIBLE = 'Y' ";
	$sql .= "ORDER BY ritual.level ASC, ritual.name ASC";
	
	$sql = $wpdb->prepare($sql, $characterID, $characterID);
	/* echo "<pre>SQL: $sql</pre>"; */
	$character_ritual_xp = $wpdb->get_results($sql);
	
	$sectioncols['ritual'] = 5;

	/* print_r($character_ritual_xp); */
	/* get pending levels */
	$ritual_spends = get_pending($characterID, 'CHARACTER_RITUAL');

	$sqlOutput = "";
	foreach ($character_ritual_xp as $ritualxp) {

		$sqlOutput .= render_ritual_row($ritualxp, $ritual_spends,
						$rituallevels, $ritualtraining, $ritual_train_error,
						$defaultTrainingString, 5, $pendingdoturl);
		
	}
	$sectioncontent['ritual'] = $sqlOutput;

	$sqlOutput = "<tr><th colspan=2 class=\"gvthead gvcol_1\">Name</th>";
	for($i=1;$i<=5;$i++) {
		$sqlOutput .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
	}
	$sqlOutput .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">Clear</th>";
	$sqlOutput .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
	$sectionheading['ritual'] = $sqlOutput;

	/* Flaws - current that can be bought off (can't buy off current merits)
	------------------------------------------------------------------------*/
	$sectiontitle['merit'] = "Flaws";
	$sql = "SELECT merit.name, cha_merit.comment, cha_merit.level, cha_merit.id,
				merit.ID as item_id, merit.xp_cost
			FROM
				" . $table_prefix . "CHARACTER_MERIT cha_merit,
				" . $table_prefix . "MERIT merit
			WHERE
				cha_merit.MERIT_ID = merit.ID
				AND merit.XP_COST > 0
				AND cha_merit.level < 0
				AND cha_merit.CHARACTER_ID = %s
			ORDER BY merit.name ASC";
	
	$sql = $wpdb->prepare($sql, $characterID, $characterID);
	/* echo "<pre>SQL: $sql</pre>";  */
	$character_merit_xp = $wpdb->get_results($sql);

	$sectioncols['merit'] = 10;
	
	/* echo "<pre>";
	print_r($character_merit_xp);
	echo "</pre>"; */
	
	/* get pending levels */
	$merit_spends = get_pending($characterID, 'CHARACTER_MERIT');

	$sqlOutput = "";
	foreach ($character_merit_xp as $meritxp) {
		$sqlOutput .= render_merit_row('merit', $meritxp, $merit_spends, 
						$meritlevels, $merittraining, $merit_train_error,
						$defaultTrainingString, 10, $pendingdoturl);
		
	}
	$sectioncontent['merit'] = $sqlOutput;

	$sqlOutput = "<tr><th class=\"gvthead gvcol_1\">Name</th>"; 
	$sqlOutput .= "<th class=\"gvthead gvcol_2\">Specialisation</th>"; 
	
	$sqlOutput .= "<th colspan=4 class=\"gvthead\">Level</th>"; /* dot cols: dot1 & dot2 */
	$sqlOutput .= "<th colspan=3 class=\"gvthead\">Cost</th>";   /* dot cols: dot3 & 1/2 of dot4 */
	
	$sqlOutput .= "<th colspan=" . ($max2display * 2 - 7) . " class=\"gvthead\">&nbsp;</th>";   /* dot cols: dot5+ */
	
	$sqlOutput .= "<th class=\"gvthead gvcol_radiohead\">Clear</th>";
	$sqlOutput .= "<th class=\"gvthead\">Training Notes</th></tr>";
	$sectionheading['merit'] = $sqlOutput;
	
	
	/* Merits - new ones that can be bought (can't buy flaws)
	------------------------------------------------------------------------*/
	$sectiontitle['newmerit'] = "Merits";
	$sql = "SELECT merit.name, \"\" as comment, merit.value as level, 0 as id,
				merit.ID as item_id, merit.xp_cost, merit.has_specialisation
			FROM
				" . $table_prefix . "MERIT merit
				LEFT JOIN
					(
					SELECT DISTINCT MERIT_ID 
					FROM 
						" . $table_prefix . "CHARACTER_MERIT
					WHERE
						CHARACTER_ID = %d
					) as char_merit
				ON
					merit.ID = char_merit.MERIT_ID
			WHERE
				merit.XP_COST > 0
				AND merit.value >= 0
				AND (ISNULL(char_merit.MERIT_ID) OR merit.MULTIPLE = 'Y') ";
	if (!isST())
		$sql .= "AND merit.VISIBLE = 'Y' ";
	$sql .= "ORDER BY merit.name ASC";
	
	$sql = $wpdb->prepare($sql, $characterID, $characterID);
	$character_newmerit_xp = $wpdb->get_results($sql);

	/* echo "<pre>SQL: $sql</pre>"; 
	echo "<pre>";
	print_r($character_merit_xp);
	echo "</pre>"; */
	
	/* get pending levels */
	$newmerit_spends = get_pending($characterID, 'CHARACTER_MERIT');

	$sqlOutput = "";
	foreach ($character_newmerit_xp as $newmeritxp) {
	
		$sqlOutput .= render_merit_row('newmerit', $newmeritxp, $newmerit_spends, 
						$newmeritlevels, $newmerittraining, $newmerit_train_error,
						$defaultTrainingString, $max2display, $pendingdoturl, $newmerit_specialisations,
						$newmerit_spec_error);
		
	}
	$sectioncontent['newmerit'] = $sqlOutput;
	$sectionheading['newmerit'] = $sectionheading['merit'];

	/* DISPLAY TABLES */
	$jumpto = array();
	$i = 0;
	foreach ($sectionorder as $section) {
		if ($sectiontitle[$section] && $sectioncontent[$section]) {
			$jumpto[$i++] .= "<a href='#gvid_xpst_$section' class='gvxp_jump'>" . $sectiontitle[$section] . "</a>";
		}
	}
	$outputJump = "<p>Jump to section: " . implode(" | ", $jumpto) . "</p>";
	
	$output .= "<div class='gvplugin' id=\"gvid_xpst\">\n";
	$output .= "<form name=\"SPEND_XP_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">\n";
	
	foreach ($sectionorder as $section) {
	
		$output .= "<h4 class='gvxp_head' id='gvid_xpst_$section'>" . $sectiontitle[$section] . "</h4>\n";
			
		if ($sectioncontent[$section]) {
			$output .= "$outputJump\n";
			$output .= "<table class='gvplugin'>\n";
			$output .= $sectionheading[$section];
			$output .= $sectioncontent[$section];
			$output .= "</table>\n";
			$output .= "<input class='gvxp_submit' type='submit' name=\"xSubmit\" value=\"Spend XP\">\n";
		} else {
			$output .= "<p>None available</p>\n";
		}
		
	}


	if ($_POST['GVLARP_CHARACTER'] != "") {
		$output .= "<input type='HIDDEN' name=\"GVLARP_CHARACTER\" value=\"" . $_POST['GVLARP_CHARACTER'] . "\" />\n";
	}

	$output .= "<input type='HIDDEN' name=\"character\" value=\"" . $character . "\">\n";
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

function get_pending($characterID, $table) {
	global $wpdb;

	$sql = "SELECT ID, CHARTABLE_ID, CHARTABLE_LEVEL, ITEMTABLE, ITEMTABLE_ID, TRAINING_NOTE
			FROM " . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND
			WHERE
				CHARACTER_ID = %s
				AND CHARTABLE = '%s'";
	$sql = $wpdb->prepare($sql, $characterID, $table);
	/* echo "<p>SQL: $sql</p>"; */
	$result = $wpdb->get_results($sql);

	/* print_r($result); */
	
	/* foreach ($result as $spend)
		$output[$spend->CHARTABLE_ID] = $spend->CHARTABLE_LEVEL; */
	
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

	for ($i=1;$i<=10;$i++) $costlvls[$i] = $_REQUEST[$type . '_cost_' . $i];
	foreach ($_REQUEST[$type . '_level'] as $id => $level) {
		if ($level)
			$spend .= $costlvls[$level][$id];
	}
	return $spend;
	
}
function calc_submitted_merit_spend($type) {
	$spend = 0;

	foreach ($_REQUEST[$type . '_level'] as $id => $level) {
		if ($level)
			$spend .= $_REQUEST[$type . '_cost'][$id];
	}
	return $spend;
	
}

function render_spend_row($type, $xpdata, $specdata, $specerrors, $pending, 
						$max2display, $maxRating, $typecosts, $levelsdata,
						$training, $trainerrors, $traindefault,
						$fulldoturl, $emptydoturl, $pendingdoturl, $nospec = 0) {

	if ($xpdata->id == 0)
		$id = $xpdata->item_id;
	else
		$id = $xpdata->id;

	$output = "";
    $output .= "<tr style='display:none'><td>\n";
	$output .= "<input type='hidden' name='{$type}_spec_at[" . $id . "]' value='" . $xpdata->spec_at . "' >";
	$output .= "<input type='hidden' name='{$type}_curr[" . $id . "]' value='" . $xpdata->level . "' >\n";
	$output .= "<input type='hidden' name='{$type}_itemid[" . $id . "]' value='" . $xpdata->item_id . "' >\n";
	$output .= "<input type='hidden' name='{$type}_new[" . $id . "]' value='" . ($xpdata->id == 0) . "' >\n";
    $output .= "</td></tr>\n";
    $output .= "<tr>\n";
	/* Name */
	$output .= "<th ";
	if ($nospec || $xpdata->spec_at == 0)
		$output .= "colspan=2 ";
	$output .= " class='gvthleft'>" . $xpdata->name . "\n";
	$output .= " </th>\n";

	$pendinglvl = pending_level($pending, $xpdata->id, $xpdata->item_id);
	

	/* Specialisation */
	if (!$nospec && $xpdata->spec_at > 0) {
		$output .= "<td class='gvcol_2 gvcol_val";
		$spec = isset($specdata[$id]) ? $specdata[$id] : stripslashes($xpdata->comment);
		if ($specerrors[$id])
			$output .= " gvcol_error";
		if ($pendinglvl)
			$output .= "'>$spec";
		else
			$output .= "'><input type='text' name='${type}_spec[" . $id . "]' value='" . $spec . "' size=15 maxlength=60>";
		$output .= "</td>";
	} 
	

	$xpcost = 0;
	for ($i=1;$i<=$max2display;$i++) {
		$radiogroup = $type . "_level[" . $id . "]";
		$radiovalue = "$i";
		if ($i <= $xpdata->level) {
			$output .= "<td class='gvxp_dot'><img src='$fulldoturl'></td><td>&nbsp;</td>";
		} 
		elseif ($i > $maxRating) {
			$output .= "<td class='gvxp_dot'><img src='$emptydoturl'></td><td>&nbsp;</td>";
		}
		elseif ($pendinglvl)
			if ($pendinglvl >= $i)
				$output .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td><td>&nbsp;</td>";
			else
				$output .= "<td class='gvxp_dot'><img src='$emptydoturl'></td><td>&nbsp;</td>";
		else {
			$xpcost    = get_xp_cost($typecosts, $xpdata->level, $i);
			if (!$xpcost) 
				$output .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
			else {
				$output .= "<td class='gvxp_radio'><input type='RADIO' name='$radiogroup' value='$radiovalue' ";
				if (isset($levelsdata[$id]) && $i == $levelsdata[$id])
					$output .= "checked";
				$output .= ">";
				$output .= "<input type='hidden' name='${type}_cost_" . $i . "[" . $id . "]' value='" . $xpcost . "' >";
				$comment = $xpdata->name . " " . $xpdata->level . " > " . $i;
				$output .= "<input type='hidden' name='${type}_comment_" . $i . "[" . $id . "]' value='$comment' ></td>";
				$output .= "</td>";
			}
			$output .= "<td>" . ($xpcost ? $xpcost : "&nbsp;");"</td>";
		}
	}
	
	if ($pendinglvl)
		$output .= "<td class='gvxp_checkbox'><input type='CHECKBOX' name='{$type}_cancel[{$id}]' value='" . pending_id($pending, $xpdata->id, $xpdata->item_id) . "'></td>"; /* cancel spend */
	else
		$output .= "<td class='gvxp_radio'><input type='RADIO' name='{$type}_level[{$id}]' value='0' selected-'selected'></td>"; /* no change dot */
	
	/* no training note if you cannot buy */
	$trainingString = isset($training[$id]) ? $training[$id] : $traindefault;
	$output .= "<td";
	if ($trainerrors[$id])
		$output .= " class='gvcol_error'";
	if ($pendinglvl)
		$output .= ">" . pending_training($pending, $xpdata->id, $xpdata->item_id) . "</td></tr></td>";
	else
		$output .= "><input type='text'  name='{$type}_training[" . $id . "]' value='" . $trainingString ."' size=30 maxlength=160 /></td></tr></td>";


	
	return $output;
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
		$output .= "<td class='gvxp_checkbox'><input type='CHECKBOX' name='ritual_cancel[{$id}]' value='" . pending_id($pending, $xpdata->id, $xpdata->item_id) . "'></td>"; /* cancel spend */
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

	$spec_at         = $_REQUEST[$type . '_spec_at'];
	$levels          = $_REQUEST[$type . '_level'];
	$specialisations = $_REQUEST[$type . '_spec'];
	$training        = $_REQUEST[$type . '_training'];
	$itemid          = $_REQUEST[$type . '_itemid'];
	$isnew          = $_REQUEST[$type . '_new'];
	for ($i=1;$i<=10;$i++) $costlvls[$i] = $_REQUEST[$type . '_cost_' . $i];
	for ($i=1;$i<=10;$i++) $comments[$i] = $_REQUEST[$type . '_comment_' . $i];
			
	foreach ($levels as $id => $level) {
		
		if ($level) {
			$dataarray = array (
				'PLAYER_ID'       => $playerID,
				'CHARACTER_ID'    => $characterID,
				'CHARTABLE'       => $table,
				'CHARTABLE_ID'    => ($isnew[$id] ? 0 : $id),
				'CHARTABLE_LEVEL' => $level,
				'AWARDED'         => Date('Y-m-d'),
				'AMOUNT'          => $costlvls[$level][$id] * -1,
				'COMMENT'         => $comments[$level][$id],
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
				echo "<p style='color:red'><b>Error:</b> XP (";
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

	$levels     = $_REQUEST[$type . '_level'];
	$training   = $_REQUEST[$type . '_training'];
	$itemid     = $_REQUEST[$type . '_itemid'];
	$isnew      = $_REQUEST[$type . '_new'];
	$costs      = $_REQUEST[$type . '_cost' . $i];
	$comments   = $_REQUEST[$type . '_comment' . $i];
	$specs      = $_REQUEST[$type . '_spec' . $i];
			
	foreach ($levels as $id => $level) {
		
		if ($level) {
			$dataarray = array (
				'PLAYER_ID'       => $playerID,
				'CHARACTER_ID'    => $characterID,
				'CHARTABLE'       => $table,
				'CHARTABLE_ID'    => ($isnew[$id] ? 0 : $id),
				'CHARTABLE_LEVEL' => $level,
				'AWARDED'         => Date('Y-m-d'),
				'AMOUNT'          => $costs[$id] * -1,
				'COMMENT'         => $comments[$id],
				'SPECIALISATION'  => $specs[$id],
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
				echo "<p style='color:red'><b>Error:</b> XP (";
				$wpdb->print_error();
				echo ")</p>";
			} 
		}
	
	}	
	
	return $newid;
							
}


function render_merit_row($type, $xpdata, $pending, $levelsdata, $training, $trainerrors, 
						$traindefault, $max2display, $pendingdoturl, $specdata = array(), $specerrors = array()) {

	if ($xpdata->id == 0)
		$id = $xpdata->item_id;
	else
		$id = $xpdata->id;

	$output = "";
    $output .= "<tr style='display:none'><td>\n";
	$output .= "<input type='hidden' name='{$type}_itemid[" . $id . "]' value='" . $xpdata->item_id . "' >\n";
	$output .= "<input type='hidden' name='{$type}_new[" . $id . "]' value='" . ($xpdata->id == 0) . "' >\n";
	$output .= "<input type='hidden' name='{$type}_hasspec[" . $id . "]' value='" . $xpdata->has_specialisation . "' >\n";
    $output .= "</td></tr>\n";
    $output .= "<tr>\n";
	/* Name */
	$output .= "<th class='gvthleft'>" . $xpdata->name . "</th>\n";

	if ($xpdata->has_specialisation == 'Y') {
		$output .= "<td class='gvcol_2 gvcol_val";
		$spec = isset($specdata[$id]) ? $specdata[$id] : stripslashes($xpdata->comment);
		if ($specerrors[$id])
			$output .= " gvcol_error";
		if ($pendinglvl)
			$output .= "'>$spec";
		else
			$output .= "'><input type='text' name='${type}_spec[" . $id . "]' value='" . $spec . "' size=15 maxlength=60>";
		$output .= "</td>";
	} else {
		$output .= "<td>{$xpdata->comment}</td>";
	}

	$pendinglvl = pending_level($pending, $xpdata->id, $xpdata->item_id);
	
	$radiogroup = "{$type}_level[" . $id . "]";
	$radiovalue = $xpdata->level;
	$output .= "<td colspan=4 class='gvxp_value'>$radiovalue</td>";
	
	if ($pendinglvl) {
		$output .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td><td>&nbsp;</td>";
	} else {
		$output .= "<td class='gvxp_radio'><input type='RADIO' name='$radiogroup' value='$radiovalue' ";
		if (isset($levelsdata[$id]))
			$output .= "checked";
		$output .= ">";
		$output .= "<input type='hidden' name='{$type}_cost[" . $id . "]' value='" . $xpdata->xp_cost . "' >";
		$output .= "<input type='hidden' name='{$type}_comment[" . $id . "]' value='";
		if ($xpdata->level < 0)
			$output .= "Remove level " . $xpdata->level . " Flaw ";
		else
			$output .= "Add level " . $xpdata->level . " Merit ";
		$output .= $xpdata->name . "' ></td>";
		$output .= "<td>" . $xpdata->xp_cost . "</td>";
	}

	for ($i=4;$i<=$max2display;$i++)
		$output .= "<td colspan=2 class='gvxp_radio'>&nbsp;</td>";
	
	if ($pendinglvl)
		$output .= "<td class='gvxp_checkbox'><input type='CHECKBOX' name='{$type}_cancel[{$id}]' value='" . pending_id($pending, $xpdata->id, $xpdata->item_id) . "'></td>"; /* cancel spend */
	else
		$output .= "<td class='gvxp_radio'><input type='RADIO' name='{$type}_level[{$id}]' value='0' selected-'selected'></td>"; /* no change dot */
	
	/* no training note if you cannot buy */
	$trainingString = isset($training[$id]) ? $training[$id] : $traindefault;
	$output .= "<td";
	if ($trainerrors[$id])
		$output .= " class='gvcol_error'";
	if ($pendinglvl)
		$output .= ">" . pending_training($pending, $xpdata->id, $xpdata->item_id) . "</td></tr></td>";
	else
		$output .= "><input type='text'  name='{$type}_training[" . $id . "]' value='" . $trainingString ."' size=30 maxlength=160 /></td></tr></td>";


	
	return $output;
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
?>