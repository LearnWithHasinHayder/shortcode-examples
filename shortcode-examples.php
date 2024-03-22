<?php
/**
 * Plugin Name: Shortcode Examples
 * Description: A simple plugin to demonstrate how to use Shortcode in WordPress
 * Version: 1.0
 * Author: Hasin Hayder
 */
// require_once 'vendor/autoload.php';
class Shortcode_Examples {
    function __construct() {
        add_action('init', [$this, 'init']);
    }
    function init() {
        //create a greetings shortcode 
        add_shortcode('greet', [$this, 'greet']);
        //create a shortcode with name attribute
        add_shortcode('greetings', [$this, 'greetings']);

        //create a shortcode with content [hello name="WordPress"]What a wonderful day[/hello]
        add_shortcode('hello', [$this, 'hello']);

        //parent child shortcode [parent][child/][/parent]
        add_shortcode('parent', [$this, 'parent']);
        add_shortcode('child', [$this, 'child']);

        //create a video shortcode to display youtube and vimeo video
        add_shortcode('video', [$this, 'video']);

        //create an xkcd comic shortcode like [xkcd comic='936'/]
        add_shortcode('xkcd', [$this, 'xkcd']);

        //register a custom post type 'time' with only title
        register_post_type('time', [
            'public' => false,
            'show_ui'=>true,
            'label' => 'Time',
            'supports' => ['title']
        ]);

        add_filter('manage_time_posts_columns', [$this, 'time_column']);
        add_action('manage_time_posts_custom_column', [$this, 'time_column_content'], 10, 2);

        add_action('add_meta_boxes', [$this, 'add_time_meta_boxes']);
        add_action('save_post', [$this, 'save_time_meta_box_data']);

        //register the shortcode time
        add_shortcode('time', [$this, 'time_shortcode']);

    }

    function time_shortcode($atts) {
        $default_values = [
            'id' => ''
        ];


        $attributes = shortcode_atts($default_values, $atts);
        
        if (empty($attributes['id'])) {
            return "<p>Please provide a valid post id</p>";
        }

        $timezone = get_post_meta($attributes['id'], 'timezone', true);
        $country = get_post_meta($attributes['id'], 'country', true);
        $city = get_post_meta($attributes['id'], 'city', true);

        $time = new DateTime('now', new DateTimeZone($timezone));
        // $time->setTimezone(new DateTimeZone('UTC'));

        return "<p>Time in {$city}, {$country} is {$time->format('Y-m-d H:i:s')}</p>";
    }

    function save_time_meta_box_data($post_id) {
        // Check if nonce is set
        if (!isset ($_POST['time_meta_box_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['time_meta_box_nonce'], 'time_meta_box')) {
            return;
        }


        // Save timezone, country, city meta fields
        if (isset ($_POST['timezone'])) {
            update_post_meta($post_id, 'timezone', sanitize_text_field($_POST['timezone']));
        }
        if (isset ($_POST['country'])) {
            update_post_meta($post_id, 'country', sanitize_text_field($_POST['country']));
        }
        if (isset ($_POST['city'])) {
            update_post_meta($post_id, 'city', sanitize_text_field($_POST['city']));
        }
    }

    function add_time_meta_boxes() {
        add_meta_box('time_meta_box', 'Time', [$this, 'time_meta_box_content'], 'time', 'side', 'default');
    }

    function time_meta_box_content($post) {
        wp_nonce_field('time_meta_box', 'time_meta_box_nonce');
        // Retrieve existing values for fields
        $utimezone = get_post_meta($post->ID, 'timezone', true);
        $country = get_post_meta($post->ID, 'country', true);
        $city = get_post_meta($post->ID, 'city', true);
        ?>
        <p>
            <label for="timezone">Timezone:</label>
            <!-- select dropdown -->
            <select id="timezone" name="timezone">
                <option value="GMT">GMT</option>
                <option value="CET">CET</option>
                <option value="CEST">CEST</option>
                <option value="EST">EST</option>
                <option value="PST">PST</option>
                <option value="GMT+1">GMT+1</option>
                <option value="GMT+2">GMT+2</option>
                <option value="GMT+3">GMT+3</option>


                <?php 
                $timezones = timezone_identifiers_list();
                foreach ($timezones as $timezone) {
                    echo "<option value='{$timezone}' " . selected($timezone, $utimezone, false) . ">{$timezone}</option>";
                }
                ?>
                
            </select>
        </p>
        <p>
            <label for="country">Country:</label>
            <input type="text" id="country" name="country" value="<?php echo esc_attr($country); ?>" />
        </p>
        <p>
            <label for="city">City:</label>
            <input type="text" id="city" name="city" value="<?php echo esc_attr($city); ?>" />
        </p>
        <?php
    }

    function time_column($columns) {
        $columns['shortcode'] = 'Shortcode';
        return $columns;
    }

    function time_column_content($column, $post_id) {
        if ($column == 'shortcode') {
            echo "[time id='{$post_id}']";
        }
    }

    function xkcd($atts) {
        $default_values = [
            'comic' => '936'
        ];

        $attributes = shortcode_atts($default_values, $atts);

        $response = wp_remote_get("https://xkcd.com/{$attributes['comic']}/info.0.json");
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $image = esc_url($data['img']);
        $title = esc_attr($data['title']);
        $alt = esc_attr($data['alt']);

        return "<p><img src='{$image}' title='{$title}' alt='{$alt}' /></p> ";

    }

    function video($atts) {
        $default_values = [
            'type' => 'youtube',
            'id' => '',
            'width' => '560',
            'height' => '315'
        ];

        $atts['type'] = sanitize_text_field($atts['type']);

        $attributes = shortcode_atts($default_values, $atts);

        

        $attributes['id'] = esc_attr($attributes['id']);
        $attributes['width'] = esc_attr($attributes['width']);
        $attributes['height'] = esc_attr($attributes['height']);

        if ($attributes['type'] == 'youtube') {
            return "<p><iframe width='{$attributes['width']}' height='{$attributes['height']}' src='https://www.youtube.com/embed/{$attributes['id']}' frameborder='0' allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe></p>";
        } else if ($attributes['type'] == 'vimeo') {
            return "<p><iframe src='https://player.vimeo.com/video/{$attributes['id']}' width='{$attributes['width']}' height='{$attributes['height']}' frameborder='0' allow='autoplay; fullscreen' allowfullscreen></iframe><p>";
        } else {
            return "<p>Invalid Video Type</p>";
        }
    }

    function parent($atts, $content = null) {
        $content = do_shortcode($content);
        return "<div style='border: 1px solid red; padding: 10px;'>This is Parent - {$content}</div>";
    }

    function child($atts, $content = null) {
        return "<div style='border: 1px solid green; padding: 10px;'>{$content}</div>";
    }

    function hello($atts, $content = null) {
        $default_values = [
            'name' => 'Guest'
        ];

        $attributes = shortcode_atts($default_values, $atts);

        return "<p>Hello, {$attributes['name']}! {$content}</p>";
    }

    function greetings($atts) {
        $default_values = [
            'name' => 'Guest',
            'greet' => 'Good Morning'
        ];

        $attributes = shortcode_atts($default_values, $atts);

        // $attributes = shortcode_atts([
        //     'name' => 'Guest'
        // ], $atts);
        $greetings = "<p>{$attributes['greet']}, {$attributes['name']}!</p>";
        return $greetings;
        // return print_r($attributes, true);
    }

    function greet() {
        return "<p>Good Morning!</p>";
    }


}

new Shortcode_Examples();