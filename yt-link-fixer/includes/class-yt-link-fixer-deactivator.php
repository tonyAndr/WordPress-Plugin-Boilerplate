<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.1.0
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/includes
 * @author     Your Name <email@example.com>
 */
class Yt_Link_Fixer_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.1.0
	 */
	public static function deactivate() {
	    // disable cron
        $timestamp = wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_CHECK );
        wp_unschedule_event( $timestamp, YT_LINK_FIXER_CRON_HOOK_CHECK );

        $timestamp = wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_REPLACE );
        wp_unschedule_event( $timestamp, YT_LINK_FIXER_CRON_HOOK_REPLACE );

        $timestamp = wp_next_scheduled( YT_LINK_FIXER_CRON_HOOK_MAILER );
        wp_unschedule_event( $timestamp, YT_LINK_FIXER_CRON_HOOK_MAILER );
	}

}
