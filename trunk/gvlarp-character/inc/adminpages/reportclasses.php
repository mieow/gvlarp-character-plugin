<?php
require_once GVLARP_CHARACTER_URL . 'inc/classes.php';

class gvreport_flaws extends GVReport_ListTable {

    function column_default($item, $column_name){
        switch($column_name){
            case 'PLAYERNAME':
                return $item->$column_name;
            case 'CHARACTERNAME':
                return $item->$column_name;
            case 'MERIT':
                return stripslashes($item->$column_name);
            case 'LEVEL':
                return $item->$column_name;
            case 'COMMENT':
                return stripslashes($item->$column_name);
            case 'DETAIL':
                return str_replace("\n", "<br>", stripslashes($item->$column_name));
            case 'SOURCEBOOK':
                return $item->$column_name;
            case 'PAGE_NUMBER':
                return $item->$column_name;
           default:
                return print_r($item,true); 
        }
    }

    function get_columns(){
        $columns = array(
            'CHARACTERNAME'    => 'Character',
            'PLAYERNAME'   => 'Player',
            'MERIT'        => 'Merit or Flaw',
            'LEVEL'        => 'Level',
			'COMMENT'      => 'Comment',
			'DETAIL'       => 'Background Detail',
            'SOURCEBOOK'   => 'Source Book',
        );
        return $columns;
	}
		
   function get_sortable_columns() {
        $sortable_columns = array(
            'CHARACTERNAME' => array('CHARACTERNAME',true),
            'PLAYERNAME'    => array('PLAYERNAME',false),
            'MERIT'  	=> array('MERIT',false),
            'LEVEL'     => array('LEVEL',false)
        );
        return $sortable_columns;
    }

	function extra_tablenav($which) {
		if ($which == 'top')  {
			echo "<div class='gvfilter'>";
			
			echo "<span>Merit/Flaw: </span>";
			echo "<select name='merit_or_flaw'>";
			echo '<option value="all" '    . selected( $this->active_filter_merit_or_flaw, 'all' )  . '>All</option>';
			echo '<option value="merit" '  . selected( $this->active_filter_merit_or_flaw, 'merit') . '>Merits</option>';
			echo '<option value="flaw" ' . selected( $this->active_filter_merit_or_flaw, 'flaw' )   . '>Flaws</option>';
			echo '</select>';
			
			$this->filter_tablenav();
		
			echo "</div>";
		}
	}
	function set_column_alignment ($columns = "") {
	
		$colwidths = array(
            'CHARACTERNAME' => 'L',
            'PLAYERNAME'   => 'L',
            'MERIT'        => 'L',
            'LEVEL'        => 'C',
			'COMMENT'      => 'L',
			'DETAIL'       => 'L',
            'SOURCEBOOK'   => 'C',
			
		);

		return $colwidths;
	}
	function set_column_widths($columns = "") {
	
		/* total 297-10, avg width 42 */
		$colwidths = array(
            'CHARACTERNAME' => 33,
            'PLAYERNAME'   => 33,
            'MERIT'        => 50,
            'LEVEL'        => 10,
			'COMMENT'      => 45,
			'DETAIL'       => 80,
            'SOURCEBOOK'   => 35,
			
		);

		return $colwidths;
	}
	function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

		/* filters */
		$this->load_filters();
		/* Merit or Flaw */
		if ( isset( $_REQUEST['merit_or_flaw'] )) {
			$this->active_filter_merit_or_flaw = sanitize_key( $_REQUEST['merit_or_flaw'] );
		} else {
			$this->active_filter_merit_or_flaw = 'all';
		}
		
		
		/* Character visibility */
		
