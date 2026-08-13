<?php
/**
 * Plugin Name: UWEBBZ Drive Teacher UI
 * Description: Loads the UWEBBZ Drive teacher console stylesheet in WordPress admin.
 * Version: 2.0.0
 * Author: UWEBBZ Technology
 */
if (!defined('ABSPATH')) exit;
add_action('admin_enqueue_scripts', function(){
    wp_enqueue_style('uld-teacher-console-ui', plugin_dir_url(__FILE__) . 'assets/teacher-console.css', [], '2.0.0');
});
