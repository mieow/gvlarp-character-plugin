<?php
/**
 * You can use this file to add you custom hooks to EasyMail plugin.
 *
 * To make loading this file you have to rename it to 'alo-easymail_custom-hooks.php'.
 * Some examples of custom hooks on http://www.eventualo.net/blog/wp-alo-easymail-newsletter/
 *
 * IMPORTANT! To avoid the loss of the file when you use the automatic WP upgrade,
 * I suggest that you move the file into folder /wp-content/mu-plugins 
 * (if the directory doesn't exist, simply create it).
 *
*/




/*******************************************************************************
 * 
 * EXAMPLE 
 *
 * The following set of functions adds a new placeholder that includes the latest 
 * published posts inside newsletter
 *
 * @since: 2.0
 *
 ******************************************************************************/


/**
 * Add placeholder to table in new/edit newsletter screen
 *
 */
function custom_easymail_placeholders ( $placeholders ) {
	$placeholders["custom_gvlarp"] = array (
		"title" 		=> __("GVLARP tags", "alo-easymail"),
		"tags" 			=> array (
			"[GV_CALENDAR]"			=> "Upcoming event dates", 
			/*"[GV_MESSAGES]"			=> "Current Inbox Status",*/
			"[GV_CHARNAME]"			=> "Character Name",
			"[GV_PATHNAME]"			=> "Path Name",
			"[GV_PATHRATE]"			=> "Character rating on the path",
			"[GV_XPTOTAL]"			=> "Total XP available to spend"
		)
	);
	return $placeholders;
}
add_filter ( 'alo_easymail_newsletter_placeholders_table', 'custom_easymail_placeholders' );



/**
 * Replace the placeholder when the newsletter is sending 
 * @param	str		the newsletter text
 * @param	obj		newsletter object, with all post values
 * @param	obj		recipient object, with following properties: ID (int), newsletter (int: recipient ID), email (str), result (int: 1 if successfully sent or 0 if not), lang (str: 2 chars), unikey (str), name (str: subscriber name), user_id (int/false: user ID if registered user exists), subscriber (int: subscriber ID), firstname (str: firstname if registered user exists, otherwise subscriber name)
 * @param	bol    	if apply "the_content" filters: useful to avoid recursive and infinite loop
 */ 
function custom_easymail_placeholders_get_gvlarp_calendar ( $content, $newsletter, $recipient, $stop_recursive_the_content=false ) {  
	if ( !is_object( $recipient ) ) $recipient = new stdClass();
	if ( empty( $recipient->lang ) ) $recipient->lang = alo_em_short_langcode ( get_locale() );

	$gvevents = eme_get_events_list("limit=2&scope=future&order=ASC&echo=0&format=<li>#_LINKEDNAME on #j #M #Y at #H:#i</li>");

	$content = str_replace("[GV_CALENDAR]", $gvevents , $content);
   
	return $content;	
}
/*add_filter ( 'alo_easymail_newsletter_content',  'custom_easymail_placeholders_get_gvlarp_calendar', 10, 4 ); */


function custom_easymail_placeholders_get_gvlarp_charactername ( $content, $newsletter, $recipient, $stop_recursive_the_content=false ) {  
	if ( !is_object( $recipient ) ) $recipient = new stdClass();
	if ( empty( $recipient->lang ) ) $recipient->lang = alo_em_short_langcode ( get_locale() );

	$gvtext = get_character_from_email($recipient->email, 'name');
	$content = str_replace("[GV_CHARNAME]", $gvtext , $content);

	return $content;	
}
add_filter ( 'alo_easymail_newsletter_content',  'custom_easymail_placeholders_get_gvlarp_charactername', 10, 4 );

function custom_easymail_placeholders_get_gvlarp_pathname ( $content, $newsletter, $recipient, $stop_recursive_the_content=false ) {  
	if ( !is_object( $recipient ) ) $recipient = new stdClass();
	if ( empty( $recipient->lang ) ) $recipient->lang = alo_em_short_langcode ( get_locale() );

	$gvtext = get_character_from_email($recipient->email, 'path');
	if ($gvtext == "") $gvtext = "Humanity";

	$content = str_replace("[GV_PATHNAME]", $gvtext , $content);

	return $content;	
}
add_filter ( 'alo_easymail_newsletter_content',  'custom_easymail_placeholders_get_gvlarp_pathname', 10, 4 );

function custom_easymail_placeholders_get_gvlarp_pathvalue ( $content, $newsletter, $recipient, $stop_recursive_the_content=false ) {  
	if ( !is_object( $recipient ) ) $recipient = new stdClass();
	if ( empty( $recipient->lang ) ) $recipient->lang = alo_em_short_langcode ( get_locale() );

	$gvtext = get_character_from_email($recipient->email, 'rating');
	
	if (empty($gvtext)) $gvtext = "50";

	$content = str_replace("[GV_PATHRATE]", $gvtext , $content);

	return $content;	
}
add_filter ( 'alo_easymail_newsletter_content',  'custom_easymail_placeholders_get_gvlarp_pathvalue', 10, 4 ); 

function custom_easymail_placeholders_get_gvlarp_xptotal ( $content, $newsletter, $recipient, $stop_recursive_the_content=false ) {  
	if ( !is_object( $recipient ) ) $recipient = new stdClass();
	if ( empty( $recipient->lang ) ) $recipient->lang = alo_em_short_langcode ( get_locale() );

	$gvtext = get_character_from_email($recipient->email, 'xptotal');
	if (empty($gvtext)) $gvtext = "0";

	$content = str_replace("[GV_XPTOTAL]", $gvtext , $content);

	return $content;	
}
add_filter ( 'alo_easymail_newsletter_content',  'custom_easymail_placeholders_get_gvlarp_xptotal', 10, 4 );
