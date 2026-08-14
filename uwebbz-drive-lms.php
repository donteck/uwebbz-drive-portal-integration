<?php
/**
 * Plugin Name: UWEBBZ Drive LMS
 * Plugin URI: https://github.com/donteck/uwebbz-drive-portal-integration
 * Description: Unified Google Drive LMS for WordPress with teacher dashboard, restricted Drive workspace, visual course and student assignment, course builder, quizzes, assignments, gradebook, progress, certificates, announcements, cohorts, calendar, reports and notifications.
 * Version: 4.1.0
 * Author: UWEBBZ Technology
 * Text Domain: uwebbz-drive-portal
 */
if (!defined('ABSPATH')) exit;

if (!defined('ULD_LMS_VERSION')) define('ULD_LMS_VERSION', '4.1.0');
if (!defined('ULD_LMS_DIR')) define('ULD_LMS_DIR', plugin_dir_path(__FILE__));
if (!defined('ULD_LMS_URL')) define('ULD_LMS_URL', plugin_dir_url(__FILE__));
if (!defined('ULD_LMS_FILE')) define('ULD_LMS_FILE', __FILE__);

require_once ULD_LMS_DIR . 'includes/core-v4.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v4-navigation-guard.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v4-visual-workspace.php';

add_action('admin_enqueue_scripts', function($hook){
    if (strpos((string)$hook,'uld')!==false) {
        wp_enqueue_script('uld-v4-workspace',ULD_LMS_URL.'assets/v4-workspace.js',[],ULD_LMS_VERSION,true);
    }
},100);

register_activation_hook(__FILE__, function(){
    if (!get_option('uld_portal_refresh_seconds')) update_option('uld_portal_refresh_seconds', 30);
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function(){
    flush_rewrite_rules();
});
