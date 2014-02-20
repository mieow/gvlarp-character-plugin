<!DOCTYPE html>
<<<<<<< .mine<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title><?php
	wp_title( '|', true, 'right' );
	bloginfo( 'name' );
?></title>
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<link rel="profile" href="http://gmpg.org/xfn/11" />
<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' );wp_head();?></head>
<body <?php body_class(); ?>><div id="page" class="hfeed">	<header id="pageheader">		<?php if ( 'blank' != get_header_textcolor() ) : ?>		<hgroup id="site-info">			<h1 id="site-title" style="color:#<?php echo get_header_textcolor(); ?>"><span><a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></span></h1>			<h2 id="site-description" style="color:#<?php echo get_header_textcolor(); ?>"><?php bloginfo( 'description' ); ?></h2>		</hgroup>		<?php endif; ?>		<img id="site-image" src="<?php header_image(); ?>" height="<?php echo get_custom_header()->height; ?>" width="<?php echo get_custom_header()->width; ?>" alt="" />		<nav id="mainnavbar">			<?php wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>		</nav>
	</header>	<div id="main">