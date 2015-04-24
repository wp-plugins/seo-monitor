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
class Seo_Monitor_Keyword_List_Table extends WP_List_Table {

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
            'singular'  => __( 'keyword', 'seo_monitor' ),     //singular name of the listed records
            'plural'    => __( 'keywords', 'seo_monitor' ),    //plural name of the listed records
            'ajax'      => true           //does this table support ajax?
        ) );

        add_action( 'seo_monitor_keyword_filter_actions', array( $this, 'sites_filter' ) );
        add_action( 'seo_monitor_keyword_filter_actions', array( $this, 'engines_filter' ) );
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
     * Retrieves the ID of the site we're filtering keywords by
     *
     * @access public
     * @since 1.0
     * @return int Download ID
     */
    public function get_filtered_site() {
        return ! empty( $_GET['site'] ) ? absint( $_GET['site'] ) : false;
    }

    /**
     * Retrieves the ID of the engine we're filtering keywords by
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
        $this->seomonitor_keyword_filters( $which );
    }


    /**
     * Renders the Keywords filter drop down
     *
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     * @since 1.0
     * @return void
    */
    private function seomonitor_keyword_filters( $which ) {

        if( 'top' === $which ) {
            do_action( 'seo_monitor_keyword_filter_actions' );
            submit_button( __( 'Filter', 'seo-monitor' ), 'secondary', 'submit', false );
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

        echo '<select name="site" id="seo-monitor-site-filter">';
        echo '<option value="0">' . __( 'All', 'seo-monitor' ) . '</option>';

        if ( $sites ) {
            foreach ( $sites as $site ) {
                echo '<option value="' . $site . '"' . selected( $site, $this->get_filtered_site() ) . '>' .
                     esc_html( rwmb_meta( 'seomonitor_site_name', '', $site ) ) . '</option>';
            }
        }

        echo '</select>';
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
            'orderby'                   => 'meta_value',
            'meta_key'                  => 'seomonitor_se_search_engine',
            'order'                     => 'ASC',
            'fields'                    => 'ids',
            'update_post_meta_cache'    => false,
            'update_post_term_cache'    => false
        ) );

        echo '<select name="engine" id="seo-monitor-engine-filter">';
        echo '<option value="0">' . __( 'All', 'seo-monitor' ) . '</option>';

        if ( $engines ) {
            foreach ( $engines as $engine ) {
                echo '<option value="' . $engine . '"' . selected( $engine, $this->get_filtered_engine() ) . '>' .
                     esc_html( get_the_title( $engine ) ) . '</option>';
            }
        }

        echo '</select>';
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
            case 'keyword':
                $link = add_query_arg(
                            array(
                                'page'          => 'seomonitor-rankings',
                                'keyword_id'    => $item['id']
                            ),
                            admin_url('admin.php')
                        );
                $value = '<a class="kwlink" href="' . $link . '">' . $item[ $column_name ] . '</a>';
                break;
            case 'location':
                $value  = rwmb_meta( 'seomonitor_site_country', '', $item['website'] );
                if( strlen( $value ) == 0 ) {
                    $value = __( 'All', 'seo-monitor' );
                }
                break;
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

        return apply_filters( 'seo_monitor_keyword_column_' . $column_name, $value, $item['id'] );
    }

    public function column_rank( $item ) {

        if( $item['previous'] != 0 && $item['rank'] > $item['previous'] ) {
            $class = 'rank_decrease';
        } elseif ( $item['rank'] < $item['previous'] || ( $item['rank'] > 0 && $item['previous'] == 0 ) ) {
            $class = 'rank_increase';
        } else {
            $class = 'rank_same';
        }

        return '<span class="' . $class . '">' . $item['rank'] . '</span>';
    }

    public function column_update( $item ) {

        $today      = new DateTime( 'now' );
        $last_check = new DateTime( $item['last_check'] );

        if( $last_check->format('Y-m-d') < $today->format('Y-m-d') ) {
            $class = '';
        } else {
            $class = 'seo_monitor_updated seo_monitor_updated-' . $item['id'];
        }

        $column_update = sprintf( '<div class="seo_monitor_spinner seo_monitor_spinner-%s" style="display: none;"></div>
                                        <a id="seo_monitor_kw-%s" class="seomonitor_update_row" href="#">
                                            <div class="%s dashicons dashicons-yes"></div>
                                        </a>',
                      $item['id'],
                      $item['id'],
                      $class
        );

        return $column_update;
        //return 'test';
    }

    public function column_ranking_url( $item ) {

        global $wpdb;

        $wpdb->seomonitor_ranks     = $wpdb->prefix . 'seomonitor_ranks';

        //ORDER by is important to get latest rank_link
        $sqlQuery   = "SELECT rank_link FROM $wpdb->seomonitor_ranks WHERE keyword_id = " . $item['id'] . " ORDER BY time DESC";
        $data       = $wpdb->get_row( $sqlQuery );

        if( isset( $data->rank_link ) ) {
            $ranking_url = '<a href="' . $data->rank_link . '">' . $data->rank_link . '</a>';
        } else {
            $ranking_url = '';
        }

        return $ranking_url;
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
            'update'        => __( 'Update', 'seo-monitor' ),
            'keyword'       => __( 'Keyword', 'seo-monitor' ),
            'main_url'      => __( 'Website', 'seo-monitor' ),
            'ranking_url'   => __( 'Ranking URL', 'seo-monitor' ),
            'engine'        => __( 'Engine', 'seo-monitor' ),
            'location'      => __( 'Location', 'seo-monitor' ),
            'rank'          => __( 'Rank', 'seo-monitor' ),
            'previous'      => __( 'Previous', 'seo-monitor' ),
            'top_rank'      => __( 'Top', 'seo-monitor' ),
            'last_check'    => __( 'Last Check', 'seo-monitor' ),
        );

        return apply_filters( 'seo_monitor_keyword_columns', $columns );
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
            'main_url'      => array( 'main_url', false ),
            //'ranking_url'   => array( 'ranking_url', false ), //TODO
            'engine'        => array( 'engine', false ),
            'location'      => array( 'location', false ),
            'rank'          => array( 'rank', false ),
            'previous'      => array( 'previous', false ),
            'top_rank'      => array( 'top_rank', false ),
            'last_check'    => array( 'last_check', false ),
        );
        return $sortable_columns;
    }

    /**
     * Build all the keywords data
     *
     * @access public
     * @since 1.0
     * @global object $wpdb Used to query the database using the WordPress
     *   Database API
     * @return array $data All the data for keyword listing
     */
    public function keywords_data() {
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
                    'engine'        => $keyword->engine,
                    'rank'          => $keyword->rank,
                    'previous'      => $keyword->previous,
                    'top_rank'      => $keyword->top_rank,
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

        $this->items = $this->keywords_data();

        $this->set_pagination_args( array(
            'total_items' => $this->total,
            'per_page'    => $this->per_page,
            'total_pages' => ceil( $this->total / $this->per_page )
        ) );
    }
}