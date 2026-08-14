<?php
if (!defined('ABSPATH')) exit;

final class ULD_V4_Visual_Enrollment {
    public static function init(){ add_action('admin_menu',[__CLASS__,'menu'],100); }
    public static function menu(){
        remove_submenu_page('uld','uld-assignments');
        add_submenu_page('uld','Students & Enrollments','Enrollments','manage_options','uld-assignments',[__CLASS__,'page']);
    }
    public static function page(){
        $students=get_users(['orderby'=>'display_name']);
        $courses=get_posts(['post_type'=>'uld_course','post_status'=>['publish','draft'],'numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
        echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ DRIVE LMS v4</span><h1>Students & Enrollments</h1><p>Select a student and a course visually, then enroll with one click. No dropdowns.</p></div><div class="uld-status is-connected"><span class="uld-dot"></span>Visual Enrollment</div></div>';
        if(isset($_GET['uld_notice'])&&sanitize_key($_GET['uld_notice'])==='enrolled') echo '<div class="notice notice-success is-dismissible"><p>Student enrolled and enrollment notification processed.</p></div>';
        echo '<form method="post" class="uld-v4-enrollment-form">'; wp_nonce_field('uld_tc_enroll');
        echo '<section class="uld-v4-panel"><div class="uld-v4-section-head"><div><span class="uld-v4-stepnum">1</span><h2>Choose Student</h2></div><p>Select the learner you want to enroll.</p></div><div class="uld-v4-choice-grid uld-v4-choice-grid-large">';
        foreach($students as $u){
            echo '<label class="uld-v4-choice uld-v4-student-card"><input type="radio" name="student_id" value="'.absint($u->ID).'" required><span class="uld-v4-avatar">'.esc_html(strtoupper(substr($u->display_name?:$u->user_login,0,1))).'</span><span><strong>'.esc_html($u->display_name?:$u->user_login).'</strong><small>'.esc_html($u->user_email).'</small></span></label>';
        }
        echo '</div></section>';
        echo '<section class="uld-v4-panel"><div class="uld-v4-section-head"><div><span class="uld-v4-stepnum">2</span><h2>Choose Course</h2></div><p>Select the course to assign.</p></div><div class="uld-v4-course-grid">';
        if(!$courses) echo '<div class="uld-empty"><h3>No courses yet</h3><p>Create a course first.</p><a class="button button-primary" href="'.esc_url(admin_url('post-new.php?post_type=uld_course')).'">Create Course</a></div>';
        foreach($courses as $c){
            $folder=get_post_meta($c->ID,'uld_drive_folder_name',true);
            echo '<label class="uld-v4-course-card"><input type="radio" name="course_id" value="'.absint($c->ID).'" required><span class="dashicons dashicons-welcome-learn-more"></span><span><strong>'.esc_html($c->post_title).'</strong><small>'.esc_html($folder?:'WordPress course').'</small></span></label>';
        }
        echo '</div></section>';
        echo '<section class="uld-v4-panel uld-v4-confirm"><div><span class="uld-v4-stepnum">3</span><h2>Confirm Enrollment</h2><p>The student receives the course assignment and the enrollment email notification.</p></div><button class="button button-primary button-hero" name="uld_tc_enroll" value="1">Enroll Selected Student</button></section></form>';
        echo '<section class="uld-v4-panel"><h2>Current Enrollments</h2><div class="uld-v4-roster">';
        $has=false;
        foreach($students as $u){
            foreach(array_map('absint',(array)get_user_meta($u->ID,'uld_course_ids',true)) as $cid){
                if(get_post_type($cid)!=='uld_course') continue; $has=true;
                echo '<div class="uld-v4-roster-row"><span class="uld-v4-avatar">'.esc_html(strtoupper(substr($u->display_name?:$u->user_login,0,1))).'</span><span><strong>'.esc_html($u->display_name?:$u->user_login).'</strong><small>'.esc_html($u->user_email).'</small></span><span class="uld-v4-roster-course">'.esc_html(get_the_title($cid)).'</span></div>';
            }
        }
        if(!$has) echo '<p>No course enrollments yet.</p>';
        echo '</div></section></div>';
    }
}
ULD_V4_Visual_Enrollment::init();
