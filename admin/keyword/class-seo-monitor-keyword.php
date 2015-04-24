<?php
/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/admin
 * @author     To Be On The Web <info@tobeontheweb.nl>
 */
class Seo_Monitor_Keyword {

    /**
    * holds the table name
    * @since 1.0
    */
	private $table_name;

    /**
    * holds the cache group
    * @since 1.0
    */
	private $cache_group;

    /*
    * Properties
    */

    /**
    *
    * @since 1.0
    */
	private $id;

    /**
    *
    * @since 1.0
    */
	private $keyword;

    /**
    *
    * @since 1.0
    */
	private $rank;

    /**
    *
    * @since 1.0
    */
	private $previous;

    /**
    *
    * @since 1.0
    */
	private $top_rank;

    /**
    *
    * @since 1.0
    */
	private $search_engine_id;

    /**
    *
    * @since 1.0
    */
	private $site_id;

    /**
    *
    * @since 1.0
    */
	private $last_check;

    /**
    * Constructor
    * @since 1.0
    */
	public function __construct( $id = null, $keyword = null, $rank = 0, $previous = 0,
								 $top_rank = 0, $search_engine_id = null, $site_id = null,
								 $last_check = null ) {

		global $wpdb;

		$this->table_name 	= $wpdb->prefix . 'seomonitor_keywords';
		$this->cache_group 	= 'seo-monitor-keywords';

		$this->set_id( $id );
		$this->set_keyword( $keyword );
		$this->set_rank( $rank );
		$this->set_previous( $previous );
		$this->set_top_rank( $top_rank );
		$this->set_search_engine_id( $search_engine_id );
	    $this->set_site_id( $site_id );
	    $this->set_last_check( $last_check );
	}

	/**
	* Insert a new keyword in the database
	*
	* @access  public
	* @return keyword_id
	* @since 1.0
	*/
	public function add_keyword() {

		global $wpdb;

		$keyword = $this->get_keyword();

		//make sure we have values
		if( empty( $keyword ) ) {
			return false;
		}

		//add CPT keyword
		$keyword_data = array(
			'keyword' 			=> $keyword,
			'project_id'		=> $this->get_site_id(),
			'engine' 			=> $this->get_search_engine_id(),
		);

		$affected_records 	= $wpdb->insert( $this->table_name, $keyword_data );
		$keyword_id 		= $wpdb->insert_id;

		return $keyword_id;
	}

	/**
	* Update Keyword data
	*
	* @access  public
	* @since 1.0
	*/
	public function edit_keyword() {

		global $wpdb;

		$data 	= array(
					'rank' 			=> $this->get_rank(),
					'previous'		=> $this->get_previous(),
					'top_rank'		=> $this->get_top_rank(),
					'last_check' 	=> $this->get_last_check()
		);

        return $wpdb->update( $this->table_name, $data, array( 'id' => $this->get_id() ) );
	}

