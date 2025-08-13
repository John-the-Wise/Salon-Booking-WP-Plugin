<?php

/**
 * Provide an admin area view for managing staff
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salon_booking_nonce'])) {
    if (wp_verify_nonce($_POST['salon_booking_nonce'], 'salon_booking_admin')) {
        if ($action === 'add' || $action === 'edit') {
            $staff_data = array(
                'name' => sanitize_text_field($_POST['name']),
                'email' => sanitize_email($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'bio' => sanitize_textarea_field($_POST['bio']),
                'specialties' => sanitize_textarea_field($_POST['specialties']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            );
            
            if ($action === 'add') {
                $result = Salon_Booking_Database::save_staff_member($staff_data);
                if ($result) {
                    echo '<div class="notice notice-success"><p>Staff member added successfully!</p></div>';
                    $action = 'list';
                } else {
                    echo '<div class="notice notice-error"><p>Error adding staff member. Please try again.</p></div>';
                }
            } else {
                $staff_data['id'] = $staff_id;
                $result = Salon_Booking_Database::save_staff_member($staff_data);
                if ($result) {
                    echo '<div class="notice notice-success"><p>Staff member updated successfully!</p></div>';
                    $action = 'list';
                } else {
                    echo '<div class="notice notice-error"><p>Error updating staff member. Please try again.</p></div>';
                }
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $staff_id && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_staff_' . $staff_id)) {
        // Check if staff member has bookings
        $bookings_count = Salon_Booking_Database::get_bookings_count(null, null, null, $staff_id);
        if ($bookings_count > 0) {
            echo '<div class="notice notice-error"><p>Cannot delete staff member. They have existing bookings. Please deactivate instead.</p></div>';
        } else {
            $result = Salon_Booking_Database::delete_staff_member($staff_id);
            if ($result) {
                echo '<div class="notice notice-success"><p>Staff member deleted successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error deleting staff member. Please try again.</p></div>';
            }
        }
        $action = 'list';
    }
}

// Handle toggle active status
if ($action === 'toggle_active' && $staff_id && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'toggle_active_' . $staff_id)) {
        $staff = Salon_Booking_Database::get_staff_member($staff_id);
        if ($staff) {
            $new_status = $staff->is_active ? 0 : 1;
            $result = Salon_Booking_Database::save_staff_member(array(
                'id' => $staff_id,
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'bio' => $staff->bio,
                'specialties' => $staff->specialties,
                'is_active' => $new_status
            ));
            
            if ($result) {
                $status_text = $new_status ? 'activated' : 'deactivated';
                echo '<div class="notice notice-success"><p>Staff member ' . $status_text . ' successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error updating staff member status. Please try again.</p></div>';
            }
        }
        $action = 'list';
    }
}

if ($action === 'edit' && $staff_id) {
    $staff = Salon_Booking_Database::get_staff_member($staff_id);
    if (!$staff) {
        echo '<div class="notice notice-error"><p>Staff member not found.</p></div>';
        $action = 'list';
    }
}

?>

<div class="wrap salon-booking-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-users"></span>
        Manage Staff
    </h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-staff&action=add'); ?>" class="page-title-action">Add New Staff Member</a>
        
        <div class="salon-booking-staff">
            <?php
            $staff_members = Salon_Booking_Database::get_staff_members(true); // Include inactive
            ?>
            
            <div class="staff-grid">
                <?php if (!empty($staff_members)): ?>
                    <?php foreach ($staff_members as $staff): 
                        $bookings_count = Salon_Booking_Database::get_bookings_count(null, null, null, $staff->id);
                        $upcoming_bookings = Salon_Booking_Database::get_bookings_count(date('Y-m-d'), null, null, $staff->id);
                    ?>
                        <div class="staff-card <?php echo $staff->is_active ? 'active' : 'inactive'; ?>">
                            <div class="staff-header">
                                <div class="staff-avatar">
                                    <?php echo strtoupper(substr($staff->name, 0, 2)); ?>
                                </div>
                                <div class="staff-info">
                                    <h3><?php echo esc_html($staff->name); ?></h3>
                                    <p class="staff-email"><?php echo esc_html($staff->email); ?></p>
                                    <p class="staff-phone"><?php echo esc_html($staff->phone); ?></p>
                                </div>
                                <div class="staff-status">
                                    <span class="status-badge <?php echo $staff->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $staff->is_active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if (!empty($staff->bio)): ?>
                                <div class="staff-bio">
                                    <h4>Bio</h4>
                                    <p><?php echo esc_html($staff->bio); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($staff->specialties)): ?>
                                <div class="staff-specialties">
                                    <h4>Specialties</h4>
                                    <p><?php echo esc_html($staff->specialties); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="staff-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $bookings_count; ?></span>
                                    <span class="stat-label">Total Bookings</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $upcoming_bookings; ?></span>
                                    <span class="stat-label">Upcoming</span>
                                </div>
                            </div>
                            
                            <div class="staff-actions">
                                <a href="<?php echo admin_url('admin.php?page=salon-booking-staff&action=edit&staff_id=' . $staff->id); ?>" 
                                   class="button button-secondary">Edit</a>
                                
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=salon-booking-staff&action=toggle_active&staff_id=' . $staff->id), 'toggle_active_' . $staff->id); ?>" 
                                   class="button <?php echo $staff->is_active ? 'deactivate-btn' : 'activate-btn'; ?>">
                                    <?php echo $staff->is_active ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                
                                <a href="<?php echo admin_url('admin.php?page=salon-booking-staff&action=availability&staff_id=' . $staff->id); ?>" 
                                   class="button button-primary">Availability</a>
                                
                                <?php if ($bookings_count === 0): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=salon-booking-staff&action=delete&staff_id=' . $staff->id), 'delete_staff_' . $staff->id); ?>" 
                                       onclick="return confirm('Are you sure you want to delete this staff member? This action cannot be undone.')" 
                                       class="button delete-btn">Delete</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-staff">
                        <div class="no-staff-content">
                            <span class="dashicons dashicons-admin-users"></span>
                            <h3>No Staff Members Found</h3>
                            <p>Add your first staff member to start managing your salon team.</p>
                            <a href="<?php echo admin_url('admin.php?page=salon-booking-staff&action=add'); ?>" class="button button-primary">Add Staff Member</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-staff'); ?>" class="page-title-action">← Back to Staff</a>
        
        <div class="salon-booking-form">
            <form method="post" action="">
                <?php wp_nonce_field('salon_booking_admin', 'salon_booking_nonce'); ?>
                
                <div class="form-grid">
                    <div class="form-section basic-info">
                        <h3>Basic Information</h3>
                        
                        <div class="form-row">
                            <label for="name">Full Name *</label>
                            <input type="text" name="name" id="name" 
                                   value="<?php echo esc_attr(isset($staff) ? $staff->name : ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="email">Email Address *</label>
                            <input type="email" name="email" id="email" 
                                   value="<?php echo esc_attr(isset($staff) ? $staff->email : ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" name="phone" id="phone" 
                                   value="<?php echo esc_attr(isset($staff) ? $staff->phone : ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" id="is_active" 
                                       <?php checked(isset($staff) ? $staff->is_active : 1, 1); ?>>
                                <span class="checkmark"></span>
                                Active (can receive bookings)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-section additional-info">
                        <h3>Additional Information</h3>
                        
                        <div class="form-row">
                            <label for="bio">Bio</label>
                            <textarea name="bio" id="bio" rows="4" 
                                      placeholder="Brief description about the staff member..."><?php echo esc_textarea(isset($staff) ? $staff->bio : ''); ?></textarea>
                            <small class="form-help">This will be displayed to clients when they select a staff member.</small>
                        </div>
                        
                        <div class="form-row">
                            <label for="specialties">Specialties</label>
                            <textarea name="specialties" id="specialties" rows="3" 
                                      placeholder="Hair cutting, coloring, styling, etc."><?php echo esc_textarea(isset($staff) ? $staff->specialties : ''); ?></textarea>
                            <small class="form-help">List the services this staff member specializes in.</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo $action === 'add' ? 'Add Staff Member' : 'Update Staff Member'; ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=salon-booking-staff'); ?>" class="button">Cancel</a>
                </div>
            </form>
        </div>
        
    <?php elseif ($action === 'availability'): ?>
        <?php
        $staff = Salon_Booking_Database::get_staff_member($staff_id);
        if (!$staff) {
            echo '<div class="notice notice-error"><p>Staff member not found.</p></div>';
        } else {
            $availability = Salon_Booking_Database::get_staff_availability($staff_id);
        ?>
            <a href="<?php echo admin_url('admin.php?page=salon-booking-staff'); ?>" class="page-title-action">← Back to Staff</a>
            
            <div class="staff-availability">
                <div class="availability-header">
                    <h2>Availability for <?php echo esc_html($staff->name); ?></h2>
                    <p>Set the working hours for each day of the week. Leave times empty for days off.</p>
                </div>
                
                <form method="post" id="availability-form">
                    <?php wp_nonce_field('salon_booking_admin', 'salon_booking_nonce'); ?>
                    <input type="hidden" name="action" value="save_availability">
                    <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
                    
                    <div class="availability-grid">
                        <?php
                        $days = array(
                            'monday' => 'Monday',
                            'tuesday' => 'Tuesday',
                            'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday',
                            'friday' => 'Friday',
                            'saturday' => 'Saturday',
                            'sunday' => 'Sunday'
                        );
                        
                        foreach ($days as $day => $label):
                            $day_availability = null;
                            if ($availability) {
                                foreach ($availability as $avail) {
                                    if ($avail->day_of_week === $day) {
                                        $day_availability = $avail;
                                        break;
                                    }
                                }
                            }
                            
                            // Set default times based on salon's actual trading hours
                            $default_start = '09:00';
                            $default_end = '18:00';
                            $default_available = true;
                            
                            if ($day === 'sunday' || $day === 'monday') {
                                $default_available = false;
                                $default_start = '';
                                $default_end = '';
                            } elseif ($day === 'friday' || $day === 'saturday') {
                                $default_end = '15:00';
                            }
                        ?>
                            <div class="availability-day">
                                <div class="day-header">
                                    <h4><?php echo $label; ?></h4>
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="day-toggle" data-day="<?php echo $day; ?>" 
                                               <?php checked($day_availability ? $day_availability->is_available : $default_available, 1); ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="time-inputs <?php echo (!$day_availability || !$day_availability->is_available) && !$default_available ? 'disabled' : ''; ?>">
                                    <div class="time-row">
                                        <label>Start Time:</label>
                                        <input type="time" name="<?php echo $day; ?>_start" 
                                               value="<?php echo esc_attr($day_availability ? $day_availability->start_time : $default_start); ?>" 
                                               <?php echo (!$day_availability || !$day_availability->is_available) && !$default_available ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="time-row">
                                        <label>End Time:</label>
                                        <input type="time" name="<?php echo $day; ?>_end" 
                                               value="<?php echo esc_attr($day_availability ? $day_availability->end_time : $default_end); ?>" 
                                               <?php echo (!$day_availability || !$day_availability->is_available) && !$default_available ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="time-row">
                                        <label>Break Start:</label>
                                        <input type="time" name="<?php echo $day; ?>_break_start" 
                                               value="<?php echo esc_attr($day_availability ? $day_availability->break_start : ''); ?>" 
                                               <?php echo (!$day_availability || !$day_availability->is_available) ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="time-row">
                                        <label>Break End:</label>
                                        <input type="time" name="<?php echo $day; ?>_break_end" 
                                               value="<?php echo esc_attr($day_availability ? $day_availability->break_end : ''); ?>" 
                                               <?php echo (!$day_availability || !$day_availability->is_available) ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">Save Availability</button>
                        <a href="<?php echo admin_url('admin.php?page=salon-booking-staff'); ?>" class="button">Cancel</a>
                    </div>
                </form>
            </div>
        <?php } ?>
    <?php endif; ?>
</div>

<style>
.salon-booking-admin {
    margin: 20px 0;
}

/* Staff Grid */
.staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.staff-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.staff-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.staff-card.inactive {
    opacity: 0.7;
    border-color: #ccc;
}

