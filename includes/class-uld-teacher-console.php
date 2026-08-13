<?php
if (!defined('ABSPATH')) exit;

final class ULD_Teacher_Console {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menus'], 20);
        add_action('admin_init', [__CLASS__, 'actions']);
        add_action('init', [__CLASS__, 'content_types']);
    }

    public static function content_types() {
        register_post_type('uld_course', [
            'labels' => ['name'=>'UWEBBZ Courses','singular_name'=>'UWEBBZ Course','add_new_item'=>'Add Course','edit_item'=>'Edit Course'],
            'public'=>false,'show_ui'=>true,'show_in_menu'=>false,'supports'=>['title','editor']
        ]);
        register_post_type('uld_lesson', [
            'labels' => ['name'=>'UWEBBZ Lessons','singular_name'=>'UWEBBZ Lesson','add_new_item'=>'Add Lesson','edit_item'=>'Edit Lesson'],
            'public'=>false,'show_ui'=>true,'show_in_menu'=>false,'supports'=>['title','editor']
        ]);
    }

    public static function menus() {
        add_submenu_page('uld','My Drive','My Drive','manage_options','uld-drive-browser',[__CLASS__,'browser']);
        add_submenu_page('uld','Courses','Courses','manage_options','edit.php?post_type=uld_course');
        add_submenu_page('uld','Lessons','Lessons','manage_options','edit.php?post_type=uld_lesson');
        add_submenu_page('uld','Assignments','Assignments','manage_options','uld-assignments',[__CLASS__,'assignments']);
    }

    private static function access_token() {
        $token = (string) get_option('uld_google_access_token');
        $expires = absint(get_option('uld_google_token_expires'));
        if ($token && $expires > time()) return $token;
        $refresh = (string) get_option('uld_google_refresh_token');
        if (!$refresh) return '';
        $r = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout'=>20,
            'body'=>[
                'client_id'=>get_option('uld_google_client_id'),
                'client_secret'=>get_option('uld_google_client_secret'),
                'refresh_token'=>$refresh,
                'grant_type'=>'refresh_token'
            ]
        ]);
        if (is_wp_error($r)) return '';
        $b = json_decode(wp_remote_retrieve_body($r), true);
        if (empty($b['access_token'])) return '';
        update_option('uld_google_access_token', sanitize_text_field($b['access_token']), false);
        update_option('uld_google_token_expires', time()+max(60,absint($b['expires_in']??3600)-60), false);
        return (string) $b['access_token'];
    }

    private static function drive_get($path, $query=[]) {
        $token = self::access_token();
        if (!$token) return new WP_Error('not_connected','Google Drive is not connected.');
        $url = 'https://www.googleapis.com/drive/v3/'.ltrim($path,'/');
        if ($query) $url = add_query_arg($query,$url);
        $r = wp_remote_get($url,['timeout'=>25,'headers'=>['Authorization'=>'Bearer '.$token]]);
        if (is_wp_error($r)) return $r;
        $b = json_decode(wp_remote_retrieve_body($r),true);
        $code = wp_remote_retrieve_response_code($r);
        if ($code<200 || $code>=300) return new WP_Error('drive_api',$b['error']['message']??'Google Drive API error');
        return $b;
    }

    private static function list_folder($folder='root') {
        $q = "'".str_replace("'","\\'",$folder)."' in parents and trashed = false";
        return self::drive_get('files',[
            'q'=>$q,
            'fields'=>'files(id,name,mimeType,webViewLink,modifiedTime,parents,size,thumbnailLink)',
            'orderBy'=>'folder,name',
            'pageSize'=>1000
        ]);
    }

    public static function actions() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        if (isset($_POST['uld_tc_assign_student'])) {
            check_admin_referer('uld_tc_assign_student');
            $uid=absint($_POST['student_id']??0); $id=sanitize_text_field(wp_unslash($_POST['drive_id']??''));
            if ($uid && $id) {
                update_user_meta($uid,'uld_drive_folder_id',$id);
                update_user_meta($uid,'uld_drive_folder_name',sanitize_text_field(wp_unslash($_POST['drive_name']??'')));
            }
            self::redirect('student_assigned');
        }
        if (isset($_POST['uld_tc_assign_course'])) {
            check_admin_referer('uld_tc_assign_course');
            $cid=absint($_POST['course_id']??0); $id=sanitize_text_field(wp_unslash($_POST['drive_id']??''));
            if ($cid && get_post_type($cid)==='uld_course') {
                update_post_meta($cid,'uld_drive_folder_id',$id);
                update_post_meta($cid,'uld_drive_folder_name',sanitize_text_field(wp_unslash($_POST['drive_name']??'')));
            }
            self::redirect('course_assigned');
        }
        if (isset($_POST['uld_tc_create_lesson'])) {
            check_admin_referer('uld_tc_create_lesson');
            $post_id=wp_insert_post(['post_type'=>'uld_lesson','post_status'=>'publish','post_title'=>sanitize_text_field(wp_unslash($_POST['lesson_title']??'Drive Lesson'))]);
            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id,'uld_drive_id',sanitize_text_field(wp_unslash($_POST['drive_id']??'')));
                update_post_meta($post_id,'uld_drive_name',sanitize_text_field(wp_unslash($_POST['drive_name']??'')));
                update_post_meta($post_id,'uld_drive_mime',sanitize_text_field(wp_unslash($_POST['drive_mime']??'')));
                update_post_meta($post_id,'uld_course_id',absint($_POST['course_id']??0));
                update_post_meta($post_id,'uld_availability',sanitize_key($_POST['availability']??'now'));
                update_post_meta($post_id,'uld_available_at',sanitize_text_field(wp_unslash($_POST['available_at']??'')));
            }
            self::redirect('lesson_created');
        }
        if (isset($_POST['uld_tc_enroll'])) {
            check_admin_referer('uld_tc_enroll');
            $uid=absint($_POST['student_id']??0); $cid=absint($_POST['course_id']??0);
            if ($uid && $cid) {
                $list=(array)get_user_meta($uid,'uld_course_ids',true);
                $list=array_values(array_unique(array_map('absint',array_merge($list,[$cid]))));
                update_user_meta($uid,'uld_course_ids',$list);
            }
            wp_safe_redirect(add_query_arg(['page'=>'uld-assignments','uld_notice'=>'enrolled'],admin_url('admin.php'))); exit;
        }
    }

    private static function redirect($notice) {
        $folder=sanitize_text_field(wp_unslash($_POST['return_folder']??'root'));
        wp_safe_redirect(add_query_arg(['page'=>'uld-drive-browser','folder'=>$folder,'uld_notice'=>$notice],admin_url('admin.php'))); exit;
    }

    private static function type_label($mime) {
        if ($mime==='application/vnd.google-apps.folder') return 'Folder';
        if (strpos($mime,'presentation')!==false) return 'PowerPoint / Slides';
        if (strpos($mime,'pdf')!==false) return 'PDF';
        if (strpos($mime,'document')!==false) return 'Document';
        if (strpos($mime,'spreadsheet')!==false) return 'Spreadsheet';
        if (strpos($mime,'video')!==false) return 'Video';
        return 'File';
    }

    public static function browser() {
        $folder=sanitize_text_field(wp_unslash($_GET['folder']??'root'));
        echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ TECHNOLOGY</span><h1>My Drive</h1><p>Browse folders and teaching files, then assign or publish them without copying folder IDs.</p></div><div class="uld-status is-connected"><span class="uld-dot"></span>Teacher Workspace</div></div>';
        if (isset($_GET['uld_notice'])) echo '<div class="notice notice-success is-dismissible"><p>'.esc_html(str_replace('_',' ',sanitize_key($_GET['uld_notice']))).'.</p></div>';
        echo '<div class="uld-toolbar"><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=uld-drive-browser')).'">My Drive</a> <a class="button" href="'.esc_url(add_query_arg(['page'=>'uld-drive-browser','folder'=>$folder],admin_url('admin.php'))).'">Refresh</a><span>Current folder: <code>'.esc_html($folder==='root'?'My Drive':$folder).'</code></span></div>';
        $data=self::list_folder($folder);
        if (is_wp_error($data)) { echo '<div class="notice notice-error"><p>'.esc_html($data->get_error_message()).'</p></div></div>'; return; }
        $files=$data['files']??[];
        if (!$files) { echo '<div class="uld-empty"><h2>This folder is empty</h2><p>Add content in Google Drive and click Refresh.</p></div></div>'; return; }
        echo '<div class="uld-drive-grid">';
        foreach ($files as $file) self::card($file,$folder);
        echo '</div></div>';
    }

    private static function card($file,$folder) {
        $id=$file['id']??''; $name=$file['name']??'Untitled'; $mime=$file['mimeType']??''; $is_folder=$mime==='application/vnd.google-apps.folder';
        echo '<article class="uld-drive-card"><div class="uld-file-icon"><span class="dashicons '.($is_folder?'dashicons-category':'dashicons-media-document').'"></span></div><div class="uld-file-body"><span class="uld-type">'.esc_html(self::type_label($mime)).'</span><h3>'.esc_html($name).'</h3><small>'.esc_html($file['modifiedTime']??'').'</small></div><div class="uld-actions">';
        if ($is_folder) echo '<a class="button button-primary" href="'.esc_url(add_query_arg(['page'=>'uld-drive-browser','folder'=>$id],admin_url('admin.php'))).'">Open Folder</a>';
        elseif (!empty($file['webViewLink'])) echo '<a class="button" target="_blank" rel="noopener" href="'.esc_url($file['webViewLink']).'">Preview</a>';
        echo '<details><summary>Assign / Publish</summary><div class="uld-action-panel">';
        self::student_form($id,$name,$folder);
        if ($is_folder) self::course_form($id,$name,$folder);
        self::lesson_form($id,$name,$mime,$folder);
        echo '</div></details></div></article>';
    }

    private static function student_form($id,$name,$folder) {
        echo '<form method="post" class="uld-mini-form">'; wp_nonce_field('uld_tc_assign_student');
        echo '<input type="hidden" name="drive_id" value="'.esc_attr($id).'"><input type="hidden" name="drive_name" value="'.esc_attr($name).'"><input type="hidden" name="return_folder" value="'.esc_attr($folder).'"><label>Assign to Student</label><select name="student_id" required><option value="">Choose user…</option>';
        foreach(get_users(['orderby'=>'display_name']) as $u) echo '<option value="'.absint($u->ID).'">'.esc_html($u->display_name.' — '.$u->user_email).'</option>';
        echo '</select><button class="button" name="uld_tc_assign_student" value="1">Assign to Student</button></form>';
    }

    private static function course_form($id,$name,$folder) {
        echo '<form method="post" class="uld-mini-form">'; wp_nonce_field('uld_tc_assign_course');
        echo '<input type="hidden" name="drive_id" value="'.esc_attr($id).'"><input type="hidden" name="drive_name" value="'.esc_attr($name).'"><input type="hidden" name="return_folder" value="'.esc_attr($folder).'"><label>Assign Folder to Course</label><select name="course_id" required><option value="">Choose course…</option>';
        foreach(get_posts(['post_type'=>'uld_course','post_status'=>'publish','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']) as $c) echo '<option value="'.absint($c->ID).'">'.esc_html($c->post_title).'</option>';
        echo '</select><button class="button" name="uld_tc_assign_course" value="1">Assign to Course</button></form>';
    }

    private static function lesson_form($id,$name,$mime,$folder) {
        echo '<form method="post" class="uld-mini-form">'; wp_nonce_field('uld_tc_create_lesson');
        echo '<input type="hidden" name="drive_id" value="'.esc_attr($id).'"><input type="hidden" name="drive_name" value="'.esc_attr($name).'"><input type="hidden" name="drive_mime" value="'.esc_attr($mime).'"><input type="hidden" name="return_folder" value="'.esc_attr($folder).'"><label>Make this a Lesson</label><input type="text" name="lesson_title" value="'.esc_attr($name).'" required><select name="course_id"><option value="0">No course</option>';
        foreach(get_posts(['post_type'=>'uld_course','post_status'=>'publish','numberposts'=>-1]) as $c) echo '<option value="'.absint($c->ID).'">'.esc_html($c->post_title).'</option>';
        echo '</select><select name="availability"><option value="now">Available now</option><option value="scheduled">Schedule release</option><option value="hidden">Hidden until teacher releases it</option></select><label>Scheduled date/time (only if scheduled)</label><input type="datetime-local" name="available_at"><button class="button button-primary" name="uld_tc_create_lesson" value="1">Create Lesson</button></form>';
    }

    public static function assignments() {
        echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ TECHNOLOGY</span><h1>Assignments</h1><p>Enroll students in courses and review direct Drive-folder access.</p></div></div>';
        if (isset($_GET['uld_notice'])) echo '<div class="notice notice-success"><p>Student enrolled.</p></div>';
        echo '<div class="uld-grid"><section class="uld-card"><h2>Enroll Student in Course</h2><form method="post">'; wp_nonce_field('uld_tc_enroll');
        echo '<label>Student</label><select name="student_id" required><option value="">Choose student…</option>'; foreach(get_users(['orderby'=>'display_name']) as $u) echo '<option value="'.absint($u->ID).'">'.esc_html($u->display_name.' — '.$u->user_email).'</option>'; echo '</select><label>Course</label><select name="course_id" required><option value="">Choose course…</option>'; foreach(get_posts(['post_type'=>'uld_course','post_status'=>'publish','numberposts'=>-1]) as $c) echo '<option value="'.absint($c->ID).'">'.esc_html($c->post_title).'</option>'; echo '</select><p><button class="button button-primary" name="uld_tc_enroll" value="1">Enroll Student</button></p></form></section><section class="uld-card"><h2>Direct Folder Assignments</h2><div class="uld-assignment-list">';
        foreach(get_users(['orderby'=>'display_name']) as $u) { $id=get_user_meta($u->ID,'uld_drive_folder_id',true); if ($id) echo '<div><strong>'.esc_html($u->display_name).'</strong><span>'.esc_html(get_user_meta($u->ID,'uld_drive_folder_name',true)?:$id).'</span></div>'; }
        echo '</div></section></div></div>';
    }
}
