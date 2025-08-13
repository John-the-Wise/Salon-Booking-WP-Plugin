<?php

/**
 * The email notifications class.
 *
 * This class is a wrapper/alias for the Email Handler class to maintain
 * compatibility with different naming conventions used in the codebase.
 *
 * @package SalonBooking
 * @subpackage SalonBooking/includes
 */

// Include the actual email handler class
require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-email-handler.php';

/**
 * Alias class for backward compatibility.
 * This allows the code to use either class name.
 */
class Salon_Booking_Email_Notifications extends Salon_Booking_Email_Handler {
    // This class inherits all functionality from Salon_Booking_Email_Handler
    // No additional code needed - it's just an alias
}