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

class Yt_Link_Fixer_Utils {

    // Utils
    public static function get_post_types() {
        $types = get_post_types();
        unset($types["revision"]);
        unset($types["attachment"]);
        unset($types["nav_menu_item"]);
        return $types;
    }

    public static function clean_string($string) {
        $string = preg_replace('~\s+~', ' ', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('~[^A-Za-zА-Яа-яЁё0-9\-\s]~ui', '', $string); // Removes special chars.
        $string = mb_strtolower(trim($string));
        // remove short words
        $words = explode(' ', $string);
        foreach ($words as $k=>$word) {
            if (mb_strlen($word) <= 3)
                unset($words[$k]);
        }
        $string = implode(' ', $words);
        return $string;
    }


//    private function get_post_content( $post_id = null  ) {
//        $post_object = get_post( $post_id );
//        if ( ! $post_object ) { return false; }
//        //else
//
//        return apply_filters('the_content', $post_object->post_content);
//    }
}
