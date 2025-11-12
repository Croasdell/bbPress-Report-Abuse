<?php
/**
 * Plugin Name: bbPress Report Abuse
 * Plugin URI:  http://github.com/croasdell/bbpress-report-abuse
 * Description: Provides a "Report Abuse" link in replies
 * Version:     1.0.0
 * Author:      Ian Croasdell
 * Author URI:  http://www.croasdell.biz
 * Text Domain: bbpress-report-abuse
 * Domain Path: /languages
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author     Ian Croasdell
 * @version    1.0.0
 * @package    bbPressReportAbuse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'bbp_Report_Abuse' ) ) {

	/**
	 * bbPress Report Abuse init class
	 *
	 * Improvements:
	 * - Loads textdomain
	 * - Checks for bbPress / Gravity Forms before hooking
	 * - Sanitizes inputs and avoids raw $_GET usage
	 * - Supports both array- and object-based GF field structures
	 * - Uses escaping and translation functions
	 */
	final class bbp_Report_Abuse {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			// Only hook bbPress template injection if bbPress is active.
			if ( function_exists( 'bbp_get_reply_id' ) ) {
				add_action( 'bbp_theme_before_reply_admin_links', array( $this, 'abuse_link_in_forum' ) );
			}

			// Only hook Gravity Forms filter if GF is active.
			if ( class_exists( 'GFForms' ) || function_exists( 'gform_pre_render' ) ) {
				add_filter( 'gform_pre_render', array( $this, 'abuse_link_in_form' ) );
			}
		}

		/**
		 * Load plugin textdomain
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'bbpress-report-abuse', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Abuse Link in Forum
		 *
		 * Defaults to '/report-abuse' but is filterable
		 *
		 * @since 1.0.0
		 */
		public function abuse_link_in_forum() {
			/**
			 * Filterable label and URL. Callers must pass safe values;
			 * we still run basic sanitization & escaping here.
			 */
			$label = apply_filters( 'bbpress_report_abuse_label', __( 'Report Abuse', 'bbpress-report-abuse' ) );

			$default_url = site_url( '/report-abuse' );
			$url         = apply_filters( 'bbpress_report_abuse_url', $default_url );

			// Determine current reply/topic ID safely: prefer bbPress helper if available.
			$item_id = 0;
			if ( function_exists( 'bbp_get_reply_id' ) ) {
				$item_id = intval( bbp_get_reply_id() );
			}
			// fallback to global post ID (sanitized)
			if ( empty( $item_id ) ) {
				$item_id = intval( get_the_ID() );
			}

			// Allow an empty item_id if something odd is happening, but avoid adding a broken query arg.
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
		 * Add the abuse link to a Gravity form field with a
		 * parameter name of 'bbp_report_abuse'
		 *
		 * Supports both array and object field shapes used by different GF versions.
		 *
		 * @param array|object $form Gravity Forms form object/array.
		 * @return array|object $form
		 *
		 * @since 1.0.0
		 */
		public function abuse_link_in_form( $form ) {
			// Safely get topic ID from GET. Use filter_input for sanitization.
			$topic = filter_input( INPUT_GET, 'bbp_report_topic', FILTER_VALIDATE_INT );
			if ( empty( $topic ) || $topic <= 0 ) {
				return $form;
			}

			// Build permalink once and sanitize for storing in the default value.
			$permalink = get_permalink( $topic );
			if ( ! $permalink ) {
				return $form;
			}
			$permalink = esc_url_raw( $permalink );

			// Gravity Forms sometimes provides fields as objects or arrays.
			if ( empty( $form ) || empty( $form['fields'] ) ) {
				return $form;
			}

			foreach ( $form['fields'] as &$field ) {
				// Check allowsPrepopulate for both object/array
				$allows_prepopulate = false;
				$input_name         = '';

				if ( is_array( $field ) ) {
					$allows_prepopulate = ! empty( $field['allowsPrepopulate'] );
					$input_name         = isset( $field['inputName'] ) ? $field['inputName'] : '';
				} elseif ( is_object( $field ) ) {
					$allows_prepopulate = ! empty( $field->allowsPrepopulate );
					$input_name         = isset( $field->inputName ) ? $field->inputName : ( isset( $field->input_name ) ? $field->input_name : '' );
				}

				if ( $allows_prepopulate && 'bbp_report_abuse' === $input_name ) {
					// Set defaultValue in the correct shape.
					if ( is_array( $field ) ) {
						$field['defaultValue'] = $permalink;
					} else {
						$field->defaultValue = $permalink;
					}
				}
			}
			// Return modified form (GF expects the form back).
			return $form;
		}
	}

	// Initialize.
	( new bbp_Report_Abuse() );
}
