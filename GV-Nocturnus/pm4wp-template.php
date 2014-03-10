<?php
/**
 * Template Name: Private Messages
 *
 * @package Nocturnus
 * @subpackage Templates
 */
if (!is_user_logged_in()) {
	redirect_to_login_url();
}
get_header(); ?>		<div id="primary">			<div id="content" role="main">			<?php			if (have_posts()) :				while (have_posts()) : the_post();									?><article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>						<header class="entry-header"><?php 						the_title('<h1 class="posttitle">', '</h1>');						edit_post_link('Edit', '<span class="edit-link">', '</span>' );						if ( 'post' == get_post_type() ) : ?>							<p>Date posted: <?php the_date(); ?> (							<?php comments_popup_link( 'No comments yet', '1 comment', '% comments', 'comments-link', 'comments closed'); ?>						<?php endif;						if ( 'post' == get_post_type() ) : ?>							)</p>						<?php endif;						?></header><?php						the_content();
?>
	<!--- PRIVATE MESSAGE CONTENT -->
	<div class="hfeed">			
	<a href="javascript:void(0);" onclick="pmSwitch('pm-send');">Send</a> | <a href="javascript:void(0);" onclick="pmSwitch('pm-inbox');">Inbox</a> | <a href="javascript:void(0);" onclick="pmSwitch('pm-outbox');">Outbox</a>
	<script type="text/javascript">
		// Switch between send page, inbox and outbox
		function pmSwitch(page) {
			document.getElementById('pm-send').style.display = 'none';
			document.getElementById('pm-inbox').style.display = 'none';
			document.getElementById('pm-outbox').style.display = 'none';
			document.getElementById(page).style.display = '';
			return false;
		}
	</script>
	<!-- Include scripts and style for autosuggest feature -->
	<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/private-messages-for-wordpress/js/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/private-messages-for-wordpress/js/jquery.autoSuggest.packed.js"></script>
	<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/private-messages-for-wordpress/js/script.js"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/private-messages-for-wordpress/css/style.css" />

	<?php
	$show = array(false, true, false);	if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'rwpm_inbox') {		$show = array(false, true, false);	} elseif (isset($_REQUEST['page']) && $_REQUEST['page'] == 'rwpm_outbox') {		$show = array(false, false, true);	} elseif (isset($_REQUEST['page']) && $_REQUEST['page'] == 'rwpm_send') {		$show = array(true, false, false);	}	?>
	<div id="pm-send" <?php if (!$show[0]) echo 'style="display:none"'; ?>><?php rwpm_send();?></div>
	<div id="pm-inbox" <?php if (!$show[1]) echo 'style="display:none"'; ?>><?php rwpm_inbox();?></div>
	<div id="pm-outbox" <?php if (!$show[2]) echo 'style="display:none"'; ?>><?php rwpm_outbox();?></div>
	</div>
	<!-- END PRIVATE MESSAGE CONTENT -->		
											<footer class="entry-footer"><?php						wp_link_pages( array( 'before' => '<div class="page-link"><span>' . 'Pages:' . '</span>', 'after' => '</div>' ) );						?></footer>					</article><?php				endwhile;				echo paginate_links( );			else :							?><article id="post-0" class="post no-results not-found">				<header class="entry-header">					<h1 class="entry-title">No posts to display</h1>				</header>				<div class="entry-content">					<p>No posts to display</p>				</div>			</article><!-- #post-0 --> <?php			endif;			?>			</div> <!-- content -->		 </div> <!-- primary-->		<?php  get_sidebar(); get_footer(); ?>