/**
 * QuickShipD Product Video for WooCommerce
 * Admin JS: settings page (tabs, AJAX save/restore) + product editor (URL badge, preview).
 *
 * @package QuickShipD_Product_Video
 * @version 1.0.0
 */
( function ( $ ) {
	'use strict';

	var cfg = window.qspvAdmin || {};

	/* ================================================================ */
	/* Settings page                                                     */
	/* ================================================================ */

	var SPINNER = '<span class="pv-btn-spinner"></span>';

	function btnLoading( $btn, label ) {
		$btn.prop( 'disabled', true ).data( 'label', $btn.html() ).html( SPINNER + label );
	}

	function btnReset( $btn ) {
		$btn.prop( 'disabled', false ).html( $btn.data( 'label' ) || $btn.html() );
	}

	function initTabs() {
		$( document ).on( 'click', '.qspv-tab-btn', function () {
			var tab = $( this ).data( 'tab' );
			$( '.qspv-tab-btn' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );
			$( '.qspv-tab-pane' ).removeClass( 'is-active' );
			$( '#qspv-tab-' + tab ).addClass( 'is-active' );
		} );
	}

	function saveSettings() {
		var $btn    = $( '#qspv-save-btn' );
		var $status = $( '#qspv-save-status' );
		var tab     = $( '.qspv-tab-pane.is-active' ).data( 'tab' );

		btnLoading( $btn, ' Saving…' );
		$status.text( '' ).removeClass( 'is-success is-error' );

		var postData = {
			action: 'qspv_save_settings',
			nonce:  cfg.saveNonce || '',
			tab:    tab,
		};

		$( '#qspv-tab-' + tab ).find( 'input, select, textarea' ).each( function () {
			var name = $( this ).attr( 'name' );
			if ( ! name ) { return; }
			if ( $( this ).is( '[type=checkbox]' ) ) {
				postData[ name ] = $( this ).is( ':checked' ) ? 'yes' : 'no';
			} else {
				postData[ name ] = $( this ).val();
			}
		} );

		$.post( cfg.ajaxUrl || ajaxurl, postData, function ( response ) {
			btnReset( $btn );
			if ( response && response.success ) {
				$status.text( cfg.savedText || 'Saved.' ).addClass( 'is-success' );
				setTimeout( function () { $status.text( '' ).removeClass( 'is-success' ); }, 3000 );
			} else {
				$status.text( cfg.errorText || 'Error.' ).addClass( 'is-error' );
			}
		} ).fail( function () {
			btnReset( $btn );
			$status.text( cfg.errorText || 'Error.' ).addClass( 'is-error' );
		} );
	}

	function restoreDefaults() {
		if ( ! window.confirm( cfg.confirmText || 'Reset all settings to defaults?' ) ) { return; }

		var $btn    = $( '#qspv-restore-btn' );
		var $status = $( '#qspv-save-status' );

		btnLoading( $btn, ' Restoring…' );
		$status.text( '' ).removeClass( 'is-success is-error' );

		$.post( cfg.ajaxUrl || ajaxurl, {
			action: 'qspv_restore_defaults',
			nonce:  cfg.restoreNonce || '',
		}, function ( response ) {
			btnReset( $btn );
			if ( response && response.success ) {
				$status.text( cfg.restoredText || 'Defaults restored.' ).addClass( 'is-success' );
				setTimeout( function () { window.location.reload(); }, 800 );
			} else {
				$status.text( cfg.errorText || 'Error.' ).addClass( 'is-error' );
			}
		} ).fail( function () {
			btnReset( $btn );
			$status.text( cfg.errorText || 'Error.' ).addClass( 'is-error' );
		} );
	}

	function initSettingsPage() {
		if ( ! $( '#qspv-save-btn' ).length ) { return; }
		initTabs();
		$( '#qspv-save-btn' ).on( 'click', saveSettings );
		$( '#qspv-restore-btn' ).on( 'click', restoreDefaults );
	}

	/* ================================================================ */
	/* Product editor: URL badge + inline video preview                 */
	/* ================================================================ */

	var debounceTimer = null;

	function detectProvider( url ) {
		if ( /youtube\.com|youtu\.be/i.test( url ) ) { return { type: 'youtube',  label: 'YouTube' }; }
		if ( /vimeo\.com/i.test( url ) )              { return { type: 'vimeo',    label: 'Vimeo' }; }
		if ( /\.(mp4|mov|webm|ogg)(\?|$)/i.test( url ) ) { return { type: 'mp4', label: 'Self-hosted' }; }
		if ( url.length > 0 )                         { return { type: 'unknown', label: 'Unrecognised URL' }; }
		return { type: '', label: '' };
	}

	function youtubeEmbedUrl( url ) {
		var m = url.match( /(?:v=|youtu\.be\/|\/embed\/|\/shorts\/)([a-zA-Z0-9_-]{11})/ );
		return m ? 'https://www.youtube.com/embed/' + m[1] : '';
	}

	function vimeoEmbedUrl( url ) {
		var m = url.match( /vimeo\.com\/(\d+)/ );
		return m ? 'https://player.vimeo.com/video/' + m[1] : '';
	}

	function updateBadge( $badge, provider ) {
		if ( ! provider.label ) {
			$badge.hide().text( '' ).removeClass( 'is-youtube is-vimeo is-mp4 is-unknown' );
			return;
		}
		$badge
			.text( provider.label )
			.removeClass( 'is-youtube is-vimeo is-mp4 is-unknown' )
			.addClass( 'is-' + provider.type )
			.show();
	}

	function updatePreview( $container, url, type ) {
		$container.empty();
		var src = '';

		if ( 'youtube' === type ) { src = youtubeEmbedUrl( url ); }
		if ( 'vimeo'   === type ) { src = vimeoEmbedUrl( url ); }

		if ( src ) {
			$( '<iframe>', { src: src, allow: 'encrypted-media', allowfullscreen: true } ).appendTo( $container );
			return;
		}

		if ( 'mp4' === type ) {
			$( '<video>', { src: url, controls: true } ).appendTo( $container );
		}
	}

	/* ================================================================ */
	/* Media library picker                                             */
	/* ================================================================ */

	function initMediaButtons() {
		if ( typeof wp === 'undefined' || ! wp.media ) { return; }

		// Video URL picker.
		$( document ).on( 'click', '.qspv-media-btn', function ( e ) {
			e.preventDefault();

			var $btn    = $( this );
			var $target = $( $btn.data( 'target' ) );

			var frame = wp.media( {
				title:    'Choose a video',
				library:  { type: 'video' },
				button:   { text: 'Use this video' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				$target.val( attachment.url ).trigger( 'input' );
			} );

			frame.open();
		} );

		// Thumbnail image picker.
		$( document ).on( 'click', '.qspv-thumb-choose-btn', function ( e ) {
			e.preventDefault();

			var $picker  = $( this ).closest( '.qspv-thumb-picker' );
			var $input   = $picker.find( 'input[type=hidden]' );
			var $preview = $picker.find( '.qspv-thumb-preview' );
			var $img     = $preview.find( 'img' );
			var $remove  = $picker.find( '.qspv-thumb-remove-btn' );
			var $btn     = $( this );

			var frame = wp.media( {
				title:    'Choose a thumbnail image',
				library:  { type: 'image' },
				button:   { text: 'Use as thumbnail' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var url = attachment.sizes && attachment.sizes.medium
					? attachment.sizes.medium.url
					: attachment.url;

				$input.val( attachment.url ); // always store full-size URL
				$img.attr( 'src', url );
				$preview.show();
				$remove.show();
				$btn.text( 'Change Thumbnail' );
			} );

			frame.open();
		} );

		$( document ).on( 'click', '.qspv-thumb-remove-btn', function ( e ) {
			e.preventDefault();

			var $picker  = $( this ).closest( '.qspv-thumb-picker' );
			$picker.find( 'input[type=hidden]' ).val( '' );
			$picker.find( '.qspv-thumb-preview' ).hide();
			$picker.find( '.qspv-thumb-remove-btn' ).hide();
			$picker.find( '.qspv-thumb-choose-btn' ).text( 'Choose Thumbnail' );
		} );
	}

	/* ================================================================ */
	/* Gallery position sortable                                        */
	/* ================================================================ */

	function initPositionSort() {
		var $list = $( '#qspv_position_sort' );
		if ( ! $list.length || ! $.fn.sortable ) { return; }

		var positions = [ 'before_featured', 'after_featured', 'last' ];

		$list.sortable( {
			handle:      '.qspv-position-handle',
			cancel:      '.qspv-position-fixed',
			axis:        'y',
			containment: 'parent',
			update: function () {
				var index = $list.children( '.qspv-position-video' ).index();
				$( '#_qspv_video_position' ).val( positions[ index ] || 'last' );
			},
		} );
	}

	function initProductEditor() {
		var $input   = $( '#_qspv_video_url' );
		var $badge   = $( '#qspv_provider_badge' );
		var $preview = $( '#qspv_admin_preview' );

		if ( ! $input.length ) { return; }

		var initialUrl = String( $input.val() || '' ).trim();
		if ( initialUrl ) {
			var initial = detectProvider( initialUrl );
			updateBadge( $badge, initial );
			if ( initial.type && 'unknown' !== initial.type ) {
				updatePreview( $preview, initialUrl, initial.type );
			}
		}

		$input.on( 'input', function () {
			var url      = String( $( this ).val() ).trim();
			var provider = detectProvider( url );

			updateBadge( $badge, provider );

			clearTimeout( debounceTimer );

			if ( ! provider.type || 'unknown' === provider.type ) {
				$preview.empty();
				return;
			}

			debounceTimer = setTimeout( function () {
				updatePreview( $preview, url, provider.type );
			}, 500 );
		} );
	}

	/* ================================================================ */
	/* Boot                                                              */
	/* ================================================================ */
	$( function () {
		initSettingsPage();
		initMediaButtons();
		initPositionSort();
		initProductEditor();
	} );

} )( jQuery );
