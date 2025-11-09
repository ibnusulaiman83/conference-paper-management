<?php
/**
 * Plugin Name: Conference Paper Management System
 * Plugin URI: https://example.com/conference-management
 * Description: Sistem pengurusan conference paper dengan role-based access untuk Administrator, Conference Manager, dan Participant
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: conference-paper
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CPM_VERSION', '1.0.0');
define('CPM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPM_PLUGIN_URL', plugin_dir_url(__FILE__));

class ConferencePaperManagement {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Activation & Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // Include required files
        $this->include_files();
    }
    
    public function activate() {
        // Create custom roles
        $this->create_custom_roles();
        
        // Create custom post type
        $this->register_post_types();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create default pages
        $this->create_default_pages();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function init() {
        // Register custom post types
        $this->register_post_types();
        
        // Register custom taxonomies
        $this->register_taxonomies();
        
        // Load text domain
        load_plugin_textdomain('conference-paper', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function include_files() {
        // Core files
        require_once CPM_PLUGIN_DIR . 'includes/class-registration.php';
        require_once CPM_PLUGIN_DIR . 'includes/class-paper-submission.php';
        require_once CPM_PLUGIN_DIR . 'includes/class-paper-review.php';
        require_once CPM_PLUGIN_DIR . 'includes/class-payment.php';
        require_once CPM_PLUGIN_DIR . 'includes/class-email-notifications.php';
        require_once CPM_PLUGIN_DIR . 'includes/class-dashboard.php';
        
        // Initialize classes
        new CPM_Registration();
        new CPM_Paper_Submission();
        new CPM_Paper_Review();
        new CPM_Payment();
        new CPM_Email_Notifications();
        new CPM_Dashboard();
    }
    
    private function create_custom_roles() {
        // Remove existing roles if they exist
        remove_role('conference_manager');
        remove_role('participant');
        
        // Conference Manager Role
        add_role('conference_manager', 'Conference Manager', array(
            'read' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => false,
            'edit_conference_papers' => true,
            'edit_others_conference_papers' => true,
            'publish_conference_papers' => true,
            'read_private_conference_papers' => true,
            'manage_conference_papers' => true,
        ));
        
        // Participant Role
        add_role('participant', 'Participant', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'submit_conference_paper' => true,
            'edit_own_conference_paper' => true,
            'view_own_conference_paper' => true,
        ));
        
        // Add capabilities to Administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_conference_papers');
            $admin->add_cap('edit_conference_papers');
            $admin->add_cap('edit_others_conference_papers');
            $admin->add_cap('publish_conference_papers');
            $admin->add_cap('read_private_conference_papers');
            $admin->add_cap('delete_conference_papers');
            $admin->add_cap('delete_others_conference_papers');
        }
    }
    
    public function register_post_types() {
        // Conference Paper Post Type
        $labels = array(
            'name' => 'Conference Papers',
            'singular_name' => 'Conference Paper',
            'menu_name' => 'Conference Papers',
            'add_new' => 'Tambah Baru',
            'add_new_item' => 'Tambah Conference Paper Baru',
            'edit_item' => 'Edit Conference Paper',
            'new_item' => 'Conference Paper Baru',
            'view_item' => 'Lihat Conference Paper',
            'search_items' => 'Cari Conference Papers',
            'not_found' => 'Tiada conference paper dijumpai',
            'not_found_in_trash' => 'Tiada conference paper dalam trash',
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'author', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => array('slug' => 'conference-paper'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-media-document',
        );
        
        register_post_type('conference_paper', $args);
        
        // Payment Post Type
        $payment_labels = array(
            'name' => 'Payments',
            'singular_name' => 'Payment',
            'menu_name' => 'Payments',
        );
        
        $payment_args = array(
            'labels' => $payment_labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'custom-fields'),
            'has_archive' => false,
            'show_in_rest' => false,
        );
        
        register_post_type('cpm_payment', $payment_args);
    }
    
    public function register_taxonomies() {
        // Paper Status Taxonomy
        register_taxonomy('paper_status', 'conference_paper', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Paper Status',
                'singular_name' => 'Status',
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'paper-status'),
        ));
        
        // Create default terms
        if (!term_exists('pending', 'paper_status')) {
            wp_insert_term('Pending Review', 'paper_status', array('slug' => 'pending'));
            wp_insert_term('Under Review', 'paper_status', array('slug' => 'under-review'));
            wp_insert_term('Accepted', 'paper_status', array('slug' => 'accepted'));
            wp_insert_term('Rejected', 'paper_status', array('slug' => 'rejected'));
            wp_insert_term('Payment Pending', 'paper_status', array('slug' => 'payment-pending'));
            wp_insert_term('Completed', 'paper_status', array('slug' => 'completed'));
        }
    }
    
    private function create_default_pages() {
        // Registration Page
        $registration_page = array(
            'post_title' => 'Pendaftaran Peserta',
            'post_content' => '[cpm_registration_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'pendaftaran-peserta',
        );
        
        if (!get_page_by_path('pendaftaran-peserta')) {
            wp_insert_post($registration_page);
        }
        
        // Paper Submission Page
        $submission_page = array(
            'post_title' => 'Hantar Conference Paper',
            'post_content' => '[cpm_paper_submission_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'hantar-paper',
        );
        
        if (!get_page_by_path('hantar-paper')) {
            wp_insert_post($submission_page);
        }
        
        // Payment Page
        $payment_page = array(
            'post_title' => 'Pembayaran',
            'post_content' => '[cpm_payment_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'pembayaran',
        );
        
        if (!get_page_by_path('pembayaran')) {
            wp_insert_post($payment_page);
        }
        
        // Dashboard Page
        $dashboard_page = array(
            'post_title' => 'Dashboard Peserta',
            'post_content' => '[cpm_participant_dashboard]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'dashboard-peserta',
        );
        
        if (!get_page_by_path('dashboard-peserta')) {
            wp_insert_post($dashboard_page);
        }
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Conference Management',
            'Conference',
            'manage_options',
            'conference-management',
            array($this, 'admin_dashboard_page'),
            'dashicons-groups',
            30
        );
        
        // Submenu items
        add_submenu_page(
            'conference-management',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'conference-management',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'conference-management',
            'Conference Papers',
            'Conference Papers',
            'edit_conference_papers',
            'edit.php?post_type=conference_paper'
        );
        
        add_submenu_page(
            'conference-management',
            'Payments',
            'Payments',
            'manage_options',
            'conference-payments',
            array($this, 'admin_payments_page')
        );
        
        add_submenu_page(
            'conference-management',
            'Participants',
            'Participants',
            'manage_options',
            'conference-participants',
            array($this, 'admin_participants_page')
        );
        
        add_submenu_page(
            'conference-management',
            'Settings',
            'Settings',
            'manage_options',
            'conference-settings',
            array($this, 'admin_settings_page')
        );
    }
    
    public function admin_dashboard_page() {
        include CPM_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    public function admin_payments_page() {
        include CPM_PLUGIN_DIR . 'admin/views/payments.php';
    }
    
    public function admin_participants_page() {
        include CPM_PLUGIN_DIR . 'admin/views/participants.php';
    }
    
    public function admin_settings_page() {
        include CPM_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    public function enqueue_admin_scripts($hook) {
        wp_enqueue_style('cpm-admin-style', CPM_PLUGIN_URL . 'admin/css/admin-style.css', array(), CPM_VERSION);
        wp_enqueue_script('cpm-admin-script', CPM_PLUGIN_URL . 'admin/js/admin-script.js', array('jquery'), CPM_VERSION, true);
        
        wp_localize_script('cpm-admin-script', 'cpmAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cpm_admin_nonce'),
        ));
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('cpm-frontend-style', CPM_PLUGIN_URL . 'public/css/frontend-style.css', array(), CPM_VERSION);
        wp_enqueue_script('cpm-frontend-script', CPM_PLUGIN_URL . 'public/js/frontend-script.js', array('jquery'), CPM_VERSION, true);
        
        wp_localize_script('cpm-frontend-script', 'cpmFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cpm_frontend_nonce'),
        ));
    }
}

// Initialize plugin
function cpm_init() {
    return ConferencePaperManagement::get_instance();
}

// Start the plugin
cpm_init();