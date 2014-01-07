<?php

function count_XP4approval() {
	global $wpdb;
	
	$sql = "SELECT COUNT(ID) as count
			FROM 
				" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND";
	$sql = $wpdb->prepare($sql, $characterID, $table);
	$result = $wpdb->get_results($sql);
	
	return $result[0]->count;
}
function count_BG4approval() {
	global $wpdb;
	
	$count = 0;
	
	$sql = "SELECT COUNT(ID) as count
			FROM 
				" . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND
			WHERE NOT(PENDING_DETAIL = '') AND DENIED_DETAIL = ''";
	$sql = $wpdb->prepare($sql, $characterID, $table);
	$result = $wpdb->get_results($sql);
	$count += $result[0]->count;
	
	$sql = "SELECT COUNT(ID) as count
			FROM 
				" . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT
			WHERE NOT(PENDING_DETAIL = '') AND DENIED_DETAIL = ''";
	$sql = $wpdb->prepare($sql, $characterID, $table);
	$result = $wpdb->get_results($sql);
	$count += $result[0]->count;
	
	$sql = "SELECT COUNT(ID) as count
			FROM 
				" . GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND
			WHERE NOT(PENDING_DETAIL = '') AND DENIED_DETAIL = ''";
	$sql = $wpdb->prepare($sql, $characterID, $table);
	$result = $wpdb->get_results($sql);
	$count += $result[0]->count;
	//echo "<p>SQL: $sql</p>";
	//print_r($result);
	
	return $count;
}


/* WORDPRESS TOOLBAR 
----------------------------------------------------------------- */
function toolbar_link_gvadmin( $wp_admin_bar ) {

	if ( current_user_can( 'manage_options' ) )  {
		$args = array(
			'id'    => 'gvcharacters',
			'title' => 'Characters',
			'href'  => admin_url('admin.php?page=gvcharacter-plugin'),
			'meta'  => array( 'class' => 'my-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvcharacters2',
			'title' => 'Character Admin',
			'href'  => admin_url('admin.php?page=gvcharacter-plugin'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvplayers',
			'title' => 'Player Admin',
			'href'  => admin_url('admin.php?page=gvcharacter-player'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvbg',
			'title' => 'Approve Backgrounds (' . count_BG4approval() . ')',
			'href'  => admin_url('admin.php?page=gvcharacter-bg'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvspendxp',
			'title' => 'Approve Spends (' . count_XP4approval() . ')',
			'href'  => admin_url('admin.php?page=gvcharacter-xp'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvassignxp',
			'title' => 'Assign Experience',
			'href'  => admin_url('admin.php?page=gvcharacter-xpassign'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );

		$args = array(
			'id'    => 'gvdata',
			'title' => 'Data Tables',
			'href'  => admin_url('admin.php?page=gvcharacter-data'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );


		$args = array(
			'id'    => 'gvreport',
			'title' => 'Reports',
			'href'  => admin_url('admin.php?page=gvcharacter-report'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvconfig',
			'title' => 'Configuration',
			'href'  => admin_url('admin.php?page=gvcharacter-config'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
	}
}
add_action( 'admin_bar_menu', 'toolbar_link_gvadmin', 999 );


?>