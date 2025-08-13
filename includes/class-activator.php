<?php

/**
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Salon_Booking_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     */
    public static function activate() {
        self::create_tables();
        self::insert_default_data();
        self::create_pages();
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix . SALON_BOOKING_TABLE_PREFIX;

        // Services table
        $services_table = $table_prefix . 'services';
        $services_sql = "CREATE TABLE $services_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text,
            duration int(11) NOT NULL,
            price decimal(10,2) NOT NULL,
            upfront_fee decimal(10,2) NOT NULL,
            category varchar(50),
            is_active boolean DEFAULT TRUE,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Staff table
        $staff_table = $table_prefix . 'staff';
        $staff_sql = "CREATE TABLE $staff_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL UNIQUE,
            phone varchar(20),
            specialties text,
            working_hours text,
            is_active boolean DEFAULT TRUE,
            is_owner boolean DEFAULT FALSE,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Bookings table
        $bookings_table = $table_prefix . 'bookings';
        $bookings_sql = "CREATE TABLE $bookings_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            client_name varchar(100) NOT NULL,
            client_email varchar(100) NOT NULL,
            client_phone varchar(20),
            service_id int(11) NOT NULL,
            staff_id int(11) NOT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            duration int(11) NOT NULL,
            status enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
            payment_status enum('pending','paid','failed','refunded') DEFAULT 'pending',
            payment_intent_id varchar(255),
            total_amount decimal(10,2) NOT NULL,
            upfront_fee decimal(10,2) NOT NULL,
            notes text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY staff_id (staff_id),
            KEY booking_date (booking_date),
            KEY status (status)
        ) $charset_collate;";

        // Staff availability table
        $availability_table = $table_prefix . 'staff_availability';
        $availability_sql = "CREATE TABLE $availability_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            staff_id int(11) NOT NULL,
            date date NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            is_available boolean DEFAULT TRUE,
            reason varchar(255),
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_staff_date_time (staff_id, date, start_time),
            KEY staff_id (staff_id),
            KEY date (date)
        ) $charset_collate;";

        // Email templates table
        $email_templates_table = $table_prefix . 'email_templates';
        $email_templates_sql = "CREATE TABLE $email_templates_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            template_name varchar(50) NOT NULL UNIQUE,
            subject varchar(255) NOT NULL,
            body text NOT NULL,
            variables text,
            is_active boolean DEFAULT TRUE,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($services_sql);
        dbDelta($staff_sql);
        dbDelta($bookings_sql);
        dbDelta($availability_sql);
        dbDelta($email_templates_sql);
    }

    /**
     * Insert default data
     */
    public static function insert_default_data() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . SALON_BOOKING_TABLE_PREFIX;

        // Insert default services based on UR Beautiful website
        $services = [
            ['Gel Nails', 'Transform your nails with stunning gel finishes.', 60, 250.00, 50.00, 'Nails'],
            ['Full Set Nails', 'Complete nail transformation with gel application.', 90, 350.00, 70.00, 'Nails'],
            ['Nail Art', 'Creative nail designs and decorations.', 45, 150.00, 30.00, 'Nails'],
            ['Full Body Wax', 'Complete body hair removal service.', 120, 800.00, 160.00, 'Waxing'],
            ['Leg Wax', 'Professional leg hair removal.', 45, 300.00, 60.00, 'Waxing'],
            ['Brazilian Wax', 'Intimate area hair removal.', 30, 250.00, 50.00, 'Waxing'],
            ['Underarm Wax', 'Quick and efficient underarm hair removal.', 15, 80.00, 16.00, 'Waxing'],
            ['Relaxation Massage', 'Full body therapeutic massage.', 60, 400.00, 80.00, 'Massages'],
            ['Deep Tissue Massage', 'Intensive muscle therapy massage.', 90, 550.00, 110.00, 'Massages'],
            ['Hot Stone Massage', 'Relaxing massage with heated stones.', 75, 500.00, 100.00, 'Massages'],
            ['Eyebrow Threading', 'Precise eyebrow shaping and hair removal.', 20, 120.00, 24.00, 'Threading'],
            ['Upper Lip Threading', 'Gentle facial hair removal.', 10, 60.00, 12.00, 'Threading'],
            ['Full Face Threading', 'Complete facial hair removal service.', 30, 200.00, 40.00, 'Threading'],
            ['Eyebrow Tinting', 'Professional eyebrow color enhancement.', 15, 100.00, 20.00, 'Brows & Lashes'],
            ['Eyelash Extensions', 'Beautiful lash enhancement service.', 120, 600.00, 120.00, 'Brows & Lashes'],
            ['Lash Lift & Tint', 'Natural lash enhancement treatment.', 45, 300.00, 60.00, 'Brows & Lashes'],
            ['Hydrating Facial', 'Deep moisturizing facial treatment.', 60, 350.00, 70.00, 'Facials'],
            ['Anti-Aging Facial', 'Advanced anti-aging skin treatment.', 75, 450.00, 90.00, 'Facials'],
            ['Deep Cleansing Facial', 'Thorough skin purification treatment.', 60, 300.00, 60.00, 'Facials']
        ];

        $services_table = $table_prefix . 'services';
        foreach ($services as $service) {
            $wpdb->insert(
                $services_table,
                [
                    'name' => $service[0],
                    'description' => $service[1],
                    'duration' => $service[2],
                    'price' => $service[3],
                    'upfront_fee' => $service[4],
                    'category' => $service[5],
                    'is_active' => 1
                ],
                ['%s', '%s', '%d', '%f', '%f', '%s', '%d']
            );
        }

        // Insert default staff (owner)
        $staff_table = $table_prefix . 'staff';
        $wpdb->insert(
            $staff_table,
            [
                'name' => 'UR Beautiful Owner',
                'email' => 'owner@urbeautiful.co.za',
                'phone' => '+27 53 123 4567',
                'specialties' => json_encode(['Nails', 'Waxing', 'Massages', 'Threading', 'Brows & Lashes', 'Facials']),
                'working_hours' => json_encode([
                    'tuesday' => ['start' => '09:00', 'end' => '18:00'],
                    'wednesday' => ['start' => '09:00', 'end' => '18:00'],
                    'thursday' => ['start' => '09:00', 'end' => '18:00'],
                    'friday' => ['start' => '09:00', 'end' => '15:00'],
                    'saturday' => ['start' => '09:00', 'end' => '15:00']
                ]),
                'is_active' => 1,
                'is_owner' => 1
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%d']
        );

        // Insert default email templates
        $email_templates_table = $table_prefix . 'email_templates';
        $email_templates = [
            [
                'template_name' => 'booking_confirmation_client',
                'subject' => 'Booking Confirmation - UR Beautiful',
                'body' => "Dear {client_name},\n\nThank you for booking with UR Beautiful!\n\nBooking Details:\nService: {service_name}\nDate: {booking_date}\nTime: {booking_time}\nStaff: {staff_name}\nTotal Amount: R{total_amount}\nUpfront Fee Paid: R{upfront_fee}\n\nLocation: 2N Circular Road, West End, Kimberley, South Africa\n\nWe look forward to pampering you!\n\nBest regards,\nUR Beautiful Team",
                'variables' => json_encode(['client_name', 'service_name', 'booking_date', 'booking_time', 'staff_name', 'total_amount', 'upfront_fee'])
            ],
            [
                'template_name' => 'booking_confirmation_salon',
                'subject' => 'New Booking Received - UR Beautiful',
                'body' => "New booking received:\n\nClient: {client_name}\nEmail: {client_email}\nPhone: {client_phone}\nService: {service_name}\nDate: {booking_date}\nTime: {booking_time}\nStaff: {staff_name}\nTotal Amount: R{total_amount}\nUpfront Fee: R{upfront_fee}\nPayment Status: {payment_status}\n\nNotes: {notes}",
                'variables' => json_encode(['client_name', 'client_email', 'client_phone', 'service_name', 'booking_date', 'booking_time', 'staff_name', 'total_amount', 'upfront_fee', 'payment_status', 'notes'])
            ]
        ];

        foreach ($email_templates as $template) {
            $wpdb->insert(
                $email_templates_table,
                $template,
                ['%s', '%s', '%s', '%s']
            );
        }
    }

    /**
     * Create necessary pages
     */
    private static function create_pages() {
        // Create booking page
        $booking_page = [
            'post_title' => 'Book Appointment',
            'post_content' => '[salon_booking_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_slug' => 'book-appointment'
        ];

        $page_id = wp_insert_post($booking_page);
        if ($page_id) {
            update_option('salon_booking_page_id', $page_id);
        }
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_options = [
            'salon_booking_currency' => 'ZAR',
            'salon_booking_currency_symbol' => 'R',
            'salon_booking_time_format' => '24',
            'salon_booking_date_format' => 'Y-m-d',
            'salon_booking_booking_window' => 30, // days in advance
            'salon_booking_min_booking_time' => 24, // hours in advance
            'salon_booking_slot_duration' => 30, // minutes
            'salon_booking_admin_email' => get_option('admin_email'),
            'salon_booking_stripe_mode' => 'test',
            'salon_booking_require_payment' => 1,
            'salon_booking_send_notifications' => 1
        ];

        foreach ($default_options as $option_name => $option_value) {
            add_option($option_name, $option_value);
        }
    }
}