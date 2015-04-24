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
 * @subpackage Seo_Monitor/admin/keyword
 * @author     To Be On The Web <info@tobeontheweb.nl>
 */
class Seo_Monitor_Rank {

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

	/**
	* Properties
	*/
	private $id;

	/**
	*
	* @since 1.0
	*/
	private $keyword_id;

	/**
	*
	* @since 1.0
	*/
	private $rank;

	/**
	 *
	 * @since 1.0
	 */
	private $rank_link;

	/**
	 *
	 * @since 1.0
	 */
	private $time;

	/**
	* Seo_Monitor_Keyword object
	* @since 1.0
	*/
	//private $keyword_obj;

	/**
	* Seo_Monitor_Proxy object
	* @since 1.0
	*/
	private $proxy;

	/**
	 *
	 * @since 1.0
	 */
	public function __construct(
						$id = null,
						$keyword_id = null,
						$rank = null,
						$rank_link = null,
						$time = null
					) {

		global $wpdb;

		$this->table_name  	= $wpdb->prefix . 'seomonitor_ranks';
		$this->cache_group 	= 'rankings';

		$this->set_id( $id );
		$this->set_keyword_id( $keyword_id );
		$this->rank 		= $rank;
		$this->rank_link 	= $rank_link;
		$this->time 		= $time;

		$this->set_proxy();
	}

	/**
	* Set id property
	* @since 1.0
	*/
	public function set_id( $value ) {
		$this->id = $value;
	}

	/**
	* Get id property
	* @since 1.0
	*/
	public function get_id() {
		return $this->id;
	}

	/**
	* Set proxy property
	* @since 1.0
	*/
    public function set_proxy() {

        if( class_exists( 'Seo_Monitor_Proxy' ) ) {
            $this->proxy = new Seo_Monitor_Proxy();
        } else {
            $this->proxy = null;
        }
    }

	/**
	 *
	 * @since 1.0
	 */
	public function set_keyword_id( $value ) {
		$this->keyword_id = $value;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function get_keyword_id() {
		return $this->keyword_id;
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
			'keyword_id'    => '%d',
			'rank'          => '%d',
			'rank_link'     => '%s',
			'time'    		=> '%s',
		);
	}

	/**
	* Delete single ranking
	*
	* @access public
	* @return affected rows (must be one!)
	* @since 1.0
	*/
	public function delete_rank() {

		global $wpdb;

		$rank_data = array(
			'id' 		=> $this->get_id()
		);

		$affected_rows = $wpdb->delete( $this->table_name, $rank_data );

		return $affected_rows;
	}

	/**
	* Delete all rankings which belongs to keyword
	*
	* @access public
	* @return affected rows
	* @since 1.0
	*/
	public function delete_rankings() {

		global $wpdb;

		$keyword_id = $this->get_keyword_id();

		if( empty( $keyword_id ) ) {
			return new WP_Error( 'seo-monitor-rank-delete_rankings', __( 'parameter keyword_id has no value', 'seo-monitor' ) );
		}

		$rank_data = array(
			'keyword_id' 		=> $keyword_id
		);

		$affected_rows = $wpdb->delete( $this->table_name, $rank_data );

		//echo $wpdb->last_query;
		//exit();

		return $affected_rows;
	}

	/**
	 * Retrieve rankings from the database
	 *
	 * @access public
	 * @since 1.0
	 * @global WPDB $wpdb
	*/
	public function get_rankings( $args = array() ) {

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

		// specific rankings
		if( ! empty( $args['id'] ) ) {

			if( is_array( $args['id'] ) ) {
				$ids = implode( ',', $args['id'] );
			} else {
				$ids = intval( $args['id'] );
			}

			$where .= "WHERE `id` IN( {$ids} ) ";
		}

		// specific keywords by id
		if( ! empty( $args['keyword'] ) ) {

			if( is_array( $args['keyword'] ) ) {
				$keywords = "'" . implode( "', '", $args['keyword'] ) . "'";
			} else {
				$keywords = "'" . $args['keyword'] . "'";
			}

			if( ! empty( $where ) ) {
				$where .= " AND `keyword_id` IN( {$keywords} ) ";
			} else {
				$where .= "WHERE `keyword_id` IN( {$keywords} ) ";
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

		$cache_key 			= md5( 'seo_monitor_rankings_' . serialize( $args ) );

		$rankings = wp_cache_get( $cache_key, $this->cache_group );

		if( $rankings === false ) {
			$rankings = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->table_name $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d",
											absint( $args['offset'] ), absint( $args['number'] ) ) );
			wp_cache_set( $cache_key, $rankings, $this->cache_group, 3600 );
		}

		return $rankings;
	}

