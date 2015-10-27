<?php 
/*
Plugin Name: Optinoid
Description: Optins Plugin - modal window & welcome mat optin for Wordpress
Author: Stefan Olaru
Version: 1.1
Author URI: http://stefanolaru.com/
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Autoload stuff
spl_autoload_register( 'Optinoid::autoload' );


class Optinoid {
	
	public static $version = '1.2';
	public $themes = array();
	public $output;
	
	function __construct() {
		
		// load themes
		add_action('init', array($this, 'load_themes'));
		
		// 
		add_action('init', array($this, 'load_globals'));
		add_action('init', array($this, 'load_admin_globals'));
		
		// install/uninstall hooks
		register_activation_hook( __FILE__, array('Optinoid', 'optinoid_install'));
		register_deactivation_hook( __FILE__, array('Optinoid', 'optinoid_deactivate'));
		register_uninstall_hook( __FILE__, array('Optinoid', 'optinoid_uninstall'));
		
	}
	
	public function load_globals() {
	
		// global stuff, custom post type
		new Optinoid_Global();
		new Optinoid_Api();
		
		if(!is_admin()) {
			$this->output = new Optinoid_Output($this->themes);
		}
	}
	
	public function load_admin_globals() {
	
		if(is_admin()) {
			new Optinoid_Settings;
			new Optinoid_Subscribers;
			new Optinoid_Admin($this->themes);
		}
		
	}
	
	public function load_themes() {
		//
		$this->themes = array(
			array('id' => 'content-below-image', 'label' => 'Content Below Image'), 
			array('id' => 'content-over-image', 'label' => 'Content Over Image'), 
			array('id' => 'content-over-full', 'label' => 'Full Screen'),
			array('id' => 'content-over-split', 'label' => 'Full Screen Split'), 
			array('id' => 'content-right', 'label' => 'Content on RHS'),
			array('id' => 'simple-form', 'label' => 'Simple Form')
		);
		
		// add hook
		do_action( 'optinoid_themes_loaded' );
	}
	
	public function add_theme($id, $label) {
		$this->themes[] = array('id' => $id, 'label' => $label);
	}
	
	public static function autoload($class_name) {
		// return early
		if(mb_substr($class_name, 0, 8) !== 'Optinoid') {
			return;
		}
		
		// check if file exists
		$filename = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . str_replace( 'Optinoid_', 'inc/'.DIRECTORY_SEPARATOR, $class_name ) . '.php';
		
		// load file if exists
		if(file_exists($filename)) {
			require_once($filename);
		}
		
	}
	
	public static function optinoid_install() {
		
		// 
		global $wpdb;
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$charset_collate = $wpdb->get_charset_collate();
		
		// create stats table
		$table_name = $wpdb->prefix.'optinoid_stats';
		
		$sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
		  `optinoid_id` int(11) NOT NULL,
		  `views` int(11) NOT NULL DEFAULT '0',
		  `clicks` int(11) NOT NULL DEFAULT '0',
		  `last_view` datetime NOT NULL,
		  `last_click` datetime NOT NULL,
		  PRIMARY KEY (`optinoid_id`)
		) ".$charset_collate;
	
		dbDelta($sql);
		
		// create stats table
		$table_name = $wpdb->prefix.'optinoid_subscribers';
			
		$sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) DEFAULT NULL,
		  `email` varchar(255) NOT NULL,
		  `optinoid_id` int(11) DEFAULT NULL,
		  `created` datetime NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `optinoid_id` (`optinoid_id`)
		) ".$charset_collate;
		
		dbDelta($sql);
		
		// add version option
		add_option( 'optinoid_version', self::$version );
			
	}
	
	public static function optinoid_deactivate() {
		// nothing here yet
	}
	
	public static function optinoid_uninstall() {
		global $wpdb;
		
		// remove all optinoids custom posts
		$optinoids = get_posts(array(
			'post_type' => 'optinoids',
			'posts_per_page' => -1
		));
		
		if(!empty($optinoids)) {
			foreach($optinoids as $v) {
				// delete custom post type with everything related
				wp_delete_post($v->ID, true);
			}
		}
		
		// remove optinoidoptions
		delete_option( 'optinoid_options' );
		
		// drop custom tables
		$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS ".$wpdb->prefix."optinoid_stats"));
		$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS ".$wpdb->prefix."optinoid_subscribers"));
		
	}
	
}

$optinoid = new Optinoid;

?>