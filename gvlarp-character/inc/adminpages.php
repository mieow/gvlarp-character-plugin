<?php
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
add_action( 'admin_menu', 'register_character_menu' );

function admin_css() { 
	wp_enqueue_style('my-admin-style', plugins_url('css/style-admin.css',dirname(__FILE__)));
}
add_action('admin_enqueue_scripts', 'admin_css');

function register_character_menu() {
	add_menu_page( "Character Plugin Options", "Characters", "manage_options", "gvcharacter-plugin", "character_options");
	add_submenu_page( "gvcharacter-plugin", "Database Tables", "Data", "manage_options", "gvcharacter-data", "character_datatables" );  
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
	?>
	<div class="wrap">
		<h2>Database Tables</h2>
		<script type="text/javascript">
			function tabSwitch(tab) {
				document.getElementById('gv-merits').style.display = 'none';
				document.getElementById('gv-flaws').style.display = 'none';
				document.getElementById('gv-rituals').style.display = 'none';
				document.getElementById(tab).style.display = '';
				return false;
			}
		</script>
		<div class="gvadmin_nav">
			<ul>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-merits');">Merits</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-flaws');">Flaws</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-rituals');">Rituals</a></li>
			</ul>
		</div>
		<!-- <p>tab: <?php echo $_REQUEST['tab'] ?>, m: <?php tabdisplay("merits"); ?>, f: <?php tabdisplay("flaws"); ?></p> -->
		<div class="gvadmin_content">
			<div id="gv-merits" <?php tabdisplay("merits"); ?>>
				<h1>Merits</h1>
				<?php render_meritflaw_page("merit"); ?>
			</div>
			<div id="gv-flaws" <?php tabdisplay("flaws"); ?>>
				<h1>Flaws</h1>
				<?php render_meritflaw_page("flaw"); ?>
			</div>
			<div id="gv-rituals" <?php tabdisplay("rituals"); ?>>Rituals go here</div>
		</div>

	</div>
	
	<?php
}

function tabdisplay($tab) {

	$display = "style='display:none'";

	if (isset($_REQUEST['tab'])) {
		if ($_REQUEST['tab'] == 'merit' && $tab == "merits")
			$display = "class='merit'";
		else if ($_REQUEST['tab'] == 'flaw' && $tab == "flaws")
			$display = "class='flaw'";
		else if ($_REQUEST['tab'] == 'Rituals' && $tab == "rituals")
			$display = "class='ritual'";
	} else if ($tab == "merits") {
		$display = "class='default'";
	}
		
	print $display;
		
}


/* DISPLAY TABLES 
-------------------------------------------------- */
function render_meritflaw_page($type){

    $testListTable[$type] = new gvadmin_meritsflaws_table();
	$doaction = merit_input_validation($type);
	/* echo "<p>Merit action: $doaction</p>"; */
	
	if ($doaction == "add-$type") {
		$testListTable[$type]->add_merit($_REQUEST[$type . '_name'], $_REQUEST[$type . '_group'], $_REQUEST[$type . '_sourcebook'], 
									$_REQUEST[$type . '_page_number'], $_REQUEST[$type . '_cost'], $_REQUEST[$type . '_xp_cost'], 
									$_REQUEST[$type . '_multiple'], $_REQUEST[$type . '_visible'], $_REQUEST[$type . '_desc']);
									
	}
	if ($doaction == "save-edit-$type") { 
		$testListTable[$type]->edit_merit($_REQUEST[$type . '_id'], $_REQUEST[$type . '_name'], $_REQUEST[$type . '_group'], 
									$_REQUEST[$type . '_sourcebook'], $_REQUEST[$type . '_page_number'], $_REQUEST[$type . '_cost'], 
									$_REQUEST[$type . '_xp_cost'], $_REQUEST[$type . '_multiple'], $_REQUEST[$type . '_visible'],
									$_REQUEST[$type . '_desc']);
	} 
	
	render_meritflaw_add_form($type, $doaction);
	
    $testListTable[$type]->prepare_items($type);

   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="<?php print $type ?>-filter" method="get">
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="<?php print $type ?>" />
 		<?php $testListTable[$type]->display() ?>
	</form>

    <?php
}

/* CLASSES
------------------------------------------------ */


