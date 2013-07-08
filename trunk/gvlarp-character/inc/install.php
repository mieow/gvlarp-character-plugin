<?php

register_activation_hook(__FILE__, "gvlarp_character_install");

global $gvlarp_character_db_version;
$gvlarp_character_db_version = "1.6.1"; /* 1.6.1 */

function gvlarp_update_db_check() {
    global $gvlarp_character_db_version;
	
    if (get_site_option( 'gvlarp_character_db_version' ) != $gvlarp_character_db_version) {
        gvlarp_character_install();
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
					NAME         VARCHAR(16) NOT NULL,
					DESCRIPTION  VARCHAR(60) NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "PLAYER_STATUS";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16) NOT NULL,
					DESCRIPTION  VARCHAR(60) NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "PLAYER";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID                 MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME               VARCHAR(60)  NOT NULL,
					PLAYER_TYPE_ID     MEDIUMINT(9) NOT NULL,
					PLAYER_STATUS_ID   MEDIUMINT(9) NOT NULL,
					PRIMARY KEY (ID),
					FOREIGN KEY (PLAYER_TYPE_ID)   REFERENCES " . $table_prefix . "PLAYER_TYPE(ID),
					FOREIGN KEY (PLAYER_STATUS_ID) REFERENCES " . $table_prefix . "PLAYER_STATUS(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);


		$current_table_name = $table_prefix . "ST_LINK";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL  AUTO_INCREMENT,
					VALUE        VARCHAR(32)  NOT NULL,
					DESCRIPTION  VARCHAR(60)  NOT NULL,
					LINK         VARCHAR(60)  NOT NULL,
					ORDERING     SMALLINT(3)  NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "OFFICE";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL  AUTO_INCREMENT,
					NAME         VARCHAR(32)  NOT NULL,
					DESCRIPTION  VARCHAR(120)  NOT NULL,
					ORDERING     SMALLINT(3)  NOT NULL,
					VISIBLE      VARCHAR(1)   NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "XP_REASON";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16) NOT NULL,
					DESCRIPTION  VARCHAR(60) NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "PATH_REASON";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(24) NOT NULL,
					DESCRIPTION  VARCHAR(60) NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "TEMPORARY_STAT_REASON";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16) NOT NULL,
					DESCRIPTION  VARCHAR(60) NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_TYPE";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)  NOT NULL,
					DESCRIPTION  VARCHAR(60)  NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_STATUS";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16) NOT NULL,
					DESCRIPTION  VARCHAR(60) NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "CLAN";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9)  NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16)   NOT NULL,
					DESCRIPTION  VARCHAR(60)   NOT NULL,
					ICON_LINK    VARCHAR(128)  NOT NULL,
					VISIBLE      VARCHAR(1)    NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "COURT";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16) NOT NULL,
					DESCRIPTION  VARCHAR(120) NOT NULL,
					VISIBLE      VARCHAR(1)  NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "SOURCE_BOOK";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9)  NOT NULL   AUTO_INCREMENT,
					CODE         VARCHAR(16)   NOT NULL,
					NAME         VARCHAR(60)   NOT NULL,
					VISIBLE      VARCHAR(1)    NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
		
		$current_table_name = $table_prefix . "COST_MODEL";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID           MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					NAME         VARCHAR(16) NOT NULL,
					DESCRIPTION  VARCHAR(60) NOT NULL,
					PRIMARY KEY (ID)
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
					PRIMARY KEY (ID),
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
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "STAT";
		
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              	MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME            	VARCHAR(16)   NOT NULL,
					DESCRIPTION     	VARCHAR(60)   NOT NULL,
					GROUPING        	VARCHAR(30)   NOT NULL,
					ORDERING        	SMALLINT(3)   NOT NULL,
					COST_MODEL_ID   	MEDIUMINT(9)  NOT NULL,
					SPECIALISATION_AT	SMALLINT(2)	  NOT NULL,
					PRIMARY KEY (ID),
					FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "ROAD_OR_PATH";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
					NAME            VARCHAR(32)   NOT NULL,
					DESCRIPTION     VARCHAR(120)  NOT NULL,
					STAT1_ID        MEDIUMINT(9)  NOT NULL,
					STAT2_ID        MEDIUMINT(9)  NOT NULL,
					SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
					PAGE_NUMBER     SMALLINT(4)   NOT NULL,
					VISIBLE         VARCHAR(1)    NOT NULL,
					PRIMARY KEY (ID),
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
					PRIMARY KEY (ID),
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
					PRIMARY KEY (ID),
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
					PRIMARY KEY (ID),
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
					PRIMARY KEY (ID),
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
					PRIMARY KEY (ID),
					FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
					FOREIGN KEY (PATH_REASON_ID) REFERENCES " . $table_prefix . "PATH_REASON(ID)
					) ENGINE=INNODB;";
		dbDelta($sql);

		$current_table_name = $table_prefix . "TEMPORARY_STAT";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(60)   NOT NULL,
						DESCRIPTION     VARCHAR(120)  NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID)
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
						PRIMARY KEY (ID),
						FOREIGN KEY (CHARACTER_ID) REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (TEMPORARY_STAT_ID) REFERENCES " . $table_prefix . "TEMPORARY_STAT(ID),
						FOREIGN KEY (TEMPORARY_STAT_REASON_ID) REFERENCES " . $table_prefix . "TEMPORARY_STAT_REASON(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "SKILL";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              	MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            	VARCHAR(16)   NOT NULL,
						DESCRIPTION     	VARCHAR(60)   NOT NULL,
						GROUPING        	VARCHAR(30)   NOT NULL,
						COST_MODEL_ID   	MEDIUMINT(9)  NOT NULL,
						MULTIPLE			VARCHAR(1)	  NOT NULL,
						SPECIALISATION_AT	SMALLINT(2)	  NOT NULL,
						VISIBLE         	VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "BACKGROUND";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(16)   NOT NULL,
						DESCRIPTION     VARCHAR(60)   NOT NULL,
						GROUPING        VARCHAR(30)   NOT NULL,
						COST_MODEL_ID   MEDIUMINT(9)  NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "MERIT";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(32)   NOT NULL,
						DESCRIPTION     VARCHAR(240)  NOT NULL,
						VALUE           SMALLINT(3)   NOT NULL,
						GROUPING        VARCHAR(30)   NOT NULL,
						COST            SMALLINT(3)   NOT NULL,
						XP_COST         SMALLINT(3)   NOT NULL,
						MULTIPLE        VARCHAR(1)    NOT NULL,
						SOURCE_BOOK_ID  MEDIUMINT(9)  NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "DISCIPLINE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL   AUTO_INCREMENT,
						NAME            VARCHAR(32)   NOT NULL,
						DESCRIPTION     VARCHAR(60)   NOT NULL,
						COST_MODEL_ID   MEDIUMINT(9)  NOT NULL,
						SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID),
						FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "PATH";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(32)   NOT NULL,
						DESCRIPTION     VARCHAR(240)  NOT NULL,
						DISCIPLINE_ID   MEDIUMINT(9)  NOT NULL,
						COST_MODEL_ID   MEDIUMINT(9)  NOT NULL,
						SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (DISCIPLINE_ID) REFERENCES " . $table_prefix . "DISCIPLINE(ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID),
						FOREIGN KEY (COST_MODEL_ID) REFERENCES " . $table_prefix . "COST_MODEL(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "DISCIPLINE_POWER";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID mediumint(9) NOT NULL AUTO_INCREMENT,
						NAME varchar(32) NOT NULL,
						DESCRIPTION varchar(60) NOT NULL,
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
						DESCRIPTION     VARCHAR(60)   NOT NULL,
						LEVEL           SMALLINT(2)   NOT NULL,
						PATH_ID         MEDIUMINT(9)  NOT NULL,
						DICE_POOL       VARCHAR(60)   NOT NULL,
						DIFFICULTY      VARCHAR(60)   NOT NULL,
						COST            SMALLINT(3)   NOT NULL,
						SOURCE_BOOK_ID  MEDIUINT(9)   NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (PATH_ID) REFERENCES " . $table_prefix . "PATH(ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";

			
			dbDelta($sql);

		$current_table_name = $table_prefix . "RITUAL";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						NAME            VARCHAR(60)   NOT NULL,
						DESCRIPTION     VARCHAR(120)  NOT NULL,
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
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
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
						COMMENT           VARCHAR(60),
						PRIMARY KEY (ID),
						FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID),
						FOREIGN KEY (BACKGROUND_ID) REFERENCES " . $table_prefix . "BACKGROUND(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);
			
		$current_table_name = $table_prefix . "EXTENDED_CHARACTER_BACKGROUND";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                 MEDIUMINT(9)  NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID       MEDIUMINT(9)  NOT NULL,
						TITLE              VARCHAR(64)   NOT NULL,
						CODE               VARCHAR(32)   NOT NULL,
						ORDERING		   SMALLINT(4)	 NOT NULL,
						CURRENT_TEXT       TEXT          NOT NULL,
						PROPOSED_TEXT      TEXT,
						CURRENT_ACCEPTED   VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "COMBO_DISCIPLINE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID              MEDIUMINT(9)  NOT NULL   AUTO_INCREMENT,
						NAME            VARCHAR(60)   NOT NULL,
						DESCRIPTION     VARCHAR(120)  NOT NULL,
						COST            SMALLINT(3)   NOT NULL,
						SOURCE_BOOK_ID  MEDIUMINT(9)  NOT NULL,
						PAGE_NUMBER     SMALLINT(4)   NOT NULL,
						VISIBLE         VARCHAR(1)    NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (SOURCE_BOOK_ID) REFERENCES " . $table_prefix . "SOURCE_BOOK(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_COMBO_DISCIPLINE";
		
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                   MEDIUMINT(9)  NOT NULL AUTO_INCREMENT,
						CHARACTER_ID         MEDIUMINT(9)  NOT NULL,
						COMBO_DISCIPLINE_ID  MEDIUMINT(9)  NOT NULL,
						COMMENT              VARCHAR(60),
						PRIMARY KEY (ID),
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
						PRIMARY KEY (ID),
						FOREIGN KEY (COMBO_DISCIPLINE_ID) REFERENCES " . $table_prefix . "COMBO_DISCIPLINE(ID),
						FOREIGN KEY (DISCIPLINE_ID)       REFERENCES " . $table_prefix . "DISCIPLINE(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CHARACTER_PROFILE";
			$sql = "CREATE TABLE " . $current_table_name . " (
						ID                MEDIUMINT(9)   NOT NULL  AUTO_INCREMENT,
						CHARACTER_ID      MEDIUMINT(9)   NOT NULL,
						QUOTE             VARCHAR(1024)  NOT NULL,
						PORTRAIT          VARCHAR(256)   NOT NULL,
						PRIMARY KEY (ID),
						FOREIGN KEY (CHARACTER_ID)  REFERENCES " . $table_prefix . "CHARACTER(ID)
						) ENGINE=INNODB;";
			dbDelta($sql);

		$current_table_name = $table_prefix . "CONFIG";
		$sql = "CREATE TABLE " . $current_table_name . " (
					ID                         MEDIUMINT(9)   NOT NULL  AUTO_INCREMENT,
					PROFILE_LINK               VARCHAR(128)   NOT NULL,
					PLACEHOLDER_IMAGE          VARCHAR(256)   NOT NULL,
					CLAN_DISCIPLINE_DISCOUNT   VARCHAR(10)    NOT NULL,
					PRIMARY KEY (ID)
					) ENGINE=INNODB;";
		dbDelta($sql);
	
		update_option("gvlarp_character_db_version", $gvlarp_character_db_version);
	}
	
}

?>