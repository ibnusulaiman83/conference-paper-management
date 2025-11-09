/**
 * Frontend JavaScript
 * File: assets/js/frontend.js
 */

(function($) {
    'use strict';
    
    // Form validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function validatePhone(phone) {
        const re = /^[+]?[\d\s-()]+$/;
        return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
    }
    
    // Registration form enhancement
    if ($('#cmsRegistrationForm').length) {
        const form = $('#cmsRegistrationForm');
        
        // Real-time validation
        form.find('input[type="email"]').on('blur', function() {
            const email = $(this).val();
            if (email && !validateEmail(email)) {
                $(this).css('border-color', '#f44336');
                $(this).siblings('small').remove();
                $(this).after('<small style="color: #f44336;">Please enter a valid email address</small>');
            } else {
                $(this).css('border-color', '#ddd');
                $(this).siblings('small').remove();
            }
        });
        
        form.find('input[name="phone_number"]').on('blur', function() {
            const phone = $(this).val();
            if (phone && !validatePhone(phone)) {
                $(this).css('border-color', '#f44336');
                $(this).siblings('small.error').remove();
                $(this).after('<small class="error" style="color: #f44336;">Please enter a valid phone number</small>');
            } else {
                $(this).css('border-color', '#ddd');
                $(this).siblings('small.error').remove();
            }
        });
        
        // Password strength indicator
        form.find('input[name="password"]').on('input', function() {
            const password = $(this).val();
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            $(this).siblings('.password-strength').remove();
            
            let strengthText = '';
            let strengthColor = '';
            
            if (password.length > 0) {
                if (strength <= 2) {
                    strengthText = 'Weak';
                    strengthColor = '#f44336';
                } else if (strength <= 4) {
                    strengthText = 'Medium';
                    strengthColor = '#FF9800';
                } else {
                    strengthText = 'Strong';
                    strengthColor = '#4CAF50';
                }
                
                $(this).after('<small class="password-strength" style="color: ' + strengthColor + ';">Password strength: ' + strengthText + '</small>');
            }
        });
        
        // Confirm password validation
        form.find('input[name="confirm_password"]').on('input', function() {
            const password = form.find('input[name="password"]').val();
            const confirmPassword = $(this).val();
            
            $(this).siblings('.password-match').remove();
            
            if (confirmPassword.length > 0) {
                if (password !== confirmPassword) {
                    $(this).css('border-color', '#f44336');
                    $(this).after('<small class="password-match" style="color: #f44336;">Passwords do not match</small>');
                } else {
                    $(this).css('border-color', '#4CAF50');
                    $(this).after('<small class="password-match" style="color: #4CAF50;">Passwords match</small>');
                }
            }
        });
    }
    
    // Submit paper form enhancement
    if ($('#cmsSubmitPaperForm').length) {
        const form = $('#cmsSubmitPaperForm');
        
        // File upload validation
        form.find('input[type="file"]').on('change', function() {
            const file = this.files[0];
            
            if (file) {
                // Check file type
                if (file.type !== 'application/pdf') {
                    alert('Please upload a PDF file only');
                    $(this).val('');
                    return;
                }
                
                // Check file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    $(this).val('');
                    return;
                }
                
                // Show file info
                $(this).siblings('.file-info').remove();
                $(this).after('<small class="file-info" style="color: #4CAF50;">✓ ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)</small>');
            }
        });
        
        // Word count for description
        form.find('textarea[name="description"]').on('input', function() {
            const text = $(this).val();
            const words = text.trim().split(/\s+/).filter(function(word) {
                return word.length > 0;
            }).length;
            
            $(this).siblings('.word-count').remove();
            
            let color = '#666';
            if (words > 200) {
                color = '#f44336';
            } else if (words > 180) {
                color = '#FF9800';
            }
            
            $(this).siblings('small').append('<span class="word-count" style="color: ' + color + ';"> | Words: ' + words + '/200</span>');
            
            if (words > 200) {
                $(this).css('border-color', '#f44336');
            } else {
                $(this).css('border-color', '#ddd');
            }
        });
    }
    
    // Participant Dashboard enhancements
    if ($('.cms-participant-dashboard').length) {
        // Add data labels for mobile responsive table
        $('.cms-papers-table tbody tr').each(function() {
            $(this).find('td').each(function(index) {
                const label = $('.cms-papers-table thead th').eq(index).text();
                $(this).attr('data-label', label);
            });
        });
        
        // Smooth scroll to top after actions
        $('[onclick*="viewPaperDetails"], [onclick*="makePayment"]').on('click', function() {
            $('html, body').animate({ scrollTop: 0 }, 300);
        });
    }
    
    // Modal close on outside click
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('cms-modal')) {
            $('.cms-modal').hide();
        }
    });
    
    // Escape key to close modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.cms-modal').hide();
        }
    });
    
    // Payment confirmation
    window.makePayment = function(paperId) {
        if (!confirm('You will be redirected to the payment gateway. Proceed?')) {
            return false;
        }
        
        $('.cms-participant-dashboard').addClass('loading');
        
        $.post(cmsAjax.ajax_url, {
            action: 'cms_process_payment',
            paper_id: paperId,
            nonce: cmsAjax.nonce
        }, function(response) {
            $('.cms-participant-dashboard').removeClass('loading');
            
            if (response.success) {
                window.location.href = response.redirect_url;
            } else {
                alert('Payment Error: ' + response.message);
            }
        }).fail(function() {
            $('.cms-participant-dashboard').removeClass('loading');
            alert('Connection error. Please try again.');
        });
    };
    
    // View paper details
    window.viewPaperDetails = function(paperId) {
        $.post(cmsAjax.ajax_url, {
            action: 'cms_get_paper_details',
            paper_id: paperId,
            nonce: cmsAjax.nonce
        }, function(response) {
            if (response.success) {
                $('#modalBody').html(response.data.html);
                $('#paperModal').show();
            } else {
                alert('Error loading paper details');
            }
        }).fail(function() {
            alert('Connection error. Please try again.');
        });
    };
    
    // AJAX handler for participant paper details
    $(document).ready(function() {
        // Check for payment status in URL
        const urlParams = new URLSearchParams(window.location.search);
        const paymentStatus = urlParams.get('payment');
        
        if (paymentStatus === 'success') {
            // Show success animation
            showNotification('Payment completed successfully!', 'success');
        } else if (paymentStatus === 'failed') {
            showNotification('Payment failed. Please try again.', 'error');
        } else if (paymentStatus === 'cancelled') {
            showNotification('Payment was cancelled.', 'warning');
        }
    });
    
    // Notification function
    function showNotification(message, type) {
        const colors = {
            success: '#4CAF50',
            error: '#f44336',
            warning: '#FF9800'
        };
        
        const notification = $('<div>')
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 20px',
                backgroundColor: colors[type],
                color: 'white',
                borderRadius: '5px',
                boxShadow: '0 4px 8px rgba(0,0,0,0.2)',
                zIndex: '10000',
                animation: 'slideIn 0.3s ease-out'
            })
            .text(message)
            .appendTo('body');
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Add CSS animation
    $('<style>')
        .text('@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }')
        .appendTo('head');
    
})(jQuery);

// AJAX handler for getting paper details (participant view)
jQuery(document).ready(function($) {
    if (typeof wp !== 'undefined' && wp.ajax) {
        wp.ajax.post('cms_get_paper_details', {
            // This will be populated by individual calls
        });
    }
});