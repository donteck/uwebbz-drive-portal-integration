# Troubleshooting

## Plugin will not activate

1. Confirm you downloaded the `v5-development` branch.
2. Confirm WordPress identifies the plugin as **UWEBBZ LMS 5 — AI Learning Platform**.
3. Check the WordPress/PHP error log for the exact fatal error.
4. Restore the previous stable plugin if the production site is affected.
5. Report the exact error message and PHP version before changing multiple files at once.

## Dashboard is blank

- Confirm `includes/core-v5.php` exists in the uploaded plugin directory.
- Confirm the V5 styles and required include files were included in the ZIP.
- Check browser console and PHP error logs.
- Confirm the logged-in account has administrator capability.

## Google Drive says connected but files do not appear

- Confirm Google Drive API is enabled in the correct Google Cloud project.
- Confirm the OAuth client belongs to the same project.
- Confirm the exact redirect URI matches.
- Reconnect Google if the access token expired and no usable refresh token exists.
- Check whether the selected Google account can actually see the requested folder.

## Drive browsing works but sync/create fails

Browsing may require only read access while creation requires write-capable permission. Reconnect Google with the required Drive permission and retest.

## Teaching Library is empty

Confirm the saved folder ID is correct and the connected Google account can access it. If the folder is shared from another account, verify Google Drive permissions.

## AI Setup Required

Open **AI Providers**, choose a provider, add a valid server-side API key, save, and return to the AI tool.

## AI generation fails

Possible causes include invalid key, unavailable model, provider quota/billing, network timeout, or malformed AI output. Try a smaller source document and verify the provider/model configuration.

## Enrollment email is not received

The plugin uses WordPress mail delivery. Check spam, verify the student's WordPress email address, and confirm the WordPress host can send email. Production sites may need a properly configured SMTP/mail provider.

## Student sees no course content

Verify the student is enrolled, the course contains lessons, lesson availability permits access, and connected Drive resources have appropriate Google sharing permissions.
