<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Seo_Monitor_Sites_List_Table Class
 *
 * Renders the Sites table
 *
 * @since 1.0
 */
class Seo_Monitor_Sites_List_Table extends WP_List_Table {

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
            'singular'  => __( 'site', 'seo-monitor' ),     //singular name of the listed records
            'plural'    => __( 'sites', 'seo-monitor' ),    //plural name of the listed records
            'ajax'      => false          //does this table support ajax?
        ) );

        $this->process_bulk_action();

        add_action( 'seo_monitor_sites_filter_actions', array( $this, 'engines_filter' ) );
        add_action( 'seo_monitor_sites_filter_actions', array( $this, 'groups_filter' ) );
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
     * Retrieves the ID of the engine we're filtering sites by
     *
     * @access public
     * @since 1.0
     * @return int engine ID
     */
    public function get_filtered_engine() {
        return ! empty( $_GET['engine'] ) ? absint( $_GET['engine'] ) : false;
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
            case 'group':
                $groups = rwmb_meta( '', 'type=taxonomy&taxonomy=seomonitor_site_group', $item['id'] );
                $value  = '';
                foreach ( $groups as $groups ) {
                    $value .= $groups->name . '&nbsp;';
                }
                break;
            case 'language':
                $value = rwmb_meta( 'seomonitor_site_language', '', $item['id'] );
                if( strlen( $value ) == 0 ) {
                    $value = __( 'All', 'seo-monitor' );
                }
                break;
            case 'location':
                $value  = rwmb_meta( 'seomonitor_site_country', '', $item['id'] );
                if( strlen( $value ) == 0 ) {
                    $value = __( 'All', 'seo-monitor' );
                }
                break;
            case 'main_url':
                $website = $item['main_url'];
                $value   = '<a href="' . esc_url( $website ) . '">' . $website . '</a>';
                break;
            case 'engine':
                $value = rwmb_meta( 'seomonitor_se_search_engine', '', $item['engine'] );
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
                                __( 'Edit site', 'seo-monitor' ),
                                __( 'Edit',  'seo-monitor' )
                            ),
            //<span class="inline hide-if-no-js"><a href="#" class="editinline" title="Dit item inline bewerken">Snel&nbsp;bewerken</a> | </span>
            //'delete'    => sprintf('<a href="?page=%s&action=%s&keyword=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
            'delete'    => sprintf( '<span class="trash"><a class="submitdelete" title="%s" href="%s">%s</a> | </span>',
                                __( 'Delete site', 'seo-monitor' ),
                                $delete_url,
                                'Delete'
                            ),
            //sprintf( '<span class="view"><a href="%s" title="Toon %s" rel="permalink">Bekijken</a></span>', $view_url, $title );
        );

        //Return the title contents
        return sprintf('<a href="%s" title="%s">%s</a><div class="row-actions">%s</div>',
            $edit_url,
            __( 'Edit Site', 'seo-monitor' ),
            rwmb_meta( 'seomonitor_site_name', '', $item['id'] ),
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
            '<input type="checkbox" name="site-id[]" value="%s" />',
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
            'main_url'      => __( 'Website', 'seo-monitor' ),
            'engine'        => __( 'Engine', 'seo-monitor' ),
            'location'      => __( 'Country', 'seo-monitor' ),
            'language'      => __( 'Language', 'seo-monitor' ),
            'group'         => __( 'Group', 'seo-monitor' ),
        );

        return apply_filters( 'seo_monitor_site_columns', $columns );
    }

    /**
    *
    * @since 1.0
    * @return array
    */
    function get_sortable_columns() {
        $sortable_columns = array(
            'name'          => array( 'name', true ),     //true means it's already sorted
            'main_url'      => array( 'main_url', false ),
            'location'      => array( 'location', false ),
            'language'      => array( 'language', false ),
        );

        return apply_filters( 'seo_monitor_site_sortable_columns', $sortable_columns );
    }

    /**
     * Extra controls to be displayed between bulk actions and pagination
     *
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     * @since 1.0
     * @access public
     */
    public function extra_tablenav( $which = '' ) {
        $this->seomonitor_sites_filters( $which );
    }

    /**
     * Renders the Sites filter drop down
     *
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     * @since 1.0
     * @return void
    */
    private function seomonitor_sites_filters( $which ) {

        if( 'top' === $which ) {
            do_action( 'seo_monitor_sites_filter_actions' );
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

        $groups = get_terms( 'seomonitor_site_group' );

        if ( $groups ) {
            echo '<select name="group" id="seo-monitor-group-filter">';
                echo '<option value="0">' . __( 'All', 'seo-monitor' ) . '</option>';
                foreach ( $groups as $group ) {
                    echo '<option value="' . $group->term_id . '"' . selected( $group->term_id, $this->get_filtered_group() ) . '>' .
                         esc_html( $group->name ) . '</option>';
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
            'orderby'                   => 'meta_value',
            'meta_key'                  => 'seomonitor_se_search_engine',
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
     * Retrieve the total number of sites
     *
     * @access public
     * @since 1.0
     * @return int $total Total number of sites
     */
    public function get_total_sites() {
        $total  = 0;
        $counts = wp_count_posts( 'seomonitor_site' );
        return $counts->publish;
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

        return apply_filters( 'seo_monitor_sites_table_bulk_actions', $actions );
    }

    /**
     * Process the bulk actions
     *
     * @access public
     * @since 1.0
     * @return void
     */
    function process_bulk_action() {

        $ids    = isset( $_GET['site-id'] ) ? $_GET['site-id'] : false;

        if ( ! is_array( $ids ) ) {
            $ids = array( $ids );
        }

        //Detect when a bulk action is being triggered...
        if( 'delete' === $this->current_action() ) {
            foreach( $ids as $id ) {

                $seomonitor_site = new Seo_Monitor_Site( $id );
                $seomonitor_site->delete();

                do_action( 'seo_monitor_site_table_do_bulk_action', $id, $this->current_action() );
            }
        }
    }

    /**
     * Build all the Sites data
     *
     * @access public
     * @since 1.0
      * @global object $wpdb Used to query the database using the WordPress
     *   Database API
     * @return array $data All the data for keyword listing
     */
    public function sites_data() {

        $paged      = $this->get_paged();
        $offset     = $this->per_page * ( $paged - 1 );
        $search     = $this->get_search();
        $engine     = empty( $_GET['s'] ) ? $this->get_filtered_engine() : null;
        $group      = empty( $_GET['s'] ) ? $this->get_filtered_group() : null;
        $order      = isset( $_GET['order'] )   ? sanitize_text_field( $_GET['order'] )   : 'DESC';
        $orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID';


        switch( $orderby ) {
            case 'name' :
                $meta_key   = 'seomonitor_site_name';
                $orderby    = 'meta_value';
                break;
            case 'main_url' :
                $meta_key   = 'seomonitor_site_main_url';
                $orderby    = 'meta_value';
                break;
            default:
                $meta_key = null;
        }

        $args = array(
            'posts_per_page'    => $this->per_page,
            'page'              => $paged,
            'offset'            => $offset,
            'orderby'           => $orderby,
            'order'             => $order,
            'meta_key'          => $meta_key,
            //'year'       => $year,
            //'month'      => $month,
            //'day'        => $day,
            's'                 => $search,
            //'start_date' => $start_date,
            //'end_date'   => $end_date,
            'engine'            => $engine,
            'group'             => $group,
        );

        $seo_monitor_site = new Seo_Monitor_Site;
        $sites = $seo_monitor_site->get_sites( $args );

        if ( $sites ) {

            foreach ( $sites as $site ) {

                $data[] = array(
                    'id'            => $site->ID,
                    'name'          => $site->name,
                    'main_url'      => $site->main_url,
                    'engine'        => $site->engine,
                    'location'      => $site->country,
                    'language'      => $site->language,
                    'group'         => $site->group,
                );
            }
        } else {
            $data = array();
        }

        //$this->total    = count( $data );
        //$data           = array_slice( $data, $offset, $this->per_page );

        return $data;
    }

    function prepare_items() {

        $columns    = $this->get_columns();

        $hidden     = array();
        $sortable   = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $this->items = $this->sites_data();

        $this->total = $this->get_total_sites();

        $this->set_pagination_args( array(
            'total_items' => $this->total,
            'per_page'    => $this->per_page,
            'total_pages' => ceil( $this->total / $this->per_page )
        ) );
    }
}