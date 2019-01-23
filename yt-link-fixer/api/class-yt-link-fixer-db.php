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

class Yt_Link_Fixer_DB {

    const LATEST_DB_VERSION = '1.4';

    private $table_name;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 */
	public function __construct() {
	    global $wpdb;
	    $this->table_name = $wpdb->prefix."yt_broken_links";
	}

	public function add_row($post_id, $vid_id, $vid_type, $status) {
        global $wpdb;

        return $wpdb->insert($this->table_name,
            array(
                'post_id' => $post_id,
                'vid_id' => $vid_id,
                'vid_type' => $vid_type,
                'status' => $status,
                'check_time' => current_time ('mysql')
            ));
        # returns false or id
    }

    public function del_row($item_id) {
        global $wpdb;

        return $wpdb->delete($this->table_name, array(
            'id' => $item_id
        ));
        # returns false or num of rows
    }

    public function get_rows($item_id = null, $limit = null) {
        global $wpdb;

        $where = '';
        if ($item_id != null) {
            $where = " WHERE id = $item_id";
        }

        if ($limit != null) {
            $limit = " LIMIT ".$limit;
        }

        $res = $wpdb->get_results("SELECT * FROM $this->table_name". $where . $limit, OBJECT);

        return $res;

        # returns array (may be empty) or NULL on error
    }

    public function get_rows_count() {
	    global $wpdb;

        $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");

        return $rowcount;
    }

    public function clear_db() {
        global $wpdb;

        return $wpdb->query("TRUNCATE TABLE $this->table_name");
    }

    public function update_db() {
        # initial options setup
        $options = get_option('yt-link-fixer-general');

        # database setup/update
        global $wpdb;

        if (self::LATEST_DB_VERSION != $options["db_version"]) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $this->table_name (
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              check_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
              post_id mediumint(9) NOT NULL,
              vid_id varchar(100) NOT NULL,
              vid_type tinytext NOT NULL,
              status varchar(100) DEFAULT '' NOT NULL,
              PRIMARY KEY  (id),
              UNIQUE KEY vp_id (post_id,vid_id)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            $options["db_version"] = self::LATEST_DB_VERSION;
            update_option('yt-link-fixer-general', $options);
        }
    }

    public function drop_table() {
        global $wpdb;
        $wpdb->query("DROP $this->table_name");
    }
}
