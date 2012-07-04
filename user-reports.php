<?php
/*
Plugin Name: User Reports
Plugin URI: 
Description: 
Author: Paul Menard (Incsub)
Version: 1.0.0
Author URI: http://premium.wpmudev.org/
WDP ID: 679162
Text Domain: user-reports
Domain Path: languages

Copyright 2012 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
///////////////////////////////////////////////////////////////////////////

if (!defined('USER_REPORTS_I18N_DOMAIN'))
	define('USER_REPORTS_I18N_DOMAIN', 'user-reports');

require_once( dirname(__FILE__) . '/lib/class-user-reports-posts-list-table.php');
require_once( dirname(__FILE__) . '/lib/class-user-reports-comments-list-table.php');
//require_once( dirname(__FILE__) . '/lib/class-user-reports-logins-list-table.php');

class UserReports {
		
	private $_pagehooks = array();	// A list of our various nav items. Used when hooking into the page load actions.
	private $_messages 	= array();	// Message set during the form processing steps for add, edit, udate, delete, restore actions
	private $_settings	= array();	// These are global dynamic settings NOT stores as part of the config options
	
	private $_admin_header_error;	// Set during processing will contain processing errors to display back to the user
	
	private $_admin_panels;

	private $_filters = array();	// Set during processfilters(). 


	/**
	 * The old-style PHP Class constructor. Used when an instance of this class 
 	 * is needed. If used (PHP4) this function calls the PHP5 version of the constructor.
	 *
	 * @since 1.0.0
	 * @param none
	 * @return self
	 */
    function UserReports() {
        __construct();
    }


	/**
	 * The PHP5 Class constructor. Used when an instance of this class is needed.
	 * Sets up the initial object environment and hooks into the various WordPress 
	 * actions and filters.
	 *
	 * @since 1.0.0
	 * @uses $this->_settings array of our settings
	 * @uses $this->_messages array of admin header message texts.
	 * @param none
	 * @return self
	 */
	function __construct() {
		
		$this->_settings['VERSION'] 				= '1.0.0';
		$this->_settings['MENU_URL'] 				= 'users.php?page=';
		$this->_settings['PLUGIN_URL']				= WP_CONTENT_URL . "/plugins/". basename( dirname(__FILE__) );
		$this->_settings['PLUGIN_BASE_DIR']			= dirname(__FILE__);
		$this->_settings['admin_menu_label']		= __( "User Reports", USER_REPORTS_I18N_DOMAIN ); 
		
		$this->_settings['options_key']				= "user-report-". $this->_settings['VERSION']; 
		
		$this->_admin_header_error 					= "";		
		
		add_action('admin_notices', array(&$this, 'user_reports_admin_notices_proc') );
		add_action('network_admin_notices', array(&$this, 'user_reports_admin_notices_proc') );

		/* Setup the tetdomain for i18n language handling see http://codex.wordpress.org/Function_Reference/load_plugin_textdomain */
        load_plugin_textdomain( USER_REPORTS_I18N_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		/* Standard activation hook for all WordPress plugins see http://codex.wordpress.org/Function_Reference/register_activation_hook */
        register_activation_hook( __FILE__, array( &$this, 'user_reports_plugin_activation_proc' ) );


		/* Register stadnard admin actions */
		add_action( 'admin_menu', 			array(&$this,'user_reports_admin_menu_proc') );	
		add_action( 'user_admin_menu', 		array(&$this,'user_reports_admin_menu_proc') );	
		add_action( 'network_admin_menu', 	array(&$this,'user_reports_admin_menu_proc') );			
		add_action( 'wp_login', 			array(&$this,'user_reports_wp_login_proc'), 1, 2 );

		/* Add our 'Reports' to the User listing rows */
		add_filter( 'user_row_actions', array(&$this,'user_reports_user_row_actions_proc'), 10, 2);
		add_filter( 'ms_user_row_actions', array(&$this,'user_reports_user_row_actions_proc'), 10, 2);
		
		add_filter( 'the_comments', array(&$this, 'get_comments'));
	}	


	/**
	 * Called when when our plugin is activated. Sets up the initial settings 
	 * and creates the initial Snapshot instance. 
	 *
	 * @since 1.0.0
	 * @uses none
	 * @see $this->__construct() when the action is setup to reference this function
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_plugin_activation_proc() {

	}
	
		
	/**
	 * Hook to add the User row display and add out Reports hover nav element
	 *
	 * @since 1.0.0
	 * @uses $wp_admin_bar
	 * @uses $this->_settings
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_user_row_actions_proc( $actions, $user_object ) {
		
		if ( current_user_can( 'list_users' ) ) {
			if (!isset($actions['user-reports'])) {
				if (is_network_admin()) {
					$actions['user-reports'] = '<a class="submitreports" href="users.php?page=user-reports&amp;user_login='. 
						$user_object->user_login .'">' . __( 'Reports', USER_REPORTS_I18N_DOMAIN ) . '</a>';
				} else {
					$actions['user-reports'] = '<a class="submitreports" href="users.php?page=user-reports&amp;user_id='. 
						$user_object->ID .'">' . __( 'Reports', USER_REPORTS_I18N_DOMAIN ) . '</a>';
					
				}
			}
		}
		return $actions;	
	}
	
		
	/**
	 * Add the new Menu to the Tools section in the WordPress main nav
	 *
	 * @since 1.0.0
	 * @uses $this->_pagehooks 
	 * @see $this->__construct where this function is referenced
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_admin_menu_proc() {

		$this->_pagehooks['user-reports'] = add_users_page( _x("Reports", 'page label', USER_REPORTS_I18N_DOMAIN),
			_x("Reports", 'menu label', USER_REPORTS_I18N_DOMAIN),
			'list_users', 'user-reports', 
			array($this, 'user_reports_admin_show_panel'));

		//site-users-network
		$this->_pagehooks['network-user-reports'] = add_submenu_page( 'users-network', 
			_x("Reports", 'page label', USER_REPORTS_I18N_DOMAIN),
			_x("Reports", 'menu label', USER_REPORTS_I18N_DOMAIN),
			'list_users', 'user-reports', 
			array($this, 'user_reports_admin_show_panel') );

		// Hook into the WordPress load page action for our new nav items. This is better then checking page query_str values.
		add_action('load-'. $this->_pagehooks['user-reports'], 		array(&$this, 'user_reports_on_load_page'));
	}	
	
	
	/**
	 * Capture the login action to record to the usermeta
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_logon - User login name 
	 * @param array $user - User object
	 * @return none
	 */
	function user_reports_wp_login_proc($user_login, $user) {

		global $wpdb;

		if (!$user)
			$user = get_user_by( 'login', $user_login );
		
		if ( (isset($user->ID)) && (intval($user->ID) > 0) && (isset($wpdb->blogid)) && (intval($wpdb->blogid) > 0) ) {
			
			$user_login_data = get_user_meta($user->ID, 'user-reports-login', true);
			if ((!$user_login_data) || (!is_array($user_login_data)))
				$user_login_data = array();
			
			$user_login_data[intval($wpdb->blogid)] = time();
			update_user_meta($user->ID, 'user-reports-login', (array)$user_login_data);
		}
	}
	
	/**
	 * Display our message on the Snapshot page(s) header for actions taken 
	 *
	 * @since 1.0.0
	 * @uses $this->_messages Set in form processing functions
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_admin_notices_proc() {
		
		// IF set during the processing logic setsp for add, edit, restore
		if ( (isset($_REQUEST['message'])) && (isset($this->_messages[$_REQUEST['message']])) ) {
			?><div id='user-report-warning' class='updated fade'><p><?php echo $this->_messages[$_REQUEST['message']]; ?></p></div><?php
		}
		
		// IF we set an error display in red box
		if (strlen($this->_admin_header_error))
		{
			?><div id='user-report-error' class='error'><p><?php echo $this->_admin_header_error; ?></p></div><?php
		}
	}
	
	
	/**
	 * On Load Reports page. Initializes Filters, loads needed scripts and stylesheets
	 *
	 * @since 1.0.0
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_on_load_page() {
		
		if ( ! current_user_can( 'list_users' ) )
			wp_die( __( 'Cheatin&#8217; uh?' ) );

		$this->user_reports_process_filters();

		if ($this->_filters['type'] == "comments") {
			$this->user_reports_table = new User_Reports_Comments_List_Table();
		} /* else if ($this->_filters['type'] == "logins") {
			$this->user_reports_table = new User_Reports_Logins_List_Table();
		} */ else {
			$this->user_reports_table = new User_Reports_Posts_List_Table();
		}		


		if (isset($_GET['user-report-download'])) {
			
			$download_type = esc_attr($_GET['user-report-download']);

			$report_filename = "user-report-";
			if ($this->_filters['type'] == "comments")
				$report_filename .= "comments-";
			else
				$report_filename .= "posts-";
			$report_filename .= date('ymd');

			if ($download_type == "pdf") {
				require_once( dirname(__FILE__) . '/lib/dompdf/dompdf_config.inc.php');

				$this->_filters['per_page'] = 0;
				$this->_filters['doing_reports'] = 'pdf';
				
				$this->user_reports_table->prepare_items($this->_filters);
				$html_for_pdf = $this->user_reports_table->display_pdf();
				if (strlen($html_for_pdf)) {
					//create and output the PDF as a stream (download dialog)
					$dompdf = new DOMPDF();
					$dompdf->set_paper("letter", "landscape");
			  
					$dompdf->load_html($html_for_pdf);
					$dompdf->render();
					$dompdf->stream($report_filename);
					die();
				}
			} else if ($download_type == "csv") {

				$this->_filters['doing_reports'] = 'csv';
				$this->user_reports_table->prepare_items($this->_filters);

				$html_for_csv = $this->user_reports_table->display_csv();
				if (strlen($html_for_csv)) {
										
					header("Content-type: text/csv");
					header("Content-Disposition: attachment; filename=". $report_filename .".csv");
					header("Pragma: no-cache");
					header("Expires: 0");

					echo $html_for_csv;
					die();
				}		
			}
		} else {
			$this->admin_setup_page_display_options();
			$this->user_reports_admin_plugin_help();
		
			/* enqueue our plugin styles */
			wp_enqueue_style( 'jquery.ui.datepicker-css', $this->_settings['PLUGIN_URL'] .'/css/jquery.ui.smoothness/jquery-ui-1.8.18.custom.css', 
				false, '1.8.18');	
			wp_enqueue_style( 'user-reports-admin-stylesheet', $this->_settings['PLUGIN_URL'] .'/css/user-reports-admin-styles.css', 
				false, $this->_settings['VERSION']);	
			
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-datepicker' );

			wp_enqueue_script('user-reports-admin', $this->_settings['PLUGIN_URL'] .'/js/user-reports-admin.js',
				array('jquery', 'jquery-ui-core'), $this->_settings['VERSION']);
		}
	}
	
	
	/**
	 * Setup the context help instances for the user
	 *
	 * @since 1.0.0
	 * @uses $screen global screen instance
	 * @uses $screen->add_help_tab function to add the help sections
	 * @see $this->on_load_main_page where this function is referenced
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_admin_plugin_help() {

		global $wp_version;
				
		$screen = get_current_screen();
		$screen_help_text = array();
		$screen_help_text['user-reports-help-overview'] = '<p>'. __('The User Reports plugins lets you build reports of the activity of your users.', USER_REPORTS_I18N_DOMAIN) .'</p>';
		$screen_help_text['user-reports-help-overview'] .= '<ul>';
		$screen_help_text['user-reports-help-overview'] .= '<li><strong>' . __('Report Type', USER_REPORTS_I18N_DOMAIN) .'</strong> - '. __('From the Report main screen you can select the type or report: Posts or comments', USER_REPORTS_I18N_DOMAIN) .'</li>';
		$screen_help_text['user-reports-help-overview'] .= '<li><strong>' . __('Blogs', USER_REPORTS_I18N_DOMAIN) .'</strong> - '. __('Select which blog to report on. Or you can generate a report for all Blogs', USER_REPORTS_I18N_DOMAIN) .'</li>';
		$screen_help_text['user-reports-help-overview'] .= '<li><strong>' . __('Users', USER_REPORTS_I18N_DOMAIN) .'</strong> - '. __('Select the User from the dropdown. If you are viewing reports from the network admin you can enter the username directly or select the user from the Users listing page first.', USER_REPORTS_I18N_DOMAIN) .'</li>';
		$screen_help_text['user-reports-help-overview'] .= '<li><strong>' . __('Date', USER_REPORTS_I18N_DOMAIN) .'</strong> - '. __('Select a date range for your report. Note you will be limited to 90 days maximum between the start and finish dates.', USER_REPORTS_I18N_DOMAIN) .'</li>';


		$screen_help_text['user-reports-help-overview'] .= '<li><strong>' . __('Export', USER_REPORTS_I18N_DOMAIN) .'</strong> - '. __('Below the report table you can optionally select to export the report to PDF or CSV data.', USER_REPORTS_I18N_DOMAIN) .'</li>';
		
		
		if ( version_compare( $wp_version, '3.3.0', '>' ) ) {
		
			$screen->add_help_tab( array(
				'id'		=> 'users_page_user-reports',
				'title'		=> __('Overview', USER_REPORTS_I18N_DOMAIN ),
				'content'	=> $screen_help_text['user-reports-help-overview']
	    		) 
			);		

		} else {

			if ((isset($_REQUEST['page'])) && ($_REQUEST['page'] == "user-reports")) {
		
				add_contextual_help($screen, $screen_help_text['user-reports-help-overview']);
			}
		}
	}


	/**
	 * Setup the page options. Processes the $_GET passed arguments for the filters. 
	 *
	 * @since 1.0.0
	 *
	 * @param none
	 * @return none
	 */
	function admin_setup_page_display_options() {
		
//		if ($this->_filters['type'] == "comments") {
//			$this->user_reports_table = new User_Reports_Comments_List_Table();
//		} /* else if ($this->_filters['type'] == "logins") {
//			$this->user_reports_table = new User_Reports_Logins_List_Table();
//		} */ else {
//			$this->user_reports_table = new User_Reports_Posts_List_Table();
//		}		

		$current_user_id = get_current_user_id();
		if ($current_user_id) {
			if (isset($_POST['wp_screen_options'])) {

				if ($_POST['wp_screen_options']['value'])
					$option_value = esc_attr($_POST['wp_screen_options']['value']);

				if ($_POST['wp_screen_options']['option'])
					$option_key = esc_attr($_POST['wp_screen_options']['option']);
		
				if ($option_key == 'users_page_user_reports_per_page') {
					if (!isset($option_value))
						$option_value = 20;
			
					update_user_meta($current_user_id, $option_key, $option_value);
				}
			}		
		}	
		
		if (!isset($option_value)) {
			$default_post_per_page = get_option('posts_per_page');
			if ($default_post_per_page)
				$option_value = $default_post_per_page;
			else
				$option_value = 20;
			
		}
		add_screen_option( 'per_page', array('label' => __('per Page', USER_REPORTS_I18N_DOMAIN), 'default' => $option_value) );		
	}
	
	
	/**
	 * This function is the main page wrapper output. 
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_admin_show_panel() {

		?>
		<div id="user-reports-panel" class="wrap user-reports-wrap">
			<?php screen_icon(); ?>
			<h2><?php _ex("User Reports", "User Reports New Page Title", USER_REPORTS_I18N_DOMAIN); ?></h2>
			<?php
			
				if ((!function_exists('comment_indexer_comment_insert_update')) && (!function_exists('post_indexer_post_insert_update'))) {
					?><p><?php echo  __('The User Reports plugin required both', USER_REPORTS_I18N_DOMAIN) .' <a target="_blank" href="http://premium.wpmudev.org/project/post-indexer/">'. __('Post Indexer', USER_REPORTS_I18N_DOMAIN) . '</a> and <a target="_blank" href="http://premium.wpmudev.org/project/comment-indexer/">'. __('Comment Indexer', USER_REPORTS_I18N_DOMAIN) .'</a> '. __('plugins to create the reports.', USER_REPORTS_I18N_DOMAIN); ?></p><?php
				} else {
				
					?>
					<p><?php _ex("To create a report, select the report type, blogs, users, and date range below.", 
						'User Reports page description', USER_REPORTS_I18N_DOMAIN); ?></p>
					<?php $this->user_reports_show_filter_form_bar(); ?>
					<?php
						$this->user_reports_table->prepare_items($this->_filters);
						$this->user_reports_table->display();
								
						$siteurl = get_option('siteurl');
						$href_str = $siteurl ."/wp-admin/users.php?page=user-reports";
						if (isset($_GET['type']))
							$href_str .= "&type=". esc_attr($_GET['type']);
						if (isset($_GET['blog_id']))
							$href_str .= "&blog_id=". esc_attr($_GET['blog_id']);
						if (isset($_GET['date_start']))
							$href_str .= "&date_start=". esc_attr($_GET['date_start']);
						if (isset($_GET['date_end']))
							$href_str .= "&date_end=". esc_attr($_GET['date_end']);
						if (isset($_GET['orderby']))
							$href_str .= "&orderby=". esc_attr($_GET['orderby']);
						if (isset($_GET['order']))
							$href_str .= "&order=". esc_attr($_GET['order']);
						?>
						<a class="button-secondary" href="<?php echo $href_str; ?>&amp;user-report-download=pdf"><?php _e("Download PDF",
						 USER_REPORTS_I18N_DOMAIN); ?></a> <a class="button-secondary" href="<?php echo $href_str; ?>&amp;user-report-download=csv"><?php
						 _e("Download CSV", USER_REPORTS_I18N_DOMAIN); ?></a>
					<?php
				}
			?>
		</div>
		<?php
	}
	
	
	/**
	 * This function checks the $_GET query arguments and sets the object $_filters options accordingly
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_process_filters() {

		global $wpdb;

		if (isset($_GET['type'])) {
			$this->_filters['type'] = esc_attr($_GET['type']);
		} else {
			$this->_filters['type'] = 'posts';
		}
		
		// Validate and Set the Blog selection	
		if (is_network_admin()) {
			$this->_filters['blog_id'] = 0;
		} else {	
			if (isset($_GET['blog_id']))	{
				$this->_filters['blog_id'] = intval($_GET['blog_id']);
				if (($this->_filters['blog_id'] != 0) && ($this->_filters['blog_id'] != $wpdb->blogid)) {
					$this->_filters['blog_id'] = $wpdb->blogid;				
				}
			} else {
				$this->_filters['blog_id'] = $wpdb->blogid;
			}
		}
		// Validate and Set the User selection
		
		// First we need to get a list of all User Ids for the current blog. 
		$user_args = array(
			'number' 	=> 	0,
			'blog_id'	=> 	$wpdb->blogid,
			'fields'	=>	array('ID', 'display_name')
		);

		$this->_filters['blog_users'] = array();
		$this->_filters['blog_users_ids'] = array();
		$wp_user_search = new WP_User_Query( $user_args );
		$users = $wp_user_search->get_results();
		if ($users) {
			$this->_filters['blog_users'] = $users;
			foreach($users as $user) {
				$this->_filters['blog_users_ids'][$user->ID] = $user->ID;
			}
		}
		
		if (is_network_admin()) {
			if (isset($_GET['user_login'])) {
				$userdata = get_user_by('login', esc_attr($_GET['user_login']));
				if (($userdata) && (intval($userdata->ID))) {
					$this->_filters['user_id'] = $userdata->ID;
					$this->_filters['user_login'] = $userdata->user_login;
				} else {
					$this->_admin_header_error = __("Unknown user login:", USER_REPORTS_I18N_DOMAIN) ." ". esc_attr($_GET['user_login']); 
					$userdata = wp_get_current_user();
					//echo "userdata<pre>"; print_r($userdata); echo "</pre>";
					if (($userdata) && (intval($userdata->data->ID))) {
						$this->_filters['user_id'] = $userdata->data->ID;
						$this->_filters['user_login'] = $userdata->data->user_login;
					}					
				}
			} else {
				$userdata = wp_get_current_user();
				//echo "userdata<pre>"; print_r($userdata); echo "</pre>";
				if (($userdata) && (intval($userdata->data->ID))) {
					$this->_filters['user_id'] = $userdata->data->ID;
					$this->_filters['user_login'] = $userdata->data->user_login;
				}
			}
			
		} else {
		
			if (isset($_GET['user_id'])) {
				$this->_filters['user_id'] = intval($_GET['user_id']);
				if (($this->_filters['user_id'] != 0) && (array_search($this->_filters['user_id'], $this->_filters['blog_users_ids']) === false)) {
					$this->_filters['user_id'] = get_current_user_id();				
				}
			} else {
				if (!is_super_admin()) {
					$this->_filters['user_id'] = get_current_user_id();
				} else {
					$this->_filters['user_id'] = 0;
				}
			}
		}
		
		if (isset($_GET['date_end'])) {
			$date_end = strtotime(esc_attr($_GET['date_end']));
			if ($date_end !== false) { // We have a valid date
				$this->_filters['date_end'] = mktime(23, 59, 59, 
					date("m", $date_end), 
					date("d", $date_end), 
					date("Y", $date_end) );				
			} else {
				$this->_filters['date_end'] = mktime(23, 59, 59, 
					date("m"), 
					date("d"), 
					date("Y") );				
			}
		} else {
			// Else, set date_end to taday's date
			$this->_filters['date_end'] = mktime(23, 59, 59, 
					date("m"), 
					date("d"), 
					date("Y") );
		}



		if (isset($_GET['date_start'])) {
			$date_start = strtotime(esc_attr($_GET['date_start']));
			if ($date_start !== false) {
				$this->_filters['date_start'] = mktime(0, 0, 0, 
					date("m", $date_start), 
					date("d", $date_start), 
					date("Y", $date_start) );
			} else {
				if (isset($this->_filters['date_end'])) {
					$this->_filters['date_start'] = mktime(0, 0, 0, 
						date("m", $this->_filters['date_end']), 
						date("d", $this->_filters['date_end'])-90, 
						date("Y", $this->_filters['date_end']) );
					
				} else {
					//echo "invalid date_end<br />";
					$this->_filters['date_start'] = mktime(0, 0, 0, 
						date("m"), 
						date("d")-90, 
						date("Y") );					
				}
			}
		} else {
			// Else set start date to 90 days prior to today's date
			$this->_filters['date_start'] = mktime(0, 0, 0, 
				date("m"), 
				date("d")-90, 
				date("Y") );
		}
		
		// IF the date_end is earlier than date_start. Swap them.
		if ($this->_filters['date_end'] < $this->_filters['date_start']) {
			$date_tmp = $this->_filters['date_end'];
			$this->_filters['date_end'] = $this->_filters['date_start'];
			$this->_filters['date_start'] = $date_tmp;
		}
		$date_range = intval($this->_filters['date_end']) - intval($this->_filters['date_start']);
		$date_range = intval($date_range/86400);
		if (intval($date_range) < 90) {
			$this->_filters['date_start'] = mktime(0, 0, 0, 
				date("m", $this->_filters['date_end']), 
				date("d", $this->_filters['date_end'])-90, 
				date("Y", $this->_filters['date_end']) );			
		}
	}


	/**
	 * This function build the output display for the filters bar shown at the top of the page. This 
	 * filter bar contains all form elements used to filter the main table. User, Blog, Dates, Post Types
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_show_filter_form_bar() {
		
		?>
		<form id="user-report-filters" method="get" action="">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php			
				$this->user_reports_show_filter_form_types();
				$this->user_reports_show_filter_form_blogs();
				$this->user_reports_show_filter_form_users();		
				$this->user_reports_show_filter_form_dates();
			?>
			<input class="button-secondary" id="user-reports-filters-submit" type="submit" value="<?php _e('Create', USER_REPORTS_I18N_DOMAIN); ?>" />
		</form>
		<?php
	}


	/**
	 * Show the filter bar field set for the Report Type dropdown.
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_show_filter_form_types() {
		?>
		<label for="user-reports-filter-types"><?php _e('Report Type', USER_REPORTS_I18N_DOMAIN); ?></label>: 
		<select id="user-reports-filter-types" name="type">			
			<?php
				$content_types = array();
				
				if (function_exists('post_indexer_post_insert_update'))
					$content_types['posts'] 	= 	__('Post', USER_REPORTS_I18N_DOMAIN);

				if (function_exists('comment_indexer_comment_insert_update')) 
					$content_types['comments']	=	__('Comments', USER_REPORTS_I18N_DOMAIN);

				if (($content_types) && (count($content_types))) {
					foreach($content_types as $type => $label) {

						$selected = '';
						if ($type == $this->_filters['type'])
							$selected = ' selected="selected" ';
				
						?><option <?php echo $selected; ?> value="<?php echo $type ?>"><?php echo $label ?></option><?php
					}
				}
			?>
		</select>
		<?php
	}


	/**
	 * Show the filter bar field set for the Blogs dropdown.
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_show_filter_form_blogs() {

		global $wpdb;
		
		if (is_network_admin()) {

			$blogs = array(
				'0'							=>	__('All Blogs', USER_REPORTS_I18N_DOMAIN)
			);
			
		} else {
			$current_blog = get_blog_details($wpdb->blogid);
			
			$blogs = array(
				'0'							=>	__('All Blogs', USER_REPORTS_I18N_DOMAIN),
				$current_blog->blog_id		=>	__('This Blog Only', USER_REPORTS_I18N_DOMAIN)
			);			
		}

		?>
		<label for="user-reports-filter-blogs"><?php _e('Blogs', USER_REPORTS_I18N_DOMAIN); ?></label>: 
		<select id="user-reports-filter-blogs" name="blog_id">
		<?php
			foreach($blogs as $blog_id => $blog_name) {
				$selected = '';
				if (intval($blog_id) == intval($this->_filters['blog_id']))
					$selected = ' selected="selected" ';
													
				?><option <?php echo $selected; ?> value="<?php echo $blog_id ?>"><?php echo $blog_name ?></option><?php
			}
		?>
		</select>
		<?php
	} 
	

	/**
	 * Show the filter bar field set for the Users dropdown.
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_show_filter_form_users() {
		global $wpdb;
		
		if (!is_network_admin()) {
			$users = $this->user_reports_get_users($wpdb->blogid);
			if ($users) {
				?>
				<label for="user-reports-filter-users"><?php _e('Users', USER_REPORTS_I18N_DOMAIN); ?>: </label>
				<select id="user-reports-filter-users" name="user_id">
					<option value="0"><?php _e('All Users', USER_REPORTS_I18N_DOMAIN); ?></option>
					<?php
						foreach($users as $user_group_name => $user_group) {
							if ((is_array($user_group)) && (count($user_group))) {
					
								?><optgroup label="<?php echo $user_group_name; ?>"><?php
								foreach($user_group as $user_id => $display_name) {
									$selected = '';
									if ($user_id == $this->_filters['user_id']) {
										$selected = ' selected="selected" ';
									} 

									?><option <?php echo $selected ?> value="<?php echo $user_id; ?>"><?php echo $display_name; ?></option><?php
								}
								?></optgroup><?php
							}
						}
					?>
				</select>
				<?php
			}
		} else {
			?>
			<label for="user-reports-filter-users"><?php _e('Users', USER_REPORTS_I18N_DOMAIN); ?>: </label>
			<input type="text" id="user-reports-filter-users" name="user_login" value="<?php echo $this->_filters['user_login']; ?>" />
			<?php			
		}
	}


	/**
	 * Show the filter bar field set for the Date filters.
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param none
	 * @return none
	 */
	function user_reports_show_filter_form_dates() {
		?>
		<label for="user-reports-filter-date-start">From Date</label>
		<input type="text" size="10" name="date_start" id="user-reports-filter-date-start" 
			value="<?php echo date('Y-m-d', $this->_filters['date_start']); ?>" />

		<label for="user-reports-filter-date-end">To Date</label>
		<input type="text" size="10" name="date_end" id="user-reports-filter-date-end" 
			value="<?php echo date('Y-m-d', $this->_filters['date_end']); ?>" />
		<?php
	}
	
	
	/**
	 * Utility function to determine all blogs under a Multisite install
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param none
	 * @return array of blog information
	 */
	function user_report_get_blogs() {

		global $wpdb;

		$blogs_tmp = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, site_id, domain FROM $wpdb->blogs") );
		if ($blogs_tmp) {
			$blogs = array();
			foreach($blogs_tmp as $blog) {
				$blogs[$blog->blog_id] = get_blog_details($blog->blog_id);;
			}
			return $blogs;		
		}
	}


	/**
	 * This function build an array of all users for the site and adds the super admins to the returned array
	 *
	 * @since 1.0.0
	 * @see 
	 *
	 * @param int $blog_id The blog_id we want user from. For network admin this is zero to grab all users. 
	 * @return none
	 */
	function user_reports_get_users($blog_id='') {
				
		$users_all = array();
			
		$user_args = array(
			'number' 	=> 	0,
			'blog_id'	=> 	$blog_id,
			'fields'	=>	array('ID', 'display_name')
		);
		
		$wp_user_search = new WP_User_Query( $user_args );
		$users_tmp = $wp_user_search->get_results();
		if ($users_tmp) {
				
			$users_all['Blog'] = array();
				
			foreach($users_tmp as $user) {
				if (!is_super_admin($user->ID)) {
					$users_all['Blog'][$user->ID] = $user->display_name;
				}
			}
			asort($users_all['Blog']);
		}

		return $users_all;
	}
	
	function get_comments( $comments ) {

		if( isset( $_GET['comment_reply'] ) ) {
			$comment = get_comment( absint( $_GET['comment_reply'] ) );
			//echo "comment<pre>"; print_r($comment); echo "</pre>";
			if( $comment === NULL ) {
				$comments = array();
			} else {
				$comments = array( $comment );
				//add_action( 'admin_footer', 'wp_ozh_cqr_popup_reply' );
			}
		}

		return $comments;
	}
	
}

$user_reports = new UserReports();