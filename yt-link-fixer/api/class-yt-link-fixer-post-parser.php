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

class Yt_Link_Fixer_Post_Parser {

    private $logger;
    private $db;
    private $apiv3;
    private $plugin_name;

    public function __construct($plugin_name) {
        $this->plugin_name = $plugin_name;
        $this->logger =  new Yt_Link_Fixer_Logging();
        $this->db = new Yt_Link_Fixer_DB();
        $this->apiv3 = new Yt_Link_Fixer_ApiV3($plugin_name);
    }

    public function fetch_posts() {
        if (isset($_POST["posts_num"])) {
            $max = $_POST["posts_num"];
        } else {
            $options = get_option($this->plugin_name."-settings");
            $max = $options["posts_num"]; if (!$max) $max = 50;
        }

        $options = get_option($this->plugin_name."-general");
        $offset = $options["posts_offset"];

        # if offset > last_post_id = > offset = 0 - start from the beginning


        $args = array(
            'numberposts' => $max,
            'order' => 'ASC',
            'post_type' => Yt_Link_Fixer_Utils::get_post_types(),
            'offset' => $offset
        );

        $posts = get_posts($args);

        if ($posts) {
            foreach ($posts as $post) {
//	            setup_postdata($post);

                //if ($post->post_type) continue;

                $this->find_broken_links($post);

            }

            $options["posts_offset"] = $offset + sizeof($posts);
        }

        if (sizeof($posts) == 0 || sizeof($posts) < $max) {
            $options["posts_offset"] = 0;
        }


        update_option($this->plugin_name."-general", $options);

    }


    public function find_broken_links( $post  ) {
	    $content = $post->post_content;
	    if (!$content) {
	        $this->logger->write("For post id: $post->ID Content is empty", "POST", "WARN");
	        return false;
        }

        preg_match_all("~<iframe.+?<\/iframe>~ui",$content,$matches);

        if (!$matches[0]) {

            $this->logger->write("For post id: $post->ID Content doesn't contain iframes", "POST", "OK");
            return false;
        }

        foreach($matches[0] as $frm){
            if(mb_strpos($frm, "youtu") === false){
                continue;
            } else {
                preg_match("~src\s*=\s*['\"]([^'\"]+?)['\"]~ui",$frm,$matches1);
                $src = explode("/", $matches1[1]);
                $vid_id = explode("?", end(array_values($src)))[0]; // remove query after ?
                if (!$vid_id) continue;
                $status = $this->apiv3->check_status($vid_id);

                if (!$status) {
                    $this->logger->write("For post id: $post->ID Video with id [$vid_id] NOT FOUND on YT", "VIDEO", "ERR");
                    $this->db->add_row($post->ID, $vid_id, "video", "Removed from YT");
                    continue;
                }

                $embeddable = $status->embeddable ? "yes" : "no";

                if (($status->uploadStatus === "processed" || $status->uploadStatus === "uploaded" ) && ($status->privacyStatus === "public" || $status->privacyStatus === "unlisted")) {
                    if ($status->embeddable) {
                        $this->logger->write("For post id: $post->ID Video with id [$vid_id] is OK", "VIDEO", "OK"); # it's ok, no need to replace
                    } else {
                        $this->logger->write("For post id: $post->ID Video with id [$vid_id] is NOT EMBEDDABLE", "VIDEO", "WARN"); # might be a problem
                        $this->db->add_row($post->ID, $vid_id, "video", "Video is not embeddable, could be a problem");
                    }
                } else {
                    $this->logger->write("For post id: $post->ID Video with id [$vid_id] is bad: $status->uploadStatus | $status->privacyStatus | $embeddable", "VIDEO", "ERR");
                    $this->db->add_row($post->ID, $vid_id, "video", "Bad link [uploadStatus:$status->uploadStatus, privacyStatus:$status->privacyStatus, embeddable:$embeddable");
                }
            }
        }
    }

