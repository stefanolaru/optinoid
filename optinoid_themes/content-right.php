<?php $bg_color = get_post_meta($optin->ID, 'optinoid_bg_color', true); ?>
<div class="optinoid-container"<?php if(!empty($bg_color)) echo ' style="background-color: '.$bg_color.';"'; ?>>	
	<div class="optinoid-text">
		<?php if(has_post_thumbnail($optin->ID)): ?>
			<?php echo get_the_post_thumbnail($optin->ID, 'full', array(
				'class' => 'optinoid-thumb'
			)); ?>
		<?php endif; ?>
		<?php echo apply_filters('the_content', $optin->post_content); ?>	
	</div>
</div>

<?php include('form.php'); ?>