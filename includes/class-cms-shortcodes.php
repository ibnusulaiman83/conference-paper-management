<?php
/**
 * Shortcodes Class
 * File: includes/class-cms-shortcodes.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Shortcodes {
    
    public function __construct() {
        add_shortcode('cms_registration_form', array($this, 'registration_form'));
        add_shortcode('cms_submit_paper_form', array($this, 'submit_paper_form'));
        add_shortcode('cms_participant_dashboard', array($this, 'participant_dashboard'));
        
        add_action('wp_ajax_nopriv_cms_register_participant', array($this, 'ajax_register_participant'));
        add_action('wp_ajax_cms_submit_paper', array($this, 'ajax_submit_paper'));
    }
    
    /**
     * Registration Form Shortcode
     */
    public function registration_form($atts) {
        if (is_user_logged_in()) {
            return '<p>You are already registered. <a href="' . home_url('/participant-dashboard/') . '">Go to Dashboard</a></p>';
        }
        
        ob_start();
        ?>
        <div class="cms-registration-form">
            <h2>Participant Registration</h2>
            <form id="cmsRegistrationForm" method="post">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label>Phone Number (WhatsApp) *</label>
                    <input type="tel" name="phone_number" required placeholder="+60123456789">
                </div>
                
                <div class="form-group">
                    <label>Organization / University / Company *</label>
                    <input type="text" name="organization" required>
                </div>
                
                <div class="form-group">
                    <label>Full Address *</label>
                    <textarea name="address" required rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Postcode *</label>
                    <input type="text" name="postcode" required>
                </div>
                
                <div class="form-group">
                    <label>Country *</label>
                    <select name="country" required>
                        <option value="">Select Country</option>
                        <option value="Malaysia">Malaysia</option>
                        <option value="Singapore">Singapore</option>
                        <option value="Indonesia">Indonesia</option>
                        <option value="Thailand">Thailand</option>
                        <option value="Philippines">Philippines</option>
                        <option value="Vietnam">Vietnam</option>
                        <option value="Brunei">Brunei</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-submit">Register</button>
                </div>
                
                <div class="cms-message"></div>
            </form>
            
            <p>Already have an account? <a href="<?php echo wp_login_url(home_url('/participant-dashboard/')); ?>">Login here</a></p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#cmsRegistrationForm').on('submit', function(e) {
                e.preventDefault();
                
                var password = $('[name="password"]').val();
                var confirmPassword = $('[name="confirm_password"]').val();
                
                if (password !== confirmPassword) {
                    $('.cms-message').html('<div class="error">Passwords do not match!</div>');
                    return;
                }
                
                var formData = $(this).serialize();
                formData += '&action=cms_register_participant';
                formData += '&nonce=' + cmsAjax.nonce;
                
                $('.btn-submit').prop('disabled', true).text('Registering...');
                
                $.post(cmsAjax.ajax_url, formData, function(response) {
                    if (response.success) {
                        $('.cms-message').html('<div class="success">' + response.data.message + '</div>');
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    } else {
                        $('.cms-message').html('<div class="error">' + response.data.message + '</div>');
                        $('.btn-submit').prop('disabled', false).text('Register');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Submit Paper Form Shortcode
     */
    public function submit_paper_form($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to submit a paper.</p>';
        }
        
        ob_start();
        ?>
        <div class="cms-submit-paper-form">
            <h2>Submit Conference Paper</h2>
            <form id="cmsSubmitPaperForm" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Paper Title *</label>
                    <input type="text" name="paper_title" required>
                </div>
                
                <div class="form-group">
                    <label>Description * (Maximum 200 words)</label>
                    <textarea name="description" required rows="5" maxlength="1400"></textarea>
                    <small>Character count: <span id="charCount">0</span>/1400</small>
                </div>
                
                <div class="form-group">
                    <label>Author *</label>
                    <input type="text" name="author" required value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                </div>
                
                <div class="form-group">
                    <label>Co-Author(s) (Optional)</label>
                    <textarea name="co_authors" rows="3" placeholder="Enter co-authors, one per line"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Upload Paper (PDF Only) *</label>
                    <input type="file" name="paper_document" accept=".pdf" required>
                    <small>Maximum file size: 10MB</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-submit">Submit Paper</button>
                </div>
                
                <div class="cms-message"></div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('[name="description"]').on('input', function() {
                $('#charCount').text($(this).val().length);
            });
            
            $('#cmsSubmitPaperForm').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'cms_submit_paper');
                formData.append('nonce', cmsAjax.nonce);
                
                $('.btn-submit').prop('disabled', true).text('Submitting...');
                
                $.ajax({
                    url: cmsAjax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('.cms-message').html('<div class="success">' + response.data.message + '</div>');
                            $('#cmsSubmitPaperForm')[0].reset();
                            setTimeout(function() {
                                window.location.href = '<?php echo home_url('/participant-dashboard/'); ?>';
                            }, 2000);
                        } else {
                            $('.cms-message').html('<div class="error">' + response.data.message + '</div>');
                            $('.btn-submit').prop('disabled', false).text('Submit Paper');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Participant Dashboard Shortcode
     */
    public function participant_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view your dashboard.</p>';
        }
        
        $user_id = get_current_user_id();
        
        // Get user's papers
        $args = array(
            'post_type' => 'conference_paper',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $papers = get_posts($args);
        
        ob_start();
        ?>
        <div class="cms-participant-dashboard">
            <h2>My Conference Papers</h2>
            
            <?php if (isset($_GET['payment']) && $_GET['payment'] === 'success'): ?>
                <div class="success-message">Payment completed successfully!</div>
            <?php endif; ?>
            
            <div class="dashboard-actions">
                <a href="<?php echo home_url('/submit-paper/'); ?>" class="btn-primary">Submit New Paper</a>
            </div>
            
            <?php if (empty($papers)): ?>
                <p>You haven't submitted any papers yet.</p>
            <?php else: ?>
                <table class="cms-papers-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($papers as $paper): 
                            $status = get_post_meta($paper->ID, 'paper_status', true) ?: 'review';
                            $submit_date = get_post_meta($paper->ID, 'submit_date', true) ?: get_the_date('Y-m-d H:i', $paper->ID);
                        ?>
                        <tr>
                            <td><?php echo esc_html($paper->post_title); ?></td>
                            <td><?php echo esc_html($submit_date); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $status))); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-view" onclick="viewPaperDetails(<?php echo $paper->ID; ?>)">View Details</button>
                                
                                <?php if ($status === 'pending_payment'): ?>
                                    <button class="btn-pay" onclick="makePayment(<?php echo $paper->ID; ?>)">Pay Now</button>
                                <?php endif; ?>
                                
                                <?php if ($status === 'completed'): ?>
                                    <a href="<?php echo add_query_arg('download_pass', $paper->ID); ?>" class="btn-pass" target="_blank">Download Pass</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Modal for Paper Details -->
        <div id="paperModal" class="cms-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="modalBody"></div>
            </div>
        </div>
        
        <script>
        function viewPaperDetails(paperId) {
            jQuery.post(cmsAjax.ajax_url, {
                action: 'cms_get_paper_details',
                paper_id: paperId,
                nonce: cmsAjax.nonce
            }, function(response) {
                if (response.success) {
                    jQuery('#modalBody').html(response.data.html);
                    jQuery('#paperModal').show();
                }
            });
        }
        
        function makePayment(paperId) {
            if (!confirm('Proceed to payment?')) return;
            
            jQuery.post(cmsAjax.ajax_url, {
                action: 'cms_process_payment',
                paper_id: paperId,
                nonce: cmsAjax.nonce
            }, function(response) {
                if (response.success) {
                    window.location.href = response.redirect_url;
                } else {
                    alert(response.message);
                }
            });
        }
        
        jQuery('.close').on('click', function() {
            jQuery('#paperModal').hide();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Register Participant
     */
    public function ajax_register_participant() {
        check_ajax_referer('cms_ajax_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $full_name = sanitize_text_field($_POST['full_name']);
        
        // Check if email exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already registered!'));
        }
        
        // Create user
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $full_name,
            'first_name' => $full_name,
        ));
        
        // Set role to participant
        $user = new WP_User($user_id);
        $user->set_role('participant');
        
        // Save additional fields
        update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
        update_user_meta($user_id, 'organization', sanitize_text_field($_POST['organization']));
        update_user_meta($user_id, 'address', sanitize_textarea_field($_POST['address']));
        update_user_meta($user_id, 'postcode', sanitize_text_field($_POST['postcode']));
        update_user_meta($user_id, 'country', sanitize_text_field($_POST['country']));
        
        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success(array(
            'message' => 'Registration successful! Redirecting...',
            'redirect' => home_url('/participant-dashboard/')
        ));
    }
    
    /**
     * AJAX: Submit Paper
     */
    public function ajax_submit_paper() {
        check_ajax_referer('cms_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login first'));
        }
        
        // Handle file upload
        if (empty($_FILES['paper_document'])) {
            wp_send_json_error(array('message' => 'Please upload a PDF document'));
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload = wp_handle_upload($_FILES['paper_document'], array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        // Create paper post
        $paper_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($_POST['paper_title']),
            'post_content' => sanitize_textarea_field($_POST['description']),
            'post_type' => 'conference_paper',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ));
        
        if (is_wp_error($paper_id)) {
            wp_send_json_error(array('message' => $paper_id->get_error_message()));
        }
        
        // Save metadata
        update_post_meta($paper_id, 'author', sanitize_text_field($_POST['author']));
        update_post_meta($paper_id, 'co_authors', sanitize_textarea_field($_POST['co_authors']));
        update_post_meta($paper_id, 'document_url', $upload['url']);
        update_post_meta($paper_id, 'paper_status', 'review');
        update_post_meta($paper_id, 'submit_date', current_time('mysql'));
        
        // Send emails
        $user = wp_get_current_user();
        CMS_Email::send_submission_email($user->user_email, $paper_id);
        CMS_Email::notify_manager_new_submission($paper_id);
        
        wp_send_json_success(array(
            'message' => 'Paper submitted successfully! You will be notified via email.',
            'paper_id' => $paper_id
        ));
    }
}

new CMS_Shortcodes();