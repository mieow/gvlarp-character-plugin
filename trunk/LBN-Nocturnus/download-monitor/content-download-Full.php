<?php
/**
 * For miscellaneous downloads with full listing
 */

global $dlm_download;
?>
<a class="download-link" 
	title="<?php if ( $dlm_download->has_version_number() ) printf( __( 'Version %s', 'download_monitor' ), $dlm_download->get_the_version_number() ); ?>" 
	href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
	<strong><?php $dlm_download->the_title(); ?></strong>
</a> (<?php $dlm_download->the_filetype();?> - <?php $dlm_download->the_filesize(); ?>)
<p>
	<!-- <?php echo $dlm_download->post->post_excerpt; ?> -->
	<?php echo $dlm_download->post->post_content; ?>
</p>
