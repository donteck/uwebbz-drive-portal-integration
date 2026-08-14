<?php
if (!defined('ABSPATH')) exit;
if (!defined('ULD_LMS_DIR') || !defined('ULD_LMS_URL')) return;

// V5 is self-contained. Legacy-named internal components are implementation modules,
// not a dependency on an installed V4 plugin.
if (!class_exists('UWEBBZ_Drive_Portal')) require_once ULD_LMS_DIR . 'uwebbz-drive-portal.inc';
if (!class_exists('ULD_Teacher_Console')) { require_once ULD_LMS_DIR.'includes/class-uld-teacher-console.php'; ULD_Teacher_Console::init(); }
if (!class_exists('ULD_Lesson_Sync')) { require_once ULD_LMS_DIR.'includes/class-uld-lesson-sync.php'; ULD_Lesson_Sync::init(); }
if (!class_exists('ULD_Course_Tools')) { require_once ULD_LMS_DIR.'includes/class-uld-course-tools.php'; ULD_Course_Tools::init(); }
if (!function_exists('uld_student_portal_v2')) require_once ULD_LMS_DIR.'includes/internal/student/student-portal.php';

add_action('init', function(){
 $types=['uld_module'=>['Modules','Module'],'uld_quiz'=>['Quizzes','Quiz'],'uld_assignment'=>['Assignments','Assignment'],'uld_announcement'=>['Announcements','Announcement'],'uld_certificate'=>['Certificates','Certificate'],'uld_cohort'=>['Cohorts','Cohort']];
 foreach($types as $slug=>$label){ if(post_type_exists($slug))continue; register_post_type($slug,['labels'=>['name'=>$label[0],'singular_name'=>$label[1],'add_new_item'=>'Add '.$label[1],'edit_item'=>'Edit '.$label[1]],'public'=>false,'show_ui'=>true,'show_in_menu'=>false,'supports'=>['title','editor']]); }
},30);

add_action('admin_enqueue_scripts',function($hook){
 if(strpos((string)$hook,'uld')!==false||in_array(get_post_type(),['uld_course','uld_lesson','uld_module','uld_quiz','uld_assignment','uld_announcement','uld_certificate','uld_cohort'],true)){
  wp_enqueue_style('uld-v5-core',ULD_LMS_URL.'assets/admin.css',[],ULD_LMS_VERSION);
  wp_enqueue_style('uld-v5-teacher',ULD_LMS_URL.'assets/teacher-console.css',['uld-v5-core'],ULD_LMS_VERSION);
  wp_enqueue_style('uld-v5-platform',ULD_LMS_URL.'assets/v5-platform.css',['uld-v5-teacher'],ULD_LMS_VERSION);
 }
});

add_action('admin_menu',function(){
 remove_submenu_page('uld','uld-teacher-home');
 add_submenu_page('uld','V5 Dashboard','V5 Dashboard','manage_options','uld-v5-dashboard','uld_v5_dashboard');
 add_submenu_page('uld','Students & Enrollments','Students','manage_options','uld-students','uld_v5_students_page');
 add_submenu_page('uld','Quizzes','Quizzes','manage_options','edit.php?post_type=uld_quiz');
 add_submenu_page('uld','Assignments','Assignments','manage_options','edit.php?post_type=uld_assignment');
 add_submenu_page('uld','Gradebook','Gradebook','manage_options','uld-gradebook','uld_v5_gradebook');
 add_submenu_page('uld','Student Progress','Progress','manage_options','uld-progress','uld_v5_progress');
 add_submenu_page('uld','Certificates','Certificates','manage_options','edit.php?post_type=uld_certificate');
 add_submenu_page('uld','Announcements','Announcements','manage_options','edit.php?post_type=uld_announcement');
 add_submenu_page('uld','Cohorts','Cohorts','manage_options','edit.php?post_type=uld_cohort');
 add_submenu_page('uld','Calendar','Calendar','manage_options','uld-calendar','uld_v5_calendar');
 add_submenu_page('uld','Reports & Analytics','Reports','manage_options','uld-reports','uld_v5_reports');
 add_submenu_page('uld','Notifications','Notifications','manage_options','uld-notifications','uld_v5_notifications');
 add_submenu_page('uld','LMS Settings','LMS Settings','manage_options','uld-lms-settings','uld_v5_settings');
},50);

