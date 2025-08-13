<?php

/**
 * Provide an admin area view for plugin settings
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salon_booking_settings_nonce'])) {
    if (wp_verify_nonce($_POST['salon_booking_settings_nonce'], 'salon_booking_settings')) {
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'general';
        
        switch ($tab) {
            case 'general':
                update_option('salon_booking_currency', sanitize_text_field($_POST['currency']));
                update_option('salon_booking_time_format', sanitize_text_field($_POST['time_format']));
                update_option('salon_booking_date_format', sanitize_text_field($_POST['date_format']));
                update_option('salon_booking_booking_window', intval($_POST['booking_window']));
                update_option('salon_booking_min_booking_time', intval($_POST['min_booking_time']));
                update_option('salon_booking_max_booking_time', intval($_POST['max_booking_time']));
                update_option('salon_booking_time_slot_interval', intval($_POST['time_slot_interval']));
                break;
                
            case 'payment':
                update_option('salon_booking_stripe_mode', sanitize_text_field($_POST['stripe_mode']));
                update_option('salon_booking_stripe_live_publishable_key', sanitize_text_field($_POST['stripe_live_publishable_key']));
                update_option('salon_booking_stripe_live_secret_key', sanitize_text_field($_POST['stripe_live_secret_key']));
                update_option('salon_booking_stripe_test_publishable_key', sanitize_text_field($_POST['stripe_test_publishable_key']));
                update_option('salon_booking_stripe_test_secret_key', sanitize_text_field($_POST['stripe_test_secret_key']));
                update_option('salon_booking_deposit_percentage', floatval($_POST['deposit_percentage']));
                update_option('salon_booking_payment_required', isset($_POST['payment_required']) ? 1 : 0);
                break;
                
            case 'notifications':
                update_option('salon_booking_admin_email', sanitize_email($_POST['admin_email']));
                update_option('salon_booking_from_name', sanitize_text_field($_POST['from_name']));
                update_option('salon_booking_from_email', sanitize_email($_POST['from_email']));
                update_option('salon_booking_send_client_confirmation', isset($_POST['send_client_confirmation']) ? 1 : 0);
                update_option('salon_booking_send_admin_notification', isset($_POST['send_admin_notification']) ? 1 : 0);
                update_option('salon_booking_send_reminder_emails', isset($_POST['send_reminder_emails']) ? 1 : 0);
                update_option('salon_booking_reminder_hours', intval($_POST['reminder_hours']));
                break;
                
            case 'business':
                update_option('salon_booking_business_name', sanitize_text_field($_POST['business_name']));
                update_option('salon_booking_business_address', sanitize_textarea_field($_POST['business_address']));
                update_option('salon_booking_business_phone', sanitize_text_field($_POST['business_phone']));
                update_option('salon_booking_business_email', sanitize_email($_POST['business_email']));
                update_option('salon_booking_business_website', esc_url_raw($_POST['business_website']));
                update_option('salon_booking_cancellation_policy', sanitize_textarea_field($_POST['cancellation_policy']));
                break;
        }
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Get current settings
$currency = get_option('salon_booking_currency', 'ZAR');
$time_format = get_option('salon_booking_time_format', 'H:i');
$date_format = get_option('salon_booking_date_format', 'Y-m-d');
$booking_window = get_option('salon_booking_booking_window', 30);
$min_booking_time = get_option('salon_booking_min_booking_time', 2);
$max_booking_time = get_option('salon_booking_max_booking_time', 24);
$time_slot_interval = get_option('salon_booking_time_slot_interval', 30);

$stripe_mode = get_option('salon_booking_stripe_mode', 'test');
$stripe_live_publishable_key = get_option('salon_booking_stripe_live_publishable_key', '');
$stripe_live_secret_key = get_option('salon_booking_stripe_live_secret_key', '');
$stripe_test_publishable_key = get_option('salon_booking_stripe_test_publishable_key', '');
$stripe_test_secret_key = get_option('salon_booking_stripe_test_secret_key', '');
$deposit_percentage = get_option('salon_booking_deposit_percentage', 50);
$payment_required = get_option('salon_booking_payment_required', 1);

$admin_email = get_option('salon_booking_admin_email', get_option('admin_email'));
$from_name = get_option('salon_booking_from_name', get_bloginfo('name'));
$from_email = get_option('salon_booking_from_email', get_option('admin_email'));
$send_client_confirmation = get_option('salon_booking_send_client_confirmation', 1);
$send_admin_notification = get_option('salon_booking_send_admin_notification', 1);
$send_reminder_emails = get_option('salon_booking_send_reminder_emails', 1);
$reminder_hours = get_option('salon_booking_reminder_hours', 24);

$business_name = get_option('salon_booking_business_name', get_bloginfo('name'));
$business_address = get_option('salon_booking_business_address', '');
$business_phone = get_option('salon_booking_business_phone', '');
$business_email = get_option('salon_booking_business_email', get_option('admin_email'));
$business_website = get_option('salon_booking_business_website', home_url());
$cancellation_policy = get_option('salon_booking_cancellation_policy', 'Cancellations must be made at least 24 hours in advance.');

?>

<div class="wrap salon-booking-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        Salon Booking Settings
    </h1>
    
    <nav class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=salon-booking-settings&tab=general'); ?>" 
           class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-generic"></span>
            General
        </a>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-settings&tab=payment'); ?>" 
           class="nav-tab <?php echo $current_tab === 'payment' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-money-alt"></span>
            Payment
        </a>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-settings&tab=notifications'); ?>" 
           class="nav-tab <?php echo $current_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-email-alt"></span>
            Notifications
        </a>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-settings&tab=business'); ?>" 
           class="nav-tab <?php echo $current_tab === 'business' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-building"></span>
            Business Info
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($current_tab === 'general'): ?>
            <div class="settings-section">
                <h2>General Settings</h2>
                <p>Configure basic booking system settings.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('salon_booking_settings', 'salon_booking_settings_nonce'); ?>
                    <input type="hidden" name="tab" value="general">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Currency</th>
                            <td>
                                <select name="currency">
                                    <option value="ZAR" <?php selected($currency, 'ZAR'); ?>>South African Rand (R)</option>
                                    <option value="USD" <?php selected($currency, 'USD'); ?>>US Dollar ($)</option>
                                    <option value="EUR" <?php selected($currency, 'EUR'); ?>>Euro (€)</option>
                                    <option value="GBP" <?php selected($currency, 'GBP'); ?>>British Pound (£)</option>
                                </select>
                                <p class="description">The currency used for pricing and payments.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Time Format</th>
                            <td>
                                <select name="time_format">
                                    <option value="H:i" <?php selected($time_format, 'H:i'); ?>>24-hour (14:30)</option>
                                    <option value="g:i A" <?php selected($time_format, 'g:i A'); ?>>12-hour (2:30 PM)</option>
                                </select>
                                <p class="description">How times are displayed to clients.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Date Format</th>
                            <td>
                                <select name="date_format">
                                    <option value="Y-m-d" <?php selected($date_format, 'Y-m-d'); ?>>YYYY-MM-DD (2024-01-15)</option>
                                    <option value="d/m/Y" <?php selected($date_format, 'd/m/Y'); ?>>DD/MM/YYYY (15/01/2024)</option>
                                    <option value="m/d/Y" <?php selected($date_format, 'm/d/Y'); ?>>MM/DD/YYYY (01/15/2024)</option>
                                    <option value="F j, Y" <?php selected($date_format, 'F j, Y'); ?>>Month DD, YYYY (January 15, 2024)</option>
                                </select>
                                <p class="description">How dates are displayed to clients.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Booking Window (days)</th>
                            <td>
                                <input type="number" name="booking_window" value="<?php echo esc_attr($booking_window); ?>" 
                                       min="1" max="365" class="small-text">
                                <p class="description">How far in advance clients can book appointments.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Minimum Booking Time (hours)</th>
                            <td>
                                <input type="number" name="min_booking_time" value="<?php echo esc_attr($min_booking_time); ?>" 
                                       min="0" max="72" class="small-text">
                                <p class="description">Minimum time before appointment that bookings are allowed.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Maximum Booking Time (hours)</th>
                            <td>
                                <input type="number" name="max_booking_time" value="<?php echo esc_attr($max_booking_time); ?>" 
                                       min="1" max="8760" class="small-text">
                                <p class="description">Maximum time in advance that bookings are allowed.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Time Slot Interval (minutes)</th>
                            <td>
                                <select name="time_slot_interval">
                                    <option value="15" <?php selected($time_slot_interval, 15); ?>>15 minutes</option>
                                    <option value="30" <?php selected($time_slot_interval, 30); ?>>30 minutes</option>
                                    <option value="60" <?php selected($time_slot_interval, 60); ?>>60 minutes</option>
                                </select>
                                <p class="description">The interval between available booking time slots.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="Save General Settings">
                    </p>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'payment'): ?>
            <div class="settings-section">
                <h2>Payment Settings</h2>
                <p>Configure Stripe payment processing and deposit settings.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('salon_booking_settings', 'salon_booking_settings_nonce'); ?>
                    <input type="hidden" name="tab" value="payment">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Payment Required</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="payment_required" value="1" <?php checked($payment_required, 1); ?>>
                                    Require payment to complete booking
                                </label>
                                <p class="description">If unchecked, bookings can be made without payment.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Deposit Percentage</th>
                            <td>
                                <input type="number" name="deposit_percentage" value="<?php echo esc_attr($deposit_percentage); ?>" 
                                       min="0" max="100" step="0.01" class="small-text">%
                                <p class="description">Percentage of service price required as deposit (0-100%).</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Stripe Mode</th>
                            <td>
                                <select name="stripe_mode">
                                    <option value="test" <?php selected($stripe_mode, 'test'); ?>>Test Mode</option>
                                    <option value="live" <?php selected($stripe_mode, 'live'); ?>>Live Mode</option>
                                </select>
                                <p class="description">Use test mode for development and live mode for production.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Stripe Test Keys</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Test Publishable Key</th>
                            <td>
                                <input type="text" name="stripe_test_publishable_key" 
                                       value="<?php echo esc_attr($stripe_test_publishable_key); ?>" 
                                       class="regular-text" placeholder="pk_test_...">
                                <p class="description">Your Stripe test publishable key (starts with pk_test_).</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Test Secret Key</th>
                            <td>
                                <input type="password" name="stripe_test_secret_key" 
                                       value="<?php echo esc_attr($stripe_test_secret_key); ?>" 
                                       class="regular-text" placeholder="sk_test_...">
                                <p class="description">Your Stripe test secret key (starts with sk_test_).</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Stripe Live Keys</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Live Publishable Key</th>
                            <td>
                                <input type="text" name="stripe_live_publishable_key" 
                                       value="<?php echo esc_attr($stripe_live_publishable_key); ?>" 
                                       class="regular-text" placeholder="pk_live_...">
                                <p class="description">Your Stripe live publishable key (starts with pk_live_).</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Live Secret Key</th>
                            <td>
                                <input type="password" name="stripe_live_secret_key" 
                                       value="<?php echo esc_attr($stripe_live_secret_key); ?>" 
                                       class="regular-text" placeholder="sk_live_...">
                                <p class="description">Your Stripe live secret key (starts with sk_live_).</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="stripe-info">
                        <h4>Getting Your Stripe Keys</h4>
                        <ol>
                            <li>Sign up for a <a href="https://stripe.com" target="_blank">Stripe account</a></li>
                            <li>Go to your <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a></li>
                            <li>Copy your publishable and secret keys</li>
                            <li>Use test keys for development and live keys for production</li>
                        </ol>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="Save Payment Settings">
                    </p>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'notifications'): ?>
            <div class="settings-section">
                <h2>Email Notification Settings</h2>
                <p>Configure email notifications for bookings and reminders.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('salon_booking_settings', 'salon_booking_settings_nonce'); ?>
                    <input type="hidden" name="tab" value="notifications">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Admin Email</th>
                            <td>
                                <input type="email" name="admin_email" value="<?php echo esc_attr($admin_email); ?>" 
                                       class="regular-text" required>
                                <p class="description">Email address to receive booking notifications.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">From Name</th>
                            <td>
                                <input type="text" name="from_name" value="<?php echo esc_attr($from_name); ?>" 
                                       class="regular-text" required>
                                <p class="description">Name that appears in the "From" field of emails.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">From Email</th>
                            <td>
                                <input type="email" name="from_email" value="<?php echo esc_attr($from_email); ?>" 
                                       class="regular-text" required>
                                <p class="description">Email address that appears in the "From" field of emails.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Client Confirmation</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="send_client_confirmation" value="1" 
                                           <?php checked($send_client_confirmation, 1); ?>>
                                    Send confirmation email to clients
                                </label>
                                <p class="description">Automatically send booking confirmation to clients.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Admin Notification</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="send_admin_notification" value="1" 
                                           <?php checked($send_admin_notification, 1); ?>>
                                    Send notification email to admin
                                </label>
                                <p class="description">Automatically notify admin of new bookings.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Reminder Emails</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="send_reminder_emails" value="1" 
                                           <?php checked($send_reminder_emails, 1); ?>>
                                    Send reminder emails to clients
                                </label>
                                <p class="description">Automatically send appointment reminders.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Reminder Time</th>
                            <td>
                                <input type="number" name="reminder_hours" value="<?php echo esc_attr($reminder_hours); ?>" 
                                       min="1" max="168" class="small-text"> hours before appointment
                                <p class="description">How many hours before the appointment to send reminders.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="Save Notification Settings">
                    </p>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'business'): ?>
            <div class="settings-section">
                <h2>Business Information</h2>
                <p>Configure your business details for emails and client communications.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('salon_booking_settings', 'salon_booking_settings_nonce'); ?>
                    <input type="hidden" name="tab" value="business">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Business Name</th>
                            <td>
                                <input type="text" name="business_name" value="<?php echo esc_attr($business_name); ?>" 
                                       class="regular-text" required>
                                <p class="description">Your salon or business name.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Business Address</th>
                            <td>
                                <textarea name="business_address" rows="3" class="large-text"><?php echo esc_textarea($business_address); ?></textarea>
                                <p class="description">Your business address (used in emails and confirmations).</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Business Phone</th>
                            <td>
                                <input type="tel" name="business_phone" value="<?php echo esc_attr($business_phone); ?>" 
                                       class="regular-text">
                                <p class="description">Your business phone number.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Business Email</th>
                            <td>
                                <input type="email" name="business_email" value="<?php echo esc_attr($business_email); ?>" 
                                       class="regular-text" required>
                                <p class="description">Your business email address.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Business Website</th>
                            <td>
                                <input type="url" name="business_website" value="<?php echo esc_attr($business_website); ?>" 
                                       class="regular-text">
                                <p class="description">Your business website URL.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Cancellation Policy</th>
                            <td>
                                <textarea name="cancellation_policy" rows="4" class="large-text"><?php echo esc_textarea($cancellation_policy); ?></textarea>
                                <p class="description">Your cancellation policy (displayed to clients during booking).</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Trading Hours</h3>
                    <p class="description">Current trading hours: Sunday & Monday - Closed, Tuesday-Thursday 9am-6pm, Friday-Saturday 9am-3pm<br>
                    Location: 2N Circular Road, West End, Kimberley, South Africa</p>
                    <p class="description"><em>Note: Private appointments are available on request subject to additional charges.</em></p>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="Save Business Information">
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.salon-booking-admin {
    margin: 20px 0;
}

.nav-tab-wrapper {
    margin-bottom: 20px;
}

.nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.nav-tab .dashicons {
    font-size: 16px;
}

.tab-content {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.settings-section h2 {
    margin-top: 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.settings-section p {
    color: #666;
    margin-bottom: 20px;
}

.form-table th {
    width: 200px;
    font-weight: 500;
}

.form-table td {
    padding-bottom: 20px;
}

.form-table .description {
    margin-top: 5px;
    color: #666;
    font-style: italic;
}

.stripe-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 20px;
}

.stripe-info h4 {
    margin-top: 0;
    color: #333;
}

.stripe-info ol {
    margin-bottom: 0;
}

.stripe-info a {
    color: #d4af37;
    text-decoration: none;
}

.stripe-info a:hover {
    text-decoration: underline;
}

.submit {
    border-top: 1px solid #eee;
    padding-top: 20px;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .form-table th,
    .form-table td {
        display: block;
        width: 100%;
    }
    
    .form-table th {
        padding-bottom: 5px;
    }
    
    .form-table td {
        padding-top: 0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Validate Stripe keys format
    $('input[name*="stripe"]').on('blur', function() {
        const value = $(this).val().trim();
        const name = $(this).attr('name');
        
        if (value && name.includes('publishable') && !value.startsWith('pk_')) {
            alert('Publishable keys should start with "pk_"');
            $(this).focus();
        } else if (value && name.includes('secret') && !value.startsWith('sk_')) {
            alert('Secret keys should start with "sk_"');
            $(this).focus();
        }
    });
    
    // Validate email addresses
    $('input[type="email"]').on('blur', function() {
        const email = $(this).val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            alert('Please enter a valid email address.');
            $(this).focus();
        }
    });
    
    // Validate deposit percentage
    $('input[name="deposit_percentage"]').on('change', function() {
        const value = parseFloat($(this).val());
        
        if (value < 0) {
            $(this).val(0);
            alert('Deposit percentage cannot be negative.');
        } else if (value > 100) {
            $(this).val(100);
            alert('Deposit percentage cannot exceed 100%.');
        }
    });
    
    // Validate booking window
    $('input[name="booking_window"]').on('change', function() {
        const value = parseInt($(this).val());
        
        if (value < 1) {
            $(this).val(1);
            alert('Booking window must be at least 1 day.');
        } else if (value > 365) {
            $(this).val(365);
            alert('Booking window cannot exceed 365 days.');
        }
    });
    
    // Validate reminder hours
    $('input[name="reminder_hours"]').on('change', function() {
        const value = parseInt($(this).val());
        
        if (value < 1) {
            $(this).val(1);
            alert('Reminder time must be at least 1 hour.');
        } else if (value > 168) {
            $(this).val(168);
            alert('Reminder time cannot exceed 168 hours (1 week).');
        }
    });
    
    // Form validation before submit
    $('form').on('submit', function(e) {
        const requiredFields = $(this).find('input[required], textarea[required]');
        let isValid = true;
        
        requiredFields.each(function() {
            if (!$(this).val().trim()) {
                alert('Please fill in all required fields.');
                $(this).focus();
                isValid = false;
                return false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>