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

	$character = establishCharacter('Ugly Duckling');
	$characterID = establishCharacterID($character);
		
	$content = "<div class='wrap'>
		<script type='text/javascript'>
			function tabSwitch(tab) {
				document.getElementById('gv-backgrounds').style.display = 'none';
				document.getElementById('gv-meritflaw').style.display = 'none';
				document.getElementById('gv-misc').style.display = 'none';
				document.getElementById(tab).style.display = '';
				return false;
			}
		</script>
		<div class='gvbgmenu'>
			<ul>
				<li><a href='javascript:void(0);' onclick='tabSwitch(\"gv-backgrounds\");'>Backgrounds</a></li>
				<li><a href='javascript:void(0);' onclick='tabSwitch(\"gv-meritflaw\");'>Merits and Flaws</a></li>
				<li><a href='javascript:void(0);' onclick='tabSwitch(\"gv-misc\");'>Miscellaneous</a></li>
			</ul>
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

function get_tabdisplay($tab, $default="backgrounds") {

	$display = "style='display:none'";

	if (isset($_REQUEST['tab'])) {
		if ($_REQUEST['tab'] == $tab)
			$display = "class=" . $tab;
	} else if ($tab == $default) {
		$display = "class=default";
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
			$content .= "<p class='gvext_ques'>" . $background->BACKGROUND_QUESTION . "</p>\n";
		$content .= "<div class='gvext_section'>";
		$content .= "<form name='extbgform{$i}' action='' method='post'>\n";
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
		}
		$content .= "</select></td></tr>\n";
		
		if ($background->BACKGROUND_QUESTION != '') {
			if (!empty($background->APPROVED_DETAIL))
				$content .= "<tr><th>Approved Description</th></tr><tr><td class='gvext_approved'>{$background->APPROVED_DETAIL}</td></tr>";
			if ($background->DENIED_DETAIL != "") {
				$content .= "<tr><th>Description Denied</th></tr><tr><td class='gvext_denied'>{$background->DENIED_DETAIL}</td></tr>\n";
			}
			$content .= "<tr><th>Update Description";
			if ($background->DENIED_DETAIL != "")
				$content .= " (denied, please update)";
			else if ($background->PENDING_DETAIL != "")
				$content .= " (saved, awaiting approval)";
			$content .= "</th></tr>";
			$content .= "<tr><td><textarea name='pending' rows='5' cols='100'>";
			if ($background->PENDING_DETAIL == "")
				$content .= $background->APPROVED_DETAIL;
			else
				$content .= $background->PENDING_DETAIL;
			$content .= "</textarea></td></tr>";
		}
		
		$content .= "<tr><td><input type='submit' name='save_bgform{$i}' value='Save {$background->NAME}' /></td></tr>\n";
		$content .= "</table></form></div>";
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
		$content .= "</p>\n<p class='gvext_ques'>" . $merit->BACKGROUND_QUESTION . "</p>\n";
		$content .= "<div class='gvext_section'>";
		$content .= "<form name='extmeritform{$i}' action='' method='post'>\n";
		$content .= "<input type='hidden' name='charID' value='$characterID' />";
		$content .= "<input type='hidden' name='meritID' value='{$merit->meritID}' />\n";
		$content .= "<input type='hidden' name='charmeritName' value='{$merit->NAME}' />\n";
		$content .= "<input type='hidden' name='tab' value='meritflaw' />\n";
		$content .= "<table>";

		if (!empty($merit->APPROVED_DETAIL))
			$content .= "<tr><th>Approved Description</th></tr><tr><td class='gvext_approved'>{$merit->APPROVED_DETAIL}</td></tr>";
		if ($merit->DENIED_DETAIL != "") {
			$content .= "<tr><th>Description Denied</th></tr><tr><td class='gvext_denied'>{$merit->DENIED_DETAIL}</td></tr>\n";
		}
		$content .= "<tr><th>Update Description";
		if ($merit->DENIED_DETAIL != "")
			$content .= " (denied, please update)";
		else if ($merit->PENDING_DETAIL != "")
			$content .= " (saved, awaiting approval)";
		$content .= "</th></tr>";
		$content .= "<tr><td><textarea name='pending' rows='5' cols='100'>";
		if ($merit->PENDING_DETAIL == "")
			$content .= $merit->APPROVED_DETAIL;
		else
			$content .= $merit->PENDING_DETAIL;
		$content .= "</textarea></td></tr>";

		
		$content .= "<tr><td><input type='submit' name='save_meritform{$i}' value='Save {$merit->NAME}' /></td></tr>\n";
		$content .= "</table></form></div>";
	}
	
	
	return $content;
}

