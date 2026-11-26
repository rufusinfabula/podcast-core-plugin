# Podcast Core Plugin

Episodes, series and an audio player for podcast websites built with WordPress.
Lightweight, no third-party dependencies. Pairs with [Podcast Core Theme](https://github.com/rufusinfabula/podcast-core-theme).

> Status: early development (0.2.0). Not ready for production.

## Features

- **Episode** post type (`podcast_episode`) and hierarchical **Series** taxonomy (`podcast_series`)
- Native episode fields (no ACF): number, season, audio URL, duration, video URL — edited in the block editor sidebar ("Episode details")
- **Episode Player** block (`podcast-core/episode-player`): cover, episode number, custom audio controls, optional video embed. Accessible (native range input, keyboard friendly), works with several players per page, falls back to native audio controls without JavaScript
- schema.org `PodcastEpisode` structured data on episode pages
- **Settings → Podcast**: archive and series URL bases, episodes per page, episode order (newest or oldest first — each series can override it, handy for narrative shows), show name, platform links (Spotify, Apple Podcasts, YouTube, RSS)
- **Episodes list tools**: filter by series (or *No series*), bulk add / move / remove series, sortable number and season columns
- **Section blocks** (Hero, Episodes section, News section, Call to action section): style every instance at once in *Design → Styles → Blocks*
- **Sections panel** in the Site Editor: switch sections of a template on/off without deleting them
- **Import from RSS**: one episode per feed item (text, audio, cover, number, season, date); hourly automatic update plus *Force update now*; local edits are never overwritten
- WP-CLI migration from other setups (e.g. CPT UI + ACF)
- Translation ready; Italian included

## Requirements

- WordPress 6.7+
- PHP 8.1+

## For theme developers

```php
podcast_core_get_field( 'episode_number' );   // episode_number, season_number, audio_url, duration, video_url
podcast_core_get_setting( 'spotify_url' );     // episodes_slug, series_slug, show_name, spotify_url, apple_url, youtube_url, rss_url
```

Templates: `single-podcast_episode.html`, `archive-podcast_episode.html`, `taxonomy-podcast_series.html`.

Player styling: override the CSS custom properties on `.pc-player` (`--pc-accent`, `--pc-track`, `--pc-radius`, `--pc-gap`, `--pc-cover-size`, `--pc-button-size`) or target the `.pc-player__*` classes.

Filter: `podcast_core_schema_episode` ( array $data, WP_Post $post ).

## Importing from your podcast feed

1. *Settings → Podcast → Import from RSS*: paste the RSS address of your show (Spotify for Creators, Anchor, Spreaker, Buzzsprout…) and choose whether to update automatically every hour.
2. *Podcast → Import from RSS*: **Check the feed**, then **Force update now**. Episodes are imported in small batches with a progress bar.
3. Edit any imported episode as usual: later updates only add new episodes.
4. Hosting on Spotify for Creators? Set *Episode image* to *From Spotify* to use the horizontal covers shown in the Spotify app (640×360) instead of the feed artwork, and run *Update images from Spotify* for existing episodes. The Spotify link of each episode is found, in order: in the episode's *Spotify link* field; on the public Spotify for Creators page (shows hosted there); with the **Spotify Web API** if you add a Client ID and Secret in *Settings → Podcast → Spotify API* (shows hosted anywhere — create a free app at developer.spotify.com/dashboard). Without a link the feed image is kept.

```bash
wp podcast-core import --dry-run
wp podcast-core import
```

## Migrating existing episodes

```bash
wp podcast-core migrate --post-type=<old_type> [--taxonomy=<old_tax>] \
  [--meta=old_key:episode_number,old_key2:audio_url,...] [--dry-run]
```

Changes the post type, copies the series terms and the mapped meta. Old meta is kept. Keep the plugin that registers the old taxonomy active while migrating.

## Development

- No build step: JavaScript uses the `wp.*` globals directly.
- `readme.txt` is for the WordPress.org directory; this README is for GitHub.
- Regenerate translations after changing strings:

```bash
wp i18n make-pot . languages/podcast-core-plugin.pot --domain=podcast-core-plugin --exclude=languages
# update languages/podcast-core-plugin-it_IT.po, then:
wp i18n make-mo languages/podcast-core-plugin-it_IT.po languages/
wp i18n make-json languages/podcast-core-plugin-it_IT.po languages/ --no-purge
```

## Credits

Developed by **[Fabio Ranfi Solutions](https://github.com/rufusinfabula)** (Fabio Ranfi, @rufusinfabula).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
