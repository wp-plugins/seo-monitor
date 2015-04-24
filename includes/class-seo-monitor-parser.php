<?php

class Seo_Monitor_Parser {

	/**
	* Array
	* @since 1.0
	*/
	private $search_selectors;

	private $clean_regexes;

	private $search_type;

	private $xpath;

	/**
	* Constructor
	*
	* @param html: The raw html from the search engine search
	* @param search_type: The search type. By default "normal"
	* @since 1.0
	*/
	public function __construct( $html, $search_type = 'normal' ) {

		$this->set_search_type( $search_type );

        $dom = new DOMDocument();

        // We don't want to bother with white spaces
		$dom->preserveWhiteSpace = false;

        // Parse the HTML from the search engine.
        // The @ before the method call suppresses any warnings that loadHTML might throw because of invalid HTML in the page.
        @$dom->loadHTML( $html );

        /* Use internal libxml errors -- turn on in production, off for debugging */
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        	libxml_use_internal_errors( false );
        } else {
			libxml_use_internal_errors( true );
		}

        $this->xpath = new DOMXPath( $dom );
	}

	/**
	*
	* example xpath with union: //td[@class='name']/a/@href|//td[@class='name']
	* @since 1.0
	*/
	public function parse() {

		// 1) let's see if the search query was shitty (no results for that query)

		// 2) the element that notifies the user about no results.

		// 3) get the stuff that is of interest in SERP pages.

		$search_selectors = $this->get_search_selectors();
		$result_container = $this->xpath->query( '//' . $search_selectors['link'] );

		$serp_results = array();

		foreach ( $result_container as $node ) {

			$serp_results[] = array(
				'title'		=> $node->nodeValue,
				'link'		=> $this->clean_url( $node->getAttribute('href') ),
			);
        }

        return $serp_results;

	}

	public function set_search_type( $value ) {
		$this->search_type = $value;
	}

	public function get_search_type() {
		return $this->search_type;
	}

	public function set_clean_regexes( $clean_regexes ) {
		$this->clean_regexes = $clean_regexes;
	}

	public function get_clean_regexes() {
		return $this->clean_regexes;
	}

	public function get_search_selectors() {
		return $this->search_selectors;
	}

	public function set_search_selectors( $search_selectors ) {
		$this->search_selectors = $search_selectors;
	}

	/**
	* Will clean the url if it is dirty
	* @param string
	* @return string
	* @since 1.0
	*/
	public function clean_url( $dirty_url ) {

		$clean_regexes = $this->get_clean_regexes();

		if( !empty( $clean_regexes ) ) {

			if( $this->get_search_type() == 'normal' ) {
				preg_match( $clean_regexes['normal'], $dirty_url, $matches );
			} else {
				preg_match( $clean_regexes['image'], $dirty_url, $matches );
			}
		}

		if( !empty( $matches ) && $matches[1] ) {
			$clean_url = $matches[1];
		} else {
			$clean_url = $dirty_url;
		}

		return $clean_url;

	}
}