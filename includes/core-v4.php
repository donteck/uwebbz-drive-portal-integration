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

add_action('init', function(){
    $types = [
        'uld_module'=>['Modules','Module'],
        'uld_quiz'=>['Quizzes','Quiz'],
        'uld_assignment'=>['Assignments','Assignment'],
        'uld_announcement'=>['Announcements','Announcement'],
        'uld_certificate'=>['Certificates','Certificate'],
        'uld_cohort'=>['Cohorts','Cohort'],
    ];
    foreach($types as $slug=>$label){
        if(post_type_exists($slug)) continue;
        register_post_type($slug,[
            'labels'=>['name'=>$label[0],'singular_name'=>$label[1],'add_new_item'=>'Add '.$label[1],'edit_item'=>'Edit '.$label[1]],
            'public'=>false,'show_ui'=>true,'show_in_menu'=>false,'supports'=>['title','editor']
        ]);
    }
},30);

add_action('admin_enqueue_scripts', function($hook){
    if (strpos((string)$hook,'uld')!==false || in_array(get_post_type(),['uld_course','uld_lesson','uld_module','uld_quiz','uld_assignment','uld_announcement','uld_certificate','uld_cohort'],true)) {
        wp_enqueue_style('uld-core-ui',ULD_LMS_URL.'assets/admin.css',[],ULD_LMS_VERSION);
        wp_enqueue_style('uld-teacher-ui',ULD_LMS_URL.'assets/teacher-console.css',['uld-core-ui'],ULD_LMS_VERSION);
    }
});

add_action('admin_menu', function(){
    remove_submenu_page('uld','uld-teacher-home');
    add_submenu_page('uld','Teacher Dashboard','Teacher Dashboard','manage_options','uld-teacher-dashboard','uld_v4_dashboard');
    add_submenu_page('uld','Course Builder','Course Builder','manage_options','uld-course-builder',function(){uld_v4_feature_page('Course Builder','Organize courses into modules and lessons.',['Manage Courses'=>'edit.php?post_type=uld_course','Manage Modules'=>'edit.php?post_type=uld_module','Manage Lessons'=>'edit.php?post_type=uld_lesson']);});
    add_submenu_page('uld','Build Course from Drive','Build from Drive','manage_options','uld-build-from-drive',function(){uld_v4_feature_page('Build Course from Drive','Use My Drive to browse folders, assign a folder to a course, and convert Drive files into lessons.',['Open My Drive'=>'admin.php?page=uld-drive-browser','Course Builder'=>'admin.php?page=uld-course-builder','Lesson Sync'=>'admin.php?page=uld-lesson-sync']);});
    add_submenu_page('uld','Modules','Modules','manage_options','edit.php?post_type=uld_module');
    add_submenu_page('uld','Students & Enrollments','Students','manage_options','uld-students',function(){uld_v4_feature_page('Students & Enrollments','Enroll students in courses and manage direct Drive access.',['Enrollments'=>'admin.php?page=uld-assignments','WordPress Users'=>'users.php','Cohorts'=>'edit.php?post_type=uld_cohort']);});
    add_submenu_page('uld','Quizzes','Quizzes','manage_options','edit.php?post_type=uld_quiz');
    add_submenu_page('uld','Learning Assignments','Learning Assignments','manage_options','edit.php?post_type=uld_assignment');
    add_submenu_page('uld','Gradebook','Gradebook','manage_options','uld-gradebook',function(){uld_v4_feature_page('Gradebook','Central location for quiz, assignment and course grades.',['Quizzes'=>'edit.php?post_type=uld_quiz','Assignments'=>'edit.php?post_type=uld_assignment','Progress'=>'admin.php?page=uld-progress']);});
    add_submenu_page('uld','Student Progress','Progress','manage_options','uld-progress','uld_v4_progress');
    add_submenu_page('uld','Certificates','Certificates','manage_options','edit.php?post_type=uld_certificate');
    add_submenu_page('uld','Announcements','Announcements','manage_options','edit.php?post_type=uld_announcement');
    add_submenu_page('uld','Cohorts','Cohorts','manage_options','edit.php?post_type=uld_cohort');
    add_submenu_page('uld','Calendar','Calendar','manage_options','uld-calendar','uld_v4_calendar');
    add_submenu_page('uld','Reports & Analytics','Reports','manage_options','uld-reports','uld_v4_reports');
    add_submenu_page('uld','Notifications','Notifications','manage_options','uld-notifications','uld_v4_notifications');
    add_submenu_page('uld','LMS Settings','LMS Settings','manage_options','uld-lms-settings','uld_v4_settings');
},50);

