<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class User_Reports_Comments_List_Table extends WP_List_Table {

	var $current_user_id;
	var $filters;
	
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
        switch($column_name){
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_post_title($item){

		global $current_blog;

		$blog = get_blog_details($item->blog_id);

      		//Build row actions
		$actions = array();
		$actions['view-post'] = '<a target="_blank" href="'. $item->comment_post_permalink .'">'
			. __('view post', USER_REPORTS_I18N_DOMAIN) .'</a>';
		$actions['view-comment'] = '<a target="_blank" href="'. $item->comment_post_permalink .'#comment-'. $item->comment_id .'">'
			. __('view comment', USER_REPORTS_I18N_DOMAIN) .'</a>';

		if (is_super_admin($this->current_user_id)) {
			$actions['edit'] = '<a target="_blank" href="'. $blog->siteurl .'/wp-admin/post.php?post='. $item->comment_post_id .'&action=edit">'
				. __('edit', USER_REPORTS_I18N_DOMAIN) .'</a>';
		} else if ($this->current_user_id == $item->post_author) {
			$actions['edit'] = '<a target="_blank" href="'. $blog->siteurl .'/wp-admin/post.php?post='. $item->comment_post_id .'&action=edit">'
				. __('edit', USER_REPORTS_I18N_DOMAIN) .'</a>';
		} else if (($current_blog->blog_id == $item->blog_id) && (current_user_can( 'edit_others_posts' ))) {
			$actions['edit'] = '<a target="_blank" href="'. $blog->siteurl .'/wp-admin/post.php?post='. $item->comment_post_id .'&action=edit">'
				. __('edit', USER_REPORTS_I18N_DOMAIN) .'</a>';
		}
		
   		//Return the title contents
   		return sprintf('%1$s %2$s',
			'<a target="_blank" href="'. $item->comment_post_permalink .'">'. $item->post_title .'</a>',
			$this->row_actions($actions)
   		);
    }

    function column_blog($item){

		$blog = get_blog_details($item->blog_id);

        //Build row actions
        $actions = array();
		$actions['view']	= '<a target="_blank" href="'. $blog->siteurl .'">'. __('view', USER_REPORTS_I18N_DOMAIN) .'</a>';

		if ((is_super_admin($this->current_user_id)) || (isset($this->blogs_of_user[$item->blog_id])))
			$actions['admin']	= '<a target="_blank" href="'. $blog->siteurl .'/wp-admin/">admin</a>';

        //Return the title contents
        return sprintf('%1$s %2$s',
            '<a href="'. $blog->siteurl .'">'. $blog->blogname .'</a>',
			$this->row_actions($actions)
        );
    }

	function column_user($item) {
		echo the_author_meta('display_name', $item->comment_author_user_id);
	}


	function column_post_date($item) {
		$format = get_option('date_format') .' '. get_option('time_format');						
		echo date_i18n($format, $item->comment_date_stamp + ( get_option( 'gmt_offset' ) * 3600));
	}


    function get_columns(){
        $columns = array(
            'blog'			=> 	__('Blog', 			USER_REPORTS_I18N_DOMAIN),
			'user'			=>	__('User', 			USER_REPORTS_I18N_DOMAIN),
            'post_title'    => 	__('Post Title', 	USER_REPORTS_I18N_DOMAIN),
            'post_date'  	=> 	__('Date', 			USER_REPORTS_I18N_DOMAIN)
        );
        return $columns;
    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'blog'			=> 	array('blog_id', false),     //true means its already sorted
			'user'			=>	array('user_id', false),
            'post_title'	=> 	array('post_title', false),
            'post_date'		=> 	array('date', true)
        );
        return $sortable_columns;
    }


    function get_bulk_actions() {
        $actions = array();
        return $actions;
    }


    function process_bulk_action() {

    }


    function prepare_items($filters) {

		global $wpdb;
		
		$total_items = 0;
		
		$this->current_user_id = get_current_user_id();
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

		$this->blogs_of_user = get_blogs_of_user( $this->current_user_id, false );

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

		$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'c.comment_date_stamp'; //If no sort, default to title
		$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; 	//If no order, default to asc

		if ($orderby == "user_id")
			$orderby = "c.comment_author_user_id";
		if ($orderby == "date")
			$orderby = "c.comment_date_stamp";

        $this->_column_headers = array($columns, $hidden, $sortable);

		$select_query_str 	= "SELECT 
			c.blog_id as blog_id, 
			c.site_id as site_id, 
			c.comment_id as comment_id, 
			c.comment_post_id as comment_post_id, 
			c.comment_author_user_id as comment_author_user_id, 
			c.comment_date_stamp as comment_date_stamp, 
			c.comment_post_permalink as comment_post_permalink,
			p.post_title as post_title ";

		$select_query_str_count = "SELECT SQL_CALC_FOUND_ROWS count(*) as count ";

		$tables_query_str = " FROM ". $wpdb->base_prefix . "site_comments c INNER JOIN ". 
			$wpdb->base_prefix ."site_posts p ON c.comment_post_id=p.post_id ";
			
		$where_query_str 	= "WHERE 1";

		if ((isset($filters['user_id'])) && (intval($filters['user_id']))) {
			$where_query_str .= " AND c.comment_author_user_id=". $filters['user_id'] ." ";
		} else if ( (isset($filters['blog_users_ids'])) && (count($filters['blog_users_ids'])) ) {
			$where_query_str .= " AND c.comment_author_user_id IN (". implode(',', $filters['blog_users_ids']) .") ";
		} else {
			$where_query_str .= " AND c.comment_author_user_id=". $this->current_user_id ." ";
		}

		if (intval($filters['blog_id']) > 0) {
			$where_query_str .= " AND c.blog_id=". intval($filters['blog_id']) ." ";
		} else if (!is_super_admin($this->current_user_id)) {
			$blogs = get_blogs_of_user( $this->current_user_id, false );
			if ($blogs) {
				//echo "blogs<pre>"; print_r($blogs); echo "</pre>";			
				$where_query_str .= " AND c.blog_id IN (". implode(',', array_keys($blogs)) .") ";
			}
		} 

		if ( (isset($filters['date_start'])) && (strlen($filters['date_start'])) 
		  && (isset($filters['date_end'])) && (strlen($filters['date_end'])) ) {
			$where_query_str .= " AND (c.comment_date_stamp between ". $filters['date_start'] ." AND ". $filters['date_end'] .") ";
		}

		$orderby_query_str 	= "ORDER BY ". $orderby ." ". $order;

		$query_str_count = $select_query_str_count ." ". $tables_query_str ." ". $where_query_str;
		$post_items_total = $wpdb->get_row($query_str_count);
		if ($post_items_total) {
			$total_items = $post_items_total->count;
		}
		$limit_query_str 	= " LIMIT ". ($current_page-1)*$per_page .",". $per_page;
				
		$query_str = $select_query_str ." ". $tables_query_str ." ". $where_query_str ." ". $orderby_query_str ." ". $limit_query_str;
		//echo "query_str=[". $query_str ."]<br />";
		$post_items = $wpdb->get_results($query_str);
		if ($post_items) {
			$this->items = $post_items;
		}

		$this->set_pagination_args( array(
				'total_items' => intval($total_items),                  //WE have to calculate the total number of items
				'per_page'    => intval($per_page),                     //WE have to determine how many items to show on a page
				'total_pages' => ceil(intval($total_items)/intval($per_page))   //WE have to calculate the total number of pages
			) 
		);
    }
}
