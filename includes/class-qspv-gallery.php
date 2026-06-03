<?php
/**
 * Product gallery injection and frontend asset management.
 *
 * In WooCommerce 10.x the classic gallery template (product-image.php) calls
 * do_action( 'woocommerce_product_thumbnails' ) inside the
 * .woocommerce-product-gallery__wrapper div. WooCommerce's own gallery images
 * are output at priority 20 via that same action. We hook at priority 30 to
 * append the video slide after all product images.
 *
 * Block gallery support is handled via the render_block filter on the
 * woocommerce/product-gallery block.
 *
 * @package QuickShipD_Product_Video
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class QSPV_Gallery
 *
 * @since 1.0.0
 */
class QSPV_Gallery {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		// before_featured: prepend slide to the featured image HTML via a filter,
		// so it lands before the featured image regardless of WooCommerce version.
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'prepend_video_before_featured' ), 10, 2 );
		// after_featured: priority 15 — after featured image (output at ~10), before
		// additional gallery images (WC hooks at priority 20).
		add_action( 'woocommerce_product_thumbnails', array( $this, 'render_video_slide_after_featured' ), 15 );
		// last: priority 30 — after all product images.
		add_action( 'woocommerce_product_thumbnails', array( $this, 'render_video_slide_last' ), 30 );
		add_filter( 'render_block', array( $this, 'filter_block_gallery' ), 10, 2 );
	}

	/**
	 * Conditionally enqueue frontend assets.
	 *
	 * Assets are only loaded on single product pages that have a video URL.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_the_ID();

		if ( ! $product_id ) {
			return;
		}

		$video_url = (string) get_post_meta( $product_id, '_qspv_video_url', true );

		if ( '' === $video_url ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style(
			'qspv-frontend',
			QSPV_URL . 'assets/css/frontend' . $suffix . '.css',
			array(),
			QSPV_VERSION
		);

		wp_enqueue_script(
			'qspv-frontend',
			QSPV_URL . 'assets/js/frontend' . $suffix . '.js',
			array(),
			QSPV_VERSION,
			true
		);

		wp_localize_script(
			'qspv-frontend',
			'qspvData',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'qspv_track_play' ),
				'trackingEnabled' => 'yes' === get_option( 'qspv_tracking_enabled', 'yes' ) ? 1 : 0,
				'autoplay'        => 'yes' === get_option( 'qspv_autoplay',  'no'  ) ? 1 : 0,
				'mute'            => 'yes' === get_option( 'qspv_mute',      'yes' ) ? 1 : 0,
				'loop'            => 'yes' === get_option( 'qspv_loop',      'no'  ) ? 1 : 0,
				'controls'        => 'yes' === get_option( 'qspv_controls',  'yes' ) ? 1 : 0,
				'productId'       => $product_id,
			)
		);
	}

	/**
	 * Prepend the video slide to the featured image HTML when position is 'before_featured'.
	 *
	 * Hooks woocommerce_single_product_image_thumbnail_html, which fires for every
	 * gallery image. We only prepend when the attachment being filtered is the
	 * product's featured image, so the slide appears first in the gallery wrapper.
	 *
	 * @param  string $html          The gallery image HTML produced by WooCommerce.
	 * @param  int    $attachment_id The attachment ID being rendered.
	 * @return string
	 */
	public function prepend_video_before_featured( string $html, int $attachment_id ): string {
		if ( ! is_product() ) {
			return $html;
		}

		$product_id = get_the_ID();
		if ( ! $product_id ) {
			return $html;
		}

		// Only act on the featured image, not on additional gallery images.
		if ( (int) get_post_thumbnail_id( $product_id ) !== $attachment_id ) {
			return $html;
		}

		$position = (string) get_post_meta( $product_id, '_qspv_video_position', true );
		if ( 'before_featured' !== $position ) {
			return $html;
		}

		$video_url = (string) get_post_meta( $product_id, '_qspv_video_url', true );
		if ( '' === $video_url ) {
			return $html;
		}

		$slide_html = $this->build_slide_html( $product_id, $video_url );
		if ( '' === $slide_html ) {
			return $html;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- slide_html built with esc_* throughout.
		return $slide_html . $html;
	}

	/** @return void */
	public function render_video_slide_after_featured(): void {
		// 'first' is the legacy value — treat it as after_featured.
		$this->maybe_render_video_slide( 'after_featured' );
	}

	/** @return void */
	public function render_video_slide_last(): void {
		$this->maybe_render_video_slide( 'last' );
	}

	/**
	 * Output the video slide only when the saved position matches $when.
	 *
	 * @param  string $when 'after_featured' or 'last'.
	 * @return void
	 */
	private function maybe_render_video_slide( string $when ): void {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_the_ID();

		if ( ! $product_id ) {
			return;
		}

		$position = (string) get_post_meta( $product_id, '_qspv_video_position', true );

		// Migrate legacy 'first' value.
		if ( 'first' === $position ) {
			$position = 'after_featured';
		}

		if ( '' === $position ) {
			$position = 'last';
		}

		if ( $position !== $when ) {
			return;
		}

		$video_url = (string) get_post_meta( $product_id, '_qspv_video_url', true );

		if ( '' === $video_url ) {
			return;
		}

		$html = $this->build_slide_html( $product_id, $video_url );

		if ( '' === $html ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML constructed with esc_* functions throughout build_slide_html().
		echo $html;
	}

	/**
	 * Build the full video slide HTML string.
	 *
	 * Every attribute and value is escaped inline. The returned string is safe
	 * to echo directly; callers must add a phpcs:ignore comment on the echo.
	 *
	 * @param  int    $product_id Product post ID.
	 * @param  string $video_url  Raw video URL from postmeta.
	 * @return string Fully-escaped HTML string, or empty string on failure.
	 */
	private function build_slide_html( int $product_id, string $video_url ): string {
		$type         = QSPV_Video::detect_type( $video_url );
		$embed_url    = QSPV_Video::get_embed_url( $video_url );
		$custom_thumb  = (string) get_post_meta( $product_id, '_qspv_video_thumbnail', true );
		$thumbnail_url = '' !== $custom_thumb ? $custom_thumb : QSPV_Video::get_thumbnail_url( $video_url );

		if ( '' === $embed_url ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		$title   = $product instanceof WC_Product ? wp_strip_all_tags( $product->get_name() ) : '';

		// Fall back to the product featured image when no video thumbnail is available.
		if ( '' === $thumbnail_url ) {
			$featured_id = (int) get_post_thumbnail_id( $product_id );
			if ( $featured_id ) {
				$featured_src  = wp_get_attachment_image_src( $featured_id, 'woocommerce_single' );
				$thumbnail_url = $featured_src ? (string) $featured_src[0] : '';
			}
		}

		$btn_style = get_option( 'qspv_play_button_style', 'default' );
		$btn_class = 'minimal' === $btn_style ? ' qspv-style-minimal' : '';

		$html  = '<div';
		$html .= ' data-thumb="' . esc_url( $thumbnail_url ) . '"';
		$html .= ' data-thumb-alt="' . esc_attr( $title ) . '"';
		$html .= ' class="woocommerce-product-gallery__image qspv-video-slide' . esc_attr( $btn_class ) . '"';
		$html .= ' data-video-type="' . esc_attr( $type ) . '"';
		$html .= ' data-video-url="' . esc_url( $embed_url ) . '">';
		$html .= '<div class="qspv-video-placeholder">';

		if ( '' !== $thumbnail_url ) {
			$html .= '<img src="' . esc_url( $thumbnail_url ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" />';
		}

		$html .= '<button class="qspv-play-btn" type="button" aria-label="' . esc_attr__( 'Play product video', 'quickshipd-product-video' ) . '">';
		$html .= '<span class="qspv-play-icon" aria-hidden="true"></span>';
		$html .= '</button>';
		$html .= '</div>';
		$html .= '<div class="qspv-video-embed" style="display:none"></div>';
		$html .= '</div>';

		// Hidden anchor for variation-swap JS.
		$html .= '<div class="qspv-video-data" data-product-id="' . esc_attr( (string) $product_id ) . '" style="display:none"></div>';

		return $html;
	}

	/**
	 * Append video data to the WooCommerce Product Gallery Block output.
	 *
	 * Uses a hidden div that front-end JS reads to inject a video thumbnail
	 * into the block gallery without needing a custom block.
	 *
	 * @param  string $block_content Rendered block HTML.
	 * @param  array  $block         Block data array.
	 * @return string Block HTML with the video data div appended, or unchanged.
	 */
	public function filter_block_gallery( string $block_content, array $block ): string {
		if ( 'woocommerce/product-gallery' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		if ( ! is_product() ) {
			return $block_content;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return $block_content;
		}

		$video_url = (string) get_post_meta( $product->get_id(), '_qspv_video_url', true );

		if ( '' === $video_url ) {
			return $block_content;
		}

		$type          = QSPV_Video::detect_type( $video_url );
		$embed_url     = QSPV_Video::get_embed_url( $video_url );
		$custom_thumb  = (string) get_post_meta( $product->get_id(), '_qspv_video_thumbnail', true );
		$thumbnail_url = '' !== $custom_thumb ? $custom_thumb : QSPV_Video::get_thumbnail_url( $video_url );

		if ( '' === $embed_url ) {
			return $block_content;
		}

		$data_div  = '<div class="qspv-block-video-data"';
		$data_div .= ' data-video-type="' . esc_attr( $type ) . '"';
		$data_div .= ' data-video-url="' . esc_url( $embed_url ) . '"';
		$data_div .= ' data-thumbnail="' . esc_url( $thumbnail_url ) . '"';
		$data_div .= ' data-product-id="' . esc_attr( (string) $product->get_id() ) . '"';
		$data_div .= ' style="display:none"></div>';

		return $block_content . $data_div;
	}

	/**
	 * Locate a template, allowing theme overrides.
	 *
	 * Themes may override templates by placing them at:
	 * {theme}/qspv-product-video/{template_name}
	 *
	 * @param  string $template_name Template file name (e.g. video-gallery-item.php).
	 * @return string Absolute path to the template file.
	 */
	private function locate_template( string $template_name ): string {
		$theme_template = locate_template( 'qspv-product-video/' . $template_name );

		if ( $theme_template ) {
			return $theme_template;
		}

		return QSPV_PATH . 'templates/' . $template_name;
	}
}
