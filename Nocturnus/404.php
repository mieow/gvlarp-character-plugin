<?php
get_header(); ?>

		<div id="primary">

			<div id="content" role="main">
	
			<article>
			<header><h1><?php _e('Page Not Found', 'nocturnus'); ?></h1></header>
			
			<p><?php _e('Cannot find the page you were looking for.  Perhaps we have lost it!', 'nocturnus'); ?></p>
			
			<blockquote><?php _e('<i>I put my heart and my soul into my work, and have lost my mind in the process.</i> - Vincent Van Gogh', 'nocturnus'); ?></blockquote>
			
			<?php
			if ( ! is_user_logged_in() ) { // Display WordPress login form:
				?>
				<p><?php _e('Or perhaps you just need to log in:', 'nocturnus'); ?></p>
				<?php
    				$args = array(
				        'form_id' => 'loginform-custom',
				        'label_username' => __('Login Name', 'nocturnus') ,
				        'label_password' => __('Password', 'nocturnus'),
				        'label_remember' => __('Remember Me', 'nocturnus'),
				        'label_log_in'   => __('Log In', 'nocturnus'),
				        'remember' => true
				);
			    	wp_login_form( $args );
			}
			?>
						
			</article>
	
			</div> <!-- content -->
		</div> <!-- primary-->
	
<?php get_sidebar();
get_footer();

?>