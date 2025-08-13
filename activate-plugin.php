<?php
/**
 * Manually Activate Plugin and Create Tables
 * 
 * This script manually activates the plugin and creates the necessary database tables
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Plugin Activation and Table Creation</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

// Include plugin files
require_once('salon-booking-plugin.php');
require_once('includes/class-activator.php');

echo "<h2>Step 1: Activate Plugin</h2>";

// Check if plugin is already active
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (is_plugin_active('salon-booking-plugin/salon-booking-plugin.php')) {
    echo "<p style='color: green;'>✓ Plugin is already active</p>";
} else {
    // Activate the plugin
    $result = activate_plugin('salon-booking-plugin/salon-booking-plugin.php');
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>✗ Failed to activate plugin: " . $result->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Plugin activated successfully</p>";
    }
}

echo "<h2>Step 2: Create Database Tables</h2>";

try {
    // Run the activator
    Salon_Booking_Activator::activate();
    echo "<p style='color: green;'>✓ Database tables created/updated successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error creating tables: " . $e->getMessage() . "</p>";
}

echo "<h2>Step 3: Verify Tables</h2>";

global $wpdb;
if (defined('SALON_BOOKING_TABLE_PREFIX')) {
    $tables = [
        'services' => $wpdb->prefix . SALON_BOOKING_TABLE_PREFIX . 'services',
        'staff' => $wpdb->prefix . SALON_BOOKING_TABLE_PREFIX . 'staff',
        'bookings' => $wpdb->prefix . SALON_BOOKING_TABLE_PREFIX . 'bookings',
        'availability' => $wpdb->prefix . SALON_BOOKING_TABLE_PREFIX . 'availability'
    ];
    
    foreach ($tables as $name => $table_name) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            echo "<p style='color: green;'>✓ Table '$name' exists ($table_name) - $count records</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$name' does NOT exist ($table_name)</p>";
        }
    }
} else {
    echo "<p style='color: red;'>SALON_BOOKING_TABLE_PREFIX not defined</p>";
}

echo "<h2>Step 4: Test Database Operations</h2>";

try {
    if (class_exists('Salon_Booking_Database')) {
        echo "<p style='color: green;'>✓ Database class loaded</p>";
        
        // Test services
        $services = Salon_Booking_Database::get_services();
        echo "<p><strong>Services found:</strong> " . count($services) . "</p>";
        
        // Test staff
        $staff = Salon_Booking_Database::get_staff();
        echo "<p><strong>Staff found:</strong> " . count($staff) . "</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Database class not available</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database test error: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>1. <a href='create-test-data.php'>Create Test Data</a> - Add sample services and staff</p>";
echo "<p>2. <a href='test-wordpress-booking.php'>Test Booking Form</a> - Test the complete booking flow</p>";
echo "<p>3. <a href='debug-database.php'>Debug Database</a> - Check database status</p>";

echo "</div>";
?>