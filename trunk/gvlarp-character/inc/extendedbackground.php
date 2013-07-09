<?php

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
?>