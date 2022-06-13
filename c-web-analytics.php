<?php
/**
 * Cloudflare Web Analytics
 *
 * Plugin Name:         Web Analytics
 * Plugin URI:          https://github.com/gdidentity/c-web-analytics
 * GitHub Plugin URI:   https://github.com/gdidentity/c-web-analytics
 * Description:         Cloudflare Web Analytics in your WordPress admin.
 * Version:             1.0.3
 * Author:              GD IDENTITY
 * Author URI:          https://gdidentity.sk
 * Text Domain:         cwa
 * License:             GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 */

use CWebAnalytics\Admin\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'CWebAnalytics' ) ) :

	/**
	 * This is the one true CWebAnalytics class
	 */
	final class CWebAnalytics {


		/**
		 * Stores the instance of the CWebAnalytics class
		 *
		 * @since 0.0.1
		 *
		 * @var CWebAnalytics The one true CWebAnalytics
		 */
		private static $instance;

		/**
		 * @var WordPressSettingsFramework
		 */
		private $wpsf;

		/**
		 * The instance of the CWebAnalytics object
		 *
		 * @since 0.0.1
		 *
		 * @return CWebAnalytics The one true CWebAnalytics
		 */
		public static function instance(): self {
			if ( ! isset( self::$instance ) && ! ( is_a( self::$instance, __CLASS__ ) ) ) {
				self::$instance = new self();
				self::$instance->setup_constants();
				if ( self::$instance->includes() ) {
					self::$instance->settings();
					self::$instance->api();
					self::$instance->widget();
					self::$instance->pluginLinks();
				}
			}

			/**
			 * Fire off init action.
			 *
			 * @param CWebAnalytics $instance The instance of the CWebAnalytics class
			 */
			do_action( 'cwa_init', self::$instance );

			// Return the CWebAnalytics Instance.
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
					'The CWebAnalytics class should not be cloned.',
					'cwa'
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
					'De-serializing instances of the CWebAnalytics class is not allowed.',
					'cwa'
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
			if ( ! defined( 'CWEBANALYTICS_VERSION' ) ) {
				define( 'CWEBANALYTICS_VERSION', '1.0.3' );
			}

			// Plugin Folder Path.
			if ( ! defined( 'CWEBANALYTICS_PLUGIN_DIR' ) ) {
				define( 'CWEBANALYTICS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL.
			if ( ! defined( 'CWEBANALYTICS_PLUGIN_URL' ) ) {
				define( 'CWEBANALYTICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File.
			if ( ! defined( 'CWEBANALYTICS_PLUGIN_FILE' ) ) {
				define( 'CWEBANALYTICS_PLUGIN_FILE', __FILE__ );
			}

			// Whether to autoload the files or not.
			if ( ! defined( 'CWEBANALYTICS_AUTOLOAD' ) ) {
				define( 'CWEBANALYTICS_AUTOLOAD', true );
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
			if ( defined( 'CWEBANALYTICS_AUTOLOAD' ) && false !== CWEBANALYTICS_AUTOLOAD ) {
				if ( file_exists( CWEBANALYTICS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
					require_once CWEBANALYTICS_PLUGIN_DIR . 'vendor/autoload.php';
				}

				// Bail if installed incorrectly.
				if ( ! class_exists( '\CWebAnalytics\Api' ) ) {
					add_action( 'admin_notices', [ $this, 'missing_notice' ] );
					return false;
				}
			}

			return true;
		}

		/**
		 * Composer dependencies missing notice.
		 *
		 * @since 0.0.1
		 */
		public function missing_notice(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			} ?>
			<div class="notice notice-error">
				<p>
					<?php esc_html_e( 'Web Analytics appears to have been installed without its dependencies. It will not work properly until dependencies are installed. This likely means you have cloned Cloudflare Web Analytics from Github and need to run the command `composer install`.', 'cwa' ); ?>
				</p>
			</div>
			<?php
		}


		/**
		 * Set up settings.
		 *
		 * @since 0.0.1
		 */
		private function settings(): void {

			$settings = new Settings();
			$settings->init();
		}


		/**
		 * Set up API.
		 *
		 * @since 0.0.1
		 */
		private function api(): void {

			// Setup filters.
			\CWebAnalytics\Api::init();
		}

		/**
		 * Set up Widget.
		 *
		 * @since 0.0.1
		 */
		private function widget(): void {

			// Setup filters.
			\CWebAnalytics\Widget::render();
		}

		/**
		 * Set up Action Links.
		 *
		 * @since 0.0.1
		 */
		private function pluginLinks(): void {

			// Setup Settings link.
			add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
				$links[] = '<a href="/wp-admin/admin.php?page=c-web-analytics">Settings</a>';

				return $links;
			});
		}
	}

endif;

\CWebAnalytics::instance();
