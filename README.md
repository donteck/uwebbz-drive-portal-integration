# UWEBBZ Drive LMS

UWEBBZ Drive LMS is a unified WordPress teaching plugin that connects an educator-managed Google Drive account to WordPress and combines Drive browsing, courses, lessons, student assignments, scheduled releases, student delivery, and WordPress-to-Drive lesson synchronization.

## Unified plugin

Activate **UWEBBZ Drive LMS** (`uwebbz-drive-lms.php`). It loads the existing OAuth connector and the teacher/student modules internally, so the older helper plugins do not need to be activated separately.

## Main teacher workspace

Open **WordPress Admin → UWEBBZ Drive → Teacher Home**.

The teacher can:

- Browse **My Drive → folders → subfolders**.
- View PDFs, PowerPoints/Google Slides, Docs, spreadsheets, videos, and other files.
- Preview Drive files.
- Assign Drive items directly to students.
- Assign folders to courses.
- Create courses and lessons in WordPress.
- Turn a Drive file into a WordPress lesson.
- Make lessons available immediately.
- Schedule a lesson for a future date/time.
- Hide a lesson until the teacher manually releases it.
- Enroll WordPress users in courses.
- Duplicate an existing lesson as a new draft.
- Create a Google Drive folder automatically for a WordPress course.
- Create/update a matching Drive document when a WordPress lesson is saved with Auto-sync enabled.

## Automatic lesson sync

On a WordPress lesson, use the **Google Drive Sync** panel.

1. Choose the lesson's course.
2. Make sure that course has a Google Drive folder assigned, or enter a folder override.
3. Keep **Auto-sync** enabled.
4. Save the lesson.
5. UWEBBZ creates the lesson in Google Drive and stores the Drive file ID/link on the WordPress lesson.
6. Later WordPress edits update the same Drive item instead of intentionally creating a new lesson each time.

### Google permission needed for sync

The existing Drive browser uses read access. Creating course folders and lesson files also requires Google Drive file-write permission. In **UWEBBZ Drive settings**, choose **Only files used with this app (`drive.file`)** and reconnect Google. The OAuth request uses incremental authorization, so previously granted Drive access can remain available when Google returns the new token.

If the plugin reports a sync permission error, reconnect Google and approve the requested file-write permission.

## Student portal

Add this shortcode to the student dashboard page:

`[student_drive_portal]`

Students can see:

- Directly assigned folders.
- Courses in which they are enrolled.
- Only lessons currently released by the teacher.
- Scheduled lessons after their release time.
- Drive-based course materials attached to those lessons/courses.

## Google Cloud setup

Enable the Google Drive API and create an OAuth 2.0 Web Application client.

Use the exact Authorized Redirect URI displayed inside **WordPress Admin → UWEBBZ Drive**. For the current installation this is expected to be:

`https://courses.usoundz.com/wp-admin/admin.php?page=uld`

Do not hard-code or commit the Client Secret.

## Security

- OAuth state is validated to mitigate CSRF attacks.
- WordPress nonces protect teacher/admin actions.
- Google credentials remain stored in WordPress options and are not committed to GitHub.
- Student folder/course assignments are stored in WordPress user metadata.
- Teacher lesson availability is enforced before lessons appear in the student portal.

## Version

3.1.0

© UWEBBZ Technology
