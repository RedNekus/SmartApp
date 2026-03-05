<?php
namespace CCF;

class Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_page']);
    }
    
    public static function add_admin_page() {
        add_menu_page(
            __('Contact Form Submissions', 'company-contact-form'),
            __('Contact Form', 'company-contact-form'),
            'manage_options',
            'ccf-submissions',
            [__CLASS__, 'render_page'],
            'dashicons-feedback',
            30
        );
    }
    
    public static function render_page() {
        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $total = Database::get_submissions_count();
        $submissions = Database::get_submissions($per_page, $page);
        $total_pages = ceil($total / $per_page);
        
        // Обработка удаления
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            Database::delete_submission(intval($_GET['id']));
            echo '<div class="notice notice-success"><p>' . __('Submission deleted', 'company-contact-form') . '</p></div>';
        }
        
        // Обработка экспорта
        if (isset($_GET['action']) && $_GET['action'] === 'export') {
            Database::export_to_csv();
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Contact Form Submissions', 'company-contact-form'); ?></h1>
            
            <a href="?page=ccf-submissions&action=export" class="page-title-action">
                <?php _e('Export CSV', 'company-contact-form'); ?>
            </a>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'company-contact-form'); ?></th>
                        <th><?php _e('Name', 'company-contact-form'); ?></th>
                        <th><?php _e('Email', 'company-contact-form'); ?></th>
                        <th><?php _e('Subject', 'company-contact-form'); ?></th>
                        <th><?php _e('Message', 'company-contact-form'); ?></th>
                        <th><?php _e('IP', 'company-contact-form'); ?></th>
                        <th><?php _e('Date', 'company-contact-form'); ?></th>
                        <th><?php _e('Actions', 'company-contact-form'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="8"><?php _e('No submissions yet', 'company-contact-form'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?php echo esc_html($sub['id']); ?></td>
                                <td><?php echo esc_html($sub['first_name'] . ' ' . $sub['last_name']); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($sub['email']); ?>"><?php echo esc_html($sub['email']); ?></a></td>
                                <td><?php echo esc_html($sub['subject']); ?></td>
                                <td><?php echo esc_html(wp_trim_words($sub['message'], 10)); ?></td>
                                <td><?php echo esc_html($sub['ip_address']); ?></td>
                                <td><?php echo esc_html($sub['submitted_at']); ?></td>
                                <td>
                                    <a href="?page=ccf-submissions&action=delete&id=<?php echo $sub['id']; ?>" 
                                       onclick="return confirm('Delete this submission?')"
                                       class="button button-small button-link-delete">
                                        <?php _e('Delete', 'company-contact-form'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('«'),
                            'next_text' => __('»'),
                            'total' => $total_pages,
                            'current' => $page,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
