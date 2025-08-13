<?php

/**
 * The booking manager class.
 *
 * This class handles all booking-related operations including creation,
 * updating, deletion, and retrieval of bookings.
 *
 * @package SalonBooking
 * @subpackage SalonBooking/includes
 */

class Salon_Booking_Manager {

    /**
     * Get all bookings with optional filters.
     *
     * @param array $filters Optional filters for bookings.
     * @return array Array of booking objects.
     */
    public function get_bookings($filters = array()) {
        global $wpdb;
        
        $where_clauses = array('1=1');
        $values = array();
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $values[] = $filters['status'];
        }
        
        if (!empty($filters['staff_id'])) {
            $where_clauses[] = 'staff_id = %d';
            $values[] = $filters['staff_id'];
        }
        
        if (!empty($filters['service_id'])) {
            $where_clauses[] = 'service_id = %d';
            $values[] = $filters['service_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'booking_date >= %s';
            $values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'booking_date <= %s';
            $values[] = $filters['date_to'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = "SELECT * FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE {$where_sql} ORDER BY booking_date DESC, booking_time DESC";
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get a single booking by ID.
     *
     * @param int $booking_id The booking ID.
     * @return object|null The booking object or null if not found.
     */
    public function get_booking($booking_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE id = %d",
            $booking_id
        ));
    }
    
    /**
     * Create a new booking.
     *
     * @param array $booking_data The booking data.
     * @return int|false The booking ID on success, false on failure.
     */
    public function create_booking($booking_data) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $booking_data = wp_parse_args($booking_data, $defaults);
        
        $result = $wpdb->insert(
            SALON_BOOKING_TABLE_BOOKINGS,
            $booking_data,
            array(
                '%s', // client_name
                '%s', // client_email
                '%s', // client_phone
                '%d', // service_id
                '%d', // staff_id
                '%s', // booking_date
                '%s', // booking_time
                '%f', // total_amount
                '%f', // deposit_amount
                '%s', // payment_status
                '%s', // payment_intent_id
                '%s', // status
                '%s', // notes
                '%s', // created_at
                '%s'  // updated_at
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update a booking.
     *
     * @param int $booking_id The booking ID.
     * @param array $booking_data The booking data to update.
     * @return bool True on success, false on failure.
     */
    public function update_booking($booking_id, $booking_data) {
        global $wpdb;
        
        $booking_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            SALON_BOOKING_TABLE_BOOKINGS,
            $booking_data,
            array('id' => $booking_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a booking.
     *
     * @param int $booking_id The booking ID.
     * @return bool True on success, false on failure.
     */
    public function delete_booking($booking_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            SALON_BOOKING_TABLE_BOOKINGS,
            array('id' => $booking_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Check if a time slot is available.
     *
     * @param string $date The booking date.
     * @param string $time The booking time.
     * @param int $staff_id The staff ID.
     * @param int $exclude_booking_id Optional booking ID to exclude from check.
     * @return bool True if available, false otherwise.
     */
    public function is_time_slot_available($date, $time, $staff_id, $exclude_booking_id = 0) {
        global $wpdb;
        
        $sql = "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . "
                WHERE booking_date = %s 
                AND booking_time = %s 
                AND staff_id = %d 
                AND status NOT IN ('cancelled', 'no_show')
                AND id != %d";
        
        $count = $wpdb->get_var($wpdb->prepare($sql, $date, $time, $staff_id, $exclude_booking_id));
        
        return $count == 0;
    }
    
    /**
     * Get booking statistics.
     *
     * @param array $filters Optional filters.
     * @return array Statistics array.
     */
    public function get_booking_stats($filters = array()) {
        global $wpdb;
        
        $stats = array(
            'total' => 0,
            'confirmed' => 0,
            'pending' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'revenue' => 0
        );
        
        // Base query
        $where_clauses = array('1=1');
        $values = array();
        
        // Apply date filters if provided
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'booking_date >= %s';
            $values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'booking_date <= %s';
            $values[] = $filters['date_to'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Get total bookings
        $sql = "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE {$where_sql}";
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        $stats['total'] = (int) $wpdb->get_var($sql);
        
        // Get bookings by status
        $statuses = array('confirmed', 'pending', 'completed', 'cancelled');
        foreach ($statuses as $status) {
            $status_values = array_merge($values, array($status));
            $sql = "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE {$where_sql} AND status = %s";
            $sql = $wpdb->prepare($sql, $status_values);
            $stats[$status] = (int) $wpdb->get_var($sql);
        }
        
        // Get revenue (completed bookings only)
        $revenue_values = array_merge($values, array('completed'));
        $sql = "SELECT SUM(total_amount) FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE {$where_sql} AND status = %s";
        $sql = $wpdb->prepare($sql, $revenue_values);
        $stats['revenue'] = (float) $wpdb->get_var($sql);
        
        return $stats;
    }
    
    /**
     * Get upcoming bookings for reminders.
     *
     * @param int $hours_ahead Number of hours ahead to look for bookings.
     * @return array Array of booking objects.
     */
    public function get_upcoming_bookings($hours_ahead = 24) {
        global $wpdb;
        
        $start_time = current_time('mysql');
        $end_time = date('Y-m-d H:i:s', strtotime("+{$hours_ahead} hours"));
        
        $sql = "SELECT * FROM " . SALON_BOOKING_TABLE_BOOKINGS . "
                WHERE CONCAT(booking_date, ' ', booking_time) BETWEEN %s AND %s
                AND status = 'confirmed'
                ORDER BY booking_date, booking_time";
        
        return $wpdb->get_results($wpdb->prepare($sql, $start_time, $end_time));
    }
}