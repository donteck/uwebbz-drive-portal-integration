# UWEBBZ LMS 5 — AI Learning Platform

UWEBBZ LMS 5 is a standalone WordPress learning platform that combines Google Drive, AI-assisted course creation, visual curriculum building, students and enrollments, assessments, progress tracking, certificates, analytics, notifications, and controlled teaching libraries inside one master plugin.

> Current development build: **5.0.0-dev2**
>
> Branch: **`v5-development`**

## What V5 is

V5 does **not** require UWEBBZ Drive LMS V4 to be installed or activated. It has its own `core-v5.php` and loads all required Google Drive, AI, teacher, student, and V5 creation modules internally.

The main WordPress plugin file is:

`uwebbz-drive-lms.php`

WordPress should identify it as:

**UWEBBZ LMS 5 — AI Learning Platform**

## Core capabilities

### Learning-platform dashboard

The V5 dashboard brings the major workflows together:

- Create
- AI Studio
- Content Library
- Students
- Assess
- Communicate
- Analytics
- Settings

### Google Drive

UWEBBZ supports two different Drive concepts at the same time:

**All My Drive** is the teacher's private browser for the connected Google Drive account.

**Teaching Libraries** are specific approved Google Drive folders that can be used for course and student assignment. This prevents the entire personal Drive from becoming assignable learning content.

Teachers can:

- Browse Drive folders and subfolders.
- Preview supported Drive resources.
- Add a folder from All My Drive to Teaching Libraries.
- Add a specific folder using its Google Drive URL or folder ID.
- Assign approved teaching folders to students or courses.
- Build a WordPress course structure from an approved Teaching Library.

### Visual Course Builder

The Visual Course Builder provides a single curriculum workspace for:

- Selecting a course.
- Creating modules.
- Creating lessons inside modules.
- Viewing lesson status.
- Opening lessons for editing.
- Launching AI Course Architect for the selected course.

### AI Lesson Builder

Paste source content and generate a structured WordPress lesson.

The builder can create:

- Lesson overview.
- Learning objectives.
- Instructional sections.
- Examples.
- Key terms.
- Recap.
- Next steps.
- Optional knowledge check.

Lessons can be saved as drafts or published and can be linked to a course. Existing Drive synchronization can be enabled where supported.

### AI Providers

AI credentials are configured once under **AI Providers** rather than displayed in the normal teacher lesson workflow.

Supported provider integrations currently include:

- OpenAI
- Google Gemini
- Anthropic Claude

API keys are stored server-side in WordPress options and should never be committed to GitHub.

### AI Course Architect

Paste a book outline, training manual, curriculum, notes, or other source material and ask UWEBBZ to propose a complete course blueprint.

The current blueprint schema is:

`Course → Modules → Lessons`

The workflow is intentionally review-first:

1. Add source material.
2. Choose audience level.
3. Generate a course blueprint.
4. Review the proposed modules and lessons.
5. Choose a new or existing course.
6. Click **Build Course Structure**.

UWEBBZ does not create the proposed curriculum until the teacher approves the preview.

### Build Course from Teaching Library

UWEBBZ can scan an approved Google Drive Teaching Library and propose a WordPress curriculum structure.

Current mapping:

- Approved Teaching Library → source course library
- Top-level Drive folder → Module
- Files inside that folder → Lessons/resources
- Root-level files → Course Resources module

Imported lessons retain relevant Drive IDs, MIME types, and links.

### Students and enrollment

V5 includes visual student/course selection instead of relying only on long WordPress dropdowns.

Course enrollment is stored in WordPress user metadata. The integrated notification system can email a student when the student is enrolled in a course.

### Student portal

Add the following shortcode to the WordPress page students use after login:

`[student_drive_portal]`

The student portal can surface enrolled-course content, assigned Drive resources, and lesson availability based on the LMS metadata.

### Assessments and learning operations

V5 includes the platform areas for:

- Quizzes
- Assignments
- Gradebook
- Student progress
- Certificates
- Announcements
- Cohorts
- Calendar
- Reports and analytics
- Notifications

Some of these are currently foundational development components and will receive deeper assessment, grading, submission, certificate, and automation engines during later V5 milestones.

## Installation

1. Open the `v5-development` branch.
2. Choose **Code → Download ZIP**.
3. Back up the WordPress site and the current UWEBBZ plugin first.
4. In WordPress, go to **Plugins → Add New → Upload Plugin**.
5. Upload the V5 ZIP.
6. Activate **UWEBBZ LMS 5 — AI Learning Platform**.
7. Confirm the V5 dashboard loads.
8. Confirm Google Drive is connected.
9. Test All My Drive and Teaching Libraries.
10. Test the Visual Course Builder.
11. Configure an AI provider and test AI Lesson Builder.
12. Test AI Course Architect with non-production content first.

V5 is a development build. Keep the previous stable plugin ZIP until testing is complete.

## Google OAuth

The plugin uses the Google Drive API through OAuth 2.0.

The redirect URI is generated by the plugin and must exactly match the Authorized Redirect URI configured in Google Cloud.

For the current site architecture it is expected to follow this form:

`https://YOUR-WORDPRESS-SITE/wp-admin/admin.php?page=uld`

Use the exact URL displayed by the plugin rather than copying an example from this documentation.

Never commit the Google Client Secret, access token, refresh token, or AI API keys to GitHub.

## Security principles

- OAuth state validation is used for Google authorization.
- WordPress nonces protect privileged admin actions.
- Administrative pages require appropriate WordPress capabilities.
- AI API keys are used server-side.
- Teaching Libraries provide a controlled assignment boundary between the teacher's full Drive and student/course content.
- Student/course associations are stored in WordPress metadata.
- Secrets are not intentionally stored in repository files.

## Documentation

Detailed documentation is available in [`docs/`](docs/INDEX.md):

- [Installation](docs/INSTALLATION.md)
- [Google Drive Setup](docs/GOOGLE-DRIVE.md)
- [AI Setup](docs/AI.md)
- [Teacher Guide](docs/TEACHER-GUIDE.md)
- [Student Experience](docs/STUDENT-GUIDE.md)
- [Course Building](docs/COURSE-BUILDING.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Security](docs/SECURITY.md)
- [Troubleshooting](docs/TROUBLESHOOTING.md)
- [Upgrade and Migration](docs/UPGRADE.md)
- [Roadmap](docs/ROADMAP.md)

## Development status

V5 is under active development. The current milestone focuses on making V5 a standalone master plugin and delivering:

- V5 dashboard
- Visual Course Builder
- AI Course Architect
- Build Course from Teaching Library
- Full Drive + controlled Teaching Libraries
- AI Lesson Builder
- Multi-provider AI settings
- Student enrollment foundation
- Progress/reporting foundation

The next major milestone is the deeper learning engine: quizzes, assignments/submissions, real gradebook calculations, prerequisites, completion logic, certificate generation, and Ask My Course AI.

## Repository

Repository: `donteck/uwebbz-drive-portal-integration`

Development branch: `v5-development`

## Author / Product

**UWEBBZ Technology**

UWEBBZ LMS 5 — AI Learning Platform
