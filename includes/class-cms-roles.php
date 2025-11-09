<?php
/**
 * Roles Management Class
 * File: includes/class-cms-roles.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Roles {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_roles'));
    }
    
    /**
     * Register custom user roles
     */
    public function register_roles() {
        // Check if roles already exist
        if (!get_role('conference_manager')) {
            $this->add_conference_manager_role();
        }
        
        if (!get_role('participant')) {
            $this->add_participant_role();
        }
    }
    
    /**
     * Add Conference Manager role
     */
    private function add_conference_manager_role() {
        // Get capabilities from editor role as base
        $editor = get_role('editor');
        $editor_caps = $editor ? $editor->capabilities : array();
        
        // Add custom capabilities
        $capabilities = array_merge($editor_caps, array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
            
            // Custom capabilities for conference management
            'manage_conference_papers' => true,
            'review_conference_papers' => true,
            'edit_conference_papers' => true,
            'view_conference_dashboard' => true,
            'update_paper_status' => true,
            'view_participant_details' => true,
            'export_conference_data' => true,
            
            // Prevent access to other post types
            'edit_pages' => false,
            'edit_others_posts' => false,
            'delete_others_posts' => false,
            'manage_categories' => false,
            'manage_links' => false,
        ));
        
        add_role(
            'conference_manager',
            __('Conference Manager', 'conference-management'),
            $capabilities
        );
    }
    
    /**
     * Add Participant role
     */
    private function add_participant_role() {
        $capabilities = array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
            
            // Custom capabilities for participants
            'submit_conference_papers' => true,
            'edit_own_papers' => true,
            'view_own_papers' => true,
            'make_payment' => true,
            'download_participant_pass' => true,
        );
        
        add_role(
            'participant',
            __('Participant', 'conference-management'),
            $capabilities
        );
    }
    
    /**
     * Remove custom roles (for uninstall)
     */
    public static function remove_roles() {
        remove_role('conference_manager');
        remove_role('participant');
    }
    
    /**
     * Add capabilities to administrator
     */
    public function add_admin_capabilities() {
        $admin = get_role('administrator');
        
        if ($admin) {
            $admin->add_cap('manage_conference_papers');
            $admin->add_cap('review_conference_papers');
            $admin->add_cap('edit_conference_papers');
            $admin->add_cap('view_conference_dashboard');
            $admin->add_cap('update_paper_status');
            $admin->add_cap('view_participant_details');
            $admin->add_cap('export_conference_data');
            $admin->add_cap('configure_conference_settings');
        }
    }
    
    /**
     * Check if user is conference manager
     */
    public static function is_conference_manager($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('conference_manager', $user->roles) || in_array('administrator', $user->roles);
    }
    
    /**
     * Check if user is participant
     */
    public static function is_participant($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('participant', $user->roles);
    }
    
    /**
     * Get all conference managers
     */
    public static function get_conference_managers() {
        $args = array(
            'role__in' => array('conference_manager', 'administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
        );
        
        return get_users($args);
    }
    
    /**
     * Get all participants
     */
    public static function get_participants($args = array()) {
        $default_args = array(
            'role' => 'participant',
            'orderby' => 'registered',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return get_users($args);
    }
    
    /**
     * Get participant statistics
     */
    public static function get_participant_stats() {
        $participants = self::get_participants();
        $total = count($participants);
        
        // Count participants with papers
        global $wpdb;
        $with_papers = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_author) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'conference_paper' 
            AND post_status = 'publish'
        ");
        
        // Count participants with completed payments
        $with_payment = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.post_author)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'conference_paper'
            AND pm.meta_key = 'paper_status'
            AND pm.meta_value IN ('paid', 'completed')
        ");
        
        return array(
            'total' => $total,
            'with_papers' => (int) $with_papers,
            'with_payment' => (int) $with_payment,
            'without_papers' => $total - (int) $with_papers,
        );
    }
    
    /**
     * Assign role to user
     */
    public static function assign_role($user_id, $role) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error('invalid_user', __('Invalid user ID', 'conference-management'));
        }
        
        // Valid roles
        $valid_roles = array('conference_manager', 'participant');
        
        if (!in_array($role, $valid_roles)) {
            return new WP_Error('invalid_role', __('Invalid role', 'conference-management'));
        }
        
        $user->set_role($role);
        
        return true;
    }
    
    /**
     * Get user's conference role
     */
    public static function get_user_conference_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        if (in_array('administrator', $user->roles)) {
            return 'administrator';
        }
        
        if (in_array('conference_manager', $user->roles)) {
            return 'conference_manager';
        }
        
        if (in_array('participant', $user->roles)) {
            return 'participant';
        }
        
        return false;
    }
    
    /**
     * Can user manage papers?
     */
    public static function can_manage_papers($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_conference_papers');
    }
    
    /**
     * Can user submit papers?
     */
    public static function can_submit_papers($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'submit_conference_papers');
    }
    
    /**
     * Get role display name
     */
    public static function get_role_display_name($role) {
        $roles = array(
            'administrator' => __('Administrator', 'conference-management'),
            'conference_manager' => __('Conference Manager', 'conference-management'),
            'participant' => __('Participant', 'conference-management'),
        );
        
        return isset($roles[$role]) ? $roles[$role] : ucfirst($role);
    }
    
    /**
     * Update role capabilities
     */
    public static function update_role_capabilities($role_name, $capabilities) {
        $role = get_role($role_name);
        
        if (!$role) {
            return false;
        }
        
        foreach ($capabilities as $cap => $grant) {
            if ($grant) {
                $role->add_cap($cap);
            } else {
                $role->remove_cap($cap);
            }
        }
        
        return true;
    }
}

// Initialize
new CMS_Roles();