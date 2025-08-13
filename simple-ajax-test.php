<?php
// Simple AJAX test without WordPress theme
require_once('../../../wp-load.php');

// Force the plugin to recognize this as a booking page
$_SERVER['REQUEST_URI'] = '/simple-ajax-test.php';

// Initialize the public class and enqueue scripts
if (class_exists('Salon_Booking_Public')) {
    $public = new Salon_Booking_Public('salon-booking-plugin', '1.0.0');
    $public->enqueue_scripts();
    $public->enqueue_styles();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple AJAX Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        .button {
            background: #0066cc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
    <?php wp_head(); ?>
</head>
<body>
    <div class="container">
        <h1>Simple AJAX Test</h1>
        
        <div id="debug-info" class="result info">
            <h3>Debug Information</h3>
            <p>WordPress loaded: <?php echo function_exists('wp_head') ? 'Yes' : 'No'; ?></p>
            <p>Plugin class exists: <?php echo class_exists('Salon_Booking_Public') ? 'Yes' : 'No'; ?></p>
            <p>AJAX URL: <?php echo admin_url('admin-ajax.php'); ?></p>
            <p>Current URL: <?php echo $_SERVER['REQUEST_URI']; ?></p>
        </div>
        
        <button id="test-services" class="button">Test Load Services</button>
        <button id="test-staff" class="button">Test Load Staff</button>
        <button id="test-ajax-object" class="button">Test AJAX Object</button>
        
        <div id="results"></div>
    </div>

    <script>
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded');
        console.log('jQuery available:', typeof jQuery !== 'undefined');
        console.log('salon_booking_ajax:', typeof salon_booking_ajax !== 'undefined' ? salon_booking_ajax : 'undefined');
        
        // Test AJAX object button
        document.getElementById('test-ajax-object').addEventListener('click', function() {
            const results = document.getElementById('results');
            if (typeof salon_booking_ajax !== 'undefined') {
                results.innerHTML = '<div class="result success">salon_booking_ajax object is available:<br>' + JSON.stringify(salon_booking_ajax, null, 2) + '</div>';
            } else {
                results.innerHTML = '<div class="result error">salon_booking_ajax object is NOT available</div>';
            }
        });
        
        // Test services button
        document.getElementById('test-services').addEventListener('click', function() {
            console.log('Testing services...');
            const results = document.getElementById('results');
            
            if (typeof salon_booking_ajax === 'undefined') {
                results.innerHTML = '<div class="result error">Error: salon_booking_ajax not defined</div>';
                return;
            }
            
            if (typeof jQuery === 'undefined') {
                results.innerHTML = '<div class="result error">Error: jQuery not available</div>';
                return;
            }
            
            results.innerHTML = '<div class="result info">Loading services...</div>';
            
            jQuery.ajax({
                url: salon_booking_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_services',
                    nonce: salon_booking_ajax.nonce
                },
                success: function(response) {
                    console.log('Services response:', response);
                    if (response.success && response.data) {
                        let html = '<div class="result success">Services loaded successfully! Count: ' + response.data.length + '<br>';
                        response.data.forEach(function(service) {
                            html += service.name + ' - $' + service.price + '<br>';
                        });
                        html += '</div>';
                        results.innerHTML = html;
                    } else {
                        results.innerHTML = '<div class="result error">No services found or invalid response</div>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Services error:', status, error, xhr.responseText);
                    results.innerHTML = '<div class="result error">Error loading services: ' + error + '<br>Status: ' + status + '<br>Response: ' + xhr.responseText + '</div>';
                }
            });
        });
        
        // Test staff button
        document.getElementById('test-staff').addEventListener('click', function() {
            console.log('Testing staff...');
            const results = document.getElementById('results');
            
            if (typeof salon_booking_ajax === 'undefined') {
                results.innerHTML = '<div class="result error">Error: salon_booking_ajax not defined</div>';
                return;
            }
            
            if (typeof jQuery === 'undefined') {
                results.innerHTML = '<div class="result error">Error: jQuery not available</div>';
                return;
            }
            
            results.innerHTML = '<div class="result info">Loading staff...</div>';
            
            jQuery.ajax({
                url: salon_booking_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_staff',
                    nonce: salon_booking_ajax.nonce
                },
                success: function(response) {
                    console.log('Staff response:', response);
                    if (response.success && response.data) {
                        let html = '<div class="result success">Staff loaded successfully! Count: ' + response.data.length + '<br>';
                        response.data.forEach(function(staff) {
                            html += staff.name + '<br>';
                        });
                        html += '</div>';
                        results.innerHTML = html;
                    } else {
                        results.innerHTML = '<div class="result error">No staff found or invalid response</div>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Staff error:', status, error, xhr.responseText);
                    results.innerHTML = '<div class="result error">Error loading staff: ' + error + '<br>Status: ' + status + '<br>Response: ' + xhr.responseText + '</div>';
                }
            });
        });
    });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>