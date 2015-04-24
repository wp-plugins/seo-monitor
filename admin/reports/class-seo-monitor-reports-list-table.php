<?php
/**
 * Customer Reports Table Class
 *
 * @package     Seo Monitor
 * @subpackage  Admin/partials
 * @copyright   Copyright (c) 2015, To Be On The Web
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Seo_Monitor_Keyword_List_Table Class
 *
 * Renders the Keywords table
 *
 * @since 1.0
 */
class Seo_Monitor_Reports_List_Table extends WP_List_Table {

    /**
     * Number of items per page
     *
     * @var int
     * @since 1.0
     */
    public $per_page = 15;

    /**
     * Number of Keywords found
     *
     * @var int
     * @since 1.0
     */
    public $count = 0;

    /**
     * Total Keywords
     *
     * @var int
     * @since 1.0
     */
    public $total = 0;

    public function __construct() {
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'keyword rank report', 'seo_monitor' ),     //singular name of the listed records
            'plural'    => __( 'keywords rankings report', 'seo_monitor' ),    //plural name of the listed records
            'ajax'      => true           //does this table support ajax?
        ) );

        add_action( 'seo_monitor_report_filter_actions', array( $this, 'sites_filter' ) );
        add_action( 'seo_monitor_report_filter_actions', array( $this, 'engines_filter' ) );
    }

    /**
     * Show the search field
     *
     * @since 1.0
     * @access public
     *
     * @param string $text Label for the search box
     * @param string $input_id ID of the search box
     *
     * @return void
     */
    public function search_box( $text, $input_id ) {
        $input_id = $input_id . '-search-input';

        if ( ! empty( $_REQUEST['orderby'] ) )
            echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
        if ( ! empty( $_REQUEST['order'] ) )
            echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button( $text, 'button', false, false, array('ID' => 'search-submit') ); ?>
        </p>
        <?php
    }

    /**
     * Retrieve the current page number
     *
     * @access public
     * @since 1.0
     * @return int Current page number
     */
    public function get_paged() {
        return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
    }

    /**
     * Retrieves the search query string
     *
     * @access public
     * @since 1.0
     * @return mixed string If search is present, false otherwise
     */
    public function get_search() {
        return ! empty( $_GET['s'] ) ? urldecode( trim( $_GET['s'] ) ) : false;
    }

    /**
     * Retrieves the ID of the site we're filtering reports by
     *
     * @access public
     * @since 1.0
     * @return int Download ID
     */
    public function get_filtered_site() {
        return ! empty( $_GET['site'] ) ? absint( $_GET['site'] ) : false;
    }

    /**
     * Retrieves the ID of the engine we're filtering reports by
     *
     * @access public
     * @since 1.0
     * @return int Download ID
     */
    public function get_filtered_engine() {
        return ! empty( $_GET['engine'] ) ? absint( $_GET['engine'] ) : false;
    }

    /**
     * Outputs the keyword filters
     *
     * @access public
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     * @since 1.0
     * @return void
     */
    function extra_tablenav( $which = '' ) {
        $this->seomonitor_reports_filters( $which );
    }


    /**
     * Renders the Reports filter drop down
     *
     * @since 1.0
     * @return void
    */
    private function seomonitor_reports_filters( $which ) {

        if( 'top' === $which ) {
            do_action( 'seo_monitor_report_filter_actions' );
            submit_button( __( 'Apply', 'seo-monitor' ), 'secondary', 'submit', false );
        }
    }

    /**
     * Sets up the sites filter
     *
     * @access public
     * @since 1.0
     * @return void
     */
    public function sites_filter() {
        $sites = get_posts( array(
            'post_type'                 => 'seomonitor_site',
            'post_status'               => 'publish',
            'posts_per_page'            => -1,
            'orderby'                   => 'meta_value',
            'meta_key'                  => 'seomonitor_site_name',
            'order'                     => 'ASC',
            'fields'                    => 'ids',
            'update_post_meta_cache'    => false,
            'update_post_term_cache'    => false
        ) );

        if ( $sites ) {
            echo '<select name="site" id="seo-monitor-site-filter">';
                echo '<option value="0">' . __( 'All', 'seo-monitor' ) . '</option>';
                foreach ( $sites as $site ) {
                    echo '<option value="' . $site . '"' . selected( $site, $this->get_filtered_site() ) . '>' .
                         esc_html( rwmb_meta( 'seomonitor_site_name', '', $site ) ) . '</option>';
                }
            echo '</select>';
        }
    }

    /**
     * Sets up the engines filter
     *
     * @access public
     * @since 1.0
     * @return void
     */
    public function engines_filter() {
        $engines = get_posts( array(
            'post_type'                 => 'seomonitor_se',
            'post_status'               => 'publish',
            'posts_per_page'            => -1,
            'orderby'                   => 'seomonitor_se_search_engine',
            'order'                     => 'ASC',
            'fields'                    => 'ids',
            'update_post_meta_cache'    => false,
            'update_post_term_cache'    => false
        ) );

        if ( $engines ) {
            echo '<select name="engine" id="seo-monitor-engine-filter">';
                echo '<option value="0">' . __( 'All', 'seo-monitor' ) . '</option>';
                foreach ( $engines as $engine ) {
                    echo '<option value="' . $engine . '"' . selected( $engine, $this->get_filtered_engine() ) . '>' .
                         esc_html( get_the_title( $engine ) ) . '</option>';
                }
            echo '</select>';
        }
    }


    /**
     * This function renders most of the columns in the list table.
     *
     * @access public
     * @since 1.0
     *
     * @param array $item Contains all the data of the Keywords
     * @param string $column_name The name of the column
     *
     * @return string Column Name
     */
    public function column_default( $item, $column_name ) {

        switch( $column_name ) {
            case 'main_url':
                $website = rwmb_meta( 'seomonitor_site_main_url', '', $item['website'] );
                $value   = '<a href="' . esc_url( $website ) . '">' . $website . '</a>';
                break;
            case 'engine':
                $value = rwmb_meta( 'seomonitor_se_search_engine', '', $item['engine'] );
                break;
            default:
                $value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : null;
        }

        return apply_filters( 'seo_monitor_report_column_' . $column_name, $value, $item['id'] );
    }

    public function column_report( $item ) {

        $args = array (
            'keyword'   => $item['id'],
            'orderby'   => 'time',
            'order'     => 'ASC'
        );

        $seo_monitor_rank = new Seo_Monitor_Rank();
        $rank_data = $seo_monitor_rank->get_rankings( $args );

        $rank_report_data = array();

        if( !empty( $rank_data ) ) {
            foreach ( $rank_data as $rank ) {

                $month  = date( "n", strtotime($rank->time) );
                $day    = date( "j", strtotime($rank->time) );
                $year   = date( "Y", strtotime($rank->time) );

                $date = mktime( 0, 0, 0, $month, $day, $year ) * 1000;

                if( $rank->rank == 0 ) {
                    $rank->rank = 110; // value in report for no rank
                }
                $rank_report_data[] = array( $date, $rank->rank );
            }
        }

        $website    = rwmb_meta( 'seomonitor_site_main_url', '', $item['website'] );
        $engine     = rwmb_meta( 'seomonitor_se_search_engine', '', $item['engine'] );

        $data = array(
            __( 'Rank', 'seo-monitor' )     => $rank_report_data
        );

        // start our own output buffer
        ob_start();
        ?>
        <div id="seo-monitor-dashboard-widgets-wrap">
            <div class="metabox-holder" style="padding-top: 0;">
                <div class="postbox">
                    <h3><span><?php echo $website . '(' . $engine . ')' ?></span></h3>

                    <div class="inside">
                        <?php
                        $graph = new Seo_Monitor_Graph( $data );
                        $graph->set( 'x_mode', 'time' );
                        //$graph->set( 'multiple_y_axes', true );
                        $graph->display();
                        ?>

                    </div>
                </div>
            </div>
        </div>
        <?php
        // get output buffer contents and end our own buffer
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Retrieve the table columns
     *
     * @access public
     * @since 1.0
     * @return array $columns Array of all the list table columns
     */
    public function get_columns() {
        $columns = array(
            'keyword'       => __( 'Keyword', 'seo-monitor' ),
            'report'        => __( 'Report', 'seo-monitor' ),
        );

        return apply_filters( 'seo_monitor_report_columns', $columns );
    }

    /**
     * Get the sortable columns
     *
     * @access public
     * @since 1.0
     * @return array Array of all the sortable columns
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'keyword'       => array( 'keyword', true ),     //true means it's already sorted
        );
        return $sortable_columns;
    }

    /**
     * Build all the Report data
     *
     * @access public
     * @since 1.0
     * @global object $wpdb Used to query the database using the WordPress
     *   Database API
     * @return array $data All the data for keyword listing
     */
    public function report_data() {
        $paged      = $this->get_paged();
        $offset     = $this->per_page * ( $paged - 1 );
        $search     = $this->get_search();
        $site       = empty( $_GET['s'] ) ? $this->get_filtered_site() : null;
        $engine     = empty( $_GET['s'] ) ? $this->get_filtered_engine() : null;
        $order      = isset( $_GET['order'] )   ? sanitize_text_field( $_GET['order'] )   : 'DESC';
        $orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';

        $args    = array(
            'number'        => -1,
            //'meta_query'    => $this->get_meta_query(),
            'order'         => $order,
            'orderby'       => $orderby,
            'site'          => $site,
            'engine'        => $engine,
            'keyword'       => $search,
        );

        $seo_monitor_keyword = new Seo_Monitor_Keyword;

        $keywords = $seo_monitor_keyword->get_keywords( $args );

        if ( $keywords ) {

            foreach ( $keywords as $keyword ) {

                $data[] = array(
                    'id'            => $keyword->id,
                    'keyword'       => $keyword->keyword,
                    'website'       => $keyword->project_id,
                    'rank'          => $keyword->rank,
                    'previous'      => $keyword->previous,
                    'top_rank'      => $keyword->top_rank,
                    'engine'        => $keyword->engine,
                    'last_check'    => $keyword->last_check,
                );
            }
        } else {
            $data = array();
        }

        $this->total    = count( $data );
        $data           = array_slice( $data, $offset, $this->per_page );

        return $data;
    }

    /**
     * Setup the final data for the table
     *
     * @access public
     * @since 1.0
     * @uses Seo_Monitor_Keyword_List_Table::get_columns()
     * @uses WP_List_Table::get_sortable_columns()
     * @uses Seo_Monitor_Keyword_List_Table::get_pagenum()
     * @uses Seo_Monitor_Keyword_List_Table::get_total_customers()
     * @return void
     */
    public function prepare_items() {

        $columns    = $this->get_columns();
        $hidden     = array();
        $sortable   = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $this->items = $this->report_data();

        $this->set_pagination_args( array(
            'total_items' => $this->total,
            'per_page'    => $this->per_page,
            'total_pages' => ceil( $this->total / $this->per_page )
        ) );
    }
}