<?php

namespace CWebAnalytics;

class Helpers {

	// Get Domain
	public static function getDomain() {
		$domain = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( $_SERVER['SERVER_NAME'] ) : '';
		return filter_var( $domain, FILTER_VALIDATE_DOMAIN ) ? esc_html( $domain ) : '';
	}
}
