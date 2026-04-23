<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IML_Meta_Boxes {

	public function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_logement', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register post meta for REST API exposure.
	 */
	public function register_meta() {
		$meta_fields = array(
			'_iml_short_description' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'_iml_latitude' => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_coordinate' ),
			),
			'_iml_longitude' => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_coordinate' ),
			),
			'_iml_capacity' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'_iml_action_url' => array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			),
			'_iml_superhote_property_key' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		foreach ( $meta_fields as $key => $args ) {
			register_post_meta( 'logement', $key, array(
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

	/**
	 * Sanitize latitude/longitude values.
	 */
	public function sanitize_coordinate( $value ) {
		return is_numeric( $value ) ? (float) $value : 0;
	}

	/**
	 * Add the Logement Details meta box.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'iml_logement_details',
			__( 'Détails du logement', 'interactive-map-listings' ),
			array( $this, 'render_meta_box' ),
			'logement',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box fields.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'iml_save_meta', 'iml_meta_nonce' );

		$description   = get_post_meta( $post->ID, '_iml_short_description', true );
		$latitude      = get_post_meta( $post->ID, '_iml_latitude', true );
		$longitude     = get_post_meta( $post->ID, '_iml_longitude', true );
		$capacity      = get_post_meta( $post->ID, '_iml_capacity', true );
		$action_url    = get_post_meta( $post->ID, '_iml_action_url', true );
		$property_key  = get_post_meta( $post->ID, '_iml_superhote_property_key', true );
		?>
		<table class="form-table">
			<tr>
				<th>
					<label for="iml_short_description"><?php esc_html_e( 'Description courte', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<textarea id="iml_short_description" name="iml_short_description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Brève description affichée dans la card de la carte.', 'interactive-map-listings' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_latitude"><?php esc_html_e( 'Latitude', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="number" id="iml_latitude" name="iml_latitude" value="<?php echo esc_attr( $latitude ); ?>" step="any" class="regular-text" placeholder="45.8326" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_longitude"><?php esc_html_e( 'Longitude', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="number" id="iml_longitude" name="iml_longitude" value="<?php echo esc_attr( $longitude ); ?>" step="any" class="regular-text" placeholder="6.8652" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_capacity"><?php esc_html_e( 'Capacité (personnes)', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="number" id="iml_capacity" name="iml_capacity" value="<?php echo esc_attr( $capacity ); ?>" min="1" class="small-text" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_action_url"><?php esc_html_e( 'URL du bouton', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="url" id="iml_action_url" name="iml_action_url" value="<?php echo esc_url( $action_url ); ?>" class="large-text" placeholder="https://example.com/reservation" />
					<p class="description"><?php esc_html_e( 'Lien vers lequel le bouton d\'action redirige. Si vide, renvoie vers la page du logement.', 'interactive-map-listings' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="iml_superhote_property_key"><?php esc_html_e( 'Clé Superhote (propertykey)', 'interactive-map-listings' ); ?></label>
				</th>
				<td>
					<input type="text" id="iml_superhote_property_key" name="iml_superhote_property_key" value="<?php echo esc_attr( $property_key ); ?>" class="regular-text" placeholder="ex: abc123xyz" />
					<p class="description">
						<?php esc_html_e( 'Clé unique du logement dans Superhote. Utilisée par le bloc "Moteur de réservation (logement)" pour afficher l\'iframe de réservation sur la page du logement.', 'interactive-map-listings' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta box data.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['iml_meta_nonce'] ) || ! wp_verify_nonce( $_POST['iml_meta_nonce'], 'iml_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'iml_short_description'      => '_iml_short_description',
			'iml_latitude'               => '_iml_latitude',
			'iml_longitude'              => '_iml_longitude',
			'iml_capacity'               => '_iml_capacity',
			'iml_action_url'             => '_iml_action_url',
			'iml_superhote_property_key' => '_iml_superhote_property_key',
		);

		foreach ( $fields as $form_key => $meta_key ) {
			if ( isset( $_POST[ $form_key ] ) ) {
				$value = $_POST[ $form_key ];

				switch ( $meta_key ) {
					case '_iml_short_description':
						$value = sanitize_textarea_field( $value );
						break;
					case '_iml_latitude':
					case '_iml_longitude':
						$value = is_numeric( $value ) ? (float) $value : '';
						break;
					case '_iml_capacity':
						$value = absint( $value );
						break;
					case '_iml_action_url':
						$value = esc_url_raw( $value );
						break;
					case '_iml_superhote_property_key':
						$value = sanitize_text_field( $value );
						break;
				}

				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}
}
