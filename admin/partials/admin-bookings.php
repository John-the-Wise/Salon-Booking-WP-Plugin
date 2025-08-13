<?php

/**
 * Provide an admin area view for managing bookings
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salon_booking_nonce'])) {
    if (wp_verify_nonce($_POST['salon_booking_nonce'], 'salon_booking_admin')) {
        if ($action === 'add' || $action === 'edit') {
            $booking_data = array(
                'client_name' => sanitize_text_field($_POST['client_name']),
                'client_email' => sanitize_email($_POST['client_email']),
                'client_phone' => sanitize_text_field($_POST['client_phone']),
                'service_id' => intval($_POST['service_id']),
                'staff_id' => intval($_POST['staff_id']),
                'booking_date' => sanitize_text_field($_POST['booking_date']),
                'booking_time' => sanitize_text_field($_POST['booking_time']),
                'status' => sanitize_text_field($_POST['status']),
                'notes' => sanitize_textarea_field($_POST['notes']),
                'payment_amount' => floatval($_POST['payment_amount']),
                'payment_status' => sanitize_text_field($_POST['payment_status'])
            );
            
            if ($action === 'add') {
                $result = Salon_Booking_Database::save_booking($booking_data);
                if ($result) {
                    echo '<div class="notice notice-success"><p>Booking added successfully!</p></div>';
                    $action = 'list';
                } else {
                    echo '<div class="notice notice-error"><p>Error adding booking. Please try again.</p></div>';
                }
            } else {
                $booking_data['id'] = $booking_id;
                $result = Salon_Booking_Database::save_booking($booking_data);
                if ($result) {
                    echo '<div class="notice notice-success"><p>Booking updated successfully!</p></div>';
                    $action = 'list';
                } else {
                    echo '<div class="notice notice-error"><p>Error updating booking. Please try again.</p></div>';
                }
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $booking_id && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_booking_' . $booking_id)) {
        $result = Salon_Booking_Database::delete_booking($booking_id);
        if ($result) {
            echo '<div class="notice notice-success"><p>Booking deleted successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error deleting booking. Please try again.</p></div>';
        }
        $action = 'list';
    }
}

// Get data for forms
$services = Salon_Booking_Database::get_services();
$staff_members = Salon_Booking_Database::get_staff_members();

if ($action === 'edit' && $booking_id) {
    $booking = Salon_Booking_Database::get_booking($booking_id);
    if (!$booking) {
        echo '<div class="notice notice-error"><p>Booking not found.</p></div>';
        $action = 'list';
    }
}

?>

<div class="wrap salon-booking-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-calendar-alt"></span>
        Manage Bookings
    </h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings&action=add'); ?>" class="page-title-action">Add New Booking</a>
        
        <div class="salon-booking-bookings">
            <!-- Filters -->
            <div class="booking-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="salon-booking-bookings">
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="filter_status">Status:</label>
                            <select name="filter_status" id="filter_status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php selected(isset($_GET['filter_status']) ? $_GET['filter_status'] : '', 'pending'); ?>>Pending</option>
                                <option value="confirmed" <?php selected(isset($_GET['filter_status']) ? $_GET['filter_status'] : '', 'confirmed'); ?>>Confirmed</option>
                                <option value="completed" <?php selected(isset($_GET['filter_status']) ? $_GET['filter_status'] : '', 'completed'); ?>>Completed</option>
                                <option value="cancelled" <?php selected(isset($_GET['filter_status']) ? $_GET['filter_status'] : '', 'cancelled'); ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter_service">Service:</label>
                            <select name="filter_service" id="filter_service">
                                <option value="">All Services</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo esc_attr($service->id); ?>" <?php selected(isset($_GET['filter_service']) ? $_GET['filter_service'] : '', $service->id); ?>>
                                        <?php echo esc_html($service->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter_staff">Staff:</label>
                            <select name="filter_staff" id="filter_staff">
                                <option value="">All Staff</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?php echo esc_attr($staff->id); ?>" <?php selected(isset($_GET['filter_staff']) ? $_GET['filter_staff'] : '', $staff->id); ?>>
                                        <?php echo esc_html($staff->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter_date_from">Date From:</label>
                            <input type="date" name="filter_date_from" id="filter_date_from" value="<?php echo esc_attr(isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : ''); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter_date_to">Date To:</label>
                            <input type="date" name="filter_date_to" id="filter_date_to" value="<?php echo esc_attr(isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : ''); ?>">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="button">Filter</button>
                            <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings'); ?>" class="button">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Bookings Table -->
            <?php
            // Build filter conditions
            $filters = array();
            if (!empty($_GET['filter_status'])) {
                $filters['status'] = sanitize_text_field($_GET['filter_status']);
            }
            if (!empty($_GET['filter_service'])) {
                $filters['service_id'] = intval($_GET['filter_service']);
            }
            if (!empty($_GET['filter_staff'])) {
                $filters['staff_id'] = intval($_GET['filter_staff']);
            }
            if (!empty($_GET['filter_date_from'])) {
                $filters['date_from'] = sanitize_text_field($_GET['filter_date_from']);
            }
            if (!empty($_GET['filter_date_to'])) {
                $filters['date_to'] = sanitize_text_field($_GET['filter_date_to']);
            }
            
            $bookings = Salon_Booking_Database::get_bookings($filters);
            ?>
            
            <div class="bookings-table-container">
                <table class="wp-list-table widefat fixed striped bookings-table">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-client">Client</th>
                            <th scope="col" class="manage-column column-service">Service</th>
                            <th scope="col" class="manage-column column-staff">Staff</th>
                            <th scope="col" class="manage-column column-datetime">Date & Time</th>
                            <th scope="col" class="manage-column column-status">Status</th>
                            <th scope="col" class="manage-column column-payment">Payment</th>
                            <th scope="col" class="manage-column column-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $booking): 
                                $service = Salon_Booking_Database::get_service($booking->service_id);
                                $staff = Salon_Booking_Database::get_staff_member($booking->staff_id);
                            ?>
                                <tr>
                                    <td class="column-client">
                                        <strong><?php echo esc_html($booking->client_name); ?></strong><br>
                                        <small>
                                            <?php echo esc_html($booking->client_email); ?><br>
                                            <?php echo esc_html($booking->client_phone); ?>
                                        </small>
                                    </td>
                                    <td class="column-service">
                                        <?php echo esc_html($service ? $service->name : 'Unknown Service'); ?><br>
                                        <small>R<?php echo number_format($service ? $service->price : 0, 2); ?></small>
                                    </td>
                                    <td class="column-staff">
                                        <?php echo esc_html($staff ? $staff->name : 'Unknown Staff'); ?>
                                    </td>
                                    <td class="column-datetime">
                                        <?php echo date('M j, Y', strtotime($booking->booking_date)); ?><br>
                                        <small><?php echo date('g:i A', strtotime($booking->booking_time)); ?></small>
                                    </td>
                                    <td class="column-status">
                                        <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                            <?php echo esc_html(ucfirst($booking->status)); ?>
                                        </span>
                                    </td>
                                    <td class="column-payment">
                                        R<?php echo number_format($booking->payment_amount, 2); ?><br>
                                        <small class="payment-status payment-<?php echo esc_attr($booking->payment_status); ?>">
                                            <?php echo esc_html(ucfirst($booking->payment_status)); ?>
                                        </small>
                                    </td>
                                    <td class="column-actions">
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings&action=edit&booking_id=' . $booking->id); ?>">Edit</a> |
                                            </span>
                                            <span class="view">
                                                <a href="#" class="view-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">View</a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=salon-booking-bookings&action=delete&booking_id=' . $booking->id), 'delete_booking_' . $booking->id); ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this booking?')" class="delete-link">Delete</a>
                                            </span>
                                        </div>
                                        
                                        <?php if ($booking->status === 'pending'): ?>
                                            <div class="quick-actions">
                                                <button class="button button-small confirm-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">Confirm</button>
                                                <button class="button button-small cancel-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">Cancel</button>
                                            </div>
                                        <?php elseif ($booking->status === 'confirmed'): ?>
                                            <div class="quick-actions">
                                                <button class="button button-small complete-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">Complete</button>
                                                <button class="button button-small cancel-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">Cancel</button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-bookings">
                                    <p>No bookings found.</p>
                                    <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings&action=add'); ?>" class="button button-primary">Add New Booking</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings'); ?>" class="page-title-action">← Back to Bookings</a>
        
        <div class="salon-booking-form">
            <form method="post" action="">
                <?php wp_nonce_field('salon_booking_admin', 'salon_booking_nonce'); ?>
                
                <div class="form-grid">
                    <div class="form-section client-details">
                        <h3>Client Details</h3>
                        
                        <div class="form-row">
                            <label for="client_name">Client Name *</label>
                            <input type="text" name="client_name" id="client_name" 
                                   value="<?php echo esc_attr(isset($booking) ? $booking->client_name : ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="client_email">Email Address *</label>
                            <input type="email" name="client_email" id="client_email" 
                                   value="<?php echo esc_attr(isset($booking) ? $booking->client_email : ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="client_phone">Phone Number *</label>
                            <input type="tel" name="client_phone" id="client_phone" 
                                   value="<?php echo esc_attr(isset($booking) ? $booking->client_phone : ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-section booking-details">
                        <h3>Booking Details</h3>
                        
                        <div class="form-row">
                            <label for="service_id">Service *</label>
                            <select name="service_id" id="service_id" required>
                                <option value="">Select a service</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo esc_attr($service->id); ?>" 
                                            data-price="<?php echo esc_attr($service->price); ?>"
                                            <?php selected(isset($booking) ? $booking->service_id : '', $service->id); ?>>
                                        <?php echo esc_html($service->name); ?> - R<?php echo number_format($service->price, 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label for="staff_id">Staff Member *</label>
                            <select name="staff_id" id="staff_id" required>
                                <option value="">Select staff member</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?php echo esc_attr($staff->id); ?>" 
                                            <?php selected(isset($booking) ? $booking->staff_id : '', $staff->id); ?>>
                                        <?php echo esc_html($staff->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label for="booking_date">Booking Date *</label>
                            <input type="date" name="booking_date" id="booking_date" 
                                   value="<?php echo esc_attr(isset($booking) ? $booking->booking_date : ''); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="booking_time">Booking Time *</label>
                            <select name="booking_time" id="booking_time" required>
                                <option value="">Select time</option>
                                <?php
                                // Generate time slots from 9 AM to 6 PM
                                for ($hour = 9; $hour <= 18; $hour++) {
                                    for ($minute = 0; $minute < 60; $minute += 30) {
                                        $time = sprintf('%02d:%02d', $hour, $minute);
                                        $display_time = date('g:i A', strtotime($time));
                                        $selected = isset($booking) && $booking->booking_time === $time ? 'selected' : '';
                                        echo "<option value='{$time}' {$selected}>{$display_time}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label for="status">Status *</label>
                            <select name="status" id="status" required>
                                <option value="pending" <?php selected(isset($booking) ? $booking->status : 'pending', 'pending'); ?>>Pending</option>
                                <option value="confirmed" <?php selected(isset($booking) ? $booking->status : '', 'confirmed'); ?>>Confirmed</option>
                                <option value="completed" <?php selected(isset($booking) ? $booking->status : '', 'completed'); ?>>Completed</option>
                                <option value="cancelled" <?php selected(isset($booking) ? $booking->status : '', 'cancelled'); ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" rows="3"><?php echo esc_textarea(isset($booking) ? $booking->notes : ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section payment-details">
                        <h3>Payment Details</h3>
                        
                        <div class="form-row">
                            <label for="payment_amount">Payment Amount *</label>
                            <input type="number" name="payment_amount" id="payment_amount" step="0.01" min="0"
                                   value="<?php echo esc_attr(isset($booking) ? $booking->payment_amount : ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="payment_status">Payment Status *</label>
                            <select name="payment_status" id="payment_status" required>
                                <option value="pending" <?php selected(isset($booking) ? $booking->payment_status : 'pending', 'pending'); ?>>Pending</option>
                                <option value="paid" <?php selected(isset($booking) ? $booking->payment_status : '', 'paid'); ?>>Paid</option>
                                <option value="failed" <?php selected(isset($booking) ? $booking->payment_status : '', 'failed'); ?>>Failed</option>
                                <option value="refunded" <?php selected(isset($booking) ? $booking->payment_status : '', 'refunded'); ?>>Refunded</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo $action === 'add' ? 'Add Booking' : 'Update Booking'; ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings'); ?>" class="button">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Booking Details Modal -->
<div id="booking-details-modal" class="booking-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Booking Details</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="booking-details-content"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="button modal-close">Close</button>
        </div>
    </div>
</div>

<style>
.salon-booking-admin {
    margin: 20px 0;
}

/* Filters */
.booking-filters {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}

