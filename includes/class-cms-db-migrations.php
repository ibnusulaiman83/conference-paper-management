<?php
/**
 * Database Migration Scripts
 * File: includes/class-cms-db-migrations.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_DB_Migrations {
    
    private $version = '1.0.0';
    private $option_name = 'cms_db_version';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->maybe_run_migrations();
    }
    
    /**
     * Check and run migrations if needed
     */
    public function maybe_run_migrations() {
        $installed_version = get_option($this->option_name, '0.0.0');
        
        if (version_compare($installed_version, $this->version, '<')) {
            $this->run_migrations($installed_version);
        }
    }
    
    /**
     * Run all necessary migrations
     */
    private function run_migrations($from_version) {
        global $wpdb;
        
        // Initial installation
        if ($from_version === '0.0.0') {
            $this->migration_initial_setup();
        }
        
        // Future migrations can be added here
        // if (version_compare($from_version, '1.1.0', '<')) {
        //     $this->migration_1_1_0();
        // }
        
        // Update version
        update_option($this->option_name, $this->version);
        
        // Log migration
        $this->log_migration($from_version, $this->version);
    }
    
    /**
     * Initial database setup
     */
    private function migration_initial_setup() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create custom tables if needed
        $sql = array();
        
        // Paper Reviews Table (optional - for future multi-reviewer feature)
        $table_reviews = $wpdb->prefix . 'cms_paper_reviews';
        $sql[] = "CREATE TABLE IF NOT EXISTS $table_reviews (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            paper_id bigint(20) unsigned NOT NULL,
            reviewer_id bigint(20) unsigned NOT NULL,
            rating int(2) NOT NULL DEFAULT 0,
            comments text,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY paper_id (paper_id),
            KEY reviewer_id (reviewer_id)
        ) $charset_collate;";
        
        // Payment Transactions Table
        $table_payments = $wpdb->prefix . 'cms_payment_transactions';
        $sql[] = "CREATE TABLE IF NOT EXISTS $table_payments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            paper_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            transaction_id varchar(255) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'MYR',
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) NOT NULL DEFAULT 'chip',
            payment_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY transaction_id (transaction_id),
            KEY paper_id (paper_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Activity Log Table
        $table_activity = $wpdb->prefix . 'cms_activity_log';
        $sql[] = "CREATE TABLE IF NOT EXISTS $table_activity (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            action varchar(50) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            description text,
            ip_address varchar(45),
            user_agent varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY object_type (object_type),
            KEY object_id (object_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Email Queue Table (for reliable email delivery)
        $table_email_queue = $wpdb->prefix . 'cms_email_queue';
        $sql[] = "CREATE TABLE IF NOT EXISTS $table_email_queue (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            message longtext NOT NULL,
            headers text,
            attachments text,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(3) NOT NULL DEFAULT 0,
            error_message text,
            scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY scheduled_at (scheduled_at)
        ) $charset_collate;";
        
        // Execute table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($sql as $query) {
            dbDelta($query);
        }
        
        // Create indexes for better performance
        $this->create_custom_indexes();
        
        // Insert default data
        $this->insert_default_data();
        
        // Set default options
        $this->set_default_options();
    }
    
    /**
     * Create custom indexes for performance
     */
    private function create_custom_indexes() {
        global $wpdb;
        
        // Add index to postmeta for faster queries
        $wpdb->query("
            CREATE INDEX IF NOT EXISTS idx_cms_paper_status 
            ON {$wpdb->postmeta} (meta_key, meta_value) 
            WHERE meta_key = 'paper_status'
        ");
        
        $wpdb->query("
            CREATE INDEX IF NOT EXISTS idx_cms_submit_date 
            ON {$wpdb->postmeta} (meta_key, meta_value) 
            WHERE meta_key = 'submit_date'
        ");
    }
    
    /**
     * Insert default data
     */
    private function insert_default_data() {
        // Create default paper categories
        $categories = array(
            'Technology',
            'Science',
            'Education',
            'Business',
            'Healthcare',
            'Engineering',
            'Social Sciences',
            'Arts & Humanities',
        );
        
        foreach ($categories as $category) {
            if (!term_exists($category, 'paper_category')) {
                wp_insert_term($category, 'paper_category');
            }
        }
        
        // Create default conference tracks
        $tracks = array(
            'Keynote Session',
            'Research Track',
            'Industry Track',
            'Poster Session',
            'Workshop',
            'Panel Discussion',
        );
        
        foreach ($tracks as $track) {
            if (!term_exists($track, 'paper_track')) {
                wp_insert_term($track, 'paper_track');
            }
        }
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'cms_payment_amount' => 300.00,
            'cms_conference_name' => 'International Conference 2025',
            'cms_notification_email' => get_option('admin_email'),
            'cms_paper_submission_enabled' => true,
            'cms_max_file_size' => 10, // MB
            'cms_allowed_file_types' => array('pdf'),
            'cms_enable_email_notifications' => true,
            'cms_enable_payment_gateway' => true,
            'cms_timezone' => 'Asia/Kuala_Lumpur',
            'cms_date_format' => 'Y-m-d H:i:s',
            'cms_papers_per_page' => 20,
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Log migration execution
     */
    private function log_migration($from_version, $to_version) {
        $log_entry = array(
            'from_version' => $from_version,
            'to_version' => $to_version,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
        );
        
        $logs = get_option('cms_migration_logs', array());
        $logs[] = $log_entry;
        update_option('cms_migration_logs', $logs);
        
        error_log(sprintf(
            'CMS Migration: %s -> %s completed at %s',
            $from_version,
            $to_version,
            $log_entry['timestamp']
        ));
    }
    
    /**
     * Rollback to previous version (use with caution!)
     */
    public function rollback() {
        // This is a dangerous operation and should only be used in development
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return new WP_Error('rollback_disabled', 'Rollback is only available in debug mode');
        }
        
        global $wpdb;
        
        // Drop custom tables
        $tables = array(
            $wpdb->prefix . 'cms_paper_reviews',
            $wpdb->prefix . 'cms_payment_transactions',
            $wpdb->prefix . 'cms_activity_log',
            $wpdb->prefix . 'cms_email_queue',
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Reset version
        delete_option($this->option_name);
        
        return true;
    }
    
    /**
     * Get migration history
     */
    public function get_migration_history() {
        return get_option('cms_migration_logs', array());
    }
    
    /**
     * Check database health
     */
    public function check_database_health() {
        global $wpdb;
        
        $health = array(
            'status' => 'healthy',
            'checks' => array(),
        );
        
        // Check if custom tables exist
        $required_tables = array(
            $wpdb->prefix . 'cms_paper_reviews',
            $wpdb->prefix . 'cms_payment_transactions',
            $wpdb->prefix . 'cms_activity_log',
            $wpdb->prefix . 'cms_email_queue',
        );
        
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            
            $health['checks'][$table] = array(
                'exists' => ($exists === $table),
                'rows' => $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0,
            );
            
            if (!$exists) {
                $health['status'] = 'unhealthy';
            }
        }
        
        // Check for orphaned records
        $orphaned_meta = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
            AND pm.meta_key LIKE 'cms_%'
        ");
        
        $health['checks']['orphaned_meta'] = array(
            'count' => (int) $orphaned_meta,
            'status' => $orphaned_meta > 0 ? 'warning' : 'ok',
        );
        
        return $health;
    }
    
    /**
     * Clean orphaned data
     */
    public function clean_orphaned_data() {
        global $wpdb;
        
        // Clean orphaned postmeta
        $deleted = $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
            AND pm.meta_key LIKE 'cms_%'
        ");
        
        return array(
            'deleted_meta' => $deleted,
            'message' => sprintf('Cleaned %d orphaned records', $deleted),
        );
    }
    
    /**
     * Optimize database tables
     */
    public function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'cms_paper_reviews',
            $wpdb->prefix . 'cms_payment_transactions',
            $wpdb->prefix . 'cms_activity_log',
            $wpdb->prefix . 'cms_email_queue',
        );
        
        $results = array();
        
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE $table");
            $results[$table] = $result !== false ? 'optimized' : 'failed';
        }
        
        return $results;
    }
    
    /**
     * Export database structure
     */
    public function export_structure() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'cms_paper_reviews',
            $wpdb->prefix . 'cms_payment_transactions',
            $wpdb->prefix . 'cms_activity_log',
            $wpdb->prefix . 'cms_email_queue',
        );
        
        $export = array();
        
        foreach ($tables as $table) {
            $export[$table] = $wpdb->get_var("SHOW CREATE TABLE $table", 1);
        }
        
        return $export;
    }
    
    /**
     * Get database statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = array(
            'total_papers' => $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->posts} 
                WHERE post_type = 'conference_paper' 
                AND post_status = 'publish'
            "),
            'total_participants' => $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%participant%'
            "),
            'total_payments' => $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}cms_payment_transactions
                WHERE status = 'completed'
            "),
            'total_revenue' => $wpdb->get_var("
                SELECT SUM(amount) 
                FROM {$wpdb->prefix}cms_payment_transactions
                WHERE status = 'completed'
            "),
        );
        
        return $stats;
    }
}

// Initialize migrations on plugin activation
function cms_run_migrations() {
    $migrations = new CMS_DB_Migrations();
    $migrations->maybe_run_migrations();
}

register_activation_hook(CMS_PLUGIN_BASENAME, 'cms_run_migrations');

// Add admin notice if migration is needed
add_action('admin_notices', function() {
    $version = get_option('cms_db_version', '0.0.0');
    $required_version = '1.0.0';
    
    if (version_compare($version, $required_version, '<')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Conference Management System:</strong> 
                Database migration required. 
                <a href="<?php echo admin_url('admin.php?page=conference-management&action=migrate'); ?>">
                    Run Migration Now
                </a>
            </p>
        </div>
        <?php
    }
});