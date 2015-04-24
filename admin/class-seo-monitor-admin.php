<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/admin
 * @author     To Be On The Web <info@tobeontheweb.nl>
 */
class Seo_Monitor_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $Seo_Monitor    The ID of this plugin.
	 */
	private $Seo_Monitor;
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	/**
	 * The options/settings of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Seo_Monitor_Admin_Settings    $settings    The options/settings of this plugin.
	 */
	private $settings;


	/**
	*
	* @since 1.0
	*/
	private $per_page = 25;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $Seo_Monitor       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $Seo_Monitor, $version ) {
		$this->Seo_Monitor = $Seo_Monitor;
		$this->version = $version;

		$this->set_settings();
	}


	/**
	* Setter
	* @since 1.0
	*/
	public function set_settings() {
		if( class_exists( 'Seo_Monitor_Admin_Settings' ) ) {
			$this->settings = new Seo_Monitor_Admin_Settings();
		} else {
			$this->settings = null;
		}
	}

	/**
	* Getter
	* @since 1.0
	*/
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Seo_Monitor_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Seo_Monitor_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->Seo_Monitor, plugin_dir_url( __FILE__ ) . '../assets/css/seo-monitor-admin.css', array(), $this->version, 'all' );
	}
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Seo_Monitor_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Seo_Monitor_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script( $this->Seo_Monitor, plugin_dir_url( __FILE__ ) . '../assets/js/seo-monitor-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Display Logs
	 *
	 * @since    1.0.0
	 */
	public function display_logs() {


		$LoggingListTable = new Seo_Monitor_Log_List_Table();

		$LoggingListTable->prepare_items();
		?>
		<div class="wrap">
			<h2><?php _e( 'Logging', 'seo-monitor' ); ?></h2>
			<?php do_action( 'seo_monitor_logging_table_top' ); ?>
			<form id="seomonitor-logging-filter" method="get" action="<?php echo admin_url( 'admin.php?page=seomonitor-logging' ); ?>">
			<?php
			$LoggingListTable->search_box( __( 'Search logging', 'seo-monitor' ), 'seomonitor-logging' );
			$LoggingListTable->display();
			?>
				<input type="hidden" name="page" value="seomonitor-logging" />
			</form>
			<?php do_action( 'seo_monitor_logging_table_bottom' ); ?>
		</div>
		<?php
	}

	/**
	* Intro Text Logging List
	*
	* @since 1.0
	*/
	public function intro_text_logging_list() {
		_e( 'Logging results will be automatically purged, log results older than two week will be deleted automatically. If you want you can also delete log items manually', 'seo-monitor' );
	}

	/**
	 * Add new Top Level Menu
	 *
	 * @global $seo_monitor_keywords_page
	 * @global $seo_monitor_sites_page
	 * @global $seo_monitor_se_page
	 * @global $seo_monitor_reports_page
	 * @global $seo_monitor_settings_page
	 * @global $seo_monitor_logging_page
	 * @global $seo_monitor_ranking_page
	 *
	 * @since    1.0
	 */
	public function add_admin_menu() {

		global $seo_monitor_keywords_page, $seo_monitor_sites_page, $seo_monitor_se_page, $seo_monitor_reports_page,
			   $seo_monitor_settings_page, $seo_monitor_logging_page, $seo_monitor_ranking_page;

		//create new top-level menu
		//use dashicon, see: http://melchoyce.github.io/dashicons/
		add_menu_page( 'SEO Monitor', 								// page_title
					   'SEO Monitor', 								// menu_title
					   'manage_options', 							// capability
					   'seomonitor-keywords',						// menu_slug
					   array( 'Seo_Monitor_Keyword', 'display_all_keywords' ), 		// function
					   'dashicons-admin-tools', 					// icon_url
					   81 											// position
		);

		$seo_monitor_keyword = get_post_type_object( 'seomonitor_keyword' );

		$seo_monitor_keywords_page = add_submenu_page( 	'seomonitor-keywords',									// Parent menu_slug
						  								$seo_monitor_keyword->labels->name,						// page_title
						  								$seo_monitor_keyword->labels->menu_name,					// menu_title
						  								'manage_options', 									// capability
						  								'seomonitor-keywords', 								// menu_slug
						  								array( 'Seo_Monitor_Keyword', 'display_all_keywords' ) 	// function
		);

		$seo_monitor_site = get_post_type_object( 'seomonitor_site' );

		$seo_monitor_sites_page = add_submenu_page( 'seomonitor-keywords',									// Parent menu_slug
						  							$seo_monitor_site->labels->name,						// page_title
						  							$seo_monitor_site->labels->menu_name,					// menu_title
						  							'manage_options', 									// capability
						  							'seomonitor-sites', 									// menu_slug
						  							array( 'Seo_Monitor_Site', 'display_all_sites' ) 		// function
						  							//'edit.php?post_type=seomonitor_site'
		);

		$seo_monitor_se = get_post_type_object( 'seomonitor_se' );

		$seo_monitor_se_page = add_submenu_page( 'seomonitor-keywords',									// Parent menu_slug
						  						 $seo_monitor_se->labels->name,						// page_title
						  						 $seo_monitor_se->labels->menu_name,					// menu_title
						  						 'manage_options', 									// capability
						  						 'seomonitor-se', 									// menu_slug
						  						 array( 'Seo_Monitor_Search_Engine', 'display_all_search_engines' )
		);

		$seo_monitor_reports_page = add_submenu_page( 	'seomonitor-keywords',									// Parent menu_slug
						  								__( 'Reports', 'seo-monitor' ),							// page_title
						  								__( 'Reports', 'seo-monitor' ),							// menu_title
						  								'manage_options', 									// capability
						  								'seomonitor-reports',
						  								array( 'Seo_Monitor_Rank', 'display_reports' )
		);

		if( $this->get_settings() ) {
			$seo_monitor_settings_page = add_submenu_page( 'seomonitor-keywords',						// Parent menu_slug
							  								__( 'Settings', 'seo-monitor' ), 			// page_title
							  								__( 'Settings', 'seo-monitor' ),			// menu_title
							  								'manage_options', 						// capability
							  								'seomonitor-settings', 					// menu_slug
							  								array( $this->get_settings(), 'seomonitor_options' ) 	// function
			);
		}

		$seo_monitor_logging_page = add_submenu_page( 	'seomonitor-keywords',	// Parent menu_slug
						  								__( 'Logs', 'seo-monitor' ), 			// page_title
						  								__( 'Logs', 'seo-monitor' ),			// menu_title
						  								'manage_options', 						// capability
						  								'seomonitor-logging', 					// menu_slug
						  								array( $this, 'display_logs' ) 	// function
		);

		// This is a hidden page
		$seo_monitor_ranking_page = add_submenu_page(	null,							// Parent menu_slug
														__( 'Rankings', 'seo-monitor' ),
														__( 'Rankings', 'seo-monitor' ),
														'manage_options',
														'seomonitor-rankings',
														array( 'Seo_Monitor_Rank', 'display_all_rankings' )
		);
	}

	/**
	 * Register and add settings
	 * @since 1.0
	 */
	public function register_settings() {

		if( $this->get_settings() ) {
			$this->settings->register_settings();
		}
	}
}