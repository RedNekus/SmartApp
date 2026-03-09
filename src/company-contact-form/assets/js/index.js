/**
 * Company Contact Form - Frontend JavaScript
 *
 * @package Company Contact Form
 */

document.addEventListener( 'DOMContentLoaded', function() {
	const forms = document.querySelectorAll( '.ccf-form' );

	forms.forEach( function( form ) {
		const wrapper = form.closest( '.ccf-form-wrapper' );
		const responseDiv = form.querySelector( '.ccf-response' );
		const submitButton = form.querySelector( 'button[type="submit"]' );

		form.addEventListener( 'submit', async function( e ) {
			e.preventDefault();

			// Disable submit button.
			submitButton.disabled = true;
			const originalText = submitButton.textContent;
			submitButton.textContent = ccfSettings.sending || 'Sending...';

			// Clear previous messages.
			responseDiv.className = 'ccf-response';
			responseDiv.textContent = '';

			// Collect form data.
			const formData = new FormData( form );
			const data = Object.fromEntries( formData.entries() );

			try {
				const response = await fetch( ccfSettings.apiUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': ccfSettings.nonce,
					},
					body: JSON.stringify( data ),
				} );

				const result = await response.json();

				if ( response.ok ) {
					// Success message.
					responseDiv.className = 'ccf-response success';
					responseDiv.textContent = result.message || ccfSettings.success;
					form.reset();
				} else {
					// Error message - use specific error from server or fallback.
					responseDiv.className = 'ccf-response error';
					
					// Map error codes to localized messages.
					const errorMessages = {
						'invalid_email': ccfSettings.errorInvalidEmail,
						'invalid_name': ccfSettings.errorInvalidName,
						'required_field': ccfSettings.errorRequired,
						'spam_detected': ccfSettings.errorSpam,
						'too_fast': ccfSettings.errorTooFast,
						'rate_limit': ccfSettings.errorRateLimit,
						'unauthorized': ccfSettings.errorUnauthorized,
						'server_error': ccfSettings.errorServerError,
					};

					const errorMessage = errorMessages[ result.code ] || result.message || ccfSettings.error;
					responseDiv.textContent = errorMessage;
				}
			} catch ( error ) {
				// Network error.
				responseDiv.className = 'ccf-response error';
				responseDiv.textContent = ccfSettings.errorNetwork || 'Network error. Please check your connection.';
				console.error( 'CCF Form Error:', error );
			} finally {
				// Re-enable submit button.
				submitButton.disabled = false;
				submitButton.textContent = originalText;

				// Scroll to response.
				responseDiv.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		} );
	} );
} );
