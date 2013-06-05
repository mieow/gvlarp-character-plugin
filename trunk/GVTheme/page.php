<?php
get_header(); ?>
		<div id="primary">
			<div id="content" role="main">			
			<?php
			if (have_posts()) :
				while (have_posts()) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>						<header class="entry-header"><?php 						the_title('<h1 class="posttitle">', '</h1>');						edit_post_link('Edit', '<span class="edit-link">', '</span>' );						?></header><?php						the_content();												?><footer class="entry-footer"><?php						wp_link_pages( array( 'before' => '<div class="page-link"><span>' . 'Pages:' . '</span>', 'after' => '</div>' ) );						?></footer>										</article>										<?php comments_template( '', true );
				endwhile;
			endif;
			?>
	
			</div> <!-- content -->
		</div> <!-- primary-->
<?php get_sidebar();
get_footer();

?>