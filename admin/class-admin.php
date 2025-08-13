<?php

/**
 * The admin-specific functionality of the plugin.
 */
class Salon_Booking_Admin {

    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            SALON_BOOKING_PLUGIN_URL . 'admin/css/salon-booking-admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // FullCalendar CSS (local fallback to avoid CORS issues)
        wp_enqueue_style(
            'fullcalendar',
            SALON_BOOKING_PLUGIN_URL . 'public/css/vendor/fullcalendar.min.css',
            array(),
            $this->version
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            SALON_BOOKING_PLUGIN_URL . 'admin/js/salon-booking-admin.js',
            array('jquery'),
            $this->version,
            false
        );
        
        // FullCalendar JS
        wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
            array(),
            '6.1.10',
            false
        );
        
        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'salon_booking_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('salon_booking_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'salon-booking-plugin'),
                'error_occurred' => __('An error occurred. Please try again.', 'salon-booking-plugin'),
                'success_saved' => __('Item saved successfully.', 'salon-booking-plugin'),
                'success_deleted' => __('Item deleted successfully.', 'salon-booking-plugin')
            )
        ));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Salon Booking', 'salon-booking-plugin'),
            __('Salon Booking', 'salon-booking-plugin'),
            'manage_options',
            'salon-booking',
            array($this, 'display_admin_dashboard'),
            'dashicons-calendar-alt',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'salon-booking',
            __('Dashboard', 'salon-booking-plugin'),
            __('Dashboard', 'salon-booking-plugin'),
            'manage_options',
            'salon-booking',
            array($this, 'display_admin_dashboard')
        );

        // Bookings submenu
        add_submenu_page(
            'salon-booking',
            __('All Bookings', 'salon-booking-plugin'),
            __('All Bookings', 'salon-booking-plugin'),
            'manage_options',
            'salon-booking-bookings',
            array($this, 'display_bookings_page')
        );

        // Calendar submenu
        add_submenu_page(
            'salon-booking',
            __('Calendar', 'salon-booking-plugin'),
            __('Calendar', 'salon-booking-plugin'),
            'manage_options',
            'salon-booking-calendar',
            array($this, 'display_calendar_page')
        );

        // Staff submenu
        add_submenu_page(
            'salon-booking',
            __('Staff Management', 'salon-booking-plugin'),
            __('Staff', 'salon-booking-plugin'),
            'manage_options',
            'salon-booking-staff',
            array($this, 'display_staff_page')
        );

        // Services submenu
        add_submenu_page(
            'salon-booking',
            __('Service Management', 'salon-booking-plugin'),
            __('Services', 'salon-booking-plugin'),
            'manage_options',
            'salon-booking-services',
            array($this, 'display_services_page')
        );

        // Settings submenu
        add_submenu_page(
            'salon-booking',
            __('Settings', 'salon-booking-plugin'),
            __('Settings', 'salon-booking-plugin'),
            'manage_options',
            'salon-booking-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('salon_booking_settings', 'salon_booking_stripe_publishable_key');
        register_setting('salon_booking_settings', 'salon_booking_stripe_secret_key');
        register_setting('salon_booking_settings', 'salon_booking_stripe_mode');
        register_setting('salon_booking_settings', 'salon_booking_currency');
        register_setting('salon_booking_settings', 'salon_booking_currency_symbol');
        register_setting('salon_booking_settings', 'salon_booking_admin_email');
        register_setting('salon_booking_settings', 'salon_booking_booking_window');
        register_setting('salon_booking_settings', 'salon_booking_min_booking_time');
        register_setting('salon_booking_settings', 'salon_booking_slot_duration');
        register_setting('salon_booking_settings', 'salon_booking_require_payment');
        register_setting('salon_booking_settings', 'salon_booking_send_notifications');
    }

    /**
     * Display admin dashboard
     */
    public function display_admin_dashboard() {
        include_once SALON_BOOKING_PLUGIN_DIR . 'admin/partials/admin-dashboard.php';
    }

    /**
     * Display bookings page
     */
    public function display_bookings_page() {
        include_once SALON_BOOKING_PLUGIN_DIR . 'admin/partials/admin-bookings.php';
    }

    /**
     * Display calendar page
     */
    public function display_calendar_page() {
        include_once SALON_BOOKING_PLUGIN_DIR . 'admin/partials/admin-calendar.php';
    }

    /**
     * Display staff management page
     */
    public function display_staff_page() {
        include_once SALON_BOOKING_PLUGIN_DIR . 'admin/partials/admin-staff.php';
    }

    /**
     * Display services management page
     */
    public function display_services_page() {
        include_once SALON_BOOKING_PLUGIN_DIR . 'admin/partials/admin-services.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        include_once SALON_BOOKING_PLUGIN_DIR . 'admin/partials/admin-settings.php';
    }

    /**
     * AJAX handler for getting bookings
     */
    public function ajax_get_bookings() {
        check_ajax_referer('salon_booking_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'salon-booking-plugin'));
        }

        $start = sanitize_text_field($_POST['start'] ?? '');
        $end = sanitize_text_field($_POST['end'] ?? '');
        $staff_id = intval($_POST['staff_id'] ?? 0);

        $filters = [];
        if ($start) $filters['date_from'] = $start;
        if ($end) $filters['date_to'] = $end;
        if ($staff_id) $filters['staff_id'] = $staff_id;

        $bookings = Salon_Booking_Database::get_bookings($filters);
        
        $events = [];
        foreach ($bookings as $booking) {
            $events[] = [
                'id' => $booking->id,
                'title' => $booking->client_name . ' - ' . $booking->service_name,
                'start' => $booking->booking_date . 'T' . $booking->booking_time,
                'end' => date('Y-m-d\TH:i:s', strtotime($booking->booking_date . ' ' . $booking->booking_time) + ($booking->duration * 60)),
                'backgroundColor' => $this->get_status_color($booking->status),
                'borderColor' => $this->get_status_color($booking->status),
                'extendedProps' => [
                    'client_email' => $booking->client_email,
                    'client_phone' => $booking->client_phone,
                    'staff_name' => $booking->staff_name,
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status,
                    'total_amount' => $booking->total_amount,
                    'notes' => $booking->notes
                ]
            ];
        }

        wp_send_json_success($events);
    }

    /**
     * AJAX handler for updating booking status
     */
    public function ajax_update_booking_status() {
        check_ajax_referer('salon_booking_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'salon-booking-plugin'));
        }

        $booking_id = intval($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        $payment_status = sanitize_text_field($_POST['payment_status'] ?? '');

        $result = Salon_Booking_Database::update_booking_status($booking_id, $status, $payment_status ?: null);
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Booking updated successfully', 'salon-booking-plugin')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update booking', 'salon-booking-plugin')]);
        }
    }

    /**
     * AJAX handler for saving staff
     */
    public function ajax_save_staff() {
        check_ajax_referer('salon_booking_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'salon-booking-plugin'));
        }

        $staff_id = intval($_POST['staff_id'] ?? 0);
        $staff_data = [
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'specialties' => array_map('sanitize_text_field', $_POST['specialties'] ?? []),
            'working_hours' => $_POST['working_hours'] ?? [],
            'is_active' => isset($_POST['is_active']),
            'is_owner' => isset($_POST['is_owner'])
        ];

        $result = Salon_Booking_Database::save_staff($staff_data, $staff_id ?: null);
        
        if ($result) {
            wp_send_json_success(['message' => __('Staff member saved successfully', 'salon-booking-plugin'), 'id' => $result]);
        } else {
            wp_send_json_error(['message' => __('Failed to save staff member', 'salon-booking-plugin')]);
        }
    }

    /**
     * AJAX handler for deleting staff
     */
    public function ajax_delete_staff() {
        check_ajax_referer('salon_booking_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'salon-booking-plugin'));
        }

        $staff_id = intval($_POST['staff_id']);
        $result = Salon_Booking_Database::delete_staff($staff_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Staff member deleted successfully', 'salon-booking-plugin')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete staff member', 'salon-booking-plugin')]);
        }
    }

    /**
     * AJAX handler for saving service
     */
    public function ajax_save_service() {
        check_ajax_referer('salon_booking_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'salon-booking-plugin'));
        }

        $service_id = intval($_POST['service_id'] ?? 0);
        $service_data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'duration' => intval($_POST['duration']),
            'price' => floatval($_POST['price']),
            'upfront_fee' => floatval($_POST['upfront_fee']),
            'category' => sanitize_text_field($_POST['category']),
            'is_active' => isset($_POST['is_active'])
        ];

        $result = Salon_Booking_Database::save_service($service_data, $service_id ?: null);
        
        if ($result) {
            wp_send_json_success(['message' => __('Service saved successfully', 'salon-booking-plugin'), 'id' => $result]);
        } else {
            wp_send_json_error(['message' => __('Failed to save service', 'salon-booking-plugin')]);
        }
    }

    /**
     * AJAX handler for deleting service
     */
    public function ajax_delete_service() {
        check_ajax_referer('salon_booking_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'salon-booking-plugin'));
        }

        $service_id = intval($_POST['service_id']);
        $result = Salon_Booking_Database::delete_service($service_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Service deleted successfully', 'salon-booking-plugin')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete service', 'salon-booking-plugin')]);
        }
    }

    /**
     * Get color for booking status
     */
    private function get_status_color($status) {
        $colors = [
            'pending' => '#ffc107',
            'confirmed' => '#28a745',
            'completed' => '#6c757d',
            'cancelled' => '#dc3545'
        ];
        
        return $colors[$status] ?? '#6c757d';
    }
}