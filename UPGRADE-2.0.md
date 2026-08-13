# UWEBBZ Drive Portal v2 Upgrade

This repository now includes the v2 teacher workflow in addition to the original Google OAuth connector.

## Activate these WordPress plugins

1. **UWEBBZ Drive Portal** — the existing Google OAuth connector.
2. **UWEBBZ Drive Teacher Console** — adds My Drive, Courses, Lessons, and Assignments.
3. **UWEBBZ Drive Teacher UI** — loads the enhanced teacher dashboard styling.
4. **UWEBBZ Drive Student Portal v2** — upgrades `[student_drive_portal]` with course enrollment and teacher-controlled lesson availability.

## Teacher workflow

Open **WordPress Admin → UWEBBZ Drive → My Drive**.

The teacher can:

- Browse **My Drive**.
- Open folders and subfolders.
- See folders, PDFs, PowerPoints/Google Slides, Docs, spreadsheets, videos, and other files.
- Preview Drive files.
- Assign a Drive item directly to a student.
- Assign a folder to a course.
- Turn a Drive item into a lesson.
- Make a lesson available immediately.
- Schedule a lesson for a future date/time.
- Keep a lesson hidden until the teacher decides to release it.
- Refresh the Drive browser at any time.

## Courses and students

Use **UWEBBZ Drive → Courses** to create courses.

Use **UWEBBZ Drive → Assignments** to enroll a WordPress student in a course. Course lessons then appear in the student's existing `[student_drive_portal]` page when they are available.

## Security

Google Client ID and Client Secret remain in WordPress options and are not committed to GitHub.
