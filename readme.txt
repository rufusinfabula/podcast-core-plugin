=== Podcast Core Plugin ===
Contributors: rufusinfabula
Tags: podcast, episodes, audio player, series
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Episodes, series and an audio player for podcast websites.

== Description ==

Podcast Core Plugin turns WordPress into a home for your podcast:

* An "Episode" post type with number, season, audio URL, duration and video link
* A "Series" taxonomy to group episodes
* An accessible audio player block with cover and optional video
* schema.org PodcastEpisode structured data
* Settings for archive URLs and the platforms where your show is available
* A WP-CLI command to migrate episodes from other setups (e.g. CPT UI + ACF)

No third-party plugins required. Works with any theme; pairs with Podcast Core Theme.

This plugin is in early development.

== Installation ==

1. Upload the `podcast-core-plugin` folder to `/wp-content/plugins/`.
2. Activate the plugin from the Plugins screen.

== Changelog ==

= 0.2.0 =
* Episode post type and Series taxonomy.
* Native episode fields with editor sidebar panel.
* Episode Player block.
* schema.org PodcastEpisode output.
* Settings page (URL bases, show name, platform links).
* WP-CLI migrate command.
* Italian translation.

= 0.1.0 =
* Initial plugin skeleton.
