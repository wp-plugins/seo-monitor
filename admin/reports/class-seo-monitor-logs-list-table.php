<?php
/**
 * Log View Class
 *
 * @package     Seo Monitor
 * @subpackage  admin/partials
 * @copyright   Copyright (c) 2015, To Be On The Web
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Seo_Monitor_Log_List_Table Class
 *
 * Renders the log list table
 *
 * @since 1.0
 */
class Seo_Monitor_Log_List_Table extends WP_List_Table {
	/**
	 * Number of results to show per page
	 *
	 * @since 1.0
	 * @var int
	 */
	public $per_page = 30;

	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular'  => __( 'Seo Monitor Logging', 'seo-monitor' ),    // Singular name of the listed records
			'plural'    => __( 'Seo Monitor Logging', 'seo-monitor' ),    	// Plural name of the listed records
			'ajax'      => false             			// Does this table support ajax?
		) );

		$this->process_bulk_action();

		add_action( 'seo_monitor_logs_filter_actions', array( $this, 'log_type_filter' ) );
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
     * Retrieves the ID of the log_type we're filtering logs by
     *
     * @access public
     * @since 1.0
     * @return string log_type
     */
    public function get_log_type() {
        return ! empty( $_GET['log_type'] ) ? esc_html( $_GET['log_type'] ) : false;
    }

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @param array $item Contains all the data of the log item
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'log_type':
				$value = '';
				$terms = wp_get_post_terms( $item->ID, 'seo_monitor_log_type' );
				if( $terms ) {
					foreach( $terms as $term ) {
						$value .= $term->name . ' ';
					}
				}
				return $value;
				break;
			default:
				return $item->$column_name;
		}
	}

	/**
	 * Render the checkbox column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $log Contains all the data for the checkbox column
	 * @return string Displays a checkbox
	 */
	public function column_cb( $log ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'log-id',
			$log->ID
		);
	}

	/**
	 * Output Error Message Column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $item Contains all the data of the log
	 * @return void
	 */
	public function column_message( $item ) {
	?>
		<a href="#TB_inline?width=640&amp;inlineId=log-message-<?php echo $item->ID; ?>" class="thickbox" title="<?php _e( 'View Log Message', 'seo-monitor' ); ?> ">
			<?php _e( 'View Log Message', 'seo-monitor' ); ?>
		</a>
		<div id="log-message-<?php echo $item->ID; ?>" style="display:none;">
			<?php
			$log_message = get_post_field( 'post_content', $item->ID );

			$serialized  = strpos( $log_message, '{' );

			// Check to see if the log message contains serialized information
			if ( $serialized !== false ) {
				//$length  = strlen( $log_message ) - $serialized;
				//$intro   = substr( $log_message, 0, - $length );
				//$data    = substr( $log_message, $serialized, strlen( $log_message ) - 1 );

				$data = unserialize( $log_message );

				if( is_wp_error( $data ) ) {
					$data = $data->get_error_code() . ' ' . $data->get_error_message();
				} else {
					$data = '<pre><code class="php">' . esc_html( var_export( $data ) ) . '</code></pre>';
				}

				//echo wpautop( $intro );
				echo wpautop( __( '<strong>Log data:</strong>', 'seo-monitor' ) );
				//echo '<div style="word-wrap: break-word;">' . wpautop( $data ) . '</div>';
				echo '<div style="word-wrap: break-word;">' . $data . '</div>';
			} else {
				// No serialized data found
				echo wpautop( $log_message );
			}
			?>
		</div>
	<?php
	}

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 1.4
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'        	=> '<input type="checkbox" />', //Render a checkbox instead of text
			'ID'			=> __( 'Log ID', 'seo-monitor' ),
			'post_title'	=> __( 'Title', 'seo-monitor' ),
			'message'		=> __( 'Message', 'seo-monitor' ),
			'log_type'		=> __( 'Type', 'seo-monitor' ),
			'post_date'  	=> __( 'Date', 'seo-monitor' )
		);

		return apply_filters( 'seo_monitor_logs_columns', $columns );
	}

    /**
    *
    * @since 1.0
    * @return array
    */
    function get_sortable_columns() {
        $sortable_columns = array(
            'ID'            => array( 'ID', true ),     //true means it's already sorted
            'post_title'    => array( 'post_title', false ),
            'post_date'     => array( 'post_date', false ),
        );

        return apply_filters( 'seo_monitor_logs_sortable_columns', $sortable_columns );
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
	 * @since 1.4
	 * @return string|false string If search is present, false otherwise
	 */
	public function get_search() {
		return ! empty( $_GET['s'] ) ? urldecode( trim( $_GET['s'] ) ) : false;
	}

    /**
     * Renders the Logs filter drop down
     *
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     * @since 1.0
     * @return void
    */
    private function seomonitor_logs_filters( $which ) {

        if( 'top' === $which ) {
            do_action( 'seo_monitor_logs_filter_actions' );
            submit_button( __( 'Filter', 'seo-monitor' ), 'secondary', 'submit', false );
        }
    }

   	/**
     * Sets up the log_type filter
     *
     * @access public
     * @since 1.0
     * @return void
     */
    public function log_type_filter() {

        $log_types = get_terms( 'seo_monitor_log_type' );

        echo '<select name="log_type" id="seo-monitor-log-type">';
        echo '<option value="0">' . __( 'All', 'seo-monitor' ) . '</option>';

        if ( $log_types ) {
            foreach ( $log_types as $log_type ) {
                echo '<option value="' . esc_html( $log_type->name ) . '"' . selected( $log_type->term_id, $this->get_log_type() ) . '>' .
                     esc_html( $log_type->name ) . '</option>';
            }
        }

        echo '</select>';
    }

	/**
	 * Outputs the bulk actions
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	function get_bulk_actions( $which = '' ) {
        $actions = array(
            'delete'    => __( 'Delete', 'seo-monitor' )
        );

        return apply_filters( 'seo_monitor_logs_table_bulk_actions', $actions );
	}

	/**
	 * Process the bulk actions
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
    function process_bulk_action() {

    	$ids    = isset( $_GET['log-id'] ) ? $_GET['log-id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

        //Detect when a bulk action is being triggered...
        if( 'delete' === $this->current_action() ) {

			$logs = array();

			foreach( $ids as $id ) {

				$log = new stdClass;
				$log->ID = $id;

				$logs[] = $log;
			}

			$seo_montitor_logging = new Seo_Monitor_Logging();
            $seo_montitor_logging->prune_old_logs( $logs );
        }
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
        $this->seomonitor_logs_filters( $which );
    }

	/**
	 * Gets the log entries for the current view
	 *
	 * @access public
	 * @since 1.0
	 * @return array $logs_data Array of all the Log entires
	 */
	public function get_logs() {

		$logs_data  = array();
		$paged      = $this->get_paged();
		$offset     = $this->per_page * ( $paged - 1 );
		$search     = $this->get_search();
		$order      = isset( $_GET['order'] )   ? sanitize_text_field( $_GET['order'] )   : 'DESC';
        $orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID';

		$log_type 	= $this->get_log_type();

		if( !$log_type ) {
			$log_type = array( 'debug', 'error', 'event' );
		}

		$log_query = array(
			'log_type'    		=> $log_type,
			'paged'      		=> $paged,
			'offset'        	=> $offset,
			'posts_per_page'    => $this->per_page,
			's'					=> $search,
			'orderby'           => $orderby,
            'order'             => $order,
		);

		$logs = Seo_Monitor_Logging::get_connected_logs( $log_query );

		return $logs;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function prepare_items() {

		$columns               	= $this->get_columns();
		$hidden                	= array();
		$sortable              	= $this->get_sortable_columns();
		$this->_column_headers 	= array( $columns, $hidden, $sortable );
		$this->items           	= $this->get_logs();

		$search     			= $this->get_search();

		$log_type 	= $this->get_log_type();

		if( !$log_type ) {
			$log_type = array( 'debug', 'error', 'event' );
		}

		$log_query = array(
			's'					=> $search
		);

		$total_items           	= Seo_Monitor_Logging::get_log_count( $log_query, $log_type );

		$this->set_pagination_args( array(
				'total_items'  => $total_items,
				'per_page'     => $this->per_page,
				'total_pages'  => ceil( $total_items / $this->per_page )
			)
		);
	}
}