    /**
    * Intro Text Keyword List
    *
    * @since 1.0
    */
    public function intro_text_keyword_list() {
        _e( 'The visible keywords in the list will be updated automatically, when they were not updated the last 24 hours.<br/>
        	When you want to update a keyword manually, you can click on the checkmark.<br/>
        	When you click on a keyword you will get a list with all individual rankings. In this screen you can for example remove sample data', 'seo-monitor' );
    }

	/**
	* @access  public
	* @return affected rows
	* @since 1.0
	*/
	public function delete_keyword() {

		global $wpdb;

		$keyword = $this->get_keyword();

		//make sure we have values
		if( empty( $keyword ) ) {
			return false;
		}

		$site_id 	= $this->get_site_id();
		$engine_id 	= $this->get_search_engine_id();

		$args = array(
					'keyword'	=> $keyword,
					'website'	=> $site_id,
					'engine' 	=> $engine_id
		);

		$keyword_ids = $this->get_keywords( $args );

		if( $keyword_ids ) {
			foreach ( $keyword_ids as $keyword_id ) {
				// Delete rankings which belongs to the keyword
				$seo_monitor_rank = new Seo_Monitor_Rank( null, $keyword_id->id );
				$seo_monitor_rank->delete_rankings();
			}
		}

		//delete keyword
		$keyword_data = array(
			'project_id'		=> $site_id,
			'keyword' 			=> $keyword,
			'engine' 			=> $engine_id
		);

		$affected_rows = $wpdb->delete( $this->table_name, $keyword_data );

		return $affected_rows;
	}

	/**
	*
	* Make use of dependency injection
	*
	* @param site_id integer
	* @param $form_data $_POST or other associative array
	* @since 1.0
	*/
	public function update_site_keywords( $site_id, $form_data ) {

		// Set corresponding site
		$this->set_site_id( $site_id );

		$previous_keywords 	= explode( "\n", str_replace( "\r", '', rwmb_meta( 'seomonitor_site_keywords', '',  $site_id ) ) );

    	if( isset( $form_data['seomonitor_site_keywords'] ) ) {
			$current_keywords 	= explode( "\n", str_replace( "\r", '', $form_data['seomonitor_site_keywords'] ) );
		} else {
			$current_keywords 	= '';
		}

		if( is_array( $current_keywords ) && is_array( $previous_keywords ) ) {

			$delete_keywords 	= array_diff( $previous_keywords, $current_keywords );
			$add_keywords 		= array_diff( $current_keywords, $previous_keywords );
		}

		$previous_engines 	= rwmb_meta( 'seomonitor_site_engine', 'type=select_advanced&multiple=true', $site_id );
		$current_engines	= isset( $form_data['seomonitor_site_engine'] ) ? $form_data['seomonitor_site_engine'] : '';

		if( is_array( $previous_engines ) && is_array( $current_engines ) && !empty( $previous_engines ) ) {
			$delete_engines  	= array_diff( $previous_engines, $current_engines );
			$add_engines 		= array_diff( $current_engines, $previous_engines );

			// For all removed engines
			if( !empty( $delete_engines ) ) {
				foreach ( $delete_engines as $delete_engine ) {

					$this->set_search_engine_id( $delete_engine );
					$this->delete_engine();
				}
			}

			// For all new engines
			if( !empty( $add_engines ) ) {
				foreach ( $add_engines as $engine ) {

					//add all posted keywords
					if( !empty( $current_keywords ) ) {

						foreach( $current_keywords as $current_keyword ) {

							$this->set_keyword( $current_keyword );
							$this->set_search_engine_id( $engine );
							$keyword_id = $this->add_keyword();

							if( $element = array_search( $engine, $current_engines ) ) {
								unset( $current_engines[$element] ); //prevent doubles
							}
						}
					}
				}
			}
		}

		// For all posted engines
		if( !empty( $current_engines ) ) {

			foreach( $current_engines as $engine ) {

				//delete "old" keywords
				if( !empty( $delete_keywords ) ) {

					foreach( $delete_keywords as $delete_keyword ) {
						$this->set_keyword( $delete_keyword );
						$this->set_search_engine_id( $engine );
						$this->delete_keyword();
					}
				}

				//add new keywords
				if( !empty( $add_keywords ) ) {

					foreach( $add_keywords as $add_keyword ) {
						$this->set_keyword( $add_keyword );
						$this->set_search_engine_id( $engine );
						$keyword_id = $this->add_keyword();
					}
				}
			}
		}
	}

	/**
	 * Delete all keywords with specific engine
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function delete_engine() {

		global $wpdb;

		$engine_id = $this->get_search_engine_id();

		//make sure we have values
		if( empty( $engine_id ) ) {
			return false;
		}

		//delete CPT keyword
		$keyword_data = array(
			'project_id'		=> $this->get_site_id(),
			'engine' 			=> $engine_id,
		);

		$affected_rows = $wpdb->delete( $this->table_name, $keyword_data );

		return $affected_rows;

	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function get_columns() {
		return array(
			'id'            => '%d',
			'keyword'       => '%s',
			'project_id'    => '%d',
			'rank'          => '%d',
			'previous'    	=> '%d',
			'top_rank' 		=> '%d',
			'engine' 		=> '%d',
			'last_check'    => '%s',
		);
	}

	/**
	 * Retrieve keywords from the database
	 *
	 * @access  public
	 * @since   1.0
	 * @global WPDB $wpdb
	 * @return result will be output as a numerically indexed array of row objects.
	*/
	public function get_keywords( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'       => 20,
			'offset'       => 0,
			'user_id'      => 0,
			'orderby'      => 'id',
			'order'        => 'DESC'
		);

		$args  = wp_parse_args( $args, $defaults );

		if( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = '';

		// specific keywords
		if( ! empty( $args['id'] ) ) {

			if( is_array( $args['id'] ) ) {
				$ids = implode( ',', $args['id'] );
			} else {
				$ids = intval( $args['id'] );
			}

			$where .= "WHERE `id` IN( {$ids} ) ";
		}

		// filter by site
		if( ! empty( $args['site'] ) ) {

			if( is_array( $args['site'] ) ) {
				$side_ids = "'" . implode( "', '", $args['site'] ) . "'";
			} else {
				$side_ids = "'" . $args['site'] . "'";
			}

			if( ! empty( $where ) ) {
				$where .= " AND `project_id` IN( {$side_ids} ) ";
			} else {
				$where .= "WHERE `project_id` IN( {$side_ids} ) ";
			}
		}

		// filter by engine
		if( ! empty( $args['engine'] ) ) {

			if( is_array( $args['engine'] ) ) {
				$engines = "'" . implode( "', '", $args['engine'] ) . "'";
			} else {
				$engines = "'" . $args['engine'] . "'";
			}

			if( ! empty( $where ) ) {
				$where .= " AND `engine` IN( {$engines} ) ";
			} else {
				$where .= "WHERE `engine` IN( {$engines} ) ";
			}
		}

		// specific keywords by name
		if( ! empty( $args['keyword'] ) ) {

			if( ! empty( $where ) ) {
				$where .= " AND `keyword` LIKE '" . $args['keyword'] . "' ";
			} else {
				$where .= "WHERE `keyword` LIKE '%%" . $args['keyword'] . "%%' ";
			}
		}

		// Keywords created for a specific date or in a date range
		if( ! empty( $args['date'] ) ) {

			if( is_array( $args['date'] ) ) {

				if( ! empty( $args['date']['start'] ) ) {

					$start = date( 'Y-m-d H:i:s', strtotime( $args['date']['start'] ) );

					if( ! empty( $where ) ) {

						$where .= " AND `date_created` >= '{$start}'";

					} else {

						$where .= " WHERE `date_created` >= '{$start}'";

					}

				}

				if( ! empty( $args['date']['end'] ) ) {

					$end = date( 'Y-m-d H:i:s', strtotime( $args['date']['end'] ) );

					if( ! empty( $where ) ) {

						$where .= " AND `date_created` <= '{$end}'";

					} else {

						$where .= " WHERE `date_created` <= '{$end}'";

					}

				}

			} else {

				$year  = date( 'Y', strtotime( $args['date'] ) );
				$month = date( 'm', strtotime( $args['date'] ) );
				$day   = date( 'd', strtotime( $args['date'] ) );

				if( empty( $where ) ) {
					$where .= " WHERE";
				} else {
					$where .= " AND";
				}

				$where .= " $year = YEAR ( date_created ) AND $month = MONTH ( date_created ) AND $day = DAY ( date_created )";
			}

		}

		$args['orderby'] 	= ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'id' : $args['orderby'];

		$cache_key 			= md5( 'seo_monitor_keywords_' . serialize( $args ) );

		$keywords = wp_cache_get( $cache_key, $this->cache_group );

		if( $keywords === false ) {
			$keywords = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->table_name $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d",
															absint( $args['offset'] ),
															absint( $args['number'] ) ) );

			wp_cache_set( $cache_key, $keywords, $this->cache_group, 3600 );
		}

