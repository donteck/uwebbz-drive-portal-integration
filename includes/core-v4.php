<?php
if (!defined('ABSPATH')) exit;

if (!defined('ULD_LMS_DIR') || !defined('ULD_LMS_URL')) return;

if (!class_exists('UWEBBZ_Drive_Portal')) require_once ULD_LMS_DIR . 'uwebbz-drive-portal.inc';
if (!class_exists('ULD_Teacher_Console')) {
    require_once ULD_LMS_DIR . 'includes/class-uld-teacher-console.php';
    ULD_Teacher_Console::init();
}
if (!class_exists('ULD_Lesson_Sync')) {
    require_once ULD_LMS_DIR . 'includes/class-uld-lesson-sync.php';
    ULD_Lesson_Sync::init();
}
if (!class_exists('ULD_Course_Tools')) {
    require_once ULD_LMS_DIR . 'includes/class-uld-course-tools.php';
    ULD_Course_Tools::init();
}
if (!function_exists('uld_student_portal_v2')) require_once ULD_LMS_DIR . 'includes/internal/student/student-portal.php';

add_action('admin_enqueue_scripts', function($hook){
    if (strpos((string)$hook, 'uld') !== false || in_array(get_post_type(), ['uld_course','uld_lesson'], true)) {
        wp_enqueue_style('uld-core-ui', ULD_LMS_URL . 'assets/admin.css', [], ULD_LMS_VERSION);
        wp_enqueue_style('uld-teacher-ui', ULD_LMS_URL . 'assets/teacher-console.css', ['uld-core-ui'], ULD_LMS_VERSION);
    }
});

add_action('admin_menu', function(){
    add_submenu_page('uld','Teacher Home','Teacher Home','manage_options','uld-teacher-home','uld_lms_teacher_home_v4');
},5);

function uld_lms_teacher_home_v4(){
    $courses=wp_count_posts('uld_course');
    $lessons=wp_count_posts('uld_lesson');
    $course_count=absint($courses->publish??0)+absint($courses->draft??0);
    $lesson_count=absint($lessons->publish??0)+absint($lessons->draft??0);
    $users=count_users();
    $connected=(bool)get_option('uld_google_access_token')||(bool)get_option('uld_google_refresh_token');
    echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ TECHNOLOGY</span><h1>Teacher Home</h1><p>Google Drive, courses, lessons, students and publishing in one master LMS plugin.</p></div><div class="uld-status '.($connected?'is-connected':'is-offline').'"><span class="uld-dot"></span>'.($connected?'Google Drive connected':'Google Drive needs connection').'</div></div>';
    echo '<div class="uld-grid"><section class="uld-card"><h2>'.$course_count.'</h2><p>Courses</p></section><section class="uld-card"><h2>'.$lesson_count.'</h2><p>Lessons</p></section><section class="uld-card"><h2>'.absint($users['total_users']??0).'</h2><p>Users</p></section><section class="uld-card"><h2>My Drive</h2><p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld-drive-browser')).'">Open Drive Browser</a></p></section></div></div>';
}
