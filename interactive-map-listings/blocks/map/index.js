( function () {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var TextControl = wp.components.TextControl;
	var ColorPicker = wp.components.ColorPicker;
	var Button = wp.components.Button;
	var Placeholder = wp.components.Placeholder;

	registerBlockType( 'interactive-map-listings/map', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var colorControls = [
				{
					label: __( 'Couleur des marqueurs', 'interactive-map-listings' ),
					attr: 'markerColor',
					value: attributes.markerColor,
				},
				{
					label: __( 'Fond de la card', 'interactive-map-listings' ),
					attr: 'cardBgColor',
					value: attributes.cardBgColor,
				},
				{
					label: __( 'Texte de la card', 'interactive-map-listings' ),
					attr: 'cardTextColor',
					value: attributes.cardTextColor,
				},
				{
					label: __( 'Couleur du bouton', 'interactive-map-listings' ),
					attr: 'buttonColor',
					value: attributes.buttonColor,
				},
				{
					label: __( 'Texte du bouton', 'interactive-map-listings' ),
					attr: 'buttonTextColor',
					value: attributes.buttonTextColor,
				},
			];

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
								'Paramètres de la carte',
								'interactive-map-listings'
							),
							initialOpen: true,
						},
						el( RangeControl, {
							label: __(
								'Niveau de zoom',
								'interactive-map-listings'
							),
							value: attributes.zoom,
							onChange: function ( val ) {
								setAttributes( { zoom: val } );
							},
							min: 0,
							max: 18,
							help: __(
								'0 = utiliser la valeur par défaut du plugin.',
								'interactive-map-listings'
							),
						} ),
						el( TextControl, {
							label: __(
								'Latitude du centre',
								'interactive-map-listings'
							),
							value: attributes.centerLat || '',
							onChange: function ( val ) {
								setAttributes( {
									centerLat: parseFloat( val ) || 0,
								} );
							},
							type: 'number',
							help: __(
								'0 = utiliser la valeur par défaut du plugin.',
								'interactive-map-listings'
							),
						} ),
						el( TextControl, {
							label: __(
								'Longitude du centre',
								'interactive-map-listings'
							),
							value: attributes.centerLng || '',
							onChange: function ( val ) {
								setAttributes( {
									centerLng: parseFloat( val ) || 0,
								} );
							},
							type: 'number',
						} ),
						el( RangeControl, {
							label: __(
								'Hauteur de la carte (px)',
								'interactive-map-listings'
							),
							value: attributes.mapHeight,
							onChange: function ( val ) {
								setAttributes( { mapHeight: val } );
							},
							min: 200,
							max: 1000,
							step: 10,
						} )
					),
					el(
						PanelBody,
						{
							title: __( 'Couleurs', 'interactive-map-listings' ),
							initialOpen: false,
						},
						el(
							'p',
							{
								className: 'components-base-control__help',
								style: { marginTop: 0 },
							},
							__(
								'Laissez vide pour utiliser les couleurs par défaut définies dans Réglages > Map Listings.',
								'interactive-map-listings'
							)
						),
						colorControls.map( function ( control ) {
							return el(
								'div',
								{
									key: control.attr,
									style: { marginBottom: '16px' },
								},
								el(
									'p',
									{
										style: {
											fontWeight: 600,
											marginBottom: '8px',
										},
									},
									control.label
								),
								el( ColorPicker, {
									color: control.value || undefined,
									onChangeComplete: function ( color ) {
										var update = {};
										update[ control.attr ] = color.hex;
										setAttributes( update );
									},
									disableAlpha: true,
								} ),
								control.value
									? el(
											Button,
											{
												isSmall: true,
												variant: 'secondary',
												onClick: function () {
													var update = {};
													update[ control.attr ] = '';
													setAttributes( update );
												},
												style: { marginTop: '4px' },
											},
											__(
												'Réinitialiser',
												'interactive-map-listings'
											)
									  )
									: null
							);
						} )
					)
				),
				el(
					'div',
					blockProps,
					el(
						Placeholder,
						{
							icon: 'location-alt',
							label: __(
								'Interactive Map',
								'interactive-map-listings'
							),
							instructions: __(
								"La carte interactive s'affichera ici sur le frontend. Configurez les paramètres dans le panneau latéral.",
								'interactive-map-listings'
							),
						},
						el(
							'div',
							{
								style: {
									background: '#f0f6f0',
									border: '2px dashed #4CAF50',
									borderRadius: '8px',
									height:
										Math.min(
											attributes.mapHeight || 500,
											300
										) + 'px',
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
									color: '#666',
									fontSize: '14px',
									width: '100%',
								},
							},
							el(
								'span',
								{},
								__(
									'Aperçu de la carte (frontend uniquement)',
									'interactive-map-listings'
								) +
									' — ' +
									( attributes.mapHeight || 500 ) +
									'px'
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
