# Installation

## Requirements

- A working WordPress installation.
- Administrator access to WordPress.
- HTTPS strongly recommended and required for the configured Google OAuth redirect flow.
- A Google Cloud project with Google Drive API enabled if Drive features will be used.
- An API key for at least one supported AI provider if AI features will be used.

## Install V5

1. Back up WordPress files and database.
2. Keep a copy of the last working UWEBBZ plugin ZIP.
3. Download the `v5-development` branch as a ZIP from GitHub.
4. In WordPress go to **Plugins → Add New → Upload Plugin**.
5. Upload the ZIP.
6. Activate **UWEBBZ LMS 5 — AI Learning Platform**.
7. Open the UWEBBZ LMS menu and confirm the **V5 Dashboard** loads.

## First-run checklist

After activation verify:

- The plugin reports a V5 version.
- The dashboard loads without a PHP fatal error.
- Courses and lessons remain visible if upgrading an existing installation.
- Google Drive credentials remain configured or can be reconnected.
- All My Drive loads.
- Teaching Libraries loads.
- AI Providers loads.
- AI Lesson Builder loads.
- V5 Course Builder loads.
- AI Course Architect loads.
- Build from Teaching Library loads.

## Development-build warning

The current branch is under development. Test on a staging or backed-up site before relying on it for production teaching.
