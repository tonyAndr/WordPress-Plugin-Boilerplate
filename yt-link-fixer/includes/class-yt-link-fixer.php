<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.1.0
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/includes
 * @author     Your Name <email@example.com>
 */
class Yt_Link_Fixer {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      Yt_Link_Fixer_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    0.1.0
	 */
	public function __construct() {
		if ( defined( 'YT_LINK_FIXER_VERSION' ) ) {
			$this->version = YT_LINK_FIXER_VERSION;
		} else {
			$this->version = '0.1.0';
		}
		$this->plugin_name = 'yt-link-fixer';

		$this->load_dependencies();
		$this->set_locale();
		$this->setup_db();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->setup_cron();
		$this->setup_ajax_hooks();
		$this->setup_ytapi_hooks();

		$this->plugin_updates();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Yt_Link_Fixer_Loader. Orchestrates the hooks of the plugin.
	 * - Yt_Link_Fixer_i18n. Defines internationalization functionality.
	 * - Yt_Link_Fixer_Admin. Defines all hooks for the admin area.
	 * - Yt_Link_Fixer_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * Load composer deps
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-yt-link-fixer-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-yt-link-fixer-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-yt-link-fixer-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-yt-link-fixer-public.php';

		/**
		 * Useful functions
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-yt-link-fixer-utils.php';

		/**
		 * DB communication
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-yt-link-fixer-db.php';

		/**
		 * YouTube Api communication
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-yt-link-fixer-youtube-apiv3.php';

		/**
		 * Logging class
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-yt-link-fixer-logging.php';

		/**
		 * Post Parser
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-yt-link-fixer-post-parser.php';

		/**
		 * Cron
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-yt-link-fixer-cron.php';

		$this->loader = new Yt_Link_Fixer_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Yt_Link_Fixer_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Yt_Link_Fixer_i18n();

        $plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	private function setup_db() {
	    $db = new Yt_Link_Fixer_DB($this->get_plugin_name());

        $this->loader->add_action( 'plugins_loaded', $db, 'update_db' );
    }

	private function setup_cron() {
	    $cron = new Yt_Link_Fixer_Cron($this->get_plugin_name());

	    $this->loader->add_filter('cron_schedules', $cron, 'add_weekly_cron_interval');

        $this->loader->add_action( YT_LINK_FIXER_CRON_HOOK_CHECK, $cron, 'execute_check' );
        $this->loader->add_action( YT_LINK_FIXER_CRON_HOOK_REPLACE, $cron, 'execute_replace' );
        $this->loader->add_action( YT_LINK_FIXER_CRON_HOOK_MAILER, $cron, 'send_email_notification' );

        $this->loader->add_action( 'updated_option', $cron, 'cron_rescheduler', 10, 3 );
        $this->loader->add_action( 'wp_mail_failed', $cron, 'onMailError', 10, 3 );
	}

	// Ajax
    private function setup_ajax_hooks() {
	    $pp = new Yt_Link_Fixer_Post_Parser($this->get_plugin_name());

	    $this->loader->add_action('wp_ajax_ajax_get_suggestions', $pp, 'ajax_get_suggestions');
	    $this->loader->add_action('wp_ajax_ajax_replace_link', $pp, 'ajax_replace_link');
    }

    private function setup_ytapi_hooks() {
	    $apiv3 = new Yt_Link_Fixer_ApiV3($this->get_plugin_name());

	    $this->loader->add_action('wpw_ajax_clear_cache', $apiv3, 'clear_cache');
    }

    private function plugin_updates () {
        $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
            'http://seocherry.ru/plugin-updates/yt-link-fixer/last_version.json',
            plugin_dir_path( dirname( __FILE__ ) ) . 'yt-link-fixer.php', //Full path to the main plugin file or functions.php.
            $this->get_plugin_name()
        );
    }

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Yt_Link_Fixer_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        // Save/Update our plugin options
        $this->loader->add_action( 'admin_init', $plugin_admin, 'options_update');

        // Add menu item
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
        // Add Settings link to the plugin
        $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
        $this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );

        // Top bar menu
        $this->loader->add_action('admin_bar_menu', $plugin_admin, 'add_toolbar_items', 999);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Yt_Link_Fixer_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.1.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.1.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.1.0
	 * @return    Yt_Link_Fixer_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.1.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
