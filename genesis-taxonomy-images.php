<?php
/*
Plugin Name: Genesis Taxonomy Images
Plugin URI: http://www.studiograsshopper.ch/projects/genesis-taxonomy-images
Version: 1.0.0
Author: theMikeD
Contributors: themiked
Author URI: http://codenamemiked.com
Description: Create and manage Taxonomy Images for the Genesis theme framework

License:

Copyright 2013 Ade Walker (info@studiograsshopper.ch)
Copyright 2015 Mike Dickson (info@codenamemiked.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Prevent direct access to the plugin
if ( ! defined( 'ABSPATH' ) ) {
	exit( _( 'Sorry, you are not allowed to access this page directly.' ) );
}

// Define required constants
define( 'GTAXI_VER', 			'1.0.0' );
define( 'GTAXI_GEN_MIN_VER',	'2.0.0' );


// Initialize when already active
add_action( 'genesis_init', 'gtaxi_init', 99 );



add_action( 'init', 'gtaxi_activation_dispatcher' );
/**
 * gtaxi_activation_dispatcher() performs sanity checks for plugin requirements. Loaded via the init hook.
 *
 * @since 1.0.0
 *
 * @access public
 * @return void
 */
function gtaxi_activation_dispatcher() {
	// Note: PARENT_THEME_VERSION is set by Genesis
	if (  version_compare( PARENT_THEME_VERSION, GTAXI_GEN_MIN_VER, '<' ) ) {

		// If Genesis is not active or is 1.x, deactivate the plugin...
		add_action( 'admin_init', 'gtaxi_deactivate' );

		// ...and set an admin notice saying why.
		add_action( 'admin_notices', 'gtaxi_admin_notice_deactivated' );


		/**
		 * gtaxi_deactivate() deactivates the plugin
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 * @return void
		 */
		function gtaxi_deactivate() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}


		/**
		 * gtaxi_admin_notice_deactivated() sets an admin notice describing why it was deactivated
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 * @return void
		 */
		function gtaxi_admin_notice_deactivated() {
			$e = '<b>Genesis Taxonomy Images</b> was <b>deactivated</b> because the active theme is not using the Genesis framework.';
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


/**
 * gtaxi_init() is called via the genesis_init hook. Sets up the plugin functionality.
 *
 * @since 1.0.0
 *
 * @access public
 * @return void
 */
function gtaxi_init() {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/genesis-taxonomy-image-functions.php' );
}

