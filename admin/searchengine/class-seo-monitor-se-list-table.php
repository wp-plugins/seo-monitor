<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Seo_Monitor_Se_List_Table Class
 *
 * Renders the Sites table
 *
 * @since 1.0
 */
class Seo_Monitor_Se_List_Table extends WP_List_Table {

    /**
     * Number of items per page
     *
     * @var int
     * @since 1.0
     */
    public $per_page = 15;

    /**
     * Number of Sites found
     *
     * @var int
     * @since 1.0
     */
    public $count = 0;

    /**
     * Total Sites
     *
     * @var int
     * @since 1.0
     */
    public $total = 0;

    public function __construct() {
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'search engine', 'seo-monitor' ),     //singular name of the listed records
            'plural'    => __( 'search engines', 'seo-monitor' ),             //plural name of the listed records
            'ajax'      => false                //does this table support ajax?
        ) );

        $this->process_bulk_action();

        add_action( 'seo_monitor_se_filter_actions', array( $this, 'groups_filter' ) );
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
     * Retrieves the ID of the group we're filtering sites by
     *
     * @access public
     * @since 1.0
     * @return int group ID
     */
    public function get_filtered_group() {
        return ! empty( $_GET['group'] ) ? absint( $_GET['group'] ) : false;
    }

    function column_default( $item, $column_name ){
        switch( $column_name ) {
            case 'url':
                $value  = rwmb_meta( 'seomonitor_se_url', '', $item['id'] );
                break;
            case 'group':
                $groups = rwmb_meta( '', 'type=taxonomy&taxonomy=seomonitor_se_group', $item['id'] );
                $value  = '';
                foreach ( $groups as $groups ) {
                    $value .= $groups->name . '&nbsp;';
                }
                break;
            default:
                $value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : null;
        }

        return apply_filters( 'seo_monitor_list_column_' . $column_name, $value, $item['id'] );
    }


    function column_name( $item ) {

        //Build row actions

        $title      = get_the_title( $item['id'] );
        $view_url   = get_post_permalink( $item['id'] );
        $edit_url   = get_edit_post_link( $item['id'] );
        $delete_url = get_delete_post_link( $item['id'] );

        $actions = array(
            //'edit'      => sprintf('<a href="?page=%s&action=%s&keyword=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
            'edit'      => sprintf( '<span class="edit"><a href="%s" title="%s">%s</a>| </span>',
                                $edit_url,
                                __( 'Edit search engine', 'seo-monitor' ),
                                __( 'Edit',  'seo-monitor' )
                            ),
            //<span class="inline hide-if-no-js"><a href="#" class="editinline" title="Dit item inline bewerken">Snel&nbsp;bewerken</a> | </span>
            //'delete'    => sprintf('<a href="?page=%s&action=%s&keyword=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
            'delete'    => sprintf( '<span class="trash"><a class="submitdelete" title="%s" href="%s">%s</a> | </span>',
                                __( 'Delete search engine', 'seo-monitor' ),
                                $delete_url,
                                'Delete'
                            ),
            //sprintf( '<span class="view"><a href="%s" title="Toon %s" rel="permalink">Bekijken</a></span>', $view_url, $title );
        );

        //Return the title contents
        return sprintf('<a href="%s" title="%s">%s</a><div class="row-actions">%s</div>',
            $edit_url,
            __( 'Edit search engine', 'seo-monitor' ),
            rwmb_meta( 'seomonitor_se_search_engine', '', $item['id'] ),
            $this->row_actions( $actions )
        );
    }

    /**
    *
    * @since 1.0
    * @return string
    */
    function column_cb( $item ) {

        return sprintf(
            '<input type="checkbox" name="se-id[]" value="%s" />',
            $item['id']                //The value of the checkbox should be the record's id
        );
    }

    /**
    *
    * @since 1.0
    * @return array
    */
    function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />', //Render a checkbox instead of text
            'name'          => __( 'Name', 'seo-monitor' ),
            'url'           => __( 'Base URL', 'seo-monitor' ),
            'group'         => __( 'Group', 'seo-monitor' ),
        );
        return $columns;
    }

    /**
    *
    * @since 1.0
    * @return array
    */
    function get_sortable_columns() {
        $sortable_columns = array(
            'name'          => array( 'name', true ),     //true means it's already sorted
        );
        return $sortable_columns;
    }

    /**
     * Renders the Sites filter drop down
     *
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     * @since 1.0
     * @return void
    */
    private function seomonitor_se_filters( $which ) {

        if( 'top' === $which ) {
            do_action( 'seo_monitor_se_filter_actions' );
            submit_button( __( 'Filter', 'seo-monitor' ), 'secondary', 'submit', false );
        }
    }

    /**
     * Sets up the groups filter
     *
     * @access public
     * @since 1.0
     * @return void
     */
    public function groups_filter() {

        $groups = get_terms( 'seomonitor_se_group' );

        echo '<select name="group" id="seo-monitor-group-filter">';
        echo '<option value="0">' . __( 'All', 'seo-monitor' ) . '</option>';

        if ( $groups ) {
            foreach ( $groups as $group ) {
                echo '<option value="' . $group->term_id . '"' . selected( $group->term_id, $this->get_filtered_group() ) . '>' .
                     esc_html( $group->name ) . '</option>';
            }
        }
        echo '</select>';
    }

    /**
     * Retrieve the total number of search engines
     *
     * @access public
     * @since 1.0
     * @return int $total Total number of search engines
     */
    public function get_total_se() {
        $total  = 0;
        $counts = wp_count_posts( 'seomonitor_se' );
        return $counts->publish;
    }

    /**
     * Outputs the bulk actions
     *
     * @access public
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     * @since 1.0
     * @return void
     */
    function get_bulk_actions( $which = '' ) {

        $actions = array(
            'delete'    => __( 'Delete', 'seo-monitor' )
        );

        return apply_filters( 'seo_monitor_se_table_bulk_actions', $actions );
    }

    /**
     * Extra controls to be displayed between bulk actions and pagination
     *
     * @since 1.0
     * @access public
     *
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     */
    public function extra_tablenav( $which = '' ) {
        $this->seomonitor_se_filters( $which );
    }

    /**
     * Process the bulk actions
     *
     * @access public
     * @since 1.0
     * @return void
     */
    function process_bulk_action() {

        $ids    = isset( $_GET['se-id'] ) ? $_GET['se-id'] : false;

        if ( ! is_array( $ids ) ) {
            $ids = array( $ids );
        }

        //Detect when a bulk action is being triggered...
        if( 'delete' === $this->current_action() ) {

            $logs = array();

            foreach( $ids as $id ) {

                $seomonitor_se = new Seo_Monitor_Search_Engine( $id );
                $seomonitor_se->delete();

                do_action( 'seo_monitor_se_table_do_bulk_action', $id, $this->current_action() );
            }
        }
    }

    /**
     * Build all the search engine data
     *
     * @access public
     * @since 1.0
      * @global object $wpdb Used to query the database using the WordPress
     *   Database API
     * @return array $data All the data for keyword listing
     */
    public function se_data() {

        $paged      = $this->get_paged();
        $search     = $this->get_search();
        $group      = empty( $_GET['s'] ) ? $this->get_filtered_group() : null;
        $order      = isset( $_GET['order'] )   ? sanitize_text_field( $_GET['order'] )   : 'DESC';
        $orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID';


        switch( $orderby ) {
            case 'name' :
                $meta_key   = 'seomonitor_se_search_engine';
                $orderby    = 'meta_value';
                break;
            default:
                $meta_key = null;
        }

        $args = array(
            'posts_per_page'    => $this->per_page,
            'page'              => $paged,
            'orderby'           => $orderby,
            'order'             => $order,
            'meta_key'          => $meta_key,
            //'year'       => $year,
            //'month'      => $month,
            //'day'        => $day,
            's'                 => $search,
            //'start_date' => $start_date,
            //'end_date'   => $end_date,
            'group'             => $group,
        );

        $seo_monitor_se = new Seo_Monitor_Search_Engine;
        $search_engines = $seo_monitor_se->get_search_engines( $args );

        if ( $search_engines ) {

            foreach ( $search_engines as $search_engine ) {

                $data[] = array(
                    'id'            => $search_engine->ID,
                    'name'          => $search_engine->name,
                    'group'         => $search_engine->group,
                );
            }
        } else {
            $data = array();
        }

        return $data;
    }

    function prepare_items() {

        $columns    = $this->get_columns();

        $hidden     = array();
        $sortable   = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $this->items = $this->se_data();

        $this->total = $this->get_total_se();

        $this->set_pagination_args( array(
            'total_items' => $this->total,
            'per_page'    => $this->per_page,
            'total_pages' => ceil( $this->total / $this->per_page )
        ) );
    }
}