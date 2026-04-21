( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var containers = document.querySelectorAll( '.iml-map-container' );

		containers.forEach( function ( container ) {
			initMap( container );
		} );
	} );

	function initMap( container ) {
		var configAttr = container.getAttribute( 'data-iml-config' );
		if ( ! configAttr ) {
			return;
		}

		var config;
		try {
			config = JSON.parse( configAttr );
		} catch ( e ) {
			return;
		}

		var mapEl = container.querySelector( '.iml-map' );
		if ( ! mapEl ) {
			return;
		}

		// Apply CSS custom properties for theming.
		container.style.setProperty( '--iml-marker-color', config.markerColor || '#E74C3C' );
		container.style.setProperty( '--iml-card-bg', config.cardBgColor || '#FFFFFF' );
		container.style.setProperty( '--iml-card-text', config.cardTextColor || '#333333' );
		container.style.setProperty( '--iml-btn-color', config.buttonColor || '#3498DB' );
		container.style.setProperty( '--iml-btn-text', config.buttonTextColor || '#FFFFFF' );

		// Initialize Leaflet map.
		var map = L.map( mapEl, {
			scrollWheelZoom: false,
		} ).setView(
			[ config.centerLat || 46.603354, config.centerLng || 1.888334 ],
			config.zoom || 6
		);

		// OpenStreetMap tiles.
		L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution:
				'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
			maxZoom: 19,
		} ).addTo( map );

		// Enable scroll zoom after first click on map.
		map.once( 'click', function () {
			map.scrollWheelZoom.enable();
		} );

		// Fetch logements from REST API.
		fetch( config.restUrl, {
			headers: {
				'X-WP-Nonce': config.nonce,
			},
		} )
			.then( function ( res ) {
				return res.json();
			} )
			.then( function ( data ) {
				var logements = data.logements || [];
				var buttonLabel =
					( data.settings && data.settings.button_label ) ||
					'Voir le logement';

				if ( logements.length === 0 ) {
					return;
				}

				var bounds = [];

				logements.forEach( function ( logement ) {
					var marker = createMarker( map, logement, config );
					bindHoverCard( container, marker, logement, buttonLabel );
					bounds.push( [ logement.latitude, logement.longitude ] );
				} );

				// Fit map to markers if no explicit center was set.
				if ( bounds.length > 1 && ! config.centerLat && ! config.centerLng ) {
					map.fitBounds( bounds, { padding: [ 40, 40 ] } );
				}
			} )
			.catch( function ( err ) {
				console.error( 'IML: Failed to load logements', err );
			} );
	}

	/**
	 * Create a colored SVG marker.
	 */
	function createMarker( map, logement, config ) {
		var color = config.markerColor || '#E74C3C';

		var svgIcon =
			'<svg xmlns="http://www.w3.org/2000/svg" width="30" height="42" viewBox="0 0 30 42">' +
			'<path d="M15 0C6.7 0 0 6.7 0 15c0 10.5 15 27 15 27s15-16.5 15-27C30 6.7 23.3 0 15 0z" fill="' +
			color +
			'" stroke="#fff" stroke-width="1.5"/>' +
			'<circle cx="15" cy="14" r="6" fill="#fff" opacity="0.9"/>' +
			'</svg>';

		var icon = L.divIcon( {
			className: 'iml-marker-icon',
			html: svgIcon,
			iconSize: [ 30, 42 ],
			iconAnchor: [ 15, 42 ],
			popupAnchor: [ 0, -44 ],
		} );

		return L.marker( [ logement.latitude, logement.longitude ], {
			icon: icon,
		} ).addTo( map );
	}

	/**
	 * Bind hover card (popup) to a marker.
	 */
	function bindHoverCard( container, marker, logement, buttonLabel ) {
		var popupContent = buildCardHTML( logement, buttonLabel );

		marker.bindPopup( popupContent, {
			closeButton: false,
			maxWidth: 300,
			minWidth: 280,
			className: 'iml-popup',
			autoPan: true,
		} );

		var closeTimeout = null;

		marker.on( 'mouseover', function () {
			if ( closeTimeout ) {
				clearTimeout( closeTimeout );
				closeTimeout = null;
			}
			this.openPopup();
		} );

		marker.on( 'mouseout', function () {
			var self = this;
			closeTimeout = setTimeout( function () {
				var popup = container.querySelector( '.iml-popup' );
				if ( popup && popup.matches( ':hover' ) ) {
					// Mouse is on the popup, wait for it to leave.
					popup.addEventListener(
						'mouseleave',
						function () {
							self.closePopup();
						},
						{ once: true }
					);
				} else {
					self.closePopup();
				}
			}, 200 );
		} );
	}

	/**
	 * Build the card HTML for a popup.
	 */
	function buildCardHTML( logement, buttonLabel ) {
		var html = '<div class="iml-card">';

		// Image.
		if ( logement.image_url ) {
			html +=
				'<div class="iml-card__image">' +
				'<img src="' +
				escapeAttr( logement.image_url ) +
				'" alt="' +
				escapeAttr( logement.title ) +
				'" loading="lazy" />' +
				'</div>';
		}

		html += '<div class="iml-card__body">';

		// Title.
		html +=
			'<h3 class="iml-card__title">' +
			escapeHTML( logement.title ) +
			'</h3>';

		// Description.
		if ( logement.short_description ) {
			html +=
				'<p class="iml-card__description">' +
				escapeHTML( logement.short_description ) +
				'</p>';
		}

		// Capacity.
		if ( logement.capacity ) {
			html +=
				'<div class="iml-card__meta">' +
				'<span class="iml-card__capacity">' +
				'<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-2px;margin-right:4px;">' +
				'<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>' +
				'</svg>' +
				logement.capacity +
				' pers.' +
				'</span>' +
				'</div>';
		}

		// Tags.
		if ( logement.tags && logement.tags.length ) {
			html += '<div class="iml-card__tags">';
			logement.tags.forEach( function ( tag ) {
				html +=
					'<span class="iml-card__tag">' +
					escapeHTML( tag ) +
					'</span>';
			} );
			html += '</div>';
		}

		// Button.
		if ( logement.action_url ) {
			html +=
				'<a class="iml-card__button" href="' +
				escapeAttr( logement.action_url ) +
				'" target="_blank" rel="noopener noreferrer">' +
				escapeHTML( buttonLabel ) +
				'</a>';
		}

		html += '</div>'; // .iml-card__body
		html += '</div>'; // .iml-card

		return html;
	}

	/**
	 * Escape HTML entities.
	 */
	function escapeHTML( str ) {
		if ( ! str ) {
			return '';
		}
		var div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( str ) );
		return div.innerHTML;
	}

	/**
	 * Escape for use in HTML attributes.
	 */
	function escapeAttr( str ) {
		if ( ! str ) {
			return '';
		}
		return str
			.replace( /&/g, '&amp;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#39;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
	}
} )();
