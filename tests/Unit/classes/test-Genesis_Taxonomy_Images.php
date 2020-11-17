<?php

namespace cnmd;

class Genesis_Taxonomy_Images_Test extends \WP_UnitTestCase {

	public $instance;
	public $term_without_image;
	public $term_with_image;
	public $image_id;
	public $user_id;

	public $this_year;
	public $this_month;
	

	public function setUp() {
		parent::setUp();
		$this->this_year  = date('Y');
		$this->this_month = date('n');


		$this->user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user          = wp_set_current_user( $this->user_id );
		set_current_screen( 'edit-category' );

		$this->instance = new Genesis_Taxonomy_Images();

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
		$filename = plugin_dir_path( __FILE__ ) . '../../test_data/test-image.jpg';
		$contents = file_get_contents( $filename );
		$upload   = wp_upload_bits( basename( $filename ), null, $contents );
		$this->assertTrue( empty( $upload['error'] ) );
		$this->image_id = $this->_make_attachment( $upload );

		$retrieved = wp_get_attachment_url( $this->image_id );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg', $retrieved );

		add_term_meta( $this->term_with_image, 'term_thumbnail_id', $this->image_id );
	}

	public function tearDown() {
		// my tear down code here
		$this->remove_added_uploads();
		parent::tearDown();
	}

	/**
	 * Test for using the default image
	 */
	public function test_get_term_image__default_image() {
		// error_log( print_r( $term_id, true ),3, '/Volumes/EVO/unit_test.log');
		$opts      = array(
			'term'   => $this->term_without_image,
			'format' => 'src',
		);
		$retrieved = $this->instance->get_term_image( $opts );
		$this->assertEquals( $retrieved, '/wp-content/plugins/genesis-taxonomy-images/assets/images/placeholder.png' );

		$opts['format'] = 'url';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( $retrieved, '/wp-content/plugins/genesis-taxonomy-images/assets/images/placeholder.png' );

		$opts['format'] = 'id';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( $retrieved, '' );

		$opts['format'] = 'html';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( $retrieved, '<img src="/wp-content/plugins/genesis-taxonomy-images/assets/images/placeholder.png" alt="Dog term image" class="wp-post-image" height="48" width="48" />' );
	}


	/**
	 * Tests for using a term object
	 */
	public function test_get_term_image__valid_image__term_object() {
		$term = get_term( $this->term_with_image );
		$this->assertEquals( true, is_a( $term, 'WP_Term' ) );
		$opts      = array(
			'term'   => $term,
			'format' => 'src',
		);
		$retrieved = $this->instance->get_term_image( $opts );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg', $retrieved );

		$opts['format'] = 'url';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg', $retrieved );

		$opts['format'] = 'id';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( $this->image_id, $retrieved );

		$opts['format'] = 'html';
		$retrieved      = $this->instance->get_term_image( $opts );
		$expected       = '<img width="1000" height="1000" src="http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg" class="attachment-full size-full" alt="" loading="lazy" srcset="http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg 1000w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-300x300.jpg 300w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-150x150.jpg 150w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-768x768.jpg 768w" sizes="(max-width: 1000px) 100vw, 1000px" />';
		$this->assertEquals( $expected, $retrieved );
	}


	/**
	 * Tests for using a term ID
	 */
	public function test_get_term_image__valid_image__term_id() {
		$opts      = array(
			'term'   => $this->term_with_image,
			'format' => 'src',
		);
		$retrieved = $this->instance->get_term_image( $opts );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg', $retrieved );

		$opts['format'] = 'url';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg', $retrieved );

		$opts['format'] = 'id';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( $this->image_id, $retrieved );

		$opts['format'] = 'html';
		$retrieved      = $this->instance->get_term_image( $opts );
		$expected       = '<img width="1000" height="1000" src="http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg" class="attachment-full size-full" alt="" loading="lazy" srcset="http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg 1000w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-300x300.jpg 300w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-150x150.jpg 150w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-768x768.jpg 768w" sizes="(max-width: 1000px) 100vw, 1000px" />';
		$this->assertEquals( $expected, $retrieved );
	}


	/**
	 * Tests for corner cases
	 */
	public function test_get_term_image__corner_cases() {

		// Not sent a valid term, and placeholder is false
		$opts      = array(
			'term'     => $this->term_without_image,
			'format'   => 'src',
			'fallback' => '',
		);
		$retrieved = $this->instance->get_term_image( $opts );
		$this->assertEquals( '', $retrieved );

		$opts['format'] = 'url';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( '', $retrieved );

		$opts['format'] = 'id';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( '', $retrieved );

		$opts['format'] = 'html';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( '', $retrieved );

		// Term's meta key is sent via an array in term object
		$term = get_term( $this->term_without_image );
		$this->assertEquals( true, is_a( $term, 'WP_Term' ) );
		$term->meta = array( 'term_thumbnail_id' => $this->image_id );
		$opts      = array(
			'term'   => $term,
			'format' => 'src',
		);
		$retrieved = $this->instance->get_term_image( $opts );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg', $retrieved );

		$opts['format'] = 'url';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( 'http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg', $retrieved );

		$opts['format'] = 'id';
		$retrieved      = $this->instance->get_term_image( $opts );
		$this->assertEquals( $this->image_id, $retrieved );

		$opts['format'] = 'html';
		$retrieved      = $this->instance->get_term_image( $opts );
		$expected       = '<img width="1000" height="1000" src="http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg" class="attachment-full size-full" alt="" loading="lazy" srcset="http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image.jpg 1000w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-300x300.jpg 300w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-150x150.jpg 150w, http://example.org/wp-content/uploads/' . $this->this_year . '/' . $this->this_month . '/test-image-768x768.jpg 768w" sizes="(max-width: 1000px) 100vw, 1000px" />';
		$this->assertEquals( $expected, $retrieved );

		// Send an invalid term ID
		$opts      = array(
			'term'   => '1000',
			'format' => 'src',
		);
		$retrieved = $this->instance->get_term_image( $opts );
		$this->assertEquals( null, $retrieved );
	}
}
