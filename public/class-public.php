<?php

/**
 * The public-facing functionality of the plugin.
 */
class Salon_Booking_Public {

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
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            SALON_BOOKING_PLUGIN_URL . 'public/css/salon-booking-public.css',
            array(),
            $this->version,
            'all'
        );
        
        // FullCalendar CSS for frontend calendar (local fallback to avoid CORS issues)
        wp_enqueue_style(
            'fullcalendar-public',
            SALON_BOOKING_PLUGIN_URL . 'public/css/vendor/fullcalendar.min.css',
            array(),
            $this->version
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        // Always enqueue the main script and localize it
        wp_enqueue_script(
            $this->plugin_name,
            SALON_BOOKING_PLUGIN_URL . 'public/js/salon-booking-public.js',
            array('jquery'),
            $this->version,
            true  // Load in footer to ensure DOM is ready
        );
        
        // Always localize script for AJAX to ensure salon_booking_ajax is available
        wp_localize_script($this->plugin_name, 'salon_booking_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('salon_booking_nonce'),
            'stripe_publishable_key' => get_option('salon_booking_stripe_publishable_key'),
            'currency' => get_option('salon_booking_currency', 'ZAR'),
            'currency_symbol' => get_option('salon_booking_currency_symbol', 'R'),
            'booking_page_url' => $this->get_booking_page_url(),
            'strings' => array(
                'select_service' => __('Please select a service', 'salon-booking-plugin'),
                'select_date' => __('Please select a date', 'salon-booking-plugin'),
                'select_time' => __('Please select a time', 'salon-booking-plugin'),
                'fill_details' => __('Please fill in all required details', 'salon-booking-plugin'),
                'payment_error' => __('Payment failed. Please try again.', 'salon-booking-plugin'),
                'booking_success' => __('Booking confirmed! You will receive a confirmation email shortly.', 'salon-booking-plugin'),
                'loading' => __('Loading...', 'salon-booking-plugin'),
                'processing_payment' => __('Processing payment...', 'salon-booking-plugin')
            )
        ));
        
        // Disable WordPress script modules that cause conflicts
        if ($this->is_booking_page()) {
            // Remove problematic script modules
            add_action('wp_print_scripts', function() {
                wp_dequeue_script_module('@wordpress/block-library/navigation/view');
                wp_dequeue_script_module('@wordpress/interactivity');
            }, 100);
        }
        
        // Only enqueue additional scripts on booking-related pages
        if ($this->is_booking_page()) {
            // FullCalendar JS
            wp_enqueue_script(
                'fullcalendar-public',
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
                array(),
                '6.1.10',
                false
            );
            
            // Stripe JS
            wp_enqueue_script(
                'stripe-js',
                'https://js.stripe.com/v3/',
                array(),
                '3.0',
                false
            );
            
            // Add CSP headers for Stripe compatibility
            add_action('wp_head', array($this, 'add_stripe_csp_headers'), 1);
        }
    }

    /**
     * Initialize public functionality
     */
    public function init() {
        // Add any initialization code here
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('salon_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('salon_services', array($this, 'services_shortcode'));
        add_shortcode('salon_staff', array($this, 'staff_shortcode'));
    }

    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'service_id' => '',
            'staff_id' => '',
            'show_services' => 'true',
            'show_staff' => 'true'
        ), $atts, 'salon_booking_form');

        ob_start();
        include SALON_BOOKING_PLUGIN_DIR . 'public/partials/booking-form.php';
        return ob_get_clean();
    }

    /**
     * Services list shortcode
     */
    public function services_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => -1,
            'show_price' => 'true',
            'show_duration' => 'true'
        ), $atts, 'salon_services');

        ob_start();
        include SALON_BOOKING_PLUGIN_DIR . 'public/partials/services-list.php';
        return ob_get_clean();
    }

    /**
     * Staff list shortcode
     */
    public function staff_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_specialties' => 'true',
            'show_contact' => 'false'
        ), $atts, 'salon_staff');

        ob_start();
        include SALON_BOOKING_PLUGIN_DIR . 'public/partials/staff-list.php';
        return ob_get_clean();
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('salon-booking/v1', '/services', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_services'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('salon-booking/v1', '/staff', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_staff'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('salon-booking/v1', '/availability', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_availability'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * REST API: Get services
     */
    public function rest_get_services($request) {
        $services = Salon_Booking_Database::get_services(true);
        return rest_ensure_response($services);
    }

    /**
     * REST API: Get staff
     */
    public function rest_get_staff($request) {
        $staff = Salon_Booking_Database::get_staff(true);
        return rest_ensure_response($staff);
    }

    /**
     * REST API: Get availability
     */
    public function rest_get_availability($request) {
        $staff_id = $request->get_param('staff_id');
        $date = $request->get_param('date');
        $duration = $request->get_param('duration');

        if (!$staff_id || !$date || !$duration) {
            return new WP_Error('missing_params', 'Missing required parameters', array('status' => 400));
        }

        $slots = Salon_Booking_Database::get_available_slots($staff_id, $date, $duration);
        return rest_ensure_response($slots);
    }

    /**
     * AJAX handler for getting services
     */
    public function ajax_get_services() {
        check_ajax_referer('salon_booking_nonce', 'nonce');

        $services = Salon_Booking_Database::get_services(true);
        wp_send_json_success($services);
    }

    /**
     * AJAX handler for getting staff
     */
    public function ajax_get_staff() {
        check_ajax_referer('salon_booking_nonce', 'nonce');

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : null;
        $staff = Salon_Booking_Database::get_staff(true, $service_id);
        wp_send_json_success($staff);
    }

    /**
     * AJAX handler for checking availability
     */
    public function ajax_check_availability() {
        check_ajax_referer('salon_booking_nonce', 'nonce');

        $staff_id = intval($_POST['staff_id']);
        $date = sanitize_text_field($_POST['date']);
        $duration = intval($_POST['duration']);

        if (!$staff_id || !$date || !$duration) {
            wp_send_json_error(['message' => __('Missing required parameters', 'salon-booking-plugin')]);
        }

        $slots = Salon_Booking_Database::get_available_slots($staff_id, $date, $duration);
        wp_send_json_success($slots);
    }

    /**
     * AJAX handler for creating booking
     */
    public function ajax_create_booking() {
        check_ajax_referer('salon_booking_nonce', 'nonce');

        // Validate required fields
        $required_fields = ['client_name', 'client_email', 'service_id', 'staff_id', 'booking_date', 'booking_time'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(['message' => sprintf(__('Field %s is required', 'salon-booking-plugin'), $field)]);
            }
        }

        // Get service details
        $service = Salon_Booking_Database::get_service(intval($_POST['service_id']));
        if (!$service) {
            wp_send_json_error(['message' => __('Invalid service selected', 'salon-booking-plugin')]);
        }

        // Check availability
        $staff_id = intval($_POST['staff_id']);
        $date = sanitize_text_field($_POST['booking_date']);
        $time = sanitize_text_field($_POST['booking_time']);
        
        if (!Salon_Booking_Database::check_staff_availability($staff_id, $date, $time, $service->duration)) {
            wp_send_json_error(['message' => __('Selected time slot is no longer available', 'salon-booking-plugin')]);
        }

        // Prepare booking data
        $booking_data = [
            'client_name' => sanitize_text_field($_POST['client_name']),
            'client_email' => sanitize_email($_POST['client_email']),
            'client_phone' => sanitize_text_field($_POST['client_phone'] ?? ''),
            'service_id' => $service->id,
            'staff_id' => $staff_id,
            'booking_date' => $date,
            'booking_time' => $time,
            'duration' => $service->duration,
            'total_amount' => $service->price,
            'upfront_fee' => $service->upfront_fee,
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        ];

        // Create booking
        $booking_id = Salon_Booking_Database::create_booking($booking_data);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => __('Failed to create booking', 'salon-booking-plugin')]);
        }

        wp_send_json_success([
            'booking_id' => $booking_id,
            'upfront_fee' => $service->upfront_fee,
            'message' => __('Booking created successfully', 'salon-booking-plugin')
        ]);
    }

    /**
     * AJAX handler for processing payment
     */
    public function ajax_process_payment() {
        check_ajax_referer('salon_booking_nonce', 'nonce');

        $booking_id = intval($_POST['booking_id']);
        $payment_method_id = sanitize_text_field($_POST['payment_method_id']);

        if (!$booking_id || !$payment_method_id) {
            wp_send_json_error(['message' => __('Missing payment information', 'salon-booking-plugin')]);
        }

        // Get booking details
        $booking = Salon_Booking_Database::get_booking($booking_id);
        if (!$booking) {
            wp_send_json_error(['message' => __('Booking not found', 'salon-booking-plugin')]);
        }

        // Process payment with Stripe
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-payment-handler.php';
        $payment_handler = new Salon_Booking_Payment_Handler();
        
        $payment_result = $payment_handler->process_payment(
            $booking->upfront_fee,
            $payment_method_id,
            $booking
        );

        if ($payment_result['success']) {
            // Update booking with payment info
            Salon_Booking_Database::update_booking_payment(
                $booking_id,
                $payment_result['payment_intent_id'],
                'paid'
            );
            
            // Update booking status
            Salon_Booking_Database::update_booking_status($booking_id, 'confirmed');
            
            // Send confirmation emails
            require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-email-notifications.php';
            $email_handler = new Salon_Booking_Email_Notifications();
            $email_handler->send_booking_confirmation($booking_id);
            
            wp_send_json_success([
                'message' => __('Payment successful! Booking confirmed.', 'salon-booking-plugin')
            ]);
        } else {
            wp_send_json_error([
                'message' => $payment_result['error'] ?? __('Payment failed', 'salon-booking-plugin')
            ]);
        }
    }
    
    /**
     * Add Content Security Policy headers for Stripe compatibility
     */
    public function add_stripe_csp_headers() {
        // Only add CSP headers on pages that use Stripe
        if (!$this->is_booking_page()) {
            return;
        }
        
        // Add CSP meta tag that allows Stripe domains and common antivirus domains
        // This approach extends rather than replaces existing CSP
        $stripe_domains = array(
            'https://js.stripe.com',
            'https://m.stripe.network', 
            'https://*.stripe.network',
            'https://api.stripe.com',
            'https://hooks.stripe.com'
        );
        
        $antivirus_domains = array(
            'https://*.kaspersky-labs.com',
            'https://*.avast.com',
            'https://*.avg.com',
            'https://*.norton.com',
            'https://*.mcafee.com'
        );
        
        $cdn_domains = array('https://cdn.jsdelivr.net', 'https://unpkg.com');
        $all_domains = array_merge($stripe_domains, $antivirus_domains, $cdn_domains);
        
        // Create a more permissive CSP for Stripe functionality and antivirus compatibility
        $csp_directives = array(
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' " . implode(' ', $all_domains) . " 'sha256-5DA+a07wxWmEka9IdoWjSPVHb17Cp5284/lJzfbl8KA=' 'sha256-/5Guo2nzv5n/w6ukZpOBZOtTJBJPSkJ6mhHpnBgm3Ls=' ws://gc.kis.v2.scr.kaspersky-labs.com",
            "frame-src 'self' " . implode(' ', $stripe_domains),
            "connect-src 'self' " . implode(' ', array_merge($stripe_domains, $antivirus_domains)),
            "img-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline' " . implode(' ', $cdn_domains) . " http://gc.kis.v2.scr.kaspersky-labs.com ws://gc.kis.v2.scr.kaspersky-labs.com"
        );
        
        $csp_content = implode('; ', $csp_directives);
        
        // Output CSP meta tag
        echo '<meta http-equiv="Content-Security-Policy" content="' . esc_attr($csp_content) . '">' . "\n";
        
        // Also add a comment for debugging
        echo '<!-- Salon Booking Plugin: CSP headers added for Stripe compatibility -->' . "\n";
    }
    
    /**
     * Get the booking page URL
     */
    private function get_booking_page_url() {
        $booking_page_id = get_option('salon_booking_page_id', 0);
        if ($booking_page_id) {
            $booking_page_url = get_permalink($booking_page_id);
            if ($booking_page_url) {
                return $booking_page_url;
            }
        }
        
        // Fallback to default
        return home_url('/booking/');
    }
    
    /**
     * Check if current page is a booking page
     */
    private function is_booking_page() {
        global $post;
        
        // Check if it's the designated booking page
        $booking_page_id = get_option('salon_booking_page_id', 0);
        if ($booking_page_id && is_page($booking_page_id)) {
            return true;
        }
        
        // Check if page contains booking shortcodes
        if ($post && (has_shortcode($post->post_content, 'salon_booking') || has_shortcode($post->post_content, 'salon_booking_form'))) {
            return true;
        }
        
        // Check if it's a test page (for development)
        if (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], 'test-wordpress-booking') !== false || strpos($_SERVER['REQUEST_URI'], 'debug-booking') !== false || strpos($_SERVER['REQUEST_URI'], 'manual-test') !== false || strpos($_SERVER['REQUEST_URI'], 'client-demo') !== false)) {
            return true;
        }
        
        // Check if it's an admin page with booking functionality
        if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'salon-booking') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * AJAX handler for testing database connectivity
     */
    public function ajax_test_database() {
        check_ajax_referer('salon_booking_nonce', 'nonce');
        
        $test_results = [];
        
        // Test database connection
        global $wpdb;
        $test_results['database_connection'] = $wpdb->last_error ? 'Failed: ' . $wpdb->last_error : 'OK';
        
        // Test table existence
        $services_table = $wpdb->prefix . 'salon_services';
        $staff_table = $wpdb->prefix . 'salon_staff';
        $bookings_table = $wpdb->prefix . 'salon_bookings';
        
        $test_results['services_table_exists'] = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") ? 'Yes' : 'No';
        $test_results['staff_table_exists'] = $wpdb->get_var("SHOW TABLES LIKE '$staff_table'") ? 'Yes' : 'No';
        $test_results['bookings_table_exists'] = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") ? 'Yes' : 'No';
        
        // Test data counts
        if ($test_results['services_table_exists'] === 'Yes') {
            $test_results['services_count'] = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
        }
        
        if ($test_results['staff_table_exists'] === 'Yes') {
            $test_results['staff_count'] = $wpdb->get_var("SELECT COUNT(*) FROM $staff_table");
        }
        
        // Test database class methods
        try {
            $services = Salon_Booking_Database::get_services(true);
            $test_results['get_services_method'] = 'OK - Found ' . count($services) . ' services';
        } catch (Exception $e) {
            $test_results['get_services_method'] = 'Error: ' . $e->getMessage();
        }
        
        try {
            $staff = Salon_Booking_Database::get_staff(true);
            $test_results['get_staff_method'] = 'OK - Found ' . count($staff) . ' staff members';
        } catch (Exception $e) {
            $test_results['get_staff_method'] = 'Error: ' . $e->getMessage();
        }
        
        wp_send_json_success($test_results);
    }
}