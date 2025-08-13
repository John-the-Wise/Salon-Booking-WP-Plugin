<?php

/**
 * Payment Handler Class
 *
 * Handles all payment processing using Stripe
 *
 * @package    Salon_Booking_Plugin
 * @subpackage Salon_Booking_Plugin/includes
 */

class Salon_Booking_Payment_Handler {

    /**
     * Stripe API instance
     */
    private $stripe;

    /**
     * Plugin options
     */
    private $options;

    /**
     * Initialize the payment handler
     */
    public function __construct() {
        $this->load_stripe_library();
        $this->options = get_option('salon_booking_settings', array());
        $this->init_stripe();
    }

    /**
     * Load Stripe PHP library
     */
    private function load_stripe_library() {
        if (!class_exists('\Stripe\Stripe')) {
            require_once plugin_dir_path(__FILE__) . '../vendor/stripe/stripe-php/init.php';
        }
    }

    /**
     * Initialize Stripe with API keys
     */
    private function init_stripe() {
        $stripe_mode = isset($this->options['stripe_mode']) ? $this->options['stripe_mode'] : 'test';
        
        if ($stripe_mode === 'live') {
            $secret_key = isset($this->options['stripe_live_secret_key']) ? $this->options['stripe_live_secret_key'] : '';
        } else {
            $secret_key = isset($this->options['stripe_test_secret_key']) ? $this->options['stripe_test_secret_key'] : '';
        }

        if (!empty($secret_key)) {
            \Stripe\Stripe::setApiKey($secret_key);
        }
    }

    /**
     * Create a payment intent
     *
     * @param float $amount Amount in the currency's smallest unit
     * @param string $currency Currency code
     * @param array $metadata Additional metadata
     * @return array|WP_Error
     */
    public function create_payment_intent($amount, $currency = 'zar', $metadata = array()) {
        try {
            // Convert amount to cents (Stripe expects smallest currency unit)
            $amount_cents = intval($amount * 100);

            $intent_data = array(
                'amount' => $amount_cents,
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'capture_method' => 'automatic',
                'confirmation_method' => 'manual',
                'confirm' => false
            );

            $intent = \Stripe\PaymentIntent::create($intent_data);

            return array(
                'success' => true,
                'payment_intent' => $intent,
                'client_secret' => $intent->client_secret
            );

        } catch (\Stripe\Exception\CardException $e) {
            return new WP_Error('card_error', $e->getError()->message);
        } catch (\Stripe\Exception\RateLimitException $e) {
            return new WP_Error('rate_limit', 'Too many requests made to the API too quickly');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return new WP_Error('invalid_request', 'Invalid parameters were supplied to Stripe\'s API');
        } catch (\Stripe\Exception\AuthenticationException $e) {
            return new WP_Error('authentication', 'Authentication with Stripe\'s API failed');
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            return new WP_Error('network_error', 'Network communication with Stripe failed');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new WP_Error('api_error', 'An error occurred with Stripe\'s API');
        } catch (Exception $e) {
            return new WP_Error('unknown_error', 'An unknown error occurred');
        }
    }

    /**
     * Confirm a payment intent
     *
     * @param string $payment_intent_id Payment intent ID
     * @param string $payment_method_id Payment method ID
     * @return array|WP_Error
     */
    public function confirm_payment_intent($payment_intent_id, $payment_method_id) {
        try {
            $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            $intent->confirm(array(
                'payment_method' => $payment_method_id,
                'return_url' => home_url('/booking-confirmation/')
            ));

            return array(
                'success' => true,
                'payment_intent' => $intent,
                'requires_action' => $intent->status === 'requires_action'
            );

        } catch (\Stripe\Exception\CardException $e) {
            return new WP_Error('card_error', $e->getError()->message);
        } catch (Exception $e) {
            return new WP_Error('payment_error', $e->getMessage());
        }
    }

    /**
     * Process a booking payment
     *
     * @param array $booking_data Booking information
     * @param string $payment_method_id Stripe payment method ID
     * @return array|WP_Error
     */
    public function process_booking_payment($booking_data, $payment_method_id) {
        // Validate required data
        if (empty($booking_data['amount']) || empty($booking_data['service_id'])) {
            return new WP_Error('invalid_data', 'Missing required booking data');
        }

        // Get service details for metadata
        $service = Salon_Booking_Database::get_service($booking_data['service_id']);
        if (!$service) {
            return new WP_Error('invalid_service', 'Invalid service selected');
        }

        // Prepare metadata
        $metadata = array(
            'booking_type' => 'salon_appointment',
            'service_id' => $booking_data['service_id'],
            'service_name' => $service->name,
            'staff_id' => $booking_data['staff_id'],
            'booking_date' => $booking_data['booking_date'],
            'booking_time' => $booking_data['booking_time'],
            'client_name' => $booking_data['client_name'],
            'client_email' => $booking_data['client_email']
        );

        // Create payment intent
        $intent_result = $this->create_payment_intent(
            $booking_data['amount'],
            $this->get_currency(),
            $metadata
        );

        if (is_wp_error($intent_result)) {
            return $intent_result;
        }

        // Confirm payment intent
        $confirm_result = $this->confirm_payment_intent(
            $intent_result['payment_intent']->id,
            $payment_method_id
        );

        if (is_wp_error($confirm_result)) {
            return $confirm_result;
        }

        // Check if payment requires additional action (3D Secure)
        if ($confirm_result['requires_action']) {
            return array(
                'success' => true,
                'requires_action' => true,
                'payment_intent' => $confirm_result['payment_intent'],
                'client_secret' => $confirm_result['payment_intent']->client_secret
            );
        }

        // Payment successful
        if ($confirm_result['payment_intent']->status === 'succeeded') {
            return array(
                'success' => true,
                'requires_action' => false,
                'payment_intent' => $confirm_result['payment_intent'],
                'transaction_id' => $confirm_result['payment_intent']->id
            );
        }

        return new WP_Error('payment_failed', 'Payment was not successful');
    }

