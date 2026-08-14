<?php
if (!defined('ABSPATH')) exit;

final class ULD_V4_AI_Lesson_Builder {
    const KEY_OPTION='uld_ai_api_key';
    const MODEL_OPTION='uld_ai_model';

    public static function init(){
        add_action('admin_menu',[__CLASS__,'menu'],110);
        add_action('admin_init',[__CLASS__,'actions'],8);
    }

    public static function menu(){
        add_submenu_page('uld','AI Lesson Builder','AI Lesson Builder','manage_options','uld-ai-lesson-builder',[__CLASS__,'page']);
    }

    private static function api_key(){
        if(defined('ULD_OPENAI_API_KEY') && ULD_OPENAI_API_KEY) return (string)ULD_OPENAI_API_KEY;
        return trim((string)get_option(self::KEY_OPTION));
    }

    private static function model(){
        return trim((string)get_option(self::MODEL_OPTION,'gpt-5-mini')) ?: 'gpt-5-mini';
    }

    public static function actions(){
        if(!is_admin()||!current_user_can('manage_options')) return;

        if(isset($_POST['uld_ai_save_settings'])){
            check_admin_referer('uld_ai_save_settings');
            $key=trim((string)wp_unslash($_POST['ai_api_key']??''));
            if($key!=='') update_option(self::KEY_OPTION,$key,false);
            update_option(self::MODEL_OPTION,sanitize_text_field(wp_unslash($_POST['ai_model']??'gpt-5-mini')),false);
            wp_safe_redirect(admin_url('admin.php?page=uld-ai-lesson-builder&uld_notice=settings_saved')); exit;
        }

        if(isset($_POST['uld_ai_generate_lesson'])){
            check_admin_referer('uld_ai_generate_lesson');
            $source=wp_kses_post(wp_unslash($_POST['source_content']??''));
            if(trim(wp_strip_all_tags($source))===''){
                wp_safe_redirect(admin_url('admin.php?page=uld-ai-lesson-builder&uld_error=missing_content')); exit;
            }
            $result=self::generate($source, [
                'title'=>sanitize_text_field(wp_unslash($_POST['lesson_title']??'')),
                'level'=>sanitize_text_field(wp_unslash($_POST['audience_level']??'Intermediate')),
                'length'=>sanitize_text_field(wp_unslash($_POST['lesson_length']??'Standard')),
                'tone'=>sanitize_text_field(wp_unslash($_POST['teaching_tone']??'Academic and practical')),
                'quiz'=>!empty($_POST['include_quiz']),
            ]);
            if(is_wp_error($result)){
                set_transient('uld_ai_error_'.get_current_user_id(),$result->get_error_message(),10*MINUTE_IN_SECONDS);
                wp_safe_redirect(admin_url('admin.php?page=uld-ai-lesson-builder&uld_error=generation_failed')); exit;
            }
            $title=sanitize_text_field($result['title']??($_POST['lesson_title']??'AI Lesson'));
            $status=(sanitize_key($_POST['save_mode']??'draft')==='publish')?'publish':'draft';
            $post_id=wp_insert_post([
                'post_type'=>'uld_lesson','post_status'=>$status,'post_title'=>$title,
                'post_content'=>wp_kses_post($result['html']??'')
            ]);
            if(is_wp_error($post_id)||!$post_id){
                wp_safe_redirect(admin_url('admin.php?page=uld-ai-lesson-builder&uld_error=save_failed')); exit;
            }
            $course_id=absint($_POST['course_id']??0);
            if($course_id && get_post_type($course_id)==='uld_course') update_post_meta($post_id,'uld_course_id',$course_id);
            update_post_meta($post_id,'uld_ai_generated',1);
            update_post_meta($post_id,'uld_ai_source_excerpt',wp_trim_words(wp_strip_all_tags($source),80));
            if(!empty($_POST['auto_sync_drive'])) update_post_meta($post_id,'uld_auto_sync',1);
            wp_safe_redirect(add_query_arg(['post'=>$post_id,'action'=>'edit','uld_notice'=>'ai_created'],admin_url('post.php'))); exit;
        }
    }

    private static function generate($source,$opts){
        $key=self::api_key();
        if(!$key) return new WP_Error('no_api_key','Add your AI API key in AI Lesson Builder settings first.');

        $quiz=$opts['quiz']?'Include a 5-question knowledge check with answers at the end.':'Do not include a quiz.';
        $prompt="You are the instructional-design engine inside UWEBBZ Drive LMS. Convert the supplied teacher content into a polished academic lesson for real students.\n\nREQUIREMENTS:\n- Audience level: {$opts['level']}\n- Length: {$opts['length']}\n- Teaching style: {$opts['tone']}\n- Start with a concise lesson overview.\n- Include 3-6 measurable learning objectives.\n- Organize material into clear instructional sections with headings.\n- Explain concepts thoroughly; do not merely summarize the source.\n- Add practical examples, teacher notes where helpful, key terms, a recap, and next-step guidance.\n- {$quiz}\n- Preserve factual meaning from the source and do not invent unsupported facts.\n- Return ONLY valid JSON with keys: title and html. The html value must contain clean WordPress-safe lesson HTML using h2, h3, p, ul, ol, li, strong, em, blockquote, and table when useful.\n\nPreferred title: {$opts['title']}\n\nSOURCE CONTENT:\n".wp_strip_all_tags($source);

        $response=wp_remote_post('https://api.openai.com/v1/responses',[
            'timeout'=>90,
            'headers'=>['Authorization'=>'Bearer '.$key,'Content-Type'=>'application/json'],
            'body'=>wp_json_encode(['model'=>self::model(),'input'=>$prompt])
        ]);
        if(is_wp_error($response)) return $response;
        $code=wp_remote_retrieve_response_code($response);
        $body=json_decode(wp_remote_retrieve_body($response),true);
        if($code<200||$code>=300){
            return new WP_Error('ai_api',$body['error']['message']??'AI request failed.');
        }
        $text='';
        foreach((array)($body['output']??[]) as $item){
            foreach((array)($item['content']??[]) as $part){
                if(isset($part['text']) && is_string($part['text'])) $text.=$part['text'];
            }
        }
        $text=trim($text);
        if(str_starts_with($text,'```')) $text=preg_replace('/^```(?:json)?\s*|\s*```$/','',$text);
        $data=json_decode($text,true);
        if(!is_array($data)||empty($data['html'])) return new WP_Error('bad_ai_output','The AI response could not be converted into a lesson. Try again.');
        return $data;
    }

