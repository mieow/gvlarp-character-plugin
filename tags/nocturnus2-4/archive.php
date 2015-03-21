<?php get_header(); ?>

		<div id="primary">

			<div id="content" role="main">

			<?php

			if (have_posts()) : ?>
			
				<header class="archive-header">
					<h1 class="archive-title"><?php
						if ( is_day() ) :
							printf( __('Daily Archives: %s', 'nocturnus'), '<span>' . get_the_date() . '</span>' );
						elseif ( is_month() ) :
							printf( __('Monthly Archives: %s', 'nocturnus'), '<span>' . get_the_date('F Y') . '</span>' );
						elseif ( is_year() ) :
							printf( __('Yearly Archives: %s', 'nocturnus'), '<span>' . get_the_date('Y') . '</span>' );
						else :
							_e('Archives', 'nocturnus');
						endif;
					?></h1>
				</header><!-- .archive-header --><?php
			
				while (have_posts()) : the_post(); ?>
				
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
							<header class="entry-header">
								<?php the_post_thumbnail(); ?>
								<?php if ( is_single() ) : ?>
									<h1 class="entry-title"><?php the_title(); ?></h1>
								<?php else : ?>
									<h1 class="entry-title">
										<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf(__('Permalink to %s', 'nocturnus'), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
									</h1>
								<?php endif; // is_single() ?>
								<?php if ( comments_open() ) : ?>
									<div class="comments-link">
										<?php comments_popup_link( '<span class="leave-reply">' . __('Leave a reply', 'nocturnus') . '</span>', __('1 Reply', 'nocturnus'), __('% Replies', 'nocturnus') ); ?>
									</div><!-- .comments-link -->
								<?php endif; // comments_open() ?>
							</header><!-- .entry-header -->

							
							<div class="entry-excerpt">
								<?php the_excerpt(); ?>
								<?php wp_link_pages( array( 'before' => '<div class="page-links">' . __('Pages:', 'nocturnus'), 'after' => '</div>' ) ); ?>
							</div><!-- .entry-content -->

							<footer><hr /></footer>
					</article> <?php
				endwhile;
				
			else :
				
			?><article id="post-0" class="post no-results not-found">

				<header class="archive-header">
					<h1 class="archive-title"><?php _e('No posts to display', 'nocturnus'); ?></h1>
				</header>

				<div class="archive-content">
					<p><?php _e('No posts to display', 'nocturnus'); ?></p>
				</div>

			</article><!-- #post-0 --> <?php
			endif;

			?>
			</div> <!-- content -->

		 </div> <!-- primary-->
		
<?php  get_sidebar(); 

get_footer(); ?>