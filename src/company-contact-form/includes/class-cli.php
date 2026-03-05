<?php
/**
 * WP-CLI Commands for Company Contact Form
 *
 * @package Company Contact Form
 * @since   1.2.0
 */

namespace CCF;

use WP_CLI;

/**
 * CLI class for WP-CLI commands.
 *
 * Provides commands for verifying HubSpot, listing submissions, and managing logs.
 */
class CLI {

	/**
	 * Verify HubSpot connection (test API credentials)
	 *
	 * ## OPTIONS
	 *
	 * [--token=<token>]
	 * : HubSpot Private App Token (optional, uses constant if not provided)
	 *
	 * [--portal-id=<id>]
	 * : HubSpot Portal ID (optional, uses constant if not provided)
	 *
	 * ## EXAMPLES
	 *
	 *     wp company-contact verify-hubspot
	 *     wp company-contact verify-hubspot --token=pat-na1-xxx --portal-id=12345678
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function verify_hubspot( $args, $assoc_args ) {
		$token     = $assoc_args['token'] ?? ( defined( 'CCF_HUBSPOT_TOKEN' ) ? CCF_HUBSPOT_TOKEN : '' );
		$portal_id = $assoc_args['portal-id'] ?? ( defined( 'CCF_HUBSPOT_PORTAL_ID' ) ? CCF_HUBSPOT_PORTAL_ID : '' );

		if ( empty( $token ) || empty( $portal_id ) ) {
			WP_CLI::error( 'HubSpot credentials not configured. Set CCF_HUBSPOT_TOKEN and CCF_HUBSPOT_PORTAL_ID in wp-config.php' );
		}

		WP_CLI::log( 'Testing HubSpot API connection...' );
		WP_CLI::log( sprintf( 'Portal ID: %s', $portal_id ) );
		WP_CLI::log( sprintf( 'Token: %s...', substr( $token, 0, 10 ) ) );

		$url      = sprintf( 'https://api.hubapi.com/crm/v3/objects/contacts?limit=1', $portal_id );
		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			WP_CLI::error( 'Connection failed: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code >= 200 && $status_code < 300 ) {
			WP_CLI::success( 'HubSpot API connection successful!' );
			WP_CLI::log( sprintf( 'Response: %d contacts found', $body['total'] ?? 0 ) );
		} else {
			WP_CLI::error( sprintf( 'HubSpot API error: HTTP %d - %s', $status_code, $body['message'] ?? 'Unknown error' ) );
		}
	}

	/**
	 * List recent form submissions
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : Number of submissions to show (default: 10)
	 *
	 * [--format=<format>]
	 * : Output format (table, csv, json). Default: table
	 *
	 * ## EXAMPLES
	 *
	 *     wp company-contact submissions
	 *     wp company-contact submissions --count=20 --format=json
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function submissions( $args, $assoc_args ) {
		$count  = isset( $assoc_args['count'] ) ? intval( $assoc_args['count'] ) : 10;
		$format = $assoc_args['format'] ?? 'table';

		$submissions = Database::get_submissions( $count, 1 );

		if ( empty( $submissions ) ) {
			WP_CLI::warning( 'No submissions found' );
			return;
		}

		$items = array_map(
			function ( $sub ) {
				return array(
					'ID'    => $sub['id'],
					'Email' => $sub['email'],
					'Name'  => $sub['first_name'] . ' ' . $sub['last_name'],
					'Date'  => $sub['submitted_at'],
				);
			},
			$submissions
		);

		\WP_CLI\Utils\format_items( $format, $items, array( 'ID', 'Email', 'Name', 'Date' ) );
	}

	/**
	 * View logger entries
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : Number of logs to show (default: 10)
	 *
	 * [--result=<result>]
	 * : Filter by result (received, spam_blocked, hubspot_sent, hubspot_failed)
	 *
	 * ## EXAMPLES
	 *
	 *     wp company-contact logs
	 *     wp company-contact logs --result=hubspot_sent
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function logs( $args, $assoc_args ) {
		$count  = isset( $assoc_args['count'] ) ? intval( $assoc_args['count'] ) : 10;
		$result = $assoc_args['result'] ?? '';

		global $wpdb;
		$table_name = $wpdb->prefix . 'ccf_logs';

		if ( $result ) {
			$logs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table_name WHERE result = %s ORDER BY timestamp DESC LIMIT %d",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$result,
					$count
				),
				ARRAY_A
			);
		} else {
			$logs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$count
				),
				ARRAY_A
			);
		}

		if ( empty( $logs ) ) {
			WP_CLI::warning( 'No logs found' );
			return;
		}

		\WP_CLI\Utils\format_items( 'table', $logs, array( 'id', 'timestamp', 'email', 'result', 'hubspot_id', 'user_ip' ) );
	}

	/**
	 * Clear old logs (manual rotation)
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Delete logs older than N days (default: 30)
	 *
	 * [--yes]
	 * : Skip confirmation prompt
	 *
	 * ## EXAMPLES
	 *
	 *     wp company-contact rotate-logs
	 *     wp company-contact rotate-logs --days=60 --yes
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function rotate_logs( $args, $assoc_args ) {
		$days = isset( $assoc_args['days'] ) ? intval( $assoc_args['days'] ) : 30;

		if ( ! isset( $assoc_args['yes'] ) ) {
			WP_CLI::confirm( sprintf( 'Delete all logs older than %d days?', $days ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ccf_logs';

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$days
			)
		);

		if ( false === $deleted ) {
			WP_CLI::error( 'Failed to delete logs: ' . $wpdb->last_error );
		}

		WP_CLI::success( sprintf( 'Deleted %d log entries older than %d days', $deleted, $days ) );
	}
}

// Register CLI commands (file-scope: executes immediately when file is required)
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'company-contact', 'CCF\\CLI' );
}
