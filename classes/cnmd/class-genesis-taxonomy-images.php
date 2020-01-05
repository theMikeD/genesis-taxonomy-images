<?php

namespace cnmd;

/**
 * Class Genesis_Taxonomy_Images
 *
 * @package cnmd
 */
class Genesis_Taxonomy_Images {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	protected $plugin_name = 'Genesis Taxonomy Images';

	/**
	 * Minimum genesis version.
	 *
	 * @was GENESIS_TAXONOMY_IMAGES_VERSION
	 *
	 * @var string
	 */
	protected $genesis_minimum_version = '2.0.0';

	/**
	 * Local path to placeholder image.
	 *
	 * @var string
	 */
	private $default_placeholder_img_src = '/assets/images/placeholder.png';

	/**
	 * Key used to store the image ID in the database
	 *
	 * @var string
	 */
	protected $meta_key = 'term_thumbnail_id';

	/**
	 * Image width in the admin interface.
	 *
	 * @var int
	 */
	protected $default_image_width = 200;

	/**
	 * Image height in the admin interface.
	 *
	 * @var int
	 */
	protected $default_image_height = 200;


	/**
	 * class-genesistaxonomyimages constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			$this->init__admin();
		}
	}


	/**
	 * Does the setup for the admin side of things.
	 */
	private function init__admin() {
		if ( ! $this->is_using_genesis() ) {
			add_action( 'admin_notices', array( $this, 'warn_and_deactivate' ) );
		}
		$this->set_hooks__admin();
	}


