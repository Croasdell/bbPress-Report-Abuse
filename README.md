```markdown
# bbPress Report Abuse

This plugin provides a "Report Abuse" link in bbPress replies. It integrates with Gravity Forms to pre-populate a "Reported URL" field so users can quickly report problematic content. This branch includes improvements: a small admin settings page (report page URL + moderator emails), i18n, sanitization, and a test/CI setup.

## Installation

1. Upload the plugin to `/wp-content/plugins/` and activate it.
2. Create a WordPress page for your form (default is `/report-abuse`).
3. Create a Gravity Form and add a field to capture the reported URL:
   - Edit the field → Advanced → check "Allow field to be populated dynamically".
   - Set the parameter name to `bbp_report_abuse`.
4. Insert that Gravity Form into the Report Abuse page.

## Settings

Visit Settings → bbPress Report Abuse:
- Report page URL: URL of the page with your Gravity Form (defaults to `/report-abuse`)
- Moderator emails: comma-separated emails to notify (optional)

## Filters

- `bbpress_report_abuse_label` — change the link text (default: "Report Abuse")
- `bbpress_report_abuse_url` — change the link destination programmatically (option takes precedence)

## Development

- Composer: `composer install`
- Run tests: `vendor/bin/phpunit`
- CI: GitHub Actions workflow runs PHP unit tests.

## Notes

- The plugin sanitizes inputs and escapes outputs.
- If you want email notifications on form submission, either use Gravity Forms notifications or hook into GF entry submission to email the configured moderator addresses.

## Changelog

### 1.1.0
- Add settings page for report URL and moderator emails.
- Add i18n, sanitization and escaping.
- Add basic PHPUnit & GitHub Actions CI config.
```
