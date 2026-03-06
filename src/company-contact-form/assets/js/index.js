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
					responseDiv.textContent = result.message || 'Message sent successfully!';
					form.reset();
				} else {
					// Error message.
					responseDiv.className = 'ccf-response error';
					responseDiv.textContent = result.message || 'An error occurred. Please try again.';
				}
			} catch ( error ) {
				// Network error.
				responseDiv.className = 'ccf-response error';
				responseDiv.textContent = 'Network error. Please check your connection and try again.';
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
