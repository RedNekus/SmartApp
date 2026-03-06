<?php
/**
 * Logs Tab Template
 *
 * @package Company Contact Form
 * @since   1.3.0
 *
 * @var array $logs          Array of log entries
 * @var int   $total_pages   Total pages for pagination
 * @var int   $page          Current page number
 * @var string $result_filter Current filter value
 * @var array  $results       Available filter values
 */

defined( 'ABSPATH' ) || exit;
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
							echo match ( $log['result'] ) {
								'received' => '#d4edda',
								'spam_blocked' => '#f8d7da',
								'hubspot_sent' => '#cce5ff',
								'hubspot_failed' => '#fff3cd',
								default => '#e2e3e5'
							};
							// phpcs:ignore Generic.WhiteSpace.ScopeIndent.IncorrectExact
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
