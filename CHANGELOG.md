# Changelog

## 0.3.1 – unreleased
- Sections panel lists the blocks of the edited page (inside Post Content) when editing a page in the Site Editor.

## 0.3.0 – unreleased
- Section blocks: **Hero**, **Episodes section**, **News section**, **Call to action section** (`podcast-core/*`). Containers with colour, spacing, margin, border and typography support, so each kind of section can be styled for the whole site in Design → Styles → Blocks.
- **Sections** panel in the Site Editor (and block editor): a switch per section of the template's main area. Switching off uses native block visibility (`metadata.blockVisibility = false`): the section stays in the template but is not rendered.

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
