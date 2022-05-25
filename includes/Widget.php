<?php

namespace GdIdentity\CloudflareWebAnalytics;

use GdIdentity\CloudflareWebAnalytics\Settings;

class Widget
{
    public static function render()
    {

        // add widget to the dashboard
        add_action('wp_dashboard_setup', function () {
            wp_add_dashboard_widget(
                'cwa_dashboard_widget',
                __('Cloudflare Web Analytics', 'cwa'),
                function () {
                    self::spa();
                }
            );
        });

        // add widget below post/page editor
        add_action('add_meta_boxes', function ($post_type) {
            $post_types = [ 'post', 'page' ];

            if (in_array($post_type, $post_types)) {
                add_meta_box(
                    'cwa_dashboard_widget',
                    __('Cloudflare Web Analytics', 'cwa'),
                    function ($post) {
                        $slug = preg_replace('/^(http)?s?:?\/\/[^\/]*(\/?.*)$/i', '$2', '' . get_permalink($post));
                        self::spa($slug);
                    },
                    $post_type,
                    'advanced',
                    'high'
                );
            }
        }, 10, 2);
    }

    public function spa($slug = '')
    {
        echo '<div id="cwa"></div>';

        $assets_path = CWA_PLUGIN_URL . 'build/';
        $manifest = $assets_path . 'asset-manifest.json?nocache=' . mt_rand();
        $str = file_get_contents($manifest);
        $json = json_decode($str, true);
        foreach ($json['entrypoints'] as $index => $asset) {
            if (preg_match('/.css$/', $asset)) {
                wp_enqueue_style('cwa-'.$index, $assets_path.$asset);
            }
            if (preg_match('/.js$/', $asset)) {
                wp_enqueue_script('cwa-'.$index, $assets_path.$asset, [], 1, true);
            }
        }

        wp_localize_script('cwa-1', 'cwaSettings', [
            'slug' => $slug,
            'frontendDomain' => Settings::get()['frontendDomain']
        ]);
    }
}
