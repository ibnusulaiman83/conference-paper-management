<?php
/**
 * Settings Page Template
 * File: templates/admin/settings.php
 */

if (!defined('ABSPATH')) exit;

// Save settings
if (isset($_POST['cms_save_settings'])) {
    check_admin_referer('cms_settings_nonce');
    
    update_option('cms_payment_amount', floatval($_POST['payment_amount']));
    update_option('cms_chip_brand_id', sanitize_text_field($_POST['chip_brand_id']));
    update_option('cms_chip_api_key', sanitize_text_field($_POST['chip_api_key']));
    update_option('cms_conference_name', sanitize_text_field($_POST['conference_name']));
    update_option('cms_conference_date', sanitize_text_field($_POST['conference_date']));
    update_option('cms_conference_venue', sanitize_textarea_field($_POST['conference_venue']));
    update_option('cms_notification_email', sanitize_email($_POST['notification_email']));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current settings
$payment_amount = get_option('cms_payment_amount', 300);
$chip_brand_id = get_option('cms_chip_brand_id', '');
$chip_api_key = get_option('cms_chip_api_key', '');
$conference_name = get_option('cms_conference_name', '');
$conference_date = get_option('cms_conference_date', '');
$conference_venue = get_option('cms_conference_venue', '');
$notification_email = get_option('cms_notification_email', get_option('admin_email'));
?>

<div class="wrap cms-settings">
    <h1><?php _e('Conference Management Settings', 'conference-management'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('cms_settings_nonce'); ?>
        
        <h2 class="title"><?php _e('Conference Information', 'conference-management'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="conference_name">Conference Name</label>
                </th>
                <td>
                    <input type="text" id="conference_name" name="conference_name" value="<?php echo esc_attr($conference_name); ?>" class="regular-text">
                    <p class="description">The name of your conference</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="conference_date">Conference Date</label>
                </th>
                <td>
                    <input type="date" id="conference_date" name="conference_date" value="<?php echo esc_attr($conference_date); ?>" class="regular-text">
                    <p class="description">When will the conference take place?</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="conference_venue">Conference Venue</label>
                </th>
                <td>
                    <textarea id="conference_venue" name="conference_venue" rows="3" class="large-text"><?php echo esc_textarea($conference_venue); ?></textarea>
                    <p class="description">Full address of the conference venue</p>
                </td>
            </tr>
        </table>
        
        <h2 class="title"><?php _e('Payment Settings', 'conference-management'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="payment_amount">Registration Fee (RM)</label>
                </th>
                <td>
                    <input type="number" id="payment_amount" name="payment_amount" value="<?php echo esc_attr($payment_amount); ?>" step="0.01" min="0" class="regular-text">
                    <p class="description">Conference registration fee amount in Malaysian Ringgit</p>
                </td>
            </tr>
        </table>
        
        <h2 class="title"><?php _e('CHIP Payment Gateway Settings', 'conference-management'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="chip_brand_id">CHIP Brand ID</label>
                </th>
                <td>
                    <input type="text" id="chip_brand_id" name="chip_brand_id" value="<?php echo esc_attr($chip_brand_id); ?>" class="regular-text">
                    <p class="description">Your CHIP Brand ID from <a href="https://merchant.chip-in.asia/" target="_blank">CHIP Dashboard</a></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="chip_api_key">CHIP API Key</label>
                </th>
                <td>
                    <input type="password" id="chip_api_key" name="chip_api_key" value="<?php echo esc_attr($chip_api_key); ?>" class="regular-text">
                    <p class="description">Your CHIP API Secret Key (keep this secure!)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Webhook URL</th>
                <td>
                    <code><?php echo home_url('/cms-webhook/chip/'); ?></code>
                    <p class="description">Configure this URL in your CHIP Dashboard webhook settings</p>
                    <button type="button" class="button" onclick="copyWebhookUrl()">Copy URL</button>
                </td>
            </tr>
        </table>
        
        <h2 class="title"><?php _e('Notification Settings', 'conference-management'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="notification_email">Notification Email</label>
                </th>
                <td>
                    <input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
                    <p class="description">Email address to receive notifications about new submissions</p>
                </td>
            </tr>
        </table>
        
        <h2 class="title"><?php _e('User Roles', 'conference-management'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">Conference Managers</th>
                <td>
                    <?php
                    $managers = get_users(array('role' => 'conference_manager'));
                    if (empty($managers)) {
                        echo '<p>No conference managers assigned yet.</p>';
                    } else {
                        echo '<ul>';
                        foreach ($managers as $manager) {
                            echo '<li>' . esc_html($manager->display_name) . ' (' . esc_html($manager->user_email) . ')</li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                    <p class="description">
                        To assign Conference Manager role: Go to <a href="<?php echo admin_url('users.php'); ?>">Users</a> → Edit user → Change role to "Conference Manager"
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">Total Participants</th>
                <td>
                    <?php
                    $participants = count_users();
                    $participant_count = isset($participants['avail_roles']['participant']) ? $participants['avail_roles']['participant'] : 0;
                    echo '<strong>' . $participant_count . '</strong> registered participants';
                    ?>
                    <p class="description">
                        View all participants: <a href="<?php echo admin_url('users.php?role=participant'); ?>">Participants List</a>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2 class="title"><?php _e('System Pages', 'conference-management'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">Registration Page</th>
                <td>
                    <?php
                    $reg_page = get_page_by_path('participant-registration');
                    if ($reg_page) {
                        echo '<a href="' . get_permalink($reg_page->ID) . '" target="_blank">' . get_permalink($reg_page->ID) . '</a>';
                    } else {
                        echo '<span style="color: red;">Page not found</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Submit Paper Page</th>
                <td>
                    <?php
                    $submit_page = get_page_by_path('submit-paper');
                    if ($submit_page) {
                        echo '<a href="' . get_permalink($submit_page->ID) . '" target="_blank">' . get_permalink($submit_page->ID) . '</a>';
                    } else {
                        echo '<span style="color: red;">Page not found</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Participant Dashboard</th>
                <td>
                    <?php
                    $dashboard_page = get_page_by_path('participant-dashboard');
                    if ($dashboard_page) {
                        echo '<a href="' . get_permalink($dashboard_page->ID) . '" target="_blank">' . get_permalink($dashboard_page->ID) . '</a>';
                    } else {
                        echo '<span style="color: red;">Page not found</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="cms_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'conference-management'); ?>">
        </p>
    </form>
</div>

<script>
function copyWebhookUrl() {
    var url = '<?php echo home_url('/cms-webhook/chip/'); ?>';
    navigator.clipboard.writeText(url).then(function() {
        alert('Webhook URL copied to clipboard!');
    });
}
</script>