    public function replace_broken_link($item_id) {
        $item = $this->db->get_rows($item_id);
        if ($item == null || empty($item)) {
            $this->logger->write("Item with id [$item_id] wasn't replaced", "REPLACE", "ERR");
            return false; # not found or error
        }

        $item = $item[0];
        $post_obj = get_post($item->post_id);
        if (!$post_obj) {
            $this->logger->write("Post with id [$item->post_id] wasn't found (might be deleted)", "REPLACE", "ERR");
            return false;
        }

        $content = $post_obj->post_content;
        $clean_title = Yt_Link_Fixer_Utils::clean_string($post_obj->post_title); // prepare post title for query
        // todo: load results from cache if exist (api limits optimization)

        $suggestions = $this->apiv3->get_suggestions($clean_title, $content);

        if (!$suggestions || empty($suggestions)) {
            $this->logger->write("For post id: $item->post_id with title [$clean_title] suggestions not found", "REPLACE", "ERR");
            return false;
        }

        // replace in content & update post
        $content = str_replace($item->vid_id, $suggestions[0]['videoId'], $content);
        $post_obj->post_content = $content;

        $post_id = wp_update_post( $post_obj, true );
        if (is_wp_error($post_id)) {
            $errors = $post_id->get_error_messages();
            $this->logger->write("Can't update post with id $item->post_id, errors:", "REPLACE", "ERR");
            foreach ($errors as $error) {
                $this->logger->write($error, "REPLACE", "ERR");
            }
        } else {
            $this->logger->write("Post with id $item->post_id updated successfully, replace old video [$item->vid_id] with new [".$suggestions[0]['videoId']."]", "REPLACE", "OK");
            $this->db->del_row($item_id);
        }

    }

    public function ajax_get_suggestions() {
        $item_id = $_POST['item_id'];
        $item = $this->db->get_rows($item_id);
        if ($item == null || empty($item)) {
            $response = array('msg' => "Item not found", 'status' => "ERR");
            echo json_encode($response);
            wp_die();
            return false; # not found or error
        }

        $item = $item[0];
        $post_obj = get_post($item->post_id);
        if (!$post_obj) {
            $response = array('msg' => "Post with id [$item->post_id] wasn't found (might be deleted)", 'status' => "ERR");
            echo json_encode($response);
            wp_die();
            return false;
        }

        $content = $post_obj->post_content;
        $clean_title = Yt_Link_Fixer_Utils::clean_string($post_obj->post_title); // prepare post title for query

        $suggestions = $this->apiv3->get_suggestions($clean_title, $content);

        if (!$suggestions || empty($suggestions)) {
            $response = array('msg' => "For post id $item->post_id suggestions not found", 'status' => "ERR");
            echo json_encode($response);
            wp_die();
            return false;
        }

        $suggestions["oldVideoId"] = $item->vid_id;
        $suggestions["postId"] = $item->post_id;
        $suggestions["status"] = "OK";

        echo json_encode($suggestions);

        wp_die();
    }

    public function ajax_replace_link() {
        $videoId = $_POST['videoId'];
        $oldVideoId = $_POST['oldVideoId'];
        $postId = $_POST['postId'];
        $itemId = $_POST['itemId'];

        $response = array();

        $post_obj = get_post($postId);
        if (!$post_obj) {
            $response = array('msg' => "Post with id [$postId] wasn't found (might be deleted)", 'status' => "ERR");
            echo json_encode($response);
            wp_die();
            return false;
        }

        $content = $post_obj->post_content;

        // replace in content & update post
        $content = str_replace($oldVideoId, $videoId, $content);
        $post_obj->post_content = $content;

        $post_id = wp_update_post( $post_obj, true );
        if (is_wp_error($post_id)) {
            $errors = $post_id->get_error_messages();
            $this->logger->write("Can't update post with id $postId, errors:", "REPLACE", "ERR");
            foreach ($errors as $error) {
                $this->logger->write($error, "REPLACE", "ERR");
            }
            $response = array('msg' => "Can't update post, see log", 'status' => "ERR");
            echo json_encode($response);
            wp_die();
        } else {
            $this->logger->write("Post with id $postId updated successfully, replace old video [$oldVideoId] with new [".$videoId."]", "REPLACE", "OK");
            $this->db->del_row($itemId);
            $response = array('msg' => "Post with id $postId updated successfully", 'status' => "OK");
            echo json_encode($response);
            wp_die();
        }
    }


//    private function get_post_content( $post_id = null  ) {
//        $post_object = get_post( $post_id );
//        if ( ! $post_object ) { return false; }
//        //else
//
//        return apply_filters('the_content', $post_object->post_content);
//    }
}
