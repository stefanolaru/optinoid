<?php $text = get_post_meta($optin->ID, 'optinoid_fb_text', true); ?>
<?php if(!empty($text)): ?>
<?php $text_color = get_post_meta($optin->ID, 'optinoid_text_color', true); ?>
<div class="optinoid-text"<?php if(!empty($text_color)) echo ' style="color: '.$text_color.' !important;"'; ?>>
	<?php echo $text; ?>
</div>
<?php endif; ?>
			
<?php include('form.php'); ?>