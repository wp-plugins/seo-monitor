<?php
/**
 * Customer Rankings Table Class
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
 * Seo_Monitor_Rankings_List_Table Class
 *
 * Renders the Rankings table
 *
 * @since 1.0
 */
class Seo_Monitor_Rankings_List_Table extends WP_List_Table {

    /**
     * Number of items per page
     *
     * @var int
     * @since 1.0
     */
    public $per_page = 15;

    /**
     * Number of Rankings found
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
            'singular'  => __( 'Rank', 'seo_monitor' ),     //singular name of the listed records
            'plural'    => __( 'Rankings', 'seo_monitor' ),    //plural name of the listed records
            'ajax'      => true           //does this table support ajax?
        ) );

        $this->process_bulk_action();
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
     * Retrieves the keyword_id query string
     *
     * @access public
     * @since 1.0
     * @return mixed string If keyword_id is present, false otherwise
     */
    public function get_keyword_id() {
        return ! empty( $_GET['keyword_id'] ) ? urldecode( trim( $_GET['keyword_id'] ) ) : false;
    }

    /**
     * This function renders most of the columns in the list table.
     *
     * @access public
     * @since 1.0
     *
     * @param array $item Contains all the data of the rankings
     * @param string $column_name The name of the column
     *
     * @return string Column Name
     */
    public function column_default( $item, $column_name ) {

        switch( $column_name ) {
            case 'rank_link':
                $value   = '<a href="' . esc_url( $item[$column_name] ) . '">' . $item[$column_name] . '</a>';
                break;
            default:
                $value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : null;
        }

        return apply_filters( 'seo_monitor_rankings_column_' . $column_name, $value, $item['id'] );
    }

    /**
    *
    * @since 1.0
    * @return string
    */
    function column_cb( $item ) {

        return sprintf(
            '<input type="checkbox" name="rank-id[]" value="%s" />',
            $item['id']                //The value of the checkbox should be the record's id
        );
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
            'cb'            => '<input type="checkbox" />', //Render a checkbox instead of text
            'rank_link'     => __( 'Ranking URL', 'seo-monitor' ),
            'rank'          => __( 'Rank', 'seo-monitor' ),
            'time'          => __( 'Date', 'seo-monitor' ),
        );

        return apply_filters( 'seo_monitor_rankings_columns', $columns );
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
            'rank_link'     => array( 'rank_link', false ),
            'rank'          => array( 'rank', false ),
            'time'          => array( 'time', true ),
        );

        return apply_filters( 'seo_monitor_rankings_sortable_columns', $sortable_columns );
    }

    /**
     * Outputs the bulk actions
     *
     * @access public
     * @since 1.0
     * @return void
     */
    function get_bulk_actions() {

        $actions = array(
            'delete'    => __( 'Delete', 'seo-monitor' )
        );

        return apply_filters( 'seo_monitor_rankings_table_bulk_actions', $actions );
    }

    /**
     * Process the bulk actions
     *
     * @access public
     * @since 1.0
     * @return void
     */
    function process_bulk_action() {

        $ids    = isset( $_GET['rank-id'] ) ? $_GET['rank-id'] : false;

        if ( ! is_array( $ids ) ) {
            $ids = array( $ids );
        }

        //Detect when a bulk action is being triggered...
        if( 'delete' === $this->current_action() ) {
            foreach( $ids as $id ) {

                $seomonitor_rank = new Seo_Monitor_Rank( $id );
                $seomonitor_rank->delete_rank();

                do_action( 'seo_monitor_rank_table_do_bulk_action', $id, $this->current_action() );
            }
        }
    }

    /**
     * Build all the ranking data
     *
     * @access public
     * @since 1.0
     * @global object $wpdb Used to query the database using the WordPress
     *   Database API
     * @return array $data All the data for keyword listing
     */
    public function ranking_data() {
        $paged      = $this->get_paged();
        $offset     = $this->per_page * ( $paged - 1 );
        $search     = $this->get_search();
        $keyword_id = $this->get_keyword_id();
        $order      = isset( $_GET['order'] )   ? sanitize_text_field( $_GET['order'] )   : 'DESC';
        $orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';

        $args    = array(
            'number'        => -1,
            //'meta_query'    => $this->get_meta_query(),
            'order'         => $order,
            'orderby'       => $orderby,
            'keyword'       => $keyword_id
        );

        $seo_monitor_rank = new Seo_Monitor_Rank;

        $rankings = $seo_monitor_rank->get_rankings( $args );

        if ( $rankings ) {

            foreach ( $rankings as $rank ) {

                $data[] = array(
                    'id'            => $rank->id,
                    'rank'          => $rank->rank,
                    'rank_link'     => $rank->rank_link,
                    'time'          => $rank->time,
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
     * @uses Seo_Monitor_Keyword_Ranking_Table::get_columns()
     * @uses WP_List_Table::get_sortable_columns()
     * @uses Seo_Monitor_Keyword_Ranking_Table::get_pagenum()
     * @uses Seo_Monitor_Keyword_Ranking_Table::get_total_customers()
     * @return void
     */
    public function prepare_items() {

        $columns    = $this->get_columns();
        $hidden     = array();
        $sortable   = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $this->items = $this->ranking_data();

        $this->set_pagination_args( array(
            'total_items' => $this->total,
            'per_page'    => $this->per_page,
            'total_pages' => ceil( $this->total / $this->per_page )
        ) );
    }
}