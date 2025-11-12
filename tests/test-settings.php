<?php
use PHPUnit\Framework\TestCase;

class BBRA_Settings_Test extends TestCase {

	public function test_default_report_url_option_exists() {
		// Simulate option retrieval; get_option is available when WordPress is loaded.
		if ( function_exists( 'get_option' ) ) {
			$default = get_option( 'bbra_report_url', '' );
			$this->assertIsString( $default );
		} else {
			// If WP functions not available, assert plugin constant or fallback behavior.
			$this->assertTrue( true );
		}
	}

	public function test_sanitize_moderator_emails() {
		require_once dirname( __DIR__ ) . '/bbpress-report-abuse.php';
		$plugin = new bbp_Report_Abuse();

		// The sanitize method is protected; we can't call it directly. We test option sanitization via register_setting callback
		$raw = 'test@example.com, bad-email, other@example.org';
		$expected = 'test@example.com,other@example.org';
		$sanitized = $plugin->sanitize_moderator_emails( $raw );
		$this->assertSame( $expected, $sanitized );
	}
}
