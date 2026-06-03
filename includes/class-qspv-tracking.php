<?php
/**
 * AJAX play-count tracking.
 *
 * Increments the _qspv_play_count postmeta value each time a visitor
 * triggers a play event. Requests are nonced and the product ID is
 * validated before touching the database.
 *
 * @package QuickShipD_Product_Video
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class QSPV_Tracking
 *
 * @since 1.0.0
 */
class QSPV_Tracking {

	/**
	 * Register all hooks.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Attach AJAX action hooks for logged-in and guest visitors.
	 *
	 * @return void
	 */
	private function init(): void {
		add_action( 'wp_ajax_qspv_track_play',        array( $this, 'handle_track_play' ) );
		add_action( 'wp_ajax_nopriv_qspv_track_play', array( $this, 'handle_track_play' ) );
	}

	/**
	 * Handle an incoming play-tracking AJAX request.
	 *
	 * Verifies the nonce, validates the product ID, increments the counter,
	 * and returns a JSON response. Tracking can be disabled from the settings
	 * page; disabled requests receive a 403 error response.
	 *
	 * @return void
	 */
	public function handle_track_play(): void {
		if ( 'yes' !== get_option( 'qspv_tracking_enabled', 'yes' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Tracking is disabled.', 'quickshipd-product-video' ) ),
				403
			);
			return;
		}

		if ( ! check_ajax_referer( 'qspv_track_play', 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid request.', 'quickshipd-product-video' ) ),
				403
			);
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified via check_ajax_referer() above.
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! $product_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid product.', 'quickshipd-product-video' ) ),
				400
			);
			return;
		}

		$post = get_post( $product_id );

		if ( ! $post || 'product' !== $post->post_type ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid product.', 'quickshipd-product-video' ) ),
				400
			);
			return;
		}

		$count = absint( get_post_meta( $product_id, '_qspv_play_count', true ) );
		update_post_meta( $product_id, '_qspv_play_count', $count + 1 );

		wp_send_json_success( array( 'count' => $count + 1 ) );
	}
}
