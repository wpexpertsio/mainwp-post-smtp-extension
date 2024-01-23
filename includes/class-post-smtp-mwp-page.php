<?php
/**
 * Post SMTP MainWP Page
 *
 * @package Post SMTP MainWP Page
 */

if ( ! class_exists( 'Post_SMTP_MWP_Page' ) ) :

	/**
	 * Class Post_SMTP_MWP_Page
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	class Post_SMTP_MWP_Page {


		/**
		 * Post_SMTP_MWP_Page constructor.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function __construct() {

			if ( isset( $_GET['page'] ) // phpcs:disable WordPress.Security.NonceVerification
				&& ( 'Extensions-Mainwp-Post-Smtp-Extension' === $_GET['page'] // phpcs:disable WordPress.Security.NonceVerification
				|| 'postman_email_log' === $_GET['page'] // phpcs:disable WordPress.Security.NonceVerification
				)
			) {

				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			}

			add_action( 'admin_post_post_smtp_mwp_save_sites', array( $this, 'save_sites' ) );
			add_action( 'wp_ajax_post-smtp-request-mwp-child', array( $this, 'request_child' ) );
		}


		/**
		 * Enquque Script | Action Callback
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function enqueue_scripts() {

			wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
			wp_enqueue_style( 'post-smtp-mainwp', plugin_dir_url( __DIR__ ) . 'assets/css/style.css', array(), '1.0.0' );
		}


		/**
		 * Renders Page in MainWP
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function page() {

			$child_enabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
			$child_key     = $child_enabled['key'];
			$sites         = apply_filters( 'mainwp_getsites', __FILE__, $child_key );

			$sites_ids = array();
			if ( is_array( $sites ) ) {
				foreach ( $sites as $website ) {
					$sites_ids[] = $website['id'];
				}
			}

			$option = array(
				'plugins' => true,
			);

			$dbwebsites = apply_filters( 'mainwp_getdbsites', __FILE__, $child_key, $sites_ids, array(), $option );

			do_action( 'mainwp_pageheader_extensions', __FILE__ );

			$site_ids     = array();
			$is_staging   = 'no';
			$staging_view = 'staging' === get_user_option( 'mainwp_staging_options_updates_view' ) ? true : false;
			$saved_sites  = get_option( 'post_smtp_mainwp_sites' );

			?>

		<div class="post-smtp-mainwp ui form">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="post_smtp_mwp_save_sites" />
				<input type="hidden" name="psmwp_security" class="psmwp-security" value="<?php echo esc_attr( wp_create_nonce( 'psmwp-security' ) ); ?>" />
				<div class="header">
					<div id="mainwp-select-sites-filters">
						<div class="ui mini fluid icon input">
							<input type="text" id="post-smtp-select-sites-filter" value="" placeholder="Type to filter your sites">
							<i class="filter icon"></i>
						</div>
					</div>
					<button class="ui button green ps-enable-all" style="margin: 7px!important;">Enable all sites</button>
					<button class="ui button red ps-disable-all" style="margin: 7px!important;">Disable all sites</button>
					<div style="clear: both;"></div>
				</div>
			<?php

			foreach ( $dbwebsites as $site ) {

				$id                    = $site->id;
				$email_address         = $this->get_option( $saved_sites, $id, 'email_address' );
				$name                  = $this->get_option( $saved_sites, $id, 'name' );
				$reply_to              = $this->get_option( $saved_sites, $id, 'reply_to' );
				$enabled_on_child_site = checked( $this->get_option( $saved_sites, $id, 'enable_on_child_site' ), 1, false );

				// Lets find out if Post SMTP is active on child site or not.
				if ( isset( $site->plugins ) ) {

						$plugins      = json_decode( $site->plugins );
						$has_postsmtp = false;

					foreach ( $plugins as $plugin ) {

						if ( 'Post SMTP' === $plugin->name && 1 === $plugin->active ) {

										$has_postsmtp = true;
										break;

						} else {

								continue;

						}

						break;

					}
				}

				if ( ! $has_postsmtp ) {

					continue;

				}

				?>

			<div class="post-smtp-mainwp-site">
				<div class="mainwp-search-options ui accordion mainwp-sidebar-accordion">
					<div class="title"><i class="dropdown icon"></i>
						<input type="hidden" name="site_id[]" value="<?php echo esc_attr( $id ); ?>" />
						<label class="ps-switch-1">
							<input type="checkbox" <?php echo esc_attr( $enabled_on_child_site ); ?> value="1" class="enable-on-child-site" data-id="<?php echo esc_attr( $id ); ?>" name="<?php echo 'enable_on_child_site[' . esc_attr( $id ) . ']'; ?>" />
							<span class="slider round"></span>
						</label> 
				<?php echo esc_attr( $site->name ); ?><span class="ps-error"></span><span class="spinner"></span></div>
					<div class="content">
						<table>
							<tr>
								<td><label>Email Address</label></td>
								<td><input type="text" value="<?php echo esc_attr( $email_address ); ?>" name="<?php echo 'email_address[]'; ?>" /></td>
							</tr>
							<tr>
								<td><label>Name</label></td>
								<td><input type="text" value="<?php echo esc_attr( $name ); ?>" name="<?php echo 'name[]'; ?>" /></td>
							</tr>
							<tr>
								<td><label>Reply-To</label></td>
								<td><input type="text" value="<?php echo esc_attr( $reply_to ); ?>" name="<?php echo 'reply_to[]'; ?>" /></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
				<?php

			}

			?>
				<input type="submit" class="ui button green" value="Save" />
			</form>
		</div>

			<?php

			do_action( 'mainwp_pagefooter_extensions', __FILE__ );
		}


		/**
		 * Save Sites | Action Callback
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function save_sites() {

			// Security Check.
			if ( isset( $_POST['action'] )
				&& 'post_smtp_mwp_save_sites' === $_POST['action']
				&& isset( $_POST['psmwp_security'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['psmwp_security'] ) ), 'psmwp-security' )
			) {

				$site_ids             = isset( $_POST['site_id'] ) ? array_map( 'intval', wp_unslash( $_POST['site_id'] ) ) : '';
				$email_addresses      = isset( $_POST['email_address'] ) ? array_map( 'sanitize_email', wp_unslash( $_POST['email_address'] ) ) : '';
				$names                = isset( $_POST['name'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['name'] ) ) : '';
				$reply_tos            = isset( $_POST['reply_to'] ) ? array_map( 'sanitize_email', wp_unslash( $_POST['reply_to'] ) ) : '';
				$enable_on_child_site = isset( $_POST['enable_on_child_site'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['enable_on_child_site'] ) ) : '';

				$sites = array();

				foreach ( $site_ids as $key => $id ) {

					$sites[ $id ] = array(
						'email_address'        => $email_addresses[ $key ],
						'name'                 => $names[ $key ],
						'reply_to'             => $reply_tos[ $key ],
						'enable_on_child_site' => isset( $enable_on_child_site[ $id ] ) ? 1 : '',
					);

				}

				update_option( 'post_smtp_mainwp_sites', $sites );

				wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Post-Smtp-For-Mainwp' ) );

			}
		}

		/**
		 * Gets option value by key
		 *
		 * @param   array  $option  Option.
		 * @param   int    $site_id Site ID.
		 * @param   string $key     Key of the option.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function get_option( $option, $site_id, $key ) {

			if ( $option && isset( $option[ $site_id ] ) && isset( $option[ $site_id ][ $key ] ) ) {

				return $option[ $site_id ][ $key ];

			}

			return '';
		}


		/**
		 * Request on Child Site | AJAX Callback
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function request_child() {
			
			if ( isset( $_POST['action'] )
				&& 'post-smtp-request-mwp-child' === $_POST['action']
				&& isset( $_POST['security'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'psmwp-security' )
			) {

				$what          = isset( $_POST['what'] ) ? (bool) sanitize_text_field( wp_unslash( $_POST['what'] ) ) : '';
				$status        = $what;
				$site_id       = isset( $_POST['site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['site_id'] ) ) : '';
				$child_enabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
				$child_key     = $child_enabled['key'];
				$option        = array(
					'pubkey' => true,
				);
				$website       = apply_filters( 'mainwp_getdbsites', __FILE__, $child_key, array( $site_id ), array(), $option );
				$website       = $website[ $site_id ];
				$what          = $what ? 'enable_post_smtp' : 'disable_post_smtp';
				$text          = 'enable_post_smtp' === $what ? 'Enabling' : 'Disabling';
				$api_key       = md5( $website->pubkey );
				$site_url      = $website->url;
				$validation    = new Post_SMTP_MWP_Validation();
				$quota         = $validation->get_quota();
				$sites_count   = get_option( 'post_smtp_mainwp_active_sites_count' );
				
				if( $what == 'enable_post_smtp' && $sites_count && $sites_count >= $quota ) {
					
					wp_send_json_success(
						array(
							'message' => 'QUOTA_EXCEED'
						),
						403
					);
					
				}

				try {

					$response = wp_remote_post(
						"{$site_url}wp-json/psmwp/v1/activate-from-mainwp",
						array(
							'headers' => array(
								'API-Key' => $api_key,
							),
							'body'    => array(
								'action' => $what,
							),
						)
					);

					$message = isset( $response['error'] ) ? $response['error'] : 'Somthing went wrong.';

					if ( $response && ! isset( $response['error'] ) ) {

							$sites                                     = get_option( 'post_smtp_mainwp_sites' );
							$sites                                     = $sites ? $sites : array();
							$sites[ $site_id ]['enable_on_child_site'] = $status ? 1 : '';
						
							if( $what == 'enable_post_smtp' ) {
								if( $sites_count ) {
									$sites_count++;
								}
								else {
									$sites_count = 1;
								} 
							}
							if( $what == 'disable_post_smtp' ) {
								if( $sites_count ) {
									$sites_count--;
								}
							}
						
							update_option( 'post_smtp_mainwp_active_sites_count', $sites_count );
							update_option( 'post_smtp_mainwp_sites', $sites );

							wp_send_json_success(
								array(),
								200
							);

					}

					wp_send_json_error(
						array(
							'message' => $message,
						),
						404
					);

				} catch ( Exception $e ) {

					wp_send_json_error(
						array(
							'message' => $e->getMessage(),
							'extra'   => $e->get_message_extra(),
						),
						404
					);

				}
			}
		}
	}

	new Post_SMTP_MWP_Page();

endif;
