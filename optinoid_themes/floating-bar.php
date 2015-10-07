<?php $text = get_post_meta($optin->ID, 'optinoid_fb_text', true); ?>
<?php if(!empty($text)): ?>
<div class="optinoid-text">
	<?php echo $text; ?>
</div>
<?php endif; ?>
			
<?php include('form.php'); ?>