function uld_v5_count($type){$o=wp_count_posts($type);return absint($o->publish??0)+absint($o->draft??0)+absint($o->pending??0);}
function uld_v5_start($title,$subtitle){echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ LMS 5.0 · AI LEARNING PLATFORM</span><h1>'.esc_html($title).'</h1><p>'.esc_html($subtitle).'</p></div><div class="uld-status is-connected"><span class="uld-dot"></span>Standalone V5</div></div>';}
function uld_v5_dashboard(){
 $users=count_users();$connected=(bool)get_option('uld_google_access_token')||(bool)get_option('uld_google_refresh_token');uld_v5_start('Learning Platform Dashboard','Create, teach, assess, communicate and analyze from one V5 workspace.');
 $stats=[['Courses',uld_v5_count('uld_course'),'edit.php?post_type=uld_course'],['Lessons',uld_v5_count('uld_lesson'),'edit.php?post_type=uld_lesson'],['Students',absint($users['total_users']??0),'admin.php?page=uld-students'],['Quizzes',uld_v5_count('uld_quiz'),'edit.php?post_type=uld_quiz'],['Assignments',uld_v5_count('uld_assignment'),'edit.php?post_type=uld_assignment'],['Drive',$connected?'Connected':'Reconnect','admin.php?page=uld-drive-hub']];
 echo '<div class="uld-v4-statgrid">';foreach($stats as$s)echo '<section class="uld-card uld-v4-stat"><strong>'.esc_html((string)$s[1]).'</strong><p>'.esc_html($s[0]).'</p><a class="button" href="'.esc_url(admin_url($s[2])).'">Open</a></section>';echo '</div>';
 $areas=[['Create','Visual Course Builder, AI Course Architect and Teaching Library importer.','admin.php?page=uld-v5-course-builder','Open Course Studio'],['AI Studio','Generate individual lessons or complete course blueprints.','admin.php?page=uld-v5-ai-architect','Open AI Architect'],['Content Library','Browse all Drive privately and manage assignment-only Teaching Libraries.','admin.php?page=uld-drive-hub','Open Drive Hub'],['Students','Enrollment, cohorts and progress tracking.','admin.php?page=uld-students','Manage Students'],['Assess','Quizzes, assignments and gradebook.','admin.php?page=uld-gradebook','Open Gradebook'],['Communicate','Announcements and notifications.','admin.php?page=uld-notifications','Open Notifications'],['Analytics','Progress, calendar and reports.','admin.php?page=uld-reports','Open Reports']];
 echo '<div class="uld-grid">';foreach($areas as$a)echo '<section class="uld-card"><h2>'.esc_html($a[0]).'</h2><p>'.esc_html($a[1]).'</p><a class="button button-primary" href="'.esc_url(admin_url($a[2])).'">'.esc_html($a[3]).'</a></section>';echo '</div></div>';
}
function uld_v5_students_page(){uld_v5_start('Students & Enrollments','Enroll students, assign learning content and monitor access.');echo '<div class="uld-grid"><section class="uld-card"><h2>Visual Enrollment</h2><p>Use the visual enrollment workspace to connect students with courses.</p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld-v4-enrollment')).'">Open Enrollment</a></section><section class="uld-card"><h2>Cohorts</h2><p>Organize learners into reusable class groups.</p><a class="button" href="'.esc_url(admin_url('edit.php?post_type=uld_cohort')).'">Manage Cohorts</a></section></div></div>';}
function uld_v5_gradebook(){uld_v5_start('Gradebook','Central assessment workspace for quizzes, assignments and course performance.');echo '<div class="uld-grid"><section class="uld-card"><h2>Quizzes</h2><a class="button" href="'.esc_url(admin_url('edit.php?post_type=uld_quiz')).'">Manage Quizzes</a></section><section class="uld-card"><h2>Assignments</h2><a class="button" href="'.esc_url(admin_url('edit.php?post_type=uld_assignment')).'">Manage Assignments</a></section><section class="uld-card"><h2>Progress</h2><a class="button" href="'.esc_url(admin_url('admin.php?page=uld-progress')).'">Student Progress</a></section></div></div>';}
function uld_v5_progress(){uld_v5_start('Student Progress','Track completion across every enrolled course.');echo '<section class="uld-card"><table class="widefat striped"><thead><tr><th>Student</th><th>Course</th><th>Completed</th><th>Total</th><th>Progress</th></tr></thead><tbody>';foreach(get_users(['orderby'=>'display_name'])as$u){foreach(array_map('absint',(array)get_user_meta($u->ID,'uld_course_ids',true))as$cid){if(get_post_type($cid)!=='uld_course')continue;$ls=get_posts(['post_type'=>'uld_lesson','post_status'=>'publish','numberposts'=>-1,'meta_key'=>'uld_course_id','meta_value'=>$cid,'fields'=>'ids']);$done=array_map('absint',(array)get_user_meta($u->ID,'uld_completed_lessons',true));$n=count(array_intersect($ls,$done));$t=count($ls);$p=$t?round($n*100/$t):0;echo '<tr><td>'.esc_html($u->display_name).'</td><td>'.esc_html(get_the_title($cid)).'</td><td>'.$n.'</td><td>'.$t.'</td><td><strong>'.$p.'%</strong></td></tr>';}}echo '</tbody></table></section></div>';}
function uld_v5_calendar(){uld_v5_start('Teaching Calendar','Scheduled releases and upcoming learning events.');$items=[];foreach(get_posts(['post_type'=>'uld_lesson','post_status'=>['publish','draft'],'numberposts'=>-1])as$p){$d=get_post_meta($p->ID,'uld_available_at',true);if($d)$items[]=[$d,$p->post_title];}usort($items,fn($a,$b)=>strcmp($a[0],$b[0]));echo '<section class="uld-card">';if(!$items)echo '<p>No scheduled lessons yet.</p>';foreach($items as$i)echo '<p><strong>'.esc_html($i[1]).'</strong> — '.esc_html($i[0]).'</p>';echo '</section></div>';}
function uld_v5_reports(){uld_v5_start('Reports & Analytics','Monitor your V5 learning platform.');echo '<div class="uld-v4-statgrid">';foreach([['Courses',uld_v5_count('uld_course')],['Lessons',uld_v5_count('uld_lesson')],['Modules',uld_v5_count('uld_module')],['Quizzes',uld_v5_count('uld_quiz')],['Assignments',uld_v5_count('uld_assignment')],['Certificates',uld_v5_count('uld_certificate')]]as$s)echo '<section class="uld-card uld-v4-stat"><strong>'.esc_html((string)$s[1]).'</strong><p>'.esc_html($s[0]).'</p></section>';echo '</div></div>';}
function uld_v5_notifications(){uld_v5_start('Notification Center','Course enrollment and future learning-event email notifications.');echo '<section class="uld-card"><h2>Enrollment Email</h2><p>Enabled through the integrated WordPress mail notification module.</p><p>V5 notification architecture is ready for lesson releases, assignments, grades, announcements and certificates.</p></section></div>';}
function uld_v5_settings(){uld_v5_start('Platform Settings','Configure Google Drive, AI and the student portal.');echo '<div class="uld-grid"><section class="uld-card"><h2>Google Drive</h2><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld')).'">Drive Connection</a></section><section class="uld-card"><h2>AI Providers</h2><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld-ai-providers')).'">AI Providers</a></section><section class="uld-card"><h2>Student Portal</h2><p>Use <code>[student_drive_portal]</code> on the student learning page.</p></section></div></div>';}
