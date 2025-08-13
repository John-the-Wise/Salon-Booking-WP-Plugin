<?php

/**
 * Provide an admin area view for managing services
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salon_booking_nonce'])) {
    if (wp_verify_nonce($_POST['salon_booking_nonce'], 'salon_booking_admin')) {
        if ($action === 'add' || $action === 'edit') {
            $service_data = array(
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'duration' => intval($_POST['duration']),
                'price' => floatval($_POST['price']),
                'upfront_fee' => floatval($_POST['upfront_fee']),
                'category' => sanitize_text_field($_POST['category']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            );
            
            // Validate that upfront fee doesn't exceed total price
            if ($service_data['upfront_fee'] > $service_data['price']) {
                echo '<div class="notice notice-error"><p>Error: Upfront fee cannot exceed the total service price.</p></div>';
            } else {
                if ($action === 'add') {
                    $result = Salon_Booking_Database::save_service($service_data);
                    if ($result) {
                        echo '<div class="notice notice-success"><p>Service added successfully!</p></div>';
                        $action = 'list';
                    } else {
                        echo '<div class="notice notice-error"><p>Error adding service. Please try again.</p></div>';
                    }
                } elseif ($action === 'edit') {
                    $result = Salon_Booking_Database::save_service($service_data, $service_id);
                    if ($result) {
                        echo '<div class="notice notice-success"><p>Service updated successfully!</p></div>';
                        $action = 'list';
                    } else {
                        echo '<div class="notice notice-error"><p>Error updating service. Please try again.</p></div>';
                    }
                }
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $service_id && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_service_' . $service_id)) {
        // Check if service has bookings
        $bookings_count = Salon_Booking_Database::get_bookings_count(null, null, $service_id);
        if ($bookings_count > 0) {
            echo '<div class="notice notice-error"><p>Cannot delete service. It has existing bookings. Please deactivate instead.</p></div>';
        } else {
            $result = Salon_Booking_Database::delete_service($service_id);
            if ($result) {
                echo '<div class="notice notice-success"><p>Service deleted successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error deleting service. Please try again.</p></div>';
            }
        }
        $action = 'list';
    }
}

// Handle toggle active status
if ($action === 'toggle_active' && $service_id && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'toggle_active_' . $service_id)) {
        $service = Salon_Booking_Database::get_service($service_id);
        if ($service) {
            $new_status = $service->is_active ? 0 : 1;
            $result = Salon_Booking_Database::save_service(array(
                'id' => $service_id,
                'name' => $service->name,
                'description' => $service->description,
                'duration' => $service->duration,
                'price' => $service->price,
                'category' => $service->category,
                'is_active' => $new_status
            ));
            
            if ($result) {
                $status_text = $new_status ? 'activated' : 'deactivated';
                echo '<div class="notice notice-success"><p>Service ' . $status_text . ' successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error updating service status. Please try again.</p></div>';
            }
        }
        $action = 'list';
    }
}

if ($action === 'edit' && $service_id) {
    $service = Salon_Booking_Database::get_service($service_id);
    if (!$service) {
        echo '<div class="notice notice-error"><p>Service not found.</p></div>';
        $action = 'list';
    }
}

?>

<div class="wrap salon-booking-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-tools"></span>
        Manage Services
    </h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-services&action=add'); ?>" class="page-title-action">Add New Service</a>
        
        <div class="salon-booking-services">
            <?php
            $services = Salon_Booking_Database::get_services(true); // Include inactive
            $categories = array();
            
            // Group services by category
            foreach ($services as $service) {
                $category = !empty($service->category) ? $service->category : 'Uncategorized';
                if (!isset($categories[$category])) {
                    $categories[$category] = array();
                }
                $categories[$category][] = $service;
            }
            ?>
            
            <?php if (!empty($services)): ?>
                <div class="services-overview">
                    <div class="overview-stats">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($services); ?></div>
                            <div class="stat-label">Total Services</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count(array_filter($services, function($s) { return $s->is_active; })); ?></div>
                            <div class="stat-label">Active Services</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($categories); ?></div>
                            <div class="stat-label">Categories</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">R<?php echo number_format(array_sum(array_map(function($s) { return $s->price; }, array_filter($services, function($s) { return $s->is_active; }))), 2); ?></div>
                            <div class="stat-label">Total Value</div>
                        </div>
                    </div>
                </div>
                
                <div class="services-by-category">
                    <?php foreach ($categories as $category_name => $category_services): ?>
                        <div class="category-section">
                            <div class="category-header">
                                <h3><?php echo esc_html($category_name); ?></h3>
                                <span class="category-count"><?php echo count($category_services); ?> service<?php echo count($category_services) !== 1 ? 's' : ''; ?></span>
                            </div>
                            
                            <div class="services-grid">
                                <?php foreach ($category_services as $service): 
                                    $bookings_count = Salon_Booking_Database::get_bookings_count(null, null, $service->id);
                                    $upcoming_bookings = Salon_Booking_Database::get_bookings_count(date('Y-m-d'), null, $service->id);
                                ?>
                                    <div class="service-card <?php echo $service->is_active ? 'active' : 'inactive'; ?>">
                                        <div class="service-header">
                                            <div class="service-info">
                                                <h4><?php echo esc_html($service->name); ?></h4>
                                                <div class="service-meta">
                                                    <span class="duration">
                                                        <span class="dashicons dashicons-clock"></span>
                                                        <?php echo $service->duration; ?> min
                                                    </span>
                                                    <span class="price">
                                                        <span class="dashicons dashicons-money-alt"></span>
                                                        R<?php echo number_format($service->price, 2); ?>
                                                    </span>
                                                    <span class="upfront-fee">
                                                        <span class="dashicons dashicons-admin-network"></span>
                                                        Upfront: R<?php echo number_format($service->upfront_fee, 2); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="service-status">
                                                <span class="status-badge <?php echo $service->is_active ? 'active' : 'inactive'; ?>">
                                                    <?php echo $service->is_active ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($service->description)): ?>
                                            <div class="service-description">
                                                <p><?php echo esc_html($service->description); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="service-stats">
                                            <div class="stat-item">
                                                <span class="stat-number"><?php echo $bookings_count; ?></span>
                                                <span class="stat-label">Total Bookings</span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-number"><?php echo $upcoming_bookings; ?></span>
                                                <span class="stat-label">Upcoming</span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-number">R<?php echo number_format($bookings_count * $service->price, 2); ?></span>
                                                <span class="stat-label">Revenue</span>
                                            </div>
                                        </div>
                                        
                                        <div class="service-actions">
                                            <a href="<?php echo admin_url('admin.php?page=salon-booking-services&action=edit&service_id=' . $service->id); ?>" 
                                               class="button button-secondary">Edit</a>
                                            
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=salon-booking-services&action=toggle_active&service_id=' . $service->id), 'toggle_active_' . $service->id); ?>" 
                                               class="button <?php echo $service->is_active ? 'deactivate-btn' : 'activate-btn'; ?>">
                                                <?php echo $service->is_active ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                            
                                            <?php if ($bookings_count === 0): ?>
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=salon-booking-services&action=delete&service_id=' . $service->id), 'delete_service_' . $service->id); ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this service? This action cannot be undone.')" 
                                                   class="button delete-btn">Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <div class="no-services">
                    <div class="no-services-content">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <h3>No Services Found</h3>
                        <p>Add your first service to start accepting bookings.</p>
                        <a href="<?php echo admin_url('admin.php?page=salon-booking-services&action=add'); ?>" class="button button-primary">Add Service</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <a href="<?php echo admin_url('admin.php?page=salon-booking-services'); ?>" class="page-title-action">← Back to Services</a>
        
        <div class="salon-booking-form">
            <form method="post" action="">
                <?php wp_nonce_field('salon_booking_admin', 'salon_booking_nonce'); ?>
                
                <div class="form-grid">
                    <div class="form-section basic-info">
                        <h3>Service Information</h3>
                        
                        <div class="form-row">
                            <label for="name">Service Name *</label>
                            <input type="text" name="name" id="name" 
                                   value="<?php echo esc_attr(isset($service) ? $service->name : ''); ?>" 
                                   placeholder="e.g., Hair Cut & Style" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="category">Category</label>
                            <input type="text" name="category" id="category" 
                                   value="<?php echo esc_attr(isset($service) ? $service->category : ''); ?>" 
                                   placeholder="e.g., Hair Services, Nail Care, Facial Treatments"
                                   list="category-suggestions">
                            <datalist id="category-suggestions">
                                <option value="Hair Services">
                                <option value="Nail Care">
                                <option value="Facial Treatments">
                                <option value="Body Treatments">
                                <option value="Makeup Services">
                                <option value="Eyebrow & Lash Services">
                                <option value="Massage Therapy">
                                <option value="Skin Care">
                            </datalist>
                            <small class="form-help">Group similar services together for better organization.</small>
                        </div>
                        
                        <div class="form-row">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="4" 
                                      placeholder="Describe what this service includes..."><?php echo esc_textarea(isset($service) ? $service->description : ''); ?></textarea>
                            <small class="form-help">This will be displayed to clients when they select a service.</small>
                        </div>
                        
                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" id="is_active" 
                                       <?php checked(isset($service) ? $service->is_active : 1, 1); ?>>
                                <span class="checkmark"></span>
                                Active (available for booking)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-section pricing-info">
                        <h3>Pricing & Duration</h3>
                        
                        <div class="form-row">
                            <label for="duration">Duration (minutes) *</label>
                            <input type="number" name="duration" id="duration" 
                                   value="<?php echo esc_attr(isset($service) ? $service->duration : '60'); ?>" 
                                   min="15" max="480" step="15" required>
                            <small class="form-help">How long does this service typically take? (15-480 minutes)</small>
                        </div>
                        
                        <div class="form-row">
                            <label for="price">Total Service Price (R) *</label>
                            <input type="number" name="price" id="price" 
                                   value="<?php echo esc_attr(isset($service) ? $service->price : ''); ?>" 
                                   min="0" step="0.01" placeholder="0.00" required>
                            <small class="form-help">The full price clients will pay for this service.</small>
                        </div>
                        
                        <div class="form-row">
                            <label for="upfront_fee">Upfront Fee (R) *</label>
                            <input type="number" name="upfront_fee" id="upfront_fee" 
                                   value="<?php echo esc_attr(isset($service) ? $service->upfront_fee : ''); ?>" 
                                   min="0" step="0.01" placeholder="0.00" required>
                            <small class="form-help">The amount clients pay when booking (non-refundable deposit).</small>
                        </div>
                        
                        <div class="pricing-preview">
                            <h4>Pricing Preview</h4>
                            <div class="preview-item">
                                <span class="preview-label">Service Duration:</span>
                                <span class="preview-value" id="duration-preview">60 minutes</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Total Service Price:</span>
                                <span class="preview-value" id="price-preview">R0.00</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Upfront Fee (Due at Booking):</span>
                                <span class="preview-value" id="upfront-preview">R0.00</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Remaining Balance (Due at Service):</span>
                                <span class="preview-value" id="remaining-preview">R0.00</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Price per minute:</span>
                                <span class="preview-value" id="rate-preview">R0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo $action === 'add' ? 'Add Service' : 'Update Service'; ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=salon-booking-services'); ?>" class="button">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.salon-booking-admin {
    margin: 20px 0;
}

/* Services Overview */
.services-overview {
    margin: 20px 0;
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card .stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #d4af37;
    display: block;
    margin-bottom: 5px;
}

