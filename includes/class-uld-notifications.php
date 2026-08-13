<?php
if (!defined('ABSPATH')) exit;

final class ULD_Notifications {
    public static function init() {
        // Runs before the teacher console processes the enrollment and redirects.
        add_action('admin_init', [__CLASS__, 'maybe_send_course_enrollment_email'], 5);
    }

    public static function maybe_send_course_enrollment_email() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        if (empty($_POST['uld_tc_enroll'])) return;
        if (empty($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'uld_tc_enroll')) return;

        $student_id = absint($_POST['student_id'] ?? 0);
        $course_id  = absint($_POST['course_id'] ?? 0);
        if (!$student_id || !$course_id || get_post_type($course_id) !== 'uld_course') return;

        $student = get_userdata($student_id);
        if (!$student || !is_email($student->user_email)) return;

        // Avoid notifying twice when the student is already enrolled.
        $existing = array_map('absint', (array) get_user_meta($student_id, 'uld_course_ids', true));
        if (in_array($course_id, $existing, true)) return;

        $course_name = get_the_title($course_id) ?: 'your new course';
        $site_name   = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $portal_url  = apply_filters('uld_student_portal_url', home_url('/'));

        $subject = sprintf('You have been enrolled in %s', $course_name);
        $message = sprintf(
            "Hello %s,\n\nYou have been enrolled in the course: %s.\n\nYour course materials and lessons are now available through your UWEBBZ learning portal.\n\nOpen your learning portal:\n%s\n\nNew lessons may be released immediately or according to your teacher's schedule.\n\n— %s",
            $student->display_name ?: $student->user_login,
            $course_name,
            $portal_url,
            $site_name
        );

        $subject = apply_filters('uld_course_enrollment_email_subject', $subject, $student_id, $course_id);
        $message = apply_filters('uld_course_enrollment_email_message', $message, $student_id, $course_id, $portal_url);

        $sent = wp_mail($student->user_email, $subject, $message, ['Content-Type: text/plain; charset=UTF-8']);

        set_transient(
            'uld_enrollment_email_' . $student_id . '_' . $course_id,
            $sent ? 'sent' : 'failed',
            10 * MINUTE_IN_SECONDS
        );
    }
}
