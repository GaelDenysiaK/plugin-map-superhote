<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IML_Settings {

	const OPTION_NAME = 'iml_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Get default settings values.
	 */
	public static function get_defaults() {
		return array(
			'marker_color'       => '#E74C3C',
			'card_bg_color'      => '#FFFFFF',
			'card_text_color'    => '#333333',
			'button_color'       => '#3498DB',
			'button_text_color'  => '#FFFFFF',
			'default_lat'        => 46.603354,
			'default_lng'        => 1.888334,
			'default_zoom'       => 6,
			'button_label'       => 'Voir le logement',
			'superhote_web_key'  => '',
		);
	}

	/**
	 * Add settings page under Settings menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Interactive Map Listings', 'interactive-map-listings' ),
			__( 'Map Listings', 'interactive-map-listings' ),
			'manage_options',
			'iml-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings and fields.
	 */
	public function register_settings() {
		register_setting( 'iml_settings_group', self::OPTION_NAME, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
			'default'           => self::get_defaults(),
		) );

		// Colors section.
		add_settings_section(
			'iml_colors',
			__( 'Couleurs par défaut', 'interactive-map-listings' ),
			function () {
				echo '<p>' . esc_html__( 'Ces couleurs seront utilisées par défaut pour tous les blocs carte. Vous pouvez les remplacer individuellement dans les réglages de chaque bloc.', 'interactive-map-listings' ) . '</p>';
			},
			'iml-settings'
		);

		$color_fields = array(
			'marker_color'      => __( 'Couleur des marqueurs', 'interactive-map-listings' ),
			'card_bg_color'     => __( 'Fond de la card', 'interactive-map-listings' ),
			'card_text_color'   => __( 'Texte de la card', 'interactive-map-listings' ),
			'button_color'      => __( 'Couleur du bouton', 'interactive-map-listings' ),
			'button_text_color' => __( 'Texte du bouton', 'interactive-map-listings' ),
		);

		foreach ( $color_fields as $key => $label ) {
			add_settings_field(
				$key,
				$label,
				array( $this, 'render_color_field' ),
				'iml-settings',
				'iml_colors',
				array( 'field' => $key )
			);
		}

		// Map defaults section.
		add_settings_section(
			'iml_map_defaults',
			__( 'Paramètres de la carte', 'interactive-map-listings' ),
			function () {
				echo '<p>' . esc_html__( 'Centre et zoom par défaut de la carte.', 'interactive-map-listings' ) . '</p>';
			},
			'iml-settings'
		);

		add_settings_field(
			'default_lat',
			__( 'Latitude par défaut', 'interactive-map-listings' ),
			array( $this, 'render_number_field' ),
			'iml-settings',
			'iml_map_defaults',
			array( 'field' => 'default_lat', 'step' => 'any', 'placeholder' => '46.603354' )
		);

		add_settings_field(
			'default_lng',
			__( 'Longitude par défaut', 'interactive-map-listings' ),
			array( $this, 'render_number_field' ),
			'iml-settings',
			'iml_map_defaults',
			array( 'field' => 'default_lng', 'step' => 'any', 'placeholder' => '1.888334' )
		);

		add_settings_field(
			'default_zoom',
			__( 'Zoom par défaut', 'interactive-map-listings' ),
			array( $this, 'render_number_field' ),
			'iml-settings',
			'iml_map_defaults',
			array( 'field' => 'default_zoom', 'step' => '1', 'min' => '1', 'max' => '18', 'placeholder' => '6' )
		);

		add_settings_field(
			'button_label',
			__( 'Libellé du bouton', 'interactive-map-listings' ),
			array( $this, 'render_text_field' ),
			'iml-settings',
			'iml_map_defaults',
			array( 'field' => 'button_label', 'placeholder' => 'Voir le logement' )
		);

		// Superhote section.
		add_settings_section(
			'iml_superhote',
			__( 'Superhote — Moteur de réservation', 'interactive-map-listings' ),
			function () {
				echo '<p>' . esc_html__( 'Clé commune à tous vos logements, utilisée par le bloc "Moteur de réservation (groupe)". Trouvez-la dans votre interface Superhote.', 'interactive-map-listings' ) . '</p>';
			},
			'iml-settings'
		);

		add_settings_field(
			'superhote_web_key',
			__( 'Webkey (groupe)', 'interactive-map-listings' ),
			array( $this, 'render_text_field' ),
			'iml-settings',
			'iml_superhote',
			array( 'field' => 'superhote_web_key', 'placeholder' => 'ex: abc123xyz' )
		);
	}

	/**
	 * Render a color input field.
	 */
	public function render_color_field( $args ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		$value    = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : $defaults[ $args['field'] ];
		printf(
			'<input type="color" name="%s[%s]" value="%s" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['field'] ),
			esc_attr( $value )
		);
	}

	/**
	 * Render a number input field.
	 */
	public function render_number_field( $args ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		$value    = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : $defaults[ $args['field'] ];

		$attrs = sprintf(
			'type="number" name="%s[%s]" value="%s" class="regular-text"',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['field'] ),
			esc_attr( $value )
		);

		if ( isset( $args['step'] ) ) {
			$attrs .= sprintf( ' step="%s"', esc_attr( $args['step'] ) );
		}
		if ( isset( $args['min'] ) ) {
			$attrs .= sprintf( ' min="%s"', esc_attr( $args['min'] ) );
		}
		if ( isset( $args['max'] ) ) {
			$attrs .= sprintf( ' max="%s"', esc_attr( $args['max'] ) );
		}
		if ( isset( $args['placeholder'] ) ) {
			$attrs .= sprintf( ' placeholder="%s"', esc_attr( $args['placeholder'] ) );
		}

		echo '<input ' . $attrs . ' />';
	}

	/**
	 * Render a text input field.
	 */
	public function render_text_field( $args ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		$value    = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : $defaults[ $args['field'] ];
		printf(
			'<input type="text" name="%s[%s]" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['field'] ),
			esc_attr( $value ),
			esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' )
		);
	}

	/**
	 * Sanitize settings array.
	 */
	public function sanitize_settings( $input ) {
		$defaults  = self::get_defaults();
		$sanitized = array();

		$sanitized['marker_color']      = sanitize_hex_color( $input['marker_color'] ?? $defaults['marker_color'] ) ?: $defaults['marker_color'];
		$sanitized['card_bg_color']     = sanitize_hex_color( $input['card_bg_color'] ?? $defaults['card_bg_color'] ) ?: $defaults['card_bg_color'];
		$sanitized['card_text_color']   = sanitize_hex_color( $input['card_text_color'] ?? $defaults['card_text_color'] ) ?: $defaults['card_text_color'];
		$sanitized['button_color']      = sanitize_hex_color( $input['button_color'] ?? $defaults['button_color'] ) ?: $defaults['button_color'];
		$sanitized['button_text_color'] = sanitize_hex_color( $input['button_text_color'] ?? $defaults['button_text_color'] ) ?: $defaults['button_text_color'];
		$sanitized['default_lat']       = is_numeric( $input['default_lat'] ?? '' ) ? (float) $input['default_lat'] : $defaults['default_lat'];
		$sanitized['default_lng']       = is_numeric( $input['default_lng'] ?? '' ) ? (float) $input['default_lng'] : $defaults['default_lng'];
		$sanitized['default_zoom']      = isset( $input['default_zoom'] ) ? absint( $input['default_zoom'] ) : $defaults['default_zoom'];
		$sanitized['button_label']      = sanitize_text_field( $input['button_label'] ?? $defaults['button_label'] );
		$sanitized['superhote_web_key'] = sanitize_text_field( $input['superhote_web_key'] ?? '' );

		// Clamp zoom.
		if ( $sanitized['default_zoom'] < 1 ) {
			$sanitized['default_zoom'] = 1;
		}
		if ( $sanitized['default_zoom'] > 18 ) {
			$sanitized['default_zoom'] = 18;
		}

		return $sanitized;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'iml_settings_group' );
				do_settings_sections( 'iml-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
