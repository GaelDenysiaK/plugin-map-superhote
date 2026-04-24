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

	// ── Defaults ─────────────────────────────────────────────────────────────

	public static function get_defaults() {
		return array(
			'marker_color'              => '#E74C3C',
			'card_bg_color'             => '#FFFFFF',
			'card_text_color'           => '#333333',
			'button_color'              => '#3498DB',
			'button_text_color'         => '#FFFFFF',
			'default_lat'               => 46.603354,
			'default_lng'               => 1.888334,
			'default_zoom'              => 6,
			'button_label'              => 'Voir le logement',
			'superhote_web_key'         => '',
			'superhote_primary_color'   => '#3498DB',
			'superhote_secondary_color' => '',
			'superhote_bg_color'        => '#ffffff',
			'superhote_font_family'     => '',
			'superhote_hide_price'      => false,
			'superhote_hide_address'    => false,
			// POI category colors.
			'poi_color_patrimoine'      => '#7C5CBF',
			'poi_color_nature'          => '#3A9E6E',
			'poi_color_loisirs'         => '#E07B39',
			'poi_color_commerces'       => '#2E86C1',
			'poi_color_restaurants'     => '#C0392B',
			'poi_color_services'        => '#7F8C8D',
		);
	}

	// ── theme.json helpers ───────────────────────────────────────────────────

	/**
	 * Return the merged color palette from theme.json (theme + custom).
	 * Each entry: { color, name, slug }
	 *
	 * @return array
	 */
	public static function get_theme_palette() {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return array();
		}
		$theme  = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
		$custom = wp_get_global_settings( array( 'color', 'palette', 'custom' ) );
		return array_merge(
			is_array( $theme )  ? $theme  : array(),
			is_array( $custom ) ? $custom : array()
		);
	}

	/**
	 * Return the font families from theme.json (theme + custom).
	 * Each entry: { fontFamily, name, slug }
	 *
	 * @return array
	 */
	public static function get_theme_font_families() {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return array();
		}
		$theme  = wp_get_global_settings( array( 'typography', 'fontFamilies', 'theme' ) );
		$custom = wp_get_global_settings( array( 'typography', 'fontFamilies', 'custom' ) );
		return array_merge(
			is_array( $theme )  ? $theme  : array(),
			is_array( $custom ) ? $custom : array()
		);
	}

	// ── Admin menu ────────────────────────────────────────────────────────────

	public function add_settings_page() {
		add_options_page(
			__( 'Interactive Map Listings', 'interactive-map-listings' ),
			__( 'Map Listings', 'interactive-map-listings' ),
			'manage_options',
			'iml-settings',
			array( $this, 'render_settings_page' )
		);
	}

	// ── Settings registration ─────────────────────────────────────────────────

	public function register_settings() {
		register_setting( 'iml_settings_group', self::OPTION_NAME, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
			'default'           => self::get_defaults(),
		) );

		// ── Carte : couleurs ─────────────────────────────────────────────────
		add_settings_section( 'iml_colors', __( 'Couleurs par défaut (carte)', 'interactive-map-listings' ),
			function () { echo '<p>' . esc_html__( 'Couleurs utilisées par le bloc carte. Remplaçables individuellement dans chaque bloc.', 'interactive-map-listings' ) . '</p>'; },
			'iml-settings'
		);
		foreach ( array(
			'marker_color'      => __( 'Marqueurs', 'interactive-map-listings' ),
			'card_bg_color'     => __( 'Fond de la card', 'interactive-map-listings' ),
			'card_text_color'   => __( 'Texte de la card', 'interactive-map-listings' ),
			'button_color'      => __( 'Bouton', 'interactive-map-listings' ),
			'button_text_color' => __( 'Texte du bouton', 'interactive-map-listings' ),
		) as $key => $label ) {
			add_settings_field( $key, $label, array( $this, 'render_color_swatch_field' ), 'iml-settings', 'iml_colors', array( 'field' => $key ) );
		}

		// ── Carte : paramètres ───────────────────────────────────────────────
		add_settings_section( 'iml_map_defaults', __( 'Paramètres de la carte', 'interactive-map-listings' ),
			function () { echo '<p>' . esc_html__( 'Centre et zoom par défaut.', 'interactive-map-listings' ) . '</p>'; },
			'iml-settings'
		);
		add_settings_field( 'default_lat',  __( 'Latitude par défaut', 'interactive-map-listings' ),  array( $this, 'render_number_field' ), 'iml-settings', 'iml_map_defaults', array( 'field' => 'default_lat',  'step' => 'any', 'placeholder' => '46.603354' ) );
		add_settings_field( 'default_lng',  __( 'Longitude par défaut', 'interactive-map-listings' ), array( $this, 'render_number_field' ), 'iml-settings', 'iml_map_defaults', array( 'field' => 'default_lng',  'step' => 'any', 'placeholder' => '1.888334' ) );
		add_settings_field( 'default_zoom', __( 'Zoom par défaut', 'interactive-map-listings' ),      array( $this, 'render_number_field' ), 'iml-settings', 'iml_map_defaults', array( 'field' => 'default_zoom', 'step' => '1', 'min' => '1', 'max' => '18', 'placeholder' => '6' ) );
		add_settings_field( 'button_label', __( 'Libellé du bouton', 'interactive-map-listings' ),    array( $this, 'render_text_field' ),   'iml-settings', 'iml_map_defaults', array( 'field' => 'button_label', 'placeholder' => 'Voir le logement' ) );

		// ── Points d'intérêt : couleurs par catégorie ───────────────────────
		add_settings_section( 'iml_poi_colors', __( 'Points d\'intérêt — Couleurs par catégorie', 'interactive-map-listings' ),
			function () {
				echo '<p>' . esc_html__( 'Couleur des marqueurs POI sur la carte, par catégorie. Ces couleurs s\'appliquent aussi à la pastille dans la barre de filtres.', 'interactive-map-listings' ) . '</p>';
			},
			'iml-settings'
		);
		foreach ( array(
			'poi_color_patrimoine'   => __( 'Patrimoine & Culture', 'interactive-map-listings' ),
			'poi_color_nature'       => __( 'Nature & Randonnée', 'interactive-map-listings' ),
			'poi_color_loisirs'      => __( 'Loisirs & Activités', 'interactive-map-listings' ),
			'poi_color_commerces'    => __( 'Commerces essentiels', 'interactive-map-listings' ),
			'poi_color_restaurants'  => __( 'Restaurants & Marchés', 'interactive-map-listings' ),
			'poi_color_services'     => __( 'Services pratiques', 'interactive-map-listings' ),
		) as $key => $label ) {
			add_settings_field( $key, $label, array( $this, 'render_color_swatch_field' ), 'iml-settings', 'iml_poi_colors', array( 'field' => $key ) );
		}

		// ── Superhote : clés ─────────────────────────────────────────────────
		add_settings_section( 'iml_superhote', __( 'Superhote — Clés d\'accès', 'interactive-map-listings' ),
			function () { echo '<p>' . esc_html__( 'Webkey commune à tous vos logements, utilisée par le bloc "Moteur de réservation (groupe)".', 'interactive-map-listings' ) . '</p>'; },
			'iml-settings'
		);
		add_settings_field( 'superhote_web_key', __( 'Webkey (groupe)', 'interactive-map-listings' ), array( $this, 'render_text_field' ), 'iml-settings', 'iml_superhote', array( 'field' => 'superhote_web_key', 'placeholder' => 'ex: abc123xyz' ) );

		// ── Superhote : personnalisation CSS ─────────────────────────────────
		add_settings_section( 'iml_superhote_css', __( 'Superhote — Personnalisation visuelle', 'interactive-map-listings' ),
			function () {
				echo '<p>' . wp_kses(
					__( 'Configurez l\'apparence de vos moteurs de réservation. Le CSS généré est à copier dans <strong>Superhote → Paramètres → Personnalisation CSS</strong>.', 'interactive-map-listings' ),
					array( 'strong' => array() )
				) . '</p>';
			},
			'iml-settings'
		);
		add_settings_field( 'superhote_primary_color',   __( 'Couleur principale', 'interactive-map-listings' ),   array( $this, 'render_color_swatch_field' ),  'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_primary_color',   'desc' => __( 'Boutons, flèches, titres, prix', 'interactive-map-listings' ) ) );
		add_settings_field( 'superhote_secondary_color', __( 'Couleur secondaire', 'interactive-map-listings' ),   array( $this, 'render_color_swatch_field' ),  'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_secondary_color', 'optional' => true, 'desc' => __( 'Bouton "Réserver" et validation paiement. Vide = même que principale.', 'interactive-map-listings' ) ) );
		add_settings_field( 'superhote_bg_color',        __( 'Couleur de fond', 'interactive-map-listings' ),      array( $this, 'render_color_swatch_field' ),  'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_bg_color' ) );
		add_settings_field( 'superhote_font_family',     __( 'Police de caractères', 'interactive-map-listings' ), array( $this, 'render_font_family_field' ),   'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_font_family' ) );
		add_settings_field( 'superhote_hide_price',      __( 'Masquer les prix', 'interactive-map-listings' ),     array( $this, 'render_checkbox_field' ),      'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_hide_price' ) );
		add_settings_field( 'superhote_hide_address',    __( 'Masquer les adresses', 'interactive-map-listings' ), array( $this, 'render_checkbox_field' ),      'iml-settings', 'iml_superhote_css', array( 'field' => 'superhote_hide_address' ) );
	}

	// ── Field renderers ───────────────────────────────────────────────────────

	/**
	 * Color field with theme.json palette swatches + native color picker fallback.
	 */
	public function render_color_swatch_field( $args ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		$field    = $args['field'];
		$optional = ! empty( $args['optional'] );
		$value    = $options[ $field ] ?? ( $defaults[ $field ] ?? '' );

		$palette = self::get_theme_palette();

		echo '<div class="iml-color-field" data-optional="' . ( $optional ? '1' : '0' ) . '">';

		if ( ! empty( $palette ) ) {
			echo '<div class="iml-swatches">';
			foreach ( $palette as $item ) {
				if ( empty( $item['color'] ) ) continue;
				$hex      = esc_attr( $item['color'] );
				$name     = esc_attr( $item['name'] ?? $item['slug'] ?? '' );
				$active   = ( ! empty( $value ) && strtolower( $value ) === strtolower( $item['color'] ) ) ? ' is-active' : '';
				printf(
					'<button type="button" class="iml-swatch%s" data-color="%s" style="background:%s;" title="%s" aria-label="%s"></button>',
					$active, $hex, $hex, $name, $name
				);
			}
			// Custom color swatch (gradient icon → opens native picker).
			printf(
				'<label class="iml-swatch iml-swatch-custom" title="%s">
					<input type="color" class="iml-color-custom" value="%s" />
				</label>',
				esc_attr__( 'Couleur personnalisée', 'interactive-map-listings' ),
				esc_attr( $value ?: '#3498db' )
			);
			echo '</div>';
		} else {
			// No theme.json palette — show native picker directly.
			printf(
				'<input type="color" class="iml-color-custom" value="%s" />',
				esc_attr( $value ?: '#3498db' )
			);
		}

		// Hidden input for form submission.
		printf(
			'<input type="hidden" name="%s[%s]" class="iml-color-hidden" value="%s" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $field ),
			esc_attr( $value )
		);

		// Current value display.
		echo '<div class="iml-color-preview">';
		printf( '<span class="iml-color-dot" style="background:%s;"></span>', esc_attr( $value ?: 'transparent' ) );
		printf( '<code class="iml-color-hex">%s</code>', esc_html( $value ?: '—' ) );
		if ( $optional && ! empty( $value ) ) {
			echo ' <button type="button" class="iml-color-clear button-link">' . esc_html__( 'Réinitialiser', 'interactive-map-listings' ) . '</button>';
		}
		echo '</div>';

		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Font family field: <select> from theme.json fonts, or text input fallback.
	 */
	public function render_font_family_field( $args ) {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$field   = $args['field'];
		$value   = $options[ $field ] ?? '';
		$fonts   = self::get_theme_font_families();

		if ( ! empty( $fonts ) ) {
			printf(
				'<select name="%s[%s]" class="iml-font-select">',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $field )
			);
			printf(
				'<option value="">%s</option>',
				esc_html__( '— Hériter la police du site (recommandé) —', 'interactive-map-listings' )
			);
			echo '<optgroup label="' . esc_attr__( 'Polices du thème', 'interactive-map-listings' ) . '">';
			foreach ( $fonts as $font ) {
				if ( empty( $font['fontFamily'] ) ) continue;
				$css_val = $font['fontFamily'];
				$name    = $font['name'] ?? $font['slug'] ?? $css_val;
				printf(
					'<option value="%s" %s style="font-family:%s">%s</option>',
					esc_attr( $css_val ),
					selected( $value, $css_val, false ),
					esc_attr( $css_val ),
					esc_html( $name )
				);
			}
			echo '</optgroup></select>';
			echo '<p class="description">' . esc_html__( 'Polices déclarées dans le theme.json actif.', 'interactive-map-listings' ) . '</p>';
		} else {
			// Classic theme — text input.
			printf(
				'<input type="text" name="%s[%s]" value="%s" class="regular-text" placeholder="%s" />',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $field ),
				esc_attr( $value ),
				esc_attr__( 'ex: "Helvetica Neue", sans-serif  (vide = hériter)', 'interactive-map-listings' )
			);
			echo '<p class="description">' . esc_html__( 'Aucune police détectée dans le theme.json — saisissez la valeur CSS manuellement.', 'interactive-map-listings' ) . '</p>';
		}
	}

	public function render_number_field( $args ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		$value    = $options[ $args['field'] ] ?? $defaults[ $args['field'] ];
		$attrs    = sprintf( 'type="number" name="%s[%s]" value="%s" class="regular-text"', esc_attr( self::OPTION_NAME ), esc_attr( $args['field'] ), esc_attr( $value ) );
		if ( isset( $args['step'] ) )        $attrs .= ' step="'        . esc_attr( $args['step'] ) . '"';
		if ( isset( $args['min'] ) )         $attrs .= ' min="'         . esc_attr( $args['min'] ) . '"';
		if ( isset( $args['max'] ) )         $attrs .= ' max="'         . esc_attr( $args['max'] ) . '"';
		if ( isset( $args['placeholder'] ) ) $attrs .= ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
		echo '<input ' . $attrs . ' />';
	}

	public function render_text_field( $args ) {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$value   = $options[ $args['field'] ] ?? ( self::get_defaults()[ $args['field'] ] ?? '' );
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

		foreach ( array(
			'marker_color', 'card_bg_color', 'card_text_color', 'button_color', 'button_text_color',
			'superhote_primary_color', 'superhote_bg_color',
			'poi_color_patrimoine', 'poi_color_nature', 'poi_color_loisirs',
			'poi_color_commerces', 'poi_color_restaurants', 'poi_color_services',
		) as $key ) {
			$sanitized[ $key ] = sanitize_hex_color( $input[ $key ] ?? $defaults[ $key ] ) ?: $defaults[ $key ];
		}

		// Secondary color is optional (empty = use primary).
		$sanitized['superhote_secondary_color'] = ! empty( $input['superhote_secondary_color'] )
			? ( sanitize_hex_color( $input['superhote_secondary_color'] ) ?: '' )
			: '';

		$sanitized['default_lat']  = is_numeric( $input['default_lat'] ?? '' )  ? (float) $input['default_lat']  : $defaults['default_lat'];
		$sanitized['default_lng']  = is_numeric( $input['default_lng'] ?? '' )  ? (float) $input['default_lng']  : $defaults['default_lng'];
		$sanitized['default_zoom'] = max( 1, min( 18, absint( $input['default_zoom'] ?? $defaults['default_zoom'] ) ) );
		$sanitized['button_label'] = sanitize_text_field( $input['button_label'] ?? $defaults['button_label'] );

		$sanitized['superhote_web_key']     = sanitize_text_field( $input['superhote_web_key'] ?? '' );
		$sanitized['superhote_font_family'] = sanitize_text_field( $input['superhote_font_family'] ?? '' );
		$sanitized['superhote_hide_price']   = ! empty( $input['superhote_hide_price'] );
		$sanitized['superhote_hide_address'] = ! empty( $input['superhote_hide_address'] );

		return $sanitized;
	}

	// ── CSS generator ─────────────────────────────────────────────────────────

	public static function generate_superhote_css( array $settings ) {
		$primary   = $settings['superhote_primary_color']   ?? '#3498DB';
		$secondary = ! empty( $settings['superhote_secondary_color'] ) ? $settings['superhote_secondary_color'] : $primary;
		$bg        = $settings['superhote_bg_color']        ?? '#ffffff';
		$font_raw  = trim( $settings['superhote_font_family'] ?? '' );
		$font      = empty( $font_raw ) ? 'inherit' : $font_raw;
		$hide_price   = ! empty( $settings['superhote_hide_price'] );
		$hide_address = ! empty( $settings['superhote_hide_address'] );

		$lines = array();

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

		$lines[] = '';
		$lines[] = '/* === Couleur secondaire (boutons de réservation) === */';
		$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .action-box .book-btn { background: {$secondary}; border: 1px solid {$secondary}; }";
		$lines[] = "#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .action-box .book-btn p { color: #fff; }";
		$lines[] = "#detail-checkout .checkout .payment-info-part #payment-info-field #payment-fields .booking-btn { background: {$secondary}; border: 1px solid {$secondary}; }";
		$lines[] = "#detail-checkout .checkout .payment-info-part #payment-info-field #payment-fields .booking-btn p { color: #fff; font-family: {$font}; }";

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

		$lines[] = '';
		$lines[] = '/* === Police de caractères === */';
		$lines[] = "body { font-family: {$font}; }";
		foreach ( array(
			'input, select, textarea',
			'#external-booking .rentals-list .rental-name',
			'#external-booking .rentals-list .owner-city-address',
			'#external-booking .rentals-list .rental-capacity, #external-booking .rentals-list .rental-room-count',
			'#external-rental-detail .main-title',
			'#external-rental-detail .rental-location',
			'#external-rental-detail .rental-base-info',
			'#external-rental-detail .rental-description',
			'#external-rental-detail .rental-amenities .amenities-title',
			'#external-rental-detail .rental-amenities .amenities-box .amenities-card .amenities-card-text .amenities-subtitle',
			'#external-rental-detail .things-to-note .to-note-title',
			'#external-rental-detail .things-to-note .to-note-box .to-note-content .to-note-subtitle',
			'#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .rental-widget-title',
			'#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .rental-widget-subtitle',
			'#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .box-available .available-text',
			'#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .callout-box .callout-text .callout-content-text, #external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .callout-box .callout-text .callout-text-title',
			'#detail-checkout .checkout #guest-info-text p',
			'#detail-checkout .form-label',
			'#detail-checkout .info-right-side .right-side-content .right-side-info-part .rental-name',
			'#detail-checkout .info-right-side .right-side-content .box-available .available-text',
			'#detail-checkout .checkout .payment-info-part #payment-info-field .payment-title p',
		) as $sel ) {
			$lines[] = "{$sel} { font-family: {$font}; }";
		}

		if ( $hide_price ) {
			$lines[] = '';
			$lines[] = '/* === Masquer le prix === */';
			$lines[] = '#external-booking .rentals-list .rental .rental-price { display: none; }';
		}

		if ( $hide_address ) {
			$lines[] = '';
			$lines[] = '/* === Masquer les adresses === */';
			$lines[] = '#external-booking .rentals-list .owner-city-address { visibility: hidden; }';
			$lines[] = '#external-rental-detail .rental-location { visibility: hidden; }';
			$lines[] = '#external-rental-detail .detail-right-side .rental-booking .rental-booking-widget .rental-widget-subtitle { visibility: hidden; }';
			$lines[] = '#detail-checkout .info-right-side .right-side-content .right-side-info-part .rental-address { visibility: hidden; }';
		}

		return implode( "\n", $lines );
	}

	// ── Settings page ─────────────────────────────────────────────────────────

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings      = wp_parse_args( get_option( self::OPTION_NAME, array() ), self::get_defaults() );
		$generated_css = self::generate_superhote_css( $settings );
		$has_palette   = ! empty( self::get_theme_palette() );
		$has_fonts     = ! empty( self::get_theme_font_families() );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( ! $has_palette && ! $has_fonts ) : ?>
			<div class="notice notice-info is-dismissible">
				<p><?php esc_html_e( 'Aucune couleur ni police détectée dans le theme.json du thème actif. Activez un thème FSE (ex : Twenty Twenty-Four) pour utiliser la palette du thème.', 'interactive-map-listings' ); ?></p>
			</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'iml_settings_group' );
				do_settings_sections( 'iml-settings' );
				submit_button();
				?>
			</form>

			<hr style="margin:32px 0;" />
			<h2><?php esc_html_e( 'CSS généré pour Superhote', 'interactive-map-listings' ); ?></h2>
			<p><?php echo wp_kses(
				__( 'Copiez ce CSS dans <strong>Superhote → Paramètres → Personnalisation CSS</strong>. Il est régénéré automatiquement à chaque enregistrement.', 'interactive-map-listings' ),
				array( 'strong' => array() )
			); ?></p>
			<div style="position:relative;max-width:900px;">
				<textarea id="iml-generated-css" readonly rows="20"
					style="width:100%;font-family:monospace;font-size:12px;background:#f6f7f7;border:1px solid #c3c4c7;border-radius:4px;padding:12px;resize:vertical;line-height:1.5;"
				><?php echo esc_textarea( $generated_css ); ?></textarea>
				<button type="button" id="iml-copy-css-btn" class="button button-secondary"
					style="position:absolute;top:8px;right:8px;">
					<?php esc_html_e( '📋 Copier le CSS', 'interactive-map-listings' ); ?>
				</button>
			</div>
			<p id="iml-copy-notice" style="display:none;color:#00a32a;font-weight:600;margin-top:6px;">
				✓ <?php esc_html_e( 'CSS copié dans le presse-papiers !', 'interactive-map-listings' ); ?>
			</p>
		</div>

		<style>
		/* ── Color swatches ── */
		.iml-color-field { display: flex; flex-direction: column; gap: 6px; }
		.iml-swatches { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; }
		.iml-swatch {
			width: 30px; height: 30px; border-radius: 50%;
			border: 2px solid transparent; box-shadow: inset 0 0 0 1px rgba(0,0,0,.15);
			cursor: pointer; padding: 0; transition: transform .12s;
		}
		.iml-swatch:hover  { transform: scale(1.18); z-index: 1; }
		.iml-swatch.is-active {
			border-color: #1d2327;
			box-shadow: inset 0 0 0 1px rgba(0,0,0,.15), 0 0 0 3px #fff, 0 0 0 5px #1d2327;
		}
		.iml-swatch-custom {
			background: conic-gradient(red, yellow, lime, aqua, blue, magenta, red);
			cursor: pointer; overflow: hidden; position: relative;
		}
		.iml-swatch-custom input[type="color"] {
			opacity: 0; position: absolute; inset: 0; width: 100%; height: 100%; cursor: pointer;
		}
		.iml-color-preview { display: flex; align-items: center; gap: 6px; }
		.iml-color-dot {
			display: inline-block; width: 14px; height: 14px; border-radius: 50%;
			box-shadow: inset 0 0 0 1px rgba(0,0,0,.2);
		}
		.iml-color-hex { font-family: monospace; font-size: 12px; color: #555; }
		.iml-color-clear { color: #b32d2e; font-size: 12px; text-decoration: underline; }
		/* ── Font select ── */
		.iml-font-select { min-width: 280px; }
		</style>

		<script>
		( function () {
			/* ── Color swatches ── */
			document.querySelectorAll( '.iml-color-field' ).forEach( function ( field ) {
				var hidden  = field.querySelector( '.iml-color-hidden' );
				var custom  = field.querySelector( '.iml-color-custom' );
				var dot     = field.querySelector( '.iml-color-dot' );
				var hex     = field.querySelector( '.iml-color-hex' );
				var clearBtn = field.querySelector( '.iml-color-clear' );
				var optional = field.dataset.optional === '1';

				function setValue( color ) {
					if ( hidden )  hidden.value  = color;
					if ( custom )  custom.value  = color || '#3498db';
					if ( dot )     dot.style.background = color || 'transparent';
					if ( hex )     hex.textContent = color || '—';
					field.querySelectorAll( '.iml-swatch[data-color]' ).forEach( function ( s ) {
						s.classList.toggle( 'is-active', !! color && s.dataset.color.toLowerCase() === color.toLowerCase() );
					} );
				}

				field.querySelectorAll( '.iml-swatch[data-color]' ).forEach( function ( swatch ) {
					swatch.addEventListener( 'click', function () {
						setValue( this.dataset.color );
					} );
				} );

				if ( custom ) {
					custom.addEventListener( 'input', function () { setValue( this.value ); } );
				}

				if ( clearBtn ) {
					clearBtn.addEventListener( 'click', function () { setValue( '' ); } );
				}
			} );

			/* ── CSS copy button ── */
			var copyBtn = document.getElementById( 'iml-copy-css-btn' );
			var notice  = document.getElementById( 'iml-copy-notice' );
			var area    = document.getElementById( 'iml-generated-css' );
			if ( copyBtn && area ) {
				copyBtn.addEventListener( 'click', function () {
					if ( navigator.clipboard && navigator.clipboard.writeText ) {
						navigator.clipboard.writeText( area.value ).then( showNotice );
					} else {
						area.select();
						document.execCommand( 'copy' );
						showNotice();
					}
				} );
			}
			function showNotice() {
				if ( notice ) {
					notice.style.display = 'block';
					setTimeout( function () { notice.style.display = 'none'; }, 3000 );
				}
			}
		} )();
		</script>
		<?php
	}
}
