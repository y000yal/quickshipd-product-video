<?php
/**
 * Plugin Name:       QuickShipD Product Video for WooCommerce
 * Plugin URI:        https://quickshipd.com/plugins/quickshipd-product-video/
 * Description:       Add YouTube, Vimeo, and self-hosted product videos to your WooCommerce product gallery. Includes VideoObject SEO schema and play tracking.
 * Version:           1.0.1
 * Author:            quickshipd
 * Author URI:        https://quickshipd.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quickshipd-product-video
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * WC requires at least: 8.0
 * WC tested up to:   10.5
 *
 * @package QuickShipD_Product_Video
 */

defined( 'ABSPATH' ) || exit;

define( 'QSPV_VERSION', '1.0.1' );
define( 'QSPV_PATH', plugin_dir_path( __FILE__ ) );
define( 'QSPV_URL', plugin_dir_url( __FILE__ ) );
define( 'QSPV_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Declare WooCommerce feature compatibility.
 */
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, true );
		}
	}
);

register_activation_hook( __FILE__, 'qspv_activate' );

add_action( 'plugins_loaded', 'qspv_init', 20 );

/**
 * Set default options on first activation.
 *
 * Uses add_option() so existing values are never overwritten on re-activation.
 *
 * @return void
 */
function qspv_activate(): void {
	add_option( 'qspv_autoplay', 'no' );
	add_option( 'qspv_mute', 'yes' );
	add_option( 'qspv_loop', 'no' );
	add_option( 'qspv_controls', 'yes' );
	add_option( 'qspv_schema_enabled', 'yes' );
	add_option( 'qspv_tracking_enabled', 'yes' );
	add_option( 'qspv_play_button_style', 'default' );
}

/**
 * Bootstrap the plugin after all plugins have loaded.
 *
 * @return void
 */
function qspv_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'qspv_missing_wc_notice' );
		return;
	}

	load_plugin_textdomain(
		'quickshipd-product-video',
		false,
		dirname( QSPV_BASENAME ) . '/languages'
	);

	require_once QSPV_PATH . 'includes/class-qspv-core.php';
	QSPV_Core::get_instance();
}

/**
 * Admin notice shown when WooCommerce is not active.
 *
 * @return void
 */
function qspv_missing_wc_notice(): void {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: WooCommerce plugin link */
					__( '<strong>QuickShipD Product Video</strong> requires %s to be installed and active.', 'quickshipd-product-video' ),
					'<a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>'
				)
			);
			?>
		</p>
	</div>
	<?php
}
