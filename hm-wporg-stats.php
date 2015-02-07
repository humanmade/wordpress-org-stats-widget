<?php
/**
 * Plugin Name: WordPress WP.org Stats Widget
 * Description: Easily pull WordPress.org stats into your WordPress Site
 * Author: Human Made Limited
 * Author URI: http://hmn.md/
 */

function hm_get_plugin_slugs() {

	return apply_filters( 'hm_wporg_plugins', array( 'hello-dolly' ) );

}

function hm_get_total_plugin_download_count() {

	$total = 0;

	// Loop through the plugins and get the total number of downloads
	foreach ( hm_get_plugin_slugs() as $plugin ) {
		$amount = array_sum( 
			(array) tlc_transient( 'hm_plugin_' . $plugin . '_downloads' )
				->updates_with( 'hm_get_plugin_total_downloads', array( $plugin ) )
				->expires_in( 60 * 60 * 24 )
				->background_only()
				->get()
		);

		$total += $amount;
	}

	return $total;

}

function hm_get_average_last_30_days_plugins_stats() {

	if ( ! $stats = hm_get_last_30_days_plugins_stats() )
		return 0;

	return (int) ( array_sum( $stats ) / count( $stats ) );

}

function hm_get_last_30_days_plugins_stats() {

	$stats = array();

	foreach ( hm_get_plugin_slugs() as $plugin ) {

		$plugin_downloads = tlc_transient( 'hm_plugin_' . $plugin . '_stats' )
			->updates_with( 'hm_get_plugin_download_stats', array( $plugin, 30 ) )
			->expires_in( 60 * 60 * 24 )
			->background_only()
			->get();

		foreach ( $plugin_downloads as $date => $stat ) {

			isset( $stats[$date] ) ? $stats[$date] += $stat : $stats[$date] = $stat;
		}
	}

	return $stats;

}

function hm_get_plugin_download_stats( $slug, $days = 30 ) {

	$response = wp_remote_get( 
		add_query_arg( 
			array(
				'limit' => $days,
				'slug'  => $slug
			),
			'https://api.wordpress.org/stats/plugin/1.0/downloads.php'
		)
	);

	if ( is_wp_error( $response ) ) {
		trigger_error( 
			sprintf( 'Getting download stats %s failed with code %s message %s', $slug, $response->get_error_code(), $response->get_error_message() ),
			E_USER_NOTICE
		);
		return;
	}

	$body = wp_remote_retrieve_body( $response );

	return array_map( 'absint', json_decode( $body, true ) );
}

function hm_get_plugin_total_downloads( $slug ) {

	$response = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.0/' . $slug . '.json' );

	if ( is_wp_error( $response ) || ! $body = wp_remote_retrieve_body( $response ) )
		return;

	return absint( json_decode( $body )->downloaded );
}
