<?php
/**
 * Plugin Name: UWEBBZ Drive LMS
 * Plugin URI: https://github.com/donteck/uwebbz-drive-portal-integration
 * Description: UWEBBZ LMS 5.0 AI Learning Platform development build with visual course building, AI course architecture, Teaching Library course import, Google Drive, students, assessments, progress and notifications.
 * Version: 5.0.0-dev1
 * Author: UWEBBZ Technology
 * Text Domain: uwebbz-drive-portal
 */
if (!defined('ABSPATH')) exit;

if (!defined('ULD_LMS_VERSION')) define('ULD_LMS_VERSION', '5.0.0-dev1');
if (!defined('ULD_LMS_DIR')) define('ULD_LMS_DIR', plugin_dir_path(__FILE__));
if (!defined('ULD_LMS_URL')) define('ULD_LMS_URL', plugin_dir_url(__FILE__));
if (!defined('ULD_LMS_FILE')) define('ULD_LMS_FILE', __FILE__);

// Stable v4 platform foundation.
require_once ULD_LMS_DIR . 'includes/core-v4.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v4-navigation-guard.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v4-visual-workspace.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v4-drive-hub.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v4-visual-enrollment.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v4-ai-providers.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v4-ai-lesson-builder.php';

// V5 AI Learning Platform milestone 1.
require_once ULD_LMS_DIR . 'includes/class-uld-v5-course-builder.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v5-ai-course-architect.php';
require_once ULD_LMS_DIR . 'includes/class-uld-v5-library-course-importer.php';

add_action('admin_enqueue_scripts', function($hook){
    if (strpos((string)$hook,'uld')!==false || in_array(get_post_type(),['uld_course','uld_module','uld_lesson'],true)) {
        wp_enqueue_script('uld-v4-workspace',ULD_LMS_URL.'assets/v4-workspace.js',[],ULD_LMS_VERSION,true);
    }
},100);

register_activation_hook(__FILE__, function(){
    if (!get_option('uld_portal_refresh_seconds')) update_option('uld_portal_refresh_seconds', 30);
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });
