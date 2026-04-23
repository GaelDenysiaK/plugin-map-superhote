<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IML_Rest_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the custom REST route.
	 */
	public function register_routes() {
		register_rest_route( 'iml/v1', '/logements', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_logements' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Return all published logements with map-optimized data.
	 */
	public function get_logements( $request ) {
		$query = new WP_Query( array(
			'post_type'      => 'logement',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		) );

		$logements = array();
		$settings  = get_option( 'iml_settings', IML_Settings::get_defaults() );
		$settings  = wp_parse_args( $settings, IML_Settings::get_defaults() );

		foreach ( $query->posts as $post ) {
			$lat = get_post_meta( $post->ID, '_iml_latitude', true );
			$lng = get_post_meta( $post->ID, '_iml_longitude', true );

			// Skip logements without coordinates.
			if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) || ( (float) $lat === 0.0 && (float) $lng === 0.0 ) ) {
				continue;
			}

			$image_url = '';
			$thumb_id  = get_post_thumbnail_id( $post->ID );
			if ( $thumb_id ) {
				$image = wp_get_attachment_image_src( $thumb_id, 'medium' );
				if ( $image ) {
					$image_url = $image[0];
				}
			}

			$terms     = get_the_terms( $post->ID, 'logement_tag' );
			$tag_names = array();
			if ( $terms && ! is_wp_error( $terms ) ) {
				$tag_names = wp_list_pluck( $terms, 'name' );
			}

			// Use custom action URL if set, otherwise fall back to the single post permalink.
			$action_url = get_post_meta( $post->ID, '_iml_action_url', true );
			if ( empty( $action_url ) ) {
				$action_url = get_permalink( $post->ID );
			}

			$logements[] = array(
				'id'                => $post->ID,
				'title'             => get_the_title( $post ),
				'short_description' => (string) get_post_meta( $post->ID, '_iml_short_description', true ),
				'latitude'          => (float) $lat,
				'longitude'         => (float) $lng,
				'capacity'          => (int) get_post_meta( $post->ID, '_iml_capacity', true ),
				'tags'              => $tag_names,
				'image_url'         => $image_url,
				'action_url'        => $action_url,
			);
		}

		return new WP_REST_Response( array(
			'logements' => $logements,
			'settings'  => array(
				'button_label' => $settings['button_label'],
			),
		), 200 );
	}
}
