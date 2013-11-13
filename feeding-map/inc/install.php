<?php

register_activation_hook(__FILE__, "feedingmap_install");
register_activation_hook(__FILE__, "feedingmap_install_data");

global $feedingmap_db_version;
$feedingmap_db_version = "1.0.4"; 

function feedingmap_db_check() {
    global $feedingmap_db_version;
	
    if (get_site_option( 'feedingmap_db_version' ) != $feedingmap_db_version) {
        feedingmap_install();
		feedingmap_install_data();
		
		update_option( "feedingmap_db_version", $feedingmap_db_version );
    }
}
add_action( 'plugins_loaded', 'feedingmap_db_check' );

function feedingmap_install() {
	global $wpdb;
	global $feedingmap_db_version;
	
	$installed_version = get_option( "feedingmap_db_version" );
	
	if( $installed_version != $feedingmap_db_version ) {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		// Table to define who controls each area - e.g. Clan or individual Vampire
		// Owner / Colour
		$current_table_name = FEEDINGMAP_TABLE_PREFIX . "OWNER";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9)	NOT NULL   AUTO_INCREMENT,
					NAME            VARCHAR(60)		NOT NULL,
					FILL_COLOUR     VARCHAR(7)		NOT NULL,
					VISIBLE         VARCHAR(1)		NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		//echo "<p>SQL: $sql</p>";

		// Table to define each area - e.g. glasgow ward
		// ID / Name / OWNER_ID / Description (for pop-up)
		$current_table_name = FEEDINGMAP_TABLE_PREFIX . "DOMAIN";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9)  NOT NULL   AUTO_INCREMENT,
					NAME            VARCHAR(60)   NOT NULL,
					OWNER_ID  		MEDIUMINT(9)  NOT NULL,
					DESCRIPTION     TINYTEXT      NOT NULL,
					COORDINATES     LONGTEXT      NOT NULL,
					VISIBLE         VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . FEEDINGMAP_TABLE_PREFIX . "domain_constraint_1` FOREIGN KEY (OWNER_ID) REFERENCES " . FEEDINGMAP_TABLE_PREFIX . "OWNER(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		//echo "<p>SQL: $sql</p>";
		
	}
	
}

function feedingmap_install_data() {
	global $wpdb;
	
	$wpdb->show_errors();
	
	$data = array (
		0 => array(	'ID'          => 1,
					'NAME'        => 'Unclaimed',
					'FILL_COLOUR' => '#FFFFFF',
					'VISIBLE'     => 'Y'
		),
		1 => array(	'ID'          => 2,
					'NAME'        => 'The Rack',
					'FILL_COLOUR' => '#000000',
					'VISIBLE'     => 'Y'
		),
	);
	$sql = "select ID from " . FEEDINGMAP_TABLE_PREFIX . "OWNER;";
	$exists = count($wpdb->get_results($sql));
	if (!$exists) 
		foreach ($data as $key => $entry) {
				$rowsadded = $wpdb->insert( FEEDINGMAP_TABLE_PREFIX . "OWNER", $entry);
		}


	
}

?>