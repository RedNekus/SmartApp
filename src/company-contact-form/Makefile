# Company Contact Form - Development Tasks

.PHONY: setup test lint lint-fix clean

# Setup test environment (run once after docker compose up)
setup:
	docker compose exec -u root wordpress bash /var/www/html/wp-content/plugins/company-contact-form/bin/setup-tests.sh inside-container

# Run PHPUnit tests (native WP_UnitTestCase)
test:
	docker compose exec -u www-data wordpress \
		/var/www/html/wp-content/plugins/company-contact-form/vendor/bin/phpunit \
		--configuration /var/www/html/wp-content/plugins/company-contact-form/phpunit.xml.dist \
		--colors=always

# Run PHPCS linting
lint:
	docker compose exec -u www-data wordpress \
		/var/www/html/wp-content/plugins/company-contact-form/vendor/bin/phpcs \
		--standard=/var/www/html/wp-content/plugins/company-contact-form/phpcs.xml.dist \
		/var/www/html/wp-content/plugins/company-contact-form

# Auto-fix PHPCS issues
lint-fix:
	docker compose exec -u www-data wordpress \
		/var/www/html/wp-content/plugins/company-contact-form/vendor/bin/phpcbf \
		--standard=/var/www/html/wp-content/plugins/company-contact-form/phpcs.xml.dist \
		/var/www/html/wp-content/plugins/company-contact-form

# Clean up
clean:
	docker compose exec -u www-data wordpress bash -c "\
		rm -rf /var/www/html/wp-content/plugins/company-contact-form/vendor && \
		rm -f /var/www/html/wp-content/plugins/company-contact-form/composer.lock"
