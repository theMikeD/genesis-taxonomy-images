<?php

namespace cnmd;

/**
 * Class Activator
 *
 * Handles the activation of the plugin.
 *
 * @package cnmd
 */
class Activator {

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
	 * Activate the plugin. Called manually.
	 */
	public function activate() {
		$reason = $this->check_dependencies();
		if ( null !== $reason ) {
			$this->cancel_activation( $reason );
		}
	}


	/**
	 * Check for plugin dependencies
	 */
	public function check_dependencies() {
		$bail = null;

		$result = $this->is_using_genesis();
		if ( true !== $result ) {
			$bail = $result;
		}
		return $bail;
	}


	/**
	 * Handles the cancelling of activation.
	 *
	 * @param string $reason   The reason why the activation was cancelled.
	 */
	private function cancel_activation( $reason ) {
		deactivate_plugins( TIG_BASENAME );
		 // phpcs:disable
		if ( isset( $_GET['activate'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ) ) ) {
				unset( $_GET['activate'] );
		 }
		 // phpcs:enable
		$message = '<h3>' . $this->get_plugin_name() . ': ' . esc_html__( 'Activation cancelled', 'genesis-taxonomy-images' ) . '</h3><p>';
		switch ( $reason ) {
			case 'GENESIS':
				$message .= esc_html__( 'This plugin requires a theme using the Genesis framework.', 'genesis-taxonomy-images' );
				break;
			case 'GENESIS_VER':
				// translators: %1$s is the minimum version number.
				$message .= sprintf( esc_html__( 'This plugin requires a theme using the Genesis framework version %1$s or greater, which is not currently active.', 'genesis-taxonomy-images' ), $this->get_genesis_minimum_version() );
				$message .= esc_html__( ' The version you are running is too old.', 'genesis-taxonomy-images' );
				break;
		}
		$message .= '</p>';
		 // phpcs:disable
		 wp_die( $message, 'CNMD Vendors', array( 'back_link' => true ) );
		 // phpcs:enable
	}


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


	/**
	 * Gets the plugin name.
	 *
	 * @return string
	 */
	protected function get_plugin_name() {
		return $this->plugin_name;
	}


	/**
	 * Gets the minimum genesis version required for activation.
	 *
	 * @return string
	 */
	protected function get_genesis_minimum_version() {
		return $this->genesis_minimum_version;
	}

}
