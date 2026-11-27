# Changelog

## 0.10.0 – unreleased
- **Cookie notice** (Podcast → Settings → General → Privacy, off by default): banner on the first visit with Accept and Reject; YouTube and Vimeo embeds (which set third-party cookies) wait behind a placeholder until the visitor accepts or clicks *Load the video*; YouTube loads from youtube-nocookie.com. Choice stored in the browser; links to `#pc-cookie-settings` reopen the banner.
- **Episode Video block** (`podcast-core/episode-video`): the episode's video on its own, to place it anywhere in the episode template (e.g. after the text) with the Episode Player's *Show video* turned off. The player then shows a **Watch the video** link that scrolls to it (option *"Watch the video" button*), styled as a pill, a card with the episode image, or a bar attached under the player (*Button style*), under the player or in the episode text after a chosen block, centred (*Button position*, *After text block no.*).
- **Settings moved to Podcast → Settings** (was Settings → Podcast; the old address redirects), in three tabs: *Podcast* (show name, platform links, email sign-up), *General* (URL bases, episode order and number per page, RSS import, Spotify API) and *Styles*. Same option as before; each tab saves only its own values.
- **Hero images** (Podcast → Settings → Styles): one or more images from the Media Library, in a chosen order, for the theme's hero (`podcast_core_hero_images()`). The site icon (favicon) stays in Appearance → Editor → Design → Identity.
- **Partners** (Podcast → Partners): partners, sponsors, supporters or friends of the podcast, each with logo, name, short text, website and order. Optional **Partner types** to group them. Private records, no public pages. Post type `podcast_partner`, taxonomy `podcast_partner_type`, meta `podcast_core_partner_url`.
- **Partners block** (`podcast-core/partners`): grid of logos, names and short texts linked to the partners' websites; filter by type, columns (1–6), maximum number, show/hide name and text. Styles via `.pc-partner*` classes (the wrapper also has `pc-partners--columns-N`) and the `--pc-partners-*` custom properties.
- **Series icons**: each series and sub-series can have an icon (Podcast → Series → Icon, from the Media Library). Term meta `podcast_core_icon` (attachment ID), also in the REST API and in the series list table.
- **Series list block** (`podcast-core/series-list`): the main series, or the sub-series of one series, each with icon, name, optional description and a badge with the number of episodes, linked to the series page; optionally the images of the latest episodes of each series (0–12), stacked from the left (newest on top, 30% overlap, filling the card), with a cut card with three dots when the series has more episodes. The whole card (name, badge, images) links to the series page. Order by name or by most recent episode. Columns (1–6), hide empty series. Styles via `.pc-series*` classes and `--pc-series-*` custom properties.
- **Partners section** block (`podcast-core/partners-section`): container with an editable title, like the other section blocks.

## 0.9.0 – unreleased
- Section blocks (Hero, Episodes, News, Call to action): **background image** with size and position (block sidebar → Styles → Background).
- **Renamed to Podcast Core Engine** (was Podcast Core Plugin): plugin folder and main file `podcast-core-engine/podcast-core-engine.php`, text domain `podcast-core-engine`. Settings, episodes and data are unchanged (same option names and post type). The companion theme is now **Podcast Core Studio**.

## 0.8.1 – unreleased
- "Email sign-up" address in Settings → Podcast (new episodes or newsletter by email), used by the theme's sign-up buttons.

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
