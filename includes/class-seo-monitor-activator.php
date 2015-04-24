<?php
/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/includes
 */
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/includes
 * @author     To Be On The Web <info@tobeontheweb.nl>
 */
class Seo_Monitor_Activator {
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

	    if ( version_compare( get_bloginfo( 'version' ), '4.1.1', '<' ) ) {
	        wp_die( 'Wordpress 4.1.1 or higher is required for this plugin. You must update WordPress to use this plugin.' );
	    }

		if( version_compare( phpversion(), '5.2.0', '<' ) ) {
			wp_die( 'This plugin needs php version greater than 5.2.0, please update' );
		}

        Seo_Monitor_Activator::createTables();
	}

	public static function createTables() {

		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();

		$db_version = get_option( 'seomonitor_db_version' );

		$wpdb->seomonitor_ranks 	= $wpdb->prefix . 'seomonitor_ranks';
		$wpdb->seomonitor_keywords 	= $wpdb->prefix . 'seomonitor_keywords';

		if( empty( $db_version ) ) {
			//Store Multiple Keyword data
			$sql = "CREATE TABLE $wpdb->seomonitor_ranks (
				id int(11) NOT NULL AUTO_INCREMENT,
				keyword_id int(11),
				rank int(11) NOT NULL,
				rank_link varchar(300) DEFAULT '' NOT NULL,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			dbDelta( $sql );

			//Store Single Keyword data
			$sql = "CREATE TABLE $wpdb->seomonitor_keywords (
				id int(11) NOT NULL AUTO_INCREMENT,
				keyword varchar(300) NOT NULL,
				project_id int(11),
				rank int(11) NOT NULL,
				previous int(11) NOT NULL,
				top_rank int(11) NOT NULL,
				engine varchar(100) DEFAULT '' NOT NULL,
				last_check datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			dbDelta( $sql );

			//Insert Google Search engine
			$args = array(
						'post_type' 	=> 'seomonitor_se',
						'title'			=> 'www.google.com',
						'post_status'	=> 'publish'
			);

			$google_id = wp_insert_post( $args );

			update_post_meta( $google_id, 'seomonitor_se_search_engine', 'www.google.com' );
			update_post_meta( $google_id, 'seomonitor_se_url', 'http://www.google.com' );
		}

		//Store DB version
		add_option( 'seomonitor_db_version', SEOMONITOR_DB_VERSION );
	}
}