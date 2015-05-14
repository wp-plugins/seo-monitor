<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/admin
 *
 *
 *
 *
 *  All search requests must include the parameters site, client, q, and output. All parameter values
 *  must be URL-encoded (see â€œAppendix B: URL Encodingâ€ on page 94), except where otherwise noted.
 *
 *
 *
 * Each search engine results page (SERP) has a similar layout:
 *
 *   The main search results are usually in a html container element (#main, .results, #leftSide).
 *   There might be separate columns for other search results (like ads for example). Then each
 *   result contains basically a link, a snippet and a description (usually some text on the
 *   target site). It's really astonishing how similar other search engines are to Google.
 *
 *   Each child class (that can actual parse a concrete search engine results page) needs
 *   to specify css selectors for the different search types (Like normal search, news search, video search, ...).
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Seo_Monitor_Search_Engine_Google {

	//const REG_EXP_GOOGLE    = '/<h3 class\="r"><a href\="(.*?)"/';

    /*
    <li.*?class="?g.*?<a.*?href="\/url\?q=(.*?)&amp;sa=U.*?>(.*?)<\/a>.*?<\/div><span.*?>(.*?)<\/span>
    */

    ///html//div[@id="ires"]/ol/li//h3[not(ancestor::span)]/a

    /**
    * @since 1.0
    */
    private $num_results_search_selector = 'div[@id=resultStats]'; //total search results selector

    /**
    * @since 1.0
    */
    private $search_selectors = array(
                'container'         => 'div[@id="center_col"]',
                'result_container'  => 'li[@class="g"]',
                'link'              => 'h3[@class="r"]/a',
                'snippet'           => 'div[@class="s"]/span[@class="st"]',
                'title'             => 'h3.[@class="r"]/a',
                'visible_link'      => 'cite'
    );

    /**
    * @since 1.0
    */
    private $image_search_selectors = array(
                'container'         => 'li[@id="isr_mc"]',
                'result_container'  => 'div[@class="rg_di"]',
                'link'              => 'a[@class="rg_l"]'
    );

    /**
    * @since 1.0
    */
    private $clean_regexes = array (
            'normal'    => '/\/url\?q=(.*?)&sa=U.*?/',
            'image'     => '/imgres\?imgurl=(.*?)&/'
    );

    /*
    A typical scraped results looks like the following:

    '/url?q=http://www.youtube.com/user/Apple&sa=U&ei=\
    lntiVN7JDsTfPZCMgKAO&ved=0CFQQFjAO&usg=AFQjCNGkX65O-hKLmyq1FX9HQqbb9iYn9A'

    Clean with a short regex.
    */

    // The google base search url
    private $google_search_url = 'https://www.google.com/search?';

    private $per_page               = 100;
    private $number_of_pages        = 1;

	public function __construct() {

		add_filter( 'seo_monitor_google_query', array( $this, 'prepare_query_string_google_search' ), 10, 5 );
        add_filter( 'seo_monitor_google_search_selector', array( $this, 'get_search_selectors' ) );
        add_filter( 'seo_monitor_google_clean_regexes', array( $this, 'get_clean_regexes' ) );
        add_filter( 'seo_monitor_google_number_of_pages', array( $this, 'get_number_of_pages' ) );
	}

    /**
    * @since 1.0
    */
	public function prepare_query_string_google_search( $search_query, $keyword, $location, $language, $page_number ) {
		$search_query 	= $this->add_search_query_google( $keyword, $location, $language, $page_number );
        return $search_query;
	}

    /*
	public function get_pattern( $pattern ) {
		return self::REG_EXP_GOOGLE;
	}
    */

    /**
    * @since 1.0
    */
    public function get_search_selectors() {
        return $this->search_selectors;
    }

    /**
    * @since 1.0
    */
    public function get_number_of_pages() {
        return $this->number_of_pages;
    }

    /**
    * @since 1.0
    */
    public function get_clean_regexes() {
        return $this->clean_regexes;
    }

    /**
    * @param string $value base url without query path
    *
    * @since 1.0
    */
    public function set_base_url( $value ) {
        $this->google_search_url = $value . '/search?';
    }

    /**
    * @since 1.0
    */
    public function get_base_url() {
        $google_search_url = $this->google_search_url;
        return apply_filters( 'seo_monitor_get_google_search_url', $google_search_url );
    }

    /**
    * http://www.google.com/search?hl=[--lang--]&num=[--num--]&q=[--keyword--]&start=[--start--]&cr=country[--country--]&as_qdr=all
    * @since 1.0
    */
    public function add_search_query_google( $keyword, $location, $language, $page_number ) {

        return $this->get_base_url() . http_build_query( array(
                // Query
                'q'         => $keyword,   //the search query string

                //'as_epq'  => urlencode( query+goes+here ), //Results must include the query, in the word order displayed.

                //'oq'        => null,                  // Shows the original query.
                'num'       => $this->per_page,         // Number of result to a page, max is 100
                //'numgm'     => null,
                // Number of KeyMatch results to return with the results. A value between 0 to 50 can be specified for this option.

                /*
                as_oq="query+string"+goes+here

                Results must include one or more of the words in this string. Basically, it's like a more advanced version of the one above, using an "or" filter. Thus, every result must have the main initial query, and one or more of the sets of terms in these strings.

                Shows as "query string" OR goes OR here

                as_eq=don't+include+these+words

                Results must NOT include any words in this string.

                Shows as -don't -include -these -words
                */

                'start'     => ( ( $page_number - 1 ) * $this->per_page ),
                // Specifies the index number of the first entry in the result set that is to be returned.
                // page number = (start / num) + 1
                // The maximum number of results available for a query is 1,000, i.e., the value of the start parameter added to
                // the value of the num parameter cannot exceed 1,000.

                //'rc'        => null,                  // Request an accurate result count for up to 1M documents.
                //'site'      => null,
                // Limits search results to the contents of the specified collection. If a user submits a search query without
                // the site parameter, the entire search index is queried.

                'client'    => 'firefox-a',
                // Required parameter. If this parameter does not have a valid value, other parameters in the query string
                // do not work as expected. Set to 'firefox-a' in mozilla firefox
                // A string that indicates a valid front end and the policies defined for it, including KeyMatches, related
                // queries, filters, remove URLs, and OneBox Modules. Notice that the rendering of the front end is
                // determined by the proxystylesheet parameter. Example: client=myfrontend

                //'output'    => null,
                // required parameter. Selects the format of the search results. 'xml_no_dtd XML' : XML results or custom
                // HTML, 'xml': XML results with Google DTD reference. When you use this value, omit proxystylesheet.

                // personalization turned off
                'pws'       => 0,
                'filter'    => 0,  # Include omitted results if set to 0
                'safe'      => 'off',  # Turns the adult content filter on or off
                'ie'        => 'UTF-8',  # Sets the character encoding that is used to interpret the query string.
                'oe'        => 'UTF-8',  # Sets the character encoding that is used to encode the results.
                'access'    => 'a',  # Specifies whether to search public content (p), secure content (s), or both (a).

                // Country (geolocation presumably)
                'gl'        => $location,
                // Simulates a click on the normal Google results button
                'btnG'      => 'Search',
                'hl'        => 'en', //interface language
                'channel'   => 'fs'
            ), '', '&' );

        /*
        if search_type == 'image':
            search_params.update({
                'oq': query,
                'site': 'imghp',
                'tbm': 'isch',
                'source': 'hp',
                # 'sa': 'X',
                'biw': 1920,
                'bih': 881
            })
        elif search_type == 'video':
            search_params.update({
                'tbm': 'vid',
                'source': 'lnms',
                'sa': 'X',
                'biw': 1920,
                'bih': 881
            })
        elif search_type == 'news':
            search_params.update({
                'tbm': 'nws',
                'source': 'lnms',
                'sa': 'X'
            })
        */

    /*
    private function queryToUrl( $query, $start=null, $perPage=100, $country="US" ) {
        return "http://www.google.com/search?" . $this->_helpers->url->buildQuery(array(
            // Query
            "q"     => urlencode($query),
            // Country (geolocation presumably)
            "gl"    => $country,
            // Start offset
            "start" => $start,
            // Number of result to a page
            "num"   => $perPage
        ), true);
    }
    */

        //http://www.google.com/search?hl=[--lang--]&num=[--num--]&q=[--keyword--]&start=[--start--]&cr=country[--country--]&as_qdr=all
    }
}