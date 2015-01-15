<?php

function vtm_viewcharacter_content_filter($content) {

  if (is_page(vtm_get_stlink_page('viewCharSheet')))
		if (is_user_logged_in()) {
			$content .= vtm_get_viewcharacter_content();
		} else {
			$content .= "<p>You must be logged in to view this content.</p>";
		}
  // otherwise returns the database content
  return $content;
}

add_filter( 'the_content', 'vtm_viewcharacter_content_filter' );


function vtm_get_viewcharacter_content() {
	global $wpdb;

	$character   = vtm_establishCharacter('');
	$characterID = vtm_establishCharacterID($character);
	
	$mycharacter = new vtmclass_character();
	$mycharacter->load($characterID);
	
	$config = vtm_getConfig();
	$maxrating = $mycharacter->max_rating > 5 ? 10 : 5;

	if ($config->WEB_COLUMNS == 1)
		$divder = "<tr><td class='vtmhr'><hr /></td></tr>"; // divider
	else
		$divder = "<tr><td class='vtmhr' colspan=" . $config->WEB_COLUMNS . "><hr /></td></tr>"; // divider
	
	$content = "<div class=\"gvplugin\" id=\"csheet\">";
	
	//---- TOP CHARACTER INFO ----
	$c_tableleft = "<table><tbody>
		<tr><td class='vtmcol_key'>Character</td><td>". htmlentities($mycharacter->name) . "</td></tr>
		<tr><td class='vtmcol_key'>Domain</td><td>"   . htmlentities($mycharacter->domain) . "</td></tr>
		<tr><td class='vtmcol_key'>Sect</td><td>"     . htmlentities($mycharacter->sect) . "</td></tr>
		</tbody></table>";
	$c_tablemid = "<table><tbody>
		<tr><td class='vtmcol_key'>Clan</td><td>"        . htmlentities($mycharacter->private_clan) . "</td></tr>
		<tr><td class='vtmcol_key'>Public Clan</td><td>" . htmlentities($mycharacter->clan) . "</td></tr>
		<tr><td class='vtmcol_key'>Sire</td><td>"        . htmlentities($mycharacter->sire) . "</td></tr>
		</tbody></table>";
	if ($config->USE_NATURE_DEMEANOUR == 'Y') {
		$c_tableright = "<table><tbody>
			<tr><td class='vtmcol_key'>Generation</td><td>" . htmlentities($mycharacter->generation) . "</td></tr>
			<tr><td class='vtmcol_key'>Nature</td><td>" . htmlentities($mycharacter->nature) . "</td></tr>
			<tr><td class='vtmcol_key'>Demeanour</td><td>" . htmlentities($mycharacter->demeanour) . "</td></tr>
			</tbody></table>";
	} else {
		$c_tableright = "<table><tbody>
			<tr><td class='vtmcol_key'>Generation</td><td>" . htmlentities($mycharacter->generation) . "</td></tr>
			<tr><td class='vtmcol_key'>Date of Birth</td><td>" . htmlentities($mycharacter->date_of_birth) . "</td></tr>
			<tr><td class='vtmcol_key'>Date of Embrace</td><td>" . htmlentities($mycharacter->date_of_embrace) . "</td></tr>
			</tbody></table>";
	}
	
	$content .= "<table>\n";
	if ($config->WEB_COLUMNS == 3) {
		$content .= "<tr>
			<td class='vtm_colnarrow'>$c_tableleft</td>
			<td class='vtm_colnarrow'>$c_tablemid</td>
			<td class='vtm_colnarrow'>$c_tableright</td>
			</tr>";
	} else {
		$content .= "
			<tr><td class='vtm_colfull'>$c_tableleft</td></tr>
			<tr><td class='vtm_colfull'>$c_tablemid</td></tr>
			<tr><td class='vtm_colfull'>$c_tableright</td></tr>";
	}
	$content .= $divder;
	
	//---- ATTRIBUTES ----
	$physical = $mycharacter->getAttributes("Physical");
	$social   = $mycharacter->getAttributes("Social");
	$mental   = $mycharacter->getAttributes("Mental");
	
	$c_tableleft = "<table><tr><td colspan=3><h4>Physical</h4></td></tr>";
	for ($i=0;$i<3;$i++) {
		$statname = isset($physical[$i]->name)      ? $physical[$i]->name : '';
		$statspec = isset($physical[$i]->specialty) ? stripslashes($physical[$i]->specialty) : '';
		$statlvl  = isset($physical[$i]->level)     ? $physical[$i]->level : '';
		$max = max($statlvl, $maxrating);
		$c_tableleft .= "<tr>
				<td class='vtmcol_key'>$statname</td>
				<td class='vtmcol_spec'>$statspec </td>
				<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $statlvl) . "</td>
			</tr>";
	}
	$c_tableleft .= "</table>";
	$c_tablemid = "<table><tr><td colspan=3><h4>Social</h4></td></tr>";
	for ($i=0;$i<3;$i++) {
		$statname = isset($social[$i]->name)      ? $social[$i]->name : '';
		$statspec = isset($social[$i]->specialty) ? stripslashes($social[$i]->specialty) : '';
		$statlvl  = isset($social[$i]->level)     ? $social[$i]->level : '';
		$max = max($statlvl, $maxrating);
		$c_tablemid .= "<tr>
				<td class='vtmcol_key'>$statname</td>
				<td class='vtmcol_spec'>$statspec </td>
				<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $statlvl) . "</td>
			</tr>";
	}
	$c_tablemid .= "</table>";
	$c_tableright = "<table><tr><td colspan=3><h4>Mental</h4></td></tr>";
	for ($i=0;$i<3;$i++) {
		$statname = isset($mental[$i]->name)      ? $mental[$i]->name : '';
		$statspec = isset($mental[$i]->specialty) ? stripslashes($mental[$i]->specialty) : '';
		$statlvl  = isset($mental[$i]->level)     ? $mental[$i]->level : '';
		$max = max($statlvl, $maxrating);
		$c_tableright .= "<tr>
				<td class='vtmcol_key'>$statname</td>
				<td class='vtmcol_spec'>$statspec </td>
				<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $statlvl) . "</td>
			</tr>";
	}
	$c_tableright .= "</table>";


	if ($config->WEB_COLUMNS == 3) {
		$content .= "
			<tr><td colspan=3><h3>Attributes</h3></td></tr>
			<tr>
			<td class='vtm_colnarrow'>$c_tableleft</td>
			<td class='vtm_colnarrow'>$c_tablemid</td>
			<td class='vtm_colnarrow'>$c_tableright</td>
			</tr>";
	} else {
		$content .= "
			<tr><td><h3>Attributes</h3></td></tr>
			<tr><td class='vtm_colfull'>$c_tableleft</td></tr>
			<tr><td class='vtm_colfull'>$c_tablemid</td></tr>
			<tr><td class='vtm_colfull'>$c_tableright</td></tr>";
	}

	//---- ABILITIES ----
	$content .= $divder;

	$talent    = $mycharacter->getAbilities("Talents");
	$skill     = $mycharacter->getAbilities("Skills");
	$knowledge = $mycharacter->getAbilities("Knowledges");
	
	$c_tableleft = "<table><tr><td colspan=3><h4>Talents</h4></td></tr>";
	for ($i=0;$i<count($talent);$i++) {
		$max = max($talent[$i]->level, $maxrating);
		if ($talent[$i]->level > 0)
			$c_tableleft .= "<tr>
					<td class='vtmcol_key'>" . $talent[$i]->skillname               . "</td>
					<td class='vtmcol_spec'>" . stripslashes($talent[$i]->specialty) . "</td>
					<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $talent[$i]->level) . "</td>
				</tr>";
	}
	$c_tableleft .= "</table>";
	$c_tablemid = "<table><tr><td colspan=3><h4>Skills</h4></td></tr>";
	for ($i=0;$i<count($skill);$i++) {
		$max = max($skill[$i]->level, $maxrating);
		if ($skill[$i]->level > 0)
			$c_tablemid .= "<tr>
				<td class='vtmcol_key'>" . $skill[$i]->skillname               . "</td>
				<td class='vtmcol_spec'>" . stripslashes($skill[$i]->specialty) . "</td>
				<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $skill[$i]->level) . "</td>
			</tr>";
	}
	$c_tablemid .= "</table>";
	$c_tableright = "<table><tr><td colspan=3><h4>Knowledges</h4></td></tr>";
	for ($i=0;$i<count($knowledge);$i++) {
		$max = max($knowledge[$i]->level, $maxrating);
		if ($knowledge[$i]->level > 0)
			$c_tableright .= "<tr>
				<td class='vtmcol_key'>" . $knowledge[$i]->skillname               . "</td>
				<td class='vtmcol_spec'>" . stripslashes($knowledge[$i]->specialty) . "</td>
				<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $knowledge[$i]->level) . "</td>
			</tr>";
	}
	$c_tableright .= "</table>";
	
	if ($config->WEB_COLUMNS == 3) {
		$content .= "
			<tr><td colspan=3><h3>Abilities</h3></td></tr>
			<tr>
			<td class='vtm_colnarrow'>$c_tableleft</td>
			<td class='vtm_colnarrow'>$c_tablemid</td>
			<td class='vtm_colnarrow'>$c_tableright</td>
			</tr>";
	} else {
		$content .= "
			<tr><td><h3>Abilities</h3></td></tr>
			<tr><td class='vtm_colfull'>$c_tableleft</td></tr>
			<tr><td class='vtm_colfull'>$c_tablemid</td></tr>
			<tr><td class='vtm_colfull'>$c_tableright</td></tr>";
	}
	
	//---- BACKGROUND, DISCIPLINES AND OTHER TRAITS ----
	
	$content .= $divder;
	$backgrounds = $mycharacter->getBackgrounds();
	$disciplines = $mycharacter->getDisciplines();
	
	$sql = "SELECT NAME, PARENT_ID FROM " . VTM_TABLE_PREFIX . "SKILL_TYPE;";
	$allgroups = $wpdb->get_results($sql);	
	
	$secondarygroups = array();
	foreach ($allgroups as $group) {
		if ($group->PARENT_ID > 0)
			array_push($secondarygroups, $group->NAME);
	}	

	$secondary = array();
	foreach ($secondarygroups as $group)
			$secondary = array_merge($mycharacter->getAbilities($group), $secondary);	
	
	$c_tableleft = "<table><tr><td colspan=3><h4>Backgrounds</h4></td></tr>";
	for ($i=0;$i<count($backgrounds);$i++) {
		$max = max($backgrounds[$i]->level, $maxrating);
		$c_tableleft .= "<tr>
				<td class='vtmcol_key'>" . $backgrounds[$i]->background               . "</td>
				<td class='vtmcol_spec'>" . (!empty($backgrounds[$i]->sector) ?  $backgrounds[$i]->sector : stripslashes($backgrounds[$i]->comment)) . "</td>
				<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $backgrounds[$i]->level) . "</td>
			</tr>";
	}
	$c_tableleft .= "</table>";
	$c_tablemid = "<table><tr><td colspan=3><h4>Disciplines</h4></td></tr>";
	for ($i=0;$i<count($disciplines);$i++) {
		$max = max($disciplines[$i]->level, $maxrating);
		if ($disciplines[$i]->level > 0)
			$c_tablemid .= "<tr>
				<td class='vtmcol_key'>" . $disciplines[$i]->name               . "</td>
				<td class='vtmcol_spec'>&nbsp;</td>
				<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $disciplines[$i]->level) . "</td>
			</tr>";
	}
	// COMBO DISCIPLINES
	$combo = $mycharacter->combo_disciplines;
	foreach ($combo as $id => $disc) {
		if (!strstr($disc,"PENDING"))
			$c_tablemid .= "<tr><td colspan=3>$disc</td></tr>";
	}
	$c_tablemid .= "</table>";
	$c_tableright = "<table><tr><td colspan=3><h4>Other Traits</h4></td></tr>";
	for ($i=0;$i<count($secondary);$i++) {
		$max = max($secondary[$i]->level, $maxrating);
		if ($secondary[$i]->level > 0)
			$c_tableright .= "<tr>
				<td class='vtmcol_key'>" . $secondary[$i]->skillname               . "</td>
				<td class='vtmcol_spec'>" . stripslashes($secondary[$i]->specialty) . "</td>
				<td class='vtmdot_{$maxrating}'>" . vtm_numberToDots($max, $secondary[$i]->level) . "</td>
			</tr>";
	}
	$c_tableright .= "</table>";
	
	if ($config->WEB_COLUMNS == 3) {
		$content .= "
			<tr>
			<td class='vtm_colnarrow'>$c_tableleft</td>
			<td class='vtm_colnarrow'>$c_tablemid</td>
			<td class='vtm_colnarrow'>$c_tableright</td>
			</tr>";
	} else {
		$content .= "
			<tr><td class='vtm_colfull'>$c_tableleft</td></tr>$divder
			<tr><td class='vtm_colfull'>$c_tablemid</td></tr>$divder
			<tr><td class='vtm_colfull'>$c_tableright</td></tr>";
	}
	
	//---- MERITS, FLAWS, VIRTUES, WILLPOWER, PATH AND BLOOD ----
	$content .= $divder;

	$merits = $mycharacter->meritsandflaws;
	$virtues = $mycharacter->getAttributes("Virtue");

	$c_tableleft = "<table><tr><td colspan=4><h4>Merits and Flaws</h4></td></tr>";
	$c_tableleft .= "<tr>";
	if (count($merits) > 0) {
		$c_tableleft .= "<td colspan=4><table>";
		foreach ($merits as $merit) {
			if ($merit->pending == 0) {
				$c_tableleft .= "<tr><td class='vtmcol_key'>" . stripslashes($merit->name) . "</td>";
				$c_tableleft .= "<td class='vtmcol_spec'>" . (empty($merit->comment) ? "&nbsp;" : stripslashes($merit->comment)) . "</td>";
				$c_tableleft .= "<td>" . $merit->level . "</td></tr>\n";
			}
		}
		$c_tableleft .= "</table></td>";
	} else {
		$c_tableleft .= "<td colspan=4>&nbsp;</td>";
	}
	$c_tableleft .= "</tr></table>";
	$c_tableright = "<table><tr><td colspan=3><h4>Virtues</h4></td></tr>";
	for ($i=0;$i<3;$i++) {
		$statname = isset($virtues[$i]->name)      ? $virtues[$i]->name : '';
		$statlvl  = isset($virtues[$i]->level)     ? $virtues[$i]->level : '';
		$c_tableright .= "<tr>
				<td class='vtmcol_key'>" . $statname . "</td>
				<td class='vtmcol_spec'>&nbsp;</td>
				<td class='vtmdot_5'>" . vtm_numberToDots(5, $statlvl) . "</td>
			</tr>\n";
	}
	$c_tableright .= "<tr><td colspan=3><hr /></td></tr>\n";
	$c_tableright .= "<tr><td colspan=3><h4>Willpower</h4></td></tr>";
	$c_tableright .= "<tr><td colspan=3 class='vtmdot_10 vtmdotwide'>" . vtm_numberToDots(10, $mycharacter->willpower) . "</td></tr>\n";
	$c_tableright .= "<tr><td colspan=3 class='vtmdot_10 vtmdotwide'>" . vtm_numberToBoxes(10, $mycharacter->willpower - $mycharacter->current_willpower) . "</td></tr>\n";
	$c_tableright .= "<tr><td colspan=3><hr /></td></tr>\n";
	$c_tableright .= "<tr><td colspan=2><h4>" . $mycharacter->path_of_enlightenment . "</h4></td><td><h4>" . $mycharacter->path_rating . "</h4></td></tr>\n";
	$c_tableright .= "<tr><td colspan=3><hr /></td></tr>\n";
	$c_tableright .= "<tr><td colspan=3><h4>Bloodpool</h4></td></tr>";
	$c_tableright .= "<tr><td colspan=3 class='vtmdot_10 vtmdotwide'>" . vtm_numberToBoxes($mycharacter->bloodpool,0) . "</td></tr>\n";
	$c_tableright .= "</table>";

	if ($config->WEB_COLUMNS == 3) {
		$content .= "
			<tr>
			<td class='vtm_colwide' colspan = 2>$c_tableleft</td>
			<td class='vtm_colnarrow'>$c_tableright</td>
			</tr>";
	} else {
		$content .= "
			<tr><td class='vtm_colfull'>$c_tableleft</td></tr>
			<tr><td class='vtm_colfull'>$c_tableright</td></tr>";
	}

	
	//---- MAGIK ----
	$content .= $divder;

	$rituals = $mycharacter->rituals;
	$majikpaths  = $mycharacter->paths;

	$c_tableleft = "<table><tr><td colspan=4><h4>Rituals</h4></td></tr>";
	$c_tableleft .= "<tr>";
	if (count($rituals) > 0) {
		$c_tableleft .= "<td colspan=4><table>";
		foreach ($rituals as $majikdiscipline => $rituallist) {
			$c_tableleft .= "<tr><td colspan=2><strong>" . $majikdiscipline . " Rituals</strong></td></tr>\n";
			foreach ($rituallist as $ritual) {
				if ($ritual['pending'] == 0)
					$c_tableleft .= "<tr><td class='vtmcol_key'>Level " . $ritual['level'] . "</td><td>" . $ritual['name'] . "</td></tr>\n";
			} 
		}
		$c_tableleft .= "</table></td>";
	} else {
		$c_tableleft .= "<td colspan=4>&nbsp;</td>";
	}
	$c_tableleft .= "</tr></table>";
	$c_tableright = "<table><tr><td colspan=3><h4>Paths</h4></td></tr>";
	$c_tableright .= "<tr>";
	if (count($majikpaths) > 0) {
		$c_tableright .= "<table>\n";
		foreach ($majikpaths as $discipline => $paths) {
			$c_tableright .= "<tr><td colspan=2><strong>$discipline</strong></td></tr>\n";
			foreach ($paths as $path => $info) {
				if ($info[0] > 0)
					$c_tableright .= "<tr><td class='vtmcol_key_wide'>$path</td><td class='vtmdot_5'>" . vtm_numberToDots(5, $info[0]) . "</td></tr>";
			}
		}
		$c_tableright .= "</table>\n";
	} else {
		$c_tableright .= "&nbsp;";
	}
	$c_tableright .= "</tr></table>";

	if ($config->WEB_COLUMNS == 3) {
		$content .= "
			<tr>
			<td class='vtm_colwide' colspan = 2>$c_tableleft</td>
			<td class='vtm_colnarrow'>$c_tableright</td>
			</tr>";
	} else {
		$content .= "
			<tr><td class='vtm_colfull'>$c_tableleft</td></tr>$divder
			<tr><td class='vtm_colfull'>$c_tableright</td></tr>";
	}

	$content .= "</table>";
	$content .= "</div>\n";
	
	return $content;
}


?>