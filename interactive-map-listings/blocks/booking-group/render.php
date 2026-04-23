<?php
/**
 * Server-side rendering for interactive-map-listings/booking-group block.
 * Displays the Superhote group booking engine iframe.
 *
 * Available variables: $attributes, $content, $block
 *
 * Priority for webkey:
 *   1. Block attribute "webKeyOverride" (manual override)
 *   2. Plugin setting "superhote_web_key"
 *
 * Two modes:
 *   - Simple  : static iframe pointing to the group URL.
 *   - Advanced: iframe + JS script that forwards URL params
 *               (appart, checkin, checkout, adults, children, lang).
 *
 * @package InteractiveMapListings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Resolve webkey.
$web_key = '';
if ( ! empty( $attributes['webKeyOverride'] ) ) {
	$web_key = sanitize_text_field( $attributes['webKeyOverride'] );
}

if ( empty( $web_key ) ) {
	$settings = get_option( 'iml_settings', array() );
	$web_key  = sanitize_text_field( $settings['superhote_web_key'] ?? '' );
}

// No key — admin notice only.
if ( empty( $web_key ) ) {
	if ( current_user_can( 'edit_posts' ) ) {
		printf(
			'<div style="padding:12px 16px;background:#fff3cd;border-left:4px solid #ffc107;border-radius:4px;font-size:14px;">⚠️ %s</div>',
			esc_html__( 'Bloc "Moteur de réservation (groupe)" : aucune webkey Superhote configurée. Renseignez-la dans Réglages > Map Listings ou dans les réglages du bloc.', 'interactive-map-listings' )
		);
	}
	return;
}

$height   = isset( $attributes['height'] ) ? absint( $attributes['height'] ) : 5500;
$advanced = ! empty( $attributes['advanced'] );
$iframe_id = 'iml-booking-group-' . wp_unique_id();
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'iml-booking-group' ) );

if ( $advanced ) :
	// Advanced mode: JS reads URL params and builds the iframe src dynamically.
	// This mirrors exactly the official Superhote advanced embed snippet.
	?>
	<div <?php echo $wrapper_attributes; ?>>
		<iframe id="<?php echo esc_attr( $iframe_id ); ?>"
			style="display:block;"
			src=""
			width="100%"
			height="<?php echo $height; ?>"
			frameborder="0"
			scrolling="no"
			allowfullscreen="allowfullscreen">
		</iframe>
		<script>
		( function () {
			var webKey   = <?php echo wp_json_encode( $web_key ); ?>;
			var iframeEl = document.getElementById( <?php echo wp_json_encode( $iframe_id ); ?> );
			if ( ! iframeEl ) return;

			var url      = new URL( window.location.href );
			var appart   = url.searchParams.get( 'appart' );
			var checkin  = url.searchParams.get( 'checkin' );
			var checkout = url.searchParams.get( 'checkout' );
			var adults   = url.searchParams.get( 'adults' );
			var children = url.searchParams.get( 'children' );
			var lang     = url.searchParams.get( 'lang' ) || 'fr';

			var iframeUrl;

			if ( appart ) {
				// Redirect to a specific rental.
				iframeUrl = 'https://app.superhote.com/#/rental/' + encodeURIComponent( appart ) + '?lang=' + encodeURIComponent( lang );
			} else if ( checkin ) {
				// Group view with pre-filled dates.
				iframeUrl = 'https://app.superhote.com/#/get-available-rentals/' + encodeURIComponent( webKey )
					+ '?startDate='     + encodeURIComponent( checkin || '' )
					+ '&endDate='       + encodeURIComponent( checkout || '' )
					+ '&adultsNumber='  + encodeURIComponent( adults || '' )
					+ '&childrenNumber=' + encodeURIComponent( children || '' )
					+ '&lang='          + encodeURIComponent( lang );
			} else {
				// Default group view.
				iframeUrl = 'https://app.superhote.com/#/get-available-rentals/' + encodeURIComponent( webKey ) + '?lang=' + encodeURIComponent( lang );
			}

			iframeEl.src = iframeUrl;
		} )();
		</script>
	</div>
	<?php

else :
	// Simple mode: static iframe.
	$iframe_src = 'https://app.superhote.com/#/get-available-rentals/' . rawurlencode( $web_key );
	printf(
		'<div %1$s><iframe id="%2$s" allowfullscreen src="%3$s" style="display:block;" width="100%%" height="%4$d" frameborder="0" sandbox="allow-scripts allow-forms allow-same-origin allow-presentation allow-top-navigation"></iframe></div>',
		$wrapper_attributes,
		esc_attr( $iframe_id ),
		esc_url( $iframe_src ),
		$height
	);

endif;
