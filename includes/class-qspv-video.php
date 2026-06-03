<?php
/**
 * Video URL utility — detection, parsing, and embed generation.
 *
 * All methods are static; this class holds no state and registers no hooks.
 *
 * @package QuickShipD_Product_Video
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class QSPV_Video
 *
 * Parses YouTube, Vimeo, and self-hosted video URLs into embed URLs and
 * thumbnail images. Handles sanitization of user-supplied URLs before storage.
 *
 * @since 1.0.0
 */
class QSPV_Video {

	/**
	 * Video type constants.
	 */
	const TYPE_YOUTUBE  = 'youtube';
	const TYPE_VIMEO    = 'vimeo';
	const TYPE_SELFHOST = 'selfhost';
	const TYPE_UNKNOWN  = '';

	/**
	 * Detect the video type from a URL.
	 *
	 * @param  string $url Raw video URL.
	 * @return string One of the TYPE_* constants.
	 */
	public static function detect_type( string $url ): string {
		if ( '' === $url ) {
			return self::TYPE_UNKNOWN;
		}

		if ( self::get_youtube_id( $url ) ) {
			return self::TYPE_YOUTUBE;
		}

		if ( self::get_vimeo_id( $url ) ) {
			return self::TYPE_VIMEO;
		}

		if ( preg_match( '/\.(mp4|mov|webm|ogg)(\?|$)/i', $url ) ) {
			return self::TYPE_SELFHOST;
		}

		return self::TYPE_UNKNOWN;
	}

	/**
	 * Extract the 11-character video ID from a YouTube URL.
	 *
	 * Handles watch?v=, youtu.be/, /embed/, and /shorts/ variants.
	 *
	 * @param  string $url Raw YouTube URL.
	 * @return string 11-character video ID, or empty string on no match.
	 */
	public static function get_youtube_id( string $url ): string {
		$patterns = array(
			'#youtube\.com/watch\?.*v=([a-zA-Z0-9_-]{11})#',
			'#youtu\.be/([a-zA-Z0-9_-]{11})#',
			'#youtube\.com/embed/([a-zA-Z0-9_-]{11})#',
			'#youtube\.com/shorts/([a-zA-Z0-9_-]{11})#',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}

		return '';
	}

	/**
	 * Extract the numeric video ID from a Vimeo URL.
	 *
	 * Handles vimeo.com/{id} and player.vimeo.com/video/{id}.
	 *
	 * @param  string $url Raw Vimeo URL.
	 * @return string Numeric video ID, or empty string on no match.
	 */
	public static function get_vimeo_id( string $url ): string {
		$patterns = array(
			'#vimeo\.com/(\d+)#',
			'#player\.vimeo\.com/video/(\d+)#',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}

		return '';
	}

	/**
	 * Build an iframe-embeddable URL from a raw video URL.
	 *
	 * @param  string  $url  Raw video URL.
	 * @param  array{autoplay?:bool,mute?:bool,loop?:bool,controls?:bool} $args Playback args.
	 * @return string Embed URL, or empty string for unknown video type.
	 */
	public static function get_embed_url( string $url, array $args = array() ): string {
		$type = self::detect_type( $url );

		if ( self::TYPE_YOUTUBE === $type ) {
			$id      = self::get_youtube_id( $url );
			$params  = array();

			if ( ! empty( $args['autoplay'] ) ) {
				$params['autoplay'] = '1';
			}
			if ( ! empty( $args['mute'] ) ) {
				$params['mute'] = '1';
			}
			if ( ! empty( $args['loop'] ) ) {
				$params['loop']      = '1';
				$params['playlist']  = $id;
			}
			if ( isset( $args['controls'] ) && ! $args['controls'] ) {
				$params['controls'] = '0';
			}

			$query = $params ? '?' . http_build_query( $params ) : '';

			return 'https://www.youtube.com/embed/' . $id . $query;
		}

		if ( self::TYPE_VIMEO === $type ) {
			$id     = self::get_vimeo_id( $url );
			$params = array();

			if ( ! empty( $args['autoplay'] ) ) {
				$params['autoplay'] = '1';
			}
			if ( ! empty( $args['mute'] ) ) {
				$params['muted'] = '1';
			}
			if ( ! empty( $args['loop'] ) ) {
				$params['loop'] = '1';
			}

			$query = $params ? '?' . http_build_query( $params ) : '';

			return 'https://player.vimeo.com/video/' . $id . $query;
		}

		if ( self::TYPE_SELFHOST === $type ) {
			return esc_url( $url );
		}

		return '';
	}

