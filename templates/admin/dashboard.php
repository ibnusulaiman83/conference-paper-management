<?php
/**
 * Admin Dashboard Template
 * File: templates/admin/dashboard.php
 */

if (!defined('ABSPATH')) exit;

// Get statistics
$args = array(
    'post_type' => 'conference_paper',
    'posts_per_page' => -1,
);
$all_papers = get_posts($args);

$stats = array(
    'review' => 0,
    'pending_payment' => 0,
    'paid' => 0,
    'completed' => 0,
    'reject' => 0,
);

foreach ($all_papers as $paper) {
    $status = get_post_meta($paper->ID, 'paper_status', true) ?: 'review';
    if (isset($stats[$status])) {
        $stats[$status]++;
    }
}

// Get monthly registrations
global $wpdb;
$monthly_data = $wpdb->get_results("
    SELECT 
        MONTH(post_date) as month,
        YEAR(post_date) as year,
        COUNT(*) as count
    FROM {$wpdb->posts}
    WHERE post_type = 'conference_paper'
    AND post_status = 'publish'
    GROUP BY YEAR(post_date), MONTH(post_date)
    ORDER BY year DESC, month DESC
    LIMIT 12
");

$month_names = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
$monthly_labels = array();
$monthly_counts = array();

foreach (array_reverse($monthly_data) as $data) {
    $monthly_labels[] = $month_names[$data->month - 1] . ' ' . $data->year;
    $monthly_counts[] = $data->count;
}
?>

<div class="wrap cms-dashboard">
    <h1><?php _e('Conference Management Dashboard', 'conference-management'); ?></h1>
    
    <div class="cms-stats-grid">
        <div class="stat-card stat-review">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3><?php echo $stats['review']; ?></h3>
                <p>Under Review</p>
            </div>
        </div>
        
        <div class="stat-card stat-pending">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3><?php echo $stats['pending_payment']; ?></h3>
                <p>Pending Payment</p>
            </div>
        </div>
        
        <div class="stat-card stat-paid">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3><?php echo $stats['paid']; ?></h3>
                <p>Paid</p>
            </div>
        </div>
        
        <div class="stat-card stat-completed">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3><?php echo $stats['completed']; ?></h3>
                <p>Completed</p>
            </div>
        </div>
        
        <div class="stat-card stat-reject">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <h3><?php echo $stats['reject']; ?></h3>
                <p>Rejected</p>
            </div>
        </div>
        
        <div class="stat-card stat-total">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo count($all_papers); ?></h3>
                <p>Total Submissions</p>
            </div>
        </div>
    </div>
    
    <div class="cms-charts">
        <div class="chart-container">
            <h2><?php _e('Paper Status Distribution', 'conference-management'); ?></h2>
            <canvas id="statusChart"></canvas>
        </div>
        
        <div class="chart-container">
            <h2><?php _e('Monthly Registrations', 'conference-management'); ?></h2>
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
    
    <div class="cms-recent-papers">
        <h2><?php _e('Recent Submissions', 'conference-management'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent_papers = array_slice($all_papers, 0, 10);
                foreach ($recent_papers as $paper):
                    $author = get_userdata($paper->post_author);
                    $status = get_post_meta($paper->ID, 'paper_status', true) ?: 'review';
                ?>
                <tr>
                    <td><?php echo esc_html($paper->post_title); ?></td>
                    <td><?php echo esc_html($author->display_name); ?></td>
                    <td><?php echo get_the_date('Y-m-d H:i', $paper->ID); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo esc_attr($status); ?>">
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $status))); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=conference-papers-list&paper_id=' . $paper->ID); ?>" class="button button-small">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Status Pie Chart
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Under Review', 'Pending Payment', 'Paid', 'Completed', 'Rejected'],
            datasets: [{
                data: [
                    <?php echo $stats['review']; ?>,
                    <?php echo $stats['pending_payment']; ?>,
                    <?php echo $stats['paid']; ?>,
                    <?php echo $stats['completed']; ?>,
                    <?php echo $stats['reject']; ?>
                ],
                backgroundColor: [
                    '#FFA726',
                    '#FF9800',
                    '#66BB6A',
                    '#4CAF50',
                    '#EF5350'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Monthly Bar Chart
    var monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets: [{
                label: 'Submissions',
                data: <?php echo json_encode($monthly_counts); ?>,
                backgroundColor: '#2196F3',
                borderColor: '#1976D2',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>