<?php

function gv_profile_content_filter($content) {

  if (is_page(get_stlink_page('viewProfile')))
		$content .= get_profile_content();
  // otherwise returns the database content
  return $content;
}

add_filter( 'the_content', 'gv_profile_content_filter' );


function get_profile_content() {
	global $wpdb;

	// Work out current character and what character profile is requested
	$currentUser      = wp_get_current_user();
	$currentCharacter = $currentUser->user_login;
	$currentCharacterID = establishCharacterID($currentCharacter);
	
	if (isset($_REQUEST['CHARACTER']))
		$character = $_REQUEST['CHARACTER'];
	elseif (!empty($currentCharacter))
		$character = $currentCharacter;
	else
		return "<p>You need to specify a character or be logged in to view the profiles.</p>";

	$output       = "";
	$config       = getConfig();
	$clanPrestige = 0;
	$showAll = false;
	
	$sql = "SELECT pub.NAME as pubclan, priv.NAME as privclan
			FROM 
				" . GVLARP_TABLE_PREFIX . "CHARACTER chars,
				" . GVLARP_TABLE_PREFIX . "CLAN pub,
				" . GVLARP_TABLE_PREFIX . "CLAN priv
			WHERE
				chars.PUBLIC_CLAN_ID = pub.ID
				AND chars.PRIVATE_CLAN_ID = priv.ID
				AND chars.ID = %s";
	$result = $wpdb->get_row($wpdb->prepare($sql, $currentCharacterID));
	
	$observerClanPub  = isset($result->pubclan) ? $result->pubclan : '';
	$observerClanPriv = isset($result->privclan) ? $result->privclan : '';
	
	// Show full character details to STs and if you are viewing your own profile
	if (isST() || $character == $currentCharacter)
		$showAll = true;

	$sql = "SELECT ID 
			FROM " . GVLARP_TABLE_PREFIX . "CHARACTER 
			WHERE WORDPRESS_ID = %s
			AND DELETED = 'N'";
	$sql = $wpdb->prepare($sql, $character);
	$characterID = $wpdb->get_var($sql);
	
	if (empty($characterID))
		return "<p>No information found for $character</p>";
	
	$mycharacter = new larpcharacter();
	$mycharacter->load($characterID);

	// Update display name
	if (isST() || $currentCharacter == $character) {
		$user = get_user_by('login', $character);
		$displayName = $user->display_name;
		$userID = $user->ID;
	
		if (isset($_POST['GVLARP_FORM']) && $_POST['GVLARP_FORM'] == 'updateDisplayName' && isset($_POST['displayName']) 
			&& !empty($_POST['displayName']) && $_POST['displayName'] != $displayName) {
			
			$newDisplayName = $_POST['displayName'];
			
			$output .= "<p>Changed display name to <i>$newDisplayName</i></p>";
			changeDisplayNameByID ($userID, $newDisplayName);
			$displayName = $newDisplayName;
		}
	}
	
	// Update password
	if (isset($_POST['newPassword1']) && (isST() || $currentCharacter == $character)) {
		$user = get_user_by('login', $character);
		$userID = $user->ID;
	
		$newPassword1 = $_POST['newPassword1'];
		$newPassword2 = $_POST['newPassword2'];
		
		if ($_POST['GVLARP_FORM'] == 'updatePassword' 
			&& isset($newPassword1) && !empty($newPassword1) 
			&& isset($newPassword2) && !empty($newPassword2) 
			) {
			
			if ($newPassword1 !== $newPassword2) {
				$output .= "<p>Passwords don't match</p>";
			} 
			elseif (changePassword($character, $newPassword1, $newPassword2)) {
				$output .= "<p>Successfully changed password</p>";
			}
			else {
				$output .= "<p>Failed to change password</p>";
			}
		}
	}
	
	if ($showAll) {
		$clanIcon = $mycharacter->private_icon;  // private clan
	} else {
		$clanIcon = $mycharacter->public_icon;   // public clan
	}
	
	// Title, with link to view character for STs
	$characterDisplayName = isST() ? 
							"<a href='" . get_site_url() . get_stlink_url('viewCharSheet') . "?CHARACTER=" . urlencode($character) . "'>" . $displayName . "</a>" 
							: $displayName;
	$output .= "<h1>" . $characterDisplayName . "</h1>";
	
	// Profile info
	$output .= "<table class='gvplugin gvprofile' id=\"gvid_prof_out\">\n";
	$output .= "<tr><td class=\"gvcol_1 gvcol_val\">\n";
	// Character Info
	$output .= "<p><img alt='Clan Icon' src='$clanIcon' />" . $mycharacter->quote . "</p>\n";
	$output .= "<table class='gvplugin gvprofile' id=\"gvid_prof_in\">\n";
    $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Player:</td><td class=\"gvcol_2 gvcol_val\">" . $mycharacter->player . "</td></tr>";
	$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Clan:</td><td class=\"gvcol_2 gvcol_val\">" . $mycharacter->clan;
	if ($showAll && $mycharacter->clan != $mycharacter->private_clan)
		$output .= " (" . $mycharacter->private_clan . ")";
	$output .= "</td></tr>";
    $output .= "<tr><td class=\"gvcol_1 gvcol_key\">Resides:</td><td class=\"gvcol_2 gvcol_val\">" . $mycharacter->domain . "</td></tr>";
	
	// Background - Status
	if ($config->DISPLAY_BACKGROUND_IN_PROFILE) {
		$sql = "SELECT NAME FROM " . GVLARP_TABLE_PREFIX . "BACKGROUND
				WHERE ID = %d";
		$background = $wpdb->get_var($wpdb->prepare($sql, $config->DISPLAY_BACKGROUND_IN_PROFILE));	
	
		$level = 0;
		foreach ($mycharacter->backgrounds as $row) {
			if ($row->background == $background)
				$level = $row->level;
		}
        $output .= "<tr><td class=\"gvcol_1 gvcol_key\">$background:</td><td class=\"gvcol_2 gvcol_val\">" . $level . "</td></tr>";
	}
	
	// Condition
	$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Condition:</td><td class=\"gvcol_2 gvcol_val\">" . $mycharacter->char_status;
	if ($mycharacter->char_status_comment != "") {
		$output .= " (" . $mycharacter->char_status_comment . ")";
	}
	$output .= "</td></tr>";
	
	// Clan Prestige
	foreach ($mycharacter->backgrounds as $row) {
		$testClan = empty($row->comment) ? $mycharacter->clan : $row->comment;
	
		if ($row->background == "Clan Prestige") {
			if ($showAll || $observerClanPub  == $testClan || $observerClanPriv == $testClan)
				$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Clan Prestige (" . $testClan . "):</td><td class=\"gvcol_2 gvcol_val\">" . $row->level . "</td></tr>";
		}
	}
	
	// Merits/Flaws - Clan Friendship/Enmity
	$sql = "SELECT merits.NAME
			FROM 
				" . GVLARP_TABLE_PREFIX . "MERIT merits,
				" . GVLARP_TABLE_PREFIX . "PROFILE_DISPLAY disp
			WHERE
				merits.PROFILE_DISPLAY_ID = disp.ID
				AND disp.NAME = 'If Clan Matches'
			ORDER BY merits.NAME";
	$displayMerits = $wpdb->get_col($sql);
	foreach ($displayMerits as $displaymerit) {
		foreach ($mycharacter->meritsandflaws as $charmerit) {
			if ($displaymerit != $charmerit->name)
				continue;
			
			if ($showAll || $observerClanPub  == $charmerit->comment 
				|| $observerClanPriv == $charmerit->comment) {
				$output .= "<tr><td class=\"gvcol_1 gvcol_key\">$displaymerit:</td><td class=\"gvcol_2 gvcol_val\">" . $charmerit->comment . "</td></tr>";
			
			}
		}
	}
	
	// Positions (add offices to mycharacter class)
	if (count($mycharacter->offices) > 0) {
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Positions:</td><td class=\"gvcol_2 gvcol_val\">";
		$offices = array();
		foreach ($mycharacter->offices as $office) {
			if ($office->visible == 'Y') {
				if ($office->domain == $mycharacter->domain)
					array_push($offices, $office->name);
				else
					array_push($offices, $office->name . " (" . $office->domain . ")");
			}	
		}
		$output .= implode("<br />", $offices);
		$output .= "</td></tr>";
	}
	
	
	$output .= "</table></td><td class=\"gvcol_2 gvcol_img\">\n";
	// Portrait
	$output .= "<img alt='Profile Image' src='" .  $mycharacter->portrait . "'>";
	
	$output .= "</td></tr>";
	$output .= "</table>";
	
	
	// change password and display name form
	if (isST() || $currentCharacter == $character) {
		$user = get_user_by('login', $character);
		$displayName = $user->display_name;
		$userID = $user->ID;
		
		$output .= "<div class='displayNameForm'><strong>Update Display Name:</strong>";
		$output .= "<form name=\"DISPLAY_NAME_UPDATE_FORM\" method='post'>";

		$output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"updateDisplayName\" />";
		$output .= "<input type='HIDDEN' name=\"USER_ID\" value=\"" . $userID . "\" />";
		
		$output .= "<table>\n";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Display Name:</td><td class=\"gvcol_2 gvcol_val\">";
		$output .= "<input type='text' size=50 maxlength=50 name=\"displayName\" value=\"" . $displayName . "\">";
		$output .= "</td>\n";
		$output .= "<td><input type='submit' name=\"displayNameUpdate\" value=\"Update\"></td>";
		$output .= "</tr>";
		$output .= "</table></form></div>\n";

		$output .= "<div class='PasswordForm'><strong>Update Password:</strong>";
		$output .= "<form name=\"PASSWORD_UPDATE_FORM\" method='post'>";

		$output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"updatePassword\" />";
		$output .= "<input type='HIDDEN' name=\"USER_ID\" value=\"" . $userID . "\" />";
		
		$output .= "<table>\n";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">New Password:</td><td class=\"gvcol_2 gvcol_val\">";
		$output .= "<input type=\"password\" name=\"newPassword1\">";
		$output .= "</td></tr>";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Confirm New Password:</td><td class=\"gvcol_2 gvcol_val\">";
		$output .= "<input type=\"password\" name=\"newPassword2\">";
		$output .= "</td></tr>";
		
		$output .= "<tr><td colspan=2 class=\"gvcol_1 gvcol_submit\">";
		$output .= "<input type='submit' name=\"passwordUpdate\" value=\"Update Password\">";
		$output .= "</td></tr>";

		$output .= "</table></form></div>\n";


		}
	
	return $output;
}

?>