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
class Seo_Monitor_Site {

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
    private $name;

    /**
	*
	* @since 1.0
	*/
    private $url;

   	/**
	* Array
	* @since 1.0
	*/
    private $searchengines;

    /**
	* Array
	* @since 1.0
	*/
    private $keywords;

    /**
	*
	* @since 1.0
	*/
    private $location;

    /**
	*
	* @since 1.0
	*/
    private $language;

    /**
	* Constructor
	* @since 1.0
	*/
	public function __construct( $id = null, $name = null, $url = null, $searchengines = null,
								 $keywords = null, $location = null, $language = null ) {

		$this->cache_group 	= 'seo-monitor-sites';
		$this->set_id( $id );

		if( !$this->get_id() ) {

			$this->set_name( $name );
	    	$this->set_url( $url );
	    	$this->set_searchengines ( $searchengines );
	    	$this->set_keywords( $keywords );
	    	$this->set_location( $location );
	    	$this->set_language( $language );

		} else {
			$this->get_site();
		}
	}

	/**
	*
	* @since 1.0
	*/
	private function get_site() {

		$site_id = $this->get_id();

		$this->set_name( rwmb_meta( 'seomonitor_site_name', '', $site_id ) );
    	$this->set_url( rwmb_meta( 'seomonitor_site_main_url', '', $site_id ) );
    	$this->set_searchengines( rwmb_meta( 'seomonitor_site_engine', '', $site_id ) );
    	$this->set_keywords( rwmb_meta( 'seomonitor_site_keywords', '', $site_id ) );
    	$this->set_location( rwmb_meta( 'seomonitor_site_country', '', $site_id ) );
    	$this->set_language( rwmb_meta( 'seomonitor_site_language', '', $site_id ) );
	}

    /**
    *
    * This function will update keywords on post save
    * @param site_id - integer
    * @since 1.0
    */
    public function save_post( $site_id ) {

		// make sure that this runs only when a site is saved or updated (or this function is called directly)
        if ( ( isset($_POST['post_type'] ) && 'seomonitor_site' != $_POST['post_type'] ) || wp_is_post_revision( $site_id )) {
                return;
        }

        $seo_monitor_keyword = new Seo_Monitor_Keyword();

        // make use of dependency injection
        $seo_monitor_keyword->update_site_keywords( $site_id, $_POST );
    }

