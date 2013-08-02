<?php

function gv_extended_background_content_filter($content) {

  if (is_page(get_stlink_page('viewExtBackgrnd')) && is_user_logged_in()) {
    $content .= get_extbackgrounds_content();
  }
  // otherwise returns the database content
  return $content;
}

add_filter( 'the_content', 'gv_extended_background_content_filter' );


function get_extbackgrounds_content() {

	$character = establishCharacter('');
	$characterID = establishCharacterID($character);
		
	$content = "<div class='wrap'>
		<script type='text/javascript'>
			function tabSwitch(tab) {
				setSwitchState('backgrounds', tab == 'backgrounds');
				setSwitchState('meritflaw', tab == 'meritflaw');
				setSwitchState('misc', tab == 'misc');
				return false;
			}
			function setSwitchState(tab, show) {
				document.getElementById('gv-'+tab).style.display = show ? 'block' : 'none';
				document.getElementById('gvm-'+tab).className = show ? 'shown' : '';
			}
		</script>
		<div class='gvbgmenu'>
			<ul>				<li>" . get_tabanchor('backgrounds', 'Backgrounds') . "</li>				<li>" . get_tabanchor('meritflaw', 'Merits and Flaws') . "</li>				<li>" . get_tabanchor('misc', 'Miscellaneous') . "</li>			</ul>
		</div>
		<div class='gvbgmain'>
			<div id='gv-backgrounds' " . get_tabdisplay('backgrounds') . ">
				" . get_editbackgrounds_tab($characterID) . "
				
			</div>
			<div id='gv-meritflaw' " . get_tabdisplay('meritflaw') . ">
				" . get_editmerits_tab($characterID) . "	
				
			</div>
			<div id='gv-misc' " . get_tabdisplay('misc') . ">
				" . get_editmisc_tab($characterID) . "
				
			</div>
		</div>
	</div>";
	
	return $content;
}
function get_tabanchor($tab, $text, $default = "backgrounds"){	$markup = '<a id="gvm-@TAB@" href="javascript:void(0);" onclick="tabSwitch(\'@TAB@\');"@SHOWN@>@TEXT@</a>';	return str_replace(				Array('@TAB@','@TEXT@','@SHOWN@'),				Array($tab, $text, get_highlight($tab, $default)),				$markup				);}function get_highlight($tab, $default="backgrounds"){	if ((isset($_REQUEST['tab']) && $_REQUEST['tab'] == $tab) || ($tab == $default))		return " class='shown'";	return "";}
function get_tabdisplay($tab, $default="backgrounds") {

	$display = "style='display:none'";

	if (isset($_REQUEST['tab'])) {
		if ($_REQUEST['tab'] == $tab)
			$display = "class='".$tab."'";
	} else if ($tab == $default) {
		$display = "class='default'";
	}

	return $display;
}