	/**
	 * Gets the saved taxonomy image for a term, or a default image if a saved term is not set.
	 *
	 * Based heavily on Genesis genesis_get_image().
	 *
	 * @TODO: allow $args['fallback'] to accept a full url to a placeholder image OR a specific image ID
	 * @TODO: allow $args['format'] to return an image object if 'object' is supplied
	 *
	 * @return mixed HTML or src of this Term's image, or placeholder, or false
	 * @since  0.8.0
	 *
	 * @see Genesis lib/functions/image.php genesis_get_image()
	 * @was gtaxi_get_taxonomy_image
	 *
	 * @param array $args (
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
	 */
	public function get_term_image( $args = array() ) {
		global $wp_query;
		$term = '';

		// Set the default options.
		$defaults = array(
			'format'   => 'html',
			'size'     => 'full',
			'attr'     => '',
			'fallback' => 'placeholder',
			'term'     => '',
		);

		/**
		 * Filter the default options
		 *
		 * @since 0.8.0
		 *
		 * @param array $defaults The default options.
		 */
		$defaults = apply_filters( 'gtaxi_get_taxonomy_image_default_args', $defaults );

		// Merge the args sent to the function with the defaults.
		$args = wp_parse_args( $args, $defaults );

		if ( ! has_image_size( $args['size'] ) ) {
			$args['size'] = 'full';
		}

		// If we were sent a term object, use that.
		if ( $args['term'] ) {
			if ( is_a( $args['term'], 'WP_Term' ) ) {
				$term = $args['term'];
			} elseif ( is_int( $args['term'] ) ) {
				$term = get_term( $args['term'] );
			}
		}

		// If not, get the term object based on what we were sent instead.
		if ( ! $term ) {
			if ( ! is_category() && ! is_tag() && ! is_tax() ) {
				return null;
			}
			// @todo: multiples?
			$term = is_tax() ? get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) ) : $wp_query->get_queried_object();
		}

		// And if we *still* don't have a term object, bail.
		if ( ! $term ) {
			return null;
		}

		// So we have a term object. Now get the image id.
		$term_image_id = $this->get_term_thumbnail_id( $term );

		if ( ! $term_image_id && ! 'placeholder' === mb_strtolower( $args['fallback'], 'UTF-8' ) ) {
			return null;
		}

		return $this->get_requested_format( $term_image_id, $term, $args );
	}


	/**
	 * Get the saved image ID.
	 *
	 * @was rgc_get_term_meta
	 *
	 * @param \WP_Term $term  The term object.
	 *
	 * @return mixed|string
	 * @src:   @robincornett
	 */
	private function get_term_thumbnail_id( $term ) {
		$value = get_term_meta( $term->term_id, $this->get_meta_key(), true );
		// This is how genesis pre-2.3.0 did things. This can be removed in the future.
        // @see https://www.studiopress.com/important-announcement-for-genesis-plugin-developers/
		if ( ! $value && isset( $term->meta[ $this->get_meta_key() ] ) ) {
			$value = $term->meta[ $this->get_meta_key() ];
		}

		if ( $value ) {
			return esc_attr( (int) $value );
		}
		return null;
	}


	/**
	 * Gets the requested format for the term image, or the placeholder if it doesn't exist.
	 *
	 * @param int    $term_image_id   Term id.
	 * @param object $term            WP_Term object.
	 * @param array  $args            The args as sent to the main function.
	 *
	 * @return mixed|void|null
	 */
	private function get_requested_format( $term_image_id, $term, $args ) {
		if ( $term_image_id ) {
			// This sets up $url to be the first item of the returned array.
			list( $url ) = wp_get_attachment_image_src( $term_image_id, $args['size'], false );
			$url         = str_replace( home_url(), '', $url );
			switch ( mb_strtolower( $args['format'], 'UTF-8' ) ) {
				case 'id':
					$output = $term_image_id;
					break;
				case 'src':
				case 'url':
					$html = wp_get_attachment_image( $term_image_id, $args['size'], false, $args['attr'] );
					// This sets up $url to be the first item of the returned array.
					list( $url ) = wp_get_attachment_image_src( $term_image_id, $args['size'], false );
					$output      = $url;
					break;
				case 'html':
				default:
					$output = wp_get_attachment_image( $term_image_id, $args['size'], false, $args['attr'] );
					break;
			}
		} elseif ( 'placeholder' === mb_strtolower( $args['fallback'], 'UTF-8' ) ) {
			$url = str_replace( home_url(), '', $this->get_placeholder_img_src() );
			switch ( mb_strtolower( $args['format'], 'UTF-8' ) ) {
				case 'id':
					$output = null;
					break;
				case 'src':
				case 'url':
					$output = $url;
					break;
				case 'html':
				default:
					$output = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $term->name ) . ' term image" class="wp-post-image" height="48" width="48" />';
					break;
			}
		} else {
			return null;
		}

		/**
		 * Filter the retrieved and calculated values
		 *
		 * @since 0.8.0
		 *
		 * @param string $output The requested image in the requested 'format'. Note: if not filtering, this is what is returned
		 * @param string $args The arguments used
		 * @param string $term_image_id The ID of the image
		 * @param string $url The full url to the image
		 */
		return apply_filters( 'gtaxi_get_taxonomy_image', $output, $args, $term_image_id, $url );
	}


	/*
	 * Admin Only
	 */
	/**
	 * Sets hooks and filters for the admin.
	 */
	private function set_hooks__admin() {
		add_action( 'init', array( $this, 'do_taxonomy_hooks__admin' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_scripts__admin' ) );
	}


	/**
	 * Performs sanity checks for plugin requirements, used particularly if this plugin is left active and a Genesis child
	 * theme is not.
	 *
	 * Loaded via the init hook.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function warn_and_deactivate() {
		?>
		<div class="error notice is-dismissible">

			<p>
			<?php
				// translators: %1$s is the plugin name.
				sprintf( esc_html_e( '%1$s is active but disabled because the current theme is not using the Genesis framework. If you don\'t intend to use Genesis, you should disable this plugin', 'genesis-taxonomy-images' ), $this->get_plugin_name() );
			?>
			</p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
		</div>
		<?php
		if ( isset( $_GET['activate'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ) ) ) {
			unset( $_GET['activate'] );
		}
	}


	/**
	 * Loops through all registered taxonomies that have a public UI and adds necessary filters and actions to display
	 * and edit images in the taxonomies' terms admin screens.
	 *
	 * Hooked via the 'init' action with very low priority to ensure that any custom taxonomies have been
	 * registered and are available to us.
	 *
	 * Added gtaxi_taxonomies filter, props wpsmith
	 *
	 * @was   gtaxi_add_taxonomy_image_hooks
	 * @since 0.8.0
	 *
	 * @return void.
	 */
	public function do_taxonomy_hooks__admin() {
		foreach ( $this->get_taxonomies() as $tax ) {
			// See wp-admin/includes/class-wp-links-list-table.php for these two.
			add_filter( 'manage_edit-' . $tax . '_columns', array( $this, 'add_taxonomy_image_column__admin' ) );
			add_filter( 'manage_' . $tax . '_custom_column', array( $this, 'add_taxonomy_image_column_content__admin' ), 10, 3 );

			// Priority of 9 to insert this before Genesis term meta fields. See wp-admin/edit-tag-form.php.
			add_action( $tax . '_edit_form', array( $this, 'add_edit_term_fields__admin' ), 9, 2 );
		}
	}


	/**
	 * Enqueues the built-in WP media functionality if we're viewing a term edit screen and the current taxonomy is in
	 * the array of taxonomies generated by gtaxi_get_taxonomies()
	 *
	 * Hooked via 'admin_enqueue_scripts'
	 *
	 * @was   gtaxi_enqueue_media
	 * @since 0.8.0
	 *
	 * @return void
	 */
	public function enqueue_media_scripts__admin() {
		$screen = get_current_screen();
		if ( in_array( $screen->taxonomy, $this->get_taxonomies(), true ) ) {
			wp_enqueue_media();
		}
	}


	/**
	 * Adds a new 'Image' column to the taxonomy admin screen to the right of the checkbox column.
	 *
	 * Hooked to 'manage_edit-{$tax_name}_columns' filter.
	 *
	 * @since 0.8.0
	 * @was   gtaxi_add_taxonomy_image_column
	 *
	 * @param array $columns Default columns displayed in tax terms screen.
	 *
	 * @return array             Default plus new columns to be displayed in tax terms screen.
	 */
	public function add_taxonomy_image_column__admin( $columns ) {
		if ( empty( $columns ) || ! array_key_exists( 'cb', $columns ) ) {
			return $columns;
		}
		$new_columns = array();
		// 'cb' is the checkbox column, the one furthest to the left.
		$new_columns['cb'] = $columns['cb'];

		// This is the one we're adding, along with its title.
		$new_columns['thumb'] = __( 'Image', 'genesis-taxonomy-images' );

		// Now merge the two arrays, effectively inserting our new one after the cb column.
		unset( $columns['cb'] );
		return array_merge( $new_columns, $columns );
	}


	/**
	 * Populates the new 'Image' column in the taxonomy admin screen. If no image is set, a default image is displayed
	 *
	 * Hooked to 'manage_{$tax_name}_custom_column' filter.
	 *
	 * @since 0.8.0
	 * @was   gtaxi_add_taxonomy_image_column_content
	 *
	 * @param string $columns   All columns.
	 * @param string $column    Current column.
	 * @param int    $id        Term ID.
	 *
	 * @return string    Content for our new column
	 */
	public function add_taxonomy_image_column_content__admin( $columns, $column, $id ) {
		if ( 'thumb' !== $column ) {
			return $columns;
		}

		// Get the taxonomy name.
		if ( isset( $_GET['taxonomy'] ) ) {
			$taxonomy = sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) );
		} else {
			return $columns;
		}

		$term         = get_term_by( 'id', $id, $taxonomy );
		$thumbnail_id = $this->get_term_thumbnail_id( $term );

		// If we have an ID, go get the image URL.
		if ( $thumbnail_id ) {
			$image = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' )[0];
		} else {
			// If we don't have a valid $thumbnail_id, then show the default image.
			// @TODO: Should we also reset the stored value here if b) is the case?
			$image = $this->get_term_image(
				array(
					'term'   => $term,
					'format' => 'url',
				)
			);
		}
		$columns .= '<img src="' . esc_url( $image ) . '" alt="" class="wp-post-image" height="48" width="48" />';

		return $columns;
	}


	/**
	 * Display the selected term image and controls in the taxonomy term edit screen.
	 *
	 * Hooked via {$tax_name}_edit_form filter.
	 *
	 * Note that the Image attachment ID gets saved in the Genesis Term meta
	 * array automatically without this plugin having to deal with Saving data.
	 *
	 * @since 0.8.0
	 * @was   gtaxi_add_edit_term_fields
	 *
	 * @param object $term     Term being edited.
	 * @param string $taxonomy Taxonomy of the term being edited.
	 *
	 * @return void
	 */
	public function add_edit_term_fields__admin( $term, $taxonomy ) {
		$term_image_width  = $this->default_image_width . 'px';
		$term_image_height = $this->default_image_height . 'px';
		$term_image_url    = '';

		// @todo replace with function to the get the URL
		$term_image_id = $this->get_term_thumbnail_id( $term );

		if ( $term_image_id ) {
			list( $term_image_url, $term_image_width, $term_image_height ) = wp_get_attachment_image_src( $term_image_id, 'medium', false );
		}

		if ( ! $term_image_url ) {
			$term_image_url = $this->get_placeholder_img_src();
			$term_image_id  = '0';
		}

		?>
		<table class="form-table">
			<tbody>
			<tr class="form-field">
				<th scope="row"><label><?php esc_html_e( 'Term Image', 'genesis-taxonomy-images' ); ?></label></th>
				<td>
					<div id="term_thumbnail" style="float:left;margin-right:10px;">
						<img src="<?php echo esc_url( $term_image_url ); ?>" width="<?php echo esc_attr( $term_image_width ); ?>" height="<?php echo esc_attr( $term_image_height ); ?>" />
					</div>
					<div style="line-height:60px;">
						<input type="hidden" id="genesis-meta[<?php echo esc_attr( $this->get_meta_key() ); ?>]" name="genesis-meta[<?php echo esc_attr( $this->get_meta_key() ); ?>]" value="<?php echo esc_attr( $term_image_id ); ?>" />
						<button type="submit" id="upload_image_button" class="button"><?php esc_html_e( 'Set term image', 'genesis-taxonomy-images' ); ?></button>
						<button type="submit" id="remove_image_button" class="button"><?php esc_html_e( 'Remove term image', 'genesis-taxonomy-images' ); ?></button>
					</div>
					<script type="text/javascript">

						// Only show the "remove image" button when needed
						if ( '0' === jQuery('#genesis-meta\\[<?php echo esc_attr( $this->get_meta_key() ); ?>\\]').val() )
							jQuery('#remove_image_button').hide();

						// Uploading files
						var file_frame;

						// When the Set button is clicked
						jQuery(document).on( 'click', '#upload_image_button', function( event ){
							event.preventDefault();

							// If the media frame already exists, reopen it.
							if ( file_frame ) {
								file_frame.open();
								return;
							}

							// Create the media frame.
							file_frame = wp.media.frames.downloadable_file = wp.media({
								title: '<?php esc_html_e( 'Set term image', 'genesis-taxonomy-images' ); ?>',
								button: {
									text: '<?php esc_html_e( 'Set term image', 'genesis-taxonomy-images' ); ?>'
								},
								multiple: false
							});

							// When an image is selected
							file_frame.on( 'select', function() {
								var attachment = file_frame.state().get('selection').first().toJSON();
								var medium = attachment['sizes']['medium'];
								console.log(attachment);
								jQuery('#genesis-meta\\[<?php echo esc_attr( $this->get_meta_key() ); ?>\\]').val( attachment.id );
								jQuery('#term_thumbnail img').attr('src', medium.url ).height(medium.height).width(medium.width);
								jQuery('#remove_image_button').show();
							});

							// Finally, open the modal.
							file_frame.open();
						});

						// When the Remove button is clicked
						jQuery(document).on( 'click', '#remove_image_button', function( event ){
							jQuery('#term_thumbnail img').attr('src', '<?php echo esc_url( $this->get_placeholder_img_src() ); ?>').height(<?php echo esc_attr( $this->default_image_height ); ?>).width(<?php echo esc_attr( $this->default_image_width ); ?>);
							jQuery('#genesis-meta\\[<?php echo esc_attr( $this->get_meta_key() ); ?>\\]').val('0');
							jQuery('#remove_image_button').hide();
							return false;
						});

					</script>
					<div class="clear"></div>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}



	/*
	* Getters
	*/

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	protected function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get the minimum required Genesis version.
	 *
	 * @return string
	 */
	protected function get_genesis_minimum_version() {
		return $this->genesis_minimum_version;
	}

	/**
	 * Get the meta key used to store the value of hte image ID in teh database.
	 *
	 * @return string
	 */
	protected function get_meta_key() {
		return $this->meta_key;
	}


	/**
	 * Gets the list of supported taxonomies.
	 *
	 * Props wpsmith
	 *
	 * @since 0.8.1
	 *
	 * @return array     Array of supported taxonomies.
	 */
	protected function get_taxonomies() {

		/**
		 * Filter the list of applicable taxonomies.
		 *
		 * By default, all public taxonomies that have a public UI are returned
		 *
		 * @since 0.8.1
		 *
		 * @param array array Results from get_taxonomies( array( 'show_ui' => true ) )
		 */
		return apply_filters( 'gtaxi_get_taxonomies', get_taxonomies( array( 'show_ui' => true ) ) );
	}


	/**
	 * Gets the URL of the placeholder taxonomy term image. Includes a filter to allow users to override location
	 * of placeholder image.
	 *
	 * @since 0.8.0
	 * @was   gtaxi_get_placeholder_img_src
	 *
	 * @return string     URL of placeholder image.
	 */
	private function get_placeholder_img_src() {
		/**
		 * Filter the placeholder image.
		 *
		 * By default, uses a provided image
		 *
		 * @since 0.8.0
		 *
		 * @param string void URL to default image
		 */
		return apply_filters( 'gtaxi_get_placeholder_img_src', TIG_URL . $this->default_placeholder_img_src, TIG_BASENAME );
	}



	/*
	 * Conditionals
	*/

	/**
	 * Determine if the active theme is a genesis theme.
	 *
	 * @return bool|string               true if all tests pass
	 *                                   GENESIS if genesis is not running
	 *                                   GENESIS_VER if the minimum version is not met
	 */
	protected function is_using_genesis() {
		$theme = wp_get_theme();
		if ( ! $theme->parent() || 'genesis' !== strtolower( wp_get_theme()->parent()->get( 'Name' ) ) ) {
			return 'GENESIS';
		};
		if ( version_compare( wp_get_theme()->parent()->get( 'Version' ), $this->get_genesis_minimum_version() ) < 0 ) {
			return 'GENESIS_VER';
		}
		return true;
	}

}
