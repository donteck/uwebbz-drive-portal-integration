<?php
/**
 * Plugin Name: UWEBBZ LMS 5 — AI Learning Platform
 * Plugin URI: https://github.com/donteck/uwebbz-drive-portal-integration
 * Description: Standalone AI-powered learning platform with Google Drive, Teaching Libraries, visual course building, AI course architecture, AI lessons, students, assessments, progress, certificates, analytics and notifications.
 * Version: 5.0.0-dev2
 * Author: UWEBBZ Technology
 * Text Domain: uwebbz-lms
 */
if (!defined('ABSPATH')) exit;

if (!defined('ULD_LMS_VERSION')) define('ULD_LMS_VERSION','5.0.0-dev2');
if (!defined('ULD_LMS_DIR')) define('ULD_LMS_DIR',plugin_dir_path(__FILE__));
if (!defined('ULD_LMS_URL')) define('ULD_LMS_URL',plugin_dir_url(__FILE__));
if (!defined('ULD_LMS_FILE')) define('ULD_LMS_FILE',__FILE__);

// Standalone V5 core. V4 does not need to be installed or activated.
require_once ULD_LMS_DIR.'includes/core-v5.php';

// Drive + visual workspaces bundled inside V5.
require_once ULD_LMS_DIR.'includes/class-uld-v4-navigation-guard.php';
require_once ULD_LMS_DIR.'includes/class-uld-v4-visual-workspace.php';
require_once ULD_LMS_DIR.'includes/class-uld-v4-drive-hub.php';
require_once ULD_LMS_DIR.'includes/class-uld-v4-visual-enrollment.php';

// AI engine bundled inside V5.
require_once ULD_LMS_DIR.'includes/class-uld-v4-ai-providers.php';
require_once ULD_LMS_DIR.'includes/class-uld-v4-ai-lesson-builder.php';

// Native V5 creation platform.
require_once ULD_LMS_DIR.'includes/class-uld-v5-course-builder.php';
require_once ULD_LMS_DIR.'includes/class-uld-v5-ai-course-architect.php';
require_once ULD_LMS_DIR.'includes/class-uld-v5-library-course-importer.php';

add_action('admin_enqueue_scripts',function($hook){
 if(strpos((string)$hook,'uld')!==false||in_array(get_post_type(),['uld_course','uld_module','uld_lesson','uld_quiz','uld_assignment','uld_certificate','uld_announcement','uld_cohort'],true)){
  wp_enqueue_script('uld-v5-workspace',ULD_LMS_URL.'assets/v4-workspace.js',[],ULD_LMS_VERSION,true);
  wp_enqueue_style('uld-v5-builder',ULD_LMS_URL.'assets/v5.css',[],ULD_LMS_VERSION);
 }
},100);

register_activation_hook(__FILE__,function(){
 if(!get_option('uld_portal_refresh_seconds'))update_option('uld_portal_refresh_seconds',30);
 update_option('uld_lms_active_generation','5',false);
 flush_rewrite_rules();
});
register_deactivation_hook(__FILE__,function(){flush_rewrite_rules();});