    /**
    * Change Post Title, so that we can use search
    * @param array - Sanitized post data.
    * @param array - Raw post data
    * @since 1.0
    */
    public function modify_post_title( $data, $postarr ) {

		if( strcmp( $data['post_type'], 'seomonitor_site' ) == 0 ) {
			if( isset( $postarr['seomonitor_site_name'] ) ) {
				$data['post_title'] = sanitize_text_field( $postarr['seomonitor_site_name'] );
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
	    if( $current_screen->post_type == 'seomonitor_site' ) {

	        $submenu_file 	= 'edit.php?post_type=seomonitor_site';
	        $parent_file 	= 'seomonitor-keywords';
	    }

	    return $parent_file;
	}

    /**
    * Delete the site with all corresponding keywords
    *
    * @since 1.0
    */
    public function delete() {

        wp_delete_post( $this->get_id() );
    }

    /**
    *
    * This function will delete keywords on post delete
    * @param site_id - integer
    * @since 1.0
    */
    public function delete_post( $site_id ) {

		// make sure that this runs only when a site is deleted (or this function is called directly)
        if ( ( isset( $_POST['post_type'] ) && 'seomonitor_site' != $_POST['post_type'] ) ) {
                return;
        }

		$seo_monitor_keyword = new Seo_Monitor_Keyword();
		$seo_monitor_keyword->set_site_id( $site_id );

		$args = array(
					'site' => $site_id
		);

        $keywords = $seo_monitor_keyword->get_keywords( $args );

        if( $keywords ) {
        	foreach ( $keywords as $keyword ) {
	        	$seo_monitor_keyword->set_keyword( $keyword->keyword );
	        	$seo_monitor_keyword->set_search_engine_id( $keyword->engine );
	        	$affected_records = $seo_monitor_keyword->delete_keyword();
        	}
        }
    }

	/**
	 * Retrieve Sites from the database
	 *
	 * @access  public
	 * @since   1.0
	 * @return result will be output as a numerically indexed array of row objects.
	*/
	public function get_sites( $args = array() ) {

		$defaults = array(
			'post_type'       => array( 'seomonitor_site' ),
			'start_date'      => false,
			'end_date'        => false,
			'posts_per_page'  => 15,
			'page'            => null,
			'orderby'         => 'ID',
			'order'           => 'DESC',
			'user'            => null,
			'status'          => 'publish',
			'meta_key'        => null,
			//'year'            => null,
			//'month'           => null,
			//'day'             => null,
			's'               => null,
			'children'        => false,
			'fields'          => null,
			'meta_query'	  => array()
		);

		$args  = wp_parse_args( $args, $defaults );

		if( isset( $args['engine'] ) && strlen( $args['engine'] ) > 0 )  {

			$meta_query = array(
				            'key'     => 'seomonitor_site_engine',
				            'value'   => $args['engine'],
				        );

			$args['meta_query'][] = $meta_query;
		}

		if( isset( $args['group'] ) && strlen( $args['group'] ) > 0 )  {

			$tax_query = array(
				            'taxonomy'  => 'seomonitor_site_group',
				            'field'		=> 'term_id',
				            'terms'   	=> $args['group'],
				        );

			$args['tax_query'][] = $tax_query;
		}

		$cache_key 			= md5( 'seo_monitor_sites_' . serialize( $args ) );

		$sites = wp_cache_get( $cache_key, $this->cache_group );

		if( $sites === false ) {

			$query = new WP_Query( $args );

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();

					$details = new stdClass;

					$site_id               	= get_post()->ID;

					$details->ID           	= $site_id;
					$details->date         	= get_post()->post_date;
					$details->post_status  	= get_post()->post_status;
					$details->name 			= rwmb_meta( 'seomonitor_site_name' );
					$details->main_url     	= rwmb_meta( 'seomonitor_site_main_url' );
					$details->engine     	= rwmb_meta( 'seomonitor_site_engine' );
					$details->country       = rwmb_meta( 'seomonitor_site_country' );
					$details->language      = rwmb_meta( 'seomonitor_site_language' );
					$details->keywords      = rwmb_meta( 'seomonitor_site_keywords' );
					$details->group         = rwmb_meta( 'seomonitor_site_group' );

					$sites[] = apply_filters( 'seo_monitor_sites', $details, $site_id );
				}

				wp_reset_postdata();
			}

			wp_cache_set( $cache_key, $sites, $this->cache_group, 3600 );
		}

		return $sites;
	}

	/**
	*
	* display all sites
	* @since 1.0
	* @return void
	*/
	public static function display_all_sites() {

		$sites_list_table = new Seo_Monitor_Sites_List_Table();
		$sites_list_table->prepare_items();
		?>
		<div class="wrap">
			<h2>
				<?php _e( 'Sites', 'seo-monitor' ); ?>
				<a href="<?php echo admin_url( '/post-new.php?post_type=seomonitor_site' ); ?>" class="add-new-h2"><?php _e( 'Add', 'seo-monitor' ); ?></a>
			</h2>
			<?php do_action( 'seo_monitor_sites_table_top' ); ?>
			<form id="seomonitor-sites-filter" method="get" action="<?php echo admin_url( 'admin.php?page=seomonitor-sites' ); ?>">
				<input type="hidden" name="page" value="seomonitor-sites" />
				<input type="hidden" name="page-type" value="seomonitor_site"/>
			<?php

			$sites_list_table->search_box( __( 'Search Sites', 'seo-monitor' ), 'seomonitor-sites' );
			$sites_list_table->display();
			?>
			</form>
			<?php do_action( 'seo_monitor_sites_table_bottom' ); ?>
		</div>
		<?php
	}

