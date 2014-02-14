<?php
get_header(); ?>

		<div id="primary">

			<div id="content" role="main">
	
			<article>
			<header><h1>Page Not Found</h1></header>
			
			<p>Cannot find the page you were looking for.  Perhaps we have lost it!</p>
			
			<blockquote><i>I put my heart and my soul into my work, and have lost my mind in the process.</i> - Vincent Van Gogh</blockquote>
			
			<?php
			if ( ! is_user_logged_in() ) { // Display WordPress login form:
				?>
				<p>Or perhaps you just need to log in:</p>
				<?php
    				$args = array(
				        'form_id' => 'loginform-custom',
				        'label_username' => 'Login Name:' ,
				        'label_password' => 'Password:',
				        'label_remember' => 'Remember Me',
				        'label_log_in' => 'Log In',
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