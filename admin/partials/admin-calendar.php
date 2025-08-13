<?php

/**
 * Provide an admin area view for the calendar
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get staff members for filter
$staff_members = Salon_Booking_Database::get_staff_members();
$services = Salon_Booking_Database::get_services();

?>

<div class="wrap salon-booking-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-calendar-alt"></span>
        Booking Calendar
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=salon-booking-bookings&action=add'); ?>" class="page-title-action">Add New Booking</a>
    
    <div class="salon-booking-calendar">
        <!-- Calendar Filters -->
        <div class="calendar-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="calendar_staff_filter">Filter by Staff:</label>
                    <select id="calendar_staff_filter">
                        <option value="">All Staff</option>
                        <?php foreach ($staff_members as $staff): ?>
                            <option value="<?php echo esc_attr($staff->id); ?>">
                                <?php echo esc_html($staff->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="calendar_service_filter">Filter by Service:</label>
                    <select id="calendar_service_filter">
                        <option value="">All Services</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo esc_attr($service->id); ?>">
                                <?php echo esc_html($service->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="calendar_status_filter">Filter by Status:</label>
                    <select id="calendar_status_filter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="button" id="apply_calendar_filters" class="button">Apply Filters</button>
                    <button type="button" id="clear_calendar_filters" class="button">Clear</button>
                </div>
            </div>
        </div>
        
        <!-- Calendar Legend -->
        <div class="calendar-legend">
            <h3>Legend</h3>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="legend-color status-pending"></span>
                    <span>Pending</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color status-confirmed"></span>
                    <span>Confirmed</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color status-completed"></span>
                    <span>Completed</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color status-cancelled"></span>
                    <span>Cancelled</span>
                </div>
            </div>
        </div>
        
        <!-- Calendar Container -->
        <div class="calendar-container">
            <div id="salon-booking-calendar"></div>
        </div>
        
        <!-- Calendar Stats -->
        <div class="calendar-stats">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number" id="total-bookings-month">0</span>
                    <span class="stat-label">Total Bookings This Month</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="confirmed-bookings-month">0</span>
                    <span class="stat-label">Confirmed This Month</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="revenue-month">R0</span>
                    <span class="stat-label">Revenue This Month</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="pending-bookings">0</span>
                    <span class="stat-label">Pending Bookings</span>
                </div>
            </div>
        </div>
    </div>
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
            <button type="button" id="edit-booking-btn" class="button button-primary">Edit Booking</button>
        </div>
    </div>
</div>

<!-- Quick Add Booking Modal -->
<div id="quick-add-modal" class="booking-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Quick Add Booking</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="quick-add-form">
                <div class="form-row">
                    <label for="quick_client_name">Client Name *</label>
                    <input type="text" id="quick_client_name" name="client_name" required>
                </div>
                
                <div class="form-row">
                    <label for="quick_client_email">Email *</label>
                    <input type="email" id="quick_client_email" name="client_email" required>
                </div>
                
                <div class="form-row">
                    <label for="quick_client_phone">Phone *</label>
                    <input type="tel" id="quick_client_phone" name="client_phone" required>
                </div>
                
                <div class="form-row">
                    <label for="quick_service_id">Service *</label>
                    <select id="quick_service_id" name="service_id" required>
                        <option value="">Select a service</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo esc_attr($service->id); ?>" data-price="<?php echo esc_attr($service->price); ?>">
                                <?php echo esc_html($service->name); ?> - R<?php echo number_format($service->price, 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="quick_staff_id">Staff *</label>
                    <select id="quick_staff_id" name="staff_id" required>
                        <option value="">Select staff</option>
                        <?php foreach ($staff_members as $staff): ?>
                            <option value="<?php echo esc_attr($staff->id); ?>">
                                <?php echo esc_html($staff->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="quick_booking_date">Date *</label>
                    <input type="date" id="quick_booking_date" name="booking_date" required>
                </div>
                
                <div class="form-row">
                    <label for="quick_booking_time">Time *</label>
                    <select id="quick_booking_time" name="booking_time" required>
                        <option value="">Select time</option>
                        <?php
                        // Generate time slots from 9 AM to 6 PM
                        for ($hour = 9; $hour <= 18; $hour++) {
                            for ($minute = 0; $minute < 60; $minute += 30) {
                                $time = sprintf('%02d:%02d', $hour, $minute);
                                $display_time = date('g:i A', strtotime($time));
                                echo "<option value='{$time}'>{$display_time}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="quick_payment_amount">Payment Amount *</label>
                    <input type="number" id="quick_payment_amount" name="payment_amount" step="0.01" min="0" required>
                </div>
                
                <div class="form-row">
                    <label for="quick_notes">Notes</label>
                    <textarea id="quick_notes" name="notes" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button modal-close">Cancel</button>
            <button type="button" id="save-quick-booking" class="button button-primary">Save Booking</button>
        </div>
    </div>
</div>

<style>
.salon-booking-admin {
    margin: 20px 0;
}

.salon-booking-calendar {
    max-width: 1200px;
}

/* Calendar Filters */
.calendar-filters {
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

.filter-group select {
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: end;
}

/* Calendar Legend */
.calendar-legend {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.calendar-legend h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 16px;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    border: 1px solid rgba(0,0,0,0.1);
}

.legend-color.status-pending { background: #ffc107; }
.legend-color.status-confirmed { background: #28a745; }
.legend-color.status-completed { background: #007bff; }
.legend-color.status-cancelled { background: #dc3545; }

/* Calendar Container */
.calendar-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

#salon-booking-calendar {
    min-height: 600px;
}

/* FullCalendar Customizations */
.fc-event {
    border: none !important;
    border-radius: 4px !important;
    padding: 2px 4px !important;
    font-size: 12px !important;
    cursor: pointer !important;
}

.fc-event.status-pending {
    background: #ffc107 !important;
    color: #212529 !important;
}

.fc-event.status-confirmed {
    background: #28a745 !important;
    color: white !important;
}

.fc-event.status-completed {
    background: #007bff !important;
    color: white !important;
}

.fc-event.status-cancelled {
    background: #dc3545 !important;
    color: white !important;
}

.fc-event:hover {
    opacity: 0.8 !important;
}

.fc-daygrid-event {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

.fc-event-title {
    font-weight: 500 !important;
}

.fc-event-time {
    font-weight: normal !important;
    opacity: 0.9 !important;
}

/* Calendar Stats */
.calendar-stats {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: bold;
    color: #d4af37;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
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
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Form Styles */
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

/* Responsive Design */
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
    
    .legend-items {
        flex-direction: column;
        gap: 10px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px;
    }
}

/* Loading State */
.calendar-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: #666;
}

.calendar-loading .dashicons {
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    let calendar;
    let currentFilters = {
        staff_id: '',
        service_id: '',
        status: ''
    };
    
    // Initialize FullCalendar
    function initializeCalendar() {
        const calendarEl = document.getElementById('salon-booking-calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            height: 'auto',
            events: function(fetchInfo, successCallback, failureCallback) {
                loadCalendarEvents(fetchInfo.startStr, fetchInfo.endStr, successCallback, failureCallback);
            },
            eventClick: function(info) {
                showBookingDetails(info.event.id);
            },
            dateClick: function(info) {
                openQuickAddModal(info.dateStr);
            },
            eventDidMount: function(info) {
                // Add status class to event element
                info.el.classList.add('status-' + info.event.extendedProps.status);
            },
            datesSet: function(dateInfo) {
                updateCalendarStats(dateInfo.start, dateInfo.end);
            }
        });
        
        calendar.render();
    }
    
    // Load calendar events
    function loadCalendarEvents(start, end, successCallback, failureCallback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'salon_booking_get_calendar_events',
                start: start,
                end: end,
                filters: currentFilters,
                nonce: '<?php echo wp_create_nonce('salon_booking_admin'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    successCallback(response.data.events);
                } else {
                    failureCallback(response.data.message);
                }
            },
            error: function() {
                failureCallback('Network error loading events');
            }
        });
    }
    
    // Update calendar statistics
    function updateCalendarStats(start, end) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'salon_booking_get_calendar_stats',
                start: start.toISOString().split('T')[0],
                end: end.toISOString().split('T')[0],
                filters: currentFilters,
                nonce: '<?php echo wp_create_nonce('salon_booking_admin'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data.stats;
                    $('#total-bookings-month').text(stats.total_bookings || 0);
                    $('#confirmed-bookings-month').text(stats.confirmed_bookings || 0);
                    $('#revenue-month').text('R' + (stats.revenue || 0).toFixed(2));
                    $('#pending-bookings').text(stats.pending_bookings || 0);
                }
            }
        });
    }
    
    // Show booking details modal
    function showBookingDetails(bookingId) {
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
                    $('#edit-booking-btn').data('booking-id', bookingId);
                    $('#booking-details-modal').show();
                } else {
                    alert('Error loading booking details: ' + response.data.message);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
            }
        });
    }
    
    // Open quick add modal
    function openQuickAddModal(dateStr) {
        $('#quick-add-form')[0].reset();
        $('#quick_booking_date').val(dateStr);
        $('#quick-add-modal').show();
    }
    
    // Apply calendar filters
    $('#apply_calendar_filters').on('click', function() {
        currentFilters = {
            staff_id: $('#calendar_staff_filter').val(),
            service_id: $('#calendar_service_filter').val(),
            status: $('#calendar_status_filter').val()
        };
        
        if (calendar) {
            calendar.refetchEvents();
        }
    });
    
    // Clear calendar filters
    $('#clear_calendar_filters').on('click', function() {
        $('#calendar_staff_filter, #calendar_service_filter, #calendar_status_filter').val('');
        currentFilters = {
            staff_id: '',
            service_id: '',
            status: ''
        };
        
        if (calendar) {
            calendar.refetchEvents();
        }
    });
    
    // Handle modal close
    $('.modal-close').on('click', function() {
        $('.booking-modal').hide();
    });
    
    // Close modal on outside click
    $('.booking-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Handle edit booking button
    $('#edit-booking-btn').on('click', function() {
        const bookingId = $(this).data('booking-id');
        window.location.href = '<?php echo admin_url('admin.php?page=salon-booking-bookings&action=edit&booking_id='); ?>' + bookingId;
    });
    
    // Auto-fill payment amount when service is selected in quick add
    $('#quick_service_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price');
        if (price) {
            $('#quick_payment_amount').val(price);
        }
    });
    
    // Save quick booking
    $('#save-quick-booking').on('click', function() {
        const formData = {
            action: 'salon_booking_save_quick_booking',
            client_name: $('#quick_client_name').val(),
            client_email: $('#quick_client_email').val(),
            client_phone: $('#quick_client_phone').val(),
            service_id: $('#quick_service_id').val(),
            staff_id: $('#quick_staff_id').val(),
            booking_date: $('#quick_booking_date').val(),
            booking_time: $('#quick_booking_time').val(),
            payment_amount: $('#quick_payment_amount').val(),
            notes: $('#quick_notes').val(),
            nonce: '<?php echo wp_create_nonce('salon_booking_admin'); ?>'
        };
        
        // Basic validation
        if (!formData.client_name || !formData.client_email || !formData.client_phone || 
            !formData.service_id || !formData.staff_id || !formData.booking_date || 
            !formData.booking_time || !formData.payment_amount) {
            alert('Please fill in all required fields.');
            return;
        }
        
        const button = $(this);
        button.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#quick-add-modal').hide();
                    calendar.refetchEvents();
                    alert('Booking saved successfully!');
                } else {
                    alert('Error saving booking: ' + response.data.message);
                }
                button.prop('disabled', false).text('Save Booking');
            },
            error: function() {
                alert('Network error. Please try again.');
                button.prop('disabled', false).text('Save Booking');
            }
        });
    });
    
    // Validate booking time availability in quick add
    $('#quick_booking_date, #quick_booking_time, #quick_staff_id').on('change', function() {
        const date = $('#quick_booking_date').val();
        const time = $('#quick_booking_time').val();
        const staffId = $('#quick_staff_id').val();
        
        if (date && time && staffId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'salon_booking_check_availability',
                    date: date,
                    time: time,
                    staff_id: staffId,
                    nonce: '<?php echo wp_create_nonce('salon_booking_admin'); ?>'
                },
                success: function(response) {
                    if (!response.success) {
                        alert('This time slot is not available. Please select a different time.');
                        $('#quick_booking_time').val('');
                    }
                }
            });
        }
    });
    
    // Initialize calendar when FullCalendar is loaded
    if (typeof FullCalendar !== 'undefined') {
        initializeCalendar();
    } else {
        // Wait for FullCalendar to load
        $(window).on('load', function() {
            setTimeout(initializeCalendar, 100);
        });
    }
});
</script>