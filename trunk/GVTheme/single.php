<?php
/*
Template Name: Single Post Page
*/

get_header(); ?>

		<div id="primary">

			<div id="content" role="main">
	
			<?php
			if (have_posts()) :
				while (have_posts()) : 
					the_post(); ?>
					
					<nav class="nav-single">
					<span class="nav-previous"><?php previous_post_link(); ?></span> | 
					<span class="nav-next"><?php next_post_link(); ?></span>
					</nav><!-- .nav-single -->

					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<span class="single-posted">Posted: <?php the_time( get_option( 'date_format' ) ); ?> by  <?php the_author(); ?> </span>
					<?php edit_post_link('Edit', '<span class="edit-link">', '</span>' ); ?>
					</header>

					<div class="entry-content">
						<?php the_content(); ?>
						<?php comments_template(); ?> 
						<?php wp_link_pages( array( 'before' => '<div class="page-links">' . 'Pages:', 'after' => '</div>' ) ); ?>
					</div><!-- .entry-content -->
					
					<footer class="entry-meta">
						<?php edit_post_link('Edit', '<span class="edit-link">', '</span>' ); ?>
						<span class="single-cat"><?php 
							$categories_list =  get_the_category_list(',');
							echo 'Categories: ' . $categories_list;
							$tag_list = get_the_tag_list(' | Tags: ', ',');
							if ( '' != $tag_list ) {
								echo $tag_list;
							}
						?> </span>
					</footer>
					
					</article><!-- post -->

					<?php
				endwhile;
			endif;
			?>
	
			</div> <!-- content -->
		</div> <!-- primary-->
	
<?php get_sidebar();
get_footer();

?>