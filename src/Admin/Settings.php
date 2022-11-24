<?php

namespace CWebAnalytics\Admin;

use CWebAnalytics\Admin\SettingsRegistry;
use CWebAnalytics\Helpers;

class Settings {

	/**
	 * @var SettingsRegistry
	 */
	public $settings_api;


	/**
	 * Initialize the Settings Pages
	 *
	 * @return void
	 */
	public function init() {
		$this->settings_api = new SettingsRegistry();
		add_action( 'admin_menu', [ $this, 'add_options_page' ] );
		add_action( 'init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'initialize_settings_page' ] );
	}


	/**
	 * Add the options page to the WP Admin
	 *
	 * @return void
	 */
	public function add_options_page() {

		add_options_page(
			__( 'Cloudflare Web Analytics', 'cwa' ),
			__( 'Cloudflare Web Analytics', 'cwa' ),
			'manage_options',
			'c-web-analytics',
			[ $this, 'render_settings_page' ]
		);

	}

	/**
	 * Registers the settings fields
	 *
	 * @return void
	 */
	public function register_settings() {

		$this->settings_api->register_section( 'c-web-analytics_settings', [
			'title' => __( 'Cloudflare Web Analytics', 'cwa' ),
		] );

		$this->settings_api->register_fields( 'c-web-analytics_settings', [
			[
				'name'  => 'email',
				'label' => __( 'Email', 'cwa' ),
				'type'  => 'text',
			],
			[
				'name'  => 'token',
				'label' => __( 'Token', 'cwa' ),
				'desc'  => '<a href="https://developers.cloudflare.com/api/tokens/create" target="_blank">Create API Token</a> with <b>Account.Account Analytics</b> permissions.',
				'type'  => 'password',
			],
			[
				'name'  => 'accountId',
				'label' => __( 'Account ID', 'cwa' ),
				'type'  => 'text',
			],
			[
				'name'  => 'siteTag',
				'label' => __( 'Site Tag', 'cwa' ),
				'type'  => 'text',
				'desc'  => "You'll find it in the Site analytics URL <code>https://dash.cloudflare.com/[ACCOUNT_ID]/web-analytics/overview?siteTag~in=[SITE_TAG]</code>",
			],
			[
				'name'    => 'frontendDomain',
				'label'   => __( 'Frontend Domain', 'cwa' ),
				'type'    => 'text',
				'default' => Helpers::getDomain(),
			],
		] );

	}

	/**
	 * Initialize the settings admin page
	 *
	 * @return void
	 */
	public function initialize_settings_page() {
		$this->settings_api->admin_init();
	}

	/**
	 * Render the settings page in the admin
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'c-web-analytics' ) );
		}
		?>
		<div class="wrap">
			<?php
			$this->settings_api->show_navigation();
			$this->settings_api->show_forms();
			?>
		</div>
		<?php
	}

	/**
	 * Get field value
	 */
	public static function get( string $option_name, $default = '', $section_name = 'c-web-analytics_settings' ) {

		$section_fields = get_option( $section_name );

		return isset( $section_fields[ $option_name ] ) ? $section_fields[ $option_name ] : $default;
	}

}
