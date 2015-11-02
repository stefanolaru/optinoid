<?php 
if(has_post_thumbnail($optin->ID)) {
	$optinoid_bg = wp_get_attachment_url(get_post_thumbnail_id($optin->ID));
}
?>
<div class="optinoid-container"<?php if(!empty($optinoid_bg)) echo ' style="background-image: url('.$optinoid_bg.');"'; ?>>

	<div class="optinoid-text">
		<?php echo apply_filters('the_content', $optin->post_content); ?>
	</div>
	
	<?php include('form.php'); ?>
		
</div>