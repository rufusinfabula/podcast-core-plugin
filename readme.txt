=== Podcast Core Engine ===
Contributors: rufusinfabula
Tags: podcast, episodes, audio player, rss import, spotify
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publish your podcast on WordPress: episodes, series, a sticky audio player and automatic import from your RSS feed (Spotify, Spreaker…).

== Description ==

Podcast Core Engine turns WordPress into the home of your podcast. Paste your RSS feed: every episode becomes a page on your site, with its player, cover, number and description — and new episodes arrive by themselves.

= Episodes and series =

* An **Episode** post type with number, season, audio file, duration, video and links to Spotify and to the episode page on your hosting platform, edited in the block editor sidebar.
* **Series** to group episodes, each with its own page and its own order.
* Episode archive and series pages with **episodes per page** and **order** settings: newest first, or oldest first for narrative shows. Each series can have its own order.

= Import from your podcast feed =

* **One-click import** of every episode from any podcast RSS feed (Spotify for Creators, Spreaker, Buzzsprout, Podbean…): title, description (converted to editable blocks), original date, audio, duration, episode and season number, cover image.
* **Automatic updates every hour**, plus a *Force update now* button. Episodes already on your site are never overwritten, so your edits are safe.
* **Horizontal covers from Spotify** (optional): use the 16:9 images Spotify shows for each episode instead of the feed artwork. Automatic for shows on Spotify for Creators; for other hosts, with a free Spotify API key.

= Player and blocks =

* **Episode Player** block: cover, episode number, accessible custom controls, optional video; falls back to the browser player without JavaScript. Several players on the same page work independently.
* **Section blocks** — Hero, Episodes section, News section, Call to action section — whose spacing, colours and typography can be set for the whole site in Design → Styles → Blocks.
* A **Sections** panel in the Site Editor to switch sections of a page on and off without deleting them.

= Everything else =

* schema.org **PodcastEpisode** structured data for search engines.
* **Bulk series assignment**, series filter and number/season columns in the episodes list.
* Show links (Spotify, Apple Podcasts, YouTube, RSS) and an email sign-up address that themes can use.
* WP-CLI commands: `wp podcast-core import`, `wp podcast-core images`, `wp podcast-core migrate` (from other setups such as CPT UI + ACF).
* No third-party plugins required. Translation ready; Italian included.

Works with any theme; designed together with **Podcast Core Studio**.

== Installation ==

1. In your admin area, go to Plugins → Add New Plugin, search for "Podcast Core Engine" and install it, or upload the plugin zip.
2. Activate the plugin.
3. Go to Settings → Podcast: paste your podcast RSS feed in *Feed to import* and choose the episode image source.
4. Go to Podcast → Import from RSS and click *Force update now*.

== Frequently Asked Questions ==

= Which podcast hosts are supported? =

Any host that publishes a standard podcast RSS feed: Spotify for Creators (Anchor), Spreaker, Buzzsprout, Podbean, Libsyn, Captivate, Transistor and many others.

= Will an update overwrite the changes I made to an episode? =

No. Episodes already on your site are recognised and left untouched; updates only add new episodes.

= Is the audio stored on my site? =

No. The player plays the audio file from your hosting platform, like podcast apps do. Only cover images are copied into your Media Library.

= How do I get the horizontal Spotify covers? =

Settings → Podcast → Episode image → From Spotify. If your show is not hosted on Spotify for Creators, also add the show link and a Spotify API Client ID and Secret (free, from developer.spotify.com).

= Can I show the first episode first? =

Yes: Settings → Podcast → Episode order → Oldest first. Each series can also have its own order (Podcast → Series).

== Changelog ==

= 0.9.0 =
* Renamed to Podcast Core Engine (was Podcast Core Plugin). Settings and content are unchanged.

= 0.8.0 =
* Bulk series assignment, series filter and number/season columns in the episodes list.

= 0.7.0 =
* Episode order setting (newest or oldest first), with per-series override.

= 0.6.0 =
* Optional Spotify Web API to find episode links (and images) for shows hosted anywhere.

= 0.5.0 =
* Optional horizontal episode images from Spotify.

= 0.4.0 =
* Import episodes from an RSS feed, with hourly automatic update and manual force update.

= 0.3.1 =
* Sections panel works when editing pages.

= 0.3.0 =
* Section blocks: Hero, Episodes, News, Call to action.
* Sections panel to switch template sections on and off.

= 0.2.1 =
* Fix block styles for the Episode Player not being applied.
* Video embed rendered after the player, not inside it.

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