		$sql = "SELECT players.NAME as PLAYERNAME, characters.NAME as CHARACTERNAME, merits.NAME as MERIT, charmerit.LEVEL, charmerit.COMMENT,
					charmerit.APPROVED_DETAIL as DETAIL, CONCAT(sourcebooks.NAME, \", p\" , merits.PAGE_NUMBER) as SOURCEBOOK
				FROM
					" . GVLARP_TABLE_PREFIX. "PLAYER players,
					" . GVLARP_TABLE_PREFIX. "CHARACTER characters,
					" . GVLARP_TABLE_PREFIX. "MERIT merits,
					" . GVLARP_TABLE_PREFIX. "CHARACTER_MERIT charmerit,
					" . GVLARP_TABLE_PREFIX. "SOURCE_BOOK sourcebooks
				WHERE
					players.ID = characters.PLAYER_ID
					AND characters.ID = charmerit.CHARACTER_ID
					AND merits.ID = charmerit.MERIT_ID
					AND sourcebooks.ID = merits.SOURCE_BOOK_ID";
		
		switch ($this->active_filter_merit_or_flaw) {
			case "merit":
				$sql .= " AND charmerit.LEVEL >= 0";
				break;
			case "flaw":
				$sql .= " AND charmerit.LEVEL < 0";
				break;
		}
		
		$filterinfo = $this->get_filter_sql();
		$sql .= $filterinfo[0];
		
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY {$_REQUEST['orderby']} {$_REQUEST['order']}";

			$this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
		
		/* run query */
		
		/* echo "<p>SQL: $sql</p>"; */
		$data =$wpdb->get_results($wpdb->prepare($sql,$filterinfo[1]));
 		
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        
        $this->items = $data;
		
		$this->output_report("Merits and Flaws Report");
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
	}
}

class gvreport_quotes extends GVReport_ListTable {

    function column_default($item, $column_name){
        switch($column_name){
            case 'PLAYERNAME':
                return $item->$column_name;
            case 'CHARACTERNAME':
                return $item->$column_name;
            case 'CLAN':
                return stripslashes($item->$column_name);
            case 'QUOTE':
                return str_replace("\n", "<br>", stripslashes($item->$column_name));
            default:
                return print_r($item,true); 
        }
    }

    function get_columns(){
        $columns = array(
            'CHARACTERNAME' => 'Character',
            'PLAYERNAME'    => 'Player',
            'CLAN'          => 'Public Clan',
            'QUOTE'         => 'Profile Quote',
        );
        return $columns;
	}
		
   function get_sortable_columns() {
        $sortable_columns = array(
            'CHARACTERNAME' => array('CHARACTERNAME',true),
            'PLAYERNAME'    => array('PLAYERNAME',false),
        );
        return $sortable_columns;
    }

	function extra_tablenav($which) {
		if ($which == 'top')  {
			echo "<div class='gvfilter'>";
			
			$this->filter_tablenav();
		
			echo "</div>";
		}
	}
	function set_column_widths($columns = "") {
	
		/* total 297-10, avg width 42 */
		$colwidths = array(
            'CHARACTERNAME' => 33,
            'PLAYERNAME'    => 33,
            'CLAN'          => 20,
            'QUOTE'         => 200,
			
		);

		return $colwidths;
	}
	function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

		/* filters */
		$this->load_filters();
				
		$sql = "SELECT players.NAME as PLAYERNAME, characters.NAME as CHARACTERNAME, profiles.QUOTE, clans.NAME as CLAN
				FROM
					" . GVLARP_TABLE_PREFIX. "PLAYER players,
					" . GVLARP_TABLE_PREFIX. "CHARACTER characters,
					" . GVLARP_TABLE_PREFIX. "CHARACTER_PROFILE profiles,
					" . GVLARP_TABLE_PREFIX. "CLAN clans
				WHERE
					players.ID = characters.PLAYER_ID
					AND characters.ID = profiles.CHARACTER_ID
					AND clans.ID = characters.PUBLIC_CLAN_ID ";
		
		$filterinfo = $this->get_filter_sql();
		$sql .= $filterinfo[0];
		
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY {$_REQUEST['orderby']} {$_REQUEST['order']}";

			$this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
		
		/* run query */
		
		/* echo "<p>SQL: $sql</p>"; */
		$data =$wpdb->get_results($wpdb->prepare($sql,$filterinfo[1]));
 		
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        
        $this->items = $data;
		
		$this->output_report("Profile Quotes Report");
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
	}
}


class gvreport_prestige extends GVReport_ListTable {

    function column_default($item, $column_name){
        switch($column_name){
            case 'PLAYERNAME':
                return $item->$column_name;
            case 'CHARACTERNAME':
                return $item->$column_name;
            case 'PUBLIC_CLAN':
                return $item->$column_name;
            case 'PRIVATE_CLAN':
                return $item->$column_name;
            case 'COMMENT':
                return $item->$column_name;
            case 'LEVEL':
                return $item->$column_name;
            default:
                return print_r($item,true); 
        }
    }

    function get_columns(){
        $columns = array(
            'CHARACTERNAME' => 'Character',
            'PLAYERNAME'    => 'Player',
            'PUBLIC_CLAN'   => 'Public Clan',
            'PRIVATE_CLAN'  => 'Actual Clan',
            'COMMENT'       => 'Prestige is for',
            'LEVEL'         => 'Clan Prestige Level',
        );
        return $columns;
	}
		
