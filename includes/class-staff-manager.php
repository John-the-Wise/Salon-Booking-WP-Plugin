<?php

/**
 * The staff manager class.
 *
 * This class handles all staff-related operations including creation,
 * updating, deletion, and retrieval of staff members and their availability.
 *
 * @package SalonBooking
 * @subpackage SalonBooking/includes
 */

class Salon_Booking_Staff_Manager {

    /**
     * Get all staff members.
     *
     * @param bool $active_only Whether to return only active staff.
     * @return array Array of staff objects.
     */
    public function get_staff($active_only = false) {
        global $wpdb;
        
        $sql = "SELECT * FROM " . SALON_BOOKING_TABLE_STAFF;
        
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY name ASC";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get a single staff member by ID.
     *
     * @param int $staff_id The staff ID.
     * @return object|null The staff object or null if not found.
     */
    public function get_staff_member($staff_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . SALON_BOOKING_TABLE_STAFF . " WHERE id = %d",
            $staff_id
        ));
    }
    
    /**
     * Create a new staff member.
     *
     * @param array $staff_data The staff data.
     * @return int|false The staff ID on success, false on failure.
     */
    public function create_staff($staff_data) {
        global $wpdb;
        
        $defaults = array(
            'is_active' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $staff_data = wp_parse_args($staff_data, $defaults);
        
        $result = $wpdb->insert(
            SALON_BOOKING_TABLE_STAFF,
            $staff_data,
            array(
                '%s', // name
                '%s', // email
                '%s', // phone
                '%s', // bio
                '%s', // specialties
                '%d', // is_active
                '%s', // created_at
                '%s'  // updated_at
            )
        );
        
        if ($result === false) {
            return false;
        }

        $staff_id = $wpdb->insert_id;
        
        // Create default availability based on salon's trading hours
        $this->create_default_availability($staff_id);
        
        return $staff_id;
    }
    
    /**
     * Create default availability for a new staff member based on salon's trading hours.
     *
     * @param int $staff_id The staff ID.
     * @return bool True on success, false on failure.
     */
    private function create_default_availability($staff_id) {
        $default_schedule = array(
            'sunday' => array('is_available' => 0, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => ''),
            'monday' => array('is_available' => 0, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => ''),
            'tuesday' => array('is_available' => 1, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'break_start' => '', 'break_end' => ''),
            'wednesday' => array('is_available' => 1, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'break_start' => '', 'break_end' => ''),
            'thursday' => array('is_available' => 1, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'break_start' => '', 'break_end' => ''),
            'friday' => array('is_available' => 1, 'start_time' => '09:00:00', 'end_time' => '15:00:00', 'break_start' => '', 'break_end' => ''),
            'saturday' => array('is_available' => 1, 'start_time' => '09:00:00', 'end_time' => '15:00:00', 'break_start' => '', 'break_end' => '')
        );
        
        $success = true;
        foreach ($default_schedule as $day => $schedule) {
            $result = $this->set_staff_availability($staff_id, $day, $schedule);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Update a staff member.
     *
     * @param int $staff_id The staff ID.
     * @param array $staff_data The staff data to update.
     * @return bool True on success, false on failure.
     */
    public function update_staff($staff_id, $staff_data) {
        global $wpdb;
        
        $staff_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            SALON_BOOKING_TABLE_STAFF,
            $staff_data,
            array('id' => $staff_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a staff member.
     *
     * @param int $staff_id The staff ID.
     * @return bool True on success, false on failure.
     */
    public function delete_staff($staff_id) {
        global $wpdb;
        
        // Check if staff has any bookings
        $booking_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE staff_id = %d",
            $staff_id
        ));
        
        if ($booking_count > 0) {
            return false; // Cannot delete staff with existing bookings
        }
        
        // Delete availability records first
        $wpdb->delete(
            SALON_BOOKING_TABLE_AVAILABILITY,
            array('staff_id' => $staff_id),
            array('%d')
        );
        
        // Delete staff record
        $result = $wpdb->delete(
            SALON_BOOKING_TABLE_STAFF,
            array('id' => $staff_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get staff availability for a specific day.
     *
     * @param int $staff_id The staff ID.
     * @param string $day_of_week The day of week (monday, tuesday, etc.).
     * @return object|null The availability object or null if not found.
     */
    public function get_staff_availability($staff_id, $day_of_week) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . SALON_BOOKING_TABLE_AVAILABILITY . " WHERE staff_id = %d AND day_of_week = %s",
            $staff_id,
            $day_of_week
        ));
    }
    
    /**
     * Get all availability for a staff member.
     *
     * @param int $staff_id The staff ID.
     * @return array Array of availability objects.
     */
    public function get_staff_weekly_availability($staff_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SALON_BOOKING_TABLE_AVAILABILITY . " WHERE staff_id = %d ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')",
            $staff_id
        ));
    }
    
    /**
     * Set staff availability for a specific day.
     *
     * @param int $staff_id The staff ID.
     * @param string $day_of_week The day of week.
     * @param array $availability_data The availability data.
     * @return bool True on success, false on failure.
     */
    public function set_staff_availability($staff_id, $day_of_week, $availability_data) {
        global $wpdb;
        
        // Check if availability already exists
        $existing = $this->get_staff_availability($staff_id, $day_of_week);
        
        $availability_data['staff_id'] = $staff_id;
        $availability_data['day_of_week'] = $day_of_week;
        $availability_data['updated_at'] = current_time('mysql');
        
        if ($existing) {
            // Update existing availability
            $result = $wpdb->update(
                SALON_BOOKING_TABLE_AVAILABILITY,
                $availability_data,
                array(
                    'staff_id' => $staff_id,
                    'day_of_week' => $day_of_week
                ),
                array(
                    '%d', // staff_id
                    '%s', // day_of_week
                    '%d', // is_available
                    '%s', // start_time
                    '%s', // end_time
                    '%s', // break_start
                    '%s', // break_end
                    '%s'  // updated_at
                ),
                array('%d', '%s')
            );
        } else {
            // Insert new availability
            $availability_data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                SALON_BOOKING_TABLE_AVAILABILITY,
                $availability_data,
                array(
                    '%d', // staff_id
                    '%s', // day_of_week
                    '%d', // is_available
                    '%s', // start_time
                    '%s', // end_time
                    '%s', // break_start
                    '%s', // break_end
                    '%s', // created_at
                    '%s'  // updated_at
                )
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Get available time slots for a staff member on a specific date.
     *
     * @param int $staff_id The staff ID.
     * @param string $date The date (Y-m-d format).
     * @param int $service_duration Duration in minutes.
     * @return array Array of available time slots.
     */
    public function get_available_time_slots($staff_id, $date, $service_duration = 60) {
        global $wpdb;
        
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        // Get staff availability for the day
        $availability = $this->get_staff_availability($staff_id, $day_of_week);
        
        if (!$availability || !$availability->is_available) {
            return array();
        }
        
        // Get existing bookings for the date
        $existing_bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT booking_time, 
                    (SELECT duration FROM " . SALON_BOOKING_TABLE_SERVICES . " WHERE id = service_id) as duration
             FROM " . SALON_BOOKING_TABLE_BOOKINGS . "
             WHERE staff_id = %d 
             AND booking_date = %s 
             AND status NOT IN ('cancelled', 'no_show')
             ORDER BY booking_time",
            $staff_id,
            $date
        ));
        
        // Generate time slots
        $time_slots = array();
        $slot_interval = get_option('salon_booking_time_slot_interval', 30); // minutes
        
        $start_time = strtotime($availability->start_time);
        $end_time = strtotime($availability->end_time);
        $break_start = !empty($availability->break_start) ? strtotime($availability->break_start) : null;
        $break_end = !empty($availability->break_end) ? strtotime($availability->break_end) : null;
        
        $current_time = $start_time;
        
        while ($current_time + ($service_duration * 60) <= $end_time) {
            $slot_time = date('H:i:s', $current_time);
            $slot_end_time = $current_time + ($service_duration * 60);
            
            // Check if slot conflicts with break time
            $conflicts_with_break = false;
            if ($break_start && $break_end) {
                if (($current_time < $break_end) && ($slot_end_time > $break_start)) {
                    $conflicts_with_break = true;
                }
            }
            
            // Check if slot conflicts with existing bookings
            $conflicts_with_booking = false;
            foreach ($existing_bookings as $booking) {
                $booking_start = strtotime($booking->booking_time);
                $booking_end = $booking_start + ($booking->duration * 60);
                
                if (($current_time < $booking_end) && ($slot_end_time > $booking_start)) {
                    $conflicts_with_booking = true;
                    break;
                }
            }
            
            // Add slot if no conflicts
            if (!$conflicts_with_break && !$conflicts_with_booking) {
                $time_slots[] = $slot_time;
            }
            
            $current_time += ($slot_interval * 60);
        }
        
        return $time_slots;
    }
    
    /**
     * Get staff statistics.
     *
     * @param int $staff_id Optional staff ID to get stats for specific staff.
     * @return array Statistics array.
     */
    public function get_staff_stats($staff_id = null) {
        global $wpdb;
        
        $stats = array(
            'total_staff' => 0,
            'active_staff' => 0,
            'total_bookings' => 0,
            'upcoming_bookings' => 0
        );
        
        if ($staff_id) {
            // Stats for specific staff member
            $stats['total_bookings'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE staff_id = %d",
                $staff_id
            ));
            
            $stats['upcoming_bookings'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . "
                 WHERE staff_id = %d 
                 AND CONCAT(booking_date, ' ', booking_time) > %s
                 AND status = 'confirmed'",
                $staff_id,
                current_time('mysql')
            ));
        } else {
            // General staff stats
            $stats['total_staff'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_STAFF
            );
            
            $stats['active_staff'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_STAFF . " WHERE is_active = 1"
            );
            
            $stats['total_bookings'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS
            );
            
            $stats['upcoming_bookings'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . "
                 WHERE CONCAT(booking_date, ' ', booking_time) > %s
                 AND status = 'confirmed'",
                current_time('mysql')
            ));
        }
        
        return $stats;
    }
}