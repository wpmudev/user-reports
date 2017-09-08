<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class User_Reports_Comments_List_Table extends WP_List_Table {

	var $current_user_id;
	var $filters;

	function __construct() {
		global $status, $page;

		parent::__construct( array(
			'plural' => 'comments',
			'singular' => 'comment',
			'ajax' => true,
		) );
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	function column_post_title( $item ) {

		global $current_blog;

		if ( is_multisite() ) {
			$blog = get_blog_details( $item->blog_id );
			if ( $blog ) {
				$siteurl = $blog->siteurl;
				$blogname = $blog->blogname;
			}
		} else {
			$siteurl = get_option( 'siteurl' );
			$blogname = get_option( 'blogname' );
		}

		if ( ! isset( $this->filters['doing_reports'] ) ) {

			//Build row actions
			$actions = array();

			if ( ! isset( $this->filters['doing_reports'] ) ) {

				$actions['view-post'] = '<a target="_blank" href="' . $item->comment_post_permalink . '">'
				                        . __( 'view', USER_REPORTS_I18N_DOMAIN ) . '</a>';
			}

			//Return the title contents
			return sprintf( '%1$s %2$s',
				'<a target="_blank" href="' . $item->comment_post_permalink . '">' . $item->post_title . '</a>',
				$this->row_actions( $actions )
			);
		} else if ( $this->filters['doing_reports'] == "pdf" ) {
			return '<a href="' . $item->comment_post_permalink . '">' . $item->post_title . '</a>';
		} else if ( $this->filters['doing_reports'] == "csv" ) {
			return $item->post_title;
		}
	}

	function column_post_url( $item ) {
		return $item->comment_post_permalink;
	}

	function column_comment( $item ) {

		if ( is_network_admin() ) {
			$user_can = true;
		} else {
			if ( ! isset( $this->filters['doing_reports'] ) ) {
				$user_can = current_user_can( 'edit_comment', $item->comment_id );
			} else {
				$user_can = true;
			}
		}

		//echo "comment_date_stamp=[". $item->comment_date_stamp ."]<br />";
		if ( ! isset( $this->filters['doing_reports'] ) ) {

			$ptime = date( 'G', strtotime( $item->comment_date_stamp ) );
			if ( ( abs( time() - $ptime ) ) < 86400 ) {
				$ptime = sprintf( __( '%s ago', USER_REPORTS_I18N_DOMAIN ), human_time_diff( $ptime ) );
			} else {
				$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				$ptime = date_i18n( $format, $item->comment_date_stamp + ( get_option( 'gmt_offset' ) * 3600 ) );
			}
			$output = "";
			$output .= '<div class="submitted-on">';
			$output .= __( 'Submitted on', USER_REPORTS_I18N_DOMAIN ) . ' ' . $ptime . "<br />";
			$output .= '</div>';

			$comment_content = apply_filters( 'the_content', $item->comment_content );
			$comment_content = str_replace( ']]>', ']]&gt;', $comment_content );
			$output .= $comment_content;

			if ( $user_can ) {

				if ( is_multisite() ) {
					$blog = get_blog_details( $item->blog_id );
					if ( $blog ) {
						$siteurl = $blog->siteurl;
						$blogname = $blog->blogname;
					}
				} else {
					$siteurl = get_option( 'siteurl' );
					$blogname = get_option( 'blogname' );
				}

				$actions = array();
				$actions['reply'] = '<a target="_blank" href="' . $siteurl . '/wp-admin/edit-comments.php?comment_reply=' . $item->comment_id . '">'
				                    . __( 'reply', USER_REPORTS_I18N_DOMAIN ) . '</a>';

				$output .= sprintf( '%1$s',
					$this->row_actions( $actions )
				);
			}

			return $output;

		} else if ( $this->filters['doing_reports'] == "pdf" ) {
			$output = '';

			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$output .= date_i18n( $format, $item->comment_date_stamp + ( get_option( 'gmt_offset' ) * 3600 ) );
			$output .= "<br />";
			$output .= $item->comment_content;

			return $output;

		} else if ( $this->filters['doing_reports'] == "csv" ) {
			return $item->comment_content;
		}
	}

	function column_blog( $item ) {

		if ( is_multisite() ) {
			$blog = get_blog_details( $item->blog_id );
			if ( $blog ) {
				$siteurl = $blog->siteurl;
				$blogname = $blog->blogname;
			}
		} else {
			$siteurl = get_option( 'siteurl' );
			$blogname = get_option( 'blogname' );
		}

		if ( ! isset( $this->filters['doing_reports'] ) ) {

			//Build row actions
			$actions = array();
			if ( ! isset( $this->filters['doing_reports'] ) ) {

				$actions['view'] = '<a target="_blank" href="' . $siteurl . '">' . __( 'view', USER_REPORTS_I18N_DOMAIN ) . '</a>';
			}

			//Return the title contents
			return sprintf( '%1$s %2$s',
				'<a href="' . $siteurl . '">' . $blogname . '</a>',
				$this->row_actions( $actions )
			);
		} else if ( $this->filters['doing_reports'] == "pdf" ) {
			return '<a href="' . $blog->siteurl . '">' . $blogname . '</a>';
		} else if ( $this->filters['doing_reports'] == "csv" ) {
			return $blogname;
		}
	}

	function column_blog_url( $item ) {
		$siteurl = '';

		if ( is_multisite() ) {
			$blog = get_blog_details( $item->blog_id );
			if ( $blog ) {
				$siteurl = $blog->siteurl;
			}
		} else {
			$siteurl = get_option( 'siteurl' );
		}

		return $siteurl;
	}

	function column_author( $comment ) {

		//echo "comment<pre>"; print_r($comment); echo "</pre>";
		if ( is_network_admin() ) {
			$user_can = true;
		} else {
			if ( ! isset( $this->filters['doing_reports'] ) ) {
				$user_can = current_user_can( 'edit_comment', $comment->comment_id );
			} else {
				$user_can = true;
			}
		}
		if ( ( isset( $comment->comment_author_user_id ) ) && ( intval( $comment->comment_author_user_id ) ) ) {
			$author_name_display = get_the_author_meta( 'display_name', $comment->comment_author_user_id );
			if ( $author_name_display ) {
				if ( ! isset( $this->filters['doing_reports'] ) ) {

					$href_str = "?page=user-reports";
					if ( isset( $_GET['type'] ) ) {
						$href_str .= "&type=" . esc_attr( $_GET['type'] );
					}
					if ( isset( $_GET['blog_id'] ) ) {
						$href_str .= "&blog_id=" . esc_attr( $_GET['blog_id'] );
					}
					if ( isset( $_GET['date_start'] ) ) {
						$href_str .= "&date_start=" . esc_attr( $_GET['date_start'] );
					}
					if ( isset( $_GET['date_end'] ) ) {
						$href_str .= "&date_end=" . esc_attr( $_GET['date_end'] );
					}

					if ( is_network_admin() ) {
						$href_str .= "&user_login=" . get_the_author_meta( 'login', $comment->comment_author_user_id );
					} else {
						$href_str .= "&user_id=" . $comment->comment_author_user_id;
					}

					$author_name_display = '<a href="' . $href_str . '">' . $author_name_display . '</a>';
				}

			} else {
				$author_name_display = __( 'Anonymous', USER_REPORTS_I18N_DOMAIN );
			}
		} else {
			$author_name_display = __( 'Anonymous', USER_REPORTS_I18N_DOMAIN );
		}

		$author_login_display = get_the_author_meta( 'login', $comment->comment_author_user_id );
		if ( ! $author_login_display ) {
			$author_login_display = '';
		}

		$show_comment_avatars = get_option( 'show_avatars' );
		if ( ( $author_login_display ) && ( $show_comment_avatars ) ) {
			$author_avatar_display = get_avatar( $author_login_display, 32 );
		} else {
			$author_avatar_display = '';
		}

		if ( ( isset( $comment->comment_author_url ) ) && ( strlen( $comment->comment_author_url ) ) ) {
			$author_url = $comment->comment_author_url;
			if ( 'http://' == $author_url ) {
				$author_url = '';
			}

			$author_url_display = preg_replace( '|http://(www\.)?|i', '', $comment->comment_author_url );

			if ( strlen( $author_url_display ) > 50 ) {
				$author_url_display = substr( $author_url_display, 0, 49 ) . '...';
			}
		} else {
			$author_url_display = '';
			$author_url = '';
		}

		if ( ! isset( $this->filters['doing_reports'] ) ) {

			echo '<strong>' . $author_avatar_display . ' ' . $author_name_display . '</strong><br />';

			if ( ( ! empty( $author_url_display ) ) && ( ! empty( $author_url ) ) ) {
				echo '<a title="' . $author_url . '" href="' . $author_url . '">' . $author_url_display . '</a><br />';
			}

			if ( ! empty( $comment->comment_author_email ) ) {
				?>
				<a href="mailto:<?php echo $comment->comment_author_email; ?>"><?php echo $comment->comment_author_email; ?></a>
				<br /><?php
			}

			if ( $user_can ) {
				echo '<a href="edit-comments.php?s=' . $comment->comment_author_IP . '&amp;mode=detail';
				if ( 'spam' == $comment_status ) {
					echo '&amp;comment_status=spam';
				}
				echo '">';
				echo $comment->comment_author_IP;
				echo '</a>';
			}


		} else if ( $this->filters['doing_reports'] == "pdf" ) {

			$output = '';
			if ( ! empty( $author_name_display ) ) {
				$output .= $author_name_display . '<br />';
			}

			if ( ( ! empty( $author_url_display ) ) && ( ! empty( $author_url ) ) ) {
				$output .= '<a title="' . $author_url . '" href="' . $author_url . '">' . $author_url_display . '</a><br />';
			}

			if ( ! empty( $comment->comment_author_email ) ) {
				$output .= '<a href="mailto:' . $comment->comment_author_email . '">' . $comment->comment_author_email . '</a><br />';
			}

			return $output;

		} else if ( $this->filters['doing_reports'] == "csv" ) {
			return $author_name_display;
		}
	}

	function column_login( $item ) {
		return get_the_author_meta( 'login', $item->comment_author_user_id );
	}


//	function column_post_date($item) {
//		$format = get_option('date_format') .' '. get_option('time_format');						
//		echo date_i18n($format, $item->comment_date_stamp + ( get_option( 'gmt_offset' ) * 3600));
//	}

	function column_comment_date( $item ) {
		//echo "comment_date_stamp=[". $comment_date_stamp ."]<br />";
		$format = 'Y/m/d h:i:s';

		return date_i18n( $format, $item->comment_date_stamp + ( get_option( 'gmt_offset' ) * 3600 ) );
	}

	function column_comment_url( $item ) {
		return $item->comment_post_permalink . '#comment-' . $item->comment_id;
	}

	function get_columns() {

		$columns = array();

		if ( ! isset( $this->filters['doing_reports'] ) ) {
			if ( UserReports::has_comment_indexer_plugin() ) {
				$columns['blog'] = __( 'Blog', USER_REPORTS_I18N_DOMAIN );
			}

			$columns['author'] = __( 'Author', USER_REPORTS_I18N_DOMAIN );
			$columns['comment'] = __( 'Comment', USER_REPORTS_I18N_DOMAIN );
			$columns['post_title'] = __( 'In Response To', USER_REPORTS_I18N_DOMAIN );

		} else if ( $this->filters['doing_reports'] == "pdf" ) {
			if ( UserReports::has_comment_indexer_plugin() ) {
				$columns['blog'] = __( 'Blog', USER_REPORTS_I18N_DOMAIN );
			}

			$columns['author'] = __( 'Author', USER_REPORTS_I18N_DOMAIN );
			$columns['comment'] = __( 'Comment', USER_REPORTS_I18N_DOMAIN );
			$columns['post_title'] = __( 'In Response To', USER_REPORTS_I18N_DOMAIN );

		} else if ( $this->filters['doing_reports'] == "csv" ) {

			if ( UserReports::has_comment_indexer_plugin() ) {
				$columns['blog'] = __( 'Blog', USER_REPORTS_I18N_DOMAIN );
			}

			$columns['blog_url'] = __( 'Blog Url', USER_REPORTS_I18N_DOMAIN );
			$columns['author'] = __( 'Author', USER_REPORTS_I18N_DOMAIN );
			$columns['login'] = __( 'Author Login', USER_REPORTS_I18N_DOMAIN );
			$columns['comment'] = __( 'Comment', USER_REPORTS_I18N_DOMAIN );
			$columns['comment_url'] = __( 'Comment Url', USER_REPORTS_I18N_DOMAIN );
			$columns['comment_date'] = __( 'Comment Date', USER_REPORTS_I18N_DOMAIN );
			$columns['post_title'] = __( 'Post Title', USER_REPORTS_I18N_DOMAIN );
			$columns['post_url'] = __( 'Post Url', USER_REPORTS_I18N_DOMAIN );
		}

		return $columns;
	}


	function get_sortable_columns() {
		$sortable_columns = array();

		if ( ! isset( $this->filters['doing_reports'] ) ) {

			$sortable_columns['blog'] = array( 'blog_id', false );     //true means its already sorted
			$sortable_columns['author'] = array( 'user_id', false );
			$sortable_columns['post_title'] = array( 'post_title', false );
		}

		return $sortable_columns;
	}

	function display_pdf() {
		$output = '<table width="100%" cellspacing="0" style="background-color: #F9F9F9;">';

		$column_headers = $this->get_columns();
		if ( $column_headers ) {

			$output .= "<tr>";
			foreach ( $column_headers as $column_key => $column_name ) {
				$output .= '<th>' . $column_name . '</th>';
			}
			$output .= "</tr>";

			if ( ( isset( $this->items ) ) && ( count( $this->items ) ) ) {

				$row_cnt = 0;
				foreach ( $this->items as $item ) {
					$row_styles = "font-size: 12px; font-weight: normal; ";

					$row_cnt += 1;
					if ( $row_cnt % 2 ) {
						$row_styles .= "background: #F9F9F9;";
					} else {
						$row_styles .= "background: #FCFCFC;";
					}

					if ( intval( $item->comment_approved ) ) {
						$row_styles .= "background: #FFFFE0;";
					}

					$row_styles .= " border: 1px solid #DFDFDF; ";

					$output .= '<tr style="' . $row_styles . '">';
					foreach ( $column_headers as $column_key => $column_name ) {
						if ( method_exists( $this, 'column_' . $column_key ) ) {
							$body_item = call_user_func( array( &$this, 'column_' . $column_key ), $item );
							$output .= '<td style="border-bottom: 1px solid #DFDFDF; padding: 5px;">' . $body_item . '</td>';
						}
					}
					$output .= "</tr>";
				}
			}
		}
		$output .= "</table>";

		//echo "output=[". $output ."]<br />";
		return $output;
	}

	function display_csv() {

		$csv_output = '';

		$column_headers = $this->get_columns();
		if ( $column_headers ) {

			foreach ( $column_headers as $column_key => $column_name ) {
				$csv_output .= '"' . $column_name . '",';
			}
			$csv_output .= "\r\n";

			if ( ( isset( $this->items ) ) && ( count( $this->items ) ) ) {

				foreach ( $this->items as $item ) {
					foreach ( $column_headers as $column_key => $column_name ) {
						if ( method_exists( $this, 'column_' . $column_key ) ) {
							$csv_body_item = call_user_func( array( &$this, 'column_' . $column_key ), $item );
							$csv_body_item = str_replace( '"', '\"', $csv_body_item );
							$csv_output .= '"' . $csv_body_item . '",';
						}
					}
					$csv_output .= "\r\n";
				}
			}
		}
		if ( strlen( $csv_output ) ) {
			return $csv_output;
		}
	}


	function single_row( $comment ) {

		$comment_class = "user-reports-comment-row ";
		if ( intval( $comment->comment_approved ) ) {
			$comment_class .= " approved";
		} else {
			$comment_class .= " unapproved";
		}

		echo '<tr id="comment-' . $comment->comment_id . '" class="' . $comment_class . '">';
		echo $this->single_row_columns( $comment );
		echo "</tr>";
	}

	function get_bulk_actions() {
		$actions = array();

		return $actions;
	}


	function process_bulk_action() {

	}

	function prepare_items() {

		global $wpdb, $user_reports;

		$filters = $user_reports->get_filters();

		$total_items = 0;

		$this->current_user_id = get_current_user_id();
		$this->filters = $filters;

		if ( ! isset( $this->filters['doing_reports'] ) ) {

			if ( $this->current_user_id ) {
				$per_page = (int) get_user_meta( $this->current_user_id, 'users_page_user_reports_per_page', true );
			}

			if ( ( ! $per_page ) || ( $per_page < 0 ) ) {
				$default_post_per_page = get_option( 'posts_per_page' );
				if ( $default_post_per_page ) {
					$per_page = $default_post_per_page;
				} else {
					$per_page = 20;
				}
			}
			$current_page = $this->get_pagenum();
		} else {
			$per_page = 0;
			$current_page = 1;
		}

		$this->blogs_of_user = get_blogs_of_user( $this->current_user_id, false );

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		if ( UserReports::has_comment_indexer_plugin() ) {

			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'c.comment_date_stamp'; //If no sort, default to title
			$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';    //If no order, default to asc

			if ( $orderby == "user_id" ) {
				$orderby = "c.comment_author_user_id";
			}
			if ( $orderby == "date" ) {
				$orderby = "c.comment_date_stamp";
			}

			$select_query_str = "SELECT 
				c.blog_id as blog_id, 
				c.site_id as site_id, 
				c.comment_id as comment_id, 
				c.comment_post_id as comment_post_id, 
				c.comment_author_user_id as comment_author_user_id, 
				c.comment_author_url as comment_author_url, 
				c.comment_author_email as comment_author_email,
				c.comment_author_IP as comment_author_IP, 
				c.comment_approved as comment_approved,
				c.comment_content as comment_content,
				c.comment_date_stamp as comment_date_stamp, 
				c.comment_post_permalink as comment_post_permalink,
				p.post_title as post_title ";

			$select_query_str_count = "SELECT SQL_CALC_FOUND_ROWS count(*) as count ";

			$tables_query_str = " FROM " . $wpdb->base_prefix . "site_comments c INNER JOIN " .
			                    $wpdb->base_prefix . "site_posts p ON c.comment_post_id=p.post_id ";

			$where_query_str = "WHERE 1";

			if ( ( isset( $filters['user_id'] ) ) && ( intval( $filters['user_id'] ) ) ) {
				$where_query_str .= " AND c.comment_author_user_id=" . $filters['user_id'] . " ";
			} else if ( ( isset( $filters['blog_users_ids'] ) ) && ( count( $filters['blog_users_ids'] ) ) ) {
				$where_query_str .= " AND c.comment_author_user_id IN (" . implode( ',', $filters['blog_users_ids'] ) . ") ";
			} else {
				$where_query_str .= " AND c.comment_author_user_id=" . $this->current_user_id . " ";
			}

			if ( intval( $filters['blog_id'] ) > 0 ) {
				$where_query_str .= " AND c.blog_id=" . intval( $filters['blog_id'] ) . " ";
			} else if ( ! is_super_admin( $this->current_user_id ) ) {
				$blogs = get_blogs_of_user( $this->current_user_id, false );
				if ( $blogs ) {
					//echo "blogs<pre>"; print_r($blogs); echo "</pre>";			
					$where_query_str .= " AND c.blog_id IN (" . implode( ',', array_keys( $blogs ) ) . ") ";
				}
			}

			if ( ( isset( $filters['date_start'] ) ) && ( strlen( $filters['date_start'] ) )
			     && ( isset( $filters['date_end'] ) ) && ( strlen( $filters['date_end'] ) ) ) {
				$where_query_str .= " AND (c.comment_date_stamp between " . $filters['date_start'] . " AND " . $filters['date_end'] . ") ";
			}

			$orderby_query_str = "ORDER BY " . $orderby . " " . $order;

			$query_str_count = $select_query_str_count . " " . $tables_query_str . " " . $where_query_str;
			$post_items_total = $wpdb->get_row( $query_str_count );
			if ( $post_items_total ) {
				$total_items = $post_items_total->count;
			}

			if ( ! isset( $this->filters['doing_reports'] ) ) {
				$limit_query_str = " LIMIT " . ( $current_page - 1 ) * $per_page . "," . $per_page;
			} else {
				$limit_query_str = "";
			}

			$query_str = $select_query_str . " " . $tables_query_str . " " . $where_query_str . " " . $orderby_query_str . " " . $limit_query_str;
			//echo "query_str=[". $query_str ."]<br />";
			$post_items = $wpdb->get_results( $query_str );
			if ( $post_items ) {
				$this->items = $post_items;
			}

			if ( ! isset( $this->filters['doing_reports'] ) ) {
				$this->set_pagination_args( array(
						'total_items' => intval( $total_items ),                  //WE have to calculate the total number of items
						'per_page' => intval( $per_page ),                     //WE have to determine how many items to show on a page
						'total_pages' => ceil( intval( $total_items ) / intval( $per_page ) )   //WE have to calculate the total number of pages
					)
				);
			}
		} else {

			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'c.comment_date'; //If no sort, default to title
			$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';    //If no order, default to asc
			if ( $orderby == "user_id" ) {
				$orderby = "c.comment_author";
			}
			if ( $orderby == "date" ) {
				$orderby = "c.comment_date";
			}

			$select_query_str = "SELECT 
				c.comment_ID as comment_id, 
				c.comment_post_ID as comment_post_id, 
				c.user_id as comment_author_user_id, 
				c.comment_author_url as comment_author_url, 
				c.comment_author_email as comment_author_email,
				c.comment_author_IP as comment_author_IP, 
				c.comment_approved as comment_approved,
				c.comment_content as comment_content,
				c.comment_date_gmt as comment_date_stamp";

			$select_query_str_count = "SELECT SQL_CALC_FOUND_ROWS count(*) as count ";
			$tables_query_str = " FROM " . $wpdb->prefix . "comments c INNER JOIN " .
			                    $wpdb->prefix . "posts p ON c.comment_post_id=p.ID ";

			$where_query_str = "WHERE 1";

			if ( ( isset( $filters['user_id'] ) ) && ( intval( $filters['user_id'] ) ) ) {
				$where_query_str .= " AND c.user_id=" . $filters['user_id'] . " ";
			} else if ( ( isset( $filters['blog_users_ids'] ) ) && ( count( $filters['blog_users_ids'] ) ) ) {
				$where_query_str .= " AND c.user_id IN (" . implode( ',', $filters['blog_users_ids'] ) . ") ";
			} else {
				$where_query_str .= " AND c.user_id=" . $this->current_user_id . " ";
			}

			if ( ( isset( $filters['date_start'] ) ) && ( strlen( $filters['date_start'] ) )
			     && ( isset( $filters['date_end'] ) ) && ( strlen( $filters['date_end'] ) ) ) {
				$where_query_str .= " AND (c.comment_date BETWEEN '" . date( 'Y-m-d H:i:s', $filters['date_start'] ) .
				                    "' AND '" . date( 'Y-m-d H:i:s', $filters['date_end'] ) . "') ";
			}

			$orderby_query_str = "ORDER BY " . $orderby . " " . $order;

			$query_str_count = $select_query_str_count . " " . $tables_query_str . " " . $where_query_str;
			$post_items_total = $wpdb->get_row( $query_str_count );
			if ( $post_items_total ) {
				$total_items = $post_items_total->count;
			}

			if ( ! isset( $this->filters['doing_reports'] ) ) {
				$limit_query_str = " LIMIT " . ( $current_page - 1 ) * $per_page . "," . $per_page;
			} else {
				$limit_query_str = "";
			}

			$query_str = $select_query_str . " " . $tables_query_str . " " . $where_query_str . " " . $orderby_query_str . " " . $limit_query_str;
			//echo "query_str=[". $query_str ."]<br />";
			$post_items = $wpdb->get_results( $query_str );
			if ( $post_items ) {
				if ( isset( $post_items ) && count( $post_items ) ) {
					foreach ( $post_items as $_key => $post_item ) {

						$post_item->blog_id = $wpdb->blogid;
						$post_item->site_id = $wpdb->siteid;
						$post_item->comment_post_permalink = get_permalink( $post_item->comment_post_id );
						$post_item->post_title = get_the_title( $post_item->comment_post_id );
						$post_item->comment_date_stamp = strtotime( $post_item->comment_date_stamp );

						$post_query->posts[ $_key ] = $post_item;
					}
					$this->items = $post_items;

					if ( ! isset( $this->filters['doing_reports'] ) ) {
						$this->set_pagination_args( array(
								'total_items' => intval( $total_items ),                  //WE have to calculate the total number of items
								'per_page' => intval( $per_page ),                     //WE have to determine how many items to show on a page
								'total_pages' => ceil( intval( $total_items ) / intval( $per_page ) )   //WE have to calculate the total number of pages
							)
						);
					}
				}
			}
		}
	}
}
