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
		echo output_xlmtag("NAME",       $mycharacter->name);
		echo output_xlmtag("PLAYER",     $mycharacter->player);
		echo output_xlmtag("GENERATION", $mycharacter->generation);
		echo output_xlmtag("PUBLIC_CLAN",  $mycharacter->clan);
		echo output_xlmtag("PRIVATE_CLAN", $mycharacter->private_clan);
		
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
		
		echo "</CHARACTER>\n";
		
		exit;
	} 
}

function output_xlmtag ($tagname, $value) {
	return "\t\t\t\t<$tagname>$value</$tagname>\n";
}

function get_gvconfig ($field) {

        global $wpdb;
        $sql = "SELECT $field FROM " . GVLARP_TABLE_PREFIX . "CONFIG";
        $configs = $wpdb->get_results($sql);

        return $configs[0]->$field;

}
?>