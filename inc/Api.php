<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Optinoid_Api {

	function __construct() {
	
		add_action( 'wp_ajax_load_optinoid', array($this, 'load_optinoid'));
		add_action( 'wp_ajax_nopriv_load_optinoid', array($this, 'load_optinoid'));
	
		add_action( 'wp_ajax_view_optinoid', array($this, 'update_views'));
		add_action( 'wp_ajax_nopriv_view_optinoid', array($this, 'update_views'));
		
		add_action( 'wp_ajax_submit_optinoid', array($this, 'submit_form'));
		add_action( 'wp_ajax_nopriv_submit_optinoid', array($this, 'submit_form'));
		
		add_action( 'wp_ajax_close_optinoid', array($this, 'close_optin'));
		add_action( 'wp_ajax_nopriv_close_optinoid', array($this, 'close_optin'));
		
	}
	
	public function load_optinoid() {
		
		global $wpdb;
		
		if(empty($_POST['id'])) {
			wp_die();
		}
		
		
		$post_id = $_POST['id'];
		
		$closed_optins = !empty($_POST['closed'])?$_POST['closed']:array();
		
		// get optins that won't render inline
		$optins = get_posts(array(
			'post_type' => 'optinoids',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'ID',
			'order' => 'ASC',
			'post__not_in' => $closed_optins,
			'meta_query' => array(
				array(
					'key' => 'optinoid_type',
					'value' => 'inline',
					'compare' => '!='
				),
				array(
					'key' => 'optinoid_media',
					'value' => $_POST['mobile']==='false'?'desktop':'mobile'
				)
			)
		));
		
		$output = array();
		
		if(!empty($optins)) {
			foreach($optins as $k=>$optin) {
				if($this->maybe_display_optin($optin, $post_id)) {
					$type = get_post_meta($optin->ID, 'optinoid_type', true);
					
					$key = 'overlay';
					if(in_array($type, array('welcomemat', 'welcomemat-fb'))) {
						$key = 'welcome';
					}
					if($type == 'floating-bar') {
						$key = 'floating-bar';
					}
					
					$output[$key]['items'][$k]['optin'] = $optin;
					
					if($type == 'welcomemat-fb') {
						$output['floating-bar']['items'][$k]['optin'] = $optin;
						$output['floating-bar']['items'][$k]['options'] = array('type' => 'floating-bar', 'theme' => 'floating-bar', 'hide-close' => true, 'linked' => true);
					}
					
					if(in_array($type, array('floating-bar', 'welcomemat-fb'))) {
						$optin_fb_position = get_post_meta($optin->ID, 'optinoid_fb_position', true);
						if(!empty($optin_fb_position)) {
							$output['floating-bar']['class'] = 'optinoid-stick-'.$optin_fb_position;
						}
					}								
							
				}
			}
		}
		
		foreach($output as $container => $items) {
			?>
			<div id="optinoid-<?php echo $container; ?>"<?php if(!empty($output[$container]['class'])) echo ' class="'.$output[$container]['class'].'"' ?>>
			<?php
				foreach($items['items'] as $optin) {
					$this->render_optinoid($optin['optin'], !empty($optin['options'])?$optin['options']: array());
				}
			?>
			</div>
			<?php
		}
		
		wp_die();		
		
	}
	
	public function render_optinoid($optin, $options = array()) {
	
		// get optin type if not provided
		$optin_type = !empty($options['type'])?$options['type']:get_post_meta($optin->ID, 'optinoid_type', true);
		// get theme if not provided
		$optin_theme = !empty($options['theme'])?$options['theme']:get_post_meta($optin->ID, 'optinoid_theme', true);
		
		// 
		$optin_media = get_post_meta($optin->ID, 'optinoid_media', true);
		$optin_delay = get_post_meta($optin->ID, 'optinoid_delay', true);
		
		
		$theme_root = get_theme_root();
		$plugin_root = plugin_dir_path(dirname(__FILE__));
		
		$this->args = array(
			'id' => $optin->ID,
			'class' => array('optinoid-optin', $optin_media, $optin_theme),
			'type' => $optin_type
		);
		
		if($optin_type == 'floating-bar') {
			// override theme
			$optin_theme = 'floating-bar';
			$options['hide-close'] = true;
		}
		
		if(in_array($optin_type, array('welcomemat', 'welcomemat-fb'))) {
			$options['hide-close'] = true;
		}
		
		if(!empty($options['inline'])) {
			$this->args['class'][] = 'optin-inline';
		}
		
		if(!empty($options['linked'])) {
			$this->args['class'][] = 'linked';
		}
		
		// do action before render to be able to manage css classes
		do_action( 'optinoid_before_render' );
		
		$button_color = get_post_meta($optin->ID, 'optinoid_button_color', true);
		$button_text_color = get_post_meta($optin->ID, 'optinoid_button_text_color', true);
		$bg_color = get_post_meta($optin->ID, 'optinoid_bg_color', true);
		$arrow_color = get_post_meta($optin->ID, 'optinoid_arrow_color', true);
		$text_color = get_post_meta($optin->ID, 'optinoid_text_color', true);
		
		?>
		<div class="<?php echo implode(' ', $this->args['class']); ?>" data-delay="<?php echo $optin_delay; ?>" data-ID="<?php echo $optin->ID; ?>" data-type="<?php echo $optin_type; ?>" data-text-color="<?php echo $text_color; ?>" data-btn-color="<?php echo $button_color; ?>" data-btn-text-color="<?php echo $button_text_color; ?>" data-arrow-color="<?php echo $arrow_color; ?>" data-bg-color="<?php echo $bg_color; ?>">
		<?php
		
		// check if theme has template to over-ride plugin
		if(file_exists($theme_root.'/optinoid_themes/'.$optin_theme.'.php')) {
			
			include($theme_root.'/optinoid_themes/'.$optin_theme.'.php');
			
		} elseif (file_exists($plugin_root.'optinoid_themes/'.$optin_theme.'.php')) {
			
			include($plugin_root.'optinoid_themes/'.$optin_theme.'.php');
			
		} else {
		
			echo 'Template missing!';
			
		}
		
		?>
		<?php if(empty($options['hide-close'])): ?>
			<a class="close-optin">&#215;</a>
		<?php endif; ?>
		
		</div>
		<?php
		
	}
	
	
	public function maybe_display_optin($optin, $post_id) {
	
		// check first if optin is globally enabled
		$load_globally = get_post_meta($optin->ID, 'optinoid_load_globally', true);
		
		if(!$load_globally) {
		
			// check for specific page
			$optin_pages = get_post_meta($optin->ID, 'optinoid_pages', true);
			
			if(is_array($optin_pages) && !empty($optin_pages)) {
				if(in_array($post_id, $optin_pages)) {
					return true;
				}
			}
			
			// check for post type
			$optin_post_types = get_post_meta($optin->ID, 'optinoid_post_types', true);
			
			if(is_array($optin_post_types) && !empty($optin_post_types)) {
			
				// check if is home
				if(in_array('home', $optin_post_types) && is_front_page()) {
					return true;
				}
				
				// check if is search
				if(in_array('search', $optin_post_types) && is_search()) {
					return true;
				}
				
				// check if is archive
				if(in_array('archive', $optin_post_types) && is_archive()) {
					return true;
				}
				
				// check if is singular post type
				$post_type = get_post_type($post_id);
				if(in_array($post_type, $optin_post_types) && is_singular($optin_post_types)) {
					return true;
				}
			
			}
			
			
			// check for category
			$optin_categories = get_post_meta($optin->ID, 'optinoid_categories', true);
			
			if(is_array($optin_categories) && !empty($optin_categories)) {
			
				// check if is category
				if(is_category($post_id)) {
					return true;
				}
				
				// check if is a post in category
				if(in_category($optin_categories, $post_id)) {
					return true;
				}
				
			}
		
			// better not show if no condition was met
			return false;
			
		}
	
		return true;
	}
	
	public function update_views() {
		
		global $wpdb;
		
		// check referer
		check_ajax_referer('optinoid', 'security');
		
		if(empty($_POST['id'])) {
			wp_die();
		}
	
		// 
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."optinoid_stats SET `views`=`views`+1, `last_view` = %s WHERE optinoid_id=%d", date('Y-m-d H:i:s'), $_POST['id']));
		
		wp_die();
	}
	
	public function submit_form() {
	
		global $wpdb;
	
		// check referer
		check_ajax_referer('optinoid', 'security');
		
		// check honeypot field
		if(!empty($_POST['url'])) {
			wp_die();
		}
		
		// check optin id field
		if(empty($_POST['id'])) {
			wp_die();
		}
		
		// check 
		if(empty($_POST['optin-email']) || !is_email($_POST['optin-email'])) {
			wp_send_json(array('errors' => 'Please enter a valid email'));
		}
		
		// check if optin exists
		$optin = get_post($_POST['id']);
		
		// if doesn't exist die early
		if(empty($optin->ID)) {
			wp_die();
		}
		
		// get form ID for the optin
//		$form_id = get_post_meta($optin->ID, 'optin_infusionsoft_form_id', true);
		
		// send to infusion soft
//		$response = $this->post_to_infusionsoft($form_id, array(
//			'name' => !empty($_POST['optin-name'])?$_POST['optin-name']:'',
//			'email' => $_POST['optin-email'] 
//		));

		
		// insert subscriber
		$name = !empty($_POST['optin-name'])?$_POST['optin-name']:null;
		$email = $_POST['optin-email'];
		
		// avoid duplicate subscribers
		$is_duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".$wpdb->prefix."optinoid_subscribers WHERE optinoid_id = %s AND email=%s", $optin->ID, $email));
		
		if(empty($is_duplicate)) {
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."optinoid_subscribers (name, email, optinoid_id, created) VALUES (%s, %s, %s, %s)", $name, $email, $optin->ID, date('Y-m-d H:i:s')));
		}
		
		// update clicks for stats
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."optinoid_stats SET `clicks`=`clicks`+1, `last_click` = %s WHERE optinoid_id=%d", date('Y-m-d H:i:s'), $optin->ID));
		
		
		
		// check if has any 3rd party integration
		$integration = get_post_meta($optin->ID, 'optinoid_integration', true);
		$list_id = get_post_meta($optin->ID, 'optinoid_list_id', true);
		
		if(!empty($integration) && !empty($list_id)) {
			switch ($integration) {
				case 'mailchimp':
					$this->mailchimp_subscribe($list_id, $email, $name);
					break;
				case 'infusionsoft':
					$this->infusionsoft_subscribe($list_id, $email, $name);
					break;
				case 'sendgrid':
					$this->sendgrid_subscribe($list_id, $email, $name);
					break;
				case 'campaignmonitor':
					$this->campaignmonitor_subscribe($list_id, $email, $name);
					break;
			}
		}
		
		
		// update cookie
		$this->update_cookie($optin->ID);
		
		$response = array('success' => true, 'redirect' => false);
		
		// check if has optinoid_success_url
		$success_url = get_post_meta($optin->ID, 'optinoid_success_url', true);
		
		if(!empty($success_url)) {
			$response['redirect'] = true;
			$response['url'] = $success_url;
		}
					
		wp_send_json($response);
		
		wp_die();
	}
	
	public function mailchimp_subscribe($list_id, $email, $name = null) {
	
		// get options
		$options = get_option('optinoid_options');
		// get mailchimp api
		$mailchimp_api = $options['mailchimp_api'];
		
		$dc = substr($mailchimp_api, strrpos($mailchimp_api, '-')+1);
		
		$endpoint = 'https://'.$dc.'.api.mailchimp.com/3.0/';
		
		$response = wp_remote_post($endpoint.'lists/'.$list_id.'/members', array(
			'headers' => array(
				'Authorization' => 'apikey '.$mailchimp_api
			),
			'body' => json_encode(array(
				'email_address' => $email,
				'status' => 'subscribed'
			))
		));
		
	}
	
	public function sendgrid_subscribe($list_id, $email, $name = null) {
	
		// get options
		$options = get_option('optinoid_options');
		
		// get mailchimp api
		$sendgrid_api_user = $options['sendgrid_api_user'];
		$sendgrid_api_key = $options['sendgrid_api_key'];
			
		$endpoint = 'https://api.sendgrid.com/api/newsletter/';
			
			
		$response = wp_remote_post($endpoint.'lists/email/add.json', array(
			'body' => array(
				'api_user' => $sendgrid_api_user,
				'api_key' => $sendgrid_api_key,
				'list' => $list_id,
				'data' => json_encode(array(
					'email' => $email,
					'name' => $name
				))
			)
		));
		
	}
	
	public function infusionsoft_subscribe($list_id, $email, $name = null) {
		
		// Include WordPress libraries to handle XML-RPC
			require_once ABSPATH . '/wp-includes/class-IXR.php';
			require_once ABSPATH . '/wp-includes/class-wp-http-ixr-client.php';
		
			// get options
			$options = get_option('optinoid_options');
			
			// Initialize the client
			$client = new WP_HTTP_IXR_Client( 'https://'.$options['infusionsoft_subdomain'].'.infusionsoft.com/api/xmlrpc' );
			
			$first_name = null;
			$last_name = null;
			
			if(!empty($name)) {
				$name_arr = explode(' ', $name);
				if(!empty($name_arr[0])) {
					$first_naem = $name_arr[0];
				}
				if(!empty($name_arr[1])) {
					$first_naem = $name_arr[1];
				}
			}
			
			// contact add
			$client->query('ContactService.add', $options['infusionsoft_api'], array(
				'FirstName' => $first_name,
				'LastName' => $last_name,
				'Email' => $email
			));
			
			// optin email
			$client->query('APIEmailService.optIn', $options['infusionsoft_api'], $email, 'Optinoid API Opt In');
			
		
	}
	
	public function campaignmonitor_subscribe($list_id, $email, $name = null) {
	
		// get options
		$options = get_option('optinoid_options');
		
		$endpoint = ' https://api.createsend.com/api/v3.1/subscribers/'.$list_id.'.json';
		
		$response = wp_remote_post($endpoint, array(
			'headers' => array(
				'Authorization' => 'Basic '.base64_encode($options['campaignmonitor_api'])
			),
			'body' => json_encode(array(
				'EmailAddress' => $email,
				'Name' => $name,
				'Resubscribe' => true
			))
		));
		
	}
	
	public function close_optin() {
		
		// check referer
		check_ajax_referer('optinoid', 'security');
		
		if(empty($_POST['id'])) {
			wp_die();
		}
	
		// set cookie
		$this->update_cookie($_POST['id']);
		
		wp_die();
	}
	
	public function update_cookie($id) {
		
		if(isset($_COOKIE['optinoid-closed-optins'])) {
			$closed_optins = json_decode(stripslashes($_COOKIE['optinoid-closed-optins']), true);
		}
			
		$closed_optins = !empty($closed_optins)?$closed_optins:array();
		// add id to cookie array
		$closed_optins[] = $id;
		
		// set cookie for 1 year
		setcookie('optinoid-closed-optins', json_encode($closed_optins), time()+3600*24*365, COOKIEPATH, COOKIE_DOMAIN, false);
		
	}
	
	
}