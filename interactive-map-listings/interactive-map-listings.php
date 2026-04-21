<?php
/**
 * Plugin Name: Interactive Map Listings
 * Plugin URI:  https://example.com/interactive-map-listings
 * Description: Display an interactive Leaflet map with accommodation listings. Hover markers to see cards with photos, descriptions, capacity, tags, and action buttons. Fully compatible with the Full Site Editor.
 * Version:     1.0.0
 * Author:      Gael
 * Text Domain: interactive-map-listings
 * Domain Path: /languages
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IML_VERSION', '1.0.0' );
define( 'IML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IML_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include classes.
require_once IML_PLUGIN_DIR . 'includes/class-iml-post-type.php';
require_once IML_PLUGIN_DIR . 'includes/class-iml-meta-boxes.php';
require_once IML_PLUGIN_DIR . 'includes/class-iml-settings.php';
require_once IML_PLUGIN_DIR . 'includes/class-iml-rest-api.php';

// Initialize classes.
new IML_Post_Type();
new IML_Meta_Boxes();
new IML_Settings();
new IML_Rest_API();

/**
 * Register frontend assets (Leaflet + map scripts/styles).
 * These are registered but only enqueued when the block is rendered via viewScript/viewStyle in block.json.
 */
add_action( 'init', function () {
	wp_register_style(
		'leaflet',
		'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
		array(),
		'1.9.4'
	);

	wp_register_script(
		'leaflet',
		'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
		array(),
		'1.9.4',
		true
	);

	wp_register_style(
		'iml-frontend-map',
		IML_PLUGIN_URL . 'assets/css/frontend-map.css',
		array( 'leaflet' ),
		IML_VERSION
	);

	wp_register_script(
		'iml-frontend-map',
		IML_PLUGIN_URL . 'assets/js/frontend-map.js',
		array( 'leaflet' ),
		IML_VERSION,
		true
	);

	// Register the Gutenberg block.
	register_block_type( IML_PLUGIN_DIR . 'blocks/map' );
} );

/**
 * Register block pattern category and pattern.
 */
add_action( 'init', function () {
	register_block_pattern_category( 'interactive-map-listings', array(
		'label' => __( 'Map Listings', 'interactive-map-listings' ),
	) );

	register_block_pattern(
		'interactive-map-listings/full-width-map',
		array(
			'title'       => __( 'Full Width Map', 'interactive-map-listings' ),
			'description' => __( 'A full-width interactive map displaying all logements.', 'interactive-map-listings' ),
			'categories'  => array( 'interactive-map-listings' ),
			'content'     => '<!-- wp:interactive-map-listings/map {"align":"full","mapHeight":600} /-->',
		)
	);
} );

/**
 * Load text domain.
 */
add_action( 'init', function () {
	load_plugin_textdomain( 'interactive-map-listings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );
