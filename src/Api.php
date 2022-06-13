<?php

namespace CWebAnalytics;

use CWebAnalytics\Admin\Settings;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Api {

	public static function init() {
		add_action('rest_api_init',
			function () {
				// route url: domain.com/wp-json/$namespace/$route
				$namespace = 'cwa/v1';
				$route     = 'stats';
				register_rest_route($namespace,
					$route,
					[
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => function () {
							if ( ! current_user_can( 'edit_posts' ) ) {
								return new WP_Error( 'rest_forbidden', __( 'Unauthorized', 'cwa' ), [ 'status' => 401 ] );
							}
							return true;
						},
						'callback'            => function ( WP_REST_Request $request ) {
							$slug  = sanitize_text_field( $request['slug'] ) ?? '';
							$from  = sanitize_text_field( $request['from'] ) ?? '';
							$to    = sanitize_text_field( $request['to'] ) ?? '';
							$limit = intval( $request['limit'] ?? 15 );

							return new WP_REST_Response( self::stats( $slug, $from, $to, $limit ) );
						},
					]
				);
			}
		);
	}

	public static function mostRead( $days = 30, $limit = 15 ) {
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
				'datetime_geq' => gmdate( 'Y-m-d\TH:i:s\Z', strtotime( "-$days days" ) ),
				'datetime_leq' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			],
			[
				'requestPath_neq' => '/',
			],
		];

		return self::graphql( $query, $variables )->topPaths;
	}

	public static function stats( $slug, $from, $to, $limit ) {
		$days = floor( ( strtotime( $from ) - strtotime( $to ) ) / 86400 );

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
				'datetime_leq' => $from,
			],
		];

		if ( $slug ) {
			$variables[] = [
				'requestPath' => $slug,
			];
		}

		return self::graphql( $query, $variables );
	}

	public static function mostReadPosts( $days = 30, $limit = 3 ) {
		$counts_with_slug = array_map(
			function ( $item ) {
				$slug = trim( $item->dimensions->metric, '/' );

				return (object) [
					'count' => $item->count,
					'slug'  => $slug,
				];
			},
			self::mostRead( $days, $limit + 2 )
		);

		$slugs = array_map(
			function ( $item ) {
				return $item->slug;
			},
			$counts_with_slug
		);

		$posts = get_posts([
			'posts_per_page' => -1,
			'post_type'      => 'post',
			'post_name__in'  => $slugs,
			'meta_query'     => [ [ 'key' => '_thumbnail_id' ] ],
		]);

		$posts_with_count = [];
		foreach ( $counts_with_slug as $item_s ) {
			$current_post = null;
			foreach ( $posts as $post ) {
				if ( $item_s->slug === $post->post_name ) {
					$current_post = $post;
					break;
				}
			}

			if ( $current_post ) {
				$posts_with_count[] = (object) [
					'id'        => $current_post->ID,
					'title'     => $current_post->post_title,
					'slug'      => "/$item_s->slug",
					'count'     => $item_s->count,
					'thumbnail' => get_the_post_thumbnail_url( $current_post, 'full' ),
				];

				if ( count( $posts_with_count ) === $limit ) {
					break;
				}
			}
		}

		return $posts_with_count;
	}

	public static function graphql( $query, $variables = [] ) {
		$gql = [
			'query'     => $query,
			'variables' => [
				'accountTag' => Settings::get( 'accountId' ),
				'filter'     => [
					'AND' => array_merge(
						[
							[
								'siteTag_in' => [ Settings::get( 'siteTag' ) ],
							],
							[
								'requestHost' => Settings::get( 'frontendDomain', $_SERVER['SERVER_NAME'] ),
							],
						],
						$variables
					),
				],
				'order'      => 'sum_visits_DESC',
			],
		];

		$response = wp_remote_post( 'https://api.cloudflare.com/client/v4/graphql', [
			'body'    => json_encode( $gql ),
			'headers' => [
				'X-AUTH-EMAIL'  => Settings::get( 'email' ),
				'Authorization' => 'Bearer ' . Settings::get( 'token' ),
				'Content-Type'  => 'application/json',
			],
		]);

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_data = ( ! is_wp_error( $response ) ) ? $body : null;

		if ( $response->errors ) {
			return new WP_Error( 'cloudflare-error', $response->errors[0]->message, [ 'status' => $response->errors[0]->code ] );
		}

		return $response_data['data']['viewer']['accounts'][0];
	}
}
