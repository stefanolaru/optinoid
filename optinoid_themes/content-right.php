<div class="optinoid-container">	
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