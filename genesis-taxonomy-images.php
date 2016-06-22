<?php
/*
Plugin Name: Genesis Taxonomy Images
Plugin URI:  http://www.studiograsshopper.ch/projects/genesis-taxonomy-images
Description: Create and manage Taxonomy Images for the Genesis theme framework
Version:     1.1.1
Author:      studiograsshopper, themiked
Author URI:  http://www.studiograsshopper.ch
Text Domain: gtaxi
License:     GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Copyright 2013 Ade Walker (info@studiograsshopper.ch)
Copyright 2015 Mike Dickson (info@codenamemiked.com)

*/

// Prevent direct access to the plugin
if ( ! defined( 'ABSPATH' ) ) {
	exit( _( 'Sorry, you are not allowed to access this page directly.' ) );
}

// Define required constants
define( 'GTAXI_GEN_MIN_VER',	'2.0.0' );



register_activation_hook( __FILE__, 'gtaxi_activation' );
/**
 * gtaxi_activation() performs sanity checks for plugin requirements. Loaded via register_activation_hook.
 *
 * You may be thinking to yourself, "Self, why doesn't this guy use admin_notice to show the deactivation message?"
 * The reason is that admin_notices fires after admin_init and register_activation_hook,
 * so when we hook to it nothing will happen. So we are forced to use wp_die instead.
 *
 * @since 1.0.0
 * @author Nathan Rice, Remkus de Vries, Rian Rietveld, modified by @themiked
 * @access public
 * @return void
 */
function gtaxi_activation() {
	if ( basename( get_template_directory() ) != 'genesis' ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) { unset( $_GET['activate'] ); }
		$message = __( '<h3>Genesis Taxonomy Images activation cancelled</h3><p>This plugin requires the Genesis theme framework which is not currently active.</p>', 'gtaxi' ) . $message;
		wp_die( $message, 'Genesis Taxonomy Images', array( 'back_link' => true ) );
	}

	// Find Genesis Theme Data
	$theme   = wp_get_theme( 'genesis' );
	$version = $theme->get( 'Version' );

	//if ( version_compare( PARENT_THEME_VERSION, GTAXI_GEN_MIN_VER, '<' ) ) {
	if ( version_compare( $version, GTAXI_GEN_MIN_VER, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) { unset( $_GET['activate'] ); }
		$message = __( '<p><b>Genesis Taxonomy Images</b> activation <b>cancelled</b>.</p><p>This plugin requires the Genesis theme framework version ' . GTAXI_GEN_MIN_VER . ' or greater, and yours is ' . $version . '. </p>', 'gtaxi' ) . $message;
		wp_die( $message, 'Genesis Taxonomy Images', array( 'back_link' => true ) );
	}
}



add_action( 'init', 'gtaxi_genesis_checker' );
/**
 * gtaxi_genesis_checker() performs sanity checks for plugin requirements. Loaded via the init hook.
 *
 * @since 1.0.0
 *
 * @access public
 * @return void
 */
function gtaxi_genesis_checker() {
	// Note: PARENT_THEME_VERSION is set by Genesis
	if (  version_compare( PARENT_THEME_VERSION, GTAXI_GEN_MIN_VER, '<' ) ) {

		// If Genesis is not active or is 1.x, set an admin warning.
		add_action( 'admin_notices', 'gtaxi_admin_notice_deactivated' );

		/**
		 * gtaxi_admin_notice_deactivated() sets an admin notice describing why it was deactivated
		 *
		 * @since 0.9.0
		 *
		 * @access public
		 * @return void
		 */
		function gtaxi_admin_notice_deactivated() {
			$e = '<b>Genesis Taxonomy Images</b> is <b>active but disabled</b> because the current theme is not using the Genesis framework. If you don\'t intend to use Genesis, you should disable this plugin.';
			?>
			<div class="error notice is-dismissible">
				<p><?php echo $e; ?></p>
				<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
			</div>
			<?php
			if ( isset( $_GET['activate'] ) )
				unset( $_GET['activate'] );
		}
	}
}



add_action( 'genesis_init', 'gtaxi_init', 99 );
/**
 * gtaxi_init() is called via the genesis_init hook when the plugin is active. Sets up the plugin functionality.
 *
 * @since 1.0.0
 *
 * @access public
 * @return void
 */
function gtaxi_init() {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/genesis-taxonomy-image-functions.php' );
}
