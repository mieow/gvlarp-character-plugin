<?php
/**
 * Template Name: Private Messages
 *
 * @package Suffusion
 * @subpackage Templates
 */

if (!is_user_logged_in()) {
	redirect_to_login_url();
}

get_header();
?>

<div id="main-col">
<?php
suffusion_page_navigation();
suffusion_before_begin_content();
?>
	<div id="content">
<?php
global $post;
if (have_posts()) {
	while (have_posts()) {
		the_post();
		$original_post = $post;
?>
		<div <?php post_class('fix'); ?> id="post-<?php the_ID(); ?>">
			<?php suffusion_after_begin_post(); ?>
			<div class="entry-container fix">
				<div class="entry fix">
					<?php suffusion_content(); ?>

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
		$show = array(true, false, false);
		if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'rwpm_inbox') {
			$show = array(false, true, false);
		} elseif (isset($_REQUEST['page']) && $_REQUEST['page'] == 'rwpm_outbox') {
			$show = array(false, false, true);
		}
		?>
		<div id="pm-send" <?php if (!$show[0]) echo 'style="display:none"'; ?>><?php rwpm_send();?></div>
		<div id="pm-inbox" <?php if (!$show[1]) echo 'style="display:none"'; ?>><?php rwpm_inbox();?></div>
		<div id="pm-outbox" <?php if (!$show[2]) echo 'style="display:none"'; ?>><?php rwpm_outbox();?></div>

	</div>

	<!-- END PRIVATE MESSAGE CONTENT -->		
					
				</div><!--/entry -->
			<?php
				// Due to the inclusion of Ad Hoc Widgets the global variable $post might have got changed. We will reset it to the original value.
				$post = $original_post;
				suffusion_after_content();
			?>
			</div><!-- .entry-container -->
			<?php suffusion_before_end_post(); ?>

			<?php comments_template(); ?>
		</div><!--/post -->
<?php
	}
}
?>
	</div><!-- #content -->
</div><!-- #main-col -->
<?php get_footer(); ?>