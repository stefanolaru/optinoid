<div class="optinoid-container">	
	<?php $text_color = get_post_meta($optin->ID, 'optinoid_text_color', true); ?>
	<div class="optinoid-text"<?php if(!empty($text_color)) echo ' style="color: '.$text_color.' !important;"'; ?>>
		<?php if(has_post_thumbnail($optin->ID)): ?>
			<?php echo get_the_post_thumbnail($optin->ID, 'full', array(
				'class' => 'optinoid-thumb'
			)); ?>
		<?php endif; ?>
		<?php echo apply_filters('the_content', $optin->post_content); ?>	
	</div>
</div>

<?php include('form.php'); ?>