.stat-card .stat-label {
    color: #666;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Category Sections */
.services-by-category {
    margin-top: 30px;
}

.category-section {
    margin-bottom: 40px;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #d4af37;
}

.category-header h3 {
    margin: 0;
    color: #333;
    font-size: 20px;
}

.category-count {
    background: #d4af37;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

/* Services Grid */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.service-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.service-card.inactive {
    opacity: 0.7;
    border-color: #ccc;
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.service-info {
    flex: 1;
}

.service-info h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 18px;
}

.service-meta {
    display: flex;
    gap: 15px;
    color: #666;
    font-size: 14px;
    flex-wrap: wrap;
}

.service-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.service-meta .dashicons {
    font-size: 16px;
}

.service-meta .upfront-fee {
    color: #d4af37;
    font-weight: 500;
}

.service-status {
    margin-left: 15px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.service-description {
    margin-bottom: 15px;
}

.service-description p {
    margin: 0;
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.service-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-item .stat-number {
    display: block;
    font-size: 16px;
    font-weight: bold;
    color: #d4af37;
}

.stat-item .stat-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
}

.service-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.service-actions .button {
    font-size: 12px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.4;
}

.activate-btn {
    background: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.deactivate-btn {
    background: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #212529 !important;
}

.delete-btn {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
    color: white !important;
}

/* No Services State */
.no-services {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
    margin-top: 50px;
}

.no-services-content {
    text-align: center;
    color: #666;
}

.no-services-content .dashicons {
    font-size: 48px;
    margin-bottom: 20px;
    color: #ccc;
}

.no-services-content h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.no-services-content p {
    margin: 0 0 20px 0;
}

/* Form Styles */
.salon-booking-form {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    max-width: 800px;
    margin-top: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-section {
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 20px;
}

.form-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.form-row {
    margin-bottom: 15px;
}

.form-row label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
}

.form-row input,
.form-row textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-row input:focus,
.form-row textarea:focus {
    border-color: #d4af37;
    outline: none;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}

.form-help {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
    font-style: italic;
}

.checkbox-label {
    display: flex !important;
    align-items: center;
    cursor: pointer;
    font-weight: normal !important;
}

.checkbox-label input[type="checkbox"] {
    width: auto !important;
    margin-right: 8px;
}

/* Pricing Preview */
.pricing-preview {
    background: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 15px;
    margin-top: 20px;
}

/* Form validation styles */
.form-row input.error {
    border-color: #d63638;
    box-shadow: 0 0 0 1px #d63638;
}

.form-row input.error:focus {
    border-color: #d63638;
    box-shadow: 0 0 0 2px rgba(214, 54, 56, 0.2);
}

.pricing-preview h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 14px;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 13px;
}

.preview-label {
    color: #666;
}

.preview-value {
    font-weight: 500;
    color: #333;
}

.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .service-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .service-status {
        margin-left: 0;
        margin-top: 10px;
    }
    
    .service-meta {
        flex-direction: column;
        gap: 5px;
    }
    
    .service-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .service-actions {
        justify-content: flex-start;
    }
    
    .overview-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Update pricing preview
    function updatePricingPreview() {
        const duration = parseInt($('#duration').val()) || 0;
        const price = parseFloat($('#price').val()) || 0;
        const upfrontFee = parseFloat($('#upfront_fee').val()) || 0;
        const remainingBalance = price - upfrontFee;
        const rate = duration > 0 ? price / duration : 0;
        
        $('#duration-preview').text(duration + ' minutes');
        $('#price-preview').text('R' + price.toFixed(2));
        $('#upfront-preview').text('R' + upfrontFee.toFixed(2));
        $('#remaining-preview').text('R' + remainingBalance.toFixed(2));
        $('#rate-preview').text('R' + rate.toFixed(2));
        
        // Validate that upfront fee doesn't exceed total price
        if (upfrontFee > price && price > 0) {
            $('#upfront_fee').addClass('error');
            $('#upfront-preview').css('color', '#d63638');
        } else {
            $('#upfront_fee').removeClass('error');
            $('#upfront-preview').css('color', '#333');
        }
    }
    
    // Bind events
    $('#duration, #price, #upfront_fee').on('input', updatePricingPreview);
    
    // Initial update
    updatePricingPreview();
    
    // Validate duration input
    $('#duration').on('change', function() {
        const value = parseInt($(this).val());
        if (value < 15) {
            $(this).val(15);
            alert('Minimum duration is 15 minutes.');
        } else if (value > 480) {
            $(this).val(480);
            alert('Maximum duration is 480 minutes (8 hours).');
        }
        updatePricingPreview();
    });
    
    // Validate price input
    $('#price').on('change', function() {
        const value = parseFloat($(this).val());
        if (value < 0) {
            $(this).val(0);
            alert('Price cannot be negative.');
        }
        updatePricingPreview();
    });
    
    // Validate upfront fee input
    $('#upfront_fee').on('change', function() {
        const upfrontFee = parseFloat($(this).val());
        const totalPrice = parseFloat($('#price').val()) || 0;
        
        if (upfrontFee < 0) {
            $(this).val(0);
            alert('Upfront fee cannot be negative.');
        } else if (upfrontFee > totalPrice && totalPrice > 0) {
            alert('Upfront fee cannot exceed the total service price.');
            $(this).focus();
        }
        updatePricingPreview();
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        const name = $('#name').val().trim();
        const duration = parseInt($('#duration').val());
        const price = parseFloat($('#price').val());
        
        if (!name) {
            alert('Please enter a service name.');
            $('#name').focus();
            e.preventDefault();
            return false;
        }
        
        if (!duration || duration < 15 || duration > 480) {
            alert('Please enter a valid duration between 15 and 480 minutes.');
            $('#duration').focus();
            e.preventDefault();
            return false;
        }
        
        if (isNaN(price) || price < 0) {
            alert('Please enter a valid price.');
            $('#price').focus();
            e.preventDefault();
            return false;
        }
        
        const upfrontFee = parseFloat($('#upfront_fee').val());
        if (isNaN(upfrontFee) || upfrontFee < 0) {
            alert('Please enter a valid upfront fee.');
            $('#upfront_fee').focus();
            e.preventDefault();
            return false;
        }
        
        if (upfrontFee > price) {
            alert('Upfront fee cannot exceed the total service price.');
            $('#upfront_fee').focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>