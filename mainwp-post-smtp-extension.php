<?php
/**
 * Plugin Name: MainWP Post SMTP Extension
 * Plugin URI: https://mainwp.com/extension/post-smtp/
 * Description: MainWP Post SMTP extension allows you to manage SMTP of you all your child sites from one central location.
 * Version: 1.0.1
 * Author: Post SMTP
 * Text Domain: post-smtp
 * Author URI: https://postmansmtp.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package MainWP Post SMTP Extension
 */

define( 'POST_SMTP_MAINWP_FILE', __FILE__ );

/**
 * Post SMTP Missing Notice
 *
 * @since   1.0.0
 * @version 1.0.0
 */
function post_smtp_missing_notice() {

	$class   = 'notice notice-error';
	$message = __(
		'MainWP Post SMTP Extenstion requires Post SMTP plugin to be installed and activated.',
		'post-smtp'
	);

	printf(
		'<div class="%1$s"><p>%2$s</p></div>',
		esc_attr( $class ),
		esc_html( $message )
	);
}

if ( ! function_exists( 'is_plugin_active' ) ) {

	include ABSPATH . 'wp-admin/includes/plugin.php';

}

if ( ! is_plugin_active( 'post-smtp/postman-smtp.php' ) ) {

	add_action( 'admin_notices', 'post_smtp_missing_notice' );

} else {

	require 'includes/class-post-smtp-mainwp.php';

	Post_SMTP_MainWP::get_instance();

}
