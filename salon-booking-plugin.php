<?php
/**
 * Plugin Name: Salon Booking Plugin
 * Plugin URI: https://github.com/John-the-Wise/Salon-Booking-WP-Plugin
 * Description: A comprehensive booking system for beauty salons with calendar integration, payment processing, and staff management.
 * Version: 1.0.0
 * Author: UR Beautiful
 * Author URI: https://urbeautiful.co.za
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: salon-booking
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Update URI: https://github.com/John-the-Wise/Salon-Booking-WP-Plugin
 *
 * @package SalonBooking
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version.
 */
define('SALON_BOOKING_VERSION', '1.0.0');
define('SALON_BOOKING_PLUGIN_FILE', __FILE__);

/**
 * Plugin directory path.
 */
define('SALON_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('SALON_BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('SALON_BOOKING_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Database table prefix.
 */
define('SALON_BOOKING_TABLE_PREFIX', 'salon_');

/**
 * Database table names.
 */
global $wpdb;
define('SALON_BOOKING_TABLE_SERVICES', $wpdb->prefix . 'salon_services');
define('SALON_BOOKING_TABLE_STAFF', $wpdb->prefix . 'salon_staff');
define('SALON_BOOKING_TABLE_BOOKINGS', $wpdb->prefix . 'salon_bookings');
define('SALON_BOOKING_TABLE_AVAILABILITY', $wpdb->prefix . 'salon_staff_availability');
define('SALON_BOOKING_TABLE_EMAIL_TEMPLATES', $wpdb->prefix . 'salon_email_templates');

/**
 * The code that runs during plugin activation.
 */
function activate_salon_booking_plugin() {
    require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-activator.php';
    Salon_Booking_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_salon_booking_plugin() {
    require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-deactivator.php';
    Salon_Booking_Deactivator::deactivate();
}

/**
 * Plugin update checker and version manager
 */
function salon_booking_init_updater() {
    if (is_admin()) {
        require_once plugin_dir_path(__FILE__) . 'update-system.php';
        require_once plugin_dir_path(__FILE__) . 'version-manager.php';
    }
}

register_activation_hook(__FILE__, 'activate_salon_booking_plugin');
register_deactivation_hook(__FILE__, 'deactivate_salon_booking_plugin');
add_action('init', 'salon_booking_init_updater');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SALON_BOOKING_PLUGIN_DIR . 'includes/class-salon-booking-plugin.php';

/**
 * Check if WordPress and PHP versions are compatible
 */
function salon_booking_check_requirements() {
    global $wp_version;
    
    $php_version = phpversion();
    $wp_min_version = '5.0';
    $php_min_version = '7.4';
    
    if (version_compare($wp_version, $wp_min_version, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf(
                __('Salon Booking Plugin requires WordPress %s or higher. You are running WordPress %s.', 'salon-booking'),
                $wp_min_version,
                $wp_version
            )
        );
    }
    
    if (version_compare($php_version, $php_min_version, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf(
                __('Salon Booking Plugin requires PHP %s or higher. You are running PHP %s.', 'salon-booking'),
                $php_min_version,
                $php_version
            )
        );
    }
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_salon_booking_plugin() {
    // Check requirements first
    salon_booking_check_requirements();
    
    $plugin = new Salon_Booking_Plugin();
    $plugin->run();
}
run_salon_booking_plugin();

/**
 * Add plugin action links.
 */
function salon_booking_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=salon-booking-settings') . '">' . __('Settings', 'salon-booking') . '</a>';
    $dashboard_link = '<a href="' . admin_url('admin.php?page=salon-booking') . '">' . __('Dashboard', 'salon-booking') . '</a>';
    
    array_unshift($links, $settings_link, $dashboard_link);
    return $links;
}
add_filter('plugin_action_links_' . SALON_BOOKING_PLUGIN_BASENAME, 'salon_booking_plugin_action_links');

/**
 * Add plugin meta links.
 */
function salon_booking_plugin_meta_links($links, $file) {
    if ($file === SALON_BOOKING_PLUGIN_BASENAME) {
        $links[] = '<a href="https://urbeautiful.co.za/support" target="_blank">' . __('Support', 'salon-booking') . '</a>';
        $links[] = '<a href="https://urbeautiful.co.za/documentation" target="_blank">' . __('Documentation', 'salon-booking') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'salon_booking_plugin_meta_links', 10, 2);

/**
 * Check for required dependencies.
 */
function salon_booking_check_dependencies() {
    $errors = array();
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = sprintf(
            __('Salon Booking Plugin requires PHP version 7.4 or higher. You are running version %s.', 'salon-booking'),
            PHP_VERSION
        );
    }
    
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        $errors[] = sprintf(
            __('Salon Booking Plugin requires WordPress version 5.0 or higher. You are running version %s.', 'salon-booking'),
            get_bloginfo('version')
        );
    }
    
    // Check for required PHP extensions
    $required_extensions = array('curl', 'json', 'mbstring');
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf(
                __('Salon Booking Plugin requires the %s PHP extension.', 'salon-booking'),
                $extension
            );
        }
    }
    
    if (!empty($errors)) {
        add_action('admin_notices', function() use ($errors) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . __('Salon Booking Plugin Error:', 'salon-booking') . '</strong><br>';
            echo implode('<br>', $errors);
            echo '</p></div>';
        });
        
        // Deactivate the plugin
        add_action('admin_init', function() {
            deactivate_plugins(SALON_BOOKING_PLUGIN_BASENAME);
        });
        
        return false;
    }
    
    return true;
}

