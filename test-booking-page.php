<?php
// Test if booking page detection is working
require_once('../../../wp-load.php');

echo '<h1>Booking Page Detection Test</h1>';

// Test the is_booking_page function
if (class_exists('Salon_Booking_Public')) {
    $public = new Salon_Booking_Public('salon-booking-plugin', '1.0.0');
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($public);
    $method = $reflection->getMethod('is_booking_page');
    $method->setAccessible(true);
    
    $is_booking_page = $method->invoke($public);
    
    echo '<p>Current URL: ' . $_SERVER['REQUEST_URI'] . '</p>';
    echo '<p>Is booking page: ' . ($is_booking_page ? 'Yes' : 'No') . '</p>';
    
    // Test script enqueuing
    global $wp_scripts;
    if (isset($wp_scripts->registered['salon-booking-plugin'])) {
        echo '<p>Main script registered: Yes</p>';
        echo '<p>Script URL: ' . $wp_scripts->registered['salon-booking-plugin']->src . '</p>';
    } else {
        echo '<p>Main script registered: No</p>';
    }
    
} else {
    echo '<p>Salon_Booking_Public class not found</p>';
}

// Test if salon_booking_ajax is localized
echo '<script>';
echo 'console.log("salon_booking_ajax:", typeof salon_booking_ajax !== "undefined" ? salon_booking_ajax : "undefined");';
echo '</script>';
?>