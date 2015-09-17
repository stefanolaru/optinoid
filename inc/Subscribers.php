<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Optinoid_Subscribers {
	
	function __construct() {
		add_action( 'admin_menu', array($this, 'add_admin_menu') );	
		add_action( 'admin_init', array($this, 'export_csv' ));
		
	}
	
	function add_admin_menu() {
		add_submenu_page( 'optinoid-menu', 'Optinoid Subscribers', 'Subscribers', 'manage_options', 'optinoid-subscribers', array($this, 'subscribers') );
		
		add_submenu_page( 'optinoid-menu', 'Export Subscribers', 'Export', 'manage_options', 'optinoid-export', array($this, 'export') );
	}
	
	function subscribers() {
		//
		$os = new Optinoid_Subscribers_List_Table();
		
		?>
		<div class="wrap">
				<h1>Subscribers</h1>
					
					<form method="get">
					    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
					  	<?php $os->search_box('Search Subscribers', 'subscribers'); ?>
					</form>
		
					<div id="optinoid-subscribers">
						<div id="post-body" class="metabox-holder">
							<div id="post-body-content">
								<div class="meta-box-sortables ui-sortable">
									<form method="post">
									<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
										<?php
											$os->prepare_items();
		    								$os->display(); 
										?>
									</form>
								</div>
							</div>
						</div>
					<br class="clear">
				</div>
			</div>
		<?php
		
	}
	
	public function export() {
		?>
		<div class="wrap">
			<h1>Export Subscribers</h1>
			
			<form method="post"> 
				<input type="hidden" name="optinoid-export" value="1" />
				<?php submit_button('Export Subscribers to CSV'); ?>
			</form>
				
		</div>
		<?php
	}
	
	public function export_csv() {
	
		if ( !is_super_admin() ) {
			return;
		}
	
		global $wpdb;
		
		if(empty($_POST['optinoid-export'])) return;
		
		
		$sql = "SELECT t1.*, t2.post_title FROM ".$wpdb->prefix."optinoid_subscribers as t1 LEFT JOIN ".$wpdb->prefix."posts as t2 ON t1.optinoid_id = t2.ID ORDER BY t1.id DESC";
		
		$result = $wpdb->get_results($sql, 'ARRAY_A');
		
		$csv = array();
		
		if(!empty($result)) {
			foreach($result as $k=>$v) {
				$csv[$k]['email'] = $v['email'];
				$csv[$k]['name'] = $v['name'];
				$csv[$k]['created'] = $v['created'];
				$csv[$k]['optin'] = $v['post_title'];
			}
		}
		
		$output = fopen("php://output",'w') or wp_die("Can't open php://output");
		// set headers
		header("Content-Type:application/csv"); 
		header("Content-Disposition:attachment;filename=optinoid-subscribers.csv"); 
		header( 'Expires: 0' );
		header( 'Pragma: public' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		// create csv
		if(!empty($csv)) {
			fputcsv($output, array_keys($csv[0]));
			foreach($csv as $row) {
			    fputcsv($output, $row);
			}
		}
		
		fclose($output) or wp_die("Can't close php://output");
		
		exit;
		
	}
	
}

class Optinoid_Subscribers_List_Table extends WP_List_Table {

    function __construct() {
    
    	parent::__construct( array(
    		'singular' => __('Subscriber', 'optinoid'),
    		'plural' => __('Subscribers', 'optinoid'),
	    	'ajax' => false,
	    	'screen' => null
		));
    	
    }
    
    // 
    public function get_subscribers( $per_page = 20, $page_number = 1 ) {
    
    	global $wpdb;
    	
    	$sql = "SELECT t1.*, t2.post_title FROM ".$wpdb->prefix."optinoid_subscribers as t1 LEFT JOIN ".$wpdb->prefix."posts as t2 ON t1.optinoid_id = t2.ID";
    
		if(!empty($_REQUEST['s'])) {
			$sql .= $wpdb->prepare(" WHERE t1.email LIKE '%%%s%%' OR t1.name LIKE '%%%s%%' OR t2.post_title LIKE '%%%s%%'", $_REQUEST['s'], $_REQUEST['s'], $_REQUEST['s']);
		}
    
    	if ( ! empty( $_REQUEST['orderby'] ) ) {
    		$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
    		$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
    	}
    
    	$sql .= " LIMIT $per_page";
    	$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
    
    
    	$result = $wpdb->get_results($sql, 'ARRAY_A');
    
    	return $result;
    	
    }
    
    public function count_subscribers() {
    	//
		global $wpdb;
		
    	$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."optinoid_subscribers";
    	//
    	return $wpdb->get_var($sql);
    }
    
    public function  delete_subscriber($id) {
    
    	global $wpdb;
    
    	if(empty($id)) return;
    	
    	$wpdb->query($wpdb->prepare( "DELETE FROM ".$wpdb->prefix."optinoid_subscribers WHERE id=%s", $id ));
    }
    	
    public function no_items() {
    	_e( 'No subscribers avaliable.', 'optinoid' );
    }
    
    
    public function column_default( $item, $column_name ) {
    	switch ( $column_name ) {
    		case 'optin':
    			return $item[ 'post_title' ];
    		case 'date':
    			return date('M jS  Y, h:i a', strtotime($item['created']));
    		default:
    			return $item[ $column_name ];
    	}
    }
    
    function column_cb( $item ) {
    	return sprintf(
    		'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
    	);
    }
    
    function column_email( $item ) {
    
   		$delete_nonce = wp_create_nonce( 'optinoid_delete_subscriber' );
   		// add nonce field
   		wp_nonce_field('optinoid_delete_subscriber');
    
   		$title = '<strong>' . $item['email'] . '</strong>';
    	
   		$actions = array(
   			'delete' => sprintf( '<a href="?page=%s&action=%s&subscriber_id=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
   		);
   		
   		return $title . $this->row_actions( $actions );
   		
   	}
    
    function get_columns() {
    	$columns = array(
    		'cb'      => '<input type="checkbox" />',
    		'email' => __( 'Email', 'optinoid' ),
    		'name'    => __( 'Name', 'optinoid' ),
    		'date' => __('Subscribed', 'optinoid'),
    		'optin'    => __( 'Optin', 'optinoid' )
    	);
    
    	return $columns;
    }
    
    
   	public function get_sortable_columns() {
   		$sortable_columns = array(
   			'email' => array( 'email' , true ),
   			'name' => array( 'name', true ),
   			'date' => array( 'created', true ),
   			'optin' => array( 'optinoid_id', true ) 
   		);
   
   		return $sortable_columns;
   	}
   	
   	public function get_bulk_actions() {
   		$actions = array(
   			'bulk-delete' => 'Delete'
   		);
   	
   		return $actions;
   	}
   	
    
    public function prepare_items() {
    
    	global $wpdb;
    
    	$per_page = 20;
    	
    	$this->process_bulk_action();
    	
    	$columns = $this->get_columns();
    	$hidden = array();
    	$sortable = $this->get_sortable_columns();
    	
    	$this->_column_headers = array($columns, $hidden, $sortable);
    
		
		$total_items = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."optinoid_subscribers");
		
		$this->set_pagination_args(array(
    		'total_items' => $total_items,
    		'per_page'    => $per_page
    	));

    	$this->items = $this->get_subscribers( $per_page, 1 );

    }
    
    public function process_bulk_action() {
    
    		//Detect when a bulk action is being triggered...
    		if ( 'delete' === $this->current_action() ) {
    
    			// In our file that handles the request, verify the nonce.
    			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
    			
    			if ( ! wp_verify_nonce( $nonce, 'optinoid_delete_subscriber' ) ) {
    				die( 'He he, no way!' );
    			} else {
    				
    				if(!empty($_GET['subscriber_id'])) {
    					$this->delete_subscriber(absint($_GET['subscriber_id']));
    				}

    			}
    
    		}
    		
    		// If the delete bulk action is triggered
    		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
    		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
    		) {
    			$delete_ids = esc_sql( $_POST['bulk-delete'] );
    			
    			// loop over the array of record IDs and delete them
    			foreach ( $delete_ids as $id ) {
    				$this->delete_subscriber( $id );
    
    			}

    		}
    }
    
    
    
    public function search_box($text, $input_id) {
    	$input_id = $input_id . '-search-input';
    	
    	$total_items = $this->count_subscribers();
    	
    	if(empty($total_items)) return;
    	
    	?>
    	<p class="search-box">
    		<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
    		<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
    		<?php submit_button( $text, 'button', '', false, array('id' => 'search-submit') ); ?>
    	</p>
    	<?php
    }
    
    /*
    public function extra_tablenav($which) {
    	global $wpdb;
    	
    	$sql = "SELECT COUNT(t1.id) as counter, t1.optinoid_id, t2.post_title FROM ".$wpdb->prefix."optinoid_subscribers as t1 LEFT JOIN ".$wpdb->prefix."posts as t2 ON t1.optinoid_id = t2.ID GROUP BY 2 ORDER BY 1 ASC";
    	
    	// get all optinoids
    	$result = $wpdb->get_results($sql, 'ARRAY_A');
    	
    	?>
    	<div class="alignleft actions bulkactions">
	    	<select id="filter-subscribers" name="optinoid_id">
	    		<option>- filter by optin -</option>
	    		<?php foreach($result as $v): ?>
	    		<option value="<?php echo $v['optinoid_id']; ?>"><?php echo $v['post_title']; ?> (<?php echo $v['counter']; ?>)</option>
	    		<?php endforeach; ?>
	    	</select>
    	</div>
    	<?php
    	
    	
    }
    */
    
}