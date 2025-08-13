<?php
/**
 * AJAX Test Page
 * 
 * This page tests the AJAX endpoints directly to verify they're working
 */

// Load WordPress
require_once('../../../wp-load.php');

// Ensure the plugin is loaded
if (!function_exists('is_plugin_active') || !is_plugin_active('salon-booking-plugin/salon-booking-plugin.php')) {
    wp_die('Salon Booking Plugin is not active. Please activate the plugin first.');
}

get_header();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AJAX Test - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #333;
        }
        .btn {
            background: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover {
            background: #005a87;
        }
        .result {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            white-space: pre-wrap;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
        }
        .success {
            background: #e8f5e8;
            color: #2d5a2d;
        }
        .error {
            background: #ffe6e6;
            color: #d00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 AJAX Endpoints Test</h1>
        
        <div class="test-section">
            <h3>Environment Info</h3>
            <p><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
            <p><strong>Nonce:</strong> <?php echo wp_create_nonce('salon_booking_nonce'); ?></p>
            <p><strong>Plugin Active:</strong> <?php echo is_plugin_active('salon-booking-plugin/salon-booking-plugin.php') ? 'Yes' : 'No'; ?></p>
        </div>
        
        <div class="test-section">
            <h3>Test 1: Get Services</h3>
            <button class="btn" onclick="testGetServices()">Test Get Services</button>
            <div id="services-result" class="result"></div>
        </div>
        
        <div class="test-section">
            <h3>Test 2: Get Staff</h3>
            <button class="btn" onclick="testGetStaff()">Test Get Staff</button>
            <div id="staff-result" class="result"></div>
        </div>
        
        <div class="test-section">
            <h3>Test 3: Check Availability</h3>
            <button class="btn" onclick="testCheckAvailability()">Test Check Availability</button>
            <div id="availability-result" class="result"></div>
        </div>
        
        <div class="test-section">
            <h3>Test 4: Database Direct Test</h3>
            <button class="btn" onclick="testDatabaseDirect()">Test Database Direct</button>
            <div id="database-result" class="result"></div>
        </div>
    </div>

    <?php wp_footer(); ?>
    
    <script>
        console.log('AJAX Test Page Loaded');
        console.log('salon_booking_ajax:', window.salon_booking_ajax);
        
        function testGetServices() {
            const resultDiv = document.getElementById('services-result');
            resultDiv.textContent = 'Testing...';
            resultDiv.className = 'result';
            
            jQuery.ajax({
                url: salon_booking_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_services',
                    nonce: salon_booking_ajax.nonce
                },
                success: function(response) {
                    console.log('Get Services Response:', response);
                    resultDiv.textContent = 'SUCCESS:\n' + JSON.stringify(response, null, 2);
                    resultDiv.className = 'result success';
                },
                error: function(xhr, status, error) {
                    console.error('Get Services Error:', status, error, xhr.responseText);
                    resultDiv.textContent = 'ERROR:\nStatus: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText;
                    resultDiv.className = 'result error';
                }
            });
        }
        
        function testGetStaff() {
            const resultDiv = document.getElementById('staff-result');
            resultDiv.textContent = 'Testing...';
            resultDiv.className = 'result';
            
            jQuery.ajax({
                url: salon_booking_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_staff',
                    nonce: salon_booking_ajax.nonce
                },
                success: function(response) {
                    console.log('Get Staff Response:', response);
                    resultDiv.textContent = 'SUCCESS:\n' + JSON.stringify(response, null, 2);
                    resultDiv.className = 'result success';
                },
                error: function(xhr, status, error) {
                    console.error('Get Staff Error:', status, error, xhr.responseText);
                    resultDiv.textContent = 'ERROR:\nStatus: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText;
                    resultDiv.className = 'result error';
                }
            });
        }
        
        function testCheckAvailability() {
            const resultDiv = document.getElementById('availability-result');
            resultDiv.textContent = 'Testing...';
            resultDiv.className = 'result';
            
            jQuery.ajax({
                url: salon_booking_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'salon_booking_check_availability',
                    nonce: salon_booking_ajax.nonce,
                    date: '2024-12-20',
                    staff_id: 1
                },
                success: function(response) {
                    console.log('Check Availability Response:', response);
                    resultDiv.textContent = 'SUCCESS:\n' + JSON.stringify(response, null, 2);
                    resultDiv.className = 'result success';
                },
                error: function(xhr, status, error) {
                    console.error('Check Availability Error:', status, error, xhr.responseText);
                    resultDiv.textContent = 'ERROR:\nStatus: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText;
                    resultDiv.className = 'result error';
                }
            });
        }
        
        function testDatabaseDirect() {
            const resultDiv = document.getElementById('database-result');
            resultDiv.textContent = 'Testing...';
            resultDiv.className = 'result';
            
            // Test direct database access via a custom endpoint
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'salon_booking_test_database',
                    nonce: '<?php echo wp_create_nonce('salon_booking_nonce'); ?>'
                },
                success: function(response) {
                    console.log('Database Test Response:', response);
                    resultDiv.textContent = 'SUCCESS:\n' + JSON.stringify(response, null, 2);
                    resultDiv.className = 'result success';
                },
                error: function(xhr, status, error) {
                    console.error('Database Test Error:', status, error, xhr.responseText);
                    resultDiv.textContent = 'ERROR:\nStatus: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText;
                    resultDiv.className = 'result error';
                }
            });
        }
        
        // Auto-test on page load
        jQuery(document).ready(function() {
            console.log('Auto-testing services...');
            setTimeout(testGetServices, 1000);
        });
    </script>
</body>
</html>

<?php
get_footer();
?>