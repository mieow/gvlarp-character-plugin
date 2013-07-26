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

?>