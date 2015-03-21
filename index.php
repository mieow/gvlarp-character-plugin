<?php get_header(); ?>
		<div id="primary">
			<div id="content" role="main">
			<?php
			if (have_posts()) :			
				while (have_posts()) : the_post();									?><article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>						<header class="entry-header"><?php 
						the_title('<h1 class="posttitle">', '</h1>');
						edit_post_link(__('Edit', 'nocturnus'), '<span class="edit-link">', '</span>' );						if ( 'post' == get_post_type() ) : ?>							<p>Date posted: <?php the_date(); ?> (							<?php comments_popup_link( __('No comments yet', 'nocturnus'), 								__('1 comment', 'nocturnus'), 								__('% comments', 'nocturnus'), 								__('comments-link', 'nocturnus'),								__('comments closed', 'nocturnus')); ?>						<?php endif;						if ( 'post' == get_post_type() ) : ?>							)</p>						<?php endif;						?></header><?php
						the_content();												?><footer class="entry-footer"><?php						wp_link_pages( array( 'before' => '<div class="page-link"><span>' . __('Pages', 'nocturnus') . ':</span>', 'after' => '</div>' ) );						?></footer>					</article><?php
				endwhile;				echo paginate_links( );			else :							?><article id="post-0" class="post no-results not-found">				<header class="entry-header">					<h1 class="entry-title"><?php _e('No posts to display', 'nocturnus'); ?></h1>				</header>				<div class="entry-content">					<p><?php _e('No posts to display', 'nocturnus'); ?></p>				</div>			</article><!-- #post-0 --> <?php			endif;
			?>			</div> <!-- content -->
		 </div> <!-- primary-->		
<?php  get_sidebar(); 
get_footer(); ?>