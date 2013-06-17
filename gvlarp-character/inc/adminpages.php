<?php

/* Header */

add_action( 'admin_menu', 'register_character_menu' );

function register_character_menu() {
	add_menu_page( "Character Plugin Options", "Characters", "manage_options", "gvcharacter-plugin", "character_options");
	/* add_submenu_page( "gvcharacter-plugin", "Players", "Players", "manage_options", "gvcharacter-players", "character_players" ); */
	add_submenu_page( "gvcharacter-plugin", "Database Tables", "Data", "manage_options", "gvcharacter-data", "character_datatables" ); 
	/* add_submenu_page( "gvcharacter-plugin", "Reports", "Reports", "manage_options", "gvcharacter-reports", "character_reports" ); */
	add_submenu_page( "gvcharacter-plugin", "Configuration", "Configuration", "manage_options", "gvcharacter-config", "character_config" ); 
}

function character_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
	echo '<p>Character Administration will eventually go here.</p>';
	echo '</div>';
}

function character_datatables() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
	echo '<p>Here is where the form would go if I actually had data tables.</p>';
	echo '</div>';
} 

function character_reports() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
	echo '<p>Downloadable reports will go here.</p>';
	echo '</div>';
} 


function character_config() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
	echo '<p>Configuration options will go here.</p>';
	echo '</div>';
} 

function character_players() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
	echo '<p>Player Admin will eventually go here.</p>';
	echo '</div>';
} 

?>