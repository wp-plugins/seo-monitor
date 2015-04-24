<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Seo_Monitor
 */

	// If uninstall not called from WordPress, then exit.
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit;
	}

	// Remove options
	delete_option( 'seomonitor_db_version' );

	// Remove tables
	global $wpdb;

	$wpdb->seomonitor_ranks 	= $wpdb->prefix . 'seomonitor_ranks';
	$wpdb->seomonitor_keywords 	= $wpdb->prefix . 'seomonitor_keywords';

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->seomonitor_ranks}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->seomonitor_keywords}" );

	// Remove custom post types
	$seomonitor_taxonomies = array( 'seomonitor_se_group', 'seomonitor_site_group', 'seo_monitor_log_type', );
	$seomonitor_post_types = array( 'seomonitor_site', 'seomonitor_se', 'seomonitor_keyword', 'seomonitor_log' );
	foreach ( $seomonitor_post_types as $post_type ) {

		$seomonitor_taxonomies = array_merge( $seomonitor_taxonomies, get_object_taxonomies( $post_type ) );
		$items = get_posts( array( 'post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );

		if ( $items ) {
			foreach ( $items as $item ) {
				wp_delete_post( $item, true);
			}
		}
	}

	/** Delete All the Terms & Taxonomies */
	foreach ( array_unique( array_filter( $seomonitor_taxonomies ) ) as $taxonomy ) {

		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", $taxonomy ) );

		// Delete Terms
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
			}
		}

		// Delete Taxonomies
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
	}

	//Delete all meta data
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'seomonitor_%'" ) );