.staff-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
}

.staff-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d4af37, #f4d03f);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    margin-right: 15px;
    flex-shrink: 0;
}

.staff-info {
    flex: 1;
}

.staff-info h3 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 18px;
}

.staff-email,
.staff-phone {
    margin: 2px 0;
    color: #666;
    font-size: 14px;
}

.staff-status {
    margin-left: 10px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.staff-bio,
.staff-specialties {
    margin-bottom: 15px;
}

.staff-bio h4,
.staff-specialties h4 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 14px;
    font-weight: 600;
}

.staff-bio p,
.staff-specialties p {
    margin: 0;
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.staff-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 20px;
    font-weight: bold;
    color: #d4af37;
}

.stat-label {
    font-size: 12px;
    color: #666;
}

.staff-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.staff-actions .button {
    font-size: 12px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.4;
}

.activate-btn {
    background: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.deactivate-btn {
    background: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #212529 !important;
}

.delete-btn {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
    color: white !important;
}

/* No Staff State */
.no-staff {
    grid-column: 1 / -1;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
}

.no-staff-content {
    text-align: center;
    color: #666;
}

.no-staff-content .dashicons {
    font-size: 48px;
    margin-bottom: 20px;
    color: #ccc;
}

.no-staff-content h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.no-staff-content p {
    margin: 0 0 20px 0;
}

/* Form Styles */
.salon-booking-form {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    max-width: 800px;
    margin-top: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
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
.form-row textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-row input:focus,
.form-row textarea:focus {
    border-color: #d4af37;
    outline: none;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}

.form-help {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
    font-style: italic;
}

.checkbox-label {
    display: flex !important;
    align-items: center;
    cursor: pointer;
    font-weight: normal !important;
}

.checkbox-label input[type="checkbox"] {
    width: auto !important;
    margin-right: 8px;
}

.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

/* Availability Styles */
.staff-availability {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
    max-width: 1000px;
}

.availability-header {
    margin-bottom: 30px;
}

.availability-header h2 {
    margin: 0 0 10px 0;
    color: #333;
}

.availability-header p {
    margin: 0;
    color: #666;
}

.availability-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.availability-day {
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 15px;
}

.day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.day-header h4 {
    margin: 0;
    color: #333;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #d4af37;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.time-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.time-inputs.disabled {
    opacity: 0.5;
}

.time-row {
    display: flex;
    flex-direction: column;
}

.time-row label {
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #666;
}

.time-row input {
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.time-row input:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .staff-grid {
        grid-template-columns: 1fr;
    }
    
    .staff-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .staff-avatar {
        margin-bottom: 10px;
    }
    
    .staff-actions {
        justify-content: flex-start;
    }
    
    .availability-grid {
        grid-template-columns: 1fr;
    }
    
    .time-inputs {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle day toggle switches
    $('.day-toggle').on('change', function() {
        const day = $(this).data('day');
        const isChecked = $(this).is(':checked');
        const timeInputs = $(this).closest('.availability-day').find('.time-inputs');
        const inputs = timeInputs.find('input');
        
        if (isChecked) {
            timeInputs.removeClass('disabled');
            inputs.prop('disabled', false);
        } else {
            timeInputs.addClass('disabled');
            inputs.prop('disabled', true);
        }
    });
    
    // Handle availability form submission
    $('#availability-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            action: 'salon_booking_save_staff_availability',
            staff_id: formData.get('staff_id'),
            nonce: formData.get('salon_booking_nonce'),
            availability: {}
        };
        
        // Collect availability data
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        days.forEach(day => {
            const toggle = $('.day-toggle[data-day="' + day + '"]');
            if (toggle.is(':checked')) {
                data.availability[day] = {
                    is_available: 1,
                    start_time: formData.get(day + '_start'),
                    end_time: formData.get(day + '_end'),
                    break_start: formData.get(day + '_break_start'),
                    break_end: formData.get(day + '_break_end')
                };
            } else {
                data.availability[day] = {
                    is_available: 0
                };
            }
        });
        
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert('Availability saved successfully!');
                } else {
                    alert('Error saving availability: ' + response.data.message);
                }
                submitBtn.prop('disabled', false).text('Save Availability');
            },
            error: function() {
                alert('Network error. Please try again.');
                submitBtn.prop('disabled', false).text('Save Availability');
            }
        });
    });
    
    // Validate time inputs
    $('input[type="time"]').on('change', function() {
        const row = $(this).closest('.availability-day');
        const startTime = row.find('input[name$="_start"]').val();
        const endTime = row.find('input[name$="_end"]').val();
        const breakStart = row.find('input[name$="_break_start"]').val();
        const breakEnd = row.find('input[name$="_break_end"]').val();
        
        // Validate start time is before end time
        if (startTime && endTime && startTime >= endTime) {
            alert('Start time must be before end time.');
            $(this).val('');
            return;
        }
        
        // Validate break times
        if (breakStart && breakEnd) {
            if (breakStart >= breakEnd) {
                alert('Break start time must be before break end time.');
                $(this).val('');
                return;
            }
            
            if (startTime && endTime) {
                if (breakStart < startTime || breakEnd > endTime) {
                    alert('Break times must be within working hours.');
                    $(this).val('');
                    return;
                }
            }
        }
    });
});
</script>