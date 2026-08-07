# UWEBBZ Drive Portal Integration

UWEBBZ Drive Portal is a WordPress plugin that connects an educator-managed Google Drive account to WordPress and displays assigned course materials to logged-in students.

## Current features

- Google OAuth 2.0 connection from WordPress admin
- Exact callback URL shown inside the plugin with a Copy button
- Robust OAuth callback handling on `wp-admin/admin.php?page=uld`
- Access-token and refresh-token storage in WordPress options
- Automatic access-token refresh
- Google Drive API integration
- Per-user Google Drive folder assignment
- `[student_drive_portal]` shortcode
- Student-only course-material view
- Admin connection status and disconnect control
- Configurable portal refresh interval
- Responsive UWEBBZ admin UI
- No credentials committed to this repository

## Google Cloud setup

Enable the Google Drive API and create an OAuth 2.0 Web Application client.

Use the exact Authorized Redirect URI displayed inside:

**WordPress Admin → UWEBBZ Drive**

For the current installation this is expected to be:

`https://courses.usoundz.com/wp-admin/admin.php?page=uld`

Do not hard-code or commit the Client Secret.

## Install

1. Download this repository as a ZIP.
2. In WordPress go to **Plugins → Add New → Upload Plugin**.
3. Upload and activate the ZIP.
4. Open **UWEBBZ Drive** in WordPress admin.
5. Enter the Google Client ID and Client Secret.
6. Save settings.
7. Click **Connect Google Drive**.
8. Add `[student_drive_portal]` to the student dashboard page.
9. Edit a WordPress user and assign that student's Google Drive Folder ID.

## Google Drive permission modes

The plugin currently offers two OAuth scope choices:

- `drive.readonly` — compatible with browsing existing Drive folders. This is a Google restricted scope and may require verification before broad public use.
- `drive.file` — narrower access limited to files used with the app. A Google Picker-based workflow is recommended when using this mode because existing arbitrary Drive folders are not automatically visible to the application.

## Security notes

- OAuth state is validated to mitigate CSRF attacks.
- WordPress nonces protect admin actions.
- Only administrators can configure Google credentials or initiate the shared Drive connection.
- Student folder assignments are stored per WordPress user.
- The Client Secret must never be committed to GitHub.

## Roadmap

Planned improvements include an educator-friendly Google Picker, course/folder assignment UI, teacher roles, student groups, scheduled module release, audit logs, improved file previews, and multisite/multi-teacher OAuth support.

## Version

1.0.0

© UWEBBZ Technology
