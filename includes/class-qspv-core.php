<?php
/**
 * Core plugin bootstrap — singleton orchestrator.
 *
 * Loads all class files and initialises each subsystem exactly once.
 *
 * @package QuickShipD_Product_Video
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class QSPV_Core
 *
 * Singleton that owns the plugin lifecycle. Loads dependencies and hands
 * each subsystem its own instance so hooks are registered in a predictable
 * order.
 *
 * @since 1.0.0
 */
final class QSPV_Core {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Admin settings handler.
	 *
	 * @var QSPV_Admin
	 */
	private QSPV_Admin $admin;

	/**
	 * Product / variation meta field handler.
	 *
	 * @var QSPV_Product_Meta
	 */
	private QSPV_Product_Meta $product_meta;

	/**
	 * Gallery injection and frontend asset handler.
	 *
	 * @var QSPV_Gallery
	 */
	private QSPV_Gallery $gallery;

	/**
	 * VideoObject JSON-LD schema handler.
	 *
	 * @var QSPV_Schema
	 */
	private QSPV_Schema $schema;

	/**
	 * AJAX play counter handler.
	 *
	 * @var QSPV_Tracking
	 */
	private QSPV_Tracking $tracking;

	/**
	 * Private constructor — use get_instance().
	 */
	private function __construct() {}

	/**
	 * Return (and lazy-create) the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Run one-time setup: load files then initialise subsystems.
	 *
	 * @return void
	 */
	private function setup(): void {
		$this->load_dependencies();
		$this->init_subsystems();
	}

	/**
	 * Require all include files.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		$base = QSPV_PATH . 'includes/';

		require_once $base . 'class-qspv-video.php';
		require_once $base . 'class-qspv-admin.php';
		require_once $base . 'class-qspv-product-meta.php';
		require_once $base . 'class-qspv-gallery.php';
		require_once $base . 'class-qspv-schema.php';
		require_once $base . 'class-qspv-tracking.php';
	}

	/**
	 * Instantiate each subsystem so it can register its own hooks.
	 *
	 * @return void
	 */
	private function init_subsystems(): void {
		$this->admin        = new QSPV_Admin();
		$this->product_meta = new QSPV_Product_Meta();
		$this->gallery      = new QSPV_Gallery();
		$this->schema       = new QSPV_Schema();
		$this->tracking     = new QSPV_Tracking();
	}
}