	/**
	* Register custom post type seomonitor_site
	*
	* @since 1.0
	*/
	public function site_cpt_register() {

		$site_labels = array(
					'name'               => __( 'Site', 'seo-monitor' ),
					'singular_name'      => __( 'Site', 'seo-monitor' ),
					'add_new'            => __( 'Add', 'seo-monitor' ),
					'add_new_item'       => __( 'Add Site', 'seo-monitor' ),
					'edit_item'          => __( 'Edit Site', 'seo-monitor' ),
					'new_item'           => __( 'New Site', 'seo-monitor' ),
					'all_items'          => __( 'All Sites', 'seo-monitor' ),
					'view_item'          => __( 'View Site', 'seo-monitor' ),
					'search_items'       => __( 'Search Sites', 'seo-monitor' ),
					'not_found'          => __( 'No sites found', 'seo-monitor' ),
					'not_found_in_trash' => __( 'Site not found in the trash', 'seo-monitor' ),
					'parent_item_colon'  => '',
					'menu_name'          => __( 'Sites', 'seo-monitor' )
		);

		$site_args = array(
			'labels' 				=> $site_labels,
			'public' 				=> false,
			'publicly_queryable'    => false,
            'show_ui' 				=> true,
            'show_in_nav_menus'     => false,
            'show_in_menu'			=> false,
            'show_in_admin_bar'     => false,
            'query_var' 			=> true,
            'capability_type' 		=> 'post',
            'map_meta_cap'      	=> true,
            'exclude_from_search'	=> true, //exclude posts with this post type from front end search results
			'hierarchical' 			=> false,
			'rewrite' 				=> true,
			'supports' 				=> apply_filters( 'seo_monitor_site_supports', false ),
			'has_archive'			=> false,
		);

		register_post_type( 'seomonitor_site', apply_filters( 'seo_monitor_site_post_type_args', $site_args ) );

		register_taxonomy( 'seomonitor_site_group', array( 'seomonitor_site' ),
							array(
								'hierarchical' 		=> true,
								'label' 			=> __( 'Site group', 'seo-monitor' ),
								'singular_label' 	=> __( 'Site group', 'seo-monitor' )
								//'rewrite' 			=> array( 'slug' => 'group' )
							)
		);
	}

