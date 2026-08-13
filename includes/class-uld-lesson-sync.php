<?php
if (!defined('ABSPATH')) exit;

final class ULD_Lesson_Sync {
    private static $busy=false;
    public static function init(){
        add_action('add_meta_boxes',[__CLASS__,'boxes']);
        add_action('save_post_uld_lesson',[__CLASS__,'save'],30,3);
        add_action('admin_menu',[__CLASS__,'menu'],30);
    }
    public static function menu(){add_submenu_page('uld','Lesson Sync','Lesson Sync','manage_options','uld-lesson-sync',[__CLASS__,'page']);}
    public static function boxes(){
        add_meta_box('uld_delivery','UWEBBZ Delivery',[__CLASS__,'delivery'],'uld_lesson','side','high');
        add_meta_box('uld_sync','Google Drive Sync',[__CLASS__,'syncbox'],'uld_lesson','normal','high');
    }
    public static function delivery($post){
        wp_nonce_field('uld_lesson_'.$post->ID,'uld_lesson_nonce');
        $course=absint(get_post_meta($post->ID,'uld_course_id',true)); $mode=get_post_meta($post->ID,'uld_availability',true)?:'now'; $when=get_post_meta($post->ID,'uld_available_at',true);
        echo '<p><strong>Course</strong><br><select name="uld_course_id" style="width:100%"><option value="0">No course</option>';
        foreach(get_posts(['post_type'=>'uld_course','post_status'=>['publish','draft'],'numberposts'=>-1,'orderby'=>'title','order'=>'ASC']) as $c)echo '<option value="'.absint($c->ID).'" '.selected($course,$c->ID,false).'>'.esc_html($c->post_title).'</option>';
        echo '</select></p><p><strong>Availability</strong><br><select name="uld_availability" style="width:100%">';
        foreach(['now'=>'Available now','scheduled'=>'Schedule release','hidden'=>'Hidden until teacher releases it'] as $k=>$v)echo '<option value="'.esc_attr($k).'" '.selected($mode,$k,false).'>'.esc_html($v).'</option>';
        echo '</select></p><p><strong>Release date/time</strong><br><input type="datetime-local" name="uld_available_at" value="'.esc_attr($when).'" style="width:100%"></p>';
    }
    public static function syncbox($post){
        $auto=get_post_meta($post->ID,'uld_auto_sync',true); if($auto==='')$auto='1'; $folder=get_post_meta($post->ID,'uld_drive_folder_override',true); $url=get_post_meta($post->ID,'uld_drive_url',true); $status=get_post_meta($post->ID,'uld_drive_sync_status',true)?:'Not synced yet';
        echo '<p><label><input type="checkbox" name="uld_auto_sync" value="1" '.checked($auto,'1',false).'> Automatically create/update this lesson in Google Drive when saved.</label></p>';
        echo '<p><strong>Folder override</strong><br><input class="widefat" name="uld_drive_folder_override" value="'.esc_attr($folder).'" placeholder="Uses course folder when blank"></p><p><strong>Status:</strong> '.esc_html($status).'</p>';
        if($url)echo '<p><a class="button" target="_blank" rel="noopener" href="'.esc_url($url).'">Open Google Doc</a></p>'; echo '<p><button class="button button-primary" name="uld_sync_now" value="1">Save & Sync Now</button></p>';
    }
    public static function save($id,$post,$update){
        if(self::$busy||wp_is_post_revision($id)||wp_is_post_autosave($id)||!current_user_can('edit_post',$id))return;
        if(empty($_POST['uld_lesson_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['uld_lesson_nonce'])),'uld_lesson_'.$id))return;
        update_post_meta($id,'uld_course_id',absint($_POST['uld_course_id']??0)); $mode=sanitize_key($_POST['uld_availability']??'now'); if(!in_array($mode,['now','scheduled','hidden'],true))$mode='now'; update_post_meta($id,'uld_availability',$mode); update_post_meta($id,'uld_available_at',sanitize_text_field(wp_unslash($_POST['uld_available_at']??''))); update_post_meta($id,'uld_auto_sync',isset($_POST['uld_auto_sync'])?'1':'0'); update_post_meta($id,'uld_drive_folder_override',sanitize_text_field(wp_unslash($_POST['uld_drive_folder_override']??'')));
        if(get_post_meta($id,'uld_auto_sync',true)==='1'||!empty($_POST['uld_sync_now']))self::sync($id);
    }
    public static function sync($id){
        self::$busy=true; $post=get_post($id); if(!$post){self::$busy=false;return false;}
        $folder=trim((string)get_post_meta($id,'uld_drive_folder_override',true)); if(!$folder){$course=absint(get_post_meta($id,'uld_course_id',true)); if($course)$folder=trim((string)get_post_meta($course,'uld_drive_folder_id',true));}
        if(!$folder){update_post_meta($id,'uld_drive_sync_status','Assign a course Drive folder first.');self::$busy=false;return false;}
        $token=(string)get_option('uld_google_access_token'); if(!$token){update_post_meta($id,'uld_drive_sync_status','Open My Drive once to refresh the Google session, then sync again.');self::$busy=false;return false;}
        $existing=get_post_meta($id,'uld_drive_id',true); $html='<!doctype html><html><body><h1>'.esc_html($post->post_title).'</h1>'.apply_filters('the_content',$post->post_content).'<hr><p><em>Synced from UWEBBZ Drive LMS.</em></p></body></html>';
        $result=self::upload($token,$post->post_title,$html,$folder,$existing); if(!$result){update_post_meta($id,'uld_drive_sync_status','Sync failed. Reconnect Google with Drive file-write permission.');self::$busy=false;return false;}
        $drive=$result['id']??$existing; if($drive){update_post_meta($id,'uld_drive_id',sanitize_text_field($drive));update_post_meta($id,'uld_drive_url','https://drive.google.com/open?id='.rawurlencode($drive));update_post_meta($id,'uld_drive_sync_status','Synced successfully');update_post_meta($id,'uld_drive_synced_at',current_time('mysql'));}
        self::$busy=false;return true;
    }
    private static function upload($token,$name,$html,$folder,$id=''){
        $boundary='uld'.wp_generate_password(16,false,false); $meta=['name'=>$name,'mimeType'=>'application/vnd.google-apps.document']; if(!$id)$meta['parents']=[$folder];
        $body='--'.$boundary."\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n".wp_json_encode($meta)."\r\n--".$boundary."\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n".$html."\r\n--".$boundary."--";
        $url=$id?'https://www.googleapis.com/upload/drive/v3/files/'.rawurlencode($id).'?uploadType=multipart&fields=id,name':'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name';
        $r=wp_remote_request($url,['method'=>$id?'PATCH':'POST','timeout'=>30,'headers'=>['Authorization'=>'Bearer '.$token,'Content-Type'=>'multipart/related; boundary='.$boundary],'body'=>$body]); if(is_wp_error($r))return false; $code=wp_remote_retrieve_response_code($r); if($code<200||$code>=300)return false; return json_decode(wp_remote_retrieve_body($r),true)?:[];
    }
    public static function page(){echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ TECHNOLOGY</span><h1>Lesson Sync</h1><p>Create a WordPress lesson once and keep a matching Google Doc in the course folder.</p></div></div><div class="uld-grid"><section class="uld-card"><h2>Automatic sync</h2><p>Choose a course, keep Auto-sync enabled, and save. UWEBBZ creates a Google Doc. Later edits update the same document.</p></section><section class="uld-card"><h2>Teacher workflow</h2><p><a class="button button-primary" href="'.esc_url(admin_url('post-new.php?post_type=uld_lesson')).'">Create Lesson</a> <a class="button" href="'.esc_url(admin_url('edit.php?post_type=uld_lesson')).'">Manage Lessons</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=uld-drive-browser')).'">My Drive</a></p></section></div></div>';}
}
