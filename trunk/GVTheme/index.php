<?php get_header(); ?>
		<div id="primary">
			<div id="content" role="main">
			<?php
			if (have_posts()) :			
				while (have_posts()) : the_post();									?><article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>						<header class="entry-header"><?php 
						the_title('<h1 class="posttitle">', '</h1>');
						edit_post_link('Edit', '<span class="edit-link">', '</span>' );						if ( 'post' == get_post_type() ) : ?>							<p>Date posted: <?php the_date(); ?> (							<?php comments_popup_link( 'No comments yet', '1 comment', '% comments', 'comments-link', 'comments closed'); ?>						<?php endif;						if ( 'post' == get_post_type() ) : ?>							)</p>						<?php endif;						?></header><?php
						the_content();												?><footer class="entry-footer"><?php						wp_link_pages( array( 'before' => '<div class="page-link"><span>' . 'Pages:' . '</span>', 'after' => '</div>' ) );						?></footer>					</article><?php
				endwhile;				echo paginate_links( );			else :							?><article id="post-0" class="post no-results not-found">				<header class="entry-header">					<h1 class="entry-title">No posts to display</h1>				</header>				<div class="entry-content">					<p>No posts to display</p>				</div>			</article><!-- #post-0 --> <?php			endif;
			?>			</div> <!-- content -->
		 </div> <!-- primary-->		
<?php  get_sidebar(); 
get_footer(); ?>