function get_editbackgrounds_tab($characterID) {
	global $wpdb;

	$character = establishCharacter("Ugly Duckling");
	$characterID = establishCharacterID($character);
	
	/* Save backgrounds */
	if (isset($_REQUEST['charbgID'])) {
		$data = array (
			'SECTOR_ID' => $_REQUEST['sectorid'],
			'PENDING_DETAIL' => $_REQUEST['pending'],
			'DENIED_DETAIL'  => ''
		);
		$wpdb->show_errors();
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND",
			$data,
			array (
				'ID' => $_REQUEST['charbgID']
			)
		);
		
		if ($result) 			echo "<p style='color:green'>Updated {$_REQUEST['charbgName']} background</p>";
		else if ($result === 0) echo "<p style='color:orange'>No updates made to {$_REQUEST['charbgName']} background</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update {$_REQUEST['charbgName']} background</p>";
		}
		
	}

	/* get all the backgrounds for this character that need extra detail */
	$sql = "select backgrounds.NAME, charbgs.LEVEL, backgrounds.BACKGROUND_QUESTION,
				charbgs.SECTOR_ID, charbgs.APPROVED_DETAIL, charbgs.PENDING_DETAIL,
				charbgs.DENIED_DETAIL, charbgs.ID as charbgsID, backgrounds.HAS_SECTOR,
				charbgs.COMMENT
			from	" . GVLARP_TABLE_PREFIX . "BACKGROUND backgrounds,
					" . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgs,
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters
			where	backgrounds.ID = charbgs.BACKGROUND_ID
				and	characters.ID = $characterID
				and characters.ID = charbgs.CHARACTER_ID
				and	(backgrounds.BACKGROUND_QUESTION != '' OR charbgs.SECTOR_ID > 0);";
	/* $content = "<p>SQL: $sql</p>";  */
	
	$backgrounds = $wpdb->get_results($wpdb->prepare($sql));
	$i = 0;
	foreach ($backgrounds as $background) {
		$content .= "<p class='gvext_name'>" . $background->NAME . ": " . $background->LEVEL;
		$content .= ($background->COMMENT) ? " ({$background->COMMENT})" : "";
		$content .= "</p>\n";
		if (!empty($background->BACKGROUND_QUESTION))
			$content .= "<p class='gvext_ques'>" . stripslashes($background->BACKGROUND_QUESTION) . "</p>\n";
		$content .= "<div class='gvext_section'>";
		$content .= "<form name='extbgform{$i}' method='post'>\n";
		$content .= "<input type='hidden' name='charID' value='$characterID' />";
		$content .= "<input type='hidden' name='charbgID' value='{$background->charbgsID}' />\n";
		$content .= "<input type='hidden' name='charbgName' value='{$background->NAME}' />\n";
		$content .= "<table>";
		if ($background->HAS_SECTOR == 'Y') {
			$content .= "<tr><th>Sector:</th></tr>";
			$content .= "<tr><td><select name='sectorid'>";
			$content .= "<option value='0' ";
			if ($background->SECTOR_ID == 0)
				$content .= "selected='selected'";
			$content .= ">[Select]</option>";
			foreach (get_sectors() as $sector) {
				$content .= "<option value='{$sector->ID}' ";
				if ($background->SECTOR_ID == $sector->ID)
					$content .= "selected='selected'";
				$content .= ">{$sector->NAME}</option>";
			}
			$content .= "</select></td></tr>\n";
		}
		
		if ($background->BACKGROUND_QUESTION != '') {
			if (!empty($background->APPROVED_DETAIL))
				$content .= "<tr><th>Approved Description</th></tr><tr><td class='gvext_approved'>" . stripslashes($background->APPROVED_DETAIL) . "</td></tr>";
			if ($background->DENIED_DETAIL != "") {
				$content .= "<tr><th>Description Denied</th></tr><tr><td class='gvext_denied'>" . stripslashes($background->DENIED_DETAIL) . "</td></tr>\n";
			}
			$content .= "<tr><th>Update Description";
			if ($background->DENIED_DETAIL != "")
				$content .= " (denied, please update)";
			else if ($background->PENDING_DETAIL != "")
				$content .= " (saved, awaiting approval)";
			$content .= "</th></tr>";
			$content .= "<tr><td><textarea name='pending' rows='5' cols='100'>";
			if ($background->PENDING_DETAIL == "")
				$content .= stripslashes($background->APPROVED_DETAIL);
			else
				$content .= stripslashes($background->PENDING_DETAIL);
			$content .= "</textarea></td></tr>";
		}
		
		$content .= "<tr><td><input type='submit' name='save_bgform{$i}' value='Save {$background->NAME}' /></td></tr>\n";
		$content .= "</table></form></div>";
	}
	if (count($backgrounds) == 0) {
		$content .= "<p>You have no backgrounds requiring explanation</p>";
	}
	
	return $content;
}

