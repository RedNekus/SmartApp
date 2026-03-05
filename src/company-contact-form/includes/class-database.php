<?php
/**
 * Database Class - Custom table handler for form submissions
 *
 * @package Company Contact Form
 * @since   1.2.0
 */

namespace CCF;

/**
 * Database class for managing form submissions table.
 */
class Database {

	/**
	 * Table name (without prefix)
	 *
	 * @var string
	 */
	private static $table_name = 'ccf_submissions';

	/**
	 * Create submissions table on plugin activation.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::$table_name;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name varchar(100) NOT NULL,
			last_name varchar(100) NOT NULL,
			email varchar(255) NOT NULL,
			subject varchar(255) DEFAULT '',
			message text NOT NULL,
			ip_address varchar(50) NOT NULL,
			submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY (id),
			KEY email (email),
			KEY submitted_at (submitted_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Save form submission to database.
	 *
	 * @param array $data Submission data.
	 * @return int|false Inserted ID or false.
	 */
	public static function save_submission( $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		$result = $wpdb->insert(
			$table_name,
			array(
				'first_name' => sanitize_text_field( $data['first_name'] ),
				'last_name'  => sanitize_text_field( $data['last_name'] ),
				'email'      => sanitize_email( $data['email'] ),
				'subject'    => sanitize_text_field( $data['subject'] ),
				'message'    => sanitize_textarea_field( $data['message'] ),
				'ip_address' => sanitize_text_field( $data['ip'] ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[CCF] Database: Failed to save submission - ' . $wpdb->last_error );
			return false;
		}

		$submission_id = $wpdb->insert_id;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[CCF] Database: Submission saved with ID ' . $submission_id );

		return $submission_id;
	}

	/**
	 * Get all submissions with pagination.
	 *
	 * @param int $per_page Items per page. Default 20.
	 * @param int $page     Current page. Default 1.
	 * @return array
	 */
	public static function get_submissions( $per_page = 20, $page = 1 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;
		$offset     = ( $page - 1 ) * $per_page;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT %d OFFSET %d",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$per_page,
				$offset
			),
			ARRAY_A
		);
	}

	/**
	 * Get total submissions count.
	 *
	 * @return int
	 */
	public static function get_submissions_count() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Delete submission by ID.
	 *
	 * @param int $id Submission ID.
	 * @return int|false
	 */
	public static function delete_submission( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		return $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Export submissions to CSV.
	 *
	 * @return void
	 */
	public static function export_to_csv() {
		global $wpdb;

		$table_name  = $wpdb->prefix . self::$table_name;
		$submissions = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY submitted_at DESC", ARRAY_A );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		header( 'Content-Type: text/csv; charset=utf-8' );
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		header( 'Content-Disposition: attachment; filename=ccf-submissions-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );

		// Headers.
		fputcsv( $output, array( 'ID', 'First Name', 'Last Name', 'Email', 'Subject', 'Message', 'IP Address', 'Submitted At' ) );

		// Data rows.
		foreach ( $submissions as $submission ) {
			fputcsv( $output, $submission );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}
}
