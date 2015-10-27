<?php 
if(has_post_thumbnail($optin->ID)) {
	$optinoid_bg = wp_get_attachment_url(get_post_thumbnail_id($optin->ID));
}
?>
<?php $bg_color = get_post_meta($optin->ID, 'optinoid_bg_color', true); ?>
<div class="optinoid-container"<?php if(!empty($bg_color)) echo ' style="background-color: '.$bg_color.';"'; ?>>
	
	<div class="optinoid-split-left"<?php if(!empty($optinoid_bg)) echo ' style="background-image: url('.$optinoid_bg.');"'; ?>>
		<?php $text_color = get_post_meta($optin->ID, 'optinoid_text_color', true); ?>
		<div class="optinoid-text"<?php if(!empty($text_color)) echo ' style="color: '.$text_color.' !important;"'; ?>>
			<?php echo apply_filters('the_content', $optin->post_content); ?>
		</div>
	</div>
	
	<div class="optinoid-split-right">
		<?php include('form.php'); ?>
	</div>
		
</div>