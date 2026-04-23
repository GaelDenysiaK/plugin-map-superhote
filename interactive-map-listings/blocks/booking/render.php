<?php
/**
 * Server-side rendering for interactive-map-listings/booking block.
 * Displays the Superhote single rental iframe.
 *
 * Available variables: $attributes, $content, $block
 *
 * Priority for propertykey:
 *   1. Block attribute "propertyKeyOverride" (manual override)
 *   2. Meta _iml_superhote_property_key of the current logement (FSE context)
 *
 * @package InteractiveMapListings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Manual override via block attribute.
$property_key = '';
if ( ! empty( $attributes['propertyKeyOverride'] ) ) {
	$property_key = sanitize_text_field( $attributes['propertyKeyOverride'] );
}

// 2. Automatic: read from post context (FSE single template).
if ( empty( $property_key ) ) {
	$post_id   = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
	$post_type = isset( $block->context['postType'] ) ? $block->context['postType'] : get_post_type( $post_id );

	if ( $post_id && 'logement' === $post_type ) {
		$property_key = (string) get_post_meta( $post_id, '_iml_superhote_property_key', true );
	}
}

// No key found — show notice in admin, nothing in frontend.
if ( empty( $property_key ) ) {
	if ( current_user_can( 'edit_posts' ) ) {
		printf(
			'<div style="padding:12px 16px;background:#fff3cd;border-left:4px solid #ffc107;border-radius:4px;font-size:14px;">⚠️ %s</div>',
			esc_html__( 'Bloc "Moteur de réservation (logement)" : aucune propertykey Superhote trouvée. Renseignez-la dans la meta box "Détails du logement" ou dans les réglages du bloc.', 'interactive-map-listings' )
		);
	}
	return;
}

$height            = isset( $attributes['height'] ) ? absint( $attributes['height'] ) : 3879;
$iframe_id         = 'iml-booking-' . wp_unique_id();
$iframe_src        = 'https://app.superhote.com/#/rental/' . rawurlencode( $property_key );
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'iml-booking-single' ) );

printf(
	'<div %1$s><iframe id="%2$s" allowfullscreen src="%3$s" style="display:block;" width="100%%" height="%4$d" frameborder="0" sandbox="allow-scripts allow-forms allow-same-origin allow-presentation allow-top-navigation"></iframe></div>',
	$wrapper_attributes,
	esc_attr( $iframe_id ),
	esc_url( $iframe_src ),
	$height
);
