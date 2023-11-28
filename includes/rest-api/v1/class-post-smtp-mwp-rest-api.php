<?php
/**
 * Post SMTP MainWP Rest API
 *
 * @package Post SMTP MainWP Rest API
 */

if ( ! class_exists( 'Post_SMTP_MWP_Rest_API' ) ) :

	/**
	 * Class Post_SMTP_MWP_Rest_API
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	class Post_SMTP_MWP_Rest_API {


		/**
		 * Site ID
		 *
		 * @var bool|mixed
		 */
		private $site_id = false;

		/**
		 * Site
		 *
		 * @var bool|mixed
		 */
		private $site = false;

		/**
		 * Post_SMTP_MWP_Rest_API constructor.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function __construct() {

			add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
			add_action( 'post_smtp_after_email_log_saved', array( $this, 'update_log_meta' ) );
		}


		/**
		 * Override Reply-To
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function rest_api_init() {

			register_rest_route(
				'post-smtp-for-mainwp/v1',
				'/send-email',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_email' ),
					'permission_callback' => '__return_true',
				)
			);
		}


		/**
		 * Validate
		 *
		 * @param   string $api_key API Key.
		 * @param   string $site_id Site ID.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function validate( $api_key, $site_id ) {

			if ( empty( $api_key )
				|| empty( $site_id )
			) {

				wp_send_json(
					array(
						'code'    => 'incomplete_request',
						'message' => 'Empty API-Key or Site-URL.',
					),
					404
				);

			}

			$child_enabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
			$child_key     = $child_enabled['key'];

			$option    = array(
				'pubkey' => true,
			);
			$site_data = apply_filters( 'mainwp_getdbsites', __FILE__, $child_key, array( $site_id ), array(), $option );

			if ( ! $site_data[ $site_id ] ) {

				wp_send_json(
					array(
						'code'    => 'site_not_found',
						'message' => 'Site not found.',
					),
					404
				);

			}

			// Let's allow request.
			if ( $site_data
				&& isset( $site_data[ $site_id ]->pubkey )
				&& md5( $site_data[ $site_id ]->pubkey ) === $api_key
			) {

				$this->site_id = $site_data[ $site_id ]->id;
				return true;

			}
		}


		/**
		 * Sends Email
		 *
		 * @param   WP_REST_Request $request Log ID.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function send_email( WP_REST_Request $request ) {

			$result  = false;
			$headers = $request->get_headers();
			$api_key = empty( $request->get_header( 'api_key' ) ) ? '' : sanitize_text_field( $request->get_header( 'api_key' ) );
			$site_id = empty( $request->get_header( 'site_id' ) ) ? '' : sanitize_text_field( $request->get_header( 'site_id' ) );

			if ( isset( $_GET['actionnonce'] ) // phpcs:disable WordPress.Security.NonceVerification
				&& isset( $_GET['pingnonce'] ) // phpcs:disable WordPress.Security.NonceVerification
				&& $this->validate( $api_key, $site_id )
			) {

				if ( apply_filters( 'mainwp_verify_ping_nonce', false, sanitize_text_field( wp_unslash( $_GET['pingnonce'] ) ), $this->site_id ) ) { // phpcs:disable WordPress.Security.NonceVerification

					$params        = array(
						'actionnonce' => sanitize_text_field( wp_unslash( $_GET['actionnonce'] ) ), // phpcs:disable WordPress.Security.NonceVerification
					);
					$child_enabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
					$child_key     = $child_enabled['key'];
					$result        = apply_filters( 'mainwp_fetchurlverifyaction', __FILE__, $child_key, $this->site_id, $params );

					// All set, let's send email xD.
					if ( is_array( $result ) && ! empty( $result['success'] ) ) {

						// Override settings if checked in MainWP -> Extensions -> Post SMTP -> Enable Individual Settings.
						$this->override_settings();

						$params      = $request->get_params();
						$to          = isset( $params['to'] ) ? $params['to'] : '';
						$subject     = isset( $params['subject'] ) ? $params['subject'] : '';
						$message     = isset( $params['message'] ) ? $params['message'] : '';
						$headers     = isset( $params['headers'] ) ? $params['headers'] : '';
						$attachments = isset( $params['attachments'] ) ? $params['attachments'] : array();

						// Lets upload files on server.
						if ( ! empty( $attachments ) ) {

							$_attachments = $attachments;
							$attachments  = array();
							foreach ( $_attachments as $key => $attachment ) {

								// Get the contents of the file.
								$file_info     = pathinfo( $key );
								$absolute_path = strstr( $file_info['dirname'], 'uploads/' );
								$absolute_path = str_replace( 'uploads', '', $absolute_path );
								$absolute_path = '/';

								// Define the filename and destination directory.
								$filename   = $file_info['basename'];
								$upload_dir = wp_upload_dir();

								// Create the file in the upload directory.
								$file_path   = $upload_dir['path'] . $absolute_path . $filename;
								$file_url    = $upload_dir['url'] . $absolute_path . $filename;
								$wp_filetype = wp_check_filetype( $filename, null );
								$file_data   = wp_upload_bits( $filename, null, $attachment );

								// Check if the file was successfully uploaded.
								if ( ! $file_data['error'] ) {
									// The file was uploaded successfully.
									// Insert the file into the media library.
									$attachment = array(
										'post_mime_type' => $wp_filetype['type'],
										'post_title'     => sanitize_file_name( $filename ),
										'post_content'   => '',
										'post_status'    => 'inherit',
									);

									$attachment_id = wp_insert_attachment( $attachment, $file_data['file'] );

									$attachments[] = $file_path;

								}
							}
						}

						$result = wp_mail( $to, $subject, $message, $headers, $attachments );

					}
				}
			}

			return $result;
		}


		/**
		 * Updates Log Meta
		 *
		 * @param   int $log_id Log ID.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function update_log_meta( $log_id ) {

			// Store Site ID, if log has been created :).
			if ( $this->site_id ) {

				postman_add_log_meta( $log_id, 'mainwp_child_site_id', $this->site_id );

			} else {

				postman_add_log_meta( $log_id, 'mainwp_child_site_id', 'main_site' );

			}
		}


		/**
		 * Override Settings
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function override_settings() {

			$saved_sites = get_option( 'post_smtp_mainwp_sites' );

			if ( ! empty( $saved_sites )
				&& isset( $saved_sites[ $this->site_id ] )
			) {

				$this->site = $saved_sites[ $this->site_id ];
				if ( ! empty( $saved_sites[ $this->site_id ]['email_address'] ) ) {

					add_filter( 'post_smtp_from_email_address', array( $this, 'override_from_email' ) );

				}
				if ( ! empty( $saved_sites[ $this->site_id ]['name'] ) ) {

					add_filter( 'post_smtp_from_name', array( $this, 'override_from_name' ) );

				}
				if ( ! empty( $saved_sites[ $this->site_id ]['reply_to'] ) ) {

					add_filter( 'post_smtp_reply_to', array( $this, 'override_reply_to' ) );

				}
			}
		}


		/**
		 * Override From Email
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function override_from_email() {

			return $this->site['email_address'];
		}


		/**
		 * Override From Name
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function override_from_name() {

			return $this->site['name'];
		}


		/**
		 * Override Reply-To
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function override_reply_to() {

			return $this->site['reply_to'];
		}
	}

	new Post_SMTP_MWP_Rest_API();
endif;
