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

		/**
		 * Clear all field errors
		 */
		function clearFieldErrors() {
			form.querySelectorAll( '.ccf-field-error' ).forEach( function( el ) {
				el.textContent = '';
			} );
			form.querySelectorAll( '.ccf-field-error-input' ).forEach( function( el ) {
				el.classList.remove( 'ccf-field-error-input' );
			} );
		}

		/**
		 * Show error for a specific field
		 * @param {string} fieldName - The name attribute of the field
		 * @param {string} message - Error message to display
		 */
		function showFieldError( fieldName, message ) {
			const field = form.querySelector( '[name="' + fieldName + '"]' );
			if ( field ) {
				const errorContainer = field.closest( '.ccf-form-field' )?.querySelector( '.ccf-field-error' );
				if ( errorContainer ) {
					errorContainer.textContent = message;
				}
				field.classList.add( 'ccf-field-error-input' );
			}
		}

		/**
		 * Clear error when user interacts with a field
		 */
		form.addEventListener( 'input', function( e ) {
			if ( e.target.matches( 'input, textarea' ) ) {
				const field = e.target;
				const errorContainer = field.closest( '.ccf-form-field' )?.querySelector( '.ccf-field-error' );
				
				// Clear error styling and message
				field.classList.remove( 'ccf-field-error-input' );
				if ( errorContainer ) {
					errorContainer.textContent = '';
				}
			}
		} );

		// Also clear on focus for immediate feedback
		form.addEventListener( 'focus', function( e ) {
			if ( e.target.matches( 'input, textarea' ) ) {
				const field = e.target;
				const errorContainer = field.closest( '.ccf-form-field' )?.querySelector( '.ccf-field-error' );
				
				field.classList.remove( 'ccf-field-error-input' );
				if ( errorContainer ) {
					errorContainer.textContent = '';
				}
			}
		}, true ); // useCapture: true, чтобы ловить фокус на вложенных элементах

		/**
		 * Validate form data client-side
		 * @param {Object} data - Form data object
		 * @return {boolean} - True if valid, false if errors found
		 */
		function validateFormData( data ) {
			let hasErrors = false;

			// Required: first_name
			if ( ! data.first_name || data.first_name.trim() === '' ) {
				showFieldError( 'first_name', ccfSettings.errorRequired || 'This field is required' );
				hasErrors = true;
			}

			// Required: last_name
			if ( ! data.last_name || data.last_name.trim() === '' ) {
				showFieldError( 'last_name', ccfSettings.errorRequired || 'This field is required' );
				hasErrors = true;
			}

			// Required + format: email
			if ( ! data.email || data.email.trim() === '' ) {
				showFieldError( 'email', ccfSettings.errorRequired || 'This field is required' );
				hasErrors = true;
			} else if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( data.email ) ) {
				showFieldError( 'email', ccfSettings.errorInvalidEmail || 'Invalid email format' );
				hasErrors = true;
			}

			// Required: message
			if ( ! data.message || data.message.trim() === '' ) {
				showFieldError( 'message', ccfSettings.errorRequired || 'This field is required' );
				hasErrors = true;
			}

			return ! hasErrors;
		}

		form.addEventListener( 'submit', async function( e ) {
			e.preventDefault();

			// Clear previous errors
			clearFieldErrors();
			responseDiv.className = 'ccf-response';
			responseDiv.textContent = '';

			// Disable submit button
			submitButton.disabled = true;
			const originalText = submitButton.textContent;
			submitButton.textContent = ccfSettings.sending || 'Sending...';

			// Collect form data
			const formData = new FormData( form );
			const data = Object.fromEntries( formData.entries() );

			// Client-side validation
			if ( ! validateFormData( data ) ) {
				submitButton.disabled = false;
				submitButton.textContent = originalText;
				return;
			}

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
					// Success message
					responseDiv.className = 'ccf-response success';
					responseDiv.textContent = result.message || ccfSettings.success;
					form.reset();
				} else {
					// Error message - use specific error from server or fallback
					responseDiv.className = 'ccf-response error';

					// Map error codes to localized messages
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

					// Try to map server error to a specific field
					const fieldErrors = {
						'invalid_email': 'email',
						'invalid_name': 'first_name',
					};

					if ( fieldErrors[ result.code ] ) {
						showFieldError( fieldErrors[ result.code ], result.message || ccfSettings.error );
					} else {
						const errorMessage = errorMessages[ result.code ] || result.message || ccfSettings.error;
						responseDiv.textContent = errorMessage;
					}
				}
			} catch ( error ) {
				// Network error
				responseDiv.className = 'ccf-response error';
				responseDiv.textContent = ccfSettings.errorNetwork || 'Network error. Please check your connection.';
				console.error( 'CCF Form Error:', error );
			} finally {
				// Re-enable submit button
				submitButton.disabled = false;
				submitButton.textContent = originalText;

				// Scroll to response
				responseDiv.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		} );
	} );
} );
