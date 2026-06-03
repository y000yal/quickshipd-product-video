<?php
/**
 * Admin settings page.
 *
 * @package QuickShipD_Product_Video
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class QSPV_Admin
 *
 * @since 1.0.0
 */
class QSPV_Admin {

	/**
	 * Register all hooks.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Attach WordPress admin hooks.
	 *
	 * @return void
	 */
	private function init(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_qspv_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_qspv_restore_defaults', array( $this, 'ajax_restore_defaults' ) );
	}

	/**
	 * Add the QuickShipD Product Video submenu under the WooCommerce menu.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'QuickShipD Product Video for WooCommerce', 'quickshipd-product-video' ),
			__( 'Product Video', 'quickshipd-product-video' ),
			'manage_woocommerce',
			'quickshipd-product-video',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register all plugin settings with the WordPress Settings API.
	 *
	 * Used only to render fields via do_settings_sections() — saving is handled
	 * by ajax_save_settings() rather than the standard options.php form submit.
	 *
	 * @return void
	 */
	public function register_settings(): void {

		// ------------------------------------------------------------------ //
		// Playback tab.
		// ------------------------------------------------------------------ //
		$this->register_section( 'qspv_playback_general', __( 'Playback Defaults', 'quickshipd-product-video' ), 'playback' );

		$this->register_field(
			'qspv_autoplay',
			__( 'Autoplay', 'quickshipd-product-video' ),
			'render_toggle',
			'playback',
			'qspv_playback_general',
			array(
				'id'      => 'qspv_autoplay',
				'default' => 'no',
				'tooltip' => __( 'Autoplay video when the gallery slide becomes active. Most browsers require Mute to be enabled for autoplay to work.', 'quickshipd-product-video' ),
			)
		);

		$this->register_field(
			'qspv_mute',
			__( 'Mute by default', 'quickshipd-product-video' ),
			'render_toggle',
			'playback',
			'qspv_playback_general',
			array(
				'id'      => 'qspv_mute',
				'default' => 'yes',
				'tooltip' => __( 'Start video muted. Required for autoplay in most browsers.', 'quickshipd-product-video' ),
			)
		);

		$this->register_field(
			'qspv_loop',
			__( 'Loop', 'quickshipd-product-video' ),
			'render_toggle',
			'playback',
			'qspv_playback_general',
			array(
				'id'      => 'qspv_loop',
				'default' => 'no',
				'tooltip' => __( 'Loop the video continuously after it ends.', 'quickshipd-product-video' ),
			)
		);

		$this->register_field(
			'qspv_controls',
			__( 'Show player controls', 'quickshipd-product-video' ),
			'render_toggle',
			'playback',
			'qspv_playback_general',
			array(
				'id'      => 'qspv_controls',
				'default' => 'yes',
				'tooltip' => __( 'Show the player\'s native controls (play, pause, volume, etc.). Applies to self-hosted videos only; YouTube and Vimeo always show their own controls.', 'quickshipd-product-video' ),
			)
		);

		// ------------------------------------------------------------------ //
		// Features tab.
		// ------------------------------------------------------------------ //
		$this->register_section( 'qspv_features_general', __( 'Features', 'quickshipd-product-video' ), 'features' );

		$this->register_field(
			'qspv_schema_enabled',
			__( 'VideoObject SEO schema', 'quickshipd-product-video' ),
			'render_toggle',
			'features',
			'qspv_features_general',
			array(
				'id'      => 'qspv_schema_enabled',
				'default' => 'yes',
				'tooltip' => __( 'Output a schema.org VideoObject JSON-LD block on product pages. Enables Google video rich snippets in search results.', 'quickshipd-product-video' ),
			)
		);

		$this->register_field(
			'qspv_tracking_enabled',
			__( 'Enable play tracking', 'quickshipd-product-video' ),
			'render_toggle',
			'features',
			'qspv_features_general',
			array(
				'id'      => 'qspv_tracking_enabled',
				'default' => 'yes',
				'tooltip' => __( 'Record how many times each product video is played. The count appears in the Product Video tab on each product.', 'quickshipd-product-video' ),
			)
		);

		$this->register_section( 'qspv_features_appearance', __( 'Appearance', 'quickshipd-product-video' ), 'features' );

		$this->register_field(
			'qspv_play_button_style',
			__( 'Play button style', 'quickshipd-product-video' ),
			'render_play_button_style',
			'features',
			'qspv_features_appearance',
			array(
				'id'      => 'qspv_play_button_style',
				'default' => 'default',
				'tooltip' => __( 'Visual style of the play button overlay shown on the video thumbnail.', 'quickshipd-product-video' ),
			)
		);
	}

	// -----------------------------------------------------------------------
	// Page render.
	// -----------------------------------------------------------------------

	/**
	 * Render the full settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'quickshipd-product-video' ) );
		}

		$tabs = array(
			'playback' => array(
				'label' => __( 'Playback', 'quickshipd-product-video' ),
				'icon'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><polygon points="10,8 16,12 10,16" fill="currentColor"/></svg>',
			),
			'features' => array(
				'label' => __( 'Features', 'quickshipd-product-video' ),
				'icon'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" fill="none"/></svg>',
			),
		);

		$first_tab = (string) array_key_first( $tabs );
		?>
		<div class="wrap qspv-settings-wrap">

			<div class="qspv-page-header">
				<h1 class="qspv-page-title">
					<span class="qspv-title-icon">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
							<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
							<polygon points="10,8 16,12 10,16" fill="currentColor"/>
						</svg>
					</span>
					<?php esc_html_e( 'Product Video', 'quickshipd-product-video' ); ?>
				</h1>
				<span class="qspv-version-badge">v<?php echo esc_html( QSPV_VERSION ); ?></span>
			</div>

			<div class="qspv-layout">

				<!-- Left: tabs + settings -->
				<div class="qspv-layout-left">

					<nav class="qspv-tab-nav" aria-label="<?php esc_attr_e( 'Settings tabs', 'quickshipd-product-video' ); ?>">
						<?php foreach ( $tabs as $tab_key => $tab_data ) : ?>
							<button type="button" class="qspv-tab-btn <?php echo $first_tab === $tab_key ? 'is-active' : ''; ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded SVG.
								echo $tab_data['icon'];
								echo esc_html( $tab_data['label'] );
								?>
							</button>
						<?php endforeach; ?>
					</nav>

					<div class="qspv-settings-form">
						<?php foreach ( $tabs as $tab_key => $unused ) : ?>
							<div class="qspv-tab-pane <?php echo $first_tab === $tab_key ? 'is-active' : ''; ?>" id="qspv-tab-<?php echo esc_attr( $tab_key ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
								<?php do_settings_sections( 'qspv-' . $tab_key ); ?>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="qspv-save-bar">
						<button type="button" id="qspv-save-btn" class="button button-primary">
							<?php esc_html_e( 'Save Settings', 'quickshipd-product-video' ); ?>
						</button>
						<span class="qspv-save-status" id="qspv-save-status"></span>
						<button type="button" id="qspv-restore-btn" class="button button-secondary pv-restore-btn">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="vertical-align:middle;margin-right:4px;margin-top:-2px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 3v5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<?php esc_html_e( 'Restore Defaults', 'quickshipd-product-video' ); ?>
						</button>
					</div>

				</div><!-- /.qspv-layout-left -->

				<!-- Right: quick-start info card -->
				<div class="qspv-layout-right">
					<?php $this->render_info_card(); ?>
				</div>

			</div><!-- /.qspv-layout -->

			<footer class="qspv-settings-footer">
				<div class="pv-footer-left">
					<svg class="pv-footer-logo" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/>
						<polygon points="10,8 16,12 10,16" fill="currentColor"/>
					</svg>
					<?php
					printf(
						/* translators: %s: plugin version number */
						esc_html__( 'Product Video v%s', 'quickshipd-product-video' ),
						esc_html( QSPV_VERSION )
					);
					?>
					&nbsp;&mdash;&nbsp;
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: QuickShipD plugin link */
							__( 'Also by QuickShipD: %s &mdash; Estimated delivery dates for WooCommerce.', 'quickshipd-product-video' ),
							'<a href="https://wordpress.org/plugins/quickshipd/" target="_blank" rel="noopener noreferrer">QuickShipD</a>'
						)
					);
					?>
				</div>
			</footer>

		</div>
		<?php
	}

	/**
	 * Render the right-column quick-start info card.
	 *
	 * @return void
	 */
	private function render_info_card(): void {
		?>
		<div class="qspv-info-card">
			<div class="qspv-info-card__header">
				<p class="qspv-info-card__label"><?php esc_html_e( 'Quick Start', 'quickshipd-product-video' ); ?></p>
			</div>
			<ol class="qspv-info-card__steps">
				<li><?php esc_html_e( 'Open any product and go to the Product Data panel.', 'quickshipd-product-video' ); ?></li>
				<li><?php esc_html_e( 'Click the "Product Video" tab.', 'quickshipd-product-video' ); ?></li>
				<li><?php esc_html_e( 'Paste a YouTube, Vimeo, or direct video URL and save.', 'quickshipd-product-video' ); ?></li>
				<li><?php esc_html_e( 'Visit the product page — your video appears in the gallery.', 'quickshipd-product-video' ); ?></li>
			</ol>
			<hr class="qspv-info-card__divider">
			<ul class="qspv-info-card__features">
				<li>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php esc_html_e( 'YouTube, Vimeo &amp; self-hosted videos', 'quickshipd-product-video' ); ?>
				</li>
				<li>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php esc_html_e( 'Per-variation video support', 'quickshipd-product-video' ); ?>
				</li>
				<li>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php esc_html_e( 'VideoObject SEO schema', 'quickshipd-product-video' ); ?>
				</li>
				<li>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php esc_html_e( 'Play count tracking', 'quickshipd-product-video' ); ?>
				</li>
				<li>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php esc_html_e( 'Classic &amp; Block gallery support', 'quickshipd-product-video' ); ?>
				</li>
			</ul>
		</div>
		<?php
	}

	// -----------------------------------------------------------------------
	// Field renderers.
	// -----------------------------------------------------------------------

	/**
	 * Render a yes/no field as a toggle switch.
	 *
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_toggle( array $args ): void {
		$id      = $args['id'] ?? '';
		$default = $args['default'] ?? 'no';
		$value   = get_option( $id, $default );
		printf(
			'<label class="qspv-toggle"><span class="qspv-toggle__switch"><input type="checkbox" class="qspv-toggle__input" id="%1$s" name="%1$s" value="yes" %2$s><span class="qspv-toggle__track" aria-hidden="true"></span></span></label>',
			esc_attr( $id ),
			checked( 'yes', $value, false )
		);
	}

	/**
	 * Render the play button style select field.
	 *
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_play_button_style( array $args ): void {
		$id      = $args['id'] ?? 'qspv_play_button_style';
		$default = $args['default'] ?? 'default';
		$value   = get_option( $id, $default );
		$options = array(
			'default' => __( 'Default (circle with arrow)', 'quickshipd-product-video' ),
			'minimal' => __( 'Minimal (small triangle only)', 'quickshipd-product-video' ),
		);
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">';
		foreach ( $options as $key => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	// -----------------------------------------------------------------------
	// Asset loading.
	// -----------------------------------------------------------------------

	/**
	 * Enqueue admin assets — settings page + product editor.
	 *
	 * @param  string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$suffix           = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$is_settings_page = 'woocommerce_page_quickshipd-product-video' === $hook;
		$screen           = get_current_screen();
		$is_product_page  = $screen && 'product' === $screen->post_type && 'post' === $screen->base;

		if ( ! $is_settings_page && ! $is_product_page ) {
			return;
		}

		wp_enqueue_style(
			'qspv-admin',
			QSPV_URL . 'assets/css/admin' . $suffix . '.css',
			array(),
			QSPV_VERSION
		);

		$script_deps = array( 'jquery' );
		if ( $is_product_page ) {
			$script_deps[] = 'jquery-ui-sortable';
			$script_deps[] = 'media-upload';
		}

		wp_enqueue_script(
			'qspv-admin',
			QSPV_URL . 'assets/js/admin' . $suffix . '.js',
			$script_deps,
			QSPV_VERSION,
			true
		);

		if ( $is_settings_page ) {
			wp_localize_script(
				'qspv-admin',
				'qspvAdmin',
				array(
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'saveNonce'    => wp_create_nonce( 'qspv_save_settings' ),
					'restoreNonce' => wp_create_nonce( 'qspv_restore_defaults' ),
					'savedText'    => __( 'Settings saved.', 'quickshipd-product-video' ),
					'restoredText' => __( 'Defaults restored.', 'quickshipd-product-video' ),
					'errorText'    => __( 'Could not save. Please try again.', 'quickshipd-product-video' ),
					'confirmText'  => __( 'Reset all settings to their default values?', 'quickshipd-product-video' ),
					'isSettings'   => true,
				)
			);
		}
	}

	// -----------------------------------------------------------------------
	// AJAX handlers.
	// -----------------------------------------------------------------------

	/**
	 * AJAX handler: save settings for a single tab.
	 *
	 * @return void
	 */
	public function ajax_save_settings(): void {
		check_ajax_referer( 'qspv_save_settings', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by check_ajax_referer() above.
		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';

		switch ( $tab ) {
			case 'playback':
				update_option( 'qspv_autoplay', $this->sanitize_yes_no( isset( $_POST['qspv_autoplay'] ) ? sanitize_text_field( wp_unslash( $_POST['qspv_autoplay'] ) ) : null ) );
				update_option( 'qspv_mute',     $this->sanitize_yes_no( isset( $_POST['qspv_mute'] )     ? sanitize_text_field( wp_unslash( $_POST['qspv_mute'] ) )     : null ) );
				update_option( 'qspv_loop',     $this->sanitize_yes_no( isset( $_POST['qspv_loop'] )     ? sanitize_text_field( wp_unslash( $_POST['qspv_loop'] ) )     : null ) );
				update_option( 'qspv_controls', $this->sanitize_yes_no( isset( $_POST['qspv_controls'] ) ? sanitize_text_field( wp_unslash( $_POST['qspv_controls'] ) ) : null ) );
				break;

			case 'features':
				update_option( 'qspv_schema_enabled',   $this->sanitize_yes_no( isset( $_POST['qspv_schema_enabled'] )   ? sanitize_text_field( wp_unslash( $_POST['qspv_schema_enabled'] ) )   : null ) );
				update_option( 'qspv_tracking_enabled', $this->sanitize_yes_no( isset( $_POST['qspv_tracking_enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['qspv_tracking_enabled'] ) ) : null ) );
				update_option( 'qspv_play_button_style', $this->sanitize_play_button_style( sanitize_key( wp_unslash( $_POST['qspv_play_button_style'] ?? 'default' ) ) ) );
				break;

			default:
				wp_send_json_error( array( 'message' => 'unknown_tab' ) );
				return;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing

		wp_send_json_success( array( 'message' => 'saved' ) );
	}

	/**
	 * AJAX handler: reset all settings to factory defaults.
	 *
	 * @return void
	 */
	public function ajax_restore_defaults(): void {
		check_ajax_referer( 'qspv_restore_defaults', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ) );
		}

		$defaults = array(
			'qspv_autoplay'          => 'no',
			'qspv_mute'              => 'yes',
			'qspv_loop'              => 'no',
			'qspv_controls'          => 'yes',
			'qspv_schema_enabled'    => 'yes',
			'qspv_tracking_enabled'  => 'yes',
			'qspv_play_button_style' => 'default',
		);

		foreach ( $defaults as $key => $value ) {
			update_option( $key, $value );
		}

		wp_send_json_success( array( 'message' => 'restored' ) );
	}

	// -----------------------------------------------------------------------
	// Sanitize helpers.
	// -----------------------------------------------------------------------

	/**
	 * Sanitize a yes/no value. Missing/unchecked checkbox counts as 'no'.
	 *
	 * @param  mixed $value Raw value.
	 * @return string 'yes' or 'no'.
	 */
	private function sanitize_yes_no( $value ): string {
		return 'yes' === $value ? 'yes' : 'no';
	}

	/**
	 * Sanitize play button style value.
	 *
	 * @param  mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_play_button_style( $value ): string {
		return in_array( $value, array( 'default', 'minimal' ), true ) ? (string) $value : 'default';
	}

	// -----------------------------------------------------------------------
	// Helpers.
	// -----------------------------------------------------------------------

	/**
	 * Register a settings section for a given tab page slug.
	 *
	 * @param  string $id    Section ID.
	 * @param  string $title Section title.
	 * @param  string $tab   Tab slug.
	 * @return void
	 */
	private function register_section( string $id, string $title, string $tab ): void {
		add_settings_section( $id, $title, '__return_false', 'qspv-' . $tab );
	}

	/**
	 * Register a settings field bound to a section on a tab page.
	 *
	 * @param  string $id       Option name / field ID.
	 * @param  string $title    Field label.
	 * @param  string $callback Renderer method name on this class.
	 * @param  string $tab      Tab slug.
	 * @param  string $section  Section ID.
	 * @param  array  $args     Arguments passed to the renderer.
	 * @return void
	 */
	private function register_field(
		string $id,
		string $title,
		string $callback,
		string $tab,
		string $section,
		array $args = array()
	): void {
		if ( ! empty( $args['tooltip'] ) ) {
			$tip    = esc_attr( $args['tooltip'] );
			$title .= ' <span class="qs-tip" data-tip="' . $tip . '" tabindex="0" aria-label="' . $tip . '">'
				. '<svg width="13" height="13" viewBox="0 0 14 14" fill="none" aria-hidden="true" focusable="false">'
				. '<circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5"/>'
				. '<line x1="7" y1="6.5" x2="7" y2="10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
				. '<circle cx="7" cy="4.25" r="0.85" fill="currentColor"/>'
				. '</svg>'
				. '</span>';
		}
		add_settings_field(
			$id,
			$title,
			array( $this, $callback ),
			'qspv-' . $tab,
			$section,
			$args
		);
	}
}
