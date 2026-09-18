# bbPress Report Abuse

A lightweight WordPress plugin that adds a **Report Abuse** link to bbPress
replies and passes the reported post URL to a Gravity Forms report form.

The plugin provides the reporting route; it does not moderate content, submit
reports automatically, or remove posts. A visitor must complete the configured
form, and site administrators remain responsible for reviewing each report.

## Features

- Adds a Report Abuse link to bbPress reply controls.
- Sends the reply or topic ID to a configurable report page.
- Pre-populates a Gravity Forms field with the reported permalink.
- Provides a WordPress settings page for the report URL.
- Sanitizes settings and escapes front-end output.
- Supports translation through the `bbpress-report-abuse` text domain.
- Exposes filters for changing the link label and destination.

## Requirements

- WordPress
- bbPress
- Gravity Forms for the supplied report-form integration
- PHP 7.4 or later

## Installation

1. Download or clone this repository.
2. Copy the plugin directory to
   `wp-content/plugins/bbpress-report-abuse/`.
3. Activate **bbPress Report Abuse** in **Plugins → Installed Plugins**.
4. Create a WordPress page for abuse reports. The default address is
   `/report-abuse`.
5. Create or select a Gravity Form and embed it on that page.
6. Add a field that will hold the reported URL.
7. In that field's **Advanced** settings, enable dynamic population and set its
   parameter name to `bbp_report_abuse`.
8. Open **Settings → bbPress Report Abuse** and confirm the report page URL.

When a visitor follows a Report Abuse link, the plugin adds the reported item
ID as `bbp_report_topic`. On the report page it resolves that ID to a permalink
and inserts it into the configured Gravity Forms field.

## Email notifications

Configure report notifications in Gravity Forms. Version 1.1.0 includes a
sanitized **Moderator emails** setting, but the plugin does not currently use
that setting to send mail. Storing an address there alone will not produce a
notification.

## Customization

Change the link text with `bbpress_report_abuse_label`:

```php
add_filter( 'bbpress_report_abuse_label', function () {
	return 'Flag this reply';
} );
```

Change the report-page destination with `bbpress_report_abuse_url`:

```php
add_filter( 'bbpress_report_abuse_url', function ( $url ) {
	return site_url( '/community-report/' );
} );
```

Add customizations to a theme or a small site-specific plugin rather than
editing this plugin directly.

## Development

Install development dependencies and run the test suite:

```bash
composer install
vendor/bin/phpunit --configuration phpunit.xml.dist
```

GitHub Actions is configured to run the PHPUnit suite with PHP 8.0, 8.1, and
8.2. The current tests are a lightweight foundation rather than a complete
WordPress integration test suite.

## Security and privacy

- Treat abuse reports as potentially sensitive personal data.
- Collect only the information needed to investigate a report.
- Restrict access to form entries and define a retention policy.
- Add spam and rate-limit controls through the form configuration.
- Keep WordPress, bbPress, Gravity Forms, and this plugin updated.

Please report security problems privately to the repository owner rather than
publishing exploit details in a public issue.

## Status

Version 1.1.0 is a small functional plugin with basic settings and Gravity
Forms integration. Planned work should include full WordPress integration
tests and either implementing moderator-email delivery or removing the unused
setting.

## License

GPL-2.0-or-later, as declared in `composer.json`.
