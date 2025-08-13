<?php
// Manual test with script enqueuing
require_once('../../../wp-load.php');

// Manually enqueue scripts
if (class_exists('Salon_Booking_Public')) {
    $public = new Salon_Booking_Public('salon-booking-plugin', '1.0.0');
    $public->enqueue_scripts();
    $public->enqueue_styles();
}

get_header();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manual Test - <?php bloginfo('name'); ?></title>
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
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .service-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .service-card:hover {
            border-color: #0066cc;
        }
        .service-card.selected {
            border-color: #0066cc;
            background: #f0f8ff;
        }
        .debug {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Manual Booking Test</h1>
        
        <div class="debug">
            <h3>Debug Info</h3>
            <p>WordPress loaded: <?php echo function_exists('wp_head') ? 'Yes' : 'No'; ?></p>
            <p>Plugin active: <?php echo is_plugin_active('salon-booking-plugin/salon-booking-plugin.php') ? 'Yes' : 'No'; ?></p>
            <p>AJAX URL: <?php echo admin_url('admin-ajax.php'); ?></p>
        </div>
        
        <button id="test-services" class="button">Test Load Services</button>
        <button id="test-staff" class="button">Test Load Staff</button>
        
        <div id="results"></div>
        
        <h2>Services</h2>
        <div class="services-grid" id="services-container">
            <p>Click "Test Load Services" to load services...</p>
        </div>
    </div>

    <?php wp_footer(); ?>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('Manual test page loaded');
        console.log('salon_booking_ajax:', typeof salon_booking_ajax !== 'undefined' ? salon_booking_ajax : 'undefined');
        
        $('#test-services').click(function() {
            console.log('Testing services...');
            
            if (typeof salon_booking_ajax === 'undefined') {
                $('#results').html('<div style="color: red;">Error: salon_booking_ajax not defined</div>');
                return;
            }
            
            $.ajax({
                url: salon_booking_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_services',
                    nonce: salon_booking_ajax.nonce
                },
                beforeSend: function() {
                    $('#services-container').html('<p>Loading services...</p>');
                },
                success: function(response) {
                    console.log('Services response:', response);
                    $('#results').html('<div style="color: green;">Services loaded successfully!</div>');
                    
                    if (response.success && response.data) {
                        let html = '';
                        response.data.forEach(function(service) {
                            html += `<div class="service-card">
                                <h3>${service.name}</h3>
                                <p>Price: $${service.price}</p>
                                <p>Duration: ${service.duration} minutes</p>
                            </div>`;
                        });
                        $('#services-container').html(html);
                    } else {
                        $('#services-container').html('<p>No services found</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Services error:', status, error, xhr.responseText);
                    $('#results').html('<div style="color: red;">Error loading services: ' + error + '</div>');
                    $('#services-container').html('<p>Error loading services</p>');
                }
            });
        });
        
        $('#test-staff').click(function() {
            console.log('Testing staff...');
            
            if (typeof salon_booking_ajax === 'undefined') {
                $('#results').html('<div style="color: red;">Error: salon_booking_ajax not defined</div>');
                return;
            }
            
            $.ajax({
                url: salon_booking_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'salon_booking_get_staff',
                    nonce: salon_booking_ajax.nonce
                },
                success: function(response) {
                    console.log('Staff response:', response);
                    $('#results').html('<div style="color: green;">Staff loaded successfully!</div>');
                },
                error: function(xhr, status, error) {
                    console.error('Staff error:', status, error, xhr.responseText);
                    $('#results').html('<div style="color: red;">Error loading staff: ' + error + '</div>');
                }
            });
        });
    });
    </script>
</body>
</html>

<?php get_footer(); ?>