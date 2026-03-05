<?php
/**
 * PHPUnit bootstrap for Company Contact Form
 *
 * @package Company Contact Form
 */

// 1. Load Composer autoload (for PHPUnit/Polyfills)
$composer_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
    require_once $composer_autoload;
}

// 2. Locate WP Test Library
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
    echo "Could not find {$_tests_dir}/includes/functions.php\n";
    exit( 1 );
}

// 3. Give access to tests_add_filter()
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require dirname( __DIR__ ) . '/company-contact-form.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Load PHPUnit Polyfills BEFORE WordPress bootstrap.
 * Required for compatibility with WP test suite.
 */
$polyfills_path = dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( file_exists( $polyfills_path ) ) {
    require_once $polyfills_path;
}

// 4. Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
