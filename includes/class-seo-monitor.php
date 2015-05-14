<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/includes
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/includes
 * @author     To Be On The Web <info@tobeontheweb.nl>
 */
class Seo_Monitor {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Seo_Monitor_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $Seo_Monitor    The string used to uniquely identify this plugin.
	 */
	protected $Seo_Monitor;
	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->Seo_Monitor = 'seo-monitor';
		$this->version = '1.1.0';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Seo_Monitor_Loader. Orchestrates the hooks of the plugin.
	 * - Seo_Monitor_i18n. Defines internationalization functionality.
	 * - Seo_Monitor_Admin. Defines all hooks for the admin area.
	 * - Seo_Monitor_Settings. Defines Settings functionality.
	 * - Seo_Monitor_Keyword. Defines Keyword functionality.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-monitor-loader.php';
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-monitor-i18n.php';
		/**
		 * helper functions
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers.php';
		/**
		 * The class responsible for defining all actions that belongs to Seo_Monitor_Parser.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-monitor-parser.php';
		/**
		 * The class responsible for defining all actions that belongs to Seo_Monitor_Logger.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/reports/class-seo-monitor-logs-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-monitor-logger.php';
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-seo-monitor-admin.php';

		/**
		 * Classes responsible for defining all actions that belongs to the supported search engines.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/searchengine/class-seo-monitor-search-engine-google.php';

		/**
		 * The class responsible for defining all actions that belongs to Seo_Monitor_Search_Engine.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/searchengine/class-seo-monitor-se-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/searchengine/class-seo-monitor-search-engine.php';
		/**
		 * The class responsible for defining all actions that belongs to Seo_Monitor_Site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/site/class-seo-monitor-site-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/site/class-seo-monitor-site.php';
		/**
		 * The class responsible for defining all actions that belongs to Seo_Monitor_Rank.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/keyword/class-seo-monitor-rankings-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/keyword/class-seo-monitor-rank.php';
		/**
		 * The class responsible for defining all actions that belongs to Reports.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/reports/class-seo-monitor-reports-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/reports/class-seo-monitor-graph.php';
		/**
		 * The class responsible for defining all actions that belongs to Seo_Monitor_Keyword.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/keyword/class-seo-monitor-keyword-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/keyword/class-seo-monitor-keyword.php';
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		//require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-seo-monitor-public.php';
		$this->loader = new Seo_Monitor_Loader();
	}
	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Seo_Monitor_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Seo_Monitor_i18n();
		$plugin_i18n->set_domain( $this->get_Seo_Monitor() );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}
	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$seo_monitor_admin = new Seo_Monitor_Admin( $this->get_Seo_Monitor(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $seo_monitor_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $seo_monitor_admin, 'enqueue_scripts' );
		//Note: When using 'some string' to show as a submenu of a menu page created by a plugin,
		//this item will become the first submenu item, and replace the location of the top level link.
		//If this isn't desired, the plugin that creates the menu page needs to set the add_action priority for admin_menu to 9 or lower.
		$this->loader->add_action( 'admin_menu', $seo_monitor_admin, 'add_admin_menu', 5 );
		$this->loader->add_action( 'admin_init', $seo_monitor_admin, 'register_settings' );

		// Intro text Logging List
		$this->loader->add_action( 'seo_monitor_logging_table_top', $seo_monitor_admin, 'intro_text_logging_list' );

		/*
		* Seo_Monitor_Site actions and filters
		*/
		$seo_monitor_search_engine = new Seo_Monitor_Search_Engine();

		$this->loader->add_action( 'init', $seo_monitor_search_engine, 'seomonitor_se_register' );
        $this->loader->add_action( 'admin_init', $seo_monitor_search_engine, 'register_meta_boxes_se' );
		$this->loader->add_action( 'manage_seomonitor_se_posts_custom_column', $seo_monitor_search_engine, 'manage_custom_columns', 10, 2 );

		// Intro text Logging List
		$this->loader->add_action( 'seo_monitor_se_table_top', $seo_monitor_search_engine, 'intro_text_se_list' );

		$this->loader->add_filter( 'wp_insert_post_data', $seo_monitor_search_engine, 'modify_post_title' , '99', 2 );
		$this->loader->add_filter( 'user_has_cap', $seo_monitor_search_engine, 'prevent_delete_when_se_is_active', 10, 3 );

		// Set the "seomonitor-keywords" submenu as active/current when creating/editing a "seomonitor_se" post
		$this->loader->add_filter( 'parent_file', $seo_monitor_search_engine, 'fix_admin_parent_file' );

		/*
		* Seo_Monitor_Site actions and filters
		*/
		$seo_monitor_site = new Seo_Monitor_Site();

		$this->loader->add_action( 'init', $seo_monitor_site, 'site_cpt_register' );
		$this->loader->add_action( 'admin_init', $seo_monitor_site, 'register_meta_boxes_site' );
		$this->loader->add_action( 'manage_seomonitor_site_posts_custom_column', $seo_monitor_site, 'manage_custom_columns', 10, 2 );

		//Action on save post seomonitor_site
		$this->loader->add_action( 'save_post_seomonitor_site', $seo_monitor_site, 'save_post' );

		//Action on delete post seomonitor_site
		$this->loader->add_action( 'wp_trash_post', $seo_monitor_site, 'delete_post' );
		$this->loader->add_action( 'delete_post', $seo_monitor_site, 'delete_post' );

		$this->loader->add_filter( 'wp_insert_post_data' , $seo_monitor_site, 'modify_post_title' , '99', 2 );

		// Set the "seomonitor-keywords" submenu as active/current when creating/editing a "seomonitor_site" post
		$this->loader->add_filter( 'parent_file', $seo_monitor_site, 'fix_admin_parent_file' );

		/*
		* Seo_Monitor_Keyword actions and filters
		*/
		$seo_monitor_keyword = new Seo_Monitor_Keyword();
		$this->loader->add_action( 'init', $seo_monitor_keyword, 'keyword_cpt_register' );

		// Intro text Keyword List
		$this->loader->add_action( 'seo_monitor_keywords_table_top', $seo_monitor_keyword, 'intro_text_keyword_list' );

		// To process update keywords from table listing
		//add_action( 'admin_post_update_seomonitor_keywords', $seo_monitor_keyword, 'seomonitor_update_keyword_rankings' );

		$seo_monitor_rank = new Seo_Monitor_Rank();

		// Set the "seomonitor-keywords" submenu as active/current when creating/editing a "seomonitor_site" post
		$this->loader->add_filter( 'parent_file', $seo_monitor_rank, 'fix_admin_parent_file' );

		// Set Ajax callback function
		$this->loader->add_action( 'wp_ajax_seo_monitor_update_keyword_rank', $seo_monitor_rank, 'update_keyword_rank_callback' );
	}
	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}
	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_Seo_Monitor() {
		return $this->Seo_Monitor;
	}
	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Seo_Monitor_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}