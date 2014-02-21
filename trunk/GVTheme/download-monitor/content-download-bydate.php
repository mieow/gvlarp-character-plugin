<?php
/**
 * For newsletter download by month as menu item
 */

global $dlm_download;
$postmonth = date_i18n('M Y', strtotime($dlm_download->post->post_date));
?>
<a class="download-link" 
	title="<?php if ( $dlm_download->has_version_number() ) printf( __( 'Version %s', 'download_monitor' ), $dlm_download->get_the_version_number() ); ?>" 
	href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
	<?php echo $postmonth; ?>
</a>