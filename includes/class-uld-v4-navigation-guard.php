<?php
if (!defined('ABSPATH')) exit;

final class ULD_V4_Navigation_Guard {
    public static function init(){ add_action('admin_init',[__CLASS__,'guard'],2); }
    public static function guard(){
        if(!is_admin() || !current_user_can('manage_options')) return;
        if(($_GET['page']??'')!=='uld-drive-browser') return;
        $root=(string)get_option('uld_lms_root_folder_id');
        if(!$root) return;
        $folder=sanitize_text_field(wp_unslash($_GET['folder']??$root));
        if(!$folder || $folder===$root) return;
        $nonce=sanitize_text_field(wp_unslash($_GET['_uldnav']??''));
        if(!$nonce || !wp_verify_nonce($nonce,'uld_nav_'.$folder)){
            wp_safe_redirect(add_query_arg(['page'=>'uld-drive-browser','uld_notice'=>'outside_workspace_blocked'],admin_url('admin.php')));
            exit;
        }
    }
}
ULD_V4_Navigation_Guard::init();
