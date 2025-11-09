<?php
/**
 * Payment Handler Class with CHIP Integration
 * File: includes/class-cms-payment.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Payment {
    
    /**
     * Process payment with CHIP Gateway
     */
    public static function process_chip_payment($paper_id) {
        $paper = get_post($paper_id);
        
        if (!$paper) {
            return array(
                'success' => false,
                'message' => 'Paper not found'
            );
        }
        
        // Check if user owns this paper
        if ($paper->post_author != get_current_user_id()) {
            return array(
                'success' => false,
                'message' => 'Unauthorized'
            );
        }
        
        // Check paper status
        $status = get_post_meta($paper_id, 'paper_status', true);
        if ($status !== 'pending_payment') {
            return array(
                'success' => false,
                'message' => 'Paper is not pending payment'
            );
        }
        
        // Get CHIP API credentials from settings
        $brand_id = get_option('cms_chip_brand_id', '');
        $api_key = get_option('cms_chip_api_key', '');
        $payment_amount = get_option('cms_payment_amount', 0);
        
        if (empty($brand_id) || empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'Payment gateway not configured'
            );
        }
        
        // Prepare payment data
        $user = wp_get_current_user();
        
        $purchase = array(
            'brand_id' => $brand_id,
            'client' => array(
                'email' => $user->user_email,
                'full_name' => $user->display_name,
                'phone' => get_user_meta($user->ID, 'phone_number', true),
            ),
            'purchase' => array(
                'total' => (int)($payment_amount * 100), // Amount in cents
                'currency' => 'MYR',
                'products' => array(
                    array(
                        'name' => 'Conference Registration - ' . $paper->post_title,
                        'price' => (int)($payment_amount * 100),
                        'quantity' => 1,
                    )
                ),
            ),
            'success_redirect' => add_query_arg(array(
                'payment' => 'success',
                'paper_id' => $paper_id,
            ), home_url('/participant-dashboard/')),
            'failure_redirect' => add_query_arg(array(
                'payment' => 'failed',
                'paper_id' => $paper_id,
            ), home_url('/participant-dashboard/')),
            'cancel_redirect' => add_query_arg(array(
                'payment' => 'cancelled',
                'paper_id' => $paper_id,
            ), home_url('/participant-dashboard/')),
            'reference' => 'PAPER-' . $paper_id . '-' . time(),
            'due' => time() + (24 * 60 * 60), // 24 hours from now
        );
        
        // Make API request to CHIP
        $response = wp_remote_post('https://gate.chip-in.asia/api/purchases/', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => json_encode($purchase),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Payment gateway connection failed: ' . $response->get_error_message()
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['id']) && isset($body['checkout_url'])) {
            // Save payment reference
            update_post_meta($paper_id, 'chip_payment_id', $body['id']);
            update_post_meta($paper_id, 'payment_reference', $purchase['reference']);
            update_post_meta($paper_id, 'payment_amount', $payment_amount);
            
            return array(
                'success' => true,
                'redirect_url' => $body['checkout_url'],
                'payment_id' => $body['id']
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Failed to create payment: ' . (isset($body['message']) ? $body['message'] : 'Unknown error')
        );
    }
    
    /**
     * Verify payment status with CHIP
     */
    public static function verify_chip_payment($paper_id) {
        $chip_payment_id = get_post_meta($paper_id, 'chip_payment_id', true);
        
        if (empty($chip_payment_id)) {
            return false;
        }
        
        $api_key = get_option('cms_chip_api_key', '');
        
        $response = wp_remote_get('https://gate.chip-in.asia/api/purchases/' . $chip_payment_id . '/', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['status']) && $body['status'] === 'paid') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Handle payment callback/webhook from CHIP
     */
    public static function handle_chip_webhook() {
        $raw_input = file_get_contents('php://input');
        $data = json_decode($raw_input, true);
        
        if (!$data || !isset($data['id'])) {
            wp_die('Invalid webhook data', 'Webhook Error', array('response' => 400));
        }
        
        // Find paper by CHIP payment ID
        $args = array(
            'post_type' => 'conference_paper',
            'meta_query' => array(
                array(
                    'key' => 'chip_payment_id',
                    'value' => $data['id'],
                )
            ),
            'posts_per_page' => 1,
        );
        
        $papers = get_posts($args);
        
        if (empty($papers)) {
            wp_die('Paper not found', 'Webhook Error', array('response' => 404));
        }
        
        $paper_id = $papers[0]->ID;
        
        // Update payment status based on webhook data
        if ($data['status'] === 'paid') {
            self::complete_payment($paper_id, $data);
        }
        
        wp_die('OK', 'Webhook Success', array('response' => 200));
    }
    
    /**
     * Complete payment and update paper status
     */
    public static function complete_payment($paper_id, $payment_data = array()) {
        update_post_meta($paper_id, 'paper_status', 'paid');
        update_post_meta($paper_id, 'payment_date', current_time('mysql'));
        update_post_meta($paper_id, 'payment_type', 'chip');
        
        if (!empty($payment_data)) {
            update_post_meta($paper_id, 'payment_data', $payment_data);
        }
        
        // Send confirmation email
        $paper = get_post($paper_id);
        $author = get_userdata($paper->post_author);
        CMS_Email::send_payment_confirmation_email($author->user_email, $paper_id);
        
        // Generate participant pass
        self::generate_participant_pass($paper_id);
        
        // Update status to completed
        update_post_meta($paper_id, 'paper_status', 'completed');
    }
    
    /**
     * Generate participant pass (PDF badge)
     */
    public static function generate_participant_pass($paper_id) {
        // This would integrate with a PDF generation library
        // For now, we'll create a simple HTML pass that can be printed
        
        $paper = get_post($paper_id);
        $author = get_userdata($paper->post_author);
        
        $phone = get_user_meta($author->ID, 'phone_number', true);
        $organization = get_user_meta($author->ID, 'organization', true);
        $country = get_user_meta($author->ID, 'country', true);
        
        $pass_html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .pass-container { width: 400px; border: 3px solid #4CAF50; padding: 30px; margin: 50px auto; text-align: center; }
                .pass-header { background-color: #4CAF50; color: white; padding: 20px; margin: -30px -30px 20px -30px; }
                .participant-name { font-size: 24px; font-weight: bold; margin: 20px 0; }
                .info { text-align: left; margin: 20px 0; }
                .qr-code { margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='pass-container'>
                <div class='pass-header'>
                    <h1>PARTICIPANT PASS</h1>
                    <p>Conference 2025</p>
                </div>
                
                <div class='participant-name'>{$author->display_name}</div>
                
                <div class='info'>
                    <p><strong>Organization:</strong> {$organization}</p>
                    <p><strong>Country:</strong> {$country}</p>
                    <p><strong>Email:</strong> {$author->user_email}</p>
                    <p><strong>Phone:</strong> {$phone}</p>
                    <p><strong>Paper:</strong> {$paper->post_title}</p>
                    <p><strong>Registration ID:</strong> PAPER-{$paper_id}</p>
                </div>
                
                <div class='qr-code'>
                    <img src='https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=PAPER-{$paper_id}' alt='QR Code'>
                </div>
                
                <p style='font-size: 12px; color: #666;'>Please present this pass at the conference venue</p>
            </div>
        </body>
        </html>
        ";
        
        update_post_meta($paper_id, 'participant_pass_html', $pass_html);
        
        return $pass_html;
    }
    
    /**
     * Get participant pass
     */
    public static function get_participant_pass($paper_id) {
        return get_post_meta($paper_id, 'participant_pass_html', true);
    }
}

// Register webhook endpoint
add_action('init', function() {
    add_rewrite_rule('^cms-webhook/chip/?', 'index.php?cms_chip_webhook=1', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'cms_chip_webhook';
    return $vars;
});

add_action('template_redirect', function() {
    if (get_query_var('cms_chip_webhook')) {
        CMS_Payment::handle_chip_webhook();
        exit;
    }
});