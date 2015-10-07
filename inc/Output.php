<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Optinoid_Output {

	public $themes = array();
	public $args = array();

	function __construct($themes) {
	
		// load themes
		$this->themes = $themes;
		
		// enqueue styles & scripts
		add_action('wp_enqueue_scripts', array($this, 'include_css_js'),9999);
		
		// footer output
		add_action('wp_footer', array($this, 'add_script_vars'));
		add_action('wp_footer', array($this, 'load_optinoid'));
		
		// optinoid shortcode
		add_shortcode('optinoid', array($this, 'inline_shortcode'));
	}
	
	public function include_css_js() {
		if(!is_admin()) {
			// just frontend stuff
			wp_enqueue_style('optinoid', plugin_dir_url(dirname(__FILE__)). 'css/optinoid.css', 1);
			wp_enqueue_script('optinoid', plugin_dir_url(dirname(__FILE__)). 'js/optinoid.min.js', false, null, true);
		}
	}
		
	public function add_script_vars() {
			
			?>
			<script type="text/javascript">
				var optinoid = {
					api_url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					nonce: '<?php echo wp_create_nonce( 'optinoid' ); ?>'
				};
			</script>
			<?php
			
		}
		
	public function load_optinoid() {
		
		global $post;
		
		$post_id = get_queried_object_id();
		
		// get closed optins
		if(isset($_COOKIE['optinoid-closed-optins'])) {
			$closed_optins = json_decode(stripslashes($_COOKIE['optinoid-closed-optins']), true);
		}
		
		$closed_optins = !empty($closed_optins)?$closed_optins:array();
			
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
		
		?>
		<div class="<?php echo implode(' ', $this->args['class']); ?>" data-delay="<?php echo $optin_delay; ?>" data-ID="<?php echo $optin->ID; ?>" data-type="<?php echo $optin_type; ?>">
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
	
	public function inline_shortcode($atts) {
		
		if(empty($atts['id'])) return;
		
		// get optin id
		$optin = get_post($atts['id']);
		
		// if no optin, die
		if(empty($optin->ID)) return;
		
		// if the optin is not inline type stop rendering
		$optin_type = get_post_meta($optin->ID, 'optinoid_type', true);
		if($optin_type != 'inline') {
			return 'Please select an optin for "inline" use';
		}
		
		// output buffering
		ob_start();
		$this->render_optinoid($optin, array('inline' => true, 'hide-close' => true));		
		$output = ob_get_contents();
		ob_end_clean();
		
		//return
		return $output;
	}
	
}