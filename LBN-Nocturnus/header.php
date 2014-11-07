<!DOCTYPE html>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title><?php
	wp_title( '|', true, 'right' );
	bloginfo( 'name' );
?></title>
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="shortcut icon" href="<?php echo get_stylesheet_directory_uri(); ?>/favicon.ico" /><?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' );
<body <?php body_class("{$theme}theme {$gradient}theme {$corners}theme sidebar{$sidebar}"); ?>>
	</header>