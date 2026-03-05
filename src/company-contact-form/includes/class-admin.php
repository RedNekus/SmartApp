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
			check_admin_referer( 'ccf_delete_' . $_GET['id'] );
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
	 * @return void
	 */
	public static function render_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'submissions';
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Contact Form', 'company-contact-form' ); ?></h1>
			
			<!-- Tabs -->
			<h2 class="nav-tab-wrapper">
				<a href="?page=ccf-submissions&tab=submissions" 
					class="nav-tab <?php echo 'submissions' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Submissions', 'company-contact-form' ); ?>
				</a>
				<a href="?page=ccf-submissions&tab=logs" 
					class="nav-tab <?php echo 'logs' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Logs', 'company-contact-form' ); ?>
				</a>
			</h2>

			<?php settings_errors( 'ccf_admin' ); ?>

			<?php if ( 'logs' === $tab ) : ?>
				<?php self::render_logs_tab(); ?>
			<?php else : ?>
				<?php self::render_submissions_tab(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render submissions tab.
	 *
	 * @return void
	 */
	private static function render_submissions_tab() {
		$per_page = 20;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page        = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$total       = Database::get_submissions_count();
		$submissions = Database::get_submissions( $per_page, $page );
		$total_pages = ceil( $total / $per_page );
		?>
		
		<a href="?page=ccf-submissions&action=export" class="page-title-action">
			<?php esc_html_e( 'Export CSV', 'company-contact-form' ); ?>
		</a>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Name', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Email', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Subject', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Message', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'IP', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Date', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'company-contact-form' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $submissions ) ) : ?>
					<tr><td colspan="8"><?php esc_html_e( 'No submissions yet', 'company-contact-form' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $submissions as $sub ) : ?>
						<tr>
							<td><?php echo esc_html( $sub['id'] ); ?></td>
							<td><?php echo esc_html( $sub['first_name'] . ' ' . $sub['last_name'] ); ?></td>
							<td><a href="mailto:<?php echo esc_attr( $sub['email'] ); ?>"><?php echo esc_html( $sub['email'] ); ?></a></td>
							<td><?php echo esc_html( $sub['subject'] ); ?></td>
							<td><?php echo esc_html( wp_trim_words( $sub['message'], 10 ) ); ?></td>
							<td><?php echo esc_html( $sub['ip_address'] ); ?></td>
							<td><?php echo esc_html( $sub['submitted_at'] ); ?></td>
							<td>
								<a href="?page=ccf-submissions&action=delete&id=<?php echo intval( $sub['id'] ); ?>&_wpnonce=<?php echo esc_attr( wp_create_nonce( 'ccf_delete_' . $sub['id'] ) ); ?>" 
									onclick="return confirm('Delete this submission?')"
									class="button button-small button-link-delete">
									<?php esc_html_e( 'Delete', 'company-contact-form' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav"><div class="tablenav-pages">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => __( '«' ),
						'next_text' => __( '»' ),
						'total'     => $total_pages,
						'current'   => $page,
					)
				);
				?>
			</div></div>
			<?php
		endif;
	}

	/**
	 * Render logs tab.
	 *
	 * @return void
	 */
	private static function render_logs_tab() {
		$per_page = 50;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page        = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$total       = Logger::get_logs_count();
		$logs        = Logger::get_logs( $per_page, $page );
		$total_pages = ceil( $total / $per_page );

		// Filter by result.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$result_filter = isset( $_GET['result'] ) ? sanitize_text_field( wp_unslash( $_GET['result'] ) ) : '';
		$results       = array( 'received', 'spam_blocked', 'hubspot_sent', 'hubspot_failed' );
		?>

		<div style="margin-bottom: 15px;">
			<a href="?page=ccf-submissions&tab=logs&action=clear-logs&_wpnonce=<?php echo esc_attr( wp_create_nonce( 'ccf_clear_logs' ) ); ?>" 
				onclick="return confirm('Clear ALL logs? This cannot be undone.')"
				class="button button-secondary button-link-delete">
				<?php esc_html_e( 'Clear All Logs', 'company-contact-form' ); ?>
			</a>
			<span style="margin-left: 10px; color: #666; font-size: 12px;">
				<?php esc_html_e( 'Auto-rotation: logs older than 30 days are deleted automatically', 'company-contact-form' ); ?>
			</span>
		</div>

		<!-- Filters -->
		<form method="get" style="margin-bottom: 15px;">
			<input type="hidden" name="page" value="ccf-submissions">
			<input type="hidden" name="tab" value="logs">
			<select name="result">
				<option value=""><?php esc_html_e( 'All results', 'company-contact-form' ); ?></option>
				<?php foreach ( $results as $res ) : ?>
					<option value="<?php echo esc_attr( $res ); ?>" <?php selected( $result_filter, $res ); ?>>
						<?php echo esc_html( $res ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'company-contact-form' ); ?></button>
			<?php if ( $result_filter ) : ?>
				<a href="?page=ccf-submissions&tab=logs" class="button"><?php esc_html_e( 'Reset', 'company-contact-form' ); ?></a>
			<?php endif; ?>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Timestamp', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Email', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'Result', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'HubSpot ID', 'company-contact-form' ); ?></th>
					<th><?php esc_html_e( 'IP', 'company-contact-form' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $logs ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No logs yet', 'company-contact-form' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log['id'] ); ?></td>
							<td><?php echo esc_html( $log['timestamp'] ); ?></td>
							<td><?php echo esc_html( $log['email'] ); ?></td>
							<td>
								<span style="padding: 2px 8px; border-radius: 3px; background: 
									<?php
									echo match ( $log['result'] ) { // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
										'received' => '#d4edda',
										'spam_blocked' => '#f8d7da',
										'hubspot_sent' => '#cce5ff',
										'hubspot_failed' => '#fff3cd',
										default => '#e2e3e5'
									};
	?>
								">
									<?php echo esc_html( $log['result'] ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $log['hubspot_id'] ?: '—' ); ?></td>
							<td><?php echo esc_html( $log['user_ip'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav"><div class="tablenav-pages">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => __( '«' ),
						'next_text' => __( '»' ),
						'total'     => $total_pages,
						'current'   => $page,
					)
				);
				?>
			</div></div>
			<?php
		endif;
	}
}
