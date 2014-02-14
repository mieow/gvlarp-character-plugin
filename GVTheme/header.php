<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />

<title><?php
	wp_title( '|', true, 'right' );
	bloginfo( 'name' );
?></title>
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<link rel="profile" href="http://gmpg.org/xfn/11" />
<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' );wp_head();?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="hfeed">
	<header id="pageheader">
		<img src="<?php header_image(); ?>" height="<?php echo get_custom_header()->height; ?>" width="<?php echo get_custom_header()->width; ?>" alt="" />

		<nav id="mainnavbar">
			<?php wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>
		</nav>
	</header>
	
	<div id="main">
