<?php

namespace GdIdentity\CloudflareWebAnalytics;

use GdIdentity\CloudflareWebAnalytics\Settings;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Api
{
    public static function init()
    {
        add_action('rest_api_init', function () {
            // route url: domain.com/wp-json/$namespace/$route
            $namespace = 'cwa/v1';
            $route     = 'stats';
            register_rest_route($namespace, $route, array(
                'methods'   => WP_REST_Server::READABLE,
                'permission_callback' =>  function () {
                    if (! current_user_can('edit_posts')) {
                        return new WP_Error('rest_forbidden', __('Unauthorized', 'cwa'), [ 'status' => 401 ]);
                    }
                    return true;
                },
                'callback'  => function (WP_REST_Request $request) {
                    $slug = sanitize_text_field($request['slug']) ?? '';
                    $from = sanitize_text_field($request['from']) ?? '';
                    $to = sanitize_text_field($request['to']) ?? '';
                    $limit = intval($request['limit'] ?? 15);

                    return new WP_REST_Response(self::stats($slug, $from, $to, $limit));
                }
            ));
        });
    }

    public static function mostRead($days = 30, $limit = 15)
    {
        $offset = -60;
        $query = "
            query GetRumAnalyticsTopNs {
                viewer {
                    accounts(filter: {accountTag: \$accountTag}) {
                        topPaths: rumPageloadEventsAdaptiveGroups(filter: \$filter, limit: $limit, orderBy: [\$order]) {
                            count
                            dimensions {
                                metric: requestPath
                            }
                        }
                    }
                }
            }
        ";

        $variables = [
            [
                'datetime_geq' => $offset ? gmdate('Y-m-d\TH:i:s\Z', strtotime($offset - $days . ' days')) : gmdate('Y-m-d\TH:i:s\Z', strtotime("-$days days")),
                'datetime_leq' => $offset ? gmdate('Y-m-d\TH:i:s\Z', strtotime("$offset days")) : gmdate('Y-m-d\TH:i:s\Z')
            ],
            [
                'requestPath_neq' => '/'
            ]
        ];

        return self::graphql($query, $variables)->topPaths;
    }

    public static function stats($slug, $from, $to, $limit)
    {
        $days = floor((strtotime($from) - strtotime($to)) / 86400);
        // datetimeFifteenMinutes datetimeHour date
        $interval = $days <= 1 ? 'datetimeHour' : 'date';

        $query = "
            query GetRumAnalyticsTopNs {
                viewer {
                    accounts(filter: {accountTag: \$accountTag}) {
                        total: rumPageloadEventsAdaptiveGroups(filter: \$filter, limit: 1) {
                            count
                            sum {
                                visits
                            }
                        }
                        topReferrers: rumPageloadEventsAdaptiveGroups(filter: \$filter, limit: $limit, orderBy: [\$order]) {
                            count
                            avg {
                                sampleInterval
                            }
                            sum {
                                visits
                            }
                            dimensions {
                                metric: refererHost
                            }
                        }
                        topPaths: rumPageloadEventsAdaptiveGroups(filter: \$filter, limit: $limit, orderBy: [\$order]) {
                            count
                            avg {
                                sampleInterval
                            }
                            sum {
                                visits
                            }
                            dimensions {
                                metric: requestPath
                            }
                        }
                        topBrowsers: rumPageloadEventsAdaptiveGroups(filter: \$filter, limit: $limit, orderBy: [\$order]) {
                            count
                            avg {
                                sampleInterval
                            }
                            sum {
                                visits
                            }
                            dimensions {
                                metric: userAgentBrowser
                            }
                        }
                        topOSs: rumPageloadEventsAdaptiveGroups(filter: \$filter, limit: $limit, orderBy: [\$order]) {
                            count
                            avg {
                                sampleInterval
                            }
                            sum {
                                visits
                            }
                            dimensions {
                                metric: userAgentOS
                            }
                        }
                        topDeviceTypes: rumPageloadEventsAdaptiveGroups(filter: \$filter, limit: $limit, orderBy: [\$order]) {
                            count
                            avg {
                                sampleInterval
                            }
                            sum {
                                visits
                            }
                            dimensions {
                                metric: deviceType
                            }
                        }
                        countries: rumPageloadEventsAdaptiveGroups(filter: \$filter, limit: $limit, orderBy: [\$order]) {
                            count
                            avg {
                                sampleInterval
                            }
                            sum {
                                visits
                            }
                            dimensions {
                                metric: countryName
                            }
                        }
                        visits: rumPageloadEventsAdaptiveGroups(limit: 168, filter: \$filter) {
                            sum {
                                visits
                            }
                            avg {
                                sampleInterval
                            }
                            dimensions {
                                ts: $interval
                            }
                        }
                    }
                }
            }
        ";

        $variables = [
            [
                'datetime_geq' => $to,
                'datetime_leq' => $from
            ]
        ];

        if ($slug) {
            $variables[] = [
                'requestPath' => $slug
            ];
        }

        return self::graphql($query, $variables);
    }

    public static function mostReadPosts($days = 30, $limit = 3)
    {
        $countsWithSlug = array_map(function ($item) {
            $slug = trim($item->dimensions->metric, '/');

            return (object) [
                'count' => $item->count,
                'slug'  => $slug
            ];
        }, self::mostRead($days, $limit + 2));

        $slugs = array_map(function ($item) {
            return $item->slug;
        }, $countsWithSlug);

        $posts = get_posts([
            'posts_per_page'   => -1,
            'post_type'        => 'post',
            'post_name__in'    => $slugs,
            'meta_query' => array(array('key' => '_thumbnail_id'))
        ]);

        $postsWithCount = [];
        foreach ($countsWithSlug as $itemS) {
            $currentPost = null;
            foreach ($posts as $post) {
                if ($itemS->slug === $post->post_name) {
                    $currentPost = $post;
                    break;
                }
            }

            if ($currentPost) {
                $postsWithCount[] = (object) [
                    'id'        => $currentPost->ID,
                    'title'     => $currentPost->post_title,
                    'slug'      => "/$itemS->slug",
                    'count'     => $itemS->count,
                    'thumbnail' => get_the_post_thumbnail_url($currentPost, 'full')
                ];

                if (count($postsWithCount) === $limit) {
                    break;
                }
            }
        }

        return $postsWithCount;
    }

    public function graphql($query, $variables = [])
    {
        $gql = [
            'query' => $query,
            'variables' => [
                'accountTag' => Settings::get()['accountTag'],
                'filter'     => [
                    'AND' => array_merge([
                        // [
                        //     'datetime_geq' => '2022-01-20T16:45:57Z',
                        //     'datetime_leq' => '2022-02-19T16:45:57Z'
                        // ],
                        [
                            'siteTag_in' => [Settings::get()['siteTag']]
                        ],
                        [
                            'requestHost' => Settings::get()['frontendDomain']
                        ],
                        // [
                        //     'requestPath' => '/rusky-reholnik-viktor/'
                        // ]
                    ], $variables),
                ],
                'order'     => 'sum_visits_DESC'
            ]
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/graphql');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-AUTH-EMAIL: ' . Settings::get()['email'],
            'Authorization: Bearer '. Settings::get()['token'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gql));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($response->errors) {
            return new WP_Error('cloudflare-error', $response->errors[0]->message, [ 'status' => $response->errors[0]->code ]);
        }

        return $response->data->viewer->accounts[0];
    }
}
