<?php
/**
 * Cookie notice and consent for external videos (Podcast → Settings → General → Privacy).
 *
 * The site itself only uses WordPress technical cookies; YouTube and Vimeo embeds set third-party
 * cookies as soon as they load. With the option on, their iframes are kept in a <template> behind
 * a placeholder and loaded only after the visitor accepts (banner) or clicks "Load the video".
 * YouTube is loaded from youtube-nocookie.com. The choice is stored in the browser (localStorage).
 *
 * @package PodcastCore
 */

namespace PodcastCore;

defined( 'ABSPATH' ) || exit;

final class Consent {

	private const HOSTS = '~^https?://(?:www\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be|player\.vimeo\.com|vimeo\.com)/~i';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'setup' ) );
	}

	public static function enabled(): bool {
		return (bool) Settings::get( 'consent' );
	}

	public static function setup(): void {
		if ( ! self::enabled() || is_admin() ) {
			return;
		}
		add_filter( 'embed_oembed_html', array( __CLASS__, 'wrap' ), 99, 2 );
		add_filter( 'oembed_result', array( __CLASS__, 'wrap' ), 99, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'banner' ) );
	}

	/**
	 * Wraps a YouTube or Vimeo iframe: placeholder + the iframe in a <template>.
	 *
	 * @param string|false $html Embed HTML.
	 * @param string       $url  Embedded URL.
	 * @return string|false
	 */
	public static function wrap( $html, $url ) {
		if ( ! is_string( $html ) || false === stripos( $html, '<iframe' ) || false !== strpos( $html, 'pc-consent-embed' ) ) {
			return $html;
		}
		if ( ! preg_match( self::HOSTS, (string) $url ) && ! preg_match( '~src="https?://(?:www\.)?(?:youtube|player\.vimeo)~i', $html ) ) {
			return $html;
		}
		if ( wp_is_serving_rest_request() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $html; // editor previews.
		}
		$html     = preg_replace( '~https?://(?:www\.)?youtube\.com/embed/~i', 'https://www.youtube-nocookie.com/embed/', $html );
		$provider = false !== stripos( $html, 'vimeo' ) ? 'Vimeo' : 'YouTube';
		return sprintf(
			'<div class="pc-consent-embed"><template>%1$s</template><div class="pc-consent-embed__placeholder"><p>%2$s</p><button type="button" class="pc-consent-embed__load">%3$s</button></div></div>',
			$html,
			/* translators: %s: YouTube or Vimeo */
			esc_html( sprintf( __( 'This video is hosted by %s, which uses third-party cookies.', 'podcast-core-engine' ), $provider ) ),
			esc_html__( 'Load the video', 'podcast-core-engine' )
		);
	}

	public static function assets(): void {
		wp_enqueue_style( 'podcast-core-consent', PODCAST_CORE_URL . 'assets/css/consent.css', array(), PODCAST_CORE_VERSION );
		wp_enqueue_script( 'podcast-core-consent', PODCAST_CORE_URL . 'assets/js/consent.js', array(), PODCAST_CORE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}

	public static function banner(): void {
		$privacy = get_privacy_policy_url();
		?>
		<div class="pc-consent" role="dialog" aria-live="polite" aria-label="<?php esc_attr_e( 'Cookie notice', 'podcast-core-engine' ); ?>" hidden>
			<p class="pc-consent__text">
				<?php esc_html_e( 'This site only uses technical cookies. Videos from YouTube use third-party cookies: we load them only if you accept.', 'podcast-core-engine' ); ?>
				<?php if ( $privacy ) : ?>
					<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy Policy', 'podcast-core-engine' ); ?></a>
				<?php endif; ?>
			</p>
			<p class="pc-consent__actions">
				<button type="button" class="pc-consent__button" data-pc-consent="no"><?php esc_html_e( 'Reject', 'podcast-core-engine' ); ?></button>
				<button type="button" class="pc-consent__button" data-pc-consent="yes"><?php esc_html_e( 'Accept', 'podcast-core-engine' ); ?></button>
			</p>
		</div>
		<?php
	}
}
