<?php
if (!defined('ABSPATH')) exit;

add_action('init', function(){
    remove_shortcode('student_drive_portal');
    add_shortcode('student_drive_portal', 'uld_student_portal_v2');
}, 100);

function uld_v2_token(){
    $token=(string)get_option('uld_google_access_token');
    $expires=absint(get_option('uld_google_token_expires'));
    if($token && $expires>time()) return $token;
    $refresh=(string)get_option('uld_google_refresh_token');
    if(!$refresh) return '';
    $r=wp_remote_post('https://oauth2.googleapis.com/token',['timeout'=>20,'body'=>[
        'client_id'=>get_option('uld_google_client_id'),
        'client_secret'=>get_option('uld_google_client_secret'),
        'refresh_token'=>$refresh,
        'grant_type'=>'refresh_token'
    ]]);
    if(is_wp_error($r)) return '';
    $b=json_decode(wp_remote_retrieve_body($r),true);
    if(empty($b['access_token'])) return '';
    update_option('uld_google_access_token',sanitize_text_field($b['access_token']),false);
    update_option('uld_google_token_expires',time()+max(60,absint($b['expires_in']??3600)-60),false);
    return (string)$b['access_token'];
}

function uld_v2_folder($folder){
    $token=uld_v2_token();
    if(!$token) return '<div class="uld-portal-message">Google Drive is not connected.</div>';
    $q="'".str_replace("'","\\'",$folder)."' in parents and trashed = false";
    $url=add_query_arg(['q'=>$q,'fields'=>'files(id,name,mimeType,webViewLink)','orderBy'=>'folder,name','pageSize'=>200],'https://www.googleapis.com/drive/v3/files');
    $r=wp_remote_get($url,['timeout'=>20,'headers'=>['Authorization'=>'Bearer '.$token]]);
    if(is_wp_error($r)) return '<div class="uld-portal-message">Unable to load course materials.</div>';
    $b=json_decode(wp_remote_retrieve_body($r),true); $files=$b['files']??[];
    if(!$files) return '<div class="uld-portal-message">This course folder is currently empty.</div>';
    $html='<div class="uld-file-list">';
    foreach($files as $f){
        $link=$f['webViewLink']??('https://drive.google.com/open?id='.rawurlencode($f['id']??''));
        $html.='<a class="uld-file" target="_blank" rel="noopener" href="'.esc_url($link).'"><span class="dashicons '.(($f['mimeType']??'')==='application/vnd.google-apps.folder'?'dashicons-category':'dashicons-media-document').'"></span><span>'.esc_html($f['name']??'Untitled').'</span></a>';
    }
    return $html.'</div>';
}

function uld_v2_lesson_available($id){
    $mode=get_post_meta($id,'uld_availability',true)?:'now';
    if($mode==='hidden') return false;
    if($mode==='scheduled'){
        $raw=get_post_meta($id,'uld_available_at',true);
        return $raw && strtotime($raw)<=current_time('timestamp');
    }
    return true;
}

function uld_student_portal_v2(){
    if(!is_user_logged_in()) return '<div class="uld-portal-message">Please log in to view your course materials.</div>';
    $uid=get_current_user_id();
    $direct=get_user_meta($uid,'uld_drive_folder_id',true);
    $courses=(array)get_user_meta($uid,'uld_course_ids',true);
    $html='<div class="uld-student-portal"><h2>My Learning Portal</h2>';
    if($direct){
        $title=get_user_meta($uid,'uld_drive_folder_name',true)?:'My Course Materials';
        $html.='<section class="uld-student-section"><h3>'.esc_html($title).'</h3>'.uld_v2_folder($direct).'</section>';
    }
    foreach(array_map('absint',$courses) as $cid){
        if(get_post_type($cid)!=='uld_course') continue;
        $html.='<section class="uld-student-section"><h3>'.esc_html(get_the_title($cid)).'</h3>';
        $lessons=get_posts(['post_type'=>'uld_lesson','post_status'=>'publish','numberposts'=>-1,'meta_key'=>'uld_course_id','meta_value'=>$cid,'orderby'=>'date','order'=>'ASC']);
        $visible=0;
        if($lessons){
            $html.='<div class="uld-file-list">';
            foreach($lessons as $lesson){
                if(!uld_v2_lesson_available($lesson->ID)) continue;
                $visible++;
                $did=get_post_meta($lesson->ID,'uld_drive_id',true);
                $link=$did?'https://drive.google.com/open?id='.rawurlencode($did):'#';
                $html.='<a class="uld-file" target="_blank" rel="noopener" href="'.esc_url($link).'"><span class="dashicons dashicons-welcome-learn-more"></span><span>'.esc_html($lesson->post_title).'</span></a>';
            }
            $html.='</div>';
        }
        if(!$visible){
            $folder=get_post_meta($cid,'uld_drive_folder_id',true);
            $html.=$folder?uld_v2_folder($folder):'<div class="uld-portal-message">No lessons are available yet.</div>';
        }
        $html.='</section>';
    }
    if(!$direct&&!$courses) $html.='<div class="uld-portal-message">Your teacher has not assigned course content to your account yet.</div>';
    return $html.'</div>';
}
