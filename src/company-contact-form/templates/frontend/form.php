<?php
/**
 * Frontend Form Template
 *
 * @package Company Contact Form
 * @since   1.3.0
 *
 * @var array $attributes Block attributes
 * @var string $nonce     REST API nonce
 */

defined( 'ABSPATH' ) || exit;

// Get attributes with defaults.
$attributes = wp_parse_args(
	$attributes,
	array(
		'subjectPrefix'  => '',
		'recipientEmail' => '',
		'showHoneypot'   => true,
		'showStartTime'  => true,
	)
);
?>
<div class="ccf-form-wrapper" data-attributes='<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>'>
	<form class="ccf-form" method="post" novalidate>
		<?php if ( $attributes['showHoneypot'] ) : ?>
			<!-- Honeypot field (hidden from humans) -->
			<p style="display:none !important;" aria-hidden="true">
				<label>
					<?php esc_html_e( 'Website', 'company-contact-form' ); ?>
					<input type="text" name="website" tabindex="-1" autocomplete="off">
				</label>
			</p>
		<?php endif; ?>

		<?php if ( $attributes['showStartTime'] ) : ?>
			<input type="hidden" name="form_start_time" value="<?php echo esc_attr( time() ); ?>">
		<?php endif; ?>

		<input type="hidden" name="ccf_block_attributes" value="<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>">

		<p>
			<label>
				<span class="ccf-label"><?php esc_html_e( 'First Name', 'company-contact-form' ); ?></span>
				<input type="text" name="first_name" required aria-required="true">
			</label>
		</p>

		<p>
			<label>
				<span class="ccf-label"><?php esc_html_e( 'Last Name', 'company-contact-form' ); ?></span>
				<input type="text" name="last_name" required aria-required="true">
			</label>
		</p>

		<p>
			<label>
				<span class="ccf-label"><?php esc_html_e( 'Email', 'company-contact-form' ); ?></span>
				<input type="email" name="email" required aria-required="true">
			</label>
		</p>

		<p>
			<label>
				<span class="ccf-label"><?php esc_html_e( 'Subject', 'company-contact-form' ); ?></span>
				<input type="text" name="subject" value="<?php echo esc_attr( $attributes['subjectPrefix'] ); ?>">
			</label>
		</p>

		<p>
			<label>
				<span class="ccf-label"><?php esc_html_e( 'Message', 'company-contact-form' ); ?></span>
				<textarea name="message" rows="5" required aria-required="true"></textarea>
			</label>
		</p>

		<p>
			<button type="submit" class="ccf-submit-button">
				<?php esc_html_e( 'Send', 'company-contact-form' ); ?>
			</button>
		</p>

		<div class="ccf-response" aria-live="polite" role="status"></div>
	</form>
</div>
