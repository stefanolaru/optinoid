<?php 
if(has_post_thumbnail($optin->ID)) {
	$optinoid_bg = wp_get_attachment_url(get_post_thumbnail_id($optin->ID));
}
?>
<div class="optinoid-container"<?php if(!empty($optinoid_bg)) echo ' style="background-image: url('.$optinoid_bg.');"'; ?>>
	<div class="row">
		<div class="medium-12 columns">
			<?php echo apply_filters('the_content', $optin->post_content); ?>
		</div>
	</div>
	<div class="row">
		<form method="post" class="optinoid-form">
			<?php
				$fields = get_post_meta($optin->ID, 'optinoid_fields', true);
			?>
			<?php if(in_array('name', $fields)): ?>
			<div class="optin-input medium-<?php echo count($fields)==2?'5':'9'; ?> columns">
				<input type="text" name="optin-name" placeholder="Your Name" />
			</div>
			<?php endif; ?>
			<?php if(in_array('email', $fields)): ?>
			<div class="optin-input medium-<?php echo count($fields)==2?'5':'9'; ?> columns">
				<input type="email" name="optin-email" placeholder="Your Email" />
			</div>
			<?php endif; ?>
			<div class="optin-input-url">
				<input type="text" name="url" value="" />
			</div>
			<div class="optin-input medium-<?php echo count($fields)==2?'2':'3'; ?> columns optin-submit">
				<input type="hidden" name="id" value="<?php echo $optin->ID; ?>" />
				<input type="hidden" name="action" value="submit_optinoid" />
				<input type="hidden" name="security" value="<?php echo wp_create_nonce( 'optinoid' ); ?>" />
				<?php 
					$button_text = get_post_meta($optin->ID, 'optinoid_button_text', true);
				?>
				<button type="submit" class="button"><?php echo !empty($button_text)?$button_text:'Subscribe'; ?></button>
			</div>
		</form>
	</div>
</div>