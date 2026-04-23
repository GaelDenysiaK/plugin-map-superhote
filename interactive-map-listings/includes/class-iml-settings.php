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
			// Map block colors.
			'marker_color'       => '#E74C3C',
			'card_bg_color'      => '#FFFFFF',
			'card_text_color'    => '#333333',
			'button_color'       => '#3498DB',
			'button_text_color'  => '#FFFFFF',
			// Map defaults.
			'default_lat'        => 46.603354,
			'default_lng'        => 1.888334,
			'default_zoom'       => 6,
			'button_label'       => 'Voir le logement',
			// Superhote keys.
			'superhote_web_key'  => '',
			// Superhote CSS customization.
			'superhote_primary_color'   => '#3498DB',
			'superhote_secondary_color' => '',
			'superhote_bg_color'        => '#ffffff',
			'superhote_font_family'     => '',
			'superhote_hide_price'      => false,
			'superhote_hide_address'    => false,
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

		// ── Colors section ──────────────────────────────────────────────────
		add_settings_section(
			'iml_colors',
			__( 'Couleurs par défaut (carte)', 'interactive-map-listings' ),
			function () {
				echo '<p>' . esc_html__( 'Ces couleurs s\'appliquent aux blocs carte. Remplaçables individuellement dans chaque bloc.', 'interactive-map-listings' ) . '</p>';
			},
			'iml-settings'
		);

		foreach ( array(
			'marker_color'      => __( 'Couleur des marqueurs', 'interactive-map-listings' ),
			'card_bg_color'     => __( 'Fond de la card', 'interactive-map-listings' ),
			'card_text_color'   => __( 'Texte de la card', 'interactive-map-listings' ),
			'button_color'      => __( 'Couleur du bouton', 'interactive-map-listings' ),
			'button_text_color' => __( 'Texte du bouton', 'interactive-map-listings' ),
		) as $key => $label ) {
			add_settings_field( $key, $label, array( $this, 'render_color_field' ), 'iml-settings', 'iml_colors', array( 'field' => $key ) );
		}

		// ── Map defaults section ────────────────────────────────────────────
		add_settings_section(
			'iml_map_defaults',
			__( 'Paramètres de la carte', 'interactive-map-listings' ),
			function () {
				echo '<p>' . esc_html__( 'Centre et zoom par défaut de la carte.', 'interactive-map-listings' ) . '</p>';
			},
			'iml-settings'
		);

		add_settings_field( 'default_lat',   __( 'Latitude par défaut', 'interactive-map-listings' ),  array( $this, 'render_number_field' ), 'iml-settings', 'iml_map_defaults', array( 'field' => 'default_lat',  'step' => 'any', 'placeholder' => '46.603354' ) );
		add_settings_field( 'default_lng',   __( 'Longitude par défaut', 'interactive-map-listings' ), array( $this, 'render_number_field' ), 'iml-settings', 'iml_map_defaults', array( 'field' => 'default_lng',  'step' => 'any', 'placeholder' => '1.888334' ) );
		add_settings_field( 'default_zoom',  __( 'Zoom par défaut', 'interactive-map-listings' ),      array( $this, 'render_number_field' ), 'iml-settings', 'iml_map_defaults', array( 'field' => 'default_zoom', 'step' => '1', 'min' => '1', 'max' => '18', 'placeholder' => '6' ) );
		add_settings_field( 'button_label',  __( 'Libellé du bouton', 'interactive-map-listings' ),    array( $this, 'render_text_field' ),   'iml-settings', 'iml_map_defaults', array( 'field' => 'button_label', 'placeholder' => 'Voir le logement' ) );

		// ── Superhote keys section ───────────────────────────────────────────
		add_settings_section(
			'iml_superhote',
			__( 'Superhote — Clés d\'accès', 'interactive-map-listings' ),
			function () {
				echo '<p>' . esc_html__( 'Clé commune à tous vos logements, utilisée par le bloc "Moteur de réservation (groupe)".', 'interactive-map-listings' ) . '</p>';
			},
			'iml-settings'
		);

		add_settings_field( 'superhote_web_key', __( 'Webkey (groupe)', 'interactive-map-listings' ), array( $this, 'render_text_field' ), 'iml-settings', 'iml_superhote', array( 'field' => 'superhote_web_key', 'placeholder' => 'ex: abc123xyz' ) );

		// ── Superhote CSS customization section ──────────────────────────────
		add_settings_section(
			'iml_superhote_css',
			__( 'Superhote — Personnalisation visuelle', 'interactive-map-listings' ),
			function () {
				echo '<p>' . wp_kses(
					__( 'Configurez les couleurs et la police de vos moteurs de réservation. Le CSS généré est à copier dans <strong>Superhote → Paramètres → Personnalisation CSS</strong>.', 'interactive-map-listings' ),
					array( 'strong' => array() )
				) . '</p>';
			},
			'iml-settings'
		);

		add_settings_field( 'superhote_primary_color',   __( 'Couleur principale', 'interactive-map-listings' ),        array( $this, 'render_color_field' ),    'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_primary_color',   'desc' => __( 'Boutons, flèches, titres, prix', 'interactive-map-listings' ) ) );
		add_settings_field( 'superhote_secondary_color', __( 'Couleur secondaire', 'interactive-map-listings' ),        array( $this, 'render_color_field' ),    'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_secondary_color', 'desc' => __( 'Bouton "Réserver" et validation paiement. Laissez vide = même que principale.', 'interactive-map-listings' ) ) );
		add_settings_field( 'superhote_bg_color',        __( 'Couleur de fond', 'interactive-map-listings' ),           array( $this, 'render_color_field' ),    'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_bg_color' ) );
		add_settings_field( 'superhote_font_family',     __( 'Police de caractères', 'interactive-map-listings' ),      array( $this, 'render_text_field' ),     'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_font_family', 'placeholder' => 'Laissez vide pour hériter la police du site' ) );
		add_settings_field( 'superhote_hide_price',      __( 'Masquer les prix', 'interactive-map-listings' ),          array( $this, 'render_checkbox_field' ), 'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_hide_price' ) );
		add_settings_field( 'superhote_hide_address',    __( 'Masquer les adresses', 'interactive-map-listings' ),      array( $this, 'render_checkbox_field' ), 'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_hide_address' ) );
	}

	// ── Field renderers ──────────────────────────────────────────────────────

	public function render_color_field( $args ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		$value    = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : ( $defaults[ $args['field'] ] ?? '' );

		// Secondary color is optional — no forced default.
		$type  = ( $args['field'] === 'superhote_secondary_color' && empty( $value ) ) ? 'text' : 'color';
		$attrs = sprintf(
			'type="%s" name="%s[%s]" value="%s"',
			$type,
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['field'] ),
			esc_attr( $value )
		);
		if ( $type === 'text' ) {
			$attrs .= ' placeholder="' . esc_attr__( 'Laissez vide = même que principale', 'interactive-map-listings' ) . '" class="regular-text"';
		}
		echo '<input ' . $attrs . ' />';

		if ( ! empty( $args['desc'] ) ) {
			echo ' <span class="description">' . esc_html( $args['desc'] ) . '</span>';
		}
	}

	public function render_number_field( $args ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		$value    = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : $defaults[ $args['field'] ];

		$attrs = sprintf( 'type="number" name="%s[%s]" value="%s" class="regular-text"', esc_attr( self::OPTION_NAME ), esc_attr( $args['field'] ), esc_attr( $value ) );
		if ( isset( $args['step'] ) )        $attrs .= ' step="'        . esc_attr( $args['step'] ) . '"';
		if ( isset( $args['min'] ) )         $attrs .= ' min="'         . esc_attr( $args['min'] ) . '"';
		if ( isset( $args['max'] ) )         $attrs .= ' max="'         . esc_attr( $args['max'] ) . '"';
		if ( isset( $args['placeholder'] ) ) $attrs .= ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
		echo '<input ' . $attrs . ' />';
	}

	public function render_text_field( $args ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		$value    = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : ( $defaults[ $args['field'] ] ?? '' );
		printf(
			'<input type="text" name="%s[%s]" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['field'] ),
			esc_attr( $value ),
			esc_attr( $args['placeholder'] ?? '' )
		);
	}

	public function render_checkbox_field( $args ) {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$checked = ! empty( $options[ $args['field'] ] );
		printf(
			'<input type="checkbox" name="%s[%s]" value="1" %s />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['field'] ),
			checked( $checked, true, false )
		);
	}

	// ── Sanitize ─────────────────────────────────────────────────────────────

	public function sanitize_settings( $input ) {
		$defaults  = self::get_defaults();
		$sanitized = array();

		// Map block colors.
		foreach ( array( 'marker_color', 'card_bg_color', 'card_text_color', 'button_color', 'button_text_color' ) as $key ) {
			$sanitized[ $key ] = sanitize_hex_color( $input[ $key ] ?? $defaults[ $key ] ) ?: $defaults[ $key ];
		}

		// Map defaults.
		$sanitized['default_lat']  = is_numeric( $input['default_lat'] ?? '' )  ? (float) $input['default_lat']  : $defaults['default_lat'];
		$sanitized['default_lng']  = is_numeric( $input['default_lng'] ?? '' )  ? (float) $input['default_lng']  : $defaults['default_lng'];
		$sanitized['default_zoom'] = isset( $input['default_zoom'] ) ? max( 1, min( 18, absint( $input['default_zoom'] ) ) ) : $defaults['default_zoom'];
		$sanitized['button_label'] = sanitize_text_field( $input['button_label'] ?? $defaults['button_label'] );

		// Superhote keys.
		$sanitized['superhote_web_key'] = sanitize_text_field( $input['superhote_web_key'] ?? '' );

		// Superhote CSS customization.
		$sanitized['superhote_primary_color']   = sanitize_hex_color( $input['superhote_primary_color'] ?? $defaults['superhote_primary_color'] ) ?: $defaults['superhote_primary_color'];
		$sanitized['superhote_secondary_color'] = ! empty( $input['superhote_secondary_color'] ) ? ( sanitize_hex_color( $input['superhote_secondary_color'] ) ?: '' ) : '';
		$sanitized['superhote_bg_color']        = sanitize_hex_color( $input['superhote_bg_color'] ?? $defaults['superhote_bg_color'] ) ?: $defaults['superhote_bg_color'];
		$sanitized['superhote_font_family']     = sanitize_text_field( $input['superhote_font_family'] ?? '' );
		$sanitized['superhote_hide_price']      = ! empty( $input['superhote_hide_price'] );
		$sanitized['superhote_hide_address']    = ! empty( $input['superhote_hide_address'] );

		return $sanitized;
	}

	// ── CSS generator ─────────────────────────────────────────────────────────

	/**
	 * Generate the full Superhote custom CSS string from current settings.
	 */
	public static function generate_superhote_css( array $settings ) {
		$primary   = $settings['superhote_primary_color']   ?? '#3498DB';
		$secondary = ! empty( $settings['superhote_secondary_color'] ) ? $settings['superhote_secondary_color'] : $primary;
		$bg        = $settings['superhote_bg_color']        ?? '#ffffff';
		$font_raw  = trim( $settings['superhote_font_family'] ?? '' );
		$font      = empty( $font_raw ) ? 'inherit' : $font_raw;
		$hide_price   = ! empty( $settings['superhote_hide_price'] );
		$hide_address = ! empty( $settings['superhote_hide_address'] );

		$lines = array();

		// ── Couleur principale ───────────────────────────────────────────────
		$lines[] = '/* === Couleur principale === */';
		$lines[] = "#external-booking .select-section .btn { border-color: {$primary}; background-color: {$primary}; }";
		$lines[] = "#vueper-slides .vueperslides__arrows--outside .vueperslides__arrow--next { background-color: {$primary}; }";
		$lines[] = "#vueper-slides .vueperslides__arrows--outside .vueperslides__arrow--prev { background-color: {$primary}; }";
		$lines[] = "#exampleModal .vueperslides__arrows .vueperslides__arrow--next { background-color: {$primary}; }";
		$lines[] = "#exampleModal .vueperslides__arrows .vueperslides__arrow--prev { background-color: {$primary}; }";
		$lines[] = "#external-booking .rentals-list .rental .rental-price .sales-price { color: {$primary}; }";
		$lines[] = "#external-booking .rentals-list .rental-name { color: {$primary}; }";
		$lines[] = "#external-rental-detail .main-title { color: {$primary}; }";
		$lines[] = "#external-rental-detail .the-building .building-subtitle { color: {$primary}; }";
		$lines[] = "#external-rental-detail .rental-location { color: {$primary}; }";
		$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .rental-widget-title { color: {$primary}; }";
		$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .rental-widget-subtitle { color: {$primary}; }";
		$lines[] = ".sticky-bottomRow .checking-availability-btn { background: {$primary}; border: 1px solid {$primary}; }";
		$lines[] = ".sticky-bottomRow .checking-availability-btn p { color: #fff; }";
		$lines[] = ".sticky-bottomRow .check-availability-row .price-text { color: {$primary}; }";

		// ── Couleur secondaire (bouton Réserver + paiement) ──────────────────
		$lines[] = '';
		$lines[] = '/* === Couleur secondaire (boutons de réservation) === */';
		$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .action-box .book-btn { background: {$secondary}; border: 1px solid {$secondary}; }";
		$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .action-box .book-btn p { color: #fff; }";
		$lines[] = "#detail-checkout .checkout .payment-info-part #payment-info-field #payment-fields .booking-btn { background: {$secondary}; border: 1px solid {$secondary}; }";
		$lines[] = "#detail-checkout .checkout .payment-info-part #payment-info-field #payment-fields .booking-btn p { color: #fff; font-family: {$font}; }";

		// ── Fond ─────────────────────────────────────────────────────────────
		$lines[] = '';
		$lines[] = '/* === Fond === */';
		$lines[] = "#external-booking { background-color: {$bg}; }";
		$lines[] = "#external-booking .select-section .section-search { background-color: {$bg}; }";
		$lines[] = "#external-booking .select-section .vdp-datepicker input { background-color: {$bg}; }";
		$lines[] = "#external-booking .rentals-list .rental .rental-price { background-color: {$bg}; }";
		$lines[] = "#external-booking .select-section .vdp-datepicker .vdp-datepicker__calendar { background-color: #f5f5f5; }";
		$lines[] = "#external-rental-detail { background-color: {$bg}; }";
		$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .vdp-datepicker input { background-color: {$bg}; }";
		$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .vdp-datepicker .vdp-datepicker__calendar { background-color: #f5f5f5; }";
		$lines[] = ".search-section { background-color: {$bg}; }";
		$lines[] = "#detail-checkout { background-color: {$bg}; }";
		$lines[] = "#detail-checkout .detail-form-control, #detail-checkout .detail-form-select { background-color: {$bg}; }";
		$lines[] = "#vueper-slides .vueperslides { background: {$bg}; }";

		// ── Police ───────────────────────────────────────────────────────────
		$lines[] = '';
		$lines[] = '/* === Police de caractères === */';
		$lines[] = "body { font-family: {$font}; }";
		foreach ( array(
			"input, select, textarea",
			"#external-booking .rentals-list .rental-name",
			"#external-booking .rentals-list .owner-city-address",
			"#external-booking .rentals-list .rental-capacity, #external-booking .rentals-list .rental-room-count",
			"#external-rental-detail .main-title",
			"#external-rental-detail .rental-location",
			"#external-rental-detail .rental-base-info",
			"#external-rental-detail .rental-description",
			"#external-rental-detail .rental-amenities .amenities-title",
			"#external-rental-detail .rental-amenities .amenities-box .amenities-card .amenities-card-text .amenities-subtitle",
			"#external-rental-detail .things-to-note .to-note-title",
			"#external-rental-detail .things-to-note .to-note-box .to-note-content .to-note-subtitle",
			"#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .rental-widget-title",
			"#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .rental-widget-subtitle",
			"#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .box-available .available-text",
			"#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .callout-box .callout-text .callout-content-text, #external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .callout-box .callout-text .callout-text-title",
			"#detail-checkout .checkout #guest-info-text p",
			"#detail-checkout .form-label",
			"#detail-checkout .info-right-side .right-side-content .right-side-info-part .rental-name",
			"#detail-checkout .info-right-side .right-side-content .box-available .available-text",
			"#detail-checkout .checkout .payment-info-part #payment-info-field .payment-title p",
		) as $selector ) {
			$lines[] = "{$selector} { font-family: {$font}; }";
		}

		// ── Masquer le prix ───────────────────────────────────────────────────
		if ( $hide_price ) {
			$lines[] = '';
			$lines[] = '/* === Masquer le prix === */';
			$lines[] = "#external-booking .rentals-list .rental .rental-price { display: none; }";
		}

		// ── Masquer les adresses ──────────────────────────────────────────────
		if ( $hide_address ) {
			$lines[] = '';
			$lines[] = '/* === Masquer les adresses === */';
			$lines[] = "#external-booking .rentals-list .owner-city-address { visibility: hidden; }";
			$lines[] = "#external-rental-detail .rental-location { visibility: hidden; }";
			$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .rental-widget-subtitle { visibility: hidden; }";
			$lines[] = "#detail-checkout .info-right-side .right-side-content .right-side-info-part .rental-address { visibility: hidden; }";
		}

		return implode( "\n", $lines );
	}

	// ── Settings page ────────────────────────────────────────────────────────

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings     = get_option( self::OPTION_NAME, self::get_defaults() );
		$settings     = wp_parse_args( $settings, self::get_defaults() );
		$generated_css = self::generate_superhote_css( $settings );
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

			<?php /* ── Generated CSS panel ── */ ?>
			<hr style="margin: 32px 0;" />
			<h2><?php esc_html_e( 'CSS généré pour Superhote', 'interactive-map-listings' ); ?></h2>
			<p>
				<?php echo wp_kses(
					__( 'Copiez ce CSS dans votre interface Superhote : <strong>Paramètres → Personnalisation CSS</strong>. Il est automatiquement mis à jour lorsque vous enregistrez les réglages ci-dessus.', 'interactive-map-listings' ),
					array( 'strong' => array() )
				); ?>
			</p>
			<div style="position:relative; max-width:900px;">
				<textarea id="iml-generated-css"
					readonly
					rows="20"
					style="width:100%; font-family:monospace; font-size:12px; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; padding:12px; resize:vertical; line-height:1.5;"
				><?php echo esc_textarea( $generated_css ); ?></textarea>
				<button type="button" id="iml-copy-css-btn"
					class="button button-secondary"
					style="position:absolute; top:8px; right:8px;"
				>
					📋 <?php esc_html_e( 'Copier le CSS', 'interactive-map-listings' ); ?>
				</button>
			</div>
			<p id="iml-copy-notice" style="display:none; color:#00a32a; font-weight:600; margin-top:6px;">
				✓ <?php esc_html_e( 'CSS copié dans le presse-papiers !', 'interactive-map-listings' ); ?>
			</p>
		</div>

		<script>
		( function () {
			var btn    = document.getElementById( 'iml-copy-css-btn' );
			var notice = document.getElementById( 'iml-copy-notice' );
			var area   = document.getElementById( 'iml-generated-css' );
			if ( ! btn || ! area ) return;

			btn.addEventListener( 'click', function () {
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( area.value ).then( showNotice );
				} else {
					// Fallback for older browsers.
					area.select();
					document.execCommand( 'copy' );
					showNotice();
				}
			} );

			function showNotice() {
				notice.style.display = 'block';
				setTimeout( function () { notice.style.display = 'none'; }, 3000 );
			}
		} )();
		</script>
		<?php
	}
}
