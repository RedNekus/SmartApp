<?php
/**
 * Plugin Name: Company Contact Form
 * Description: Contact form with Gutenberg, REST API, HubSpot integration, and database storage.
 * Version: 1.2.0
 * Author: Fighter Neko
 * License: GPL-2.0+
 * Text Domain: company-contact-form
 *
 * @package Company Contact Form
 */

defined( 'ABSPATH' ) || exit;

// Для разработки — отключаем кэш
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	define( 'CCF_VERSION', time() );  // Версия = текущее время
} else {
	define( 'CCF_VERSION', '1.2.0' );
}
define( 'CCF_PATH', plugin_dir_path( __FILE__ ) );
define( 'CCF_URL', plugin_dir_url( __FILE__ ) );

/**
 * ------------------------------------------------------------------------
 * Autoloader
 * ------------------------------------------------------------------------
 */
spl_autoload_register(
	static function ( $class_name ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound
		$prefix   = 'CCF\\';
		$base_dir = CCF_PATH . 'includes/';

		if ( strpos( $class_name, $prefix ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $prefix ) );
		$file           = $base_dir . 'class-' . strtolower(
			str_replace( '_', '-', $relative_class )
		) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * ------------------------------------------------------------------------
 * Load configuration from .env file (ТЕПЕРЬ класс точно загрузится)
 * ------------------------------------------------------------------------
 */
if ( class_exists( 'CCF\\Config' ) ) {
	CCF\Config::load();
}

/**
 * ------------------------------------------------------------------------
 * WP-CLI Commands
 * ------------------------------------------------------------------------
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'company-contact', 'CCF\\CLI' );
}

/**
 * ------------------------------------------------------------------------
 * Activation / Deactivation
 * ------------------------------------------------------------------------
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( class_exists( 'CCF\\Database' ) ) {
			CCF\Database::create_table();
		}
		if ( class_exists( 'CCF\\Logger' ) ) {
			CCF\Logger::create_table();
		}
		if ( class_exists( 'CCF\\Activator' ) ) {
			CCF\Activator::activate();
		}
	}
);

register_deactivation_hook( __FILE__, array( 'CCF\\Deactivator', 'deactivate' ) );

/**
 * ------------------------------------------------------------------------
 * Init
 * ------------------------------------------------------------------------
 */
function ccf_init() {
	load_plugin_textdomain(
		'company-contact-form',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	if ( class_exists( 'CCF\\API' ) ) {
		CCF\API::register_routes();
	}

	if ( is_admin() && class_exists( 'CCF\\Admin' ) ) {
		CCF\Admin::init();
	}
}
add_action( 'plugins_loaded', 'ccf_init' );

/**
 * ------------------------------------------------------------------------
 * Block registration
 * ------------------------------------------------------------------------
 */
function ccf_register_block() {
	register_block_type(
		__DIR__ . '/build/block.json',
		array(
			'render_callback' => 'ccf_render_form',
		)
	);
}
add_action( 'init', 'ccf_register_block' );

/**
 * ------------------------------------------------------------------------
 * Server-side render
 * ------------------------------------------------------------------------
 */
function ccf_render_form( $attributes, $_content ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	// Prepare template variables.
	$template_vars = array(
		'attributes' => $attributes,
		'nonce'      => wp_create_nonce( 'wp_rest' ),
	);

	// Get block wrapper attributes (includes class="wp-block-company-company-contact-form")
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'ccf-form-wrapper',
		)
	);

	// Start output buffering.
	ob_start();

	// Load template with extracted variables.
    // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( $template_vars, EXTR_SKIP );
	include __DIR__ . '/templates/frontend/form.php';

	$form_content = ob_get_clean();

	// Wrap with block wrapper (adds the block class!)
	return sprintf( '<div %s>%s</div>', $wrapper_attributes, $form_content );
}

/**
 * ------------------------------------------------------------------------
 * Frontend assets (ONLY for public site, when block is present)
 * ------------------------------------------------------------------------
 */
function ccf_enqueue_frontend_assets() {
	// Load only if our block is on the page.
	if ( ! has_block( 'company/company-contact-form' ) ) {
		return;
	}

	// Frontend form script (from assets/js/).
	wp_enqueue_script(
		'ccf-frontend-js',
		CCF_URL . 'assets/js/index.js',
		array(),
		CCF_VERSION,
		true
	);

	// Pass settings to frontend script.
	wp_localize_script(
		'ccf-frontend-js',  // ← Привязываем к правильному хендлу
		'ccfSettings',
		array(
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'apiUrl'  => rest_url( 'company/v1/contact' ),
			'sending' => __( 'Sending...', 'company-contact-form' ),
			'success' => __( 'Message sent successfully!', 'company-contact-form' ),
			'error'   => __( 'An error occurred. Please try again.', 'company-contact-form' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ccf_enqueue_frontend_assets' );

/**
 * ------------------------------------------------------------------------
 * Editor assets (ONLY for Gutenberg editor)
 * ------------------------------------------------------------------------
 */
function ccf_enqueue_editor_assets() {
	$asset_file = CCF_PATH . 'build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	// Editor script (built by @wordpress/scripts).
	wp_enqueue_script(
		'ccf-editor-js',
		CCF_URL . 'build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'ccf_enqueue_editor_assets' );

/**
 * ------------------------------------------------------------------------
 * SMTP Configuration for MailHog / Production SMTP
 * ------------------------------------------------------------------------
 */
$smtp_host = \CCF\Config::get( 'SMTP_HOST' );
if ( $smtp_host && function_exists( 'add_filter' ) ) {
	add_filter( 'wp_mail_use_phpmailer', '__return_true' );

	add_action(
		'phpmailer_init',
		function ( $phpmailer ) {
			$phpmailer->isSMTP();
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Host       = \CCF\Config::get( 'SMTP_HOST' );
			$phpmailer->Port       = \CCF\Config::get_int( 'SMTP_PORT', 587 );
			$phpmailer->SMTPAuth   = false;
			$phpmailer->SMTPSecure = '';
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			$admin_email = get_option( 'admin_email' );
			$site_name   = get_bloginfo( 'name' );

			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->From     = is_email( $admin_email ) ? $admin_email : 'noreply@localhost.local';
			$phpmailer->FromName = \CCF\Config::get( 'SMTP_FROM_NAME', $site_name ?: 'WordPress' );
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[CCF] SMTP: Configured From=' . $phpmailer->From );
		},
		1
	);
}
