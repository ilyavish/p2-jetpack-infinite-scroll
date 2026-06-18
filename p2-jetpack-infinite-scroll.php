<?php
/**
 * Plugin Name: P2 Jetpack Infinite Scroll Compatibility
 * Plugin URI: https://github.com/ilyavish/p2-jetpack-infinite-scroll
 * Description: Adds Jetpack Infinite Scroll support to the classic P2 theme without editing theme files.
 * Version: 1.0.2
 * Author: Sudo
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: p2-jetpack-infinite-scroll
 * Requires at least: 6.0
 * Requires PHP: 8.1
 *
 * @package P2_Jetpack_Infinite_Scroll
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'P2_JETPACK_INFINITE_SCROLL_VERSION', '1.0.2' );
define( 'P2_JETPACK_INFINITE_SCROLL_FILE', __FILE__ );

/**
 * P2/Jetpack Infinite Scroll compatibility layer.
 */
final class P2_Jetpack_Infinite_Scroll_Compatibility {
	/**
	 * Jetpack expects the DOM id without the leading hash.
	 *
	 * P2's main index template renders posts as direct children of <ul id="postlist">.
	 * Using that real list prevents Jetpack from appending an extra wrapper inside
	 * #main, which would break P2's list markup and keyboard/comment assumptions.
	 */
	private const P2_POST_CONTAINER = 'postlist';

	/**
	 * Register hooks.
	 */
	public static function bootstrap(): void {
		add_action( 'after_setup_theme', array( __CLASS__, 'add_infinite_scroll_support' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		register_activation_hook( P2_JETPACK_INFINITE_SCROLL_FILE, array( __CLASS__, 'activate' ) );
	}

	/**
	 * Prefer click-to-load for new installs while still allowing Jetpack's setting to switch to scroll.
	 */
	public static function activate(): void {
		self::maybe_set_default_behavior();
	}

	/**
	 * Add Jetpack Infinite Scroll support when all required moving parts exist.
	 */
	public static function add_infinite_scroll_support(): void {
		if ( ! self::should_enable() ) {
			return;
		}

		self::maybe_set_default_behavior();

		add_theme_support(
			'infinite-scroll',
			array(
				'type'           => 'scroll',
				'container'      => self::P2_POST_CONTAINER,
				'render'         => array( __CLASS__, 'render_posts' ),
				'wrapper'        => false,
				'footer'         => false,
				'footer_widgets' => false,
			)
		);
	}

	/**
	 * Enqueue small compatibility assets only on supported front-end P2 screens.
	 */
	public static function enqueue_assets(): void {
		if ( ! self::should_enable() || is_admin() || is_feed() ) {
			return;
		}

		wp_enqueue_script(
			'p2-jetpack-infinite-scroll',
			plugins_url( 'assets/p2-jetpack-infinite-scroll.js', P2_JETPACK_INFINITE_SCROLL_FILE ),
			array( 'jquery' ),
			P2_JETPACK_INFINITE_SCROLL_VERSION,
			true
		);

		$css = '
			body.infinite-scroll #main > .navigation,
			body.infinite-scroll.neverending #main > .navigation {
				display: none;
			}

			#main ul#postlist > .infinite-wrap {
				list-style: none;
				margin: 0;
				padding: 0;
			}

			#main ul#postlist > .infinite-wrap > li {
				list-style: none;
			}

			@media screen and (max-width: 480px) {
				#main ul#postlist > .infinite-wrap > li {
					font-size: 14px !important;
					margin: 0;
					padding: 10px;
					position: relative;
				}

				#main ul#postlist > .infinite-wrap > li > h4 {
					margin: -10px -10px -8px;
					padding: 7px 10px 7px 40px;
				}

				#main ul#postlist > .infinite-wrap > li div.postcontent *,
				#main ul#postlist > .infinite-wrap > li div.commentcontent * {
					font-size: 13px !important;
					line-height: 150% !important;
				}
			}
		';
		wp_register_style( 'p2-jetpack-infinite-scroll', false, array(), P2_JETPACK_INFINITE_SCROLL_VERSION );
		wp_enqueue_style( 'p2-jetpack-infinite-scroll' );
		wp_add_inline_style( 'p2-jetpack-infinite-scroll', $css );
	}

	/**
	 * Render appended posts with P2's normal entry renderer.
	 *
	 * P2's index loop calls p2_load_entry() for each post. Calling the same
	 * function here keeps post, comment, reply, edit, and microformat markup
	 * aligned with the active P2 copy instead of duplicating entry.php.
	 */
	public static function render_posts(): void {
		if ( ! function_exists( 'p2_load_entry' ) ) {
			return;
		}

		while ( have_posts() ) {
			the_post();
			p2_load_entry();
		}
	}

	/**
	 * Determine whether the compatibility layer can safely run.
	 */
	private static function should_enable(): bool {
		if ( ! self::is_p2_theme_active() ) {
			return false;
		}

		if ( ! self::is_jetpack_available() ) {
			return false;
		}

		if ( ! function_exists( 'p2_load_entry' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Detect P2 as either the active theme or the parent template.
	 */
	private static function is_p2_theme_active(): bool {
		$theme = wp_get_theme();

		$candidates = array_filter(
			array(
				$theme->get_stylesheet(),
				$theme->get_template(),
				$theme->get( 'Name' ),
				$theme->get( 'TextDomain' ),
			)
		);

		foreach ( $candidates as $candidate ) {
			if ( 'p2' === strtolower( trim( (string) $candidate ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check for Jetpack without assuming any one Jetpack version or namespace.
	 */
	private static function is_jetpack_available(): bool {
		return defined( 'JETPACK__VERSION' )
			|| class_exists( 'Jetpack', false )
			|| function_exists( 'jetpack_is_module_active' )
			|| class_exists( 'Automattic\\Jetpack\\Status', false );
	}

	/**
	 * Jetpack stores its Reading > Infinite Scroll Behavior checkbox in the
	 * `infinite_scroll` option. An empty string means click-to-load; any
	 * non-empty value means load-on-scroll. Adding the option only when it is
	 * missing keeps click mode as this plugin's default while still letting
	 * Jetpack's own checkbox choose either behavior afterward.
	 */
	private static function maybe_set_default_behavior(): void {
		if ( null === get_option( 'infinite_scroll', null ) ) {
			add_option( 'infinite_scroll', '' );
		}
	}
}

P2_Jetpack_Infinite_Scroll_Compatibility::bootstrap();