.filter-group label {
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
}

.filter-group select,
.filter-group input {
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: end;
}

/* Bookings Table */
.bookings-table-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.bookings-table {
    margin: 0;
}

.bookings-table th {
    background: #f8f9fa;
    font-weight: 600;
    padding: 12px;
}

.bookings-table td {
    padding: 12px;
    vertical-align: top;
}

.column-client strong {
    color: #333;
}

.column-client small {
    color: #666;
    line-height: 1.4;
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

.payment-status {
    font-weight: 500;
}

.payment-pending { color: #856404; }
.payment-paid { color: #155724; }
.payment-failed { color: #721c24; }
.payment-refunded { color: #004085; }

.quick-actions {
    margin-top: 8px;
    display: flex;
    gap: 5px;
}

.quick-actions .button {
    font-size: 11px;
    padding: 2px 6px;
    height: auto;
    line-height: 1.4;
}

.confirm-booking {
    background: #d4af37 !important;
    border-color: #d4af37 !important;
    color: white !important;
}

.complete-booking {
    background: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.cancel-booking {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
    color: white !important;
}

.no-bookings {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

/* Form Styles */
.salon-booking-form {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    max-width: 1000px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-section {
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 20px;
}

.form-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.form-row {
    margin-bottom: 15px;
}

.form-row label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
}

.form-row input,
.form-row select,
.form-row textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-row input:focus,
.form-row select:focus,
.form-row textarea:focus {
    border-color: #d4af37;
    outline: none;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}

.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

/* Modal Styles */
.booking-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.delete-link {
    color: #dc3545 !important;
}

.delete-link:hover {
    color: #c82333 !important;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .filter-actions {
        align-items: stretch;
    }
    
    .bookings-table {
        font-size: 12px;
    }
    
    .bookings-table th,
    .bookings-table td {
        padding: 8px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle booking status updates
    $('.confirm-booking, .complete-booking, .cancel-booking').on('click', function() {
        const bookingId = $(this).data('booking-id');
        const button = $(this);
        let status = '';
        let confirmMessage = '';
        
        if (button.hasClass('confirm-booking')) {
            status = 'confirmed';
            confirmMessage = 'Are you sure you want to confirm this booking?';
        } else if (button.hasClass('complete-booking')) {
            status = 'completed';
            confirmMessage = 'Are you sure you want to mark this booking as completed?';
        } else if (button.hasClass('cancel-booking')) {
            status = 'cancelled';
            confirmMessage = 'Are you sure you want to cancel this booking?';
        }
        
        if (confirm(confirmMessage)) {
            button.prop('disabled', true).text('Updating...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'salon_booking_update_status',
                    booking_id: bookingId,
                    status: status,
                    nonce: '<?php echo wp_create_nonce('salon_booking_admin'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error updating booking: ' + response.data.message);
                        button.prop('disabled', false).text(button.data('original-text'));
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    button.prop('disabled', false).text(button.data('original-text'));
                }
            });
        }
    });
    
    // Store original button text
    $('.confirm-booking, .complete-booking, .cancel-booking').each(function() {
        $(this).data('original-text', $(this).text());
    });
    
    // Handle view booking
    $('.view-booking').on('click', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('booking-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'salon_booking_get_booking_details',
                booking_id: bookingId,
                nonce: '<?php echo wp_create_nonce('salon_booking_admin'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#booking-details-content').html(response.data.html);
                    $('#booking-details-modal').show();
                } else {
                    alert('Error loading booking details: ' + response.data.message);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
            }
        });
    });
    
    // Handle modal close
    $('.modal-close').on('click', function() {
        $('#booking-details-modal').hide();
    });
    
    // Close modal on outside click
    $('#booking-details-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Auto-fill payment amount when service is selected
    $('#service_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price');
        if (price) {
            $('#payment_amount').val(price);
        }
    });
    
    // Validate booking date and time
    $('#booking_date, #booking_time, #staff_id').on('change', function() {
        const date = $('#booking_date').val();
        const time = $('#booking_time').val();
        const staffId = $('#staff_id').val();
        
        if (date && time && staffId) {
            // Check availability
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'salon_booking_check_availability',
                    date: date,
                    time: time,
                    staff_id: staffId,
                    booking_id: <?php echo isset($booking) ? $booking->id : 0; ?>,
                    nonce: '<?php echo wp_create_nonce('salon_booking_admin'); ?>'
                },
                success: function(response) {
                    if (!response.success) {
                        alert('This time slot is not available. Please select a different time.');
                        $('#booking_time').val('');
                    }
                }
            });
        }
    });
});
</script>