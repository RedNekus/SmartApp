<?php
/**
 * Admin Page - Submissions & Logs
 *
 * @package Company Contact Form
 * @since   1.2.0
 */

namespace CCF;

/**
 * Admin class for managing submissions and logs.
 *
 * Handles actions, data preparation, and template rendering.
 */
class Admin {

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
	}

	/**
	 * Add admin menu page.
	 *
	 * @return void
	 */
	public static function add_admin_page() {
		add_menu_page(
			__( 'Contact Form Submissions', 'company-contact-form' ),
			__( 'Contact Form', 'company-contact-form' ),
			'manage_options',
			'ccf-submissions',
			array( __CLASS__, 'render_page' ),
			'dashicons-feedback',
			30
		);
	}

	/**
	 * Handle admin actions (delete, export, clear logs).
	 *
	 * @return void
	 */
	public static function handle_actions() {
		// Delete submission.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'], $_GET['action'], $_GET['id'] )
			&& 'ccf-submissions' === $_GET['page']
			&& 'delete' === $_GET['action']
			&& current_user_can( 'manage_options' ) ) {

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			check_admin_referer( 'ccf_delete_' . wp_unslash( $_GET['id'] ) );

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			Database::delete_submission( intval( sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) );
			add_settings_error( 'ccf_admin', 'deleted', __( 'Submission deleted', 'company-contact-form' ), 'updated' );
		}

		// Export CSV.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'], $_GET['action'] )
			&& 'ccf-submissions' === $_GET['page']
			&& 'export' === $_GET['action']
			&& current_user_can( 'manage_options' ) ) {

			Database::export_to_csv();
		}

		// Clear logs.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'], $_GET['action'] )
			&& 'ccf-submissions' === $_GET['page']
			&& 'clear-logs' === $_GET['action']
			&& current_user_can( 'manage_options' ) ) {

			check_admin_referer( 'ccf_clear_logs' );
			Logger::clear_all();
			add_settings_error( 'ccf_admin', 'logs_cleared', __( 'Logs cleared', 'company-contact-form' ), 'updated' );
		}
	}

	/**
	 * Render admin page with tabs.
	 *
	 * Prepares data and loads template.
	 *
	 * @return void
	 */
	public static function render_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'submissions';

		// Prepare data for templates.
		$template_data = self::prepare_template_data( $tab );

		// Load template with extracted variables.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $template_data, EXTR_SKIP );
		include __DIR__ . '/../templates/admin/page-wrapper.php';
	}

	/**
	 * Prepare data for admin templates.
	 *
	 * @param string $tab Active tab slug.
	 * @return array Template variables.
	 */
	private static function prepare_template_data( $tab ) {
		$data = array( 'tab' => $tab );

		if ( 'logs' === $tab ) {
			$per_page = 50;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page        = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
			$total       = Logger::get_logs_count();
			$logs        = Logger::get_logs( $per_page, $page );
			$total_pages = ceil( $total / $per_page );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$result_filter = isset( $_GET['result'] ) ? sanitize_text_field( wp_unslash( $_GET['result'] ) ) : '';
			$results       = array( 'received', 'spam_blocked', 'hubspot_sent', 'hubspot_failed' );

			$data = array_merge( $data, compact( 'logs', 'total', 'total_pages', 'page', 'result_filter', 'results' ) );
		} else {
			$per_page = 20;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page        = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
			$total       = Database::get_submissions_count();
			$submissions = Database::get_submissions( $per_page, $page );
			$total_pages = ceil( $total / $per_page );

			$data = array_merge( $data, compact( 'submissions', 'total', 'total_pages', 'page' ) );
		}

		return $data;
	}
}
