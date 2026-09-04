=== QuickShipD Product Video - YouTube, Vimeo & Self-Hosted Product Video Gallery for WooCommerce ===

Contributors: quickshipd
Tags: woocommerce, product video, youtube, vimeo, video gallery
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.5
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

<<<<<<< Updated upstream
Add YouTube, Vimeo & self-hosted MP4/WebM videos to WooCommerce product galleries. VideoObject SEO, variations, custom thumbnails & play tracking.
=======
WooCommerce product video gallery plugin: add YouTube, Vimeo, and self-hosted MP4/WebM videos to your product gallery with VideoObject SEO schema, variation videos, custom thumbnails, gallery ordering, and play tracking.
>>>>>>> Stashed changes

== Description ==

[QuickShipD Product Video](https://quickshipd.com/product-video/) is a lightweight WooCommerce **product video gallery** plugin that lets you add **YouTube**, **Vimeo**, and **self-hosted** videos directly to your **WooCommerce gallery** - alongside product images, never replacing them.

Paste a video URL or upload MP4, MOV, or WebM from the Media Library. Shoppers see a gallery slide with thumbnail and play button.

Create a **WooCommerce video gallery** that combines product images and videos in one familiar shopping experience. The plugin supports both the classic WooCommerce gallery and the **Product Gallery Block**.

Ideal for stores that want **product videos**, **product page videos**, **video SEO** in Google, and per-product analytics without slowing down the rest of the site.

= Features =

* **Product video gallery** - add videos alongside your existing WooCommerce product images
* **YouTube + Vimeo embedding** - paste a URL and the video appears in the WooCommerce gallery
* **Self-hosted video** - upload MP4, MOV, or WebM from the WordPress Media Library
* **WooCommerce video gallery** - display product images and videos together in the WooCommerce product gallery
* **Gallery ordering** - place video before the featured image, after it, or after all images
* **WooCommerce Block gallery** - works with the Product Gallery Block (WooCommerce 8.2+)
* **VideoObject SEO schema** - JSON-LD structured data for Google video rich results
* **Play tracking** - count how many times each product video is played
* **Variation videos** - different video URL per product variation; gallery updates on selection
* **Custom video thumbnail** - override the default thumbnail from the Media Library
* **Playback controls** - autoplay, mute, loop, and player controls (site-wide and per product)
* **HPOS compatible** - WooCommerce High-Performance Order Storage

= Performance =

Assets load only on single product pages that have a video. Lightweight CSS and JavaScript (under 2 KB). YouTube and Vimeo player scripts load only after the visitor clicks play - fast product pages and better Core Web Vitals.

= VideoObject SEO Schema =

Automatic **schema.org VideoObject** JSON-LD on product pages with videos. Helps search engines understand your **WooCommerce product video** content and can support video thumbnails in Google results.

= By QuickShipD =

From the team behind [QuickShipD](https://wordpress.org/plugins/quickshipd/) - estimated delivery dates for WooCommerce.

== Installation ==

1. Upload the `quickshipd-product-video` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Edit any product, open the **Product Video** tab, paste a YouTube or Vimeo URL (or choose a self-hosted file), and save

= Settings =

**WooCommerce → Product Video** - configure autoplay, mute, loop, VideoObject schema, and play tracking globally.

== Frequently Asked Questions ==

= What video sources are supported? =

**YouTube**, **Vimeo**, and **self-hosted** files (MP4, MOV, WebM, OGG) from your Media Library.

= What is a product video gallery? =

A **product video gallery** lets you display product videos alongside your existing product images. QuickShipD Product Video adds each video as an additional gallery slide in WooCommerce.

= Can I add YouTube videos to my WooCommerce products? =

Yes. Paste a YouTube video URL into the Product Video settings and it will be added to your **WooCommerce video gallery**.

= Can I add Vimeo videos? =

Yes. Vimeo videos can be added alongside YouTube and self-hosted videos.

= Does the video replace my product images? =

No. The video is an extra slide in the **WooCommerce product gallery** with your existing images.

= Will it work with my theme? =

Yes, with any theme using the standard WooCommerce product gallery. Tested with Storefront, Astra, GeneratePress, Flatsome, and OceanWP.

= Does it work with the WooCommerce Product Gallery Block? =

Yes - classic gallery and **Product Gallery Block** are both supported.

= What is VideoObject SEO schema? =

Structured data that tells Google your product page includes a video. Generated automatically; no extra SEO plugin required.

= Can I show different videos per product variation? =

Yes. Each variation has its own video URL. When the customer selects a variation, the gallery video and thumbnail update.

= Can I use self-hosted product videos? =

Yes. You can upload MP4, MOV, WebM, or OGG videos from your WordPress Media Library and display them in your **product video gallery**.

= Can I track product video plays? =

Yes. Play tracking can count how many times each product video is played.

= How do I override the video gallery template? =

Copy `templates/video-gallery-item.php` to `yourtheme/quickshipd-product-video/video-gallery-item.php`.

== External Services ==

This plugin connects to external services in the following situations:

= YouTube =

When a product has a YouTube video URL, the plugin loads the video thumbnail image from YouTube's image servers (`img.youtube.com`) to display it in the gallery slide. No data about your site or visitors is sent to YouTube during thumbnail loading. The YouTube player (youtube.com) is loaded inside an iframe only when a visitor clicks the play button.

* [YouTube Terms of Service](https://www.youtube.com/t/terms)
* [Google Privacy Policy](https://policies.google.com/privacy)

= Vimeo =

When a product has a Vimeo video URL, the plugin makes a server-side request to the Vimeo oEmbed API (`https://vimeo.com/api/oembed.json`) to retrieve the video thumbnail URL. Only the Vimeo video ID is sent in this request. The result is cached on your server for seven days to minimise API calls. The Vimeo player (player.vimeo.com) is loaded inside an iframe only when a visitor clicks the play button.

* [Vimeo Terms of Service](https://vimeo.com/terms)
* [Vimeo Privacy Policy](https://vimeo.com/privacy)

= Self-hosted videos =

Self-hosted video files (MP4, WebM, etc.) uploaded to your own Media Library are served directly from your server. No external service is involved.

== Screenshots ==

1. Product page with video in the gallery alongside product images
2. Product Video Settings - the Product Video playback settings
3. Product Video Settings - features section
4. Individual product settings.

== Changelog ==

= 1.0 - 03/06/2026 =

* Initial release
* YouTube, Vimeo, and self-hosted product videos in the WooCommerce gallery
* Video Thumbnail picker for self-hosted videos
* Gallery position control (before featured image / after featured image / after all images)
* VideoObject SEO schema and play tracking
* Variation video support with thumbnail and type updates on variation change
* Vimeo thumbnail retrieval via oEmbed API

== Upgrade Notice ==

= 1.0 =

Initial release.
