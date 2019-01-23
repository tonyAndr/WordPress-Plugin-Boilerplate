<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/admin
 * @author     Your Name <email@example.com>
 */
class Yt_Link_Fixer_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $db;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->db = new Yt_Link_Fixer_DB();

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Yt_Link_Fixer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Yt_Link_Fixer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/yt-link-fixer-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Yt_Link_Fixer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Yt_Link_Fixer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/yt-link-fixer-admin.js', array( 'jquery' ), $this->version, false );

	}


    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    0.1.0
     */
    public function add_plugin_admin_menu() {
        /*
         * Add a tools page for this plugin to the Settings menu.
         *
         * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
         *
         *        Administration Menus: http://codex.wordpress.org/Administration_Menus
         *
         */
        add_management_page( 'Youtube Links Management', 'Yt Link Fixer', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page')
        );
    }
    /**
     * Render the settings page for this plugin.
     *
     * @since    0.1.0
     */
    public function display_plugin_setup_page() {
        include_once( 'partials/yt-link-fixer-admin-display.php' );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    0.1.0
     */
    public function add_action_links( $links ) {
        /*
         *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
         */
        $settings_link = array(
            '<a href="' . admin_url( 'tools.php?page=' . $this->plugin_name ) . '">' . __('Management', $this->plugin_name) . '</a>',
        );
        return array_merge(  $settings_link, $links );
    }

    function add_toolbar_items($wp_admin_bar) {

        $rows = $this->db->get_rows_count();

        if ($rows > 0 ) {
            // add menu
            $wp_admin_bar->add_menu( array(
                'id'		=> 'ytlf',
                'title' => '<span class="ab-icon"></span><span class="update-plugins count-'.$rows.'">'.$rows.'</span>',
                'href' => get_site_url() . '/wp-admin/tools.php?page=yt-link-fixer',
                'meta'   => array(
                    'target'   => '_self',
                    'title'    => sprintf( esc_html__( 'Found %d broken YT links', $this->plugin_name ), $rows ),
                    'html'     => '<!-- Custom HTML that goes below the item -->',
                ),
            ) );

        }

    }



    /**
     *  Save the plugin options
     *
     *
     * @since    0.1.0
     */
    public function options_update() {
        register_setting( $this->plugin_name."-settings", $this->plugin_name."-settings", array($this, 'validate') );
    }

    /**
     * Validate all options fields
     * @since    0.1.0
     * @param      array $input Initial created or updated settings array.
     * @return array
     */
    public function validate($input) {
        // All checkboxes inputs
        $valid = array();

        $valid['auto_replace'] = (isset($input['auto_replace']) && !empty($input['auto_replace'])) ? 1 : 0;
        $valid['replace_not_embeddable'] = (isset($input['replace_not_embeddable']) && !empty($input['replace_not_embeddable'])) ? 1 : 0;
        $valid['email_notify'] = (isset($input['email_notify']) && !empty($input['email_notify'])) ? 1 : 0;
//        $valid['posts_num'] = (isset($input['posts_num']) && !empty($input['posts_num'])) ? $input['posts_num'] : 50;

        return $valid;
    }

}
