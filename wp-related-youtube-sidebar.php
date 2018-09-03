<?php
/*
Plugin Name: WP Related YouTube Video in Sidebar
Plugin URI: https://adamjohnlea.com
Description: Add the URL of a YoutTube video to you post, and that video will display in the sidebar when the single post is viewed. Good for showing related videos that do not belong as part of the actual post.
Version: 1.0
Author: Adam John Lea
Author URI: https://adamjohnlea.com
License: MIT
*/

require_once 'class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'wprys_domain_register_required_plugins' );

//show metabox in post editing page
add_action('add_meta_boxes', 'wprys_add_metabox' );

//save metabox data
add_action('save_post', 'wprys_save_metabox' );

//register widgets
add_action('widgets_init', 'wprys_widget_init');

function wprys_domain_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(

		// This is an example of how to include a plugin from the WordPress Plugin Repository.
		array(
			'name'      => 'Video Popup',
			'slug'      => 'video-popup',
			'required'  => true,
		),

	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id'           => 'wprys_domain',          // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'plugins.php',            // Parent menu slug.
		'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
	);

	tgmpa( $plugins, $config );
}

function wprys_add_metabox() {
    add_meta_box('wprys_youtube', 'YouTube Video Link','wprys_youtube_handler', 'post');
}

// Metabox handler
function wprys_youtube_handler() {
    $value = get_post_custom();
    $youtube_link = esc_attr($value['wprys_youtube'][0]);
    // var_dump($value);
    echo '<label for="wprys_youtube">YouTube Video Link</label><input type="text" id="wprys_youtube" name="wprys_youtube" value="'.$youtube_link.'" />';
}

// Save Metadata
function wprys_save_metabox($post_id) {
    //don't save metadata if it's autosave
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    //check if user can edit post
    if( !current_user_can( 'edit_post' ) ) {
        return;
    }

    if( isset($_POST['wprys_youtube'] )) {
        update_post_meta($post_id, 'wprys_youtube', esc_url($_POST['wprys_youtube']));
    }
}

// Register Widget
function wprys_widget_init() {
    register_widget('Wprys_Widget');
}

// Widget Class
class Wprys_Widget extends WP_Widget {
    function Wprys_Widget() {
        $widget_options = array(
            'classname' => 'wprys_class', //CSS
            'description' => 'Show a YouTube Video from post metadata'
        );

        $this->WP_Widget('wprys_id', 'YouTube Video', $widget_options);
    }

    // Widget Form
    function form($instance) {
        $defaults = array('title' => 'Video');
        $instance = wp_parse_args( (array) $instance, $defaults);

        $title = esc_attr($instance['title']);

        echo '<p>Title <input type="text" class="widefat" name="'.$this->get_field_name('title').'" value="'.$title.'" /></p>';
    }

    // Update widget information
    function update($new_instance, $old_instance) {

        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    // Show widet frontend
    function widget($args, $instance) {

        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);

	    //get post metadata
	    $wprys_youtube = esc_url(get_post_meta(get_the_ID(), 'wprys_youtube', true));

        //show only if single post
        if(is_single()) {
	        //show only if single post and metadata for video exists
        	if(! empty($wprys_youtube)) {

        		echo $args['before_widget'];
		        echo $args['before_title'] . $title . $args['after_title'];

                //print widget content
                echo '<iframe frameborder="0" allowfullscreen src="https://www.youtube.com/embed/'.get_yt_videoid($wprys_youtube).'"></iframe>';
		        echo '<a class="vp-a vp-yt-type" href="https://www.youtube.com/watch?v='.get_yt_videoid($wprys_youtube).'" data-ytid="jM7-jT0WXWE">Click or Full Size</a>';
		        echo $args['after_widget'];
        	}
        }
    }
}


// get youtube video id from link
// from: http://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
function get_yt_videoid($url) {
    parse_str( parse_url( $url, PHP_URL_QUERY ), $my_array_of_vars );
    return $my_array_of_vars['v']; 
}
