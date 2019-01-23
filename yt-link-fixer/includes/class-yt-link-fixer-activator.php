<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.1.0
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/includes
 * @author     Your Name <email@example.com>
 */
class Yt_Link_Fixer_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.1.0
	 */
	public static function activate() {
        # initial options setup
        $options = get_option('yt-link-fixer-general');
        if (!isset($options["posts_offset"])) $options["posts_offset"] = 0; # offset for parsing
        if (!isset($options["posts_num"])) $options["posts_num"] = 50; # fetch this num of posts each cron call
        update_option('yt-link-fixer-general', $options);

        # schedule cron task
        if ( ! wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_CHECK ) ) {
            wp_schedule_event( time() + 30, 'twicedaily', YT_LINK_FIXER_CRON_HOOK_CHECK );
        }

        $options = get_option('yt-link-fixer-settings');
        if (isset($options["auto_replace"]) && ($options["auto_replace"] === 1)) {
            if ( ! wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_REPLACE ) ) {
                wp_schedule_event( time() + 60, 'twicedaily', YT_LINK_FIXER_CRON_HOOK_REPLACE );
            }
        }
        if (!isset($options["email_notify"]) || ($options["email_notify"] === 1)) {
            if ( ! wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_MAILER ) ) {
//                wp_schedule_event( time(), 'hourly', YT_LINK_FIXER_CRON_HOOK_MAILER );
            wp_schedule_event( time() + 604800, 'ytlf_once_per_week', YT_LINK_FIXER_CRON_HOOK_MAILER );
            }
        }
	}

}
