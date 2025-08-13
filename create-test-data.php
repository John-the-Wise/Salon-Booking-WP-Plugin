<?php
/**
 * Create Test Data for Salon Booking Plugin
 * 
 * This script creates sample services and staff members for testing
 * Run this once to populate the database with test data
 */

// Load WordPress
require_once('../../../wp-load.php');

// Ensure the plugin is loaded
if (!function_exists('is_plugin_active') || !is_plugin_active('salon-booking-plugin/salon-booking-plugin.php')) {
    wp_die('Salon Booking Plugin is not active. Please activate the plugin first.');
}

// Include the database class
require_once('includes/class-database.php');

$database = new Salon_Booking_Database();

// Create sample services
$services = [
    [
        'name' => 'Haircut & Styling',
        'description' => 'Professional haircut with styling',
        'duration' => 60,
        'price' => 150.00,
        'upfront_fee' => 50.00,
        'category' => 'Hair Services',
        'is_active' => 1
    ],
    [
        'name' => 'Hair Color',
        'description' => 'Full hair coloring service',
        'duration' => 120,
        'price' => 300.00,
        'upfront_fee' => 100.00,
        'category' => 'Hair Services',
        'is_active' => 1
    ],
    [
        'name' => 'Manicure',
        'description' => 'Professional nail care and polish',
        'duration' => 45,
        'price' => 80.00,
        'upfront_fee' => 30.00,
        'category' => 'Nail Services',
        'is_active' => 1
    ],
    [
        'name' => 'Pedicure',
        'description' => 'Foot care and nail polish',
        'duration' => 60,
        'price' => 100.00,
        'upfront_fee' => 40.00,
        'category' => 'Nail Services',
        'is_active' => 1
    ],
    [
        'name' => 'Facial Treatment',
        'description' => 'Relaxing facial with skincare',
        'duration' => 90,
        'price' => 200.00,
        'upfront_fee' => 75.00,
        'category' => 'Skincare',
        'is_active' => 1
    ]
];

// Create sample staff members
$staff = [
    [
        'name' => 'Sarah Johnson',
        'email' => 'sarah@salon.com',
        'phone' => '+27 11 123 4567',
        'specialties' => 'Hair Services',
        'bio' => 'Senior hair stylist with 10+ years experience',
        'is_active' => 1,
        'working_hours' => json_encode([
            'monday' => ['09:00', '17:00'],
            'tuesday' => ['09:00', '17:00'],
            'wednesday' => ['09:00', '17:00'],
            'thursday' => ['09:00', '17:00'],
            'friday' => ['09:00', '17:00'],
            'saturday' => ['09:00', '15:00'],
            'sunday' => ['closed']
        ])
    ],
    [
        'name' => 'Maria Rodriguez',
        'email' => 'maria@salon.com',
        'phone' => '+27 11 234 5678',
        'specialties' => 'Nail Services',
        'bio' => 'Expert nail technician specializing in nail art',
        'is_active' => 1,
        'working_hours' => json_encode([
            'monday' => ['10:00', '18:00'],
            'tuesday' => ['10:00', '18:00'],
            'wednesday' => ['10:00', '18:00'],
            'thursday' => ['10:00', '18:00'],
            'friday' => ['10:00', '18:00'],
            'saturday' => ['09:00', '16:00'],
            'sunday' => ['closed']
        ])
    ],
    [
        'name' => 'Emma Thompson',
        'email' => 'emma@salon.com',
        'phone' => '+27 11 345 6789',
        'specialties' => 'Skincare, Facial Treatment',
        'bio' => 'Licensed esthetician with expertise in skincare',
        'is_active' => 1,
        'working_hours' => json_encode([
            'monday' => ['09:00', '17:00'],
            'tuesday' => ['09:00', '17:00'],
            'wednesday' => ['closed'],
            'thursday' => ['09:00', '17:00'],
            'friday' => ['09:00', '17:00'],
            'saturday' => ['10:00', '14:00'],
            'sunday' => ['closed']
        ])
    ]
];

echo "<h1>Creating Test Data for Salon Booking Plugin</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

// Insert services
echo "<h2>Creating Services...</h2>";
foreach ($services as $service) {
    try {
        $result = $database->save_service($service);
        if ($result) {
            echo "<p style='color: green;'>✓ Created service: {$service['name']} (R{$service['price']})</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create service: {$service['name']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating service {$service['name']}: " . $e->getMessage() . "</p>";
    }
}

// Insert staff
echo "<h2>Creating Staff Members...</h2>";
foreach ($staff as $member) {
    try {
        $result = $database->save_staff($member);
        if ($result) {
            echo "<p style='color: green;'>✓ Created staff member: {$member['name']} ({$member['specialties']})</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create staff member: {$member['name']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating staff member {$member['name']}: " . $e->getMessage() . "</p>";
    }
}

// Display summary
echo "<h2>Summary</h2>";
try {
    $all_services = $database->get_services();
    $all_staff = $database->get_staff();
    
    echo "<p><strong>Total Services:</strong> " . count($all_services) . "</p>";
    echo "<p><strong>Total Staff:</strong> " . count($all_staff) . "</p>";
    
    if (count($all_services) > 0) {
        echo "<h3>Available Services:</h3>";
        echo "<ul>";
        foreach ($all_services as $service) {
            echo "<li>{$service->name} - R{$service->price} ({$service->duration} min)</li>";
        }
        echo "</ul>";
    }
    
    if (count($all_staff) > 0) {
        echo "<h3>Available Staff:</h3>";
        echo "<ul>";
        foreach ($all_staff as $member) {
            echo "<li>{$member->name} - {$member->specialties}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error retrieving data: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>1. Visit the <a href='test-wordpress-booking.php' target='_blank'>WordPress Booking Test Page</a> to test the functionality</p>";
echo "<p>2. Check the browser console for AJAX debug information</p>";
echo "<p>3. Test the booking flow from service selection to payment</p>";

echo "</div>";
?>