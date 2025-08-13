<?php

/**
 * Email Handler Class
 *
 * Handles all email notifications for the booking system
 *
 * @package    Salon_Booking_Plugin
 * @subpackage Salon_Booking_Plugin/includes
 */

class Salon_Booking_Email_Handler {

    /**
     * Plugin options
     */
    private $options;

    /**
     * Initialize the email handler
     */
    public function __construct() {
        $this->options = get_option('salon_booking_settings', array());
    }

    /**
     * Send booking confirmation email to client
     *
     * @param array $booking_data Booking information
     * @return bool
     */
    public function send_client_confirmation($booking_data) {
        // Get email template
        $template = Salon_Booking_Database::get_email_template('client_confirmation');
        if (!$template) {
            return false;
        }

        // Get service and staff details
        $service = Salon_Booking_Database::get_service($booking_data['service_id']);
        $staff = Salon_Booking_Database::get_staff_member($booking_data['staff_id']);

        if (!$service || !$staff) {
            return false;
        }

        // Prepare email data
        $to = $booking_data['client_email'];
        $subject = $this->replace_placeholders($template->subject, $booking_data, $service, $staff);
        $message = $this->replace_placeholders($template->content, $booking_data, $service, $staff);
        
        // Set headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>'
        );

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email sending
        $this->log_email_sent('client_confirmation', $to, $sent, $booking_data['id'] ?? null);