// Check dependencies on plugin load
if (!salon_booking_check_dependencies()) {
    return;
}

/**
 * Initialize the plugin after all plugins are loaded.
 */
function salon_booking_init() {
    // Load text domain for translations
    load_plugin_textdomain(
        'salon-booking',
        false,
        dirname(SALON_BOOKING_PLUGIN_BASENAME) . '/languages/'
    );
    
    // Check if we need to run database updates
    $current_version = get_option('salon_booking_version', '0.0.0');
    if (version_compare($current_version, SALON_BOOKING_VERSION, '<')) {
        salon_booking_update_database();
        update_option('salon_booking_version', SALON_BOOKING_VERSION);
    }
}
add_action('plugins_loaded', 'salon_booking_init');

/**
 * Handle database updates.
 */
function salon_booking_update_database() {
    require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-activator.php';
    Salon_Booking_Activator::create_tables();
    Salon_Booking_Activator::insert_default_data();
}

/**
 * Add admin notice for configuration.
 */
function salon_booking_admin_notice() {
    // Only show to administrators
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if Stripe is configured
    $stripe_publishable = get_option('salon_booking_stripe_publishable_key', '');
    $stripe_secret = get_option('salon_booking_stripe_secret_key', '');
    
    if (empty($stripe_publishable) || empty($stripe_secret)) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>' . __('Salon Booking Plugin:', 'salon-booking') . '</strong> ';
        echo sprintf(
            __('Please configure your Stripe payment settings in the <a href="%s">plugin settings</a> to start accepting bookings.', 'salon-booking'),
            admin_url('admin.php?page=salon-booking-settings')
        );
        echo '</p></div>';
    }
    
    // Check if booking page is set
    $booking_page = get_option('salon_booking_page_id', 0);
    if (empty($booking_page) || get_post_status($booking_page) !== 'publish') {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>' . __('Salon Booking Plugin:', 'salon-booking') . '</strong> ';
        echo sprintf(
            __('Please create a booking page and configure it in the <a href="%s">plugin settings</a>.', 'salon-booking'),
            admin_url('admin.php?page=salon-booking-settings')
        );
        echo '</p></div>';
    }
}
add_action('admin_notices', 'salon_booking_admin_notice');

/**
 * Handle plugin uninstall.
 */
function salon_booking_uninstall() {
    // Only run if user has proper permissions
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    // Check if we should delete data on uninstall
    $delete_data = get_option('salon_booking_delete_data_on_uninstall', false);
    
    if ($delete_data) {
        global $wpdb;
        
        // Drop custom tables
        $tables = array(
            SALON_BOOKING_TABLE_SERVICES,
            SALON_BOOKING_TABLE_STAFF,
            SALON_BOOKING_TABLE_BOOKINGS,
            SALON_BOOKING_TABLE_AVAILABILITY,
            SALON_BOOKING_TABLE_EMAIL_TEMPLATES
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        // Delete plugin options
        $options = array(
            'salon_booking_version',
            'salon_booking_stripe_publishable_key',
            'salon_booking_stripe_secret_key',
            'salon_booking_stripe_test_mode',
            'salon_booking_currency',
            'salon_booking_deposit_percentage',
            'salon_booking_require_payment',
            'salon_booking_admin_email',
            'salon_booking_from_email',
            'salon_booking_client_notifications',
            'salon_booking_admin_notifications',
            'salon_booking_reminder_emails',
            'salon_booking_reminder_hours',
            'salon_booking_business_name',
            'salon_booking_business_address',
            'salon_booking_business_phone',
            'salon_booking_business_email',
            'salon_booking_business_website',
            'salon_booking_cancellation_policy',
            'salon_booking_time_format',
            'salon_booking_date_format',
            'salon_booking_booking_window_days',
            'salon_booking_time_slot_interval',
            'salon_booking_page_id',
            'salon_booking_delete_data_on_uninstall'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Clear any scheduled events
        wp_clear_scheduled_hook('salon_booking_send_reminders');
        wp_clear_scheduled_hook('salon_booking_cleanup_expired_bookings');
    }
}
register_uninstall_hook(__FILE__, 'salon_booking_uninstall');

/**
 * Add custom cron schedules.
 */
function salon_booking_cron_schedules($schedules) {
    $schedules['every_15_minutes'] = array(
        'interval' => 15 * 60,
        'display' => __('Every 15 Minutes', 'salon-booking')
    );
    
    $schedules['hourly'] = array(
        'interval' => 60 * 60,
        'display' => __('Hourly', 'salon-booking')
    );
    
    return $schedules;
}
add_filter('cron_schedules', 'salon_booking_cron_schedules');

/**
 * Schedule cron events.
 */
function salon_booking_schedule_events() {
    // Schedule reminder emails
    if (!wp_next_scheduled('salon_booking_send_reminders')) {
        wp_schedule_event(time(), 'hourly', 'salon_booking_send_reminders');
    }
    
    // Schedule cleanup of expired bookings
    if (!wp_next_scheduled('salon_booking_cleanup_expired_bookings')) {
        wp_schedule_event(time(), 'daily', 'salon_booking_cleanup_expired_bookings');
    }
}
add_action('wp', 'salon_booking_schedule_events');

/**
 * Handle reminder emails cron job.
 */
function salon_booking_send_reminder_emails() {
    require_once SALON_BOOKING_PLUGIN_DIR . 'includes/class-email-handler.php';
    $email_handler = new Salon_Booking_Email_Handler();
    $email_handler->send_reminder_emails();
}
add_action('salon_booking_send_reminders', 'salon_booking_send_reminder_emails');

/**
 * Handle cleanup of expired bookings.
 */
function salon_booking_cleanup_expired_bookings() {
    global $wpdb;
    
    // Delete pending bookings older than 24 hours
    $wpdb->query($wpdb->prepare(
        "DELETE FROM " . SALON_BOOKING_TABLE_BOOKINGS . "
         WHERE status = 'pending' 
         AND created_at < %s",
        date('Y-m-d H:i:s', strtotime('-24 hours'))
    ));
    
    // Log cleanup activity
    error_log('Salon Booking: Cleaned up expired pending bookings');
}
add_action('salon_booking_cleanup_expired_bookings', 'salon_booking_cleanup_expired_bookings');

/**
 * Add custom capabilities.
 */
function salon_booking_add_capabilities() {
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_salon_bookings');
        $role->add_cap('view_salon_bookings');
        $role->add_cap('edit_salon_bookings');
        $role->add_cap('delete_salon_bookings');
        $role->add_cap('manage_salon_staff');
        $role->add_cap('manage_salon_services');
        $role->add_cap('manage_salon_settings');
    }
    
    // Add capabilities to editor role (limited access)
    $editor_role = get_role('editor');
    if ($editor_role) {
        $editor_role->add_cap('view_salon_bookings');
        $editor_role->add_cap('edit_salon_bookings');
    }
}
register_activation_hook(__FILE__, 'salon_booking_add_capabilities');

/**
 * Remove custom capabilities on deactivation.
 */
function salon_booking_remove_capabilities() {
    $roles = array('administrator', 'editor');
    $capabilities = array(
        'manage_salon_bookings',
        'view_salon_bookings',
        'edit_salon_bookings',
        'delete_salon_bookings',
        'manage_salon_staff',
        'manage_salon_services',
        'manage_salon_settings'
    );
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
}
register_deactivation_hook(__FILE__, 'salon_booking_remove_capabilities');

/**
 * Add security headers.
 */
function salon_booking_add_security_headers() {
    // Only add headers on plugin pages
    if (!isset($_GET['page']) || strpos($_GET['page'], 'salon-booking') !== 0) {
        return;
    }
    
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}
add_action('admin_init', 'salon_booking_add_security_headers');

/**
 * Add CSP headers via WordPress send_headers action for better compatibility
 */
function salon_booking_add_csp_headers() {
    // Check if we're on a page that needs Stripe
    if (!salon_booking_needs_stripe()) {
        return;
    }
    
    // Define Stripe-compatible CSP policy with antivirus compatibility
    $stripe_domains = 'https://js.stripe.com https://m.stripe.network https://*.stripe.network https://api.stripe.com https://hooks.stripe.com';
    $antivirus_domains = 'https://*.kaspersky-labs.com https://*.avast.com https://*.avg.com https://*.norton.com https://*.mcafee.com';
    $cdn_domains = 'https://cdn.jsdelivr.net https://unpkg.com';
    
    $csp_policy = "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$stripe_domains} {$antivirus_domains} {$cdn_domains} 'sha256-5DA+a07wxWmEka9IdoWjSPVHb17Cp5284/lJzfbl8KA=' 'sha256-/5Guo2nzv5n/w6ukZpOBZOtTJBJPSkJ6mhHpnBgm3Ls=' ws://gc.kis.v2.scr.kaspersky-labs.com; frame-src 'self' {$stripe_domains}; connect-src 'self' {$stripe_domains} {$antivirus_domains}; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' {$cdn_domains} http://gc.kis.v2.scr.kaspersky-labs.com ws://gc.kis.v2.scr.kaspersky-labs.com";
    
    // Allow filtering of CSP policy
    $csp_policy = apply_filters('salon_booking_csp_policy', $csp_policy);
    
    // Only add if no CSP header already exists
    if (!headers_sent() && !salon_booking_has_existing_csp()) {
        header('Content-Security-Policy: ' . $csp_policy);
    }
}
add_action('send_headers', 'salon_booking_add_csp_headers');

/**
 * Check if current request needs Stripe functionality
 */
function salon_booking_needs_stripe() {
    global $post;
    
    // Check if it's the booking page
    $booking_page_id = get_option('salon_booking_page_id', 0);
    if ($booking_page_id && is_page($booking_page_id)) {
        return true;
    }
    
    // Check if page contains booking shortcode
    if ($post && has_shortcode($post->post_content, 'salon_booking')) {
        return true;
    }
    
    // Check admin pages
    if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'salon-booking') !== false) {
        return true;
    }
    
    return false;
}

