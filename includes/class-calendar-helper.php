<?php

/**
 * The calendar helper class.
 *
 * This class provides utility functions for calendar operations,
 * date/time handling, and availability calculations.
 *
 * @package SalonBooking
 * @subpackage SalonBooking/includes
 */

class Salon_Booking_Calendar_Helper {

    /**
     * Get available dates for booking within the booking window.
     *
     * @param int $days_ahead Number of days ahead to look (from settings).
     * @return array Array of available dates.
     */
    public static function get_available_dates($days_ahead = null) {
        if ($days_ahead === null) {
            $days_ahead = get_option('salon_booking_booking_window_days', 30);
        }
        
        $dates = array();
        $start_date = current_time('Y-m-d');
        
        for ($i = 0; $i <= $days_ahead; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $dates[] = $date;
        }
        
        return $dates;
    }
    
    /**
     * Check if a date is available for booking.
     *
     * @param string $date The date to check (Y-m-d format).
     * @return bool True if available, false otherwise.
     */
    public static function is_date_available($date) {
        // Check if date is in the past
        if (strtotime($date) < strtotime(current_time('Y-m-d'))) {
            return false;
        }
        
        // Check if date is within booking window
        $booking_window = get_option('salon_booking_booking_window_days', 30);
        $max_date = date('Y-m-d', strtotime(current_time('Y-m-d') . ' +' . $booking_window . ' days'));
        
        if (strtotime($date) > strtotime($max_date)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get day of week from date.
     *
     * @param string $date The date (Y-m-d format).
     * @return string Day of week in lowercase (monday, tuesday, etc.).
     */
    public static function get_day_of_week($date) {
        return strtolower(date('l', strtotime($date)));
    }
    
    /**
     * Format time for display.
     *
     * @param string $time Time in H:i:s format.
     * @param string $format Optional format (12h or 24h).
     * @return string Formatted time.
     */
    public static function format_time($time, $format = null) {
        if ($format === null) {
            $format = get_option('salon_booking_time_format', '12h');
        }
        
        $timestamp = strtotime($time);
        
        if ($format === '12h') {
            return date('g:i A', $timestamp);
        } else {
            return date('H:i', $timestamp);
        }
    }
    
    /**
     * Format date for display.
     *
     * @param string $date Date in Y-m-d format.
     * @param string $format Optional format.
     * @return string Formatted date.
     */
    public static function format_date($date, $format = null) {
        if ($format === null) {
            $format = get_option('salon_booking_date_format', 'F j, Y');
        }
        
        return date($format, strtotime($date));
    }
    
    /**
     * Get time slots for a given time range and interval.
     *
     * @param string $start_time Start time (H:i:s format).
     * @param string $end_time End time (H:i:s format).
     * @param int $interval Interval in minutes.
     * @param int $duration Service duration in minutes.
     * @return array Array of time slots.
     */
    public static function generate_time_slots($start_time, $end_time, $interval = 30, $duration = 60) {
        $slots = array();
        
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        
        $current = $start;
        
        while ($current + ($duration * 60) <= $end) {
            $slots[] = date('H:i:s', $current);
            $current += ($interval * 60);
        }
        
        return $slots;
    }
    
    /**
     * Check if two time ranges overlap.
     *
     * @param string $start1 Start time of first range.
     * @param string $end1 End time of first range.
     * @param string $start2 Start time of second range.
     * @param string $end2 End time of second range.
     * @return bool True if they overlap, false otherwise.
     */
    public static function time_ranges_overlap($start1, $end1, $start2, $end2) {
        $start1_ts = strtotime($start1);
        $end1_ts = strtotime($end1);
        $start2_ts = strtotime($start2);
        $end2_ts = strtotime($end2);
        
        return ($start1_ts < $end2_ts) && ($end1_ts > $start2_ts);
    }
    
    /**
     * Get calendar events for FullCalendar.
     *
     * @param array $filters Optional filters.
     * @return array Array of calendar events.
     */
    public static function get_calendar_events($filters = array()) {
        global $wpdb;
        
        $where_clauses = array('1=1');
        $values = array();
        
        // Apply filters
        if (!empty($filters['staff_id'])) {
            $where_clauses[] = 'b.staff_id = %d';
            $values[] = $filters['staff_id'];
        }
        
        if (!empty($filters['service_id'])) {
            $where_clauses[] = 'b.service_id = %d';
            $values[] = $filters['service_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'b.status = %s';
            $values[] = $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $where_clauses[] = 'b.booking_date >= %s';
            $values[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where_clauses[] = 'b.booking_date <= %s';
            $values[] = $filters['end_date'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = "SELECT 
                    b.id,
                    b.client_name,
                    b.client_email,
                    b.client_phone,
                    b.booking_date,
                    b.booking_time,
                    b.status,
                    b.total_amount,
                    s.name as service_name,
                    s.duration as service_duration,
                    st.name as staff_name
                FROM " . SALON_BOOKING_TABLE_BOOKINGS . " b
                LEFT JOIN " . SALON_BOOKING_TABLE_SERVICES . " s ON b.service_id = s.id
                LEFT JOIN " . SALON_BOOKING_TABLE_STAFF . " st ON b.staff_id = st.id
                WHERE {$where_sql}
                ORDER BY b.booking_date, b.booking_time";
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        $bookings = $wpdb->get_results($sql);
        
        $events = array();
        
        foreach ($bookings as $booking) {
            $start_datetime = $booking->booking_date . 'T' . $booking->booking_time;
            $end_datetime = date('Y-m-d\TH:i:s', strtotime($start_datetime . ' +' . $booking->service_duration . ' minutes'));
            
            $color = self::get_status_color($booking->status);
            
            $events[] = array(
                'id' => $booking->id,
                'title' => $booking->client_name . ' - ' . $booking->service_name,
                'start' => $start_datetime,
                'end' => $end_datetime,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => array(
                    'booking_id' => $booking->id,
                    'client_name' => $booking->client_name,
                    'client_email' => $booking->client_email,
                    'client_phone' => $booking->client_phone,
                    'service_name' => $booking->service_name,
                    'staff_name' => $booking->staff_name,
                    'status' => $booking->status,
                    'total_amount' => $booking->total_amount
                )
            );
        }
        
        return $events;
    }
    
    /**
     * Get color for booking status.
     *
     * @param string $status The booking status.
     * @return string Hex color code.
     */
    public static function get_status_color($status) {
        $colors = array(
            'pending' => '#ffc107',    // Yellow
            'confirmed' => '#28a745',  // Green
            'completed' => '#007bff',  // Blue
            'cancelled' => '#dc3545',  // Red
            'no_show' => '#6c757d'     // Gray
        );
        
        return isset($colors[$status]) ? $colors[$status] : '#6c757d';
    }
    
    /**
     * Get business hours for a specific day.
     *
     * @param string $day_of_week Day of week (monday, tuesday, etc.).
     * @return array|null Business hours or null if closed.
     */
    public static function get_business_hours($day_of_week) {
        // Business hours based on UR Beautiful salon trading hours
        // Location: 2N Circular Road, West End, Kimberley, South Africa
        $business_hours = array(
            'sunday' => null, // Closed
            'monday' => null, // Closed
            'tuesday' => array('start' => '09:00:00', 'end' => '18:00:00'), // 9am - 6pm
            'wednesday' => array('start' => '09:00:00', 'end' => '18:00:00'), // 9am - 6pm
            'thursday' => array('start' => '09:00:00', 'end' => '18:00:00'), // 9am - 6pm
            'friday' => array('start' => '09:00:00', 'end' => '15:00:00'), // 9am - 3pm
            'saturday' => array('start' => '09:00:00', 'end' => '15:00:00') // 9am - 3pm
        );
        
        return isset($business_hours[$day_of_week]) ? $business_hours[$day_of_week] : null;
    }
    
    /**
     * Calculate end time based on start time and duration.
     *
     * @param string $start_time Start time (H:i:s format).
     * @param int $duration Duration in minutes.
     * @return string End time (H:i:s format).
     */
    public static function calculate_end_time($start_time, $duration) {
        $start_timestamp = strtotime($start_time);
        $end_timestamp = $start_timestamp + ($duration * 60);
        
        return date('H:i:s', $end_timestamp);
    }
    
    /**
     * Get timezone for the salon.
     *
     * @return string Timezone string.
     */
    public static function get_salon_timezone() {
        return get_option('timezone_string', 'UTC');
    }
    
    /**
     * Convert time to salon timezone.
     *
     * @param string $datetime DateTime string.
     * @param string $from_timezone Source timezone.
     * @return string Converted datetime.
     */
    public static function convert_to_salon_timezone($datetime, $from_timezone = 'UTC') {
        $salon_timezone = self::get_salon_timezone();
        
        $dt = new DateTime($datetime, new DateTimeZone($from_timezone));
        $dt->setTimezone(new DateTimeZone($salon_timezone));
        
        return $dt->format('Y-m-d H:i:s');
    }
    
    /**
     * Get next available slot for a service and staff.
     *
     * @param int $service_id Service ID.
     * @param int $staff_id Staff ID.
     * @return array|null Next available slot or null if none found.
     */
    public static function get_next_available_slot($service_id, $staff_id) {
        $staff_manager = new Salon_Booking_Staff_Manager();
        $service_manager = new Salon_Booking_Service_Manager();
        
        $service = $service_manager->get_service($service_id);
        if (!$service) {
            return null;
        }
        
        $available_dates = self::get_available_dates();
        
        foreach ($available_dates as $date) {
            $time_slots = $staff_manager->get_available_time_slots($staff_id, $date, $service->duration);
            
            if (!empty($time_slots)) {
                return array(
                    'date' => $date,
                    'time' => $time_slots[0],
                    'formatted_date' => self::format_date($date),
                    'formatted_time' => self::format_time($time_slots[0])
                );
            }
        }
        
        return null;
    }
}