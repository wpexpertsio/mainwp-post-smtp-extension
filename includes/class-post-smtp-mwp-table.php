<?php
/**
 * Post SMTP MainWP Table
 *
 * @package Post SMTP MainWP Table
 */

if ( ! class_exists( 'Post_SMTP_MWP_Table' ) ) :

	/**
	 * Post SMTP MainWP Table
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	class Post_SMTP_MWP_Table {


		/**
		 * Constructor
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function __construct() {

			add_action( 'post_smtp_email_logs_table_header', array( $this, 'email_logs_table_header' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_filter( 'post_smtp_get_logs_query_after_table', array( $this, 'query_join' ) );
			add_filter( 'post_smtp_get_logs_query_cols', array( $this, 'query_columns' ) );
			add_filter( 'post_smtp_email_logs_localize', array( $this, 'email_logs_localize' ) );
			add_filter( 'ps_email_logs_row', array( $this, 'filter_row' ) );
			add_filter( 'post_smtp_get_logs_args', array( $this, 'logs_args' ) );
			add_action( 'postman_delete_logs_successfully', array( $this, 'delete_logs' ) );
		}


		/**
		 * Enqueue Scripts | Action Callback
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function admin_enqueue_scripts() {

			wp_enqueue_script( 'post-smtp-mainwp', plugin_dir_url( __DIR__ ) . 'assets/js/admin.js', array(), '1.0.0', true );
			
			$localize = array(
				'childSites' 	=> $this->get_sites(),
				'mainSite'   	=> get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Main Site', 'post-smtp' ),
				'allSites'   	=> __( 'All Sites', 'post-smtp' )
			);
			
			$validation = new Post_SMTP_MWP_Validation();
			$localize['sites'] = $validation->get_quota();

			wp_localize_script(
				'post-smtp-mainwp',
				'PSMainWP',
				$localize,
			);
		}


		/**
		 * Add Opened Column | Action Callback
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function email_logs_table_header() {

			echo '<th>' . esc_html__( 'Site', 'post-smtp' ) . '</th>';
		}


		/**
		 * Add Opened Column | Filter Callback
		 *
		 * @param   String $join Join.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function query_join( $join ) {

			global $wpdb;
			$email_logs = new PostmanEmailLogs();

			$join .= " LEFT JOIN {$wpdb->prefix}{$email_logs->meta_table} AS lm ON lm.log_id = pl.id AND lm.meta_key = 'mainwp_child_site_id'";

			return $join;
		}

		/**
		 * Add Opened Column | Filter Callback
		 *
		 * @param   String $columns Columns.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function query_columns( $columns ) {

			$columns .= ', lm.meta_value AS site_id';

			return $columns;
		}


		/**
		 * Localize the strings | Filter Callback
		 *
		 * @param   Array $localize Localize.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function email_logs_localize( $localize ) {

			$localize['DTCols'][] = array(
				'data' => 'site_id',
			);

			return $localize;
		}


		/**
		 * Fitler Log's Row | Filter Callback
		 *
		 * @param   Array $row Log's Row.
		 * @since   2.5.0
		 * @version 1.0.0
		 */
		public function filter_row( $row ) {

			$child_enabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
			$child_key     = $child_enabled['key'];

			$dbwebsites = apply_filters( 'mainwp_getdbsites', __FILE__, $child_key, array( $row->site_id ), array(), $option );
			$website    = $dbwebsites[ $row->site_id ];

			$url = admin_url( 'admin.php?page=postman_email_log' );

			if ( $row->site_id && 'main_site' !== $row->site_id ) {

				$url         .= "&site_id={$row->site_id}";
				$row->site_id = "<a href='{$url}' class='ps-mainwp-site'>{$website->name}</a>";
			}

			if ( 'main_site' === $row->site_id ) {

				$url         .= '&site_id=main_site';
				$row->site_id = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Main Site';
				$row->site_id = "<a href='{$url}' class='ps-mainwp-site'>{$row->site_id}</a>";

			}

			return $row;
		}


		/**
		 * Add Opened Column | Filter Callback
		 *
		 * @param   Array $args Query Args.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function logs_args( $args ) {

			if ( 'site_id' === $args['order_by'] ) {

				$args['order_by'] = 'lm.meta_value';

			}

			return $args;
		}


		/**
		 * Gets MainWP's Child Sites
		 *
		 * @since   2.5.0
		 * @version 1.0.0
		 */
		public function get_sites() {

			$child_enabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
			$child_key     = $child_enabled['key'];
			$sites         = apply_filters( 'mainwp_getsites', __FILE__, $child_key );
			$site_ids      = array();

			foreach ( $sites as $site ) {

				$site_ids[ $site['id'] ] = $site['name'];

			}

			return empty( $site_ids ) ? false : $site_ids;
		}


		/**
		 * Checks if the user is in staging view
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function is_staging_view() {

			$user = get_current_user_id();

			$userdata = WP_User::get_data_by( 'id', $user );

			if ( ! $userdata ) {

				return false;

			}

			$user = new WP_User();

			$user->init( $userdata );

			if ( ! $user ) {

				return false;

			}

			global $wpdb;

			$prefix = $wpdb->get_blog_prefix();

			if ( $user->has_prop( $prefix . 'mainwp_staging_options_updates_view' ) ) { // Blog-specific.

				$result = $user->get( $prefix . 'mainwp_staging_options_updates_view' );

			} elseif ( $user->has_prop( 'mainwp_staging_options_updates_view' ) ) { // User-specific and cross-blog.

				$result = $user->get( 'mainwp_staging_options_updates_view' );

			} else {

				$result = false;

			}

			return $result;
		}


		/**
		 * Delete logs
		 *
		 * @param   array $ids Log IDs.
		 * @since   2.5.0
		 * @version 1.0.0
		 */
		public function delete_logs( $ids ) {

			$ids   = implode( ',', $ids );
			$ids   = -1 === $ids ? '' : "WHERE log_id IN ({$ids});";
			$table = $wpdb->prefix . $email_logs->meta_table;

			global $wpdb;
			$email_logs = new PostmanEmailLogs();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return $wpdb->query(
				$wpdb->prepare(
					'DELETE FROM %i %s',
					$table,
					$ids
				)
			);
		}
	}

	new Post_SMTP_MWP_Table();

endif;