/**
 * Check if CSP header already exists
 */
function salon_booking_has_existing_csp() {
    if (function_exists('headers_list')) {
        $headers = headers_list();
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Security-Policy:') === 0) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Handle AJAX requests for non-logged-in users.
 */
function salon_booking_handle_public_ajax() {
    // Allow public access to certain AJAX actions
    $public_actions = array(
        'salon_booking_get_services',
        'salon_booking_get_staff',
        'salon_booking_check_availability',
        'salon_booking_create_booking',
        'salon_booking_process_payment'
    );
    
    foreach ($public_actions as $action) {
        add_action('wp_ajax_nopriv_' . $action, function() use ($action) {
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['nonce'], 'salon_booking_public_nonce')) {
                wp_die('Security check failed');
            }
            
            // Handle the action
            do_action('wp_ajax_' . $action);
        });
    }
}
add_action('init', 'salon_booking_handle_public_ajax');

/**
 * Add custom post states for booking page.
 */
function salon_booking_add_post_states($post_states, $post) {
    $booking_page_id = get_option('salon_booking_page_id', 0);
    
    if ($post->ID == $booking_page_id) {
        $post_states['salon_booking_page'] = __('Booking Page', 'salon-booking');
    }
    
    return $post_states;
}
add_filter('display_post_states', 'salon_booking_add_post_states', 10, 2);

