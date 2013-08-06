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
		$current_url = remove_query_arg( 'action', $current_url );
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
		
		/* add_action('delete_merit', array($this, 'delete_merit'), 10, 1);
		add_action('showhide_merit', array($this, 'gvlarp_showhide_merit'), 10, 2); */
		/* add_action('edit_merit', array($this, 'gvlarp_edit_merit'), 10, 1); */
        
    }
	
	function delete_merit($selectedID) {
		global $wpdb;
		
		/* Check if merit id in use */
		$sql = "select characters.NAME 
			from " . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT charmerits , " . GVLARP_TABLE_PREFIX . "CHARACTER characters
			where charmerits.MERIT_ID = %d and charmerits.CHARACTER_ID = characters.ID;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this {$this->type} is being used in the following characters:";
			echo "<ul>";
			foreach ($isused as $character)
				echo "<li style='color:red'>{$character->NAME}</li>";
			echo "</ul></p>";
		} else {
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "MERIT where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
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
						$cost, $xp_cost, $multiple, $visible, $description, $question) {
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
						'VISIBLE' => $visible,
						'BACKGROUND_QUESTION' => $question
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
						$cost, $xp_cost, $multiple, $visible, $description, $question) {
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
						'VISIBLE' => $visible,
						'BACKGROUND_QUESTION' => $question
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
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&amp;merit=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&amp;merit=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
            $act        => sprintf('<a href="?page=%s&amp;action=%s&amp;merit=%s&amp;tab=%s">%s</a>',$_REQUEST['page'],$act,$item->ID, $this->type, ucfirst($act)),
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
	
	function column_has_bg_q($item) {
		if ($item->BACKGROUND_QUESTION == "")
			return "No";
		else
			return "Yes";
			
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
            'VISIBLE'      => 'Visible to Players',
            'HAS_BG_Q'     => 'Extended Background'
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
		$groups =$wpdb->get_results($wpdb->prepare($sql, ''));
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
						merit.VISIBLE as VISIBLE, merit.BACKGROUND_QUESTION
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
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
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
					where charrituals.RITUAL_ID = %d and charrituals.CHARACTER_ID = characters.ID;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this ritual is being used in the following characters:";
			echo "<ul>";
			foreach ($isused as $character)
				echo "<li style='color:red'>{$character->NAME}</li>";
			echo "</ul></p>";
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "RITUAL where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
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
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&ritual=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&ritual=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
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
		$disciplines = $wpdb->get_results($wpdb->prepare($sql,''));
		$this->filter_discipline = gvmake_filter($disciplines);
		
		/* Ritual Level filter */
		$sql = "SELECT DISTINCT LEVEL FROM " . GVLARP_TABLE_PREFIX . "RITUAL;";
		$levels = $wpdb->get_results($wpdb->prepare($sql,''));
		$this->filter_level = gvmake_filter($levels);
			
		/* Book filter */
		$sql = "SELECT DISTINCT books.ID, books.NAME 
				FROM " . GVLARP_TABLE_PREFIX . "RITUAL rituals, " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books
				WHERE rituals.SOURCE_BOOK_ID = books.ID;";
		$books = $wpdb->get_results($wpdb->prepare($sql,''));
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
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
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
/* 
-----------------------------------------------
SOURCEBOOKS TABLE
------------------------------------------------ */


class gvadmin_books_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'book',     
            'plural'    => 'books',    
            'ajax'      => false        
        ) );
    }
	
	function delete_book($selectedID) {
		global $wpdb;
		
		/* Check if book in use in MERITS and FLAWS */
		$sql = "select merits.NAME
				from " . GVLARP_TABLE_PREFIX . "MERIT merits, " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books
				where books.ID = merits.SOURCE_BOOK_ID and merits.SOURCE_BOOK_ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this book is being used in the Merits and Flaws list:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
		}
		
		/* Check if book in use in RITUALS */
		$sql = "select rituals.NAME
				from " . GVLARP_TABLE_PREFIX . "RITUAL rituals, " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books
				where books.ID = rituals.SOURCE_BOOK_ID and rituals.SOURCE_BOOK_ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this book is being used in the Rituals list:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
		}

		/* Check if book in use in COMBO DISCIPLINE */
		
		/* Check if book in use in DISCIPLINE */
		
		/* Check if book in use in MAJIK PATH */
		
		/* Check if book in use in ROAD OR PATH */
		
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this book is being used in the following places:";
			echo "<ul>";
			foreach ($isused as $character)
				echo "<li style='color:red'></li>";
			echo "</ul></p>";
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			/* print_r($result); */
			echo "<p style='color:green'>Deleted book $selectedID</p>";
		}
	}
	
 	function add_book($bookname, $bookcode, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $bookname,
						'CODE' => $bookcode,
						'VISIBLE' => $visible
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "SOURCE_BOOK",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $bookname could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added book '$bookname' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_book($bookid, $bookname, $bookcode, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $bookname,
						'CODE' => $bookcode,
						'VISIBLE' => $visible
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "SOURCE_BOOK",
					$dataarray,
					array (
						'ID' => $bookid
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $bookname</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $bookname</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $bookname ($bookid)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'CODE':
                return $item->$column_name;
           default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&book=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&book=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
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
            'CODE'         => 'Book Code',
            'VISIBLE'      => 'Visible to Players'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true)
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
			if ('string' == gettype($_REQUEST['book'])) {
				$this->delete_book($_REQUEST['book']);
			} else {
				foreach ($_REQUEST['book'] as $book) {
					$this->delete_book($book);
				}
			}
        }
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "book";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select books.ID, books.NAME, books.CODE, books.VISIBLE
				from " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY books.{$_REQUEST['orderby']} {$_REQUEST['order']}";
		
		$sql .= ";";
		
		/* echo "<p>SQL: $sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
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


/* 
-----------------------------------------------
BACKGROUNDS TABLE
------------------------------------------------ */


class gvadmin_backgrounds_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'background',     
            'plural'    => 'backgrounds',    
            'ajax'      => false        
        ) );
    }
	
	function delete_background($selectedID) {
		global $wpdb;
		
		/* Check if background in use */
		$sql = "select characters.NAME
				from " . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgs, 
					" . GVLARP_TABLE_PREFIX . "BACKGROUND backgrounds,
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters
				where charbgs.BACKGROUND_ID = backgrounds.ID 
					and characters.ID = charbgs.CHARACTER_ID
					and backgrounds.ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this background is in use for the following characters:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
			
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "BACKGROUND where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			/* print_r($result); */
			echo "<p style='color:green'>Deleted background $selectedID</p>";
		}
	}
	
 	function add_background($name, $description, $group, $costmodel_id, $visible, $has_sector, $question) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'          => $name,
						'DESCRIPTION'   => $description,
						'GROUPING'      => $group,
						'COST_MODEL_ID' => $costmodel_id,
						'VISIBLE'       => $visible,
						'HAS_SECTOR'          => $has_sector,
						'BACKGROUND_QUESTION' => $question
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "BACKGROUND",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
						'%d',
						'%s',
						'%s',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $name could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added background '$name' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_background($id, $name, $description, $group, $costmodel_id, $visible, $has_sector, $question) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'          => $name,
						'DESCRIPTION'   => $description,
						'GROUPING'      => $group,
						'COST_MODEL_ID' => $costmodel_id,
						'VISIBLE'       => $visible,
						'HAS_SECTOR'          => $has_sector,
						'BACKGROUND_QUESTION' => $question
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "BACKGROUND",
					$dataarray,
					array (
						'ID' => $id
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $name</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $name</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $name ($id)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'GROUPING':
                return $item->$column_name;
            case 'VISIBLE':
                return $item->$column_name;
            case 'COSTMODEL':
                return $item->$column_name;
           default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&background=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&background=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
            $item->ID,
            $this->row_actions($actions)
        );
    }

	function column_has_bg_q($item) {
		if ($item->BACKGROUND_QUESTION == "")
			return "No";
		else
			return "Yes";
			
	}
	function column_has_sector($item) {
		if ($item->HAS_SECTOR == "Y")
			return "Yes";
		else
			return "No";
			
	}

    function get_columns(){
        $columns = array(
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'DESCRIPTION'  => 'Description',
            'GROUPING'     => 'Background Group',
            'COSTMODEL'    => 'Cost Model',
            'HAS_SECTOR'   => 'Has a Sector',
            'VISIBLE'      => 'Visible to Players',
            'HAS_BG_Q'     => 'Extended Background'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'VISIBLE'     => array('VISIBLE',false)
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
			if ('string' == gettype($_REQUEST['background'])) {
				$this->delete_background($_REQUEST['background']);
			} else {
				foreach ($_REQUEST['background'] as $background) {
					$this->delete_background($background);
				}
			}
        }
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "bgdata";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select 
					backgrounds.ID, 
					backgrounds.NAME, 
					backgrounds.DESCRIPTION, 
					backgrounds.GROUPING, 
					costmodels.NAME as COSTMODEL, 
					backgrounds.VISIBLE,
					backgrounds.HAS_SECTOR,
					backgrounds.BACKGROUND_QUESTION
			from " . GVLARP_TABLE_PREFIX . "BACKGROUND backgrounds, " . GVLARP_TABLE_PREFIX . "COST_MODEL costmodels
			where backgrounds.COST_MODEL_ID = costmodels.ID";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY backgrounds.{$_REQUEST['orderby']} {$_REQUEST['order']}";
			
		$sql .= ";";
		
		/* echo "<p>SQL: $sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
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

/* 
-----------------------------------------------
SECTORS TABLE
------------------------------------------------ */


class gvadmin_sectors_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'sector',     
            'plural'    => 'sectors',    
            'ajax'      => false        
        ) );
    }
	
	function delete_sector($selectedID) {
		global $wpdb;
		
		/* Check if sector in use */
		$sql = "select characters.NAME
				from " . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgs, 
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
					" . GVLARP_TABLE_PREFIX . "SECTOR sectors
				where charbgs.SECTOR_ID = sectors.ID 
					and characters.ID = charbgs.CHARACTER_ID
					and sectors.ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this sector is in use for the following characters:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
			
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "SECTOR where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			/* print_r($result); */
			echo "<p style='color:green'>Deleted sector $selectedID</p>";
		}
	}
	
 	function add_sector($name, $description, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'          => $name,
						'DESCRIPTION'   => $description,
						'VISIBLE'       => $visible
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "SECTOR",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $name could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added sector '$name' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_sector($id, $name, $description, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'          => $name,
						'DESCRIPTION'   => $description,
						'VISIBLE'       => $visible
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "SECTOR",
					$dataarray,
					array (
						'ID' => $id
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $name</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $name</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $name ($id)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'VISIBLE':
                return $item->$column_name;
           default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&sector=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&sector=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
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
            'VISIBLE'      => 'Visible to Players'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'VISIBLE'     => array('VISIBLE',false)
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
			if ('string' == gettype($_REQUEST['sector'])) {
				$this->delete_sector($_REQUEST['sector']);
			} else {
				foreach ($_REQUEST['sector'] as $sector) {
					$this->delete_sector($sector);
				}
			}
        }
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "sector";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select sectors.ID, sectors.NAME, sectors.DESCRIPTION, sectors.VISIBLE
			from " . GVLARP_TABLE_PREFIX . "SECTOR sectors;";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY sectors.{$_REQUEST['orderby']} {$_REQUEST['order']}";
			
		$sql .= ";";
		
		/* echo "<p>SQL: $sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
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
/* 
-----------------------------------------------
EXTENDED BACKGROUNDS QUESTIONS TABLE
------------------------------------------------ */


class gvadmin_questions_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'question',     
            'plural'    => 'questions',    
            'ajax'      => false        
        ) );
    }
	
	function delete_question($selectedID) {
		global $wpdb;
		
		/* Check if question in use */
		$sql = "select characters.NAME
				from " . GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND charbgs, 
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
					" . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND questions
				where charbgs.QUESTION_ID = questions.ID 
					and characters.ID = charbgs.CHARACTER_ID
					and questions.ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this question has been filled in for the following characters:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
			
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			echo "<p style='color:green'>Deleted question $selectedID</p>";
		}
	}
	
 	function add_question($title, $ordering, $grouping, $question, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'TITLE'          => $title,
						'ORDERING'       => $ordering,
						'GROUPING'       => $grouping,
						'BACKGROUND_QUESTION' => $question,
						'VISIBLE'        => $visible
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND",
					$dataarray,
					array (
						'%s',
						'%d',
						'%s',
						'%s',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $title could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added question '$title' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_question($id, $title, $ordering, $grouping, $question, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'TITLE'          => $title,
						'ORDERING'       => $ordering,
						'GROUPING'       => $grouping,
						'BACKGROUND_QUESTION' => $question,
						'VISIBLE'        => $visible
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND",
					$dataarray,
					array (
						'ID' => $id
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $title</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $title</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $title ($id)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'ORDERING':
                return $item->$column_name;
            case 'GROUPING':
                return $item->$column_name;
            case 'BACKGROUND_QUESTION':
                return stripslashes($item->$column_name);
            default:
                return print_r($item,true); 
        }
    }
 
    function column_title($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&question=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&question=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->TITLE,
            $item->ID,
            $this->row_actions($actions)
        );
    }
   

    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />', 
            'TITLE'         => 'Title',
            'ORDERING'      => 'Order',
            'GROUPING'      => 'Group',
 			'VISIBLE'       => 'Question visible to players',
           'BACKGROUND_QUESTION'  => 'Question'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'TITLE'        => array('NAME',true),
            'GROUPING'     => array('GROUPING',false),
            'ORDERING'     => array('ORDERING',false)
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
			if ('string' == gettype($_REQUEST['question'])) {
				$this->delete_question($_REQUEST['question']);
			} else {
				foreach ($_REQUEST['question'] as $question) {
					$this->delete_question($question);
				}
			}
        }
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "question";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select questions.ID, questions.TITLE, questions.ORDERING, questions.GROUPING, questions.BACKGROUND_QUESTION, questions.VISIBLE
			from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND questions;";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY questions.{$_REQUEST['orderby']} {$_REQUEST['order']}";
			
		$sql .= ";";
		
		/* echo "<p>SQL: $sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
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

/* 
-----------------------------------------------
EXTENDED BACKGROUNDS APPROVAL
------------------------------------------------ */


