<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if(!class_exists('User_Reports_Posts_List_Table')){
	class User_Reports_Posts_List_Table extends WP_List_Table {

		var $current_user_id;
		var $filters;
		var $blogs_of_user;
	
	    function __construct(){
	        global $status, $page;

	        //Set parent defaults
	        parent::__construct( array(
	            'singular'  => 'post',     //singular name of the listed records
	            'plural'    => 'posts',    //plural name of the listed records
	            'ajax'      => false        //does this table support ajax?
	        ) );

	    }

	    function column_default($item, $column_name){
			//echo "column_name=[". $column_name ."]<br />";
	        switch($column_name){
	            default:
	                //return print_r($item,true); //Show the whole array for troubleshooting purposes
	        }
	    }

	    function column_post_title($item){

			global $current_blog;

			if (!isset($this->filters['doing_reports'])) {

				$actions = array();
				$actions['view'] = '<a target="_blank" href="'. $item->post_permalink .'">'. __('view', USER_REPORTS_I18N_DOMAIN) .'</a>';

				//Return the title contents
				return sprintf('%1$s %2$s',
					'<a target="_blank" href="'. $item->post_permalink .'">'. $item->post_title .'</a>',
					$this->row_actions($actions)
				);	
			} else if ($this->filters['doing_reports'] == "pdf") {
				return '<a href="'. $item->post_permalink .'">'. $item->post_title .'</a>';
			} else if ($this->filters['doing_reports'] == "csv") {
				return $item->post_title;
			}
	    }

		function column_post_url($item) {
			return $item->post_permalink;
		}
	
	    function column_blog($item) {
			//echo "item<pre>"; print_r($item); echo "</pre>";
			//if ($this->has_post_indexer_plugin())
		
			if (is_multisite()) {
				$blog = get_blog_details($item->blog_id);
				if ($blog) {
					$siteurl = $blog->siteurl;	
					$blogname = $blog->blogname;	
				}	
			} else {
				$siteurl = get_option('siteurl');
				$blogname = get_option('blogname');
			}
				
	        $actions = array();

			if (!isset($this->filters['doing_reports'])) {

				$actions['view']	= '<a target="_blank" href="'. $siteurl .'">'. __('view', USER_REPORTS_I18N_DOMAIN) .'</a>';

				//Return the title contents
	        	return sprintf('%1$s %2$s',
	            	'<a target="_blank" href="'. $siteurl .'">'. $blogname .'</a>',
					$this->row_actions($actions)
	        	);
			} else if ($this->filters['doing_reports'] == "pdf") {
				return '<a href="'. $blog->siteurl .'">'. $blogname .'</a>';
			} else if ($this->filters['doing_reports'] == "csv") {
				return $blogname;
			}
	    }

		function column_blog_url($item) {
			if (is_multisite()) {
				$blog = get_blog_details($item->blog_id);
				if ($blog) {
					$siteurl = $blog->siteurl;	
				}	
			} else {
				$siteurl = get_option('siteurl');
			}
			return $siteurl;
		}

		function column_post_type($item) {
			return $item->post_type;
		}

		function column_user($item) {

			if (!isset($this->filters['doing_reports'])) {

				if ((is_super_admin($this->current_user_id)) || (isset($this->blogs_of_user[$item->blog_id]))) {
				    $actions = array();
		
					$href_str = "?page=user-reports";
					if (isset($_GET['type']))
						$href_str .= "&type=". esc_attr($_GET['type']);
					if (isset($_GET['blog_id']))
						$href_str .= "&blog_id=". esc_attr($_GET['blog_id']);
					if (isset($_GET['date_start']))
						$href_str .= "&date_start=". esc_attr($_GET['date_start']);
					if (isset($_GET['date_end']))
						$href_str .= "&date_end=". esc_attr($_GET['date_end']);

					if (is_network_admin())
						$href_str .= "&user_login=". get_the_author_meta('login', $item->post_author);
					else
						$href_str .= "&user_id=". $item->post_author;
					
					$actions['user-reports'] = '<a class="submitreports" href="'. $href_str .'">' . __( 'Reports', USER_REPORTS_I18N_DOMAIN ) . '</a>';
	    	
			        return sprintf('%1$s %2$s',
						'<a class="submitreports" href="users.php?page=user-reports&amp;user_id='. 
							$item->post_author .'">'. get_the_author_meta('display_name', $item->post_author) .'</a>',
						$this->row_actions($actions)
			        );

				} else {
					return the_author_meta('display_name', $item->post_author);
				}

			} else if ($this->filters['doing_reports'] == "pdf") {
				return get_the_author_meta('display_name', $item->post_author);
			} else if ($this->filters['doing_reports'] == "csv") {
				return get_the_author_meta('display_name', $item->post_author);
			}
		}

		function column_login($item) {
			return get_the_author_meta('login', $item->post_author);
		}
	
		function column_post_date($item) {
			if (!isset($this->filters['doing_reports'])) {
				$format = get_option('date_format') .' '. get_option('time_format');						
				return date_i18n($format, $item->post_published_stamp + ( get_option( 'gmt_offset' ) * 3600));
			} else if ($this->filters['doing_reports'] == "pdf") {
				$format = get_option('date_format') .' '. get_option('time_format');						
				return date_i18n($format, $item->post_published_stamp + ( get_option( 'gmt_offset' ) * 3600));
			} else if ($this->filters['doing_reports'] == "csv") {
				$format = 'Y/m/d h:i:s' ;						
				return date_i18n($format, $item->post_published_stamp + ( get_option( 'gmt_offset' ) * 3600));
			}
		}

	    function get_columns() {

			$columns = array();
			if (!isset($this->filters['doing_reports'])) {
				if (UserReports::has_post_indexer_plugin())
	            	$columns['blog']		= 	__('Blog', 			USER_REPORTS_I18N_DOMAIN);
	
				$columns['user']		=	__('Author', 		USER_REPORTS_I18N_DOMAIN);
	            $columns['post_title']	= 	__('Post Title', 	USER_REPORTS_I18N_DOMAIN);

//				if (!UserReports::has_post_indexer_plugin())
//					$columns['post_type']	= 	__('Post Type', USER_REPORTS_I18N_DOMAIN);
					
	            $columns['post_date']  	= 	__('Date', 			USER_REPORTS_I18N_DOMAIN);

			} else if ($this->filters['doing_reports'] == "pdf") {
				if (UserReports::has_post_indexer_plugin())
	            	$columns['blog']		= 	__('Blog', 			USER_REPORTS_I18N_DOMAIN);
	
				$columns['user']		=	__('Author', 		USER_REPORTS_I18N_DOMAIN);
	            $columns['post_title']	= 	__('Post Title', 	USER_REPORTS_I18N_DOMAIN);

//				if (!UserReports::has_post_indexer_plugin())
//					$columns['post_type']	= 	__('Post Type', USER_REPORTS_I18N_DOMAIN);

	            $columns['post_date']  	= 	__('Date', 			USER_REPORTS_I18N_DOMAIN);

			} else if ($this->filters['doing_reports'] == "csv") {

				if (UserReports::has_post_indexer_plugin())
	            	$columns['blog']		= 	__('Blog', 			USER_REPORTS_I18N_DOMAIN);
	
	            $columns['blog_url']	= 	__('Blog Url', 		USER_REPORTS_I18N_DOMAIN);
				$columns['user']		=	__('Author Name', 	USER_REPORTS_I18N_DOMAIN);
				$columns['login']		=	__('Author Login', 	USER_REPORTS_I18N_DOMAIN);
	            $columns['post_title']	= 	__('Post Title', 	USER_REPORTS_I18N_DOMAIN);
	            $columns['post_url']	= 	__('Post Url', 		USER_REPORTS_I18N_DOMAIN);

//				if (!UserReports::has_post_indexer_plugin())
//					$columns['post_type']	= 	__('Post Type', USER_REPORTS_I18N_DOMAIN);

	            $columns['post_date']  	= 	__('Date', 			USER_REPORTS_I18N_DOMAIN);
			}
		
	        return $columns;
	    }

	    function get_sortable_columns() {

			$sortable_columns = array();
			if (!isset($this->filters['doing_reports'])) {

				$sortable_columns['blog']		= 	array('blog_id', false);
		        $sortable_columns['user']		= 	array('user_id', false);
				$sortable_columns['post_title']	= 	array('post_title', false);

//				if (!UserReports::has_post_indexer_plugin())
//					$sortable_columns['post_type']	= 	array('post_type', false);
					
				$sortable_columns['post_date']	= 	array('post_date', true);
			}
	        return $sortable_columns;
	    }


		function display_pdf() {

			$output = '<table width="100%" cellspacing="0" style="background: #F9F9F9;">';
		
			$column_headers = $this->get_columns();
			if ($column_headers) {
			
				$output .= "<tr>";
				foreach($column_headers as $column_key => $column_name) {
					$output .= '<th>'. $column_name .'</th>';
				}
				$output .= "</tr>";

				if ((isset($this->items)) && (count($this->items))) {

					$row_cnt = 0;
					foreach($this->items as $item) {
					
						$row_styles = "font-size: 12px; font-weight: normal; ";
						$row_cnt += 1;
						if ($row_cnt%2)
							$row_styles .= "background: #F9F9F9;";
						else
							$row_styles .= "background: #FCFCFC;";
					
						$row_styles .= " border: 1px solid #DFDFDF; ";
						
						$output .= '<tr style="'. $row_styles .'">';
						foreach($column_headers as $column_key => $column_name) {
							if ( method_exists( $this, 'column_' . $column_key ) ) {
								$body_item = call_user_func( array( &$this, 'column_' . $column_key ), $item );
								$output .= '<td style="border-bottom: 1px solid #DFDFDF; padding: 5px;">'. $body_item. '</td>';
							}
						} 
						$output .= "</tr>";
					}
				} 
			}
			$output .= "</table>";
			//echo "output=[". $output ."]<br />";
			//die();
			return $output;
		}

		function display_csv() {

			$csv_output 	= '';
		
			$column_headers = $this->get_columns();
			if ($column_headers) {
			
				foreach($column_headers as $column_key => $column_name) {
					$csv_output .= '"'. $column_name .'",';
				}
				$csv_output .= "\r\n";

				if ((isset($this->items)) && (count($this->items))) {

					foreach($this->items as $item) {
						foreach($column_headers as $column_key => $column_name) {

							if ( method_exists( $this, 'column_' . $column_key ) ) {
								$csv_body_item = call_user_func( array( &$this, 'column_' . $column_key ), $item );
								$csv_body_item = str_replace('"', '\"', $csv_body_item);
								$csv_output .= '"'. $csv_body_item. '",';
							}
						}
						$csv_output .= "\r\n";
					}
				} 
			}
			//echo "csv_output[". $csv_output ."]<br />";
		
			if (strlen($csv_output)) {
			
				return $csv_output;
			}		
		}
		
		/**
		 * This filter is used when the source is 'local'. Since that option uses the WP_Query to access Posts we needed
		 * a way to tell WP_Query to only pull posts with GMT post_data newer than a given timestamp ($_POST['timekey'])
		 *
		 * @since 1.0.1
		 * @see 
		 *
		 * @param string $where from WP_Query
		 * @return string $$where modified.
		 */
		function filter_posts_where( $where_query_str = '' ) {

			//echo "where_query_str[". $where_query_str ."]<br />";
			
			if ( (isset($this->filters['date_start'])) && (strlen($this->filters['date_start'])) 
			  && (isset($this->filters['date_end'])) && (strlen($this->filters['date_end'])) ) {

				$where_query_str .= " AND (post_date BETWEEN '". date('Y-m-d H:i:s', $this->filters['date_start']) .
					"' AND '". date('Y-m-d H:i:s', $this->filters['date_end']) ."') ";
			}
			//echo "where_query_str[". $where_query_str ."]<br />";
			return $where_query_str;
		}
		
	    function prepare_items($filters) {

			global $wpdb, $user_reports;
		
			$total_items = 0;
		
			$this->current_user_id = get_current_user_id();
			$this->filters = $filters;
		
			if (!isset($this->filters['doing_reports'])) {
			
				if ($this->current_user_id) {
					$per_page = (int) get_user_meta($this->current_user_id, 'users_page_user_reports_per_page', true );
				}	

				if ((!$per_page) || ($per_page < 0)) {
					$default_post_per_page = get_option('posts_per_page');
					if ($default_post_per_page)
						$per_page = $default_post_per_page;
					else
						$per_page = 20;
				}
		        $current_page = $this->get_pagenum();
	
			} else {
				$per_page = 0;
				$current_page = 1;
			}

	        $columns = $this->get_columns();
	        $hidden = array();
	        $sortable = $this->get_sortable_columns();
		
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'post_date'; //If no sort, default to title
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; 	//If no order, default to asc

	        $this->_column_headers = array($columns, $hidden, $sortable);

			//echo "filters<pre>"; print_r($this->filters); echo "</pre>";
			//echo "blog_users_ids<pre>"; print_r($this->filters['blog_users_ids']); echo "</pre>";
		
			$this->blogs_of_user = get_blogs_of_user( $this->current_user_id, false );

			$all_items = array();

			$post_indexer_plugin = UserReports::has_post_indexer_plugin();
			if ((is_multisite()) && ($post_indexer_plugin)) {
				
				//echo "post_indexer_version=[". $post_indexer_version ."]<br />";
				if ($post_indexer_plugin == 2) {
					$select_query_str 	= "SELECT blog_id, site_id, post_id, post_author, post_type, post_title, post_permalink, post_published_stamp ";
					$select_query_str_count = "SELECT SQL_CALC_FOUND_ROWS count(*) as count ";

					$tables_query_str 	= "FROM ". $wpdb->base_prefix . "site_posts ";
					$where_query_str 	= "WHERE 1";

					if ((isset($this->filters['user_id'])) && (intval($this->filters['user_id'])))
						$where_query_str .= " AND post_author=". $this->filters['user_id'] ." ";
					else if ( (isset($this->filters['blog_users_ids'])) && (count($this->filters['blog_users_ids'])) ) {
						$where_query_str .= " AND post_author IN (". implode(',', $this->filters['blog_users_ids']) .") ";
					} else {
						if (!is_super_admin($this->current_user_id)) {
							$where_query_str .= " AND post_author=". $this->current_user_id ." ";
						} 
					}
		
					if (intval($this->filters['blog_id']) > 0) {
						$where_query_str .= " AND blog_id=". intval($this->filters['blog_id']) ." ";
					} else if (!is_super_admin($this->current_user_id)) {
						$blogs = get_blogs_of_user( $this->current_user_id, false );
						if ($blogs) {
							//echo "blogs<pre>"; print_r($blogs); echo "</pre>";			
							$where_query_str .= " AND blog_id IN (". implode(',', array_keys($blogs)) .") ";
						}
					}
		
					if ( (isset($this->filters['date_start'])) && (strlen($this->filters['date_start'])) 
					  && (isset($this->filters['date_end'])) && (strlen($this->filters['date_end'])) ) {
						$where_query_str .= " AND (post_published_stamp between ". $this->filters['date_start'] ." AND ". $this->filters['date_end'] .") ";
					}

					$post_orderby = $orderby;
					if ($post_orderby == "post_date") {
						$post_orderby = "post_published_stamp";
					}
					$orderby_query_str 	= " ORDER BY ". $post_orderby ." ". $order;

					$query_str_count = $select_query_str_count ." ". $tables_query_str ." ". $where_query_str;
					//echo "query_str_count=[". $query_str_count ."]<br />";		
					$post_items_total = $wpdb->get_row($query_str_count);
					if ($post_items_total) {
						//echo "post_items_total<pre>"; print_r($post_items_total); echo "</pre>";
						$total_items = $post_items_total->count;
					}
		
					if (!isset($this->filters['doing_reports'])) {
						$limit_query_str 	= " LIMIT ". ($current_page-1)*$per_page .",". $per_page;
					} else {
						$limit_query_str = '';
					}
		
					$query_str = $select_query_str ." ". $tables_query_str ." ". $where_query_str ." ". $orderby_query_str ." ". $limit_query_str;
					//echo "query_str=[". $query_str ."]<br />";
					$post_items = $wpdb->get_results($query_str);
					if ($post_items) {
						$this->items = $post_items;
					}
					
					if (!isset($this->filters['doing_reports'])) {
						$this->set_pagination_args( array(
							'total_items' => intval($total_items),                  		//WE have to calculate the total number of items
							'per_page'    => intval($per_page),                     		//WE have to determine how many items to show on a page
							'total_pages' => ceil(intval($total_items)/intval($per_page))   //WE have to calculate the total number of pages
							) 
						);
					}
					
					
				} else if ( ($post_indexer_plugin == 3) && (class_exists('Network_Query')) ) {

					$post_query_args = array( 
						'post_type' 		=> 	$this->filters['type'], 
						'post_status'		=>	'publish',
						'orderby'			=>	$orderby,
						'order'				=>	$order,
					);
					
					if (!isset($this->filters['doing_reports'])) {
						$post_query_args['posts_per_page'] = $per_page;
						$post_query_args['paged'] = $current_page;
					} else {
						$post_query_args['nopaging']	= true;
					}

					if ((isset($this->filters['blog_id'])) && (intval($this->filters['blog_id']) > 0)) {
						$post_query_args['blog_id'] = intval($this->filters['blog_id']);
					}
					
					if ((isset($this->filters['user_id'])) && (intval($this->filters['user_id'])))
						$post_query_args['author'] = intval($this->filters['user_id']);
					else if ( (isset($this->filters['blog_users_ids'])) && (count($this->filters['blog_users_ids'])) ) {
						$post_query_args['author'] = implode(',', $this->filters['blog_users_ids']);
					} else {
						if (!is_super_admin($this->current_user_id)) {
							$post_query_args['author'] = $this->current_user_id;
						} 
					}
					
					//echo "filters<pre>"; print_r($this->filters); echo "</pre>";
					
					if ( (isset($this->filters['date_start'])) && (strlen($this->filters['date_start'])) 
					  && (isset($this->filters['date_end'])) && (strlen($this->filters['date_end'])) ) {
						  
						  /*
						  $post_query_args['date_query'] = array(
							  array(
								  'after'     => array(
									  'year'  => date('Y', $this->filters['date_start']),
									  'month' => date('n', $this->filters['date_start']),
									  'day'   => date('j', $this->filters['date_start']),
								  ),
								  'before'    => array(
									  'year'  => date('Y', $this->filters['date_end']),
									  'month' => date('n', $this->filters['date_end']),
									  'day'   => date('j', $this->filters['date_end']),
								  ),
								  'inclusive' => true,
							  ),
						  );
						  */
						  
						add_filter( 'network_posts_where', array(&$this,'filter_posts_where') );
					}

					//echo "post_query_args<pre>"; print_r($post_query_args); echo "</pre>";
					
					$current_error_reporting = error_reporting();
					error_reporting(0);
					$post_query = new Network_Query($post_query_args);
					//echo "post_query<pre>"; print_r($post_query); echo "</pre>";
					error_reporting($current_error_reporting);

					if ( (isset($this->filters['date_start'])) && (strlen($this->filters['date_start'])) 
					  && (isset($this->filters['date_end'])) && (strlen($this->filters['date_end'])) ) {
						remove_filter( 'network_posts_where', array(&$this,'filter_posts_where') );
					}

					//echo "post_query<pre>"; print_r($post_query); echo "</pre>";
					if ((isset($post_query->posts)) && (count($post_query->posts))) {
						foreach($post_query->posts as $_key => $post_item) {

							$post_item->blog_id					= $post_item->BLOG_ID;
							$post_item->post_author_id 			= $post_item->post_author;
							$post_item->post_permalink 			= network_get_permalink( $post_item->BLOG_ID, $post_item->ID);
							$post_item->post_published_stamp	= strtotime($post_item->post_date_gmt);
									
							$post_query->posts[$_key] = $post_item;
						}
						$this->items = $post_query->posts;
						$total_items = $post_query->found_posts;
						//echo "total_items=[]"
						if (!isset($this->filters['doing_reports'])) {
							$this->set_pagination_args( array(
								'total_items' => intval($total_items),                  		//WE have to calculate the total number of items
								'per_page'    => intval($per_page),                     		//WE have to determine how many items to show on a page
								'total_pages' => ceil(intval($total_items)/intval($per_page))   //WE have to calculate the total number of pages
								) 
							);
						}
					}					
				}				
			} else {
				$post_query_args = array( 
					'post_type' 		=> 	$this->filters['type'], 
					'post_status'		=>	'publish',
					'orderby'			=>	$orderby,
					'order'				=>	$order,
				);
					
				if (!isset($this->filters['doing_reports'])) {
					$post_query_args['posts_per_page'] = $per_page;
					$post_query_args['paged'] = $current_page;
				} else {
					$post_query_args['nopaging']	= true;
				}

				if ((isset($this->filters['user_id'])) && (intval($this->filters['user_id'])))
					$post_query_args['author'] = intval($this->filters['user_id']);
				else if ( (isset($this->filters['blog_users_ids'])) && (count($this->filters['blog_users_ids'])) ) {
					$post_query_args['author'] = implode(',', $this->filters['blog_users_ids']);
				} else {
					if (!is_super_admin($this->current_user_id)) {
						$post_query_args['author'] = $this->current_user_id;
					} 
				}

				if ((isset($this->filters['blog_id'])) && (intval($this->filters['blog_id']) > 0)) {
					$post_query_args['blog_id'] = intval($this->filters['blog_id']);
				}
				
				//echo "post_query_args<pre>"; print_r($post_query_args); echo "</pre>";
				
				if ( (isset($this->filters['date_start'])) && (strlen($this->filters['date_start'])) 
				  && (isset($this->filters['date_end'])) && (strlen($this->filters['date_end'])) ) {
					add_filter( 'network_posts_where', array(&$this,'filter_posts_where') );
				}
				
				$current_error_reporting = error_reporting();
				error_reporting(0);
				$post_query = new WP_Query($post_query_args);
				error_reporting($current_error_reporting);

				if ( (isset($this->filters['date_start'])) && (strlen($this->filters['date_start'])) 
				  && (isset($this->filters['date_end'])) && (strlen($this->filters['date_end'])) ) {
					remove_filter( 'network_posts_where', array(&$this,'filter_posts_where') );
				}
				
				//echo "post_query<pre>"; print_r($post_query); echo "</pre>";
				if ((isset($post_query->posts)) && (count($post_query->posts))) {
					foreach($post_query->posts as $_key => $post_item) {

						$post_item->blog_id					= $wpdb->blogid;
						$post_item->post_author_id 			= $post_item->post_author;
						$post_item->post_permalink 			= get_permalink( $post_item->ID);
						$post_item->post_published_stamp	= strtotime($post_item->post_date_gmt);
								
						$post_query->posts[$_key] = $post_item;
					}
					$this->items = $post_query->posts;
					$total_items = $post_query->found_posts;
					//echo "total_items=[]"
					if (!isset($this->filters['doing_reports'])) {
						$this->set_pagination_args( array(
							'total_items' => intval($total_items),                  		//WE have to calculate the total number of items
							'per_page'    => intval($per_page),                     		//WE have to determine how many items to show on a page
							'total_pages' => ceil(intval($total_items)/intval($per_page))   //WE have to calculate the total number of pages
							) 
						);
					}
				}					
			}			
	    }
	}
}