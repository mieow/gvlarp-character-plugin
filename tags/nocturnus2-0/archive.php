<?php get_header(); ?>

		<div id="primary">

			<div id="content" role="main">

			<?php

			if (have_posts()) : ?>
			
				<header class="archive-header">
					<h1 class="archive-title"><?php
						if ( is_day() ) :
							printf( 'Daily Archives: %s', '<span>' . get_the_date() . '</span>' );
						elseif ( is_month() ) :
							printf( 'Monthly Archives: %s', '<span>' . get_the_date('F Y') . '</span>' );
						elseif ( is_year() ) :
							printf( 'Yearly Archives: %s', '<span>' . get_the_date('Y') . '</span>' );
						else :
							echo 'Archives';
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
										<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf('Permalink to %s', the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
									</h1>
								<?php endif; // is_single() ?>
								<?php if ( comments_open() ) : ?>
									<div class="comments-link">
										<?php comments_popup_link( '<span class="leave-reply">' . 'Leave a reply' . '</span>', '1 Reply', '% Replies' ); ?>
									</div><!-- .comments-link -->
								<?php endif; // comments_open() ?>
							</header><!-- .entry-header -->

							
							<div class="entry-excerpt">
								<?php the_excerpt(); ?>
								<?php wp_link_pages( array( 'before' => '<div class="page-links">' . 'Pages:', 'after' => '</div>' ) ); ?>
							</div><!-- .entry-content -->

							<footer><hr /></footer>
					</article> <?php
				endwhile;
				
			else :
				
			?><article id="post-0" class="post no-results not-found">

				<header class="archive-header">
					<h1 class="archive-title">No posts to display</h1>
				</header>

				<div class="archive-content">
					<p>No posts to display</p>
				</div>

			</article><!-- #post-0 --> <?php
			endif;

			?>
			</div> <!-- content -->

		 </div> <!-- primary-->
		
<?php  get_sidebar(); 

get_footer(); ?>