<?php if(has_post_thumbnail($optin->ID)): ?>
<div class="optinoid-thumb-container">
	<?php echo get_the_post_thumbnail($optin->ID, 'full', array(
		'class' => 'optinoid-thumb'
	)); ?>
</div>
<?php endif; ?>
<div class="optinoid-container">	
	<div class="optinoid-text">
		<?php echo apply_filters('the_content', $optin->post_content); ?>
	</div>
</div>

<?php include('form.php'); ?>