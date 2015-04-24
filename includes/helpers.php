<?php

function create_ranking_sample_data() {

    $number_of_sample_data = 15;

    $keyword    = new Seo_Monitor_Keyword();
    $keywords   = $keyword->get_keywords();

    $seo_monitor_rank    = new Seo_Monitor_Rank();

    if( $keywords ) {
        foreach ( $keywords as $keyword ) {

            for ( $i=0; $i < $number_of_sample_data; $i++ ) {

                $date = date( 'Y-m-d', strtotime( "-$i days" ) );

                $ranking_data = array(
                    'id'            => $keyword->id,
                    'rank'          => rand( 1, 100 ),
                    'ranking_url'   => 'http://www.test.nl',
                    'last_check'    => $date
                );

                $seo_monitor_rank->update_rank( $ranking_data );
            }
        }
    }
}

// generic filter function
add_action( 'restrict_manage_posts', 'seomonitor_taxonomy_filter_restrict_manage_posts' );
add_filter( 'parse_query', 'seomonitor_taxonomy_filter_post_type_request' );

// Filter the request to just give posts for the given taxonomy, if applicable.
function seomonitor_taxonomy_filter_restrict_manage_posts() {
    global $typenow;

    // If you only want this to work for your specific post type,
    // check for that $type here and then return.
    // This function, if unmodified, will add the dropdown for each
    // post type / taxonomy combination.

    $post_types = get_post_types( array( '_builtin' => false ) );

    if ( in_array( $typenow, $post_types ) ) {
    	$filters = get_object_taxonomies( $typenow );

        foreach ( $filters as $tax_slug ) {
            $tax_obj = get_taxonomy( $tax_slug );
            wp_dropdown_categories( array(
                'show_option_all' => __( 'Show All '.$tax_obj->label ),
                'taxonomy' 	  	=> $tax_slug,
                'name' 		  	=> $tax_obj->name,
                'orderby' 	  	=> 'name',
                'selected' 	  	=> isset($_GET[$tax_slug]) ? $_GET[$tax_slug] : '',
                'hierarchical' 	=> $tax_obj->hierarchical,
                'show_count' 	=> false,
                'hide_empty' 	=> true
            ) );
        }
    }
}

function seomonitor_taxonomy_filter_post_type_request( $query ) {
  global $pagenow, $typenow;

  if ( 'edit.php' == $pagenow ) {
    $filters = get_object_taxonomies( $typenow );
    foreach ( $filters as $tax_slug ) {
      $var = &$query->query_vars[$tax_slug];
      if ( isset( $var ) ) {
        $term = get_term_by( 'id', $var, $tax_slug );
        $var = $term->slug;
      }
    }
  }
}

/**
 * Include the TGM_Plugin_Activation class.
 */
require_once dirname( __FILE__ ) . '/libraries/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'seo_monitor_register_required_plugins' );

/**
 * Register required plugins
 * @return void
 * @since  1.0
 */
function seo_monitor_register_required_plugins() {

    $plugins = array(
        array(
            'name'               => 'Meta Box',
            'slug'               => 'meta-box',
            'required'           => true,
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        // You can add more plugins here if you want
    );
    $config  = array(
        'default_path'     => '',                   // Default absolute path to pre-packaged plugins.
        'menu'             => 'tgmpa-install-plugins', // Menu slug.
        'has_notices'      => true,                 // Show admin notices or not.
        'is_automatic'     => false,                 // Automatically activate plugins after installation or not.
        'dismissable'      => false,                // If false, a user cannot dismiss the nag message
        'message'          => '',
        'strings'          => array(
            'page_title'                      => __( 'Install Required Plugins', 'seo-monitor' ),
            'menu_title'                      => __( 'Install Plugins', 'seo-monitor' ),
            'installing'                      => __( 'Installing Plugin: %s', 'seo-monitor' ),
            'oops'                            => __( 'Something went wrong with the plugin API.', 'seo-monitor' ),
            'notice_can_install_required'     => _n_noop( 'This plugin requires the following plugin: %1$s.', 'This plugin requires the following plugins: %1$s.' ),
            'notice_can_install_recommended'  => _n_noop( 'This plugin recommends the following plugin: %1$s.', 'This plugin recommends the following plugins: %1$s.' ),
            'notice_cannot_install'           => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.' ),
            'notice_can_activate_required'    => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.' ),
            'notice_can_activate_recommended' => _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.' ),
            'notice_cannot_activate'          => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.' ),
            'notice_ask_to_update'            => _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.' ),
            'notice_cannot_update'            => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.' ),
            'install_link'                    => _n_noop( 'Begin installing plugin', 'Begin installing plugins' ),
            'activate_link'                   => _n_noop( 'Activate installed plugin', 'Activate installed plugins' ),
            'return'                          => __( 'Return to Required Plugins Installer', 'seo-monitor' ),
            'plugin_activated'                => __( 'Plugin activated successfully.', 'seo-monitor' ),
            'complete'                        => __( 'All plugins installed and activated successfully. %s', 'seo-monitor' ),
            'nag_type'                        => 'updated',
        )
    );

    tgmpa( $plugins, $config );
}