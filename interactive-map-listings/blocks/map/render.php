<?php
/**
 * Server-side rendering for the interactive-map-listings/map block.
 *
 * Available variables: $attributes, $content, $block
 *
 * @package InteractiveMapListings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Merge block attributes with plugin defaults.
$settings = get_option( 'iml_settings', array() );
$defaults = IML_Settings::get_defaults();
$settings = wp_parse_args( $settings, $defaults );

// Block attributes override settings when non-empty/non-zero.
$zoom       = ! empty( $attributes['zoom'] )       ? (int) $attributes['zoom']       : (int) $settings['default_zoom'];
$center_lat = ! empty( $attributes['centerLat'] )   ? (float) $attributes['centerLat'] : (float) $settings['default_lat'];
$center_lng = ! empty( $attributes['centerLng'] )   ? (float) $attributes['centerLng'] : (float) $settings['default_lng'];
$map_height = ! empty( $attributes['mapHeight'] )   ? (int) $attributes['mapHeight']   : 500;

$marker_color    = ! empty( $attributes['markerColor'] )    ? $attributes['markerColor']    : $settings['marker_color'];
$card_bg_color   = ! empty( $attributes['cardBgColor'] )    ? $attributes['cardBgColor']    : $settings['card_bg_color'];
$card_text_color = ! empty( $attributes['cardTextColor'] )  ? $attributes['cardTextColor']  : $settings['card_text_color'];
$button_color    = ! empty( $attributes['buttonColor'] )    ? $attributes['buttonColor']    : $settings['button_color'];
$button_text_color = ! empty( $attributes['buttonTextColor'] ) ? $attributes['buttonTextColor'] : $settings['button_text_color'];

$show_pois = isset( $attributes['showPois'] ) ? (bool) $attributes['showPois'] : true;

// Build config for JS.
$map_config = array(
	'zoom'            => $zoom,
	'centerLat'       => $center_lat,
	'centerLng'       => $center_lng,
	'restUrl'         => esc_url_raw( rest_url( 'iml/v1/logements' ) ),
	'nonce'           => wp_create_nonce( 'wp_rest' ),
	'markerColor'     => sanitize_hex_color( $marker_color ),
	'cardBgColor'     => sanitize_hex_color( $card_bg_color ),
	'cardTextColor'   => sanitize_hex_color( $card_text_color ),
	'buttonColor'     => sanitize_hex_color( $button_color ),
	'buttonTextColor' => sanitize_hex_color( $button_text_color ),
	'poisUrl'         => $show_pois ? esc_url_raw( rest_url( 'iml/v1/pois' ) ) : null,
);

$unique_id = 'iml-map-' . wp_unique_id();

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class'           => 'iml-map-container',
	'style'           => sprintf( 'height: %dpx;', absint( $map_height ) ),
	'data-iml-config' => wp_json_encode( $map_config ),
) );

printf(
	'<div %s><div class="iml-map" id="%s"></div></div>',
	$wrapper_attributes,
	esc_attr( $unique_id )
);