/**
 * Prevent deletion of booking page.
 */
function salon_booking_prevent_page_deletion($post_id) {
    $booking_page_id = get_option('salon_booking_page_id', 0);
    
    if ($post_id == $booking_page_id) {
        wp_die(
            __('This page cannot be deleted as it is set as the booking page for the Salon Booking Plugin.', 'salon-booking'),
            __('Cannot Delete Page', 'salon-booking'),
            array('back_link' => true)
        );
    }
}
add_action('wp_trash_post', 'salon_booking_prevent_page_deletion');
add_action('before_delete_post', 'salon_booking_prevent_page_deletion');

/**
 * Add plugin to WordPress admin bar.
 */
function salon_booking_admin_bar_menu($wp_admin_bar) {
    if (!current_user_can('manage_salon_bookings')) {
        return;
    }
    
    $wp_admin_bar->add_menu(array(
        'id' => 'salon-booking',
        'title' => __('Salon Booking', 'salon-booking'),
        'href' => admin_url('admin.php?page=salon-booking'),
        'meta' => array(
            'title' => __('Salon Booking Dashboard', 'salon-booking')
        )
    ));
    
    $wp_admin_bar->add_menu(array(
        'parent' => 'salon-booking',
        'id' => 'salon-booking-calendar',
        'title' => __('Calendar', 'salon-booking'),
        'href' => admin_url('admin.php?page=salon-booking-calendar')
    ));
    
    $wp_admin_bar->add_menu(array(
        'parent' => 'salon-booking',
        'id' => 'salon-booking-bookings',
        'title' => __('Bookings', 'salon-booking'),
        'href' => admin_url('admin.php?page=salon-booking-bookings')
    ));
}
add_action('admin_bar_menu', 'salon_booking_admin_bar_menu', 100);

/**
 * Log plugin errors.
 */
function salon_booking_log_error($message, $data = array()) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = 'Salon Booking Plugin Error: ' . $message;
        if (!empty($data)) {
            $log_message .= ' Data: ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

/**
 * Plugin compatibility checks.
 */
function salon_booking_compatibility_check() {
    // Check for conflicting plugins
    $conflicting_plugins = array(
        'booking/booking.php' => 'Booking Calendar',
        'simply-schedule-appointments/simply-schedule-appointments.php' => 'Simply Schedule Appointments'
    );
    
    foreach ($conflicting_plugins as $plugin_file => $plugin_name) {
        if (is_plugin_active($plugin_file)) {
            add_action('admin_notices', function() use ($plugin_name) {
                echo '<div class="notice notice-warning">';
                echo '<p><strong>' . __('Salon Booking Plugin Warning:', 'salon-booking') . '</strong> ';
                echo sprintf(
                    __('The plugin "%s" may conflict with Salon Booking Plugin. Please deactivate it if you experience issues.', 'salon-booking'),
                    $plugin_name
                );
                echo '</p></div>';
            });
        }
    }
}
add_action('admin_init', 'salon_booking_compatibility_check');

/**
 * Handle plugin updates.
 */
function salon_booking_check_for_updates() {
    // This would typically connect to a remote server to check for updates
    // For now, we'll just ensure the database is up to date
    $current_version = get_option('salon_booking_version', '0.0.0');
    
    if (version_compare($current_version, SALON_BOOKING_VERSION, '<')) {
        // Run any necessary update procedures
        salon_booking_update_database();
        update_option('salon_booking_version', SALON_BOOKING_VERSION);
        
        // Show update notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Salon Booking Plugin:', 'salon-booking') . '</strong> ';
            echo sprintf(
                __('Plugin updated to version %s successfully.', 'salon-booking'),
                SALON_BOOKING_VERSION
            );
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'salon_booking_check_for_updates');

// End of file
