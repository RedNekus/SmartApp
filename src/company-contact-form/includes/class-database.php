<?php
namespace CCF;

class Database {
    private static $table_name = 'ccf_submissions';
    
    /**
     * Создание таблицы при активации плагина
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            subject varchar(255) DEFAULT '',
            message text NOT NULL,
            ip_address varchar(50) NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY submitted_at (submitted_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Сохранение заявки в БД
     */
    public static function save_submission($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $result = $wpdb->insert(
            $table_name,
            [
                'first_name' => sanitize_text_field($data['first_name']),
                'last_name'  => sanitize_text_field($data['last_name']),
                'email'      => sanitize_email($data['email']),
                'subject'    => sanitize_text_field($data['subject']),
                'message'    => sanitize_textarea_field($data['message']),
                'ip_address' => sanitize_text_field($data['ip']),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[CCF] Database: Failed to save submission - ' . $wpdb->last_error);
            return false;
        }
        
        $submission_id = $wpdb->insert_id;
        error_log('[CCF] Database: Submission saved with ID ' . $submission_id);
        
        return $submission_id;
    }
    
    /**
     * Получение всех заявок (для админки)
     */
    public static function get_submissions($per_page = 20, $page = 1) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $offset = ($page - 1) * $per_page;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }
    
    /**
     * Подсчёт общего количества заявок
     */
    public static function get_submissions_count() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    /**
     * Удаление заявки по ID
     */
    public static function delete_submission($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->delete($table_name, ['id' => $id], ['%d']);
    }
    
    /**
     * Экспорт заявок в CSV
     */
    public static function export_to_csv() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC", ARRAY_A);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ccf-submissions-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Заголовки
        fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Subject', 'Message', 'IP Address', 'Submitted At']);
        
        // Данные
        foreach ($submissions as $submission) {
            fputcsv($output, $submission);
        }
        
        fclose($output);
        exit;
    }
}