		return $keywords;
	}

	/**
	*
	* display all projects with keywords
	* @since 1.0
	*/
	public static function display_all_keywords() {

		$keyword_list_table = new Seo_Monitor_Keyword_List_Table();
		$keyword_list_table->prepare_items();
		?>
		<div class="wrap">
			<h2><?php _e( 'Keywords', 'seo-monitor' ); ?></h2>
			<?php do_action( 'seo_monitor_keywords_table_top' ); ?>
			<form id="seomonitor-keywords-filter" method="get" action="<?php echo admin_url( 'admin.php?page=seomonitor-keywords' ); ?>">
				<input type="hidden" name="page" value="seomonitor-keywords" />
				<input type="hidden" name="post_type" value="seomonitor_keyword" />
			<?php

			$keyword_list_table->search_box( __( 'Search Keywords', 'seo-monitor' ), 'seo-monitor-keywords' );
			$keyword_list_table->display();
			?>
			</form>
			<?php do_action( 'seo_monitor_keywords_table_bottom' ); ?>
		</div>
		<?php

		// For test purposes
		//$seo_monitor_rank = new Seo_Monitor_Rank();
		//$seo_monitor_rank->update_all_rankings();
	}

	public function keyword_cpt_register() {

		$keyword_labels = array(
	                'name'               => __( 'Keyword', 'seo-monitor' ),
	                'singular_name'      => __( 'Keyword', 'seo-monitor' ),
	                'add_new'            => __( 'Add', 'seo-monitor' ),
	                'add_new_item'       => __( 'Add Keyword', 'seo-monitor' ),
	                'edit_item'          => __( 'Edit Keyword', 'seo-monitor' ),
	                'new_item'           => __( 'New Keyword', 'seo-monitor' ),
	                'all_items'          => __( 'All Keywords', 'seo-monitor' ),
	                'view_item'          => __( 'View Keyword', 'seo-monitor' ),
	                'search_items'       => __( 'Search Keywords', 'seo-monitor' ),
	                'not_found'          => __( 'No keywords found', 'seo-monitor' ),
	                'not_found_in_trash' => __( 'No keyword found in the trash', 'seo-monitor' ),
	                'parent_item_colon'  => '',
	                'menu_name'          => __( 'Keywords', 'seo-monitor' )
	        );
	    $keyword_args = array(
	        'labels' 				=> $keyword_labels,
	        'public' 				=> false,
            'show_ui' 				=> true,
            'show_in_nav_menus'     => false,
            //'show_in_menu'          => 'admin.php?page=seomonitor-setting', //not working??
            'show_in_menu'          => false,
            'show_in_admin_bar'     => false,
            'capability_type' 		=> 'post',
            'exclude_from_search'	=> true, //exclude posts with this post type from front end search results
            'publicly_queryable'    => false,
	        'hierarchical' 			=> false,
	        'rewrite' 				=> true,
	        'supports' 				=> apply_filters( 'seo_monitor_keywords_supports', false ),
	        'has_archive'			=> false,
	    );

	    register_post_type( 'seomonitor_keyword' , apply_filters( 'seo_monitor_keyword_post_type_args', $keyword_args ) );

	    /*
	    register_taxonomy( 'seomonitor_keyword_group', array( 'seomonitor_keyword' ),
	                    array(
	                        'hierarchical' 		=> true,
	                        'label' 			=> __( 'Keyword group' , 'seo-monitor' ),
	                        'singular_label' 	=> __( 'Keyword group' , 'seo-monitor' ),
	                        //'rewrite' 			=> array( 'slug' => 'group' )
						)
		);
		*/
	}

	/**
	*
	* @since 1.0
	*/
	public function set_id( $value ) {
		$this->id = $value;
	}

	/**
	*
	* @since 1.0
	*/
	public function set_rank( $value ) {
		$this->rank = $value;
	}

	/**
	*
	* @since 1.0
	*/
	public function set_previous( $value ) {
		$this->previous = $value;
	}

	/**
	*
	* @since 1.0
	*/
	public function set_top_rank( $value ) {
		$this->top_rank = $value;
	}

	/**
	*
	* @since 1.0
	*/
	public function set_last_check( $value ) {
		$this->last_check = $value;
	}

	/**
	*
	* @since 1.0
	*/
	public function set_keyword( $value ) {
		$this->keyword = trim( $value );
	}

	/**
	*
	* @since 1.0
	*/
	public function set_site_id( $value ) {
		$this->site_id = $value;
	}

	/**
	*
	* @since 1.0
	*/
	public function set_search_engine_id( $value ) {
		$this->search_engine_id = $value;
	}

	/**
	*
	* @since 1.0
	*/
	public function get_id() {
		return $this->id;
	}

	/**
	*
	* @since 1.0
	*/
	public function get_rank() {
		return $this->rank;
	}

	/**
	*
	* @since 1.0
	*/
	public function get_previous() {
		return $this->previous;
	}

	/**
	*
	* @since 1.0
	*/
	public function get_top_rank() {
		return $this->top_rank;
	}

	/**
	*
	* @since 1.0
	*/
	public function get_keyword() {
		return $this->keyword;
	}

	/**
	*
	* @since 1.0
	*/
	public function get_site_id() {
		return $this->site_id;
	}

	/**
	*
	* @since 1.0
	*/
	public function get_search_engine_id() {
		return $this->search_engine_id;
	}

	/**
	*
	* @since 1.0
	*/
	public function get_last_check() {
		return $this->last_check;
	}
}