# Changelog

## 0.8.0 – unreleased
- Episodes list: **series filter** (including *No series*), **bulk actions** to add episodes to a series, move them to a series (removing the others) or remove them from all series, and sortable **No.** and **Season** columns.

## 0.7.0 – unreleased
- **Episode order** (Settings → Podcast): newest first or oldest first, for the episode archive and series pages. Each series can override it (Podcast → Series → edit), e.g. a narrative series from the first episode. Term meta `podcast_core_order`.

## 0.6.0 – unreleased
- **Spotify Web API (optional)**: with a Client ID and Client Secret (Settings → Podcast → Spotify API) and the show link in the *Spotify* field, the plugin finds the Spotify link of every episode for shows hosted anywhere, matching by title (case, accents and punctuation ignored) or by the most similar title published within two days. Used to fetch the Spotify episode image.
- Lookup order for the Spotify link: episode field → Spotify for Creators page → Web API.
- Spotify API status on the Import page. The Client Secret is never shown again after saving.

## 0.5.0 – unreleased
- **Episodes per page** setting for the episode archive and series pages.
- **Episode images from Spotify**: Settings → Podcast → *Episode image* = *From Spotify (horizontal 16:9)* uses the cover Spotify shows for each episode (640×360, official oEmbed). The open.spotify.com link of each episode is read from the public Spotify for Creators page and stored (`podcast_core_spotify_url`, editable in Episode details); if it cannot be found, the feed image is used.
- *Update images from Spotify* button (Podcast → Import from RSS) and `wp podcast-core images [--force]`: replace feed images of existing episodes; images chosen by hand are kept; unused feed images are deleted.

## 0.4.0 – unreleased
- **Import from RSS**: Settings → Podcast → *Feed to import* and *Automatic update* (hourly check); Podcast → *Import from RSS* page with preview, *Force update now* and progress; `wp podcast-core import [--dry-run] [--url=]`.
- Each feed item becomes a published episode with its original date, title, description converted to blocks (paragraphs, lists, headings), audio URL, duration, episode and season number, link to the episode page on the platform and cover image (downloaded once into the Media Library and reused).
- Episodes already on the site (same guid, or same audio URL for episodes added by hand) are never overwritten.
- New field: *Episode page on the podcast platform* (`podcast_core_source_url`).
- Action `podcast_core_episode_imported`.

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
