<?php
/* ---------------------------------------------------------------
<CHARACTER>
	<NAME>Character Name</NAME>
	<PLAYER>Player Name</PLAYER>
	<GENERATION>Generation</GENERATION>

</CHARACTER>
------------------------------------------------------------------ */

require_once GVLARP_CHARACTER_URL . 'inc/classes.php';

add_action( 'template_redirect', 'gv_android_redirect' );

function gv_android_redirect () {
	global $wpdb;

    if( $_SERVER['REQUEST_URI'] == get_gvconfig('ANDROID_LINK') && is_user_logged_in() ) {
		$character = establishCharacter('Ugly Duckling');
		$characterID = establishCharacterID($character);
		$mycharacter = new larpcharacter();
		$mycharacter->load($characterID);

		header("Content-type: text/xml");
		echo "<?xml version='1.0' encoding='ISO-8859-1'?>\n";
		
		echo "<CHARACTER>\n";

		/* Character Info */
		echo output_xlmtag("NAME",         $mycharacter->name);
		echo output_xlmtag("PLAYER",       $mycharacter->player);
		echo output_xlmtag("GENERATION",   $mycharacter->generation);
		echo output_xlmtag("PUBLIC_CLAN",  $mycharacter->clan);
		echo output_xlmtag("PRIVATE_CLAN", $mycharacter->private_clan);
		echo output_xlmtag("CURRENT_XP",   $mycharacter->current_experience);
		echo output_xlmtag("CURRENT_WP",   $mycharacter->current_willpower);
		echo output_xlmtag("WILLPOWER",    $mycharacter->willpower);
		echo output_xlmtag("BLOODPOOL",    $mycharacter->bloodpool);
		echo output_xlmtag("PATH_OF_ENLIGHTENMENT", $mycharacter->path_of_enlightenment);
		echo output_xlmtag("PATH_LEVEL",   $mycharacter->path_rating);
		echo output_xlmtag("DOB",          $mycharacter->date_of_birth);
		echo output_xlmtag("DOE",          $mycharacter->date_of_embrace);
		echo output_xlmtag("SIRE",         $mycharacter->sire);
		echo output_xlmtag("CLAN_FLAW",    $mycharacter->clan_flaw);
		
		if (get_gvconfig('USE_NATURE_DEMEANOUR') == 'Y') {
			echo output_xlmtag("NATURE",    $mycharacter->nature);
			echo output_xlmtag("DEMEANOUR", $mycharacter->demeanour);
		}
		
		/* Attributes */
		echo "\t<ATTRIBUTES>\n";
		echo "\t\t<PHYSICAL>\n";
		foreach ($mycharacter->getAttributes("Physical") as $attribute) {
			echo "\t\t\t<ATTRIBUTE>\n";
			echo output_xlmtag("NAME",       $attribute->name);
			echo output_xlmtag("LEVEL",      $attribute->level);
			echo output_xlmtag("ORDER",      $attribute->ordering);
			echo output_xlmtag("SPECIALTY",  $attribute->specialty);
			echo "\t\t\t</ATTRIBUTE>\n";
		}
		echo "\t\t</PHYSICAL>\n"; 
		echo "\t\t<SOCIAL>\n";
		foreach ($mycharacter->getAttributes("Social") as $attribute) {
			echo "\t\t\t<ATTRIBUTE>\n";
			echo output_xlmtag("NAME",       $attribute->name);
			echo output_xlmtag("LEVEL",      $attribute->level);
			echo output_xlmtag("ORDER",      $attribute->ordering);
			echo output_xlmtag("SPECIALTY",  $attribute->specialty);
			echo "\t\t\t</ATTRIBUTE>\n";
		}
		echo "\t\t</SOCIAL>\n"; 
		echo "\t\t<MENTAL>\n";
		foreach ($mycharacter->getAttributes("Mental") as $attribute) {
			echo "\t\t\t<ATTRIBUTE>\n";
			echo output_xlmtag("NAME",       $attribute->name);
			echo output_xlmtag("LEVEL",      $attribute->level);
			echo output_xlmtag("ORDER",      $attribute->ordering);
			echo output_xlmtag("SPECIALTY",  $attribute->specialty);
			echo "\t\t\t</ATTRIBUTE>\n";
		}
		echo "\t\t</MENTAL>\n"; 
		echo "\t</ATTRIBUTES>\n";
		
		/* Abilities */
		$abilities = $mycharacter->getAbilities();
		echo "\t<ABILITIES>\n";
		foreach ($abilities as $ability) {
			echo "\t\t<ABILITY>\n";
			echo output_xlmtag("NAME",       $ability->skillname);
			echo output_xlmtag("LEVEL",      $ability->level);
			echo output_xlmtag("GROUPING",   $ability->grouping);
			echo output_xlmtag("SPECIALTY",  $ability->specialty);
			echo "\t\t</ABILITY>\n";
		}
		echo "\t</ABILITIES>\n";
		
		/* Backgrounds */
		$backgrounds =  $mycharacter->getBackgrounds();
		echo "\t<BACKGROUNDS>\n";
		foreach ($backgrounds as $background) {
			echo "\t\t<BACKGROUND>\n";
			echo output_xlmtag("NAME",       $background->background);
			echo output_xlmtag("LEVEL",      $background->level);
			echo output_xlmtag("SECTOR",     $background->sector);
			echo output_xlmtag("COMMENT",    $background->comment);
			echo "\t\t</BACKGROUND>\n";
		}
		echo "\t</BACKGROUNDS>\n";
		
		/* Disciplines */
		$disciplines =  $mycharacter->getDisciplines();
		echo "\t<DISCIPLINES>\n";
		foreach ($disciplines as $discipline) {
			echo "\t\t<DISCIPLINE>\n";
			echo output_xlmtag("NAME",       $discipline->name);
			echo output_xlmtag("LEVEL",      $discipline->level);
			echo "\t\t</DISCIPLINE>\n";
		}
		echo "\t</DISCIPLINES>\n";

		/* Merits and Flaws */
		$merits =  $mycharacter->meritsandflaws;
		echo "\t<MERITSANDFLAWS>\n";
		foreach ($merits as $merit) {
			echo "\t\t<MERITFLAW>\n";
			echo output_xlmtag("NAME",       $merit->name);
			echo output_xlmtag("LEVEL",      $merit->level);
			echo output_xlmtag("COMMENT",    $merit->comment);
			echo "\t\t</MERITFLAW>\n";
		}
		echo "\t</MERITSANDFLAWS>\n";

		/* Virtues */
		$virtues =  $mycharacter->getAttributes("Virtue");
		echo "\t<VIRTUES>\n";
		foreach ($virtues as $virtue) {
			echo "\t\t<VIRTUE>\n";
			echo output_xlmtag("NAME",       $virtue->name);
			echo output_xlmtag("LEVEL",      $virtue->level);
			echo output_xlmtag("ORDER",      $virtue->ordering);
			echo "\t\t</VIRTUE>\n";
		}
		echo "\t</VIRTUES>\n";
		
		/* Rituals */
		$rituals = $mycharacter->rituals;
		echo "\t<RITUALS>\n";
		foreach ($rituals as $majikdiscipline => $rituallist) {
			foreach ($rituallist as $ritual) {
				echo "\t\t<RITUAL>\n";
				echo output_xlmtag("NAME",       $ritual[name]);
				echo output_xlmtag("LEVEL",      $ritual[level]);
				echo output_xlmtag("DISCIPLINE", $majikdiscipline);
				echo "\t\t</RITUAL>\n";
			} 
		}
		echo "\t</RITUALS>\n";
		
		
		/* Combo Disciplines */
		$combodisciplines = $mycharacter->combo_disciplines;
		echo "\t<COMBODISCIPLINES>\n";
		if (count($combodisciplines) > 0) {
			foreach ($combodisciplines as $discipline) {
				echo output_xlmtag("DISCIPLINE", $discipline);
			}
		}
		echo "\t</COMBODISCIPLINES>\n";
		
		
		echo "</CHARACTER>\n";
		
		exit;
	} 
}

function output_xlmtag ($tagname, $value) {
	return (empty($value) ? "" : "\t\t\t\t<$tagname>$value</$tagname>\n");
}

function get_gvconfig ($field) {

        global $wpdb;
        $sql = "SELECT $field FROM " . GVLARP_TABLE_PREFIX . "CONFIG";
        $configs = $wpdb->get_results($sql);

        return $configs[0]->$field;

}
?>