   function get_sortable_columns() {
        $sortable_columns = array(
            'CHARACTERNAME' => array('CHARACTERNAME',true),
            'PLAYERNAME'    => array('PLAYERNAME',false),
            'PUBLIC_CLAN'   => array('CLAN',false),
            'PRIVATE_CLAN'  => array('CLAN',false),
            'COMMENT'       => array('COMMENT',false),
        );
        return $sortable_columns;
    }

	function extra_tablenav($which) {	
		if ($which == 'top')  {
			echo "<div class='gvfilter'>";
			
			echo "<span>Clan: </span>";
			echo "<select name='clanfilter'>\n";
			echo '<option value="all">All</option>';
			foreach (get_clans() as $clan) {
				echo '<option value="' . $clan->ID . '" ';
				selected( $this->active_filter_clan, $clan->ID );
				echo '>' . $clan->NAME , '</option>';
			}
			echo '</select>';

			$this->filter_tablenav();
		
			echo "</div>";
		}
	}
	function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

		/* filters */
		$this->load_filters();
		/* Clan */
		if ( isset( $_REQUEST['clanfilter'] )) {
			$this->active_filter_clan = sanitize_key( $_REQUEST['clanfilter'] );
		} else {
			$this->active_filter_clan = 'all';
		}
		
		$filterinfo = $this->get_filter_sql();
		$args = array();
		if (isset($this->active_filter_clan) && $this->active_filter_clan != 'all') {
			$clanfilter = " AND (characters.PUBLIC_CLAN_ID = %s OR characters.PRIVATE_CLAN_ID = %s)";
			$args = array($this->active_filter_clan, $this->active_filter_clan);
		}
		$args = array_merge($args, $filterinfo[1]);
		
		$subtable = "SELECT charbgs.CHARACTER_ID as CHARACTER_ID, charbgs.LEVEL as LEVEL, charbgs.COMMENT as COMMENT
						FROM
							" . GVLARP_TABLE_PREFIX. "PLAYER players, 
							" . GVLARP_TABLE_PREFIX. "CHARACTER_BACKGROUND charbgs,
							" . GVLARP_TABLE_PREFIX. "BACKGROUND backgrounds,
							" . GVLARP_TABLE_PREFIX. "CLAN pubclans, 
							" . GVLARP_TABLE_PREFIX. "CLAN privclans, 
							" . GVLARP_TABLE_PREFIX. "CHARACTER characters
						WHERE
							charbgs.BACKGROUND_ID = backgrounds.ID
							AND players.ID = characters.PLAYER_ID
							AND characters.ID = charbgs.CHARACTER_ID
							AND characters.PUBLIC_CLAN_ID = pubclans.ID
							AND characters.PRIVATE_CLAN_ID = privclans.ID
							AND backgrounds.NAME = 'Clan Prestige'";
		$subtable .= $clanfilter;
		$subtable .= $filterinfo[0];
				
		$sql = "SELECT characters.NAME as CHARACTERNAME, players.NAME as PLAYERNAME, 
					pubclans.NAME as PUBLIC_CLAN, privclans.NAME as PRIVATE_CLAN, 
					TBGRND.LEVEL, TBGRND.COMMENT
				FROM 
					" . GVLARP_TABLE_PREFIX. "PLAYER players, 
					" . GVLARP_TABLE_PREFIX. "CLAN pubclans, 
					" . GVLARP_TABLE_PREFIX. "CLAN privclans, 
					" . GVLARP_TABLE_PREFIX. "CHARACTER characters
					LEFT JOIN 
						($subtable) as TBGRND 
					ON 
						TBGRND.CHARACTER_ID = characters.ID 
				WHERE 
					players.ID = characters.PLAYER_ID
					AND characters.PUBLIC_CLAN_ID = pubclans.ID
					AND characters.PRIVATE_CLAN_ID = privclans.ID";
		$sql .= $clanfilter;
		$sql .= $filterinfo[0];
		

		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY {$_REQUEST['orderby']} {$_REQUEST['order']}";

		$this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
		
		/* run query */

		$args = array_merge($args, $args);
		$sql = $wpdb->prepare($sql,$args);
		/* echo "<p>SQL: $sql (";
		print_r($args);
		echo ")</p>"; */
		$data =$wpdb->get_results($sql);
 		
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        
        $this->items = $data;
		
		$this->output_report("Clan Prestige Report");
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
	}
}
?>