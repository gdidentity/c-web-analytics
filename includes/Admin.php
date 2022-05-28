<?php

namespace GdIdentity\CloudflareWebAnalytics;

class Admin
{
    private static $option = 'cwa';

    public static function init()
    {
        if (is_admin()) {
            self::settings();
            self::adminRender();
        }
    }

    private static function adminRender()
    {
        add_action('admin_menu', function () {
            add_options_page(
                'Cloudflare Web Analytics',
                'Cloudflare Web Analytics',
                'manage_options',
                'cloudflare-web-analytics',
                function () {
                    if (!current_user_can('manage_options')) {
                        wp_die(__('You do not have sufficient permissions to access this page.'));
                    } ?>
					<div class="wrap">
						<h1>Cloudflare Web Analytics</h1>
						<form method="post" action="options.php">
						<?php
                            settings_fields(self::$option);
                    do_settings_sections(self::$option);
                    submit_button(); ?>
						</form>
					</div>
					<?php
                }
            );
        });
    }

    private static function settings()
    {
        add_action('admin_init', function () {
            register_setting(self::$option, self::$option);
            add_settings_section(
                'cwa',
                'Cloudflare',
                function () {
                },
                self::$option
            );
            add_settings_field(
                'email',
                'Email',
                function () {
                    $field = 'email';
                    $option = get_option(self::$option);
                    $value = $option[$field] ? esc_attr($option[$field]) : '';
                    echo '<input type="text" name="'.self::$option.'['.$field.']" value="'.$value.'" />';
                },
                self::$option,
                'cwa'
            );
            add_settings_field(
                'token',
                'Token',
                function () {
                    $field = 'token';
                    $option = get_option(self::$option);
                    $value = $option[$field] ? esc_attr($option[$field]) : '';
                    echo '<input type="password" name="'.self::$option.'['.$field.']" value="'.$value.'" />';
                    echo '<p class="description"><a href="https://developers.cloudflare.com/api/tokens/create" target="_blank">Create API Token</a> with <b>Account.Account Analytics</b> permissions.</p>';
                },
                self::$option,
                'cwa'
            );
            add_settings_field(
                'accountTag',
                'Account ID',
                function () {
                    $field = 'accountTag';
                    $option = get_option(self::$option);
                    $value = $option[$field] ? esc_attr($option[$field]) : '';
                    echo '<input type="text" name="'.self::$option.'['.$field.']" value="'.$value.'" />';
                },
                self::$option,
                'cwa'
            );
            add_settings_field(
                'siteTag',
                'Site Tag',
                function () {
                    $field = 'siteTag';
                    $option = get_option(self::$option);
                    $value = $option[$field] ? esc_attr($option[$field]) : '';
                    echo '<input type="text" name="'.self::$option.'['.$field.']" value="'.$value.'" />';
                },
                self::$option,
                'cwa'
            );
            add_settings_field(
                'frontendDomain',
                'Frontend Domain',
                function () {
                    $field = 'frontendDomain';
                    $option = get_option(self::$option);
                    $value = $option[$field] ? esc_attr($option[$field]) : $_SERVER['SERVER_NAME'];
                    echo '<input type="text" name="'.self::$option.'['.$field.']" value="'.$value.'" />';
                },
                self::$option,
                'cwa'
            );
        });
    }
}
