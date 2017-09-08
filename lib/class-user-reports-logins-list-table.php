<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class User_Reports_Logins_List_Table extends WP_List_Table {

	/** ************************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 ***************************************************************************/
	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
			'singular' => 'post',     //singular name of the listed records
			'plural' => 'posts',    //plural name of the listed records
			'ajax' => false       //does this table support ajax?
		) );

	}


	/** ************************************************************************
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title()
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as
	 * possible.
	 *
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 *
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 *
	 * @return string Text or HTML to be placed inside the column <td>
	 **************************************************************************/
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}


	function column_blog( $item ) {

		//echo "item<pre>"; print_r($item); echo "</pre>";
		$blog = get_blog_details( $item['blog_id'] );

		$actions = array();
		$actions['admin'] = '<a href="' . $blog->siteurl . '/wp-admin/">admin</a>';
		$actions['edit'] = '<a href="' . $blog->siteurl . '">view</a>';

		//Return the title contents
		return sprintf( '%1$s %2$s',
			$blog->blogname,
			$this->row_actions( $actions )
		);
	}


	function column_login_time( $item ) {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		echo date_i18n( $format, $item['login_time'] + ( get_option( 'gmt_offset' ) * 3600 ) );
	}

	/** ************************************************************************
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 *
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_columns() {
		$columns = array(
			'blog' => 'Blog',
			'login_time' => 'Date',
		);

		return $columns;
	}

	/** ************************************************************************
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 **************************************************************************/
	function get_sortable_columns() {
		/*
				$sortable_columns = array(
					'blog'			=> array('blog_id', false),     //true means its already sorted
					'post_title'	=> array('post_title', false),
					'post_type'		=> array('post_type', false),
					'post_date'		=> array('post_date', true)
				);
		*/
//        return $sortable_columns;
		return array();
	}


	/** ************************************************************************
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 *
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_bulk_actions() {
		$actions = array();

		return $actions;
	}


	/** ************************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 *
	 * @see $this->prepare_items()
	 **************************************************************************/
	function process_bulk_action() {

	}

	/** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	function prepare_items() {

		global $wpdb, $user_reports;

		$filters = $user_reports->get_filters();

		/**
		 * First, lets decide how many records per page to show
		 */
//		$current_user_id = get_current_user_id();
//		if ($current_user_id) {
//			$per_page = (int) get_user_meta($current_user_id, 'users_page_user_reports_per_page', true );
//		}	
//		if ((!$per_page) || ($per_page < 0)) {
//			$default_post_per_page = get_option('posts_per_page');
//			if ($default_post_per_page)
//				$per_page = $default_post_per_page;
//			else
//				$per_page = 20;
//		}

		$per_page = 9999;


		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();


		if ( isset( $_GET['paged'] ) ) {
			$paged = intval( $_GET['paged'] );
		} else {
			$paged = 1;
		}
		$offset = $paged * $per_page;


		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example
		 * package slightly different than one you might build on your own. In
		 * this example, we'll be using array manipulation to sort and paginate
		 * our data. In a real-world implementation, you will probably want to
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
		//$data = $this->example_data;

//		$user_blogs = get_blogs_of_user( intval($filters['user_id']), true );
//		echo "user_blogs<pre>"; print_r($user_blogs); echo "</pre>";

//		$query_str = "SELECT * FROM {$wpdb->blogs} WHERE 1";
//		$blogs = $wpdb->get_results($query_str);
//		if (($blogs) && (count($blogs))) {
//			//echo "blogs<pre>"; print_r($blogs); echo "</pre>";
//			$this->blogs = array();
//			foreach($blogs as $blog) {
//				$this->blogs[$blog->blog_id] = $blog;
//			}
//		}

		//echo "filters<pre>"; print_r($filters); echo "</pre>";
		//$filters['user_id'] = 1;
		$data = null;
		if ( isset( $filters['user_id'] ) ) {
			$user_login_data = get_user_meta( (int) $filters['user_id'], 'user-reports-login', true );
			if ( $user_login_data ) {
				$all_items = array();
				foreach ( $user_login_data as $blog_id => $login_time ) {
					$all_items[ $blog_id ] = array(
						'blog_id' => $blog_id,
						'login_time' => $login_time,
					);
				}
				//echo "all_items<pre>"; print_r($all_items); echo "</pre>";
				$data = $all_items;
			}
		}

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		if ( isset( $data ) ) {
			$total_items = count( $data );
		} else {
			$total_items = 0;
		}

		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		if ( isset( $data ) ) {
			$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}


		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		if ( isset( $data ) ) {
			$this->items = $data;
		}


		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page' => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
		) );
	}
}
