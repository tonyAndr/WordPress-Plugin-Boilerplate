<?php

/**
 * The logger.
 *
 * Read/write to the log file
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/api
 */

/**
 * The logger.
 *
 * Read/write to the log file
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/api
 * @author     Your Name <email@example.com>
 */

class Yt_Link_Fixer_Cron {

//    const AUTO_REPLACE = 0;
//    const REPLACE_NOT_EMBEDDABLE = 0;

    private $logger;
    private $parser;
    private $db;
    private $plugin_name;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 */
	public function __construct($plugin_name) {
	    $this->logger = new Yt_Link_Fixer_Logging();
	    $this->parser = new Yt_Link_Fixer_Post_Parser($plugin_name);
	    $this->db = new Yt_Link_Fixer_DB();
	    $this->plugin_name= $plugin_name;
	}

	public function cron_rescheduler($option_name, $old, $new) {
	    if ($option_name === $this->plugin_name."-settings" && $new["auto_replace"] !== $old["auto_replace"] ) {
            if (isset($new["auto_replace"]) && ($new["auto_replace"] === 1)) {
                //enable
                if ( ! wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_REPLACE ) ) {
                    wp_schedule_event( time(), 'twicedaily', YT_LINK_FIXER_CRON_HOOK_REPLACE );
                    $this->logger->write("Replacer was enabled on option_updated", "CRON", "OK");
                } else {
                    $this->logger->write("Replacer is enabled already, no action required on option_updated", "CRON", "OK");
                }
            } else {
                //disable
                $timestamp = wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_REPLACE );
                if (!$timestamp) {
                    $this->logger->write("Replacer is disabled already, no action required on option_updated", "CRON", "OK");
                } else {
                    wp_unschedule_event( $timestamp, YT_LINK_FIXER_CRON_HOOK_REPLACE );
                    $this->logger->write("Replacer was disabled successfully on option_updated", "CRON", "OK");
                }
            }
        }
        if ($option_name === $this->plugin_name."-settings" && $new["email_notify"] !== $old["email_notify"] ) {
            if (isset($new["email_notify"]) && ($new["email_notify"] === 1)) {
                //enable
                if ( ! wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_MAILER ) ) {
                    wp_schedule_event( time(), 'ytlf_once_per_week', YT_LINK_FIXER_CRON_HOOK_MAILER );
                    $this->logger->write("Email notifications were enabled successfully", "EMAIL", "OK");
                } else {
                    $this->logger->write("Notifications are enabled already, no action required on option_updated", "EMAIL", "OK");
                }
            } else {
                //disable
                $timestamp = wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_MAILER );
                if (!$timestamp) {
                    $this->logger->write("Notifications are disabled already, no action required on option_updated", "EMAIL", "OK");
                } else {
                    wp_unschedule_event( $timestamp, YT_LINK_FIXER_CRON_HOOK_MAILER );
                    $this->logger->write("Email notifications were disabled successfully on option_updated", "EMAIL", "OK");
                }
            }
        }

    }

	public function execute_check() {
	    $this->logger->write("Fetching posts on schedule", "CRON", "START");
        $this->parser->fetch_posts();
        $this->logger->write("Fetching posts on schedule", "CRON", "FINISHED");
    }

	public function execute_replace() {

	    $this->logger->write("Replace broken links on schedule", "CRON", "START");
	    $results = $this->db->get_rows(null, 3); // load limited num of rows (second arg)

        foreach ($results as $result) {
            $this->parser->replace_broken_link($result->id);
        }

        if (!$results)
            $this->logger->write("The DB table is empty, no broken links found", "CRON", "OK");

        $this->logger->write("Replace broken links on schedule", "CRON", "FINISHED");
    }


    public function send_email_notification() {
	    $rows = $this->db->get_rows_count();

	    if ($rows > 0) {
            $this->setup_encoding();

	        $sent = $this->notify_user($rows);

	        if (!$sent) {
                $this->logger->write("Could not send email", "EMAIL", "ERR");
            }
        }
    }



    private function notify_user($data) {
	    $admin_email = get_option('admin_email');
	    $plugin_url = get_site_url() . "/wp-admin/tools.php?page=yt-link-fixer";
        $body = "На прошлой неделе плагин Yt Link Fixer нашел на вашем сайте <strong>$data сломанных ссылок</strong> на YouTube.
        <br><br>
        Перейти на страницу управления плагина для редактирования ссылок: <a href='$plugin_url' title='Управление ссылками'>$plugin_url</a>
        <br><br>
        Чтобы отключить эти уведомления, перейдите в настройки плагина по ссылке: <a href='$plugin_url&tab=options' title='Настройки'>$plugin_url&tab=options</a>
        ";
        $subject = "Найдены сломанные YouTube ссылки на сайте: " . get_bloginfo('name');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $headers[] = 'From: '.get_bloginfo("name").' <'.$admin_email.'>';
        // mail to buyer
        return wp_mail($admin_email, $subject, $body, $headers);
    }

    private function setup_encoding() {
        add_filter('wp_mail_content_type', function(){ return "text/html";});
        add_filter('wp_mail_charset', function(){ return "UTF-8";});

    }
    public function onMailError( $wp_error ) {
        $this->logger->write($wp_error->get_error_message(), "EMAIL", "ERR");
    }
    public function add_weekly_cron_interval( $schedules ) {
        $schedules['ytlf_once_per_week'] = array(
            'interval' => 604800,
            'display'  => esc_html__( 'Once a Week' ),
        );

        return $schedules;
    }
}
