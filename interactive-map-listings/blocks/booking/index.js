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
	var Placeholder = wp.components.Placeholder;
	var Notice = wp.components.Notice;

	registerBlockType( 'interactive-map-listings/booking', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			// Detect context (FSE single logement template).
			var context = props.context || {};
			var isInLogementTemplate = context.postType === 'logement';

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{
							title: __( 'Configuration Superhote', 'interactive-map-listings' ),
							initialOpen: true,
						},
						el( TextControl, {
							label: __(
								'Propertykey (override)',
								'interactive-map-listings'
							),
							value: attributes.propertyKeyOverride,
							onChange: function ( val ) {
								setAttributes( { propertyKeyOverride: val } );
							},
							help: isInLogementTemplate
								? __(
										'Laissez vide pour utiliser automatiquement la clé du logement courant.',
										'interactive-map-listings'
								  )
								: __(
										'Saisissez la propertykey Superhote du logement à afficher.',
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
							max: 8000,
							step: 100,
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
								'Moteur de réservation — Logement',
								'interactive-map-listings'
							),
						},
						isInLogementTemplate && ! attributes.propertyKeyOverride
							? el(
									Notice,
									{
										status: 'info',
										isDismissible: false,
									},
									__(
										'La propertykey Superhote sera lue automatiquement sur chaque logement. Renseignez-la dans la meta box "Détails du logement".',
										'interactive-map-listings'
									)
							  )
							: null,
						attributes.propertyKeyOverride
							? el(
									Notice,
									{
										status: 'success',
										isDismissible: false,
									},
									__( 'Propertykey configurée : ', 'interactive-map-listings' ) +
										attributes.propertyKeyOverride
							  )
							: null,
						el(
							'div',
							{
								style: {
									background: '#f0f6ff',
									border: '2px dashed #3498DB',
									borderRadius: '8px',
									height: Math.min( attributes.height, 300 ) + 'px',
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
									color: '#666',
									fontSize: '14px',
									width: '100%',
									marginTop: '8px',
								},
							},
							el(
								'span',
								{},
								'🏠 ' +
									__( 'Iframe Superhote (logement)', 'interactive-map-listings' ) +
									' — ' +
									attributes.height +
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
