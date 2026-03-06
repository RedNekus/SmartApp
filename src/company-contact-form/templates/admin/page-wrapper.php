<?php
/**
 * Admin Page Wrapper Template
 *
 * @package Company Contact Form
 * @since   1.3.0
 *
 * @var string $tab Active tab slug ('submissions' or 'logs')
 */

defined( 'ABSPATH' ) || exit;
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
		<?php include __DIR__ . '/logs-tab.php'; ?>
	<?php else : ?>
		<?php include __DIR__ . '/submissions-tab.php'; ?>
	<?php endif; ?>
</div>
