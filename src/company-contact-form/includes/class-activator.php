<?php
namespace CCF;

class Activator {
    public static function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'company_contact_logs';
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            email varchar(255) NOT NULL,
            result varchar(50) NOT NULL,
            hubspot_id varchar(100) DEFAULT NULL,
            user_ip varchar(45) DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_timestamp (timestamp)
        ) $charset;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if (!wp_next_scheduled('ccf_rotate_logs')) {
            wp_schedule_event(time(), 'daily', 'ccf_rotate_logs');
        }
        flush_rewrite_rules();
    }
}
