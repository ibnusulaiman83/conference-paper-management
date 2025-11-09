<?php
/**
 * Email Handler Class
 * File: includes/class-cms-email.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Email {
    
    /**
     * Send email when paper is submitted
     */
    public static function send_submission_email($user_email, $paper_id) {
        $paper = get_post($paper_id);
        $paper_title = $paper->post_title;
        $submit_date = get_post_meta($paper_id, 'submit_date', true);
        
        $subject = 'Conference Paper Submission Confirmation';
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Paper Submission Received</h2>
                </div>
                <div class='content'>
                    <p>Dear Participant,</p>
                    <p>Your conference paper has been successfully submitted for review.</p>
                    
                    <h3>Submission Details:</h3>
                    <ul>
                        <li><strong>Paper Title:</strong> {$paper_title}</li>
                        <li><strong>Submission Date:</strong> {$submit_date}</li>
                        <li><strong>Status:</strong> Under Review</li>
                    </ul>
                    
                    <p>Our conference management team will review your paper and notify you of the outcome shortly.</p>
                    
                    <p>You can track your submission status by logging into your dashboard.</p>
                    
                    <p>Thank you for your submission!</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Conference Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($user_email, $subject, $message, $headers);
    }
    
    /**
     * Send email when paper is accepted
     */
    public static function send_acceptance_email($user_email, $paper_id) {
        $paper = get_post($paper_id);
        $paper_title = $paper->post_title;
        $payment_amount = get_option('cms_payment_amount', 0);
        $payment_url = home_url('/participant-dashboard/?paper_id=' . $paper_id . '&action=payment');
        
        $subject = 'Conference Paper Accepted - Payment Required';
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .amount { font-size: 24px; font-weight: bold; color: #4CAF50; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Congratulations! Your Paper Has Been Accepted</h2>
                </div>
                <div class='content'>
                    <p>Dear Participant,</p>
                    <p>We are pleased to inform you that your conference paper has been <strong>ACCEPTED</strong>!</p>
                    
                    <h3>Paper Details:</h3>
                    <ul>
                        <li><strong>Paper Title:</strong> {$paper_title}</li>
                        <li><strong>Status:</strong> Accepted - Pending Payment</li>
                    </ul>
                    
                    <h3>Payment Information:</h3>
                    <p>To complete your registration, please make a payment of:</p>
                    <p class='amount'>RM " . number_format($payment_amount, 2) . "</p>
                    
                    <p>Click the button below to proceed with payment:</p>
                    <a href='{$payment_url}' class='button'>Make Payment Now</a>
                    
                    <p style='margin-top: 20px;'>After successful payment, you will receive your participant pass.</p>
                    
                    <p>We look forward to your participation in the conference!</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Conference Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($user_email, $subject, $message, $headers);
    }
    
    /**
     * Send email when paper is rejected
     */
    public static function send_rejection_email($user_email, $paper_id) {
        $paper = get_post($paper_id);
        $paper_title = $paper->post_title;
        
        $subject = 'Conference Paper Review Result';
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f44336; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Conference Paper Review Result</h2>
                </div>
                <div class='content'>
                    <p>Dear Participant,</p>
                    <p>Thank you for submitting your paper to our conference.</p>
                    
                    <h3>Paper Details:</h3>
                    <ul>
                        <li><strong>Paper Title:</strong> {$paper_title}</li>
                        <li><strong>Status:</strong> Not Accepted</li>
                    </ul>
                    
                    <p>After careful review by our conference committee, we regret to inform you that your paper has not been selected for this conference.</p>
                    
                    <p>We appreciate your interest and encourage you to submit your work to future conferences.</p>
                    
                    <p>Thank you for your understanding.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Conference Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($user_email, $subject, $message, $headers);
    }
    
    /**
     * Send email when payment is completed
     */
    public static function send_payment_confirmation_email($user_email, $paper_id) {
        $paper = get_post($paper_id);
        $paper_title = $paper->post_title;
        $payment_amount = get_post_meta($paper_id, 'payment_amount', true);
        $payment_date = get_post_meta($paper_id, 'payment_date', true);
        
        $subject = 'Payment Confirmation - Conference Registration Complete';
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .success { background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Payment Confirmed!</h2>
                </div>
                <div class='content'>
                    <div class='success'>
                        <p style='margin:0; color: #155724;'><strong>✓ Your payment has been successfully processed!</strong></p>
                    </div>
                    
                    <p>Dear Participant,</p>
                    <p>Thank you for completing your conference registration payment.</p>
                    
                    <h3>Payment Details:</h3>
                    <ul>
                        <li><strong>Paper Title:</strong> {$paper_title}</li>
                        <li><strong>Amount Paid:</strong> RM " . number_format($payment_amount, 2) . "</li>
                        <li><strong>Payment Date:</strong> {$payment_date}</li>
                        <li><strong>Status:</strong> Completed</li>
                    </ul>
                    
                    <p>Your participant pass is now available in your dashboard. Please download and keep it for the conference.</p>
                    
                    <p>We look forward to seeing you at the conference!</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Conference Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($user_email, $subject, $message, $headers);
    }
    
    /**
     * Notify conference manager about new submission
     */
    public static function notify_manager_new_submission($paper_id) {
        $managers = get_users(array('role' => 'conference_manager'));
        
        if (empty($managers)) {
            $managers = get_users(array('role' => 'administrator'));
        }
        
        $paper = get_post($paper_id);
        $author = get_userdata($paper->post_author);
        
        $subject = 'New Conference Paper Submission';
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 10px 20px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Paper Submission</h2>
                </div>
                <div class='content'>
                    <p>A new conference paper has been submitted for review.</p>
                    
                    <h3>Submission Details:</h3>
                    <ul>
                        <li><strong>Paper Title:</strong> {$paper->post_title}</li>
                        <li><strong>Submitted By:</strong> {$author->display_name}</li>
                        <li><strong>Email:</strong> {$author->user_email}</li>
                        <li><strong>Date:</strong> " . get_the_date('', $paper_id) . "</li>
                    </ul>
                    
                    <p>Please review this submission in the conference management system.</p>
                    <a href='" . admin_url('admin.php?page=conference-papers-list') . "' class='button'>Review Submission</a>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        foreach ($managers as $manager) {
            wp_mail($manager->user_email, $subject, $message, $headers);
        }
    }
}