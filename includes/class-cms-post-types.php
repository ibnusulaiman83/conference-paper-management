<?php
/**
 * Post Types Registration Class
 * File: includes/class-cms-post-types.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Post_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('post_updated_messages', array($this, 'updated_messages'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_conference_paper', array($this, 'save_paper_meta'), 10, 2);
    }
    
    /**
     * Register Conference Paper post type
     */
    public function register_post_types() {
        $labels = array(
            'name' => _x('Conference Papers', 'Post Type General Name', 'conference-management'),
            'singular_name' => _x('Conference Paper', 'Post Type Singular Name', 'conference-management'),
            'menu_name' => __('Papers', 'conference-management'),
            'name_admin_bar' => __('Conference Paper', 'conference-management'),
            'archives' => __('Paper Archives', 'conference-management'),
            'attributes' => __('Paper Attributes', 'conference-management'),
            'parent_item_colon' => __('Parent Paper:', 'conference-management'),
            'all_items' => __('All Papers', 'conference-management'),
            'add_new_item' => __('Add New Paper', 'conference-management'),
            'add_new' => __('Add New', 'conference-management'),
            'new_item' => __('New Paper', 'conference-management'),
            'edit_item' => __('Edit Paper', 'conference-management'),
            'update_item' => __('Update Paper', 'conference-management'),
            'view_item' => __('View Paper', 'conference-management'),
            'view_items' => __('View Papers', 'conference-management'),
            'search_items' => __('Search Paper', 'conference-management'),
            'not_found' => __('Not found', 'conference-management'),
            'not_found_in_trash' => __('Not found in Trash', 'conference-management'),
            'featured_image' => __('Featured Image', 'conference-management'),
            'set_featured_image' => __('Set featured image', 'conference-management'),
            'remove_featured_image' => __('Remove featured image', 'conference-management'),
            'use_featured_image' => __('Use as featured image', 'conference-management'),
            'insert_into_item' => __('Insert into paper', 'conference-management'),
            'uploaded_to_this_item' => __('Uploaded to this paper', 'conference-management'),
            'items_list' => __('Papers list', 'conference-management'),
            'items_list_navigation' => __('Papers list navigation', 'conference-management'),
            'filter_items_list' => __('Filter papers list', 'conference-management'),
        );
        
        $args = array(
            'label' => __('Conference Paper', 'conference-management'),
            'description' => __('Conference Paper submissions', 'conference-management'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'author', 'custom-fields'),
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'menu_position' => 30,
            'menu_icon' => 'dashicons-media-document',
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'edit_conference_papers',
                'read_post' => 'read_conference_papers',
                'delete_post' => 'delete_conference_papers',
                'edit_posts' => 'edit_conference_papers',
                'edit_others_posts' => 'manage_conference_papers',
                'publish_posts' => 'submit_conference_papers',
                'read_private_posts' => 'read_conference_papers',
            ),
            'map_meta_cap' => true,
            'rewrite' => false,
            'query_var' => false,
        );
        
        register_post_type('conference_paper', $args);
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        // Paper Category taxonomy
        $labels = array(
            'name' => _x('Paper Categories', 'Taxonomy General Name', 'conference-management'),
            'singular_name' => _x('Paper Category', 'Taxonomy Singular Name', 'conference-management'),
            'menu_name' => __('Categories', 'conference-management'),
            'all_items' => __('All Categories', 'conference-management'),
            'parent_item' => __('Parent Category', 'conference-management'),
            'parent_item_colon' => __('Parent Category:', 'conference-management'),
            'new_item_name' => __('New Category Name', 'conference-management'),
            'add_new_item' => __('Add New Category', 'conference-management'),
            'edit_item' => __('Edit Category', 'conference-management'),
            'update_item' => __('Update Category', 'conference-management'),
            'view_item' => __('View Category', 'conference-management'),
            'separate_items_with_commas' => __('Separate categories with commas', 'conference-management'),
            'add_or_remove_items' => __('Add or remove categories', 'conference-management'),
            'choose_from_most_used' => __('Choose from the most used', 'conference-management'),
            'popular_items' => __('Popular Categories', 'conference-management'),
            'search_items' => __('Search Categories', 'conference-management'),
            'not_found' => __('Not Found', 'conference-management'),
            'no_terms' => __('No categories', 'conference-management'),
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'rewrite' => false,
        );
        
        register_taxonomy('paper_category', array('conference_paper'), $args);
        
        // Paper Track taxonomy
        $labels = array(
            'name' => _x('Conference Tracks', 'Taxonomy General Name', 'conference-management'),
            'singular_name' => _x('Conference Track', 'Taxonomy Singular Name', 'conference-management'),
            'menu_name' => __('Tracks', 'conference-management'),
            'all_items' => __('All Tracks', 'conference-management'),
            'new_item_name' => __('New Track Name', 'conference-management'),
            'add_new_item' => __('Add New Track', 'conference-management'),
            'edit_item' => __('Edit Track', 'conference-management'),
            'update_item' => __('Update Track', 'conference-management'),
            'view_item' => __('View Track', 'conference-management'),
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'rewrite' => false,
        );
        
        register_taxonomy('paper_track', array('conference_paper'), $args);
    }
    
    /**
     * Custom update messages
     */
    public function updated_messages($messages) {
        $post = get_post();
        
        $messages['conference_paper'] = array(
            0  => '',
            1  => __('Paper updated.', 'conference-management'),
            2  => __('Custom field updated.', 'conference-management'),
            3  => __('Custom field deleted.', 'conference-management'),
            4  => __('Paper updated.', 'conference-management'),
            5  => isset($_GET['revision']) ? sprintf(__('Paper restored to revision from %s', 'conference-management'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => __('Paper published.', 'conference-management'),
            7  => __('Paper saved.', 'conference-management'),
            8  => __('Paper submitted.', 'conference-management'),
            9  => sprintf(
                __('Paper scheduled for: <strong>%1$s</strong>.', 'conference-management'),
                date_i18n(__('M j, Y @ G:i', 'conference-management'), strtotime($post->post_date))
            ),
            10 => __('Paper draft updated.', 'conference-management'),
        );
        
        return $messages;
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'cms_paper_details',
            __('Paper Details', 'conference-management'),
            array($this, 'render_paper_details_metabox'),
            'conference_paper',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cms_paper_status',
            __('Paper Status', 'conference-management'),
            array($this, 'render_paper_status_metabox'),
            'conference_paper',
            'side',
            'high'
        );
        
        add_meta_box(
            'cms_participant_info',
            __('Participant Information', 'conference-management'),
            array($this, 'render_participant_info_metabox'),
            'conference_paper',
            'side',
            'default'
        );
    }
    
    /**
     * Render paper details metabox
     */
    public function render_paper_details_metabox($post) {
        wp_nonce_field('cms_paper_meta', 'cms_paper_meta_nonce');
        
        $author = get_post_meta($post->ID, 'author', true);
        $co_authors = get_post_meta($post->ID, 'co_authors', true);
        $document_url = get_post_meta($post->ID, 'document_url', true);
        $submit_date = get_post_meta($post->ID, 'submit_date', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cms_author"><?php _e('Author', 'conference-management'); ?></label></th>
                <td>
                    <input type="text" id="cms_author" name="cms_author" value="<?php echo esc_attr($author); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="cms_co_authors"><?php _e('Co-Authors', 'conference-management'); ?></label></th>
                <td>
                    <textarea id="cms_co_authors" name="cms_co_authors" rows="3" class="large-text"><?php echo esc_textarea($co_authors); ?></textarea>
                    <p class="description"><?php _e('One per line', 'conference-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cms_document_url"><?php _e('Document', 'conference-management'); ?></label></th>
                <td>
                    <?php if ($document_url): ?>
                        <a href="<?php echo esc_url($document_url); ?>" target="_blank" class="button">
                            <?php _e('View PDF', 'conference-management'); ?>
                        </a>
                        <p><code><?php echo esc_html(basename($document_url)); ?></code></p>
                    <?php else: ?>
                        <p><?php _e('No document uploaded', 'conference-management'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Submission Date', 'conference-management'); ?></label></th>
                <td>
                    <strong><?php echo $submit_date ? esc_html($submit_date) : get_the_date('Y-m-d H:i:s', $post->ID); ?></strong>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render paper status metabox
     */
    public function render_paper_status_metabox($post) {
        $status = get_post_meta($post->ID, 'paper_status', true) ?: 'review';
        $payment_amount = get_post_meta($post->ID, 'payment_amount', true);
        $payment_date = get_post_meta($post->ID, 'payment_date', true);
        
        $statuses = array(
            'review' => __('Under Review', 'conference-management'),
            'pending_payment' => __('Pending Payment', 'conference-management'),
            'paid' => __('Paid', 'conference-management'),
            'completed' => __('Completed', 'conference-management'),
            'reject' => __('Rejected', 'conference-management'),
        );
        ?>
        <div class="cms-status-metabox">
            <p>
                <strong><?php _e('Current Status:', 'conference-management'); ?></strong><br>
                <select name="cms_paper_status" id="cms_paper_status" style="width: 100%;">
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($status, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <?php if ($payment_amount): ?>
                <p>
                    <strong><?php _e('Payment Amount:', 'conference-management'); ?></strong><br>
                    RM <?php echo number_format($payment_amount, 2); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($payment_date): ?>
                <p>
                    <strong><?php _e('Payment Date:', 'conference-management'); ?></strong><br>
                    <?php echo esc_html($payment_date); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render participant info metabox
     */
    public function render_participant_info_metabox($post) {
        $author_id = $post->post_author;
        $author = get_userdata($author_id);
        
        if (!$author) {
            echo '<p>' . __('Author not found', 'conference-management') . '</p>';
            return;
        }
        
        $phone = get_user_meta($author_id, 'phone_number', true);
        $organization = get_user_meta($author_id, 'organization', true);
        $country = get_user_meta($author_id, 'country', true);
        ?>
        <div class="cms-participant-info">
            <p>
                <strong><?php echo esc_html($author->display_name); ?></strong><br>
                <a href="mailto:<?php echo esc_attr($author->user_email); ?>">
                    <?php echo esc_html($author->user_email); ?>
                </a>
            </p>
            
            <?php if ($phone): ?>
                <p>
                    <strong><?php _e('Phone:', 'conference-management'); ?></strong><br>
                    <?php echo esc_html($phone); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($organization): ?>
                <p>
                    <strong><?php _e('Organization:', 'conference-management'); ?></strong><br>
                    <?php echo esc_html($organization); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($country): ?>
                <p>
                    <strong><?php _e('Country:', 'conference-management'); ?></strong><br>
                    <?php echo esc_html($country); ?>
                </p>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo admin_url('user-edit.php?user_id=' . $author_id); ?>" class="button button-small">
                    <?php _e('View Profile', 'conference-management'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save paper meta
     */
    public function save_paper_meta($post_id, $post) {
        // Check nonce
        if (!isset($_POST['cms_paper_meta_nonce']) || !wp_verify_nonce($_POST['cms_paper_meta_nonce'], 'cms_paper_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_conference_papers', $post_id)) {
            return;
        }
        
        // Save fields
        if (isset($_POST['cms_author'])) {
            update_post_meta($post_id, 'author', sanitize_text_field($_POST['cms_author']));
        }
        
        if (isset($_POST['cms_co_authors'])) {
            update_post_meta($post_id, 'co_authors', sanitize_textarea_field($_POST['cms_co_authors']));
        }
        
        if (isset($_POST['cms_paper_status'])) {
            $old_status = get_post_meta($post_id, 'paper_status', true);
            $new_status = sanitize_text_field($_POST['cms_paper_status']);
            
            update_post_meta($post_id, 'paper_status', $new_status);
            
            // Trigger status change actions
            if ($old_status !== $new_status) {
                do_action('cms_paper_status_changed', $post_id, $new_status, $old_status);
                
                // Send emails
                if ($new_status === 'accept' || $new_status === 'pending_payment') {
                    $author = get_userdata($post->post_author);
                    if ($author) {
                        CMS_Email::send_acceptance_email($author->user_email, $post_id);
                    }
                } elseif ($new_status === 'reject') {
                    $author = get_userdata($post->post_author);
                    if ($author) {
                        CMS_Email::send_rejection_email($author->user_email, $post_id);
                    }
                }
            }
        }
    }
    
    /**
     * Get post type object
     */
    public static function get_post_type_object() {
        return get_post_type_object('conference_paper');
    }
}

// Initialize
new CMS_Post_Types();