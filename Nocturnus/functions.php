<?php 
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( get_template_directory() . '/theme-options.php' );
?>
<?php 
if ( function_exists('register_sidebar') )
	register_sidebar(array('name' => 'nocturnus_sidebar'));
if ( function_exists('register_nav_menu') )
	register_nav_menu( 'primary', 'Primary Menu' );

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

/* Theme Support - Customer Backgrounds
------------------------------------------------ */
$themedefaults = array(
	'default-color'          => '#000000',
	'default-image'          => '',
	'wp-head-callback'       => '_custom_background_cb',
	'admin-head-callback'    => '',
	'admin-preview-callback' => ''
);
add_theme_support( 'custom-background', $themedefaults );

/* Theme Support - HTML5
------------------------------------------------ */
add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form' ) );

/* Editor Style
------------------------------------------------ */
function nocturnus_add_editor_styles() {
    add_editor_style( 'css/custom-editor-style.css' );
}
add_action( 'init', 'nocturnus_add_editor_styles' );	

/* Login Skinning
------------------------------------------------ */
function nocturnus_logo() { 
?>
    <style type="text/css">
        body.login div#login h1 a {
            background-image: url(<?php header_image(); ?>);
            padding-bottom: 20px;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'nocturnus_logo' );

add_theme_support( 'automatic-feed-links' );

function nocturnus_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'nocturnus_logo_url' );

function nocturnus_logo_url_title() {
    return wp_title('',false);
}
add_filter( 'login_headertitle', 'nocturnus_logo_url_title' );

function nocturnus_stylesheet() { ?>
    <link rel="stylesheet" id="custom_wp_admin_css"  href="<?php echo get_stylesheet_directory_uri() . '/css/style-login.css'; ?>" type="text/css" media="all" />
<?php }
add_action( 'login_enqueue_scripts', 'nocturnus_stylesheet' );

/*
function nocturnus_redirect( $redirect_to, $request, $user ){
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
add_filter("login_redirect", "nocturnus_redirect", 10, 3); */

/* USEFUL FUNCTIONS
------------------------------------------------ */

function get_user_role() {
	global $current_user;

	$user_roles = $current_user->roles;
	$user_role = array_shift($user_roles);

	return $user_role;
}

if ( ! isset( $content_width ) ) $content_width = 750;

?>