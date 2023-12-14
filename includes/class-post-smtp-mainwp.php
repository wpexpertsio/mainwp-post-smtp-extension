<?php
/**
 * Post SMTP MainWP
 *
 * @package Post SMTP MainWP
 */

if ( ! class_exists( 'Post_SMTP_MainWP' ) ) :

	/**
	 * Class Post_SMTP_MainWP
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	class Post_SMTP_MainWP {



		/**
		 * Child Key
		 *
		 * @var $child_key string
		 */
		private $child_key = false;

		/**
		 * Instance of the class
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var     object
		 */
		private static $instance = null;


		/**
		 * Get the instance of the class
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function get_instance() {

			if ( null === self::$instance ) {

				self::$instance = new self();

			}

			return self::$instance;
		}


		/**
		 * Post_SMTP_MainWP constructor.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function __construct() {

			add_filter( 'mainwp_getextensions', array( $this, 'get_this_extension' ) );
			add_filter( 'mainwp_header_left', array( $this, 'change_title' ) );

			$mainwp_activated = apply_filters( 'mainwp_activated_check', false );

			if ( false !== $mainwp_activated ) {

				$this->start_post_smtp_mainwp();

			} else {

				add_action( 'mainwp_activated', array( $this, 'start_post_smtp_mainwp' ) );

			}
		}


		/**
		 * Get this extension | Filter Callback
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function get_this_extension( $extensions ) {

			$extensions[] = array(
				'plugin'   => POST_SMTP_MAINWP_FILE,
				'callback' => array( $this, 'post_smtp_mainwp_page' ),
			);

			return $extensions;
		}


		/**
		 * Start the plugin
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function start_post_smtp_mainwp() {

			global $child_enabled;
			$child_enabled = apply_filters( 'mainwp_extension_enabled_check', POST_SMTP_MAINWP_FILE );

			if ( ! $child_enabled ) {

				return;

			}

			$this->child_key = $child_enabled['key'];

			$this->init();
		}


		/**
		 * Post SMTP MainWP Page
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function post_smtp_mainwp_page() {

			$page = new Post_SMTP_MWP_Page();
			$page->page();
		}


		/**
		 * Change Title
		 *
		 * @param   Title $title string.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function change_title( $title ) {

			if ( 'Post Smtp/Postman//Core/' === $title ) {

				$title = 'Post SMTP';

			}

			return $title;
		}


		/**
		 * Initialize The Plugin
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function init() {

			include_once 'rest-api/v1/class-post-smtp-mwp-rest-api.php';
			include_once 'class-post-smtp-mwp-page.php';
			include_once 'class-post-smtp-mwp-table.php';
		}
	}

endif;
