<?php
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/* 
-----------------------------------------------
MULTI-PAGE LIST TABLE
------------------------------------------------ */


class GVMultiPage_ListTable extends WP_List_Table {
      
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
   
	/* Need own version of this function to deal with tabs */
	function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );
		$current_url = add_query_arg('tab', $this->type, $current_url);

		if ( isset( $_GET['orderby'] ) && (!isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == $this->type ) ) )
			$current_orderby = $_GET['orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] && (!isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == $this->type ) )  )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}


}

/* 
-----------------------------------------------
MERITS AND FLAWS TABLE
------------------------------------------------ */


class gvadmin_meritsflaws_table extends GVMultiPage_ListTable {
   
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
		$this->filter_group = gvmake_filter($groups);
			
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
		
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']) && $type == $_REQUEST['tab'])
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

}
/* 
-----------------------------------------------
RITUALS TABLE
------------------------------------------------ */


class gvadmin_rituals_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'ritual',     
            'plural'    => 'rituals',    
            'ajax'      => false        
        ) );
    }
	
	function delete_ritual($selectedID) {
		global $wpdb;
		
		/* Check if ritual id in use */
		$sql = "select characters.NAME
					from " . GVLARP_TABLE_PREFIX . "CHARACTER_RITUAL charrituals, " . GVLARP_TABLE_PREFIX . "CHARACTER characters
					where charrituals.RITUAL_ID = $selectedID and charrituals.CHARACTER_ID = characters.ID;";
		$isused = $wpdb->get_results($wpdb->prepare($sql));
		
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this ritual is being used in the following characters:";
			echo "<ul>";
			foreach ($isused as $character)
				echo "<li style='color:red'>{$character->NAME}</li>";
			echo "</ul></p>";
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "RITUAL where ID = $selectedID;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql));
		
			/* print_r($result); */
			echo "<p style='color:green'>Deleted ritual $selectedID</p>";
		}
	}
	
 	function add_ritual($ritualname, $description, $level, $disciplineid, $dicepool,
						$difficulty, $xp_cost, $sourcebookid, $pagenum, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $ritualname,
						'DESCRIPTION' => $description,
						'LEVEL' => $level,
						'DISCIPLINE_ID' => $disciplineid,
						'DICE_POOL' => $dicepool,
						'DIFFICULTY' => $difficulty,
						'COST' => $xp_cost,
						'SOURCE_BOOK_ID' => $sourcebookid,
						'PAGE_NUMBER' => $pagenum,
						'VISIBLE' => $visible
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "RITUAL",
					$dataarray,
					array (
						'%s',
						'%s',
						'%d',
						'%d',
						'%s',
						'%s',
						'%d',
						'%d',
						'%d',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $ritualname could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added ritual '$ritualname' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_ritual($ritualid, $ritualname, $description, $level, $disciplineid, $dicepool,
						$difficulty, $xp_cost, $sourcebookid, $pagenum, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $ritualname,
						'DESCRIPTION' => $description,
						'LEVEL' => $level,
						'DISCIPLINE_ID' => $disciplineid,
						'DICE_POOL' => $dicepool,
						'DIFFICULTY' => $difficulty,
						'COST' => $xp_cost,
						'SOURCE_BOOK_ID' => $sourcebookid,
						'PAGE_NUMBER' => $pagenum,
						'VISIBLE' => $visible
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "RITUAL",
					$dataarray,
					array (
						'ID' => $ritualid
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $ritualname</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $ritualname</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $ritualname ($ritualid)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'LEVEL':
                return $item->$column_name;
            case 'DISCIPLINE':
                return $item->$column_name;
            case 'DICE_POOL':
                return $item->$column_name;
            case 'DIFFICULTY':
                return $item->$column_name;
             case 'COST':
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
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&ritual=%s&tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&action=%s&ritual=%s&tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
            $item->ID,
            $this->row_actions($actions)
        );
    }
   

    function get_columns(){
        $columns = array(
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'DESCRIPTION'  => 'Description',
            'LEVEL'        => 'Ritual Level',
            'DISCIPLINE'   => 'Associated Discipline',
            'DICE_POOL'    => 'Dice Pool',
            'DIFFICULTY'   => 'Difficulty of roll',
            'COST'         => 'Experience Cost',
            'SOURCEBOOK'   => 'Source Book',
            'PAGE_NUMBER'  => 'Source Page',
            'VISIBLE'      => 'Visible to Players'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',false),
            'LEVEL'       => array('LEVEL',false),
            'COST'  	  => array('COST',false),
            'SOURCEBOOK'  => array('SOURCEBOOK',false)
        );
        return $sortable_columns;
    }
	
	
	
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
       );
        return $actions;
    }
    
    function process_bulk_action() {
        		
        if( 'delete'===$this->current_action() && $_REQUEST['tab'] == $this->type) {
			if ('string' == gettype($_REQUEST['ritual'])) {
				$this->delete_ritual($_REQUEST['ritual']);
			} else {
				foreach ($_REQUEST['ritual'] as $ritual) {
					$this->delete_ritual($ritual);
				}
			}
        }
     }
	
	function extra_tablenav($which) {
		if ($which == 'top') {

			echo "<div class='gvfilter'>";
			echo "<span>Discipline: </span>";
			if ( !empty( $this->filter_discipline ) ) {
				echo "<select name='{$this->type}_discipline'>";
				foreach( $this->filter_discipline as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_discipline, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			echo "<span>Level: </span>";
			if ( !empty( $this->filter_level ) ) {
				echo "<select name='{$this->type}_level'>";
				foreach( $this->filter_level as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_level, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			echo "<span>Sourcebook: </span>";
			if ( !empty( $this->filter_book ) ) {
				echo "<select name='{$this->type}_book'>";
				foreach( $this->filter_book as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_book, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			submit_button( 'Filter', 'secondary', false, false );
			echo "</div>";
		}
	}
        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "ritual";
        
		/* setup filters here */
		$sql = "SELECT DISTINCT disciplines.ID as ID, disciplines.NAME as NAME
				FROM " . GVLARP_TABLE_PREFIX . "RITUAL rituals, " . GVLARP_TABLE_PREFIX . "DISCIPLINE disciplines
				WHERE disciplines.ID = rituals.DISCIPLINE_ID;";
		$disciplines = $wpdb->get_results($wpdb->prepare($sql));
		$this->filter_discipline = gvmake_filter($disciplines);
		
		/* Ritual Level filter */
		$sql = "SELECT DISTINCT LEVEL FROM " . GVLARP_TABLE_PREFIX . "RITUAL;";
		$levels = $wpdb->get_results($wpdb->prepare($sql));
		$this->filter_level = gvmake_filter($levels);
			
		/* Book filter */
		$sql = "SELECT DISTINCT books.ID, books.NAME 
				FROM " . GVLARP_TABLE_PREFIX . "RITUAL rituals, " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books
				WHERE rituals.SOURCE_BOOK_ID = books.ID;";
		$books = $wpdb->get_results($wpdb->prepare($sql));
		$this->filter_book = gvmake_filter($books);
						
		if ( isset( $_REQUEST[$type . '_discipline'] ) && array_key_exists( $_REQUEST[$type . '_discipline'], $this->filter_discipline ) ) {
			$this->active_filter_discipline = sanitize_key( $_REQUEST[$type . '_discipline'] );
		} else {
			$this->active_filter_discipline = 'all';
		}
		if ( isset( $_REQUEST[$type . '_level'] ) && array_key_exists( $_REQUEST[$type . '_level'], $this->filter_level ) ) {
			$this->active_filter_level = sanitize_key( $_REQUEST[$type . '_level'] );
		} else {
			$this->active_filter_level = 'all';
		}
		if ( isset( $_REQUEST[$type . '_book'] ) && array_key_exists( $_REQUEST[$type . '_book'], $this->filter_book ) ) {
			$this->active_filter_book = sanitize_key( $_REQUEST[$type . '_book'] );
		} else {
			$this->active_filter_book = 'all';
		}
			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select rituals.ID, rituals.NAME as NAME, rituals.DESCRIPTION as DESCRIPTION, rituals.LEVEL as LEVEL,
					disciplines.NAME as DISCIPLINE, rituals.DICE_POOL as DICE_POOL,
					rituals.COST as COST, rituals.DIFFICULTY as DIFFICULTY,
						books.NAME as SOURCEBOOK, rituals.PAGE_NUMBER as PAGE_NUMBER,
						rituals.VISIBLE as VISIBLE
				from " . GVLARP_TABLE_PREFIX. "RITUAL rituals, " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books, "
						. GVLARP_TABLE_PREFIX. "DISCIPLINE disciplines 
				where disciplines.ID = rituals.DISCIPLINE_ID and books.ID = rituals.SOURCE_BOOK_ID";
		
		/* limit data according to the filters */
		if ( "all" !== $this->active_filter_discipline)
			$sql .= " AND disciplines.ID = '" . $this->active_filter_discipline . "'";
		if ( "all" !== $this->active_filter_level)			
			$sql .= " AND rituals.LEVEL = '" . $this->active_filter_level . "'";
		if ( "all" !== $this->active_filter_book )			
			$sql .= " AND books.ID = '" . $this->active_filter_book . "'";
		
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY rituals.{$_REQUEST['orderby']} {$_REQUEST['order']}";
		
		$sql .= ";";
		
		/* echo "<p>SQL: $sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
        
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
    }

}

?>