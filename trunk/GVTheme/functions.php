<?php 
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( get_template_directory() . '/theme-options.php' );
?>
<?php 
if ( function_exists('register_sidebar') )
	register_sidebar(array('name' => 'gvsidebar'));
if ( function_exists('register_nav_menu') )
	register_nav_menu( 'primary', __( 'Primary Menu', 'gvtheme' ) );

/* Custom Header
------------------------------------------------ */
$defaults = array(
	'default-image'          => get_template_directory_uri() . '/images/banner.jpg',
	'random-default'         => false,
	'width'                  => 1000,
	'height'                 => 150,
	'flex-height'            => false,
	'flex-width'             => false,
	'default-text-color'     => '',
	'header-text'            => true,
	'uploads'                => true,
	'wp-head-callback'       => '',
	'admin-head-callback'    => '',
	'admin-preview-callback' => '',
);
add_theme_support( 'custom-header', $defaults );

/* Theme Support - Thumbnails
------------------------------------------------ */
if ( function_exists( 'add_theme_support' ) ) {
	add_theme_support( 'post-thumbnails' );
	set_post_thumbnail_size( 150, 150 );
}
	
/* Login Skinning
------------------------------------------------ */
function my_login_logo() { 
?>
    <style type="text/css">
        body.login div#login h1 a {
            background-image: url(<?php header_image(); ?>);
            padding-bottom: 30px;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'my_login_logo' );

add_theme_support( 'automatic-feed-links' );

function my_login_logo_url() {
    return get_bloginfo( 'url' );
}
add_filter( 'login_headerurl', 'my_login_logo_url' );

function my_login_logo_url_title() {
    return 'Glasgow Vampire Live Action Role play';
}
add_filter( 'login_headertitle', 'my_login_logo_url_title' );

function my_login_stylesheet() { ?>
    <link rel="stylesheet" id="custom_wp_admin_css"  href="<?php echo get_bloginfo( 'stylesheet_directory' ) . '/css/style-login.css'; ?>" type="text/css" media="all" />
<?php }
add_action( 'login_enqueue_scripts', 'my_login_stylesheet' );

function my_login_redirect( $redirect_to, $request, $user ){
    //is there a user to check?
    if( is_array( $user->roles ) ) {
        //check for admins
        if( in_array( "administrator", $user->roles ) ) {
            // redirect them to the default place
            // EDIT THIS TO BE THE PROFILE IN THE FUTURE
            return $redirect_to;
        } else {
            return home_url();
        }
    }}
add_filter("login_redirect", "my_login_redirect", 10, 3);

/* USEFUL FUNCTIONS
------------------------------------------------ */

function get_user_role() {
	global $current_user;

	$user_roles = $current_user->roles;
	$user_role = array_shift($user_roles);

	return $user_role;
}

/* THEME WIDGET
	Welcome, <login>
	----------------------
	- Login
	- Character Sheet Link
	- Profile Link
	- Inbox
	- Logout
------------------------------------------------ */
class GVTheme_Widget extends WP_Widget {
	/**	 * Register widget with WordPress.	 */
	public function __construct() {
		parent::__construct(
	 		'gvtheme_widget', // Base ID
			'GVTheme Widget', // Name
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
						$title = apply_filters( 'widget_title', 'Welcome, ' . $current_user->user_login );
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
			<?php if ( isset( $instance[ 'charheet_link' ] ) ) { ?>
			<li><a href="<?php echo $instance['charheet_link']; ?>">Character Sheet</a></li>
			<?php } ?>
			<?php if ( isset( $instance[ 'profile_link' ] ) ) { ?>
			<li><a href="<?php echo $instance['profile_link']; ?>">Character Profile</a></li>
			<?php } ?>
			<?php if ( isset( $instance[ 'spendxp_link' ] ) ) { ?>
			<li><a href="<?php echo $instance['spendxp_link']; ?>">Spend Experience</a></li>
			<?php } ?>
			
			<?php 
				$user_role = get_user_role();
				$options = get_option('gv_options');
				if ( isset( $options['rolelink_' . $user_role] ) ) { 
					$linktext = $options['rolelink_' . $user_role];
					$linkurl = $options['roleurl_' . $user_role]; 
			?>
			<li><a href="<?php echo $linkurl; ?>"><?php echo $linktext; ?></a></li>
			<?php } ?>
			
		 	<?php
			     if ( isset( $instance[ 'inbox_link' ] ) ) {
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
add_action( 'widgets_init', create_function( '', 'register_widget( "gvtheme_widget" );' ) );

?>