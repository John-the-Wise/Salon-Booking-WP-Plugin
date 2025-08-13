<?php
/**
 * Debug Database Connection and Tables
 * 
 * This script checks if the database tables exist and tests basic connectivity
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Database Debug Information</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

// Check if plugin is active
echo "<h2>Plugin Status</h2>";
if (function_exists('is_plugin_active') && is_plugin_active('salon-booking-plugin/salon-booking-plugin.php')) {
    echo "<p style='color: green;'>✓ Plugin is active</p>";
} else {
    echo "<p style='color: red;'>✗ Plugin is NOT active</p>";
}

// Check if constants are defined
echo "<h2>Plugin Constants</h2>";
if (defined('SALON_BOOKING_TABLE_PREFIX')) {
    echo "<p style='color: green;'>✓ SALON_BOOKING_TABLE_PREFIX: " . SALON_BOOKING_TABLE_PREFIX . "</p>";
} else {
    echo "<p style='color: red;'>✗ SALON_BOOKING_TABLE_PREFIX not defined</p>";
}

if (defined('SALON_BOOKING_VERSION')) {
    echo "<p style='color: green;'>✓ SALON_BOOKING_VERSION: " . SALON_BOOKING_VERSION . "</p>";
} else {
    echo "<p style='color: red;'>✗ SALON_BOOKING_VERSION not defined</p>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
global $wpdb;
if ($wpdb) {
    echo "<p style='color: green;'>✓ WordPress database connection active</p>";
    echo "<p><strong>Database Name:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Table Prefix:</strong> " . $wpdb->prefix . "</p>";
} else {
    echo "<p style='color: red;'>✗ No database connection</p>";
}

// Check if tables exist
echo "<h2>Database Tables</h2>";
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
    echo "<p style='color: red;'>Cannot check tables - SALON_BOOKING_TABLE_PREFIX not defined</p>";
}

// Test database class
echo "<h2>Database Class Test</h2>";
try {
    if (class_exists('Salon_Booking_Database')) {
        echo "<p style='color: green;'>✓ Salon_Booking_Database class exists</p>";
        
        // Test get_services
        $services = Salon_Booking_Database::get_services();
        if (is_array($services)) {
            echo "<p style='color: green;'>✓ get_services() works - found " . count($services) . " services</p>";
            if (count($services) > 0) {
                echo "<h3>Services:</h3><ul>";
                foreach ($services as $service) {
                    echo "<li>{$service->name} - R{$service->price} ({$service->duration} min)</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>✗ get_services() failed</p>";
        }
        
        // Test get_staff
        $staff = Salon_Booking_Database::get_staff();
        if (is_array($staff)) {
            echo "<p style='color: green;'>✓ get_staff() works - found " . count($staff) . " staff members</p>";
            if (count($staff) > 0) {
                echo "<h3>Staff:</h3><ul>";
                foreach ($staff as $member) {
                    echo "<li>{$member->name} - {$member->specialties}</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>✗ get_staff() failed</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Salon_Booking_Database class does NOT exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error testing database class: " . $e->getMessage() . "</p>";
}

// Test AJAX object
echo "<h2>AJAX Configuration</h2>";
if (function_exists('admin_url')) {
    echo "<p><strong>AJAX URL:</strong> " . admin_url('admin-ajax.php') . "</p>";
} else {
    echo "<p style='color: red;'>admin_url function not available</p>";
}

if (function_exists('wp_create_nonce')) {
    $nonce = wp_create_nonce('salon_booking_nonce');
    echo "<p><strong>Test Nonce:</strong> $nonce</p>";
} else {
    echo "<p style='color: red;'>wp_create_nonce function not available</p>";
}

// Check if public class exists
echo "<h2>Public Class Status</h2>";
if (class_exists('Salon_Booking_Public')) {
    echo "<p style='color: green;'>✓ Salon_Booking_Public class exists</p>";
} else {
    echo "<p style='color: red;'>✗ Salon_Booking_Public class does NOT exist</p>";
}

echo "</div>";
?>