class gvadmin_extbgapproval_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'extbackground',     
            'plural'    => 'extbackgrounds',    
            'ajax'      => false        
        ) );
    }
	
	function approve($tableid) {
		global $wpdb;
		$table = $this->items[$tableid]['TABLE'];
		
		$data = array(
			'PENDING_DETAIL'  => '',
			'APPROVED_DETAIL' => $this->items[$tableid]['TABLE.DETAIL']
		);
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . $table,
			$data,
			array ('ID' => $this->items[$tableid]['TABLE.ID'])
		);
		
		if ($result) echo "<p style='color:green'>Approved extended background</p>";
		else echo "<p style='color:red'>Could not approve extended background</p>";
		
		/*
		switch($table){
			case 'CHARACTER_BACKGROUND':
				$data = array(
					'PENDING_DETAIL'  => '',
					'APPROVED_DETAIL' => $this->items[$tableid]['TABLE.DETAIL']
				);
				$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND",
					$data,
					array ('ID' => $this->items[$tableid]['TABLE.ID'])
				);
				
				if ($result) echo "<p style='color:green'>Approved background</p>";
				else echo "<p style='color:red'>Could not approve background</p>";
				break;
			case 'CHARACTER_MERIT':
				$data = array(
					'PENDING_DETAIL'  => '',
					'APPROVED_DETAIL' => $this->items[$tableid]['TABLE.DETAIL']
				);
				$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_MERIT",
					$data,
					array ('ID' => $this->items[$tableid]['TABLE.ID'])
				);
				
				if ($result) echo "<p style='color:green'>Approved Merit/Flaw</p>";
				else echo "<p style='color:red'>Could not approve Merit/Flaw</p>";
				break;
			default:
				break;
		} */
		
	}
	
 	function deny($tableid, $deny_message = 'Denied - see storytellers for more information') {
		global $wpdb;
		$table = $this->items[$tableid]['TABLE'];
		
		$data = array('DENIED_DETAIL'  => $deny_message);
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . $table,
			$data,
			array ('ID' => $this->items[$tableid]['TABLE.ID'])
		);
		
		if ($result) echo "<p style='color:green'>Denied extended background</p>";
		else echo "<p style='color:red'>Could not deny extended background</p>";
		
		/* switch($table){
			case 'CHARACTER_BACKGROUND':
				$data = array('DENIED_DETAIL'  => $deny_message);
				$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND",
					$data,
					array ('ID' => $this->items[$tableid]['TABLE.ID'])
				);
				
				if ($result) echo "<p style='color:green'>Denied background</p>";
				else echo "<p style='color:red'>Could not deny background</p>";
				break;
			case 'CHARACTER_MERIT':
				$data = array('DENIED_DETAIL'  => $deny_message);
				$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_MERIT",
					$data,
					array ('ID' => $this->items[$tableid]['TABLE.ID'])
				);
				
				if ($result) echo "<p style='color:green'>Denied Merit/Flaw</p>";
				else echo "<p style='color:red'>Could not deny Merit/Flaw</p>";
				break;
			case 'CHARACTER_EXTENDED_BACKGROUND':
				$data = array('DENIED_DETAIL'  => $deny_message);
				$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND",
					$data,
					array ('ID' => $this->items[$tableid]['TABLE.ID'])
				);
				
				if ($result) echo "<p style='color:green'>Expanded Background Update denied</p>";
				else echo "<p style='color:red'>Could not deny Expanded Background</p>";
				break;
			default:
				break;
		} */
		
	}
  
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item[$column_name];
            case 'TABLE':
                return $item[$column_name];
            case 'TABLE.ID':
                return $item[$column_name];
            case 'TABLE.DETAIL':
                return $item[$column_name];
           default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
        
        $actions = array(
            'approveit' => sprintf('<a href="?page=%s&amp;action=%s&extbackground=%s&amp;tab=%s">Approve</a>',$_REQUEST['page'],'approveit',$item['ID'], $this->type),
            'denyit'    => sprintf('<a href="?page=%s&amp;action=%s&extbackground=%s&amp;tab=%s">Deny</a>',$_REQUEST['page'],'denyit',$item['ID'], $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item[NAME],
            $item[ID],
            $this->row_actions($actions)
        );
    }
   
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  
            $item[ID]
        );
    }

    function get_columns(){
        $columns = array(
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'DESCRIPTION'  => 'Description',
            'TABLE'        => 'Table Name',
            'TABLE.ID'     => 'ID of item in table',
			'TABLE.DETAIL' => 'Data for table'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true)
        );
        return $sortable_columns;
    }
	
	
	
    
    function get_bulk_actions() {
        $actions = array(
            'approveit' => 'Approve',
            'denyit'    => 'Deny'
       );
        return $actions;
    }
    
    function process_bulk_action() {
        		
		if( 'approveit'===$this->current_action() && $_REQUEST['tab'] == $this->type) {

			if ('string' == gettype($_REQUEST['extbackground'])) {
				$this->approve($_REQUEST['extbackground']);
			} else {
				foreach ($_REQUEST['extbackground'] as $extbackground) {
					$this->approve($extbackground);
				}
			}
        }
        if( 'denyit'===$this->current_action() && $_REQUEST['tab'] == $this->type) {
			if ('string' == gettype($_REQUEST['extbackground'])) {
				/* $this->deny($_REQUEST['extbackground']); */
			} else {
				foreach ($_REQUEST['extbackground'] as $extbackground) {
					$this->deny($extbackground);
				}
			}
        }
     }

	function read_data() {
		global $wpdb;
		
		$data = array();
	
		/* Get the data from the database - backgrounds */
		$sql = "select characters.ID charID, charbgs.ID chargbID, characters.NAME charname, 
					backgrounds.NAME background, charbgs.LEVEL, 
					sectors.NAME sector, charbgs.PENDING_DETAIL, charbgs.DENIED_DETAIL,
					backgrounds.HAS_SECTOR, charbgs.COMMENT
				from	" . GVLARP_TABLE_PREFIX . "BACKGROUND backgrounds,
						" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
						" . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgs
				left join
						" . GVLARP_TABLE_PREFIX . "SECTOR sectors
				on
					charbgs.SECTOR_ID = sectors.ID
				where	backgrounds.ID = charbgs.BACKGROUND_ID
					and characters.ID = charbgs.CHARACTER_ID
					and charbgs.PENDING_DETAIL != ''
					and charbgs.DENIED_DETAIL = ''
					and	(backgrounds.BACKGROUND_QUESTION != '' OR charbgs.SECTOR_ID > 0);";
				
		
		$tempdata =$wpdb->get_results($wpdb->prepare($sql,''));
		$row = 0;
		foreach ($tempdata as $tablerow) {
			$description = "<strong>{$tablerow->background} {$tablerow->LEVEL}";
			$description .= ($tablerow->sector) ? " ({$tablerow->sector})" : "";
			$description .= ($tablerow->COMMENT) ? " ({$tablerow->COMMENT})" : "";
			$description .= "</strong><br /><span>" . stripslashes($tablerow->PENDING_DETAIL) . "</span>";
			
			$data[$row] = array (
				'ID'          => $row,
				'NAME'        => $tablerow->charname,
				'TABLE.ID'    => $tablerow->chargbID,
				'TABLE'       => "CHARACTER_BACKGROUND",
				'TABLE.DETAIL' => $tablerow->PENDING_DETAIL,
				'DESCRIPTION'  => $description,
				'COMMENT'      => $tablerow->COMMENT
			);
			$row++;
		}

		/* Get the data from the database - merits and flaws */
		$sql = "select characters.ID charID, charmerit.ID charmeritID, characters.NAME charname, 
					merits.NAME merit, charmerit.COMMENT,
					charmerit.PENDING_DETAIL, charmerit.DENIED_DETAIL
				from	" . GVLARP_TABLE_PREFIX . "MERIT merits,
						" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
						" . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT charmerit
				where	merits.ID = charmerit.MERIT_ID
					and characters.ID = charmerit.CHARACTER_ID
					and charmerit.PENDING_DETAIL != ''
					and charmerit.DENIED_DETAIL = ''
					and	merits.BACKGROUND_QUESTION != '';";
				
		
		$tempdata =$wpdb->get_results($wpdb->prepare($sql,''));
		foreach ($tempdata as $tablerow) {
			$description = "<strong>{$tablerow->merit}";
			$description .= ($tablerow->COMMENT) ? " ({$tablerow->COMMENT})" : "";
			$description .= "</strong><br />
				<span>" . stripslashes($tablerow->PENDING_DETAIL) . "</span>";
			
			$data[$row] = array (
				'ID'          => $row,
				'NAME'        => $tablerow->charname,
				'TABLE.ID'    => $tablerow->charmeritID,
				'TABLE'       => "CHARACTER_MERIT",
				'TABLE.DETAIL' => $tablerow->PENDING_DETAIL,
				'DESCRIPTION'  => $description,
				'COMMENT'      => $tablerow->COMMENT
			);
			$row++;
		}
		
		/* Get the data from the database - questions */
		$sql = "select characters.ID charID, answers.ID answerID, characters.NAME charname, 
					questions.TITLE, questions.GROUPING,
					answers.PENDING_DETAIL, answers.DENIED_DETAIL
				from	" . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND questions,
						" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
						" . GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND answers
				where	questions.ID = answers.QUESTION_ID
					and characters.ID = answers.CHARACTER_ID
					and answers.PENDING_DETAIL != ''
					and answers.DENIED_DETAIL = '';";
					
		$tempdata =$wpdb->get_results($wpdb->prepare($sql,''));
		foreach ($tempdata as $tablerow) {
			$description = "<strong>{$tablerow->TITLE} ({$tablerow->GROUPING})</strong><br />
				<span>" . stripslashes($tablerow->PENDING_DETAIL) . "</span>";
			
			$data[$row] = array (
				'ID'          => $row,
				'NAME'        => $tablerow->charname,
				'TABLE.ID'    => $tablerow->answerID,
				'TABLE'       => "CHARACTER_EXTENDED_BACKGROUND",
				'TABLE.DETAIL' => $tablerow->PENDING_DETAIL,
				'DESCRIPTION'  => $description,
				'COMMENT'      => ''
			);
			$row++;
		}
		
		
		return $data;
	}
        
    function prepare_items() {
        
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "gvapprove";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
		
		$data = $this->read_data();
		$this->items = $data;
        
        $this->process_bulk_action();
		
		$data = $this->read_data();
		$this->items = $data;
		
        function usort_reorder($a,$b){

            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'name';
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; 
            $result = strcmp($a[$orderby], $b[$orderby]); 
            return ($order==='asc') ? $result : -$result; 
        }
        usort($data, 'usort_reorder');
       
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

/* 
-----------------------------------------------
CLANS
------------------------------------------------ */
class gvadmin_clans_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'clan',     
            'plural'    => 'clans',    
            'ajax'      => false        
        ) );
    }
	
	function delete_clan($selectedID) {
		global $wpdb;
		
		/* Check if clan id in use in a character */
		$sql = "select characters.NAME 
			from " . GVLARP_TABLE_PREFIX . "CHARACTER characters
			where characters.PUBLIC_CLAN_ID = %d or characters.PRIVATE_CLAN_ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID, $selectedID));
		
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this {$this->type} is being used in the following characters:";
			echo "<ul>";
			foreach ($isused as $character)
				echo "<li style='color:red'>{$character->NAME}</li>";
			echo "</ul></p>";
		} else {
		
			/* Check if clan id in use in a clan discipline */
			$sql = "select disciplines.NAME 
				from " . GVLARP_TABLE_PREFIX . "CLAN_DISCIPLINE clandisc, 
					" . GVLARP_TABLE_PREFIX . "DISCIPLINE disciplines
				where disciplines.ID = clandisc.DISCIPLINE_ID
					and clandisc.CLAN_ID = %d;";
			$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));

			if ($isused) {
				echo "<p style='color:red'>Cannot delete as this {$this->type} is being used in the following clan disciplines:";
				echo "<ul>";
				foreach ($isused as $disc)
					echo "<li style='color:red'>{$disc->NAME}</li>";
				echo "</ul></p>";
			
			} else {
				$sql = "delete from " . GVLARP_TABLE_PREFIX . "CLAN where ID = %d;";
				
				$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
			
				/* print_r($result); */
				echo "<p style='color:green'>Deleted item $selectedID</p>";
			}
		}
	}
	
 	function add_clan($name, $description, $iconlink, $clanpage,
						$clanflaw, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $name,
						'DESCRIPTION' => $description,
						'ICON_LINK' => $iconlink,
						'CLAN_PAGE_LINK' => $clanpage,
						'CLAN_FLAW' => $clanflaw,
						'VISIBLE' => $visible
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "CLAN",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $name could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added clan '$name' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_clan($clanid, $name, $description, $iconlink, $clanpage,
						$clanflaw, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $name,
						'DESCRIPTION' => $description,
						'ICON_LINK' => $iconlink,
						'CLAN_PAGE_LINK' => $clanpage,
						'CLAN_FLAW' => $clanflaw,
						'VISIBLE' => $visible
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CLAN",
					$dataarray,
					array (
						'ID' => $clanid
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $name</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $name</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $name ($clanid)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'CLAN_PAGE_LINK':
                return $item->$column_name;
             case 'ICON_LINK':
                return $item->$column_name;
           case 'CLAN_FLAW':
                return $item->$column_name;
            default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
	
		$act = ($item->VISIBLE === 'Y') ? 'hide' : 'show';
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&clan=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&clan=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
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
            'ICON_LINK'    => 'Link to clan icon',
            'CLAN_PAGE_LINK' => 'Link to clan page',
            'CLAN_FLAW'    => 'Clan Flaw',
            'VISIBLE'      => 'Visible to Players',
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'VISIBLE'    => array('GROUPING',false),
        );
        return $sortable_columns;
    }
	
	
	
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete',
       );
        return $actions;
    }
    
    function process_bulk_action() {
        
		/* echo "<p>Bulk action " . $this->current_action() . ", currently on tab {$_REQUEST['tab']} and will do action if {$this->type}.</p>"; */
		
        if( 'delete'===$this->current_action() && $_REQUEST['tab'] == $this->type) {
			if ('string' == gettype($_REQUEST['clan'])) {
				$this->delete_clan($_REQUEST['clan']);
			} else {
				foreach ($_REQUEST['clan'] as $clan) {
					$this->delete_clan($clan);
				}
			}
        }
		
    } 
    
    function prepare_items() {
        global $wpdb; 

        /* $per_page = 20; */
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = "clan";
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select * from " . GVLARP_TABLE_PREFIX. "CLAN clan";
		
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']) && $type == $_REQUEST['tab'])
			$sql .= " ORDER BY clan.{$_REQUEST['orderby']} {$_REQUEST['order']}";
		
		$sql .= ";";
		/* echo "<p>SQL: " . $sql . "</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
        $current_page = $this->get_pagenum();
        $total_items = count($data);
                
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

?>