	/**
	 * Get the thumbnail image URL for a video.
	 *
	 * YouTube: maxresdefault.jpg (no API call needed).
	 * Vimeo:   fetched from the oEmbed endpoint and cached in a transient.
	 * Self-hosted: no remote thumbnail available.
	 *
	 * @param  string $url Raw video URL.
	 * @return string Thumbnail URL, or empty string when unavailable.
	 */
	public static function get_thumbnail_url( string $url ): string {
		$type = self::detect_type( $url );

		if ( self::TYPE_YOUTUBE === $type ) {
			$id = self::get_youtube_id( $url );
			return 'https://img.youtube.com/vi/' . $id . '/maxresdefault.jpg';
		}

		if ( self::TYPE_VIMEO === $type ) {
			return self::get_vimeo_thumbnail( $url );
		}

		if ( self::TYPE_SELFHOST === $type ) {
			return self::get_selfhost_thumbnail( $url );
		}

		return '';
	}

	/**
	 * Return the poster/thumbnail for a self-hosted video attachment.
	 *
	 * Looks up the WordPress attachment by URL and returns the featured image
	 * the user has set on the attachment post, or an auto-generated poster frame
	 * if WordPress created one during upload.
	 *
	 * @param  string $url Self-hosted video URL.
	 * @return string Thumbnail URL, or empty string when unavailable.
	 */
	private static function get_selfhost_thumbnail( string $url ): string {
		$attachment_id = attachment_url_to_postid( $url );

		if ( ! $attachment_id ) {
			return '';
		}

		// User-assigned featured image on the attachment (set via media library).
		$thumb_id = (int) get_post_thumbnail_id( $attachment_id );
		if ( $thumb_id ) {
			$thumb_url = wp_get_attachment_url( $thumb_id );
			return $thumb_url ? (string) $thumb_url : '';
		}

		// WordPress may auto-generate a poster frame and attach it as a child.
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( isset( $meta['image']['src'] ) && '' !== $meta['image']['src'] ) {
			return esc_url_raw( (string) $meta['image']['src'] );
		}

		return '';
	}

	/**
	 * Fetch the Vimeo thumbnail URL via the oEmbed API.
	 *
	 * Result is cached per video ID in a 7-day transient.
	 *
	 * @param  string $url Raw Vimeo URL.
	 * @return string Thumbnail URL, or empty string on failure.
	 */
	private static function get_vimeo_thumbnail( string $url ): string {
		$id = self::get_vimeo_id( $url );
		if ( '' === $id ) {
			return '';
		}

		$transient_key = 'qspv_vimeo_thumb_' . $id;
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$api_url  = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode( 'https://vimeo.com/' . $id );
		$response = wp_remote_get( $api_url, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache a short negative result so we don't hammer the API on failures.
			set_transient( $transient_key, '', 300 );
			return '';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$thumb = isset( $body['thumbnail_url'] ) ? esc_url_raw( (string) $body['thumbnail_url'] ) : '';

		set_transient( $transient_key, $thumb, WEEK_IN_SECONDS );

		return $thumb;
	}

	/**
	 * Sanitize a video URL for safe storage.
	 *
	 * @param  string $url Raw user-supplied URL.
	 * @return string Sanitized URL.
	 */
	public static function sanitize_url( string $url ): string {
		return esc_url_raw( wp_unslash( trim( $url ) ) );
	}

	/**
	 * Return true when the URL is a recognised video source.
	 *
	 * @param  string $url URL to test.
	 * @return bool
	 */
	public static function is_valid_url( string $url ): bool {
		return self::TYPE_UNKNOWN !== self::detect_type( $url );
	}
}
