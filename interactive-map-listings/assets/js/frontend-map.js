( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.iml-map-container' ).forEach( function ( container ) {
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

		var headers = { 'X-WP-Nonce': config.nonce };

		// Fetch logements and POIs in parallel.
		var logementsPromise = fetch( config.restUrl, { headers: headers } )
			.then( function ( r ) { return r.json(); } );

		var poisPromise = config.poisUrl
			? fetch( config.poisUrl, { headers: headers } ).then( function ( r ) { return r.json(); } )
			: Promise.resolve( { pois: [], categories: [] } );

		Promise.all( [ logementsPromise, poisPromise ] )
			.then( function ( results ) {
				var logementData = results[ 0 ];
				var poiData      = results[ 1 ];

				var logements   = logementData.logements || [];
				var buttonLabel = ( logementData.settings && logementData.settings.button_label ) || 'Voir le logement';
				var pois        = poiData.pois || [];
				var categories  = poiData.categories || [];

				var bounds = [];

				// ── Logement markers (hover popup) ──────────────────────────
				logements.forEach( function ( logement ) {
					var marker = createLogementMarker( map, logement, config );
					bindHoverCard( container, marker, logement, buttonLabel );
					bounds.push( [ logement.latitude, logement.longitude ] );
				} );

				// ── POI markers (click popup) + filter bar ──────────────────
				if ( pois.length > 0 && categories.length > 0 ) {
					var layerGroups = buildPoiLayers( map, pois, categories );
					addFilterControl( map, categories, layerGroups );
				}

				// Fit map to logement markers if no explicit center was set.
				if ( bounds.length > 1 && ! config.centerLat && ! config.centerLng ) {
					map.fitBounds( bounds, { padding: [ 40, 40 ] } );
				}
			} )
			.catch( function ( err ) {
				console.error( 'IML: Failed to load map data', err );
			} );
	}

	// ── POI: layer groups ────────────────────────────────────────────────────

	/**
	 * Create one Leaflet LayerGroup per category, add POI markers to them.
	 * Returns a { [slug]: LayerGroup } map for the filter bar.
	 */
	function buildPoiLayers( map, pois, categories ) {
		var layerGroups = {};

		categories.forEach( function ( cat ) {
			layerGroups[ cat.slug ] = L.layerGroup().addTo( map );
		} );

		pois.forEach( function ( poi ) {
			var cat   = poi.category;
			var group = layerGroups[ cat.slug ];
			if ( ! group ) {
				return;
			}

			var marker = createPoiMarker( cat.color || '#7F8C8D' );
			marker.setLatLng( [ poi.latitude, poi.longitude ] );
			marker.addTo( group );
			bindPoiClickPopup( marker, poi, cat );
		} );

		return layerGroups;
	}

	/**
	 * Create a small filled circle SVG marker for a POI.
	 */
	function createPoiMarker( color ) {
		var svg =
			'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">' +
			'<circle cx="9" cy="9" r="7" fill="' + escapeAttr( color ) + '" stroke="#fff" stroke-width="2"/>' +
			'</svg>';

		var icon = L.divIcon( {
			className: 'iml-poi-marker',
			html: svg,
			iconSize: [ 18, 18 ],
			iconAnchor: [ 9, 9 ],
			popupAnchor: [ 0, -12 ],
		} );

		return L.marker( [ 0, 0 ], { icon: icon } );
	}

	/**
	 * Bind a click popup to a POI marker.
	 */
	function bindPoiClickPopup( marker, poi, cat ) {
		marker.bindPopup( buildPoiCardHTML( poi, cat ), {
			closeButton: true,
			maxWidth: 280,
			minWidth: 240,
			className: 'iml-poi-popup',
			autoPan: true,
		} );
	}

	/**
	 * Build popup HTML for a POI.
	 */
	function buildPoiCardHTML( poi, cat ) {
		var html = '<div class="iml-poi-card">';

		if ( poi.image_url ) {
			html +=
				'<div class="iml-poi-card__image">' +
				'<img src="' + escapeAttr( poi.image_url ) + '" alt="' + escapeAttr( poi.title ) + '" loading="lazy" />' +
				'</div>';
		}

		html += '<div class="iml-poi-card__body">';
		html +=
			'<span class="iml-poi-card__badge" style="background:' + escapeAttr( cat.color ) + '">' +
			escapeHTML( cat.name ) +
			'</span>';
		html += '<h3 class="iml-poi-card__title">' + escapeHTML( poi.title ) + '</h3>';

		if ( poi.short_description ) {
			html += '<p class="iml-poi-card__desc">' + escapeHTML( poi.short_description ) + '</p>';
		}

		if ( poi.address ) {
			html +=
				'<p class="iml-poi-card__address">' +
				'<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-1px;margin-right:3px;opacity:.6;">' +
				'<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/>' +
				'</svg>' +
				escapeHTML( poi.address ) +
				'</p>';
		}

		if ( poi.external_link ) {
			html +=
				'<a class="iml-poi-card__link" href="' + escapeAttr( poi.external_link ) + '" target="_blank" rel="noopener noreferrer">' +
				'Voir plus' +
				'<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-1px;margin-left:4px;">' +
				'<path d="M19 19H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>' +
				'</svg>' +
				'</a>';
		}

		html += '</div></div>';
		return html;
	}

	// ── Filter bar ───────────────────────────────────────────────────────────

	/**
	 * Add a collapsible filter control overlay to the map.
	 */
	function addFilterControl( map, categories, layerGroups ) {
		var FilterControl = L.Control.extend( {
			options: { position: 'topleft' },

			onAdd: function () {
				var div = L.DomUtil.create( 'div', 'iml-filter-control' );

				// Collapse by default on narrow maps (mobile).
				if ( map.getContainer().offsetWidth < 480 ) {
					div.classList.add( 'is-collapsed' );
				}

				L.DomEvent.disableClickPropagation( div );
				L.DomEvent.disableScrollPropagation( div );

				// Header (clickable to collapse/expand).
				var header = L.DomUtil.create( 'div', 'iml-filter-header', div );
				header.innerHTML =
					'<span>Points d\'intérêt</span>' +
					'<span class="iml-filter-arrow">▾</span>';

				header.addEventListener( 'click', function () {
					div.classList.toggle( 'is-collapsed' );
				} );

				// Category list.
				var catList = L.DomUtil.create( 'div', 'iml-filter-categories', div );

				categories.forEach( function ( cat ) {
					var label = document.createElement( 'label' );
					label.className = 'iml-filter-item';
					label.innerHTML =
						'<input type="checkbox" checked />' +
						'<span class="iml-filter-dot" style="background:' + escapeAttr( cat.color ) + '"></span>' +
						'<span class="iml-filter-name">' + escapeHTML( cat.name ) + '</span>';

					var checkbox = label.querySelector( 'input' );
					( function ( slug, lbl ) {
						checkbox.addEventListener( 'change', function () {
							var group = layerGroups[ slug ];
							if ( ! group ) {
								return;
							}
							if ( this.checked ) {
								group.addTo( map );
								lbl.classList.remove( 'is-hidden' );
							} else {
								group.remove();
								lbl.classList.add( 'is-hidden' );
							}
						} );
					} )( cat.slug, label );

					catList.appendChild( label );
				} );

				return div;
			},
		} );

		new FilterControl().addTo( map );
	}

	// ── Logement markers (unchanged) ────────────────────────────────────────

	/**
	 * Create a colored pin SVG marker for a logement.
	 */
	function createLogementMarker( map, logement, config ) {
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
	 * Bind hover card (popup) to a logement marker.
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
	 * Build the card HTML for a logement popup.
	 */
	function buildCardHTML( logement, buttonLabel ) {
		var html = '<div class="iml-card">';

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

		html +=
			'<h3 class="iml-card__title">' +
			escapeHTML( logement.title ) +
			'</h3>';

		if ( logement.short_description ) {
			html +=
				'<p class="iml-card__description">' +
				escapeHTML( logement.short_description ) +
				'</p>';
		}

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

		if ( logement.action_url ) {
			html +=
				'<a class="iml-card__button" href="' +
				escapeAttr( logement.action_url ) +
				'" target="_blank" rel="noopener noreferrer">' +
				escapeHTML( buttonLabel ) +
				'</a>';
		}

		html += '</div>';
		html += '</div>';

		return html;
	}

	// ── Utilities ────────────────────────────────────────────────────────────

	function escapeHTML( str ) {
		if ( ! str ) {
			return '';
		}
		var div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( str ) );
		return div.innerHTML;
	}

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
