<?php
/**
 * Video gallery item template.
 *
 * This template can be overridden by copying it to:
 * yourtheme/qspv-product-video/video-gallery-item.php
 *
 * HOWEVER, on occasion QuickShipD Product Video will need to update template files, and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it
 * does happen. When this occurs, the version of the template file will be
 * bumped and the readme will list any important changes.
 *
 * @package QuickShipD_Product_Video
 * @version 1.0
 *
 * Available variables:
 * @var string $video_type    One of 'youtube', 'vimeo', 'selfhost'.
 * @var string $embed_url     Iframe-embeddable URL.
 * @var string $thumbnail_url Thumbnail image URL (may be empty for self-hosted).
 * @var string $title         Product name for alt text.
 * @var int    $product_id    Product post ID.
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	class="woocommerce-product-gallery__image qspv-video-slide"
	data-video-type="<?php echo esc_attr( $video_type ); ?>"
	data-video-url="<?php echo esc_url( $embed_url ); ?>"
	<?php if ( $thumbnail_url ) : ?>
	data-thumb="<?php echo esc_url( $thumbnail_url ); ?>"
	data-thumb-alt="<?php echo esc_attr( $title ); ?>"
	<?php endif; ?>
>
	<div class="qspv-video-placeholder">
		<?php if ( $thumbnail_url ) : ?>
			<img
				src="<?php echo esc_url( $thumbnail_url ); ?>"
				alt="<?php echo esc_attr( $title ); ?>"
				loading="lazy"
			/>
		<?php endif; ?>

		<button class="qspv-play-btn" type="button" aria-label="<?php esc_attr_e( 'Play product video', 'quickshipd-product-video' ); ?>">
			<span class="qspv-play-icon" aria-hidden="true"></span>
		</button>
	</div>

	<div class="qspv-video-embed" style="display:none"></div>
</div>
