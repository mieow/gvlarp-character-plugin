<?php
get_header(); ?>
		<div id="primary">
			<div id="content" role="main">
			<?php
			if (have_posts()) :
				while (have_posts()) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				endwhile;
			endif;
			?>
	
			</div> <!-- content -->
		</div> <!-- primary-->
<?php get_sidebar();
get_footer();

?>