class gvadmin_meritsflaws_table extends WP_List_Table {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'merit',     
            'plural'    => 'merits',    
            'ajax'      => false        
        ) );
		
		add_action('delete_merit', array($this, 'delete_merit'), 10, 1);
		/* add_action('showhide_merit', array($this, 'gvlarp_showhide_merit'), 10, 2); */
		/* add_action('edit_merit', array($this, 'gvlarp_edit_merit'), 10, 1); */
        
    }
	
	function delete_merit($selectedID) {
		global $wpdb;
		
		/* Check if merit id in use */
		$sql = "select characters.NAME 
			from " . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT charmerits , " . GVLARP_TABLE_PREFIX . "CHARACTER characters
			where charmerits.MERIT_ID = $selectedID and charmerits.CHARACTER_ID = characters.ID;";
		$isused = $wpdb->get_results($wpdb->prepare($sql));
		
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this {$this->type} is being used in the following characters:";
			echo "<ul>";
			foreach ($isused as $character)
				echo "<li style='color:red'>{$character->NAME}</li>";
			echo "</ul></p>";
		} else {
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "MERIT where ID = $selectedID;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql));
		
			/* print_r($result); */
			echo "<p style='color:green'>Deleted item $selectedID</p>";
		}
	}
 	function gvlarp_showhide_merit($selectedID, $showhide) {
		global $wpdb;
		
		/* echo "id: $selectedID, setting: $showhide"; */
		
		$wpdb->show_errors();
		
		$visiblity = $showhide == 'hide' ? 'N' : 'Y';
		
		$result = $wpdb->update( GVLARP_TABLE_PREFIX . "MERIT", 
			array (
				'VISIBLE' => $visiblity
			), 
			array (
				'ID' => $selectedID
			)
		);
		
		if ($result) 
			echo "<p style='color:green'>" . ucfirst($showhide) . " item $selectedID successful</p>";
		else if ($result === 0)
			echo "<p style='color:orange'>Item $selectedID has not been changed</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Item $selectedID could not be updated</p>";
		}
	}
	
 	function add_merit($meritname, $meritgroup, $sourcebookid, $pagenum,
						$cost, $xp_cost, $multiple, $visible, $description) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $meritname,
						'DESCRIPTION' => $description,
						'GROUPING' => $meritgroup,
						'SOURCE_BOOK_ID' => $sourcebookid,
						'PAGE_NUMBER' => $pagenum,
						'COST'  => $cost,
						'VALUE' => $cost,
						'XP_COST' => $xp_cost,
						'MULTIPLE' => $multiple,
						'VISIBLE' => $visible
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "MERIT",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
						'%d',
						'%d',
						'%d',
						'%d',
						'%d',
						'%s',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $meritname could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added $meritgroup merit/flaw '$meritname' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_merit($meritid, $meritname, $meritgroup, $sourcebookid, $pagenum,
						$cost, $xp_cost, $multiple, $visible, $description) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $meritname,
						'DESCRIPTION' => $description,
						'GROUPING' => $meritgroup,
						'SOURCE_BOOK_ID' => $sourcebookid,
						'PAGE_NUMBER' => $pagenum,
						'COST'  => $cost,
						'VALUE' => $cost,
						'XP_COST' => $xp_cost,
						'MULTIPLE' => $multiple,
						'VISIBLE' => $visible
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "MERIT",
					$dataarray,
					array (
						'ID' => $meritid
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $meritname</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $meritname</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $meritname ($meritid)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'GROUPING':
                return $item->$column_name;
            case 'COST':
                return $item->$column_name;
             case 'XP_COST':
                return $item->$column_name;
            case 'PAGE_NUMBER':
                return $item->$column_name;
            case 'SOURCEBOOK':
                return $item->$column_name;
           default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
	
		$act = ($item->VISIBLE === 'Y') ? 'hide' : 'show';
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&merit=%s&tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&action=%s&merit=%s&tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
            $act        => sprintf('<a href="?page=%s&action=%s&merit=%s&tab=%s">%s</a>',$_REQUEST['page'],$act,$item->ID, $this->type, ucfirst($act)),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
            $item->ID,
            $this->row_actions($actions)
        );
    }
   
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  
            $item->ID               
        );
    }

    function column_visible($item){
		return ($item->VISIBLE == "Y") ? "Yes" : "No";
    }
	function column_multiple($item){
		return ($item->MULTIPLE == "Y") ? "Yes" : "No";
    }
   

    function get_columns(){
        $columns = array(
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'DESCRIPTION'  => 'Description',
            'GROUPING'     => 'Grouping/Type',
            'COST'         => 'Freebie Cost',
            'XP_COST'      => 'Experience Cost',
            'MULTIPLE'     => 'Can be bought multiple times?',
            'SOURCEBOOK'   => 'Source Book',
            'PAGE_NUMBER'  => 'Source Page',
            'VISIBLE'      => 'Visible to Players'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',false),
            'GROUPING'    => array('GROUPING',false),
            'COST'  	  => array('COST',false),
            'XP_COST'     => array('XP_COST',false)
        );
        return $sortable_columns;
    }
	
	
	
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete',
			'hide'      => 'Hide',
 			'show'      => 'Show'
       );
        return $actions;
    }
    
    function process_bulk_action() {
        
		/* echo "<p>Bulk action " . $this->current_action() . ", currently on tab {$_REQUEST['tab']} and will do action if {$this->type}.</p>"; */
		
        if( 'delete'===$this->current_action() && $_REQUEST['tab'] == $this->type) {
			if ('string' == gettype($_REQUEST['merit'])) {
				$this->delete_merit($_REQUEST['merit']);
			} else {
				foreach ($_REQUEST['merit'] as $merit) {
					$this->delete_merit($merit);
				}
			}
        }
		
        if( 'hide'===$this->current_action() && $_REQUEST['tab'] == $this->type ) {
			if ('string' == gettype($_REQUEST['merit'])) {
				$this->gvlarp_showhide_merit($_REQUEST['merit'], "hide");
			} else {
				foreach ($_REQUEST['merit'] as $merit) {
					$this->gvlarp_showhide_merit($merit, "hide");
				}
			}
        }
        if( 'show'===$this->current_action() && $_REQUEST['tab'] == $this->type ) {
			if ('string' == gettype($_REQUEST['merit'])) {
				$this->gvlarp_showhide_merit($merit, "show");
			} else {
				foreach ($_REQUEST['merit'] as $merit) {
					$this->gvlarp_showhide_merit($merit, "show");
				}
			}
        }
    }
	
	function extra_tablenav($which) {
		if ($which == 'top') {

			echo "<div class='gvfilter'>";
			/* Select if visible */
			echo "<span>Visiblity to Players: </span>";
			if ( !empty( $this->filter_visible ) ) {
				echo "<select name='{$this->type}_filter'>";
				foreach( $this->filter_visible as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_visible, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			/* Select Grouping */
			echo "<span>Group: </span>";
			if ( !empty( $this->filter_group ) ) {
				echo "<select name='{$this->type}_group'>";
				foreach( $this->filter_group as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_group, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			/* Select if multiple */
			echo "<span>Multiple: </span>";
			if ( !empty( $this->filter_multiple ) ) {
				echo "<select name='{$this->type}_multiple'>";
				foreach( $this->filter_multiple as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_multiple, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			submit_button( 'Filter', 'secondary', false, false );
			echo "</div>";
		}
	}
    
    
    function prepare_items($type) {
        global $wpdb; 

        /* $per_page = 20; */
        
        $columns = $this->get_columns();
        $hidden = array('VALUE');
        $sortable = $this->get_sortable_columns();
        
		/* setup filters here */
		$this->filter_visible = array(
				'all' => 'Both',
				'y' => 'Visible',
				'n'  => 'Not Visible',
			);
		$this->filter_multiple = array(
				'all' => 'All',
				'y' => 'Yes',
				'n'  => 'No',
			);
			
		$sql = "SELECT DISTINCT GROUPING FROM " . GVLARP_TABLE_PREFIX . "MERIT merit;";
		$groups =$wpdb->get_results($wpdb->prepare($sql));
		$grouparray = array(
				'all'    => 'All'
			);
		foreach ($groups as $group) {
			foreach ($group as $groupitem) {
				$grouparray = array_merge($grouparray, array(sanitize_key($groupitem) => $groupitem));
			} 
		}
		$grouparray = array_change_key_case($grouparray,CASE_LOWER);
		/* print_r($grouparray); */
		$this->filter_group = $grouparray;
			
		if ( isset( $_REQUEST[$type . '_filter'] ) && array_key_exists( $_REQUEST[$type . '_filter'], $this->filter_visible ) ) {
			$this->active_filter_visible = sanitize_key( $_REQUEST[$type . '_filter'] );
		} else {
			$this->active_filter_visible = 'all';
		}
		if ( isset( $_REQUEST[$type . '_group'] ) && array_key_exists( $_REQUEST[$type . '_group'], $this->filter_group ) ) {
			$this->active_filter_group = sanitize_key( $_REQUEST[$type . '_group'] );
		} else {
			$this->active_filter_group = 'all';
		}
		if ( isset( $_REQUEST[$type . '_multiple'] ) && array_key_exists( $_REQUEST[$type . '_multiple'], $this->filter_multiple ) ) {
			$this->active_filter_multiple = sanitize_key( $_REQUEST[$type . '_multiple'] );
		} else {
			$this->active_filter_multiple = 'all';
		}
			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select merit.ID, merit.NAME as NAME, merit.DESCRIPTION as DESCRIPTION, merit.GROUPING as GROUPING,
						merit.COST as COST, merit.XP_COST as XP_COST, merit.MULTIPLE as MULTIPLE,
						books.NAME as SOURCEBOOK, merit.PAGE_NUMBER as 	PAGE_NUMBER,
						merit.VISIBLE as VISIBLE
						from " . GVLARP_TABLE_PREFIX. "MERIT merit, " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books where ";
		if ($type == "merit") {
			$sql .= "merit.value >= 0";
		} else {
			$sql .= "merit.value < 0";
		}
		
		if ( "all" !== $this->active_filter_visible)
			$sql .= " AND merit.visible = '" . $this->active_filter_visible . "'";
		if ( "all" !== $this->active_filter_multiple)			
			$sql .= " AND merit.multiple = '" . $this->active_filter_multiple . "'";
		if ( "all" !== $this->active_filter_group )			
			$sql .= " AND merit.grouping = '" . $this->active_filter_group . "'";
		$sql .= " AND books.ID = merit.SOURCE_BOOK_ID";
		
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY merit.{$_REQUEST['orderby']} {$_REQUEST['order']}";
		
		$sql .= ";";
		/* echo "<p>SQL: " . $sql . "</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
        
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        
        /* $data = array_slice($data,(($current_page-1)*$per_page),$per_page); */
        
        $this->items = $data;
        
        /* $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                  
            'total_pages' => ceil($total_items/$per_page)
        ) ); */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
    }
	
	/* Update this so moving between pages isn't messed up */
	function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args, EXTR_SKIP );

		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );  /* WHAT DOES THIS DO? */
		
		$type = $this->type;

		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) )
			$pagination_links_class = ' hide-if-js';
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}
}

