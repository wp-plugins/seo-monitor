<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Seo_Monitor
 * @subpackage Seo_Monitor/admin
 * @author     To Be On The Web <info@tobeontheweb.nl>
 */
class Seo_Monitor_Admin_Settings {

	private $options;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Set class property
		$this->options = get_option( 'seomonitor_settings' );
	}

	public function register_settings() {

		register_setting( 'seomonitor_settings',		// Option group
						  'seomonitor_settings', 		// Option name
						  array( $this, 'sanitize' ) 	// Sanitize
		);

		add_settings_section(
			'seomonitor_general_id',                         // ID
			__( 'General', 'seo-monitor' ),                                // Title
			array( $this, 'seomonitor_general_info' ),       // Callback
			'seomonitor-setting'                     // menu_slug
		);

		add_settings_field(
			'seomonitor_auto_update',               				// ID
			__( 'Auto update ranks', 'seo-monitor' ),                                 			// Title
			array( $this, 'seomonitor_auto_update_callback' ),  	// Callback
			'seomonitor-setting',                    				// menu_slug
			'seomonitor_general_id'                    					// Section ID
		);

		add_settings_field(
			'seomonitor_external_cronjob',               				// ID
			__( 'Use external cron job', 'seo-monitor' ),                                 			// Title
			array( $this, 'seomonitor_external_cronjob_callback' ),  	// Callback
			'seomonitor-setting',                    				// menu_slug
			'seomonitor_general_id'                    					// Section ID
		);

		add_settings_section(
			'moz_section_id',                         // ID
			__( 'MOZ API', 'seo-monitor' ),                                // Title
			array( $this, 'moz_section_info' ),       // Callback
			'seomonitor-setting'                     // menu_slug
		);

		add_settings_field(
			'seomonitor_moz_accessid',               				// ID
			__( 'Access ID', 'seo-monitor' ),                                 			// Title
			array( $this, 'seomonitor_moz_accessid_callback' ),  	// Callback
			'seomonitor-setting',                    				// menu_slug
			'moz_section_id'                    					// Section ID
		);

		add_settings_field(
			'seomonitor_moz_secretkey',                  			// ID
			__( 'Secret Key', 'seo-monitor' ),                                     		// Title
			array( $this, 'seomonitor_moz_secretkey_callback' ),    // Callback
			'seomonitor-setting',                                   // menu_slug
			'moz_section_id'                                        // Section ID
		);

		add_settings_section(
			'setting_section_id', 						// ID
			__( 'SEO Monitor Settings', 'seo-monitor' ), 					// Title
			array( $this, 'print_section_info' ), 		// Callback
			'seomonitor-setting'						// menu_slug
		);

		add_settings_field(
			'seomonitor_enable_proxies', 								// ID
			__( 'Enable Proxies', 'seo-monitor' ), 											// Title
			array( $this, 'seomonitor_enable_proxies_callback' ), 		// Callback
			'seomonitor-setting',										// menu_slug
			'setting_section_id' 										// Section ID
		);

		add_settings_field(
			'seomonitor_proxies',  								// ID
			__( 'Proxy list', 'seo-monitor' ),										// Title
			array( $this, 'seomonitor_proxies_callback' ), 		// Callback
			'seomonitor-setting',								// menu_slug
			'setting_section_id' 								// Section ID
		);

		// Set default value seomonitor_auto_update_callback
		add_option( 'seomonitor_settings', array( 'seomonitor_auto_update' => true ) );
	}

	/**
	 * Options page callback
	 */
	public function seomonitor_options() {

		if( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page', 'seo-monitor' ) );
		}

		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Seo Monitor Settings</h2>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'seomonitor_settings' ); // Options Group
				do_settings_sections( 'seomonitor-setting'	); // menu_slug
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {

		$new_input = array();

		if( isset( $input['seomonitor_auto_update'] ) ) {
			$new_input['seomonitor_auto_update'] = $input['seomonitor_auto_update'];
		}

		if( isset( $input['seomonitor_external_cronjob'] ) ) {
			$new_input['seomonitor_external_cronjob'] = $input['seomonitor_external_cronjob'];
		}

		if( isset( $input['seomonitor_enable_proxies'] ) ) {
			$new_input['seomonitor_enable_proxies'] = $input['seomonitor_enable_proxies'];
			//$new_input['seomonitor_enable_proxies'] = absint( $input['seomonitor_enable_proxies'] );
		}

		if( isset( $input['seomonitor_proxies'] ) ) {
			//$new_input['seomonitor_proxies'] = sanitize_text_field( $input['seomonitor_proxies'] );
			$new_input['seomonitor_proxies'] = $input['seomonitor_proxies'];
		}

		if( isset( $input['seomonitor_moz_accessid'] ) ) {
			$new_input['seomonitor_moz_accessid'] = sanitize_text_field( $input['seomonitor_moz_accessid'] );
		}

		if( isset( $input['seomonitor_moz_secretkey'] ) ) {
			$new_input['seomonitor_moz_secretkey'] = sanitize_text_field( $input['seomonitor_moz_secretkey'] );
		}

		return $new_input;
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function seomonitor_auto_update_callback() {

		$checked = $this->options['seomonitor_auto_update'];

		?>
			<input type="checkbox" id="seomonitor_auto_update" name="seomonitor_settings[seomonitor_auto_update]" value="1" <?php checked( true, $checked ); ?> />
		<?php
		 	_e( 'Tick this option for the plugin to update ranks daily for each keyword', 'seo-monitor' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function seomonitor_external_cronjob_callback() {

		$checked = $this->options['seomonitor_external_cronjob'];

		?>
			<input type="checkbox" id="seomonitor_external_cronjob" name="seomonitor_settings[seomonitor_external_cronjob]" value="1" <?php checked( $checked ); ?> />
		<?php
		 	_e( 'if you don\'t use an external cron job the plugin will use the internal wordpress cron job that is triggered by the site visitors', 'seo-monitor' );

		 	echo '<div><br/><strong>';
		 	_e( 'External Cronjob command: ', 'seo-monitor' );
		 	if( strlen( $_SERVER['HTTPS'] ) > 0 ) {
				$protocol = 'https://';
		 	} else {
		 		$protocol = 'http://';
		 	}
		 	echo '</strong> wget -O - -q ' . $protocol . $_SERVER['HTTP_HOST'] . '/?seomonitor_cron_update_rankings=1';
		 	echo '</div>';
	}

	public function seomonitor_general_info() {
		_e( 'general settings Seo Monitor:', 'seo-monitor' );
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {
		_e( 'Enter your settings below:', 'seo-monitor' );
	}

	/**
	 * Print the Section text
	 */
	public function moz_section_info() {
		_e( 'Provide your Mozscape API key to reveal SEO related information', 'seo-monitor' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function seomonitor_enable_proxies_callback() {

		$checked = $this->options['seomonitor_enable_proxies'];

		?>
			<input type="checkbox" id="seomonitor_enable_proxies" name="seomonitor_settings[seomonitor_enable_proxies]" value="1" <?php checked( $checked ); ?> />
		<?php
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function seomonitor_proxies_callback() {

		printf(
			'<textarea cols="80" rows="7" id="seomonitor_proxies" name="seomonitor_settings[seomonitor_proxies]" />%s</textarea>',
			isset( $this->options['seomonitor_proxies'] ) ? esc_attr( $this->options['seomonitor_proxies'] ) : ''
		);
	}

	public function seomonitor_moz_accessid_callback() {
		printf(
			'<input type="text" id="seomonitor_moz_accessid" name="seomonitor_settings[seomonitor_moz_accessid]" value="%s" />
				<label for="seomonitor_moz_accessid">%s</label>',
			isset( $this->options['seomonitor_moz_accessid'] ) ? esc_attr( $this->options['seomonitor_moz_accessid'] ) : '',
			__( 'Enter your Access ID:', 'seo-monitor' )
		);
	}

	public function seomonitor_moz_secretkey_callback() {
		printf(
			'<input type="text" id="seomonitor_moz_secretkey" name="seomonitor_settings[seomonitor_moz_secretkey]" value="%s" />
				<label for="seomonitor_moz_secretkey">%s</label>',
			isset( $this->options['seomonitor_moz_secretkey'] ) ? esc_attr( $this->options['seomonitor_moz_secretkey']) : '',
			__( 'Enter your Secret Key:', 'seo-monitor' )
		);
	}

}