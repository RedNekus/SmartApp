<?php
/**
 * Logger Class - Custom logging table with 30-day rotation
 *
 * @package Company Contact Form
 * @since 1.2.0
 */

namespace CCF;

class Logger {
    /**
     * Table name (without prefix)
     *
     * @var string
     */
    private static $table_name = 'ccf_logs';

    /**
     * Create logs table on plugin activation
     *
     * @return void
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            email varchar(255) NOT NULL,
            result varchar(50) NOT NULL,
            hubspot_id varchar(100) DEFAULT '',
            user_ip varchar(50) NOT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY timestamp (timestamp),
            KEY result (result)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Log a submission event
     *
     * @param string $email      Submitter email.
     * @param string $result     Result status (received, spam_blocked, hubspot_sent, etc).
     * @param string $hubspot_id HubSpot contact/form ID (optional).
     * @param string $user_ip    User IP address.
     * @return int|false Inserted log ID or false on failure.
     */
    public static function log( $email, $result, $hubspot_id = '', $user_ip = '' ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $inserted = $wpdb->insert(
            $table_name,
            [
                'email'      => sanitize_email( $email ),
                'result'     => sanitize_text_field( $result ),
                'hubspot_id' => sanitize_text_field( $hubspot_id ),
                'user_ip'    => sanitize_text_field( $user_ip ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );

        // Rotate: delete logs older than 30 days
        self::rotate_logs();

        if ( false === $inserted ) {
            error_log( '[CCF] Logger: Failed to insert log - ' . $wpdb->last_error );
            return false;
        }

        $log_id = $wpdb->insert_id;
        error_log( '[CCF] Logger: Log entry created with ID ' . $log_id );

        return $log_id;
    }

    /**
     * Delete logs older than 30 days (rotation)
     *
     * @return int|false Number of rows deleted or false on failure.
     */
    public static function rotate_logs() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )
        );

        if ( false !== $deleted && $deleted > 0 ) {
            error_log( '[CCF] Logger: Rotated ' . $deleted . ' old log entries' );
        }

        return $deleted;
    }

    /**
     * Get logs with pagination
     *
     * @param int $per_page Items per page.
     * @param int $page     Current page number.
     * @return array        Array of log entries.
     */
    public static function get_logs( $per_page = 50, $page = 1 ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;
        $offset     = ( $page - 1 ) * $per_page;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            'ARRAY_A'
        );
    }

    /**
     * Get total log count
     *
     * @return int Total number of log entries.
     */
    public static function get_logs_count() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    }

    /**
     * Clear all logs (for admin use)
     *
     * @return int|false Number of rows deleted or false on failure.
     */
    public static function clear_all() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        return $wpdb->query( "TRUNCATE TABLE $table_name" );
    }
}
