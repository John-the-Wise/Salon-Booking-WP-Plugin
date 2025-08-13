<?php
/**
 * Test WordPress Booking Page
 * 
 * This file creates a proper WordPress page for testing the salon booking plugin
 * with real AJAX endpoints and database connectivity.
 * 
 * To use this file:
 * 1. Copy it to your WordPress root directory
 * 2. Access it via: http://localhost/test-wordpress-booking.php
 * 3. This will load the full WordPress environment with proper AJAX support
 */

// Load WordPress
require_once('wp-load.php');

// Ensure the plugin is loaded
if (!function_exists('is_plugin_active') || !is_plugin_active('salon-booking-plugin/salon-booking-plugin.php')) {
    wp_die('Salon Booking Plugin is not active. Please activate the plugin first.');
}

// Get WordPress header
get_header();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salon Booking Test - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-notice {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .booking-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .form-section {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        .form-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007cba;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 5px rgba(0, 124, 186, 0.3);
        }
        .btn {
            background: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #005a87;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .calendar-container {
            margin: 20px 0;
        }
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .time-slot {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .time-slot:hover {
            background: #f0f8ff;
            border-color: #007cba;
        }
        .time-slot.selected {
            background: #007cba;
            color: white;
            border-color: #005a87;
        }
        .time-slot.unavailable {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
        }
        .payment-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .booking-summary {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background: #ffe6e6;
            color: #d00;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            display: none;
        }
        .success-message {
            background: #e8f5e8;
            color: #2d5a2d;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            display: none;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-notice">
            <h2>🧪 WordPress Salon Booking Test Environment</h2>
            <p><strong>Environment:</strong> <?php echo WP_ENVIRONMENT_TYPE; ?> | <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
            <p><strong>Database:</strong> Connected to <?php echo DB_NAME; ?> | <strong>Plugin Version:</strong> <?php echo SALON_BOOKING_VERSION; ?></p>
            <p><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
        </div>

        <h1>Salon Booking System Test</h1>
        
        <div class="booking-form">
            <form id="booking-form">
                <!-- Service Selection -->
                <div class="form-section">
                    <h3>1. Select Service</h3>
                    <div class="form-group">
                        <label for="service-select">Choose a Service:</label>
                        <select id="service-select" name="service_id" required>
                            <option value="">Loading services...</option>
                        </select>
                    </div>
                    <div id="service-details" style="display: none;">
                        <p><strong>Duration:</strong> <span id="service-duration"></span> minutes</p>
                        <p><strong>Price:</strong> R<span id="service-price"></span></p>
                        <p><strong>Upfront Fee:</strong> R<span id="service-upfront"></span></p>
                    </div>
                </div>

                <!-- Staff Selection -->
                <div class="form-section">
                    <h3>2. Select Staff Member</h3>
                    <div class="form-group">
                        <label for="staff-select">Choose Staff:</label>
                        <select id="staff-select" name="staff_id" required disabled>
                            <option value="">Please select a service first</option>
                        </select>
                    </div>
                </div>

                <!-- Date & Time Selection -->
                <div class="form-section">
                    <h3>3. Select Date & Time</h3>
                    <div class="calendar-container">
                        <div id="calendar"></div>
                    </div>
                    <div id="time-slots-container" style="display: none;">
                        <h4>Available Times:</h4>
                        <div class="time-slots" id="time-slots"></div>
                    </div>
                    <input type="hidden" id="selected-date" name="booking_date">
                    <input type="hidden" id="selected-time" name="booking_time">
                </div>

                <!-- Client Information -->
                <div class="form-section">
                    <h3>4. Your Information</h3>
                    <div class="form-group">
                        <label for="client-name">Full Name *</label>
                        <input type="text" id="client-name" name="client_name" required>
                    </div>
                    <div class="form-group">
                        <label for="client-email">Email Address *</label>
                        <input type="email" id="client-email" name="client_email" required>
                    </div>
                    <div class="form-group">
                        <label for="client-phone">Phone Number</label>
                        <input type="tel" id="client-phone" name="client_phone">
                    </div>
                    <div class="form-group">
                        <label for="notes">Special Requests</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any special requests or notes..."></textarea>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div id="booking-summary" class="booking-summary" style="display: none;">
                    <h3>Booking Summary</h3>
                    <div id="summary-content"></div>
                </div>

                <!-- Payment Section -->
                <div id="payment-section" class="payment-section" style="display: none;">
                    <h3>5. Payment</h3>
                    <div id="card-element">
                        <!-- Stripe Elements will create form elements here -->
                    </div>
                    <div id="card-errors" role="alert"></div>
                </div>

                <!-- Messages -->
                <div id="error-message" class="error-message"></div>
                <div id="success-message" class="success-message"></div>

                <!-- Submit Button -->
                <button type="submit" id="submit-booking" class="btn" disabled>
                    Book Appointment
                </button>
            </form>
        </div>
    </div>

    <?php wp_footer(); ?>
    
    <script>
        // Debug information
        console.log('WordPress Booking Test Page Loaded');
        console.log('salon_booking_ajax object:', window.salon_booking_ajax);
        console.log('jQuery version:', jQuery.fn.jquery);
        
        // Test AJAX connectivity
        jQuery(document).ready(function($) {
            console.log('Testing AJAX connectivity...');
            
            // Test basic AJAX call
            $.ajax({
                url: salon_booking_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_services',
                    nonce: salon_booking_ajax.nonce
                },
                success: function(response) {
                    console.log('AJAX Test Success:', response);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Test Failed:', status, error);
                }
            });
        });
    </script>
</body>
</html>

<?php
// Get WordPress footer
get_footer();
?>