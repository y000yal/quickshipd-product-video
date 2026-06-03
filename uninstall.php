<?php
/**
 * Plugin uninstall handler.
 *
 * Removes all data created by QuickShipD Product Video when the plugin is deleted.
 *
 * @package QuickShipD_Product_Video
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_post_meta_by_key( '_qspv_video_url' );
delete_post_meta_by_key( '_qspv_video_thumbnail' );
delete_post_meta_by_key( '_qspv_video_position' );
delete_post_meta_by_key( '_qspv_play_count' );

$qspv_options = array(
	'qspv_autoplay',
	'qspv_mute',
	'qspv_loop',
	'qspv_controls',
	'qspv_schema_enabled',
	'qspv_tracking_enabled',
	'qspv_play_button_style',
);

foreach ( $qspv_options as $qspv_option ) {
	delete_option( $qspv_option );
}

// Remove cached Vimeo thumbnails.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_qspv_vimeo_thumb_%' OR option_name LIKE '_transient_timeout_qspv_vimeo_thumb_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall only; no alternative.
