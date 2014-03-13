<?php

function vtm_count_XP4approval() {
	global $wpdb;
	
	$sql = "SELECT COUNT(ID) as count
			FROM 
				" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND";
	$result = $wpdb->get_results($sql);
	
	return $result[0]->count;
}
function vtm_count_BG4approval() {
	global $wpdb;
	
	$count = 0;	
	$sql = "SELECT COUNT(ID) as count
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND
			WHERE NOT(PENDING_DETAIL = '') AND DENIED_DETAIL = ''";
	$result = $wpdb->get_results($sql);
	$count += $result[0]->count;
	
	$sql = "SELECT COUNT(ID) as count
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_MERIT
			WHERE NOT(PENDING_DETAIL = '') AND DENIED_DETAIL = ''";
	$result = $wpdb->get_results($sql);
	$count += $result[0]->count;
	
	$sql = "SELECT COUNT(ID) as count
			FROM 
				" . VTM_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND
			WHERE NOT(PENDING_DETAIL = '') AND DENIED_DETAIL = ''";
	$result = $wpdb->get_results($sql);
	$count += $result[0]->count;
	//echo "<p>SQL: $sql</p>";
	//print_r($result);
	
	return $count;
}


/* WORDPRESS TOOLBAR 
----------------------------------------------------------------- */
function vtm_toolbar_link_admin( $wp_admin_bar ) {

	if ( current_user_can( 'manage_options' ) )  {
		$args = array(
			'id'    => 'vtmcharacters',
			'title' => 'Characters',
			'href'  => admin_url('admin.php?page=vtmcharacter-plugin'),
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'vtmcharacters2',
			'title' => 'Character Admin',
			'href'  => admin_url('admin.php?page=vtmcharacter-plugin'),
			'parent' => 'vtmcharacters',
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'vtmplayers',
			'title' => 'Player Admin',
			'href'  => admin_url('admin.php?page=vtmcharacter-player'),
			'parent' => 'vtmcharacters',
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'vtmbg',
			'title' => 'Approve Backgrounds (' . vtm_count_BG4approval() . ')',
			'href'  => admin_url('admin.php?page=vtmcharacter-bg'),
			'parent' => 'vtmcharacters',
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'vtmspendxp',
			'title' => 'Approve Spends (' . vtm_count_XP4approval() . ')',
			'href'  => admin_url('admin.php?page=vtmcharacter-xp'),
			'parent' => 'vtmcharacters',
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'vtmassignxp',
			'title' => 'Assign Experience',
			'href'  => admin_url('admin.php?page=vtmcharacter-xpassign'),
			'parent' => 'vtmcharacters',
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );

		$args = array(
			'id'    => 'vtmdata',
			'title' => 'Data Tables',
			'href'  => admin_url('admin.php?page=vtmcharacter-data'),
			'parent' => 'vtmcharacters',
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );


		$args = array(
			'id'    => 'vtmreport',
			'title' => 'Reports',
			'href'  => admin_url('admin.php?page=vtmcharacter-report'),
			'parent' => 'vtmcharacters',
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'vtmconfig',
			'title' => 'Configuration',
			'href'  => admin_url('admin.php?page=vtmcharacter-config'),
			'parent' => 'vtmcharacters',
			'meta'  => array( 'class' => 'vtm-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
	}
}
add_action( 'admin_bar_menu', 'vtm_toolbar_link_admin', 999 );


?>