	/**
	 * Register meta boxes
	 *
	 * @since 1.0
	 */
	public function register_meta_boxes_site() {

		if ( !class_exists( 'RW_Meta_Box' ) ) {
				return;
		}

		$prefix 			= 'seomonitor_site_';
		$engine_data 		= array();
		$seo_monitor_engine = new Seo_Monitor_Search_Engine();
		$engines 			= $seo_monitor_engine->get_search_engines();

		if( $engines ) {
			foreach ( $engines as $engine ) {
				$engine_data[$engine->ID] = $engine->url;
			}
		}

		$meta_box_site = array(
				'title' => __( 'Site', 'rwmb' ),
				'pages' => array( 'seomonitor_site'),
				'fields' => array(

						array(
								'name' => __( 'Name', 'seo-monitor' ),
								'id'   => "{$prefix}name",
								'type' => 'text',
						),
						array(
								'name' 			=> __( 'Main URL', 'seo-monitor' ),
								'id'   			=> "{$prefix}main_url",
								'type' 			=> 'url',
								'desc' 			=> 'Begins with http://',
								'std'  			=> 'http://',
						),
						array(
                                'name'      => __( 'Language', 'seo-monitor' ),
                                'id'        => "{$prefix}language",
                                'type'      => 'select', //select_advanced
                                'multiple'    => false,
                                //'placeholder' => __( 'Select an Item', 'meta-box' ),
								'options' => array(
												"" 	 => '-- all --',
												"sq" => 'Albanian',
												"ar" => 'Arabic',
												"hy" => 'Armenian',
												"bs" => 'Bosnian',
												"bg" => 'Bulgarian',
												"ca" => 'Catalan',
												"zh" => 'Chinese (Simplified)',
												"cn" => 'Chinese (Traditional)',
												"hr" => 'Croatian',
												"cs" => 'Czech',
												"da" => 'Danish',
												"nl" => 'Dutch',
												"en" => 'English',
												"fa" => 'Farsi',
												"fi" => 'Finnish',
												"fr" => 'French',
												"de" => 'German',
												"el" => 'Greek',
												"he" => 'Hebrew',
												"hi" => 'Hindi',
												"hu" => 'Hungarian',
												"id" => 'Indonesian',
												"it" => 'Italian',
												"ja" => 'Japanese',
												"ko" => 'Korean',
												"lt" => 'Lithuanian',
												"mk" => 'Macedonian',
												"no" => 'Norwegian',
												"pl" => 'Polish',
												"pt" => 'Portuguese',
												"pt-br" => 'Portuguese - Brazil',
												"ro" => 'Romanian',
												"ru" => 'Russian',
												"sr" => 'Serbian',
												"sk" => 'Slovak',
												"sl" => 'Slovenian',
												"es" => 'Spanish',
												"es-ar" => 'Spanish Argentina',
												"sw" => 'Swahili',
												"sv" => 'Swedish',
												"tl" => 'Tagalog',
												"th" => 'Thai',
												"tr" => 'Turkish',
												"uk" => 'Ukrainian',
												"vn" => 'Vietnamese'
											),
                        ),

                        array(
                                'name'      => __( 'Country', 'seo-monitor' ),
                                'id'        => "{$prefix}country",
                                'type'      => 'select', //select_advanced
                                'multiple'    => false,
                                //'placeholder' => __( 'Select an Item', 'meta-box' ),
								'options' => array(
												"" => '-- all --',
												"AF" => 'Afghanistan',
												"AX" => 'Aland Islands',
												"AL" => 'Albania',
												"DZ" => 'Algeria',
												"AS" => 'American Samoa',
												"AD" => 'Andorra',
												"AO" => 'Angola',
												"AI" => 'Anguilla',
												"AQ" => 'Antarctica',
												"AG" => 'Antigua and Barbuda',
												"AR" => 'Argentina',
												"AM" => 'Armenia',
												"AW" => 'Aruba',
												"AP" => 'Asia/Pacific Region',
												"AU" => 'Australia',
												"AT" => 'Austria',
												"AZ" => 'Azerbaijan',
												"BS" => 'Bahamas',
												"BH" => 'Bahrain',
												"BD" => 'Bangladesh',
												"BB" => 'Barbados',
												"BY" => 'Belarus',
												"BE" => 'Belgium',
												"BZ" => 'Belize',
												"BJ" => 'Benin',
												"BM" => 'Bermuda',
												"BT" => 'Bhutan',
												"BO" => 'Bolivia',
												"BA" => 'Bosnia and Herzegovina',
												"BW" => 'Botswana',
												"BV" => 'Bouvet Island',
												"BR" => 'Brazil',
												"IO" => 'British Indian Ocean Territory',
												"BN" => 'Brunei Darussalam',
												"BG" => 'Bulgaria',
												"BF" => 'Burkina Faso',
												"BI" => 'Burundi',
												"KH" => 'Cambodia',
												"CM" => 'Cameroon',
												"CA" => 'Canada',
												"CV" => 'Cape Verde',
												"KY" => 'Cayman Islands',
												"CF" => 'Central African Republic',
												"TD" => 'Chad',
												"CL" => 'Chile',
												"CN" => 'China',
												"CX" => 'Christmas Island',
												"CC" => 'Cocos (Keeling) Islands',
												"CO" => 'Colombia',
												"KM" => 'Comoros',
												"CD" => 'Congo',
												"CG" => 'Congo',
												"CK" => 'Cook Islands',
												"CR" => 'Costa Rica',
												"CI" => 'Cote d\'Ivoire',
												"HR" => 'Croatia',
												"CU" => 'Cuba',
												"CY" => 'Cyprus',
												"CZ" => 'Czech Republic',
												"DK" => 'Denmark',
												"DJ" => 'Djibouti',
												"DM" => 'Dominica',
												"DO" => 'Dominican Republic',
												"EC" => 'Ecuador',
												"EG" => 'Egypt',
												"SV" => 'El Salvador',
												"GQ" => 'Equatorial Guinea',
												"ER" => 'Eritrea',
												"EE" => 'Estonia',
												"ET" => 'Ethiopia',
												"EU" => 'Europe',
												"FK" => 'Falkland Islands (Malvinas)',
												"FO" => 'Faroe Islands',
												"FJ" => 'Fiji',
												"FI" => 'Finland',
												"FR" => 'France',
												"GF" => 'French Guiana',
												"PF" => 'French Polynesia',
												"TF" => 'French Southern Territories',
												"GA" => 'Gabon',
												"GM" => 'Gambia',
												"GE" => 'Georgia',
												"DE" => 'Germany',
												"GH" => 'Ghana',
												"GI" => 'Gibraltar',
												"GR" => 'Greece',
												"GL" => 'Greenland',
												"GD" => 'Grenada',
												"GP" => 'Guadeloupe',
												"GU" => 'Guam',
												"GT" => 'Guatemala',
												"GG" => 'Guernsey',
												"GN" => 'Guinea',
												"GW" => 'Guinea-Bissau',
												"GY" => 'Guyana',
												"HT" => 'Haiti',
												"HM" => 'Heard Island and McDonald Islands',
												"VA" => 'Holy See (Vatican City State)',
												"HN" => 'Honduras',
												"HK" => 'Hong Kong',
												"HU" => 'Hungary',
												"IS" => 'Iceland',
												"IN" => 'India',
												"ID" => 'Indonesia',
												"IR" => 'Iran Islamic Republic of',
												"IQ" => 'Iraq',
												"IE" => 'Ireland',
												"IM" => 'Isle of Man',
												"IL" => 'Israel',
												"IT" => 'Italy',
												"JM" => 'Jamaica',
												"JP" => 'Japan',
												"JE" => 'Jersey',
												"JO" => 'Jordan',
												"KZ" => 'Kazakhstan',
												"KE" => 'Kenya',
												"KI" => 'Kiribati',
												"KP" => 'Korea Democratic People\'s Republic of',
												"KR" => 'Korea Republic of',
												"KW" => 'Kuwait',
												"KG" => 'Kyrgyzstan',
												"LA" => 'Lao People\'s Democratic Republic',
												"LV" => 'Latvia',
												"LB" => 'Lebanon',
												"LS" => 'Lesotho',
												"LR" => 'Liberia',
												"LY" => 'Libyan Arab Jamahiriya',
												"LI" => 'Liechtenstein',
												"LT" => 'Lithuania',
												"LU" => 'Luxembourg',
												"MO" => 'Macao',
												"MK" => 'Macedonia',
												"MG" => 'Madagascar',
												"MW" => 'Malawi',
												"MY" => 'Malaysia',
												"MV" => 'Maldives',
												"ML" => 'Mali',
												"MT" => 'Malta',
												"MH" => 'Marshall Islands',
												"MQ" => 'Martinique',
												"MR" => 'Mauritania',
												"MU" => 'Mauritius',
												"YT" => 'Mayotte',
												"MX" => 'Mexico',
												"FM" => 'Micronesia Federated States of',
												"MD" => 'Moldova Republic of',
												"MC" => 'Monaco',
												"MN" => 'Mongolia',
												"ME" => 'Montenegro',
												"MS" => 'Montserrat',
												"MA" => 'Morocco',
												"MZ" => 'Mozambique',
												"MM" => 'Myanmar',
												"NA" => 'Namibia',
												"NR" => 'Nauru',
												"NP" => 'Nepal',
												"NL" => 'Netherlands',
												"AN" => 'Netherlands Antilles',
												"NC" => 'New Caledonia',
												"NZ" => 'New Zealand',
												"NI" => 'Nicaragua',
												"NE" => 'Niger',
												"NG" => 'Nigeria',
												"NU" => 'Niue',
												"NF" => 'Norfolk Island',
												"MP" => 'Northern Mariana Islands',
												"NO" => 'Norway',
												"OM" => 'Oman',
												"PK" => 'Pakistan',
												"PW" => 'Palau',
												"PS" => 'Palestinian Territory',
												"PA" => 'Panama',
												"PG" => 'Papua New Guinea',
												"PY" => 'Paraguay',
												"PE" => 'Peru',
												"PH" => 'Philippines',
												"PN" => 'Pitcairn',
												"PL" => 'Poland',
												"PT" => 'Portugal',
												"PR" => 'Puerto Rico',
												"QA" => 'Qatar',
												"RE" => 'Reunion',
												"RO" => 'Romania',
												"RU" => 'Russian Federation',
												"RW" => 'Rwanda',
												"SH" => 'Saint Helena',
												"KN" => 'Saint Kitts and Nevis',
												"LC" => 'Saint Lucia',
												"PM" => 'Saint Pierre and Miquelon',
												"VC" => 'Saint Vincent and the Grenadines',
												"WS" => 'Samoa',
												"SM" => 'San Marino',
												"ST" => 'Sao Tome and Principe',
												"SA" => 'Saudi Arabia',
												"SN" => 'Senegal',
												"RS" => 'Serbia',
												"SC" => 'Seychelles',
												"SL" => 'Sierra Leone',
												"SG" => 'Singapore',
												"SK" => 'Slovakia',
												"SI" => 'Slovenia',
												"SB" => 'Solomon Islands',
												"SO" => 'Somalia',
												"ZA" => 'South Africa',
												"GS" => 'South Georgia and the South Sandwich Islands',
												"ES" => 'Spain',
												"LK" => 'Sri Lanka',
												"SD" => 'Sudan',
												"SR" => 'Suriname',
												"SJ" => 'Svalbard and Jan Mayen',
												"SZ" => 'Swaziland',
												"SE" => 'Sweden',
												"CH" => 'Switzerland',
												"SY" => 'Syrian Arab Republic',
												"TW" => 'Taiwan',
												"TJ" => 'Tajikistan',
												"TZ" => 'Tanzania United Republic of',
												"TH" => 'Thailand',
												"TL" => 'Timor-Leste',
												"TG" => 'Togo',
												"TK" => 'Tokelau',
												"TO" => 'Tonga',
												"TT" => 'Trinidad and Tobago',
												"TN" => 'Tunisia',
												"TR" => 'Turkey',
												"TM" => 'Turkmenistan',
												"TC" => 'Turks and Caicos Islands',
												"TV" => 'Tuvalu',
												"UG" => 'Uganda',
												"UA" => 'Ukraine',
												"AE" => 'United Arab Emirates',
												"GB" => 'United Kingdom',
												"US" => 'United States',
												"UM" => 'United States Minor Outlying Islands',
												"UY" => 'Uruguay',
												"UZ" => 'Uzbekistan',
												"VU" => 'Vanuatu',
												"VE" => 'Venezuela',
												"VN" => 'Vietnam',
												"VG" => 'Virgin Islands British',
												"VI" => 'Virgin Islands U.S.',
												"WF" => 'Wallis and Futuna',
												"EH" => 'Western Sahara',
												"YE" => 'Yemen',
												"ZM" => 'Zambia',
												"ZW" => 'Zimbabwe',
											),
                        ),

                        array(
                                'name'      	=> __( 'Engine', 'seo-monitor' ),
                                'id'        	=> "{$prefix}engine",
                                'type'      	=> 'select_advanced',
                                'multiple'		=> true,
                                'options'  		=> $engine_data
                        ),

                        array(
                        		'name'		=> __( 'Keywords', 'seo-monitor' ),
                        		'id'        => "{$prefix}keywords",
                                'type'      => 'textarea',
                                'desc' 		=> 'separated by new line',
                                'cols' 		=> 20,
                                'rows' 		=> 10,
                        	),

						array(
								'name'    => __( 'Site Group', 'seo-monitor' ),
								'id'      => "{$prefix}site_group",
								'type'    => 'taxonomy',
								//'std'     => 1,
								'options' => array(
										// Taxonomy name
										'taxonomy' => 'seomonitor_site_group',
										// How to show taxonomy: 'checkbox_list' (default) or 'checkbox_tree', 'select_tree', select_advanced or 'select'. Optional
										'type' => 'checkbox_list',
										// Additional arguments for get_terms() function. Optional
										'args' => array()
								),
						),
				),

				'validation' => array(
						'rules' => array(
								"{$prefix}name" 		=> array(
										'required'  	=> true,
								),
								"{$prefix}main_url" 	=> array(
										'required'  	=> true,
								),
								"{$prefix}engine"		=> array(
										'required'  	=> true,
								),
								"{$prefix}keywords" 	=> array(
										'required'  	=> true,
								),
						),
						// optional override of default jquery.validate messages
						'messages' => array(
								"{$prefix}keywords"  => array(
										'required'  => __( 'You have to fill in one keyword as minimum', 'seo-monitor' ),
								),
						)
				)
		);

		new RW_Meta_Box( apply_filters( 'seo_monitor_site_meta_box', $meta_box_site ) );
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
	public function get_location() {
		return $this->location;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function get_language() {
		return $this->language;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function get_url() {
		return $this->url;
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
	public function set_name( $value ) {
		$this->name = $value;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function set_url( $value ) {
		$this->url = $value;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function set_searchengines( $value ) {
		$this->searchengines = $value;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function set_keywords( $value ) {
		$this->keywords = $value;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function set_location( $value ) {
		$this->location = $value;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function set_language( $value ) {
		$this->language = $value;
	}
}