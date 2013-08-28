<?php


/* WORDPRESS TOOLBAR 
----------------------------------------------------------------- */
function toolbar_link_gvadmin( $wp_admin_bar ) {

	if ( current_user_can( 'manage_options' ) )  {
		$args = array(
			'id'    => 'gvcharacters',
			'title' => 'Characters',
			/* 'href'  => admin_url('admin.php?page=gvcharacter-plugin'), */
			'meta'  => array( 'class' => 'my-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvdata',
			'title' => 'Approve Backgrounds',
			'href'  => admin_url('admin.php?page=gvcharacter-bg'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvspendxp',
			'title' => 'Approve Spends',
			'href'  => admin_url('admin.php?page=gvcharacter-xp'),
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