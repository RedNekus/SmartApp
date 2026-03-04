<?php
/**
 * Plugin Name: Company Contact Form
 * Description: Contact form with Gutenberg, REST API, and HubSpot integration.
 * Version: 1.0.0
 * Author: Fighter Neko
 * License: GPL-2.0+
 * Text Domain: company-contact-form
 */

defined('ABSPATH') || exit;

define('CCF_VERSION', '1.0.0');
define('CCF_PATH', plugin_dir_path(__FILE__));
define('CCF_URL', plugin_dir_url(__FILE__));

// === АВТОЗАГРУЗКА КЛАССОВ ===
spl_autoload_register(function ($class) {
    $prefix = 'CCF\\';
    $base_dir = CCF_PATH . 'includes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    if (file_exists($file)) require $file;
});

register_activation_hook(__FILE__, ['CCF\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['CCF\Deactivator', 'deactivate']);

// === ИНИЦИАЛИЗАЦИЯ ===
function ccf_init() {
    load_plugin_textdomain('company-contact-form', false, dirname(plugin_basename(__FILE__)) . '/languages');
    CCF\API::register_routes();
}
add_action('plugins_loaded', 'ccf_init');

// === РЕГИСТРАЦИЯ БЛОКА (без editorScript из block.json) ===
function ccf_register_block() {
    register_block_type(__DIR__ . '/build/block.json', [
        'render_callback' => 'ccf_render_form',
    ]);
}
add_action('init', 'ccf_register_block');

// === РЕНДЕР ФОРМЫ ===
function ccf_render_form($attributes, $content) {
    ob_start();
    ?>
    <div class="ccf-form-wrapper">
        <form class="ccf-form" method="post">
            <p><label>First Name<br><input type="text" name="first_name" required></label></p>
            <p><label>Last Name<br><input type="text" name="last_name" required></label></p>
            <p><label>Email<br><input type="email" name="email" required></label></p>
            <p><label>Message<br><textarea name="message" rows="5" required></textarea></label></p>
            <p><button type="submit">Send</button></p>
            <div class="ccf-response"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// === ФРОНТЕНД СКРИПТЫ (только если блок есть на странице) ===
function ccf_enqueue_frontend() {
    if (!has_block('company/company-contact-form')) return;
    
    $asset_file = CCF_PATH . 'build/frontend.asset.php';
    if (!file_exists($asset_file)) return;
    $asset = require $asset_file;
    
    wp_enqueue_script(
        'ccf-frontend',
        CCF_URL . 'build/frontend.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
    
    wp_localize_script('ccf-frontend', 'ccfSettings', [
        'nonce' => wp_create_nonce('wp_rest'),
        'apiUrl' => rest_url('company/v1/contact'),
    ]);
    
    wp_enqueue_style(
        'ccf-style',
        CCF_URL . 'build/style-index.css',
        [],
        $asset['version']
    );
}
add_action('wp_enqueue_scripts', 'ccf_enqueue_frontend');

// === РЕДАКТОР СКРИПТЫ (с фиксом react-jsx-runtime) ===
function ccf_enqueue_editor_assets() {
    $asset_file = CCF_PATH . 'build/index.asset.php';
    if (!file_exists($asset_file)) return;
    $asset = require $asset_file;
    
    // 🔧 ФИКС: заменяем react-jsx-runtime на wp-element
    $deps = array_map(function($dep) {
        return $dep === 'react-jsx-runtime' ? 'wp-element' : $dep;
    }, $asset['dependencies']);
    
    // Регистрируем и enqueue-им зависимости
    foreach ($deps as $dep) {
        if (!wp_script_is($dep, 'registered')) {
            wp_register_script($dep, false, [], false, true);
        }
        wp_enqueue_script($dep);
    }
    
    // Выводим в footer после всех зависимостей
    add_action('admin_print_footer_scripts', function() use ($asset, $deps) {
        // Печатаем зависимости в правильном порядке
        wp_scripts()->do_items($deps);
        
        // Наш скрипт (JSX-runtime уже выполнен через wp-element)
        echo "\n<!-- CCF Editor Block -->\n";
        echo "<script src='" . esc_url(CCF_URL . 'build/index.js') . "?ver=" . esc_attr($asset['version']) . "'></script>\n";
        echo "<link rel='stylesheet' href='" . esc_url(CCF_URL . 'build/index.css') . "?ver=" . esc_attr($asset['version']) . "' />\n";
        echo "<!-- END CCF Editor Block -->\n";
    }, 999);
}
add_action('enqueue_block_editor_assets', 'ccf_enqueue_editor_assets');
