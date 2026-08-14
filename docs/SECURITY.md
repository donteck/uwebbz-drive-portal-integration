# Security

## Secrets

Never commit the following to GitHub:

- Google OAuth Client Secret
- Google access tokens
- Google refresh tokens
- OpenAI API keys
- Gemini API keys
- Claude API keys
- WordPress salts, passwords, or database credentials

Secrets should remain in WordPress/server configuration.

## Authorization

Administrative workflows should require appropriate WordPress capabilities. Current privileged screens are generally limited to administrators through `manage_options`.

## Request protection

Administrative write actions use WordPress nonces. Google OAuth uses state validation.

## Google Drive boundaries

A teacher may browse All My Drive privately, but assignment should happen through approved Teaching Libraries, enrolled courses, or explicit assignment metadata. Students should not receive a general browser for the teacher's entire Drive.

## AI security

AI calls are executed server-side so API keys are not intentionally exposed in browser JavaScript. Teachers should never paste private API keys into lesson content.

## Content review

AI-generated curriculum and lessons must be reviewed by the instructor before publication, especially for technical, medical, legal, financial, or other high-stakes subject matter.

## File links

Google Drive links may still be subject to Google sharing permissions. The LMS assignment itself does not override Google Drive access-control rules.

## Production recommendations

- Use HTTPS.
- Keep WordPress, PHP, and dependencies updated.
- Restrict administrator accounts.
- Use backups before plugin updates.
- Test OAuth and student permissions after migrations.
- Do not log secrets in debug output.
