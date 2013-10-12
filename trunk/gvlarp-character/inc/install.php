<?php

register_activation_hook(__FILE__, "gvlarp_character_install");
register_activation_hook( __FILE__, 'gvlarp_character_install_data' );

global $gvlarp_character_db_version;
$gvlarp_character_db_version = "1.8.22"; /* 1.8.16 */

function gvlarp_update_db_check() {
    global $gvlarp_character_db_version;
	
    if (get_site_option( 'gvlarp_character_db_version' ) != $gvlarp_character_db_version) {
        gvlarp_character_install();
		gvlarp_character_install_data();
		
		update_option( "gvlarp_character_db_version", $gvlarp_character_db_version );
    }
}
add_action( 'plugins_loaded', 'gvlarp_update_db_check' );

function gvlarp_character_install() {
	global $wpdb;
	global $gvlarp_character_db_version;
	
	$table_prefix = GVLARP_TABLE_PREFIX;
	$installed_version = get_option( "gvlarp_character_db_version" );
	
	if( $installed_version != $gvlarp_character_db_version ) {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		$current_table_name = $table_prefix . "PLAYER_TYPE";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "PLAYER_STATUS";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "PLAYER";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID                 MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME               VARCHAR(60)  NOT NULL,
					PLAYER_TYPE_ID     MEDIUMINT(9) NOT NULL,
					PLAYER_STATUS_ID   MEDIUMINT(9) NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "player_constraint_1` FOREIGN KEY (PLAYER_TYPE_ID)   REFERENCES " . $table_prefix . "PLAYER_TYPE(ID),
					CONSTRAINT `" . $table_prefix . "player_constraint_2` FOREIGN KEY (PLAYER_STATUS_ID) REFERENCES " . $table_prefix . "PLAYER_STATUS(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);


		$current_table_name = $table_prefix . "ST_LINK";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL  AUTO_INCREMENT,
					VALUE        VARCHAR(32)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					LINK         TINYTEXT     NOT NULL,
					ORDERING     SMALLINT(3)  NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "OFFICE";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL  AUTO_INCREMENT,
					NAME         VARCHAR(32)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					ORDERING     SMALLINT(3)  NOT NULL,
					VISIBLE      VARCHAR(1)   NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "XP_REASON";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "PATH_REASON";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(24)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "TEMPORARY_STAT_REASON";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_TYPE";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_STATUS";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CLAN";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           	MEDIUMINT(9)  NOT NULL AUTO_INCREMENT,
					NAME         	VARCHAR(30)   NOT NULL,
					DESCRIPTION  	TINYTEXT      NOT NULL,
					ICON_LINK    	TINYTEXT      NOT NULL,
					CLAN_PAGE_LINK	TINYTEXT      NOT NULL,
					CLAN_FLAW    	TINYTEXT      NOT NULL,
					CLAN_COST_MODEL_ID      MEDIUMINT(9) NOT NULL,
					NONCLAN_COST_MODEL_ID   MEDIUMINT(9) NOT NULL,
					VISIBLE      	VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID)
					CONSTRAINT `" . $table_prefix . "clan_constraint_1` FOREIGN KEY (CLAN_COST_MODEL_ID)    REFERENCES " . $table_prefix . "COST_MODEL(ID),
					CONSTRAINT `" . $table_prefix . "clan_constraint_2` FOREIGN KEY (NONCLAN_COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		/* $current_table_name = $table_prefix . "COURT";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					VISIBLE      VARCHAR(1)   NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql); */
		if (table_exists('COURT')) {
			rename_table("COURT", "DOMAIN");
		} else {		
			$current_table_name = $table_prefix . "DOMAIN";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
						NAME         VARCHAR(16)  NOT NULL,
						DESCRIPTION  TINYTEXT     NOT NULL,
						VISIBLE      VARCHAR(1)   NOT NULL,
						PRIMARY KEY  (ID)
						) ENGINE=INNODB;";
			dbDelta($sql);
		}

		$current_table_name = $table_prefix . "SECT";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					VISIBLE      VARCHAR(1)   NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		
		$current_table_name = $table_prefix . "SOURCE_BOOK";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9)  NOT NULL   AUTO_INCREMENT,
					CODE         VARCHAR(16)   NOT NULL,
					NAME         VARCHAR(60)   NOT NULL,
					VISIBLE      VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		
		$current_table_name = $table_prefix . "COST_MODEL";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  TINYTEXT     NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "COST_MODEL_STEP";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9) NOT NULL  AUTO_INCREMENT,
					COST_MODEL_ID   MEDIUMINT(9) NOT NULL,
					SEQUENCE        SMALLINT(3)  NOT NULL,
					CURRENT_VALUE   SMALLINT(3)  NOT NULL,
					NEXT_VALUE      SMALLINT(3)  NOT NULL,
					FREEBIE_COST    SMALLINT(3)  NOT NULL,
					XP_COST         SMALLINT(3)  NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "cost_model_step_constraint_1` FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "GENERATION";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME            VARCHAR(16)  NOT NULL,
					BLOODPOOL       SMALLINT(3)  NOT NULL,
					BLOOD_PER_ROUND SMALLINT(2)  NOT NULL,
					MAX_RATING      SMALLINT(2)  NOT NULL,
					MAX_DISCIPLINE  SMALLINT(2)  NOT NULL,
					COST            SMALLINT(3)  NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "STAT";
		
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              	MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME            	VARCHAR(16)   NOT NULL,
					DESCRIPTION     	TINYTEXT      NOT NULL,
					GROUPING        	VARCHAR(30)   NOT NULL,
					ORDERING        	SMALLINT(3)   NOT NULL,
					COST_MODEL_ID   	MEDIUMINT(9)  NOT NULL,
					SPECIALISATION_AT	SMALLINT(2)	  NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "stat_constraint_1` FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "ROAD_OR_PATH";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME            VARCHAR(32)   NOT NULL,
					DESCRIPTION     TINYTEXT      NOT NULL,
					STAT1_ID        MEDIUMINT(9)  NOT NULL,
					STAT2_ID        MEDIUMINT(9)  NOT NULL,
					SOURCE_BOOK_ID  MEDIUMINT(9)   NOT NULL,
					PAGE_NUMBER     SMALLINT(4)   NOT NULL,
					VISIBLE         VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "road_constraint_1` FOREIGN KEY (STAT1_ID) REFERENCES " . $table_prefix . "STAT(ID),
					CONSTRAINT `" . $table_prefix . "road_constraint_2` FOREIGN KEY (STAT2_ID) REFERENCES " . $table_prefix . "STAT(ID),
					CONSTRAINT `" . $table_prefix . "road_constraint_3` FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		
		$current_table_name = $table_prefix . "CHARACTER";
		$rename = array (
			'from' => 'COURT_ID',
			'to'   => 'DOMAIN_ID',
			'table' => $current_table_name,
			'definition' => 'MEDIUMINT(9)  NOT NULL',
			'constraint' => $current_table_name . "_ibfk_8",
			'reference'  => $table_prefix . "DOMAIN(ID)"
		);
		rename_column($rename);
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID                        MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME                      VARCHAR(60)   NOT NULL,
					PUBLIC_CLAN_ID            MEDIUMINT(9)  NOT NULL,
					PRIVATE_CLAN_ID           MEDIUMINT(9)  NOT NULL,
					GENERATION_ID             MEDIUMINT(9)  NOT NULL,
					DATE_OF_BIRTH             DATE          NOT NULL,
					DATE_OF_EMBRACE           DATE          NOT NULL,
					SIRE                      VARCHAR(60)   NOT NULL,
					PLAYER_ID                 MEDIUMINT(9)  NOT NULL,
					CHARACTER_TYPE_ID         MEDIUMINT(9)  NOT NULL,
					CHARACTER_STATUS_ID       MEDIUMINT(9)  NOT NULL,
					CHARACTER_STATUS_COMMENT  VARCHAR(120),
					ROAD_OR_PATH_ID           MEDIUMINT(9)  NOT NULL,
					ROAD_OR_PATH_RATING       SMALLINT(3)   NOT NULL,
					DOMAIN_ID                 MEDIUMINT(9)  NOT NULL,
					WORDPRESS_ID              VARCHAR(32)   NOT NULL,
					SECT_ID                   MEDIUMINT(9)  NOT NULL,
					LAST_UPDATED              DATE          NOT NULL,
					DELETED                   VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "char_constraint_1` FOREIGN KEY (PUBLIC_CLAN_ID)       REFERENCES " . $table_prefix . "CLAN(ID),
					CONSTRAINT `" . $table_prefix . "char_constraint_2` FOREIGN KEY (PRIVATE_CLAN_ID)      REFERENCES " . $table_prefix . "CLAN(ID),
					CONSTRAINT `" . $table_prefix . "char_constraint_3` FOREIGN KEY (GENERATION_ID)        REFERENCES " . $table_prefix . "GENERATION(ID),
					CONSTRAINT `" . $table_prefix . "char_constraint_4` FOREIGN KEY (PLAYER_ID)            REFERENCES " . $table_prefix . "PLAYER(ID),
					CONSTRAINT `" . $table_prefix . "char_constraint_5` FOREIGN KEY (CHARACTER_TYPE_ID)    REFERENCES " . $table_prefix . "CHARACTER_TYPE(ID),
					CONSTRAINT `" . $table_prefix . "char_constraint_6` FOREIGN KEY (CHARACTER_STATUS_ID)  REFERENCES " . $table_prefix . "CHARACTER_STATUS(ID),
					CONSTRAINT `" . $table_prefix . "char_constraint_7` FOREIGN KEY (ROAD_OR_PATH_ID)      REFERENCES " . $table_prefix . "ROAD_OR_PATH(ID),
					CONSTRAINT `" . $table_prefix . "char_constraint_8` FOREIGN KEY (DOMAIN_ID)            REFERENCES " . $table_prefix . "DOMAIN(ID)
					CONSTRAINT `" . $table_prefix . "char_constraint_9` FOREIGN KEY (SECT_ID)              REFERENCES " . $table_prefix . "SECT(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_OFFICE";
		$rename = array (
			'from' => 'COURT_ID',
			'to'   => 'DOMAIN_ID',
			'table' => $current_table_name,
			'definition' => 'MEDIUMINT(9)  NOT NULL',
			'constraint' => $current_table_name . "_ibfk_2",
			'reference'  => $table_prefix . "DOMAIN(ID)"
		);
		rename_column($rename);
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID            MEDIUMINT(9) NOT NULL  AUTO_INCREMENT,
					OFFICE_ID     MEDIUMINT(9) NOT NULL,
					DOMAIN_ID     MEDIUMINT(9) NOT NULL,
					CHARACTER_ID  MEDIUMINT(9) NOT NULL,
					COMMENT       VARCHAR(60),
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "office_constraint_1` FOREIGN KEY (OFFICE_ID)    REFERENCES " . $table_prefix . "OFFICE(ID),
					CONSTRAINT `" . $table_prefix . "office_constraint_2` FOREIGN KEY (DOMAIN_ID)    REFERENCES " . $table_prefix . "DOMAIN(ID),
					CONSTRAINT `" . $table_prefix . "office_constraint_3` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "PLAYER_XP";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID             MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					PLAYER_ID      MEDIUMINT(9)  NOT NULL,
					CHARACTER_ID   MEDIUMINT(9)  NOT NULL,
					XP_REASON_ID   MEDIUMINT(9)  NOT NULL,
					AWARDED        DATE          NOT NULL,
					AMOUNT         SMALLINT(3)   NOT NULL,
					COMMENT        VARCHAR(120)  NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "player_xp_constraint_1` FOREIGN KEY (PLAYER_ID)    REFERENCES " . $table_prefix . "PLAYER(ID),
					CONSTRAINT `" . $table_prefix . "player_xp_constraint_2` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
					CONSTRAINT `" . $table_prefix . "player_xp_constraint_3` FOREIGN KEY (XP_REASON_ID) REFERENCES " . $table_prefix . "XP_REASON(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		/* 	CHARTABLE 		= Character table to update or add new row to
			CHARTABLE_ID	= ID of row in character table to update (0 for new)
			CHARTABLE_LEVEL = LEVEL of item to add/update character table to
			SPECIALISATION  = COMMENT of item to add/update character table to
			COMMENT			= What gets displayed in spend table
			ITEMTABLE       = For new skills/stats/etc: what table they belong to
			ITEMNAME        = For new skills/stats/etc: what is the name of the column for the item
			ITEMTABLE_ID    = For new skills/stats/etc: what table ID they have
		*/
		$current_table_name = $table_prefix . "PENDING_XP_SPEND";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID             MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					PLAYER_ID      MEDIUMINT(9)  NOT NULL,
					CHARACTER_ID   MEDIUMINT(9)  NOT NULL,
					CHARTABLE      TINYTEXT      NOT NULL,
					CHARTABLE_ID   MEDIUMINT(9)  NOT NULL,
					CHARTABLE_LEVEL  TINYTEXT    NOT NULL,
					AWARDED        DATE          NOT NULL,
					AMOUNT         SMALLINT(3)   NOT NULL,
					COMMENT        VARCHAR(120)  NOT NULL,
					SPECIALISATION VARCHAR(64)	 NOT NULL,
					TRAINING_NOTE  VARCHAR(164)  NOT NULL,
					ITEMTABLE      TINYTEXT      NOT NULL,
					ITEMNAME       TINYTEXT      NOT NULL,
					ITEMTABLE_ID   MEDIUMINT(9)  NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "pending_xp_constraint_1` FOREIGN KEY (PLAYER_ID)    REFERENCES " . $table_prefix . "PLAYER(ID),
					CONSTRAINT `" . $table_prefix . "pending_xp_constraint_2` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		$remove = array( 'CODE' => '', 'ITEM_ID' => '');
		remove_columns($current_table_name, $remove);

		$current_table_name = $table_prefix . "CHARACTER_ROAD_OR_PATH";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID               MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					CHARACTER_ID     MEDIUMINT(9)  NOT NULL,
					PATH_REASON_ID   MEDIUMINT(9)  NOT NULL,
					AWARDED          DATE          NOT NULL,
					AMOUNT           SMALLINT(3)   NOT NULL,
					COMMENT          VARCHAR(120)  NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "char_road_constraint_1` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
					CONSTRAINT `" . $table_prefix . "char_road_constraint_2` FOREIGN KEY (PATH_REASON_ID) REFERENCES " . $table_prefix . "PATH_REASON(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "TEMPORARY_STAT";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(60)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_TEMPORARY_STAT";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                        MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID              MEDIUMINT(9)  NOT NULL,
						TEMPORARY_STAT_ID         MEDIUMINT(9)  NOT NULL,
						TEMPORARY_STAT_REASON_ID  MEDIUMINT(9)  NOT NULL,
						AWARDED                   DATE          NOT NULL,
						AMOUNT                    SMALLINT(3)   NOT NULL,
						COMMENT                   VARCHAR(120)  NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_temp_constraint_1` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_temp_constraint_2` FOREIGN KEY (TEMPORARY_STAT_ID) REFERENCES " . $table_prefix . "TEMPORARY_STAT(ID),
						CONSTRAINT `" . $table_prefix . "char_temp_constraint_3` FOREIGN KEY (TEMPORARY_STAT_REASON_ID) REFERENCES " . $table_prefix . "TEMPORARY_STAT_REASON(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "SKILL";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              	MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            	VARCHAR(16)   NOT NULL,
						DESCRIPTION     	TINYTEXT      NOT NULL,
						GROUPING        	VARCHAR(30)   NOT NULL,
						COST_MODEL_ID   	MEDIUMINT(9)  NOT NULL,
						MULTIPLE			VARCHAR(1)	  NOT NULL,
						SPECIALISATION_AT	SMALLINT(2)	  NOT NULL,
						VISIBLE         	VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "skill_constraint_1` FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "BACKGROUND";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME            VARCHAR(30)   NOT NULL,
					DESCRIPTION     TINYTEXT      NOT NULL,
					GROUPING        VARCHAR(30)   NOT NULL,
					COST_MODEL_ID   MEDIUMINT(9)  NOT NULL,
					HAS_SECTOR      VARCHAR(1)    NOT NULL,
					VISIBLE         VARCHAR(1)    NOT NULL,
					BACKGROUND_QUESTION TEXT,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "background_constraint_1` FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
					) ENGINE=INNODB;";

		
		dbDelta($sql);
			
		$current_table_name = $table_prefix . "SECTOR";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME            VARCHAR(16)   NOT NULL,
					DESCRIPTION     TINYTEXT      NOT NULL,
					VISIBLE         VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "MERIT";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                  MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME                VARCHAR(32)   NOT NULL,
						DESCRIPTION         TINYTEXT      NOT NULL,
						VALUE               SMALLINT(3)   NOT NULL,
						GROUPING            VARCHAR(30)   NOT NULL,
						COST                SMALLINT(3)   NOT NULL,
						XP_COST             SMALLINT(3)   NOT NULL,
						MULTIPLE            VARCHAR(1)    NOT NULL,
						HAS_SPECIALISATION  VARCHAR(1)    NOT NULL,
						SOURCE_BOOK_ID      MEDIUMINT(9)  NOT NULL,
						PAGE_NUMBER         SMALLINT(4)   NOT NULL,
						VISIBLE             VARCHAR(1)    NOT NULL,
						BACKGROUND_QUESTION VARCHAR(255),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "merit_constraint_1` FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "DISCIPLINE";
		$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL   AUTO_INCREMENT,
						NAME            VARCHAR(32)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						SOURCE_BOOK_ID  MEDIUMINT(9)  NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "discipline_constraint_1` FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);
		
		/* remove COST_MODEL_ID if it exists */
		$remove = array (
			'COST_MODEL_ID' => $table_prefix . 'ibfk_1' 
		);
		remove_columns($current_table_name, $remove);
		
		
		$current_table_name = $table_prefix . "PATH";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(63)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						DISCIPLINE_ID   MEDIUMINT(9)  NOT NULL,
						COST_MODEL_ID   MEDIUMINT(9)  NOT NULL,
						SOURCE_BOOK_ID  MEDIUMINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "path_constraint_1` FOREIGN KEY (DISCIPLINE_ID)  REFERENCES " . $table_prefix . "DISCIPLINE(ID),
						CONSTRAINT `" . $table_prefix . "path_constraint_2` FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID),
						CONSTRAINT `" . $table_prefix . "path_constraint_3` FOREIGN KEY (COST_MODEL_ID)  REFERENCES " . $table_prefix . "COST_MODEL(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "DISCIPLINE_POWER";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID mediumint(9) NOT NULL AUTO_INCREMENT,
						NAME varchar(32) NOT NULL,
						DESCRIPTION TINYTEXT NOT NULL,
						LEVEL smallint(2) NOT NULL,
						DISCIPLINE_ID mediumint(9) NOT NULL,
						DICE_POOL varchar(60) NOT NULL,
						DIFFICULTY varchar(60) NOT NULL,
						COST smallint(3) NOT NULL,
						SOURCE_BOOK_ID mediumint(9) NOT NULL,
						PAGE_NUMBER smallint(4) NOT NULL,
						VISIBLE varchar(1) NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "disc_power_constraint_1` FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID),
						CONSTRAINT `" . $table_prefix . "disc_power_constraint_2` FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "PATH_POWER";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(32)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						LEVEL           SMALLINT(2)   NOT NULL,
						PATH_ID         MEDIUMINT(9)  NOT NULL,
						DICE_POOL       VARCHAR(60)   NOT NULL,
						DIFFICULTY      VARCHAR(60)   NOT NULL,
						COST            SMALLINT(3)   NOT NULL,
						SOURCE_BOOK_ID  MEDIUMINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "path_power_constraint_1` FOREIGN KEY (PATH_ID) REFERENCES " . $table_prefix . "PATH(ID),
						CONSTRAINT `" . $table_prefix . "path_power_constraint_2` FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "RITUAL";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(60)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						LEVEL           SMALLINT(2)   NOT NULL,
						DISCIPLINE_ID   MEDIUMINT(9)  NOT NULL,
						DICE_POOL       VARCHAR(60)   NOT NULL,
						DIFFICULTY      VARCHAR(60)   NOT NULL,
						COST            SMALLINT(3)   NOT NULL,
						SOURCE_BOOK_ID  MEDIUMINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "ritual_constraint_1` FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID),
						CONSTRAINT `" . $table_prefix . "ritual_constraint_2` FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CLAN_DISCIPLINE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID             MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
						CLAN_ID        MEDIUMINT(9) NOT NULL,
						DISCIPLINE_ID  MEDIUMINT(9) NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "clan_disc_constraint_1` FOREIGN KEY (CLAN_ID)       REFERENCES " . $table_prefix . "CLAN(ID),
						CONSTRAINT `" . $table_prefix . "clan_disc_constraint_2` FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_STAT";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID            MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID  MEDIUMINT(9)  NOT NULL,
						STAT_ID       MEDIUMINT(9)  NOT NULL,
						LEVEL         SMALLINT(3)   NOT NULL,
						COMMENT       VARCHAR(60),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_stat_constraint_1` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_stat_constraint_2` FOREIGN KEY (STAT_ID)      REFERENCES " . $table_prefix . "STAT(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_RITUAL";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID            MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID  MEDIUMINT(9)  NOT NULL,
						RITUAL_ID     MEDIUMINT(9)  NOT NULL,
						LEVEL         SMALLINT(3)   NOT NULL,
						COMMENT       VARCHAR(60),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_ritual_constraint_1` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_ritual_constraint_2` FOREIGN KEY (RITUAL_ID)    REFERENCES " . $table_prefix . "RITUAL(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_DISCIPLINE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID             MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID   MEDIUMINT(9)  NOT NULL,
						DISCIPLINE_ID  MEDIUMINT(9)  NOT NULL,
						LEVEL          SMALLINT(3)   NOT NULL,
						COMMENT        VARCHAR(60),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_disc_constraint_1` FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_disc_constraint_2` FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_PATH";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID             MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID   MEDIUMINT(9)  NOT NULL,
						PATH_ID        MEDIUMINT(9)  NOT NULL,
						LEVEL          SMALLINT(3)   NOT NULL,
						COMMENT        VARCHAR(60),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_path_constraint_1` FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_path_constraint_2` FOREIGN KEY (PATH_ID) REFERENCES " . $table_prefix . "PATH(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_PATH_POWER";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                      MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID            MEDIUMINT(9)  NOT NULL,
						PATH_POWER_ID           MEDIUMINT(9)  NOT NULL,
						LEVEL                   SMALLINT(3)   NOT NULL,
						COMMENT                 VARCHAR(60),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_path_power_constraint_1` FOREIGN KEY (CHARACTER_ID)    REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_path_power_constraint_2` FOREIGN KEY (PATH_POWER_ID)   REFERENCES " . $table_prefix . "PATH_POWER(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_DISCIPLINE_POWER";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                      MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID            MEDIUMINT(9)  NOT NULL,
						DISCIPLINE_POWER_ID     MEDIUMINT(9)  NOT NULL,
						LEVEL                   SMALLINT(3)   NOT NULL,
						COMMENT                 VARCHAR(60),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_disc_power_constraint_1` FOREIGN KEY (CHARACTER_ID)        REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_disc_power_constraint_2` FOREIGN KEY (DISCIPLINE_POWER_ID) REFERENCES " . $table_prefix . "DISCIPLINE_POWER(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_MERIT";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID            MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID  MEDIUMINT(9)  NOT NULL,
						MERIT_ID      MEDIUMINT(9)  NOT NULL,
						LEVEL         SMALLINT(3)   NOT NULL,
						COMMENT       VARCHAR(60),
						APPROVED_DETAIL TEXT,
						PENDING_DETAIL  TEXT,
						DENIED_DETAIL   TEXT,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_merit_constraint_1` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_merit_constraint_2` FOREIGN KEY (MERIT_ID)     REFERENCES " . $table_prefix . "MERIT(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_SKILL";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID            MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID  MEDIUMINT(9)  NOT NULL,
						SKILL_ID      MEDIUMINT(9)  NOT NULL,
						LEVEL         SMALLINT(3)   NOT NULL,
						COMMENT       VARCHAR(60),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_skill_constraint_1` FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_skill_constraint_2` FOREIGN KEY (SKILL_ID)     REFERENCES " . $table_prefix . "SKILL(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_BACKGROUND";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID      MEDIUMINT(9)  NOT NULL,
						BACKGROUND_ID     MEDIUMINT(9)  NOT NULL,
						LEVEL             SMALLINT(3)   NOT NULL,
						SECTOR_ID		  MEDIUMINT(9)  NOT NULL,
						COMMENT           VARCHAR(60),
						APPROVED_DETAIL   TEXT,
						PENDING_DETAIL    TEXT,
						DENIED_DETAIL     TEXT,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_bg_constraint_1` FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_bg_constraint_2` FOREIGN KEY (BACKGROUND_ID) REFERENCES " . $table_prefix . "BACKGROUND(ID),
						CONSTRAINT `" . $table_prefix . "char_bg_constraint_3` FOREIGN KEY (SECTOR_ID) REFERENCES " . $table_prefix . "SECTOR(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);
		
		$current_table_name = $table_prefix . "COMBO_DISCIPLINE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL   AUTO_INCREMENT,
						NAME            VARCHAR(60)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						COST            SMALLINT(3)   NOT NULL,
						SOURCE_BOOK_ID  MEDIUMINT(9)  NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "combo_disc_constraint_1` FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_COMBO_DISCIPLINE";
		
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                   MEDIUMINT(9)  NOT NULL AUTO_INCREMENT,
						CHARACTER_ID         MEDIUMINT(9)  NOT NULL,
						COMBO_DISCIPLINE_ID  MEDIUMINT(9)  NOT NULL,
						COMMENT              VARCHAR(60),
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_combo_constraint_1` FOREIGN KEY (CHARACTER_ID)        REFERENCES " . $table_prefix . "CHARACTER(ID),
						CONSTRAINT `" . $table_prefix . "char_combo_constraint_2` FOREIGN KEY (COMBO_DISCIPLINE_ID) REFERENCES " . $table_prefix . "COMBO_DISCIPLINE(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "COMBO_DISCIPLINE_PREREQUISITE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                   MEDIUMINT(9)  NOT NULL AUTO_INCREMENT,
						COMBO_DISCIPLINE_ID  MEDIUMINT(9)  NOT NULL,
						DISCIPLINE_ID        MEDIUMINT(9)  NOT NULL,
						DISCIPLINE_LEVEL     SMALLINT(3)   NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_combo_pre_constraint_1` FOREIGN KEY (COMBO_DISCIPLINE_ID) REFERENCES " . $table_prefix . "COMBO_DISCIPLINE(ID),
						CONSTRAINT `" . $table_prefix . "char_combo_pre_constraint_2` FOREIGN KEY (DISCIPLINE_ID)       REFERENCES " . $table_prefix . "DISCIPLINE(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_PROFILE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                MEDIUMINT(9)   NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID      MEDIUMINT(9)   NOT NULL,
						QUOTE             TEXT			 NOT NULL,
						PORTRAIT          TINYTEXT       NOT NULL,
						PRIMARY KEY  (ID),
						CONSTRAINT `" . $table_prefix . "char_profile_constraint_1` FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CONFIG";
		//rename_column("CONFIG", "HOME_COURT_ID", "HOME_DOMAIN_ID", "MEDIUMINT(9)");
		$rename = array (
			'from' => 'HOME_COURT_ID',
			'to'   => 'HOME_DOMAIN_ID',
			'table' => $current_table_name,
			'definition' => 'MEDIUMINT(9)  NOT NULL',
			'constraint' => $table_prefix . "config_constraint_1",
			'reference'  => $table_prefix . "DOMAIN(ID)"
		);
		rename_column($rename);
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID                         MEDIUMINT(9)   NOT NULL  AUTO_INCREMENT,
					PROFILE_LINK               TINYTEXT       NOT NULL,
					PLACEHOLDER_IMAGE          TINYTEXT       NOT NULL,
					CLAN_DISCIPLINE_DISCOUNT   VARCHAR(10)    NOT NULL,
					ANDROID_LINK               TINYTEXT       NOT NULL,
					HOME_DOMAIN_ID             MEDIUMINT(9)   NOT NULL,
					HOME_SECT_ID               MEDIUMINT(9)   NOT NULL,
					PRIMARY KEY  (ID)
					CONSTRAINT `" . $table_prefix . "config_constraint_1` FOREIGN KEY (HOME_DOMAIN_ID)  REFERENCES " . $table_prefix . "DOMAIN(ID)
					CONSTRAINT `" . $table_prefix . "config_constraint_2` FOREIGN KEY (HOME_SECT_ID)    REFERENCES " . $table_prefix . "SECT(ID)
					) ENGINE=INNODB;";
		//echo "<p>SQL: $sql</p>";
		dbDelta($sql);
		/* remove COLUMNS if it exists */
		$remove = array (
			'CLAN_DISCIPLINE_DISCOUNT' => '' 
		);
		remove_columns($current_table_name, $remove);
		
		$current_table_name = $table_prefix . "EXTENDED_BACKGROUND";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID                    MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					ORDERING              SMALLINT(4)   NOT NULL,
					GROUPING              VARCHAR(90)   NOT NULL,
					TITLE                 VARCHAR(90)   NOT NULL,
					BACKGROUND_QUESTION   TEXT   		NOT NULL,
					VISIBLE				  VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		
		$current_table_name = $table_prefix . "CHARACTER_EXTENDED_BACKGROUND";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID                    MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					CHARACTER_ID          MEDIUMINT(9)  NOT NULL,
					QUESTION_ID			  MEDIUMINT(9)  NOT NULL,
					APPROVED_DETAIL       TEXT   		NOT NULL,
					PENDING_DETAIL        TEXT   		NOT NULL,
					DENIED_DETAIL         TEXT   		NOT NULL,
					PRIMARY KEY  (ID),
					CONSTRAINT `" . $table_prefix . "char_ext_bg_constraint_1` FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
					CONSTRAINT `" . $table_prefix . "char_ext_bg_constraint_2` FOREIGN KEY (QUESTION_ID)  REFERENCES " . $table_prefix . "EXTENDED_BACKGROUND(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		

	
	}
	
}

function gvlarp_character_install_data() {
	global $wpdb;
	
	$data = array (
		'viewCharSheet' => array(	'VALUE' => 'viewCharSheet',
									'DESCRIPTION' => 'View Character Sheet',
									'LINK' => '',
									'ORDERING' => 1
							),
		'printCharSheet' => array(	'VALUE' => 'printCharSheet',
									'DESCRIPTION' => 'View Printable Character Sheet',
									'LINK' => '',
									'ORDERING' => 2
							),
		'viewCharPage' => array(	'VALUE' => 'viewCharPage',
									'DESCRIPTION' => 'View Character Page',
									'LINK' => '',
									'ORDERING' => 3
							),
		'View Character XP Page' => array(	'VALUE' => 'View Character XP Page',
											'DESCRIPTION' => 'View Character XP Page',
											'LINK' => '',
											'ORDERING' => 4
									),
		'viewXPSpend' => array(	'VALUE' => 'viewXPSpend',
								'DESCRIPTION' => 'View XP Spend Workspace',
								'LINK' => '',
								'ORDERING' => 5,
						),
		'viewExtBackgrnd' => array(	'VALUE' => 'viewExtBackgrnd',
								'DESCRIPTION' => 'View Extended Background',
								'LINK' => '',
								'ORDERING' => 6,
						),
	);
	foreach ($data as $key => $entry) {
		$sql = "select VALUE from " . GVLARP_TABLE_PREFIX . "ST_LINK where VALUE = %s;";
		$exists = count($wpdb->get_results($wpdb->prepare($sql,$key)));
		if (!$exists) 
			$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "ST_LINK", $entry);
	}


	// Does the sect ID need to be added to the characters
	$sql = "SELECT ID FROM " . GVLARP_TABLE_PREFIX . "CHARACTER WHERE SECT_ID = 0";
	$results = $wpdb->get_results($sql);
		
	if (count($results) > 0) {
		foreach ($results as $row) {
			$data = array ('SECT_ID' => '1');
			$results = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER",$data,array ('ID' => $row->ID));		
		}
	}
	
	$datalist = glob(GVLARP_CHARACTER_URL . "init/*.csv");
	
	foreach ($datalist as $datafile) {
		$temp = explode(".", basename($datafile));
		$tablename = $temp[1];
		
		$sql = "select ID from " . GVLARP_TABLE_PREFIX . $tablename;
		$rows = count($wpdb->get_results($wpdb->prepare($sql,'')));
		if (!$rows) {
			/* print "<p>Reading data for table $tablename</p>";  */
			$filehandle = fopen($datafile,"r");
			
			$i=0;
			$data = array();
			while(! feof($filehandle)) {
				/* print_r(fgetcsv($filehandle)); */
				
				if ($i == 0) {
					$headings = fgetcsv($filehandle,0,",");
					/* print_r($headings); */
				} else {
					$line = fgetcsv($filehandle,0,",");
					if ($line > 0) {
						$j=0;
						foreach ($headings as $heading) {
							$data[$i-1][$heading] = $line[$j];
							$j++;
						}
					}
				}
				
				$i++;
			}
			fclose($filehandle);
			/* print_r($data); */
			foreach ($data as $id => $entry)
					$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . $tablename, $entry);
		}
		
	}

	
}

function remove_columns($table, $columninfo) {
	global $wpdb;

	//SHOW CREATE TABLE gvpluginwp_GVLARP_CHARACTER
	// gvpluginwp_GVLARP_CHARACTER_ibfk_8
	
	/* echo "</p>columninfo:";
	print_r($columninfo);
	echo "</p>"; */
	
	$existing_keys = $wpdb->get_col("SHOW INDEX FROM $table WHERE Key_name != 'PRIMARY';",2);
	$existing_columns = $wpdb->get_col("DESC $table", 0);
	
	/* which constraints/foreign keys to remove */
	$remove_constraints = array_intersect(array_values($columninfo), $existing_keys);
	$sql = "ALTER TABLE $table DROP FOREIGN KEY ".implode(', DROP INDEX ',$remove_constraints).';';
	
	/* do remove */
	/* if( !empty($remove_constraints) ) {
		echo "</p>constraints:";
		print_r($remove_constraints);
		echo "</p>SQL: $sql</p>";
	} */
	if( !empty($remove_constraints) ) $wpdb->query($sql);			

	/* which columns to remove */
	$remove_columns = array_intersect(array_keys($columninfo), $existing_columns);
	$sql = "ALTER TABLE $table DROP COLUMN ".implode(', DROP COLUMN ',$remove_columns).';';
	
	/* do remove */
	/* if( !empty($remove_columns) ) {
		echo "</p>columns:";
		print_r($remove_columns);
		echo "</p>SQL: $sql</p>";
	} */
	if( !empty($remove_columns) ) $wpdb->query($sql); 

}

function table_exists($table) {
	global $wpdb;

	$sql = "SHOW TABLES LIKE " . GVLARP_TABLE_PREFIX . $table;
	$result = $wpdb->get_results($sql);
	$tableExists = count($result) > 0;
	
	//echo "<p>Table $table exists: $tableExists</p>";
	
	return $tableExists;
}

function rename_column($columninfo) {
	global $wpdb;

	//print_r($columninfo);
	
	$table = $columninfo['table'];
	
	$sql = "SHOW INDEX FROM $table WHERE Key_name != 'PRIMARY';";
	//echo "<p>indexes: $sql</p>";
	$existing_keys = $wpdb->get_col("SHOW INDEX FROM $table WHERE Key_name != 'PRIMARY';",2);
	$existing_columns = $wpdb->get_col("DESC $table", 0);
	
	$remove_constraints = array_intersect(array($columninfo['from']), $existing_keys);
	$sql = "ALTER TABLE $table DROP FOREIGN KEY {$columninfo['constraint']};";
	//echo "<p>rem constraint: $sql</p>";
	if( !empty($remove_constraints) ) $wpdb->query($sql);	
	
	$rename_columns = array_intersect(array($columninfo['from']), $existing_columns);
	$sql = "ALTER TABLE $table CHANGE {$columninfo['from']} {$columninfo['to']} {$columninfo['definition']};";
	//echo "<p>rename col: $sql</p>";
	if (!empty($rename_columns)) $wpdb->query($sql);

	$sql = "ALTER TABLE $table ADD CONSTRAINT {$columninfo['constraint']} FOREIGN KEY ({$columninfo['to']}) REFERENCES {$columninfo['reference']};";
	//echo "<p>add constraint: $sql</p>";
	if( !empty($remove_constraints) ) $wpdb->query($sql);	
	

}

function rename_table($from, $to) {
	global $wpdb;

	$sql = "RENAME TABLE " . GVLARP_TABLE_PREFIX . $from . " TO " . GVLARP_TABLE_PREFIX . $to;
	//echo "<p>rename sql: $sql</p>";
	$result = $wpdb->get_results($sql);

}
?>