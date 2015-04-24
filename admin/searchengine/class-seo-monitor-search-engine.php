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

class Seo_Monitor_Search_Engine {

    /**
    * holds the cache group
    * @since 1.0
    */
    private $cache_group;

    /**
    *
    * @since 1.0
    */
    private $id;

    /**
    *
    * @since 1.0
    */
    private $engine_base_url;

    /**
    * Seo_Monitor_Proxy
    * @since 1.0
    */
    private $proxy;

    /**
    * Constructor
    *
    * @since 1.0
    */
    public function __construct( $id = null, $proxy = null ) {

        $this->cache_group  = 'searchengine';

        $this->set_id( $id );
        $this->set_proxy( $proxy );
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
    public function set_id( $value ) {
        $this->id = $value;
    }

    /**
    * Delete search engine, there is an active filter which checks if it is allowed to delete the search engine
    *
    * @since 1.0
    */
    public function delete() {
        if( wp_delete_post( $this->get_id() ) ) {
            delete_post_meta( $this->get_id(), 'seomonitor_se_url' );
            delete_post_meta( $this->get_id(), 'seomonitor_se_search_engine' );
        }
    }

    /**
    * Prevent delete of search engine when active
    *
    * @uses filter user_has_cap
    *
    * @param $allcaps (an array of all the capabilities, each one set to true or false)
    * @param $caps (an array of the capabilities being requested by the current operation)
    * @param $args (an array of arguments relevant to this operation).
    * @since 1.0
    */
    public function prevent_delete_when_se_is_active( $allcaps, $caps, $args ) {

        if ( isset( $args[0] ) && isset( $args[2] ) && $args[0] == 'delete_post' ) {

            $post = get_post( $args[2] );

            if ( $post->post_type == 'seomonitor_se' && $post->post_status == 'publish' ) {

                $this->set_id( $post->ID );

                if ( $this->is_active() ) {
                    $allcaps[$caps[0]] = false;
                }
            }
        }

        return $allcaps;
    }

    /**
    * Check if search engine is used in a site
    * @since 1.0
    */
    public function is_active() {

        $id = $this->get_id();

        $args = array(
            'post_type'     =>  'seomonitor_site',
            'meta_query'    =>  array(
                array(
                    'key'   => 'seomonitor_site_engine',
                    'value' =>  $id
                )
            )
        );

        $query = new WP_Query( $args );

        if( $query->have_posts() ) {
            return true;
        } else {
            return false;
        }
    }

    /**
    *
    * @since 1.0
    */
    public function set_proxy( $value ) {

        if( !$value ) {
            if( class_exists( 'Seo_Monitor_Proxy' ) ) {
                $this->proxy = new Seo_Monitor_Proxy();
            } else {
                $this->proxy = null;
            }
        } else {
            $this->proxy = $value;
        }
    }

    /**
     * Retrieve Search Engines from the database
     *
     * @access  public
     * @since   1.0
     * @return result will be output as a numerically indexed array of row objects.
    */
    public function get_search_engines( $args = array() ) {

        $defaults = array(
            'post_type'       => array( 'seomonitor_se' ),
            'start_date'      => false,
            'end_date'        => false,
            'posts_per_page'  => 15,
            'page'            => null,
            'orderby'         => 'ID',
            'order'           => 'DESC',
            'user'            => null,
            'status'          => 'publish',
            'meta_key'        => null,
            'year'            => null,
            'month'           => null,
            'day'             => null,
            's'               => null,
            'children'        => false,
            'fields'          => null,
            'meta_query'      => array()
        );

        $args  = wp_parse_args( $args, $defaults );

        if( isset( $args['group'] ) && strlen( $args['group'] ) > 0 )  {

            $tax_query = array(
                            'taxonomy'  => 'seomonitor_se_group',
                            'field'     => 'term_id',
                            'terms'     => $args['group'],
                        );

            $args['tax_query'][] = $tax_query;
        }

        $cache_key          = md5( 'seo_monitor_se_' . serialize( $args ) );

        $search_engines = wp_cache_get( $cache_key, $this->cache_group );

        if( $search_engines === false ) {

            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();

                    $details = new stdClass;

                    $se_id                  = get_post()->ID;

                    $details->ID            = $se_id;
                    $details->date          = get_post()->post_date;
                    $details->post_status   = get_post()->post_status;
                    $details->name          = rwmb_meta( 'seomonitor_se_search_engine' );
                    $details->url           = rwmb_meta( 'seomonitor_se_url' );
                    $details->group         = rwmb_meta( 'seomonitor_se_group' );

                    $search_engines[] = apply_filters( 'seo_monitor_searchengines', $details, $se_id );
                }

                wp_reset_postdata();
            }

            wp_cache_set( $cache_key, $search_engines, $this->cache_group, 3600 );
        }

