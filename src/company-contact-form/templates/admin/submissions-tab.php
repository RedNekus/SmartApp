<?php
/**
 * Submissions Tab Template
 *
 * @package Company Contact Form
 * @since   1.3.0
 *
 * @var array $submissions Array of submission records
 * @var int   $total_pages Total pages for pagination
 * @var int   $page        Current page number
 */

defined( 'ABSPATH' ) || exit;
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
