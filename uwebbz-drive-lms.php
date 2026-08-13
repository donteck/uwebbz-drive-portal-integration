<?php
/**
 * Plugin Name: UWEBBZ Drive LMS
 * Plugin URI: https://github.com/donteck/uwebbz-drive-portal-integration
 * Description: Unified Google Drive teaching workspace for WordPress: Drive browser, courses, lessons, student assignments, scheduled releases, student portal, automatic course folders, lesson duplication, and WordPress-to-Drive lesson sync.
 * Version: 3.1.0
 * Author: UWEBBZ Technology
 * Text Domain: uwebbz-drive-portal
 */
if (!defined('ABSPATH')) exit;

define('ULD_LMS_VERSION','3.1.0');
define('ULD_LMS_DIR',plugin_dir_path(__FILE__));
define('ULD_LMS_URL',plugin_dir_url(__FILE__));

// Reuse the proven OAuth connector and its existing saved settings.
if (!class_exists('UWEBBZ_Drive_Portal')) require_once ULD_LMS_DIR.'uwebbz-drive-portal.php';

// Teacher Drive browser, courses, lessons, assignments and release controls.
if (!class_exists('ULD_Teacher_Console')) {
    require_once ULD_LMS_DIR.'includes/class-uld-teacher-console.php';
    ULD_Teacher_Console::init();
}

// WordPress lesson -> Google Drive synchronization.
if (!class_exists('ULD_Lesson_Sync')) {
    require_once ULD_LMS_DIR.'includes/class-uld-lesson-sync.php';
    ULD_Lesson_Sync::init();
}

// Course folder automation and lesson duplication.
if (!class_exists('ULD_Course_Tools')) {
    require_once ULD_LMS_DIR.'includes/class-uld-course-tools.php';
    ULD_Course_Tools::init();
}

// Enhanced student portal. This safely replaces the base shortcode at init priority 100.
if (!function_exists('uld_student_portal_v2')) require_once ULD_LMS_DIR.'uwebbz-drive-student-portal-v2.php';

add_action('admin_enqueue_scripts', function($hook){
    if (strpos((string)$hook,'uld')!==false || in_array(get_post_type(),['uld_course','uld_lesson'],true)) {
        wp_enqueue_style('uld-core-ui',ULD_LMS_URL.'assets/admin.css',[],ULD_LMS_VERSION);
        wp_enqueue_style('uld-teacher-ui',ULD_LMS_URL.'assets/teacher-console.css',['uld-core-ui'],ULD_LMS_VERSION);
    }
});

add_action('admin_menu', function(){
    add_submenu_page('uld','Teacher Home','Teacher Home','manage_options','uld-teacher-home','uld_lms_teacher_home');
},5);

function uld_lms_teacher_home(){
    $courses=wp_count_posts('uld_course'); $lessons=wp_count_posts('uld_lesson');
    $course_count=absint($courses->publish??0)+absint($courses->draft??0);
    $lesson_count=absint($lessons->publish??0)+absint($lessons->draft??0);
    $users=count_users();
    $connected=(bool)get_option('uld_google_access_token')||(bool)get_option('uld_google_refresh_token');
    $upcoming=get_posts(['post_type'=>'uld_lesson','post_status'=>'publish','numberposts'=>6,'meta_key'=>'uld_availability','meta_value'=>'scheduled','orderby'=>'date','order'=>'ASC']);
    echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ TECHNOLOGY</span><h1>Teacher Home</h1><p>Your complete teaching workspace: Google Drive, courses, lessons, publishing, students and lesson synchronization in one plugin.</p></div><div class="uld-status '.($connected?'is-connected':'is-offline').'"><span class="uld-dot"></span>'.($connected?'Google Drive connected':'Google Drive needs connection').'</div></div>';
    echo '<div class="uld-grid"><section class="uld-card"><h2>'.$course_count.'</h2><p>Courses</p><a class="button" href="'.esc_url(admin_url('edit.php?post_type=uld_course')).'">Manage Courses</a></section><section class="uld-card"><h2>'.$lesson_count.'</h2><p>Lessons</p><a class="button" href="'.esc_url(admin_url('edit.php?post_type=uld_lesson')).'">Manage Lessons</a></section><section class="uld-card"><h2>'.absint($users['total_users']??0).'</h2><p>WordPress users</p><a class="button" href="'.esc_url(admin_url('admin.php?page=uld-assignments')).'">Assignments & Enrollment</a></section><section class="uld-card"><h2>My Drive</h2><p>Browse folders, PDFs, PowerPoints, Slides, Docs, spreadsheets and videos.</p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld-drive-browser')).'">Open Drive Browser</a></section>';
    echo '<section class="uld-card"><h2>Quick Create</h2><p><a class="button button-primary" href="'.esc_url(admin_url('post-new.php?post_type=uld_lesson')).'">New Lesson</a> <a class="button" href="'.esc_url(admin_url('post-new.php?post_type=uld_course')).'">New Course</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=uld-lesson-sync')).'">Lesson Sync</a></p><p>Create a lesson in WordPress and synchronize it into the assigned Google Drive course folder. Courses can also create their own Drive folder automatically.</p></section>';
    echo '<section class="uld-card"><h2>Scheduled Releases</h2>';
    if(!$upcoming) echo '<p>No scheduled lessons right now.</p>';
    else {echo '<div class="uld-assignment-list">'; foreach($upcoming as $l){$when=get_post_meta($l->ID,'uld_available_at',true);echo '<div><strong>'.esc_html($l->post_title).'</strong><span>'.esc_html($when?:'Schedule not set').'</span></div>'; } echo '</div>';}
    echo '</section></div></div>';
}

register_activation_hook(__FILE__, function(){
    if (!get_option('uld_portal_refresh_seconds')) update_option('uld_portal_refresh_seconds',30);
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){flush_rewrite_rules();});
