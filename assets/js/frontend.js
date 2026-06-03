/**
 * QuickShipD Product Video for WooCommerce
 * Frontend: gallery injection, play handling, variation swap, play tracking.
 *
 * No jQuery dependency. Wrapped in an IIFE to avoid global scope pollution.
 *
 * @package QuickShipD_Product_Video
 * @version 1.0.0
 */
( function ( raw ) {
	'use strict';

	// wp_localize_script casts all PHP scalars to strings ('0'/'1').
	// Normalise everything to proper booleans/values up-front so that
	// truthiness checks work correctly throughout the rest of this file.
	var data = {
		ajaxUrl:         raw.ajaxUrl         || '',
		nonce:           raw.nonce            || '',
		productId:       raw.productId        || 0,
		trackingEnabled: !! +raw.trackingEnabled,
		autoplay:        !! +raw.autoplay,
		mute:            !! +raw.mute,
		loop:            !! +raw.loop,
		controls:        !! +raw.controls,
	};

	var tracked = false;

	/**
	 * Send a single play-count increment to the server.
	 */
	function trackPlay() {
		if ( tracked || ! data.trackingEnabled || ! data.ajaxUrl ) {
			return;
		}
		tracked = true;

		var body = new URLSearchParams( {
			action:     'qspv_track_play',
			nonce:      data.nonce || '',
			product_id: String( data.productId || 0 ),
		} );

		fetch( data.ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:        body.toString(),
		} ).catch( function () {} ); // fire-and-forget
	}

	/**
	 * Build an iframe src from a base embed URL, applying playback settings.
	 *
	 * @param  {string}  baseUrl   Clean embed URL (no existing params).
	 * @param  {string}  type      'youtube' or 'vimeo'.
	 * @param  {boolean} autoplay  Whether to request autoplay.
	 * @returns {string}
	 */
	function buildIframeSrc( baseUrl, type, autoplay ) {
		var params = [];

		if ( autoplay ) {
			params.push( 'autoplay=1' );
		}

		if ( data.mute ) {
			params.push( 'youtube' === type ? 'mute=1' : 'muted=1' );
		}

		if ( data.loop ) {
			if ( 'youtube' === type ) {
				// YouTube loop requires playlist=VIDEO_ID extracted from the embed URL.
				var ytId = baseUrl.match( /embed\/([a-zA-Z0-9_-]{11})/ );
				params.push( ytId ? 'loop=1&playlist=' + ytId[1] : 'loop=1' );
			} else {
				params.push( 'loop=1' );
			}
		}

		// controls=0 only makes sense when controls are explicitly disabled.
		if ( ! data.controls ) {
			params.push( 'controls=0' );
		}

		var sep = baseUrl.indexOf( '?' ) === -1 ? '?' : '&';
		var finalUrl = params.length ? baseUrl + sep + params.join( '&' ) : baseUrl;

		return finalUrl;
	}

	/**
	 * Replace the video placeholder with a live iframe/video element.
	 *
	 * @param {HTMLElement} slide     The .qspv-video-slide element.
	 * @param {boolean}     userClick True when triggered by an explicit click.
	 */
	function activateSlide( slide, userClick ) {
		var placeholder = slide.querySelector( '.qspv-video-placeholder' );
		var embedWrap   = slide.querySelector( '.qspv-video-embed' );

		if ( ! placeholder || ! embedWrap || embedWrap.children.length ) {
			return; // Already activated.
		}

		var videoType = slide.dataset.videoType || '';
		var videoUrl  = slide.dataset.videoUrl  || '';

		if ( ! videoUrl ) {
			return;
		}

		// Autoplay when: the admin enabled it, OR the visitor clicked play.
		// A user gesture grants autoplay permission in the browser.
		var autoplay = !! ( data.autoplay || userClick );

		var embed;

		if ( 'selfhost' === videoType ) {
			embed          = document.createElement( 'video' );
			embed.src      = videoUrl;
			embed.controls = !! data.controls;
			if ( autoplay )      { embed.autoplay = true; }
			if ( data.mute )     { embed.muted    = true; }
			if ( data.loop )     { embed.loop     = true; }
		} else {
			embed        = document.createElement( 'iframe' );
			embed.src    = buildIframeSrc( videoUrl, videoType, autoplay );
			embed.allow  = 'autoplay; encrypted-media; picture-in-picture';
			embed.setAttribute( 'allowfullscreen', '' );
		}

		placeholder.style.display = 'none';
		embedWrap.style.display   = '';
		embedWrap.appendChild( embed );

		trackPlay();
	}

	/**
	 * Attach a click handler to every play button in the classic gallery.
	 */
	function initClassicGallery() {
		var slides = document.querySelectorAll( '.qspv-video-slide' );

		slides.forEach( function ( slide ) {
			var btn = slide.querySelector( '.qspv-play-btn' );
			if ( ! btn ) { return; }

			btn.addEventListener( 'click', function () {
				activateSlide( slide, true );
			} );
		} );
	}

	/**
	 * Inject a video thumbnail button into the WooCommerce Product Gallery Block.
	 */
	function initBlockGallery() {
		var dataEl = document.querySelector( '.qspv-block-video-data' );
		if ( ! dataEl ) { return; }

		var gallery = document.querySelector(
			'.wp-block-woocommerce-product-gallery, .wc-block-product-gallery'
		);
		if ( ! gallery ) { return; }

		var thumbnail = dataEl.dataset.thumbnail;
		var videoUrl  = dataEl.dataset.videoUrl;
		var videoType = dataEl.dataset.videoType;

		if ( ! videoUrl ) { return; }

		var btn = document.createElement( 'button' );
		btn.type      = 'button';
		btn.className = 'qspv-block-video-thumb';
		btn.setAttribute( 'aria-label', 'Play product video' );

		if ( thumbnail ) {
			var img     = document.createElement( 'img' );
			img.src     = thumbnail;
			img.alt     = '';
			img.loading = 'lazy';
			btn.appendChild( img );
		}

		var icon       = document.createElement( 'span' );
		icon.className = 'qspv-play-btn';
		icon.setAttribute( 'aria-hidden', 'true' );
		var iconInner       = document.createElement( 'span' );
		iconInner.className = 'qspv-play-icon';
		icon.appendChild( iconInner );
		btn.appendChild( icon );

		btn.addEventListener( 'click', function () {
			var embedWrap = gallery.querySelector( '.qspv-block-embed-wrap' );

			if ( ! embedWrap ) {
				embedWrap           = document.createElement( 'div' );
				embedWrap.className = 'qspv-block-embed-wrap qspv-video-embed';
				gallery.appendChild( embedWrap );
			}

			if ( embedWrap.children.length ) { return; }

			var iframe        = document.createElement( 'iframe' );
			iframe.src        = buildIframeSrc( videoUrl, videoType, true );
			iframe.allow      = 'autoplay; encrypted-media; picture-in-picture';
			iframe.setAttribute( 'allowfullscreen', '' );
			embedWrap.appendChild( iframe );

			trackPlay();
		} );

		gallery.appendChild( btn );
	}

	/**
	 * Handle WooCommerce found_variation / reset_data events for variable products.
	 *
	 * Stores the original product-level URL, type, and thumbnail on first run so
	 * they can be restored when the variation selection is cleared.
	 */
	function initVariationSwap() {
		if ( typeof jQuery === 'undefined' ) { return; }

		var slide = document.querySelector( '.qspv-video-slide' );
		if ( slide ) {
			// Snapshot the product-level values before any variation swap.
			slide.dataset.origVideoUrl  = slide.dataset.videoUrl  || '';
			slide.dataset.origVideoType = slide.dataset.videoType || '';
			var origImg = slide.querySelector( '.qspv-video-placeholder img' );
			slide.dataset.origThumb = origImg ? origImg.src : '';
		}

		jQuery( document ).on( 'found_variation', function ( _e, variation ) {
			updateVideoForVariation( variation );
		} );

		jQuery( document ).on( 'reset_data', function () {
			updateVideoForVariation( {} );
		} );
	}

	/**
	 * Swap the video slide for a variation, or restore the product-level video.
	 *
	 * Receives the full WooCommerce variation object so it can update the URL,
	 * video type, and thumbnail in one pass. An empty object triggers a restore.
	 *
	 * @param {Object} variation WooCommerce variation data (may be empty object).
	 */
	function updateVideoForVariation( variation ) {
		var slides = document.querySelectorAll( '.qspv-video-slide' );
		if ( ! slides.length ) { return; }

		var slide = slides[0];
		var url   = variation.qspv_video_url   || '';
		var type  = variation.qspv_video_type  || '';
		var thumb = variation.qspv_video_thumb || '';

		if ( url ) {
			// Apply variation-specific values.
			slide.dataset.videoUrl  = url;
			if ( type ) { slide.dataset.videoType = type; }

			var img = slide.querySelector( '.qspv-video-placeholder img' );
			if ( img && thumb ) { img.src = thumb; }
		} else {
			// Restore product-level originals.
			slide.dataset.videoUrl  = slide.dataset.origVideoUrl  || '';
			slide.dataset.videoType = slide.dataset.origVideoType || '';

			var img = slide.querySelector( '.qspv-video-placeholder img' );
			if ( img && slide.dataset.origThumb ) { img.src = slide.dataset.origThumb; }
		}

		// Collapse any active embed so the new URL loads fresh on next click.
		var embedWrap   = slide.querySelector( '.qspv-video-embed' );
		var placeholder = slide.querySelector( '.qspv-video-placeholder' );

		if ( embedWrap ) {
			embedWrap.innerHTML     = '';
			embedWrap.style.display = 'none';
		}
		if ( placeholder ) {
			placeholder.style.display = '';
		}
	}

	/**
	 * When the admin has enabled autoplay, watch for Flexslider to make the
	 * video slide active and trigger activateSlide() automatically.
	 *
	 * For browsers to allow untriggered autoplay in an iframe, mute must also
	 * be enabled (standard browser autoplay policy).
	 */
	function initAutoplay() {
		if ( ! data.autoplay ) { return; }

		var slide = document.querySelector( '.qspv-video-slide' );
		if ( ! slide ) { return; }

		var observer = new MutationObserver( function () {
			if ( slide.classList.contains( 'flex-active-slide' ) ) {
				activateSlide( slide, false );
			}
		} );

		observer.observe( slide, { attributes: true, attributeFilter: [ 'class' ] } );
	}

	/**
	 * Add a play-icon overlay to the Flexslider thumbnail strip item that
	 * corresponds to the video slide.
	 */
	function addPlayIconToThumbnail() {
		var gallery = document.querySelector( '.woocommerce-product-gallery' );
		if ( ! gallery ) { return; }

		var slides     = gallery.querySelectorAll( '.woocommerce-product-gallery__image' );
		var slideIndex = -1;

		slides.forEach( function ( slide, i ) {
			if ( slide.classList.contains( 'qspv-video-slide' ) ) {
				slideIndex = i;
			}
		} );

		if ( slideIndex === -1 ) { return; }

		var thumbItems = gallery.querySelectorAll( '.flex-control-thumbs li' );
		if ( ! thumbItems[ slideIndex ] ) { return; }

		var li    = thumbItems[ slideIndex ];
		var slide = slides[ slideIndex ];
		if ( li.querySelector( '.qspv-thumb-play-icon' ) ) { return; } // already done

		li.classList.add( 'qspv-thumb-has-video' );
		if ( slide && slide.classList.contains( 'qspv-style-minimal' ) ) {
			li.classList.add( 'qspv-thumb-has-video--minimal' );
		}

		var icon = document.createElement( 'span' );
		icon.className = 'qspv-thumb-play-icon';
		icon.setAttribute( 'aria-hidden', 'true' );
		li.appendChild( icon );
	}

	/**
	 * Watch for Flexslider to create .flex-control-thumbs, then add the play icon.
	 */
	function watchForThumbs() {
		var gallery = document.querySelector( '.woocommerce-product-gallery' );
		if ( ! gallery ) { return; }

		addPlayIconToThumbnail();

		if ( gallery.querySelector( '.flex-control-thumbs' ) ) { return; }

		var observer = new MutationObserver( function () {
			if ( gallery.querySelector( '.flex-control-thumbs li' ) ) {
				observer.disconnect();
				addPlayIconToThumbnail();
			}
		} );

		observer.observe( gallery, { childList: true, subtree: true } );
	}

	/* Boot ----------------------------------------------------------------- */
	document.addEventListener( 'DOMContentLoaded', function () {
		initClassicGallery();
		initBlockGallery();
		initVariationSwap();
		initAutoplay();
		watchForThumbs();
	} );

} )( window.qspvData || {} );

