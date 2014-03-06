<?php
/*
Template Name: No Sidebar
*/
?><?php
get_header(); ?>

		<div id="primary">

			<div id="content" role="main">
	
			<?php
			if (have_posts()) :
				while (have_posts()) :
					the_post();
					the_title('<h1 class="posttitle">', '</h1>');
					edit_post_link('Edit', '<span class="edit-link">', '</span>' );
					the_content();
				endwhile;
			endif;
			?>
	
			</div> <!-- content -->
		</div> <!-- primary-->
	
<?php 
get_footer();

?>