    /**
    * Properly set the Custom-Post-Type submenu entry as "active"
    * @param string - $parent_file
    * @since 1.0
    */
	public function fix_admin_parent_file( $parent_file ) {
	    global $submenu_file, $current_screen;

	    //print_r($current_screen);
	    //exit();

	    //wat is current_screen?

	    if( $current_screen->post_type == 'seomonitor_rank' ) {

	        $submenu_file 	= 'edit.php?post_type=seomonitor_rank';
	        $parent_file 	= 'seomonitor-keywords';
	    }

	    return $parent_file;
	}

	/**
    *
    * function to update all rankings
    * this function is called from cronjob schedule
    */
    public function update_all_rankings() {

    	$args = array(
    				'number' => -1
    		);

    	$seo_monitor_keyword = new Seo_Monitor_Keyword();
		$keywords = $seo_monitor_keyword->get_keywords( $args );
		$today 	  = new DateTime( 'now' );

		if( $keywords ) {
	    	foreach ( $keywords as $keyword ) {

	    		$last_check = new DateTime( $keyword->last_check );

	    		if( $last_check->format('Y-m-d') < $today->format('Y-m-d') ) {
	    			$this->update_keyword_rank( $keyword );
	    		}
	    	}
	    }
    }


	/**
	* Returns false if errors, or the number of rows affected if successful.
	* @param ranking - array
	* @since 1.0
	*/
	public function update_rank( $ranking ) {

		global $wpdb;

		$today 	  	= new DateTime( 'now' );

		$last_check = isset( $ranking['last_check'] ) ? $ranking['last_check'] : $today->format('Y-m-d');
		$rank 		= isset( $ranking['rank'] ) ? $ranking['rank'] : 0;

    	$wpdb->seomonitor_ranks 	= $wpdb->prefix . 'seomonitor_ranks';

		$data 	= array(
					'keyword_id'	=> $ranking['id'],
					'rank' 			=> $rank,
					'rank_link'		=> isset( $ranking['ranking_url'] ) ? $ranking['ranking_url'] : '',
					'time' 			=> $last_check
					);

		$rank_id = $wpdb->insert( $this->table_name, $data );

		$previous = isset( $ranking['previous'] ) ? $ranking['previous'] : 0;
		$top_rank = isset( $ranking['top_rank'] ) ? $ranking['top_rank'] : 0;

		$seo_monitor_keyword = new Seo_Monitor_Keyword( $ranking['id'], null, $rank, $previous, $top_rank, null, null, $last_check );
		$seo_monitor_keyword->edit_keyword();

		return $rank_id;
    }

	/**
	* Ajax Callback function
	* @since 1.0
	*/
	public function update_keyword_rank_callback() {

		//TODO: add nounce
        //$valid_req = check_ajax_referer( 'seo-monitor-update-keyword', false, false );
        //if ( false == $valid_req ) {
        //    wp_die( '-1' );
        //}

		$item_id = $_POST['itm'];

		$seo_monitor_keyword = new Seo_Monitor_Keyword( $item_id );

		$args = array(
				'id' 	=> $item_id
			);

		$ranking  = array();
		$keywords = $seo_monitor_keyword->get_keywords( $args );

		if( $keywords ) {
	    	foreach ( $keywords as $keyword ) {
				$ranking = $this->update_keyword_rank( $keyword );
			}
		}

		// Or use PHPUNIT Output Test features
		//ob_start();
		$ranking_data = json_encode( $ranking );
		//$ranking_data = ob_get_contents();
		//ob_end_clean();

		wp_die( $ranking_data ); // this is required to terminate immediately and return a proper response
	}