    public static function page(){
        $has_key=(bool)self::api_key();
        $courses=get_posts(['post_type'=>'uld_course','post_status'=>['publish','draft'],'numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
        echo '<div class="wrap uld-wrap"><div class="uld-hero"><div><span class="uld-kicker">UWEBBZ AI STUDIO</span><h1>AI Lesson Builder</h1><p>Paste notes, an outline, article text, training material, or raw teaching content and transform it into a structured LMS lesson.</p></div><div class="uld-status '.($has_key?'is-connected':'is-offline').'"><span class="uld-dot"></span>'.($has_key?'AI Ready':'AI Setup Required').'</div></div>';
        if(isset($_GET['uld_error'])){ $detail=get_transient('uld_ai_error_'.get_current_user_id()); echo '<div class="notice notice-error"><p>'.esc_html($detail?:str_replace('_',' ',sanitize_key($_GET['uld_error']))).'</p></div>'; }
        echo '<div class="uld-ai-layout"><section class="uld-v4-panel uld-ai-main"><div class="uld-v4-section-head"><div><span class="uld-v4-stepnum">1</span><h2>Add Your Content</h2></div><p>The AI will turn this source into a teachable lesson.</p></div><form method="post">'; wp_nonce_field('uld_ai_generate_lesson');
        echo '<textarea class="uld-ai-source" name="source_content" rows="18" placeholder="Paste your chapter, notes, outline, SOP, technical material, lecture notes, or other content here..." required></textarea>';
        echo '<div class="uld-ai-options"><div class="uld-v4-field"><label>Preferred lesson title</label><input type="text" name="lesson_title" placeholder="AI can create one automatically"></div><div class="uld-v4-field"><label>Audience level</label><div class="uld-v4-tabs"><label><input type="radio" name="audience_level" value="Beginner"><span>Beginner</span></label><label><input type="radio" name="audience_level" value="Intermediate" checked><span>Intermediate</span></label><label><input type="radio" name="audience_level" value="Advanced"><span>Advanced</span></label></div></div><div class="uld-v4-field"><label>Lesson depth</label><div class="uld-v4-tabs"><label><input type="radio" name="lesson_length" value="Concise"><span>Concise</span></label><label><input type="radio" name="lesson_length" value="Standard" checked><span>Standard</span></label><label><input type="radio" name="lesson_length" value="Deep academic"><span>Deep</span></label></div></div><div class="uld-v4-field"><label>Teaching style</label><input type="text" name="teaching_tone" value="Academic, practical, clear, and instructor-led"></div></div>';
        echo '<div class="uld-v4-panel uld-ai-destination"><h3>2. Choose Destination</h3><div class="uld-v4-course-grid">';
        echo '<label class="uld-v4-course-card"><input type="radio" name="course_id" value="0" checked><span class="dashicons dashicons-media-document"></span><span><strong>Unassigned Lesson</strong><small>Add to a course later</small></span></label>';
        foreach($courses as $c) echo '<label class="uld-v4-course-card"><input type="radio" name="course_id" value="'.absint($c->ID).'"><span class="dashicons dashicons-welcome-learn-more"></span><span><strong>'.esc_html($c->post_title).'</strong><small>Course</small></span></label>';
        echo '</div><div class="uld-ai-switches"><label><input type="checkbox" name="include_quiz" value="1" checked> Include knowledge check</label><label><input type="checkbox" name="auto_sync_drive" value="1"> Auto-sync lesson to Google Drive when supported</label></div><div class="uld-v4-tabs"><label><input type="radio" name="save_mode" value="draft" checked><span>Save Draft</span></label><label><input type="radio" name="save_mode" value="publish"><span>Publish</span></label></div><p><button class="button button-primary button-hero" name="uld_ai_generate_lesson" value="1">Generate AI Lesson</button></p></div></form></section>';
        echo '<aside class="uld-v4-panel uld-ai-settings"><h2>AI Connection</h2><p>Your API key is used only by the WordPress server when generating lessons.</p><form method="post">'; wp_nonce_field('uld_ai_save_settings'); echo '<div class="uld-v4-field"><label>API Key</label><input type="password" name="ai_api_key" value="" placeholder="'.($has_key?'Key saved — enter only to replace':'Enter API key').'" autocomplete="new-password"></div><div class="uld-v4-field"><label>Model</label><input type="text" name="ai_model" value="'.esc_attr(self::model()).'"></div><button class="button" name="uld_ai_save_settings" value="1">Save AI Settings</button></form><hr><h3>What the builder creates</h3><div class="uld-ai-feature-list"><span>Lesson overview</span><span>Learning objectives</span><span>Structured sections</span><span>Examples</span><span>Key terms</span><span>Recap</span><span>Knowledge check</span><span>WordPress lesson</span></div></aside></div></div>';
    }
}

ULD_V4_AI_Lesson_Builder::init();
