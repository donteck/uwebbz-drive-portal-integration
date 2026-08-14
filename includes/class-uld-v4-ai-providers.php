<?php
if (!defined('ABSPATH')) exit;

final class ULD_V4_AI_Providers {
    const PROVIDER='uld_ai_provider';
    public static function init(){ add_action('admin_menu',[__CLASS__,'menu'],109); add_action('admin_init',[__CLASS__,'save'],7); }
    public static function menu(){ add_submenu_page('uld','AI Providers','AI Providers','manage_options','uld-ai-providers',[__CLASS__,'page']); }
    public static function save(){
        if(!is_admin()||!current_user_can('manage_options')||empty($_POST['uld_ai_provider_save'])) return;
        check_admin_referer('uld_ai_provider_save');
        $provider=sanitize_key($_POST['provider']??'openai');
        if(!in_array($provider,['openai','gemini','claude'],true)) $provider='openai';
        update_option(self::PROVIDER,$provider,false);
        $map=['openai'=>'uld_ai_api_key','gemini'=>'uld_gemini_api_key','claude'=>'uld_claude_api_key'];
        $models=['openai'=>'uld_ai_model','gemini'=>'uld_gemini_model','claude'=>'uld_claude_model'];
        foreach($map as $p=>$option){ $v=trim((string)wp_unslash($_POST[$p.'_key']??'')); if($v!=='') update_option($option,$v,false); }
        foreach($models as $p=>$option){ $v=sanitize_text_field(wp_unslash($_POST[$p.'_model']??'')); if($v!=='') update_option($option,$v,false); }
        wp_safe_redirect(admin_url('admin.php?page=uld-ai-providers&saved=1')); exit;
    }
    public static function provider(){ $p=(string)get_option(self::PROVIDER,'openai'); return in_array($p,['openai','gemini','claude'],true)?$p:'openai'; }
    public static function key($p=null){ $p=$p?:self::provider(); $opts=['openai'=>'uld_ai_api_key','gemini'=>'uld_gemini_api_key','claude'=>'uld_claude_api_key']; return trim((string)get_option($opts[$p]??$opts['openai'])); }
    public static function model($p=null){ $p=$p?:self::provider(); $defaults=['openai'=>'gpt-5-mini','gemini'=>'gemini-2.5-flash','claude'=>'claude-sonnet-4-5']; $opts=['openai'=>'uld_ai_model','gemini'=>'uld_gemini_model','claude'=>'uld_claude_model']; return trim((string)get_option($opts[$p]??$opts['openai'],$defaults[$p]??$defaults['openai'])); }
    public static function page(){
        $active=self::provider();
        $providers=[
          'openai'=>['OpenAI','General-purpose lesson generation','gpt-5-mini'],
          'gemini'=>['Google Gemini','Google AI lesson generation','gemini-2.5-flash'],
          'claude'=>['Anthropic Claude','Long-form academic lesson generation','claude-sonnet-4-5']
        ];
        echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ AI</span><h1>AI Providers</h1><p>Configure AI once here. Teachers use the Lesson Builder without seeing API-key fields.</p></div><div class="uld-status is-connected"><span class="uld-dot"></span>'.esc_html(strtoupper($active)).'</div></div>';
        if(isset($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>AI provider settings saved.</p></div>';
        echo '<form method="post">'; wp_nonce_field('uld_ai_provider_save'); echo '<div class="uld-v4-provider-grid">';
        foreach($providers as $slug=>$info){ $saved=(bool)self::key($slug); echo '<label class="uld-v4-provider-card"><input type="radio" name="provider" value="'.esc_attr($slug).'" '.checked($active,$slug,false).'><span class="uld-v4-provider-icon dashicons dashicons-superhero"></span><span><strong>'.esc_html($info[0]).'</strong><small>'.esc_html($info[1]).'</small><em>'.($saved?'Connected':'Setup required').'</em></span></label>'; }
        echo '</div><div class="uld-grid">';
        foreach($providers as $slug=>$info){ echo '<section class="uld-v4-panel"><h2>'.esc_html($info[0]).'</h2><div class="uld-v4-field"><label>API Key</label><input type="password" name="'.esc_attr($slug).'_key" placeholder="'.(self::key($slug)?'Key saved — enter only to replace':'Enter API key').'" autocomplete="new-password"></div><div class="uld-v4-field"><label>Model</label><input type="text" name="'.esc_attr($slug).'_model" value="'.esc_attr(self::model($slug)?:$info[2]).'"></div></section>'; }
        echo '</div><p><button class="button button-primary button-hero" name="uld_ai_provider_save" value="1">Save AI Provider</button></p></form></div>';
    }
}
ULD_V4_AI_Providers::init();
