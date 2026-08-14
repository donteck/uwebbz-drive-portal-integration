<?php
if (!defined('ABSPATH')) exit;

final class ULD_V4_Visual_Workspace {
    const ROOT_OPTION = 'uld_lms_root_folder_id';
    const ROOT_NAME_OPTION = 'uld_lms_root_folder_name';

    public static function init(){
        add_action('admin_menu',[__CLASS__,'menus'],99);
        add_action('admin_init',[__CLASS__,'actions'],4);
    }

    public static function menus(){
        remove_submenu_page('uld','uld-drive-browser');
        add_submenu_page('uld','My Drive Workspace','My Drive','manage_options','uld-drive-browser',[__CLASS__,'browser']);
        add_submenu_page('uld','Drive Workspace Settings','Drive Workspace','manage_options','uld-drive-workspace',[__CLASS__,'settings']);
    }

    private static function token(){
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

    private static function drive_get($path,$query=[]){
        $token=self::token();
        if(!$token) return new WP_Error('not_connected','Google Drive is not connected.');
        $url='https://www.googleapis.com/drive/v3/'.ltrim($path,'/');
        if($query) $url=add_query_arg($query,$url);
        $r=wp_remote_get($url,['timeout'=>25,'headers'=>['Authorization'=>'Bearer '.$token]]);
        if(is_wp_error($r)) return $r;
        $code=wp_remote_retrieve_response_code($r);
        $b=json_decode(wp_remote_retrieve_body($r),true);
        if($code<200||$code>=300) return new WP_Error('drive_api',$b['error']['message']??'Google Drive API error');
        return $b;
    }

    private static function list_folder($folder){
        $q="'".str_replace("'","\\'",$folder)."' in parents and trashed = false";
        return self::drive_get('files',['q'=>$q,'fields'=>'files(id,name,mimeType,webViewLink,modifiedTime,parents,thumbnailLink)','orderBy'=>'folder,name','pageSize'=>1000]);
    }

    private static function normalize_folder_id($raw){
        $raw=trim((string)$raw);
        if(preg_match('~/folders/([A-Za-z0-9_-]+)~',$raw,$m)) return $m[1];
        return preg_replace('/[^A-Za-z0-9_-]/','',$raw);
    }

    private static function nav_url($folder){
        $args=['page'=>'uld-drive-browser','folder'=>$folder];
        $root=(string)get_option(self::ROOT_OPTION);
        if($folder && $folder!==$root) $args['_uldnav']=wp_create_nonce('uld_nav_'.$folder);
        return add_query_arg($args,admin_url('admin.php'));
    }

    public static function actions(){
        if(!is_admin()||!current_user_can('manage_options')) return;
        if(isset($_POST['uld_set_workspace_root'])){
            check_admin_referer('uld_set_workspace_root');
            $id=self::normalize_folder_id(wp_unslash($_POST['root_folder']??''));
            $name=sanitize_text_field(wp_unslash($_POST['root_name']??''));
            if($id){ update_option(self::ROOT_OPTION,$id,false); update_option(self::ROOT_NAME_OPTION,$name?:'Course Library',false); }
            wp_safe_redirect(admin_url('admin.php?page=uld-drive-browser')); exit;
        }
        if(isset($_POST['uld_clear_workspace_root'])){
            check_admin_referer('uld_clear_workspace_root');
            delete_option(self::ROOT_OPTION); delete_option(self::ROOT_NAME_OPTION);
            wp_safe_redirect(admin_url('admin.php?page=uld-drive-workspace')); exit;
        }
        if(isset($_POST['uld_visual_assign'])){
            check_admin_referer('uld_visual_assign');
            $drive_id=sanitize_text_field(wp_unslash($_POST['drive_id']??''));
            $drive_name=sanitize_text_field(wp_unslash($_POST['drive_name']??''));
            $kind=sanitize_key($_POST['assign_kind']??'');
            $target=absint($_POST['target_id']??0);
            if($drive_id && $target){
                if($kind==='student'){
                    update_user_meta($target,'uld_drive_folder_id',$drive_id);
                    update_user_meta($target,'uld_drive_folder_name',$drive_name);
                } elseif($kind==='course' && get_post_type($target)==='uld_course'){
                    update_post_meta($target,'uld_drive_folder_id',$drive_id);
                    update_post_meta($target,'uld_drive_folder_name',$drive_name);
                }
            }
            $return=sanitize_text_field(wp_unslash($_POST['return_folder']??''));
            wp_safe_redirect(add_query_arg('uld_notice','assigned',self::nav_url($return?:get_option(self::ROOT_OPTION)))); exit;
        }
    }

    public static function settings(){
        $root=(string)get_option(self::ROOT_OPTION);
        $name=(string)get_option(self::ROOT_NAME_OPTION,'Course Library');
        echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ TECHNOLOGY</span><h1>Drive Workspace</h1><p>Choose one Google Drive folder for UWEBBZ. Teachers will work inside this folder instead of browsing your entire Drive.</p></div></div>';
        echo '<div class="uld-v4-panel"><h2>Choose LMS Root Folder</h2><p>Paste a Google Drive folder URL or folder ID. Example: <code>https://drive.google.com/drive/folders/...</code></p><form method="post">';
        wp_nonce_field('uld_set_workspace_root');
        echo '<div class="uld-v4-field"><label>Folder URL or ID</label><input type="text" name="root_folder" value="'.esc_attr($root).'" placeholder="Paste folder URL or ID"></div><div class="uld-v4-field"><label>Workspace Name</label><input type="text" name="root_name" value="'.esc_attr($name).'" placeholder="Course Library"></div><p><button class="button button-primary button-hero" name="uld_set_workspace_root" value="1">Use This Folder</button></p></form>';
        if($root){ echo '<form method="post">'; wp_nonce_field('uld_clear_workspace_root'); echo '<button class="button" name="uld_clear_workspace_root" value="1">Remove Folder Restriction</button></form>'; }
        echo '</div></div>';
    }

    public static function browser(){
        $root=(string)get_option(self::ROOT_OPTION);
        $root_name=(string)get_option(self::ROOT_NAME_OPTION,'Course Library');
        if(!$root){
            echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ TECHNOLOGY</span><h1>My Drive Workspace</h1><p>Select the one Drive folder you want UWEBBZ to use.</p></div></div><div class="uld-empty"><h2>No LMS folder selected</h2><p>Your full Drive is not being shown. Choose a specific folder first.</p><a class="button button-primary button-hero" href="'.esc_url(admin_url('admin.php?page=uld-drive-workspace')).'">Choose Drive Folder</a></div></div>';
            return;
        }
        $folder=sanitize_text_field(wp_unslash($_GET['folder']??$root)); if(!$folder)$folder=$root;
        $data=self::list_folder($folder);
        echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ TECHNOLOGY</span><h1>'.esc_html($root_name).'</h1><p>Your protected LMS workspace. Only content inside the selected Drive folder is presented here.</p></div><div class="uld-status is-connected"><span class="uld-dot"></span>Restricted Workspace</div></div>';
        if(isset($_GET['uld_notice'])&&sanitize_key($_GET['uld_notice'])==='outside_workspace_blocked') echo '<div class="notice notice-warning"><p>Navigation outside the selected LMS Drive folder was blocked.</p></div>';
        if(isset($_GET['uld_notice'])&&sanitize_key($_GET['uld_notice'])==='assigned') echo '<div class="notice notice-success is-dismissible"><p>Assignment saved.</p></div>';
        echo '<div class="uld-v4-toolbar"><a class="button button-primary" href="'.esc_url(self::nav_url($root)).'">Workspace Home</a><a class="button" href="'.esc_url(admin_url('admin.php?page=uld-drive-workspace')).'">Change Root Folder</a><a class="button" href="'.esc_url(self::nav_url($folder)).'">Refresh</a></div>';
        if(is_wp_error($data)){ echo '<div class="notice notice-error"><p>'.esc_html($data->get_error_message()).'</p></div></div>'; return; }
        $files=$data['files']??[];
        if(!$files){ echo '<div class="uld-empty"><h2>This folder is empty</h2><p>Add course materials to the selected Google Drive folder and click Refresh.</p></div></div>'; return; }
        echo '<div class="uld-v4-file-grid">'; foreach($files as $f) self::card($f,$folder); echo '</div></div>';
    }

    private static function card($f,$folder){
        $id=$f['id']??''; $name=$f['name']??'Untitled'; $mime=$f['mimeType']??''; $is_folder=$mime==='application/vnd.google-apps.folder';
        echo '<article class="uld-v4-file"><div class="uld-v4-file-top"><span class="dashicons '.($is_folder?'dashicons-category':'dashicons-media-document').'"></span><div><small>'.esc_html($is_folder?'Folder':'Learning Resource').'</small><h3>'.esc_html($name).'</h3></div></div><div class="uld-v4-actions">';
        if($is_folder) echo '<a class="button button-primary" href="'.esc_url(self::nav_url($id)).'">Open Folder</a>';
        elseif(!empty($f['webViewLink'])) echo '<a class="button button-primary" target="_blank" rel="noopener" href="'.esc_url($f['webViewLink']).'">Preview</a>';
        echo '<button type="button" class="button uld-v4-toggle" data-target="assign-'.esc_attr($id).'">Assign</button></div>';
        echo '<div id="assign-'.esc_attr($id).'" class="uld-v4-assign-panel" hidden>'; self::visual_assigner($id,$name,$folder,$is_folder); echo '</div></article>';
    }

    private static function visual_assigner($id,$name,$folder,$is_folder){
        echo '<form method="post">'; wp_nonce_field('uld_visual_assign');
        echo '<input type="hidden" name="drive_id" value="'.esc_attr($id).'"><input type="hidden" name="drive_name" value="'.esc_attr($name).'"><input type="hidden" name="return_folder" value="'.esc_attr($folder).'">';
        echo '<div class="uld-v4-tabs"><label><input type="radio" name="assign_kind" value="student" checked><span>Students</span></label>'; if($is_folder) echo '<label><input type="radio" name="assign_kind" value="course"><span>Courses</span></label>'; echo '</div>';
        echo '<div class="uld-v4-choice-grid">';
        foreach(get_users(['orderby'=>'display_name']) as $u) echo '<label class="uld-v4-choice"><input type="radio" name="target_id" value="'.absint($u->ID).'" required><span class="uld-v4-avatar">'.esc_html(strtoupper(substr($u->display_name?:$u->user_login,0,1))).'</span><span><strong>'.esc_html($u->display_name?:$u->user_login).'</strong><small>'.esc_html($u->user_email).'</small></span></label>';
        if($is_folder){ foreach(get_posts(['post_type'=>'uld_course','post_status'=>['publish','draft'],'numberposts'=>-1,'orderby'=>'title','order'=>'ASC']) as $c) echo '<label class="uld-v4-choice uld-v4-course-choice"><input type="radio" name="target_id" value="'.absint($c->ID).'"><span class="dashicons dashicons-welcome-learn-more"></span><span><strong>'.esc_html($c->post_title).'</strong><small>Course</small></span></label>'; }
        echo '</div><p><button class="button button-primary" name="uld_visual_assign" value="1">Assign Selected</button></p></form>';
    }
}

ULD_V4_Visual_Workspace::init();
