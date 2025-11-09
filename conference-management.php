<?php
/**
 * Plugin Name: Conference Management System
 * Plugin URI: https://example.com/conference-management
 * Description: Sistem pengurusan conference paper dengan integrasi Fluent Forms, ACF dan CHIP Payment Gateway
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: conference-management
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CMS_VERSION', '1.0.0');
define('CMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Conference Management System Class
 */
class Conference_Management_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'register_custom_roles'));
        add_action('init', array($this, 'register_post_types'));
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_cms_update_paper_status', array($this, 'ajax_update_paper_status'));
        add_action('wp_ajax_cms_process_payment', array($this, 'ajax_process_payment'));
    }
    
    private function load_dependencies() {
        require_once CMS_PLUGIN_DIR . 'includes/class-cms-roles.php';
        require_once CMS_PLUGIN_DIR . 'includes/class-cms-post-types.php';
        require_once CMS_PLUGIN_DIR . 'includes/class-cms-email.php';
        require_once CMS_PLUGIN_DIR . 'includes/class-cms-payment.php';
        require_once CMS_PLUGIN_DIR . 'includes/class-cms-dashboard.php';
        require_once CMS_PLUGIN_DIR . 'includes/class-cms-shortcodes.php';
    }
    
    public function activate() {
        $this->register_custom_roles();
        $this->register_post_types();
        flush_rewrite_rules();
        
        // Create default pages
        $this->create_default_pages();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function register_custom_roles() {
        // Conference Manager Role
        add_role('conference_manager', __('Conference Manager', 'conference-management'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
            'manage_conference_papers' => true,
        ));
        
        // Participant Role
        add_role('participant', __('Participant', 'conference-management'), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true,
            'submit_conference_papers' => true,
        ));
    }
    
    public function register_post_types() {
        // Conference Paper Post Type
        $labels = array(
            'name' => _x('Conference Papers', 'Post Type General Name', 'conference-management'),
            'singular_name' => _x('Conference Paper', 'Post Type Singular Name', 'conference-management'),
            'menu_name' => __('Conference Papers', 'conference-management'),
            'all_items' => __('All Papers', 'conference-management'),
            'view_item' => __('View Paper', 'conference-management'),
            'add_new_item' => __('Add New Paper', 'conference-management'),
            'add_new' => __('Add New', 'conference-management'),
            'edit_item' => __('Edit Paper', 'conference-management'),
            'update_item' => __('Update Paper', 'conference-management'),
            'search_items' => __('Search Papers', 'conference-management'),
        );
        
        $args = array(
            'label' => __('Conference Papers', 'conference-management'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'author', 'custom-fields'),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'has_archive' => false,
            'can_export' => true,
        );
        
        register_post_type('conference_paper', $args);
    }
    
    public function add_admin_menus() {
        // Main menu for Conference Manager
        add_menu_page(
            __('Conference Management', 'conference-management'),
            __('Conference', 'conference-management'),
            'manage_conference_papers',
            'conference-management',
            array($this, 'render_dashboard_page'),
            'dashicons-welcome-learn-more',
            30
        );
        
        // Sub-menu: Dashboard
        add_submenu_page(
            'conference-management',
            __('Dashboard', 'conference-management'),
            __('Dashboard', 'conference-management'),
            'manage_conference_papers',
            'conference-management',
            array($this, 'render_dashboard_page')
        );
        
        // Sub-menu: Paper Work List
        add_submenu_page(
            'conference-management',
            __('Paper Work List', 'conference-management'),
            __('Paper Work List', 'conference-management'),
            'manage_conference_papers',
            'conference-papers-list',
            array($this, 'render_papers_list_page')
        );
        
        // Sub-menu: Settings
        add_submenu_page(
            'conference-management',
            __('Settings', 'conference-management'),
            __('Settings', 'conference-management'),
            'manage_options',
            'conference-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'conference') !== false) {
            wp_enqueue_style('cms-admin-css', CMS_PLUGIN_URL . 'assets/css/admin.css', array(), CMS_VERSION);
            wp_enqueue_script('cms-admin-js', CMS_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'chart-js'), CMS_VERSION, true);
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
            
            wp_localize_script('cms-admin-js', 'cmsAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cms_ajax_nonce'),
            ));
        }
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('cms-frontend-css', CMS_PLUGIN_URL . 'assets/css/frontend.css', array(), CMS_VERSION);
        wp_enqueue_script('cms-frontend-js', CMS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CMS_VERSION, true);
        
        wp_localize_script('cms-frontend-js', 'cmsAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cms_ajax_nonce'),
        ));
    }
    
    public function render_dashboard_page() {
        include CMS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    public function render_papers_list_page() {
        include CMS_PLUGIN_DIR . 'templates/admin/papers-list.php';
    }
    
    public function render_settings_page() {
        include CMS_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    public function ajax_update_paper_status() {
        check_ajax_referer('cms_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_conference_papers')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $paper_id = intval($_POST['paper_id']);
        $status = sanitize_text_field($_POST['status']);
        
        update_post_meta($paper_id, 'paper_status', $status);
        
        if ($status === 'accept') {
            update_post_meta($paper_id, 'paper_status', 'pending_payment');
            
            // Send email to participant
            $paper = get_post($paper_id);
            $author_id = $paper->post_author;
            $author = get_userdata($author_id);
            
            CMS_Email::send_acceptance_email($author->user_email, $paper_id);
        } elseif ($status === 'reject') {
            $paper = get_post($paper_id);
            $author_id = $paper->post_author;
            $author = get_userdata($author_id);
            
            CMS_Email::send_rejection_email($author->user_email, $paper_id);
        }
        
        wp_send_json_success(array('message' => 'Status updated successfully'));
    }
    
    public function ajax_process_payment() {
        check_ajax_referer('cms_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login first'));
        }
        
        $paper_id = intval($_POST['paper_id']);
        
        // Process payment with CHIP
        $payment_result = CMS_Payment::process_chip_payment($paper_id);
        
        wp_send_json($payment_result);
    }
    
    private function create_default_pages() {
        // Create Participant Dashboard page
        if (!get_page_by_path('participant-dashboard')) {
            wp_insert_post(array(
                'post_title' => 'Participant Dashboard',
                'post_content' => '[cms_participant_dashboard]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'participant-dashboard',
            ));
        }
        
        // Create Registration page
        if (!get_page_by_path('participant-registration')) {
            wp_insert_post(array(
                'post_title' => 'Participant Registration',
                'post_content' => '[cms_registration_form]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'participant-registration',
            ));
        }
        
        // Create Submit Paper page
        if (!get_page_by_path('submit-paper')) {
            wp_insert_post(array(
                'post_title' => 'Submit Paper',
                'post_content' => '[cms_submit_paper_form]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'submit-paper',
            ));
        }
    }
}

// Initialize the plugin
function cms_init() {
    return Conference_Management_System::get_instance();
}

cms_init();