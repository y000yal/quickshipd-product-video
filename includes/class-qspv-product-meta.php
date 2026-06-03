<?php
/**
 * Product and variation video meta fields.
 *
 * Adds a "Product Video" tab to the WooCommerce Product Data metabox,
 * handles save for both simple products and variable product variations,
 * and exposes variation video URLs to the front-end via WooCommerce's
 * variation data filter.
 *
 * @package QuickShipD_Product_Video
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class QSPV_Product_Meta
 *
 * @since 1.0.0
 */
class QSPV_Product_Meta {

	/**
	 * Register all hooks.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Attach WordPress / WooCommerce hooks.
	 *
	 * @return void
	 */
	private function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_tab_content' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render_variation_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_meta' ), 10, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_data' ), 10, 3 );
	}

	/**
	 * Add the "Product Video" tab to the Product Data metabox.
	 *
	 * @param  array<string, array<string, mixed>> $tabs Existing product data tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_product_tab( array $tabs ): array {
		$tabs['qspv_video'] = array(
			'label'    => __( 'Product Video', 'quickshipd-product-video' ),
			'target'   => 'qspv_video_product_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 65,
		);

		return $tabs;
	}

	/**
	 * Render the panel content for the Product Video tab.
	 *
	 * @return void
	 */
	public function render_tab_content(): void {
		global $post;

		$video_url  = (string) get_post_meta( $post->ID, '_qspv_video_url', true );
		$thumb_url  = (string) get_post_meta( $post->ID, '_qspv_video_thumbnail', true );
		$position   = (string) get_post_meta( $post->ID, '_qspv_video_position', true );
		$play_count = absint( get_post_meta( $post->ID, '_qspv_play_count', true ) );

		// Migrate legacy value.
		if ( 'first' === $position ) {
			$position = 'after_featured';
		}
		if ( '' === $position ) {
			$position = 'last';
		}
		?>
		<div id="qspv_video_product_data" class="panel woocommerce_options_panel">
			<?php wp_nonce_field( 'qspv_save_product_video', '_qspv_nonce' ); ?>

			<div class="options_group">
				<p class="form-field _qspv_video_url_field">
					<label for="_qspv_video_url">
						<?php esc_html_e( 'Video URL', 'quickshipd-product-video' ); ?>
						<span id="qspv_provider_badge" style="display:none"></span>
					</label>
					<input
						type="text"
						class="short"
						style="width:100%;max-width:380px"
						id="_qspv_video_url"
						name="_qspv_video_url"
						value="<?php echo esc_attr( $video_url ); ?>"
						placeholder="<?php esc_attr_e( 'YouTube, Vimeo, or .mp4 / .webm URL', 'quickshipd-product-video' ); ?>"
					/>
					<button
						type="button"
						class="button qspv-media-btn qspv-icon-btn"
						data-target="#_qspv_video_url"
						title="<?php esc_attr_e( 'Choose from Media Library', 'quickshipd-product-video' ); ?>"
					><span class="dashicons dashicons-admin-media"></span></button>
					<span id="qspv_admin_preview"></span>
				</p>

				<p class="form-field _qspv_video_thumbnail_field">
					<label>
						<?php esc_html_e( 'Video Thumbnail', 'quickshipd-product-video' ); ?>
						<span
							class="qs-tip"
							tabindex="0"
							data-tip="<?php esc_attr_e( 'Optional. Auto-fetched for YouTube/Vimeo. For uploaded videos, set one here or use the product\'s featured image as fallback.', 'quickshipd-product-video' ); ?>"
						>?</span>
					</label>
					<span class="qspv-thumb-picker">
						<span class="qspv-thumb-preview" style="<?php echo '' === $thumb_url ? 'display:none' : ''; ?>">
							<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" />
						</span>
						<input type="hidden" id="_qspv_video_thumbnail" name="_qspv_video_thumbnail" value="<?php echo esc_attr( $thumb_url ); ?>" />
						<button type="button" class="button qspv-thumb-choose-btn">
							<?php '' === $thumb_url ? esc_html_e( 'Choose Thumbnail', 'quickshipd-product-video' ) : esc_html_e( 'Change Thumbnail', 'quickshipd-product-video' ); ?>
						</button>
						<a href="#" class="qspv-thumb-remove-btn" style="<?php echo '' === $thumb_url ? 'display:none' : ''; ?>">
							<?php esc_html_e( 'Remove', 'quickshipd-product-video' ); ?>
						</a>
					</span>
				</p>
			</div>

			<div class="options_group">
				<p class="form-field _qspv_video_position_field">
					<label>
						<?php esc_html_e( 'Gallery Position', 'quickshipd-product-video' ); ?>
						<span
							class="qs-tip"
							tabindex="0"
							data-tip="<?php esc_attr_e( 'Drag to set where the video appears in the product gallery.', 'quickshipd-product-video' ); ?>"
						>?</span>
					</label>
					<input type="hidden" id="_qspv_video_position" name="_qspv_video_position" value="<?php echo esc_attr( $position ); ?>" />
					<?php
					// Build the three-item list in the order matching the saved position.
					$video_item    = '<li class="qspv-position-item qspv-position-video" data-id="video"><span class="qspv-position-handle dashicons dashicons-menu"></span><span class="dashicons dashicons-video-alt3"></span>' . esc_html__( 'Video', 'quickshipd-product-video' ) . '</li>';
					$featured_item = '<li class="qspv-position-item qspv-position-fixed" data-id="featured"><span class="dashicons dashicons-format-image"></span>' . esc_html__( 'Featured Image', 'quickshipd-product-video' ) . '</li>';
					$gallery_item  = '<li class="qspv-position-item qspv-position-fixed" data-id="gallery"><span class="dashicons dashicons-format-gallery"></span>' . esc_html__( 'Gallery Images', 'quickshipd-product-video' ) . '</li>';

					if ( 'before_featured' === $position ) {
						$order = $video_item . $featured_item . $gallery_item;
					} elseif ( 'after_featured' === $position ) {
						$order = $featured_item . $video_item . $gallery_item;
					} else {
						$order = $featured_item . $gallery_item . $video_item;
					}
					?>
					<ul class="qspv-position-sort" id="qspv_position_sort">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- All parts escaped above.
						echo $order;
						?>
					</ul>
				</p>
			</div>

			<?php if ( $play_count > 0 ) : ?>
			<div class="options_group">
				<p class="form-field">
					<label><?php esc_html_e( 'Play count', 'quickshipd-product-video' ); ?></label>
					<span class="qspv-play-count">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of video plays */
								_n( '%d play', '%d plays', $play_count, 'quickshipd-product-video' ),
								$play_count
							)
						);
						?>
					</span>
				</p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save the product video URL from the Product Data metabox.
	 *
	 * @param  int $post_id Product post ID.
	 * @return void
	 */
	public function save_product_meta( int $post_id ): void {
		if (
			! isset( $_POST['_qspv_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_qspv_nonce'] ) ), 'qspv_save_product_video' )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$raw_url      = isset( $_POST['_qspv_video_url'] ) ? sanitize_text_field( wp_unslash( $_POST['_qspv_video_url'] ) ) : '';
		$raw_thumb    = isset( $_POST['_qspv_video_thumbnail'] ) ? sanitize_text_field( wp_unslash( $_POST['_qspv_video_thumbnail'] ) ) : '';
		$raw_position = isset( $_POST['_qspv_video_position'] ) ? sanitize_key( wp_unslash( $_POST['_qspv_video_position'] ) ) : 'last';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$video_url = QSPV_Video::sanitize_url( $raw_url );
		$thumb_url = QSPV_Video::sanitize_url( $raw_thumb );
		$allowed_positions = array( 'before_featured', 'after_featured', 'last' );
		$position          = in_array( $raw_position, $allowed_positions, true ) ? $raw_position : 'last';

		if ( '' !== $video_url ) {
			update_post_meta( $post_id, '_qspv_video_url', $video_url );
		} else {
			delete_post_meta( $post_id, '_qspv_video_url' );
		}

		if ( '' !== $thumb_url ) {
			update_post_meta( $post_id, '_qspv_video_thumbnail', $thumb_url );
		} else {
			delete_post_meta( $post_id, '_qspv_video_thumbnail' );
		}

		update_post_meta( $post_id, '_qspv_video_position', $position );
	}

	/**
	 * Render a video URL field inside each variation row.
	 *
	 * @param  int      $loop           The variation loop index.
	 * @param  array    $variation_data Variation data array.
	 * @param  \WP_Post $variation      The variation post object.
	 * @return void
	 */
	public function render_variation_field( int $loop, array $variation_data, \WP_Post $variation ): void {
		$video_url = (string) get_post_meta( $variation->ID, '_qspv_video_url', true );
		?>
		<div class="form-row form-row-full qspv-variation-video">
			<label for="<?php echo esc_attr( 'qspv_variation_video_url_' . $loop ); ?>">
				<?php esc_html_e( 'Variation Video URL', 'quickshipd-product-video' ); ?>
			</label>
			<input
				type="text"
				class="short qspv-variation-video-input"
				id="<?php echo esc_attr( 'qspv_variation_video_url_' . $loop ); ?>"
				name="<?php echo esc_attr( 'qspv_variation_video_url[' . $loop . ']' ); ?>"
				value="<?php echo esc_attr( $video_url ); ?>"
				placeholder="<?php esc_attr_e( 'YouTube, Vimeo, or .mp4 / .webm URL', 'quickshipd-product-video' ); ?>"
			/>
			<button
				type="button"
				class="button qspv-media-btn qspv-icon-btn"
				data-target="#<?php echo esc_attr( 'qspv_variation_video_url_' . $loop ); ?>"
				title="<?php esc_attr_e( 'Choose from Media Library', 'quickshipd-product-video' ); ?>"
			><span class="dashicons dashicons-admin-media"></span></button>
			<span class="description">
				<?php esc_html_e( 'Optional: override the product video with a variation-specific video.', 'quickshipd-product-video' ); ?>
			</span>
		</div>
		<?php
	}

	/**
	 * Save the variation video URL.
	 *
	 * WooCommerce's variation save form already carries a nonce; no
	 * separate nonce is issued or verified here.
	 *
	 * @param  int $variation_id Variation post ID.
	 * @param  int $loop         The variation loop index.
	 * @return void
	 */
	public function save_variation_meta( int $variation_id, int $loop ): void {
		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce handled by WooCommerce variation save.
		$raw_url = '';
		if ( isset( $_POST['qspv_variation_video_url'] ) && is_array( $_POST['qspv_variation_video_url'] ) && isset( $_POST['qspv_variation_video_url'][ $loop ] ) ) {
			$raw_url = sanitize_text_field( wp_unslash( $_POST['qspv_variation_video_url'][ $loop ] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$video_url = QSPV_Video::sanitize_url( $raw_url );

		if ( '' !== $video_url ) {
			update_post_meta( $variation_id, '_qspv_video_url', $video_url );
		} else {
			delete_post_meta( $variation_id, '_qspv_video_url' );
		}
	}

	/**
	 * Expose the variation video URL in WooCommerce's variation data object.
	 *
	 * The front-end JS reads qspv_video_url from the found_variation event
	 * payload to swap the gallery video when a variation is selected.
	 *
	 * @param  array                   $data      Existing variation data.
	 * @param  \WC_Product_Variable    $product   Parent variable product.
	 * @param  \WC_Product_Variation   $variation The variation object.
	 * @return array
	 */
	public function add_variation_data( array $data, \WC_Product_Variable $product, \WC_Product_Variation $variation ): array {
		$video_url = (string) get_post_meta( $variation->get_id(), '_qspv_video_url', true );

		if ( '' !== $video_url ) {
			$data['qspv_video_url']   = QSPV_Video::get_embed_url( $video_url );
			$data['qspv_video_type']  = QSPV_Video::detect_type( $video_url );
			$data['qspv_video_thumb'] = QSPV_Video::get_thumbnail_url( $video_url );
		} else {
			$data['qspv_video_url']   = '';
			$data['qspv_video_type']  = '';
			$data['qspv_video_thumb'] = '';
		}

		return $data;
	}
}