    /**
     * Handle payment confirmation after 3D Secure
     *
     * @param string $payment_intent_id Payment intent ID
     * @return array|WP_Error
     */
    public function handle_payment_confirmation($payment_intent_id) {
        try {
            $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);

            if ($intent->status === 'succeeded') {
                return array(
                    'success' => true,
                    'payment_intent' => $intent,
                    'transaction_id' => $intent->id
                );
            } else {
                return new WP_Error('payment_not_completed', 'Payment was not completed successfully');
            }

        } catch (Exception $e) {
            return new WP_Error('confirmation_error', $e->getMessage());
        }
    }

    /**
     * Create a refund
     *
     * @param string $payment_intent_id Payment intent ID
     * @param float $amount Amount to refund (optional, full refund if not specified)
     * @param string $reason Reason for refund
     * @return array|WP_Error
     */
    public function create_refund($payment_intent_id, $amount = null, $reason = 'requested_by_customer') {
        try {
            $refund_data = array(
                'payment_intent' => $payment_intent_id,
                'reason' => $reason
            );

            if ($amount !== null) {
                $refund_data['amount'] = intval($amount * 100); // Convert to cents
            }

            $refund = \Stripe\Refund::create($refund_data);

            return array(
                'success' => true,
                'refund' => $refund,
                'refund_id' => $refund->id
            );

        } catch (Exception $e) {
            return new WP_Error('refund_error', $e->getMessage());
        }
    }

    /**
     * Get payment details
     *
     * @param string $payment_intent_id Payment intent ID
     * @return array|WP_Error
     */
    public function get_payment_details($payment_intent_id) {
        try {
            $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);

            return array(
                'success' => true,
                'payment_intent' => $intent,
                'status' => $intent->status,
                'amount' => $intent->amount / 100, // Convert from cents
                'currency' => strtoupper($intent->currency),
                'created' => $intent->created,
                'metadata' => $intent->metadata->toArray()
            );

        } catch (Exception $e) {
            return new WP_Error('retrieval_error', $e->getMessage());
        }
    }

    /**
     * Get publishable key for frontend
     *
     * @return string
     */
    public function get_publishable_key() {
        $stripe_mode = isset($this->options['stripe_mode']) ? $this->options['stripe_mode'] : 'test';
        
        if ($stripe_mode === 'live') {
            return isset($this->options['stripe_live_publishable_key']) ? $this->options['stripe_live_publishable_key'] : '';
        } else {
            return isset($this->options['stripe_test_publishable_key']) ? $this->options['stripe_test_publishable_key'] : '';
        }
    }

    /**
     * Get currency from settings
     *
     * @return string
     */
    private function get_currency() {
        return isset($this->options['currency']) ? strtolower($this->options['currency']) : 'zar';
    }

    /**
     * Validate webhook signature
     *
     * @param string $payload Webhook payload
     * @param string $signature Stripe signature header
     * @return bool|WP_Error
     */
    public function validate_webhook($payload, $signature) {
        $webhook_secret = isset($this->options['stripe_webhook_secret']) ? $this->options['stripe_webhook_secret'] : '';
        
        if (empty($webhook_secret)) {
            return new WP_Error('no_webhook_secret', 'Webhook secret not configured');
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $webhook_secret);
            return $event;
        } catch (\UnexpectedValueException $e) {
            return new WP_Error('invalid_payload', 'Invalid payload');
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new WP_Error('invalid_signature', 'Invalid signature');
        }
    }

    /**
     * Handle webhook events
     *
     * @param object $event Stripe event object
     * @return bool
     */
    public function handle_webhook_event($event) {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                return $this->handle_payment_succeeded($event->data->object);
            
            case 'payment_intent.payment_failed':
                return $this->handle_payment_failed($event->data->object);
            
            case 'charge.dispute.created':
                return $this->handle_dispute_created($event->data->object);
            
            default:
                // Unhandled event type
                return true;
        }
    }

    /**
     * Handle successful payment webhook
     *
     * @param object $payment_intent Stripe payment intent object
     * @return bool
     */
    private function handle_payment_succeeded($payment_intent) {
        // Update booking status if needed
        $metadata = $payment_intent->metadata->toArray();
        
        if (isset($metadata['booking_id'])) {
            $booking_id = $metadata['booking_id'];
            
            // Update booking payment status
            Salon_Booking_Database::update_booking_payment_status(
                $booking_id,
                'paid',
                $payment_intent->id
            );
        }

        return true;
    }

    /**
     * Handle failed payment webhook
     *
     * @param object $payment_intent Stripe payment intent object
     * @return bool
     */
    private function handle_payment_failed($payment_intent) {
        // Update booking status if needed
        $metadata = $payment_intent->metadata->toArray();
        
        if (isset($metadata['booking_id'])) {
            $booking_id = $metadata['booking_id'];
            
            // Update booking payment status
            Salon_Booking_Database::update_booking_payment_status(
                $booking_id,
                'failed',
                $payment_intent->id
            );
        }

        return true;
    }

    /**
     * Handle dispute created webhook
     *
     * @param object $charge Stripe charge object
     * @return bool
     */
    private function handle_dispute_created($charge) {
        // Log dispute for admin attention
        error_log('Stripe dispute created for charge: ' . $charge->id);
        
        // You could send an email notification to admin here
        
        return true;
    }

    /**
     * Test Stripe connection
     *
     * @return array
     */
    public function test_connection() {
        try {
            // Try to retrieve account information
            $account = \Stripe\Account::retrieve();
            
            return array(
                'success' => true,
                'message' => 'Stripe connection successful',
                'account_id' => $account->id,
                'country' => $account->country,
                'currency' => $account->default_currency
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Stripe connection failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Format amount for display
     *
     * @param float $amount Amount to format
     * @return string
     */
    public function format_amount($amount) {
        $currency_symbol = get_option('salon_booking_currency_symbol', 'R');
        return $currency_symbol . number_format($amount, 2);
    }

    /**
     * Get supported currencies
     *
     * @return array
     */
    public function get_supported_currencies() {
        return array(
            'zar' => 'South African Rand (R)',
            'usd' => 'US Dollar ($)',
            'eur' => 'Euro (€)',
            'gbp' => 'British Pound (£)',
            'aud' => 'Australian Dollar (A$)',
            'cad' => 'Canadian Dollar (C$)'
        );
    }
}