<?php

/**
 * Provide an admin area view for the plugin dashboard
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard statistics
$today = date('Y-m-d');
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_month_start = date('Y-m-01');

$stats = array(
    'today_bookings' => Salon_Booking_Database::get_bookings_count($today, $today),
    'week_bookings' => Salon_Booking_Database::get_bookings_count($this_week_start, $today),
    'month_bookings' => Salon_Booking_Database::get_bookings_count($this_month_start, $today),
    'total_bookings' => Salon_Booking_Database::get_bookings_count(),
    'pending_bookings' => Salon_Booking_Database::get_bookings_count(null, null, 'pending'),
    'confirmed_bookings' => Salon_Booking_Database::get_bookings_count(null, null, 'confirmed'),
    'completed_bookings' => Salon_Booking_Database::get_bookings_count(null, null, 'completed'),
    'cancelled_bookings' => Salon_Booking_Database::get_bookings_count(null, null, 'cancelled')
);

// Get recent bookings
$recent_bookings = Salon_Booking_Database::get_bookings(array(
    'limit' => 5,
    'order_by' => 'created_at',
    'order' => 'DESC'
));

// Get upcoming bookings
$upcoming_bookings = Salon_Booking_Database::get_bookings(array(
    'date_from' => $today,
    'limit' => 5,
    'order_by' => 'booking_date, booking_time',
    'order' => 'ASC'
));

// Get revenue statistics
$revenue_stats = Salon_Booking_Database::get_revenue_stats();

?>

<div class="wrap salon-booking-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-calendar-alt"></span>
        Salon Booking Dashboard
    </h1>
    
    <div class="salon-booking-dashboard">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card today">
                <div class="stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['today_bookings']); ?></h3>
                    <p>Today's Bookings</p>
                </div>
            </div>
            
            <div class="stat-card week">
                <div class="stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['week_bookings']); ?></h3>
                    <p>This Week</p>
                </div>
            </div>
            
            <div class="stat-card month">
                <div class="stat-icon">
                    <span class="dashicons dashicons-chart-bar"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['month_bookings']); ?></h3>
                    <p>This Month</p>
                </div>
            </div>
            
            <div class="stat-card total">
                <div class="stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['total_bookings']); ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
        </div>

        <!-- Status Overview -->
        <div class="status-overview">
            <h2>Booking Status Overview</h2>
            <div class="status-grid">
                <div class="status-item pending">
                    <span class="status-count"><?php echo esc_html($stats['pending_bookings']); ?></span>
                    <span class="status-label">Pending</span>
                </div>
                <div class="status-item confirmed">
                    <span class="status-count"><?php echo esc_html($stats['confirmed_bookings']); ?></span>
                    <span class="status-label">Confirmed</span>
                </div>
                <div class="status-item completed">
                    <span class="status-count"><?php echo esc_html($stats['completed_bookings']); ?></span>
                    <span class="status-label">Completed</span>
                </div>
                <div class="status-item cancelled">
                    <span class="status-count"><?php echo esc_html($stats['cancelled_bookings']); ?></span>
                    <span class="status-label">Cancelled</span>
                </div>
            </div>
        </div>

        <!-- Revenue Overview -->
        <?php if (!empty($revenue_stats)): ?>
        <div class="revenue-overview">
            <h2>Revenue Overview</h2>
            <div class="revenue-grid">
                <div class="revenue-item">
                    <h3>R<?php echo number_format($revenue_stats['today'] ?? 0, 2); ?></h3>
                    <p>Today's Revenue</p>
                </div>
                <div class="revenue-item">
                    <h3>R<?php echo number_format($revenue_stats['week'] ?? 0, 2); ?></h3>
                    <p>This Week</p>
                </div>
                <div class="revenue-item">
                    <h3>R<?php echo number_format($revenue_stats['month'] ?? 0, 2); ?></h3>
                    <p>This Month</p>
                </div>
                <div class="revenue-item">
                    <h3>R<?php echo number_format($revenue_stats['total'] ?? 0, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content Grid -->
        <div class="dashboard-content">
            <!-- Recent Bookings -->
            <div class="dashboard-section recent-bookings">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings'); ?>" class="button button-secondary">View All</a>
                </div>
                
                <?php if (!empty($recent_bookings)): ?>
                    <div class="bookings-list">
                        <?php foreach ($recent_bookings as $booking): 
                            $service = Salon_Booking_Database::get_service($booking->service_id);
                            $staff = Salon_Booking_Database::get_staff_member($booking->staff_id);
                        ?>
                            <div class="booking-item">
                                <div class="booking-info">
                                    <h4><?php echo esc_html($booking->client_name); ?></h4>
                                    <p class="service-info">
                                        <?php echo esc_html($service ? $service->name : 'Unknown Service'); ?>
                                        with <?php echo esc_html($staff ? $staff->name : 'Unknown Staff'); ?>
                                    </p>
                                    <p class="booking-time">
                                        <?php echo date('M j, Y \a\t g:i A', strtotime($booking->booking_date . ' ' . $booking->booking_time)); ?>
                                    </p>
                                </div>
                                <div class="booking-status">
                                    <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                        <?php echo esc_html(ucfirst($booking->status)); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <p>No recent bookings found.</p>
                        <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings&action=add'); ?>" class="button button-primary">Add New Booking</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Bookings -->
            <div class="dashboard-section upcoming-bookings">
                <div class="section-header">
                    <h2>Upcoming Bookings</h2>
                    <a href="<?php echo admin_url('admin.php?page=salon-booking-calendar'); ?>" class="button button-secondary">View Calendar</a>
                </div>
                
                <?php if (!empty($upcoming_bookings)): ?>
                    <div class="bookings-list">
                        <?php foreach ($upcoming_bookings as $booking): 
                            $service = Salon_Booking_Database::get_service($booking->service_id);
                            $staff = Salon_Booking_Database::get_staff_member($booking->staff_id);
                            $is_today = date('Y-m-d') === $booking->booking_date;
                        ?>
                            <div class="booking-item <?php echo $is_today ? 'today' : ''; ?>">
                                <div class="booking-info">
                                    <h4><?php echo esc_html($booking->client_name); ?></h4>
                                    <p class="service-info">
                                        <?php echo esc_html($service ? $service->name : 'Unknown Service'); ?>
                                        with <?php echo esc_html($staff ? $staff->name : 'Unknown Staff'); ?>
                                    </p>
                                    <p class="booking-time">
                                        <?php 
                                        if ($is_today) {
                                            echo 'Today at ' . date('g:i A', strtotime($booking->booking_time));
                                        } else {
                                            echo date('M j, Y \a\t g:i A', strtotime($booking->booking_date . ' ' . $booking->booking_time));
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div class="booking-actions">
                                    <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                        <?php echo esc_html(ucfirst($booking->status)); ?>
                                    </span>
                                    <?php if ($booking->status === 'pending'): ?>
                                        <button class="button button-small confirm-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                                            Confirm
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <p>No upcoming bookings found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings&action=add'); ?>" class="action-card">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span>Add New Booking</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=salon-booking-services'); ?>" class="action-card">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <span>Manage Services</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=salon-booking-staff'); ?>" class="action-card">
                    <span class="dashicons dashicons-admin-users"></span>
                    <span>Manage Staff</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=salon-booking-settings'); ?>" class="action-card">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span>Settings</span>
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="system-status">
            <h2>System Status</h2>
            <div class="status-checks">
                <?php
                // Check Stripe configuration
                $stripe_configured = !empty(get_option('salon_booking_settings')['stripe_test_secret_key']) || 
                                   !empty(get_option('salon_booking_settings')['stripe_live_secret_key']);
                
                // Check email configuration
                $email_configured = !empty(get_option('salon_booking_settings')['salon_email']);
                
                // Check if booking page exists
                $booking_page_exists = get_page_by_title('Book Appointment') !== null;
                ?>
                
                <div class="status-check <?php echo $stripe_configured ? 'success' : 'warning'; ?>">
                    <span class="dashicons <?php echo $stripe_configured ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <span>Stripe Payment Gateway</span>
                    <?php if (!$stripe_configured): ?>
                        <a href="<?php echo admin_url('admin.php?page=salon-booking-settings'); ?>" class="configure-link">Configure</a>
                    <?php endif; ?>
                </div>
                
                <div class="status-check <?php echo $email_configured ? 'success' : 'warning'; ?>">
                    <span class="dashicons <?php echo $email_configured ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <span>Email Notifications</span>
                    <?php if (!$email_configured): ?>
                        <a href="<?php echo admin_url('admin.php?page=salon-booking-settings'); ?>" class="configure-link">Configure</a>
                    <?php endif; ?>
                </div>
                
                <div class="status-check <?php echo $booking_page_exists ? 'success' : 'warning'; ?>">
                    <span class="dashicons <?php echo $booking_page_exists ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <span>Booking Page</span>
                    <?php if (!$booking_page_exists): ?>
                        <span class="configure-link">Missing - Check plugin activation</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.salon-booking-admin {
    margin: 20px 0;
}

.salon-booking-dashboard {
    max-width: 1200px;
}

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.stat-icon {
    margin-right: 15px;
    padding: 15px;
    border-radius: 50%;
    background: #f0f0f1;
}

.stat-card.today .stat-icon { background: #e3f2fd; color: #1976d2; }
.stat-card.week .stat-icon { background: #f3e5f5; color: #7b1fa2; }
.stat-card.month .stat-icon { background: #e8f5e8; color: #388e3c; }
.stat-card.total .stat-icon { background: #fff3e0; color: #f57c00; }

.stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: bold;
    color: #333;
}

.stat-content p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

/* Status Overview */
.status-overview {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.status-overview h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.status-item {
    text-align: center;
    padding: 15px;
    border-radius: 6px;
    border: 2px solid transparent;
}

.status-item.pending { background: #fff3cd; border-color: #ffeaa7; }
.status-item.confirmed { background: #d4edda; border-color: #c3e6cb; }
.status-item.completed { background: #cce5ff; border-color: #b3d9ff; }
.status-item.cancelled { background: #f8d7da; border-color: #f5c6cb; }

.status-count {
    display: block;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.status-label {
    font-size: 14px;
    color: #666;
}

/* Revenue Overview */
.revenue-overview {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.revenue-overview h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.revenue-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.revenue-item {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #d4af37, #f4d03f);
    color: white;
    border-radius: 8px;
}

.revenue-item h3 {
    margin: 0 0 10px 0;
    font-size: 24px;
    font-weight: bold;
}

.revenue-item p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

/* Dashboard Content */
.dashboard-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .dashboard-content {
        grid-template-columns: 1fr;
    }
}

.dashboard-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.section-header h2 {
    margin: 0;
    color: #333;
}

.bookings-list {
    space-y: 15px;
}

.booking-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 6px;
    margin-bottom: 10px;
    transition: background-color 0.2s ease;
}

.booking-item:hover {
    background-color: #f9f9f9;
}

.booking-item.today {
    border-left: 4px solid #d4af37;
    background-color: #fefdf8;
}

.booking-info h4 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 16px;
}

.booking-info p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.service-info {
    font-weight: 500;
}

.booking-time {
    font-size: 13px !important;
    color: #888 !important;
}

.booking-status,
.booking-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d4edda; color: #155724; }
.status-completed { background: #cce5ff; color: #004085; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.no-bookings {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

/* Quick Actions */
.quick-actions {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.quick-actions h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-card {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s ease;
}

.action-card:hover {
    background: #e9ecef;
    border-color: #d4af37;
    color: #333;
    text-decoration: none;
    transform: translateY(-1px);
}

.action-card .dashicons {
    margin-right: 10px;
    color: #d4af37;
}

/* System Status */
.system-status {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.system-status h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.status-checks {
    space-y: 10px;
}

.status-check {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.status-check.success {
    background: #d4edda;
    color: #155724;
}

.status-check.warning {
    background: #fff3cd;
    color: #856404;
}

.status-check .dashicons {
    margin-right: 10px;
}

.configure-link {
    margin-left: auto;
    font-size: 12px;
    text-decoration: underline;
}

.confirm-booking {
    background: #d4af37 !important;
    border-color: #d4af37 !important;
    color: white !important;
}

.confirm-booking:hover {
    background: #b8941f !important;
    border-color: #b8941f !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle booking confirmation
    $('.confirm-booking').on('click', function() {
        const bookingId = $(this).data('booking-id');
        const button = $(this);
        
        if (confirm('Are you sure you want to confirm this booking?')) {
            button.prop('disabled', true).text('Confirming...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'salon_booking_update_status',
                    booking_id: bookingId,
                    status: 'confirmed',
                    nonce: '<?php echo wp_create_nonce('salon_booking_admin'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error confirming booking: ' + response.data.message);
                        button.prop('disabled', false).text('Confirm');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    button.prop('disabled', false).text('Confirm');
                }
            });
        }
    });
});
</script>