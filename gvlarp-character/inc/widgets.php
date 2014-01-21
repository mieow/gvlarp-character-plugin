<?php
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


/* LOGIN WIDGET
	Welcome, <login>
	----------------------
	- Login
	- Character Sheet Link
	- Profile Link
	- Inbox
	- Logout
------------------------------------------------ */
class GVPlugin_Widget extends WP_Widget {
	/**	 * Register widget with WordPress.	 */
	public function __construct() {
		parent::__construct(
	 		'gvplugin_widget', // Base ID
			'Character Login Widget', // Name
			array( 'description' => __( 'For login/logout and useful links', 'text_domain' ), ) // Args
		);
	}
	/**	 * Front-end display of widget.	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		global $wpdb;
		extract( $args );
		
		echo $before_widget;
		
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
				$title = apply_filters( 'widget_title', 'Welcome, ' . $current_user->display_name );
		echo $before_title . $title . $after_title;
			?>
			<?php /* Latest DT article */ ?>
			<?php
				if (is_plugin_active('download-monitor/wp-download_monitor.php') && isset( $instance[ 'dl_category' ] )) {
					$dl = get_downloads('orderby=date&category=' . $instance[ 'dl_category' ] . '&order=DESC&limit=1');
					if (!empty($dl)) {
						$dPm=str_split('JanFebMarAprMayJunJulAugSepOctNovDec',3);
						echo '<p>';
						foreach($dl as $d) {
							$dP=date_parse($d->date);
							echo sprintf('Download <a href="%s" title="%s">%s %d Dark Times</a><br/>',
 									$d->url,$d->title, $dPm[--$dP['month']], $dP['year']);
						}
						unset($dPm);
						echo '</p>';
					}
				}
			?>
			<ul>
			<?php if ( isset( $instance[ 'charheet_link' ] ) && !empty($instance[ 'charheet_link' ]) ) { ?>
			<li><a href="<?php echo $instance['charheet_link']; ?>">Character Sheet</a></li>
			<?php } ?>
			<?php if ( isset( $instance[ 'profile_link' ] ) && !empty($instance[ 'profile_link' ]) ) { ?>
			<li><a href="<?php echo $instance['profile_link']; ?>">Character Profile</a></li>
			<?php } ?>
			<?php if ( isset( $instance[ 'spendxp_link' ] ) && !empty($instance[ 'spendxp_link' ]) ) { ?>
			<li><a href="<?php echo $instance['spendxp_link']; ?>">Spend Experience</a></li>
			<?php } 
			
				$clanlink  = get_clan_link();
				if ( !empty($clanlink) ) { 
			?>
					<li><a href="<?php echo $clanlink; ?>">Clan Page</a></li> 
			<?php } ?>
			
