<?php
/*
 * Plugin Name: MainWP Post SMTP Extension
 * Plugin URI: https://mainwp.com/extension/post-smtp/
 * Description: MainWP Post SMTP extension allows you to manage SMTP of you all your child sites from one central location.
 * Version: 1.0.0
 * Author: Post SMTP
 * Text Domain: post-smtp
 * Author URI: https://postmansmtp.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !class_exists( 'Post_SMTP_MainWP' ) ):

class Post_SMTP_MainWP {

    private $child_key = false;

    /**
     * Instance of the class
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @var object
     */
    private static $instance = null;


    /**
     * Get the instance of the class
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function get_instance() {

        if( null == self::$instance ) {

            self::$instance = new self;

        }

        return self::$instance;

    }


    /**
     * Post_SMTP_MainWP constructor.
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct() {
    
    	add_filter( 'mainwp_getextensions', array( $this, 'get_this_extension' ) );
		add_filter( 'mainwp_header_left', array( $this, 'change_title' ) );

        $mainWPActivated = apply_filters( 'mainwp_activated_check', false );

        if ( $mainWPActivated !== false ) {

            $this->start_post_smtp_mainwp();
        
        } 
        else {
        
            add_action( 'mainwp_activated', array( $this, 'start_post_smtp_mainwp' ) );
        
        }

    }


    /**
     * Get this extension | Filter Callback
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function get_this_extension() {

        $extensions[] = array(
            'plugin'    =>  __FILE__, 
            'callback'  =>  array( $this, 'post_smtp_mainwp_page' )
        );

        return $extensions;

    }


    /**
     * Start the plugin
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function start_post_smtp_mainwp() {

		global $childEnabled;
		$childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );

		if ( !$childEnabled ) {

			return;

		}

		$this->child_key = $childEnabled['key'];

		$this->init();

    }


    /**
     * Post SMTP MainWP Page
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function post_smtp_mainwp_page() {

        $page = new Post_SMTP_MWP_Page();
		$page->page();

    }
	
	
	public function change_title( $title ) {
		
		if( $title == 'Post Smtp/Postman//Core/' ) {
			
			$title = 'Post SMTP';
			
		}
		
		return $title;
		
	}


    /**
     * Initialize The Plugin
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function init() {

        require_once 'includes/rest-api/v1/class-psmp-rest-api.php';
        require_once 'includes/ps-mainwp-page.php';
        require_once 'includes/ps-mainwp-table.php';

    }

}

/**
 * Post SMTP Missing Notice
 * 
 * @since 1.0.0
 * @version 1.0.0
*/
function post_smtp_missing_notice() {

	$class = 'notice notice-error';
	$message = __( 'MainWP Post SMTP Extenstion requires Post SMTP plugin to be installed and activated.', 'post-smtp' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 

}

if( !function_exists( 'is_plugin_active' ) ) {
            
	include ABSPATH . 'wp-admin/includes/plugin.php';

}

if( !is_plugin_active( 'post-smtp/postman-smtp.php' ) ) {

	add_action( 'admin_notices', 'post_smtp_missing_notice' );

}
else {

	Post_SMTP_MainWP::get_instance();

}

endif;