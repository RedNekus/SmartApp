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

		<!-- First Name -->
		<p class="ccf-form-field">
			<label for="ccf-first-name">
				<span class="ccf-label"><?php esc_html_e( 'First Name', 'company-contact-form' ); ?> <span class="ccf-required">*</span></span>
				<input type="text" id="ccf-first-name" name="first_name" required aria-required="true">
			</label>
			<span class="ccf-field-error" aria-live="polite"></span>
		</p>

		<!-- Last Name -->
		<p class="ccf-form-field">
			<label for="ccf-last-name">
				<span class="ccf-label"><?php esc_html_e( 'Last Name', 'company-contact-form' ); ?> <span class="ccf-required">*</span></span>
				<input type="text" id="ccf-last-name" name="last_name" required aria-required="true">
			</label>
			<span class="ccf-field-error" aria-live="polite"></span>
		</p>

		<!-- Email -->
		<p class="ccf-form-field">
			<label for="ccf-email">
				<span class="ccf-label"><?php esc_html_e( 'Email', 'company-contact-form' ); ?> <span class="ccf-required">*</span></span>
				<input type="email" id="ccf-email" name="email" required aria-required="true">
			</label>
			<span class="ccf-field-error" aria-live="polite"></span>
		</p>

		<!-- Subject -->
		<p class="ccf-form-field">
			<label for="ccf-subject">
				<span class="ccf-label"><?php esc_html_e( 'Subject', 'company-contact-form' ); ?></span>
				<input type="text" id="ccf-subject" name="subject" value="<?php echo esc_attr( $attributes['subjectPrefix'] ); ?>">
			</label>
			<span class="ccf-field-error" aria-live="polite"></span>
		</p>

		<!-- Message -->
		<p class="ccf-form-field">
			<label for="ccf-message">
				<span class="ccf-label"><?php esc_html_e( 'Message', 'company-contact-form' ); ?> <span class="ccf-required">*</span></span>
				<textarea id="ccf-message" name="message" rows="5" required aria-required="true"></textarea>
			</label>
			<span class="ccf-field-error" aria-live="polite"></span>
		</p>

		<!-- Submit -->
		<p>
			<button type="submit" class="ccf-submit-button">
				<?php esc_html_e( 'Send', 'company-contact-form' ); ?>
			</button>
		</p>

		<!-- General Response -->
		<div class="ccf-response" aria-live="polite" role="status"></div>
	</form>
</div>
