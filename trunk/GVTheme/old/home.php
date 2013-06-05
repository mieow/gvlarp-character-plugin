<?php
/*
Template Name: Comments Page
*/

get_header(); ?>

		<div id="primary">

			<div id="content" role="main">
	
			<?php
			if (have_posts()) :
				while (have_posts()) : 
					the_post(); ?>
					
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<header>
						<?php the_title('<h1 class="posttitle">', '</h1>');
						edit_post_link('Edit', '<span class="edit-link">', '</span>' ); ?>
						<?php if ( 'post' == get_post_type() ) : ?>
							<p>Date posted: <?php the_date(); ?> (
							<?php comments_popup_link( 'No comments yet', '1 comment', '% comments', 'comments-link', 'comments closed'); ?>
						<?php endif; ?>
						<?php if ( 'post' == get_post_type() ) : ?>
							)</p>
						<?php endif; ?>
						</header>
						
						<?php the_content();?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link"><span>' . 'Pages:' . '</span>', 'after' => '</div>' ) ); ?>
						
						<footer>
							
						</footer>
					</article>
					<?php
				endwhile;
			endif;
			?>
	
			</div> <!-- content -->
		</div> <!-- primary-->
	
<?php get_sidebar();
get_footer();

?>