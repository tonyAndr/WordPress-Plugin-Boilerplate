<?php

/**
 * The APIv3 communicating module.
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/api
 */

/**
 * The APIv3 communicating module.
 *
 * Call to YouTube API to check status/get search results
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/api
 * @author     Your Name <email@example.com>
 */

class Yt_Link_Fixer_ApiV3 {

	/**
	 * YT Api v3 key
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $auth_key    Authorization key for Api.
	 */
	private $auth_key;

	private $plugin_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 */
	public function __construct($plugin_name) {
		$this->auth_key = "AIzaSyB4i91lkTkUTrqQMltbAVhtjIv1zBMRbV8";
		$this->plugin_name = $plugin_name;
	}

    public function check_status( $video_id ) {
        $data = $this->make_request("check_status", $video_id);
        return $data->items[0]->status;
        // todo: fail check
    }

    public function get_search_results( $title ) {
        $data = $this->make_request("get_search_results", urlencode($title));
        return $data->items;
        // todo: fail check
    }

    private function make_request ( $method , $param) {
	    $api_url = "https://www.googleapis.com/youtube/v3/";
	    if ($method == "check_status") {
	        $api_url .= "videos?id=" . $param . "&part=status";
        } else {
            $api_url .= "search?q=" . $param . "&part=snippet&maxResults=20&type=video&videoEmbeddable=true";
        }
	    $api_url .= "&key=" . $this->auth_key;

	    $response = wp_remote_get($api_url);
	    if (is_wp_error($response)) {
	        return false;
        }
	    $data = json_decode($response["body"]);
	    return $data;
    }

    public function get_suggestions($post_title, $post_content) {
	    // load from cache if exists or make api request

        $suggestions = $this->load_from_cache($post_title, $post_content);

        if ($suggestions) {
            return $suggestions;
        }

        // cache is empty, lets try to search through api
        $videos = $this->get_search_results($post_title); // search videos by title

        if (!$videos) {
            return false;
        }

        $suggestions = array();

        $yt_score = 100;
        $sub_koef = $yt_score / sizeof($videos); # percent per suggestion
        $similar_score = 0;
        foreach ($videos as $k => $video) { // if post_content contains same videoId, then we don't want to duplicate it, and remove it from array
            if (strpos($post_content, $video->id->videoId) !== false) {
                unset($videos[$k]);
                continue;
            }
            // calculate similarity and total score
            mb_similar_text(Yt_Link_Fixer_Utils::clean_string($video->snippet->title), $post_title, $similar_score);
            $suggestions[] = array(
                'videoId' => $video->id->videoId,
                'title' => $video->snippet->title,
                'description' => $video->snippet->description,
                'preview' => $video->snippet->thumbnails->medium->url,
                'score' => round(($yt_score + $similar_score) / 2)
            );
            $yt_score -= $sub_koef;
        }

        usort($suggestions, function ($a,$b) { // sort array by score DESC, best result on top
            return $b['score'] <=> $a['score'];
        });

        // keep results for the future
        $this->save_to_cache($post_title, $suggestions);

        return $suggestions;
    }


    // Simple cache implementation using wp options

    private function load_from_cache($key, $post_content) {
	    $options = get_option($this->plugin_name . '-cache');
	    if (isset($options[$key])) {
            // check cache timestamp
            $cache_time = $options[$key]['time'];
            if (time() - $cache_time >= 604800) {
                return false; // if results are older than a week, don't use them
            }
            $suggestions = $options[$key]['videos'];
            // remove videos which we used already
            foreach ($suggestions as $k => $video) { // if post_content contains same videoId, then we don't want to duplicate it, and remove it from array
                if (strpos($post_content, $video['videoId']) !== false) {
                    unset($suggestions[$k]);
                }
            }

            return empty($suggestions) ? false : $suggestions;
        }
	    return false;
    }

    private function save_to_cache($key, $value) {
        $options = get_option($this->plugin_name . '-cache');
        $options[$key] = array('time' => time(), 'videos' => $value);
        update_option($this->plugin_name . '-cache', $options);
    }

    public function clear_cache() {
        update_option($this->plugin_name . '-cache', '');
    }
}
