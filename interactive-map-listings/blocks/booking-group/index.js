( function () {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;
	var Placeholder = wp.components.Placeholder;
	var Notice = wp.components.Notice;

	registerBlockType( 'interactive-map-listings/booking-group', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{
							title: __(
								'Configuration Superhote',
								'interactive-map-listings'
							),
							initialOpen: true,
						},
						el( TextControl, {
							label: __(
								'Webkey (override)',
								'interactive-map-listings'
							),
							value: attributes.webKeyOverride,
							onChange: function ( val ) {
								setAttributes( { webKeyOverride: val } );
							},
							help: __(
								'Laissez vide pour utiliser la webkey définie dans Réglages > Map Listings.',
								'interactive-map-listings'
							),
							placeholder: 'abc123xyz',
						} ),
						el( RangeControl, {
							label: __(
								'Hauteur de l\'iframe (px)',
								'interactive-map-listings'
							),
							value: attributes.height,
							onChange: function ( val ) {
								setAttributes( { height: val } );
							},
							min: 500,
							max: 10000,
							step: 100,
						} ),
						el( ToggleControl, {
							label: __(
								'Version avancée (forwarding URL)',
								'interactive-map-listings'
							),
							checked: attributes.advanced,
							onChange: function ( val ) {
								setAttributes( { advanced: val } );
							},
							help: attributes.advanced
								? __(
										'Les paramètres checkin, checkout, adultes, lang… sont transmis depuis l\'URL de la page.',
										'interactive-map-listings'
								  )
								: __(
										'Version simple : iframe statique sans paramètres URL.',
										'interactive-map-listings'
								  ),
						} )
					)
				),
				el(
					'div',
					blockProps,
					el(
						Placeholder,
						{
							icon: 'calendar-alt',
							label: __(
								'Moteur de réservation — Groupe',
								'interactive-map-listings'
							),
						},
						! attributes.webKeyOverride
							? el(
									Notice,
									{
										status: 'info',
										isDismissible: false,
									},
									__(
										'Webkey lue depuis Réglages > Map Listings. Vous pouvez la remplacer ici pour ce bloc uniquement.',
										'interactive-map-listings'
									)
							  )
							: el(
									Notice,
									{
										status: 'success',
										isDismissible: false,
									},
									__( 'Webkey configurée : ', 'interactive-map-listings' ) +
										attributes.webKeyOverride
							  ),
						el(
							'div',
							{
								style: {
									background: '#f0f6ff',
									border: '2px dashed #3498DB',
									borderRadius: '8px',
									height: Math.min( attributes.height, 300 ) + 'px',
									display: 'flex',
									flexDirection: 'column',
									alignItems: 'center',
									justifyContent: 'center',
									color: '#666',
									fontSize: '14px',
									gap: '8px',
									width: '100%',
									marginTop: '8px',
								},
							},
							el(
								'span',
								{},
								'🏘️ ' +
									__( 'Iframe Superhote (groupe)', 'interactive-map-listings' ) +
									' — ' +
									attributes.height +
									'px'
							),
							el(
								'span',
								{ style: { fontSize: '12px', opacity: 0.7 } },
								attributes.advanced
									? __( 'Mode avancé — forwarding URL activé', 'interactive-map-listings' )
									: __( 'Mode simple', 'interactive-map-listings' )
							)
						)
					)
				)
			);
		},

		save: function () {
			return null;
		},
	} );
} )();
