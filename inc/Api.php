<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Optinoid_Api {

	function __construct() {
	
		add_action( 'wp_ajax_view_optinoid', array($this, 'update_views'));
		add_action( 'wp_ajax_nopriv_view_optinoid', array($this, 'update_views'));
		
		add_action( 'wp_ajax_submit_optinoid', array($this, 'submit_form'));
		add_action( 'wp_ajax_nopriv_submit_optinoid', array($this, 'submit_form'));
		
		add_action( 'wp_ajax_close_optinoid', array($this, 'close_optin'));
		add_action( 'wp_ajax_nopriv_close_optinoid', array($this, 'close_optin'));
		
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
			
			$client->query('ContactService.add', $options['infusionsoft_api'], array(
				'FirstName' => $first_name,
				'LastName' => $last_name,
				'Email' => $email
			));
			
			$response = $client->getResponse();
		
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