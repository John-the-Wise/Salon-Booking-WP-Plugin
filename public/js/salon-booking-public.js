/**
 * Public-facing JavaScript for the Salon Booking Plugin
 */

(function($) {
    'use strict';

    // Debug logging flag - set to false in production
    const DEBUG_MODE = true;
    
    // Logging utility
    function logDebug(message, data = null) {
        if (DEBUG_MODE) {
            console.log('[Salon Booking Debug]', message, data || '');
        }
    }
    
    function logError(message, error = null) {
        console.error('[Salon Booking Error]', message, error || '');
    }
    
    function logWarning(message, data = null) {
        console.warn('[Salon Booking Warning]', message, data || '');
    }

    // Global variables
    let currentStep = 1;
    let selectedService = null;
    let selectedStaff = null;
    let selectedDate = null;
    let selectedTime = null;
    let stripe = null;
    let elements = null;
    let cardElement = null;
    let calendar = null;

    // Initialize when document is ready and salon_booking_ajax is available
    $(document).ready(function() {
        logDebug('Document ready, initializing salon booking plugin');
        
        try {
            // Wait for salon_booking_ajax to be available
            let retryCount = 0;
            const maxRetries = 50; // 5 seconds max wait
            
            function initializeWhenReady() {
                try {
                    if (typeof salon_booking_ajax !== 'undefined') {
                        logDebug('salon_booking_ajax is available, initializing components', salon_booking_ajax);
                        
                        initializeBookingForm();
                        initializeStripe();
                        bindEvents();
                        
                        // Handle staff data from hidden fields
                        if (typeof hasMultipleStaff !== 'undefined' && !hasMultipleStaff) {
                            logDebug('Single staff mode detected');
                            // Auto-select single staff member
                            const singleStaffId = $('.staff-member').first().data('staff-id');
                            if (singleStaffId) {
                                selectedStaffId = singleStaffId;
                                $('.staff-member').first().addClass('selected');
                                logDebug('Auto-selected single staff member', singleStaffId);
                            }
                        }
                        
                        logDebug('Salon booking plugin initialization complete');
                    } else {
                        retryCount++;
                        if (retryCount >= maxRetries) {
                            logError('salon_booking_ajax not available after maximum retries', retryCount);
                            return;
                        }
                        logDebug('salon_booking_ajax not yet available, retrying...', retryCount);
                        // Retry after a short delay
                        setTimeout(initializeWhenReady, 100);
                    }
                } catch (error) {
                    logError('Error in initializeWhenReady', error);
                }
            }
            
            initializeWhenReady();
        } catch (error) {
            logError('Error in document ready handler', error);
        }
    });

    /**
     * Initialize the booking form
     */
    function initializeBookingForm() {
        try {
            logDebug('Initializing booking form');
            
            // Show first step
            showStep(1);
            
            // Load services dynamically
            loadServices();
            
            // Handle staff data from hidden fields
            try {
                const staffData = $('#staff_data').val();
                const hasMultipleStaff = $('#has_multiple_staff').val() === '1';
                
                logDebug('Staff data retrieved', { staffData, hasMultipleStaff });
                
                if (staffData) {
                    const staff = JSON.parse(staffData);
                    logDebug('Parsed staff data', staff);
                    
                    if (!hasMultipleStaff && staff.length > 0) {
                        // Auto-select single staff member
                        selectedStaff = {
                            id: staff[0].id,
                            name: staff[0].name
                        };
                        window.singleStaffMode = true;
                        window.singleStaffId = staff[0].id;
                        window.singleStaffName = staff[0].name;
                        logDebug('Auto-selected single staff member', selectedStaff);
                    }
                }
            } catch (error) {
                logError('Error handling staff data', error);
            }
            
            // Check for URL parameters to pre-select service
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const serviceId = urlParams.get('service_id');
                const staffId = urlParams.get('staff_id');
                
                logDebug('URL parameters checked', { serviceId, staffId });
                
                if (serviceId) {
                    // Store for later use when services are loaded
                    window.preSelectServiceId = serviceId;
                    logDebug('Pre-selecting service from URL', serviceId);
                }
                
                if (staffId && hasMultipleStaff) {
                    // Store for later use when staff are loaded
                    window.preSelectStaffId = staffId;
                    logDebug('Pre-selecting staff from URL', staffId);
                }
            } catch (error) {
                logError('Error processing URL parameters', error);
            }
            
            logDebug('Booking form initialization complete');
        } catch (error) {
            logError('Error in initializeBookingForm', error);
        }
    }

    /**
     * Initialize Stripe
     */
    function initializeStripe() {
        try {
            logDebug('Initializing Stripe payment system');
            
            // Check if salon_booking_ajax object exists and has Stripe key
            if (typeof salon_booking_ajax === 'undefined') {
                logWarning('salon_booking_ajax object not loaded, skipping Stripe initialization');
                return;
            }
            
            if (typeof salon_booking_ajax.stripe_publishable_key !== 'undefined' && salon_booking_ajax.stripe_publishable_key) {
                logDebug('Stripe publishable key found, initializing Stripe', salon_booking_ajax.stripe_publishable_key.substring(0, 10) + '...');
                
                stripe = Stripe(salon_booking_ajax.stripe_publishable_key);
                elements = stripe.elements();
                
                logDebug('Stripe initialized successfully');
                
                // Create card element if Stripe is available
                if (stripe && elements) {
                    try {
                        logDebug('Creating Stripe card element');
                        
                        cardElement = elements.create('card', {
                            style: {
                                base: {
                                    fontSize: '16px',
                                    color: '#424770',
                                    '::placeholder': {
                                        color: '#aab7c4',
                                    },
                                },
                                invalid: {
                                    color: '#9e2146',
                                },
                            },
                        });
                        
                        // Mount card element
                        const cardElementContainer = document.getElementById('card-element');
                        if (cardElementContainer) {
                            cardElement.mount('#card-element');
                            logDebug('Card element mounted successfully');
                        } else {
                            logWarning('Card element container not found');
                        }
                        
                        // Handle real-time validation errors from the card Element
                        cardElement.on('change', function(event) {
                            try {
                                const displayError = document.getElementById('card-errors');
                                if (displayError) {
                                    if (event.error) {
                                        displayError.textContent = event.error.message;
                                        logDebug('Card validation error', event.error.message);
                                    } else {
                                        displayError.textContent = '';
                                    }
                                }
                            } catch (error) {
                                logError('Error handling card change event', error);
                            }
                        });
                    } catch (error) {
                        logError('Error creating Stripe card element', error);
                    }
                } else {
                    logDebug('Stripe not available, skipping card element creation');
                }
            } else {
                logWarning('Stripe publishable key not found or empty, payment functionality will be disabled');
            }
        } catch (error) {
            logError('Error initializing Stripe', error);
        }
    }

    /**
     * Load services dynamically
     */
    function loadServices() {
        try {
            logDebug('Loading services');
            
            const $servicesGrid = $('.services-grid');
            
            if ($servicesGrid.length === 0) {
                logWarning('Services grid container not found');
                return;
            }
            
            // Check if salon_booking_ajax is available
            if (typeof salon_booking_ajax === 'undefined') {
                logError('salon_booking_ajax is not defined. Please check if the script is properly localized.');
                $servicesGrid.html('<div class="error-services"><p>Configuration error. Please refresh the page.</p></div>');
                return;
            }
            
            // Show loading state
            $servicesGrid.html('<div class="services-loading"><div class="loading-spinner"></div><p>Loading services...</p></div>');
            logDebug('Loading state displayed');
            
            // Use WordPress AJAX to get services
            $.ajax({
                url: salon_booking_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'salon_booking_get_services',
                    nonce: salon_booking_ajax.nonce
                },
                timeout: 10000,
                beforeSend: function() {
                    logDebug('Sending AJAX request for services');
                },
                success: function(response) {
                    try {
                        logDebug('Services loaded successfully', response);
                        
                        if (response && response.success && response.data && response.data.length > 0) {
                            displayServices(response.data);
                            logDebug('Services displayed', response.data.length + ' services');
                        } else if (response && response.success && response.data) {
                            logWarning('No services found in response');
                            $servicesGrid.html('<div class="no-services"><p>No services available at the moment.</p></div>');
                        } else {
                            logError('Invalid response format', response);
                            $servicesGrid.html('<div class="error-services"><p>Error loading services. Please refresh the page.</p></div>');
                        }
                    } catch (error) {
                        logError('Error processing services response', error);
                        $servicesGrid.html('<div class="error-services"><p>Error processing services. Please refresh the page.</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    logError('AJAX error loading services', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    $servicesGrid.html('<div class="error-services"><p>Error loading services. Please refresh the page.</p></div>');
                }
            });
        } catch (error) {
            logError('Error in loadServices function', error);
        }
    }
    
    /**
     * Display services in the grid
     */
    function displayServices(services) {
        try {
            logDebug('Displaying services', services);
            
            const $servicesGrid = $('.services-grid');
            
            if ($servicesGrid.length === 0) {
                logError('Services grid container not found');
                return;
            }
            
            if (!services || !Array.isArray(services)) {
                logError('Invalid services data provided', services);
                $servicesGrid.html('<div class="error-services"><p>Invalid services data.</p></div>');
                return;
            }
            
            const hasMultipleStaff = $('#has_multiple_staff').val() === '1';
            const currencySymbol = (typeof salon_booking_ajax !== 'undefined' && salon_booking_ajax.currency_symbol) || 'R';
            
            logDebug('Display settings', {
                hasMultipleStaff: hasMultipleStaff,
                currencySymbol: currencySymbol,
                servicesCount: services.length
            });
            
            // Group services by category
            const servicesByCategory = {};
            services.forEach(service => {
                try {
                    if (!service || typeof service !== 'object') {
                        logWarning('Invalid service object', service);
                        return;
                    }
                    
                    const category = service.category || 'Uncategorized';
                    if (!servicesByCategory[category]) {
                        servicesByCategory[category] = [];
                    }
                    servicesByCategory[category].push(service);
                } catch (error) {
                    logError('Error processing service', { service, error });
                }
            });
            
            logDebug('Services grouped by category', servicesByCategory);
            
            let html = '';
            
            Object.keys(servicesByCategory).forEach(category => {
                try {
                    html += `<div class="service-category">`;
                    html += `<h4 class="category-title">${escapeHtml(category)}</h4>`;
                    html += `<div class="category-services">`;
                    
                    servicesByCategory[category].forEach(service => {
                        try {
                            const nextStep = hasMultipleStaff ? 'staff' : 'datetime';
                            const nextStepText = hasMultipleStaff ? 'Continue to Staff Selection' : 'Continue to Date & Time';
                            
                            // Validate service data
                            const serviceId = service.id || '';
                            const serviceName = service.name || 'Unnamed Service';
                            const serviceDescription = service.description || '';
                            const serviceDuration = service.duration || 0;
                            const servicePrice = parseFloat(service.price) || 0;
                            const serviceUpfrontFee = parseFloat(service.upfront_fee) || 0;
                            
                            html += `
                                <div class="service-card" data-service-id="${serviceId}" 
                                     data-duration="${serviceDuration}" 
                                     data-price="${servicePrice}"
                                     data-upfront-fee="${serviceUpfrontFee}">
                                    <div class="service-info">
                                        <h5 class="service-name">${escapeHtml(serviceName)}</h5>
                                        <p class="service-description">${escapeHtml(serviceDescription)}</p>
                                        <div class="service-details">
                                            <span class="service-duration">${serviceDuration} min</span>
                                            <span class="service-price">${currencySymbol}${servicePrice.toFixed(2)}</span>
                                        </div>
                                        <div class="service-upfront">
                                            <small>Upfront fee: ${currencySymbol}${serviceUpfrontFee.toFixed(2)}</small>
                                        </div>
                                    </div>
                                    <div class="service-select">
                                        <button type="button" class="btn-select-service">Select</button>
                                    </div>
                                    
                                    <div class="service-continue-container" style="display: none;">
                                        <div class="continue-button-wrapper">
                                            <div class="selected-service-summary">
                                                <span class="summary-service-name"></span>
                                                <span class="summary-service-price"></span>
                                            </div>
                                            <button type="button" class="btn-continue-inline" data-next="${nextStep}">${nextStepText}</button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } catch (error) {
                            logError('Error rendering service card', { service, error });
                        }
                    });
                    
                    html += `</div></div>`;
                } catch (error) {
                    logError('Error rendering category', { category, error });
                }
            });
            
            $servicesGrid.html(html);
            logDebug('Services HTML rendered successfully');
            
            // Handle pre-selection if specified in URL
            if (window.preSelectServiceId) {
                try {
                    logDebug('Pre-selecting service', window.preSelectServiceId);
                    setTimeout(() => {
                        try {
                            const $serviceCard = $(`.service-card[data-service-id="${window.preSelectServiceId}"]`);
                            if ($serviceCard.length) {
                                selectService($serviceCard);
                                logDebug('Service pre-selected successfully');
                            } else {
                                logWarning('Pre-select service not found', window.preSelectServiceId);
                            }
                            delete window.preSelectServiceId;
                        } catch (error) {
                            logError('Error in pre-selection timeout', error);
                        }
                    }, 100);
                } catch (error) {
                    logError('Error setting up pre-selection', error);
                }
            }
        } catch (error) {
            logError('Error in displayServices function', error);
        }
    }
    
    /**
     * Load staff dynamically when needed
     */
    function loadStaff() {
        try {
            logDebug('Loading staff');
            
            const $staffGrid = $('.staff-grid');
            
            if ($staffGrid.length === 0) {
                logWarning('Staff grid container not found');
                return;
            }
            
            // Check if salon_booking_ajax is available
            if (typeof salon_booking_ajax === 'undefined') {
                logError('salon_booking_ajax is not defined. Please check if the script is properly localized.');
                $staffGrid.html('<div class="error-message"><p>Configuration error. Please refresh the page.</p></div>');
                return;
            }
            
            // Show loading state
            $staffGrid.html('<div class="staff-loading"><div class="loading-spinner"></div><p>Loading staff...</p></div>');
            logDebug('Staff loading state displayed');
            
            // Use WordPress AJAX to get staff
            $.ajax({
                url: salon_booking_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'salon_booking_get_staff',
                    nonce: salon_booking_ajax.nonce,
                    service_id: selectedService ? selectedService.id : null
                },
                timeout: 10000,
                beforeSend: function() {
                    logDebug('Sending AJAX request for staff');
                },
                success: function(response) {
                    try {
                        logDebug('Staff loaded successfully', response);
                        
                        if (response && response.success && response.data && response.data.length > 0) {
                            displayStaff(response.data);
                            logDebug('Staff displayed', response.data.length + ' staff members');
                        } else if (response && response.success && response.data) {
                            logWarning('No staff found in response');
                            $staffGrid.html('<div class="no-staff"><p>No staff available at the moment.</p></div>');
                        } else {
                            logError('Invalid staff response format', response);
                            $staffGrid.html('<div class="error-staff"><p>Error loading staff. Please refresh the page.</p></div>');
                        }
                    } catch (error) {
                        logError('Error processing staff response', error);
                        $staffGrid.html('<div class="error-staff"><p>Error processing staff. Please refresh the page.</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    logError('AJAX error loading staff', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    $staffGrid.html('<div class="error-staff"><p>Error loading staff. Please refresh the page.</p></div>');
                }
            });
        } catch (error) {
            logError('Error in loadStaff function', error);
        }
    }
    
    /**
     * Display staff in the grid
     */
    function displayStaff(staff) {
        try {
            logDebug('Displaying staff', staff);
            
            const $staffGrid = $('.staff-grid');
            
            if ($staffGrid.length === 0) {
                logError('Staff grid container not found');
                return;
            }
            
            if (!staff || !Array.isArray(staff)) {
                logError('Invalid staff data provided', staff);
                $staffGrid.html('<div class="error-staff"><p>Invalid staff data.</p></div>');
                return;
            }
            
            logDebug('Staff display settings', {
                staffCount: staff.length
            });
            
            let html = '';
            
            staff.forEach(staffMember => {
                try {
                    if (!staffMember || typeof staffMember !== 'object') {
                        logWarning('Invalid staff member object', staffMember);
                        return;
                    }
                    
                    // Validate and parse specialties safely
                    let specialties = [];
                    let specialtiesText = '';
                    
                    try {
                        if (staffMember.specialties) {
                            if (typeof staffMember.specialties === 'string') {
                                specialties = JSON.parse(staffMember.specialties);
                            } else if (Array.isArray(staffMember.specialties)) {
                                specialties = staffMember.specialties;
                            }
                        }
                        specialtiesText = Array.isArray(specialties) ? specialties.join(', ') : '';
                    } catch (parseError) {
                        logWarning('Error parsing staff specialties', { staffMember, parseError });
                        specialtiesText = '';
                    }
                    
                    // Validate staff data
                    const staffId = staffMember.id || '';
                    const staffName = staffMember.name || 'Unnamed Staff';
                    const isOwner = Boolean(staffMember.is_owner);
                    
                    html += `
                        <div class="staff-card" data-staff-id="${staffId}">
                            <div class="staff-info">
                                <h5 class="staff-name">${escapeHtml(staffName)}</h5>
                                ${isOwner ? '<span class="staff-badge owner">Owner</span>' : ''}
                                <div class="staff-specialties">
                                    <strong>Specialties:</strong>
                                    <span>${escapeHtml(specialtiesText)}</span>
                                </div>
                            </div>
                            <div class="staff-select">
                                <button type="button" class="btn-select-staff">Select</button>
                            </div>
                        </div>
                    `;
                } catch (error) {
                    logError('Error rendering staff card', { staffMember, error });
                }
            });
            
            $staffGrid.html(html);
            logDebug('Staff HTML rendered successfully');
            
            // Handle pre-selection if specified in URL
            if (window.preSelectStaffId) {
                try {
                    logDebug('Pre-selecting staff', window.preSelectStaffId);
                    setTimeout(() => {
                        try {
                            const $staffCard = $(`.staff-card[data-staff-id="${window.preSelectStaffId}"]`);
                            if ($staffCard.length) {
                                selectStaff($staffCard);
                                logDebug('Staff pre-selected successfully');
                            } else {
                                logWarning('Pre-select staff not found', window.preSelectStaffId);
                            }
                            delete window.preSelectStaffId;
                        } catch (error) {
                            logError('Error in staff pre-selection timeout', error);
                        }
                    }, 100);
                } catch (error) {
                    logError('Error setting up staff pre-selection', error);
                }
            }
        } catch (error) {
            logError('Error in displayStaff function', error);
        }
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Initialize FullCalendar with performance optimizations
     */
    function initializeCalendar() {
        try {
            logDebug('Initializing calendar');
            
            const calendarEl = document.getElementById('booking-calendar');
            
            if (!calendarEl) {
                logWarning('Calendar element not found');
                return;
            }
            
            if (typeof FullCalendar === 'undefined') {
                logError('FullCalendar library not loaded');
                calendarEl.innerHTML = '<div class="calendar-error">Calendar could not be loaded. Please refresh the page.</div>';
                return;
            }
            
            // Show loading indicator
            calendarEl.innerHTML = '<div class="calendar-loading">Loading calendar...</div>';
            logDebug('Calendar loading indicator displayed');
            
            // Use setTimeout to prevent blocking UI
            setTimeout(function() {
                try {
                    logDebug('Creating FullCalendar instance');
                    
                    calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: ''
                        },
                        selectable: true,
                        selectMirror: true,
                        validRange: {
                            start: new Date()
                        },
                        selectConstraint: {
                            start: new Date()
                        },
                        // Optimize rendering
                        lazyFetching: true,
                        eventDisplay: 'block',
                        dayMaxEvents: 3,
                        moreLinkClick: 'popover',
                        select: function(info) {
                            try {
                                logDebug('Calendar date selected', info.startStr);
                                selectDate(info.startStr);
                            } catch (error) {
                                logError('Error in calendar select handler', error);
                            }
                        },
                        dateClick: function(info) {
                            try {
                                logDebug('Calendar date clicked', info.dateStr);
                                selectDate(info.dateStr);
                            } catch (error) {
                                logError('Error in calendar dateClick handler', error);
                            }
                        },
                        // Add loading states
                        loading: function(isLoading) {
                            try {
                                logDebug('Calendar loading state changed', isLoading);
                                if (isLoading) {
                                    $('.time-selection').addClass('loading');
                                } else {
                                    $('.time-selection').removeClass('loading');
                                }
                            } catch (error) {
                                logError('Error in calendar loading handler', error);
                            }
                        }
                    });
                    
                    calendar.render();
                    logDebug('Calendar rendered successfully');
                } catch (error) {
                    logError('Error creating or rendering calendar', error);
                    calendarEl.innerHTML = '<div class="calendar-error">Error loading calendar. Please refresh the page.</div>';
                }
            }, 100);
        } catch (error) {
            logError('Error in initializeCalendar function', error);
        }
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        try {
            logDebug('Binding event handlers');
            
            // Service selection
            $(document).on('click', '.service-card', function() {
                try {
                    logDebug('Service card clicked');
                    selectService($(this));
                } catch (error) {
                    logError('Error in service card click handler', error);
                }
            });
            
            // Service select button
            $(document).on('click', '.btn-select-service', function(e) {
                try {
                    logDebug('Service select button clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    const $serviceCard = $(this).closest('.service-card');
                    selectService($serviceCard);
                } catch (error) {
                    logError('Error in service select button handler', error);
                }
            });

            // Staff selection
            $(document).on('click', '.staff-card', function() {
                try {
                    logDebug('Staff card clicked');
                    selectStaff($(this));
                } catch (error) {
                    logError('Error in staff card click handler', error);
                }
            });

            // Time slot selection
            $(document).on('click', '.time-slot', function() {
                try {
                    logDebug('Time slot clicked');
                    selectTimeSlot($(this));
                } catch (error) {
                    logError('Error in time slot click handler', error);
                }
            });

            // Navigation buttons
            $('#btn-next-staff').on('click', function() {
                try {
                    logDebug('Next staff button clicked');
                    if (selectedService) {
                        showStep(2);
                    } else {
                        logWarning('No service selected for staff navigation');
                    }
                } catch (error) {
                    logError('Error in next staff button handler', error);
                }
            });
            
            logDebug('Event handlers bound successfully');
        } catch (error) {
            logError('Error in bindEvents function', error);
        }

        $('#btn-next-datetime').on('click', function() {
            if (selectedStaff || window.singleStaffMode) {
                // Initialize calendar only when datetime step is accessed
                if (!calendar) {
                    initializeCalendar();
                }
                showStep(3);
            }
        });

        $('#btn-next-details').on('click', function() {
            if (selectedDate && selectedTime) {
                showStep(4);
            }
        });

        $('#btn-next-payment').on('click', function() {
            if (validateClientDetails()) {
                updateBookingSummary();
                showStep(5);
            }
        });

        // Previous buttons
        $('#btn-prev-service').on('click', function() {
            showStep(1);
        });

        $('#btn-prev-staff').on('click', function() {
            showStep(2);
        });

        $('#btn-prev-datetime').on('click', function() {
            showStep(3);
        });

        $('#btn-prev-details').on('click', function() {
            showStep(4);
        });

        // Form submission
        $('#salon-booking-form').on('submit', function(e) {
            e.preventDefault();
            processBooking();
        });

        // Client details validation
        $('#client_name, #client_email').on('input', function() {
            updateDetailsButton();
        });
        
        // Inline continue button events
        $(document).on('click', '.btn-continue-inline', function() {
            const nextStep = $(this).data('next');
            if (nextStep === 'staff') {
                showStep(2);
            } else if (nextStep === 'datetime') {
                showStep(window.singleStaffMode ? 3 : 4);
            }
            hideInlineContinue();
        });
    }

    /**
     * Select a service
     */
    function selectService($serviceCard) {
        if ($serviceCard.length === 0) {
            return;
        }
        
        // Remove previous selection
        $('.service-card').removeClass('selected');
        
        // Add selection to clicked card
        $serviceCard.addClass('selected');
        
        // Store selected service data
        selectedService = {
            id: $serviceCard.data('service-id'),
            name: $serviceCard.find('.service-name').text(),
            duration: $serviceCard.data('duration'),
            price: $serviceCard.data('price'),
            upfront_fee: $serviceCard.data('upfront-fee')
        };
        
        // Update hidden field
        $('#selected_service_id').val(selectedService.id);
        $('#booking_duration').val(selectedService.duration);
        $('#total_amount').val(selectedService.price);
        $('#upfront_fee').val(selectedService.upfront_fee);
        
        // Show inline continue button
        showInlineContinue($serviceCard);
        
        // Enable next button
        if (window.singleStaffMode) {
            $('#btn-next-datetime').prop('disabled', false);
        } else {
            $('#btn-next-staff').prop('disabled', false);
        }
    }

    /**
     * Select a staff member
     */
    function selectStaff($staffCard) {
        // Remove previous selection
        $('.staff-card').removeClass('selected');
        
        // Add selection to clicked card
        $staffCard.addClass('selected');
        
        // Store selected staff data
        selectedStaff = {
            id: $staffCard.data('staff-id'),
            name: $staffCard.find('.staff-name').text()
        };
        
        // Update hidden field
        $('#selected_staff_id').val(selectedStaff.id);
        
        // Enable next button
        $('#btn-next-datetime').prop('disabled', false);
    }

    /**
     * Select a date
     */
    function selectDate(dateStr) {
        selectedDate = dateStr;
        $('#selected_date').val(dateStr);
        
        // Load available times for the selected date
        loadAvailableTimes(dateStr);
        
        // Clear previous time selection
        selectedTime = null;
        $('#selected_time').val('');
        $('#btn-next-details').prop('disabled', true);
    }

    /**
     * Load available times for a date with debouncing
     */
    const loadAvailableTimes = debounce(function(date) {
        const $timeSlotsContainer = $('#available-times');
        
        // Get staff ID from selected staff or single staff mode
        const staffId = selectedStaff ? selectedStaff.id : (window.singleStaffMode ? window.singleStaffId : null);
        
        if (!staffId) {
            $timeSlotsContainer.html('<p class="error-times">Please select a staff member first.</p>');
            return;
        }
        
        // Show loading with better UX
        $timeSlotsContainer.html('<div class="loading-times"><div class="loading-spinner"></div><p>Loading available times...</p></div>');
        
        // AJAX request to get available times
        $.ajax({
            url: salon_booking_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'salon_booking_check_availability',
                nonce: salon_booking_ajax.nonce,
                date: date,
                staff_id: staffId,
                duration: selectedService.duration
            },
            timeout: 10000, // 10 second timeout
            success: function(response) {
                if (response.success) {
                    displayAvailableTimes(response.data.times);
                } else {
                    $timeSlotsContainer.html('<p class="no-times">No available times for this date.</p>');
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    $timeSlotsContainer.html('<p class="error-times">Request timed out. Please try again.</p>');
                } else {
                    $timeSlotsContainer.html('<p class="error-times">Error loading times. Please try again.</p>');
                }
            }
        });
    }, 300); // 300ms debounce

    /**
     * Display available times
     */
    function displayAvailableTimes(times) {
        const $timeSlotsContainer = $('#available-times');
        
        if (times.length === 0) {
            $timeSlotsContainer.html('<p class="no-times">No available times for this date.</p>');
            return;
        }
        
        let timeSlotsHtml = '';
        times.forEach(function(time) {
            timeSlotsHtml += `<div class="time-slot" data-time="${time}"><span>${time}</span></div>`;
        });
        
        $timeSlotsContainer.html(timeSlotsHtml);
    }

    /**
     * Select a time slot
     */
    function selectTimeSlot($timeSlot) {
        // Remove previous selection
        $('.time-slot').removeClass('selected');
        
        // Add selection to clicked slot
        $timeSlot.addClass('selected');
        
        // Store selected time
        selectedTime = $timeSlot.data('time');
        $('#selected_time').val(selectedTime);
        
        // Enable next button
        $('#btn-next-details').prop('disabled', false);
    }

    /**
     * Validate client details
     */
    function validateClientDetails() {
        const name = $('#client_name').val().trim();
        const email = $('#client_email').val().trim();
        
        if (!name) {
            alert('Please enter your full name.');
            $('#client_name').focus();
            return false;
        }
        
        if (!email || !isValidEmail(email)) {
            alert('Please enter a valid email address.');
            $('#client_email').focus();
            return false;
        }
        
        return true;
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Update details button state
     */
    function updateDetailsButton() {
        const name = $('#client_name').val().trim();
        const email = $('#client_email').val().trim();
        
        $('#btn-next-payment').prop('disabled', !(name && email && isValidEmail(email)));
    }

    /**
     * Update booking summary
     */
    function updateBookingSummary() {
        const staffName = selectedStaff ? selectedStaff.name : (window.singleStaffMode ? window.singleStaffName : '');
        
        if (!selectedService || (!selectedStaff && !window.singleStaffMode) || !selectedDate || !selectedTime) {
            return;
        }
        
        const currencySymbol = salon_booking_ajax.currency_symbol || 'R';
        const remainingBalance = selectedService.price - selectedService.upfront_fee;
        
        // Format date
        const dateObj = new Date(selectedDate);
        const formattedDate = dateObj.toLocaleDateString('en-ZA', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        // Update summary fields
        $('#summary-service').text(selectedService.name);
        $('#summary-staff').text(staffName);
        $('#summary-datetime').text(`${formattedDate} at ${selectedTime}`);
        $('#summary-duration').text(`${selectedService.duration} minutes`);
        $('#summary-total').text(`${currencySymbol}${selectedService.price.toFixed(2)}`);
        $('#summary-upfront').text(`${currencySymbol}${selectedService.upfront_fee.toFixed(2)}`);
        $('#summary-remaining').text(`${currencySymbol}${remainingBalance.toFixed(2)}`);
    }

    /**
     * Update confirm button state
     */
    function updateConfirmButton() {
        const cardComplete = cardElement && cardElement._complete;
        const detailsValid = validateClientDetails();
        
        $('#btn-confirm-booking').prop('disabled', !(cardComplete && detailsValid));
    }

    /**
     * Process the booking
     */
    function processBooking() {
        if (!stripe || !cardElement) {
            alert('Payment system not initialized. Please refresh the page.');
            return;
        }
        
        const $submitButton = $('#btn-confirm-booking');
        const $buttonText = $submitButton.find('.btn-text');
        const $buttonLoading = $submitButton.find('.btn-loading');
        
        // Disable button and show loading
        $submitButton.prop('disabled', true);
        $buttonText.hide();
        $buttonLoading.show();
        
        // Create payment method
        stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: $('#client_name').val(),
                email: $('#client_email').val(),
                phone: $('#client_phone').val()
            }
        }).then(function(result) {
            if (result.error) {
                // Show error to customer
                showPaymentError(result.error.message);
                resetSubmitButton();
            } else {
                // Send payment method to server
                processPayment(result.paymentMethod.id);
            }
        });
    }

    /**
     * Process payment on server
     */
    function processPayment(paymentMethodId) {
        const staffId = selectedStaff ? selectedStaff.id : (window.singleStaffMode ? window.singleStaffId : null);
        
        const bookingData = {
            action: 'salon_booking_create_booking',
            nonce: salon_booking_ajax.nonce,
            service_id: selectedService.id,
            staff_id: staffId,
            booking_date: selectedDate,
            booking_time: selectedTime,
            client_name: $('#client_name').val(),
            client_email: $('#client_email').val(),
            client_phone: $('#client_phone').val(),
            notes: $('#notes').val(),
            payment_method_id: paymentMethodId,
            amount: selectedService.upfront_fee
        };
        
        $.ajax({
            url: salon_booking_ajax.ajax_url,
            type: 'POST',
            data: bookingData,
            success: function(response) {
                if (response.success) {
                    if (response.data.requires_action) {
                        // Handle 3D Secure authentication
                        handlePaymentAction(response.data.payment_intent);
                    } else {
                        // Payment successful
                        showBookingSuccess();
                    }
                } else {
                    showPaymentError(response.data.message || 'Booking failed. Please try again.');
                    resetSubmitButton();
                }
            },
            error: function() {
                showPaymentError('Network error. Please check your connection and try again.');
                resetSubmitButton();
            }
        });
    }

    /**
     * Handle payment action (3D Secure)
     */
    function handlePaymentAction(paymentIntent) {
        stripe.handleCardAction(paymentIntent.client_secret).then(function(result) {
            if (result.error) {
                showPaymentError(result.error.message);
                resetSubmitButton();
            } else {
                // Confirm payment on server
                confirmPayment(result.paymentIntent.id);
            }
        });
    }

    /**
     * Confirm payment after 3D Secure
     */
    function confirmPayment(paymentIntentId) {
        $.ajax({
            url: salon_booking_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'salon_booking_confirm_payment',
                nonce: salon_booking_ajax.nonce,
                payment_intent_id: paymentIntentId
            },
            success: function(response) {
                if (response.success) {
                    showBookingSuccess();
                } else {
                    showPaymentError(response.data.message || 'Payment confirmation failed.');
                    resetSubmitButton();
                }
            },
            error: function() {
                showPaymentError('Network error during payment confirmation.');
                resetSubmitButton();
            }
        });
    }

    /**
     * Show payment error
     */
    function showPaymentError(message) {
        $('#card-errors').text(message);
    }

    /**
     * Reset submit button
     */
    function resetSubmitButton() {
        const $submitButton = $('#btn-confirm-booking');
        const $buttonText = $submitButton.find('.btn-text');
        const $buttonLoading = $submitButton.find('.btn-loading');
        
        $submitButton.prop('disabled', false);
        $buttonText.show();
        $buttonLoading.hide();
    }

    /**
     * Show booking success
     */
    function showBookingSuccess() {
        $('#salon-booking-form').hide();
        $('#booking-success').show();
        
        // Scroll to top
        $('html, body').animate({
            scrollTop: $('#booking-success').offset().top - 50
        }, 500);
    }

    /**
     * Show a specific step
     */
    function showStep(stepNumber) {
        const hasMultipleStaff = $('#has_multiple_staff').val() === '1';
        
        // In single staff mode, skip step 2 (staff selection)
        if (!hasMultipleStaff && stepNumber === 2) {
            stepNumber = 3;
        }
        
        // Hide inline continue button when leaving service selection step
        if (stepNumber !== 1) {
            hideInlineContinue();
        }
        
        // Hide all steps
        $('.booking-step').removeClass('active');
        
        // Show/hide staff step based on staff count
        if (hasMultipleStaff) {
            $('#step-staff').show();
        } else {
            $('#step-staff').hide();
        }
        
        // Show target step
        $(`#step-${getStepName(stepNumber)}`).addClass('active');
        
        currentStep = stepNumber;
        
        // Load staff when staff step is shown
        if (stepNumber === 2 && hasMultipleStaff) {
            loadStaff();
        }
        
        // Initialize calendar when datetime step is shown
        if (stepNumber === 3 && !calendar) {
            initializeCalendar();
        }
        
        // Scroll to top of form
        const $container = $('.salon-booking-container');
        if ($container.length > 0) {
            $('html, body').animate({
                scrollTop: $container.offset().top - 50
            }, 300);
        }
    }

    /**
     * Get step name by number
     */
    function getStepName(stepNumber) {
        const stepNames = {
            1: 'service',
            2: 'staff',
            3: 'datetime',
            4: 'details',
            5: 'payment'
        };
        return stepNames[stepNumber] || 'service';
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        const currencySymbol = salon_booking_ajax.currency_symbol || 'R';
        return `${currencySymbol}${parseFloat(amount).toFixed(2)}`;
    }

    /**
     * Show inline continue button
     */
    function showInlineContinue($serviceCard) {
        // Hide all continue containers first
        $('.service-continue-container').slideUp(200);
        
        if (selectedService && $serviceCard.length > 0) {
            // Find the continue container within the selected card
            const $continueContainer = $serviceCard.find('.service-continue-container');
            
            if ($continueContainer.length > 0) {
                // Update service info in continue button
                const $serviceName = $continueContainer.find('.summary-service-name');
                const $servicePrice = $continueContainer.find('.summary-service-price');
                
                $serviceName.text(selectedService.name);
                $servicePrice.text('R' + parseFloat(selectedService.price).toFixed(2));
                
                // Show the continue container with slide down animation
                $continueContainer.slideDown(300);
            }
        }
    }
    
    /**
     * Hide inline continue button
     */
    function hideInlineContinue() {
        $('.service-continue-container').slideUp(200);
    }

    /**
     * Utility function to debounce function calls
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Expose functions globally
    window.SalonBooking = {
        showStep: showStep,
        selectedService: function() { return selectedService; },
        selectedStaff: function() { return selectedStaff; },
        selectedDate: function() { return selectedDate; },
        selectedTime: function() { return selectedTime; }
    };

})(jQuery);

/**
 * Additional utility functions
 */

// Format phone number as user types
jQuery(document).ready(function($) {
    $('#client_phone').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 10) {
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
        }
        $(this).val(value);
    });
});

// Prevent form submission on Enter key (except in textarea)
jQuery(document).ready(function($) {
    $('#salon-booking-form').on('keypress', function(e) {
        if (e.which === 13 && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });
});

// Auto-resize textarea
jQuery(document).ready(function($) {
    $('#notes').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});



// Add loading states to AJAX calls
jQuery(document).ajaxStart(function() {
    jQuery('body').addClass('loading');
}).ajaxStop(function() {
    jQuery('body').removeClass('loading');
});

// Handle browser back button
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.step) {
        window.salonBooking.showStep(event.state.step);
    }
});

// Add browser history for steps
jQuery(document).ready(function($) {
    $('.btn-next, .btn-prev').on('click', function() {
        setTimeout(function() {
            const currentStep = $('.booking-step.active').attr('id').replace('step-', '');
            const stepNumber = {
                'service': 1,
                'staff': 2,
                'datetime': 3,
                'details': 4,
                'payment': 5
            }[currentStep] || 1;
            
            history.pushState({step: stepNumber}, '', window.location.href);
        }, 100);
    });
    
    // Handle staff booking buttons from staff list shortcode
     $('.salon-booking-book-staff-btn').on('click', function() {
         var staffId = $(this).data('staff-id');
         
         // Check if booking form exists on the page
         var bookingForm = $('.salon-booking-form');
         if (bookingForm.length) {
             // Scroll to booking form
             $('html, body').animate({
                 scrollTop: bookingForm.offset().top - 100
             }, 500);
             
             // Pre-select the staff member if the form supports it
             var staffSelect = bookingForm.find('select[name="staff_id"]');
             if (staffSelect.length) {
                 staffSelect.val(staffId).trigger('change');
             }
         } else {
             // Get booking page URL from localized data or use default
             var bookingPageUrl = salon_booking_ajax && salon_booking_ajax.booking_page_url 
                 ? salon_booking_ajax.booking_page_url 
                 : '/booking/';
             window.location.href = bookingPageUrl + '?staff_id=' + staffId;
         }
     });
     
     // Handle service booking buttons from services list shortcode
     $('.salon-booking-book-service-btn').on('click', function() {
         var serviceId = $(this).data('service-id');
         
         // Check if booking form exists on the page
         var bookingForm = $('.salon-booking-form');
         if (bookingForm.length) {
             // Scroll to booking form
             $('html, body').animate({
                 scrollTop: bookingForm.offset().top - 100
             }, 500);
             
             // Pre-select the service if the form supports it
             var serviceSelect = bookingForm.find('select[name="service_id"]');
             if (serviceSelect.length) {
                 serviceSelect.val(serviceId).trigger('change');
             }
         } else {
             // Get booking page URL from localized data or use default
             var bookingPageUrl = salon_booking_ajax && salon_booking_ajax.booking_page_url 
                 ? salon_booking_ajax.booking_page_url 
                 : '/booking/';
             window.location.href = bookingPageUrl + '?service_id=' + serviceId;
         }
     });
});