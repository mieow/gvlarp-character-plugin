<?php

function gv_viewcharacter_content_filter($content) {

  if (is_page(get_stlink_page('viewCharSheet')))
		if (is_user_logged_in()) {
			$content .= get_viewcharacter_content();
		} else {
			$content .= "<p>You must be logged in to view this content.</p>";
		}
  // otherwise returns the database content
  return $content;
}

add_filter( 'the_content', 'gv_viewcharacter_content_filter' );


function get_viewcharacter_content() {
	global $wpdb;

	$character = establishCharacter('');
	$characterID = establishCharacterID($character);
	
	$mycharacter = new larpcharacter();
	$mycharacter->load($characterID);
	
	$config = getConfig();
	$maxrating = $mycharacter->max_rating > 5 ? 10 : 5;

	$content = "<div class=\"gvplugin\" id=\"csheet\">";
	
	//---- TOP CHARACTER INFO ----
	$content .= "<table><tbody>
		<tr>
			<td>Character</td><td>" . htmlentities($mycharacter->name) . "</td>
			<td>Clan</td><td>" . htmlentities($mycharacter->private_clan) . "</td>
			<td>Generation</td><td>" . htmlentities($mycharacter->generation) . "</td>
		</tr><tr>
			<td>Domain</td><td>" . htmlentities($mycharacter->domain) . "</td>
			<td>Public Clan</td><td>" . htmlentities($mycharacter->clan) . "</td>\n";
	if ($config->USE_NATURE_DEMEANOUR == 'Y')
		$content .= "<td>Nature</td><td>" . htmlentities($mycharacter->nature) . "</td>";
	else
		$content .= "<td>Date of Birth</td><td>" . htmlentities($mycharacter->date_of_birth) . "</td>";
	$content .= "	</tr><tr>
			<td>Sect</td><td>" . htmlentities($mycharacter->sect) . "</td>
			<td>Sire</td><td>" . htmlentities($mycharacter->sire) . "</td>";
	if ($config->USE_NATURE_DEMEANOUR == 'Y')
		$content .= "<td>Demeanour</td><td>" . htmlentities($mycharacter->demeanour) . "</td>";
	else
		$content .= "<td>Date of Embrace</td><td>" . htmlentities($mycharacter->date_of_embrace) . "</td>";
	$content .= "	</tr>
		<tr><td class=\"gvhr\" colspan=6><hr /></td></tr>
		<tr><td colspan=6><h3>Attributes</h3></td></tr>
		<tr>
			<td colspan=2><h4>Physical</h4></td>
			<td colspan=2><h4>Social</h4></td>
			<td colspan=2><h4>Mental</h4></td>
		</tr>";

	//---- ATTRIBUTES ----
	$physical = $mycharacter->getAttributes("Physical");
	$social   = $mycharacter->getAttributes("Social");
	$mental   = $mycharacter->getAttributes("Mental");
	
	$content .= "<tr><td colspan=2><table>\n";
	for ($i=0;$i<3;$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $physical[$i]->name                    . "</td>
				<td class='gvcol_spec'>" . stripslashes($physical[$i]->specialty) . "</td>
				<td class='gvdot_{$maxrating}_{$physical[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	$content .= "</table></td><td colspan=2><table>";
	for ($i=0;$i<3;$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $social[$i]->name                    . "</td>
				<td class='gvcol_spec'>" . stripslashes($social[$i]->specialty) . "</td>
				<td class='gvdot_{$maxrating}_{$social[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	$content .= "</table></td><td colspan=2><table>";
	for ($i=0;$i<3;$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $mental[$i]->name                    . "</td>
				<td class='gvcol_spec'>" . stripslashes($mental[$i]->specialty) . "</td>
				<td class='gvdot_{$maxrating}_{$mental[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	$content .= "</table></td></tr>";
	
	//---- ABILITIES ----
	$content .= "<tr><td class=\"gvhr\" colspan=6><hr /></td></tr>
		<tr><td colspan=6><h3>Abilities</h3></td></tr>
		<tr>
			<td colspan=2><h4>Talents</h4></td>
			<td colspan=2><h4>Skills</h4></td>
			<td colspan=2><h4>Knowledges</h4></td>
		</tr>";
	$talent    = $mycharacter->getAbilities("Talents");
	$skill     = $mycharacter->getAbilities("Skills");
	$knowledge = $mycharacter->getAbilities("Knowledges");
	
	//$abilrows = 1;
	//if ($abilrows < count($talent)) $abilrows = count($talent);
	//if ($abilrows < count($skill)) $abilrows = count($skill);
	//if ($abilrows < count($knowledge)) $abilrows = count($knowledge);

	$content .= "<tr><td colspan=2><table>\n";
	for ($i=0;$i<count($talent);$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $talent[$i]->skillname               . "</td>
				<td class='gvcol_spec'>" . stripslashes($talent[$i]->specialty) . "</td>
				<td class='gvdot_{$maxrating}_{$talent[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	$content .= "</table></td><td colspan=2><table>";
	for ($i=0;$i<count($skill);$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $skill[$i]->skillname               . "</td>
				<td class='gvcol_spec'>" . stripslashes($skill[$i]->specialty) . "</td>
				<td class='gvdot_{$maxrating}_{$skill[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	$content .= "</table></td><td colspan=2><table>";
	for ($i=0;$i<count($knowledge);$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $knowledge[$i]->skillname               . "</td>
				<td class='gvcol_spec'>" . stripslashes($knowledge[$i]->specialty) . "</td>
				<td class='gvdot_{$maxrating}_{$knowledge[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	$content .= "</table></td></tr>";
	$content .= "<tr><td class=\"gvhr\" colspan=6><hr /></td></tr>";
	
	//---- BACKGROUND, DISCIPLINES AND SECONDARY SKILLS ----
	
	$backgrounds = $mycharacter->getBackgrounds();
	$disciplines = $mycharacter->getDisciplines();
	
	$sql = "SELECT DISTINCT GROUPING FROM " . GVLARP_TABLE_PREFIX . "SKILL skills;";
	$allgroups = $wpdb->get_results($wpdb->prepare($sql,''));	
	
	$secondarygroups = array();
	foreach ($allgroups as $group) {
		if ($group->GROUPING != 'Talents' && $group->GROUPING != 'Skills' && $group->GROUPING != 'Knowledges')
			array_push($secondarygroups, $group->GROUPING);
	}	

	$secondary = array();
	foreach ($secondarygroups as $group)
			$secondary = array_merge($mycharacter->getAbilities($group), $secondary);	
	
	//$rows = 1;
	//if ($rows < count($backgrounds)) $rows = count($backgrounds);
	//if ($rows < count($disciplines)) $rows = count($disciplines);
	//if ($rows < count($secondary)) $rows = count($secondary);

	$content .= "<tr>
			<td colspan=2><h4>Backgrounds</h4></td>
			<td colspan=2><h4>Disciplines</h4></td>
			<td colspan=2><h4>Secondary</h4></td>
		</tr><tr><td colspan=2><table>\n";
	for ($i=0;$i<count($backgrounds);$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $backgrounds[$i]->background               . "</td>
				<td class='gvcol_spec'>" . (!empty($backgrounds[$i]->sector) ?  $backgrounds[$i]->sector : stripslashes($backgrounds[$i]->comment)) . "</td>
				<td class='gvdot_{$maxrating}_{$backgrounds[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	$content .= "</table></td><td colspan=2><table>";
	for ($i=0;$i<count($disciplines);$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $disciplines[$i]->name               . "</td>
				<td class='gvcol_spec'>&nbsp;</td>
				<td class='gvdot_{$maxrating}_{$disciplines[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	// COMBO DISCIPLINES
	$combo = $mycharacter->combo_disciplines;
	foreach ($combo as $id => $disc)
		$content .= "<tr><td colspan=3>$disc</td></tr>";
	
	$content .= "</table></td><td colspan=2><table>";
	for ($i=0;$i<count($secondary);$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $secondary[$i]->skillname               . "</td>
				<td class='gvcol_spec'>" . stripslashes($secondary[$i]->specialty) . "</td>
				<td class='gvdot_{$maxrating}_{$secondary[$i]->level}'>&nbsp;</td>
			</tr>";
	}
	$content .= "</table></td></tr>";
	$content .= "<tr><td class=\"gvhr\" colspan=6><hr /></td></tr>";

	//---- MERITS, FLAWS, VIRTUES, WILLPOWER, PATH AND BLOOD ----
	
	$merits = $mycharacter->meritsandflaws;
	$virtues = $mycharacter->getAttributes("Virtue");
	
	$content .= "<tr><td colspan=4><h4>Merits and Flaws</h4></td><td colspan=2><h4>Virtues</h4></td></tr>\n";
	$content .= "<tr>";
	if (count($merits) > 0) {
		$content .= "<td colspan=4><table>";
		foreach ($merits as $merit) {
			$content .= "<tr><td class='gvcol_key'>" . stripslashes($merit->name) . "</td>";
			$content .= "<td class='gvcol_spec'>" . (empty($merit->comment) ? "&nbsp;" : stripslashes($merit->comment)) . "</td>";
			$content .= "<td>" . $merit->level . "</td></tr>\n";
		}
		$content .= "</table></td>";
	} else {
		$content .= "<td colspan=4>&nbsp;</td>";
	}
	
	$content .= "<td colspan=2><table>";
	for ($i=0;$i<3;$i++) {
		$content .= "<tr>
				<td class='gvcol_key'>" . $virtues[$i]->name . "</td>
				<td class='gvcol_spec'>&nbsp;</td>
				<td class='gvdot_{$maxrating}_{$virtues[$i]->level}'>&nbsp;</td>
			</tr>\n";
	}
	$content .= "<tr><td colspan=3><hr /></td></tr>\n";
	$content .= "<tr><td colspan=3><h4>Willpower</h4></td></tr>\n";
	$content .= "<tr><td colspan=3 class='gvdot_10_{$mycharacter->willpower} gvdotwide'>&nbsp;</td></tr>\n";
	$content .= "<tr><td colspan=2 class='gvcol_key'>Current</td><td>" . $mycharacter->current_willpower . "</td></tr>\n";
	$content .= "<tr><td colspan=3><hr /></td></tr>\n";
	$content .= "<tr><td colspan=2><h4>" . $mycharacter->path_of_enlightenment . "</h4></td><td><h4>" . $mycharacter->path_rating . "</h4></td></tr>\n";
	$content .= "<tr><td colspan=3><hr /></td></tr>\n";
	$content .= "<tr><td colspan=2><h4>Bloodpool</h4></td><td><h4>" . $mycharacter->bloodpool . "</h4></td></tr>\n";
	
	$content .= "</table></td></tr>\n";
	$content .= "<tr><td class=\"gvhr\" colspan=6><hr /></td></tr>\n";

	//---- MAGIK ----
	
	$rituals = $mycharacter->rituals;
	$majikpaths  = $mycharacter->paths;
	
	$content .= "<tr><td colspan=4><h4>Rituals</h4></td><td colspan=2><h4>Paths</h4></td></tr>\n";
	$content .= "<tr>";
	if (count($rituals) > 0) {
		$content .= "<td colspan=4><table>";
		foreach ($rituals as $majikdiscipline => $rituallist) {
			$content .= "<tr><td colspan=2><strong>" . $majikdiscipline . " Rituals</strong></td></tr>\n";
			foreach ($rituallist as $ritual) {
				$content .= "<tr><td class='gvcol_key'>Level " . $ritual[level] . "</td><td>" . $ritual[name] . "</td></tr>\n";
			} 
		}
		$content .= "</table></td>";
	} else {
		$content .= "<td colspan=4>&nbsp;</td>";
	}
	$content .= "<td colspan=2>";
	if (count($majikpaths) > 0) {
		$content .= "<table>\n";
		foreach ($majikpaths as $discipline => $paths) {
			$content .= "<tr><td colspan=2><strong>$discipline</strong></td></tr>\n";
			foreach ($paths as $path => $level) {
				$content .= "<tr><td class='gvcol_key'>$path</td><td class='gvdot_5_$level'>&nbsp;</td></tr>";
			}
		}
		$content .= "</table>\n";
	} else {
		$content .= "&nbsp;";
	}
	
	$content .= "</td></tr>\n";
	$content .= "</table>\n";
	$content .= "</div>\n";
	return $content;
}


?>