function get_editmisc_tab($characterID) {
	global $wpdb;

	$character = establishCharacter("Ugly Duckling");
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
				ON questions.ID = tempcharmisc.QUESTION_ID AND tempcharmisc.charID = $characterID
			WHERE characters.ID = $characterID";
			
	/* $content = "<p>SQL: $sql</p>"; */
	
	$questions = $wpdb->get_results($wpdb->prepare($sql));
	$i = 0;
	foreach ($questions as $question) {
	
		$content .= "<p class='gvext_name'>" . $question->TITLE . "</p>\n";
		$content .= "<p class='gvext_ques'>" . $question->BACKGROUND_QUESTION . "</p>\n";
		$content .= "<div class='gvext_section'>";
		$content .= "<form name='extmiscform{$i}' action='' method='post'>\n";
		$content .= "<input type='hidden' name='charID' value='$characterID' />";
		$content .= "<input type='hidden' name='miscID' value='{$question->miscID}' />\n";
		$content .= "<input type='hidden' name='miscformID' value='{$i}' />\n";
		$content .= "<input type='hidden' name='questID' value='{$question->questID}' />\n";
		$content .= "<input type='hidden' name='charmiscTitle' value='{$question->TITLE}' />\n";
		$content .= "<input type='hidden' name='tab' value='misc' />\n";
		$content .= "<table>";

		if (!empty($question->APPROVED_DETAIL))
			$content .= "<tr><th>Approved Description</th></tr><tr><td class='gvext_approved'>{$question->APPROVED_DETAIL}</td></tr>";
		if ($question->DENIED_DETAIL != "") {
			$content .= "<tr><th>Description Denied</th></tr><tr><td class='gvext_denied'>{$question->DENIED_DETAIL}</td></tr>\n";
		}
		$content .= "<tr><th>Update Description";
		if ($question->DENIED_DETAIL != "")
			$content .= " (denied, please update)";
		else if ($question->PENDING_DETAIL != "")
			$content .= " (saved, awaiting approval)";
		$content .= "</th></tr>";
		$content .= "<tr><td><textarea name='pending' rows='5' cols='100'>";
		if ($question->PENDING_DETAIL == "")
			$content .= $question->APPROVED_DETAIL;
		else
			$content .= $question->PENDING_DETAIL;
		$content .= "</textarea></td></tr>";

		
		$content .= "<tr><td><input type='submit' name='save_miscform{$i}' value='Save {$question->TITLE}' /></td></tr>\n";
		$content .= "</table></form></div>";
	}
	
	
	return $content;
}

/* --------------------------------------------------------------------------- */
/* --------------------------------------------------------------------------- */

