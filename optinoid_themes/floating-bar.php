<div class="row">
		<form method="post" class="optinoid-form">
			<?php $text = get_post_meta($optin->ID, 'optinoid_fb_text', true); ?>
			<?php if(!empty($text)): ?>
			<div class="medium-4 columns">
				<?php echo $text; ?>
			</div>
			<?php endif; ?>
			<div class="medium-<?php echo !empty($text)?8:12; ?> columns">
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
				<?php 
					$button_text = get_post_meta($optin->ID, 'optinoid_button_text', true);
				?>
				<input type="hidden" name="id" value="<?php echo $optin->ID; ?>" /><input type="hidden" name="action" value="submit_optinoid" /><input type="hidden" name="security" value="<?php echo wp_create_nonce( 'optinoid' ); ?>" /><button type="submit" class="button"><?php echo !empty($button_text)?$button_text:'Subscribe'; ?></button>
			</div>
		</div>
	</form>
</div>