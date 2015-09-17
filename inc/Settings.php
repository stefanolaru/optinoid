<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Optinoid_Settings {

	private $options;

	function __construct() {
	
		// admin menu
		add_action( 'admin_menu', array($this, 'add_admin_menu') );
		add_action( 'admin_init', array($this, 'register_settings') );
		
	}
	
	public function add_admin_menu() {
		
		//
		add_menu_page( 'Optinoid Settings', 'Optinoid', 'manage_options', 'optinoid-menu', array($this, 'settings'), plugin_dir_url(dirname(__FILE__)).'/img/optinoid-icon.png' );
		add_submenu_page( 'optinoid-menu', 'Optinoid Settings', 'Settings', 'manage_options', 'optinoid-settings', array($this, 'settings') );
		
	}
	
	public function settings() {
	
		// populate options
		$this->options = get_option( 'optinoid_options' );

		?>
		<div class="wrap">
				<h1>Optinoid Settings</h1>
				
				<form method="post" action="options.php"> 
					
					<?php settings_fields( 'optinoid-options' ); ?>
					
					<?php do_settings_sections( 'optinoid-options' ); ?>
					
					<?php submit_button(); ?>
					
				</form>
				
		</div>
		<?php
	}
	
	public function register_settings() {
		register_setting( 'optinoid-options', 'optinoid_options' );
		
		add_settings_section('optinoid-infusionsoft-api', 'Infusionsoft API Integration', '', 'optinoid-options' );
		
		add_settings_field('optinoid-infusionsoft-subdomain', 'Infusionsoft Subdomain', array($this, 'infusionsoft_subdomain'), 'optinoid-options', 'optinoid-infusionsoft-api');
		add_settings_field('optinoid-infusionsoft-api-key', 'Infusionsoft API Key', array($this, 'infusionsoft_api'), 'optinoid-options', 'optinoid-infusionsoft-api');
		
		add_settings_section('optinoid-mailchimp-api', 'Mailchimp API Integration', '', 'optinoid-options' );
		
		add_settings_field('optinoid-mailchimp-api-key', 'Mailchimp API Key', array($this, 'mailchimp_api'), 'optinoid-options', 'optinoid-mailchimp-api');
		
		
		
		add_settings_section('optinoid-cm-api', 'Campaign Monitor API Integration', '', 'optinoid-options' );
		
		add_settings_field('optinoid-cm-api-key', 'Campaign Monitor API Key', array($this, 'campaignmonitor_api'), 'optinoid-options', 'optinoid-cm-api');
		
		add_settings_field('optinoid-cm-api-client', 'Campaign Monitor Client ID', array($this, 'campaignmonitor_api_client'), 'optinoid-options', 'optinoid-cm-api');
		
		
		
		add_settings_section('optinoid-sendgrid-api', 'Sendgrid API Integration', '', 'optinoid-options' );
		
		add_settings_field('optinoid-sendgrid-api-user', 'Sendgrid API User (username)', array($this, 'sendgrid_api_user'), 'optinoid-options', 'optinoid-sendgrid-api');
		
		add_settings_field('optinoid-sendgrid-api-key', 'Sendgrid API Key (password)', array($this, 'sendgrid_api_key'), 'optinoid-options', 'optinoid-sendgrid-api');
		
	}
	
	public function infusionsoft_subdomain() {
		printf('<input type="text" id="infusionsoft_subdomain" name="optinoid_options[infusionsoft_subdomain]" value="%s" />',
			isset( $this->options['infusionsoft_subdomain'] ) ? esc_attr( $this->options['infusionsoft_subdomain']) : ''
		);
	}
	
	public function infusionsoft_api() {
		printf('<input type="text" id="infusionsoft_api" name="optinoid_options[infusionsoft_api]" value="%s" />',
			isset( $this->options['infusionsoft_api'] ) ? esc_attr( $this->options['infusionsoft_api']) : ''
		);
	}
	
	
	public function mailchimp_api() {
		printf('<input type="text" id="mailchimp_api" name="optinoid_options[mailchimp_api]" value="%s" />',
			isset( $this->options['mailchimp_api'] ) ? esc_attr( $this->options['mailchimp_api']) : ''
		);
	}
	
	public function campaignmonitor_api() {
		printf('<input type="text" id="campaignmonitor_api" name="optinoid_options[campaignmonitor_api]" value="%s" />',
			isset( $this->options['campaignmonitor_api'] ) ? esc_attr( $this->options['campaignmonitor_api']) : ''
		);
	}
	
	public function campaignmonitor_api_client() {
		printf('<input type="text" id="campaignmonitor_api_client" name="optinoid_options[campaignmonitor_api_client]" value="%s" />',
			isset( $this->options['campaignmonitor_api_client'] ) ? esc_attr( $this->options['campaignmonitor_api_client']) : ''
		);
	}
	
	public function sendgrid_api_user() {
		printf('<input type="text" id="sendgrid_api_user" name="optinoid_options[sendgrid_api_user]" value="%s" />',
			isset( $this->options['sendgrid_api_user'] ) ? esc_attr( $this->options['sendgrid_api_user']) : ''
		);
	}
	
	public function sendgrid_api_key() {
		printf('<input type="password" id="sendgrid_api_key" name="optinoid_options[sendgrid_api_key]" value="%s" />',
			isset( $this->options['sendgrid_api_key'] ) ? esc_attr( $this->options['sendgrid_api_key']) : ''
		);
	}
	
}