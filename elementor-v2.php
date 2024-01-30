<?php
/*
Plugin Name: Custom Page Plugin
Description: Save and render JSON content for pages.
Version: 1.0
Author: Your Name
*/
require_once __DIR__ . '/vendor/autoload.php';
use Rendering\RenderEngine;

add_filter('the_content', 'custom_render_admin_post');

function custom_render_admin_post() {
    global $post;

    $time_start = microtime(true);
    $elements_json = get_post_meta($post->ID, '_v2_content', true);
    $styles_json = get_post_meta($post->ID, '_v2_styles', true);
    // Render the content using your custom engine
    $elements = json_decode($elements_json, true);
    $styles = json_decode($styles_json, true);

    $render_engine = new RenderEngine($elements, $styles);

    return $render_engine->render();
}
