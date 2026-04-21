=== Interactive Map Listings ===
Contributors: gael
Tags: map, leaflet, logement, listing, interactive
Requires at least: 6.1
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display an interactive Leaflet map with accommodation listings. Fully compatible with the Full Site Editor.

== Description ==

Interactive Map Listings adds a "Logement" custom post type and a Gutenberg block that displays all logements on an interactive Leaflet/OpenStreetMap map.

**Features:**

* Custom Post Type "Logement" with latitude, longitude, capacity, description, tags, and action URL
* Interactive Leaflet map with colored SVG markers
* Hover cards showing photo, title, description, capacity, tags, and action button
* Configurable default colors via Settings > Map Listings
* Per-block color overrides in the block inspector
* Full Site Editor (FSE) compatible — works in templates and template parts
* Block pattern included for full-width map layout
* No API key required (uses OpenStreetMap tiles)
* Zero external dependencies (no Node.js build step required)

== Installation ==

1. Upload the `interactive-map-listings` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Settings > Map Listings to configure default colors and map center
4. Create Logement posts with coordinates, descriptions, and images
5. Insert the "Interactive Map" block in any page, post, or template

== Frequently Asked Questions ==

= Do I need an API key? =

No. The plugin uses OpenStreetMap tiles which are free and require no API key.

= Can I customize the marker and card colors? =

Yes. Set default colors in Settings > Map Listings. Override them per-block in the block sidebar under "Colors".

= Does it work with the Full Site Editor? =

Yes. The block can be inserted in any template or template part via the Site Editor.

== Changelog ==

= 1.0.0 =
* Initial release
