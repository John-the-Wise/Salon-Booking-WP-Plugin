/**
 * Admin-specific JavaScript for the Salon Booking Plugin
 */

(function($) {
    'use strict';

    // Global variables
    let calendar;
    let currentBookingModal;
    let currentQuickAddModal;

    // Debug logging utility
    function logDebug(message, data = null) {
        if (typeof console !== 'undefined' && console.log) {
            const timestamp = new Date().toISOString();
            console.log(`[${timestamp}] [SALON-ADMIN-DEBUG] ${message}`, data || '');
        }
    }

    // Error logging utility
    function logError(message, error = null) {
        if (typeof console !== 'undefined' && console.error) {
            const timestamp = new Date().toISOString();
            console.error(`[${timestamp}] [SALON-ADMIN-ERROR] ${message}`, error || '');
        }
    }

    // Warning logging utility
    function logWarning(message, data = null) {
        if (typeof console !== 'undefined' && console.warn) {
            const timestamp = new Date().toISOString();
            console.warn(`[${timestamp}] [SALON-ADMIN-WARNING] ${message}`, data || '');
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        try {
            logDebug('Admin JavaScript initializing...');
            initializeAdmin();
        } catch (error) {
            logError('Failed to initialize admin JavaScript', error);
        }
    });

    /**
     * Initialize admin functionality
     */
    function initializeAdmin() {
        try {
            logDebug('Starting admin initialization...');
            
            // Check if required objects are available
            if (typeof salonBookingAdmin === 'undefined') {
                logWarning('salonBookingAdmin object not found');
            }
            
            logDebug('Initializing calendar...');
            initializeCalendar();
            
            logDebug('Initializing booking actions...');
            initializeBookingActions();
            
            logDebug('Initializing modals...');
            initializeModals();
            
            logDebug('Initializing filters...');
            initializeFilters();
            
            logDebug('Initializing form validation...');
            initializeFormValidation();
            
            logDebug('Initializing staff availability...');
            initializeStaffAvailability();
            
            logDebug('Initializing service management...');
            initializeServiceManagement();
            
            logDebug('Initializing settings validation...');
            initializeSettingsValidation();
            
            logDebug('Loading dashboard data...');
            loadDashboardData();
            
            logDebug('Admin initialization completed successfully');
        } catch (error) {
            logError('Failed to initialize admin functionality', error);
        }
    }

    /**
     * Initialize FullCalendar
     */
    function initializeCalendar() {
        try {
            logDebug('Initializing FullCalendar...');
            
            const calendarEl = document.getElementById('booking-calendar');
            if (!calendarEl) {
                logWarning('Calendar element not found');
                return;
            }
            
            // Check if FullCalendar is loaded
            if (typeof FullCalendar === 'undefined') {
                logError('FullCalendar library not loaded');
                return;
            }
            
            logDebug('Creating FullCalendar instance...');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    try {
                        logDebug('Loading calendar events...', { start: fetchInfo.startStr, end: fetchInfo.endStr });
                        loadCalendarEvents(fetchInfo, successCallback, failureCallback);
                    } catch (error) {
                        logError('Error in calendar events function', error);
                        failureCallback('Failed to load events');
                    }
                },
                eventClick: function(info) {
                    try {
                        logDebug('Calendar event clicked', { eventId: info.event.id });
                        showBookingDetails(info.event.id);
                    } catch (error) {
                        logError('Error handling event click', error);
                    }
                },
                dateClick: function(info) {
                    try {
                        logDebug('Calendar date clicked', { date: info.dateStr });
                        showQuickAddBooking(info.dateStr);
                    } catch (error) {
                        logError('Error handling date click', error);
                    }
                },
                eventDidMount: function(info) {
                    try {
                        // Add status class to event element
                        const status = info.event.extendedProps.status;
                        if (status && info.el) {
                            info.el.classList.add(status);
                            logDebug('Event mounted with status', { status });
                        }
                    } catch (error) {
                        logError('Error mounting event', error);
                    }
                },
                height: 'auto',
                dayMaxEvents: 3,
                moreLinkClick: 'popover'
            });
            
            logDebug('Rendering calendar...');
            calendar.render();
            
            logDebug('Loading calendar stats...');
            loadCalendarStats();
            
            logDebug('Calendar initialization completed successfully');
        } catch (error) {
            logError('Failed to initialize calendar', error);
        }
    }

    /**
     * Load calendar events via AJAX
     */
    function loadCalendarEvents(fetchInfo, successCallback, failureCallback) {
        try {
            logDebug('Loading calendar events via AJAX...');
            
            // Check if required objects are available
            if (typeof salonBookingAdmin === 'undefined') {
                logError('salonBookingAdmin object not available');
                failureCallback('Configuration error');
                return;
            }
            
            if (!salonBookingAdmin.ajaxUrl || !salonBookingAdmin.nonce) {
                logError('Missing AJAX URL or nonce');
                failureCallback('Configuration error');
                return;
            }
            
            const filters = getCalendarFilters();
            logDebug('Calendar filters applied', filters);
            
            $.ajax({
                url: salonBookingAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_calendar_events',
                    nonce: salonBookingAdmin.nonce,
                    start: fetchInfo.startStr,
                    end: fetchInfo.endStr,
                    ...filters
                },
                success: function(response) {
                    try {
                        logDebug('Calendar events AJAX response received', response);
                        
                        if (response.success) {
                            if (response.data && response.data.events) {
                                logDebug(`Loaded ${response.data.events.length} calendar events`);
                                successCallback(response.data.events);
                            } else {
                                logWarning('No events data in response');
                                successCallback([]);
                            }
                        } else {
                            const errorMessage = response.data?.message || 'Failed to load events';
                            logError('Calendar events request failed', errorMessage);
                            failureCallback(errorMessage);
                        }
                    } catch (error) {
                        logError('Error processing calendar events response', error);
                        failureCallback('Error processing response');
                    }
                },
                error: function(xhr, status, error) {
                    logError('Calendar events AJAX request failed', { xhr, status, error });
                    failureCallback('Network error occurred');
                }
            });
        } catch (error) {
            logError('Error in loadCalendarEvents function', error);
            failureCallback('Unexpected error occurred');
        }
    }

    /**
     * Get current calendar filters
     */
    function getCalendarFilters() {
        try {
            logDebug('Getting calendar filters...');
            
            const filters = {
                staff_id: $('#filter-staff').val() || '',
                service_id: $('#filter-service').val() || '',
                status: $('#filter-status').val() || ''
            };
            
            logDebug('Calendar filters retrieved', filters);
            return filters;
        } catch (error) {
            logError('Error getting calendar filters', error);
            return {
                staff_id: '',
                service_id: '',
                status: ''
            };
        }
    }

    /**
     * Load calendar statistics
     */
    function loadCalendarStats() {
        try {
            logDebug('Loading calendar statistics...');
            
            // Check if required objects are available
            if (typeof salonBookingAdmin === 'undefined') {
                logError('salonBookingAdmin object not available for stats');
                return;
            }
            
            if (!salonBookingAdmin.ajaxUrl || !salonBookingAdmin.nonce) {
                logError('Missing AJAX URL or nonce for stats');
                return;
            }
            
            const filters = getCalendarFilters();
            logDebug('Loading stats with filters', filters);
            
            $.ajax({
                url: salonBookingAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_calendar_stats',
                    nonce: salonBookingAdmin.nonce,
                    ...filters
                },
                success: function(response) {
                    try {
                        logDebug('Calendar stats AJAX response received', response);
                        
                        if (response.success) {
                            if (response.data) {
                                logDebug('Updating calendar stats display');
                                updateCalendarStats(response.data);
                            } else {
                                logWarning('No stats data in response');
                            }
                        } else {
                            logError('Calendar stats request failed', response.data?.message);
                        }
                    } catch (error) {
                        logError('Error processing calendar stats response', error);
                    }
                },
                error: function(xhr, status, error) {
                    logError('Calendar stats AJAX request failed', { xhr, status, error });
                }
            });
        } catch (error) {
            logError('Error in loadCalendarStats function', error);
        }
    }

    /**
     * Update calendar statistics display
     */
    function updateCalendarStats(stats) {
        try {
            logDebug('Updating calendar statistics display', stats);
            
            if (!stats || typeof stats !== 'object') {
                logWarning('Invalid stats data provided');
                return;
            }
            
            // Update total bookings
            const totalElement = $('#stat-total-bookings');
            if (totalElement.length) {
                totalElement.text(stats.total || 0);
                logDebug('Updated total bookings stat');
            } else {
                logWarning('Total bookings stat element not found');
            }
            
            // Update confirmed bookings
            const confirmedElement = $('#stat-confirmed-bookings');
            if (confirmedElement.length) {
                confirmedElement.text(stats.confirmed || 0);
                logDebug('Updated confirmed bookings stat');
            } else {
                logWarning('Confirmed bookings stat element not found');
            }
            
            // Update total revenue
            const revenueElement = $('#stat-total-revenue');
            if (revenueElement.length) {
                revenueElement.text(stats.revenue || '$0.00');
                logDebug('Updated total revenue stat');
            } else {
                logWarning('Total revenue stat element not found');
            }
            
            // Update pending bookings
            const pendingElement = $('#stat-pending-bookings');
            if (pendingElement.length) {
                pendingElement.text(stats.pending || 0);
                logDebug('Updated pending bookings stat');
            } else {
                logWarning('Pending bookings stat element not found');
            }
            
            logDebug('Calendar statistics display updated successfully');
        } catch (error) {
            logError('Error updating calendar statistics display', error);
        }
    }

    /**
     * Initialize booking action handlers
     */
    function initializeBookingActions() {
        try {
            logDebug('Initializing booking action handlers...');
            
            // Booking status update buttons
            $(document).on('click', '.confirm-booking', function(e) {
                try {
                    e.preventDefault();
                    const bookingId = $(this).data('booking-id');
                    logDebug('Confirm booking clicked', { bookingId });
                    
                    if (!bookingId) {
                        logWarning('No booking ID found for confirm action');
                        return;
                    }
                    
                    updateBookingStatus(bookingId, 'confirmed');
                } catch (error) {
                    logError('Error handling confirm booking click', error);
                }
            });

            $(document).on('click', '.complete-booking', function(e) {
                try {
                    e.preventDefault();
                    const bookingId = $(this).data('booking-id');
                    logDebug('Complete booking clicked', { bookingId });
                    
                    if (!bookingId) {
                        logWarning('No booking ID found for complete action');
                        return;
                    }
                    
                    updateBookingStatus(bookingId, 'completed');
                } catch (error) {
                    logError('Error handling complete booking click', error);
                }
            });

            $(document).on('click', '.cancel-booking', function(e) {
                try {
                    e.preventDefault();
                    const bookingId = $(this).data('booking-id');
                    logDebug('Cancel booking clicked', { bookingId });
                    
                    if (!bookingId) {
                        logWarning('No booking ID found for cancel action');
                        return;
                    }
                    
                    if (confirm('Are you sure you want to cancel this booking?')) {
                        updateBookingStatus(bookingId, 'cancelled');
                    }
                } catch (error) {
                    logError('Error handling cancel booking click', error);
                }
            });

            // View booking details
            $(document).on('click', '.view-booking', function(e) {
                try {
                    e.preventDefault();
                    const bookingId = $(this).data('booking-id');
                    logDebug('View booking clicked', { bookingId });
                    
                    if (!bookingId) {
                        logWarning('No booking ID found for view action');
                        return;
                    }
                    
                    showBookingDetails(bookingId);
                } catch (error) {
                    logError('Error handling view booking click', error);
                }
            });
            
            logDebug('Booking action handlers initialized successfully');
        } catch (error) {
            logError('Failed to initialize booking action handlers', error);
        }
    }

    /**
     * Update booking status via AJAX
     */
    function updateBookingStatus(bookingId, status) {
        try {
            logDebug('Updating booking status...', { bookingId, status });
            
            // Validate inputs
            if (!bookingId || !status) {
                logError('Invalid booking ID or status provided', { bookingId, status });
                showNotice('Invalid booking data', 'error');
                return;
            }
            
            // Check if required objects are available
            if (typeof salonBookingAdmin === 'undefined') {
                logError('salonBookingAdmin object not available for status update');
                showNotice('Configuration error', 'error');
                return;
            }
            
            if (!salonBookingAdmin.ajaxUrl || !salonBookingAdmin.nonce) {
                logError('Missing AJAX URL or nonce for status update');
                showNotice('Configuration error', 'error');
                return;
            }
            
            const button = $(`.booking-actions button[data-booking-id="${bookingId}"]`);
            if (button.length) {
                button.prop('disabled', true).addClass('loading');
                logDebug('Button disabled and loading state applied');
            } else {
                logWarning('Button not found for booking', { bookingId });
            }

            $.ajax({
                url: salonBookingAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'salon_booking_update_status',
                    nonce: salonBookingAdmin.nonce,
                    booking_id: bookingId,
                    status: status
                },
                success: function(response) {
                    try {
                        logDebug('Booking status update response received', response);
                        
                        if (response.success) {
                            logDebug('Booking status updated successfully');
                            showNotice('Booking status updated successfully', 'success');
                            // Refresh the page or update the UI
                            location.reload();
                        } else {
                            const errorMessage = response.data?.message || 'Failed to update booking status';
                            logError('Booking status update failed', errorMessage);
                            showNotice(errorMessage, 'error');
                        }
                    } catch (error) {
                        logError('Error processing booking status update response', error);
                        showNotice('Error processing response', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    logError('Booking status update AJAX request failed', { xhr, status, error });
                    showNotice('Network error occurred', 'error');
                },
                complete: function() {
                    try {
                        if (button.length) {
                            button.prop('disabled', false).removeClass('loading');
                            logDebug('Button re-enabled and loading state removed');
                        }
                    } catch (error) {
                        logError('Error in AJAX complete handler', error);
                    }
                }
            });
        } catch (error) {
            logError('Error in updateBookingStatus function', error);
            showNotice('Unexpected error occurred', 'error');
        }
    }

    /**
     * Show booking details modal
     */
    function showBookingDetails(bookingId) {
        try {
            logDebug('Showing booking details...', { bookingId });
            
            // Validate booking ID
            if (!bookingId) {
                logError('No booking ID provided for details');
                showNotice('Invalid booking ID', 'error');
                return;
            }
            
            // Check if required objects are available
            if (typeof salonBookingAdmin === 'undefined') {
                logError('salonBookingAdmin object not available for booking details');
                showNotice('Configuration error', 'error');
                return;
            }
            
            if (!salonBookingAdmin.ajaxUrl || !salonBookingAdmin.nonce) {
                logError('Missing AJAX URL or nonce for booking details');
                showNotice('Configuration error', 'error');
                return;
            }
            
            $.ajax({
                url: salonBookingAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_details',
                    nonce: salonBookingAdmin.nonce,
                    booking_id: bookingId
                },
                success: function(response) {
                    try {
                        logDebug('Booking details response received', response);
                        
                        if (response.success) {
                            if (response.data) {
                                logDebug('Displaying booking modal');
                                displayBookingModal(response.data);
                            } else {
                                logWarning('No booking data in response');
                                showNotice('No booking data found', 'error');
                            }
                        } else {
                            const errorMessage = response.data?.message || 'Failed to load booking details';
                            logError('Booking details request failed', errorMessage);
                            showNotice(errorMessage, 'error');
                        }
                    } catch (error) {
                        logError('Error processing booking details response', error);
                        showNotice('Error processing response', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    logError('Booking details AJAX request failed', { xhr, status, error });
                    showNotice('Network error occurred', 'error');
                }
            });
        } catch (error) {
            logError('Error in showBookingDetails function', error);
            showNotice('Unexpected error occurred', 'error');
        }
    }

    /**
     * Display booking details in modal
     */
    function displayBookingModal(booking) {
        try {
            logDebug('Displaying booking modal...', { booking });
            
            // Validate booking data
            if (!booking) {
                logError('No booking data provided for modal');
                showNotice('No booking data available', 'error');
                return;
            }
            
            if (!booking.id) {
                logError('Booking data missing ID');
                showNotice('Invalid booking data', 'error');
                return;
            }
            
            // Close any existing modal
            if (currentBookingModal) {
                try {
                    currentBookingModal.remove();
                } catch (error) {
                    logWarning('Error closing existing modal', error);
                }
            }
            
            // Safely get booking values with fallbacks
            const safeValue = (value) => value || 'N/A';
            const safeDuration = booking.duration ? `${booking.duration} minutes` : 'N/A';
            const safeStatus = booking.status || 'unknown';
            
            const modalHtml = `
                <div id="booking-details-modal" class="booking-modal">
                    <div class="booking-modal-content">
                        <div class="booking-modal-header">
                            <h3>Booking Details</h3>
                            <button class="booking-modal-close">&times;</button>
                        </div>
                        <div class="booking-details">
                            <div class="detail-section">
                                <h4>Client Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value">${safeValue(booking.client_name)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value">${safeValue(booking.client_email)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value">${safeValue(booking.client_phone)}</span>
                                </div>
                            </div>
                            <div class="detail-section">
                                <h4>Booking Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value">${safeValue(booking.service_name)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Staff:</span>
                                    <span class="detail-value">${safeValue(booking.staff_name)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date:</span>
                                    <span class="detail-value">${safeValue(booking.booking_date)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Time:</span>
                                    <span class="detail-value">${safeValue(booking.booking_time)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Duration:</span>
                                    <span class="detail-value">${safeDuration}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value booking-status ${safeStatus}">${safeStatus}</span>
                                </div>
                            </div>
                            <div class="detail-section">
                                <h4>Payment Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Amount:</span>
                                    <span class="detail-value">${safeValue(booking.payment_amount)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Payment Status:</span>
                                    <span class="detail-value">${safeValue(booking.payment_status)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Payment ID:</span>
                                    <span class="detail-value">${booking.payment_intent_id || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="booking-actions">
                            <button class="button confirm-booking" data-booking-id="${booking.id}">Confirm</button>
                            <button class="button complete-booking" data-booking-id="${booking.id}">Complete</button>
                            <button class="button cancel-booking" data-booking-id="${booking.id}">Cancel</button>
                            <button class="button button-secondary" onclick="closeBookingModal()">Close</button>
                        </div>
                    </div>
                </div>
            `;

            try {
                $('body').append(modalHtml);
                const modal = $('#booking-details-modal');
                
                if (modal.length === 0) {
                    logError('Failed to create booking modal element');
                    showNotice('Error displaying booking details', 'error');
                    return;
                }
                
                modal.show();
                currentBookingModal = modal;
                
                // Bind close event
                modal.find('.booking-modal-close').on('click', function() {
                    try {
                        closeBookingModal();
                    } catch (error) {
                        logError('Error closing modal via close button', error);
                    }
                });
                
                logDebug('Booking modal displayed successfully');
            } catch (error) {
                logError('Error appending modal to DOM', error);
                showNotice('Error displaying booking details', 'error');
            }
        } catch (error) {
            logError('Error in displayBookingModal function', error);
            showNotice('Unexpected error occurred', 'error');
        }
    }

    /**
     * Show quick add booking modal
     */
    function showQuickAddBooking(dateStr) {
        try {
            logDebug('Showing quick add booking modal...', { dateStr });
            
            // Validate date string
            if (!dateStr) {
                logWarning('No date provided for quick add booking');
                dateStr = new Date().toISOString().split('T')[0]; // Default to today
            }
            
            // Close any existing modal
            if (currentQuickAddModal) {
                try {
                    currentQuickAddModal.remove();
                } catch (error) {
                    logWarning('Error closing existing quick add modal', error);
                }
            }
            
            const modalHtml = `
                <div id="quick-add-modal" class="booking-modal">
                    <div class="booking-modal-content">
                        <div class="booking-modal-header">
                            <h3>Quick Add Booking</h3>
                            <button class="booking-modal-close">&times;</button>
                        </div>
                        <form id="quick-add-form">
                            <div class="form-grid">
                                <div class="form-section">
                                    <h4>Client Details</h4>
                                    <div class="form-row">
                                        <label for="quick-client-name">Client Name *</label>
                                        <input type="text" id="quick-client-name" name="client_name" required>
                                    </div>
                                    <div class="form-row">
                                        <label for="quick-client-email">Email *</label>
                                        <input type="email" id="quick-client-email" name="client_email" required>
                                    </div>
                                    <div class="form-row">
                                        <label for="quick-client-phone">Phone *</label>
                                        <input type="tel" id="quick-client-phone" name="client_phone" required>
                                    </div>
                                </div>
                                <div class="form-section">
                                    <h4>Booking Details</h4>
                                    <div class="form-row">
                                        <label for="quick-service">Service *</label>
                                        <select id="quick-service" name="service_id" required>
                                            <option value="">Select Service</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label for="quick-staff">Staff *</label>
                                        <select id="quick-staff" name="staff_id" required>
                                            <option value="">Select Staff</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label for="quick-date">Date *</label>
                                        <input type="date" id="quick-date" name="booking_date" value="${dateStr}" required>
                                    </div>
                                    <div class="form-row">
                                        <label for="quick-time">Time *</label>
                                        <select id="quick-time" name="booking_time" required>
                                            <option value="">Select Time</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label for="quick-payment">Payment Amount</label>
                                        <input type="number" id="quick-payment" name="payment_amount" step="0.01" min="0">
                                    </div>
                                    <div class="form-row">
                                        <label for="quick-notes">Notes</label>
                                        <textarea id="quick-notes" name="notes" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="booking-actions">
                                <button type="submit" class="button button-primary">Add Booking</button>
                                <button type="button" class="button button-secondary" onclick="closeQuickAddModal()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            try {
                $('body').append(modalHtml);
                const modal = $('#quick-add-modal');
                
                if (modal.length === 0) {
                    logError('Failed to create quick add modal element');
                    showNotice('Error displaying quick add form', 'error');
                    return;
                }
                
                modal.show();
                currentQuickAddModal = modal;
                
                // Bind close event
                modal.find('.booking-modal-close').on('click', function() {
                    try {
                        closeQuickAddModal();
                    } catch (error) {
                        logError('Error closing quick add modal via close button', error);
                    }
                });
                
                logDebug('Quick add modal displayed successfully');
                
                // Load services and staff
                loadQuickAddData();
            } catch (error) {
                logError('Error appending quick add modal to DOM', error);
                showNotice('Error displaying quick add form', 'error');
            }
        } catch (error) {
            logError('Error in showQuickAddBooking function', error);
            showNotice('Unexpected error occurred', 'error');
        }
    }

    /**
     * Load data for quick add modal
     */
    function loadQuickAddData() {
        try {
            logDebug('Loading quick add modal data...');
            
            // Check if required objects are available
            if (typeof salonBookingAdmin === 'undefined') {
                logError('salonBookingAdmin object not available for quick add data');
                showNotice('Configuration error', 'error');
                return;
            }
            
            if (!salonBookingAdmin.ajaxUrl || !salonBookingAdmin.nonce) {
                logError('Missing AJAX URL or nonce for quick add data');
                showNotice('Configuration error', 'error');
                return;
            }
            
            // Load services
            $.ajax({
                url: salonBookingAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_services',
                    nonce: salonBookingAdmin.nonce
                },
                success: function(response) {
                    try {
                        logDebug('Services response received for quick add', response);
                        
                        if (response.success) {
                            if (response.data && Array.isArray(response.data)) {
                                const serviceSelect = $('#quick-service');
                                if (serviceSelect.length === 0) {
                                    logWarning('Service select element not found in quick add modal');
                                    return;
                                }
                                
                                response.data.forEach(service => {
                                    if (service.id && service.name) {
                                        const price = service.price || 'N/A';
                                        serviceSelect.append(`<option value="${service.id}">${service.name} - ${price}</option>`);
                                    } else {
                                        logWarning('Invalid service data', service);
                                    }
                                });
                                
                                logDebug(`Loaded ${response.data.length} services for quick add`);
                            } else {
                                logWarning('No services data in response for quick add');
                            }
                        } else {
                            logError('Services request failed for quick add', response.data?.message);
                        }
                    } catch (error) {
                        logError('Error processing services response for quick add', error);
                    }
                },
                error: function(xhr, status, error) {
                    logError('Services AJAX request failed for quick add', { xhr, status, error });
                }
            });

            // Load staff
            $.ajax({
                url: salonBookingAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_staff',
                    nonce: salonBookingAdmin.nonce
                },
                success: function(response) {
                    try {
                        logDebug('Staff response received for quick add', response);
                        
                        if (response.success) {
                            if (response.data && Array.isArray(response.data)) {
                                const staffSelect = $('#quick-staff');
                                if (staffSelect.length === 0) {
                                    logWarning('Staff select element not found in quick add modal');
                                    return;
                                }
                                
                                response.data.forEach(staff => {
                                    if (staff.id && staff.name) {
                                        staffSelect.append(`<option value="${staff.id}">${staff.name}</option>`);
                                    } else {
                                        logWarning('Invalid staff data', staff);
                                    }
                                });
                                
                                logDebug(`Loaded ${response.data.length} staff for quick add`);
                            } else {
                                logWarning('No staff data in response for quick add');
                            }
                        } else {
                            logError('Staff request failed for quick add', response.data?.message);
                        }
                    } catch (error) {
                        logError('Error processing staff response for quick add', error);
                    }
                },
                error: function(xhr, status, error) {
                    logError('Staff AJAX request failed for quick add', { xhr, status, error });
                }
            });
        } catch (error) {
            logError('Error in loadQuickAddData function', error);
            showNotice('Error loading form data', 'error');
        }
    }

    /**
     * Initialize modal functionality
     */
    function initializeModals() {
        // Close modal when clicking outside or on close button
        $(document).on('click', '.booking-modal', function(e) {
            if (e.target === this) {
                closeAllModals();
            }
        });

        $(document).on('click', '.booking-modal-close', function() {
            closeAllModals();
        });

        // Handle quick add form submission
        $(document).on('submit', '#quick-add-form', function(e) {
            e.preventDefault();
            submitQuickAddBooking();
        });

        // Handle service/staff change for time slots
        $(document).on('change', '#quick-service, #quick-staff, #quick-date', function() {
            loadAvailableTimeSlots();
        });
    }

    /**
     * Load available time slots for quick add
     */
    function loadAvailableTimeSlots() {
        const serviceId = $('#quick-service').val();
        const staffId = $('#quick-staff').val();
        const date = $('#quick-date').val();

        if (!serviceId || !staffId || !date) {
            $('#quick-time').html('<option value="">Select Time</option>');
            return;
        }

        $.ajax({
            url: salonBookingAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'salon_booking_get_available_times',
                nonce: salonBookingAdmin.nonce,
                service_id: serviceId,
                staff_id: staffId,
                date: date
            },
            success: function(response) {
                const timeSelect = $('#quick-time');
                timeSelect.html('<option value="">Select Time</option>');
                
                if (response.success && response.data.length > 0) {
                    response.data.forEach(time => {
                        timeSelect.append(`<option value="${time}">${time}</option>`);
                    });
                } else {
                    timeSelect.append('<option value="">No available times</option>');
                }
            }
        });
    }

    /**
     * Submit quick add booking form
     */
    function submitQuickAddBooking() {
        const formData = $('#quick-add-form').serialize();
        
        $.ajax({
            url: salonBookingAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'salon_booking_quick_add',
                nonce: salonBookingAdmin.nonce,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Booking added successfully', 'success');
                    closeAllModals();
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                    loadCalendarStats();
                } else {
                    showNotice(response.data.message || 'Failed to add booking', 'error');
                }
            },
            error: function() {
                showNotice('Network error occurred', 'error');
            }
        });
    }

    /**
     * Close all modals
     */
    function closeAllModals() {
        try {
            logDebug('Closing all modals...');
            
            const modals = $('.booking-modal');
            if (modals.length > 0) {
                modals.remove();
                logDebug(`Removed ${modals.length} modal(s)`);
            }
            
            currentBookingModal = null;
            currentQuickAddModal = null;
            
            logDebug('All modals closed successfully');
        } catch (error) {
            logError('Error closing all modals', error);
        }
    }

    // Make close functions global for onclick handlers
    window.closeBookingModal = function() {
        try {
            logDebug('Closing booking modal...');
            
            if (currentBookingModal) {
                currentBookingModal.remove();
                currentBookingModal = null;
                logDebug('Booking modal closed successfully');
            } else {
                logWarning('No current booking modal to close');
            }
        } catch (error) {
            logError('Error closing booking modal', error);
        }
    };

    window.closeQuickAddModal = function() {
        try {
            logDebug('Closing quick add modal...');
            
            if (currentQuickAddModal) {
                currentQuickAddModal.remove();
                currentQuickAddModal = null;
                logDebug('Quick add modal closed successfully');
            } else {
                logWarning('No current quick add modal to close');
            }
        } catch (error) {
            logError('Error closing quick add modal', error);
        }
    };

    /**
     * Initialize filter functionality
     */
    function initializeFilters() {
        // Calendar filters
        $(document).on('change', '#filter-staff, #filter-service, #filter-status', function() {
            if (calendar) {
                calendar.refetchEvents();
                loadCalendarStats();
            }
        });

        // Clear filters
        $(document).on('click', '#clear-filters', function() {
            $('#filter-staff, #filter-service, #filter-status').val('');
            if (calendar) {
                calendar.refetchEvents();
                loadCalendarStats();
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        // Real-time validation for booking forms
        $(document).on('input', 'input[type="email"]', function() {
            validateEmail($(this));
        });

        $(document).on('input', 'input[type="tel"]', function() {
            validatePhone($(this));
        });

        $(document).on('change', 'input[type="date"]', function() {
            validateDate($(this));
        });

        $(document).on('change', 'input[type="time"]', function() {
            validateTime($(this));
        });
    }

    /**
     * Validate email field
     */
    function validateEmail(field) {
        const email = field.val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            field.addClass('error');
            showFieldError(field, 'Please enter a valid email address');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Validate phone field
     */
    function validatePhone(field) {
        const phone = field.val();
        const phoneRegex = /^[\+]?[1-9][\d\s\-\(\)]{7,}$/;
        
        if (phone && !phoneRegex.test(phone)) {
            field.addClass('error');
            showFieldError(field, 'Please enter a valid phone number');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Validate date field
     */
    function validateDate(field) {
        const date = new Date(field.val());
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (date < today) {
            field.addClass('error');
            showFieldError(field, 'Date cannot be in the past');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Validate time field
     */
    function validateTime(field) {
        const time = field.val();
        const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
        
        if (time && !timeRegex.test(time)) {
            field.addClass('error');
            showFieldError(field, 'Please enter a valid time');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Show field error message
     */
    function showFieldError(field, message) {
        hideFieldError(field);
        field.after(`<span class="field-error" style="color: #dc3545; font-size: 12px; display: block; margin-top: 2px;">${message}</span>`);
    }

    /**
     * Hide field error message
     */
    function hideFieldError(field) {
        field.next('.field-error').remove();
    }

    /**
     * Initialize staff availability management
     */
    function initializeStaffAvailability() {
        // Day toggle switches
        $(document).on('change', '.day-toggle', function() {
            const day = $(this).data('day');
            const isEnabled = $(this).is(':checked');
            const daySection = $(`.availability-day[data-day="${day}"]`);
            
            if (isEnabled) {
                daySection.find('input[type="time"]').prop('disabled', false);
                daySection.removeClass('disabled');
            } else {
                daySection.find('input[type="time"]').prop('disabled', true).val('');
                daySection.addClass('disabled');
            }
        });

        // Save availability
        $(document).on('click', '#save-availability', function() {
            saveStaffAvailability();
        });

        // Validate time ranges
        $(document).on('change', '.time-input', function() {
            validateTimeRange($(this));
        });
    }

    /**
     * Save staff availability
     */
    function saveStaffAvailability() {
        const staffId = $('#staff-id').val();
        const availability = {};
        
        $('.availability-day').each(function() {
            const day = $(this).data('day');
            const isEnabled = $(this).find('.day-toggle').is(':checked');
            
            if (isEnabled) {
                availability[day] = {
                    enabled: true,
                    start_time: $(this).find('.start-time').val(),
                    end_time: $(this).find('.end-time').val(),
                    break_start: $(this).find('.break-start').val(),
                    break_end: $(this).find('.break-end').val()
                };
            } else {
                availability[day] = { enabled: false };
            }
        });

        $.ajax({
            url: salonBookingAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'salon_booking_save_availability',
                nonce: salonBookingAdmin.nonce,
                staff_id: staffId,
                availability: JSON.stringify(availability)
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Availability saved successfully', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to save availability', 'error');
                }
            },
            error: function() {
                showNotice('Network error occurred', 'error');
            }
        });
    }

    /**
     * Validate time range
     */
    function validateTimeRange(field) {
        const daySection = field.closest('.availability-day');
        const startTime = daySection.find('.start-time').val();
        const endTime = daySection.find('.end-time').val();
        const breakStart = daySection.find('.break-start').val();
        const breakEnd = daySection.find('.break-end').val();
        
        if (startTime && endTime && startTime >= endTime) {
            showFieldError(field, 'End time must be after start time');
            return false;
        }
        
        if (breakStart && breakEnd && breakStart >= breakEnd) {
            showFieldError(field, 'Break end time must be after break start time');
            return false;
        }
        
        if (breakStart && (breakStart < startTime || breakStart > endTime)) {
            showFieldError(field, 'Break time must be within working hours');
            return false;
        }
        
        if (breakEnd && (breakEnd < startTime || breakEnd > endTime)) {
            showFieldError(field, 'Break time must be within working hours');
            return false;
        }
        
        hideFieldError(field);
        return true;
    }

    /**
     * Initialize service management
     */
    function initializeServiceManagement() {
        // Dynamic pricing preview
        $(document).on('input', '#service-price', function() {
            updatePricingPreview();
        });

        // Duration validation
        $(document).on('input', '#service-duration', function() {
            validateDuration($(this));
        });

        // Price validation
        $(document).on('input', '#service-price', function() {
            validatePrice($(this));
        });
    }

    /**
     * Update pricing preview
     */
    function updatePricingPreview() {
        const price = parseFloat($('#service-price').val()) || 0;
        const currency = salonBookingAdmin.currency || '$';
        
        $('#pricing-preview').text(`${currency}${price.toFixed(2)}`);
    }

    /**
     * Validate duration field
     */
    function validateDuration(field) {
        const duration = parseInt(field.val());
        
        if (duration && (duration < 15 || duration > 480)) {
            field.addClass('error');
            showFieldError(field, 'Duration must be between 15 and 480 minutes');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Validate price field
     */
    function validatePrice(field) {
        const price = parseFloat(field.val());
        
        if (price && (price < 0 || price > 10000)) {
            field.addClass('error');
            showFieldError(field, 'Price must be between 0 and 10,000');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Initialize settings validation
     */
    function initializeSettingsValidation() {
        // Stripe key validation
        $(document).on('input', '#stripe_publishable_key, #stripe_secret_key', function() {
            validateStripeKey($(this));
        });

        // Email validation
        $(document).on('input', '#admin_email, #from_email', function() {
            validateEmail($(this));
        });

        // Percentage validation
        $(document).on('input', '#deposit_percentage', function() {
            validatePercentage($(this));
        });

        // Booking window validation
        $(document).on('input', '#booking_window_days', function() {
            validateBookingWindow($(this));
        });

        // Reminder hours validation
        $(document).on('input', '#reminder_hours', function() {
            validateReminderHours($(this));
        });
    }

    /**
     * Validate Stripe key
     */
    function validateStripeKey(field) {
        const key = field.val();
        const isPublishable = field.attr('id') === 'stripe_publishable_key';
        const prefix = isPublishable ? 'pk_' : 'sk_';
        
        if (key && !key.startsWith(prefix)) {
            field.addClass('error');
            showFieldError(field, `${isPublishable ? 'Publishable' : 'Secret'} key must start with ${prefix}`);
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Validate percentage field
     */
    function validatePercentage(field) {
        const percentage = parseFloat(field.val());
        
        if (percentage && (percentage < 0 || percentage > 100)) {
            field.addClass('error');
            showFieldError(field, 'Percentage must be between 0 and 100');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Validate booking window
     */
    function validateBookingWindow(field) {
        const days = parseInt(field.val());
        
        if (days && (days < 1 || days > 365)) {
            field.addClass('error');
            showFieldError(field, 'Booking window must be between 1 and 365 days');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Validate reminder hours
     */
    function validateReminderHours(field) {
        const hours = parseInt(field.val());
        
        if (hours && (hours < 1 || hours > 168)) {
            field.addClass('error');
            showFieldError(field, 'Reminder hours must be between 1 and 168 (1 week)');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    }

    /**
     * Load dashboard data
     */
    function loadDashboardData() {
        if ($('#dashboard-stats').length) {
            loadDashboardStats();
            loadRecentBookings();
            loadUpcomingBookings();
        }
    }

    /**
     * Load dashboard statistics
     */
    function loadDashboardStats() {
        $.ajax({
            url: salonBookingAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'salon_booking_get_dashboard_stats',
                nonce: salonBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data);
                }
            }
        });
    }

    /**
     * Update dashboard statistics
     */
    function updateDashboardStats(stats) {
        $('#stat-today').text(stats.today || 0);
        $('#stat-week').text(stats.week || 0);
        $('#stat-month').text(stats.month || 0);
        $('#stat-total').text(stats.total || 0);
        $('#stat-pending').text(stats.pending || 0);
        $('#stat-confirmed').text(stats.confirmed || 0);
        $('#stat-completed').text(stats.completed || 0);
        $('#stat-cancelled').text(stats.cancelled || 0);
        $('#stat-revenue').text(stats.revenue || '$0.00');
    }

    /**
     * Load recent bookings
     */
    function loadRecentBookings() {
        $.ajax({
            url: salonBookingAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'salon_booking_get_recent_bookings',
                nonce: salonBookingAdmin.nonce,
                limit: 5
            },
            success: function(response) {
                if (response.success) {
                    updateBookingList('#recent-bookings', response.data);
                }
            }
        });
    }

    /**
     * Load upcoming bookings
     */
    function loadUpcomingBookings() {
        $.ajax({
            url: salonBookingAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'salon_booking_get_upcoming_bookings',
                nonce: salonBookingAdmin.nonce,
                limit: 5
            },
            success: function(response) {
                if (response.success) {
                    updateBookingList('#upcoming-bookings', response.data);
                }
            }
        });
    }

    /**
     * Update booking list display
     */
    function updateBookingList(selector, bookings) {
        const container = $(selector);
        container.empty();
        
        if (bookings.length === 0) {
            container.append('<p>No bookings found.</p>');
            return;
        }
        
        bookings.forEach(booking => {
            const bookingHtml = `
                <div class="booking-item">
                    <div class="booking-info">
                        <div class="booking-client">${booking.client_name}</div>
                        <div class="booking-service">${booking.service_name}</div>
                        <div class="booking-time">${booking.booking_date} at ${booking.booking_time}</div>
                    </div>
                    <span class="booking-status ${booking.status}">${booking.status}</span>
                </div>
            `;
            container.append(bookingHtml);
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type = 'info') {
        const noticeHtml = `
            <div class="salon-booking-notice ${type}" style="margin: 15px 0; padding: 12px 15px; border-left: 4px solid; background: white;">
                ${message}
                <button type="button" class="notice-dismiss" style="float: right; background: none; border: none; cursor: pointer;">&times;</button>
            </div>
        `;
        
        $('.salon-booking-admin').prepend(noticeHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.salon-booking-notice').fadeOut();
        }, 5000);
    }

    // Notice dismiss handler
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.salon-booking-notice').fadeOut();
    });

    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Escape key to close modals
        if (e.key === 'Escape') {
            closeAllModals();
        }
        
        // Ctrl+S to save forms (prevent default browser save)
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const activeForm = $('form:visible').first();
            if (activeForm.length) {
                activeForm.submit();
            }
        }
    });

})(jQuery);