/*
    function print_character_expanded_backgrounds($atts, $content=null) {
        extract(shortcode_atts(array ("character" => "null", "group" => ""), $atts));
        $character = establishCharacter($character);
        $output = "";

        if ($_POST['GVLARP_FORM'] == "displayExpandedBackgrounds"
            && $_POST['ebSubmit'] == "Submit Background Updates") {
            $characterID = establishCharacterID($character);

            if ($characterID == $_POST['characterID']) {
                $maxCounter = $_POST['maxCounter'];

                $updateCounter = 0;
                if (((int) $maxCounter) > 0) {
                    for ($i = 0; $i < $maxCounter; $i++) {
                        $proposed = stripslashes($_POST['proposed_' . $i]);
                        if ($proposed != null && $proposed != "") {
                            if (isset($_POST['ecbid_' . $i])) {
                                updateExtendedBackground($_POST['ecbid_' . $i], $proposed);
                            }
                            else {
                                addExtendedBackground($_POST['title_' . $i], $_POST['code_' . $i], $proposed, $characterID);
                            }
                            $updateCounter++;
                        }
                    }
                    $output .= $updateCounter . " update";
                    if ($updateCounter != 1) {
                        $output .= "s";
                    }
                    $output .= " have been made to extended backgrounds<br />";
                }
                else {
                    $output .= "Max Counter " . $maxCounter . " is not a positive int<br />No updates initiated<br />";
                }
            }
            else {
                $output .= "Stored character ID (" . $_POST['characterID']
                    .  ") is different from established ID (" . $characterID
                    .  ")<br />No updates initiated<br />";
            }
        }

        $output .= displayCharacterExtendedBackground($character);
        return $output;
    }
    add_shortcode('character_expanded_background', 'print_character_expanded_backgrounds');

    function print_extended_character_background_approvals($atts, $content=null) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $output    = "";
        $sqlOutput = "";

        if (!isST()) {
            return "<br /><h2>Only STs can access the extended background approval section!</h2><br />";
        }


        if ($_POST['GVLARP_FORM'] == "displayPendingExtendedBackgrounds"
            && $_POST['pebSubmit'] == "Approve/Deny Expanded Backgrounds") {

            $approvedExpBackgrounds = $_POST['approved_extBack'];
            $count = count($approvedExpBackgrounds);

            for ($i = 0; $i < $count; $i++) {
                approveExtendedBackground($approvedExpBackgrounds[$i]);
            }

            if ($count > 0) {
                $output .= $count . " extended background";
                if ($count != 1) {
                    $output .= "s";
                }
                $output .= " approved<br />";
            }

            $deniedExpBackgrounds = $_POST['denied_extBack'];
            $count = count($deniedExpBackgrounds);

            for ($i = 0; $i < $count; $i++) {
                $id = $deniedExpBackgrounds[$i];
                $reason = $_POST['reason_' . $id];

                denyExtendedBackground($id, $reason);
            }

            if ($count > 0) {
                $output .= $count . " extended background";
                if ($count != 1) {
                    $output .= "s";
                }
                $output .= " denied<br />";
            }
        }

        $sql = "SELECT chara.name, exchaback.id, exchaback.title, exchaback.proposed_text, exchaback.current_text
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND exchaback,
                         " . $table_prefix . "CHARACTER chara
                    WHERE exchaback.character_id = chara.id
                      AND exchaback.current_accepted = 'W'
                    ORDER BY chara.name, exchaback.id ";

        $extBackgrounds = $wpdb->get_results($sql);

        foreach ($extBackgrounds as $extBackground) {
            $sqlOutput .= "<tr><td class=\"gvcol_1 gvcol_val\" width=50%>" . $extBackground->name . "</td><td class=\"gvcol_2 gvcol_val\" width=50%>" . $extBackground->title . "</td></tr>"
                .  "<tr><td class=\"gvcol_1 gvcol_val\">" . $extBackground->current_text . "</td><td class=\"gvcol_2 gvcol_val\">" . $extBackground->proposed_text . "</td></tr>"
                .  "<tr><td class=\"gvcol_1 gvcol_val\"><table class='gvplugin' id=\"gvid_inpeb\"><tr><td class=\"gvcol_1 gvcol_val\" align=\"center\">
                           Approve <input type=\"checkbox\" name=\"approved_extBack[]\" value=\"" . $extBackground->id . "\"></td><td class=\"gvcol_2 gvcol_val\" align=\"center\">"
                .  "Deny <input type=\"checkbox\" name=\"denied_extBack[]\" value=\"" . $extBackground->id . "\"></td><td class=\"gvcol_3 gvcol_val\" align=\"right\">Reason:</td></tr></table>"
                .  "</td><td class=\"gvcol_2 gvcol_val\"><input type='text' name=\"reason_" . $extBackground->id . "\" size=50 maxlength=200></td></tr>";
        }

        if ($sqlOutput == "") {
            $output .= "No expanded background updates waiting for approval";
        }
        else {
            $output .= "<form name=\"PEB_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
            $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayPendingExtendedBackgrounds\" />";
            $output .= "<table class='gvplugin' id=\"gvid_peb\">";
            $output .= $sqlOutput;
            $output .= "<tr><td colspan=2><input type='submit' name=\"pebSubmit\" value=\"Approve/Deny Expanded Backgrounds\"></td></tr></table></form>";        }

        return $output;
    }
    add_shortcode('extended_background_approvals', 'print_extended_character_background_approvals');

	
    function displaySingleExtendedBackground($extendedBackgroundID, $counter) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT title, current_text, proposed_text, current_accepted
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE id = %d ";

        $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $extendedBackgroundID));

        $title           = "";
        $currentText     = "";
        $proposedText    = "";
        $currentAccepted = "";

        foreach ($characterBackgrounds as $characterBackground) {
            $title           = $characterBackground->title;
            $currentText     = $characterBackground->current_text;
            $proposedText    = $characterBackground->proposed_text;
            $currentAccepted = $characterBackground->current_accepted;
        }

        if ($title == "") {
            return "No extended character background found with id (" . $extendedBackgroundID . ")";
        }

        $output  = "<table class='gvplugin' id=\"gvid_ecb\"><tr><th class=\"gvthead gvcol_1\">" . $title . "</th></tr>";
        $output .= "<tr style='display:none'><td><input type='HIDDEN' name=\"ecbid_" .$counter . "\" value=\"" . $extendedBackgroundID . "\"></td></tr>";
        if ($currentAccepted == "Y" || $currentAccepted == "R") {
            if ($currentText != null && $currentText != "") {
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Current</td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\">" . $currentText . "</td></tr>";
            }
            $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Proposed</td></tr>";
            $output .= "<tr><td class=\"gvcol_1 gvcol_val\">"
                . "<textarea name=\"proposed_" . $counter . "\" rows=\"5\" cols=\"100\">"
                . $proposedText . "</textarea></td></tr>";
        }
        else {
            if ($currentText != null && $currentText != "") {
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Current</td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\">" . $currentText . "</td></tr>";
            }

            if ($proposedText != null && $proposedText != "") {
                $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Proposed</td></tr>";
                $output .= "<tr><td class=\"gvcol_1 gvcol_val\">" . $proposedText . "</td></tr>";
            }
        }
        $output .= "</table>";

        return $output;
    }

    function displayNewExtendedBackground($title, $code, $counter) {
         $output .= "<input type='HIDDEN' name=\"code_"        . $counter . "\" value=\"" . $code        . "\">";

        $output .= "<input type='HIDDEN' name=\"title_"       . $counter . "\" value=\"" . $title       . "\">";

		$output  = "<table class='gvplugin' id=\"gvid_ecb\"><tr><th class=\"gvthead gvcol_1\">" . $title . "</th></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Proposed</td></tr>";
        $output .= "<tr><td class=\"gvcol_1 gvcol_val\">"
            . "<textarea name=\"proposed_" . $counter . "\" rows=\"5\" cols=\"100\"></textarea></td></tr></table>";
        return $output;
    }

    function addExtendedBackground($title, $code, $proposed, $characterID) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";
        $sql = "INSERT INTO " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND (character_id,
                                                                                    title,
                                                                                    code,
                                                                                    proposed_text,
                                                                                    current_accepted)
                    VALUES (%d, %s, %s, %s, 'W')";
        $sql = $wpdb->prepare($sql, $characterID, $title, $code, $proposed);

        $wpdb->query($sql);
    }

    function updateExtendedBackground($id, $proposed) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT current_accepted
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE id = %d ";

        $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $id));

        $currentAccepted = "";

        foreach ($characterBackgrounds as $characterBackground) {
            $currentAccepted = $characterBackground->current_accepted;
        }

        if ($currentAccepted == "Y") {
            $updateSQL = "UPDATE " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                              SET current_accepted = 'W',
                                  proposed_text    = %s
                              WHERE id = %d";

            $wpdb->query($wpdb->prepare($updateSQL, $proposed, $id));
        }
        else if ($currentAccepted == "R") {
            $updateSQL = "UPDATE " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                              SET current_accepted = 'W',
                                  current_text     = %s,
                                  proposed_text    = ''
                              WHERE id = %d";

            $wpdb->query($wpdb->prepare($updateSQL, $proposed, $id));
        }
    }

    function approveExtendedBackground($id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT title, current_text, proposed_text, current_accepted
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE id = %d ";

        $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $id));

        $proposedText    = "";

        foreach ($characterBackgrounds as $characterBackground) {
            $proposedText    = $characterBackground->proposed_text;
        }

        $updateSQL = "UPDATE " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                          SET current_text = %s,
                              current_accepted = 'Y',
                              proposed_text = ''
                          WHERE id = %d";

        $wpdb->query($wpdb->prepare($updateSQL, $proposedText, $id));
    }

    function denyExtendedBackground($id, $reason) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $sql = "SELECT title, current_text, proposed_text, current_accepted
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE id = %d ";

        $characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $id));

        $proposedText    = "";

        foreach ($characterBackgrounds as $characterBackground) {
            $proposedText    = $characterBackground->proposed_text;
        }

        $proposedText = "The proposed update below has been refused.\n\n" . $reason . "\n\n" . $proposedText;

        $updateSQL = "UPDATE " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                          SET current_accepted = 'R',
                              proposed_text = %s
                          WHERE id = %d";

        $wpdb->query($wpdb->prepare($updateSQL, $proposedText, $id));
    }

    function displayCharacterExtendedBackground($character) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . "GVLARP_";

        $characterID = establishCharacterID($character);

        $sql = "SELECT id, title, code
                    FROM " . $table_prefix . "EXTENDED_CHARACTER_BACKGROUND
                    WHERE character_id = %d ";

        $sql = $wpdb->prepare($sql, $characterID);

        $expandedCharacterBackgrounds = $wpdb->get_results($sql);

        $i = 0;
        $output  = "<form name=\"EBF_Form\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
        $output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayExpandedBackgrounds\" />";
        $output .= "<input type='HIDDEN' name=\"characterID\" value=\"". $characterID . "\" />";
        $output .= "<input type='HIDDEN' name=\"GVLARP_CHARACTER\" value=\"" . $character . "\" />";

        $clanFlaw = extractExtendedCharacterBackground($expandedCharacterBackgrounds, "clan_flaw");
        if ($clanFlaw != null) {
            $output .= displaySingleExtendedBackground($clanFlaw->id, $i);
        }
        else {
            $output .= displayNewExtendedBackground("Clan Flaw", "clan_flaw", $i);
        }
        $i++;

        $clanFlaw = extractExtendedCharacterBackground($expandedCharacterBackgrounds, "character_history");
        if ($clanFlaw != null) {
            $output .= displaySingleExtendedBackground($clanFlaw->id, $i);
        }
        else {
            $output .= displayNewExtendedBackground("Character History", "character_history", $i);
        }
        $i++;

        $sql = "SELECT merit.name, cmerit.id, cmerit.comment
                    FROM " . $table_prefix . "CHARACTER_MERIT cmerit,
                         " . $table_prefix . "MERIT merit
                    WHERE cmerit.character_id = %d
                      AND cmerit.merit_id     = merit.id
                    ORDER BY cmerit.level DESC, merit.name";

        $sql = $wpdb->prepare($sql, $characterID);
        $characterMerits = $wpdb->get_results($sql);


        foreach ($characterMerits as $characterMerit) {
            $currentCode = "merit_" . $characterMerit->id;
            $currentExtendedBackground = extractExtendedCharacterBackground($expandedCharacterBackgrounds, $currentCode);
            if ($currentExtendedBackground != null) {
                $output .= displaySingleExtendedBackground($currentExtendedBackground->id, $i);
            }
            else {
                $title = $characterMerit->name;
                if ($characterMerit->comment != null && $characterMerit->comment != "") {
                    $title .= " (" . $characterMerit->comment . ")";
                }
                $output .= displayNewExtendedBackground($title, $currentCode, $i);
            }
            $i++;
        }

        $sql = "SELECT background.name, cbackground.id, cbackground.comment
                    FROM " . $table_prefix . "CHARACTER_BACKGROUND cbackground,
                         " . $table_prefix . "BACKGROUND background
                    WHERE cbackground.character_id  = %d
                      AND cbackground.background_id = background.id
                    ORDER BY cbackground.level DESC, background.name";

        $sql = $wpdb->prepare($sql, $characterID);
        $characterBackgrounds = $wpdb->get_results($sql);

        foreach ($characterBackgrounds as $characterBackground) {
            $currentCode = "background_" . $characterBackground->id;
            $currentExtendedBackground = extractExtendedCharacterBackground($expandedCharacterBackgrounds, $currentCode);
            if ($currentExtendedBackground != null) {
                $output .= displaySingleExtendedBackground($currentExtendedBackground->id, $i);
            }
            else {
                $title = $characterBackground->name;
                if ($characterBackground->comment != null && $characterBackground->comment != "") {
                    $title .= " (" . $characterBackground->comment . ")";
                }
                $output .= displayNewExtendedBackground($title, $currentCode, $i);
            }
            $i++;
        }

        $output .= "<input type='HIDDEN' name=\"maxCounter\" value=\"" . $i . "\">";
        $output .= "<input type='HIDDEN' name=\"characterID\" value=\"" . $characterID . "\">";
        $output .= "<center><input type='submit' name=\"ebSubmit\" value=\"Submit Background Updates\"></center></form>";

        return $output;
    }

    function extractExtendedCharacterBackground($characterBackgrounds, $code) {

        foreach ($characterBackgrounds as $characterBackground) {
            if ($characterBackground->code == $code) {
                return $characterBackground;
            }
        }

        return null;
    }
*/
?>