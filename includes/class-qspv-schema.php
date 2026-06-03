<?php
/**
 * VideoObject structured data (JSON-LD) output.
 *
 * Outputs a schema.org VideoObject script block in wp_head on single
 * product pages that have a video URL stored. The schema enables Google to
 * display video rich snippets in search results.
 *
 * @package QuickShipD_Product_Video
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class QSPV_Schema
 *
 * @since 1.0.0
 */
class QSPV_Schema {

	/**
	 * Register all hooks.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Attach WordPress hooks.
	 *
	 * @return void
	 */
	private function init(): void {
		add_action( 'wp_head', array( $this, 'output_schema' ) );
	}

	/**
	 * Output the VideoObject JSON-LD block in the page <head>.
	 *
	 * @return void
	 */
	public function output_schema(): void {
		if ( 'yes' !== get_option( 'qspv_schema_enabled', 'yes' ) ) {
			return;
		}

		if ( ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$video_url = (string) get_post_meta( $product->get_id(), '_qspv_video_url', true );

		if ( '' === $video_url ) {
			return;
		}

		$schema = $this->build_schema( $product, $video_url );

		if ( empty( $schema ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is inherently safe.
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	/**
	 * Build the schema.org VideoObject array for a product.
	 *
	 * Keys with empty values are stripped before returning so the JSON-LD
	 * output does not contain blank fields.
	 *
	 * @param  WC_Product $product   The product.
	 * @param  string     $video_url Raw video URL.
	 * @return array<string, mixed>
	 */
	private function build_schema( WC_Product $product, string $video_url ): array {
		$embed_url     = QSPV_Video::get_embed_url( $video_url );
		$thumbnail_url = QSPV_Video::get_thumbnail_url( $video_url );
		$description   = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );

		$schema = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'VideoObject',
			'name'         => wp_strip_all_tags( $product->get_name() ),
			'description'  => $description,
			'thumbnailUrl' => $thumbnail_url,
			'embedUrl'     => $embed_url,
			'uploadDate'   => get_the_date( 'c', $product->get_id() ),
		);

		// Remove keys whose value is empty so the output stays clean.
		return array_filter(
			$schema,
			static function ( $value ): bool {
				return '' !== $value && null !== $value;
			}
		);
	}
}
