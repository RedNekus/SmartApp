#!/usr/bin/env bash
set -e

MODE=${1:-inside-container}
PLUGIN_DIR="/var/www/html/wp-content/plugins/company-contact-form"
WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}

if [ "$MODE" = "inside-container" ]; then
    echo "🔧 Setting up test environment inside container..."
    
    # 1. Install Composer if missing
    if ! command -v composer &> /dev/null; then
        echo "📦 Installing Composer..."
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    fi
    
    # 2. Install svn for WordPress Test Library
    if ! command -v svn &> /dev/null; then
        echo "📦 Installing Subversion..."
        apt-get update -qq && apt-get install -y -qq subversion > /dev/null
    fi
    
    # 3. Install PHP dependencies
    echo "📦 Installing PHP dependencies..."
    cd "$PLUGIN_DIR"
    composer install --no-interaction --prefer-dist
    
    # 4. Download WordPress Test Library
    echo "📦 Downloading WordPress Test Library..."
    if [ ! -d "$WP_TESTS_DIR/includes" ]; then
        mkdir -p "$WP_TESTS_DIR"
        svn co --quiet https://develop.svn.wordpress.org/tags/6.5/tests/phpunit/includes/ "$WP_TESTS_DIR/includes"
        svn co --quiet https://develop.svn.wordpress.org/tags/6.5/tests/phpunit/data/ "$WP_TESTS_DIR/data"
    fi
    
    # 5. Create wp-tests-config.php
    echo "📝 Creating wp-tests-config.php..."
    cat > "$WP_TESTS_DIR/wp-tests-config.php" << 'CONFIGEOF'
<?php
define( 'ABSPATH', '/var/www/html/' );
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'wordpress' );
define( 'DB_PASSWORD', 'wordpress' );
define( 'DB_HOST', 'mysql' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
$table_prefix = 'wptests_';
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
CONFIGEOF
    
    # 6. Create test database
    echo "🗄️ Creating test database..."
    mysql -h mysql -u wordpress -pwordpress -e "CREATE DATABASE IF NOT EXISTS wordpress_test;" 2>/dev/null || true
    
    # 7. Fix permissions
    echo "🔐 Fixing permissions..."
    chown -R www-data:www-data "$PLUGIN_DIR/vendor" 2>/dev/null || true
    chmod -R 755 "$PLUGIN_DIR/vendor" 2>/dev/null || true
    
    echo "✅ Test environment ready!"
    echo "Run: composer test"
else
    echo "🐳 Running setup inside Docker container..."
    docker compose exec -u root wordpress bash "$PLUGIN_DIR/bin/setup-tests.sh" inside-container
fi
