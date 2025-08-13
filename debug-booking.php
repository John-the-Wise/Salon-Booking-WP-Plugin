<?php
/**
 * Debug Booking Page
 * 
 * This file helps debug why the booking form isn't loading services.
 */

// Load WordPress
require_once('../../../wp-load.php');

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
    <title>Debug Booking - <?php bloginfo('name'); ?></title>
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
        .debug-info {
            background: #f0f8ff;
            border: 1px solid #0066cc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error {
            background: #ffe6e6;
            border: 1px solid #cc0000;
            color: #cc0000;
        }
        .success {
            background: #e6ffe6;
            border: 1px solid #00cc00;
            color: #00cc00;
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .service-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .service-card:hover {
            border-color: #0066cc;
            box-shadow: 0 2px 8px rgba(0,102,204,0.2);
        }
        .service-card.selected {
            border-color: #0066cc;
            background-color: #f0f8ff;
        }
        #debug-output {
            background: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .button {
            background: #0066cc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .button:hover {
            background: #0052a3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Booking System</h1>
        
        <!-- Debug Information -->
        <div class="debug-info">
            <h3>System Status</h3>
            <p><strong>WordPress:</strong> <?php echo function_exists('wp_head') ? '✓ Loaded' : '✗ Not Loaded'; ?></p>
            <p><strong>Plugin:</strong> <?php echo is_plugin_active('salon-booking-plugin/salon-booking-plugin.php') ? '✓ Active' : '✗ Inactive'; ?></p>
            <p><strong>Database:</strong> 
                <?php 
                global $wpdb;
                $services_table = $wpdb->prefix . 'salon_services';
                $services_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
                echo $services_count > 0 ? "✓ $services_count services found" : '✗ No services found';
                ?>
            </p>
            <p><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
            <p><strong>Nonce:</strong> <?php echo wp_create_nonce('salon_booking_nonce'); ?></p>
        </div>

        <!-- Manual AJAX Test -->
        <div class="debug-info">
            <h3>Manual AJAX Test</h3>
            <button id="test-services-ajax" class="button">Test Services AJAX</button>
            <button id="test-staff-ajax" class="button">Test Staff AJAX</button>
            <div id="ajax-results"></div>
        </div>

        <!-- Booking Form -->
        <div class="booking-form">
            <h2>1. Select Service</h2>
            <div class="services-grid">
                <div class="service-loading">
                    <p>Loading services...</p>
                </div>
            </div>
        </div>

        <!-- Debug Console -->
        <div id="debug-output"></div>
    </div>

    <script>
    // Debug console
    function debugLog(message) {
        const output = document.getElementById('debug-output');
        const timestamp = new Date().toLocaleTimeString();
        output.innerHTML += `[${timestamp}] ${message}\n`;
        output.scrollTop = output.scrollHeight;
        console.log('[Debug]', message);
    }

    // Check if salon_booking_ajax is available
    document.addEventListener('DOMContentLoaded', function() {
        debugLog('DOM loaded');
        
        if (typeof salon_booking_ajax !== 'undefined') {
            debugLog('salon_booking_ajax is available: ' + JSON.stringify(salon_booking_ajax));
        } else {
            debugLog('ERROR: salon_booking_ajax is not defined!');
        }

        // Manual AJAX tests
        document.getElementById('test-services-ajax').addEventListener('click', function() {
            debugLog('Testing services AJAX...');
            
            if (typeof salon_booking_ajax === 'undefined') {
                debugLog('ERROR: Cannot test - salon_booking_ajax not available');
                return;
            }

            fetch(salon_booking_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'salon_booking_get_services',
                    nonce: salon_booking_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                debugLog('Services AJAX response: ' + JSON.stringify(data));
                document.getElementById('ajax-results').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                debugLog('Services AJAX error: ' + error.message);
                document.getElementById('ajax-results').innerHTML = '<div class="error">Error: ' + error.message + '</div>';
            });
        });

        document.getElementById('test-staff-ajax').addEventListener('click', function() {
            debugLog('Testing staff AJAX...');
            
            if (typeof salon_booking_ajax === 'undefined') {
                debugLog('ERROR: Cannot test - salon_booking_ajax not available');
                return;
            }

            fetch(salon_booking_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'salon_booking_get_staff',
                    nonce: salon_booking_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                debugLog('Staff AJAX response: ' + JSON.stringify(data));
                document.getElementById('ajax-results').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                debugLog('Staff AJAX error: ' + error.message);
                document.getElementById('ajax-results').innerHTML = '<div class="error">Error: ' + error.message + '</div>';
            });
        });
    });
    </script>

    <?php wp_footer(); ?>
</body>
</html>