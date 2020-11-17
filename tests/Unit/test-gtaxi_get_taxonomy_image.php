<?php

namespace cnmd;

class gtaxi_get_taxonomy_image_Test extends \WP_UnitTestCase {

	public $term_without_image;
	public $term_with_image;
	public $image_id;
	public $user_id;


	public function setUp() {
		parent::setUp();

		$this->user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user          = wp_set_current_user( $this->user_id );
		set_current_screen( 'edit-category' );

		$this->term_without_image = self::factory()->term->create(
				array(
						'taxonomy' => 'category',
						'name'     => 'Dog',
				)
		);
		$this->term_with_image    = self::factory()->term->create(
				array(
						'taxonomy' => 'category',
						'name'     => 'Cat',
				)
		);

		// @src https://core.trac.wordpress.org/browser/branches/4.5/tests/phpunit/tests/post/attachments.php#L24
		// @src https://core.trac.wordpress.org/browser/branches/4.5/tests/phpunit/includes/testcase.php#L722
		$filename = plugin_dir_path( __FILE__ ) . '../test_data/test-image.jpg';
		$contents = file_get_contents( $filename );
		$upload   = wp_upload_bits( basename( $filename ), null, $contents );
		$this->assertTrue( empty( $upload['error'] ) );
		$this->image_id = $this->_make_attachment( $upload );

		$retrieved = wp_get_attachment_url( $this->image_id );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . date('Y') . '/' . date('n') . '/test-image.jpg', $retrieved );

		add_term_meta( $this->term_with_image, 'term_thumbnail_id', $this->image_id );
	}

	public function tearDown() {
		// my tear down code here
		$this->remove_added_uploads();
		parent::tearDown();
	}

	/**
	 * Test for the wrapper function. The class tests all possible combinations; we're just testing that
	 * the wrapper does the expected thing correctly.
	 */
	public function test_get_term_image__default_image() {
		// error_log( print_r( $term_id, true ),3, '/Volumes/EVO/unit_test.log');

		// Get the default image correctly
		$opts      = array(
				'term'   => $this->term_without_image,
				'format' => 'src',
		);
		$retrieved = gtaxi_get_taxonomy_image( $opts );
		$this->assertEquals( $retrieved, '/wp-content/plugins/genesis-taxonomy-images/assets/images/placeholder.png' );

		// Get the term image with a valid term
		$term = get_term( $this->term_with_image );
		$this->assertEquals( true, is_a( $term, 'WP_Term' ) );
		$opts      = array(
				'term'   => $term,
				'format' => 'src',
		);
		$retrieved = gtaxi_get_taxonomy_image( $opts );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . date('Y') . '/' . date('n') . '/test-image.jpg', $retrieved );


		// Get the term image with a valid term ID
		$opts      = array(
				'term'   => $this->term_with_image,
				'format' => 'src',
		);
		$retrieved = gtaxi_get_taxonomy_image( $opts );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . date('Y') . '/' . date('n') . '/test-image.jpg', $retrieved );

	}


}
