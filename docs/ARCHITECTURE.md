# Architecture

## Entry point

The master plugin entry is:

`uwebbz-drive-lms.php`

It defines the plugin version and paths, loads the standalone V5 core, then loads bundled Google Drive, AI, enrollment, and V5 creation modules.

## Standalone core

`includes/core-v5.php` is the V5 platform core. It registers shared content types, loads the base internal teaching components, creates V5 admin pages, and wires platform styles.

V5 does not require an independently installed V4 plugin.

## Major internal components

### Drive

- OAuth connector
- All My Drive browser
- Teaching Libraries
- Visual Drive assignment
- Drive navigation controls
- Lesson/Drive sync tools

### Content model

Custom post types include or are planned around:

- `uld_course`
- `uld_module`
- `uld_lesson`
- `uld_quiz`
- `uld_assignment`
- `uld_announcement`
- `uld_certificate`
- `uld_cohort`

Relationships are primarily stored through WordPress post metadata.

### Students

Student enrollment and completion data currently use WordPress user metadata, including enrolled course IDs and completed lesson IDs.

### AI

AI provider settings are centralized. AI requests are made server-side through WordPress HTTP functions. AI Lesson Builder and AI Course Architect consume the selected provider configuration.

### V5 creation modules

- `class-uld-v5-course-builder.php`
- `class-uld-v5-ai-course-architect.php`
- `class-uld-v5-library-course-importer.php`

## Front-end delivery

The student portal is exposed through the shortcode:

`[student_drive_portal]`

## Design principle

V5 uses one WordPress plugin entry and multiple internal modules. Internal PHP files should not contain independent WordPress plugin headers intended to appear as separate plugins.

## Compatibility

V5 intentionally reuses existing WordPress option/meta keys where practical so existing Drive connections, course data, and assignments can survive migration from earlier UWEBBZ builds.
