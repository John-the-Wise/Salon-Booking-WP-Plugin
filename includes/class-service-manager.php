<?php

/**
 * The service manager class.
 *
 * This class handles all service-related operations including creation,
 * updating, deletion, and retrieval of services.
 *
 * @package SalonBooking
 * @subpackage SalonBooking/includes
 */

class Salon_Booking_Service_Manager {

    /**
     * Get all services.
     *
     * @param bool $active_only Whether to return only active services.
     * @return array Array of service objects.
     */
    public function get_services($active_only = false) {
        global $wpdb;
        
        $sql = "SELECT * FROM " . SALON_BOOKING_TABLE_SERVICES;
        
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY category ASC, name ASC";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get services grouped by category.
     *
     * @param bool $active_only Whether to return only active services.
     * @return array Array of services grouped by category.
     */
    public function get_services_by_category($active_only = false) {
        $services = $this->get_services($active_only);
        $grouped = array();
        
        foreach ($services as $service) {
            $category = !empty($service->category) ? $service->category : 'Other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = array();
            }
            $grouped[$category][] = $service;
        }
        
        return $grouped;
    }
    
    /**
     * Get a single service by ID.
     *
     * @param int $service_id The service ID.
     * @return object|null The service object or null if not found.
     */
    public function get_service($service_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . SALON_BOOKING_TABLE_SERVICES . " WHERE id = %d",
            $service_id
        ));
    }
    
    /**
     * Create a new service.
     *
     * @param array $service_data The service data.
     * @return int|false The service ID on success, false on failure.
     */
    public function create_service($service_data) {
        global $wpdb;
        
        $defaults = array(
            'is_active' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $service_data = wp_parse_args($service_data, $defaults);
        
        $result = $wpdb->insert(
            SALON_BOOKING_TABLE_SERVICES,
            $service_data,
            array(
                '%s', // name
                '%s', // description
                '%d', // duration
                '%f', // price
                '%s', // category
                '%d', // is_active
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
     * Update a service.
     *
     * @param int $service_id The service ID.
     * @param array $service_data The service data to update.
     * @return bool True on success, false on failure.
     */
    public function update_service($service_id, $service_data) {
        global $wpdb;
        
        $service_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            SALON_BOOKING_TABLE_SERVICES,
            $service_data,
            array('id' => $service_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a service.
     *
     * @param int $service_id The service ID.
     * @return bool True on success, false on failure.
     */
    public function delete_service($service_id) {
        global $wpdb;
        
        // Check if service has any bookings
        $booking_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE service_id = %d",
            $service_id
        ));
        
        if ($booking_count > 0) {
            return false; // Cannot delete service with existing bookings
        }
        
        $result = $wpdb->delete(
            SALON_BOOKING_TABLE_SERVICES,
            array('id' => $service_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get all service categories.
     *
     * @return array Array of unique categories.
     */
    public function get_categories() {
        global $wpdb;
        
        $categories = $wpdb->get_col(
            "SELECT DISTINCT category FROM " . SALON_BOOKING_TABLE_SERVICES . "
             WHERE category IS NOT NULL AND category != ''
             ORDER BY category ASC"
        );
        
        return $categories;
    }
    
    /**
     * Get service statistics.
     *
     * @param int $service_id Optional service ID to get stats for specific service.
     * @return array Statistics array.
     */
    public function get_service_stats($service_id = null) {
        global $wpdb;
        
        $stats = array(
            'total_services' => 0,
            'active_services' => 0,
            'total_bookings' => 0,
            'upcoming_bookings' => 0,
            'revenue' => 0
        );
        
        if ($service_id) {
            // Stats for specific service
            $stats['total_bookings'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE service_id = %d",
                $service_id
            ));
            
            $stats['upcoming_bookings'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_BOOKINGS . "
                 WHERE service_id = %d 
                 AND CONCAT(booking_date, ' ', booking_time) > %s
                 AND status = 'confirmed'",
                $service_id,
                current_time('mysql')
            ));
            
            $stats['revenue'] = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_amount) FROM " . SALON_BOOKING_TABLE_BOOKINGS . "
                 WHERE service_id = %d AND status = 'completed'",
                $service_id
            ));
        } else {
            // General service stats
            $stats['total_services'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_SERVICES
            );
            
            $stats['active_services'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . SALON_BOOKING_TABLE_SERVICES . " WHERE is_active = 1"
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
            
            $stats['revenue'] = (float) $wpdb->get_var(
                "SELECT SUM(total_amount) FROM " . SALON_BOOKING_TABLE_BOOKINGS . " WHERE status = 'completed'"
            );
        }
        
        return $stats;
    }
    
    /**
     * Get popular services based on booking count.
     *
     * @param int $limit Number of services to return.
     * @return array Array of service objects with booking counts.
     */
    public function get_popular_services($limit = 5) {
        global $wpdb;
        
        $sql = "SELECT s.*, COUNT(b.id) as booking_count
                FROM " . SALON_BOOKING_TABLE_SERVICES . " s
                LEFT JOIN " . SALON_BOOKING_TABLE_BOOKINGS . " b ON s.id = b.service_id
                WHERE s.is_active = 1
                GROUP BY s.id
                ORDER BY booking_count DESC, s.name ASC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Search services by name or description.
     *
     * @param string $search_term The search term.
     * @param bool $active_only Whether to search only active services.
     * @return array Array of matching service objects.
     */
    public function search_services($search_term, $active_only = true) {
        global $wpdb;
        
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        $sql = "SELECT * FROM " . SALON_BOOKING_TABLE_SERVICES . "
                WHERE (name LIKE %s OR description LIKE %s)";
        
        $params = array($search_term, $search_term);
        
        if ($active_only) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY name ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get services within a price range.
     *
     * @param float $min_price Minimum price.
     * @param float $max_price Maximum price.
     * @param bool $active_only Whether to return only active services.
     * @return array Array of service objects.
     */
    public function get_services_by_price_range($min_price, $max_price, $active_only = true) {
        global $wpdb;
        
        $sql = "SELECT * FROM " . SALON_BOOKING_TABLE_SERVICES . "
                WHERE price BETWEEN %f AND %f";
        
        $params = array($min_price, $max_price);
        
        if ($active_only) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY price ASC, name ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get services by duration range.
     *
     * @param int $min_duration Minimum duration in minutes.
     * @param int $max_duration Maximum duration in minutes.
     * @param bool $active_only Whether to return only active services.
     * @return array Array of service objects.
     */
    public function get_services_by_duration($min_duration, $max_duration, $active_only = true) {
        global $wpdb;
        
        $sql = "SELECT * FROM " . SALON_BOOKING_TABLE_SERVICES . "
                WHERE duration BETWEEN %d AND %d";
        
        $params = array($min_duration, $max_duration);
        
        if ($active_only) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY duration ASC, name ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
}