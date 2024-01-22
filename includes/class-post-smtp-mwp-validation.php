<?php
/**
 * Post SMTP MainWP Table
 *
 * @package Post SMTP MainWP Validation
 */

if ( ! class_exists( 'Post_SMTP_MWP_Validation' ) ) :

	/**
	 * Post SMTP MainWP Table
	 *
	 * @since   1.0.2
	 * @version 1.0.0
	 */
	class Post_SMTP_MWP_Validation {
		
		/**
		 * Constructor
		 *
		 * @since   1.0.2
		 * @version 1.0.0
		 */
		public function __construct() {
		
			
			
		}
		
		public function get_quota() {
			
			if( class_exists( 'PostmanTransportRegistry' ) ) {

				$active_transport = PostmanTransportRegistry::getInstance()->getActiveTransport();
				$active_transport = $active_transport ? $active_transport->getSlug() : false;
				$sites = false;

				if( $active_transport ) {

					if( function_exists( 'pseo_fs' ) && $active_transport === 'office365_api' ) {

						$sites = pseo_fs();

					}

					if( function_exists( 'pseas_fs' ) && $active_transport === 'aws_ses_api' ) {

						$sites = pseas_fs();

					}

					if( function_exists( 'zoh_fs' ) && $active_transport === 'zohomail_api' ) {

						$sites = zoh_fs();

					}

					if ( is_object( $sites ) ) {

						$license = $sites->_get_license();
						$sites = $license->quota;

					}

					if( is_int( $sites ) ) {

						$sites = $sites;

					}

				}
				
				return $sites;

			}
			
		}
		
	}

	new Post_SMTP_MWP_Validation();

endif;