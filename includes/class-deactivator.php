<?php

/**
 * Fired during plugin deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Salon_Booking_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('salon_booking_send_reminders');
        wp_clear_scheduled_hook('salon_booking_cleanup_expired');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't delete tables or data on deactivation
        // This preserves user data in case they reactivate the plugin
    }
}