# Changelog

## 0.2.1 – unreleased
- Fix: theme.json block styles for the Episode Player were output for a wrong class (`.wp-block-podcast-episode-player`) because WordPress strips `core/` from block names; the block now declares its root selector.
- The video embed is rendered after the player instead of inside it, so themes can make the player sticky without the video.
- Player icon colour can be set with the `--pc-icon` custom property.

## 0.2.0 – unreleased
- Episode post type (`podcast_episode`) and Series taxonomy (`podcast_series`) with configurable URL bases.
- Native episode fields (number, season, audio URL, duration, video URL) with an "Episode details" panel in the block editor.
- Episode Player block: custom accessible controls, cover, episode number, optional video; native fallback without JS.
- schema.org `PodcastEpisode` JSON-LD on episode pages.
- Settings → Podcast: URL bases, show name, platform links.
- `wp podcast-core migrate` to convert episodes from another post type/taxonomy/meta.
- Italian translation.

## 0.1.0 – unreleased
- Initial plugin skeleton: bootstrap, text domain, GPL license, readme.
