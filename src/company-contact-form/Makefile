# Company Contact Form - Development Tasks

.PHONY: setup test lint lint-fix clean

setup:
	docker compose exec -u root wordpress bash /var/www/html/wp-content/plugins/company-contact-form/bin/setup-tests.sh inside-container

test:
	docker compose exec -u www-data wordpress bash -c "\
		cd /var/www/html/wp-content/plugins/company-contact-form && \
		./vendor/bin/phpunit --colors=always"

# PHPCS lint (read-only, www-data ok)
lint:
	docker compose exec -u www-data wordpress bash -c "\
		cd /var/www/html/wp-content/plugins/company-contact-form && \
		./vendor/bin/phpcs ."

# PHPCBF fix (needs write access, run as root)
lint-fix:
	docker compose exec -u root wordpress bash -c "\
		cd /var/www/html/wp-content/plugins/company-contact-form && \
		./vendor/bin/phpcbf . && \
		chown -R 1000:1000 . 2>/dev/null || true"

clean:
	docker compose exec -u www-data wordpress bash -c "\
		rm -rf /var/www/html/wp-content/plugins/company-contact-form/vendor && \
		rm -f /var/www/html/wp-content/plugins/company-contact-form/composer.lock"
