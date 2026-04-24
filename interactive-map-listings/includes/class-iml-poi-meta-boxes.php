<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IML_POI_Meta_Boxes {

	public function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_poi', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register post meta for REST API exposure.
	 */
	public function register_meta() {
		$meta_fields = array(
			'_iml_poi_short_description' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'_iml_poi_latitude'          => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_coordinate' ),
			),
			'_iml_poi_longitude'         => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_coordinate' ),
			),
			'_iml_poi_address'           => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_iml_poi_external_link'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			),
		);

		foreach ( $meta_fields as $key => $args ) {
			register_post_meta( 'poi', $key, array(
				'type'              => $args['type'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $args['sanitize_callback'],
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			) );
		}
	}

	public function sanitize_coordinate( $value ) {
		return is_numeric( $value ) ? (float) $value : 0;
	}

	public function add_meta_boxes() {
		add_meta_box(
			'iml_poi_details',
			__( 'Détails du point d\'intérêt', 'interactive-map-listings' ),
			array( $this, 'render_meta_box' ),
			'poi',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'iml_save_poi_meta', 'iml_poi_meta_nonce' );

		$desc    = get_post_meta( $post->ID, '_iml_poi_short_description', true );
		$lat     = get_post_meta( $post->ID, '_iml_poi_latitude', true );
		$lng     = get_post_meta( $post->ID, '_iml_poi_longitude', true );
		$address = get_post_meta( $post->ID, '_iml_poi_address', true );
		$link    = get_post_meta( $post->ID, '_iml_poi_external_link', true );
		?>
		<table class="form-table">
			<tr>
				<th>
					<label for="iml_poi_short_description"><?php esc_html_e( 'Description courte', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<textarea id="iml_poi_short_description" name="iml_poi_short_description" rows="3" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Brève description affichée dans la popup de la carte.', 'interactive-map-listings' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_poi_latitude"><?php esc_html_e( 'Latitude', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="number" id="iml_poi_latitude" name="iml_poi_latitude" value="<?php echo esc_attr( $lat ); ?>" step="any" class="regular-text" placeholder="48.8566" />
					<p class="description"><?php esc_html_e( 'Requis pour apparaître sur la carte.', 'interactive-map-listings' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_poi_longitude"><?php esc_html_e( 'Longitude', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="number" id="iml_poi_longitude" name="iml_poi_longitude" value="<?php echo esc_attr( $lng ); ?>" step="any" class="regular-text" placeholder="2.3522" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_poi_address"><?php esc_html_e( 'Adresse', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="text" id="iml_poi_address" name="iml_poi_address" value="<?php echo esc_attr( $address ); ?>" class="large-text" placeholder="<?php esc_attr_e( '12 rue de la Paix, 75001 Paris', 'interactive-map-listings' ); ?>" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_poi_external_link"><?php esc_html_e( 'Lien externe (optionnel)', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="url" id="iml_poi_external_link" name="iml_poi_external_link" value="<?php echo esc_url( $link ); ?>" class="large-text" placeholder="https://example.com" />
					<p class="description"><?php esc_html_e( 'Site web, fiche Google Maps, page de l\'établissement…', 'interactive-map-listings' ); ?></p>
				</td>
			</tr>
		</table>
		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( 'Assignez une catégorie via le panneau "Catégories POI" ci-contre pour que le marqueur soit coloré sur la carte.', 'interactive-map-listings' ); ?>
		</p>
		<?php
	}

	/**
	 * Save meta box data.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['iml_poi_meta_nonce'] ) || ! wp_verify_nonce( $_POST['iml_poi_meta_nonce'], 'iml_save_poi_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'iml_poi_short_description' => '_iml_poi_short_description',
			'iml_poi_latitude'          => '_iml_poi_latitude',
			'iml_poi_longitude'         => '_iml_poi_longitude',
			'iml_poi_address'           => '_iml_poi_address',
			'iml_poi_external_link'     => '_iml_poi_external_link',
		);

		foreach ( $fields as $form_key => $meta_key ) {
			if ( ! isset( $_POST[ $form_key ] ) ) {
				continue;
			}
			$value = $_POST[ $form_key ];

			switch ( $meta_key ) {
				case '_iml_poi_short_description':
					$value = sanitize_textarea_field( $value );
					break;
				case '_iml_poi_latitude':
				case '_iml_poi_longitude':
					$value = is_numeric( $value ) ? (float) $value : '';
					break;
				case '_iml_poi_address':
					$value = sanitize_text_field( $value );
					break;
				case '_iml_poi_external_link':
					$value = esc_url_raw( $value );
					break;
			}

			update_post_meta( $post_id, $meta_key, $value );
		}
	}
}