	/**
	*
	*
	* @since 1.0
	*/
	public function seomonitor_update_keyword_rankings() {

		if( isset( $_GET['keyword'] ) ) {
			$this->keyword_id = esc_html( $_GET['keyword'] );

			$seo_monitor_keyword = new Seo_Monitor_Keyword( $this->keyword_id );

			$args = array(
				'id' 	=> $this->keyword_id
			);

			$keyword = $seo_monitor_keyword->get_keywords( $args );
			$this->update_keyword_rank( $keyword );

			wp_redirect( admin_url() . '/admin.php?page=seomonitor-keywords' );
			exit();
		}
	}

	/**
	* @param $keyword - Keyword record
	* @return ranking array or WP_Error
	* @since 1.0
	*/
	public function update_keyword_rank( $keyword ) {

		if( is_object( $keyword ) ) {
			$seo_monitor_search_engine = new Seo_Monitor_Search_Engine( $keyword->engine, $this->proxy );
		} else {
			return new WP_Error( 'seo-monitor-rank-update_keyword_rank', sprintf( __( 'Parameter %s is not an object', 'seo-monitor' ), serialize( $keyword ) ) );
		}

		$ranking = $seo_monitor_search_engine->get_ranking( $keyword );

		if( ! is_wp_error( $ranking ) ) {

			if( $keyword->top_rank < $ranking['rank'] ) {
				$ranking['top_rank'] = $ranking['rank'];
			}

			$ranking['previous'] 	= $keyword->rank;
			$ranking['id'] 			= $keyword->id;
			$ranking['last_check'] 	= date('Y-m-d H:i:s');

			$this->update_rank( $ranking );
		}

		return $ranking;
	}

	/**
	*
	* display all rankings
	* @since 1.0
	*/
	public static function display_reports() {

		$reports_list_table = new Seo_Monitor_Reports_List_Table();
		$reports_list_table->prepare_items();
		?>
		<div class="wrap">
			<h2><?php _e( 'Keywords Rankings', 'seo-monitor' ); ?></h2>
			<?php do_action( 'seo_monitor_reports_table_top' ); ?>
			<form id="seomonitor-reports-filter" method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
				<input type="hidden" name="page" value="seomonitor-reports" />
			<?php

			$reports_list_table->search_box( __( 'Search rankings', 'seo-monitor' ), 'seo-monitor-rankings' );
			$reports_list_table->display();
			?>
			</form>
			<?php do_action( 'seo_monitor_reports_table_bottom' ); ?>
		</div>
		<?php
	}

	/**
	*
	* display all rankings
	* @since 1.0
	*/
	public static function display_all_rankings() {

		//To create sample data
		//remove in production
		//create_ranking_sample_data();

		if( isset( $_GET['keyword_id'] )) {
			$keyword_id = sanitize_text_field( $_GET['keyword_id'] );
		}

		$keyword = new Seo_Monitor_Keyword( $keyword_id );
		$keyword = $keyword->get_keywords( array( 'id' => $keyword_id ) );

		if( isset( $keyword[0]->keyword ) ) {
			$keyword = $keyword[0]->keyword;
		} else {
			$keyword = __( 'Not found', 'seo-monitor' );
		}

		$ranking_list_table = new Seo_Monitor_Rankings_List_Table();
		$ranking_list_table->prepare_items();
		?>
		<div class="wrap">
			<h2><?php echo sprintf( __( 'Rankings keyword: %s', 'seo-monitor' ), $keyword ); ?></h2>
			<?php do_action( 'seo_monitor_rankings_table_top' ); ?>
			<form id="seomonitor-keywords-filter" method="get" action="<?php echo admin_url( 'admin.php?&page=seomonitor-rankings' ); ?>">
				<input type="hidden" name="page" value="seomonitor-rankings" />
				<input type="hidden" name="page-type" value="seomonitor_rank"/>
				<input type="hidden" name="keyword_id" value="<?php echo $keyword_id; ?>" />
			<?php

			$ranking_list_table->search_box( __( 'Search rankings', 'seo-monitor' ), 'seo-monitor-rankings' );
			$ranking_list_table->display();
			?>
			</form>
			<?php do_action( 'seo_monitor_rankings_table_bottom' ); ?>
		</div>
		<?php
	}
}