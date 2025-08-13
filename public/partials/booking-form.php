<?php
/**
 * Provide a public-facing view for the booking form
 *
 * This file is used to markup the public-facing aspects of the plugin.
 */

// Get staff to check if we have multiple staff members
$staff = Salon_Booking_Database::get_staff(true);
$currency_symbol = get_option('salon_booking_currency_symbol', 'R');

// Check if we have multiple staff members
$has_multiple_staff = count($staff) > 1;
?>

<div class="salon-booking-container">
    <div class="salon-booking-header">
        <h2 class="salon-booking-title">Book Your Appointment</h2>
        <p class="salon-booking-subtitle">Experience personalized beauty services designed to enhance your natural glow</p>
    </div>

    <form id="salon-booking-form" class="salon-booking-form">
        <!-- Step 1: Service Selection -->
        <div class="booking-step active" id="step-service">
            <div class="step-header">
                <h3>Select Your Service</h3>
                <p>Choose from our range of beauty treatments</p>
            </div>
            
            <div class="services-grid">
                <div class="services-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading services...</p>
                </div>
            </div>
            

            
            <div class="step-navigation">
                <?php if ($has_multiple_staff): ?>
                    <button type="button" class="btn-next" id="btn-next-staff" disabled>Next: Select Staff</button>
                <?php else: ?>
                    <button type="button" class="btn-next" id="btn-next-datetime" disabled>Next: Select Date & Time</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: Staff Selection (only shown if multiple staff) -->
        <div class="booking-step" id="step-staff" style="display: none;">
            <div class="step-header">
                <h3>Select Your Preferred Staff Member</h3>
                <p>Choose who you'd like to provide your service</p>
            </div>
            
            <div class="staff-grid">
                <div class="staff-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading staff...</p>
                </div>
            </div>
            
            <div class="step-navigation">
                <button type="button" class="btn-prev" id="btn-prev-service">Previous</button>
                <button type="button" class="btn-next" id="btn-next-datetime" disabled>Next: Select Date & Time</button>
            </div>
        </div>
        
        <!-- Hidden fields for staff data -->
        <input type="hidden" id="staff_data" value="<?php echo esc_attr(json_encode($staff)); ?>">
        <input type="hidden" id="has_multiple_staff" value="<?php echo $has_multiple_staff ? '1' : '0'; ?>">

        <!-- Step 3: Date & Time Selection -->
        <div class="booking-step" id="step-datetime">
            <div class="step-header">
                <h3>Select Date & Time</h3>
                <p>Choose your preferred appointment date and time</p>
            </div>
            
            <div class="datetime-container">
                <div class="date-selection">
                    <h4>Select Date</h4>
                    <div id="booking-calendar"></div>
                </div>
                
                <div class="time-selection">
                    <h4>Available Times</h4>
                    <div id="available-times" class="time-slots">
                        <p class="select-date-first">Please select a date first</p>
                    </div>
                </div>
            </div>
            
            <div class="step-navigation">
                <button type="button" class="btn-prev" id="btn-prev-staff">Previous</button>
                <button type="button" class="btn-next" id="btn-next-details" disabled>Next: Your Details</button>
            </div>
        </div>

        <!-- Step 4: Client Details -->
        <div class="booking-step" id="step-details">
            <div class="step-header">
                <h3>Your Details</h3>
                <p>Please provide your contact information</p>
            </div>
            
            <div class="client-details-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Full Name *</label>
                        <input type="text" id="client_name" name="client_name" required>
                    </div>
                    <div class="form-group">
                        <label for="client_email">Email Address *</label>
                        <input type="email" id="client_email" name="client_email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_phone">Phone Number</label>
                        <input type="tel" id="client_phone" name="client_phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Special Requests or Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Any special requests or information we should know about?"></textarea>
                </div>
            </div>
            
            <div class="step-navigation">
                <button type="button" class="btn-prev" id="btn-prev-datetime">Previous</button>
                <button type="button" class="btn-next" id="btn-next-payment" disabled>Next: Payment</button>
            </div>
        </div>

        <!-- Step 5: Payment -->
        <div class="booking-step" id="step-payment">
            <div class="step-header">
                <h3>Secure Payment</h3>
                <p>Complete your booking with a secure upfront payment</p>
            </div>
            
            <div class="booking-summary">
                <h4>Booking Summary</h4>
                <div class="summary-item">
                    <span class="label">Service:</span>
                    <span class="value" id="summary-service">-</span>
                </div>
                <div class="summary-item">
                    <span class="label">Staff:</span>
                    <span class="value" id="summary-staff">-</span>
                </div>
                <div class="summary-item">
                    <span class="label">Date & Time:</span>
                    <span class="value" id="summary-datetime">-</span>
                </div>
                <div class="summary-item">
                    <span class="label">Duration:</span>
                    <span class="value" id="summary-duration">-</span>
                </div>
                <div class="summary-item total">
                    <span class="label">Total Service Cost:</span>
                    <span class="value" id="summary-total">-</span>
                </div>
                <div class="summary-item upfront">
                    <span class="label">Upfront Fee (Due Now):</span>
                    <span class="value" id="summary-upfront">-</span>
                </div>
                <div class="summary-item remaining">
                    <span class="label">Remaining Balance:</span>
                    <span class="value" id="summary-remaining">-</span>
                </div>
            </div>
            
            <div class="payment-form">
                <h4>Payment Information</h4>
                <div id="card-element" class="card-element">
                    <!-- Stripe Elements will create form elements here -->
                </div>
                <div id="card-errors" class="card-errors" role="alert"></div>
            </div>
            
            <div class="payment-info">
                <p><i class="icon-lock"></i> Your payment information is secure and encrypted</p>
                <p><small>The upfront fee is non-refundable and secures your appointment. The remaining balance will be due at the time of service.</small></p>
            </div>
            
            <div class="step-navigation">
                <button type="button" class="btn-prev" id="btn-prev-details">Previous</button>
                <button type="submit" class="btn-confirm-booking" id="btn-confirm-booking" disabled>
                    <span class="btn-text">Confirm Booking & Pay</span>
                    <span class="btn-loading" style="display: none;">Processing...</span>
                </button>
            </div>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" id="selected_service_id" name="service_id">
        <input type="hidden" id="selected_staff_id" name="staff_id">
        <input type="hidden" id="selected_date" name="booking_date">
        <input type="hidden" id="selected_time" name="booking_time">
        <input type="hidden" id="booking_duration" name="duration">
        <input type="hidden" id="total_amount" name="total_amount">
        <input type="hidden" id="upfront_fee" name="upfront_fee">
    </form>

    <!-- Success Message -->
    <div id="booking-success" class="booking-success" style="display: none;">
        <div class="success-content">
            <div class="success-icon">✓</div>
            <h3>Booking Confirmed!</h3>
            <p>Thank you for booking with UR Beautiful. You will receive a confirmation email shortly with all the details.</p>
            <div class="success-details">
                <p><strong>What's next?</strong></p>
                <ul>
                    <li>Check your email for confirmation details</li>
                    <li>Add the appointment to your calendar</li>
                    <li>Arrive 10 minutes early for your appointment</li>
                </ul>
            </div>
            <div class="salon-contact">
                <p><strong>Location:</strong> 2N Circular Road, West End, Kimberley, South Africa</p>
                <p><strong>Questions?</strong> Contact us at the salon</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Inline styles for immediate styling - will be moved to CSS file */
