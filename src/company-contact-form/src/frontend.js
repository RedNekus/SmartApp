document.addEventListener('DOMContentLoaded', function () {
	const forms = document.querySelectorAll('.ccf-form');
	
	forms.forEach(form => {
		// 1. Записываем время начала заполнения (time-trap)
		const startTimeInput = form.querySelector('.ccf-start-time');
		if (startTimeInput) {
			startTimeInput.value = Math.floor(Date.now() / 1000);
		}
		
		// 2. Обработка отправки
		form.addEventListener('submit', async function (e) {
			e.preventDefault();
			
			const submitBtn = form.querySelector('.ccf-submit');
			const responseDiv = form.querySelector('.ccf-response');
			const nonceInput = form.querySelector('.ccf-nonce');
			
			// Собираем данные
			const formData = new FormData(form);
			const data = Object.fromEntries(formData.entries());
			
			// UI: состояние загрузки
			submitBtn.disabled = true;
			submitBtn.textContent = 'Sending...';
			responseDiv.style.display = 'block';
			responseDiv.textContent = '';
			responseDiv.className = 'ccf-response loading';
			
			try {
				const response = await fetch('/wp-json/company/v1/contact', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonceInput.value || window.ccfSettings?.nonce || '',
					},
					body: JSON.stringify(data),
				});
				
				const result = await response.json();
				
				if (response.ok && result.success) {
					responseDiv.className = 'ccf-response success';
					responseDiv.textContent = result.message || 'Message sent!';
					form.reset();
					// Сбрасываем time-trap для повторной отправки
					if (startTimeInput) {
						startTimeInput.value = Math.floor(Date.now() / 1000);
					}
				} else {
					// Ошибка валидации или антиспама
					responseDiv.className = 'ccf-response error';
					responseDiv.textContent = result.message || 'Something went wrong';
				}
			} catch (error) {
				console.error('CCF Error:', error);
				responseDiv.className = 'ccf-response error';
				responseDiv.textContent = 'Network error. Please try again.';
			} finally {
				submitBtn.disabled = false;
				submitBtn.textContent = 'Send Message';
			}
		});
	});
});
