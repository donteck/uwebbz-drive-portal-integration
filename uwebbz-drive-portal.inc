<?php
/**
 * Plugin Name: UWEBBZ Drive Portal
 * Plugin URI: https://github.com/donteck/uwebbz-drive-portal-integration
 * Description: Connect WordPress educators to Google Drive and securely surface course materials to logged-in students.
 * Version: 1.0.0
 * Author: UWEBBZ Technology
 * Text Domain: uwebbz-drive-portal
 */

if (!defined('ABSPATH')) {
    exit;
}

final class UWEBBZ_Drive_Portal {
    const OPTION_CLIENT_ID     = 'uld_google_client_id';
    const OPTION_CLIENT_SECRET = 'uld_google_client_secret';
    const OPTION_ACCESS_TOKEN  = 'uld_google_access_token';
    const OPTION_REFRESH_TOKEN = 'uld_google_refresh_token';
    const OPTION_TOKEN_EXPIRES = 'uld_google_token_expires';
    const OPTION_SCOPE         = 'uld_google_scope';
    const OPTION_REFRESH_SECS  = 'uld_portal_refresh_seconds';
    const NONCE_ACTION         = 'uld_google_oauth';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'handle_admin_actions']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_shortcode('student_drive_portal', [__CLASS__, 'student_portal_shortcode']);
        add_action('show_user_profile', [__CLASS__, 'user_profile_fields']);
        add_action('edit_user_profile', [__CLASS__, 'user_profile_fields']);
        add_action('personal_options_update', [__CLASS__, 'save_user_profile_fields']);
        add_action('edit_user_profile_update', [__CLASS__, 'save_user_profile_fields']);
    }

    public static function admin_menu() {
        add_menu_page(
            'UWEBBZ Drive',
            'UWEBBZ Drive',
            'manage_options',
            'uld',
            [__CLASS__, 'settings_page'],
            'dashicons-google',
            58
        );
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_uld') {
            return;
        }
        wp_enqueue_style(
            'uld-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            [],
            '1.0.0'
        );
    }

    public static function redirect_uri() {
        // Keep this stable. It must exactly match the URI registered in Google Cloud.
        // Force https explicitly: Google rejects non-https redirect URIs, and the
        // 'admin' scheme falls back to is_ssl(), which misreports http behind
        // reverse proxies/load balancers that don't forward HTTPS detection.
        return admin_url('admin.php?page=uld', 'https');
    }

    private static function configured_scope() {
        $scope = get_option(self::OPTION_SCOPE, 'https://www.googleapis.com/auth/drive.readonly');
        $allowed = [
            'https://www.googleapis.com/auth/drive.readonly',
            'https://www.googleapis.com/auth/drive.file',
        ];
        return in_array($scope, $allowed, true) ? $scope : $allowed[0];
    }

    public static function handle_admin_actions() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['uld_save_settings'])) {
            check_admin_referer('uld_save_settings');
            update_option(self::OPTION_CLIENT_ID, sanitize_text_field(wp_unslash($_POST['uld_client_id'] ?? '')));
            update_option(self::OPTION_CLIENT_SECRET, sanitize_text_field(wp_unslash($_POST['uld_client_secret'] ?? '')));
            update_option(self::OPTION_REFRESH_SECS, max(10, absint($_POST['uld_refresh_seconds'] ?? 30)));
            $scope = sanitize_text_field(wp_unslash($_POST['uld_scope'] ?? ''));
            if (in_array($scope, [
                'https://www.googleapis.com/auth/drive.readonly',
                'https://www.googleapis.com/auth/drive.file'
            ], true)) {
                update_option(self::OPTION_SCOPE, $scope);
            }
            wp_safe_redirect(add_query_arg(['page' => 'uld', 'uld_notice' => 'saved'], admin_url('admin.php')));
            exit;
        }

        if (isset($_GET['uld_connect_google'])) {
            check_admin_referer(self::NONCE_ACTION);
            self::start_oauth();
        }

        // Google returns ?page=uld&code=...&state=... to the SAME page.
        // Do not require a custom callback query flag; that caused previous integrations to miss the callback.
        if (isset($_GET['page']) && $_GET['page'] === 'uld' && isset($_GET['code'])) {
            self::finish_oauth();
        }

        // If the user cancels consent (or Google errors out), it redirects back with
        // ?error=... and no code. Without this, the settings page silently reloads as
        // "not connected" with no explanation of what happened.
        if (isset($_GET['page']) && $_GET['page'] === 'uld' && isset($_GET['error'])) {
            self::redirect_oauth_error('google_denied', sanitize_text_field(wp_unslash($_GET['error'])));
        }

        if (isset($_GET['uld_disconnect_google'])) {
            check_admin_referer('uld_disconnect_google');
            delete_option(self::OPTION_ACCESS_TOKEN);
            delete_option(self::OPTION_REFRESH_TOKEN);
            delete_option(self::OPTION_TOKEN_EXPIRES);
            wp_safe_redirect(add_query_arg(['page' => 'uld', 'uld_notice' => 'disconnected'], admin_url('admin.php')));
            exit;
        }
    }

    private static function start_oauth() {
        $client_id = trim((string) get_option(self::OPTION_CLIENT_ID));
        if (!$client_id) {
            wp_safe_redirect(add_query_arg(['page' => 'uld', 'uld_error' => 'missing_client_id'], admin_url('admin.php')));
            exit;
        }

        $state = wp_generate_password(32, false, false);
        set_transient('uld_oauth_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS);

        $params = [
            'client_id' => $client_id,
            'redirect_uri' => self::redirect_uri(),
            'response_type' => 'code',
            'scope' => self::configured_scope(),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ];

        wp_redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        exit;
    }

    private static function finish_oauth() {
        $expected_state = get_transient('uld_oauth_state_' . get_current_user_id());
        $returned_state = sanitize_text_field(wp_unslash($_GET['state'] ?? ''));

        if (!$expected_state || !$returned_state || !hash_equals($expected_state, $returned_state)) {
            wp_safe_redirect(add_query_arg(['page' => 'uld', 'uld_error' => 'invalid_state'], admin_url('admin.php')));
            exit;
        }
        delete_transient('uld_oauth_state_' . get_current_user_id());

        $client_id = trim((string) get_option(self::OPTION_CLIENT_ID));
        $client_secret = trim((string) get_option(self::OPTION_CLIENT_SECRET));
        $code = sanitize_text_field(wp_unslash($_GET['code']));

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 20,
            'body' => [
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => self::redirect_uri(),
                'grant_type' => 'authorization_code',
            ],
        ]);

        if (is_wp_error($response)) {
            self::redirect_oauth_error('token_request_failed', $response->get_error_message());
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300 || empty($body['access_token'])) {
            $detail = !empty($body['error_description']) ? $body['error_description'] : (!empty($body['error']) ? $body['error'] : 'Unknown token exchange error');
            self::redirect_oauth_error('token_exchange_failed', $detail);
        }

        update_option(self::OPTION_ACCESS_TOKEN, sanitize_text_field($body['access_token']), false);
        if (!empty($body['refresh_token'])) {
            update_option(self::OPTION_REFRESH_TOKEN, sanitize_text_field($body['refresh_token']), false);
        }
        update_option(self::OPTION_TOKEN_EXPIRES, time() + max(60, absint($body['expires_in'] ?? 3600) - 60), false);

        wp_safe_redirect(add_query_arg(['page' => 'uld', 'uld_notice' => 'connected'], admin_url('admin.php')));
        exit;
    }

    private static function redirect_oauth_error($code, $detail = '') {
        set_transient('uld_last_oauth_error_' . get_current_user_id(), sanitize_text_field($detail), 10 * MINUTE_IN_SECONDS);
        wp_safe_redirect(add_query_arg(['page' => 'uld', 'uld_error' => $code], admin_url('admin.php')));
        exit;
    }

    private static function access_token() {
        $token = (string) get_option(self::OPTION_ACCESS_TOKEN);
        $expires = absint(get_option(self::OPTION_TOKEN_EXPIRES));
        if ($token && $expires > time()) {
            return $token;
        }

        $refresh = (string) get_option(self::OPTION_REFRESH_TOKEN);
        $client_id = (string) get_option(self::OPTION_CLIENT_ID);
        $client_secret = (string) get_option(self::OPTION_CLIENT_SECRET);
        if (!$refresh || !$client_id || !$client_secret) {
            return '';
        }

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 20,
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh,
                'grant_type' => 'refresh_token',
            ],
        ]);

        if (is_wp_error($response)) {
            return '';
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) {
            return '';
        }

        update_option(self::OPTION_ACCESS_TOKEN, sanitize_text_field($body['access_token']), false);
        update_option(self::OPTION_TOKEN_EXPIRES, time() + max(60, absint($body['expires_in'] ?? 3600) - 60), false);
        return (string) $body['access_token'];
    }

    private static function drive_get($path, array $query = []) {
        $token = self::access_token();
        if (!$token) {
            return new WP_Error('not_connected', 'Google Drive is not connected.');
        }
        $url = 'https://www.googleapis.com/drive/v3/' . ltrim($path, '/');
        if ($query) {
            $url = add_query_arg($query, $url);
        }
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        if (is_wp_error($response)) {
            return $response;
        }
        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300) {
            return new WP_Error('drive_api_error', $body['error']['message'] ?? 'Google Drive API request failed.');
        }
        return $body;
    }

    public static function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $client_id = (string) get_option(self::OPTION_CLIENT_ID);
        $client_secret = (string) get_option(self::OPTION_CLIENT_SECRET);
        $refresh = absint(get_option(self::OPTION_REFRESH_SECS, 30));
        $connected = (bool) get_option(self::OPTION_REFRESH_TOKEN) || (bool) get_option(self::OPTION_ACCESS_TOKEN);
        $scope = self::configured_scope();
        ?>
        <div class="wrap uld-wrap">
            <div class="uld-hero">
                <div>
                    <span class="uld-kicker">UWEBBZ TECHNOLOGY</span>
                    <h1>Drive Portal</h1>
                    <p>Connect Google Drive once, then organize and deliver learning materials inside WordPress.</p>
                </div>
                <div class="uld-status <?php echo $connected ? 'is-connected' : 'is-offline'; ?>">
                    <span class="uld-dot"></span><?php echo $connected ? 'Google Drive connected' : 'Google Drive not connected'; ?>
                </div>
            </div>

            <?php if (isset($_GET['uld_notice'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php
                    $notice = sanitize_key($_GET['uld_notice']);
                    echo esc_html($notice === 'connected' ? 'Google Drive connected successfully.' : ($notice === 'disconnected' ? 'Google Drive disconnected.' : 'Settings saved.'));
                ?></p></div>
            <?php endif; ?>

            <?php if (isset($_GET['uld_error'])) : ?>
                <?php $detail = get_transient('uld_last_oauth_error_' . get_current_user_id()); ?>
                <div class="notice notice-error"><p><strong>Google connection error:</strong> <?php echo esc_html(sanitize_key($_GET['uld_error'])); ?><?php echo $detail ? ' — ' . esc_html($detail) : ''; ?></p></div>
            <?php endif; ?>

            <div class="uld-grid">
                <section class="uld-card">
                    <div class="uld-step">1</div>
                    <h2>Google Cloud credentials</h2>
                    <p class="description">These credentials stay in WordPress. Never commit your Client Secret to GitHub.</p>
                    <form method="post">
                        <?php wp_nonce_field('uld_save_settings'); ?>
                        <label>Google Client ID</label>
                        <input class="regular-text" type="text" name="uld_client_id" value="<?php echo esc_attr($client_id); ?>" autocomplete="off">
                        <label>Google Client Secret</label>
                        <input class="regular-text" type="password" name="uld_client_secret" value="<?php echo esc_attr($client_secret); ?>" autocomplete="new-password">

                        <label>Drive permission</label>
                        <select name="uld_scope">
                            <option value="https://www.googleapis.com/auth/drive.readonly" <?php selected($scope, 'https://www.googleapis.com/auth/drive.readonly'); ?>>Read-only access to Drive</option>
                            <option value="https://www.googleapis.com/auth/drive.file" <?php selected($scope, 'https://www.googleapis.com/auth/drive.file'); ?>>Only files used with this app</option>
                        </select>
                        <p class="description">For the current folder-browser workflow, read-only access is the most compatible option. A future Google Picker workflow can use drive.file.</p>

                        <label>Student portal refresh (seconds)</label>
                        <input type="number" min="10" step="1" name="uld_refresh_seconds" value="<?php echo esc_attr($refresh); ?>">

                        <div class="uld-redirect">
                            <strong>Authorized redirect URI</strong>
                            <code><?php echo esc_html(self::redirect_uri()); ?></code>
                            <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js(self::redirect_uri()); ?>')">Copy URI</button>
                        </div>

                        <p><button class="button button-primary button-large" name="uld_save_settings" value="1">Save settings</button></p>
                    </form>
                </section>

                <section class="uld-card">
                    <div class="uld-step">2</div>
                    <h2>Connect Google Drive</h2>
                    <?php if (!$client_id || !$client_secret) : ?>
                        <p>Save your Google Client ID and Client Secret first.</p>
                    <?php elseif ($connected) : ?>
                        <div class="uld-connected-box"><strong>Connected</strong><br>OAuth tokens are stored in WordPress and refresh automatically.</div>
                        <p><a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'uld', 'uld_disconnect_google' => 1], admin_url('admin.php')), 'uld_disconnect_google')); ?>">Disconnect Google Drive</a></p>
                    <?php else : ?>
                        <p>Sign in to Google and approve access. Google will return to this exact settings page and the plugin will exchange the authorization code automatically.</p>
                        <p><a class="button button-primary button-hero" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'uld', 'uld_connect_google' => 1], admin_url('admin.php')), self::NONCE_ACTION)); ?>">Connect Google Drive</a></p>
                    <?php endif; ?>
                </section>

                <section class="uld-card">
                    <div class="uld-step">3</div>
                    <h2>Student portal</h2>
                    <p>Add this shortcode to the page students see after login:</p>
                    <code>[student_drive_portal]</code>
                    <p>Then assign a Google Drive folder ID to each WordPress user from their profile.</p>
                </section>
            </div>
        </div>
        <?php
    }

    public static function user_profile_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        $folder = get_user_meta($user->ID, 'uld_drive_folder_id', true);
        ?>
        <h2>UWEBBZ Drive Portal</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="uld_drive_folder_id">Assigned Google Drive Folder ID</label></th>
                <td>
                    <input type="text" name="uld_drive_folder_id" id="uld_drive_folder_id" class="regular-text" value="<?php echo esc_attr($folder); ?>">
                    <p class="description">Students can only browse the folder assigned to their WordPress account.</p>
                </td>
            </tr>
        </table>
        <?php wp_nonce_field('uld_user_folder_' . $user->ID, 'uld_user_folder_nonce');
    }

    public static function save_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        if (!isset($_POST['uld_user_folder_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['uld_user_folder_nonce'])), 'uld_user_folder_' . $user_id)) {
            return;
        }
        update_user_meta($user_id, 'uld_drive_folder_id', sanitize_text_field(wp_unslash($_POST['uld_drive_folder_id'] ?? '')));
    }

    public static function student_portal_shortcode() {
        if (!is_user_logged_in()) {
            return '<div class="uld-portal-message">Please log in to view your course materials.</div>';
        }

        $folder_id = get_user_meta(get_current_user_id(), 'uld_drive_folder_id', true);
        if (!$folder_id) {
            return '<div class="uld-portal-message">No Google Drive course folder has been assigned to your account yet.</div>';
        }

        $result = self::drive_get('files', [
            'q' => sprintf("'%s' in parents and trashed = false", str_replace("'", "\\'", $folder_id)),
            'fields' => 'files(id,name,mimeType,webViewLink,iconLink,modifiedTime)',
            'orderBy' => 'folder,name',
            'pageSize' => 100,
        ]);
        if (is_wp_error($result)) {
            return '<div class="uld-portal-message">Unable to load course materials: ' . esc_html($result->get_error_message()) . '</div>';
        }

        $files = $result['files'] ?? [];
        if (!$files) {
            return '<div class="uld-portal-message">Your course folder is currently empty.</div>';
        }

        ob_start(); ?>
        <div class="uld-student-portal" data-refresh="<?php echo esc_attr(absint(get_option(self::OPTION_REFRESH_SECS, 30))); ?>">
            <h3>My Course Materials</h3>
            <div class="uld-file-list">
                <?php foreach ($files as $file) : ?>
                    <a class="uld-file" href="<?php echo esc_url($file['webViewLink'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">
                        <span class="dashicons <?php echo ($file['mimeType'] ?? '') === 'application/vnd.google-apps.folder' ? 'dashicons-category' : 'dashicons-media-document'; ?>"></span>
                        <span><?php echo esc_html($file['name'] ?? 'Untitled'); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

UWEBBZ_Drive_Portal::init();
