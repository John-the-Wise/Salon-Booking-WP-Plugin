<?php

/**
 * Database operations class
 *
 * This class handles all database operations for the salon booking plugin.
 */
class Salon_Booking_Database {

    /**
     * Get table name with prefix
     */
    private static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . SALON_BOOKING_TABLE_PREFIX . $table;
    }

    /**
     * Get all services
     */
    public static function get_services($active_only = true) {
        global $wpdb;
        $table = self::get_table_name('services');
        
        $where = $active_only ? 'WHERE is_active = 1' : '';
        $sql = "SELECT * FROM $table $where ORDER BY category, name";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get service by ID
     */
    public static function get_service($id) {
        global $wpdb;
        $table = self::get_table_name('services');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * Create or update service
     */
    public static function save_service($data, $id = null) {
        global $wpdb;
        $table = self::get_table_name('services');
        
        $service_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'duration' => intval($data['duration']),
            'price' => floatval($data['price']),
            'upfront_fee' => floatval($data['upfront_fee']),
            'category' => sanitize_text_field($data['category']),
            'is_active' => isset($data['is_active']) ? 1 : 0
        ];
        
        if ($id) {
            $result = $wpdb->update(
                $table,
                $service_data,
                ['id' => $id],
                ['%s', '%s', '%d', '%f', '%f', '%s', '%d'],
                ['%d']
            );
            return $result !== false ? $id : false;
        } else {
            $result = $wpdb->insert(
                $table,
                $service_data,
                ['%s', '%s', '%d', '%f', '%f', '%s', '%d']
            );
            return $result ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete service
     */
    public static function delete_service($id) {
        global $wpdb;
        $table = self::get_table_name('services');
        
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }

    /**
     * Get all staff
     */
    public static function get_staff($active_only = true) {
        global $wpdb;
        $table = self::get_table_name('staff');
        
        $where = $active_only ? 'WHERE is_active = 1' : '';
        $sql = "SELECT * FROM $table $where ORDER BY is_owner DESC, name";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get staff member by ID
     */
    public static function get_staff_member($id) {
        global $wpdb;
        $table = self::get_table_name('staff');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * Create or update staff member
     */
    public static function save_staff($data, $id = null) {
        global $wpdb;
        $table = self::get_table_name('staff');
        
        $staff_data = [
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone']),
            'specialties' => json_encode($data['specialties']),
            'working_hours' => json_encode($data['working_hours']),
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'is_owner' => isset($data['is_owner']) ? 1 : 0
        ];
        
        if ($id) {
            $result = $wpdb->update(
                $table,
                $staff_data,
                ['id' => $id],
                ['%s', '%s', '%s', '%s', '%s', '%d', '%d'],
                ['%d']
            );
            return $result !== false ? $id : false;
        } else {
            $result = $wpdb->insert(
                $table,
                $staff_data,
                ['%s', '%s', '%s', '%s', '%s', '%d', '%d']
            );
            return $result ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete staff member
     */
    public static function delete_staff($id) {
        global $wpdb;
        $table = self::get_table_name('staff');
        
        // Don't allow deletion of owner
        $staff = self::get_staff_member($id);
        if ($staff && $staff->is_owner) {
            return false;
        }
        
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }

    /**
     * Create booking
     */
    public static function create_booking($data) {
        global $wpdb;
        $table = self::get_table_name('bookings');
        
        $booking_data = [
            'client_name' => sanitize_text_field($data['client_name']),
            'client_email' => sanitize_email($data['client_email']),
            'client_phone' => sanitize_text_field($data['client_phone']),
            'service_id' => intval($data['service_id']),
            'staff_id' => intval($data['staff_id']),
            'booking_date' => sanitize_text_field($data['booking_date']),
            'booking_time' => sanitize_text_field($data['booking_time']),
            'duration' => intval($data['duration']),
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => floatval($data['total_amount']),
            'upfront_fee' => floatval($data['upfront_fee']),
            'notes' => sanitize_textarea_field($data['notes'] ?? '')
        ];
        
        $result = $wpdb->insert(
            $table,
            $booking_data,
            ['%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get booking by ID
     */
    public static function get_booking($id) {
        global $wpdb;
        $bookings_table = self::get_table_name('bookings');
        $services_table = self::get_table_name('services');
        $staff_table = self::get_table_name('staff');
        
        $sql = "SELECT b.*, s.name as service_name, st.name as staff_name 
                FROM $bookings_table b
                LEFT JOIN $services_table s ON b.service_id = s.id
                LEFT JOIN $staff_table st ON b.staff_id = st.id
                WHERE b.id = %d";
        
        return $wpdb->get_row($wpdb->prepare($sql, $id));
    }

    /**
     * Get bookings with filters
     */
    public static function get_bookings($filters = []) {
        global $wpdb;
        $bookings_table = self::get_table_name('bookings');
        $services_table = self::get_table_name('services');
        $staff_table = self::get_table_name('staff');
        
        $where_conditions = [];
        $where_values = [];
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'b.booking_date >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'b.booking_date <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        if (!empty($filters['staff_id'])) {
            $where_conditions[] = 'b.staff_id = %d';
            $where_values[] = $filters['staff_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'b.status = %s';
            $where_values[] = $filters['status'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT b.*, s.name as service_name, st.name as staff_name 
                FROM $bookings_table b
                LEFT JOIN $services_table s ON b.service_id = s.id
                LEFT JOIN $staff_table st ON b.staff_id = st.id
                $where_clause
                ORDER BY b.booking_date DESC, b.booking_time DESC";
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_results($sql);
        }
    }

    /**
     * Update booking status
     */
    public static function update_booking_status($id, $status, $payment_status = null) {
        global $wpdb;
        $table = self::get_table_name('bookings');
        
        $update_data = ['status' => $status];
        $format = ['%s'];
        
        if ($payment_status !== null) {
            $update_data['payment_status'] = $payment_status;
            $format[] = '%s';
        }
        
        return $wpdb->update(
            $table,
            $update_data,
            ['id' => $id],
            $format,
            ['%d']
        );
    }

    /**
     * Update payment information
     */
    public static function update_booking_payment($id, $payment_intent_id, $payment_status) {
        global $wpdb;
        $table = self::get_table_name('bookings');
        
        return $wpdb->update(
            $table,
            [
                'payment_intent_id' => $payment_intent_id,
                'payment_status' => $payment_status
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Check staff availability
     */
    public static function check_staff_availability($staff_id, $date, $start_time, $duration) {
        global $wpdb;
        $bookings_table = self::get_table_name('bookings');
        
        $end_time = date('H:i:s', strtotime($start_time) + ($duration * 60));
        
        $sql = "SELECT COUNT(*) FROM $bookings_table 
                WHERE staff_id = %d 
                AND booking_date = %s 
                AND status NOT IN ('cancelled') 
                AND (
                    (booking_time <= %s AND DATE_ADD(CONCAT(booking_date, ' ', booking_time), INTERVAL duration MINUTE) > %s)
                    OR (booking_time < %s AND DATE_ADD(CONCAT(booking_date, ' ', booking_time), INTERVAL duration MINUTE) >= %s)
                )";
        
        $count = $wpdb->get_var($wpdb->prepare(
            $sql,
            $staff_id,
            $date,
            $start_time,
            $date . ' ' . $start_time,
            $date . ' ' . $end_time,
            $date . ' ' . $end_time
        ));
        
        return $count == 0;
    }

    /**
     * Get available time slots for a staff member on a specific date
     */
    public static function get_available_slots($staff_id, $date, $duration) {
        $staff = self::get_staff_member($staff_id);
        if (!$staff || !$staff->is_active) {
            return [];
        }
        
        $working_hours = json_decode($staff->working_hours, true);
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        if (!isset($working_hours[$day_of_week])) {
            return [];
        }
        
        $start_time = $working_hours[$day_of_week]['start'];
        $end_time = $working_hours[$day_of_week]['end'];
        
        $slots = [];
        $slot_duration = get_option('salon_booking_slot_duration', 30); // minutes
        
        $current_time = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        
        while ($current_time + ($duration * 60) <= $end_timestamp) {
            $slot_time = date('H:i:s', $current_time);
            
            if (self::check_staff_availability($staff_id, $date, $slot_time, $duration)) {
                $slots[] = [
                    'time' => $slot_time,
                    'formatted_time' => date('H:i', $current_time)
                ];
            }
            
            $current_time += ($slot_duration * 60);
        }
        
        return $slots;
    }

    /**
     * Get email template
     */
    public static function get_email_template($template_name) {
        global $wpdb;
        $table = self::get_table_name('email_templates');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE template_name = %s AND is_active = 1",
            $template_name
        ));
    }
    
    /**
     * Alias methods for backward compatibility
     */
    public static function get_staff_members($active_only = true) {
        return self::get_staff($active_only);
    }
    
    public static function save_staff_member($data, $id = null) {
        return self::save_staff($data, $id);
    }
    
    public static function delete_staff_member($id) {
        return self::delete_staff($id);
    }
    
    public static function save_booking($data, $id = null) {
        if ($id) {
            return self::update_booking($data, $id);
        } else {
            return self::create_booking($data);
        }
    }
    
    public static function delete_booking($id) {
        global $wpdb;
        $table = self::get_table_name('bookings');
        
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }
    
    public static function update_booking($data, $id) {
        global $wpdb;
        $table = self::get_table_name('bookings');
        
        $booking_data = [
            'client_name' => sanitize_text_field($data['client_name']),
            'client_email' => sanitize_email($data['client_email']),
            'client_phone' => sanitize_text_field($data['client_phone']),
            'service_id' => intval($data['service_id']),
            'staff_id' => intval($data['staff_id']),
            'booking_date' => sanitize_text_field($data['booking_date']),
            'booking_time' => sanitize_text_field($data['booking_time']),
            'duration' => intval($data['duration']),
            'total_amount' => floatval($data['total_amount']),
            'upfront_fee' => floatval($data['upfront_fee']),
            'notes' => sanitize_textarea_field($data['notes'] ?? '')
        ];
        
        if (isset($data['status'])) {
            $booking_data['status'] = sanitize_text_field($data['status']);
        }
        
        if (isset($data['payment_status'])) {
            $booking_data['payment_status'] = sanitize_text_field($data['payment_status']);
        }
        
        $result = $wpdb->update(
            $table,
            $booking_data,
            ['id' => $id],
            ['%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%f', '%f', '%s', '%s', '%s'],
            ['%d']
        );
        
        return $result !== false ? $id : false;
    }
    
    /**
     * Get bookings count with filters
     */
    public static function get_bookings_count($date_from = null, $date_to = null, $status = null, $staff_id = null, $service_id = null) {
        global $wpdb;
        $table = self::get_table_name('bookings');
        
        $where_conditions = [];
        $where_values = [];
        
        if ($date_from !== null) {
            $where_conditions[] = 'booking_date >= %s';
            $where_values[] = $date_from;
        }
        
        if ($date_to !== null) {
            $where_conditions[] = 'booking_date <= %s';
            $where_values[] = $date_to;
        }
        
        if ($status !== null) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $status;
        }
        
        if ($staff_id !== null) {
            $where_conditions[] = 'staff_id = %d';
            $where_values[] = $staff_id;
        }
        
        if ($service_id !== null) {
            $where_conditions[] = 'service_id = %d';
            $where_values[] = $service_id;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT COUNT(*) FROM $table $where_clause";
        
        if (!empty($where_values)) {
            return $wpdb->get_var($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_var($sql);
        }
    }
    
    /**
     * Get revenue statistics
     */
    public static function get_revenue_stats() {
        global $wpdb;
        $table = self::get_table_name('bookings');
        
        $today = current_time('Y-m-d');
        $this_week_start = date('Y-m-d', strtotime('monday this week'));
        $this_month_start = date('Y-m-01');
        
        $stats = [];
        
        // Today's revenue
        $stats['today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(total_amount) FROM $table WHERE booking_date = %s AND payment_status = 'paid'",
            $today
        )) ?: 0;
        
        // This week's revenue
        $stats['week'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(total_amount) FROM $table WHERE booking_date >= %s AND booking_date <= %s AND payment_status = 'paid'",
            $this_week_start,
            $today
        )) ?: 0;
        
        // This month's revenue
        $stats['month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(total_amount) FROM $table WHERE booking_date >= %s AND booking_date <= %s AND payment_status = 'paid'",
            $this_month_start,
            $today
        )) ?: 0;
        
        // Total revenue
        $stats['total'] = $wpdb->get_var(
            "SELECT SUM(total_amount) FROM $table WHERE payment_status = 'paid'"
        ) ?: 0;
        
        return $stats;
    }
    
    /**
     * Get staff availability
     */
    public static function get_staff_availability($staff_id) {
        $staff = self::get_staff_member($staff_id);
        if (!$staff) {
            return null;
        }
        
        return json_decode($staff->working_hours, true);
    }
    
    /**
     * Update payment status
     */
    public static function update_booking_payment_status($booking_id, $status) {
        global $wpdb;
        $table = self::get_table_name('bookings');
        
        return $wpdb->update(
            $table,
            ['payment_status' => $status],
            ['id' => $booking_id],
            ['%s'],
            ['%d']
        );
    }
}