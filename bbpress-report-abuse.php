<?php
/**
 * Plugin Name: bbPress Report Abuse
 * Plugin URI:  http://github.com/croasdell/bbpress-report-abuse
 * Description: Provides a "Report Abuse" link in replies and a simple settings page to configure the report form URL and moderator emails.
 * Version:     1.1.0
 * Author:      Ian Croasdell
 * Author URI:  http://www.croasdell.biz
 * Text Domain: bbpress-report-abuse
 * Domain Path: /languages
 *
 * @package bbPressReportAbuse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'bbp_Report_Abuse' ) ) {

	final class bbp_Report_Abuse {

		const VERSION = '1.1.0';
		const OPTION_REPORT_URL = 'bbra_report_url';
		const OPTION_MODERATOR_EMAILS = 'bbra_moderator_emails';

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Load textdomain early.
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			// Hook frontend link if bbPress present.
			if ( function_exists( 'bbp_get_reply_id' ) || function_exists( 'bbp_get_topic_id' ) ) {
				add_action( 'bbp_theme_before_reply_admin_links', array( $this, 'abuse_link_in_forum' ) );
			}

			// Gravity Forms integration.
			if ( function_exists( 'gform_pre_render' ) || class_exists( 'GFForms' ) ) {
				add_filter( 'gform_pre_render', array( $this, 'abuse_link_in_form' ) );
			}

			// Admin settings.
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// Uninstall cleanup registered in uninstall.php if you choose to include it.
		}

		/**
		 * Load translations.
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'bbpress-report-abuse', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Print abuse link in forum replies.
		 */
		public function abuse_link_in_forum() {
			$label = apply_filters( 'bbpress_report_abuse_label', __( 'Report Abuse', 'bbpress-report-abuse' ) );

			// Get configured report page URL or default.
			$default_url = site_url( '/report-abuse' );
			$url = apply_filters( 'bbpress_report_abuse_url', $this->get_report_url( $default_url ) );

			// Determine item id (prefer bbPress helpers).
			$item_id = 0;
			if ( function_exists( 'bbp_get_reply_id' ) ) {
				$item_id = intval( bbp_get_reply_id() );
			}
			if ( empty( $item_id ) && function_exists( 'bbp_get_topic_id' ) ) {
				$item_id = intval( bbp_get_topic_id() );
			}
			if ( empty( $item_id ) ) {
				$item_id = intval( get_the_ID() );
			}

			$sanitized_url = esc_url( $url );
			if ( $item_id > 0 ) {
				$sanitized_url = esc_url( add_query_arg( 'bbp_report_topic', $item_id, $sanitized_url ) );
			}

			printf(
				'<a class="bbp-report-abuse" href="%1$s">%2$s</a>',
				$sanitized_url,
				esc_html( $label )
			);
		}

		/**
		 * Pre-populate Gravity Forms field parameter 'bbp_report_abuse' with the reported permalink.
		 *
		 * Supports GF field formats as arrays or objects.
		 *
		 * @param array|object $form Gravity Forms form.
		 * @return array|object
		 */
		public function abuse_link_in_form( $form ) {
			$topic = filter_input( INPUT_GET, 'bbp_report_topic', FILTER_VALIDATE_INT );
			if ( empty( $topic ) || $topic <= 0 ) {
				return $form;
			}

			$permalink = get_permalink( $topic );
			if ( ! $permalink ) {
				return $form;
			}
			$permalink = esc_url_raw( $permalink );

			if ( empty( $form ) || empty( $form['fields'] ) ) {
				return $form;
			}

			foreach ( $form['fields'] as &$field ) {
				$allows_prepopulate = false;
				$input_name = '';

				if ( is_array( $field ) ) {
					$allows_prepopulate = ! empty( $field['allowsPrepopulate'] );
					$input_name = isset( $field['inputName'] ) ? $field['inputName'] : '';
				} elseif ( is_object( $field ) ) {
					$allows_prepopulate = ! empty( $field->allowsPrepopulate );
					$input_name = isset( $field->inputName ) ? $field->inputName : ( isset( $field->input_name ) ? $field->input_name : '' );
				}

				if ( $allows_prepopulate && 'bbp_report_abuse' === $input_name ) {
					if ( is_array( $field ) ) {
						$field['defaultValue'] = $permalink;
					} else {
						$field->defaultValue = $permalink;
					}
				}
			}

			return $form;
		}

		/**
		 * Add admin menu for plugin settings.
		 */
		public function add_admin_menu() {
			add_options_page(
				__( 'bbPress Report Abuse', 'bbpress-report-abuse' ),
				__( 'bbPress Report Abuse', 'bbpress-report-abuse' ),
				'manage_options',
				'bbpress-report-abuse',
				array( $this, 'settings_page' )
			);
		}

		/**
		 * Register settings.
		 */
		public function register_settings() {
			register_setting( 'bbra_settings_group', self::OPTION_REPORT_URL, array( $this, 'sanitize_report_url' ) );
			register_setting( 'bbra_settings_group', self::OPTION_MODERATOR_EMAILS, array( $this, 'sanitize_moderator_emails' ) );

			add_settings_section(
				'bbra_main',
				__( 'Report Abuse Settings', 'bbpress-report-abuse' ),
				function() { echo '<p>' . esc_html__( 'Configure the report page URL and moderator email addresses.', 'bbpress-report-abuse' ) . '</p>'; },
				'bbra_settings'
			);

			add_settings_field(
				self::OPTION_REPORT_URL,
				__( 'Report page URL', 'bbpress-report-abuse' ),
				array( $this, 'field_report_url_cb' ),
				'bbra_settings',
				'bbra_main'
			);

			add_settings_field(
				self::OPTION_MODERATOR_EMAILS,
				__( 'Moderator emails (comma-separated)', 'bbpress-report-abuse' ),
				array( $this, 'field_moderator_emails_cb' ),
				'bbra_settings',
				'bbra_main'
			);
		}

		/**
		 * Sanitize report url.
		 */
		public function sanitize_report_url( $val ) {
			return esc_url_raw( trim( $val ) );
		}

		/**
		 * Sanitize moderator emails.
		 */
		public function sanitize_moderator_emails( $val ) {
			if ( is_array( $val ) ) {
				$val = implode( ',', $val );
			}
			$parts = array_map( 'trim', explode( ',', $val ) );
			$good = array();
			foreach ( $parts as $p ) {
				if ( is_email( $p ) ) {
					$good[] = $p;
				}
			}
			return implode( ',', $good );
		}

		/**
		 * Field callback - report url.
		 */
		public function field_report_url_cb() {
			$value = get_option( self::OPTION_REPORT_URL, site_url( '/report-abuse' ) );
			printf(
				'<input type="url" class="regular-text" name="%1$s" value="%2$s" />',
				esc_attr( self::OPTION_REPORT_URL ),
				esc_attr( $value )
			);
			echo '<p class="description">' . esc_html__( 'URL of the page containing your report form. Default: /report-abuse', 'bbpress-report-abuse' ) . '</p>';
		}

		/**
		 * Field callback - moderator emails.
		 */
		public function field_moderator_emails_cb() {
			$value = get_option( self::OPTION_MODERATOR_EMAILS, '' );
			printf(
				'<input type="text" class="regular-text" name="%1$s" value="%2$s" />',
				esc_attr( self::OPTION_MODERATOR_EMAILS ),
				esc_attr( $value )
			);
			echo '<p class="description">' . esc_html__( 'Comma-separated list of emails to notify when a report is submitted (optional).', 'bbpress-report-abuse' ) . '</p>';
		}

		/**
		 * Settings page output.
		 */
		public function settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'bbPress Report Abuse', 'bbpress-report-abuse' ); ?></h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'bbra_settings_group' );
					do_settings_sections( 'bbra_settings' );
					submit_button();
					?>
				</form>
			</div>
			<?php
		}

		/**
		 * Helper to get the report URL (option or default).
		 */
		private function get_report_url( $default ) {
			$url = get_option( self::OPTION_REPORT_URL, '' );
			if ( empty( $url ) ) {
				return $default;
			}
			return esc_url( $url );
		}
	}

	// Initialize plugin.
	( new bbp_Report_Abuse() );
}
