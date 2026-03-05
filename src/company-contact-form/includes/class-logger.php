<?php
/**
 * Logger Class - Custom logging table with 30-day rotation
 *
 * @package Company Contact Form
 * @since   1.2.0
 */

namespace CCF;

/**
 * Logger class for storing form submission events.
 *
 * Handles logging to custom database table with automatic 30-day rotation.
 */
class Logger {

	/**
	 * Table name (without prefix)
	 *
	 * @var string
	 */
	private static $table_name = 'ccf_logs';

	/**
	 * Create logs table on plugin activation.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::$table_name;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			email varchar(255) NOT NULL,
			result varchar(50) NOT NULL,
			hubspot_id varchar(100) DEFAULT '',
			user_ip varchar(50) NOT NULL,
			PRIMARY KEY (id),
			KEY email (email),
			KEY timestamp (timestamp),
			KEY result (result)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log a submission event.
	 *
	 * @param string $email      Submitter email.
	 * @param string $result     Result status.
	 * @param string $hubspot_id HubSpot ID.
	 * @param string $user_ip    User IP address.
	 * @return int|false
	 */
	public static function log( $email, $result, $hubspot_id = '', $user_ip = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'email'      => sanitize_email( $email ),
				'result'     => sanitize_text_field( $result ),
				'hubspot_id' => sanitize_text_field( $hubspot_id ),
				'user_ip'    => sanitize_text_field( $user_ip ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		self::rotate_logs();

		if ( false === $inserted ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[CCF] Logger: Failed to insert log - ' . $wpdb->last_error );
			return false;
		}

		$log_id = $wpdb->insert_id;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[CCF] Logger: Log entry created with ID ' . $log_id );

		return $log_id;
	}

	/**
	 * Delete logs older than specified days.
	 *
	 * @param int $days Days to keep. Default 30.
	 * @return int|false
	 */
	public static function rotate_logs( $days = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$days
			)
		);

		if ( false !== $deleted && $deleted > 0 ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[CCF] Logger: Rotated ' . $deleted . ' old log entries' );
		}

		return $deleted;
	}

	/**
	 * Get logs with pagination.
	 *
	 * @param int $per_page Items per page. Default 50.
	 * @param int $page     Current page. Default 1.
	 * @return array
	 */
	public static function get_logs( $per_page = 50, $page = 1 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;
		$offset     = ( $page - 1 ) * $per_page;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$per_page,
				$offset
			),
			ARRAY_A
		);
	}

	/**
	 * Get total log count.
	 *
	 * @return int
	 */
	public static function get_logs_count() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Clear all logs.
	 *
	 * @return int|false
	 */
	public static function clear_all() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		return $wpdb->query( "TRUNCATE TABLE $table_name" );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