.salon-booking-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Roboto', Arial, sans-serif;
}

.salon-booking-header {
    text-align: center;
    margin-bottom: 40px;
}

.salon-booking-title {
    font-size: 2.5em;
    color: #d4af37;
    margin-bottom: 10px;
    font-weight: 300;
}

.salon-booking-subtitle {
    color: #666;
    font-size: 1.1em;
    margin: 0;
}

.booking-step {
    display: none;
    animation: fadeIn 0.3s ease-in;
}

.booking-step.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.step-header {
    text-align: center;
    margin-bottom: 30px;
}

.step-header h3 {
    color: #333;
    font-size: 1.8em;
    margin-bottom: 10px;
}

.step-header p {
    color: #666;
    margin: 0;
}

.services-grid .service-category {
    margin-bottom: 30px;
}

.category-title {
    color: #d4af37;
    font-size: 1.3em;
    margin-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 5px;
}

.category-services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.service-card {
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.service-card:hover {
    border-color: #d4af37;
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
}

.service-card.selected {
    border-color: #d4af37;
    background-color: #fefdf8;
}

.service-name {
    color: #333;
    font-size: 1.2em;
    margin-bottom: 8px;
}

.service-description {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.4;
}

.service-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.service-duration {
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.service-price {
    font-weight: bold;
    color: #d4af37;
    font-size: 1.1em;
}

.service-upfront {
    color: #666;
    font-size: 0.9em;
}

.btn-select-service {
    background: #d4af37;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
    width: 100%;
    margin-top: 10px;
}

.btn-select-service:hover {
    background: #b8941f;
}

.staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.staff-card {
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.staff-card:hover {
    border-color: #d4af37;
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
}

.staff-card.selected {
    border-color: #d4af37;
    background-color: #fefdf8;
}

.staff-name {
    color: #333;
    margin-bottom: 10px;
}

.staff-badge {
    background: #d4af37;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    margin-left: 10px;
}

.staff-specialties {
    color: #666;
    font-size: 0.9em;
    margin-top: 10px;
}

.btn-select-staff {
    background: #d4af37;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
    width: 100%;
    margin-top: 15px;
}

.btn-select-staff:hover {
    background: #b8941f;
}

.step-navigation {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}

.btn-prev, .btn-next, .btn-confirm-booking {
    background: #d4af37;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1em;
    margin: 0 10px;
    transition: all 0.3s ease;
}

.btn-prev {
    background: #6c757d;
}

.btn-prev:hover {
    background: #5a6268;
}

.btn-next:hover, .btn-confirm-booking:hover {
    background: #b8941f;
}

.btn-next:disabled, .btn-confirm-booking:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.datetime-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .datetime-container {
        grid-template-columns: 1fr;
    }
}

.time-slots {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
}

.time-slot {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    padding: 10px;
    text-align: center;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.time-slot:hover {
    border-color: #d4af37;
}

.time-slot.selected {
    background: #d4af37;
    color: white;
    border-color: #d4af37;
}

.client-details-form {
    max-width: 500px;
    margin: 0 auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-weight: 500;
}

.form-group input, .form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 5px;
    font-size: 1em;
    transition: border-color 0.3s ease;
}

.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: #d4af37;
}

.booking-summary {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.booking-summary h4 {
    color: #333;
    margin-bottom: 15px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 5px;
}

.summary-item.total {
    border-top: 1px solid #dee2e6;
    padding-top: 10px;
    font-weight: bold;
    font-size: 1.1em;
}

.summary-item.upfront {
    color: #d4af37;
    font-weight: bold;
}

.summary-item.remaining {
    color: #666;
    font-size: 0.9em;
}

.payment-form {
    margin-bottom: 20px;
}

.card-element {
    background: white;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 5px;
    margin-bottom: 10px;
}

.card-errors {
    color: #dc3545;
    font-size: 0.9em;
    margin-top: 5px;
}

.payment-info {
    background: #e8f5e8;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.payment-info p {
    margin: 5px 0;
    color: #155724;
}

.booking-success {
    text-align: center;
    padding: 40px 20px;
}

.success-icon {
    font-size: 4em;
    color: #28a745;
    margin-bottom: 20px;
}

.success-content h3 {
    color: #333;
    margin-bottom: 15px;
}

.success-details {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    text-align: left;
}

.success-details ul {
    margin: 10px 0;
    padding-left: 20px;
}

.salon-contact {
    background: #fefdf8;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #d4af37;
}

/* Floating Continue Button */
.floating-continue {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border: 2px solid #d4af37;
    animation: slideUp 0.3s ease-out;
}

.floating-continue-content {
    padding: 20px;
    text-align: center;
    min-width: 280px;
}

.selected-service-info {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.selected-service-name {
    display: block;
    font-weight: bold;
    color: #333;
    font-size: 1.1em;
    margin-bottom: 5px;
}

.selected-service-price {
    display: block;
    color: #d4af37;
    font-weight: bold;
    font-size: 1.2em;
}

.btn-floating-continue {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1em;
    width: 100%;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.btn-floating-continue:hover {
    background: linear-gradient(135deg, #b8941f, #a08019);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
}

@keyframes slideUp {
    from {
        transform: translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .floating-continue {
        bottom: 10px;
        right: 10px;
        left: 10px;
    }
    
    .floating-continue-content {
        min-width: auto;
        padding: 15px;
    }
}
</style>

<script>
// Initialize booking form data for JavaScript access
if (typeof window.salonBookingData === 'undefined') {
    window.salonBookingData = {
        hasMultipleStaff: <?php echo $has_multiple_staff ? 'true' : 'false'; ?>,
        staffData: <?php echo json_encode($staff); ?>,
        currencySymbol: <?php echo json_encode($currency_symbol); ?>
    };
}
</script>