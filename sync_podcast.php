<?php
/* 
Plugin Name: ESDLV Podcast syncronizer
Plugin URI: ---
Description: This plugin will syncronize the podcast feed with the ESDLV website.
Version: 0.2
Author: Javier Malonda
Author URI: ---
License: GPL2

sync_podcast.php
This script is used to create new post entries from new posdcast episodes.
It reads the podcast feed and finds out if there are new podcast episodes.
If so, it creates a new post entry for each episode.
*/

// Register a route from which the code will be called
// URL = https://elsentidodelavida.net/wp-json/sync/podcast
function sync_podcast_register_route() {
    register_rest_route('sync', 'podcast', array(
        'methods' => 'GET',
        'callback' => 'sync_podcast',
            )
    );
}
add_action('rest_api_init', 'sync_podcast_register_route');

function prepare_post( $item, $episode_title ) {
    // Create an iframe containing the episode audio player
    $episode_link = $item->link;
    $episode_ahref = "<a href='" . $episode_link . "'>" . $episode_title . "</a>";
    // Rewrite the episode link adding '/embed' after 'esdlv'
    $episode_link = str_replace("esdlv", "esdlv/embed", $episode_link);
    $episode_iframe = "<div class='podcast'>
        <iframe src='" . $episode_link . "' height='102px' width='463px' frameborder='0' scrolling='no'></iframe>
        </div>";
    // Set current user to admin
    wp_set_current_user( 1 );
    // Convert $item->pubDate to the format [ Y-m-d H:i:s ]
    $episode_date = date("Y-m-d H:i:s", strtotime($item->pubDate));
    $post_content = $item->description . "<br>" . $episode_iframe;
    $post_info = array( $post_content, $episode_date );

    return $post_info;
}

function sync_podcast() {
    // Define podcast feed url
    $feed_url = 'https://anchor.fm/s/8978fe4/podcast/rss';

    // Parse the feed
    $feed = simplexml_load_file($feed_url);

    // Beginning with the last episode, iterate doing the following:
    // 1. Get the title of the episode
    // 2. Check if a post with the title containing the podcast title is already in the database
    // 3. If not, create a new post entry
    // 4. If so, do nothing. Stop the iteration. Finish the script.
    $i = 0;
    foreach ($feed->channel->item as $item) {
        $episode_title = $item->title;
        echo($episode_title);
        $episode_post = get_page_by_title($episode_title, OBJECT, 'post');
        echo "\n<br>Episode id: " . $episode_post->ID;

        if ( !$episode_post ) {
            $post_info = prepare_post( $item, $episode_title );
            $post_content = $post_info[0];
            $post_date = $post_info[1];
            // Create a new post entry
            $post_id = wp_insert_post(array(
                'post_title' => $episode_title,
                'post_content' => $post_content,
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_author' => 1,
                // Category id for podcast = 351
                'post_category' => array(351),
                // Post date is the podcast publication date
                'post_date' => $post_date
            ));

            // Echo the post id
            echo '<br>The post id is: ' . $post_id;
            break;

        } else {
            echo '<br>The episode is already in the database';
            break;
        }
    }

}

?>