<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IML_POI_Post_Type {

	/**
	 * Default categories: slug => [ name, default_color ].
	 * The color keys (poi_color_*) must match IML_Settings::get_defaults().
	 */
	public static function get_default_categories() {
		return array(
			'patrimoine-culture'   => array(
				'name'      => 'Patrimoine & Culture',
				'color_key' => 'poi_color_patrimoine',
			),
			'nature-randonnee'     => array(
				'name'      => 'Nature & Randonnée',
				'color_key' => 'poi_color_nature',
			),
			'loisirs-activites'    => array(
				'name'      => 'Loisirs & Activités',
				'color_key' => 'poi_color_loisirs',
			),
			'commerces-essentiels' => array(
				'name'      => 'Commerces essentiels',
				'color_key' => 'poi_color_commerces',
			),
			'restaurants-marches'  => array(
				'name'      => 'Restaurants & Marchés',
				'color_key' => 'poi_color_restaurants',
			),
			'services-pratiques'   => array(
				'name'      => 'Services pratiques',
				'color_key' => 'poi_color_services',
			),
		);
	}

	/**
	 * Map term slug → settings key for color lookup.
	 */
	public static function get_slug_to_color_key() {
		$map = array();
		foreach ( self::get_default_categories() as $slug => $data ) {
			$map[ $slug ] = $data['color_key'];
		}
		return $map;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'maybe_insert_default_terms' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Points d\'intérêt', 'interactive-map-listings' ),
			'singular_name'      => __( 'Point d\'intérêt', 'interactive-map-listings' ),
			'add_new'            => __( 'Ajouter', 'interactive-map-listings' ),
			'add_new_item'       => __( 'Ajouter un point d\'intérêt', 'interactive-map-listings' ),
			'edit_item'          => __( 'Modifier le point d\'intérêt', 'interactive-map-listings' ),
			'new_item'           => __( 'Nouveau point d\'intérêt', 'interactive-map-listings' ),
			'view_item'          => __( 'Voir le point d\'intérêt', 'interactive-map-listings' ),
			'search_items'       => __( 'Rechercher des points d\'intérêt', 'interactive-map-listings' ),
			'not_found'          => __( 'Aucun point d\'intérêt trouvé', 'interactive-map-listings' ),
			'not_found_in_trash' => __( 'Aucun point d\'intérêt dans la corbeille', 'interactive-map-listings' ),
			'menu_name'          => __( 'Points d\'intérêt', 'interactive-map-listings' ),
		);

		register_post_type( 'poi', array(
			'labels'       => $labels,
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'rest_base'    => 'pois',
			'menu_icon'    => 'dashicons-location',
			'supports'     => array( 'title', 'thumbnail', 'custom-fields' ),
			'taxonomies'   => array( 'poi_category' ),
			'rewrite'      => false,
		) );
	}

	public function register_taxonomy() {
		$labels = array(
			'name'          => __( 'Catégories POI', 'interactive-map-listings' ),
			'singular_name' => __( 'Catégorie POI', 'interactive-map-listings' ),
			'add_new_item'  => __( 'Ajouter une catégorie', 'interactive-map-listings' ),
			'new_item_name' => __( 'Nouvelle catégorie', 'interactive-map-listings' ),
			'search_items'  => __( 'Rechercher des catégories', 'interactive-map-listings' ),
			'menu_name'     => __( 'Catégories', 'interactive-map-listings' ),
		);

		register_taxonomy( 'poi_category', 'poi', array(
			'labels'       => $labels,
			'public'       => false,
			'show_ui'      => true,
			'hierarchical' => false,
			'show_in_rest' => true,
			'rest_base'    => 'poi-categories',
			'rewrite'      => false,
		) );
	}

	/**
	 * Insert the 6 default category terms on first activation (idempotent).
	 */
	public function maybe_insert_default_terms() {
		if ( ! taxonomy_exists( 'poi_category' ) ) {
			return;
		}
		foreach ( self::get_default_categories() as $slug => $data ) {
			if ( ! term_exists( $slug, 'poi_category' ) ) {
				wp_insert_term( $data['name'], 'poi_category', array( 'slug' => $slug ) );
			}
		}
	}
}