function get_booknames() {

	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK;";
	$booklist = $wpdb->get_results($wpdb->prepare($sql));
	
	return $booklist;
}

function render_meritflaw_add_form($type, $addaction) {

	global $wpdb;
	
	/* echo "<p>Creating $type form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id = $_REQUEST['merit'];
		$name = $_REQUEST[$type . '_name'];
		$group = $_REQUEST[$type . '_group'];
		$bookid = $_REQUEST[$type . '_sourcebook'];
		$pagenum = $_REQUEST[$type . '_page_number'];
		$cost = $_REQUEST[$type . '_cost'];
		$xpcost = $_REQUEST[$type . '_xp_cost'];
		$multiple = $_REQUEST[$type . '_multiple'];
		$visible = $_REQUEST[$type . '_visible'];
		$desc = $_REQUEST[$type . '_desc'];
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['merit'];
		
		$sql = "select merit.ID, merit.NAME as NAME, merit.DESCRIPTION as DESCRIPTION, merit.GROUPING as GROUPING,
						merit.COST as COST, merit.XP_COST as XP_COST, merit.MULTIPLE as MULTIPLE,
						books.NAME as SOURCEBOOK, merit.PAGE_NUMBER as PAGE_NUMBER,
						merit.VISIBLE as VISIBLE
						from " . GVLARP_TABLE_PREFIX . "MERIT merit, " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books 
						where merit.ID = '$id' and books.ID = merit.SOURCE_BOOK_ID;";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
		
		/* print_r($data); */
		
		$name = $data[0]->NAME;
		$group = $data[0]->GROUPING;
		$bookid = $data[0]->SOURCEBOOK;
		$pagenum = $data[0]->PAGE_NUMBER;
		$cost = $data[0]->COST;
		$xpcost = $data[0]->XP_COST;
		$multiple = $data[0]->MULTIPLE;
		$visible = $data[0]->VISIBLE;
		$desc = $data[0]->DESCRIPTION;
		
	} else {
	
		/* defaults */
		$id = 1;
		$name = "";
		$group = "";
		$bookid = 1;
		$pagenum = 1;
		$cost = 0;
		$xpcost = 0;
		$multiple = "N";
		$visible = "Y";
		$desc = "";
	}

	$booklist = get_booknames();

	?>
	<form id="new-<?php print $type; ?>" method="post">
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<table>
		<tr>
			<td><?php print ucfirst($type); ?> Name:  </td><td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=20/></td> <!-- check sizes -->
			<td>Grouping:   </td><td><input type="text" name="<?php print $type; ?>_group" value="<?php print $group; ?>" size=20/></td>
			<td>Sourcebook: </td><td>
				<select name="<?php print $type; ?>_sourcebook">
					<?php
						foreach ($booklist as $book) {
							print "<option value='{$book->ID}' ";
							($book->ID == $bookid) ? print "selected" : print "";
							echo ">{$book->NAME}</option>";
						}
					?>
				</select>
			</td>
			<td>Page Number: </td><td><input type="number" name="<?php print $type; ?>_page_number" value="<?php print $pagenum; ?>" /></td>
		</tr>
		<tr>
			<td>Freebie Point Cost: </td><td><input type="number" name="<?php print $type; ?>_cost" value="<?php print $cost; ?>" /></td>
			<td>Experience Cost: </td><td><input type="number" name="<?php print $type; ?>_xp_cost" value="<?php print $xpcost; ?>" /></td>
			<td>Multiple?: </td><td>
				<select name="<?php print $type; ?>_multiple">
					<option value="N" <?php selected($multiple, "N"); ?>>No</option>
					<option value="Y" <?php selected($multiple, "Y"); ?>>Yes</option>
				</select>
			</td>
			<td>Visible to Players: </td><td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select></td>
		</tr>
		<tr>
			<td>Description: </td><td colspan=3><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=50/></td>
		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save <?php print ucfirst($type); ?>" />
	</form>
	
	<?php
}

function merit_input_validation($type) {

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	/* echo "<p>Requested action: " . $_REQUEST['action'] . ", " . $type . "_name: " . $_REQUEST[$type . '_name']; */
			
	if (!empty($_REQUEST[$type . '_name'])){

		if ($doaction == "edit-$type")
			$doaction = "save-edit-$type";
		else
			$doaction = "add-$type";
			
		/* Input Validation */
		if (empty($_REQUEST[$type . '_group']) || $_REQUEST[$type . '_group'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Name of group is missing</p>";
		}
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Description is missing</p>";
		}
		/* Page number is a number */
		if (empty($_REQUEST[$type . '_page_number']) || $_REQUEST[$type . '_page_number'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: sourcebook page number is missing</p>";
		} else if ($_REQUEST[$type . '_page_number'] <= 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Invalid sourcebook page number</p>";
		} 
		
		/* Freebies is greater than 0 */
		if (empty($_REQUEST[$type . '_cost']) || $_REQUEST[$type . '_cost'] == "") {
			echo "<p style='color:orange'>Warning: Freebie point cost is missing. Will save cost as 0.</p>";
		} else if ($_REQUEST[$type . '_cost'] == 0 && $type == "flaw") {
			echo "<p style='color:orange'>Warning: Freebie point cost is 0 and will be saved as a Merit</p>";
		} else if ($type == "merit" && $_REQUEST[$type . '_cost'] < 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Freebie point cost for merits should greater than or equal to 0</p>";
		} else if ($type == "flaw" && $_REQUEST[$type . '_cost'] > 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Freebie point cost for flaws should less than or equal to 0</p>";
		}

		
		/* XP is 0 or greater */
		if ($_REQUEST[$type . '_xp_cost'] < 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Experience point cost should greater than or equal to 0</p>";
		}
		
	}
	
	/* echo "action: $doaction</p>"; */

	return $doaction;
}

?>