		 	<?php
			     if ( isset( $instance[ 'inbox_link' ] ) && !empty($instance[ 'inbox_link' ]) ) {
   				if (is_plugin_active('private-messages-for-wordpress/pm4wp.php')) { ?>
					<li><a href="<?php echo $instance['inbox_link']; ?>">Inbox<?php
								$num_unread = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'pm WHERE `recipient` = "' . $current_user->user_login . '" AND `read` = 0 AND `deleted` != "2"' );
								if ( ! empty( $num_unread ) ) {
						echo ' (' . $num_unread . ' unread)';
					}
			?></a></li><?php
			    }
   				}
			?>
 			<li><a href="<?php echo wp_logout_url( home_url() ); ?>" title="Logout">Logout</a></li>
			</ul>
			<?php
		} else {
			$title = apply_filters( 'widget_title', 'Welcome' );
				echo $before_title . $title . $after_title;
			wp_login_form( $args );
		}
				echo $after_widget;	}
	/**	 * Sanitize widget form values as they are saved.
	 *	 * @see WP_Widget::update()
	 *	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['charheet_link'] = strip_tags( $new_instance['charheet_link'] );
		$instance['profile_link'] = strip_tags( $new_instance['profile_link'] );
		$instance['inbox_link'] = strip_tags( $new_instance['inbox_link'] );
		$instance['spendxp_link'] = strip_tags( $new_instance['spendxp_link'] );
		$instance['dl_category'] = strip_tags( $new_instance['dl_category'] );
		return $instance;	}
	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'charheet_link' ] ) ) {
			$charheet_link = $instance[ 'charheet_link' ];
		}
		else {
			$charheet_link = '';
		}
		if ( isset( $instance[ 'profile_link' ] ) ) {
			$profile_link = $instance[ 'profile_link' ];
		}
		else {
			$profile_link = '';
		}
		if ( isset( $instance[ 'inbox_link' ] ) ) {
			$inbox_link = $instance[ 'inbox_link' ];
		}		else {
			$inbox_link = '';
		}
		if ( isset( $instance[ 'spendxp_link' ] ) ) {
			$spendxp_link = $instance[ 'spendxp_link' ];
		}
		else {
			$spendxp_link = '';
		}
		if ( isset( $instance[ 'dl_category' ] ) ) {
			$dl_category = $instance[ 'dl_category' ];
		}
		else {
			$dl_category = '';
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'charheet_link' ); ?>"><?php _e( 'Character Sheet Link:' ); ?></label>
 		<input class="widefat" id="<?php echo $this->get_field_id( 'charheet_link' ); ?>" name="<?php echo $this->get_field_name( 'charheet_link' ); ?>" type="text" value="<?php echo esc_attr( $charheet_link ); ?>" />
		</p><p>
		<label for="<?php echo $this->get_field_id( 'profile_link' ); ?>"><?php _e( 'Profile Link:' ); ?></label>
 		<input class="widefat" id="<?php echo $this->get_field_id( 'profile_link' ); ?>" name="<?php echo $this->get_field_name( 'profile_link' ); ?>" type="text" value="<?php echo esc_attr( $profile_link ); ?>" />
		</p><p>
		<label for="<?php echo $this->get_field_id( 'spendxp_link' ); ?>"><?php _e( 'Spend XP Link:' ); ?></label>
 		<input class="widefat" id="<?php echo $this->get_field_id( 'spendxp_link' ); ?>" name="<?php echo $this->get_field_name( 'spendxp_link' ); ?>" type="text" value="<?php echo esc_attr( $spendxp_link ); ?>" />
		</p><p>
		<label for="<?php echo $this->get_field_id( 'inbox_link' ); ?>"><?php _e( 'Inbox Link:' ); ?></label>
 		<input class="widefat" id="<?php echo $this->get_field_id( 'inbox_link' ); ?>" name="<?php echo $this->get_field_name( 'inbox_link' ); ?>" type="text" value="<?php echo esc_attr( $inbox_link ); ?>" />
		</p>
		<?php
 		if (is_plugin_active('download-monitor/wp-download_monitor.php')) { ?>
			<p>
			<label for="<?php echo $this->get_field_id( 'dl_category' ); ?>"><?php _e( 'Download Category:' ); ?></label>
 			<input class="widefat" id="<?php echo $this->get_field_id( 'dl_category' ); ?>" name="<?php echo $this->get_field_name( 'dl_category' ); ?>" type="text" value="<?php echo esc_attr( $dl_category ); ?>" />
		</p>
		<?php
		}
	}}
 // class Foo_Widget
// register Foo_Widget widget
add_action( 'widgets_init', create_function( '', 'register_widget( "gvplugin_widget" );' ) );

class GVPlugin_Background_Widget extends WP_Widget {
	/**	 * Register widget with WordPress.	 */
	public function __construct() {
		parent::__construct(
	 		'gvplugin_background_widget', // Base ID
			'Character Background Widget', // Name
			array( 'description' => __( 'Percentage background complete', 'text_domain' ), ) // Args
		);
	}
	/**	 * Front-end display of widget.	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		global $wpdb;
		extract( $args );
		
		echo $before_widget;
		
		if ( is_user_logged_in() ) {
			$character = establishCharacter("");
			$characterID = establishCharacterID($character);
			
						
			$title = apply_filters( 'widget_title', 'Backgrounds' );
			echo $before_title . $title . $after_title;
			
			if (empty($characterID)) {
				echo '<p>No character selected</p>';
			} else {
			
				$donebgs = 0;
				$totalbgs = 0;
				
				// backgrounds
				$sql = "select count(backgrounds.BACKGROUND_QUESTION) as total2do, count(charbgs.APPROVED_DETAIL) as totaldone
						from	" . GVLARP_TABLE_PREFIX . "BACKGROUND backgrounds,
								" . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgs,
								" . GVLARP_TABLE_PREFIX . "CHARACTER characters
						where	
							backgrounds.ID = charbgs.BACKGROUND_ID
							and	characters.ID = %d
							and characters.ID = charbgs.CHARACTER_ID
							and	(backgrounds.BACKGROUND_QUESTION != '' OR charbgs.SECTOR_ID > 0);";
				$sql = $wpdb->prepare($sql, $characterID);
				$result1 = $wpdb->get_row($sql);
				$donebgs += $result1->totaldone;
				$totalbgs += $result1->total2do;
				
				// Merits and Flaws
				$sql = "select count(charmerits.APPROVED_DETAIL) as totaldone, count(merits.BACKGROUND_QUESTION) as total2do
						from	" . GVLARP_TABLE_PREFIX . "MERIT merits,
								" . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT charmerits,
								" . GVLARP_TABLE_PREFIX . "CHARACTER characters
						where	merits.ID = charmerits.MERIT_ID
							and	characters.ID = %d
							and characters.ID = charmerits.CHARACTER_ID
							and	merits.BACKGROUND_QUESTION != '';";
				$sql = $wpdb->prepare($sql, $characterID);
				$result2 = $wpdb->get_row($sql);
				$donebgs += $result2->totaldone;
				$totalbgs += $result2->total2do;
				
				// Misc questions
				$sql = "SELECT COUNT(ID) as total2do FROM " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND WHERE VISIBLE = 'Y'";
				$totalbgs += $wpdb->get_var($sql);
				
				$sql = "SELECT COUNT(questions.ID) AS totaldone
						FROM
							" . GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND as charquest,
							" . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND as questions
						WHERE
							charquest.CHARACTER_ID = %s
							AND charquest.QUESTION_ID = questions.ID
							AND questions.VISIBLE = 'Y'
							AND charquest.APPROVED_DETAIL != ''";
				$sql = $wpdb->prepare($sql, $characterID);
				$donebgs += $wpdb->get_var($sql);
				//echo "<p>SQL: $sql</p>"; 
				
				if ($totalbgs <= 0) {
					echo "<p>There are no <a href='" . get_stlink_url('viewExtBackgrnd') . "?CHARACTER=" . urlencode($character) . "'>character background</a> questions to complete</p>";
				} 
				elseif ($donebgs == $totalbgs) {
					echo "<p>The <a href='" . get_stlink_url('viewExtBackgrnd') . "?CHARACTER=" . urlencode($character) . "'>character background</a> for $character has been completed</p>";
				}
				else {
					echo "<p>The <a href='" . get_stlink_url('viewExtBackgrnd') . "?CHARACTER=" . urlencode($character) . "'>character background</a>  for $character is ";
					echo sprintf ("%.0f%%", $donebgs * 100 / $totalbgs);
					echo " complete</p>";
				}
			}
		} 
		
		echo $after_widget;
	}
	/**	 * Sanitize widget form values as they are saved.
	 *	 * @see WP_Widget::update()
	 *	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		//$instance['charheet_link'] = strip_tags( $new_instance['charheet_link'] );
		//$instance['profile_link'] = strip_tags( $new_instance['profile_link'] );
		//$instance['inbox_link'] = strip_tags( $new_instance['inbox_link'] );
		//$instance['spendxp_link'] = strip_tags( $new_instance['spendxp_link'] );
		//$instance['dl_category'] = strip_tags( $new_instance['dl_category'] );
		return $instance;
	}
	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
	
	}
}
 // class Foo_Widget
// register Foo_Widget widget
add_action( 'widgets_init', create_function( '', 'register_widget( "gvplugin_background_widget" );' ) );

function get_clan_link() {
	global $wpdb;
	
	$character = establishCharacter('');
	$characterID = establishCharacterID($character);

	$sql = "SELECT clans.CLAN_PAGE_LINK 
			FROM " . GVLARP_TABLE_PREFIX . "CLAN clans,
				" . GVLARP_TABLE_PREFIX . "CHARACTER characters
			WHERE clans.ID = characters.PRIVATE_CLAN_ID
				AND characters.ID = %d;";
	$result = $wpdb->get_results($wpdb->prepare($sql, $characterID));
	
	return $result[0]->CLAN_PAGE_LINK;
	
}


    /*
		Previous Plugin Name: Stu's Solar Calc
		Previous Plugin URI: http://stu-in-flag.net/blog/
		Description: A simple plug-in widget to allow the display of sunrise/set data.
		Version: 0.2
		Author: Stu-in-Flag
		Author URI: http://stu-in-flag.net
		Author email: stu-in-flag@stu-in-flag.net
	*/

	/*  Copyright 2010  Stuart Broyles  (email : stu-in-flag@stu-in-flag.net)

	    This program is free software; you can redistribute it and/or modify
	    it under the terms of the GNU General Public License as published by
	    the Free Software Foundation; either version 2 of the License, or
	    (at your option) any later version.
	
	    This program is distributed in the hope that it will be useful,
	    but WITHOUT ANY WARRANTY; without even the implied warranty of
	    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	    GNU General Public License for more details.
	
	    You should have received a copy of the GNU General Public License
	    along with this program; if not, write to the Free Software
	    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/
	
	/*	DISCLOSURE: I am not a professional programmer. While I would like to hear 
	  	feedback on how the plugin is functioning for others, I am making no 
	  	commitment to solving integration issues, providing updates, or any other 
	  	sort of support that you would expect of a professional. I just don't have 
	  	the knowledge to do that.
	 	
	 	I am using this widget on my own blog and intend to for the long-term. So, I
	 	think it is reasonably safe. There is very little error trapping for 
	 	entering the wrong values in the admin window on the widgets page. You are 
	 	the error trap. If you enter goofy things, you will get goofy, possibly 
	 	dangerous results. Still, you can just delete it manually and move along.
	 	
	 	With all those disclaimers, I hope this tool works for you. Please, let me
	 	if you have thoughts, questions or suggestions at stu-in-flag@stu-in-flag.net. 
	 	I'll see what I can do.
	 */
	 
	/* 
		Some edits have been made for the Character Plugin
	*/
	
	//	Add function to widgets_init that'll load our widget.
	add_action( 'widgets_init', 'SSC_load_widget' );
	//  Register widget
	function SSC_load_widget() {
		register_widget('StuSolarCalc_Widget');	
	}
	class StuSolarCalc_Widget extends WP_Widget {
		function StuSolarCalc_Widget() {
			/* Widget settings. */
			$widget_ops = array( 'classname' => 'solar', 
				'description' => 'A simple plug-in widget to allow the display of sunrise/set data.');
			/* Widget control settings. */
			$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'solar' );
			/* Create the widget. */
			$this->WP_Widget( 'solar', 'Sunset/Sunrise Times', $widget_ops, $control_ops );
		}
		
		function form($instance) {
			// outputs the options form on admin
			/* 	Variable list - Initialized to Glasgow, Scotland
					lat = Your latitude
					long = Your longitude
					location = Your location name
					offset = Your time offset from GMT for non-Daylight Savings Time
					dst = True for Daylight Savings Time (on/off values for checkbox)
					zenith = 90.83 or 90+50/60; Removed as variable 
						adjust zenith in the $defaults below, but only if you know what you are doing!!!
			*/
			$defaults = array( 'lat' => '55.869725', 'long' => '-4.256573', 'location' => 'Glasgow, Scotland', 'offset' => '0', 'dst' => 'off');
			$instance = wp_parse_args( (array) $instance, $defaults );
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'lat' ); ?>">Latitude (XX.XXXXX degrees):</label>
				<input id="<?php echo $this->get_field_id( 'lat' ); ?>" name="<?php echo $this->get_field_name( 'lat' ); ?>" value="<?php echo $instance['lat']; ?>" style="width:100%;" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'long' ); ?>">Longitude (+/-XXX.XXXXX):</label>
				<input id="<?php echo $this->get_field_id( 'long' ); ?>" name="<?php echo $this->get_field_name( 'long' ); ?>" value="<?php echo $instance['long']; ?>" style="width:100%;" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'offset' ); ?>">Offset from GMT (hours):</label>
				<input id="<?php echo $this->get_field_id( 'offset' ); ?>" name="<?php echo $this->get_field_name( 'offset' ); ?>" value="<?php echo $instance['offset']; ?>" style="width:100%;" />
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id( 'location' ); ?>">Location name:</label>
				<input id="<?php echo $this->get_field_id( 'location' ); ?>" name="<?php echo $this->get_field_name( 'location' ); ?>" value="<?php echo $instance['location']; ?>" style="width:100%;" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'dst' ); ?>">Daylight Savings Time in Effect?</label>
				<input class="checkbox" type="checkbox" <?php checked( $instance['dst'], 'on' ); ?> id="<?php echo $this->get_field_id( 'dst' ); ?>" name="<?php echo $this->get_field_name( 'dst' ); ?>" />
			</p>
												
			<?php
			
			if ($instance['dst']=='on') {
				$timeGMT = gmdate("H:i", time() + 3600*($instance['offset']+1));  //  with Daylight Savings Time
			}
			else{
				$timeGMT = gmdate("H:i", time() + 3600*$instance['offset']);  //  without Daylight Savings Time
			}
			echo 'Current Time: ' . $timeGMT;
			echo $instance['dst'];		
		}
	
		function update($new_instance, $old_instance) {
			// processes widget options to be saved
			
			$instance = $old_instance;
			$instance['lat'] =  $new_instance['lat'];
			$instance['long'] =  $new_instance['long'];
			$instance['offset'] =  $new_instance['offset'];
			$instance['dst'] =  $new_instance['dst'];
			$instance['location'] = $new_instance['location'];
			//$instance['zenith'] = $new_instance['zenith']; Removed as variable. Change only if you understand zenith.
			return $instance;
	
		}
		function widget($args, $instance) {
			// Actual widget - displays a image file from a URL
			extract($args);
			echo $before_widget;
/*			echo $before_title.'Daily Sunrise/Sunset'.$after_title;	
*/
			
			if ($instance['dst']=='on') {
				$timeGMT =  gmdate("H:i", time() + 3600*($instance['offset']+1));  //  with Daylight Savings Time
			}
			else {
				$timeGMT =  gmdate("H:i", time() + 3600*$instance['offset']);  //  without Daylight Savings Time
			}
				
			$sunrisetime = date_sunrise(time(), SUNFUNCS_RET_STRING, $instance['lat'], $instance['long'], 90.83, $instance['offset']);
			$sunsettime = date_sunset(time(), SUNFUNCS_RET_STRING, $instance['lat'], $instance['long'], 90.83, $instance['offset']);
			$civilstart = date_sunrise(time(), SUNFUNCS_RET_STRING, $instance['lat'], $instance['long'], 96, $instance['offset']);  //  96 replaces $zenith
			$civilend = date_sunset(time(), SUNFUNCS_RET_STRING, $instance['lat'], $instance['long'], 96, $instance['offset']);  //  96 replaces $zenith
			$nautstart = date_sunrise(time(), SUNFUNCS_RET_STRING, $instance['lat'], $instance['long'], 102, $instance['offset']);  //  102 replaces $zenith
			$nautend = date_sunset(time(), SUNFUNCS_RET_STRING, $instance['lat'], $instance['long'], 102, $instance['offset']);  //  102 replaces $zenith
			$astrostart = date_sunrise(time(), SUNFUNCS_RET_STRING, $instance['lat'], $instance['long'], 108, $instance['offset']);  //  108 replaces $zenith
			$astroend = date_sunset(time(), SUNFUNCS_RET_STRING, $instance['lat'], $instance['long'], 108, $instance['offset']);  //  108 replaces $zenith

	        if ($timeGMT > $astroend) { 
	        $setday = 'Night Time';     
	        }     
	        else if ($timeGMT >= $nautend) { 
	        $setday = 'Astronomical Twilight';     
	        }     
	        else if ($timeGMT >= $civilend) {
	        $setday = 'Nautical Twilight';   
	        }   
	        else if ($timeGMT > $sunsettime) {
	        $setday = 'Civil Twilight';   
	        }   
	        else if ($timeGMT == $sunsettime) {
	        $setday = 'SUNSET';   
	        } 
	        else if ($timeGMT > $sunrisetime) {
	        $setday = 'Daylight';   
	        } 
	        else if ($timeGMT == $sunrisetime) {
	        $setday = 'SUNRISE';   
	        } 
	        else if ($timeGMT >= $civilstart) {
	        $setday = 'Civil Twilight';   
	        }   
	        else if ($timeGMT >= $nautstart) {
	        $setday = 'Nautical Twilight';   
	        } 
	        else if ($timeGMT >= $astrostart) {
	        $setday = 'Astronomical Twilight';   
	        } 
	        else  {
	        $setday = 'Night Time';
	        }
			
/*		echo '<center>'."\n";
		echo '<b>Current Time ' . $timeGMT . '</b><br />' . "\n";
		echo 'Current Event ' . $setday . '<br />' .  "\n"; 
		echo 'Astronomical Twilight starts ' . $astrostart . '<br />' . "\n";
		echo 'Nautical Twilight starts ' . $nautstart . '<br />' . "\n";
		echo 'Civil Twilight starts ' . $civilstart . '<br />' . "\n";
		echo '<b>SUNRISE ' . $sunrisetime . "\n";
		echo 'SUNSET ' . $sunsettime . '</b><br />' . "\n";
		echo 'Civil Twilight ends ' . $civilend  . '<br />' . "\n";
		echo 'Nautical Twilight ends ' . $nautend . '<br />' . "\n";
		echo 'Astronomical Twilight ends ' . $astroend . '<br />' . "\n";			
*/

		echo '<span class="solar">';
		echo 'Current Time - ' . $timeGMT . '<br />' . "\n";
		echo 'Sunrise - ' . $sunrisetime . '<br />' . "\n";
		echo 'Sunset - ' . $sunsettime . '' . "\n";
		echo '</span>';
		
		echo $after_widget;
		}
	}
?>