<?php
// Simple PHP test
require_once('../../../wp-load.php');

echo '<h1>Simple Test</h1>';
echo '<p>WordPress loaded: ' . (function_exists('wp_head') ? 'Yes' : 'No') . '</p>';
echo '<p>Plugin active: ' . (is_plugin_active('salon-booking-plugin/salon-booking-plugin.php') ? 'Yes' : 'No') . '</p>';

// Test database
global $wpdb;
$services_table = $wpdb->prefix . 'salon_services';
$services_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
echo '<p>Services in database: ' . $services_count . '</p>';

// Test AJAX URL
echo '<p>AJAX URL: ' . admin_url('admin-ajax.php') . '</p>';

// Test nonce
echo '<p>Nonce: ' . wp_create_nonce('salon_booking_nonce') . '</p>';

// Test services directly
if (class_exists('Salon_Booking_Database')) {
    try {
        $services = Salon_Booking_Database::get_services(true);
        echo '<p>Services found: ' . count($services) . '</p>';
        if (!empty($services)) {
            echo '<ul>';
            foreach ($services as $service) {
                echo '<li>' . $service->name . ' - $' . $service->price . '</li>';
            }
            echo '</ul>';
        }
    } catch (Exception $e) {
        echo '<p>Error getting services: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p>Salon_Booking_Database class not found</p>';
}
?>