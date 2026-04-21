<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IML_Post_Type {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Logements', 'interactive-map-listings' ),
			'singular_name'      => __( 'Logement', 'interactive-map-listings' ),
			'add_new'            => __( 'Ajouter', 'interactive-map-listings' ),
			'add_new_item'       => __( 'Ajouter un logement', 'interactive-map-listings' ),
			'edit_item'          => __( 'Modifier le logement', 'interactive-map-listings' ),
			'new_item'           => __( 'Nouveau logement', 'interactive-map-listings' ),
			'view_item'          => __( 'Voir le logement', 'interactive-map-listings' ),
			'search_items'       => __( 'Rechercher des logements', 'interactive-map-listings' ),
			'not_found'          => __( 'Aucun logement trouvé', 'interactive-map-listings' ),
			'not_found_in_trash' => __( 'Aucun logement dans la corbeille', 'interactive-map-listings' ),
			'menu_name'          => __( 'Logements', 'interactive-map-listings' ),
		);

		register_post_type( 'logement', array(
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'rest_base'    => 'logements',
			'menu_icon'    => 'dashicons-location-alt',
			'supports'     => array( 'title', 'thumbnail', 'custom-fields' ),
			'taxonomies'   => array( 'logement_tag' ),
			'rewrite'      => array( 'slug' => 'logement' ),
		) );
	}

	public function register_taxonomy() {
		$labels = array(
			'name'          => __( 'Tags', 'interactive-map-listings' ),
			'singular_name' => __( 'Tag', 'interactive-map-listings' ),
			'add_new_item'  => __( 'Ajouter un tag', 'interactive-map-listings' ),
			'new_item_name' => __( 'Nouveau tag', 'interactive-map-listings' ),
			'search_items'  => __( 'Rechercher des tags', 'interactive-map-listings' ),
			'menu_name'     => __( 'Tags', 'interactive-map-listings' ),
		);

		register_taxonomy( 'logement_tag', 'logement', array(
			'labels'       => $labels,
			'public'       => true,
			'hierarchical' => false,
			'show_in_rest' => true,
			'rest_base'    => 'logement-tags',
			'rewrite'      => array( 'slug' => 'logement-tag' ),
		) );
	}
}
