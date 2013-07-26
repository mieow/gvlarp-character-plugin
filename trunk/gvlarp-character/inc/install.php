<?php

register_activation_hook(__FILE__, "gvlarp_character_install");
register_activation_hook( __FILE__, 'gvlarp_character_install_data' );

global $gvlarp_character_db_version;
$gvlarp_character_db_version = "1.6.1"; /* 1.6.1 */

function gvlarp_update_db_check() {
    global $gvlarp_character_db_version;
	
    if (get_site_option( 'gvlarp_character_db_version' ) != $gvlarp_character_db_version) {
        gvlarp_character_install();
		gvlarp_character_install_data();
    }
}
add_action( 'plugins_loaded', 'gvlarp_update_db_check' );

function gvlarp_character_install() {
	global $wpdb;
	global $gvlarp_character_db_version;
	
	$table_prefix = $wpdb->prefix . "GVLARP_";
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
					FOREIGN KEY (PLAYER_TYPE_ID)   REFERENCES " . $table_prefix . "PLAYER_TYPE(ID),
					FOREIGN KEY (PLAYER_STATUS_ID) REFERENCES " . $table_prefix . "PLAYER_STATUS(ID)
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
					NAME         	VARCHAR(16)   NOT NULL,
					DESCRIPTION  	TINYTEXT      NOT NULL,
					ICON_LINK    	TINYTEXT      NOT NULL,
					CLAN_PAGE_LINK	TINYTEXT      NOT NULL,
					CLAN_FLAW    	TINYTEXT      NOT NULL,
					VISIBLE      	VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "COURT";
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
					FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "GENERATION";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME            VARCHAR(16)  NOT NULL,
					BLOODPOOL       SMALLINT(3)  NOT NULL,
					BLOOD_PER_ROUND SMALLINT(2)  NOT NULL,
					MAX_RATING      SMALLINT(2)  NOT NULL,
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
					FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "ROAD_OR_PATH";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME            VARCHAR(32)   NOT NULL,
					DESCRIPTION     TINYTEXT      NOT NULL,
					STAT1_ID        MEDIUMINT(9)  NOT NULL,
					STAT2_ID        MEDIUMINT(9)  NOT NULL,
					SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
					PAGE_NUMBER     SMALLINT(4)   NOT NULL,
					VISIBLE         VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID),
					FOREIGN KEY (STAT1_ID) REFERENCES " . $table_prefix . "STAT(ID),
					FOREIGN KEY (STAT2_ID) REFERENCES " . $table_prefix . "STAT(ID),
					FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER";
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
					COURT_ID                  MEDIUMINT(9)  NOT NULL,
					WORDPRESS_ID              VARCHAR(32)   NOT NULL,
					VISIBLE                   VARCHAR(1)    NOT NULL,
					DELETED                   VARCHAR(1)    NOT NULL,
					PRIMARY KEY  (ID),
					FOREIGN KEY (PUBLIC_CLAN_ID)       REFERENCES " . $table_prefix . "CLAN(ID),
					FOREIGN KEY (PRIVATE_CLAN_ID)      REFERENCES " . $table_prefix . "CLAN(ID),
					FOREIGN KEY (GENERATION_ID)        REFERENCES " . $table_prefix . "GENERATION(ID),
					FOREIGN KEY (PLAYER_ID)            REFERENCES " . $table_prefix . "PLAYER(ID),
					FOREIGN KEY (CHARACTER_TYPE_ID)    REFERENCES " . $table_prefix . "CHARACTER_TYPE(ID),
					FOREIGN KEY (CHARACTER_STATUS_ID)  REFERENCES " . $table_prefix . "CHARACTER_STATUS(ID),
					FOREIGN KEY (ROAD_OR_PATH_ID)      REFERENCES " . $table_prefix . "ROAD_OR_PATH(ID),
					FOREIGN KEY (COURT_ID)             REFERENCES " . $table_prefix . "COURT(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_OFFICE";
		
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID            MEDIUMINT(9) NOT NULL  AUTO_INCREMENT,
					OFFICE_ID     MEDIUMINT(9) NOT NULL,
					COURT_ID      MEDIUMINT(9) NOT NULL,
					CHARACTER_ID  MEDIUMINT(9) NOT NULL,
					COMMENT       VARCHAR(60),
					PRIMARY KEY  (ID),
					FOREIGN KEY (OFFICE_ID)    REFERENCES " . $table_prefix . "OFFICE(ID),
					FOREIGN KEY (COURT_ID)     REFERENCES " . $table_prefix . "COURT(ID),
					FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID)
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
					FOREIGN KEY (PLAYER_ID)    REFERENCES " . $table_prefix . "PLAYER(ID),
					FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
					FOREIGN KEY (XP_REASON_ID) REFERENCES " . $table_prefix . "XP_REASON(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "PENDING_XP_SPEND";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID             MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					PLAYER_ID      MEDIUMINT(9)  NOT NULL,
					CHARACTER_ID   MEDIUMINT(9)  NOT NULL,
					CODE           VARCHAR(20)   NOT NULL,
					AWARDED        DATE          NOT NULL,
					AMOUNT         SMALLINT(3)   NOT NULL,
					COMMENT        VARCHAR(120)  NOT NULL,
					SPECIALISATION VARCHAR(64)	 NOT NULL,
					TRAINING_NOTE  VARCHAR(164)  NOT NULL,
					PRIMARY KEY  (ID),
					FOREIGN KEY (PLAYER_ID)    REFERENCES " . $table_prefix . "PLAYER(ID),
					FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_ROAD_OR_PATH";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID               MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					CHARACTER_ID     MEDIUMINT(9)  NOT NULL,
					PATH_REASON_ID   MEDIUMINT(9)  NOT NULL,
					AWARDED          DATE          NOT NULL,
					AMOUNT           SMALLINT(3)   NOT NULL,
					COMMENT          VARCHAR(120)  NOT NULL,
					PRIMARY KEY  (ID),
					FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
					FOREIGN KEY (PATH_REASON_ID) REFERENCES " . $table_prefix . "PATH_REASON(ID)
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
						FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (TEMPORARY_STAT_ID) REFERENCES " . $table_prefix . "TEMPORARY_STAT(ID),
						FOREIGN KEY (TEMPORARY_STAT_REASON_ID) REFERENCES " . $table_prefix . "TEMPORARY_STAT_REASON(ID)
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
						FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "BACKGROUND";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME            VARCHAR(16)   NOT NULL,
					DESCRIPTION     TINYTEXT      NOT NULL,
					GROUPING        VARCHAR(30)   NOT NULL,
					COST_MODEL_ID   MEDIUMINT(9)  NOT NULL,
					HAS_SECTOR      VARCHAR(1)    NOT NULL,
					VISIBLE         VARCHAR(1)    NOT NULL,
					BACKGROUND_QUESTION TEXT,
					PRIMARY KEY  (ID),
					FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
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
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(32)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						VALUE           SMALLINT(3)   NOT NULL,
						GROUPING        VARCHAR(30)   NOT NULL,
						COST            SMALLINT(3)   NOT NULL,
						XP_COST         SMALLINT(3)   NOT NULL,
						MULTIPLE        VARCHAR(1)    NOT NULL,
						SOURCE_BOOK_ID  MEDIUMINT(9)  NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						BACKGROUND_QUESTION VARCHAR(255),
						PRIMARY KEY  (ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "DISCIPLINE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL   AUTO_INCREMENT,
						NAME            VARCHAR(32)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						COST_MODEL_ID   MEDIUMINT(9)  NOT NULL,
						SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID),
						FOREIGN KEY (COST_MODEL_ID)  REFERENCES " . $table_prefix . "COST_MODEL(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "PATH";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(63)   NOT NULL,
						DESCRIPTION     TINYTEXT      NOT NULL,
						DISCIPLINE_ID   MEDIUMINT(9)  NOT NULL,
						COST_MODEL_ID   MEDIUMINT(9)  NOT NULL,
						SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						FOREIGN KEY (DISCIPLINE_ID)  REFERENCES " . $table_prefix . "DISCIPLINE(ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID),
						FOREIGN KEY (COST_MODEL_ID)  REFERENCES " . $table_prefix . "COST_MODEL(ID)
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
						FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
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
						SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						FOREIGN KEY (PATH_ID) REFERENCES " . $table_prefix . "PATH(ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
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
						SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CLAN_DISCIPLINE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID             MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
						CLAN_ID        MEDIUMINT(9) NOT NULL,
						DISCIPLINE_ID  MEDIUMINT(9) NOT NULL,
						PRIMARY KEY  (ID),
						FOREIGN KEY (CLAN_ID)       REFERENCES " . $table_prefix . "CLAN(ID),
						FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID)
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
						FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (STAT_ID)      REFERENCES " . $table_prefix . "STAT(ID)
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
						FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (RITUAL_ID)    REFERENCES " . $table_prefix . "RITUAL(ID)
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
						FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID)
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
						FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (PATH_ID) REFERENCES " . $table_prefix . "PATH(ID)
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
						FOREIGN KEY (CHARACTER_ID)    REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (PATH_POWER_ID)   REFERENCES " . $table_prefix . "PATH_POWER(ID)
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
						FOREIGN KEY (CHARACTER_ID)        REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (DISCIPLINE_POWER_ID) REFERENCES " . $table_prefix . "DISCIPLINE_POWER(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_MERIT";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID            MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID  MEDIUMINT(9)  NOT NULL,
						MERIT_ID      MEDIUMINT(9)  NOT NULL,
						LEVEL         SMALLINT(3)   NOT NULL,
						COMMENT       VARCHAR(60),
						APPROVED_DETAIL VARCHAR(255),
						PENDING_DETAIL  VARCHAR(255),
						DENIED_DETAIL   VARCHAR(255),
						PRIMARY KEY  (ID),
						FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (MERIT_ID)     REFERENCES " . $table_prefix . "MERIT(ID)
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
						FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (SKILL_ID)     REFERENCES " . $table_prefix . "SKILL(ID)
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
						FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (BACKGROUND_ID) REFERENCES " . $table_prefix . "BACKGROUND(ID),
						FOREIGN KEY (SECTOR_ID) REFERENCES " . $table_prefix . "SECTOR(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);
		
		/* TO REMOVE? */
		/* $current_table_name = $table_prefix . "EXTENDED_CHARACTER_BACKGROUND";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                 MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID       MEDIUMINT(9)  NOT NULL,
						TITLE              VARCHAR(64)   NOT NULL,
						CODE               VARCHAR(32)   NOT NULL,
						ORDERING		   SMALLINT(4)	 NOT NULL,
						CURRENT_TEXT       TEXT          NOT NULL,
						PROPOSED_TEXT      TEXT,
						CURRENT_ACCEPTED   VARCHAR(1)    NOT NULL,
						PRIMARY KEY  (ID),
						FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID)
						) ENGINE=INNODB;";
			dbDelta($sql); */

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
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_COMBO_DISCIPLINE";
		
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                   MEDIUMINT(9)  NOT NULL AUTO_INCREMENT,
						CHARACTER_ID         MEDIUMINT(9)  NOT NULL,
						COMBO_DISCIPLINE_ID  MEDIUMINT(9)  NOT NULL,
						COMMENT              VARCHAR(60),
						PRIMARY KEY  (ID),
						FOREIGN KEY (CHARACTER_ID)        REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (COMBO_DISCIPLINE_ID) REFERENCES " . $table_prefix . "COMBO_DISCIPLINE(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "COMBO_DISCIPLINE_PREREQUISITE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                   MEDIUMINT(9)  NOT NULL AUTO_INCREMENT,
						COMBO_DISCIPLINE_ID  MEDIUMINT(9)  NOT NULL,
						DISCIPLINE_ID        MEDIUMINT(9)  NOT NULL,
						DISCIPLINE_LEVEL     SMALLINT(3)   NOT NULL,
						PRIMARY KEY  (ID),
						FOREIGN KEY (COMBO_DISCIPLINE_ID) REFERENCES " . $table_prefix . "COMBO_DISCIPLINE(ID),
						FOREIGN KEY (DISCIPLINE_ID)       REFERENCES " . $table_prefix . "DISCIPLINE(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_PROFILE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                MEDIUMINT(9)   NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID      MEDIUMINT(9)   NOT NULL,
						QUOTE             TEXT			 NOT NULL,
						PORTRAIT          TINYTEXT       NOT NULL,
						PRIMARY KEY  (ID),
						FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CONFIG";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID                         MEDIUMINT(9)   NOT NULL  AUTO_INCREMENT,
					PROFILE_LINK               TINYTEXT       NOT NULL,
					PLACEHOLDER_IMAGE          TINYTEXT       NOT NULL,
					CLAN_DISCIPLINE_DISCOUNT   VARCHAR(10)    NOT NULL,
					PRIMARY KEY  (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		
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
					FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
					FOREIGN KEY (QUESTION_ID)  REFERENCES " . $table_prefix . "EXTENDED_BACKGROUND(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		
		
	
	}
	
}

function gvlarp_character_install_data() {
	global $wpdb;
	
	/* Setup ST Links Config Options */
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
		$sql = "select VALUE from " . GVLARP_TABLE_PREFIX . "ST_LINK where VALUE = '" . $key . "';";
		$exists = count($wpdb->get_results($wpdb->prepare($sql)));
		if (!$exists) 
			$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "ST_LINK", $entry);
	}
	

	/* SECTORS */

	$sql = "select ID from " . GVLARP_TABLE_PREFIX . "SECTOR;";
	$rows = count($wpdb->get_results($wpdb->prepare($sql)));
	if (!$rows) {
		$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "SECTOR", 
			array(	'NAME' => 'Underworld',
					'DESCRIPTION' => 'Streets and crime',
					'VISIBLE' => 'Y'
			) 
		);
		$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "SECTOR", 
			array(	'NAME' => 'Academic',
					'DESCRIPTION' => 'Universities and Colleges',
					'VISIBLE' => 'Y'
			) 
		);
		
	}
	
	/* Extended Background Questions */
	$data = array (
		'0' => array (
			'ID'       => 1,
			'ORDERING' => 1,
			'GROUPING' => "Clan Flaw",
			'TITLE'    => "Clan Flaw",
			'BACKGROUND_QUESTION' => "Explain your Clan Flaw",
			'VISIBLE'  => "N"
		),
		'1' => array (
			'ID'       => 2,
			'ORDERING' => 2,
			'GROUPING' => "Travel",
			'TITLE'    => "Travel To/From Court",
			'BACKGROUND_QUESTION' => "Explain your method of transport to and from court",
			'VISIBLE'  => "N"
		),
		'2' => array (
			'ID'       => 3,
			'ORDERING' => 3,
			'GROUPING' => "Feeding",
			'TITLE'    => "Feeding Habits",
			'BACKGROUND_QUESTION' => "Explain your usual method of hunting and feeding",
			'VISIBLE'  => "N"
		),
		'3' => array (
			'ID'       => 4,
			'ORDERING' => 4,
			'GROUPING' => "History",
			'TITLE'    => "Character History",
			'BACKGROUND_QUESTION' => "Give a summary of your character history",
			'VISIBLE'  => "N"
		)
	
	);
	
	$sql = "select ID from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND;";
	$rows = count($wpdb->get_results($wpdb->prepare($sql)));
	if (!$rows)
		foreach ($data as $id => $entry)
				$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND", $entry);
	
	/* Generations */
	$data = array (
		0 => array (
			'ID'              => 1,
			'NAME'            => 8,
			'BLOODPOOL'       => 15,
			'BLOOD_PER_ROUND' => 3,
			'MAX_RATING'      => 5,
			'MAX_DISCIPLINE'  => 5,
			'COST'            => 5
		),
		1 => array (
			'ID'              => 2,
			'NAME'            => 9,
			'BLOODPOOL'       => 14,
			'BLOOD_PER_ROUND' => 2,
			'MAX_RATING'      => 5,
			'MAX_DISCIPLINE'  => 5,
			'COST'            => 4
		),
		2 => array (
			'ID'              => 3,
			'NAME'            => 10,
			'BLOODPOOL'       => 13,
			'BLOOD_PER_ROUND' => 3,
			'MAX_RATING'      => 5,
			'MAX_DISCIPLINE'  => 5,
			'COST'            => 3
		),
		3 => array (
			'ID'              => 4,
			'NAME'            => 11,
			'BLOODPOOL'       => 12,
			'BLOOD_PER_ROUND' => 1,
			'MAX_RATING'      => 5,
			'MAX_DISCIPLINE'  => 5,
			'COST'            => 2
		),
		4 => array (
			'ID'              => 5,
			'NAME'            => 12,
			'BLOODPOOL'       => 11,
			'BLOOD_PER_ROUND' => 1,
			'MAX_RATING'      => 5,
			'MAX_DISCIPLINE'  => 5,
			'COST'            => 1
		),
		5 => array (
			'ID'              => 6,
			'NAME'            => 13,
			'BLOODPOOL'       => 10,
			'BLOOD_PER_ROUND' => 1,
			'MAX_RATING'      => 5,
			'MAX_DISCIPLINE'  => 5,
			'COST'            => 0
		)
	);
	$sql = "select ID from " . GVLARP_TABLE_PREFIX . "GENERATION;";
	$rows = count($wpdb->get_results($wpdb->prepare($sql)));
	if (!$rows)
		foreach ($data as $id => $entry)
				$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "GENERATION", $entry);
	
	/* Player Status */
	$data = array (
		0 => array (
			'ID'          => 1,
			'NAME'        => 'Active',
			'DESCRIPTION' => 'Player is active'
		),
		1 => array (
			'ID'          => 2,
			'NAME'        => 'Inactive',
			'DESCRIPTION' => 'Player is not active'
		)
	);
	$sql = "select ID from " . GVLARP_TABLE_PREFIX . "PLAYER_STATUS;";
	$rows = count($wpdb->get_results($wpdb->prepare($sql)));
	if (!$rows)
		foreach ($data as $id => $entry)
				$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "PLAYER_STATUS", $entry);
	
	/* Character Status */
	$data = array (
		0 => array (
			'ID'          => 1,
			'NAME'        => 'Alive',
			'DESCRIPTION' => 'Character is alive'
		),
		1 => array (
			'ID'          => 2,
			'NAME'        => 'Torpor',
			'DESCRIPTION' => 'Character is in torpor and will wake up:'
		),
		2 => array (
			'ID'          => 3,
			'NAME'        => 'Dead',
			'DESCRIPTION' => 'The character has been destroyed:'
		),
		3 => array (
			'ID'          => 4,
			'NAME'        => 'Imprisoned',
			'DESCRIPTION' => 'The character has been imprisoned at:'
		),
		4 => array (
			'ID'          => 5,
			'NAME'        => 'Missing',
			'DESCRIPTION' => 'Location and status unknown'
		)
	);
	$sql = "select ID from " . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS;";
	$rows = count($wpdb->get_results($wpdb->prepare($sql)));
	if (!$rows)
		foreach ($data as $id => $entry)
				$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "CHARACTER_STATUS", $entry);
	
	/* Attributes (expecially needed so that the groups match what we output for character sheets */
	$data = array(
		0 => array (
			'ID' => 1,
			'NAME' => 'Strength',
			'DESCRIPTION' => 'How strong your character is',
			'GROUPING' => 'Physical',
			'ORDERING' => 1,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4
		),
		1 => array (
			'ID' => 2,
			'NAME' => 'Dexterity',
			'DESCRIPTION' => 'How agile and coordinated your character is',
			'GROUPING' => 'Physical',
			'ORDERING' => 2,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4 
		),
		2 => array (
			'ID' => 3,
			'NAME' => 'Stamina',
			'DESCRIPTION' => 'How much punishment your character can take',
			'GROUPING' => 'Physical',
			'ORDERING' => 3,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4
		),
		3 => array (
			'ID' => 4,
			'NAME' => 'Appearance',
			'DESCRIPTION' => 'How good looking your character is',
			'GROUPING' => 'Social',
			'ORDERING' => 6,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4 
		),
		4 => array (
			'ID' => 5,
			'NAME' => 'Charisma',
			'DESCRIPTION' => 'The strength of personality of your character',
			'GROUPING' => 'Social',
			'ORDERING' => 4,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4 
		),
		5 => array (
			'ID' => 6,
			'NAME' => 'Manipulation',
			'DESCRIPTION' => 'How good your character is at manipulating others',
			'GROUPING' => 'Social',
			'ORDERING' => 5,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4 
		),
		6 => array (
			'ID' => 7,
			'NAME' => 'Intelligence',
			'DESCRIPTION' => 'How smart your character is',
			'GROUPING' => 'Mental',
			'ORDERING' => 8,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4 
		),
		7 => array (
			'ID' => 8,
			'NAME' => 'Wits',
			'DESCRIPTION' => 'How quick thinking your character is',
			'GROUPING' => 'Mental',
			'ORDERING' => 9,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4 
		),
		8 => array (
			'ID' => 9,
			'NAME' => 'Perception',
			'DESCRIPTION' => 'How much your character notices',
			'GROUPING' => 'Mental',
			'ORDERING' => 7,
			'COST_MODEL_ID' => 1,
			'SPECIALISATION_AT' => 4 
		),
		9 => array (
			'ID' => 10,
			'NAME' => 'Self Control',
			'DESCRIPTION' => 'How well you keep yourself in check',
			'GROUPING' => 'Virtue',
			'ORDERING' => 11,
			'COST_MODEL_ID' => 10,
			'SPECIALISATION_AT' => 0
		),
		10 => array (
			'ID' => 11,
			'NAME' => 'Courage',
			'DESCRIPTION' => 'How brave you are',
			'GROUPING' => 'Virtue',
			'ORDERING' => 12,
			'COST_MODEL_ID' => 10,
			'SPECIALISATION_AT' => 0 
		),
		11 => array (
			'ID' => 12,
			'NAME' => 'Conscience',
			'DESCRIPTION' => 'How caring you are',
			'GROUPING' => 'Virtue',
			'ORDERING' => 10,
			'COST_MODEL_ID' => 10,
			'SPECIALISATION_AT' => 0
		),
		12 => array (
			'ID' => 13,
			'NAME' => 'Conviction',
			'DESCRIPTION' => 'The strength of your convictions',
			'GROUPING' => 'Virtue',
			'ORDERING' => 10,
			'COST_MODEL_ID' => 10,
			'SPECIALISATION_AT' => 0
		),
		13 => array (
			'ID' => 14,
			'NAME' => 'Instinct',
			'DESCRIPTION' => 'How well you ride the beast',
			'GROUPING' => 'Virtue',
			'ORDERING' => 11,
			'COST_MODEL_ID' => 10,
			'SPECIALISATION_AT' => 0
		),
		14 => array (
			'ID' => 15,
			'NAME' => 'Willpower',
			'DESCRIPTION' => 'How strong your force of will is',
			'GROUPING' => 'Willpower',
			'ORDERING' => 13,
			'COST_MODEL_ID' => 11,
			'SPECIALISATION_AT' => 0
		)
	);
	$sql = "select ID from " . GVLARP_TABLE_PREFIX . "STAT;";
	$rows = count($wpdb->get_results($wpdb->prepare($sql)));
	if (!$rows)
		foreach ($data as $id => $entry)
				$rowsadded = $wpdb->insert( GVLARP_TABLE_PREFIX . "STAT", $entry);
				
	/* Clans */
	$data = array (
		0 => array (
			'ID' => 1,
			'NAME' => 'Nosferatu',
			'DESCRIPTION' => 'Nosferatu',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Nosferatu.gif',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Horribly ugly',
			'VISIBLE' => 'Y'), 
		1 => array (
			'ID' => 2,
			'NAME' => 'Tremere',
			'DESCRIPTION' => 'Tremere',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Tremere.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Easily bloodbonded',
			'VISIBLE' => 'Y'), 
		2 => array (
			'ID' => 3,
			'NAME' => 'Malkavian',
			'DESCRIPTION' => 'Malkavian',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Malkavian.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Insane',
			'VISIBLE' => 'Y'), 
		3 => array (
			'ID' => 4,
			'NAME' => 'Brujah',
			'DESCRIPTION' => 'Brujah',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Brujah.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Frenzies easily',
			'VISIBLE' => 'Y'), 
		4 => array (
			'ID' => 5,
			'NAME' => 'Ventrue',
			'DESCRIPTION' => 'Ventrue',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Ventrue.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Rarified tastes',
			'VISIBLE' => 'Y'), 
		5 => array (
			'ID' => 6,
			'NAME' => 'Toreador',
			'DESCRIPTION' => 'Toreador',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Toreador.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Entranced by beauty',
			'VISIBLE' => 'Y'), 
		6 => array (
			'ID' => 7,
			'NAME' => 'Gangrel',
			'DESCRIPTION' => 'Gangrel',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Gangrel.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Animal features',
			'VISIBLE' => 'Y'), 
		7 => array (
			'ID' => 8,
			'NAME' => 'Giovanni',
			'DESCRIPTION' => 'Giovanni',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Giovanni.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Kiss causes pain and damage',
			'VISIBLE' => 'N'),
		8 => array (
			'ID' => 9,
			'NAME' => 'Lasombra',
			'DESCRIPTION' => 'Lasombra',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Lasombra.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'No reflection',
			'VISIBLE' => 'N'),
		9 => array (
			'ID' => 10,
			'NAME' => 'Caitiff',
			'DESCRIPTION' => 'Caitiff',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Caitiff.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => 'Clanless',
			'VISIBLE' => 'Y'), 
		10 => array (
			'ID' => 11,
			'NAME' => 'Assamite',
			'DESCRIPTION' => 'Assamite',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Assamite.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => '',
			'VISIBLE' => 'N' ),
		11 => array (
			'ID' => 12,
			'NAME' => 'Followers of Set',
			'DESCRIPTION' => 'Settites',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Settite.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => '',
			'VISIBLE' => 'N'),
		12 => array (
			'ID' => 13,
			'NAME' => 'Ravnos',
			'DESCRIPTION' => 'Ravnos',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Ravnos.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => '',
			'VISIBLE' => 'N'),
		13 => array (
			'ID' => 14,
			'NAME' => 'Tzimisce',
			'DESCRIPTION' => 'Tzimisce',
			'ICON_LINK' => '/wp-content/plugins/gvlarp-character/images/Tzimisce.png',
			'CLAN_PAGE_LINK' => '',
			'CLAN_FLAW' => '',
			'VISIBLE' => 'N'
		)
	);
}

?>