function get_editmerits_tab($characterID) {
	global $wpdb;

	$character = establishCharacter("Ugly Duckling");
	$characterID = establishCharacterID($character);
	
	/* Save Merits and Flaws */
	if (isset($_REQUEST['meritID'])) {
		$data = array (
			'PENDING_DETAIL' => $_REQUEST['pending'],
			'DENIED_DETAIL'  => ''
		);
		$wpdb->show_errors();
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_MERIT",
			$data,
			array (
				'ID' => $_REQUEST['meritID']
			)
		);
		
		if ($result) 			echo "<p style='color:green'>Updated {$_REQUEST['charmeritName']}</p>";
		else if ($result === 0) echo "<p style='color:orange'>No updates made to {$_REQUEST['charmeritName']} background</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update {$_REQUEST['charmeritName']}</p>";
		}
		
	} 

	/* get all the merits and flaws for this character that need extra detail */
	$sql = "select merits.NAME, charmerits.APPROVED_DETAIL, charmerits.PENDING_DETAIL,
				charmerits.DENIED_DETAIL, charmerits.ID as meritID, merits.BACKGROUND_QUESTION,
				charmerits.COMMENT
			from	" . GVLARP_TABLE_PREFIX . "MERIT merits,
					" . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT charmerits,
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters
			where	merits.ID = charmerits.MERIT_ID
				and	characters.ID = $characterID
				and characters.ID = charmerits.CHARACTER_ID
				and	merits.BACKGROUND_QUESTION != '';";
	/* $content = "<p>SQL: $sql</p>"; */
	
	$merits = $wpdb->get_results($wpdb->prepare($sql));
	$i = 0;
	foreach ($merits as $merit) {
	
		$content .= "<p class='gvext_name'>" . $merit->NAME;
		$content .= ($merit->COMMENT) ? " ({$merit->COMMENT})" : "";
		$content .= "</p>\n<p class='gvext_ques'>" . stripslashes($merit->BACKGROUND_QUESTION) . "</p>\n";
		$content .= "<div class='gvext_section'>";
		$content .= "<form name='extmeritform{$i}' method='post'>\n";
		$content .= "<input type='hidden' name='charID' value='$characterID' />";
		$content .= "<input type='hidden' name='meritID' value='{$merit->meritID}' />\n";
		$content .= "<input type='hidden' name='charmeritName' value='{$merit->NAME}' />\n";
		$content .= "<input type='hidden' name='tab' value='meritflaw' />\n";
		$content .= "<table>";

		if (!empty($merit->APPROVED_DETAIL))
			$content .= "<tr><th>Approved Description</th></tr><tr><td class='gvext_approved'>" . stripslashes($merit->APPROVED_DETAIL) . "</td></tr>";
		if ($merit->DENIED_DETAIL != "") {
			$content .= "<tr><th>Description Denied</th></tr><tr><td class='gvext_denied'>" . stripslashes($merit->DENIED_DETAIL) . "</td></tr>\n";
		}
		$content .= "<tr><th>Update Description";
		if ($merit->DENIED_DETAIL != "")
			$content .= " (denied, please update)";
		else if ($merit->PENDING_DETAIL != "")
			$content .= " (saved, awaiting approval)";
		$content .= "</th></tr>";
		$content .= "<tr><td><textarea name='pending' rows='5' cols='100'>";
		if ($merit->PENDING_DETAIL == "")
			$content .= stripslashes($merit->APPROVED_DETAIL);
		else
			$content .= stripslashes($merit->PENDING_DETAIL);
		$content .= "</textarea></td></tr>";

		
		$content .= "<tr><td><input type='submit' name='save_meritform{$i}' value='Save {$merit->NAME}' /></td></tr>\n";
		$content .= "</table></form></div>";
	}
	if (count($merits) == 0) {
		$content .= "<p>You have no merits or flaws requiring explanation</p>";
	}
	
	
	return $content;
}

