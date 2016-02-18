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
		add_action('wp_footer', array($this, 'insert_optinoid'));
		
		// optinoid shortcode
//		add_shortcode('optinoid', array($this, 'inline_shortcode'));
	}
	
	public function include_css_js() {
		if(!is_admin()) {
			// just frontend stuff
			wp_enqueue_style('optinoid', plugin_dir_url(dirname(__FILE__)). 'css/optinoid.css', 1);
			wp_enqueue_script('optinoid', plugin_dir_url(dirname(__FILE__)). 'js/optinoid.min.js', false, null, true);
		}
	}
		
	public function add_script_vars() {
			
			$post_id = get_queried_object_id();
			
			?>
			<script type="text/javascript">
				var optinoid = {
					id: '<?php echo get_queried_object_id(); ?>',
					api_url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					nonce: '<?php echo wp_create_nonce( 'optinoid' ); ?>',
					is_home: <?php echo is_front_page()?1:0; ?>
				};
			</script>
			<?php
			
	}
	
	public function insert_optinoid() {
		?>
		<div id="optinoid-placeholder"></div>
		<?php
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
		
		$output = '<div class="optinoid-inline-placeholder" data-id="'.$optin->ID.'"></div>';
		
		//return
		return $output;
	}
	
}