function uld_v4_count($type){$o=wp_count_posts($type);return absint($o->publish??0)+absint($o->draft??0)+absint($o->pending??0);}
function uld_v4_start($title,$subtitle){echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ DRIVE LMS v4</span><h1>'.esc_html($title).'</h1><p>'.esc_html($subtitle).'</p></div><div class="uld-status is-connected"><span class="uld-dot"></span>Master LMS</div></div>';}
function uld_v4_feature_page($title,$subtitle,$links=[]){uld_v4_start($title,$subtitle);echo '<div class="uld-grid">';foreach($links as $label=>$path)echo '<section class="uld-card"><h2>'.esc_html($label).'</h2><p><a class="button button-primary" href="'.esc_url(admin_url($path)).'">Open</a></p></section>';echo '</div></div>';}

function uld_v4_dashboard(){
    $users=count_users();$connected=(bool)get_option('uld_google_access_token')||(bool)get_option('uld_google_refresh_token');
    uld_v4_start('Teacher Dashboard','Your command center for courses, Drive content, students, assessments, progress and communication.');
    echo '<div class="uld-v4-statgrid">';
    $stats=[['Courses',uld_v4_count('uld_course'),'edit.php?post_type=uld_course'],['Lessons',uld_v4_count('uld_lesson'),'edit.php?post_type=uld_lesson'],['Students',absint($users['total_users']??0),'admin.php?page=uld-students'],['Quizzes',uld_v4_count('uld_quiz'),'edit.php?post_type=uld_quiz'],['Assignments',uld_v4_count('uld_assignment'),'edit.php?post_type=uld_assignment'],['Google Drive',$connected?'Connected':'Reconnect','admin.php?page=uld-drive-browser']];
    foreach($stats as $s)echo '<section class="uld-card uld-v4-stat"><strong>'.esc_html((string)$s[1]).'</strong><p>'.esc_html($s[0]).'</p><a class="button" href="'.esc_url(admin_url($s[2])).'">Open</a></section>';
    echo '</div><div class="uld-grid"><section class="uld-card"><h2>Build & Organize</h2><p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld-course-builder')).'">Course Builder</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=uld-build-from-drive')).'">Build from Drive</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=uld-drive-browser')).'">My Drive</a></p></section><section class="uld-card"><h2>Teaching Operations</h2><p><a class="button" href="'.esc_url(admin_url('admin.php?page=uld-progress')).'">Progress</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=uld-gradebook')).'">Gradebook</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=uld-calendar')).'">Calendar</a></p></section><section class="uld-card"><h2>Communication</h2><p><a class="button" href="'.esc_url(admin_url('edit.php?post_type=uld_announcement')).'">Announcements</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=uld-notifications')).'">Notifications</a></p></section><section class="uld-card"><h2>Completion</h2><p><a class="button" href="'.esc_url(admin_url('edit.php?post_type=uld_certificate')).'">Certificates</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=uld-reports')).'">Reports</a></p></section></div></div>';
}

function uld_v4_progress(){uld_v4_start('Student Progress','Track enrolled students and course completion.');echo '<section class="uld-card"><table class="widefat striped"><thead><tr><th>Student</th><th>Course</th><th>Completed Lessons</th><th>Total Lessons</th><th>Progress</th></tr></thead><tbody>';foreach(get_users(['orderby'=>'display_name']) as $u){foreach(array_map('absint',(array)get_user_meta($u->ID,'uld_course_ids',true)) as $cid){if(get_post_type($cid)!=='uld_course')continue;$lessons=get_posts(['post_type'=>'uld_lesson','post_status'=>'publish','numberposts'=>-1,'meta_key'=>'uld_course_id','meta_value'=>$cid,'fields'=>'ids']);$done=array_map('absint',(array)get_user_meta($u->ID,'uld_completed_lessons',true));$completed=count(array_intersect($lessons,$done));$total=count($lessons);$pct=$total?round($completed*100/$total):0;echo '<tr><td>'.esc_html($u->display_name).'</td><td>'.esc_html(get_the_title($cid)).'</td><td>'.$completed.'</td><td>'.$total.'</td><td><strong>'.$pct.'%</strong></td></tr>';}}echo '</tbody></table></section></div>';}

function uld_v4_calendar(){uld_v4_start('Teaching Calendar','Scheduled lessons and upcoming learning events.');$items=[];foreach(get_posts(['post_type'=>'uld_lesson','post_status'=>'publish','numberposts'=>-1]) as $p){$d=get_post_meta($p->ID,'uld_available_at',true);if($d)$items[]=[$d,$p->post_title];}usort($items,function($a,$b){return strcmp($a[0],$b[0]);});echo '<section class="uld-card"><div class="uld-assignment-list">';if(!$items)echo '<p>No scheduled lessons yet.</p>';foreach($items as $i)echo '<div><strong>'.esc_html($i[1]).'</strong><span>'.esc_html($i[0]).'</span></div>';echo '</div></section></div>';}

function uld_v4_reports(){uld_v4_start('Reports & Analytics','Monitor content, enrollment and learning activity.');echo '<div class="uld-v4-statgrid">';foreach([['Courses',uld_v4_count('uld_course')],['Lessons',uld_v4_count('uld_lesson')],['Quizzes',uld_v4_count('uld_quiz')],['Assignments',uld_v4_count('uld_assignment')],['Certificates',uld_v4_count('uld_certificate')]] as $s)echo '<section class="uld-card uld-v4-stat"><strong>'.esc_html((string)$s[1]).'</strong><p>'.esc_html($s[0]).'</p></section>';echo '</div></div>';}

function uld_v4_notifications(){uld_v4_start('Notification Center','Configure student email notifications for LMS events.');echo '<section class="uld-card"><p><strong>Course enrollment email:</strong> Enabled</p><p>Notification framework covers enrollment, new lessons, assignments, grades, announcements and certificates. Course enrollment already uses the integrated WordPress mail notification module.</p><p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld-assignments')).'">Test Enrollment</a></p></section></div>';}

function uld_v4_settings(){uld_v4_start('LMS Settings','Manage Google Drive, portal delivery and core LMS configuration.');echo '<div class="uld-grid"><section class="uld-card"><h2>Google Drive</h2><p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld')).'">Drive Settings</a></p></section><section class="uld-card"><h2>Student Portal</h2><p>Use shortcode <code>[student_drive_portal]</code> on the page students access after login.</p></section></div></div>';}
