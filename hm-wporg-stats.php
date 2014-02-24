<?php
/**
 * Plugin Name: WordPress WP.org Stats Widget
 * Description: Easily pull WordPress.org stats into your WordPress Site
 * Author: Human Made Limited
 * Author URI: http://hmn.md/
 */

function hm_get_plugin_slugs() {

	return apply_filters( 'hm_wporg_plugins', $plugins );

}

function hm_get_total_plugin_download_count() {

	$total = 0;

	// Loop through the plugins and get the total number of downloads
	foreach ( hm_get_plugin_slugs() as $plugin )
		$total += (int) tlc_transient( 'hm_plugin_' . $plugin . '_downloads' )
		->updates_with( 'get_plugin_info', array( $plugin, 'downloaded_raw' ) )
		->expires_in( 60 * 60 * 24 )
		->background_only()
		->get();

	return $total;

}

function hm_get_average_last_30_days_plugins_stats() {

	if ( ! $stats = hm_get_last_30_days_plugins_stats() )
		return 0;

	return (int) ( array_sum( $stats ) / count( $stats ) );

}

function hm_get_last_30_days_plugins_stats() {

	$stats = array();

	foreach ( hm_get_plugin_slugs() as $plugin )
		foreach ( (array) tlc_transient( 'hm_plugin_' . $plugin . '_stats' )
		->updates_with( 'hm_get_last_30_days_plugin_stats', array( $plugin ) )
		->expires_in( 60 * 60 * 24 )
		->background_only()
		->get() as $date => $stat )
			isset( $stats[$date] ) ? $stats[$date] += $stat : $stats[$date] = $stat;

	return $stats;

}

function hm_get_last_30_days_plugin_stats( $slug ) {

	$response = wp_remote_get( 'http://wordpress.org/extend/stats/plugin-xml.php?slug=' . $slug );

	if ( is_wp_error( $response ) || ! $body = wp_remote_retrieve_body( $response ) )
		return;

	$body = new SimpleXMLElement( $body );
	$rows = json_decode( json_encode( $body->chart_data ), true );

	return array_slice( array_combine( array_slice( $rows['row'][0]['string'], 1 ), array_values( $rows['row'][1]['number'] ) ), -30 );

}
