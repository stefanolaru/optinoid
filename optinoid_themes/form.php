<form method="post" class="optinoid-form">
	<div class="optinoid-form-inner">
		<?php
			$fields = get_post_meta($optin->ID, 'optinoid_fields', true);
		?>
		<?php if(in_array('name', $fields)): ?>
		<input type="text" name="optin-name" placeholder="Your Name" class="optinoid-field<?php if(count($fields) > 1) echo ' has-multiple-fields'; ?>" />
		<?php endif; ?>
			
		<?php if(in_array('email', $fields)): ?>
		<input type="email" name="optin-email" placeholder="Your Email" class="optinoid-field<?php if(count($fields) > 1) echo ' has-multiple-fields'; ?>" />
		<?php endif; ?>
			
		<input type="hidden" name="id" value="<?php echo $optin->ID; ?>" />
		<input type="hidden" name="action" value="submit_optinoid" />
		<input type="hidden" name="security" value="<?php echo wp_create_nonce( 'optinoid' ); ?>" />
		<?php 
			$button_text = get_post_meta($optin->ID, 'optinoid_button_text', true);
		?>
		<button type="submit" class="button optinoid-button"><?php echo !empty($button_text)?$button_text:'Subscribe'; ?></button>
			
		<div class="optin-input-url">
			<input type="text" name="url" value="" />
		</div>
	</div>
</form>