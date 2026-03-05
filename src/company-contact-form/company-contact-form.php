<?php
/**
 * Plugin Name: Company Contact Form
 * Description: Contact form with Gutenberg, REST API, and HubSpot integration.
 * Version: 1.0.0
 * Author: Fighter Neko
 * License: GPL-2.0+
 * Text Domain: company-contact-form
 */

defined( 'ABSPATH' ) || exit;

define( 'CCF_VERSION', '1.0.0' );
define( 'CCF_PATH', plugin_dir_path( __FILE__ ) );
define( 'CCF_URL', plugin_dir_url( __FILE__ ) );

/**
 * ------------------------------------------------------------------------
 * Autoloader
 * ------------------------------------------------------------------------
 */
spl_autoload_register(
    static function ( $class ) {
        $prefix   = 'CCF\\';
        $base_dir = CCF_PATH . 'includes/';

        if ( strpos( $class, $prefix ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, strlen( $prefix ) );
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
 * Activation / Deactivation
 * ------------------------------------------------------------------------
 */
register_activation_hook( __FILE__, [ 'CCF\\Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'CCF\\Deactivator', 'deactivate' ] );

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
        [
            'render_callback' => 'ccf_render_form',
        ]
    );
}
add_action( 'init', 'ccf_register_block' );

/**
 * ------------------------------------------------------------------------
 * Server-side render
 * ------------------------------------------------------------------------
 */
function ccf_render_form( $attributes, $content ) {
    ob_start();
    ?>
    <div class="ccf-form-wrapper">
        <form class="ccf-form" method="post" novalidate>
            <p>
                <label>
                    <?php esc_html_e( 'First Name', 'company-contact-form' ); ?><br>
                    <input type="text" name="first_name" required>
                </label>
            </p>

            <p>
                <label>
                    <?php esc_html_e( 'Last Name', 'company-contact-form' ); ?><br>
                    <input type="text" name="last_name" required>
                </label>
            </p>

            <p>
                <label>
                    <?php esc_html_e( 'Email', 'company-contact-form' ); ?><br>
                    <input type="email" name="email" required>
                </label>
            </p>

            <p>
                <label>
                    <?php esc_html_e( 'Message', 'company-contact-form' ); ?><br>
                    <textarea name="message" rows="5" required></textarea>
                </label>
            </p>

            <p>
                <button type="submit">
                    <?php esc_html_e( 'Send', 'company-contact-form' ); ?>
                </button>
            </p>

            <div class="ccf-response" aria-live="polite"></div>
            <input type="hidden" name="ccf_block_attributes" value="<?php echo esc_attr(json_encode($attributes)); ?>">
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * ------------------------------------------------------------------------
 * Frontend assets (only if block exists)
 * ------------------------------------------------------------------------
 */
function ccf_enqueue_frontend_assets() {
    if ( ! has_block( 'company/company-contact-form' ) ) {
        return;
    }

    $asset_file = CCF_PATH . 'build/index.asset.php';
    if ( ! file_exists( $asset_file ) ) {
        return;
    }

    $asset = require $asset_file;

    wp_enqueue_script(
        'ccf-block',
        CCF_URL . 'build/index.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );

    wp_localize_script(
        'ccf-block',
        'ccfSettings',
        [
            'nonce'  => wp_create_nonce( 'wp_rest' ),
            'apiUrl' => rest_url( 'company/v1/contact' ),
        ]
    );

    wp_enqueue_style(
        'ccf-style',
        CCF_URL . 'build/style-index.css',
        [],
        $asset['version']
    );
}
add_action( 'wp_enqueue_scripts', 'ccf_enqueue_frontend_assets' );

/**
 * ------------------------------------------------------------------------
 * Editor assets
 * ------------------------------------------------------------------------
 */
function ccf_enqueue_editor_assets() {
    $asset_file = CCF_PATH . 'build/index.asset.php';
    if ( ! file_exists( $asset_file ) ) {
        return;
    }

    $asset = require $asset_file;

    wp_enqueue_script(
        'ccf-editor',
        CCF_URL . 'build/index.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );

    wp_enqueue_style(
        'ccf-editor-style',
        CCF_URL . 'build/style-index.css',
        [],
        $asset['version']
    );
}
add_action( 'enqueue_block_editor_assets', 'ccf_enqueue_editor_assets' );

/**
 * SMTP Configuration for MailHog - Fixed From address
 */
if ( defined( 'SMTP_HOST' ) && function_exists( 'add_filter' ) ) {
    add_filter( 'wp_mail_use_phpmailer', '__return_true' );
    
    add_action( 'phpmailer_init', function( $phpmailer ) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = SMTP_HOST;
        $phpmailer->Port       = SMTP_PORT;
        $phpmailer->SMTPAuth   = false;
        $phpmailer->SMTPSecure = '';
        
        // ✅ Используем реальный admin_email WordPress
        $admin_email = get_option('admin_email');
        $site_name   = get_bloginfo('name');
        
        $phpmailer->From     = is_email($admin_email) ? $admin_email : 'noreply@localhost.local';
        $phpmailer->FromName = $site_name ?: 'WordPress';
        
        error_log('[CCF] SMTP: Configured From=' . $phpmailer->From);
    }, 1);
}
