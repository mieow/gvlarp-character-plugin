<?php
/**
 * Template Name: Private Messages
 *
 * @package GG-Nocturnus
 * @subpackage Templates
 */
if (!is_user_logged_in()) {
	redirect_to_login_url();
}

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
	$show = array(false, true, false);
	<div id="pm-send" <?php if (!$show[0]) echo 'style="display:none"'; ?>><?php rwpm_send();?></div>
	<div id="pm-inbox" <?php if (!$show[1]) echo 'style="display:none"'; ?>><?php rwpm_inbox();?></div>
	<div id="pm-outbox" <?php if (!$show[2]) echo 'style="display:none"'; ?>><?php rwpm_outbox();?></div>
	</div>
	<!-- END PRIVATE MESSAGE CONTENT -->		
					