function get_editmisc_tab($characterID) {
	global $wpdb;

	$character = establishCharacter("");
	$characterID = establishCharacterID($character);
	$wpdb->show_errors();
	
	/* Save Misc Extended Background Answers */
	if (isset($_REQUEST['miscID']) && $_REQUEST['miscID'] != "") {
		/* update answer */
		$data = array (
			'PENDING_DETAIL' => $_REQUEST['pending'],
			'DENIED_DETAIL'  => ''
		);
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND",
			$data,
			array (
				'ID' => $_REQUEST['miscID']
			)
		);
		
		if ($result) 			echo "<p style='color:green'>Updated {$_REQUEST['charmiscTitle']}</p>";
		else if ($result === 0) echo "<p style='color:orange'>No updates made to {$_REQUEST['charmiscTitle']} background</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update {$_REQUEST['charmiscTitle']}</p>";
		}
		
	} else {
		if (isset($_REQUEST['miscformID'])) {
			/* new answer */
			$data = array (
				'CHARACTER_ID'    => $_REQUEST['charID'],
				'QUESTION_ID'     => $_REQUEST['questID'],
				'APPROVED_DETAIL' => '',
				'PENDING_DETAIL'  => $_REQUEST['pending'],
				'DENIED_DETAIL'   => ''
			);
			$wpdb->insert(GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND", $data,
				array (
					'%d',
					'%d',
					'%s',
					'%s',
					'%s'
				)
			);
			if ($wpdb->insert_id == 0) {
				echo "<p style='color:red'><b>Error:</b> {$_REQUEST['charmiscTitle']} could not be saved (";
				$wpdb->print_error();
				echo ")</p>";
			} else {
				echo "<p style='color:green'>Saved answer '{$_REQUEST['charmiscTitle']}' for approval</p>";
			}
			
		}
	}

	
	
	/* get all the background questions that need extra detail */
	$sql = "SELECT questions.TITLE, questions.ORDERING, questions.GROUPING, questions.BACKGROUND_QUESTION, 
				tempcharmisc.APPROVED_DETAIL, tempcharmisc.PENDING_DETAIL, tempcharmisc.DENIED_DETAIL, 
				tempcharmisc.ID AS miscID, questions.ID as questID
			FROM " . GVLARP_TABLE_PREFIX . "CHARACTER characters, 
				 " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND questions
				LEFT JOIN (
					SELECT charmisc.APPROVED_DETAIL, charmisc.PENDING_DETAIL, charmisc.DENIED_DETAIL, 
						charmisc.ID AS ID, charmisc.QUESTION_ID, characters.ID as charID
					FROM " . GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND charmisc, 
						 " . GVLARP_TABLE_PREFIX . "CHARACTER characters
					WHERE characters.ID = charmisc.CHARACTER_ID
				) tempcharmisc 
				ON questions.ID = tempcharmisc.QUESTION_ID AND tempcharmisc.charID = '$characterID'
			WHERE characters.ID = '$characterID'
				AND questions.VISIBLE = 'Y'
			ORDER BY questions.ORDERING ASC";
			
	/* $content = "<p>SQL: $sql</p>"; */
	
	$questions = $wpdb->get_results($wpdb->prepare($sql));
	$i = 0;
	foreach ($questions as $question) {
	
		$content .= "<p class='gvext_name'>" . $question->TITLE . "</p>\n";
		$content .= "<p class='gvext_ques'>" . stripslashes($question->BACKGROUND_QUESTION) . "</p>\n";
		$content .= "<div class='gvext_section'>";
		$content .= "<form name='extmiscform{$i}' method='post'>\n";
		$content .= "<input type='hidden' name='charID' value='$characterID' />";
		$content .= "<input type='hidden' name='miscID' value='{$question->miscID}' />\n";
		$content .= "<input type='hidden' name='miscformID' value='{$i}' />\n";
		$content .= "<input type='hidden' name='questID' value='{$question->questID}' />\n";
		$content .= "<input type='hidden' name='charmiscTitle' value='{$question->TITLE}' />\n";
		$content .= "<input type='hidden' name='tab' value='misc' />\n";
		$content .= "<table>";

		if (!empty($question->APPROVED_DETAIL))
			$content .= "<tr><th>Approved Description</th></tr><tr><td class='gvext_approved'>" . stripslashes($question->APPROVED_DETAIL) . "</td></tr>";
		if ($question->DENIED_DETAIL != "") {
			$content .= "<tr><th>Description Denied</th></tr><tr><td class='gvext_denied'>" . stripslashes($question->DENIED_DETAIL) . "</td></tr>\n";
		}
		$content .= "<tr><th>Update Description";
		if ($question->DENIED_DETAIL != "")
			$content .= " (denied, please update)";
		else if ($question->PENDING_DETAIL != "")
			$content .= " (saved, awaiting approval)";
		$content .= "</th></tr>";
		$content .= "<tr><td><textarea name='pending' rows='5' cols='100'>";
		if ($question->PENDING_DETAIL == "")
			$content .= stripslashes($question->APPROVED_DETAIL);
		else
			$content .= stripslashes($question->PENDING_DETAIL);
		$content .= "</textarea></td></tr>";

		
		$content .= "<tr><td><input type='submit' name='save_miscform{$i}' value='Save {$question->TITLE}' /></td></tr>\n";
		$content .= "</table></form></div>";
	}
	if (count($questions) == 0) {
		$content .= "<p>There are no extended background questions to answer.</p>";
	}
	
	
	return $content;
}

?>