        return $search_engines;
    }

    /**
    * Returns domain name without domain extension and without www or search at the beginning
    * Also set property engine_base_url
    *
    * @param int $engine_id search engine id
    * @return string
    * @since 1.0
    */
    public function get_engine_by_id( $engine_id ) {

        $this->engine_base_url = rwmb_meta( 'seomonitor_se_url', '', $engine_id );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            Seo_Monitor_Logging::add( 'engine_base_url', sprintf( __( 'engine_base_url %s', 'seo-monitor' ), $this->engine_base_url ), 0, 'debug' );
        }

        $engine = explode( '.', $this->engine_base_url );

        if( isset( $engine[0] ) &&
            ( ! stristr( $engine[0], 'http') && ! stristr( $engine[0], 'www') &&
              ! stristr( $engine[0], 'search') ) ) {

            $engine = $engine[0];

        } else {
            $engine = isset( $engine[1] ) ? $engine[1] : '';
        }

        return $engine;
    }

    /**
    * Change Post Title, so that we can use search
    * @param array - Sanitized post data.
    * @param array - Raw post data
    * @since 1.0
    */
    public function modify_post_title( $data, $postarr ) {

        if( strcmp( $data['post_type'], 'seomonitor_se' ) == 0 ) {
            if( isset( $postarr['seomonitor_se_search_engine'] ) ) {
                $data['post_title'] = sanitize_text_field( $postarr['seomonitor_se_search_engine'] );
            }
        }
        return $data;
    }

    /**
    * Properly set the Custom-Post-Type submenu entry as "active"
    * @param string - $parent_file
    * @since 1.0
    */
    public function fix_admin_parent_file( $parent_file ) {
        global $submenu_file, $current_screen;

        // Set correct active/current menu and submenu in the WordPress Admin menu for the "seomonitor_site" Add-New/Edit/List
        if( $current_screen->post_type == 'seomonitor_se' ) {

            $submenu_file   = 'edit.php?post_type=seomonitor_se';
            $parent_file    = 'seomonitor-keywords';
        }
        return $parent_file;
    }

    /**
    * Intro Text SearchEngine List
    *
    * @since 1.0
    */
    public function intro_text_se_list() {
        _e( 'This plugin supports only the Google search engine out of the box. You can easily insert new local Google Search Engines, e.g. http://www.google.co.uk.<br/>
            It is also possible to add extra search engines like Yahoo, Bing and others. You need development skills to do this. There will be an explanation on the website of the plugin how to this.
            Additional search engines are also supported in the premium version.<br/><br/>
            When you try to delete a search engine which is currently active on a site, nothing happens. If you want to delete a search engine which is in use by a site,
            you have to remove the search engine from the site first.', 'seo-monitor' );
    }

    /**
    *
    * display all search_engines
    * @since 1.0
    */
    public static function display_all_search_engines() {

        $search_engines_list_table = new Seo_Monitor_Se_List_Table();
        $search_engines_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h2>
                <?php _e( 'Search Engines', 'seo-monitor' ); ?>
                <a href="<?php echo admin_url( '/post-new.php?post_type=seomonitor_se' ); ?>" class="add-new-h2"><?php _e( 'Add', 'seo-monitor' ); ?></a>
            </h2>
            <?php do_action( 'seo_monitor_se_table_top' ); ?>
            <form id="seomonitor-se-filter" method="get" action="<?php echo admin_url( 'admin.php?page=seomonitor-se' ); ?>">
                <input type="hidden" name="page" value="seomonitor-se" />
                <input type="hidden" name="page-type" value="seomonitor_se"/>
            <?php

            $search_engines_list_table->search_box( __( 'Search for a search engine', 'seo-monitor' ), 'seomonitor-sites' );
            $search_engines_list_table->display();
            ?>
            </form>
            <?php do_action( 'seo_monitor_se_table_bottom' ); ?>
        </div>
        <?php
    }

    /**
    *
    * $keyword - keyword record
    * @return ranking array or WP_Error
    * @since 1.0
    */
    public function get_ranking( $keyword_obj ) {

        // for main_url, location and language
        $site               = new Seo_Monitor_Site( $keyword_obj->project_id );
        $engine             = $this->get_engine_by_id( $keyword_obj->engine );

        $location           = $site->get_location();
        $language           = $site->get_language();
        $search_query       = '';
        $search_selectors   = '';
        $number_of_pages    = 1;
        $keyword            = $keyword_obj->keyword;

        $clean_regexes      = ''; //optional

        $serp_results       = array();

        $class_name         = 'Seo_Monitor_Search_Engine_' . $engine;

        if( class_exists( $class_name )) {
            $search_engine_object = new $class_name;
            $search_engine_object->set_base_url( $this->engine_base_url );
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                Seo_Monitor_Logging::add( 'Search Engine Id', sprintf( __( 'Search Engine Id %s', 'seo-monitor' ), $keyword_obj->engine ), 0, 'debug' );
            }
            Seo_Monitor_Logging::add( 'seo-monitor-se-get_ranking', sprintf( __( 'Search Engine class %s is not available', 'seo-monitor' ), $class_name ), 0, 'error' );
            return new WP_Error( 'seo-monitor-se-get_ranking', sprintf( __( 'Search Engine class %s is not available', 'seo-monitor' ), $class_name ) );
        }

        if( has_filter( 'seo_monitor_' . $engine . '_query' ) && has_filter( 'seo_monitor_' . $engine . '_search_selector' ) ) {
            $search_selectors   = apply_filters( 'seo_monitor_' . $engine . '_search_selector', $search_selectors );
            $clean_regexes      = apply_filters( 'seo_monitor_' . $engine . '_clean_regexes', $clean_regexes );
            $number_of_pages    = apply_filters( 'seo_monitor_' . $engine . '_number_of_pages', $number_of_pages );

        } else {
            Seo_Monitor_Logging::add( 'seo-monitor-se-get_ranking', sprintf( __( 'Search Engine %s filters are not available', 'seo-monitor' ), $class_name ), 0, 'error' );
            return new WP_Error( 'seo-monitor-se-get_ranking', sprintf( __( 'Search Engine %s filters are not available', 'seo-monitor' ), $engine ) );
        }

        for( $i = 0; $i < $number_of_pages; $i++ ) {

            $page_number = $i + 1;

            $search_query = apply_filters( 'seo_monitor_' . $engine . '_query', $search_query, $keyword, $location, $language, $page_number );

            if( $this->proxy === null || !$this->proxy->get_use_proxy() ) {
                Seo_Monitor_Logging::add( 'Make search request without proxy', sprintf( __( 'Search query is %s', 'seo-monitor' ), $search_query ), 0, 'event' );
                $results        = $this->make_request( $search_query, false );
            } else {
                Seo_Monitor_Logging::add( 'Make search request with proxy', sprintf( __( 'Search query is %s' , 'seo-monitor' ), $search_query ), 0, 'event' );
                $results        = $this->make_curl_request( $search_query, false );
            }

            $parser = new Seo_Monitor_Parser( $results );
            $parser->set_search_selectors( $search_selectors );
            $parser->set_clean_regexes( $clean_regexes );

            $serp_results = array_merge( $serp_results, $parser->parse() );
        }

        $ranking     = array();

        if ( count( $serp_results ) == 0 ) {

            //no results appear here
            $ranking['rank']    = 0;
            $ranking['status']  = 'success';
            $ranking['message'] = __( 'No links found in the returned search page', 'seo-monitor' );

            Seo_Monitor_Logging::add( __( 'No links found in search result', 'seo-monitor' ), $ranking['message'], 0, 'event' );

        } else {
            $ranking = $this->fetch_result( $serp_results, $site->get_url() );
        }

        return $ranking;
    }

    /**
     *
     * Method to make a GET HTTP connecton to
     * the given url and return the output
     *
     * @param urlToFetch url to be connected
     * @param jsonRequest output of request is Json else HTML
     * @return the decoded JSON http get response
     * @since 1.0
     */
    public function make_request( $urlToFetch, $jsonRequest ) {

        $response = wp_remote_get( $urlToFetch );

        // Check for Wordpress Error
        if( is_wp_error( $response ) ) {

            Seo_Monitor_Logging::add( 'Error response', serialize( $response ), 0, 'error' );
            return false;
        }

        // Check for errored response
        if ( array_key_exists( 'response', $response ) &&
             array_key_exists( 'code', $response["response"] ) &&
             $response["response"]["code"] != "200" ) {

            Seo_Monitor_Logging::add( 'Error response', serialize( $response ), 0, 'error' );
            return false;
        }

        $data = wp_remote_retrieve_body( $response );

        if( $jsonRequest ) {
            return json_decode( $data );
        } else {
            return ( $data );
        }

        Seo_Monitor_Logging::add( 'Error response', serialize( $response ), 0, 'error' );
        return false;
    }

    /**
     *
     * Method to make a GET HTTP connecton to
     * the given url and return the output
     *
     * @param url_to_fetch url to be connected
     * @param json_request output of request is Json else HTML
     * @return the decoded JSON http get response
     * @since 1.0
     */
    public function make_curl_request( $url_to_fetch, $json_request ) {

        $parsed_url     = parse_url( $url_to_fetch );
        $referred_url   = 'http://' . $parsed_url['host'];

        $curl = curl_init();

        curl_setopt( $curl, CURLOPT_HTTPGET, true );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); //data will returned, so that you can add it to variable
        curl_setopt( $curl, CURLOPT_HEADER, 0 ); //don't return headers
        curl_setopt( $curl, CURLOPT_VERBOSE, 0 );
        curl_setopt( $curl, CURLOPT_REFERER, $referred_url ); //set the HTTP referer header
        //curl_setopt( $curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)" );
        curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:28.0) Gecko/20100101 Firefox/28.0' );
        curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
        curl_setopt( $curl, CURLOPT_MAXREDIRS, 5 ); //if http server gives redirection responce
        curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true ); // Follow redirects, need this if the url changes
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false ); // false for https

        curl_setopt( $curl, CURLOPT_URL, $url_to_fetch );

        curl_setopt( $curl, CURLOPT_COOKIEJAR, "cookie.txt" ); // wat doet deze???

        if ( has_filter( 'seo_monitor_se_before_curl_exec' ) ) {
            apply_filters( 'seo_monitor_se_before_curl_exec', $curl );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $verbose = fopen( dirname(__FILE__) . '/verbose.txt', 'w' );
            curl_setopt( $curl, CURLOPT_VERBOSE , 1 );
            curl_setopt( $curl, CURLOPT_STDERR, $verbose );
        }

        $response = curl_exec( $curl );

        // Check for errors and display the error message
        if( $errno = curl_errno( $curl ) ) {
            if( version_compare( phpversion(), '5.5.0', '<' ) ) {
                echo "cURL error ({$errno})";
            } else {
                $error_message = curl_strerror( $errno );
                echo "cURL error ({$errno}):\n {$error_message}";
            }
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            Seo_Monitor_Logging::add( 'Error response', serialize(curl_getinfo( $curl)), 0, 'debug' );
        }

        if( 0 !== curl_errno( $curl ) || 200 !== curl_getinfo( $curl, CURLINFO_HTTP_CODE ) ) {
            return false;
        }

        curl_close( $curl );

        if( $json_request ) {
            return json_decode( $response );
        } else {
            return ( $response );
        }

        return false;
    }

    /**
     *
     * Search for $site
     *
     * @param serp_results array with the serp results found
     * @param site which site must found in serp_results
     * @return array with ranking and link, 0 if not found
     * @since 1.0
     */
    public function fetch_result( $serp_results, $site ) {

        $i      = 0;
        $site   = parse_url( $site ); //Parse a URL and return its components
        $site   = preg_replace( '/^www\./', '', $site['host'] ); //remove wwww before compare

        foreach( $serp_results as $serp_result ) {

            $i++;

            //find site (needle) within $serp_result['link'] (haystack)
            if( stristr( $serp_result['link'], $site ) ) {
                //verify

                $parsed_url  = parse_url( $serp_result['link'] ); //Parse a URL and return its components
                $parsed_host = $parsed_url['host'];

                $parsed_host = preg_replace( '/^www\./', '', $parsed_host ); //remove wwww
                //$site        = preg_replace( '/^www\./', '', $site ); //remove wwww

                if( trim( $parsed_host ) == trim( $site ) ) {

                    $return['rank']             = $i;
                    $return['ranking_url']      = $serp_result['link'];
                    $return['ranking_title']    = $serp_result['title'];
                    $return['status']           = 'success';

                    return $return;
                }
            }
        }

        $return['rank']     = 0;
        $return['status']   = 'success';

        return $return; // no matches found
    }


    /**
    * function to check whether captcha found in search engine results
    *
    * @since 1.0
    */
    public function is_captcha_in_search_results( $search_result ) {

        $captcha_found = false;

        // if captcha input field is found
        if (stristr($search_result, 'name="captcha"') || stristr($search_result, 'id="captcha"')) {
            $captcha_found = true;
        }

        return $captcha_found;
    }

    /**
     * Register meta boxes
     * @since 1.0
     */
    function register_meta_boxes_se() {

            if ( !class_exists( 'RW_Meta_Box' ) )
                    return;

            $prefix         = 'seomonitor_se_';
            $meta_box_se    = array(
                    'title'     => __( 'Search Engine', 'seo-monitor' ),
                    'pages'     => array( 'seomonitor_se' ),
                    'fields'    => array(
                            array(
                                    'name' => __( 'Name Search Engine', 'seo-monitor' ),
                                    'id'   => "{$prefix}search_engine",
                                    'type' => 'text',
                            ),

                            array(
                                    'name' => __( 'Search Engine Base URL', 'seo-monitor' ),
                                    'desc' => __( 'Search Engine url has to begin with http://, e.g. http://www.google.co.uk', 'seo-monitor' ),
                                    'id'   => "{$prefix}url",
                                    'std'  => 'http://',
                                    'type' => 'url',
                            ),

                            array(
                                    'name'    => __( 'Search Engine Group', 'seo-monitor' ),
                                    'id'      => "{$prefix}se_group",
                                    'type'    => 'taxonomy',
                                    'options' => array(
                                            // Taxonomy name
                                            'taxonomy' => 'seomonitor_se_group',
                                            // How to show taxonomy: 'checkbox_list' (default) or 'checkbox_tree', 'select_tree', select_advanced or 'select'. Optional
                                            'type' => 'checkbox_list',
                                            // Additional arguments for get_terms() function. Optional
                                            'args' => array()
                                    ),
                            ),
                    ),
                    'validation' => array(
                            'rules' => array(
                                    "{$prefix}search_engine"    => array( 'required'  => true ),
                                    "{$prefix}url"              => array( 'required'  => true ),
                            ),
                            // optional override of default jquery.validate messages
                            'messages' => array(
                                    "{$prefix}search_engine" => array(
                                            'required'  => __( 'Name Search Engine is mandatory', 'seo-monitor' ),
                                    ),
                            )
                    )
            );

            new RW_Meta_Box( apply_filters( 'seo_monitor_se_meta_box', $meta_box_se ) );
    }

    /**
    * Register Custom Post Type seomonitor_se
    *
    * @since 1.0
    */
    public function seomonitor_se_register() {
        $se_labels = array(
                    'name'               => __( 'Search Engine', 'seo-monitor' ),
                    'singular_name'      => __( 'Search Engine', 'seo-monitor' ),
                    'add_new'            => __( 'Add', 'seo-monitor' ),
                    'add_new_item'       => __( 'Add Search Engine', 'seo-monitor' ),
                    'edit_item'          => __( 'Modify Search Engine', 'seo-monitor' ),
                    'new_item'           => __( 'New Search Engine', 'seo-monitor' ),
                    'all_items'          => __( 'All Search Engines', 'seo-monitor' ),
                    'view_item'          => __( 'View Search Engine', 'seo-monitor' ),
                    'search_items'       => __( 'Search Search Engines', 'seo-monitor' ),
                    'not_found'          => __( 'No Search Engine found', 'seo-monitor' ),
                    'not_found_in_trash' => __( 'No Search Engine found in the trash', 'seo-monitor' ),
                    'parent_item_colon'  => '',
                    'menu_name'          => __( 'Search Engine', 'seo-monitor' )
        );

        $se_args = array(
            'labels'                => $se_labels,
            'public'                => false,
            'show_ui'               => true,
            'show_in_nav_menus'     => false,
            'show_in_menu'          => false,
            'show_in_admin_bar'     => false,
            'capability_type'       => 'post',
            'exclude_from_search'   => true, //exclude posts with this post type from front end search results
            'publicly_queryable'    => false,
            'hierarchical'          => false,
            'rewrite'               => true,
            'supports'              => apply_filters( 'seo_monitor_se_supports', false ),
            'has_archive'           => false,
        );

        register_post_type( 'seomonitor_se', apply_filters( 'seo_monitor_se_post_type_args', $se_args ) );

        register_taxonomy( 'seomonitor_se_group', array( 'seomonitor_se' ),
                        array(
                            "hierarchical"      => true,
                            "label"             => __( 'Search Engine group', 'seo-monitor' ),
                            "singular_label"    => __( 'Search Engine group', 'seo-monitor' ),
                            //"rewrite"           => array( 'slug' => 'group')
                        )
        );
    }
}