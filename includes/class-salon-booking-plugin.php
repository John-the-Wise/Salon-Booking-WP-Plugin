<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 */

class Salon_Booking_Plugin {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        if (defined('SALON_BOOKING_VERSION')) {
            $this->version = SALON_BOOKING_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'salon-booking-plugin';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once SALON_BOOKING_PLUGIN_DIR . 'admin/class-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once SALON_BOOKING_PLUGIN_DIR . 'public/class-public.php';

        /**
         * Core business logic classes
         */
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-database.php';
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-booking-manager.php';
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-staff-manager.php';
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-service-manager.php';
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-payment-handler.php';
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-email-notifications.php';
        require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-calendar-helper.php';

        $this->loader = new Salon_Booking_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $plugin_i18n = new Salon_Booking_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Salon_Booking_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');

        // AJAX hooks for admin
        $this->loader->add_action('wp_ajax_salon_get_bookings', $plugin_admin, 'ajax_get_bookings');
        $this->loader->add_action('wp_ajax_salon_update_booking_status', $plugin_admin, 'ajax_update_booking_status');
        $this->loader->add_action('wp_ajax_salon_save_staff', $plugin_admin, 'ajax_save_staff');
        $this->loader->add_action('wp_ajax_salon_delete_staff', $plugin_admin, 'ajax_delete_staff');
        $this->loader->add_action('wp_ajax_salon_save_service', $plugin_admin, 'ajax_save_service');
        $this->loader->add_action('wp_ajax_salon_delete_service', $plugin_admin, 'ajax_delete_service');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_public_hooks() {
        $plugin_public = new Salon_Booking_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'init');

        // Shortcode registration
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');

        // AJAX hooks for public
        $this->loader->add_action('wp_ajax_salon_booking_get_services', $plugin_public, 'ajax_get_services');
        $this->loader->add_action('wp_ajax_nopriv_salon_booking_get_services', $plugin_public, 'ajax_get_services');
        $this->loader->add_action('wp_ajax_salon_booking_get_staff', $plugin_public, 'ajax_get_staff');
        $this->loader->add_action('wp_ajax_nopriv_salon_booking_get_staff', $plugin_public, 'ajax_get_staff');
        $this->loader->add_action('wp_ajax_salon_booking_check_availability', $plugin_public, 'ajax_check_availability');
        $this->loader->add_action('wp_ajax_nopriv_salon_booking_check_availability', $plugin_public, 'ajax_check_availability');
        $this->loader->add_action('wp_ajax_salon_booking_create_booking', $plugin_public, 'ajax_create_booking');
        $this->loader->add_action('wp_ajax_nopriv_salon_booking_create_booking', $plugin_public, 'ajax_create_booking');
        $this->loader->add_action('wp_ajax_salon_booking_process_payment', $plugin_public, 'ajax_process_payment');
        $this->loader->add_action('wp_ajax_nopriv_salon_booking_process_payment', $plugin_public, 'ajax_process_payment');
        
        // Add database test handler
        $this->loader->add_action('wp_ajax_salon_booking_test_database', $plugin_public, 'ajax_test_database');
        $this->loader->add_action('wp_ajax_nopriv_salon_booking_test_database', $plugin_public, 'ajax_test_database');

        // REST API endpoints
        $this->loader->add_action('rest_api_init', $plugin_public, 'register_rest_routes');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}