# Upgrade and Migration

## Goal

V5 is designed to become the standalone successor to earlier UWEBBZ Drive LMS builds while preserving existing WordPress data where practical.

## Before upgrading

1. Back up the WordPress database.
2. Back up the current UWEBBZ plugin directory or ZIP.
3. Record the current Google OAuth configuration.
4. Record the student portal page and shortcode location.
5. Test V5 on staging when possible.

## Existing data

V5 intentionally continues using many existing option and metadata keys for:

- Google OAuth credentials/tokens.
- Course and lesson relationships.
- Drive folder assignments.
- Student enrollments.
- Lesson completion data.
- Teaching Libraries.
- AI provider settings.

This is intended to reduce unnecessary migration work.

## Recommended replacement process

Do not delete the last known-good plugin first.

1. Back up.
2. Deactivate the previous UWEBBZ plugin.
3. Install V5.
4. Activate V5.
5. Confirm dashboard and data.
6. Confirm Google connection.
7. Confirm Teaching Libraries.
8. Confirm AI settings.
9. Test a student enrollment and email.
10. Test the student portal.
11. Only remove the old plugin after successful validation.

## Rollback

If V5 fails on production:

1. Deactivate V5 if possible.
2. Restore the previous plugin ZIP.
3. Reactivate the previous version.
4. Recheck OAuth and course data.
5. Use the PHP error log to diagnose the V5 issue before retrying.

## Development branch

`v5-development` is not yet the production release branch. Treat it as a testing line until a stable V5 release is explicitly tagged or promoted.