        return $sent;
    }

    /**
     * Send booking notification email to salon
     *
     * @param array $booking_data Booking information
     * @return bool
     */
    public function send_salon_notification($booking_data) {
        // Get email template
        $template = Salon_Booking_Database::get_email_template('salon_notification');
        if (!$template) {
            return false;
        }

        // Get service and staff details
        $service = Salon_Booking_Database::get_service($booking_data['service_id']);
        $staff = Salon_Booking_Database::get_staff_member($booking_data['staff_id']);

        if (!$service || !$staff) {
            return false;
        }

        // Prepare email data
        $to = $this->get_salon_email();
        $subject = $this->replace_placeholders($template->subject, $booking_data, $service, $staff);
        $message = $this->replace_placeholders($template->content, $booking_data, $service, $staff);
        
        // Set headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>'
        );

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email sending
        $this->log_email_sent('salon_notification', $to, $sent, $booking_data['id'] ?? null);

        return $sent;
    }

    /**
     * Send booking cancellation email to client
     *
     * @param array $booking_data Booking information
     * @return bool
     */
    public function send_cancellation_email($booking_data) {
        // Get email template
        $template = Salon_Booking_Database::get_email_template('booking_cancelled');
        if (!$template) {
            // Use default template if custom not found
            $template = $this->get_default_cancellation_template();
        }

        // Get service and staff details
        $service = Salon_Booking_Database::get_service($booking_data['service_id']);
        $staff = Salon_Booking_Database::get_staff_member($booking_data['staff_id']);

        if (!$service || !$staff) {
            return false;
        }

        // Prepare email data
        $to = $booking_data['client_email'];
        $subject = $this->replace_placeholders($template->subject, $booking_data, $service, $staff);
        $message = $this->replace_placeholders($template->content, $booking_data, $service, $staff);
        
        // Set headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>'
        );

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email sending
        $this->log_email_sent('booking_cancelled', $to, $sent, $booking_data['id'] ?? null);

        return $sent;
    }

    /**
     * Send booking reminder email to client
     *
     * @param array $booking_data Booking information
     * @return bool
     */
    public function send_reminder_email($booking_data) {
        // Get email template
        $template = Salon_Booking_Database::get_email_template('booking_reminder');
        if (!$template) {
            // Use default template if custom not found
            $template = $this->get_default_reminder_template();
        }

        // Get service and staff details
        $service = Salon_Booking_Database::get_service($booking_data['service_id']);
        $staff = Salon_Booking_Database::get_staff_member($booking_data['staff_id']);

        if (!$service || !$staff) {
            return false;
        }

        // Prepare email data
        $to = $booking_data['client_email'];
        $subject = $this->replace_placeholders($template->subject, $booking_data, $service, $staff);
        $message = $this->replace_placeholders($template->content, $booking_data, $service, $staff);
        
        // Set headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>'
        );

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email sending
        $this->log_email_sent('booking_reminder', $to, $sent, $booking_data['id'] ?? null);

        return $sent;
    }

    /**
     * Replace placeholders in email templates
     *
     * @param string $content Template content
     * @param array $booking_data Booking information
     * @param object $service Service object
     * @param object $staff Staff object
     * @return string
     */
    private function replace_placeholders($content, $booking_data, $service, $staff) {
        // Format date and time
        $booking_date = date('l, F j, Y', strtotime($booking_data['booking_date']));
        $booking_time = date('g:i A', strtotime($booking_data['booking_time']));
        
        // Calculate end time
        $end_time = date('g:i A', strtotime($booking_data['booking_time'] . ' + ' . $service->duration . ' minutes'));
        
        // Get currency symbol
        $currency_symbol = get_option('salon_booking_currency_symbol', 'R');
        
        // Calculate remaining balance
        $remaining_balance = $service->price - $service->upfront_fee;
        
        // Define placeholders
        $placeholders = array(
            '{client_name}' => $booking_data['client_name'],
            '{client_email}' => $booking_data['client_email'],
            '{client_phone}' => $booking_data['client_phone'] ?? '',
            '{service_name}' => $service->name,
            '{service_description}' => $service->description,
            '{service_duration}' => $service->duration . ' minutes',
            '{service_price}' => $currency_symbol . number_format($service->price, 2),
            '{upfront_fee}' => $currency_symbol . number_format($service->upfront_fee, 2),
            '{remaining_balance}' => $currency_symbol . number_format($remaining_balance, 2),
            '{staff_name}' => $staff->name,
            '{booking_date}' => $booking_date,
            '{booking_time}' => $booking_time,
            '{booking_end_time}' => $end_time,
            '{booking_datetime}' => $booking_date . ' at ' . $booking_time,
            '{booking_id}' => $booking_data['id'] ?? 'N/A',
            '{salon_name}' => $this->get_salon_name(),
            '{salon_address}' => $this->get_salon_address(),
            '{salon_phone}' => $this->get_salon_phone(),
            '{salon_email}' => $this->get_salon_email(),
            '{salon_website}' => home_url(),
            '{booking_notes}' => $booking_data['notes'] ?? '',
            '{current_date}' => date('F j, Y'),
            '{current_time}' => date('g:i A')
        );

        // Replace placeholders
        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
        
        // Add basic HTML structure if not present
        if (strpos($content, '<html>') === false) {
            $content = $this->wrap_in_html_template($content);
        }
        
        return $content;
    }

    /**
     * Wrap content in HTML email template
     *
     * @param string $content Email content
     * @return string
     */
    private function wrap_in_html_template($content) {
        $salon_name = $this->get_salon_name();
        $salon_address = $this->get_salon_address();
        $salon_phone = $this->get_salon_phone();
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - ' . esc_html($salon_name) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .content {
            background: #fff;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e0e0e0;
            border-top: none;
            font-size: 14px;
            color: #666;
        }
        .highlight {
            background: #fff9e6;
            padding: 15px;
            border-left: 4px solid #d4af37;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: #d4af37;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . esc_html($salon_name) . '</h1>
    </div>
    <div class="content">
        ' . $content . '
    </div>
    <div class="footer">
        <p><strong>' . esc_html($salon_name) . '</strong></p>
        <p>' . esc_html($salon_address) . '</p>
        <p>Phone: ' . esc_html($salon_phone) . '</p>
        <p>This email was sent automatically. Please do not reply to this email.</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Get default cancellation email template
     *
     * @return object
     */
    private function get_default_cancellation_template() {
        return (object) array(
            'subject' => 'Booking Cancelled - {salon_name}',
            'content' => '
                <h2>Booking Cancelled</h2>
                <p>Dear {client_name},</p>
                <p>We regret to inform you that your booking has been cancelled.</p>
                
                <div class="highlight">
                    <h3>Cancelled Booking Details:</h3>
                    <p><strong>Service:</strong> {service_name}</p>
                    <p><strong>Staff Member:</strong> {staff_name}</p>
                    <p><strong>Date & Time:</strong> {booking_datetime}</p>
                    <p><strong>Booking ID:</strong> {booking_id}</p>
                </div>
                
                <p>If you have any questions about this cancellation or would like to reschedule, please contact us.</p>
                
                <p>Thank you for your understanding.</p>
                
                <p>Best regards,<br>
                The {salon_name} Team</p>
            '
        );
    }

    /**
     * Get default reminder email template
     *
     * @return object
     */
    private function get_default_reminder_template() {
        return (object) array(
            'subject' => 'Appointment Reminder - {salon_name}',
            'content' => '
                <h2>Appointment Reminder</h2>
                <p>Dear {client_name},</p>
                <p>This is a friendly reminder about your upcoming appointment with us.</p>
                
                <div class="highlight">
                    <h3>Appointment Details:</h3>
                    <p><strong>Service:</strong> {service_name}</p>
                    <p><strong>Staff Member:</strong> {staff_name}</p>
                    <p><strong>Date & Time:</strong> {booking_datetime}</p>
                    <p><strong>Duration:</strong> {service_duration}</p>
                    <p><strong>Location:</strong> {salon_address}</p>
                </div>
                
                <p><strong>Important Reminders:</strong></p>
                <ul>
                    <li>Please arrive 10 minutes early for your appointment</li>
                    <li>Bring any relevant information about your preferences</li>
                    <li>Contact us if you need to reschedule or cancel</li>
                </ul>
                
                <p>We look forward to seeing you soon!</p>
                
                <p>Best regards,<br>
                The {salon_name} Team</p>
            '
        );
    }

    /**
     * Log email sending
     *
     * @param string $type Email type
     * @param string $to Recipient email
     * @param bool $sent Whether email was sent successfully
     * @param int $booking_id Booking ID
     */
    private function log_email_sent($type, $to, $sent, $booking_id = null) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'recipient' => $to,
            'status' => $sent ? 'sent' : 'failed',
            'booking_id' => $booking_id
        );
        
        // Store in WordPress options or custom table
        $email_logs = get_option('salon_booking_email_logs', array());
        $email_logs[] = $log_entry;
        
        // Keep only last 100 log entries
        if (count($email_logs) > 100) {
            $email_logs = array_slice($email_logs, -100);
        }
        
        update_option('salon_booking_email_logs', $email_logs);
    }

    /**
     * Get salon name
     *
     * @return string
     */
    private function get_salon_name() {
        return isset($this->options['salon_name']) ? $this->options['salon_name'] : get_bloginfo('name');
    }

    /**
     * Get salon email
     *
     * @return string
     */
    private function get_salon_email() {
        return isset($this->options['salon_email']) ? $this->options['salon_email'] : get_option('admin_email');
    }

    /**
     * Get salon phone
     *
     * @return string
     */
    private function get_salon_phone() {
        return isset($this->options['salon_phone']) ? $this->options['salon_phone'] : '';
    }

    /**
     * Get salon address
     *
     * @return string
     */
    private function get_salon_address() {
        return isset($this->options['salon_address']) ? $this->options['salon_address'] : '';
    }

    /**
     * Get from email
     *
     * @return string
     */
    private function get_from_email() {
        return isset($this->options['from_email']) ? $this->options['from_email'] : get_option('admin_email');
    }

    /**
     * Get from name
     *
     * @return string
     */
    private function get_from_name() {
        return isset($this->options['from_name']) ? $this->options['from_name'] : $this->get_salon_name();
    }

    /**
     * Test email configuration
     *
     * @param string $test_email Email to send test to
     * @return bool
     */
    public function send_test_email($test_email) {
        $subject = 'Test Email - ' . $this->get_salon_name();
        $message = $this->wrap_in_html_template('
            <h2>Test Email</h2>
            <p>This is a test email to verify that your email configuration is working correctly.</p>
            <p><strong>Sent at:</strong> ' . current_time('F j, Y g:i A') . '</p>
            <p>If you received this email, your email notifications are working properly.</p>
        ');
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>'
        );

        return wp_mail($test_email, $subject, $message, $headers);
    }

    /**
     * Get email logs
     *
     * @param int $limit Number of logs to retrieve
     * @return array
     */
    public function get_email_logs($limit = 50) {
        $logs = get_option('salon_booking_email_logs', array());
        return array_slice(array_reverse($logs), 0, $limit);
    }

    /**
     * Clear email logs
     *
     * @return bool
     */
    public function clear_email_logs() {
        return delete_option('salon_booking_email_logs');
    }

    /**
     * Schedule reminder emails
     *
     * @param array $booking_data Booking information
     */
    public function schedule_reminder_email($booking_data) {
        // Get reminder settings
        $reminder_hours = isset($this->options['reminder_hours']) ? intval($this->options['reminder_hours']) : 24;
        
        if ($reminder_hours > 0) {
            // Calculate reminder time (X hours before appointment)
            $appointment_time = strtotime($booking_data['booking_date'] . ' ' . $booking_data['booking_time']);
            $reminder_time = $appointment_time - ($reminder_hours * 3600);
            
            // Only schedule if reminder time is in the future
            if ($reminder_time > time()) {
                wp_schedule_single_event(
                    $reminder_time,
                    'salon_booking_send_reminder',
                    array($booking_data['id'])
                );
            }
        }
    }

    /**
     * Cancel scheduled reminder email
     *
     * @param int $booking_id Booking ID
     */
    public function cancel_reminder_email($booking_id) {
        wp_clear_scheduled_hook('salon_booking_send_reminder', array($booking_id));
    }
}