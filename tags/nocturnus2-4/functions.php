<?php 
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( get_template_directory() . '/theme-options.php' );
?>
<?php 
if ( function_exists('register_nav_menu') )
	register_nav_menu( 'primary', 'Primary Menu' );
if ( ! isset( $content_width ) ) $content_width = 750;
	

/* Sidebar
------------------------------------------------ */
add_action( 'widgets_init', 'nocturnus_widgets_init' );
function nocturnus_widgets_init() {
    register_sidebar( array(
        'name' => __( 'Nocturnus Sidebar', 'nocturnus' ),
        'id' => 'sidebar-1',
        'description' => __( 'Widgets in this area will be shown on all posts and pages.', 'nocturnus' ),
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget'  => '</li>',
		'before_title'  => '<h2 class="widgettitle">',
		'after_title'   => '</h2>',
    ) );
}

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

/* Theme Support - Custom Backgrounds
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

/* Theme Support - Title
------------------------------------------------ */
add_theme_support('title-tag');

/* Internationalisation / Localisation
------------------------------------------------ */

function nocturnus_load_theme_textdomain() {
load_theme_textdomain( 'nocturnus', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'after_setup_theme', 'nocturnus_load_theme_textdomain' );

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

function nocturnus_stylesheet() {
	 wp_enqueue_style('custom_wp_admin_css', get_template_directory_uri() . '/css/style-login.css', false, null);
}
add_action( 'login_enqueue_scripts', 'nocturnus_stylesheet' );


?>