<?php
/**
Plugin Name:       Genesis Taxonomy Images
Plugin URI:        https://wordpress.org/plugins/genesis-taxonomy-images/
Description:       Create and manage Taxonomy Images for the Genesis theme framework
Version:           2.0.3
Requires at least: 4.4.0
Requires PHP:      7.0
Author:            theMikeD
Author URI:        http://www.codenamemiked.com
License:           GNU General Public License v2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Based on "Genesis Taxonomy Images" plugin Copyright 2013 Ade Walker (info@studiograsshopper.ch)

@package CNMD\class-genesistaxonomyimages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'TIG_DIR', plugin_dir_path( __FILE__ ) );
define( 'TIG_URL', plugins_url( 'genesis-taxonomy-images' ) );
define( 'TIG_BASENAME', plugin_basename( __FILE__ ) );
register_activation_hook( __FILE__, 'cnmd_activate_tig' );

/**
 * The code that runs during plugin activation.
 *
 * @todo: if we take out the genesis requirement, we don't need this
 */
function cnmd_activate_tig() {
	$activator = new \cnmd\Activator();
	$activator->activate();
}


/**
 * The class autoloader.
 */
spl_autoload_register(
	function( $class ) {

		// Standard WP class name format:
		//   class-<class_name>.php
		$filepath = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
		$parts = pathinfo( $filepath );

		// If the files are sorted into folders mirroring the namespace prepended with 'classes/'.
		$filename = TIG_DIR . 'classes/' . $parts['dirname'] . '/class-' . strtolower( str_replace("_", "-", $parts['basename'] ) );
		if ( file_exists( $filename ) ) {
			include_once $filename;
			return;
		}
	}
);


/**
 * Initialize the plugin, but only for admin. On the front end it's called on-demand.
 *
 * @todo: this is called on demand so we don't need this
 */
function cnmd_init_tig() {
	if ( is_admin() ) {
		$tig = new \cnmd\Genesis_Taxonomy_Images();
	}
}

cnmd_init_tig();



/**
 * Gets the saved taxonomy image for a term, or a default image if a saved term is not set.
 *
 * Based heavily on Genesis genesis_get_image().
 *
 * @since  0.8.0
 *
 * @see Genesis lib/functions/image.php genesis_get_image()
 * @was gtaxi_get_taxonomy_image
 *
 * @param array $opts (
 *       string       'format'       The format to retrieve. One of:
 *                                     'html' creates a full HTML markup including srcset;
 *                                     'url' or 'src' gets just the image url;
 *                                     'id' gets the image ID.
 *                                     Default is 'html'
 *       string       'size'         Any built-in or user-defined media size. If missing or invalid, 'full' is used.
 *                                     Default is 'full'
 *       string|array 'attr'         Attributes applied to the requested image html. For details see
 *                                   https://codex.wordpress.org/Function_Reference/wp_get_attachment_image
 *                                     Default is ''
 *       string|bool  'fallback'     What to return if no term image is assigned.
 *                                   'placeholder' will return the default image in the requested format except for
 *                                     ID, which will return null (because there is no ID for the default image)
 *                                   Otherwise, return null.
 *                                     Default is 'placeholder'
 *       WP_Term      'term'         Term object, if you're after the image for a specific term.
 * )
 *
 * @return mixed HTML or src of this Term's image, or placeholder, or false
 */
function gtaxi_get_taxonomy_image( $opts ) {
	$gtaxi = new \cnmd\Genesis_Taxonomy_Images();
	return $gtaxi->get_term_image( $opts );
}


/**
 * Get the term meta.
 *
 * @deprecated 2.0.0
 *
 * @src: @robincornett
 *
 * @param $term object  The term
 * @param $key string   not used
 * @param string $value not used
 *
 * @return int|null
 */
function rgc_get_term_meta( $term, $key, $value = '' ) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'gtaxi_get_taxonomy_image( array( \'term\' => $term, \'format\' => \'id\' ) );');
	return gtaxi_get_taxonomy_image( array( 'term' => $term, 'format' => 'id' ) );
}
