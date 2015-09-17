<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Optinoid_Global {

	function __construct() {
		// create custom posts
		$this->create_custom_post();
	}
	
	public function create_custom_post() {
		$labels = array(
			'name' => _x('Opt-Ins', 'post type general name'),
			'singular_name' => _x('Optin', 'post type singular name'),
			'add_new' => _x('Add New', 'Optin'),
			'add_new_item' => __('Add New Optin'),
			'edit_item' => __('Edit Optin'),
			'new_item' => __('New Optin'),
			'view_item' => __('View Optin'),
			'search_items' => __('Search Optins'),
			'not_found' => __('No Optins found'),
			'not_found_in_trash' => __('No Optins found in Trash'),
			'parent_item_colon' => ''
		);
		
		$supports = array('title', 'editor', 'thumbnail');
		
		register_post_type('optinoids', array(
			'labels' => $labels,
			'public' => false,
			'has_archive' => false,
			'rewrite' => false,
			'show_ui' => true,
			'show_in_menu' => 'optinoid-menu',
			'supports' => $supports,
			'hierarchical' => false
		));
	}
	
}