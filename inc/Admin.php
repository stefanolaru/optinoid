<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Optinoid_Admin {

	public $themes = array();
	private $options;

	function __construct($themes) {
	
		// load themes
		$this->themes = $themes;
	
		add_filter( 'manage_optinoids_posts_columns', array( $this, 'add_optinoid_column' ));
		add_action( 'manage_optinoids_posts_custom_column', array( $this, 'add_optinoid_column_content' ), 10, 2);
	

		add_action('add_meta_boxes', array($this, 'create_metaboxes'));	
		add_action('save_post', array($this, 'save_optinoid_info'));
		
		add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));
		
		// ajax stuff
		add_action( 'wp_ajax_page_search', array($this, 'page_search') );
		add_action( 'wp_ajax_optinoid_mailchimp_lists', array($this, 'mailchimp_lists') );
		add_action( 'wp_ajax_optinoid_infusionsoft_lists', array($this, 'infusionsoft_lists') );
		add_action( 'wp_ajax_optinoid_sendgrid_lists', array($this, 'sendgrid_lists') );
		add_action( 'wp_ajax_optinoid_campaignmonitor_lists', array($this, 'campaignmonitor_lists') );
		
	}
	
	public function load_admin_scripts() {
	
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		
		wp_enqueue_script('optinoid-admin', plugin_dir_url(dirname(__FILE__)). 'js/optinoid-admin.min.js', array('jquery'), null, true);
		
		wp_register_style('optinoid-admin', plugin_dir_url(dirname(__FILE__)).'/css/optinoid-admin.css', array(), '', 'all');
	
		wp_enqueue_style('optinoid-admin');
	}
	
	
	public function add_optinoid_column( $defaults ) {
	
		$columns = array();
		
		foreach( $defaults as $k=>$v ) {
			$columns[$k] = $v;
			if($k == 'title') {
				$columns['optin_type'] = 'Optin Type';
				$columns['optin_stats'] = 'Stats';
				$columns['optin_conversion'] = 'Conversion Rate';
			}
		}
	
		
		return $columns;
	}
	
	public function add_optinoid_column_content( $column_name, $post_id ) {
		global $wpdb;
		if ($column_name == 'optin_type') {
			echo ucfirst(get_post_meta($post_id, 'optinoid_media', true));
		}
		if ($column_name == 'optin_stats') {
			$stats = $wpdb->get_row($wpdb->prepare("SELECT views, clicks FROM ".$wpdb->prefix."optinoid_stats WHERE optinoid_id = %d", $post_id));
	    	echo (!empty($stats->views)?$stats->views:0).' views<br />'.(!empty($stats->clicks)?$stats->clicks:0).' conversions';
		}
		if ($column_name == 'optin_conversion') {
			$stats = $wpdb->get_row($wpdb->prepare("SELECT views, clicks FROM ".$wpdb->prefix."optinoid_stats WHERE optinoid_id = %d", $post_id));
			if(!empty($stats->views)) {
			 	echo number_format(($stats->clicks*100)/$stats->views, 2).'%';
			} else {
				echo 'N/A';
			}
		}
	}
	
	public function create_metaboxes() {
		add_meta_box( 'optinoid-options-metabox', 'Optin Settings', array($this, 'metabox'), 'optinoids', 'normal', 'high');
	}
	
	public function metabox() {
		global $post;
		
		wp_nonce_field( plugin_basename( __FILE__ ), 'optinoid-metabox_nonce');
		
		?>
		
		<div id="optinoid-tabs">
			<ul>
			    <li><a href="#appearance-tab">Appearance</a></li>
			    <li><a href="#visibility-tab">Visibility</a></li>
			    <li><a href="#form-tab">Form Settings</a></li>
			    <li><a href="#integrations-tab">Integrations</a></li>
			</ul>
			  
			<table style="width: 100%;" id="appearance-tab">
				<tr>
					<td style="width: 50%;">
						<p>
							<legend style="margin-bottom: 5px;"><strong>Opt-in Type:</strong></legend>
							<?php
								$optin_types = array(
									array('name' => 'popup', 'label' => 'Modal Popup'),
									array('name' => 'welcomemat', 'label' => 'Welcome Mat'),
									array('name' => 'welcomemat-fb', 'label' => 'Welcome Mat with Floating Bar'),
									array('name' => 'floating-bar', 'label' => 'Floating Bar'),
//									array('name' => 'inline', 'label' => 'Inline (shortcode)')
								);
								$post_meta = get_post_meta($post->ID, 'optinoid_type', true);
							?>
							<select name="optinoid_type" id="optinoid_type" style="width: 75%;">
							<?php foreach($optin_types as $k=>$v): ?>
							<option value="<?php echo $v['name']; ?>"<?php if(isset($post_meta) && $post_meta == $v['name']) echo ' selected="selected"'; ?> /><?php echo $v['label']; ?></option>
							<?php endforeach; ?>
							</select>
						</p>
					</td>
					<td>
						<p>
							<legend style="margin-bottom: 5px;"><strong>Show Opt-in on:</strong></legend>
							<?php
								$optin_media = array(
									array('name' => 'desktop', 'label' => 'Desktop'),
									array('name' => 'mobile', 'label' => 'Mobile'),
								);
								$optinoid_media = get_post_meta($post->ID, 'optinoid_media', true);
							?>
							<input type="hidden" name="optinoid_media" value="desktop" />
							<?php foreach($optin_media as $k=>$v): ?>
							<label><input type="radio" name="optinoid_media" value="<?php echo $v['name']; ?>"<?php if(isset($optinoid_media) && $optinoid_media == $v['name']) echo ' checked="checked"'; ?> /><?php echo $v['label']; ?></label> &nbsp;&nbsp;&nbsp; 
							<?php endforeach; ?>
						</p>
					</td>
				</tr>
				<tr id="optinoid-shortcode" class="hidden">
					<td style="width: 50%;">
						<p>
							<legend style="margin-bottom: 5px;"><strong>Opt-in Shortcode:</strong></legend>
							<input type="text" readonly value="[optinoid id=<?php echo $post->ID ?>]" style="width: 75%;" />
						</p>
					</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td style="width: 100%;" colspan="2">
							<legend style="margin-bottom: 5px;"><strong>Opt-in Theme:</strong></legend>
							<input type="hidden" name="optinoid_theme" value="<?php echo reset($this->themes)['id']; ?>" />
							
							<?php $optinoid_theme = get_post_meta($post->ID, 'optinoid_theme', true); ?>
							
							<?php foreach($this->themes as $v): ?>
							<label class="optinoid-theme<?php if(isset($optinoid_theme) && $optinoid_theme == $v['id']) echo ' active'; ?>"><input type="radio" name="optinoid_theme" value="<?php echo $v['id']; ?>"<?php if(isset($optinoid_theme) && $optinoid_theme == $v['id']) echo ' checked="checked"'; ?> /><img src="<?php echo plugin_dir_url(dirname(__FILE__)).'img/theme-'.$v['id'].'.png'; ?>" alt="<?php echo $v['label']; ?>" />
								<span><?php echo $v['label']; ?></span>
							</label> 
							<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<td style="width: 50%;">
						<p>
							<?php $post_meta = get_post_meta($post->ID, 'optinoid_bg_color', true); ?>
							<label for="optin_bg_color"><strong>Background Color</strong> <em>(HEX format)</em></label><br />
							<input type="text" name="optinoid_bg_color" id="optinoid_bg_color" placeholder="#FFF" style="width: 75%;" value="<?php echo isset($post_meta)?$post_meta:''; ?>" />
						</p>	
					</td>
					<td><p>
						<?php $post_meta = get_post_meta($post->ID, 'optinoid_text_color', true); ?>
						<label for="optin_text_color"><strong>Text Color</strong> <em>(HEX format)</em></label><br />
						<input type="text" name="optinoid_text_color" id="optinoid_text_color" placeholder="#000000" style="width: 75%;" value="<?php echo isset($post_meta)?$post_meta:''; ?>" />
					</p></td>
				</tr>
				<tr>
					<td style="width: 50%;">
						<p>
							<?php $post_meta = get_post_meta($post->ID, 'optinoid_button_color', true); ?>
							<label for="optin_button_color"><strong>Button Color</strong> <em>(HEX format)</em></label><br />
							<input type="text" name="optinoid_button_color" id="optinoid_button_color" placeholder="#000" style="width: 75%;" value="<?php echo isset($post_meta)?$post_meta:''; ?>" />
						</p>	
					</td>
					<td><p>
						<?php $post_meta = get_post_meta($post->ID, 'optinoid_button_text_color', true); ?>
						<label for="optin_button_text_color"><strong>Button Text Color</strong> <em>(HEX format)</em></label><br />
						<input type="text" name="optinoid_button_text_color" id="optinoid_button_text_color" placeholder="#FFF" style="width: 75%;" value="<?php echo isset($post_meta)?$post_meta:''; ?>" />
					</p></td>
				</tr>
				<tr>
					<td style="width: 50%;">
						<p>
							<?php $post_meta = get_post_meta($post->ID, 'optinoid_arrow_color', true); ?>
							<label for="optin_arrow_color"><strong>Arrow Color</strong> <em>(if applicable)</em></label><br />
							<input type="text" name="optinoid_arrow_color" id="optin_arrow_color" style="width: 75%;" value="<?php echo isset($post_meta)?$post_meta:''; ?>" />
						</p>	
					</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td style="width: 50%;">
						<p>
							<?php $post_meta = get_post_meta($post->ID, 'optinoid_delay', true); ?>
							<label for="optin_delay"><strong>Optin Delay</strong> <em>(in miliseconds, just for popup &amp; welcome mat)</em></label><br />
							<input type="text" name="optinoid_delay" id="optin_delay" style="width: 75%;" value="<?php echo isset($post_meta)?$post_meta:2000; ?>" />
						</p>	
					</td>
					<td>&nbsp;</td>
				</tr>
				
				<tr class="floating-bar hidden">
					<td style="width: 50%;">
						<p>
							<?php $post_meta = get_post_meta($post->ID, 'optinoid_fb_text', true); ?>
							<label for="optinoid_fb_text"><strong>Floating Bar Text</strong></label><br />
							<input type="text" name="optinoid_fb_text" id="optinoid_fb_text" style="width: 75%;" value="<?php echo $post_meta; ?>" />
						</p>	
					</td>
					<td>
						<p>
							<legend style="margin-bottom: 5px;"><strong>Floating Bar Position:</strong></legend>
							<?php
								$optin_positions = array(
									array('name' => 'top', 'label' => 'Top'),
									array('name' => 'bottom', 'label' => 'Bottom'),
								);
								$optinoid_fb_position = get_post_meta($post->ID, 'optinoid_fb_position', true);
							?>
							<input type="hidden" name="optinoid_fb_position" value="top" />
							<?php foreach($optin_positions as $k=>$v): ?>
							<label><input type="radio" name="optinoid_fb_position" value="<?php echo $v['name']; ?>"<?php if(isset($optinoid_fb_position) && $optinoid_fb_position == $v['name']) echo ' checked="checked"'; ?> /><?php echo $v['label']; ?></label> &nbsp;&nbsp;&nbsp; 
							<?php endforeach; ?>
						</p>
					</td>
				</tr>
				
			</table>
			
			<table style="width: 100%;" id="visibility-tab">
				<tr>
					<td colspan="2">
					<p>
						<strong>Load optin globally</strong></p>
					<p>
						<?php $optinoid_load_globally = get_post_meta($post->ID, 'optinoid_load_globally', true); ?> 
						
						<input type="hidden" name="optinoid_load_globally" id="optinoid_load_globally" value="<?php echo !empty($optinoid_load_globally)?1:0; ?>" />
						
						<label><input type="checkbox"  id="load_globally"<?php if(!empty($optinoid_load_globally)) echo ' checked="checked"'; ?> /> If checked will load optin on all pages of the site</em></label>
					</p>
					</td>
				</tr>
				<tr class="optin-not-globally" style="display: none;">
					<td colspan="2">
						<p>
							<strong>Load optin just on these pages</strong></p>
						<p>
							<input type="text" id="optin_page_search" placeholder="Enter to search ..." />
						</p>
						<?php
							$optin_pages = get_post_meta($post->ID, 'optinoid_pages', true);
						 ?>
						<input type="hidden" name="optinoid_pages[]" value="0" /> 
						<ul class="optin_pages">
							<?php if(!empty($optin_pages)): ?>
								<?php
									$optin_pages = get_posts(array(
										'post_type' => 'page',
										'post__in' => $optin_pages
									));
									if(!empty($optin_pages)):
								 ?>
								<?php foreach($optin_pages as $v): ?>
								<li class="page-<?php echo $v->ID ?>"><label><input type="checkbox" name="optinoid_pages[]" value="<?php echo $v->ID ?>" checked="checked" /><?php echo $v->post_title; ?></label></li>
								<?php endforeach; ?>
								<?php endif; ?>
							<?php endif; ?>
						</ul>
					</td>
				</tr>
				<tr class="optin-not-globally" style="display: none;">
					<td colspan="2">
						<p>
							<strong>Load optin on blog categories</strong></p>
							<?php $cats = get_categories(array(
								'hide_empty' => 0
							)); 
							$optin_categories = get_post_meta($post->ID, 'optinoid_categories', true);
							?>
							<p>
							<input type="hidden" name="optinoid_categories[]" value="0" />
							<?php foreach($cats as $v): ?>
							<label><input type="checkbox" name="optinoid_categories[]" value="<?php echo $v->term_id; ?>"<?php if(is_array($optin_categories) && in_array($v->term_id, $optin_categories)) echo ' checked="checked"'; ?> /> <?php echo $v->name; ?></label><br />
							<?php endforeach; ?>
						</p>
					</td>
				</tr>
				<tr class="optin-not-globally" style="display: none;">
					<td colspan="2">
						<p>
							<strong>Load optin on post types</strong></p>
							<?php $post_types = get_post_types(array(
								'public' => true
							));
							$optin_post_types = get_post_meta($post->ID, 'optinoid_post_types', true);
							?>
							<p>
							<input type="hidden" name="optinoid_post_types[]" value="0" />
							<label><input type="checkbox" name="optinoid_post_types[]" value="home"<?php if(is_array($optin_post_types) && in_array('home', $optin_post_types)) echo ' checked="checked"'; ?> /> Home Page</label><br />
							<label><input type="checkbox" name="optinoid_post_types[]" value="archive"<?php if(is_array($optin_post_types) && in_array('archive', $optin_post_types)) echo ' checked="checked"'; ?> /> Archives</label><br />
							<label><input type="checkbox" name="optinoid_post_types[]" value="search"<?php if(is_array($optin_post_types) && in_array('search', $optin_post_types)) echo ' checked="checked"'; ?> /> Search</label><br />
							<?php foreach($post_types as $v): $v = get_post_type_object($v); ?>
								<label><input type="checkbox" name="optinoid_post_types[]" value="<?php echo $v->name; ?>"<?php if(is_array($optin_post_types) && in_array($v->name, $optin_post_types)) echo ' checked="checked"'; ?> /> <?php echo $v->labels->name; ?></label><br />
							<?php endforeach; ?>
						</p>
					</td>
				</tr>
			</table>
		
		
			<table style="width: 100%;" id="form-tab">
				<tr>
					<td style="width: 50%;">
						<p>
							<?php $post_meta = get_post_meta($post->ID, 'optinoid_button_text', true); ?>
							<label for="optin_button_text"><strong>Opt-in Button Text</strong></label><br />
							<input type="text" name="optinoid_button_text" id="optin_button_text" style="width: 75%;" value="<?php echo isset($post_meta['optinoid_button_text'])?$post_meta['optinoid_button_text']:'Subscribe'; ?>" />
						</p>	
					</td>
					<td>
						<p>
							<legend style="margin-bottom: 5px;"><strong>Opt-in Form Fields:</strong></legend>
							<?php
								$optin_fields = array(
									array('name' => 'name', 'label' => 'Name'),
									array('name' => 'email', 'label' => 'Email'),
								);
								$post_meta = get_post_meta($post->ID, 'optinoid_fields', true);
							?>
							<input type="hidden" name="optinoid_fields[]" value="email" />
							<?php foreach($optin_fields as $k=>$v): ?>
							<label><input type="checkbox" name="optinoid_fields[]" value="<?php echo $v['name']; ?>"<?php if(is_array($post_meta) && in_array($v['name'], $post_meta)) echo ' checked="checked"'; ?><?php if($v['name'] == 'email') echo ' disabled checked="checked"'; ?> /><?php echo $v['label']; ?></label> &nbsp;&nbsp;&nbsp; 
							<?php endforeach; ?>
						</p>
					</td>
				</tr>
				<tr>
					<td width="50%">
						<p>
							<?php $optin_success_url = get_post_meta($post->ID, 'optinoid_success_url', true); ?>
							<label for="optin_success_url"><strong>Success URL</strong> <em>(redirect here after successful submit)</em></label><br />
							<input type="text" name="optinoid_success_url" id="optinoid_success_url" style="width: 75%;" value="<?php echo isset($optin_success_url)?$optin_success_url:''; ?>" />
						</p>
					</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td width="50%">
						<p>
							<?php $optin_form_text = get_post_meta($post->ID, 'optinoid_form_text', true); ?>
							<label for="optin_form_text"><strong>Form Text</strong> <em>(to be displayed before form)</em></label><br />
							<input type="text" name="optinoid_form_text" id="optinoid_form_text" style="width: 75%;" value="<?php echo isset($optin_form_text)?$optin_form_text:''; ?>" />
						</p>
					</td>
					<td>&nbsp;</td>
				</tr>
			</table>
			
			<table style="width: 100%;" id="integrations-tab">
				<tr>
					<td style="width: 50%;">
						<p>
							<?php
								$integrations = array(
									array('id' => 'infusionsoft', 'label' => 'Infusionsoft'),
									array('id' => 'mailchimp', 'label' => 'Mailchimp'),
									array('id' => 'sendgrid', 'label' => 'Sendgrid'),
									array('id' => 'campaignmonitor', 'label' => 'CampaignMonitor'),
								);
							?>
							<?php $post_meta = get_post_meta($post->ID, 'optinoid_integration', true); ?>
							<label for="optin_integration"><strong>Integration</strong> <em>(email provider)</em></label><br />
							<select name="optinoid_integration" id="optinoid_integration" style="width: 75%;">
								<option value="">- please select -</option>
								<?php foreach($integrations as $v): ?>
									<option value="<?php echo $v['id']; ?>"<?php if($post_meta == $v['id']) echo ' selected="selected"'; ?>><?php echo $v['label']; ?></option>
								<?php endforeach; ?>
							</select>
						</p>
					</td>
					<td id="list-select">
						<label>&nbsp;</label><br />
						<?php $optinoid_list_id = get_post_meta($post->ID, 'optinoid_list_id', true); ?>
						<input type="hidden" name="optinoid_list_id" value="<?php echo $optinoid_list_id; ?>" />
						<select name="optinoid_list_id" class="hidden" style="width: 75%;">
							<option value="">- please select -</option>
						</select>
					</td>
				</tr>
			</table>

		</div>
		<?php
		
	}
	
	public function save_optinoid_info($post_id) {
	
		global $wpdb;
		
		// check post type
		if( get_post_type($post_id) != 'optinoids' )
			return;
		
		// check permissions
		if ( !current_user_can('edit_post', $post_id) )
			return;
			
		// check if autosave
		if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return;
		
		// check if post revision
		if( wp_is_post_revision($post_id) )
			return;
		
		// verify nonce
		if (empty($_POST['optinoid-metabox_nonce']) || !wp_verify_nonce( $_POST['optinoid-metabox_nonce'], plugin_basename( __FILE__ ) ) )
			return;
		
			
		if(isset($_POST)) {
			
			// find optinoid keys
			$optinoid_keys = array_filter(array_keys($_POST), function($key) {
			    return strpos($key, 'optinoid_') === 0;
			});
			
			
			// loop through values
			if(!empty($optinoid_keys)) {
				foreach($optinoid_keys as $key) {
				
					$val = $_POST[$key];
					
					if(is_array($val) && empty($val[0])) {
						// remove first key if = 0 - it's the fallback field
						unset($val[0]);
						// reset keys
						$val = array_values($val);
					}
					
					// update post meta
					update_post_meta($post_id, $key, $val);
						
				}
			}
			
			
		}
		
			
		// check if stats are generated for this post_id
		$exists = $wpdb->get_var($wpdb->prepare("SELECT optinoid_id FROM ".$wpdb->prefix."optinoid_stats WHERE optinoid_id = %d", $post_id));
			
		if(empty($exists)) {
			// attempt to create
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."optinoid_stats (optinoid_id) VALUES (%d)", $post_id));
		}
			
	}
	
	public function page_search() {
		global $wpdb;
		
		$results = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->prefix."posts WHERE post_type='page' AND post_status='publish' AND  post_title LIKE '".esc_sql( $wpdb->esc_like($_GET['term']) ) ."%'");
		
		
		$response = array();
		
		if(!empty($results)) {
			foreach($results as $k=>$v) {
				$response[$k] = array('label' => $v->post_title, 'id' => $v->ID);	
			}
		}
		
		wp_send_json($response);
		
	}
	
	function mailchimp_lists() {
		
		// get options
		$options = get_option('optinoid_options');
		// get mailchimp api
		$mailchimp_api = $options['mailchimp_api'];
		
		$dc = substr($mailchimp_api, strrpos($mailchimp_api, '-')+1);
		
		$endpoint = 'https://'.$dc.'.api.mailchimp.com/3.0/';
		
//		$request = /
		
		$response = wp_remote_get($endpoint.'lists', array(
			'headers' => array(
				'Authorization' => 'apikey '.$mailchimp_api
			)
		));
		
		$response = json_decode($response['body']);
		$lists = array();
		
		if(!empty($response->lists)) {
			foreach($response->lists as $k=>$v) {
				$lists[$k]['id'] = $v->id;
				$lists[$k]['name'] = $v->name;
			}
		} elseif (!empty($response->status)) {
			wp_send_json(array('error' => $response->title));
		}
		
		// return json response
		if(!empty($lists)) {
			wp_send_json($lists);
		}
		
		wp_die();
		
	}
	
	function sendgrid_lists() {
			
		// get options
		$options = get_option('optinoid_options');
		
		// get mailchimp api
		$sendgrid_api_user = $options['sendgrid_api_user'];
		$sendgrid_api_key = $options['sendgrid_api_key'];
			
		$endpoint = 'https://api.sendgrid.com/api/newsletter/';
			
			
		$response = wp_remote_post($endpoint.'lists/get.json', array(
			'body' => array(
				'api_user' => $sendgrid_api_user,
				'api_key' => $sendgrid_api_key
			)
		));
			
		$response = json_decode($response['body']);
		$lists = array();
//			
		if(empty($response->error)) {
			foreach($response as $k=>$v) {
				$lists[$k]['id'] = $v->list;
				$lists[$k]['name'] = $v->list;
			}
		} else {
			wp_send_json($response);
		}
			
		// return json response
		if(!empty($lists)) {
			wp_send_json($lists);
		}
			
		wp_die();
			
	}
	
	
	function infusionsoft_lists() {
	
		// Include WordPress libraries to handle XML-RPC
		require_once ABSPATH . '/wp-includes/class-IXR.php';
		require_once ABSPATH . '/wp-includes/class-wp-http-ixr-client.php';
	
		// get options
		$options = get_option('optinoid_options');
		
		// Initialize the client
		$client = new WP_HTTP_IXR_Client( 'https://'.$options['infusionsoft_subdomain'].'.infusionsoft.com/api/xmlrpc' );
		
		
		
		$client->query('WebFormService.getMap', $options['infusionsoft_api']);

		$response = $client->getResponse();
		
		
		// empty list array
		$lists = array();
		
		if(!empty($response['faultCode'])) {
			wp_send_json(array('error' => $response['faultString']));
		} else {
			if(!empty($response)) {
				foreach($response as $k=>$v) {
					$lists[$k]['id'] = $k;
					$lists[$k]['name'] = $v;
				}
			}
			
			wp_send_json($lists);
		}
		
		wp_die();
		
	}
	
	function campaignmonitor_lists() {
		
		// get options
		$options = get_option('optinoid_options');
		
		$endpoint = ' https://api.createsend.com/api/v3.1/clients/'.$options['campaignmonitor_api_client'].'/lists.json';
		
		$response = wp_remote_get($endpoint, array(
			'headers' => array(
				'Authorization' => 'Basic '.base64_encode($options['campaignmonitor_api'])
			)
		));
		
		$response = json_decode($response['body']);
		
		// empty list array
		$lists = array();
		
		if(!empty($response->Code)) {
			wp_send_json(array('error' => $response->Message));
		} else {
			if(!empty($response)) {
				foreach($response as $k=>$v) {
					$lists[$k]['id'] = $v->ListID;
					$lists[$k]['name'] = $v->Name;
				}
			}
			
			wp_send_json($lists);
		}
		
		wp_die();
		
	}
	
}