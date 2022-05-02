<?php
/**
 * Cloudflare Web Analytics
 *
 * Plugin Name:       Cloudflare Web Analytics
 * Description:       Display Cloudflare Web Analytics in the administration.
 * Version:           0.0.1
 * Author:            GD IDENTITY
 * Author URI:        https://gdidentity.sk
 * Text Domain:       cwa
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'GdIdentity_CloudflareWebAnalytics' ) ) :

	/**
	 * This is the one true GdIdentity_CloudflareWebAnalytics class
	 */
	final class GdIdentity_CloudflareWebAnalytics {

		/**
		 * Stores the instance of the GdIdentity_CloudflareWebAnalytics class
		 *
		 * @since 0.0.1
		 *
		 * @var GdIdentity_CloudflareWebAnalytics The one true GdIdentity_CloudflareWebAnalytics
		 */
		private static $instance;

		/**
		 * The instance of the GdIdentity_CloudflareWebAnalytics object
		 *
		 * @since 0.0.1
		 *
		 * @return GdIdentity_CloudflareWebAnalytics The one true GdIdentity_CloudflareWebAnalytics
		 */
		public static function instance(): self {

			if ( ! isset( self::$instance ) && ! ( is_a( self::$instance, __CLASS__ ) ) ) {
				self::$instance = new self();
				self::$instance->setup_constants();
				if ( self::$instance->includes() ) {
					self::$instance->admin();
                    self::$instance->api();
                    self::$instance->widget();
				}
			}

			/**
			 * Fire off init action.
			 *
			 * @param GdIdentity_CloudflareWebAnalytics $instance The instance of the GdIdentity_CloudflareWebAnalytics class
			 */
			do_action( 'gdidentity_cwa_init', self::$instance );

			// Return the GdIdentity_CloudflareWebAnalytics Instance.
			return self::$instance;
		}

		/**
		 * Throw error on object clone.
		 * The whole idea of the singleton design pattern is that there is a single object
		 * therefore, we don't want the object to be cloned.
		 *
		 * @since 0.0.1
		 */
		public function __clone() {

			// Cloning instances of the class is forbidden.
			_doing_it_wrong(
				__FUNCTION__,
				esc_html__(
					'The GdIdentity_CloudflareWebAnalytics class should not be cloned.',
					'cloudflare-web-analytics'
				),
				'0.0.1'
			);
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since 0.0.1
		 */
		public function __wakeup() {

			// De-serializing instances of the class is forbidden.
			_doing_it_wrong(
				__FUNCTION__,
				esc_html__(
					'De-serializing instances of the GdIdentity_CloudflareWebAnalytics class is not allowed.',
					'cloudflare-web-analytics'
				),
				'0.0.1'
			);
		}

		/**
		 * Setup plugin constants.
		 *
		 * @since 0.0.1
		 */
		private function setup_constants(): void {

			// Plugin version.
			if ( ! defined( 'CWA_VERSION' ) ) {
				define( 'CWA_VERSION', '0.0.1' );
			}

			// Plugin Folder Path.
			if ( ! defined( 'CWA_PLUGIN_DIR' ) ) {
				define( 'CWA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL.
			if ( ! defined( 'CWA_PLUGIN_URL' ) ) {
				define( 'CWA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File.
			if ( ! defined( 'CWA_PLUGIN_FILE' ) ) {
				define( 'CWA_PLUGIN_FILE', __FILE__ );
			}

			// Whether to autoload the files or not.
			if ( ! defined( 'CWA_AUTOLOAD' ) ) {
				define( 'CWA_AUTOLOAD', true );
			}
		}

		/**
		 * Uses composer's autoload to include required files.
		 *
		 * @since 0.0.1
		 *
		 * @return bool
		 */
		private function includes(): bool {

			// Autoload Required Classes.
			if ( defined( 'CWA_AUTOLOAD' ) && false !== CWA_AUTOLOAD ) {

				if ( file_exists( CWA_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
					require_once CWA_PLUGIN_DIR . 'vendor/autoload.php';
				}

				// Bail if installed incorrectly.
				if ( ! class_exists( '\GdIdentity\CloudflareWebAnalytics\Admin' ) ) {
					add_action( 'admin_notices', [ $this, 'gdidentity_cwa_missing_notice' ] );
					return false;
				}
			}

			return true;
		}

		/**
		 * Cloudflare Web Analytics missing notice.
		 *
		 * @since 0.0.1
		 */
		public function gdidentity_cwa_missing_notice(): void {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			?>
			<div class="notice notice-error">
				<p>
					<?php esc_html_e( 'Cloudflare Web Analytics appears to have been installed without its dependencies. It will not work properly until dependencies are installed. This likely means you have cloned Cloudflare Web Analytics from Github and need to run the command `composer install`.', 'cwa' ); ?>
				</p>
			</div>
			<?php
		}

		/**
		 * Set up admin.
		 *
		 * @since 0.0.1
		 */
		private function admin(): void {

			// Setup filters.
			\GdIdentity\CloudflareWebAnalytics\Admin::init();
		}

        /**
		 * Set up API.
		 *
		 * @since 0.0.1
		 */
		private function api(): void {

			// Setup filters.
			\GdIdentity\CloudflareWebAnalytics\Api::init();
		}

        /**
		 * Set up Widget.
		 *
		 * @since 0.0.1
		 */
		private function widget(): void {

			// Setup filters.
			\GdIdentity\CloudflareWebAnalytics\Widget::render();
		}

	}

endif;

// /**
//  * Function that instantiates the plugin main class.
//  *
//  * @since 0.0.1
//  *
//  * @return GdIdentity_CloudflareWebAnalytics The one true GdIdentity_CloudflareWebAnalytics
//  */
// function wp_gdidentity_cwa_init(): \GdIdentity_CloudflareWebAnalytics {

// 	// Return an instance of the action.
// 	return \GdIdentity_CloudflareWebAnalytics::instance();
// }
// add_action( 'graphql_init', 'wp_gdidentity_cwa_init' );

\GdIdentity_CloudflareWebAnalytics::instance();
