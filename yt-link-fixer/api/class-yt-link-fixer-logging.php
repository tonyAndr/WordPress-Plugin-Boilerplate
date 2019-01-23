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

class Yt_Link_Fixer_Logging {


    private $filename;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 */
	public function __construct() {
        $this->filename = plugin_dir_path( dirname( __FILE__ ) ) . 'log/main.txt';
	}

    public function read() {
        if (file_exists($this->filename)) {
            $text = file_get_contents($this->filename);
            $text = explode(PHP_EOL, $text);
            $text = array_filter(array_reverse($text)); // reverse and remove empty elements
            $text = implode(PHP_EOL, $text);
            return $text;
        } else {
            return false;
        }
    }

    /**
     * Write to log file function.
     *
     * @param $process  - What process or procedure is going on.
     * @param $level     - ERR, OK, WARN...
     * @param $msg      - What is going on
     */
    public function write($msg, $process = "GENERAL", $level = "OK") {
        $line = $this->get_curr_time() . ' ['.$process.'] ['.$level.']: '.$msg;
        $file = file_put_contents($this->filename, $line.PHP_EOL , FILE_APPEND | LOCK_EX);
        return;
    }

    public function clear_log() {
        if (file_exists($this->filename))
            unlink($this->filename);
    }

    private function get_curr_time() {
        return current_time( 'mysql' );
    }
}
